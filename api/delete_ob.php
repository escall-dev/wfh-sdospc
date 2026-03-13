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

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$obId = (int)($input['ob_id'] ?? 0);

if ($obId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid OB entry.']);
    exit;
}

$userId = getCurrentUserId();
$isSuperAdmin = isSuperAdmin();

// Fetch the OB entry
$stmt = $pdo->prepare("SELECT * FROM official_business WHERE id = :id");
$stmt->execute([':id' => $obId]);
$ob = $stmt->fetch();

if (!$ob) {
    echo json_encode(['success' => false, 'message' => 'OB entry not found.']);
    exit;
}

// Employees can only delete their own entries
if (!$isSuperAdmin && (int)$ob['user_id'] !== $userId) {
    echo json_encode(['success' => false, 'message' => 'You can only delete your own OB entries.']);
    exit;
}

// Employees must be within the IDLAR edit window; superadmin can delete anytime
if (!$isSuperAdmin) {
    $window = checkAccomplishmentWindow($ob['ob_date']);
    if (!$window['allowed']) {
        echo json_encode(['success' => false, 'message' => 'OB entries can only be deleted during the edit window (Friday 5:00 PM – Monday 11:59 PM).']);
        exit;
    }
}

try {
    $delStmt = $pdo->prepare("DELETE FROM official_business WHERE id = :id");
    $delStmt->execute([':id' => $obId]);

    echo json_encode(['success' => true, 'message' => 'Official Business entry deleted.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
