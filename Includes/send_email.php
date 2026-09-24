<?php
// includes/send_email.php
require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ------------------------------------------------------------
// SMTP Configuration
// ------------------------------------------------------------
define('SMTP_HOST',         'smtp.gmail.com');
define('SMTP_USERNAME',     'gamedev50035@gmail.com');
define('SMTP_PASSWORD',     getenv('SMTP_PASSWORD') ?: '');   // Gmail App Password (no spaces)
define('SMTP_PORT',         587);
define('SMTP_ENCRYPTION',   PHPMailer::ENCRYPTION_STARTTLS);
define('SMTP_FROM_EMAIL',   'gamedev50035@gmail.com');
define('SMTP_FROM_NAME',    'Kuwento ng Bayan');

// Public URL — used only as a link in the footer.
define('SITE_URL',          'http://localhost/GameLauncherKERWIN');

// ------------------------------------------------------------
// Palette (matches style.css)
// ------------------------------------------------------------
const KUWB_PARCHMENT_1 = '#fcf8f0';
const KUWB_PARCHMENT_2 = '#f4e9d8';
const KUWB_PARCHMENT_3 = '#e8ddc8';
const KUWB_INK         = '#2a1f18';
const KUWB_INK_SOFT    = '#6b4f3a';
const KUWB_GOLD        = '#b8860b';
const KUWB_GOLD_LIGHT  = '#d4af37';
const KUWB_BORDER      = 'rgba(184,134,11,0.35)';
const KUWB_RED         = '#8b2c1f';
const KUWB_GREEN       = '#7a8b6f';


/**
 * Send a generic email via PHPMailer.
 */
function sendEmail($to, $subject, $body, $isHTML = false) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port       = SMTP_PORT;
        $mail->Timeout    = 30;

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];

        $mail->CharSet = 'UTF-8';
        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        if (!$isHTML) {
            $mail->AltBody = strip_tags($body);
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}


/**
 * Shared HTML template for the two code emails (verification + reset).
 *
 * Uses only web-safe-ish stacks that degrade gracefully in most mail
 * clients (Gmail will substitute its own sans-serif for the fancy
 * cursive stack, which is fine — the gold/parchment palette carries
 * the branding).
 */
function _renderKuwentoCodeEmail($heading, $introHTML, $code, $footerNote) {
    $siteUrl = SITE_URL;

    $heading    = htmlspecialchars($heading, ENT_QUOTES, 'UTF-8');
    $code       = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');
    $footerNote = htmlspecialchars($footerNote, ENT_QUOTES, 'UTF-8');

    return "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>{$heading}</title>
    </head>
    <body style=\"margin:0; padding:0; background:" . KUWB_PARCHMENT_3 . "; font-family:'EB Garamond', Georgia, serif; color:" . KUWB_INK . ";\">

        <table role='presentation' width='100%' cellpadding='0' cellspacing='0' border='0'
               style=\"background:" . KUWB_PARCHMENT_3 . "; padding:32px 12px;\">
            <tr>
                <td align='center'>

                    <table role='presentation' width='600' cellpadding='0' cellspacing='0' border='0'
                           style=\"max-width:600px; width:100%;
                                  background:" . KUWB_PARCHMENT_1 . ";
                                  border:1px solid " . KUWB_BORDER . ";
                                  border-radius:14px;
                                  box-shadow:0 12px 40px rgba(26,20,16,0.18);
                                  overflow:hidden;\">

                        <!-- ============================================= -->
                        <!-- TEXT-BASED TITLE HEADER                       -->
                        <!-- ============================================= -->
                        <tr>
                            <td align='center' style=\"padding:36px 24px 10px 24px;
                                                       background: linear-gradient(180deg,
                                                         " . KUWB_PARCHMENT_1 . " 0%,
                                                         " . KUWB_PARCHMENT_2 . " 100%);\">
                                <div style=\"font-family:'Great Vibes','Lavonia Classy','Pinyon Script', cursive;
                                            font-size:44px;
                                            font-style:italic;
                                            line-height:1;
                                            letter-spacing:1px;
                                            color:" . KUWB_INK . ";
                                            text-shadow: 0 2px 3px rgba(26,20,16,0.15);\">
                                    ᜂᜄᜆ᜔
                                </div>
                                <div style=\"margin-top:6px;
                                            font-family:'EB Garamond', Georgia, serif;
                                            font-size:15px;
                                            letter-spacing:3px;
                                            text-transform:uppercase;
                                            color:" . KUWB_INK_SOFT . ";
                                            opacity:0.85;\">
                                    Mga Kuwento ng Bayan
                                </div>
                            </td>
                        </tr>

                        <!-- Gold divider -->
                        <tr>
                            <td align='center' style=\"padding:10px 24px 0 24px;\">
                                <div style=\"height:1px; width:220px; margin:0 auto;
                                            background: linear-gradient(90deg, transparent,
                                                         " . KUWB_GOLD . ", transparent);\">
                                </div>
                            </td>
                        </tr>

                        <!-- Heading + intro -->
                        <tr>
                            <td style=\"padding:26px 40px 8px 40px; text-align:center;\">
                                <h1 style=\"margin:0 0 14px 0;
                                           font-family:'Great Vibes','Lavonia Classy', cursive;
                                           font-weight:400;
                                           font-size:36px;
                                           line-height:1.05;
                                           color:" . KUWB_INK . ";\">
                                    {$heading}
                                </h1>
                                <p style=\"margin:0 0 22px 0;
                                          font-family:'EB Garamond', Georgia, serif;
                                          font-size:17px;
                                          line-height:1.7;
                                          color:" . KUWB_INK_SOFT . ";\">
                                    {$introHTML}
                                </p>
                            </td>
                        </tr>

                        <!-- Code box -->
                        <tr>
                            <td align='center' style=\"padding:0 40px 8px 40px;\">
                                <div style=\"display:inline-block;
                                            padding:22px 32px;
                                            background:" . KUWB_PARCHMENT_2 . ";
                                            border:2px solid " . KUWB_GOLD . ";
                                            border-radius:12px;
                                            box-shadow: inset 0 1px 2px rgba(255,255,255,0.6);\">
                                    <span style=\"font-family:'Courier New', monospace;
                                                 font-size:40px;
                                                 font-weight:700;
                                                 letter-spacing:12px;
                                                 color:" . KUWB_GOLD . ";
                                                 text-shadow:0 1px 0 rgba(255,255,255,0.6);\">
                                        {$code}
                                    </span>
                                </div>
                            </td>
                        </tr>

                        <!-- Timer -->
                        <tr>
                            <td align='center' style=\"padding:18px 40px 0 40px;\">
                                <p style=\"margin:0;
                                          font-family:'EB Garamond', Georgia, serif;
                                          font-style:italic;
                                          font-size:15px;
                                          color:" . KUWB_INK_SOFT . ";\">
                                    This code expires in <strong style='font-style:normal;'>15 minutes</strong>.
                                </p>
                            </td>
                        </tr>

                        <!-- Safety note -->
                        <tr>
                            <td align='center' style=\"padding:14px 40px 30px 40px;\">
                                <p style=\"margin:0;
                                          font-family:'EB Garamond', Georgia, serif;
                                          font-size:14px;
                                          color:" . KUWB_INK_SOFT . ";
                                          opacity:0.85;\">
                                    If you didn't request this, you can safely ignore this email.
                                </p>
                            </td>
                        </tr>

                        <!-- Divider -->
                        <tr>
                            <td align='center' style=\"padding:0 24px;\">
                                <div style=\"height:1px; width:180px; margin:0 auto;
                                            background: linear-gradient(90deg, transparent,
                                                         " . KUWB_BORDER . ", transparent);\">
                                </div>
                            </td>
                        </tr>

                        <!-- Footer -->
                        <tr>
                            <td align='center' style=\"padding:20px 24px 28px 24px;
                                                       background:" . KUWB_PARCHMENT_2 . ";\">
                                <p style=\"margin:0 0 4px 0;
                                          font-family:'Lavonia Classy','Great Vibes', cursive;
                                          font-style:italic;
                                          font-size:16px;
                                          color:" . KUWB_INK_SOFT . ";\">
                                    {$footerNote}
                                </p>
                                <p style=\"margin:0;
                                          font-family:'EB Garamond', Georgia, serif;
                                          font-size:12px;
                                          letter-spacing:1px;
                                          color:" . KUWB_INK_SOFT . ";
                                          opacity:0.7;\">
                                    ᜂᜄᜆ᜔: Kuwento ng Bayan
                                </p>
                                <p style=\"margin:6px 0 0 0;\">
                                    <a href='{$siteUrl}'
                                       style=\"font-family:'EB Garamond', Georgia, serif;
                                              font-size:12px;
                                              color:" . KUWB_GOLD . ";
                                              text-decoration:none;\">
                                        {$siteUrl}
                                    </a>
                                </p>
                            </td>
                        </tr>

                    </table>

                </td>
            </tr>
        </table>

    </body>
    </html>
    ";
}


/**
 * Plain-text fallback for the code emails.
 */
function _renderKuwentoCodeEmailText($heading, $introPlain, $code, $footerNote) {
    return "ᜂᜄᜆ᜔ — Kuwento ng Bayan\n"
         . "========================================\n\n"
         . $heading . "\n\n"
         . strip_tags($introPlain) . "\n\n"
         . "    " . $code . "\n\n"
         . "This code expires in 15 minutes.\n\n"
         . "If you didn't request this, you can safely ignore this email.\n\n"
         . "----------------------------------------\n"
         . $footerNote . "\n"
         . "ᜂᜄᜆ᜔: Kuwento ng Bayan\n"
         . SITE_URL . "\n";
}


/**
 * Verification email sent on signup.
 */
function sendVerificationEmail($to, $username, $verificationCode) {
    $subject = "Verify Your Email — ᜂᜄᜆ᜔: Kuwento ng Bayan";

    $safeUser = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    $heading  = "Welcome to San Isidro";
    $intro    = "Hello <strong>{$safeUser}</strong>, thank you for registering. "
              . "Please confirm your email address by entering the code below.";

    $body = _renderKuwentoCodeEmail(
        $heading,
        $intro,
        $verificationCode,
        "You are receiving this because you signed up for ᜂᜄᜆ᜔: Kuwento ng Bayan."
    );

    $altBody = _renderKuwentoCodeEmailText(
        "Welcome to San Isidro",
        "Hello {$username}, thank you for registering.",
        $verificationCode,
        "You are receiving this because you signed up for ᜂᜄᜆ᜔: Kuwento ng Bayan."
    );

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port       = SMTP_PORT;
        $mail->Timeout    = 30;

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];

        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $altBody;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}


/**
 * Password-reset code email.
 */
function sendPasswordResetCode($to, $resetCode) {
    $subject = "Password Reset Code — ᜂᜄᜆ᜔: Kuwento ng Bayan";

    $heading = "Reset Your Password";
    $intro   = "You requested a password reset. "
             . "Enter the code below to choose a new password.";

    $body = _renderKuwentoCodeEmail(
        $heading,
        $intro,
        $resetCode,
        "If you didn't request a password reset, you can safely ignore this email."
    );

    $altBody = _renderKuwentoCodeEmailText(
        "Reset Your Password",
        "You requested a password reset.",
        $resetCode,
        "If you didn't request a password reset, you can safely ignore this email."
    );

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port       = SMTP_PORT;
        $mail->Timeout    = 30;

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];

        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $altBody;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>