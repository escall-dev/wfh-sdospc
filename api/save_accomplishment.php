<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireEmployee();

header('Content-Type: application/json');

$userId = getCurrentUserId();
$input = json_decode(file_get_contents('php://input'), true);

$logId = isset($input['log_id']) ? (int)$input['log_id'] : 0;
$itemText = isset($input['item_text']) ? trim($input['item_text']) : '';

if ($logId <= 0 || $itemText === '') {
    echo json_encode(['success' => false, 'message' => 'Log ID and accomplishment text are required.']);
    exit;
}

if (mb_strlen($itemText) > 300) {
    echo json_encode(['success' => false, 'message' => 'Accomplishment text must be 300 characters or less.']);
    exit;
}

// Verify the log belongs to this user
$stmt = $pdo->prepare("SELECT id, date FROM attendance_logs WHERE id = :lid AND user_id = :uid");
$stmt->execute([':lid' => $logId, ':uid' => $userId]);
$logRow = $stmt->fetch();
if (!$logRow) {
    echo json_encode(['success' => false, 'message' => 'Invalid log entry.']);
    exit;
}

// Enforce weekly submission window (Friday 5 PM – Sunday midnight, current week only)
$windowCheck = checkAccomplishmentWindow($logRow['date']);
if (!$windowCheck['allowed']) {
    echo json_encode(['success' => false, 'message' => $windowCheck['message']]);
    exit;
}

// Insert accomplishment
$ins = $pdo->prepare("INSERT INTO accomplishments (log_id, item_text) VALUES (:lid, :txt)");
$ins->execute([':lid' => $logId, ':txt' => $itemText]);

echo json_encode([
    'success' => true,
    'message' => 'Accomplishment saved.',
    'id' => (int)$pdo->lastInsertId(),
    'item_text' => $itemText
]);
