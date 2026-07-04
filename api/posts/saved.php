<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';

$user = requireAuth();
$pdo = getDB();

$stmt = $pdo->prepare(
    "SELECT p.*, u.first_name, u.last_name, u.avatar,
       (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id=p.id) AS like_count,
       (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id=p.id) AS comment_count,
       EXISTS(SELECT 1 FROM post_likes pl2 WHERE pl2.post_id=p.id AND pl2.user_id=?) AS liked_by_me,
       1 AS saved
     FROM saved_posts sp
     JOIN posts p ON p.id = sp.post_id
     JOIN users u ON u.id = p.user_id
     WHERE sp.user_id = ?
     ORDER BY sp.created_at DESC"
);
$stmt->execute([$user['id'], $user['id']]);
$posts = $stmt->fetchAll();

$posts = array_map(function($p){
    $p['id'] = (int)$p['id'];
    $p['like_count'] = (int)$p['like_count'];
    $p['comment_count'] = (int)$p['comment_count'];
    $p['liked_by_me'] = (bool)$p['liked_by_me'];
    return $p;
}, $posts);

jsonResponse(['success' => true, 'posts' => $posts]);
