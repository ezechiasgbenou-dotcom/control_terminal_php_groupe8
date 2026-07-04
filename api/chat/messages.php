<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';

$user = requireAuth();
$otherId = (int)($_GET['user_id'] ?? 0);
if (!$otherId) jsonError('Utilisateur invalide.');

$pdo = getDB();
$conv = getOrCreateConversation($pdo, (int)$user['id'], $otherId);

$stmt = $pdo->prepare(
    "SELECT id, sender_id, content, image, created_at FROM messages WHERE conversation_id = ? ORDER BY created_at ASC"
);
$stmt->execute([$conv]);
$messages = $stmt->fetchAll();

$pdo->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ?")
    ->execute([$conv, $user['id']]);

jsonResponse(['success' => true, 'conversation_id' => $conv, 'messages' => array_map(function($m) use ($user){
    $m['id'] = (int)$m['id'];
    $m['from_me'] = ((int)$m['sender_id'] === (int)$user['id']);
    return $m;
}, $messages)]);

function getOrCreateConversation(PDO $pdo, int $a, int $b): int {
    [$u1, $u2] = $a < $b ? [$a, $b] : [$b, $a];
    $stmt = $pdo->prepare("SELECT id FROM conversations WHERE user_one_id = ? AND user_two_id = ?");
    $stmt->execute([$u1, $u2]);
    if ($row = $stmt->fetch()) return (int)$row['id'];
    $pdo->prepare("INSERT INTO conversations (user_one_id, user_two_id) VALUES (?, ?)")->execute([$u1, $u2]);
    return (int)$pdo->lastInsertId();
}
