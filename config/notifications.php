<?php
/**
 * Crée une notification.
 * Appelé par les autres endpoints (like, comment, friend_request…)
 */
function createNotification(PDO $pdo, int $userId, int $actorId, string $type, ?int $postId = null): void {
    // Pas de notification à soi-même
    if ($userId === $actorId) return;
    // Pour les messages et likes, on n'empile pas les doublons non lus
    if (in_array($type, ['like','message'], true)) {
        $stmt = $pdo->prepare(
            "SELECT id FROM notifications WHERE user_id=? AND actor_id=? AND type=? AND post_id<=>? AND is_read=0"
        );
        $stmt->execute([$userId, $actorId, $type, $postId]);
        if ($stmt->fetch()) return; // déjà une notif non lue identique
    }
    $pdo->prepare(
        "INSERT INTO notifications (user_id, actor_id, type, post_id) VALUES (?,?,?,?)"
    )->execute([$userId, $actorId, $type, $postId]);
}
