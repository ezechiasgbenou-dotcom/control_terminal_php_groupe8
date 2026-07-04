<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';

requireRole(['admin','moderator']);
$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query(
        "SELECT p.id, p.content, p.image, p.created_at, u.first_name, u.last_name, u.avatar,
           (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.id) AS like_count,
           (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id = p.id) AS comment_count
         FROM posts p JOIN users u ON u.id = p.user_id
         ORDER BY p.created_at DESC LIMIT 100"
    );
    jsonResponse(['success' => true, 'posts' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $body = getJsonBody();
    $postId = (int)($body['post_id'] ?? 0);
    if (!$postId) jsonError('Publication invalide.');
    $pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$postId]);
    jsonResponse(['success' => true, 'message' => 'Publication supprimée.']);
}

jsonError('Méthode non autorisée.', 405);
