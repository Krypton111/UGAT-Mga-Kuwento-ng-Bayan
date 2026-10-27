<?php
session_start();
include 'includes/db.php';
require_once 'includes/pending_cleanup.php';

$message = "";
$success = false;
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $message = "Missing reset token.";
} else {
    $stmt = $conn->prepare("SELECT id, reset_expires FROM users WHERE reset_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $message = "Invalid or expired token.";
    } else {
        $user = $result->fetch_assoc();
        $expiry = strtotime($user['reset_expires']);
        if (time() > $expiry) {
            $message = "Token has expired. Please request a new reset link.";
        } else {
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $newPassword = $_POST['password'];
                $confirm = $_POST['confirm_password'];

                if (empty($newPassword) || empty($confirm)) {
                    $message = "Please fill in both fields.";
                } elseif ($newPassword !== $confirm) {
                    $message = "Passwords do not match.";
                } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&_\-])[A-Za-z\d@$!%*?&_\-]{8,16}$/', $newPassword)) {
                    $message = "Password must be 8-16 characters with uppercase, lowercase, number, and special character.";
                } else {
                    $hashed = hash('sha256', $newPassword);
                    $update = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
                    $update->bind_param("si", $hashed, $user['id']);
                    if ($update->execute()) {
                        $success = true;
                        $message = "Password updated successfully! You can now login.";

                        // Send the same branded security notice for the legacy token-reset route.
                        require_once 'includes/send_email.php';
                        $accountStmt = $conn->prepare("SELECT username, email FROM users WHERE id = ? LIMIT 1");
                        $accountStmt->bind_param("i", $user['id']);
                        $accountStmt->execute();
                        $account = $accountStmt->get_result()->fetch_assoc();
                        $recipient = trim((string)($account['email'] ?? ''));
                        $safeUsername = htmlspecialchars((string)($account['username'] ?? 'there'), ENT_QUOTES, 'UTF-8');
                        $subject = 'UGAT: Your account password was reset';
                        $body = '<!doctype html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Password Reset Notice</title></head>'
                            . '<body style="margin:0;padding:0;background:#141b26;font-family:Arial,Helvetica,sans-serif;color:#f7f4ed;"><div style="max-width:600px;margin:32px auto;border:1px solid #c6a66e;border-radius:14px;overflow:hidden;background:#1f2a38;">'
                            . '<header style="padding:28px;text-align:center;background:#141b26;border-bottom:1px solid #6f5a3a;"><div style="font-size:12px;letter-spacing:4px;color:#dac08f;">ᜂᜄᜆ᜔</div><div style="margin-top:8px;font-family:Georgia,serif;font-size:24px;letter-spacing:1px;color:#f7e7bd;">ᜂᜄᜆ᜔: Kultura’t Tradisyon</div><div style="margin-top:10px;font-size:11px;letter-spacing:2px;color:#c6a66e;">ACCOUNT SECURITY</div></header>'
                            . '<main style="padding:30px;line-height:1.8;"><h1 style="font-size:21px;font-weight:normal;color:#f7e7bd;">Your password was reset</h1><p>Hello ' . $safeUsername . ',</p><p>The password for your UGAT account was successfully reset using the password recovery process.</p><div style="margin:22px 0;padding:16px;border-left:3px solid #c6a66e;background:#141b26;">If you requested this change, no further action is needed. If you did not, reset your password again and contact the site administrator.</div><p style="color:#dac08f;">— ᜂᜄᜆ᜔: Kultura’t Tradisyon</p></main>'
                            . '<footer style="padding:16px;text-align:center;background:#141b26;color:#9a9a91;font-size:11px;">This automated notice never includes your password.</footer></div></body></html>';
                        if ($recipient !== '' && filter_var($recipient, FILTER_VALIDATE_EMAIL)
                            && function_exists('sendEmail') && sendEmail($recipient, $subject, $body, true)) {
                            $message = "Password updated successfully! A security notice has been sent to your email.";
                        } else {
                            error_log('UGAT token-reset notice could not be sent for user ID ' . (int)$user['id']);
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ᜂᜄᜆ᜔: Mga Kuwento ng Bayan — Reset Password</title>
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
           Entry animation
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
    <source src="Assets/mp3/system%20music.mp3" type="audio/mpeg">
</audio>

<div class="login-wrapper">
    <div class="form-block" id="resetFormBlock">

        <?php if ($message): ?>
            <div class="message <?= $success ? 'success' : '' ?>"><?= $message ?></div>
            <?php if ($success): ?>
                <script>
                    // Wait for the welcome state before navigating.
                    setTimeout(function () {
                        document.body.classList.add('leaving');
                        const block = document.getElementById('resetFormBlock');
                        if (block) block.classList.add('leaving');
                        setTimeout(function () {
                            window.location.href = 'login.php';
                        }, 1300);
                    }, 1800);
                </script>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!$success && !empty($token)): ?>
        <form method="POST" id="resetForm">
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
</script>

<script src="assets/js/particles.js"></script>
<script src="Assets/js/app.js"></script>
</body>
</html>