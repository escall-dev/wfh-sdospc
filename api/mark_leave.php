<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireEmployee();

header('Content-Type: application/json');

$userId = getCurrentUserId();
$input = json_decode(file_get_contents('php://input'), true);
$logId = isset($input['log_id']) ? (int)$input['log_id'] : 0;

if ($logId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid log ID.']);
    exit;
}

// Verify ownership and get current status + date
$stmt = $pdo->prepare("SELECT id, date, time_in, am_status FROM attendance_logs WHERE id = :lid AND user_id = :uid");
$stmt->execute([':lid' => $logId, ':uid' => $userId]);
$log = $stmt->fetch();
if (!$log) {
    echo json_encode(['success' => false, 'message' => 'Invalid log entry.']);
    exit;
}

// Enforce weekly submission window (Friday 5 PM – Sunday midnight, current week only)
$windowCheck = checkAccomplishmentWindow($log['date']);
if (!$windowCheck['allowed']) {
    echo json_encode(['success' => false, 'message' => $windowCheck['message']]);
    exit;
}

// Toggle: if currently 'leave', unmark; otherwise mark as leave
if ($log['am_status'] === 'leave') {
    // Restore original status from time_in, or null if no time_in
    $restored = $log['time_in'] ? getAmStatus($log['time_in']) : null;
    $upd = $pdo->prepare("UPDATE attendance_logs SET am_status = :s WHERE id = :id");
    $upd->execute([':s' => $restored, ':id' => $logId]);
    echo json_encode(['success' => true, 'is_leave' => false, 'message' => 'Leave declaration removed.']);
} else {
    $upd = $pdo->prepare("UPDATE attendance_logs SET am_status = 'leave' WHERE id = :id");
    $upd->execute([':id' => $logId]);
    echo json_encode(['success' => true, 'is_leave' => true, 'message' => 'Day marked as leave.']);
}
