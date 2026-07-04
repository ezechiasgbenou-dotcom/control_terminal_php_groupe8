<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/notifications.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Méthode non autorisée.', 405);
$user = requireAuth();

$body = getJsonBody();
$requesterId = (int)($body['user_id'] ?? 0);
$action = $body['action'] ?? ''; // accept | decline

if (!$requesterId || !in_array($action, ['accept', 'decline'], true)) jsonError('Requête invalide.');

$pdo = getDB();
$stmt = $pdo->prepare(
    "SELECT * FROM friendships WHERE requester_id = ? AND addressee_id = ? AND status = 'pending'"
);
$stmt->execute([$requesterId, $user['id']]);
$friendship = $stmt->fetch();
if (!$friendship) jsonError('Aucune demande en attente trouvée.', 404);

$newStatus = $action === 'accept' ? 'accepted' : 'declined';
$pdo->prepare("UPDATE friendships SET status = ?, responded_at = NOW() WHERE id = ?")
    ->execute([$newStatus, $friendship['id']]);

if ($newStatus === 'accepted') {
    // Notifie l'expéditeur de la demande que c'est accepté
    createNotification($pdo, (int)$friendship['requester_id'], (int)$user['id'], 'friend_accept');
}

jsonResponse(['success' => true, 'status' => $newStatus]);
