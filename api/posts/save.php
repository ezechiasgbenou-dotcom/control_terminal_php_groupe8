<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Méthode non autorisée.', 405);
$user = requireAuth();

$body = getJsonBody();
$postId = (int)($body['post_id'] ?? 0);
if (!$postId) jsonError('Publication invalide.');

$pdo = getDB();
$stmt = $pdo->prepare("SELECT id FROM saved_posts WHERE user_id=? AND post_id=?");
$stmt->execute([$user['id'], $postId]);

if ($stmt->fetch()) {
    $pdo->prepare("DELETE FROM saved_posts WHERE user_id=? AND post_id=?")->execute([$user['id'], $postId]);
    jsonResponse(['success' => true, 'saved' => false]);
} else {
    $pdo->prepare("INSERT INTO saved_posts (user_id, post_id) VALUES (?,?)")->execute([$user['id'], $postId]);
    jsonResponse(['success' => true, 'saved' => true]);
}
