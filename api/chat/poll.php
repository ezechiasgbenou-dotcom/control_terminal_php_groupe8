<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';

$user = requireAuth();
$otherId = (int)($_GET['user_id'] ?? 0);
$sinceId = (int)($_GET['since'] ?? 0);
if (!$otherId) jsonError('Utilisateur invalide.');

// Marque l'utilisateur comme actif (utile pour le statut "en ligne")
getDB()->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?")->execute([$user['id']]);

[$u1, $u2] = $user['id'] < $otherId ? [$user['id'], $otherId] : [$otherId, $user['id']];
$pdo = getDB();
$stmt = $pdo->prepare("SELECT id FROM conversations WHERE user_one_id = ? AND user_two_id = ?");
$stmt->execute([$u1, $u2]);
$conv = $stmt->fetch();

if (!$conv) jsonResponse(['success' => true, 'messages' => []]);

$stmt = $pdo->prepare(
    "SELECT id, sender_id, content, image, created_at FROM messages
     WHERE conversation_id = ? AND id > ? ORDER BY created_at ASC"
);
$stmt->execute([$conv['id'], $sinceId]);
$messages = $stmt->fetchAll();

$pdo->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ?")
    ->execute([$conv['id'], $user['id']]);

jsonResponse(['success' => true, 'messages' => array_map(function($m) use ($user){
    $m['id'] = (int)$m['id'];
    $m['from_me'] = ((int)$m['sender_id'] === (int)$user['id']);
    return $m;
}, $messages)]);
