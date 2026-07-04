<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Méthode non autorisée.', 405);
$user = requireAuth();

$pdo = getDB();
// Supprime toutes les sessions actives puis l'utilisateur (CASCADE supprime le reste)
$pdo->prepare("DELETE FROM sessions WHERE user_id = ?")->execute([$user['id']]);
$pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user['id']]);

jsonResponse(['success' => true, 'message' => 'Compte supprimé.']);
