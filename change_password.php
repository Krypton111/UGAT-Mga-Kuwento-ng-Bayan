<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

require_once 'Includes/db.php';
// SMTP credentials and PHPMailer configuration stay in this ignored local helper.
require_once __DIR__ . '/Includes/send_email.php';

$username = $_SESSION['username'];
$message = '';
$success = false;

if (empty($_SESSION['csrf_change_password'])) {
    $_SESSION['csrf_change_password'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!hash_equals($_SESSION['csrf_change_password'], $csrf)) {
        $message = 'Your session validation expired. Please refresh the page and try again.';
    } elseif ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
        $message = 'Please complete all password fields.';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'The new passwords do not match.';
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&_\-])[A-Za-z\d@$!%*?&_\-]{8,16}$/', $newPassword)) {
        $message = 'Your new password must be 8–16 characters and contain an uppercase letter, a lowercase letter, a number, and a special character (@$!%*?&_ -).';
    } elseif ($currentPassword === $newPassword) {
        $message = 'Your new password must be different from your current password.';
    } else {
        $stmt = $conn->prepare("SELECT id, password, email FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $account = $stmt->get_result()->fetch_assoc();

        if (!$account || !hash_equals((string)$account['password'], hash('sha256', $currentPassword))) {
            $message = 'Your current password is incorrect.';
        } else {
            $newHash = hash('sha256', $newPassword);
            $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update->bind_param("si", $newHash, $account['id']);

            if ($update->execute()) {
                $success = true;
                $_SESSION['csrf_change_password'] = bin2hex(random_bytes(32));
                $message = 'Your password has been changed successfully.';

                // Send a security notice through the existing PHPMailer helper.
                // SMTP credentials stay in Includes/send_email.php and are not duplicated here.
                $email = trim((string)($account['email'] ?? ''));
                if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
                    $subject = 'Security Notice — ᜂᜄᜆ᜔: Kultura’t Tradisyon';
                    $body = "
                    <!DOCTYPE html>
                    <html lang='en'>
                    <head>
                        <meta charset='UTF-8'>
                        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                        <title>Security Notice</title>
                    </head>
                    <body style='margin:0;padding:0;background:#e8ddc8;font-family:Georgia,\"Times New Roman\",serif;color:#2a1f18;'>
                        <table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='background:#e8ddc8;padding:32px 12px;'>
                            <tr><td align='center'>
                                <table role='presentation' width='600' cellpadding='0' cellspacing='0' border='0' style='max-width:600px;width:100%;background:#fcf8f0;border:1px solid rgba(184,134,11,.35);border-radius:14px;overflow:hidden;'>
                                    <tr><td align='center' style='padding:36px 24px 18px;background:#f4e9d8;'>
                                        <div style='font-family:Georgia,\"Times New Roman\",serif;font-size:42px;font-style:italic;line-height:1;color:#2a1f18;letter-spacing:2px;'>ᜂᜄᜆ᜔</div>
                                        <div style='margin-top:8px;font-size:14px;letter-spacing:3px;text-transform:uppercase;color:#6b4f3a;'>Kultura’t Tradisyon</div>
                                    </td></tr>
                                    <tr><td align='center' style='padding:0 24px;'><div style='height:1px;max-width:220px;background:#b8860b;margin:0 auto;'></div></td></tr>
                                    <tr><td style='padding:30px 36px 12px;text-align:center;'>
                                        <h1 style='margin:0 0 16px;font-size:30px;font-weight:normal;color:#2a1f18;'>Password Changed Successfully</h1>
                                        <p style='margin:0 0 18px;font-size:17px;line-height:1.7;color:#6b4f3a;'>Hello <strong style='color:#2a1f18;'>${safeUsername}</strong>, this is a security notice confirming that your account password has been changed.</p>
                                    </td></tr>
                                    <tr><td style='padding:0 36px 10px;'>
                                        <table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0' style='background:#f4e9d8;border:1px solid rgba(184,134,11,.35);border-radius:10px;'>
                                            <tr><td style='padding:20px 22px;font-size:15px;line-height:1.7;color:#2a1f18;'>
                                                <strong style='color:#7a8b6f;'>If this was you</strong><br>No further action is needed. Your password update is complete.
                                                <br><br>
                                                <strong style='color:#8b2c1f;'>If you did not make this change</strong><br>Please reset your password immediately and contact the site administrator if you need further assistance.
                                            </td></tr>
                                        </table>
                                    </td></tr>
                                    <tr><td align='center' style='padding:18px 36px 28px;'>
                                        <p style='margin:0;font-size:14px;line-height:1.7;color:#6b4f3a;'>For your security, this email does not contain your password. If you did not request this change, please do not ignore this notice.</p>
                                    </td></tr>
                                    <tr><td align='center' style='padding:20px 24px 26px;background:#f4e9d8;'>
                                        <p style='margin:0 0 6px;font-size:15px;font-style:italic;color:#6b4f3a;'>Preserving stories, honoring traditions.</p>
                                        <p style='margin:0;font-size:12px;letter-spacing:1px;color:#6b4f3a;'>ᜂᜄᜆ᜔: Kultura’t Tradisyon</p>
                                    </td></tr>
                                </table>
                            </td></tr>
                        </table>
                    </body>
                    </html>";

                    if (function_exists('sendEmail') && sendEmail($email, $subject, $body, true)) {
                        $message = 'Your password has been changed successfully. A security notice has been sent to your registered email address.';
                    } else {
                        error_log('UGAT password-change notice could not be sent for user ID ' . (int)$account['id']);
                        $message = 'Your password has been changed successfully, but the email notice could not be sent. Please contact the site administrator if this continues.';
                    }
                } else {
                    error_log('UGAT password-change notice skipped: no valid registered email for user ID ' . (int)$account['id']);
                    $message = 'Your password has been changed successfully, but your account does not have a valid email address for a security notice.';
                }
            } else {
                $message = 'We could not update your password. Please try again.';
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
    <title>ᜂᜄᜆ᜔: Kultura’t Tradisyon — Change Password</title>
    <link rel="icon" type="image/x-icon" href="Assets/img/favicon.ico">
    <link rel="stylesheet" href="Assets/css/style.css">
    <style>
        .password-page { width:min(560px,100%); margin:0 auto; }
        .password-panel { padding:clamp(20px,4vw,32px); border:1px solid rgba(218,192,143,.28); border-radius:16px; background:rgba(20,27,38,.84); }

        /* Match the directional page transitions used across the site. */
        .password-panel {
            opacity: 0;
            transform: translateX(90px);
            animation: passwordPageEnter .85s cubic-bezier(.22,1,.36,1) .08s forwards;
            will-change: opacity, transform;
        }
        @keyframes passwordPageEnter {
            from { opacity: 0; transform: translateX(90px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        body.password-leaving .password-panel {
            animation: passwordPageExit .65s cubic-bezier(.55,0,.85,.3) forwards;
            pointer-events: none;
        }
        @keyframes passwordPageExit {
            from { opacity: 1; transform: translateX(0); }
            to   { opacity: 0; transform: translateX(110px); }
        }
        @media (prefers-reduced-motion: reduce) {
            .password-panel { animation-duration: .01ms !important; animation-delay: 0ms !important; }
            body.password-leaving .password-panel { animation-duration: .01ms !important; }
        }

        .password-panel h1 { margin:0 0 8px; color:#f8edcf; font-size:34px; }
        .password-intro { margin:0 0 24px; color:rgba(247,244,237,.75); font-size:18px; line-height:1.7; }
        .password-field { margin-bottom:18px; }
        .password-field label { display:block; margin-bottom:8px; color:#f7e7bd; font-size:17px; }
        .password-input-wrap { position:relative; display:flex; align-items:center; }
        .password-field input { box-sizing:border-box; width:100%; padding:15px 48px 15px 14px; font-size:17px; border:1px solid rgba(218,192,143,.32); border-radius:8px; background:rgba(0,0,0,.2); color:#fff7e4; }
        .password-field input:focus { outline:2px solid rgba(218,192,143,.65); outline-offset:1px; }
        .password-visibility { position:absolute; right:10px; top:50%; transform:translateY(-50%); display:inline-flex; align-items:center; justify-content:center; width:30px; height:30px; padding:0; border:0; border-radius:6px; background:transparent; color:#f7e7bd; font-size:18px; cursor:pointer; }
        .password-visibility:hover, .password-visibility:focus-visible { background:rgba(218,192,143,.15); outline:2px solid rgba(218,192,143,.5); outline-offset:1px; }
        .password-rules { margin:0 0 22px; color:rgba(247,244,237,.68); font-size:15px; line-height:1.7; }
        .password-message { margin-bottom:20px; padding:12px 14px; border-radius:8px; font-size:16px; line-height:1.6; color:#f7e7bd; background:rgba(218,192,143,.1); border:1px solid rgba(218,192,143,.25); }
        .password-actions { display:flex; gap:12px; flex-wrap:wrap; margin-top:8px; }
        .password-actions .btn { flex:1 1 180px; text-align:center; }
    </style>
</head>
<body class="scrollable">
<div class="bg-image"></div>
<div class="bg-overlay"></div>
<audio id="interface-music" loop preload="auto"><source src="Assets/mp3/system%20music.mp3" type="audio/mpeg"></audio>

<div class="page-wrapper">
    <main class="password-page">
        <section class="password-panel">
            <h1>Change Password</h1>
            <p class="password-intro">Update your account password. You will need to enter your current password before choosing a new one.</p>

            <?php if ($message !== ''): ?>
                <div class="password-message" role="status"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="POST" action="change_password.php" autocomplete="off" id="changePasswordForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_change_password'], ENT_QUOTES, 'UTF-8') ?>">

                <div class="password-field">
                    <label for="current_password">Current Password</label>
                    <div class="password-input-wrap">
                        <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                        <button class="password-visibility" type="button" data-password-toggle="current_password" aria-controls="current_password" aria-pressed="false" aria-label="Show password" title="Show password">👁️</button>
                    </div>
                </div>
                <div class="password-field">
                    <label for="new_password">New Password</label>
                    <div class="password-input-wrap">
                        <input id="new_password" name="new_password" type="password" minlength="8" maxlength="16" autocomplete="new-password" required>
                        <button class="password-visibility" type="button" data-password-toggle="new_password" aria-controls="new_password" aria-pressed="false" aria-label="Show password" title="Show password">👁️</button>
                    </div>
                </div>
                <div class="password-field">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="password-input-wrap">
                        <input id="confirm_password" name="confirm_password" type="password" minlength="8" maxlength="16" autocomplete="new-password" required>
                        <button class="password-visibility" type="button" data-password-toggle="confirm_password" aria-controls="confirm_password" aria-pressed="false" aria-label="Show password" title="Show password">👁️</button>
                    </div>
                </div>

                <p class="password-rules">Password requirements: 8–16 characters, at least one uppercase letter, one lowercase letter, one number, and one special character (@ $ ! % * ? &amp; _ -).</p>

                <div class="password-actions">
                    <button type="submit" class="btn btn-block" id="updatePasswordButton">Update Password</button>
                    <a href="profile.php" class="btn btn-block">Back to Profile</a>
                </div>
            </form>
        </section>
    </main>
</div>


<script>
const changePasswordForm = document.getElementById('changePasswordForm');
const updatePasswordButton = document.getElementById('updatePasswordButton');

if (changePasswordForm && updatePasswordButton) {
    changePasswordForm.addEventListener('submit', function () {
        if (changePasswordForm.dataset.submitting === 'true') return;
        changePasswordForm.dataset.submitting = 'true';
        updatePasswordButton.disabled = true;
        updatePasswordButton.dataset.originalText = updatePasswordButton.textContent;
        updatePasswordButton.textContent = 'Updating password…';

        if (window.Loading && typeof window.Loading.show === 'function') {
            window.Loading.show('Updating your account…');
        }
    });
}

// Animate ordinary page navigation, but leave the password POST form untouched.
let passwordNavigating = false;
document.querySelectorAll('a[href]').forEach(function (link) {
    link.addEventListener('click', function (event) {
        if (passwordNavigating || event.defaultPrevented || event.button !== 0 ||
            event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ||
            link.target === '_blank' || link.hasAttribute('download')) return;

        const destination = new URL(link.href, window.location.href);
        if (destination.origin !== window.location.origin ||
            destination.href === window.location.href) return;

        event.preventDefault();
        passwordNavigating = true;
        document.body.classList.add('password-leaving');
        window.setTimeout(function () {
            window.location.href = destination.href;
        }, window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 20 : 650);
    });
});

document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
    button.addEventListener('click', function () {
        const input = document.getElementById(button.dataset.passwordToggle);
        const shouldShow = input.type === 'password';
        input.type = shouldShow ? 'text' : 'password';
        button.textContent = shouldShow ? '🙈' : '👁️';
        button.setAttribute('aria-pressed', shouldShow ? 'true' : 'false');
        button.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
        button.setAttribute('title', shouldShow ? 'Hide password' : 'Show password');
    });
});
</script>
<script src="Assets/js/particles.js"></script>
<script src="Assets/js/app.js"></script>
</body>
</html>
