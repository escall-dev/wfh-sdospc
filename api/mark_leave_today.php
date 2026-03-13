<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
requireEmployee();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Friday-only restriction removed — leave marking is no longer tied to a specific day

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$leaveType = $input['leave_type'] ?? 'whole_day';

$leaveTypeMap = [
    'am'        => 'am_leave',
    'pm'        => 'pm_leave',
    'whole_day' => 'leave',
];

if (!array_key_exists($leaveType, $leaveTypeMap)) {
    echo json_encode(['success' => false, 'message' => 'Invalid leave type specified.']);
    exit;
}

$amStatus = $leaveTypeMap[$leaveType];

$leaveLabels = [
    'am'        => 'AM Leave',
    'pm'        => 'PM Leave',
    'whole_day' => 'Whole Day Leave',
];
$leaveLabel = $leaveLabels[$leaveType];

$userId = getCurrentUserId();
$today = date('Y-m-d');
$todayLog = getTodayLog($pdo, $userId);

if (isAnyLeaveLog($todayLog)) {
    echo json_encode(['success' => false, 'message' => 'You are already marked as On Leave for today.']);
    exit;
}

if (!canMarkTodayLeave($todayLog, $leaveType)) {
    if ($leaveType === 'pm') {
        echo json_encode(['success' => false, 'message' => 'PM Leave can only be marked before the PM session begins.']);
    } elseif ($leaveType === 'am') {
        echo json_encode(['success' => false, 'message' => 'AM Leave can only be marked before any AM attendance action is recorded.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Leave can only be marked before any attendance action is recorded for today.']);
    }
    exit;
}

try {
    $pdo->beginTransaction();

    if ($leaveType === 'pm') {
        // PM leave: keep AM session data, only clear PM session fields
        if ($todayLog) {
            $stmt = $pdo->prepare(
                "UPDATE attendance_logs
                 SET lunch_in = NULL,
                     time_out = NULL,
                     total_hours = NULL,
                     am_status = 'pm_leave',
                     is_emergency = 0
                 WHERE id = :id"
            );
            $stmt->execute([':id' => $todayLog['id']]);
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO attendance_logs (user_id, date, am_status)
                 VALUES (:uid, :dt, 'pm_leave')"
            );
            $stmt->execute([':uid' => $userId, ':dt' => $today]);
        }
    } elseif ($leaveType === 'am') {
        // AM leave: clear AM session fields, keep PM open
        if ($todayLog) {
            $stmt = $pdo->prepare(
                "UPDATE attendance_logs
                 SET time_in = NULL,
                     lunch_out = NULL,
                     am_status = 'am_leave',
                     is_emergency = 0
                 WHERE id = :id"
            );
            $stmt->execute([':id' => $todayLog['id']]);
            $pdo->prepare("DELETE FROM accomplishments WHERE log_id = :lid")->execute([':lid' => $todayLog['id']]);
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO attendance_logs (user_id, date, am_status)
                 VALUES (:uid, :dt, 'am_leave')"
            );
            $stmt->execute([':uid' => $userId, ':dt' => $today]);
        }
    } else {
        // Whole Day leave: clear all clock data
        if ($todayLog) {
            $stmt = $pdo->prepare(
                "UPDATE attendance_logs
                 SET time_in = NULL,
                     lunch_out = NULL,
                     lunch_in = NULL,
                     time_out = NULL,
                     total_hours = NULL,
                     am_status = 'leave',
                     is_emergency = 0
                 WHERE id = :id"
            );
            $stmt->execute([':id' => $todayLog['id']]);
            $pdo->prepare("DELETE FROM accomplishments WHERE log_id = :lid")->execute([':lid' => $todayLog['id']]);
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO attendance_logs (user_id, date, am_status)
                 VALUES (:uid, :dt, 'leave')"
            );
            $stmt->execute([':uid' => $userId, ':dt' => $today]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => "You have been marked as {$leaveLabel} for today.",
        'data' => ['date' => $today, 'am_status' => $amStatus]
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}