#!/usr/bin/env python3
"""Apply account-specific Kultura't Tradisyon achievements to a local UGAT checkout.

Run from the repository root:
    python apply_profile_achievements.py

This edits only the local checkout. It does not contact or modify GitHub.
"""
from pathlib import Path
import shutil
import sys
import re

ROOT = Path.cwd()
PROFILE = ROOT / "profile.php"
TEMPLATE_DIR = Path(__file__).parent / "patch_templates"
ENDPOINT_SOURCE = TEMPLATE_DIR / "save_game_achievement.php"
SQL_SOURCE = TEMPLATE_DIR / "player_achievements.sql"

if not PROFILE.is_file():
    sys.exit("ERROR: profile.php not found. Run this script from the UGAT repository root.")
if not (ROOT / "Includes" / "db.php").is_file():
    sys.exit("ERROR: Includes/db.php not found. This does not look like the UGAT repository root.")

original = PROFILE.read_text(encoding="utf-8")

php_anchor_pattern = re.compile(r"^[ \t]*\$userId\s*=\s*\(int\)\$user\['id'\];[ \t]*$", re.MULTILINE)
php_insert = r"""$userId = (int)$user['id'];

// Kultura't Tradisyon achievement catalogue. The IDs must match the Godot game.
$achievementCatalog = [
    'heritage_collector' => [
        'title' => 'A Curious Mind',
        'description' => 'Collected all heritage objects and received the salakot.',
        'icon' => '📜',
    ],
    'ingredients_in_pot' => [
        'title' => 'A Taste of Home',
        'description' => 'Collected the required ingredients and placed them in the pot.',
        'icon' => '🍲',
    ],
    'collect_eggs' => [
        'title' => 'A Helping Hand',
        'description' => 'Collected the eggs from the chicken coop.',
        'icon' => '🥚',
    ],
    'ending_city' => [
        'title' => 'Back to the City',
        'description' => 'Completed the story by choosing to return to the city.',
        'icon' => '🏙️',
    ],
    'ending_stay' => [
        'title' => 'A Place to Belong',
        'description' => 'Completed the story by choosing to help Lola Nena.',
        'icon' => '🏡',
    ],
];

$unlockedAchievements = [];
$achievementTableCheck = $conn->query("SHOW TABLES LIKE 'player_achievements'");
if ($achievementTableCheck && $achievementTableCheck->num_rows > 0) {
    $achievementStmt = $conn->prepare(
        'SELECT achievement_id, unlocked_at
         FROM player_achievements
         WHERE user_id = ? AND game_name = ?
         ORDER BY unlocked_at DESC'
    );
    if ($achievementStmt) {
        $achievementGameName = 'Kultura at Tradisyon';
        $achievementStmt->bind_param('is', $userId, $achievementGameName);
        if ($achievementStmt->execute()) {
            $achievementResult = $achievementStmt->get_result();
            while ($achievementRow = $achievementResult->fetch_assoc()) {
                $achievementId = $achievementRow['achievement_id'];
                if (isset($achievementCatalog[$achievementId])) {
                    $unlockedAchievements[$achievementId] = $achievementRow['unlocked_at'];
                }
            }
        }
        $achievementStmt->close();
    }
}

"""
if len(list(php_anchor_pattern.finditer(original))) != 1:
    sys.exit("ERROR: Could not safely find the profile user ID line (expected exactly one match). No files changed.")

css_anchor = "        @media (max-width:600px) {"
css_insert = r"""        .achievement-progress {
            margin: -4px 0 18px;
            color: rgba(247,244,237,.72);
            font-size: 16px;
        }
        .achievement-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .achievement-card {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            min-width: 0;
            padding: 18px;
            border: 1px solid rgba(218,192,143,.32);
            border-radius: 12px;
            background: linear-gradient(145deg, rgba(218,192,143,.10), rgba(255,255,255,.025));
        }
        .achievement-icon {
            display: grid;
            place-items: center;
            flex: 0 0 48px;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(218,192,143,.15);
            font-size: 25px;
        }
        .achievement-card h3 {
            margin: 0 0 6px;
            color: #f7e7bd;
            font-size: 18px;
            line-height: 1.35;
        }
        .achievement-card p {
            margin: 0;
            color: rgba(247,244,237,.72);
            font-size: 15px;
            line-height: 1.55;
        }
        .achievement-card .achievement-date {
            display: block;
            margin-top: 9px;
            color: rgba(247,244,237,.52);
            font-size: 13px;
        }
        .achievement-card.is-locked { opacity: .62; background: rgba(255,255,255,.015); border-style: dashed; }
        .achievement-card.is-locked .achievement-icon { filter: grayscale(1); background: rgba(255,255,255,.04); }
        .achievement-status { display: inline-block; margin-top: 9px; color: #dac08f; font-size: 12px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; }
        .achievement-card.is-locked .achievement-status { color: rgba(247,244,237,.55); }
        .achievement-empty {
            padding: 20px;
            border: 1px dashed rgba(218,192,143,.28);
            border-radius: 10px;
            color: rgba(247,244,237,.72);
            line-height: 1.7;
        }
        @media (max-width:600px) {"""
if original.count(css_anchor) != 1:
    sys.exit("ERROR: Could not safely find the profile CSS insertion point (expected exactly one match). No files changed.")

section_anchor_pattern = re.compile(r'^[ \t]*<section class="profile-section">\s*<h2>Player Details</h2>', re.MULTILINE)
section_insert = r"""        <section class="profile-section" id="achievements">
            <h2>Achievements</h2>
            <p class="achievement-progress">
                <?= number_format(count($unlockedAchievements)) ?> of <?= number_format(count($achievementCatalog)) ?> achievements unlocked
            </p>
            <div class="achievement-grid">
                <?php foreach ($achievementCatalog as $achievementId => $achievement): ?>
                    <?php $isUnlocked = array_key_exists($achievementId, $unlockedAchievements); ?>
                    <article class="achievement-card<?= $isUnlocked ? '' : ' is-locked' ?>">
                        <div class="achievement-icon" aria-hidden="true"><?= htmlspecialchars($achievement['icon'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div>
                            <h3><?= htmlspecialchars($achievement['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                            <p><?= htmlspecialchars($achievement['description'], ENT_QUOTES, 'UTF-8') ?></p>
                            <?php if ($isUnlocked): ?>
                                <span class="achievement-status">Unlocked</span>
                                <span class="achievement-date"><?= htmlspecialchars(profileDate($unlockedAchievements[$achievementId], 'M j, Y g:i A'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php else: ?>
                                <span class="achievement-status">Locked</span>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="profile-section">
            <h2>Player Details</h2>"""
if len(list(section_anchor_pattern.finditer(original))) != 1:
    sys.exit("ERROR: Could not safely find the Player Details section (expected exactly one match). No files changed.")

updated = php_anchor_pattern.sub(lambda match: php_insert, original, count=1)
updated = updated.replace(css_anchor, css_insert, 1)
updated = section_anchor_pattern.sub(lambda match: section_insert, updated, count=1)

# Prepare all destination files and backups before writing any changes.
dest_endpoint = ROOT / "save_game_achievement.php"
dest_sql = ROOT / "database" / "player_achievements.sql"
if dest_endpoint.exists() and dest_endpoint.read_bytes() != ENDPOINT_SOURCE.read_bytes():
    sys.exit("ERROR: save_game_achievement.php already exists with different contents. Back it up and review before rerunning.")
if dest_sql.exists() and dest_sql.read_bytes() != SQL_SOURCE.read_bytes():
    sys.exit("ERROR: database/player_achievements.sql already exists with different contents. Back it up and review before rerunning.")

backup = PROFILE.with_suffix(".php.achievement-backup")
if not backup.exists():
    shutil.copy2(PROFILE, backup)

dest_endpoint.parent.mkdir(parents=True, exist_ok=True)
dest_sql.parent.mkdir(parents=True, exist_ok=True)
PROFILE.write_text(updated, encoding="utf-8")
shutil.copy2(ENDPOINT_SOURCE, dest_endpoint)
shutil.copy2(SQL_SOURCE, dest_sql)

print("Profile achievements patch applied to this local checkout.")
print("Backup: profile.php.achievement-backup")
print("Created: save_game_achievement.php")
print("Created: database/player_achievements.sql")
print("Next: run database/player_achievements.sql in the game_launcher database.")
print("Then connect the Godot achievement manager to save_game_achievement.php.")
print("No GitHub requests, commits, or pushes were made.")
