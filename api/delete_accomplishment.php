<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireEmployee();

header('Content-Type: application/json');

$userId = getCurrentUserId();
$input = json_decode(file_get_contents('php://input'), true);

$accId = isset($input['accomplishment_id']) ? (int)$input['accomplishment_id'] : 0;

if ($accId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid accomplishment ID.']);
    exit;
}

// Verify ownership through attendance_logs join and get the log date
$stmt = $pdo->prepare("
    SELECT a.id, al.date FROM accomplishments a
    JOIN attendance_logs al ON a.log_id = al.id
    WHERE a.id = :aid AND al.user_id = :uid
");
$stmt->execute([':aid' => $accId, ':uid' => $userId]);
$row = $stmt->fetch();
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Accomplishment not found.']);
    exit;
}

// Enforce weekly submission window (Friday 5 PM – Sunday midnight, current week only)
$windowCheck = checkAccomplishmentWindow($row['date']);
if (!$windowCheck['allowed']) {
    echo json_encode(['success' => false, 'message' => $windowCheck['message']]);
    exit;
}

$pdo->prepare("DELETE FROM accomplishments WHERE id = :aid")->execute([':aid' => $accId]);

echo json_encode(['success' => true, 'message' => 'Accomplishment removed.']);
