<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/backup_helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

requireSuperAdmin();

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// --- Rate limiting: 3 attempts per hour ---
if (!isset($_SESSION['dz_failed_attempts'])) {
    $_SESSION['dz_failed_attempts'] = 0;
}
if (!isset($_SESSION['dz_lockout_until'])) {
    $_SESSION['dz_lockout_until'] = 0;
}

// Check if currently locked out
if ($_SESSION['dz_failed_attempts'] >= 3 && time() < $_SESSION['dz_lockout_until']) {
    $remaining = ceil(($_SESSION['dz_lockout_until'] - time()) / 60);
    echo json_encode([
        'success' => false,
        'locked' => true,
        'remaining_minutes' => $remaining,
        'message' => "Too many failed attempts. Locked for {$remaining} more minute(s)."
    ]);
    exit;
}

// If lockout period has passed, reset
if ($_SESSION['dz_lockout_until'] > 0 && time() >= $_SESSION['dz_lockout_until']) {
    $_SESSION['dz_failed_attempts'] = 0;
    $_SESSION['dz_lockout_until'] = 0;
}

function verifyPassword(PDO $pdo, string $password, string $confirmPassword): array {
    if (empty($password) || empty($confirmPassword)) {
        return ['valid' => false, 'message' => 'Both password fields are required.', 'skip_attempt' => true];
    }
    if ($password !== $confirmPassword) {
        return ['valid' => false, 'message' => 'Passwords do not match.', 'skip_attempt' => true];
    }

    $userId = getCurrentUserId();
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :uid AND role = 'superadmin'");
    $stmt->execute([':uid' => $userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['valid' => false, 'message' => 'Incorrect password.', 'skip_attempt' => false];
    }

    return ['valid' => true, 'message' => '', 'skip_attempt' => false];
}

function handlePasswordCheck(PDO $pdo, string $password, string $confirmPassword): bool {
    $result = verifyPassword($pdo, $password, $confirmPassword);

    if (!$result['valid']) {
        if (!$result['skip_attempt']) {
            $_SESSION['dz_failed_attempts']++;
            if ($_SESSION['dz_failed_attempts'] >= 3) {
                $_SESSION['dz_lockout_until'] = time() + 3600;
                echo json_encode([
                    'success' => false,
                    'locked' => true,
                    'remaining_minutes' => 60,
                    'message' => 'Too many failed attempts. You are locked out for 1 hour.'
                ]);
                return false;
            }
        }
        $attemptsLeft = 3 - $_SESSION['dz_failed_attempts'];
        echo json_encode([
            'success' => false,
            'message' => $result['message'],
            'attempts_left' => $attemptsLeft
        ]);
        return false;
    }

    $_SESSION['dz_failed_attempts'] = 0;
    $_SESSION['dz_lockout_until'] = 0;
    return true;
}

$password = $input['password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';

$validActions = ['verify_password', 'clear_attendance', 'clear_accomplishments', 'clear_idlar', 'reset_passwords'];

if (!in_array($action, $validActions, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

// All actions require password verification
if (!handlePasswordCheck($pdo, $password, $confirmPassword)) {
    exit;
}

// --- Action: verify_password (gate entry) ---
if ($action === 'verify_password') {
    echo json_encode(['success' => true, 'message' => 'Identity verified.']);
    exit;
}

// --- Action: clear_attendance ---
if ($action === 'clear_attendance') {
    try {
        createTableBackup($pdo, ['attendance_logs', 'accomplishments', 'idlar_attachments'], 'pre_deletion', 'Pre-deletion: Clear Attendance Logs', getCurrentUserId());

        $pdo->beginTransaction();
        $pdo->exec("DELETE FROM accomplishments");
        $pdo->exec("DELETE FROM idlar_attachments");

        $attachDir = __DIR__ . '/../uploads/idlar_attachments/';
        if (is_dir($attachDir)) {
            $files = glob($attachDir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        $pdo->exec("DELETE FROM attendance_logs");
        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => 'All attendance logs, accomplishments, and related IDLAR data have been cleared.'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to clear attendance logs. No changes were made.']);
    }
    exit;
}

// --- Action: clear_accomplishments ---
if ($action === 'clear_accomplishments') {
    try {
        createTableBackup($pdo, ['accomplishments'], 'pre_deletion', 'Pre-deletion: Clear Accomplishments', getCurrentUserId());

        $pdo->exec("DELETE FROM accomplishments");
        echo json_encode([
            'success' => true,
            'message' => 'All accomplishment records have been cleared.'
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to clear accomplishments.']);
    }
    exit;
}

// --- Action: clear_idlar ---
if ($action === 'clear_idlar') {
    try {
        createTableBackup($pdo, ['idlar_attachments'], 'pre_deletion', 'Pre-deletion: Clear IDLAR Attachments', getCurrentUserId());

        $pdo->beginTransaction();
        $pdo->exec("DELETE FROM idlar_attachments");

        $attachDir = __DIR__ . '/../uploads/idlar_attachments/';
        if (is_dir($attachDir)) {
            $files = glob($attachDir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => 'All IDLAR attachments and uploaded files have been cleared.'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to clear IDLAR data.']);
    }
    exit;
}

// --- Action: reset_passwords ---
if ($action === 'reset_passwords') {
    try {
        createTableBackup($pdo, ['users'], 'pre_deletion', 'Pre-deletion: Reset All Passwords', getCurrentUserId());

        $pdo->beginTransaction();
        $stmt = $pdo->query("SELECT id, employee_id FROM users WHERE role != 'superadmin'");
        $users = $stmt->fetchAll();
        $updateStmt = $pdo->prepare(
            "UPDATE users SET password = :pw, must_change_password = 1 WHERE id = :uid"
        );
        foreach ($users as $u) {
            $hash = password_hash($u['employee_id'], PASSWORD_DEFAULT);
            $updateStmt->execute([':pw' => $hash, ':uid' => $u['id']]);
        }
        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => count($users) . ' user password(s) have been reset to their Employee IDs.',
            'users_reset' => count($users)
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to reset passwords.']);
    }
    exit;
}
