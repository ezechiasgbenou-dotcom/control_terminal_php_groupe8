<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';

$user = requireAuth();
$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // 1. Récupération des notifications
    $stmt = $pdo->prepare(
        "SELECT n.id, n.type, n.is_read, n.post_id, n.created_at,
                a.id AS actor_id, a.first_name, a.last_name, a.avatar
         FROM notifications n
         JOIN users a ON a.id = n.actor_id
         WHERE n.user_id = ?
         ORDER BY n.created_at DESC
         LIMIT 30"
    );
    $stmt->execute([$user['id']]);
    $rows = $stmt->fetchAll();

    // Correction propre du compteur (Une seule requête préparée, rapide et sécurisée)
    $stmtUnread = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmtUnread->execute([$user['id']]);
    $unread = (int)$stmtUnread->fetchColumn();

    // Message humain selon le type
    $labels = [
        'like'           => 'a aimé votre publication',
        'comment'        => 'a commenté votre publication',
        'friend_request' => 'vous a envoyé une demande d\'amitié',
        'friend_accept'  => 'a accepté votre demande d\'amitié',
        'message'        => 'vous a envoyé un message',
    ];
    $icons = [
        'like'           => 'ph-heart',
        'comment'        => 'ph-chat-circle',
        'friend_request' => 'ph-user-plus',
        'friend_accept'  => 'ph-users',
        'message'        => 'ph-chat-circle-dots',
    ];

    $rows = array_map(function($n) use ($labels, $icons) {
        $n['label'] = $labels[$n['type']] ?? $n['type'];
        $n['icon']  = $icons[$n['type']] ?? 'ph-bell';
        $n['is_read'] = (bool)$n['is_read']; // Convertit le 0 ou 1 de la bdd en vrai true/false pour le JS
        return $n;
    }, $rows);

    jsonResponse(['success' => true, 'notifications' => $rows, 'unread' => $unread]);
}

if ($method === 'POST') {
    $body = getJsonBody();
    $action = $body['action'] ?? '';

    if ($action === 'mark_read') {
        $id = (int)($body['id'] ?? 0);
        if ($id) {
            // Marque une seule notification comme lue
            $pdo->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?")->execute([$id, $user['id']]);
        } else {
            // Marque TOUTES les notifications comme lues
            $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$user['id']]);
        }
        jsonResponse(['success' => true]);
    }
    jsonError('Action invalide.');
}

jsonError('Méthode non autorisée.', 405);