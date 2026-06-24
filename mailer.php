<?php
require_once __DIR__ . '/env.php';
require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send an email using PHPMailer + Gmail SMTP
 * Returns true on success, false on failure
 */
function sendLIMSEmail($toEmail, $toName, $subject, $bodyHtml) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email failed: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Standard LIMS email template wrapper
 */
function limsEmailTemplate($title, $message, $buttonText = '', $buttonLink = '') {
    $button = '';
    if ($buttonText && $buttonLink) {
        $button = "<div style='text-align:center;margin:24px 0;'>
            <a href='{$buttonLink}' style='display:inline-block;padding:12px 32px;background:#6246ea;color:#fff;border-radius:50px;text-decoration:none;font-weight:700;font-family:Arial,sans-serif;font-size:14px;'>{$buttonText}</a>
        </div>";
    }

    return "
    <div style='font-family:Arial,sans-serif;background:#f7f6fb;padding:30px;'>
        <div style='max-width:100%;background:#fff;border-radius:14px;overflow:hidden;border:1px solid #e8e7f0;'>
            <div style='background:linear-gradient(135deg,#6246ea,#2563eb);padding:24px 30px;'>
                <div style='font-size:22px;font-weight:900;color:#fff;'><span style='background:rgba(255,255,255,0.2);padding:2px 8px;border-radius:5px;margin-right:3px;'>L</span>IMS</div>
                <div style='font-size:11px;color:rgba(255,255,255,0.7);margin-top:4px;'>Women University Multan</div>
            </div>
            <div style='padding:30px;'>
                <h2 style='font-size:18px;color:#0f0e17;margin:0 0 12px;'>{$title}</h2>
                <p style='font-size:14px;color:#72737d;line-height:1.6;margin:0;'>{$message}</p>
                {$button}
            </div>
            <div style='background:#f7f6fb;padding:16px 30px;text-align:center;font-size:11px;color:#aaa;border-top:1px solid #e8e7f0;'>
                This is an automated message from LIMS. Please do not reply.
            </div>
        </div>
    </div>";
}
?>
