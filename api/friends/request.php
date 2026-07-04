<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/notifications.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Méthode non autorisée.', 405);
$user = requireAuth();

$body = getJsonBody();
$targetId = (int)($body['user_id'] ?? 0);
if (!$targetId || $targetId === (int)$user['id']) jsonError('Utilisateur invalide.');

$pdo = getDB();
$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$targetId]);
if (!$stmt->fetch()) jsonError('Utilisateur introuvable.', 404);

$check = $pdo->prepare(
    "SELECT id FROM friendships WHERE (requester_id = ? AND addressee_id = ?) OR (requester_id = ? AND addressee_id = ?)"
);
$check->execute([$user['id'], $targetId, $targetId, $user['id']]);
if ($check->fetch()) jsonError('Une relation existe déjà avec cet utilisateur.', 409);

$pdo->prepare("INSERT INTO friendships (requester_id, addressee_id, status) VALUES (?, ?, 'pending')")
    ->execute([$user['id'], $targetId]);
createNotification($pdo, $targetId, (int)$user['id'], 'friend_request');
jsonResponse(['success' => true, 'message' => "Demande d'amitié envoyée."]);
