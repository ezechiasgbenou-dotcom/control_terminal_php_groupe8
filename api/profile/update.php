<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Méthode non autorisée.', 405);
$user = requireAuth();
$pdo = getDB();

$isMultipart = str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data');
$data = $isMultipart ? $_POST : getJsonBody();

$fields = [];
$params = [];
foreach (['first_name','last_name','bio','job','school','city'] as $f) {
    if (isset($data[$f])) { $fields[] = "$f = ?"; $params[] = trim($data[$f]); }
}

// Changement de mot de passe (optionnel)
if (!empty($data['password'])) {
    if (strlen($data['password']) < 8) jsonError('Le nouveau mot de passe doit contenir au moins 8 caractères.');
    $fields[] = "password_hash = ?";
    $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
}

// Upload de la photo de profil
function handleUpload(string $field, string $folder, int $userId): ?string {
    if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = mime_content_type($_FILES[$field]['tmp_name']);
    if (!isset($allowed[$mime])) jsonError("Format d'image non pris en charge pour $field.");
    $filename = $field . '_' . $userId . '_' . time() . '.' . $allowed[$mime];
    $dest = __DIR__ . "/../../assets/uploads/$folder/$filename";
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) jsonError("Échec de l'envoi de l'image.", 500);
    return "assets/uploads/$folder/$filename";
}

if ($isMultipart) {
    if ($avatar = handleUpload('avatar', 'avatars', $user['id'])) { $fields[] = "avatar = ?"; $params[] = $avatar; }
    if ($cover = handleUpload('cover', 'covers', $user['id'])) { $fields[] = "cover = ?"; $params[] = $cover; }
}

if (empty($fields)) jsonError('Aucune modification fournie.');

$params[] = $user['id'];
$pdo->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user['id']]);

jsonResponse(['success' => true, 'message' => 'Profil mis à jour.', 'user' => publicUser($stmt->fetch())]);
