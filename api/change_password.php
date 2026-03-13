<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

requireLogin();

$input = json_decode(file_get_contents('php://input'), true);
$currentPass = $input['current_password'] ?? '';
$newPass = $input['new_password'] ?? '';
$confirmPass = $input['confirm_password'] ?? '';

if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

if ($newPass !== $confirmPass) {
    echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
    exit;
}

if (strlen($newPass) < 6) {
    echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters.']);
    exit;
}

$userId = getCurrentUserId();
$stmt = $pdo->prepare("SELECT password FROM users WHERE id = :uid");
$stmt->execute([':uid' => $userId]);
$user = $stmt->fetch();

if (!$user || !password_verify($currentPass, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
    exit;
}

$hash = password_hash($newPass, PASSWORD_DEFAULT);
$pdo->prepare("UPDATE users SET password = :pw, must_change_password = 0 WHERE id = :uid")
    ->execute([':pw' => $hash, ':uid' => $userId]);

$_SESSION['must_change_password'] = 0;

echo json_encode([
    'success' => true,
    'message' => 'Password changed successfully.',
    'redirect' => '/employee/dashboard.php'
]);
