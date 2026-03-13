<?php
$pageTitle = 'Reports';
$currentPage = 'reports';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
requireAdminOrHr();

$selMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$selYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$_isSuperAdmin = isSuperAdmin();
$_isHr = isHrTimekeeping();
$_canSeeAll = $_isSuperAdmin || $_isHr;
$_myDivision = getCurrentUserDivision();
$_divFilter = '';
$_divJoinFilter = '';
$_divParams = [];
if (!$_canSeeAll && $_myDivision) {
    $_divFilter = " AND functional_division = :mydiv";
    $_divJoinFilter = " AND u.functional_division = :mydiv";
    $_divParams = [':mydiv' => $_myDivision];
}

$stmtTotal = $pdo->prepare("SELECT COUNT(DISTINCT id) as cnt FROM users WHERE role='employee' AND is_active=1{$_divFilter}");
$stmtTotal->execute($_divParams);
$totalEmployees = (int)$stmtTotal->fetch()['cnt'];

$stmtPresent = $pdo->prepare("
    SELECT COUNT(DISTINCT al.user_id) as cnt FROM attendance_logs al
    JOIN users u ON u.id = al.user_id
    WHERE MONTH(al.date) = :m AND YEAR(al.date) = :y AND u.role='employee'{$_divJoinFilter}
");
$stmtPresent->execute(array_merge([':m' => $selMonth, ':y' => $selYear], $_divParams));
$activeThisMonth = (int)$stmtPresent->fetch()['cnt'];

$stmtAvg = $pdo->prepare("
    SELECT COALESCE(AVG(al.total_hours), 0) as avg_hours FROM attendance_logs al
    JOIN users u ON u.id = al.user_id
    WHERE MONTH(al.date) = :m AND YEAR(al.date) = :y AND al.total_hours IS NOT NULL AND u.role='employee'{$_divJoinFilter}
");
$stmtAvg->execute(array_merge([':m' => $selMonth, ':y' => $selYear], $_divParams));
$avgHours = round((float)$stmtAvg->fetch()['avg_hours'], 1);

$stmtReport = $pdo->prepare("
    SELECT u.id as user_id, u.employee_id, u.full_name,
        SUM(CASE WHEN al.id IS NOT NULL AND al.am_status NOT IN ('leave', 'am_leave', 'pm_leave') THEN 1 ELSE 0 END) as days_present,
        COALESCE(SUM(al.total_hours), 0) as total_hours,
        SUM(CASE WHEN al.am_status = 'on_time' THEN 1 ELSE 0 END) as on_time,
        SUM(CASE WHEN al.am_status = 'grace' THEN 1 ELSE 0 END) as grace,
        SUM(CASE WHEN al.am_status = 'late' THEN 1 ELSE 0 END) as late,
        SUM(CASE WHEN al.am_status IN ('leave', 'am_leave', 'pm_leave') THEN 1 ELSE 0 END) as leave_days,
        (SELECT COUNT(DISTINCT ob.ob_date) FROM official_business ob WHERE ob.user_id = u.id AND MONTH(ob.ob_date) = :m2 AND YEAR(ob.ob_date) = :y2) as ob_days
    FROM users u
    LEFT JOIN attendance_logs al ON u.id = al.user_id AND MONTH(al.date) = :m AND YEAR(al.date) = :y
    WHERE u.role = 'employee' AND u.is_active = 1{$_divJoinFilter}
    GROUP BY u.id
    ORDER BY u.full_name
");
$stmtReport->execute(array_merge([':m' => $selMonth, ':y' => $selYear, ':m2' => $selMonth, ':y2' => $selYear], $_divParams));
$report = $stmtReport->fetchAll();

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="monthly_report_' . $selYear . '_' . str_pad($selMonth, 2, '0', STR_PAD_LEFT) . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Employee ID', 'Full Name', 'Days Present', 'On Leave', 'OB Days', 'Total Hours', 'On Time', 'Grace', 'Late']);
    foreach ($report as $row) {
        fputcsv($out, [$row['employee_id'], $row['full_name'], $row['days_present'], $row['leave_days'], $row['ob_days'], round($row['total_hours'], 1), $row['on_time'], $row['grace'], $row['late']]);
    }
    fclose($out);
    exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <div class="top-bar mobile-hidden-topbar">
        <div class="page-title">
            <span class="page-icon"><?= icon('reports', '24') ?></span>
            <h2>Monthly Reports</h2>
        </div>
        <a href="/admin/profile.php" class="user-dropdown" style="text-decoration:none;color:inherit;">
            <div class="user-avatar">AD</div>
            <div class="user-meta">
                <div class="user-name"><?= htmlspecialchars(getCurrentUserName()) ?></div>
                <div class="user-role"><?= $_isSuperAdmin ? 'Super Admin' : ($_isHr ? 'HR Timekeeping' : 'Admin') ?></div>
            </div>
        </a>
    </div>

    <form method="GET" class="form-row" style="margin-bottom:1.5rem;">
        <div class="form-group">
            <label>Month</label>
            <select name="month" onchange="this.form.submit()">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $selMonth === $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Year</label>
            <select name="year" onchange="this.form.submit()">
                <?php for ($y = (int)date('Y'); $y >= 2024; $y--): ?>
                    <option value="<?= $y ?>" <?= $selYear === $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
    </form>

    <div class="stats-grid admin-stats" style="grid-template-columns:repeat(3,1fr);">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Total Employees</span>
                <span class="stat-icon"><?= icon('employees', '20') ?></span>
            </div>
            <div class="stat-value"><?= $totalEmployees ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Active This Month</span>
                <span class="stat-icon" style="color:var(--success)"><?= icon('check-circle', '20') ?></span>
            </div>
            <div class="stat-value"><?= $activeThisMonth ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Avg. Daily Hours</span>
                <span class="stat-icon"><?= icon('clock', '20') ?></span>
            </div>
            <div class="stat-value"><?= $avgHours ?><span class="stat-unit">hrs</span></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title"><?= icon('idlar', '18') ?> <?= date('F', mktime(0, 0, 0, $selMonth, 1)) ?> <?= $selYear ?> Summary</div>
            <a href="?month=<?= $selMonth ?>&year=<?= $selYear ?>&export=csv" class="btn btn-secondary btn-sm"><?= icon('download', '14') ?> Export CSV</a>
        </div>
        <div class="table-wrapper admin-report-table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Employee ID</th>
                        <th>Days Present</th>
                        <th>On Leave</th>
                        <th>OB Days</th>
                        <th>Total Hours</th>
                        <th>On Time</th>
                        <th>Grace</th>
                        <th>Late</th>
                        <th>IDLAR</th>
                        <th>DTR</th>
                        <th>Export</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report as $row): ?>
                        <tr>
                            <td data-label="Employee"><strong><?= htmlspecialchars($row['full_name']) ?></strong></td>
                            <td data-label="Employee ID"><?= htmlspecialchars($row['employee_id']) ?></td>
                            <td data-label="Days Present"><?= $row['days_present'] ?></td>
                            <td data-label="On Leave"><span class="badge badge-leave"><?= $row['leave_days'] ?></span></td>
                            <td data-label="OB Days"><span class="badge badge-ob"><?= $row['ob_days'] ?></span></td>
                            <td data-label="Total Hours"><?= round($row['total_hours'], 1) ?>h</td>
                            <td data-label="On Time"><span class="badge badge-ontime"><?= $row['on_time'] ?></span></td>
                            <td data-label="Grace"><span class="badge badge-grace"><?= $row['grace'] ?></span></td>
                            <td data-label="Late"><span class="badge badge-late"><?= $row['late'] ?></span></td>
                            <td data-label="IDLAR">
                                <?php if ($row['days_present'] > 0): ?>
                                    <a href="/api/generate_idlar_docx.php?user_id=<?= $row['user_id'] ?>&month=<?= $selMonth ?>&year=<?= $selYear ?>" target="_blank" class="btn btn-primary btn-sm" style="white-space:nowrap;padding:0.4rem;" title="Preview IDLAR">
                                        <?= icon('file-text', '13') ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color:var(--text-light);font-size:0.8rem;">--</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="DTR">
                                <a href="/api/generate_dtr.php?user_id=<?= $row['user_id'] ?>&month=<?= $selMonth ?>&year=<?= $selYear ?>" target="_blank" class="btn btn-secondary btn-sm" style="white-space:nowrap;padding:0.4rem;" title="Generate DTR">
                                    <?= icon('clock', '13') ?>
                                </a>
                            </td>
                            <td data-label="Export">
                                <?php if ($row['days_present'] > 0): ?>
                                    <a href="/api/generate_combined.php?user_id=<?= $row['user_id'] ?>&month=<?= $selMonth ?>&year=<?= $selYear ?>" target="_blank" class="btn btn-primary btn-sm" style="white-space:nowrap;padding:0.4rem;" title="Export Combined">
                                        <?= icon('download', '13') ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color:var(--text-light);font-size:0.8rem;">--</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
