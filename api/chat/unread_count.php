<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';

$user = requireAuth();
$pdo = getDB();

try {
    // On compte tous les messages non lus reçus par l'utilisateur connecté
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS unread 
        FROM messages m
        JOIN conversations c ON m.conversation_id = c.id
        WHERE m.is_read = 0 
          AND m.sender_id != ? 
          AND (c.user_one_id = ? OR c.user_two_id = ?)
    ");
    $stmt->execute([$user['id'], $user['id'], $user['id']]);
    $row = $stmt->fetch();

    jsonResponse([
        'success' => true,
        'unread_count' => (int)($row['unread'] ?? 0)
    ]);
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'unread_count' => 0,
        'error' => $e->getMessage()
    ]);
}