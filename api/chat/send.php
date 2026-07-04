<?php
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Méthode non autorisée.', 405);
$user = requireAuth();
$pdo = getDB();

$isMultipart = str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data');
$data = $isMultipart ? $_POST : getJsonBody();
$otherId = (int)($data['user_id'] ?? 0);
$content = trim($data['content'] ?? '');

if (!$otherId) jsonError('Destinataire invalide.');

$imagePath = null;
if ($isMultipart && !empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    $mime = mime_content_type($_FILES['image']['tmp_name']);
    if (!isset($allowed[$mime])) jsonError("Format d'image non pris en charge.");
    $filename = 'chat_' . $user['id'] . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $dest = __DIR__ . '/../../assets/uploads/chat/' . $filename;
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) jsonError("Échec de l'envoi de l'image.", 500);
    $imagePath = 'assets/uploads/chat/' . $filename;
}

if ($content === '' && !$imagePath) jsonError('Le message ne peut pas être vide.');

[$u1, $u2] = $user['id'] < $otherId ? [$user['id'], $otherId] : [$otherId, $user['id']];
$stmt = $pdo->prepare("SELECT id FROM conversations WHERE user_one_id = ? AND user_two_id = ?");
$stmt->execute([$u1, $u2]);
$conv = $stmt->fetch();
if ($conv) {
    $convId = (int)$conv['id'];
} else {
    $pdo->prepare("INSERT INTO conversations (user_one_id, user_two_id) VALUES (?, ?)")->execute([$u1, $u2]);
    $convId = (int)$pdo->lastInsertId();
}

$pdo->prepare("INSERT INTO messages (conversation_id, sender_id, content, image) VALUES (?, ?, ?, ?)")
    ->execute([$convId, $user['id'], $content ?: null, $imagePath]);

jsonResponse([
    'success' => true,
    'message' => [
        'id' => (int)$pdo->lastInsertId(),
        'content' => $content,
        'image' => $imagePath,
        'created_at' => date('Y-m-d H:i:s'),
        'from_me' => true,
    ],
]);
