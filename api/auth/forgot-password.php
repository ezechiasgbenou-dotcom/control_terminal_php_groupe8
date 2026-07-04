<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Méthode non autorisée.', 405);

$body = getJsonBody();
$email = trim(strtolower($body['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonError('Adresse e-mail invalide.');

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

// Réponse identique que le compte existe ou non, pour ne pas divulguer les e-mails enregistrés
$response = ['success' => true, 'message' => "Si un compte correspond à cette adresse, un e-mail de réinitialisation vient d'être envoyé."];

if ($user) {
    $token = generateToken();
    $pdo->prepare(
        "INSERT INTO email_tokens (user_id, token, type, expires_at) VALUES (?, ?, 'reset', DATE_ADD(NOW(), INTERVAL 1 HOUR))"
    )->execute([$user['id'], $token]);
    sendPasswordResetEmail($user['email'], $user['first_name'], $token);
}

jsonResponse($response);
