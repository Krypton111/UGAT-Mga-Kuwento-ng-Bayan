<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit();
}
include 'includes/db.php';

$actionMessage = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['reset_coins'])) {
        $userId = intval($_POST['user_id']);
        $coins = intval($_POST['coins']);
        $conn->query("UPDATE users SET coins = $coins WHERE id = $userId");
        $actionMessage = "Coins updated!";
    }
    if (isset($_POST['reset_level'])) {
        $userId = intval($_POST['user_id']);
        $level = intval($_POST['level']);
        $conn->query("UPDATE users SET level = $level WHERE id = $userId");
        $actionMessage = "Level updated!";
    }
    if (isset($_POST['delete_user'])) {
        $userId = intval($_POST['user_id']);
        $conn->query("DELETE FROM users WHERE id = $userId AND is_admin = 0");
        $actionMessage = "User deleted!";
    }
    if (isset($_POST['verify_user'])) {
        $userId = intval($_POST['user_id']);
        $conn->query("UPDATE users SET is_verified = 1 WHERE id = $userId");
        $actionMessage = "User verified!";
    }
}
$users = $conn->query("SELECT id, username, email, level, coins, is_verified, is_admin, created_at, last_login FROM users ORDER BY id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ᜂᜄᜆ᜔: Curator Panel</title>
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="scrollable">
<div class="bg-image"></div>
<div class="bg-overlay"></div>

<audio id="interface-music" loop preload="auto">
    <source src="assets/mp3/theme.mp3" type="audio/mpeg">
</audio>

<div class="page-wrapper">
    <div class="top-bar">
        <div class="brand">
            <img src="assets/img/fulltitle.png" alt="ᜂᜄᜆ᜔: Mga Kuwento ng Bayan" class="full-title-sm">
            <div class="brand-text">
                <p>Curator Panel</p>
            </div>
        </div>
        <div class="user-chip">
            <span class="u"><?= htmlspecialchars($_SESSION['username']) ?></span>
            <span class="r">Curator</span>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h2>Curator Panel</h2>
            <p>Manage the chroniclers of San Isidro</p>
        </div>

        <?php if ($actionMessage): ?>
            <div class="message success" style="max-width: 100%;"><?= htmlspecialchars($actionMessage) ?></div>
        <?php endif; ?>

        <table class="lb-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Level</th>
                    <th>Coins</th>
                    <th>Verified</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $users->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= $row['level'] ?></td>
                    <td><?= $row['coins'] ?></td>
                    <td><?= $row['is_verified'] ? 'Yes' : 'No' ?></td>
                    <td><?= $row['is_admin'] ? 'Curator' : 'Explorer' ?></td>
                    <td>
                        <?php if (!$row['is_admin']): ?>
                            <form method="POST" style="display:inline-block;">
                                <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                <input type="number" name="coins" value="<?= $row['coins'] ?>" style="width:70px;">
                                <button type="submit" name="reset_coins" class="btn btn-small">Coins</button>
                            </form>
                            <form method="POST" style="display:inline-block;">
                                <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                <input type="number" name="level" value="<?= $row['level'] ?>" min="1" max="999" style="width:70px;">
                                <button type="submit" name="reset_level" class="btn btn-small">Level</button>
                            </form>
                            <?php if (!$row['is_verified']): ?>
                                <form method="POST" style="display:inline-block;">
                                    <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="verify_user" class="btn btn-small">Verify</button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline-block;">
                                <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                <button type="submit" name="delete_user" class="btn btn-small" onclick="return confirm('Delete this user?')">Delete</button>
                            </form>
                        <?php else: ?>
                            <span style="font-style: italic; opacity: 0.6;">Protected</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

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