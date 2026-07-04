<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Méthode non autorisée.', 405);

$body = getJsonBody();
$firstName = trim($body['first_name'] ?? '');
$lastName  = trim($body['last_name'] ?? '');
$email     = trim(strtolower($body['email'] ?? ''));
$password  = $body['password'] ?? '';

if (!$firstName || !$lastName) jsonError('Le prénom et le nom sont requis.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonError('Adresse e-mail invalide.');
if (strlen($password) < 8) jsonError('Le mot de passe doit contenir au moins 8 caractères.');

$pdo = getDB();

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) jsonError('Cette adresse e-mail est déjà utilisée.', 409);

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare(
    "INSERT INTO users (first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?)"
);
$stmt->execute([$firstName, $lastName, $email, $hash]);
$userId = (int)$pdo->lastInsertId();

// Génère un jeton de vérification d'e-mail (valable 24h)
$token = generateToken();
$stmt = $pdo->prepare(
    "INSERT INTO email_tokens (user_id, token, type, expires_at) VALUES (?, ?, 'verify', DATE_ADD(NOW(), INTERVAL 24 HOUR))"
);
$stmt->execute([$userId, $token]);

$mailSent = sendVerificationEmail($email, $firstName, $token);

jsonResponse([
    'success' => true,
    'message' => $mailSent
        ? "Compte créé. Vérifiez votre boîte e-mail pour confirmer votre adresse."
        : "Compte créé, mais l'e-mail de confirmation n'a pas pu être envoyé (configuration SMTP manquante).",
]);
