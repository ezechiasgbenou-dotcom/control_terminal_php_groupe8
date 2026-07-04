<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/notifications.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Méthode non autorisée.', 405);
$user = requireAuth();

$body = getJsonBody();
$postId = (int)($body['post_id'] ?? 0);
if (!$postId) jsonError('Publication invalide.');

$pdo = getDB();
$stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ?");
$stmt->execute([$postId]);
if (!$stmt->fetch()) jsonError('Publication introuvable.', 404);

$stmt = $pdo->prepare("SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?");
$stmt->execute([$postId, $user['id']]);

if ($stmt->fetch()) {
    $pdo->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?")->execute([$postId, $user['id']]);
    $liked = false;
} else {
    $pdo->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)")->execute([$postId, $user['id']]);
    $liked = true;
    // Notifie le propriétaire du post
    $owner = $pdo->prepare("SELECT user_id FROM posts WHERE id=?");
    $owner->execute([$postId]);
    if ($row = $owner->fetch()) {
        createNotification($pdo, (int)$row['user_id'], (int)$user['id'], 'like', $postId);
    }
}

$count = $pdo->prepare("SELECT COUNT(*) FROM post_likes WHERE post_id = ?");
$count->execute([$postId]);

jsonResponse(['success' => true, 'liked' => $liked, 'like_count' => (int)$count->fetchColumn()]);
