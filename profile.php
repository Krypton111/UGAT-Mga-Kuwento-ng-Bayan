<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require_once 'Includes/db.php';
require_once 'Includes/score_functions.php';

$username = $_SESSION['username'];

$stmt = $conn->prepare("SELECT id, username, email, level, coins, is_verified, is_admin, first_login, last_login, created_at FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: dashboard.php");
    exit();
}

$userId = (int)$user['id'];

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

$gameStats = getUserGameStats($userId);
$recentScores = getUserRecentScores($userId, 5);
$totalPlays = 0;
foreach ($gameStats as $stat) {
    $totalPlays += (int)($stat['total_plays'] ?? 0);
}

// The completion-time table is optional. Only query it if it exists.
$bestTime = null;
$latestCompletion = null;
$tableCheck = $conn->query("SHOW TABLES LIKE 'game_runs'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $timeStmt = $conn->prepare("SELECT MIN(elapsed_seconds) AS best_time FROM game_runs WHERE user_id = ? AND game_name = ?");
    if ($timeStmt) {
        $gameName = 'Kultura at Tradisyon';
        $timeStmt->bind_param("is", $userId, $gameName);
        if ($timeStmt->execute()) {
            $timeRow = $timeStmt->get_result()->fetch_assoc();
            if ($timeRow && $timeRow['best_time'] !== null) {
                $bestTime = (int)$timeRow['best_time'];
            }
        }
        $timeStmt->close();
    }

    $latestStmt = $conn->prepare("SELECT completed_at FROM game_runs WHERE user_id = ? AND game_name = ? ORDER BY completed_at DESC LIMIT 1");
    if ($latestStmt) {
        $gameName = 'Kultura at Tradisyon';
        $latestStmt->bind_param("is", $userId, $gameName);
        if ($latestStmt->execute()) {
            $latestRow = $latestStmt->get_result()->fetch_assoc();
            $latestCompletion = $latestRow['completed_at'] ?? null;
        }
        $latestStmt->close();
    }
}

function formatDuration($seconds) {
    $seconds = max(0, (int)$seconds);
    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $remainingSeconds = $seconds % 60;
    if ($hours > 0) {
        return sprintf('%d:%02d:%02d', $hours, $minutes, $remainingSeconds);
    }
    return sprintf('%02d:%02d', $minutes, $remainingSeconds);
}

function profileDate($value, $format = 'F j, Y') {
    return !empty($value) ? date($format, strtotime($value)) : '—';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ᜂᜄᜆ᜔: Kultura’t Tradisyon — Player Profile</title>
    <link rel="icon" type="image/x-icon" href="Assets/img/favicon.ico">
    <link rel="stylesheet" href="Assets/css/style.css">
    <style>
        .player-profile { width: min(900px, 100%); margin: 0 auto; }

        /* Match the site's directional page transitions. */
        .player-profile {
            opacity: 0;
            transform: translateX(90px);
            animation: profilePageEnter .85s cubic-bezier(.22,1,.36,1) .08s forwards;
            will-change: opacity, transform;
        }
        @keyframes profilePageEnter {
            from { opacity: 0; transform: translateX(90px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        body.profile-leaving .player-profile {
            animation: profilePageExit .65s cubic-bezier(.55,0,.85,.3) forwards;
            pointer-events: none;
        }
        @keyframes profilePageExit {
            from { opacity: 1; transform: translateX(0); }
            to   { opacity: 0; transform: translateX(110px); }
        }
        @media (prefers-reduced-motion: reduce) {
            .player-profile { animation-duration: .01ms !important; animation-delay: 0ms !important; }
            body.profile-leaving .player-profile { animation-duration: .01ms !important; }
        }

        .profile-hero {
            display: flex; align-items: center; gap: 20px; padding: 28px;
            border: 1px solid rgba(218,192,143,.32); border-radius: 16px;
            background: rgba(20,27,38,.82); margin-bottom: 20px;
        }
        .profile-avatar {
            display: grid; place-items: center; flex: 0 0 76px; width: 76px; height: 76px;
            border-radius: 50%; background: linear-gradient(145deg,#dec08f,#a78043);
            color: #20202a; font-size: 32px; font-weight: 700; text-transform: uppercase;
            box-shadow: 0 0 26px rgba(218,192,143,.18);
        }
        .profile-hero h1 { margin: 0 0 6px; color: #f8edcf; font-size: clamp(26px,4vw,38px); overflow-wrap: anywhere; }
        .profile-hero p { margin: 0; color: rgba(247,244,237,.76); font-size: 18px; line-height: 1.6; }
        .profile-tag {
            display: inline-block; margin-top: 10px; padding: 5px 10px; border-radius: 999px;
            border: 1px solid rgba(218,192,143,.35); color: #e5c98d; font-size: 14px;
        }
        .profile-section {
            padding: 24px; margin-bottom: 20px; border: 1px solid rgba(218,192,143,.24);
            border-radius: 14px; background: rgba(20,27,38,.76);
        }
        .profile-section h2 { margin: 0 0 18px; color: #f8edcf; font-size: 26px; }
        .profile-grid { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 12px; }
        .profile-stat {
            min-width: 0; padding: 18px; border-radius: 10px;
            border: 1px solid rgba(218,192,143,.18); background: rgba(255,255,255,.035);
        }
        .profile-stat .label { display:block; margin-bottom: 8px; color: rgba(247,244,237,.65); font-size: 16px; }
        .profile-stat .value { display:block; color: #f7e7bd; font-size: 25px; font-weight: 600; overflow-wrap: anywhere; }
        .profile-stat .subvalue { display:block; margin-top: 5px; color: rgba(247,244,237,.6); font-size: 14px; }
        .profile-row { display:flex; justify-content:space-between; align-items:flex-start; gap:20px; padding:13px 0; border-bottom:1px solid rgba(218,192,143,.14); }
        .profile-row:last-child { border-bottom:0; }
        .profile-row .k { color:rgba(247,244,237,.68); font-size:17px; }
        .profile-row .v { color:#f7e7bd; font-size:17px; text-align:right; overflow-wrap:anywhere; }
        .profile-empty { color:rgba(247,244,237,.7); font-size:17px; line-height:1.7; }
        .profile-actions { display:flex; gap:12px; flex-wrap:wrap; margin-top:22px; }
        .profile-actions .btn { flex:1 1 180px; text-align:center; }
        .achievement-progress {
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
        .achievement-empty {
            padding: 20px;
            border: 1px dashed rgba(218,192,143,.28);
            border-radius: 10px;
            color: rgba(247,244,237,.72);
            line-height: 1.7;
        }
        .achievement-progress {
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
        @media (max-width:600px) {
            .profile-hero { align-items:flex-start; padding:20px; gap:14px; }
            .profile-avatar { flex-basis:58px; width:58px; height:58px; font-size:25px; }
            .profile-section { padding:18px; }
            .profile-grid { grid-template-columns:1fr 1fr; gap:8px; }
            .profile-stat { padding:13px; }
            .profile-stat .value { font-size:22px; }
            .profile-row .k, .profile-row .v { font-size:15px; }
            .profile-hero p { font-size:16px; }
            .profile-row { gap:12px; }
        }
        @media (max-width:390px) { .profile-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body class="scrollable">
<div class="bg-image"></div>
<div class="bg-overlay"></div>

<audio id="interface-music" loop preload="auto">
    <source src="Assets/mp3/system%20music.mp3" type="audio/mpeg">
</audio>

<div class="page-wrapper">
    <main class="player-profile">
        <section class="profile-hero">
            <div class="profile-avatar" aria-hidden="true"><?= htmlspecialchars(mb_substr($user['username'], 0, 1, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?></div>
            <div>
                <h1><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></h1>
                <p>Your account and progress in ᜂᜄᜆ᜔: Kultura’t Tradisyon.</p>
                <span class="profile-tag"><?= !empty($user['is_admin']) ? 'Curator Account' : 'Player Account' ?></span>
            </div>
        </section>

        <section class="profile-section">
            <h2>Player Overview</h2>
            <div class="profile-grid">
                <div class="profile-stat"><span class="label">Player Level</span><span class="value"><?= number_format((int)$user['level']) ?></span></div>
                <div class="profile-stat"><span class="label">Coins</span><span class="value"><?= number_format((int)$user['coins']) ?></span></div>
                <div class="profile-stat"><span class="label">Total Recorded Plays</span><span class="value"><?= number_format($totalPlays) ?></span></div>
                <div class="profile-stat"><span class="label">Games With Records</span><span class="value"><?= number_format(count($gameStats)) ?></span></div>
                <div class="profile-stat">
                    <span class="label">Best Kultura at Tradisyon Time</span>
                    <span class="value"><?= $bestTime !== null ? htmlspecialchars(formatDuration($bestTime), ENT_QUOTES, 'UTF-8') : '—' ?></span>
                    <span class="subvalue"><?= $bestTime !== null ? 'Fastest recorded completion' : 'No completion time recorded yet' ?></span>
                </div>
                <div class="profile-stat">
                    <span class="label">Email Status</span>
                    <span class="value"><?= !empty($user['is_verified']) ? 'Verified' : 'Unverified' ?></span>
                    <span class="subvalue"><?= !empty($user['is_verified']) ? 'Your email is verified' : 'Email verification is still pending' ?></span>
                </div>
            </div>
        </section>

        <section class="profile-section" id="achievements">
            <h2>Achievements</h2>
            <p class="achievement-progress">
                <?= number_format(count($unlockedAchievements)) ?> of <?= number_format(count($achievementCatalog)) ?> achievements unlocked
            </p>
            <?php if (!empty($unlockedAchievements)): ?>
                <div class="achievement-grid">
                    <?php foreach ($unlockedAchievements as $achievementId => $unlockedAt): ?>
                        <?php $achievement = $achievementCatalog[$achievementId]; ?>
                        <article class="achievement-card">
                            <div class="achievement-icon" aria-hidden="true"><?= htmlspecialchars($achievement['icon'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div>
                                <h3><?= htmlspecialchars($achievement['title'], ENT_QUOTES, 'UTF-8') ?></h3>
                                <p><?= htmlspecialchars($achievement['description'], ENT_QUOTES, 'UTF-8') ?></p>
                                <span class="achievement-date">Unlocked <?= htmlspecialchars(profileDate($unlockedAt, 'M j, Y g:i A'), ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="achievement-empty">
                    You have not unlocked any Kultura at Tradisyon achievements yet. Play the story and complete its challenges to start your collection.
                </div>
            <?php endif; ?>
        </section>

        <section class="profile-section" id="achievements">
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
            <h2>Player Details</h2>
            <div class="profile-row"><span class="k">Username</span><span class="v"><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="profile-row"><span class="k">Email Address</span><span class="v"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="profile-row"><span class="k">Account Created</span><span class="v"><?= htmlspecialchars(profileDate($user['created_at']), ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="profile-row"><span class="k">First Login</span><span class="v"><?= htmlspecialchars(profileDate($user['first_login'], 'F j, Y g:i A'), ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="profile-row"><span class="k">Last Login</span><span class="v"><?= htmlspecialchars(profileDate($user['last_login'], 'F j, Y g:i A'), ENT_QUOTES, 'UTF-8') ?></span></div>
            <?php if ($latestCompletion): ?>
                <div class="profile-row"><span class="k">Last Kultura at Tradisyon Completion</span><span class="v"><?= htmlspecialchars(profileDate($latestCompletion, 'F j, Y g:i A'), ENT_QUOTES, 'UTF-8') ?></span></div>
            <?php endif; ?>
        </section>

        <section class="profile-section">
            <h2>Game Records</h2>
            <?php if (!empty($gameStats)): ?>
                <?php foreach ($gameStats as $stat): ?>
                    <div class="profile-row">
                        <span class="k"><?= htmlspecialchars($stat['game_name'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="v"><?= number_format((int)$stat['high_score']) ?> pts · <?= number_format((int)$stat['total_plays']) ?> plays</span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="profile-empty">Your game records will appear here after your progress is recorded.</p>
            <?php endif; ?>
        </section>

        <section class="profile-section">
            <h2>Recent Activity</h2>
            <?php if (!empty($recentScores)): ?>
                <?php foreach ($recentScores as $score): ?>
                    <div class="profile-row">
                        <span class="k"><?= htmlspecialchars($score['game_name'], ENT_QUOTES, 'UTF-8') ?><br><small><?= htmlspecialchars(profileDate($score['created_at'], 'M j, Y g:i A'), ENT_QUOTES, 'UTF-8') ?></small></span>
                        <span class="v"><?= number_format((int)$score['score']) ?> pts<?= isset($score['level']) && $score['level'] !== null ? ' · Lv. ' . (int)$score['level'] : '' ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="profile-empty">There is no recent game activity to display yet.</p>
            <?php endif; ?>
        </section>

        <div class="profile-actions">
            <a href="change_password.php" class="btn btn-block">Change Password</a>
            <a href="dashboard.php" class="btn btn-block">Back to Dashboard</a>
        </div>
    </main>
</div>



<script>
(function () {
    'use strict';
    let navigating = false;
    document.querySelectorAll('a[href]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            if (navigating || event.defaultPrevented || event.button !== 0 ||
                event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ||
                link.target === '_blank' || link.hasAttribute('download')) return;

            const destination = new URL(link.href, window.location.href);
            if (destination.origin !== window.location.origin ||
                destination.href === window.location.href) return;

            event.preventDefault();
            navigating = true;
            document.body.classList.add('profile-leaving');
            window.setTimeout(function () {
                window.location.href = destination.href;
            }, window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 20 : 650);
        });
    });
})();
</script>
<script src="Assets/js/particles.js"></script>
<script src="Assets/js/app.js"></script>
</body>
</html>
