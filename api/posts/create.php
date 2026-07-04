<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Méthode non autorisée.', 405);
$user = requireAuth();

$isMultipart = str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data');
$content = trim($isMultipart ? ($_POST['content'] ?? '') : (getJsonBody()['content'] ?? ''));
$visibility = ($isMultipart ? ($_POST['visibility'] ?? 'public') : (getJsonBody()['visibility'] ?? 'public'));
$visibility = in_array($visibility, ['public','friends'], true) ? $visibility : 'public';

if ($content === '' && empty($_FILES['image']['name'])) {
    jsonError('La publication ne peut pas être vide.');
}

$imagePath = null;
if ($isMultipart && !empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $mime = mime_content_type($_FILES['image']['tmp_name']);
    if (!isset($allowed[$mime])) jsonError("Format d'image non pris en charge.");
    if ($_FILES['image']['size'] > 8 * 1024 * 1024) jsonError("L'image dépasse la taille maximale de 8 Mo.");

    $filename = 'post_' . $user['id'] . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $dest = __DIR__ . '/../../assets/uploads/posts/' . $filename;
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) jsonError("Échec de l'envoi de l'image.", 500);
    $imagePath = 'assets/uploads/posts/' . $filename;
}

$pdo = getDB();
$stmt = $pdo->prepare("INSERT INTO posts (user_id, content, image, visibility) VALUES (?, ?, ?, ?)");
$stmt->execute([$user['id'], $content, $imagePath, $visibility]);

jsonResponse(['success' => true, 'post_id' => (int)$pdo->lastInsertId(), 'image' => $imagePath]);
