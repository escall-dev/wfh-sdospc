<?php
$pageTitle = 'Manage Employees';
$currentPage = 'employees';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
requireAdminOrHr();

$message = '';
$messageType = '';
$_isHrUser = isHrTimekeeping();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$_isHrUser) {
    $action = $_POST['form_action'] ?? '';

    if ($action === 'add') {
        $empId = trim($_POST['employee_id'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $fullName = trim($_POST['full_name'] ?? '');
        $funcDiv = $_POST['functional_division'] ?? null;
        $password = $_POST['password'] ?? '';

        if (empty($empId) || empty($lastName) || empty($fullName) || empty($password)) {
            $message = 'Employee ID, Last Name, Full Name, and Password are required.';
            $messageType = 'error';
        } else {
            $check = $pdo->prepare("SELECT id FROM users WHERE employee_id = :eid");
            $check->execute([':eid' => $empId]);
            if ($check->fetch()) {
                $message = 'An employee with this ID already exists.';
                $messageType = 'error';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $idPicFile = null;

                if (!empty($_FILES['id_picture']['name'])) {
                    $uploadDir = __DIR__ . '/../assets/id_pictures/';
                    $ext = strtolower(pathinfo($_FILES['id_picture']['name'], PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (in_array($ext, $allowed) && $_FILES['id_picture']['size'] <= 5 * 1024 * 1024) {
                        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $fullName);
                        $idPicFile = $empId . '_' . $safeName . '.' . $ext;
                        move_uploaded_file($_FILES['id_picture']['tmp_name'], $uploadDir . $idPicFile);
                    }
                }

                $ins = $pdo->prepare("INSERT INTO users (employee_id, last_name, full_name, functional_division, position, recommending, id_picture, role, password, must_change_password) VALUES (:eid, :ln, :fn, :fd, :pos, :rec, :ip, 'employee', :pw, 1)");
                $ins->execute([
                    ':eid' => $empId,
                    ':ln' => $lastName,
                    ':fn' => $fullName,
                    ':fd' => $funcDiv ?: null,
                    ':pos' => trim($_POST['position'] ?? '') ?: null,
                    ':rec' => trim($_POST['recommending'] ?? '') ?: null,
                    ':ip' => $idPicFile,
                    ':pw' => $hash
                ]);
                $message = 'Employee added successfully.';
                $messageType = 'success';
            }
        }
    } elseif ($action === 'toggle' && isSuperAdmin()) {
        $uid = (int)($_POST['user_id'] ?? 0);
        $newStatus = (int)($_POST['new_status'] ?? 0);
        // Don't allow toggling own account
        if ($uid === getCurrentUserId()) {
            $message = 'You cannot deactivate your own account.';
            $messageType = 'error';
        } else {
            $pdo->prepare("UPDATE users SET is_active = :s WHERE id = :uid AND id != :myid")
                ->execute([':s' => $newStatus, ':uid' => $uid, ':myid' => getCurrentUserId()]);
            $message = $newStatus ? 'Employee activated.' : 'Employee deactivated.';
            $messageType = 'success';
        }
    } elseif ($action === 'edit' && isSuperAdmin()) {
        $uid = (int)($_POST['user_id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $funcDiv = $_POST['functional_division'] ?? null;
        $position = trim($_POST['position'] ?? '') ?: null;
        $recommending = trim($_POST['recommending'] ?? '') ?: null;
        $newPass = $_POST['new_password'] ?? '';

        if (!empty($fullName)) {
            $pdo->prepare("UPDATE users SET full_name = :fn, functional_division = :fd, position = :pos, recommending = :rec WHERE id = :uid")
                ->execute([':fn' => $fullName, ':fd' => $funcDiv ?: null, ':pos' => $position, ':rec' => $recommending, ':uid' => $uid]);
        }

        if (!empty($_FILES['edit_id_picture']['name'])) {
            $uploadDir = __DIR__ . '/../assets/id_pictures/';
            $ext = strtolower(pathinfo($_FILES['edit_id_picture']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed) && $_FILES['edit_id_picture']['size'] <= 5 * 1024 * 1024) {
                $empRow = $pdo->prepare("SELECT employee_id, full_name FROM users WHERE id = :uid");
                $empRow->execute([':uid' => $uid]);
                $empData = $empRow->fetch();
                $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $empData['full_name']);
                $idPicFile = $empData['employee_id'] . '_' . $safeName . '.' . $ext;
                move_uploaded_file($_FILES['edit_id_picture']['tmp_name'], $uploadDir . $idPicFile);
                $pdo->prepare("UPDATE users SET id_picture = :ip WHERE id = :uid")
                    ->execute([':ip' => $idPicFile, ':uid' => $uid]);
            }
        }

        if (!empty($newPass)) {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = :pw WHERE id = :uid")
                ->execute([':pw' => $hash, ':uid' => $uid]);
        }
        $message = 'Employee updated successfully.';
        $messageType = 'success';
    } elseif ($action === 'reset_password' && isSuperAdmin()) {
        $uid = (int)($_POST['user_id'] ?? 0);
        $empRow = $pdo->prepare("SELECT employee_id FROM users WHERE id = :uid");
        $empRow->execute([':uid' => $uid]);
        $empData = $empRow->fetch();
        if ($empData) {
            $hash = password_hash($empData['employee_id'], PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = :pw, must_change_password = 1 WHERE id = :uid")
                ->execute([':pw' => $hash, ':uid' => $uid]);
            $message = 'Password reset to Employee ID. They will be prompted to change it on next login.';
            $messageType = 'success';
        }
    } elseif ($action === 'delete_employee' && isSuperAdmin()) {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid === getCurrentUserId()) {
            $message = 'You cannot delete your own account.';
            $messageType = 'error';
        } else {
            $pdo->prepare("DELETE FROM users WHERE id = :uid AND id != :myid")
                ->execute([':uid' => $uid, ':myid' => getCurrentUserId()]);
            $message = 'Employee deleted successfully.';
            $messageType = 'delete_success';
        }
    } elseif ($action === 'change_role' && isSuperAdmin()) {
        $uid = (int)($_POST['user_id'] ?? 0);
        $newRole = $_POST['new_role'] ?? '';
        $allowedRoles = ['employee', 'admin', 'superadmin', 'hr_timekeeping'];
        if ($uid === getCurrentUserId()) {
            $message = 'You cannot change your own role.';
            $messageType = 'error';
        } elseif (!in_array($newRole, $allowedRoles)) {
            $message = 'Invalid role selected.';
            $messageType = 'error';
        } else {
            $pdo->prepare("UPDATE users SET role = :role WHERE id = :uid")
                ->execute([':role' => $newRole, ':uid' => $uid]);
            $roleLabels = ['employee' => 'Employee', 'admin' => 'Admin', 'superadmin' => 'Super Admin', 'hr_timekeeping' => 'HR Timekeeping'];
            $message = 'Role changed to ' . $roleLabels[$newRole] . ' successfully.';
            $messageType = 'role_success';
        }
    }
}

$filterDiv = $_GET['division'] ?? '';
$searchQuery = trim($_GET['search'] ?? '');
$currentUserId = getCurrentUserId();
$isSuperAdminUser = isSuperAdmin();
$canViewAllUsers = $isSuperAdminUser || $_isHrUser;
$myDivision = getCurrentUserDivision();

// Superadmin and HR see all users; regular admin sees only employees in their division
$sql = "SELECT * FROM users WHERE id != :myid";
$params = [':myid' => $currentUserId];

if (!$canViewAllUsers) {
    // Regular admin: everyone in their division except superadmins
    $sql .= " AND role != 'superadmin'";
    if ($myDivision) {
        $sql .= " AND functional_division = :mydiv";
        $params[':mydiv'] = $myDivision;
    }
} else {
    // Superadmin/HR can filter by role
    $filterRole = $_GET['role'] ?? '';
    if ($filterRole && in_array($filterRole, ['employee', 'admin', 'superadmin', 'hr_timekeeping'])) {
        $sql .= " AND role = :frole";
        $params[':frole'] = $filterRole;
    }
}

if ($filterDiv && in_array($filterDiv, ['OSDS', 'SGOD', 'CID'])) {
    $sql .= " AND functional_division = :div";
    $params[':div'] = $filterDiv;
}
if ($searchQuery) {
    $sql .= " AND (full_name LIKE :q OR employee_id LIKE :q2)";
    $params[':q'] = "%{$searchQuery}%";
    $params[':q2'] = "%{$searchQuery}%";
}

$sql .= " ORDER BY full_name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$employees = $stmt->fetchAll();

// Stats - scoped same way
$countWhere = "WHERE id != :myid";
$countParams = [':myid' => $currentUserId];
if (!$canViewAllUsers) {
    $countWhere .= " AND role != 'superadmin'";
    if ($myDivision) {
        $countWhere .= " AND functional_division = :mydiv";
        $countParams[':mydiv'] = $myDivision;
    }
}

$stmtDivCounts = $pdo->prepare("SELECT functional_division, COUNT(*) as cnt FROM users {$countWhere} GROUP BY functional_division");
$stmtDivCounts->execute($countParams);
$divCounts = $stmtDivCounts->fetchAll(PDO::FETCH_KEY_PAIR);

$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM users {$countWhere}");
$stmtTotal->execute($countParams);
$totalCount = $stmtTotal->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <div class="top-bar mobile-hidden-topbar">
        <div class="page-title">
            <span class="page-icon"><?= icon('employees', '24') ?></span>
            <h2>User Management</h2>
        </div>
        <a href="/admin/profile.php" class="user-dropdown" style="text-decoration:none;color:inherit;">
            <div class="user-avatar">AD</div>
            <div class="user-meta">
                <div class="user-name"><?= htmlspecialchars(getCurrentUserName()) ?></div>
                <div class="user-role"><?= isSuperAdmin() ? 'Super Admin' : ($_isHrUser ? 'HR Timekeeping' : 'Admin') ?></div>
            </div>
        </a>
    </div>

    <?php if ($message && $messageType !== 'role_success' && $messageType !== 'delete_success'): ?>
        <div class="card" style="margin-bottom:1rem;border-left:4px solid var(--<?= $messageType === 'success' ? 'success' : 'danger' ?>);background:var(--<?= $messageType === 'success' ? 'success' : 'danger' ?>-bg);">
            <p style="font-size:0.85rem;"><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>

    <!-- Division Stats -->
    <div class="stats-grid admin-stats" style="margin-bottom:1.5rem;">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Total Users</span>
                <span class="stat-icon"><?= icon('employees', '20') ?></span>
            </div>
            <div class="stat-value"><?= $totalCount ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">OSDS</span>
                <span class="division-badge division-osds">OSDS</span>
            </div>
            <div class="stat-value"><?= $divCounts['OSDS'] ?? 0 ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">SGOD</span>
                <span class="division-badge division-sgod">SGOD</span>
            </div>
            <div class="stat-value"><?= $divCounts['SGOD'] ?? 0 ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">CID</span>
                <span class="division-badge division-cid">CID</span>
            </div>
            <div class="stat-value"><?= $divCounts['CID'] ?? 0 ?></div>
        </div>
    </div>

    <!-- Add Employee Button Row -->
    <?php if (!$_isHrUser): ?>
    <div style="display:flex;gap:0.75rem;margin-bottom:1.5rem;">
        <button class="btn btn-primary" style="width:auto;" onclick="document.getElementById('add-modal').classList.add('show')">
            <?= icon('plus', '16') ?> Add New Employee
        </button>
        <!--<a href="/admin/import_employees.php" class="btn btn-accent" style="width:auto;">
            <?= icon('import', '14') ?> Import from DTR-->
        </a>
    </div>
    <?php endif; ?>

    <!-- Filter/Search Bar -->
    <div class="card" style="margin-bottom:1rem;">
        <form method="GET" class="filter-bar emp-filter-form">
            <div class="form-group emp-filter-search">
                <div class="input-wrapper">
                    <span class="input-icon"><?= icon('search', '16') ?></span>
                    <input type="text" name="search" placeholder="Search by name or ID..." value="<?= htmlspecialchars($searchQuery) ?>">
                </div>
            </div>
            <?php if ($canViewAllUsers): ?>
            <div class="emp-filter-selects">
                <div class="form-group">
                    <select name="division" onchange="this.form.submit()">
                        <option value="">All Divisions</option>
                        <option value="OSDS" <?= $filterDiv === 'OSDS' ? 'selected' : '' ?>>OSDS</option>
                        <option value="SGOD" <?= $filterDiv === 'SGOD' ? 'selected' : '' ?>>SGOD</option>
                        <option value="CID" <?= $filterDiv === 'CID' ? 'selected' : '' ?>>CID</option>
                    </select>
                </div>
                <div class="form-group">
                    <?php $filterRole = $_GET['role'] ?? ''; ?>
                    <select name="role" onchange="this.form.submit()">
                        <option value="">All Roles</option>
                        <option value="employee" <?= $filterRole === 'employee' ? 'selected' : '' ?>>Employee</option>
                        <option value="admin" <?= $filterRole === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="superadmin" <?= $filterRole === 'superadmin' ? 'selected' : '' ?>>Super Admin</option>
                        <option value="hr_timekeeping" <?= $filterRole === 'hr_timekeeping' ? 'selected' : '' ?>>HR Timekeeping</option>
                    </select>
                </div>
            </div>
            <?php else: ?>
            <div class="emp-filter-selects">
                <div class="form-group">
                    <select name="division" onchange="this.form.submit()">
                        <option value="">All Divisions</option>
                        <option value="OSDS" <?= $filterDiv === 'OSDS' ? 'selected' : '' ?>>OSDS</option>
                        <option value="SGOD" <?= $filterDiv === 'SGOD' ? 'selected' : '' ?>>SGOD</option>
                        <option value="CID" <?= $filterDiv === 'CID' ? 'selected' : '' ?>>CID</option>
                    </select>
                </div>
            </div>
            <?php endif; ?>
            <div class="emp-filter-actions">
                <button type="submit" class="btn btn-secondary btn-sm"><?= icon('search', '14') ?> Search</button>
                <?php if ($searchQuery || $filterDiv || (!empty($_GET['role']))): ?>
                    <a href="/admin/employees.php" class="btn btn-secondary btn-sm"><?= icon('x', '14') ?> Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Employee Table -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><?= icon('employees', '18') ?> All Users (<?= count($employees) ?>)</div>
        </div>
        <div class="table-wrapper admin-emp-table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Photo</th>
                        <th>Employee ID</th>
                        <th>Full Name</th>
                        <th>Division</th>
                        <th>Position</th>
                        <th>Recommending</th>
                        <?php if ($canViewAllUsers): ?><th>Role</th><?php endif; ?>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employees)): ?>
                        <tr><td colspan="<?= $canViewAllUsers ? 10 : 9 ?>" class="text-center" style="padding:2rem;">No employees found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($employees as $i => $emp): ?>
                            <?php $picUrl = getProfilePictureUrl($emp['id_picture']); ?>
                            <tr>
                                <td data-label="#"><?= $i + 1 ?></td>
                                <td data-label="Photo">
                                    <?php if ($picUrl): ?>
                                        <img src="<?= $picUrl ?>" alt="" class="table-avatar">
                                    <?php else: ?>
                                        <div class="table-avatar table-avatar-initials"><?= strtoupper(substr($emp['full_name'], 0, 1)) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Employee ID"><strong><?= htmlspecialchars($emp['employee_id']) ?></strong></td>
                                <td data-label="Full Name"><?= htmlspecialchars($emp['full_name']) ?></td>
                                <td data-label="Division">
                                    <?php if ($emp['functional_division']): ?>
                                        <span class="division-badge division-<?= strtolower($emp['functional_division']) ?>"><?= $emp['functional_division'] ?></span>
                                    <?php else: ?>
                                        <span style="color:var(--text-light);font-size:0.8rem;">--</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Position"><?= $emp['position'] ? htmlspecialchars($emp['position']) : '<span style="color:var(--text-light);font-size:0.8rem;">--</span>' ?></td>
                                <td data-label="Recommending"><?= $emp['recommending'] ? htmlspecialchars($emp['recommending']) : '<span style="color:var(--text-light);font-size:0.8rem;">--</span>' ?></td>
                                <?php if ($canViewAllUsers): ?>
                                <td data-label="Role">
                                    <?php
                                        $roleBadgeMap = [
                                            'employee' => ['class' => 'badge-grace', 'label' => 'Employee'],
                                            'admin' => ['class' => 'badge-ontime', 'label' => 'Admin'],
                                            'superadmin' => ['class' => 'badge-late', 'label' => 'Super Admin'],
                                            'hr_timekeeping' => ['class' => 'badge-leave', 'label' => 'HR Timekeeping'],
                                        ];
                                        $rb = $roleBadgeMap[$emp['role']] ?? $roleBadgeMap['employee'];
                                    ?>
                                    <span class="badge <?= $rb['class'] ?>"><?= $rb['label'] ?></span>
                                </td>
                                <?php endif; ?>
                                <td data-label="Status">
                                    <?php if ($emp['is_active']): ?>
                                        <span class="badge badge-ontime">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-absent">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Actions">
                                    <div class="admin-actions">
                                        <?php if ($isSuperAdminUser): ?>
                                        <button class="btn btn-secondary btn-sm" onclick="openEditModal(<?= htmlspecialchars(json_encode($emp)) ?>)" title="Edit Employee"><?= icon('edit', '12') ?></button>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="form_action" value="toggle">
                                            <input type="hidden" name="user_id" value="<?= $emp['id'] ?>">
                                            <input type="hidden" name="new_status" value="<?= $emp['is_active'] ? 0 : 1 ?>">
                                            <button type="submit" class="btn <?= $emp['is_active'] ? 'btn-danger-outline' : 'btn-accent' ?> btn-sm" title="<?= $emp['is_active'] ? 'Deactivate Employee' : 'Activate Employee' ?>">
                                                <?= $emp['is_active'] ? icon('ban', '12') : icon('check', '12') ?>
                                            </button>
                                        </form>
                                        <button class="btn btn-secondary btn-sm reset-pwd-btn" data-uid="<?= $emp['id'] ?>" data-name="<?= htmlspecialchars($emp['full_name'], ENT_QUOTES) ?>" title="Reset password to Employee ID"><?= icon('key', '12') ?></button>
                                        <button class="btn btn-secondary btn-sm role-btn" data-uid="<?= $emp['id'] ?>" data-name="<?= htmlspecialchars($emp['full_name'], ENT_QUOTES) ?>" data-role="<?= $emp['role'] ?>" title="Change Role"><?= icon('shield', '12') ?></button>
                                        <button class="btn btn-danger-outline btn-sm delete-emp-btn"
                                            data-uid="<?= $emp['id'] ?>"
                                            data-name="<?= htmlspecialchars($emp['full_name'], ENT_QUOTES) ?>"
                                            data-empid="<?= htmlspecialchars($emp['employee_id'], ENT_QUOTES) ?>"
                                            data-division="<?= htmlspecialchars($emp['functional_division'] ?? '--', ENT_QUOTES) ?>"
                                            data-position="<?= htmlspecialchars($emp['position'] ?? '--', ENT_QUOTES) ?>"
                                            data-role="<?= htmlspecialchars($emp['role'], ENT_QUOTES) ?>"
                                            title="Delete Employee"><?= icon('trash', '12') ?></button>
                                        <?php else: ?>
                                        <button class="btn btn-secondary btn-sm" onclick="openViewModal(<?= htmlspecialchars(json_encode($emp)) ?>)" title="View Employee"><?= icon('eye', '12') ?></button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<!-- Add Employee Modal -->
<div class="modal-overlay" id="add-modal">
    <div class="modal" style="max-width:600px;">
        <div class="modal-header">
            <h3><?= icon('plus', '20') ?> Add New Employee</h3>
            <button class="btn-icon" onclick="document.getElementById('add-modal').classList.remove('show')"><?= icon('x', '20') ?></button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="form_action" value="add">
            <div class="form-row">
                <div class="form-group">
                    <label>Employee ID</label>
                    <div class="input-wrapper">
                        <input type="text" name="employee_id" placeholder="e.g., 04-0245" required style="padding-left:0.75rem;">
                    </div>
                </div>
                <div class="form-group">
                    <label>Last Name</label>
                    <div class="input-wrapper">
                        <input type="text" name="last_name" placeholder="e.g., Santos" required style="padding-left:0.75rem;">
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name</label>
                    <div class="input-wrapper">
                        <input type="text" name="full_name" placeholder="e.g., Juan D. Santos" required style="padding-left:0.75rem;">
                    </div>
                </div>
                <div class="form-group">
                    <label>Functional Division</label>
                    <select name="functional_division" id="add-func-div" onchange="autoFillRecommending(this.value, 'add-recommending')">
                        <option value="">-- Select --</option>
                        <option value="OSDS">OSDS</option>
                        <option value="SGOD">SGOD</option>
                        <option value="CID">CID</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Position</label>
                    <div class="input-wrapper">
                        <input type="text" name="position" placeholder="e.g., Education Program Specialist II" style="padding-left:0.75rem;">
                    </div>
                </div>
                <div class="form-group">
                    <label>Recommending (Supervisor)</label>
                    <div class="input-wrapper">
                        <input type="text" name="recommending" id="add-recommending" placeholder="Auto-filled by division" style="padding-left:0.75rem;">
                    </div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" placeholder="Initial password" required style="padding-left:0.75rem;">
                    </div>
                </div>
                <div class="form-group">
                    <label>ID Picture (optional)</label>
                    <input type="file" name="id_picture" accept="image/*" class="file-input-simple">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:auto;margin-top:0.5rem;">
                <?= icon('plus', '16') ?> Add Employee
            </button>
        </form>
    </div>
</div>

<!-- View Modal (Admin only) -->
<div class="modal-overlay" id="view-modal">
    <div class="modal" style="max-width:480px;">
        <div class="modal-header">
            <h3><?= icon('eye', '20') ?> Employee Details</h3>
            <button class="btn-icon" onclick="document.getElementById('view-modal').classList.remove('show')"><?= icon('x', '20') ?></button>
        </div>
        <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1.25rem;">
            <div id="view-avatar" style="width:60px;height:60px;border-radius:50%;overflow:hidden;flex-shrink:0;"></div>
            <div>
                <div id="view-full-name" style="font-size:1.05rem;font-weight:700;"></div>
                <div id="view-employee-id" style="font-size:0.82rem;color:var(--text-light);"></div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;font-size:0.88rem;">
            <div><span style="color:var(--text-light);display:block;font-size:0.75rem;">Division</span><span id="view-division" style="font-weight:500;"></span></div>
            <div><span style="color:var(--text-light);display:block;font-size:0.75rem;">Position</span><span id="view-position" style="font-weight:500;"></span></div>
            <div><span style="color:var(--text-light);display:block;font-size:0.75rem;">Recommending</span><span id="view-recommending" style="font-weight:500;"></span></div>
            <div><span style="color:var(--text-light);display:block;font-size:0.75rem;">Status</span><span id="view-status" style="font-weight:500;"></span></div>
        </div>
        <div style="margin-top:1.25rem;text-align:right;">
            <button class="btn btn-secondary" onclick="document.getElementById('view-modal').classList.remove('show')">Close</button>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="edit-modal">
    <div class="modal">
        <div class="modal-header">
            <h3>Edit Employee</h3>
            <button class="btn-icon" onclick="closeEditModal()"><?= icon('x', '20') ?></button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="form_action" value="edit">
            <input type="hidden" name="user_id" id="edit-user-id">
            <div class="form-group">
                <label>Full Name</label>
                <div class="input-wrapper">
                    <input type="text" name="full_name" id="edit-full-name" style="padding-left:0.75rem;">
                </div>
            </div>
            <div class="form-group">
                <label>Functional Division</label>
                <select name="functional_division" id="edit-func-div" onchange="autoFillRecommending(this.value, 'edit-recommending')">
                    <option value="">-- Select --</option>
                    <option value="OSDS">OSDS</option>
                    <option value="SGOD">SGOD</option>
                    <option value="CID">CID</option>
                </select>
            </div>
            <div class="form-group">
                <label>Position</label>
                <div class="input-wrapper">
                    <input type="text" name="position" id="edit-position" style="padding-left:0.75rem;">
                </div>
            </div>
            <div class="form-group">
                <label>Recommending (Supervisor)</label>
                <div class="input-wrapper">
                    <input type="text" name="recommending" id="edit-recommending" style="padding-left:0.75rem;">
                </div>
            </div>
            <div class="form-group">
                <label>ID Picture (leave empty to keep current)</label>
                <input type="file" name="edit_id_picture" accept="image/*" class="file-input-simple">
                <div id="edit-current-pic" style="margin-top:0.5rem;"></div>
            </div>
            <div class="form-group">
                <label>New Password (leave blank to keep current)</label>
                <div class="input-wrapper">
                    <input type="password" name="new_password" id="edit-new-password" placeholder="New password" style="padding-left:0.75rem;"
                        data-icon-eye="<?= htmlspecialchars(icon('eye', '18')) ?>"
                        data-icon-off="<?= htmlspecialchars(icon('eye-off', '18')) ?>">
                    <button type="button" class="password-toggle"
                        data-icon-eye="<?= htmlspecialchars(icon('eye', '18')) ?>"
                        data-icon-off="<?= htmlspecialchars(icon('eye-off', '18')) ?>"><?= icon('eye-off', '18') ?></button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top:0.5rem;">Save Changes</button>
        </form>
    </div>
</div>

<!-- Reset Password Confirm Dialog -->
<div class="modal-overlay" id="reset-pwd-overlay" style="z-index:10001;">
    <div class="modal" style="max-width:360px;text-align:center;">
        <div style="width:60px;height:60px;background:#fef3c7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
            <?= icon('key', '28') ?>
        </div>
        <h3 style="font-size:1.1rem;font-weight:700;color:var(--text);margin-bottom:0.4rem;">Reset Password</h3>
        <p style="font-size:0.88rem;color:var(--text-light);line-height:1.5;margin-bottom:1.5rem;" id="reset-pwd-text"></p>
        <div style="display:flex;gap:0.75rem;">
            <button class="btn btn-secondary" style="flex:1;" onclick="closeResetPwdConfirm()">Cancel</button>
            <button class="btn btn-primary" style="flex:1;" id="reset-pwd-submit">Yes, Reset</button>
        </div>
        <form id="reset-pwd-form" method="POST" style="display:none;">
            <input type="hidden" name="form_action" value="reset_password">
            <input type="hidden" name="user_id" id="reset-pwd-uid">
        </form>
    </div>
</div>

<?php if ($isSuperAdminUser): ?>
<!-- Delete Employee Confirm Dialog -->
<div class="modal-overlay" id="delete-emp-overlay" style="z-index:10001;">
    <div class="modal" style="max-width:400px;text-align:center;">
        <div style="width:60px;height:60px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
            <?= icon('trash', '28') ?>
        </div>
        <h3 style="font-size:1.1rem;font-weight:700;color:var(--text);margin-bottom:0.75rem;">Delete Employee</h3>
        <p style="font-size:0.88rem;color:var(--text-light);line-height:1.5;margin-bottom:1rem;">Are you sure you want to delete this employee? This action cannot be undone.</p>
        <div style="background:var(--bg);border-radius:8px;padding:0.75rem;margin-bottom:1.25rem;text-align:left;font-size:0.85rem;line-height:1.8;">
            <div><strong>Name:</strong> <span id="delete-emp-name"></span></div>
            <div><strong>Employee ID:</strong> <span id="delete-emp-id"></span></div>
            <div><strong>Division:</strong> <span id="delete-emp-division"></span></div>
            <div><strong>Position:</strong> <span id="delete-emp-position"></span></div>
            <div><strong>Role:</strong> <span id="delete-emp-role"></span></div>
        </div>
        <div style="display:flex;gap:0.75rem;">
            <button class="btn btn-secondary" style="flex:1;" onclick="closeDeleteEmpConfirm()">Cancel</button>
            <button class="btn btn-danger" style="flex:1;" id="delete-emp-submit"><?= icon('trash', '14') ?> Yes, Delete</button>
        </div>
        <form id="delete-emp-form" method="POST" style="display:none;">
            <input type="hidden" name="form_action" value="delete_employee">
            <input type="hidden" name="user_id" id="delete-emp-uid">
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($isSuperAdminUser): ?>
<!-- Role Change Modal -->
<div class="modal-overlay" id="role-modal">
    <div class="modal" style="max-width:420px;">
        <div class="modal-header">
            <h3><?= icon('shield', '20') ?> Change Role</h3>
            <button class="btn-icon" onclick="closeRoleModal()"><?= icon('x', '20') ?></button>
        </div>
        <form id="role-change-form" method="POST">
            <input type="hidden" name="form_action" value="change_role">
            <input type="hidden" name="user_id" id="role-user-id">
            <div class="form-group">
                <label style="font-size:0.8rem;color:var(--text-light);">Employee</label>
                <p style="font-weight:700;font-size:1rem;margin:0.25rem 0 0;" id="role-emp-name"></p>
            </div>
            <div class="form-group">
                <label>Assign New Role</label>
                <select name="new_role" id="role-select" style="font-size:0.95rem;">
                    <option value="employee">Employee</option>
                    <option value="admin">Admin</option>
                    <option value="superadmin">Super Admin</option>
                    <option value="hr_timekeeping">HR Timekeeping</option>
                </select>
            </div>
            <div style="background:var(--bg);border-radius:8px;padding:0.75rem;margin-bottom:1rem;font-size:0.8rem;color:var(--text-light);">
                <strong>Role Descriptions:</strong>
                <ul style="margin:0.5rem 0 0 1rem;padding:0;line-height:1.75;">
                    <li><strong>Employee</strong> — Regular user, clock in/out and own records</li>
                    <li><strong>Admin</strong> — SGOD Chief, CID Chief, AO V Administrative and HR personnels. Oversees division employees</li>
                    <li><strong>Super Admin</strong> — Full access, role management</li>
                    <li><strong>HR Timekeeping</strong> — Read-only access to all employee records, can export DTR and IDLAR reports</li>
                </ul>
            </div>
            <div style="display:flex;gap:0.75rem;margin-top:0.5rem;">
                <button type="button" class="btn btn-secondary" style="flex:1;" onclick="closeRoleModal()">Cancel</button>
                <button type="button" class="btn btn-primary" style="flex:1;" onclick="confirmRoleChange()">
                    <?= icon('shield', '16') ?> Update Role
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Role Change Confirm Dialog -->
<div class="modal-overlay" id="role-confirm-overlay" style="z-index:10001;">
    <div class="modal" style="max-width:360px;text-align:center;">
        <div style="width:60px;height:60px;background:#fef3c7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
            <?= icon('shield', '28') ?>
        </div>
        <h3 style="font-size:1.1rem;font-weight:700;color:var(--text);margin-bottom:0.4rem;">Confirm Role Change</h3>
        <p style="font-size:0.88rem;color:var(--text-light);line-height:1.5;margin-bottom:1.5rem;" id="role-confirm-text"></p>
        <div style="display:flex;gap:0.75rem;">
            <button class="btn btn-secondary" style="flex:1;" onclick="closeRoleConfirm()">Cancel</button>
            <button class="btn btn-primary" style="flex:1;" id="role-confirm-submit">Yes, Change Role</button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$inlineScript = <<<'JS'
var divisionRecommending = {
    'OSDS': 'AO V - ADMINISTRATIVE',
    'SGOD': 'SGOD CHIEF',
    'CID':  'CID CHIEF'
};

function autoFillRecommending(division, targetId) {
    var recField = document.getElementById(targetId);
    if (recField && divisionRecommending[division]) {
        recField.value = divisionRecommending[division];
    } else if (recField) {
        recField.value = '';
    }
}

function openViewModal(emp) {
    var picHtml = emp.id_picture
        ? '<img src="/assets/id_pictures/' + emp.id_picture + '" alt="" style="width:100%;height:100%;object-fit:cover;">'
        : '<div style="width:60px;height:60px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.3rem;font-weight:700;">' + emp.full_name.charAt(0).toUpperCase() + '</div>';
    document.getElementById('view-avatar').innerHTML = picHtml;
    document.getElementById('view-full-name').textContent = emp.full_name;
    document.getElementById('view-employee-id').textContent = 'ID: ' + emp.employee_id;
    document.getElementById('view-division').textContent = emp.functional_division || '--';
    document.getElementById('view-position').textContent = emp.position || '--';
    document.getElementById('view-recommending').textContent = emp.recommending || '--';
    document.getElementById('view-status').textContent = emp.is_active == 1 ? 'Active' : 'Inactive';
    document.getElementById('view-modal').classList.add('show');
}

document.getElementById('view-modal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('show');
});

function openEditModal(emp) {
    document.getElementById('edit-user-id').value = emp.id;
    document.getElementById('edit-full-name').value = emp.full_name;
    document.getElementById('edit-func-div').value = emp.functional_division || '';
    document.getElementById('edit-position').value = emp.position || '';
    document.getElementById('edit-recommending').value = emp.recommending || '';
    document.getElementById('edit-new-password').value = '';

    var picDiv = document.getElementById('edit-current-pic');
    if (emp.id_picture) {
        picDiv.innerHTML = '<img src="/assets/id_pictures/' + emp.id_picture + '" alt="" style="width:60px;height:60px;border-radius:50%;object-fit:cover;">';
    } else {
        picDiv.innerHTML = '<span style="font-size:0.8rem;color:#888;">No current picture</span>';
    }

    document.getElementById('edit-modal').classList.add('show');
}

function closeEditModal() {
    document.getElementById('edit-modal').classList.remove('show');
}

document.getElementById('edit-modal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});

document.getElementById('add-modal').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('show');
});

// AJAX edit form submission
document.querySelector('#edit-modal form').addEventListener('submit', async function(e) {
    e.preventDefault();
    var btn = this.querySelector('[type="submit"]');
    var origLabel = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Saving...';

    try {
        var formData = new FormData(this);
        var resp = await fetch('/api/save_employee.php', {
            method: 'POST',
            body: formData
        });
        var result = await resp.json();

        if (result.success) {
            closeEditModal();
            showEmployeeSuccessModal(result.message);
            setTimeout(function() { location.reload(); }, 2200);
        } else {
            showToast(result.message || 'Failed to save changes.', 'error');
            btn.disabled = false;
            btn.innerHTML = origLabel;
        }
    } catch (err) {
        showToast('Network error. Please try again.', 'error');
        btn.disabled = false;
        btn.innerHTML = origLabel;
    }
});

function showEmployeeSuccessModal(message) {
    var existing = document.getElementById('emp-success-overlay');
    if (existing) existing.remove();

    var overlay = document.createElement('div');
    overlay.id = 'emp-success-overlay';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.45);display:flex;align-items:center;justify-content:center;z-index:10000;opacity:0;transition:opacity 0.25s ease;';

    overlay.innerHTML =
        '<div id="emp-success-card" style="background:#fff;border-radius:16px;padding:2.5rem 2rem;text-align:center;max-width:340px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.25);transform:scale(0.9);transition:transform 0.25s ease;">' +
            '<div style="width:68px;height:68px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">' +
                '<svg xmlns="http://www.w3.org/2000/svg" width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>' +
            '</div>' +
            '<h3 style="font-size:1.2rem;font-weight:700;color:#1e293b;margin-bottom:0.4rem;">Changes Saved!</h3>' +
            '<p style="color:#64748b;font-size:0.88rem;line-height:1.5;">' + message + '</p>' +
        '</div>';

    document.body.appendChild(overlay);

    requestAnimationFrame(function() {
        overlay.style.opacity = '1';
        var card = document.getElementById('emp-success-card');
        if (card) card.style.transform = 'scale(1)';
    });

    setTimeout(function() {
        overlay.style.opacity = '0';
        var card = document.getElementById('emp-success-card');
        if (card) card.style.transform = 'scale(0.9)';
        setTimeout(function() { overlay.remove(); }, 280);
    }, 1800);
}

// Reset password confirm
document.addEventListener('click', function(e) {
    var btn = e.target.closest('.reset-pwd-btn');
    if (btn) {
        var uid = btn.dataset.uid;
        var name = btn.dataset.name;
        document.getElementById('reset-pwd-uid').value = uid;
        document.getElementById('reset-pwd-text').innerHTML =
            'Reset <strong>' + name + '</strong>\'s password to their Employee ID?';
        document.getElementById('reset-pwd-overlay').classList.add('show');
        document.getElementById('reset-pwd-submit').onclick = function() {
            document.getElementById('reset-pwd-form').submit();
        };
    }
});

function closeResetPwdConfirm() {
    document.getElementById('reset-pwd-overlay').classList.remove('show');
}

var resetPwdOverlay = document.getElementById('reset-pwd-overlay');
if (resetPwdOverlay) {
    resetPwdOverlay.addEventListener('click', function(e) {
        if (e.target === this) closeResetPwdConfirm();
    });
}

// Role modal functions
var _roleLabels = { employee: 'Employee', admin: 'Admin ', superadmin: 'Super Admin', hr_timekeeping: 'HR Timekeeping' };

// Use event delegation to handle role buttons (avoids issues with special chars in names)
document.addEventListener('click', function(e) {
    var btn = e.target.closest('.role-btn');
    if (btn) {
        openRoleModal(btn.dataset.uid, btn.dataset.name, btn.dataset.role);
    }
});

function openRoleModal(userId, fullName, currentRole) {
    var modal = document.getElementById('role-modal');
    if (!modal) return;
    document.getElementById('role-user-id').value = userId;
    document.getElementById('role-emp-name').textContent = fullName;
    document.getElementById('role-select').value = currentRole;
    modal.classList.add('show');
}

function closeRoleModal() {
    var modal = document.getElementById('role-modal');
    if (modal) modal.classList.remove('show');
}

function confirmRoleChange() {
    var empName = document.getElementById('role-emp-name').textContent;
    var newRole = document.getElementById('role-select').value;
    var roleLabel = _roleLabels[newRole] || newRole;

    document.getElementById('role-confirm-text').innerHTML =
        'You are about to change <strong>' + empName + '</strong>\'s role to <strong>' + roleLabel + '</strong>. This will affect their access immediately.';

    var confirmOverlay = document.getElementById('role-confirm-overlay');
    confirmOverlay.classList.add('show');

    document.getElementById('role-confirm-submit').onclick = function() {
        closeRoleConfirm();
        closeRoleModal();
        document.getElementById('role-change-form').submit();
    };
}

function closeRoleConfirm() {
    var overlay = document.getElementById('role-confirm-overlay');
    if (overlay) overlay.classList.remove('show');
}

var roleModal = document.getElementById('role-modal');
if (roleModal) {
    roleModal.addEventListener('click', function(e) {
        if (e.target === this) closeRoleModal();
    });
}

var roleConfirmOverlay = document.getElementById('role-confirm-overlay');
if (roleConfirmOverlay) {
    roleConfirmOverlay.addEventListener('click', function(e) {
        if (e.target === this) closeRoleConfirm();
    });
}

// Delete employee confirm
var _deleteRoleLabels = { employee: 'Employee', admin: 'Admin', superadmin: 'Super Admin', hr_timekeeping: 'HR Timekeeping' };

document.addEventListener('click', function(e) {
    var btn = e.target.closest('.delete-emp-btn');
    if (btn) {
        document.getElementById('delete-emp-uid').value = btn.dataset.uid;
        document.getElementById('delete-emp-name').textContent = btn.dataset.name;
        document.getElementById('delete-emp-id').textContent = btn.dataset.empid;
        document.getElementById('delete-emp-division').textContent = btn.dataset.division;
        document.getElementById('delete-emp-position').textContent = btn.dataset.position;
        document.getElementById('delete-emp-role').textContent = _deleteRoleLabels[btn.dataset.role] || btn.dataset.role;
        document.getElementById('delete-emp-overlay').classList.add('show');
        document.getElementById('delete-emp-submit').onclick = function() {
            document.getElementById('delete-emp-form').submit();
        };
    }
});

function closeDeleteEmpConfirm() {
    document.getElementById('delete-emp-overlay').classList.remove('show');
}

var deleteEmpOverlay = document.getElementById('delete-emp-overlay');
if (deleteEmpOverlay) {
    deleteEmpOverlay.addEventListener('click', function(e) {
        if (e.target === this) closeDeleteEmpConfirm();
    });
}

JS;

// Auto-show success modal for role change or delete
if (in_array($messageType, ['role_success', 'delete_success']) && $message) {
    $inlineScript .= "\nwindow.addEventListener('DOMContentLoaded', function() { showEmployeeSuccessModal(" . json_encode($message, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) . "); });";
}

require_once __DIR__ . '/../includes/footer.php';
?>
