<?php
$pageTitle = 'My Profile';
$currentPage = 'profile';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
requireAdminOrHr();

$userId = getCurrentUserId();
$message = '';
$messageType = '';

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :uid");
$stmt->execute([':uid' => $userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        $funcDiv = $_POST['functional_division'] ?? null;
        $position = trim($_POST['position'] ?? '') ?: null;

        if (!empty($fullName)) {
            $pdo->prepare("UPDATE users SET full_name = :fn, functional_division = :fd, position = :pos WHERE id = :uid")
                ->execute([':fn' => $fullName, ':fd' => $funcDiv ?: null, ':pos' => $position, ':uid' => $userId]);

            // Update session
            $_SESSION['full_name'] = $fullName;
            $_SESSION['functional_division'] = $funcDiv;
        }

        // Handle profile picture upload
        if (!empty($_FILES['id_picture']['name'])) {
            $uploadDir = __DIR__ . '/../assets/id_pictures/';
            $ext = strtolower(pathinfo($_FILES['id_picture']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed) && $_FILES['id_picture']['size'] <= 5 * 1024 * 1024) {
                $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $fullName ?: $user['full_name']);
                $idPicFile = $user['employee_id'] . '_' . $safeName . '.' . $ext;
                move_uploaded_file($_FILES['id_picture']['tmp_name'], $uploadDir . $idPicFile);
                $pdo->prepare("UPDATE users SET id_picture = :ip WHERE id = :uid")
                    ->execute([':ip' => $idPicFile, ':uid' => $userId]);
                $_SESSION['id_picture'] = $idPicFile;
            }
        }

        $message = 'Profile updated successfully.';
        $messageType = 'success';

        // Re-fetch user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :uid");
        $stmt->execute([':uid' => $userId]);
        $user = $stmt->fetch();
    } elseif ($action === 'change_password') {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
            $message = 'All password fields are required.';
            $messageType = 'error';
        } elseif (!password_verify($currentPass, $user['password'])) {
            $message = 'Current password is incorrect.';
            $messageType = 'error';
        } elseif (strlen($newPass) < 6) {
            $message = 'New password must be at least 6 characters.';
            $messageType = 'error';
        } elseif ($newPass !== $confirmPass) {
            $message = 'New passwords do not match.';
            $messageType = 'error';
        } else {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = :pw WHERE id = :uid")
                ->execute([':pw' => $hash, ':uid' => $userId]);
            $message = 'Password changed successfully.';
            $messageType = 'success';
        }
    }
}

$picUrl = getProfilePictureUrl($user['id_picture']);
$initials = '';
$nameParts = explode(' ', $user['full_name']);
if (count($nameParts) >= 2) {
    $initials = strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1));
} elseif (count($nameParts) === 1) {
    $initials = strtoupper(substr($nameParts[0], 0, 2));
}

$roleLabel = $user['role'] === 'superadmin' ? 'Super Admin' : 'Admin';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <div class="top-bar mobile-hidden-topbar">
        <div class="page-title">
            <span class="page-icon"><?= icon('user', '24') ?></span>
            <h2>My Profile</h2>
        </div>
        <a href="/admin/profile.php" class="user-dropdown" style="text-decoration:none;color:inherit;">
            <div class="user-avatar"><?= $initials ?></div>
            <div class="user-meta">
                <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
                <div class="user-role"><?= $roleLabel ?></div>
            </div>
        </a>
    </div>

    <?php if ($message): ?>
        <div class="card" style="margin-bottom:1rem;border-left:4px solid var(--<?= $messageType === 'success' ? 'success' : 'danger' ?>);background:var(--<?= $messageType === 'success' ? 'success' : 'danger' ?>-bg);">
            <p style="font-size:0.85rem;"><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>

    <!-- Profile Info Card -->
    <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-header">
            <div class="card-title"><?= icon('user', '18') ?> Profile Information</div>
            <span class="badge badge-ontime"><?= $roleLabel ?></span>
        </div>
        <div style="display:flex;gap:2rem;align-items:flex-start;flex-wrap:wrap;">
            <div style="text-align:center;">
                <?php if ($picUrl): ?>
                    <img src="<?= $picUrl ?>" alt="" style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:3px solid var(--primary);">
                <?php else: ?>
                    <div style="width:120px;height:120px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:2.5rem;font-weight:700;"><?= $initials ?></div>
                <?php endif; ?>
            </div>
            <div style="flex:1;min-width:250px;">
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;">
                    <div>
                        <label style="font-size:0.75rem;color:var(--text-light);text-transform:uppercase;letter-spacing:0.05em;">Employee ID</label>
                        <p style="font-weight:600;margin-top:0.25rem;"><?= htmlspecialchars($user['employee_id']) ?></p>
                    </div>
                    <div>
                        <label style="font-size:0.75rem;color:var(--text-light);text-transform:uppercase;letter-spacing:0.05em;">Full Name</label>
                        <p style="font-weight:600;margin-top:0.25rem;"><?= htmlspecialchars($user['full_name']) ?></p>
                    </div>
                    <div>
                        <label style="font-size:0.75rem;color:var(--text-light);text-transform:uppercase;letter-spacing:0.05em;">Division</label>
                        <p style="font-weight:600;margin-top:0.25rem;">
                            <?php if ($user['functional_division']): ?>
                                <span class="division-badge division-<?= strtolower($user['functional_division']) ?>"><?= $user['functional_division'] ?></span>
                            <?php else: ?>
                                <span style="color:var(--text-light);">Not assigned</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <label style="font-size:0.75rem;color:var(--text-light);text-transform:uppercase;letter-spacing:0.05em;">Position</label>
                        <p style="font-weight:600;margin-top:0.25rem;"><?= $user['position'] ? htmlspecialchars($user['position']) : '<span style="color:var(--text-light);">Not set</span>' ?></p>
                    </div>
                    <div>
                        <label style="font-size:0.75rem;color:var(--text-light);text-transform:uppercase;letter-spacing:0.05em;">Role</label>
                        <p style="font-weight:600;margin-top:0.25rem;"><?= $roleLabel ?></p>
                    </div>
                    <div>
                        <label style="font-size:0.75rem;color:var(--text-light);text-transform:uppercase;letter-spacing:0.05em;">Account Status</label>
                        <p style="font-weight:600;margin-top:0.25rem;">
                            <span class="badge <?= $user['is_active'] ? 'badge-ontime' : 'badge-absent' ?>"><?= $user['is_active'] ? 'Active' : 'Inactive' ?></span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile -->
    <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-header">
            <div class="card-title"><?= icon('edit', '18') ?> Edit Profile</div>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="form_action" value="update_profile">
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name</label>
                    <div class="input-wrapper">
                        <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required style="padding-left:0.75rem;">
                    </div>
                </div>
                <div class="form-group">
                    <label>Functional Division</label>
                    <select name="functional_division">
                        <option value="">-- Select --</option>
                        <option value="OSDS" <?= $user['functional_division'] === 'OSDS' ? 'selected' : '' ?>>OSDS</option>
                        <option value="SGOD" <?= $user['functional_division'] === 'SGOD' ? 'selected' : '' ?>>SGOD</option>
                        <option value="CID" <?= $user['functional_division'] === 'CID' ? 'selected' : '' ?>>CID</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Position</label>
                    <div class="input-wrapper">
                        <input type="text" name="position" value="<?= htmlspecialchars($user['position'] ?? '') ?>" placeholder="e.g., Schools Division Superintendent" style="padding-left:0.75rem;">
                    </div>
                </div>
                <div class="form-group">
                    <label>Profile Picture</label>
                    <input type="file" name="id_picture" accept="image/*" class="file-input-simple">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:auto;margin-top:0.5rem;">
                <?= icon('check', '16') ?> Save Changes
            </button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><?= icon('lock', '18') ?> Change Password</div>
        </div>
        <form method="POST">
            <input type="hidden" name="form_action" value="change_password">
            <div class="form-row">
                <div class="form-group">
                    <label>Current Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="current_password" required placeholder="Enter current password" style="padding-left:0.75rem;">
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>New Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="new_password" required placeholder="At least 6 characters" style="padding-left:0.75rem;">
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="confirm_password" required placeholder="Confirm new password" style="padding-left:0.75rem;">
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:auto;margin-top:0.5rem;">
                <?= icon('key', '16') ?> Change Password
            </button>
        </form>
    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
