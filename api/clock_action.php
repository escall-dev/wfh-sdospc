<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$accomplishments = $input['accomplishments'] ?? [];
$deviceFp = preg_replace('/[^a-f0-9]/i', '', $input['device_fp'] ?? '');
$deviceFp = substr($deviceFp, 0, 128);
$userId = getCurrentUserId();
$now = date('H:i:s');
$today = date('Y-m-d');

$validActions = ['time_in', 'lunch_out', 'lunch_in', 'time_out', 'emergency_time_out'];
if (!in_array($action, $validActions)) {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

$todayLog = getTodayLog($pdo, $userId);
$currentStep = getCurrentStep($todayLog);

// Whole Day Leave: block all actions
if (isLeaveLog($todayLog)) {
    echo json_encode(['success' => false, 'message' => 'You are already marked as On Leave for today.']);
    exit;
}

// AM Leave: block AM actions (time_in, lunch_out), allow PM actions
if ($todayLog && ($todayLog['am_status'] ?? '') === 'am_leave') {
    if (in_array($action, ['time_in', 'lunch_out'])) {
        echo json_encode(['success' => false, 'message' => 'AM session is locked — you are on AM Leave.']);
        exit;
    }
}

// PM Leave: block PM actions (lunch_in, time_out), allow AM actions
if ($todayLog && ($todayLog['am_status'] ?? '') === 'pm_leave') {
    if (in_array($action, ['lunch_in', 'time_out'])) {
        echo json_encode(['success' => false, 'message' => 'PM session is locked — you are on PM Leave.']);
        exit;
    }
}

// Official Business: block clock actions during active OB periods
$obEntries = getOBForDate($pdo, $userId, $today);
foreach ($obEntries as $ob) {
    if ($now >= $ob['time_from'] && $now <= $ob['time_to']) {
        $obFrom = date('h:i A', strtotime($ob['time_from']));
        $obTo   = date('h:i A', strtotime($ob['time_to']));
        echo json_encode(['success' => false, 'message' => "Clock action blocked — you are currently on Official Business ({$obFrom} – {$obTo})."]);
        exit;
    }
}

// Absent AM or AM Leave: allow Lunch In as first PM action
$isAbsentAMLunchIn = ($action === 'lunch_in') && !$todayLog && isTimeInWindowPassed();
$isAmLeaveLunchIn = ($action === 'lunch_in') && $todayLog && ($todayLog['am_status'] ?? '') === 'am_leave' && !$todayLog['lunch_in'];

// PM skip: allow PM Out when PM In window has passed and PM In was never logged
// Case A – employee completed AM session but missed PM In window
$isPmSkippedTimeOutWithAm = ($action === 'time_out')
    && $todayLog
    && !empty($todayLog['time_in'])
    && !empty($todayLog['lunch_out'])
    && !$todayLog['lunch_in']
    && isPmInWindowPassed();
// Case B – absent-AM employee who also missed PM In (no log exists at all)
$isPmSkippedTimeOutAbsent = ($action === 'time_out')
    && !$todayLog
    && isPmInWindowPassed();
$isPmSkippedTimeOut = $isPmSkippedTimeOutWithAm || $isPmSkippedTimeOutAbsent;

// Emergency time out handling
if ($action === 'emergency_time_out') {
    if (!$todayLog || !$todayLog['time_in']) {
        echo json_encode(['success' => false, 'message' => 'You must clock in first before using Emergency Time Out.']);
        exit;
    }
    if ($todayLog['time_out']) {
        echo json_encode(['success' => false, 'message' => 'You have already clocked out.']);
        exit;
    }

    $totalHours = calculateTotalHours($todayLog['time_in'], $todayLog['lunch_out'], $todayLog['lunch_in'], $now);

    $stmt = $pdo->prepare("UPDATE attendance_logs SET time_out = :tout, total_hours = :th, is_emergency = 1 WHERE id = :id");
    $stmt->execute([':tout' => $now, ':th' => $totalHours, ':id' => $todayLog['id']]);

    saveAccomplishments($pdo, $todayLog['id'], $accomplishments);
    recordLoginLog($pdo, $userId, 'emergency_time_out', $deviceFp);

    echo json_encode([
        'success' => true,
        'message' => 'Emergency Time Out recorded at ' . formatTimeDisplay($now),
        'data' => ['time_out' => $now, 'total_hours' => $totalHours]
    ]);
    exit;
}

// Validate step order
$expectedStep = [
    'time_in' => 1,
    'lunch_out' => 2,
    'lunch_in' => 3,
    'time_out' => 4
];

if (!$isAbsentAMLunchIn && !$isAmLeaveLunchIn && !$isPmSkippedTimeOut && $currentStep !== $expectedStep[$action]) {
    $expected = getNextActionLabel($currentStep);
    echo json_encode(['success' => false, 'message' => "Steps must be completed in order. Next expected: $expected"]);
    exit;
}

// Validate time window (server-side, context-aware)
$window = isWithinWindow($action, $todayLog);
if (!$window['allowed']) {
    echo json_encode(['success' => false, 'message' => $window['message']]);
    exit;
}

try {
    $pdo->beginTransaction();

    switch ($action) {
        case 'time_in':
            $amStatus = getAmStatus($now);
            $stmt = $pdo->prepare("
                INSERT INTO attendance_logs (user_id, date, time_in, am_status)
                VALUES (:uid, :dt, :tin, :status)
            ");
            $stmt->execute([
                ':uid' => $userId,
                ':dt' => $today,
                ':tin' => $now,
                ':status' => $amStatus
            ]);
            $logId = $pdo->lastInsertId();

            saveAccomplishments($pdo, $logId, $accomplishments);

            $pdo->commit();
            recordLoginLog($pdo, $userId, 'time_in', $deviceFp);
            echo json_encode([
                'success' => true,
                'message' => 'Time In recorded at ' . formatTimeDisplay($now) . ' (' . getStatusLabel($amStatus) . ')',
                'data' => ['time_in' => $now, 'am_status' => $amStatus, 'log_id' => $logId]
            ]);
            break;

        case 'lunch_out':
            $stmt = $pdo->prepare("UPDATE attendance_logs SET lunch_out = :lo WHERE id = :id");
            $stmt->execute([':lo' => $now, ':id' => $todayLog['id']]);

            saveAccomplishments($pdo, $todayLog['id'], $accomplishments);

            $pdo->commit();
            recordLoginLog($pdo, $userId, 'lunch_out', $deviceFp);
            echo json_encode([
                'success' => true,
                'message' => 'Lunch Out recorded at ' . formatTimeDisplay($now),
                'data' => ['lunch_out' => $now]
            ]);
            break;

        case 'lunch_in':
            if (!$todayLog) {
                // Absent AM: create the log with absent status and set lunch_in
                $stmt = $pdo->prepare("INSERT INTO attendance_logs (user_id, date, am_status, lunch_in) VALUES (:uid, :dt, 'absent', :li)");
                $stmt->execute([':uid' => $userId, ':dt' => $today, ':li' => $now]);
                $newLogId = (int)$pdo->lastInsertId();
                saveAccomplishments($pdo, $newLogId, $accomplishments);
            } else {
                $stmt = $pdo->prepare("UPDATE attendance_logs SET lunch_in = :li WHERE id = :id");
                $stmt->execute([':li' => $now, ':id' => $todayLog['id']]);
                saveAccomplishments($pdo, $todayLog['id'], $accomplishments);
            }

            $pdo->commit();
            recordLoginLog($pdo, $userId, 'lunch_in', $deviceFp);
            echo json_encode([
                'success' => true,
                'message' => 'Lunch In recorded at ' . formatTimeDisplay($now),
                'data' => ['lunch_in' => $now]
            ]);
            break;

        case 'time_out':
            if (!$todayLog) {
                // Absent AM + PM In both skipped: create a minimal absent log with PM Out
                $stmt = $pdo->prepare("INSERT INTO attendance_logs (user_id, date, am_status, time_out) VALUES (:uid, :dt, 'absent', :tout)");
                $stmt->execute([':uid' => $userId, ':dt' => $today, ':tout' => $now]);
                $newLogId = (int)$pdo->lastInsertId();
                saveAccomplishments($pdo, $newLogId, $accomplishments);
                $pdo->commit();
                recordLoginLog($pdo, $userId, 'time_out', $deviceFp);
                echo json_encode([
                    'success' => true,
                    'message' => 'PM Out recorded at ' . formatTimeDisplay($now),
                    'data' => ['time_out' => $now, 'total_hours' => null]
                ]);
                break;
            }

            $totalHours = calculateTotalHours(
                $todayLog['time_in'],
                $todayLog['lunch_out'],
                $todayLog['lunch_in'],
                $now
            );

            $stmt = $pdo->prepare("UPDATE attendance_logs SET time_out = :tout, total_hours = :th WHERE id = :id");
            $stmt->execute([':tout' => $now, ':th' => $totalHours, ':id' => $todayLog['id']]);

            saveAccomplishments($pdo, $todayLog['id'], $accomplishments);

            $pdo->commit();
            recordLoginLog($pdo, $userId, 'time_out', $deviceFp);
            echo json_encode([
                'success' => true,
                'message' => 'PM Out recorded at ' . formatTimeDisplay($now) . '. Total hours: ' . $totalHours,
                'data' => ['time_out' => $now, 'total_hours' => $totalHours]
            ]);
            break;
    }

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}

function recordLoginLog(PDO $pdo, int $userId, string $action, string $deviceFp): void {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    $ip = trim(explode(',', $ip)[0]);
    $ip = filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 512);
    try {
        $stmt = $pdo->prepare("
            INSERT INTO login_logs (user_id, action, ip_address, device_fingerprint, user_agent)
            VALUES (:uid, :act, :ip, :fp, :ua)
        ");
        $stmt->execute([':uid' => $userId, ':act' => $action, ':ip' => $ip, ':fp' => $deviceFp, ':ua' => $ua]);
    } catch (Exception $e) { /* non-fatal */ }
}

function saveAccomplishments(PDO $pdo, int $logId, array $items): void {
    // Delete existing accomplishments for this log (update scenario)
    $pdo->prepare("DELETE FROM accomplishments WHERE log_id = :lid")->execute([':lid' => $logId]);

    $items = array_slice(array_filter(array_map('trim', $items)), 0, 4);

    $stmt = $pdo->prepare("INSERT INTO accomplishments (log_id, item_text) VALUES (:lid, :txt)");
    foreach ($items as $item) {
        if (strlen($item) > 0 && strlen($item) <= 300) {
            $stmt->execute([':lid' => $logId, ':txt' => $item]);
        }
    }
}
