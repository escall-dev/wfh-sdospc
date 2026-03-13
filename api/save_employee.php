<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

requireAdmin();

$uid      = (int)($_POST['user_id'] ?? 0);
$fullName = trim($_POST['full_name'] ?? '');
$funcDiv  = $_POST['functional_division'] ?? null;
$position = trim($_POST['position'] ?? '') ?: null;
$recommending = trim($_POST['recommending'] ?? '') ?: null;
$newPass  = $_POST['new_password'] ?? '';

if (!$uid) {
    echo json_encode(['success' => false, 'message' => 'Invalid employee.']);
    exit;
}

// Verify the user exists (superadmin can edit any user, regular admin can edit employees only)
if (isSuperAdmin()) {
    $check = $pdo->prepare("SELECT id FROM users WHERE id = :uid AND id != :myid");
    $check->execute([':uid' => $uid, ':myid' => getCurrentUserId()]);
} else {
    $check = $pdo->prepare("SELECT id FROM users WHERE id = :uid AND role = 'employee'");
    $check->execute([':uid' => $uid]);
}
if (!$check->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Employee not found.']);
    exit;
}

// Update basic info
if (!empty($fullName)) {
    $pdo->prepare("UPDATE users SET full_name = :fn, functional_division = :fd, position = :pos, recommending = :rec WHERE id = :uid")
        ->execute([':fn' => $fullName, ':fd' => $funcDiv ?: null, ':pos' => $position, ':rec' => $recommending, ':uid' => $uid]);
}

// Handle ID picture upload
if (!empty($_FILES['edit_id_picture']['name'])) {
    $uploadDir = __DIR__ . '/../assets/id_pictures/';
    $ext = strtolower(pathinfo($_FILES['edit_id_picture']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (in_array($ext, $allowed) && $_FILES['edit_id_picture']['size'] <= 5 * 1024 * 1024) {
        $empRow = $pdo->prepare("SELECT employee_id, full_name FROM users WHERE id = :uid");
        $empRow->execute([':uid' => $uid]);
        $empData = $empRow->fetch();
        if ($empData) {
            $safeName  = preg_replace('/[^A-Za-z0-9_\-]/', '_', $empData['full_name']);
            $idPicFile = $empData['employee_id'] . '_' . $safeName . '.' . $ext;
            move_uploaded_file($_FILES['edit_id_picture']['tmp_name'], $uploadDir . $idPicFile);
            $pdo->prepare("UPDATE users SET id_picture = :ip WHERE id = :uid")
                ->execute([':ip' => $idPicFile, ':uid' => $uid]);
        }
    }
}

// Update password — also clears the forced-change flag so the employee
// can log in directly with the new password the admin set.
if (!empty($newPass)) {
    if (strlen($newPass) < 6) {
        echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters.']);
        exit;
    }
    $hash = password_hash($newPass, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password = :pw, must_change_password = 0 WHERE id = :uid")
        ->execute([':pw' => $hash, ':uid' => $uid]);
}

echo json_encode(['success' => true, 'message' => 'Employee updated successfully.']);
