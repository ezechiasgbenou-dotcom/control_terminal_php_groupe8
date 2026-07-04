<?php
require_once __DIR__ . '/app.php';
require_once __DIR__ . '/../lib/PHPMailer/Exception.php';
require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

define('GMAIL_ADDRESS', getenv('GMAIL_ADDRESS') ?: '');
define('GMAIL_APP_PASSWORD', getenv('GMAIL_APP_PASSWORD') ?: '');

/**
 * Envoie un e-mail HTML via le SMTP de Gmail.
 * Nécessite GMAIL_ADDRESS et GMAIL_APP_PASSWORD dans le fichier .env
 * (mot de passe d'application généré depuis myaccount.google.com/apppasswords).
 */
function sendMail(string $toEmail, string $toName, string $subject, string $htmlBody): bool {
    if (!GMAIL_ADDRESS || !GMAIL_APP_PASSWORD) {
        error_log("Mailer : identifiants Gmail non configurés (.env). E-mail non envoyé à $toEmail.");
        return false;
    }
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = GMAIL_ADDRESS;
        $mail->Password   = GMAIL_APP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(GMAIL_ADDRESS, SITE_NAME);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags($htmlBody);

        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        error_log("Mailer : échec d'envoi à $toEmail — " . $mail->ErrorInfo);
        return false;
    }
}

/** Gabarit HTML commun à tous les e-mails de Lien */
function emailTemplate(string $heading, string $bodyHtml, string $buttonLabel = '', string $buttonUrl = ''): string {
    $button = '';
    if ($buttonLabel && $buttonUrl) {
        $button = "
        <tr><td align='center' style='padding:28px 0 8px;'>
          <a href='{$buttonUrl}' style='background:#E8772E;color:#ffffff;text-decoration:none;font-weight:600;
            font-size:14px;padding:13px 28px;border-radius:8px;display:inline-block;font-family:Arial,sans-serif;'>
            {$buttonLabel}
          </a>
        </td></tr>";
    }
    return "
    <table width='100%' cellpadding='0' cellspacing='0' style='background:#F2F4F7;padding:40px 0;font-family:Arial,sans-serif;'>
      <tr><td align='center'>
        <table width='480' cellpadding='0' cellspacing='0' style='background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #E3E7ED;'>
          <tr><td style='background:#13293D;padding:24px 32px;'>
            <span style='font-family:Georgia,serif;font-size:24px;color:#ffffff;font-weight:700;'>Lien<span style='color:#E8772E;'>.</span></span>
          </td></tr>
          <tr><td style='padding:32px;'>
            <h2 style='color:#13293D;font-size:20px;margin:0 0 14px;font-family:Georgia,serif;'>{$heading}</h2>
            <div style='color:#5B6B79;font-size:14px;line-height:1.6;'>{$bodyHtml}</div>
          </td></tr>
          {$button}
          <tr><td style='padding:20px 32px;background:#F8F9FB;border-top:1px solid #E3E7ED;'>
            <p style='color:#8A97A6;font-size:12px;margin:0;'>Vous recevez cet e-mail car une action a été initiée sur votre compte Lien. Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer ce message.</p>
          </td></tr>
        </table>
      </td></tr>
    </table>";
}

function sendVerificationEmail(string $email, string $firstName, string $token): bool {
    $link = SITE_URL . "/vues/clients/verification.html?token=" . urlencode($token);
    $body = emailTemplate(
        "Bienvenue sur Lien, {$firstName} !",
        "Plus qu'une étape avant de retrouver vos amis : confirmez votre adresse e-mail en cliquant sur le bouton ci-dessous. Ce lien est valable 24 heures.",
        "Confirmer mon e-mail",
        $link
    );
    return sendMail($email, $firstName, "Confirmez votre adresse e-mail — Lien", $body);
}

function sendPasswordResetEmail(string $email, string $firstName, string $token): bool {
    $link = SITE_URL . "/vues/clients/reinitialiser-mot-de-passe.html?token=" . urlencode($token);
    $body = emailTemplate(
        "Réinitialisation de votre mot de passe",
        "Vous avez demandé à réinitialiser votre mot de passe Lien. Cliquez sur le bouton ci-dessous pour en choisir un nouveau. Ce lien expire dans 1 heure.",
        "Réinitialiser mon mot de passe",
        $link
    );
    return sendMail($email, $firstName, "Réinitialisation de votre mot de passe — Lien", $body);
}
