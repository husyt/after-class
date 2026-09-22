<?php
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendPasswordResetEmail($to_email, $to_name, $reset_link) {
    $config = require __DIR__ . '/../config/mail_config.php';
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port       = $config['port'];
        $mail->CharSet    = 'UTF-8';
        $mail->SMTPDebug  = SMTP::DEBUG_OFF;
        
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($to_email, $to_name);
        
        $mail->isHTML(true);
        $mail->Subject = 'Reset Your After Class Password';
        $mail->Body = "
        <div style='font-family:Arial;max-width:500px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;'>
            <div style='background:#d13639;color:#fff;padding:32px;text-align:center;'>
                <h1 style='margin:0;'>AFTER CLASS</h1>
            </div>
            <div style='padding:32px;color:#333;'>
                <h2>Hi " . htmlspecialchars($to_name) . ",</h2>
                <p>Click the button below to reset your password:</p>
                <p style='text-align:center;'>
                    <a href='$reset_link' style='display:inline-block;background:#d13639;color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;'>Reset Password</a>
                </p>
                <p>Or copy this link: <br><span style='word-break:break-all;color:#666;font-size:12px;'>$reset_link</span></p>
                <p><strong>This link expires in 1 hour.</strong></p>
            </div>
        </div>";
        $mail->AltBody = "Reset your After Class password: $reset_link";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

function sendOTPEmail($to_email, $to_name, $code) {
    $config = require __DIR__ . '/../config/mail_config.php';
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port       = $config['port'];
        $mail->CharSet    = 'UTF-8';
        $mail->SMTPDebug  = SMTP::DEBUG_OFF;
        
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($to_email, $to_name);
        
        $mail->isHTML(true);
        $mail->Subject = "After Class Verification Code: $code";
        $mail->Body = "
        <div style='font-family:Arial;max-width:500px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;'>
            <div style='background:#d13639;color:#fff;padding:32px;text-align:center;'>
                <h1 style='margin:0;'>AFTER CLASS</h1>
            </div>
            <div style='padding:32px;color:#333;text-align:center;'>
                <h2>Hi " . htmlspecialchars($to_name) . ",</h2>
                <p>Your verification code is:</p>
                <div style='background:#f9f9f9;border:2px dashed #d13639;padding:24px;border-radius:12px;margin:24px 0;'>
                    <div style='font-size:42px;font-weight:800;color:#d13639;letter-spacing:8px;font-family:Courier New,monospace;'>$code</div>
                </div>
                <p>This code expires in <strong>5 minutes</strong>.</p>
            </div>
        </div>";
        $mail->AltBody = "Your verification code: $code";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("OTP Email Error: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Send QR Login Approval Email
 * Called from qr_scan.php after user enters their email.
 */
function sendQRApprovalEmail($to_email, $to_name, $approve_url) {
    $config = require __DIR__ . '/../config/mail_config.php';
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port       = $config['port'];
        $mail->CharSet    = 'UTF-8';
        $mail->SMTPDebug  = SMTP::DEBUG_OFF;

        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = 'Approve Your After Class Login';

        $mail->Body = "
        <div style='font-family:Arial;max-width:500px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;'>
            <div style='background:#d13639;color:#fff;padding:32px;text-align:center;'>
                <h1 style='margin:0;'>AFTER CLASS</h1>
            </div>
            <div style='padding:32px;color:#333;text-align:center;'>
                <h2 style='margin-top:0;'>Hi " . htmlspecialchars($to_name) . ",</h2>
                <p style='font-size:14px;line-height:1.6;color:#555;'>
                    Someone is trying to sign in to your After Class account from another device.
                </p>
                <p style='font-size:14px;line-height:1.6;color:#555;'>
                    If this was you, click the button below to approve the login.
                </p>
                <p style='margin:32px 0;'>
                    <a href='" . htmlspecialchars($approve_url) . "'
                       style='display:inline-block;background:#2ecc71;color:#fff;padding:16px 40px;border-radius:999px;text-decoration:none;font-weight:800;font-size:16px;'>
                       ✓ Approve Login
                    </a>
                </p>
                <p style='font-size:12px;color:#888;line-height:1.6;'>
                    Or copy this link:<br>
                    <span style='word-break:break-all;color:#3498db;'>" . htmlspecialchars($approve_url) . "</span>
                </p>
                <p style='font-size:12px;color:#888;margin-top:24px;'>
                    If you didn't request this, you can safely ignore this email.
                </p>
                <p style='font-size:12px;color:#888;'>
                    <strong>This link expires in 5 minutes.</strong>
                </p>
            </div>
        </div>";

        $mail->AltBody = "Approve your After Class login: $approve_url";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("QR Approval Email Error: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Send a "come back and play" reminder email
 * Called from send_reminders.php cron job
 */
function sendReminderEmail($to_email, $to_name) {
    $config = require __DIR__ . '/../config/mail_config.php';
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $config['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['username'];
        $mail->Password   = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port       = $config['port'];
        $mail->CharSet    = 'UTF-8';
        $mail->SMTPDebug  = SMTP::DEBUG_OFF;

        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = 'We miss you at After Class! 🎮';

        $mail->Body = "
        <div style='font-family:Arial;max-width:500px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;'>
            <div style='background:#d13639;color:#fff;padding:32px;text-align:center;'>
                <h1 style='margin:0;'>AFTER CLASS</h1>
            </div>
            <div style='padding:32px;color:#333;text-align:center;'>
                <h2 style='margin-top:0;'>Hi " . htmlspecialchars($to_name) . ",</h2>
                <p style='font-size:14px;line-height:1.6;color:#555;'>
                    It's been a while since you last played. Your games miss you!
                </p>
                <p style='font-size:14px;line-height:1.6;color:#555;'>
                    Come back and keep leveling up — there's more XP waiting for you.
                </p>
                <p style='margin:32px 0;'>
                    <a href='http://localhost/after-class/public/dashboard.php'
                       style='display:inline-block;background:#d13639;color:#fff;padding:16px 40px;border-radius:999px;text-decoration:none;font-weight:800;font-size:16px;'>
                       ▶ Play Now
                    </a>
                </p>
                <p style='font-size:12px;color:#888;line-height:1.6;margin-top:24px;'>
                    You're receiving this because you enabled game reminders in your settings.
                    You can turn them off anytime from the settings panel.
                </p>
            </div>
        </div>";

        $mail->AltBody = "Come back and play After Class! Visit: http://localhost/after-class/public/dashboard.php";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Reminder Email Error: " . $mail->ErrorInfo);
        return false;
    }
}