<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'includes/db.php';
include 'includes/score_functions.php';

$username = $_SESSION['username'];
$isAdmin = $_SESSION['is_admin'] ?? 0;

$stmt = $conn->prepare("SELECT id, username, email, level, coins, is_verified, is_admin, first_login, last_login, created_at FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: dashboard.php");
    exit();
}

$gameStats = getUserGameStats($user['id']);
$recentScores = getUserRecentScores($user['id'], 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ᜂᜄᜆ᜔: Kuwento ng Bayan — Profile</title>
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="scrollable">
<div class="bg-image"></div>
<div class="bg-overlay"></div>

<img src="assets/img/fulltitle.png"
     alt="ᜂᜄᜆ᜔: Mga Kuwento ng Bayan"
     class="full-title-fixed">

<audio id="interface-music" loop preload="auto">
    <source src="assets/mp3/theme.mp3" type="audio/mpeg">
</audio>

<div class="page-wrapper">
    <div class="top-bar">
        <div class="brand">
            <div class="brand-text">
                <p>Explorer Profile</p>
            </div>
        </div>
        <div class="user-chip">
            <span class="u"><?= htmlspecialchars($user['username']) ?></span>
            <?php if ($isAdmin): ?><span class="r">Curator</span><?php endif; ?>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h2><?= htmlspecialchars($user['username']) ?></h2>
            <p>Your heritage journey</p>
        </div>

        <div class="profile-row"><span class="k">Email</span><span class="v"><?= htmlspecialchars($user['email']) ?></span></div>
        <div class="profile-row"><span class="k">Level</span><span class="v"><?= $user['level'] ?></span></div>
        <div class="profile-row"><span class="k">Coins</span><span class="v"><?= $user['coins'] ?></span></div>
        <div class="profile-row"><span class="k">Verified</span><span class="v"><?= $user['is_verified'] ? 'Yes' : 'No' ?></span></div>
        <div class="profile-row"><span class="k">Joined</span><span class="v"><?= date('F j, Y', strtotime($user['created_at'])) ?></span></div>
        <?php if ($user['last_login']): ?>
        <div class="profile-row"><span class="k">Last Login</span><span class="v"><?= date('F j, Y g:i A', strtotime($user['last_login'])) ?></span></div>
        <?php endif; ?>

        <?php if (!empty($gameStats)): ?>
            <div class="panel-header" style="margin-top: 30px;">
                <h2 style="font-size: 32px;">Best Chapters</h2>
            </div>
            <?php foreach ($gameStats as $stat): ?>
                <div class="profile-row">
                    <span class="k"><?= htmlspecialchars($stat['game_name']) ?></span>
                    <span class="v"><?= number_format($stat['high_score']) ?> pts · <?= $stat['total_plays'] ?> plays</span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($recentScores)): ?>
            <div class="panel-header" style="margin-top: 30px;">
                <h2 style="font-size: 32px;">Recent Sessions</h2>
            </div>
            <?php foreach ($recentScores as $score): ?>
                <div class="profile-row">
                    <span class="k"><?= htmlspecialchars($score['game_name']) ?></span>
                    <span class="v"><?= number_format($score['score']) ?> pts (Lv. <?= $score['level'] ?>)</span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div style="margin-top: 24px;">
            <a href="dashboard.php" class="btn btn-block">Back to Hub</a>
        </div>
    </div>
</div>

<div class="music-controls">
    <button id="music-toggle" aria-label="Toggle music">🔊</button>
    <input type="range" id="volume-slider" min="0" max="1" step="0.01" value="0.35">
</div>

<script src="assets/js/particles.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>