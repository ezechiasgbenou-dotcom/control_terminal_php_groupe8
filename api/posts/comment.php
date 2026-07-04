<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/notifications.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Méthode non autorisée.', 405);
$user = requireAuth();

$body = getJsonBody();
$postId = (int)($body['post_id'] ?? 0);
$content = trim($body['content'] ?? '');

if (!$postId) jsonError('Publication invalide.');
if ($content === '') jsonError('Le commentaire ne peut pas être vide.');
if (strlen($content) > 500) jsonError('Le commentaire est trop long (500 caractères maximum).');

$pdo = getDB();
$stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
$stmt->execute([$postId]);
if (!$stmt->fetch()) jsonError('Publication introuvable.', 404);

$stmt = $pdo->prepare("INSERT INTO post_comments (post_id, user_id, content) VALUES (?, ?, ?)");
$stmt->execute([$postId, $user['id'], $content]);

// Notifie le propriétaire du post
$owner = $pdo->prepare("SELECT user_id FROM posts WHERE id=?");
$owner->execute([$postId]);
if ($row = $owner->fetch()) {
    createNotification($pdo, (int)$row['user_id'], (int)$user['id'], 'comment', $postId);
}

jsonResponse(['success' => true, 'comment' => [
    'id' => (int)$pdo->lastInsertId(),
    'content' => $content,
    'first_name' => $user['first_name'],
    'last_name' => $user['last_name'],
    'avatar' => $user['avatar'],
    'created_at' => date('Y-m-d H:i:s'),
]]);
