<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'includes/db.php';
require_once 'includes/pending_cleanup.php';
require_once 'includes/send_email.php';

$message = "";
$success = false;
$showResetForm = false;
$resetEmail = "";
$step = 'request';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (isset($_POST['request_code'])) {
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $resetEmail = $email;

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $message = "No account found with that email address.";
        } else {
            $user = $result->fetch_assoc();
            $userId = $user['id'];

            $resetCode = sprintf("%06d", mt_rand(100000, 999999));
            $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            $update = $conn->prepare("UPDATE users SET reset_code = ?, reset_code_expires = ? WHERE id = ?");
            $update->bind_param("ssi", $resetCode, $expires, $userId);

            if ($update->execute()) {
                if (sendPasswordResetCode($email, $resetCode)) {
                    $success = true;
                    $showResetForm = true;
                    $resetEmail = $email;
                    $step = 'verify';
                    $message = "A 6-digit reset code has been sent to your email.";
                } else {
                    $message = "Failed to send reset code. Please try again.";
                }
            } else {
                $message = "Database error. Please try again.";
            }
        }
    }

    if (isset($_POST['reset_password'])) {
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $code = isset($_POST['reset_code']) ? trim($_POST['reset_code']) : '';
        $newPassword = isset($_POST['password']) ? $_POST['password'] : '';
        $confirm = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        if (empty($newPassword) || empty($confirm)) {
            $message = "Please fill in both password fields.";
        } elseif ($newPassword !== $confirm) {
            $message = "Passwords do not match.";
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&_\-])[A-Za-z\d@$!%*?&_\-]{8,16}$/', $newPassword)) {
            $message = "Password must be 8-16 characters with uppercase, lowercase, number, and special character.";
        } else {
            $stmt = $conn->prepare("SELECT id, reset_code_expires FROM users WHERE email = ? AND reset_code = ?");
            $stmt->bind_param("ss", $email, $code);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $message = "Invalid reset code.";
            } else {
                $user = $result->fetch_assoc();
                $expiry = strtotime($user['reset_code_expires']);
                if (time() > $expiry) {
                    $message = "Reset code has expired. Please request a new one.";
                } else {
                    $hashed = hash('sha256', $newPassword);
                    $update = $conn->prepare("UPDATE users SET password = ?, reset_code = NULL, reset_code_expires = NULL WHERE id = ?");
                    $update->bind_param("si", $hashed, $user['id']);
                    if ($update->execute()) {
                        $success = true;
                        $step = 'done';
                        $message = "Password updated successfully! You can now login.";

                        // Send a branded security notice after a successful reset.
                        $accountStmt = $conn->prepare("SELECT username FROM users WHERE id = ? LIMIT 1");
                        $accountStmt->bind_param("i", $user['id']);
                        $accountStmt->execute();
                        $account = $accountStmt->get_result()->fetch_assoc();
                        $safeUsername = htmlspecialchars((string)($account['username'] ?? 'there'), ENT_QUOTES, 'UTF-8');
                        $subject = 'UGAT: Your account password was reset';
                        $body = '<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Password Reset Notice</title></head>'
                            . '<body style="margin:0;padding:0;background:#141b26;font-family:Arial,Helvetica,sans-serif;color:#f7f4ed;"><div style="max-width:600px;margin:32px auto;border:1px solid #c6a66e;border-radius:14px;overflow:hidden;background:#1f2a38;">'
                            . '<header style="padding:28px;text-align:center;background:#141b26;border-bottom:1px solid #6f5a3a;"><div style="font-size:12px;letter-spacing:4px;color:#dac08f;">ᜂᜄᜆ᜔</div><div style="margin-top:8px;font-family:Georgia,serif;font-size:24px;letter-spacing:1px;color:#f7e7bd;">ᜂᜄᜆ᜔: Kultura’t Tradisyon</div><div style="margin-top:10px;font-size:11px;letter-spacing:2px;color:#c6a66e;">ACCOUNT SECURITY</div></header>'
                            . '<main style="padding:30px;line-height:1.8;"><h1 style="font-size:21px;font-weight:normal;color:#f7e7bd;">Your password was reset</h1><p>Hello ' . $safeUsername . ',</p><p>The password for your UGAT account was successfully reset using the password recovery process.</p><div style="margin:22px 0;padding:16px;border-left:3px solid #c6a66e;background:#141b26;">If you requested this change, no further action is needed. If you did not, reset your password again and contact the site administrator.</div><p style="color:#dac08f;">— ᜂᜄᜆ᜔: Kultura’t Tradisyon</p></main>'
                            . '<footer style="padding:16px;text-align:center;background:#141b26;color:#9a9a91;font-size:11px;">This automated notice never includes your password.</footer></div></body></html>';
                        if (function_exists('sendEmail') && sendEmail($email, $subject, $body, true)) {
                            $message = "Password updated successfully! A security notice has been sent to your email.";
                        } else {
                            error_log('UGAT password-reset notice could not be sent for user ID ' . (int)$user['id']);
                            $message = "Password updated successfully, but the security email could not be sent.";
                        }
                    } else {
                        $message = "Error updating password. Please try again.";
                    }
                }
            }
        }
    }
}

// Scroll is only enabled on the code + new password step.
$isScrollable = ($showResetForm && $step === 'verify');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ᜂᜄᜆ᜔: Mga Kuwento ng Bayan — Forgot Password</title>
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .login-wrapper {
            position: relative;
            width: 100%;
            min-height: 100vh;
            padding: 410px 6vw 60px 60px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: flex-start;
        }
        /* ------------------------------------------------------
           Forgot-password entry animation
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
<body class="<?= $isScrollable ? 'scrollable' : '' ?>">
<div class="bg-image"></div>
<div class="bg-overlay"></div>
<div class="page-fog"></div>

<img src="assets/img/fulltitle.png"
     alt="ᜂᜄᜆ᜔: Mga Kuwento ng Bayan"
     class="full-title">

<audio id="interface-music" loop preload="auto">
    <source src="Assets/mp3/system%20music.mp3" type="audio/mpeg">
</audio>

<div class="login-wrapper">
    <div class="form-block" id="resetFormBlock">

        <?php if ($message): ?>
            <div class="message <?= $success ? 'success' : '' ?>"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($step == 'request' || (!$success && empty($resetEmail))): ?>
            <form method="POST" id="requestForm">
                <input type="hidden" name="request_code" value="1">
                <div class="field">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" required>
                </div>
                <button type="submit" class="btn">Send Reset Code</button>
            </form>
        <?php endif; ?>

        <?php if ($showResetForm && $step == 'verify'): ?>
            <form method="POST" id="resetForm">
                <input type="hidden" name="reset_password" value="1">
                <input type="hidden" name="email" value="<?= htmlspecialchars($resetEmail) ?>">

                <div class="field">
                    <label for="reset_code">6-Digit Reset Code</label>
                    <input type="text" name="reset_code" id="reset_code" class="code-input"
                           placeholder="123456" maxlength="6"
                           pattern="[0-9]{6}" inputmode="numeric" required>
                </div>

                <div class="field">
                    <label for="reset-password">New Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="reset-password" maxlength="16" required
                               onkeyup="checkPasswordStrength(this.value)"
                               oncopy="return false" oncut="return false" onpaste="return false">
                        <button type="button" class="toggle-password" data-target="reset-password">👁</button>
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
                    <label for="reset-confirm">Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" id="reset-confirm" maxlength="16" required
                               oncopy="return false" oncut="return false" onpaste="return false">
                        <button type="button" class="toggle-password" data-target="reset-confirm">👁</button>
                    </div>
                </div>

                <button type="submit" class="btn" id="resetBtn" disabled>Update Password</button>
            </form>
        <?php endif; ?>

        <div class="footer">
            <a href="login.php" class="exit-link">Back to login</a>
        </div>
    </div>
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
        const btn = document.getElementById('resetBtn');
        if (btn) btn.disabled = !allValid;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('reset-password');
        if (input) checkPasswordStrength(input.value);

        const requestForm = document.getElementById('requestForm');
        if (requestForm) requestForm.addEventListener('submit', function () {
            if (window.Loading) window.Loading.show('Sending the letter…');
        });

        const resetForm = document.getElementById('resetForm');
        if (resetForm) resetForm.addEventListener('submit', function () {
            if (window.Loading) window.Loading.show('Updating the chronicle…');
        });
    });

    // ----------------------------------------------------------
    // Exit transition: plays the form slide-out AND the fog
    // fade-out, then navigates to the href.
    // ----------------------------------------------------------
    document.querySelectorAll('.exit-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const target = link.getAttribute('href');
            if (!target) return;

            const block = document.getElementById('resetFormBlock');
            if (block) block.classList.add('leaving');
            document.body.classList.add('leaving');

            setTimeout(function () {
                window.location.href = target;
            }, 1300);
        });
    });

    // Fade the form as it approaches the title (only when the page is scrollable).
    (function () {
        const formBlock = document.getElementById('resetFormBlock');
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
<script src="Assets/js/app.js"></script>
</body>
</html>