<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'includes/db.php';
require_once 'includes/pending_cleanup.php';

$message = "";
$savedUsername = "";
$loginSuccess = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $loginInput = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $savedUsername = $loginInput;

    if (empty($loginInput) || empty($password)) {
        $message = "Please fill in all fields.";
    } else {
        $hashedPassword = hash('sha256', $password);
        $sql = "SELECT id, username, password, level, coins, is_verified, is_admin, first_login FROM users WHERE username='$loginInput' OR email='$loginInput'";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if ($user['password'] === $hashedPassword) {
                if ($user['is_verified'] == 0) {
                    $message = "Please verify your email before logging in.";
                } else {
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['level'] = $user['level'];
                    $_SESSION['coins'] = $user['coins'];
                    $_SESSION['is_admin'] = $user['is_admin'] ?? 0;

                    if ($user['first_login'] == 1) {
                        $_SESSION['first_login'] = true;
                        $conn->query("UPDATE users SET first_login = 0, last_login = NOW() WHERE id = " . $user['id']);
                    } else {
                        $_SESSION['first_login'] = false;
                        $conn->query("UPDATE users SET last_login = NOW() WHERE id = " . $user['id']);
                    }
                    // Don't redirect yet — play the exit animation, then navigate.
                    $loginSuccess = true;
                }
            } else {
                $message = "Invalid username or password.";
            }
        } else {
            $message = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ᜂᜄᜆ᜔: Mga Kuwento ng Bayan — Log In</title>
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>

        .login-wrapper {
            position: relative;
            width: 100%;
            min-height: 100vh;
            padding: 370px 6vw 60px 60px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: flex-start;
        }
        /* ------------------------------------------------------
           Login page entry animation
           The form slides in from the left and fades in on load.
           ------------------------------------------------------ */
        .form-block {
            opacity: 0;
            transform: translateX(-80px);
            animation: formEnter 0.75s cubic-bezier(.22,1,.36,1) 0.1s forwards;
        }
        @keyframes formEnter {
            0%   { opacity: 0; transform: translateX(-80px); }
            100% { opacity: 1; transform: translateX(0);     }
        }

        /* ------------------------------------------------------
           Exit animation
           ------------------------------------------------------ */
        .form-block.leaving {
            animation: formSlideOut 0.7s cubic-bezier(.55,0,.85,.3) forwards;
            pointer-events: none;
        }
        @keyframes formSlideOut {
            0%   { opacity: 1; transform: translateX(0)     scale(1);   }
            100% { opacity: 0; transform: translateX(-140%) scale(0.9); }
        }
    </style>
</head>
<body>
<div class="bg-image"></div>
<div class="bg-overlay"></div>
<div class="page-fog"></div>

<img src="assets/img/fulltitle.png"
     alt="ᜂᜄᜆ᜔: Mga Kuwento ng Bayan"
     class="full-title">

<audio id="interface-music" loop preload="auto">
    <source src="assets/mp3/theme.mp3" type="audio/mpeg">
</audio>

<div class="login-wrapper">
    <div class="form-block" id="loginFormBlock">

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="field">
                <label for="username">Email / Username</label>
                <input type="text" name="username" id="username"
                       value="<?= htmlspecialchars($savedUsername) ?>" required>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" required>
                    <button type="button" class="toggle-password" data-target="password">👁</button>
                </div>
            </div>

            <button type="submit" class="btn" id="loginBtn">Log In</button>
        </form>

        <div class="footer">
            <a href="register.php" class="exit-link">Create an account</a>
            <span class="sep">·</span>
            <a href="forgot_password.php" class="exit-link">Forgot password?</a>
        </div>
    </div>
</div>

<div class="music-controls">
    <button id="music-toggle" aria-label="Toggle music">🔊</button>
    <input type="range" id="volume-slider" min="0" max="1" step="0.01" value="0.35">
</div>

<script>
    // ----------------------------------------------------------
    // If the login succeeded, play the exit animation, then
    // navigate to dashboard.php.
    // ----------------------------------------------------------
    <?php if ($loginSuccess): ?>
    (function () {
        const block = document.getElementById('loginFormBlock');
        if (block) block.classList.add('leaving');
        document.body.classList.add('leaving');
        setTimeout(function () {
            window.location.href = 'dashboard.php';
        }, 1300);
    })();
    <?php endif; ?>

    // ----------------------------------------------------------
    // Exit transition: any ".exit-link" plays the slide-out
    // animation, then navigates to the href.
    // ----------------------------------------------------------
    document.querySelectorAll('.exit-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const target = link.getAttribute('href');
            if (!target) return;

            const block = document.getElementById('loginFormBlock');
            if (block) block.classList.add('leaving');
            document.body.classList.add('leaving');

            setTimeout(function () {
                window.location.href = target;
            }, 1300);
        });
    });
</script>

<script src="assets/js/particles.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>