<?php
include 'mailer.php';

$result = sendLIMSEmail(
    'iqraiqrashahzadi355@gmail.com',
    'Iqra',
    'LIMS Email Test',
    limsEmailTemplate('Email System Working! ✅', 'This is a test email from your LIMS system. If you received this, PHPMailer is configured correctly.')
);

if ($result) {
    echo "✅ Email sent successfully! Check your inbox.";
} else {
    echo "❌ Email failed to send. Check error log.";
}
?>
