<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';

$user = requireAuth();
$pdo = getDB();

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$filterUserId = (int)($_GET['user_id'] ?? 0);

// Flux : publications publiques + publications "amis" si lien d'amitié accepté
$sql = "SELECT p.*, u.first_name, u.last_name, u.avatar,
          (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.id) AS like_count,
          (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id = p.id) AS comment_count,
          EXISTS(SELECT 1 FROM post_likes pl2 WHERE pl2.post_id = p.id AND pl2.user_id = ?) AS liked_by_me
        FROM posts p
        JOIN users u ON u.id = p.user_id
        WHERE (p.visibility = 'public'
           OR p.user_id = ?
           OR EXISTS (
              SELECT 1 FROM friendships f
              WHERE f.status = 'accepted'
                AND ((f.requester_id = ? AND f.addressee_id = p.user_id)
                  OR (f.addressee_id = ? AND f.requester_id = p.user_id))
           ))"
        . ($filterUserId ? " AND p.user_id = ?" : "") . "
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
$params = [$user['id'], $user['id'], $user['id'], $user['id']];
if ($filterUserId) $params[] = $filterUserId;
$params[] = $limit;
$params[] = $offset;
$stmt->execute($params);
$posts = $stmt->fetchAll();

$posts = array_map(function($p){
    $p['id'] = (int)$p['id'];
    $p['user_id'] = (int)$p['user_id']; 
    $p['like_count'] = (int)$p['like_count'];
    $p['comment_count'] = (int)$p['comment_count'];
    $p['liked_by_me'] = (bool)$p['liked_by_me'];
    return $p;
}, $posts);

jsonResponse(['success' => true, 'posts' => $posts]);
