<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Méthode non autorisée.', 405);

$body = getJsonBody();
$token = $body['token'] ?? '';
$password = $body['password'] ?? '';

if (!$token) jsonError('Jeton manquant.');
if (strlen($password) < 8) jsonError('Le mot de passe doit contenir au moins 8 caractères.');

$pdo = getDB();
$stmt = $pdo->prepare(
    "SELECT * FROM email_tokens WHERE token = ? AND type = 'reset' AND used = 0 AND expires_at > NOW()"
);
$stmt->execute([$token]);
$row = $stmt->fetch();
if (!$row) jsonError('Ce lien de réinitialisation est invalide ou a expiré.', 410);

$hash = password_hash($password, PASSWORD_DEFAULT);
$pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $row['user_id']]);
$pdo->prepare("UPDATE email_tokens SET used = 1 WHERE id = ?")->execute([$row['id']]);
// Invalide toutes les sessions actives par sécurité
$pdo->prepare("DELETE FROM sessions WHERE user_id = ?")->execute([$row['user_id']]);

jsonResponse(['success' => true, 'message' => 'Votre mot de passe a été réinitialisé. Vous pouvez vous connecter.']);
