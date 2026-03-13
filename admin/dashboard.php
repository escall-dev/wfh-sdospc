<?php
$pageTitle = 'Admin Dashboard';
$currentPage = 'dashboard';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
requireAdminOrHr();

$selectedDate = $_GET['date'] ?? date('Y-m-d');
$filterStatus = $_GET['status'] ?? 'all';
$_isSuperAdmin = isSuperAdmin();
$_isHr = isHrTimekeeping();
$_canSeeAll = $_isSuperAdmin || $_isHr;
$_myDivision = getCurrentUserDivision();
$_divFilter = '';
$_divParams = [];
if (!$_canSeeAll && $_myDivision) {
    $_divFilter = " AND u.functional_division = :mydiv";
    $_divParams = [':mydiv' => $_myDivision];
}

// CSV Export (must be before any HTML output)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $stmtExport = $pdo->prepare("
        SELECT u.employee_id, u.full_name, al.time_in, al.lunch_out, al.lunch_in, al.time_out, al.total_hours, al.am_status
        FROM users u LEFT JOIN attendance_logs al ON u.id = al.user_id AND al.date = :dt
        WHERE u.role = 'employee' AND u.is_active = 1{$_divFilter} ORDER BY u.full_name
    ");
    $stmtExport->execute(array_merge([':dt' => $selectedDate], $_divParams));

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_' . $selectedDate . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Employee ID', 'Full Name', 'Time In', 'Lunch Out', 'Lunch In', 'Time Out', 'Total Hours', 'Status']);
    while ($row = $stmtExport->fetch()) {
        fputcsv($out, [
            $row['employee_id'], $row['full_name'],
            formatTimeDisplay($row['time_in']), formatTimeDisplay($row['lunch_out']),
            formatTimeDisplay($row['lunch_in']), formatTimeDisplay($row['time_out']),
            $row['total_hours'] ?? '', getStatusLabel($row['am_status'] ?? 'absent')
        ]);
    }
    fclose($out);
    exit;
}

$stmt = $pdo->prepare("
    SELECT u.id, u.employee_id, u.full_name,
           al.time_in, al.lunch_out, al.lunch_in, al.time_out,
           al.total_hours, al.am_status
    FROM users u
    LEFT JOIN attendance_logs al ON u.id = al.user_id AND al.date = :dt
    WHERE u.role IN ('employee','admin','superadmin','hr_timekeeping') AND u.is_active = 1{$_divFilter}
    ORDER BY u.full_name ASC
");
$stmt->execute(array_merge([':dt' => $selectedDate], $_divParams));
$employees = $stmt->fetchAll();

// Fetch OB entries for the selected date
$obStmt = $pdo->prepare("
    SELECT ob.user_id, ob.time_from, ob.time_to FROM official_business ob
    JOIN users u ON u.id = ob.user_id
    WHERE ob.ob_date = :dt AND u.role = 'employee' AND u.is_active = 1{$_divFilter}
");
$obStmt->execute(array_merge([':dt' => $selectedDate], $_divParams));
$obUsers = [];
foreach ($obStmt->fetchAll() as $obRow) {
    $obUsers[$obRow['user_id']] = $obRow;
}

$totalEmployees = count($employees);
$present = $grace = $late = $absent = $leave = $obCount = 0;

foreach ($employees as $emp) {

    $hasOB = isset($obUsers[$emp['id']]);
    if ($hasOB) $obCount++;

    $status = $emp['am_status'] ?? null;

    if (in_array($status, ['leave', 'am_leave', 'pm_leave'])) {
        $leave++;

    } elseif ($emp['time_in']) {

        if ($status === 'on_time') $present++;
        elseif ($status === 'grace') $grace++;
        elseif ($status === 'late') $late++;
        else $present++;

    } else {

        // Do not count absent if employee has OB
        if (!$hasOB) {
            $absent++;
        }

    }
}

if ($filterStatus !== 'all') {
    $employees = array_filter($employees, function($emp) use ($filterStatus, $obUsers) {
        $status = $emp['am_status'] ?? null;
        $hasOB = isset($obUsers[$emp['id']]);

        if ($filterStatus === 'present') return $emp['time_in'] && $status === 'on_time';
        if ($filterStatus === 'grace') return $status === 'grace';
        if ($filterStatus === 'late') return $status === 'late';
        if ($filterStatus === 'leave') return in_array($status, ['leave', 'am_leave', 'pm_leave']);
        if ($filterStatus === 'am_leave') return $status === 'am_leave';
        if ($filterStatus === 'pm_leave') return $status === 'pm_leave';
        if ($filterStatus === 'whole_leave') return $status === 'leave';

        // Exclude OB users from absent
        if ($filterStatus === 'absent') 
            return !$emp['time_in'] && 
                   !in_array($status, ['leave', 'am_leave', 'pm_leave']) && 
                   !$hasOB;

        if ($filterStatus === 'ob') return $hasOB;

        return true;
    });
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <div class="top-bar mobile-hidden-topbar">
        <div class="page-title">
            <span class="page-icon"><?= icon('reports', '24') ?></span>
            <div>
                <h2>Admin Dashboard</h2>
                <p style="font-size:0.8rem;color:var(--text-light)">Today's attendance overview<?= !$_canSeeAll && $_myDivision ? ' — ' . $_myDivision : '' ?></p>
            </div>
        </div>
        <a href="/admin/profile.php" class="user-dropdown" style="text-decoration:none;color:inherit;">
            <div class="user-avatar">AD</div>
            <div class="user-meta">
                <div class="user-name"><?= htmlspecialchars(getCurrentUserName()) ?></div>
                <div class="user-role"><?= $_isSuperAdmin ? 'Super Admin' : ($_isHr ? 'HR Timekeeping' : 'Admin') ?></div>
            </div>
        </a>
    </div>

    <div class="stats-grid admin-stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Total Employees</span>
                <span class="stat-icon"><?= icon('employees', '20') ?></span>
            </div>
            <div class="stat-value"><?= $totalEmployees ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Present / On Time</span>
                <span class="stat-icon" style="color:var(--success)"><?= icon('check-circle', '20') ?></span>
            </div>
            <div class="stat-value" style="color:var(--success)"><?= $present ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Grace Period</span>
                <span class="stat-icon" style="color:var(--warning)"><?= icon('clock', '20') ?></span>
            </div>
            <div class="stat-value" style="color:var(--warning)"><?= $grace ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Late</span>
                <span class="stat-icon" style="color:#ea580c"><?= icon('clock', '20') ?></span>
            </div>
            <div class="stat-value" style="color:#ea580c"><?= $late ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">On Leave</span>
                <span class="stat-icon" style="color:var(--info)"><?= icon('calendar', '20') ?></span>
            </div>
            <div class="stat-value" style="color:var(--info)"><?= $leave ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Absent</span>
                <span class="stat-icon" style="color:var(--danger)"><?= icon('x', '20') ?></span>
            </div>
            <div class="stat-value" style="color:var(--danger)"><?= $absent ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Official Business</span>
                <span class="stat-icon" style="color:#c2410c"><?= icon('briefcase', '20') ?></span>
            </div>
            <div class="stat-value" style="color:#c2410c"><?= $obCount ?></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title"><?= icon('calendar', '18') ?> Attendance for <?= date('F j, Y', strtotime($selectedDate)) ?></div>
            <div style="display:flex;gap:0.5rem;align-items:center;">
                <input type="date" value="<?= $selectedDate ?>" onchange="window.location.href='?date='+this.value+'&status=<?= $filterStatus ?>'"
                    style="padding:0.4rem 0.6rem;border:1.5px solid var(--border);border-radius:6px;font-size:0.82rem;">
                <a href="?date=<?= $selectedDate ?>&export=csv" class="btn btn-secondary btn-sm"><?= icon('download', '14') ?> Export CSV</a>
            </div>
        </div>

        <div class="filter-bar">
            <a href="?date=<?= $selectedDate ?>&status=all" class="filter-btn <?= $filterStatus === 'all' ? 'active' : '' ?>">All (<?= $totalEmployees ?>)</a>
            <a href="?date=<?= $selectedDate ?>&status=present" class="filter-btn <?= $filterStatus === 'present' ? 'active' : '' ?>">On Time (<?= $present ?>)</a>
            <a href="?date=<?= $selectedDate ?>&status=grace" class="filter-btn <?= $filterStatus === 'grace' ? 'active' : '' ?>">Grace (<?= $grace ?>)</a>
            <a href="?date=<?= $selectedDate ?>&status=late" class="filter-btn <?= $filterStatus === 'late' ? 'active' : '' ?>">Late (<?= $late ?>)</a>
            <a href="?date=<?= $selectedDate ?>&status=leave" class="filter-btn <?= $filterStatus === 'leave' ? 'active' : '' ?>">On Leave (<?= $leave ?>)</a>
            <a href="?date=<?= $selectedDate ?>&status=am_leave" class="filter-btn <?= $filterStatus === 'am_leave' ? 'active' : '' ?>">AM Leave</a>
            <a href="?date=<?= $selectedDate ?>&status=pm_leave" class="filter-btn <?= $filterStatus === 'pm_leave' ? 'active' : '' ?>">PM Leave</a>
            <a href="?date=<?= $selectedDate ?>&status=whole_leave" class="filter-btn <?= $filterStatus === 'whole_leave' ? 'active' : '' ?>">Whole Day</a>
            <a href="?date=<?= $selectedDate ?>&status=absent" class="filter-btn <?= $filterStatus === 'absent' ? 'active' : '' ?>">Absent (<?= $absent ?>)</a>
            <a href="?date=<?= $selectedDate ?>&status=ob" class="filter-btn <?= $filterStatus === 'ob' ? 'active' : '' ?>">OB (<?= $obCount ?>)</a>
        </div>

        <div class="table-wrapper admin-dash-table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Employee ID</th>
                        <th>Time In</th>
                        <th>Lunch Out</th>
                        <th>Lunch In</th>
                        <th>Time Out</th>
                        <th>Hours</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employees)): ?>
                        <tr><td colspan="8" class="text-center" style="padding:2rem;">No records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td data-label="Employee"><strong><?= htmlspecialchars($emp['full_name']) ?></strong></td>
                                <td data-label="Employee ID"><?= htmlspecialchars($emp['employee_id']) ?></td>
                                <td data-label="Time In"><?= formatTimeDisplay($emp['time_in']) ?></td>
                                <td data-label="Lunch Out"><?= formatTimeDisplay($emp['lunch_out']) ?></td>
                                <td data-label="Lunch In"><?= formatTimeDisplay($emp['lunch_in']) ?></td>
                                <td data-label="Time Out"><?= formatTimeDisplay($emp['time_out']) ?></td>
                                <td data-label="Hours"><?= $emp['total_hours'] ? $emp['total_hours'] . 'h' : '--' ?></td>
                                <td data-label="Status">
                                    <?php
                                        $empStatus = $emp['am_status'] ?? null;
                                        $hasOB = isset($obUsers[$emp['id']]);
                                
                                        // If employee has OB, show only OB
                                        if ($hasOB):
                                    ?>
                                        <span class="badge badge-ob">OB</span>
                                
                                    <?php elseif (in_array($empStatus, ['leave', 'am_leave', 'pm_leave'])): ?>
                                        <span class="badge badge-leave"><?= getStatusLabel($empStatus) ?></span>
                                
                                    <?php elseif ($emp['time_in']): ?>
                                        <span class="badge <?= getStatusBadgeClass($empStatus ?? 'on_time') ?>">
                                            <?= getStatusLabel($empStatus ?? 'on_time') ?>
                                        </span>
                                
                                    <?php else: ?>
                                        <span class="badge badge-absent">Absent</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
