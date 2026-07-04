<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';

requireRole(['admin','moderator']);
$pdo = getDB();

$stats = [
    'total_users' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
    'total_posts' => (int)$pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn(),
    'total_comments' => (int)$pdo->query("SELECT COUNT(*) FROM post_comments")->fetchColumn(),
    'total_likes' => (int)$pdo->query("SELECT COUNT(*) FROM post_likes")->fetchColumn(),
    'total_messages' => (int)$pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn(),
    'banned_users' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'banned'")->fetchColumn(),
    'new_users_7d' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='user' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn(),
    'posts_7d' => (int)$pdo->query("SELECT COUNT(*) FROM posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn(),
];

// Évolution des inscriptions sur les 7 derniers jours (pour un graphique)
$stmt = $pdo->query(
    "SELECT DATE(created_at) AS day, COUNT(*) AS count FROM users
     WHERE role = 'user' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY DATE(created_at) ORDER BY day"
);
$stats['signups_chart'] = $stmt->fetchAll();

jsonResponse(['success' => true, 'stats' => $stats]);
