<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';
$deviceFp = preg_replace('/[^a-f0-9]/i', '', $input['device_fp'] ?? '');
$deviceFp = substr($deviceFp, 0, 128);

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please enter both username and password.']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, employee_id, last_name, full_name, functional_division, position, id_picture, role, password, is_active, must_change_password
    FROM users
    WHERE employee_id = :eid
    LIMIT 1
");
$stmt->execute([':eid' => $username]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Invalid Employee ID. Please try again.']);
    exit;
}

if (!$user['is_active']) {
    echo json_encode(['success' => false, 'message' => 'Your account has been deactivated. Contact your administrator.']);
    exit;
}

if (!password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid password. Please try again.']);
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['employee_id'] = $user['employee_id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['last_name'] = $user['last_name'];
$_SESSION['role'] = $user['role'];
$_SESSION['functional_division'] = $user['functional_division'];
$_SESSION['position'] = $user['position'];
$_SESSION['id_picture'] = $user['id_picture'];
$_SESSION['must_change_password'] = (int)$user['must_change_password'];

if (!in_array($user['role'], ['admin', 'superadmin', 'hr_timekeeping']) && $user['must_change_password']) {
    $redirect = '/employee/change_password.php';
} else {
    $redirect = in_array($user['role'], ['admin', 'superadmin', 'hr_timekeeping'])
        ? '/admin/dashboard.php'
        : '/employee/dashboard.php';
}

// Record login audit log
$ipAddr = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
$ipAddr = trim(explode(',', $ipAddr)[0]);
$ipAddr = filter_var($ipAddr, FILTER_VALIDATE_IP) ? $ipAddr : '';
$ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 512);
try {
    $logStmt = $pdo->prepare("
        INSERT INTO login_logs (user_id, action, ip_address, device_fingerprint, user_agent)
        VALUES (:uid, 'login', :ip, :fp, :ua)
    ");
    $logStmt->execute([
        ':uid' => $user['id'],
        ':ip'  => $ipAddr,
        ':fp'  => $deviceFp,
        ':ua'  => $ua,
    ]);
} catch (Exception $e) { /* non-fatal */ }

echo json_encode([
    'success' => true,
    'message' => 'Login successful.',
    'redirect' => $redirect,
    'user' => [
        'full_name' => $user['full_name'],
        'role' => $user['role']
    ]
]);
