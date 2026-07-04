<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';

$user = requireAuth();
$pdo = getDB();

// Tous les utilisateurs sauf moi, avec le statut de relation
$stmt = $pdo->prepare(
    "SELECT u.id, u.first_name, u.last_name, u.avatar, u.city,
       f.status AS friendship_status, f.requester_id
     FROM users u
     LEFT JOIN friendships f ON
       (f.requester_id = ? AND f.addressee_id = u.id) OR
       (f.addressee_id = ? AND f.requester_id = u.id)
     WHERE u.id != ? AND u.role = 'user' AND u.status = 'active'
     ORDER BY u.first_name"
);
$stmt->execute([$user['id'], $user['id'], $user['id']]);
$rows = $stmt->fetchAll();

$result = array_map(function($r) use ($user) {
    $status = $r['friendship_status'] ?? 'none';
    $direction = null;
    if ($status === 'pending') {
        $direction = ((int)$r['requester_id'] === (int)$user['id']) ? 'sent' : 'received';
    }
    return [
        'id' => (int)$r['id'],
        'first_name' => $r['first_name'],
        'last_name' => $r['last_name'],
        'avatar' => $r['avatar'],
        'city' => $r['city'],
        'status' => $status,       // none | pending | accepted | declined
        'direction' => $direction, // sent | received | null
    ];
}, $rows);

jsonResponse(['success' => true, 'users' => $result]);
