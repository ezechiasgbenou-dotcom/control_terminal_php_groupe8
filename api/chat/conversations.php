<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';

$user = requireAuth();
$pdo = getDB();

$stmt = $pdo->prepare(
    "SELECT c.id,
       IF(c.user_one_id = ?, c.user_two_id, c.user_one_id) AS other_id,
       (SELECT content FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) AS last_message,
       (SELECT created_at FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) AS last_time,
       (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.is_read = 0 AND m.sender_id != ?) AS unread
     FROM conversations c
     WHERE c.user_one_id = ? OR c.user_two_id = ?
     ORDER BY last_time DESC"
);
$stmt->execute([$user['id'], $user['id'], $user['id'], $user['id']]);
$rows = $stmt->fetchAll();

$result = [];
foreach ($rows as $r) {
    $u = $pdo->prepare("SELECT id, first_name, last_name, avatar, last_seen FROM users WHERE id = ?");
    $u->execute([$r['other_id']]);
    $other = $u->fetch();
    $result[] = [
        'conversation_id' => (int)$r['id'],
        'user' => [
            'id' => (int)$other['id'],
            'first_name' => $other['first_name'],
            'last_name' => $other['last_name'],
            'avatar' => $other['avatar'],
            'online' => $other['last_seen'] && (strtotime($other['last_seen']) > time() - 120),
        ],
        'last_message' => $r['last_message'],
        'last_time' => $r['last_time'],
        'unread' => (int)$r['unread'],
    ];
}

jsonResponse(['success' => true, 'conversations' => $result]);
