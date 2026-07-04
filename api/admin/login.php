<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Méthode non autorisée.', 405);

$body = getJsonBody();
$email = trim(strtolower($body['email'] ?? ''));
$password = $body['password'] ?? '';
if (!$email || !$password) jsonError('Adresse e-mail et mot de passe requis.');

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    jsonError('Identifiants incorrects.', 401);
}
if (!in_array($user['role'], ['admin','moderator'], true)) {
    jsonError("Ce compte n'a pas accès au back-office.", 403);
}
if ($user['status'] === 'banned') jsonError('Ce compte a été suspendu.', 403);

$token = createSession((int)$user['id']);
jsonResponse(['success' => true, 'token' => $token, 'user' => publicUser($user)]);
