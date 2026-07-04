<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';

// Protection de la route et récupération de la connexion PDO
$user = requireAuth();
$pdo = getDB();

$query = isset($_GET['q']) ? trim($_GET['q']) : '';

$usersResult = [];
$postsResult = [];

if ($query !== '') {
    $searchTerm = '%' . $query . '%';
    
    // 1. RECHERCHE DES MEMBRES (Prénom ou Nom)
    try {
        $stmtUsers = $pdo->prepare("
            SELECT id, first_name, last_name, avatar, last_seen 
            FROM users 
            WHERE first_name LIKE ? OR last_name LIKE ? 
            LIMIT 8
        ");
        $stmtUsers->execute([$searchTerm, $searchTerm]);
        $users = $stmtUsers->fetchAll();
        
        foreach ($users as $u) {
            $usersResult[] = [
                'id' => (int)$u['id'],
                'first_name' => $u['first_name'],
                'last_name' => $u['last_name'],
                'avatar' => $u['avatar'],
                'online' => $u['last_seen'] && (strtotime($u['last_seen']) > time() - 120)
            ];
        }
    } catch (Throwable $e) {
        $usersResult = []; // Reste sécurisé si la table users pose problème
    }

    // 2. RECHERCHE DES PUBLICATIONS (Contenu)
    try {
        $stmtPosts = $pdo->prepare("
            SELECT p.id, p.content, p.created_at, u.id AS author_id, u.first_name, u.last_name, u.avatar 
            FROM posts p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.content LIKE ? 
            ORDER BY p.created_at DESC 
            LIMIT 10
        ");
        
        // CORRECTION ICI : Ajout du $ oublié devant searchTerm
        $stmtPosts->execute([$searchTerm]); 
        
        $posts = $stmtPosts->fetchAll();
        
        foreach ($posts as $p) {
            $postsResult[] = [
                'id' => (int)$p['id'],
                'content' => $p['content'],
                'created_at' => $p['created_at'],
                'author' => [
                    'id' => (int)$p['author_id'],
                    'first_name' => $p['first_name'],
                    'last_name' => $p['last_name'],
                    'avatar' => $p['avatar']
                ]
            ];
        }
    } catch (Throwable $e) {
        $postsResult = []; // Reste silencieux si la table posts n'existe pas ou diffère
    }
}

// Réponse structurée propre transmise à api.js
jsonResponse([
    'success' => true, 
    'users' => $usersResult, 
    'posts' => $postsResult
]);