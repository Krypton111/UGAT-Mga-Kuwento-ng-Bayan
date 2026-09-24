<?php
session_start();
include 'includes/db.php';
require_once 'includes/pending_cleanup.php';   // CHANGED
require_once 'includes/send_email.php';

$message = "";
$success = false;
$showVerification = false;
$registeredEmail = "";
$savedUsername = "";
$savedEmail = "";
$justVerified = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ==========================================================
    // VERIFICATION CODE SUBMISSION
    // ==========================================================
    if (isset($_POST['verify_code'])) {
        $code = trim($_POST['verification_code']);
        $email = trim($_POST['email']);

        $registeredEmail = $email;   // keep for re-show on failure

        // Look up the pending signup.
        $stmt = $conn->prepare("
            SELECT id, username, email, password, verification_expires
            FROM pending_users
            WHERE email = ? AND verification_code = ?
        ");
        $stmt->bind_param("ss", $email, $code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $pending = $result->fetch_assoc();
            $expiry = strtotime($pending['verification_expires']);

            if (time() > $expiry) {
                $message = "Verification code has expired. Please request a new one.";
                $showVerification = true;
            } else {
                // Move the pending row into the real users table.
                $insert = $conn->prepare("
                    INSERT INTO users (username, email, password, is_verified, first_login)
                    VALUES (?, ?, ?, 1, 1)
                ");
                $insert->bind_param("sss", $pending['username'], $pending['email'], $pending['password']);

                if ($insert->execute()) {
                    $newUserId = $conn->insert_id;

                    // Delete the pending row now that it's been promoted.
                    $del = $conn->prepare("DELETE FROM pending_users WHERE id = ?");
                    $del->bind_param("i", $pending['id']);
                    $del->execute();

                    $success = true;
                    $justVerified = true;

                    $userData = $conn->query("SELECT username, level, coins, is_admin FROM users WHERE id = " . $newUserId)->fetch_assoc();
                    $_SESSION['username'] = $userData['username'];
                    $_SESSION['level'] = $userData['level'];
                    $_SESSION['coins'] = $userData['coins'];
                    $_SESSION['is_admin'] = $userData['is_admin'] ?? 0;
                    $_SESSION['first_login'] = true;

                    $showVerification = false;
                } else {
                    $message = "Error creating your account. Please try again.";
                    $showVerification = true;
                }
            }
        } else {
            // Wrong code — keep the verification form visible so the user can retry.
            $message = "Invalid verification code. Please try again.";
            $showVerification = true;
        }

    // ==========================================================
    // MAIN REGISTRATION FORM SUBMISSION
    // ==========================================================
    } else {
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $confirm = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        $savedUsername = $username;
        $savedEmail = $email;

        if (empty($username) || empty($email) || empty($password) || empty($confirm)) {
            $message = "All fields are required!";
        } elseif ($password !== $confirm) {
            $message = "Passwords do not match!";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Invalid email address!";
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&_\-])[A-Za-z\d@$!%*?&_\-]{8,16}$/', $password)) {
            $message = "Password must be 8-16 characters and contain:<br>
                        • Uppercase letter (A-Z)<br>
                        • Lowercase letter (a-z)<br>
                        • Number (0-9)<br>
                        • Special character (@$!%*?&_-)";
        } else {
            // --- Check for existing verified accounts ---
            $checkUsers = $conn->prepare("SELECT username, email FROM users WHERE username = ? OR email = ?");
            $checkUsers->bind_param("ss", $username, $email);
            $checkUsers->execute();
            $usersResult = $checkUsers->get_result();

            $blocked = false;
            if ($usersResult->num_rows > 0) {
                $row = $usersResult->fetch_assoc();
                if ($row['username'] == $username) {
                    $message = "Username already taken!";
                } else {
                    $message = "Email already registered!";
                }
                $blocked = true;
            }

            // --- Check for existing pending signups ---
            if (!$blocked) {
                $checkPending = $conn->prepare("SELECT id, username, email FROM pending_users WHERE username = ? OR email = ?");
                $checkPending->bind_param("ss", $username, $email);
                $checkPending->execute();
                $pendingResult = $checkPending->get_result();

                if ($pendingResult->num_rows > 0) {
                    // Either same user retrying or a genuine conflict.
                    // If the email matches, we take over their pending row (refresh the code).
                    // Otherwise, we treat it as a conflict.
                    $pendingRow = $pendingResult->fetch_assoc();

                    if ($pendingRow['email'] === $email) {
                        // Same email — refresh the pending signup.
                        $verificationCode = sprintf("%06d", mt_rand(100000, 999999));
                        $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                        $hashed = hash('sha256', $password);

                        $updatePending = $conn->prepare("
                            UPDATE pending_users
                            SET username = ?, password = ?, verification_code = ?, verification_expires = ?, created_at = NOW()
                            WHERE id = ?
                        ");
                        $updatePending->bind_param("ssssi", $username, $hashed, $verificationCode, $expires, $pendingRow['id']);

                        if ($updatePending->execute() && sendVerificationEmail($email, $username, $verificationCode)) {
                            $success = true;
                            $showVerification = true;
                            $registeredEmail = $email;
                            $message = "A new 6-digit verification code has been sent to your email.";
                        } else {
                            $message = "Failed to send verification email. Please try again.";
                        }
                        $blocked = true;
                    } else {
                        // Username or a different email is already pending.
                        if ($pendingRow['username'] == $username) {
                            $message = "Username is currently pending verification. Please choose another.";
                        } else {
                            $message = "Email is currently pending verification. Please check your inbox.";
                        }
                        $blocked = true;
                    }
                }
            }

            // --- Create a brand-new pending signup ---
            if (!$blocked) {
                $verificationCode = sprintf("%06d", mt_rand(100000, 999999));
                $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                $hashed = hash('sha256', $password);

                $insert = $conn->prepare("
                    INSERT INTO pending_users (username, email, password, verification_code, verification_expires)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $insert->bind_param("sssss", $username, $email, $hashed, $verificationCode, $expires);

                if ($insert->execute()) {
                    if (sendVerificationEmail($email, $username, $verificationCode)) {
                        $success = true;
                        $showVerification = true;
                        $registeredEmail = $email;
                        $message = "A 6-digit verification code has been sent to your email.";
                    } else {
                        // Roll back the pending row if we couldn't send the email.
                        $newId = $conn->insert_id;
                        $conn->query("DELETE FROM pending_users WHERE id = $newId");
                        $message = "Failed to send verification email. Please try again later.";
                        $success = false;
                        $showVerification = false;
                    }
                } else {
                    $message = "Error: " . $conn->error;
                }
            }
        }
    }
}

// Body is only scrollable when we're showing the main registration form.
$isScrollable = (!$showVerification && !$justVerified);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ᜂᜄᜆ᜔: Mga Kuwento ng Bayan — Register</title>
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-wrapper {
            position: relative;
            width: 100%;
            min-height: 100vh;
            padding: 420px 6vw 60px 60px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: flex-start;
        }
        .form-block {
            opacity: 0;
            transform: translateX(-80px);
            animation: formEnter 0.75s cubic-bezier(.22,1,.36,1) 0.1s forwards;
        }
        @keyframes formEnter {
            0%   { opacity: 0; transform: translateX(-80px); }
            100% { opacity: 1; transform: translateX(0);     }
        }

        .form-block.leaving {
            animation: formSlideOut 0.7s cubic-bezier(.55,0,.85,.3) forwards;
            pointer-events: none;
        }
        @keyframes formSlideOut {
            0%   { opacity: 1; transform: translateX(0)     scale(1);   }
            100% { opacity: 0; transform: translateX(-140%) scale(0.9); }
        }

        .verified-card {
            animation: cardEnter 0.7s cubic-bezier(.22,1,.36,1) 0.05s both;
        }
        @keyframes cardEnter {
            0%   { opacity: 0; transform: translateX(-80px); }
            100% { opacity: 1; transform: translateX(0);     }
        }
    </style>
</head>
<body class="<?= $isScrollable ? 'scrollable' : '' ?>">
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
    <div class="form-block" id="regFormBlock">

        <?php if ($message && !$showVerification && !$justVerified): ?>
            <div class="message <?= $success ? 'success' : '' ?>"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($justVerified): ?>

            <div class="verified-card" id="verifiedCard">
                <div class="verified-check">✓</div>
                <h2>You are now in!</h2>
                <p>Welcome to San Isidro, <?= htmlspecialchars($_SESSION['username']) ?>.</p>
                <a href="dashboard.php" class="btn exit-link">Enter the Hub</a>
            </div>

        <?php elseif ($showVerification): ?>

            <form method="POST" id="verifyForm">
                <input type="hidden" name="email" value="<?= htmlspecialchars($registeredEmail) ?>">

                <?php if ($message): ?>
                    <div class="message <?= $success ? 'success' : '' ?>"><?= $message ?></div>
                <?php endif; ?>

                <div class="field">
                    <label for="verification_code">6-Digit Verification Code</label>
                    <input type="text" name="verification_code" id="verification_code" class="code-input"
                        placeholder="123456" maxlength="6" pattern="[0-9]{6}" inputmode="numeric" required autofocus>
                </div>
                <button type="submit" name="verify_code" class="btn">Verify Email</button>
            </form>

            <div class="footer">
                Already have an account? <a href="login.php" class="exit-link">Log in</a>
            </div>

        <?php else: ?>

            <form method="POST" id="registerForm">
                <div class="field">
                    <label for="username">Username</label>
                    <input type="text" name="username" id="username" value="<?= htmlspecialchars($savedUsername) ?>" required>
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" value="<?= htmlspecialchars($savedEmail) ?>" required>
                </div>
                <div class="field">
                    <label for="reg-password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="reg-password" maxlength="16" required
                               onkeyup="checkPasswordStrength(this.value)"
                               oncopy="return false" oncut="return false" onpaste="return false">
                        <button type="button" class="toggle-password" data-target="reg-password">👁</button>
                    </div>
                    <div class="password-requirements" id="passwordRequirements">
                        <small>Password must contain:</small>
                        <ul>
                            <li id="req-length"><span class="invalid">✗</span> 8–16 characters</li>
                            <li id="req-uppercase"><span class="invalid">✗</span> Uppercase letter (A–Z)</li>
                            <li id="req-lowercase"><span class="invalid">✗</span> Lowercase letter (a–z)</li>
                            <li id="req-number"><span class="invalid">✗</span> Number (0–9)</li>
                            <li id="req-special"><span class="invalid">✗</span> Special character (@$!%*?&_-)</li>
                        </ul>
                    </div>
                    <div class="password-length" id="passwordLength">
                        Characters: <span id="charCount" class="invalid">0</span>/16
                    </div>
                </div>
                <div class="field">
                    <label for="reg-confirm">Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" id="reg-confirm" maxlength="16" required
                               oncopy="return false" oncut="return false" onpaste="return false">
                        <button type="button" class="toggle-password" data-target="reg-confirm">👁</button>
                    </div>
                </div>
                <button type="submit" class="btn" id="registerBtn" disabled>Create Account</button>
            </form>

            <div class="footer">
                Already have an account? <a href="login.php" class="exit-link">Log in</a>
            </div>

        <?php endif; ?>
    </div>
</div>

<div class="music-controls">
    <button id="music-toggle" aria-label="Toggle music">🔊</button>
    <input type="range" id="volume-slider" min="0" max="1" step="0.01" value="0.35">
</div>

<script>
    function checkPasswordStrength(password) {
        const requirements = {
            length:    password.length >= 8 && password.length <= 16,
            uppercase: /[A-Z]/.test(password),
            lowercase: /[a-z]/.test(password),
            number:    /\d/.test(password),
            special:   /[@$!%*?&_\-]/.test(password)
        };
        const reqIds = {
            length: 'req-length', uppercase: 'req-uppercase', lowercase: 'req-lowercase',
            number: 'req-number', special: 'req-special'
        };
        let allValid = true;
        for (const key of Object.keys(requirements)) {
            const el = document.getElementById(reqIds[key]);
            if (!el) continue;
            const icon = el.querySelector('span');
            if (requirements[key]) { icon.className = 'valid'; icon.textContent = '✓'; }
            else { icon.className = 'invalid'; icon.textContent = '✗'; allValid = false; }
        }
        const charCount = document.getElementById('charCount');
        if (charCount) {
            const n = password.length;
            charCount.textContent = n;
            charCount.className = (n >= 8 && n <= 16) ? 'valid' : 'invalid';
        }
        const btn = document.getElementById('registerBtn');
        if (btn) btn.disabled = !allValid;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('reg-password');
        if (input) checkPasswordStrength(input.value);

        const registerForm = document.getElementById('registerForm');
        if (registerForm) {
            registerForm.addEventListener('submit', function () {
                if (window.Loading) window.Loading.show('Sending the letter…');
            });
        }

        const verifyForm = document.getElementById('verifyForm');
        if (verifyForm) {
            verifyForm.addEventListener('submit', function () {
                if (window.Loading) window.Loading.show('Opening the gate…');
            });
        }

        const card = document.getElementById('verifiedCard');
        if (card) {
            requestAnimationFrame(() => card.classList.add('show'));
        }
    });

    document.querySelectorAll('.exit-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const target = link.getAttribute('href');
            if (!target) return;

            const block = document.getElementById('regFormBlock');
            if (block) block.classList.add('leaving');
            document.body.classList.add('leaving');

            setTimeout(function () {
                window.location.href = target;
            }, 1300);
        });
    });

    (function () {
        const formBlock = document.getElementById('regFormBlock');
        const title = document.querySelector('.full-title');
        if (!formBlock || !title) return;
        if (!document.body.classList.contains('scrollable')) return;

        function updateMask() {
            const formRect = formBlock.getBoundingClientRect();
            const titleRect = title.getBoundingClientRect();
            const overlap = titleRect.bottom - formRect.top;

            if (overlap <= 0) {
                formBlock.style.setProperty('--mask-start', '-200px');
            } else {
                formBlock.style.setProperty('--mask-start', overlap + 'px');
            }
        }

        window.addEventListener('scroll', updateMask, { passive: true });
        window.addEventListener('resize', updateMask);
        updateMask();
    })();
</script>

<script src="assets/js/particles.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>