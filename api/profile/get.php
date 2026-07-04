<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';

$me = requireAuth();
$pdo = getDB();
$targetId = (int)($_GET['id'] ?? $me['id']);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'user'");
$stmt->execute([$targetId]);
$target = $stmt->fetch();
if (!$target) jsonError('Utilisateur introuvable.', 404);

$friendCount = $pdo->prepare(
    "SELECT COUNT(*) FROM friendships WHERE status = 'accepted' AND (requester_id = ? OR addressee_id = ?)"
);
$friendCount->execute([$targetId, $targetId]);

$profile = publicUser($target);
$profile['friend_count'] = (int)$friendCount->fetchColumn();
$profile['is_me'] = ((int)$targetId === (int)$me['id']);

jsonResponse(['success' => true, 'profile' => $profile]);
