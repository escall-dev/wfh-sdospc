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

$input = json_decode(file_get_contents('php://input'), true);
$attachId = (int)($input['attachment_id'] ?? 0);
$userId = getCurrentUserId();

if (!$attachId) {
    echo json_encode(['success' => false, 'message' => 'Missing attachment ID.']);
    exit;
}

$stmt = $pdo->prepare("SELECT ia.id, ia.file_name, al.date FROM idlar_attachments ia JOIN attendance_logs al ON ia.log_id = al.id WHERE ia.id = :aid AND ia.user_id = :uid");
$stmt->execute([':aid' => $attachId, ':uid' => $userId]);
$attachment = $stmt->fetch();

if (!$attachment) {
    echo json_encode(['success' => false, 'message' => 'Attachment not found.']);
    exit;
}

// Enforce IDLAR upload window (Friday 5 PM – Monday 11:59 PM, active week only)
$windowCheck = checkAccomplishmentWindow($attachment['date']);
if (!$windowCheck['allowed']) {
    echo json_encode(['success' => false, 'message' => $windowCheck['message']]);
    exit;
}

$filePath = __DIR__ . '/../uploads/idlar_attachments/' . $attachment['file_name'];
if (file_exists($filePath)) {
    unlink($filePath);
}

$pdo->prepare("DELETE FROM idlar_attachments WHERE id = :aid")->execute([':aid' => $attachId]);

echo json_encode(['success' => true, 'message' => 'Attachment deleted.']);
