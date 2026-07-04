<?php
/**
 * Authentification par jeton (équivalent serveur du sessionStorage JS).
 * Le frontend stocke le jeton retourné par /api/auth/login.php dans
 * sessionStorage et le renvoie via l'en-tête "Authorization: Bearer <token>".
 */
require_once __DIR__ . '/database.php';

function generateToken(int $bytes = 48): string {
    return bin2hex(random_bytes($bytes));
}

function createSession(int $userId): string {
    $pdo = getDB();
    $token = generateToken();
    $stmt = $pdo->prepare(
        "INSERT INTO sessions (user_id, token, user_agent, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))"
    );
    $stmt->execute([$userId, $token, $_SERVER['HTTP_USER_AGENT'] ?? '']);
    return $token;
}

function getBearerToken(): ?string {
    // Méthode 1 : variables serveur (Apache classique)
    $header = $_SERVER['HTTP_AUTHORIZATION']
           ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
           ?? '';

    // Méthode 2 : getallheaders() (XAMPP Windows)
    if (!$header && function_exists('getallheaders')) {
        $headers = getallheaders();
        $header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if (preg_match('/Bearer\s+(\S+)/', $header, $m)) return $m[1];
    return null;
}

/** Retourne l'utilisateur courant ou null si non authentifié */
function currentUser(): ?array {
    $token = getBearerToken();
    if (!$token) return null;

    $pdo = getDB();
    $stmt = $pdo->prepare(
        "SELECT u.* FROM sessions s
         JOIN users u ON u.id = s.user_id
         WHERE s.token = ? AND s.expires_at > NOW()"
    );
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    return $user ?: null;
}

/** Bloque la requête si l'utilisateur n'est pas authentifié */
function requireAuth(): array {
    $user = currentUser();
    if (!$user) jsonError('Authentification requise.', 401);
    if ($user['status'] === 'banned') jsonError('Ce compte a été suspendu.', 403);
    return $user;
}

/** Bloque la requête si l'utilisateur n'a pas l'un des rôles autorisés */
function requireRole(array $roles): array {
    $user = requireAuth();
    if (!in_array($user['role'], $roles, true)) {
        jsonError('Accès refusé : privilèges insuffisants.', 403);
    }
    return $user;
}

function publicUser(array $u): array {
    return [
        'id' => (int)$u['id'],
        'first_name' => $u['first_name'],
        'last_name' => $u['last_name'],
        'email' => $u['email'],
        'avatar' => $u['avatar'],
        'cover' => $u['cover'] ?? null,
        'bio' => $u['bio'] ?? '',
        'job' => $u['job'] ?? '',
        'school' => $u['school'] ?? '',
        'city' => $u['city'] ?? '',
        'role' => $u['role'],
        'created_at' => $u['created_at'],
    ];
}
