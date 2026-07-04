<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';

$admin = requireRole(['admin']); // réservé à l'administrateur
$pdo = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query(
        "SELECT id, first_name, last_name, email, role, status, created_at FROM users
         WHERE role IN ('admin','moderator') ORDER BY role, created_at"
    );
    jsonResponse(['success' => true, 'staff' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $body = getJsonBody();
    $action = $body['action'] ?? ''; // add | remove

    if ($action === 'add') {
        $firstName = trim($body['first_name'] ?? '');
        $lastName = trim($body['last_name'] ?? '');
        $email = trim(strtolower($body['email'] ?? ''));
        $password = $body['password'] ?? '';
        $role = $body['role'] ?? 'moderator';

        if (!$firstName || !$lastName) jsonError('Prénom et nom requis.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonError('Adresse e-mail invalide.');
        if (strlen($password) < 8) jsonError('Le mot de passe doit contenir au moins 8 caractères.');
        if (!in_array($role, ['moderator','admin'], true)) jsonError('Rôle invalide.');

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) jsonError('Cette adresse e-mail est déjà utilisée.', 409);

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare(
            "INSERT INTO users (first_name,last_name,email,password_hash,role,email_verified) VALUES (?,?,?,?,?,1)"
        )->execute([$firstName, $lastName, $email, $hash, $role]);

        jsonResponse(['success' => true, 'message' => ucfirst($role) . ' ajouté avec succès.']);
    }

    if ($action === 'remove') {
        $targetId = (int)($body['user_id'] ?? 0);
        if (!$targetId) jsonError('Utilisateur invalide.');
        if ($targetId === (int)$admin['id']) jsonError('Vous ne pouvez pas supprimer votre propre compte.');
        $pdo->prepare("DELETE FROM users WHERE id = ? AND role IN ('admin','moderator')")->execute([$targetId]);
        jsonResponse(['success' => true, 'message' => 'Membre du staff supprimé.']);
    }

    jsonError("Action invalide.");
}

jsonError('Méthode non autorisée.', 405);
