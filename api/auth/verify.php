<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';

$token = $_GET['token'] ?? (getJsonBody()['token'] ?? '');
if (!$token) jsonError('Jeton manquant.');

$pdo = getDB();
$stmt = $pdo->prepare(
    "SELECT * FROM email_tokens WHERE token = ? AND type = 'verify' AND used = 0 AND expires_at > NOW()"
);
$stmt->execute([$token]);
$row = $stmt->fetch();

if (!$row) jsonError('Ce lien de confirmation est invalide ou a expiré.', 410);

$pdo->prepare("UPDATE users SET email_verified = 1 WHERE id = ?")->execute([$row['user_id']]);
$pdo->prepare("UPDATE email_tokens SET used = 1 WHERE id = ?")->execute([$row['id']]);

jsonResponse(['success' => true, 'message' => 'Votre adresse e-mail a été confirmée. Vous pouvez maintenant vous connecter.']);
