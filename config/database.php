<?php
/**
 * Connexion à la base de données "social_network"
 * Détection et adaptation automatique selon l'environnement (Local vs Alwaysdata)
 */

if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
    // 💻 CONFIGURATION LOCAL (XAMPP)
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_NAME', getenv('DB_NAME') ?: 'social_network'); //
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') ?: '');
} else {
    // 🌐 CONFIGURATION PRODUCTION (Alwaysdata)
    define('DB_HOST', getenv('DB_HOST') ?: 'mysql-gbenou.alwaysdata.net'); // Regarde l'hôte exact dans l'onglet MySQL
    define('DB_NAME', getenv('DB_NAME') ?: 'gbenou_social_network');      // Le nom complet avec le préfixe
    define('DB_USER', getenv('DB_USER') ?: 'gbenou');                     // Ton utilisateur MySQL Always data
    define('DB_PASS', getenv('DB_PASS') ?: 'Ezeboss123'); 
}
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Connexion à la base de données impossible.']);
            exit;
        }
    }
    return $pdo;
}