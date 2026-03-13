<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/backup_helpers.php';

requireSuperAdmin();

$action = $_GET['action'] ?? '';

// Download is GET-based (streams a file)
if ($action === 'download') {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo 'Missing backup ID.';
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM db_backups WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $backup = $stmt->fetch();

    if (!$backup) {
        http_response_code(404);
        echo 'Backup not found.';
        exit;
    }

    $filePath = BACKUP_DIR . $backup['file_name'];
    if (!file_exists($filePath)) {
        http_response_code(404);
        echo 'Backup file missing from disk.';
        exit;
    }

    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $backup['file_name'] . '"');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}

// All other actions are POST + JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$userId = getCurrentUserId();

$validActions = ['create_manual', 'restore', 'delete', 'check_scheduled'];

if (!in_array($action, $validActions, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
    exit;
}

// --- create_manual ---
if ($action === 'create_manual') {
    try {
        $result = createFullDatabaseBackup($pdo, 'manual', 'Manual backup by Super Admin', $userId);
        echo json_encode([
            'success' => true,
            'message' => 'Manual backup created successfully.',
            'backup' => $result,
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to create backup: ' . $e->getMessage()]);
    }
    exit;
}

// --- restore (requires password) ---
if ($action === 'restore') {
    $backupId = (int) ($input['backup_id'] ?? 0);
    $password = $input['password'] ?? '';

    if (!$backupId) {
        echo json_encode(['success' => false, 'message' => 'Missing backup ID.']);
        exit;
    }
    if (empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Password is required to restore.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :uid AND role = 'superadmin'");
    $stmt->execute([':uid' => $userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
        exit;
    }

    $result = restoreFromBackup($pdo, $backupId);
    echo json_encode($result);
    exit;
}

// --- delete ---
if ($action === 'delete') {
    $backupId = (int) ($input['backup_id'] ?? 0);
    if (!$backupId) {
        echo json_encode(['success' => false, 'message' => 'Missing backup ID.']);
        exit;
    }

    $result = deleteBackupEntry($pdo, $backupId);
    echo json_encode($result);
    exit;
}

// --- check_scheduled ---
if ($action === 'check_scheduled') {
    try {
        checkAndRunScheduledBackup($pdo, $userId);
        echo json_encode(['success' => true, 'message' => 'Scheduled backup check complete.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Scheduled check failed: ' . $e->getMessage()]);
    }
    exit;
}
