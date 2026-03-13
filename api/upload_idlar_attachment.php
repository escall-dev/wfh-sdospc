<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

requireLogin();

$userId = getCurrentUserId();
$logId = (int)($_POST['log_id'] ?? 0);

if (!$logId) {
    echo json_encode(['success' => false, 'message' => 'Missing attendance log ID.']);
    exit;
}

$logCheck = $pdo->prepare("SELECT id, date FROM attendance_logs WHERE id = :lid AND user_id = :uid");
$logCheck->execute([':lid' => $logId, ':uid' => $userId]);
$logRow = $logCheck->fetch();
if (!$logRow) {
    echo json_encode(['success' => false, 'message' => 'Invalid attendance log.']);
    exit;
}

// Enforce IDLAR upload window (Friday 5 PM – Monday 11:59 PM, active week only)
$windowCheck = checkAccomplishmentWindow($logRow['date']);
if (!$windowCheck['allowed']) {
    echo json_encode(['success' => false, 'message' => $windowCheck['message']]);
    exit;
}

if (empty($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error.']);
    exit;
}

$file = $_FILES['attachment'];
$maxSize = 5 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum is 5MB.']);
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
if (!in_array($ext, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: PDF, DOC, DOCX, JPG, PNG.']);
    exit;
}

$uploadDir = __DIR__ . '/../uploads/idlar_attachments/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$uniqueName = $userId . '_' . $logId . '_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
$destPath = $uploadDir . $uniqueName;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file.']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO idlar_attachments (user_id, log_id, file_name, original_name) VALUES (:uid, :lid, :fn, :on)");
$stmt->execute([
    ':uid' => $userId,
    ':lid' => $logId,
    ':fn' => $uniqueName,
    ':on' => $file['name']
]);

echo json_encode([
    'success' => true,
    'message' => 'File uploaded successfully.',
    'attachment' => [
        'id' => $pdo->lastInsertId(),
        'original_name' => $file['name'],
        'file_name' => $uniqueName
    ]
]);
