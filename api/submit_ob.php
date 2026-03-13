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

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$obDate   = trim($input['ob_date'] ?? '');
$timeFrom = trim($input['time_from'] ?? '');
$timeTo   = trim($input['time_to'] ?? '');
$reason   = trim($input['reason'] ?? '');
$location = trim($input['location'] ?? '');

// --- Validation ---

if (!$obDate || !strtotime($obDate)) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid date.']);
    exit;
}

$today = date('Y-m-d');
if ($obDate < $today) {
    echo json_encode(['success' => false, 'message' => 'Official Business cannot be filed for past dates.']);
    exit;
}

if (!$timeFrom || !$timeTo) {
    echo json_encode(['success' => false, 'message' => 'Please provide both Time From and Time To.']);
    exit;
}

$fromTs = strtotime($timeFrom);
$toTs   = strtotime($timeTo);

if ($fromTs === false || $toTs === false) {
    echo json_encode(['success' => false, 'message' => 'Invalid time format.']);
    exit;
}

// Normalize to H:i:s
$timeFromNorm = date('H:i:s', $fromTs);
$timeToNorm   = date('H:i:s', $toTs);

if ($timeFromNorm >= $timeToNorm) {
    echo json_encode(['success' => false, 'message' => 'Time From must be earlier than Time To.']);
    exit;
}

// Work hours: 07:45 - 18:00
if ($timeFromNorm < '07:45:00' || $timeToNorm > '18:00:00') {
    echo json_encode(['success' => false, 'message' => 'OB time must be within work hours (7:45 AM – 6:00 PM).']);
    exit;
}

if ($reason === '') {
    echo json_encode(['success' => false, 'message' => 'Please provide a reason or description.']);
    exit;
}
if (mb_strlen($reason) > 500) {
    echo json_encode(['success' => false, 'message' => 'Reason must not exceed 500 characters.']);
    exit;
}

if ($location === '') {
    echo json_encode(['success' => false, 'message' => 'Please provide a location or office visited.']);
    exit;
}
if (mb_strlen($location) > 300) {
    echo json_encode(['success' => false, 'message' => 'Location must not exceed 300 characters.']);
    exit;
}

// Enforce OB filing window (07:45 - 08:15)
$obWindow = isObFilingWindow();
if (!$obWindow['allowed']) {
    echo json_encode(['success' => false, 'message' => $obWindow['message']]);
    exit;
}

$userId = getCurrentUserId();

// Check for overlapping OB entries on the same date
$stmtOverlap = $pdo->prepare("
    SELECT id, time_from, time_to FROM official_business
    WHERE user_id = :uid AND ob_date = :dt
      AND time_from < :to_time AND time_to > :from_time
");
$stmtOverlap->execute([
    ':uid'       => $userId,
    ':dt'        => $obDate,
    ':to_time'   => $timeToNorm,
    ':from_time' => $timeFromNorm,
]);
$overlap = $stmtOverlap->fetch();

if ($overlap) {
    $overlapFrom = date('h:i A', strtotime($overlap['time_from']));
    $overlapTo   = date('h:i A', strtotime($overlap['time_to']));
    echo json_encode(['success' => false, 'message' => "This overlaps with an existing OB entry ({$overlapFrom} – {$overlapTo})."]);
    exit;
}

// Compute duration
$durationSeconds = $toTs - $fromTs;
$durationHours = round($durationSeconds / 3600, 2);

try {
    $stmt = $pdo->prepare("
        INSERT INTO official_business (user_id, ob_date, time_from, time_to, duration_hours, reason, location)
        VALUES (:uid, :dt, :tf, :tt, :dh, :reason, :loc)
    ");
    $stmt->execute([
        ':uid'    => $userId,
        ':dt'     => $obDate,
        ':tf'     => $timeFromNorm,
        ':tt'     => $timeToNorm,
        ':dh'     => $durationHours,
        ':reason' => $reason,
        ':loc'    => $location,
    ]);

    // If the OB is for today (or the attendance log exists for that date), mark attendance_logs.am_status = 'ob'
    try {
        $stmtLog = $pdo->prepare("SELECT id FROM attendance_logs WHERE user_id = :uid AND date = :dt");
        $stmtLog->execute([':uid' => $userId, ':dt' => $obDate]);
        $existing = $stmtLog->fetch();
        if ($existing) {
            $upd = $pdo->prepare("UPDATE attendance_logs SET am_status = 'ob' WHERE id = :id");
            $upd->execute([':id' => $existing['id']]);
        } else {
            $ins = $pdo->prepare("INSERT INTO attendance_logs (user_id, date, am_status, created_at) VALUES (:uid, :dt, 'ob', NOW())");
            $ins->execute([':uid' => $userId, ':dt' => $obDate]);
        }
    } catch (Exception $e) {
        // Non-fatal: OB was created but attendance log update failed. Continue.
    }

    $dateLabel = date('M j, Y', strtotime($obDate));
    $fromLabel = date('h:i A', strtotime($timeFromNorm));
    $toLabel   = date('h:i A', strtotime($timeToNorm));

    echo json_encode([
        'success' => true,
        'message' => "Official Business filed for {$dateLabel} ({$fromLabel} – {$toLabel}).",
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
