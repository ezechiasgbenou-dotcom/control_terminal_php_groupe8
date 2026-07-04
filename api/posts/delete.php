<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Méthode non autorisée.', 405);
$user = requireAuth();

$body = getJsonBody();
$postId = (int)($body['post_id'] ?? 0);
if (!$postId) jsonError('Publication invalide.');

$pdo = getDB();
$stmt = $pdo->prepare("SELECT user_id, image FROM posts WHERE id = ?");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post) jsonError('Publication introuvable.', 404);
if ((int)$post['user_id'] !== (int)$user['id']) jsonError('Vous ne pouvez supprimer que vos propres publications.', 403);

// Supprime l'image associée si elle existe
if ($post['image']) {
    $imgPath = __DIR__ . '/../../' . $post['image'];
    if (file_exists($imgPath)) unlink($imgPath);
}

$pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$postId]);

jsonResponse(['success' => true, 'message' => 'Publication supprimée.']);
