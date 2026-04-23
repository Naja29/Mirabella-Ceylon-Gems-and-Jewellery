<?php
/**
 * Mirabella Ceylon — PHPMailer wrapper
 * includes/mailer.php
 *
 * Usage:
 *   require_once __DIR__ . '/mailer.php';
 *   send_mail('to@example.com', 'Subject', '<p>HTML body</p>');
 *
 * Returns true on success, false if SMTP is not configured or send fails.
 * Never throws — silently logs errors so orders are never blocked by email issues.
 */

require_once __DIR__ . '/../lib/phpmailer/PHPMailer.php';
require_once __DIR__ . '/../lib/phpmailer/SMTP.php';
require_once __DIR__ . '/../lib/phpmailer/Exception.php';
require_once __DIR__ . '/site_settings.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function send_mail(string $toEmail, string $toName, string $subject, string $htmlBody): bool
{
    $host     = get_site_setting('smtp_host',       '');
    $user     = get_site_setting('smtp_username',   '');
    $pass     = get_site_setting('smtp_password',   '');
    $port     = (int)get_site_setting('smtp_port',  '465');
    $enc      = get_site_setting('smtp_encryption', 'ssl');
    $fromName = get_site_setting('smtp_from_name',  get_site_setting('store_name', 'Mirabella Ceylon'));
    $fromMail = get_site_setting('smtp_from_email', '');

    // Skip silently if SMTP is not configured
    if (!$host || !$user || !$pass || !$fromMail) {
        return false;
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $user;
        $mail->Password   = $pass;
        $mail->SMTPSecure = ($enc === 'tls') ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = $port;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom($fromMail, $fromName);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mailer error: ' . $e->getMessage());
        return false;
    }
}

// ── Email template builder ────────────────────────────────────────────────────

function mail_wrap(string $heading, string $bodyHtml): string
{
    $storeName = get_site_setting('store_name', 'Mirabella Ceylon');
    $year      = date('Y');
    return '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>' . htmlspecialchars($heading) . '</title>
</head>
<body style="margin:0;padding:0;background:#f5f3ef;font-family:\'Helvetica Neue\',Arial,sans-serif;color:#1a1a1a;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f3ef;padding:40px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08);">
      <!-- Header -->
      <tr><td style="background:#0d0d0d;padding:28px 40px;text-align:center;">
        <p style="margin:0;font-family:Georgia,serif;font-size:22px;font-weight:700;color:#c8a84b;letter-spacing:1px;">' . htmlspecialchars($storeName) . '</p>
        <p style="margin:4px 0 0;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#888;">Gems &amp; Jewellery Worldwide</p>
      </td></tr>
      <!-- Divider line -->
      <tr><td style="height:3px;background:linear-gradient(90deg,#c8a84b,#e8d49a,#c8a84b);"></td></tr>
      <!-- Body -->
      <tr><td style="padding:36px 40px;">
        <h2 style="margin:0 0 20px;font-family:Georgia,serif;font-size:24px;font-weight:700;color:#1a1a1a;">' . htmlspecialchars($heading) . '</h2>
        ' . $bodyHtml . '
      </td></tr>
      <!-- Footer -->
      <tr><td style="background:#f9f7f3;border-top:1px solid #ede9e0;padding:20px 40px;text-align:center;">
        <p style="margin:0;font-size:12px;color:#888;">&copy; ' . $year . ' ' . htmlspecialchars($storeName) . ' &nbsp;&middot;&nbsp; All rights reserved.</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>';
}
