<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';

$token = getBearerToken();
if ($token) {
    getDB()->prepare("DELETE FROM sessions WHERE token = ?")->execute([$token]);
}
jsonResponse(['success' => true, 'message' => 'Déconnexion réussie.']);
