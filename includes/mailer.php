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
?>