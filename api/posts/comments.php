<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';

requireAuth();
$postId = (int)($_GET['post_id'] ?? 0);
if (!$postId) jsonError('Publication invalide.');

$pdo = getDB();
$stmt = $pdo->prepare(
    "SELECT c.id, c.content, c.created_at, u.id AS user_id, u.first_name, u.last_name, u.avatar
     FROM post_comments c JOIN users u ON u.id = c.user_id
     WHERE c.post_id = ? ORDER BY c.created_at ASC"
);
$stmt->execute([$postId]);
jsonResponse(['success' => true, 'comments' => $stmt->fetchAll()]);
