<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';

requireRole(['admin','moderator']);
$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query(
        "SELECT id, first_name, last_name, email, avatar, status, role, email_verified, created_at
         FROM users WHERE role = 'user' ORDER BY created_at DESC"
    );
    jsonResponse(['success' => true, 'users' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $body = getJsonBody();
    $userId = (int)($body['user_id'] ?? 0);
    $action = $body['action'] ?? ''; // ban | unban | delete
    if (!$userId || !in_array($action, ['ban','unban','delete'], true)) jsonError('Requête invalide.');

    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $target = $stmt->fetch();
    if (!$target) jsonError('Utilisateur introuvable.', 404);
    if ($target['role'] !== 'user') jsonError("Les modérateurs ne peuvent pas agir sur ce compte.", 403);

    if ($action === 'ban') {
        $pdo->prepare("UPDATE users SET status = 'banned' WHERE id = ?")->execute([$userId]);
        $pdo->prepare("DELETE FROM sessions WHERE user_id = ?")->execute([$userId]);
    } elseif ($action === 'unban') {
        $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?")->execute([$userId]);
    } else {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
    }
    jsonResponse(['success' => true, 'message' => "Action '$action' effectuée."]);
}

jsonError('Méthode non autorisée.', 405);
