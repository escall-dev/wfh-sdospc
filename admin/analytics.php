<?php
$pageTitle = 'Analytics';
$currentPage = 'analytics';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
requireAdminOrHr();

$_isSuperAdmin = isSuperAdmin();
$_isHr = isHrTimekeeping();
$_canSeeAll = $_isSuperAdmin || $_isHr;
$_myDivision = getCurrentUserDivision();

// --- Period toggle: monthly (default) or weekly ---
$period = ($_GET['period'] ?? 'monthly') === 'weekly' ? 'weekly' : 'monthly';

// --- Month / Year (for monthly mode) ---
$selMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$selYear  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');

// --- Week (for weekly mode) – ISO week number ---
$selWeek = isset($_GET['week']) ? (int)$_GET['week'] : (int)date('W');
$selWeekYear = isset($_GET['wyear']) ? (int)$_GET['wyear'] : (int)date('o');

// Clamp week to valid range 1-53
$selWeek = max(1, min(53, $selWeek));

// Compute ISO week start (Monday) and end (Sunday)
$weekStart = new DateTime();
$weekStart->setISODate($selWeekYear, $selWeek, 1);
$weekEnd = clone $weekStart;
$weekEnd->modify('+6 days');
$weekStartStr = $weekStart->format('Y-m-d');
$weekEndStr   = $weekEnd->format('Y-m-d');

// --- Division filter ---
$selDivision = $_GET['division'] ?? 'all';
if (!$_canSeeAll) {
    $selDivision = $_myDivision ?: 'all';
}

// Build SQL fragments for division filtering
$_divFilter = '';
$_divJoinFilter = '';
$_divParams = [];
if ($selDivision !== 'all') {
    $_divFilter = " AND functional_division = :mydiv";
    $_divJoinFilter = " AND u.functional_division = :mydiv";
    $_divParams = [':mydiv' => $selDivision];
}

// Build SQL date-range fragment depending on period
if ($period === 'weekly') {
    $dateFilter     = " AND al.date BETWEEN :ds AND :de";
    $dateFilterOnly = " AND date BETWEEN :ds AND :de";
    $dateParams     = [':ds' => $weekStartStr, ':de' => $weekEndStr];
} else {
    $dateFilter     = " AND MONTH(al.date) = :m AND YEAR(al.date) = :y";
    $dateFilterOnly = " AND MONTH(date) = :m AND YEAR(date) = :y";
    $dateParams     = [':m' => $selMonth, ':y' => $selYear];
}

// ============================================================
// QUERIES
// ============================================================

// 1. Total active employees
$stmtTotal = $pdo->prepare("SELECT COUNT(*) as cnt FROM users WHERE role='employee' AND is_active=1{$_divFilter}");
$stmtTotal->execute($_divParams);
$totalEmployees = (int)$stmtTotal->fetch()['cnt'];

// 2. Active this period (distinct employees with at least 1 attendance log)
$stmtActive = $pdo->prepare("
    SELECT COUNT(DISTINCT al.user_id) as cnt
    FROM attendance_logs al
    JOIN users u ON u.id = al.user_id
    WHERE u.role='employee' AND u.is_active=1{$_divJoinFilter}{$dateFilter}
");
$stmtActive->execute(array_merge($_divParams, $dateParams));
$activeThisPeriod = (int)$stmtActive->fetch()['cnt'];

// 3. Attendance breakdown (aggregate)
$stmtAtt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN al.am_status = 'on_time' THEN 1 ELSE 0 END) as on_time,
        SUM(CASE WHEN al.am_status = 'grace' THEN 1 ELSE 0 END) as grace,
        SUM(CASE WHEN al.am_status = 'late' THEN 1 ELSE 0 END) as late_days,
        SUM(CASE WHEN al.am_status IN ('leave','am_leave','pm_leave') THEN 1 ELSE 0 END) as leave_days,
        COALESCE(AVG(al.total_hours), 0) as avg_hours,
        COUNT(al.id) as total_logs
    FROM attendance_logs al
    JOIN users u ON u.id = al.user_id
    WHERE u.role='employee' AND u.is_active=1{$_divJoinFilter}{$dateFilter}
");
$stmtAtt->execute(array_merge($_divParams, $dateParams));
$att = $stmtAtt->fetch();
$onTime    = (int)($att['on_time'] ?? 0);
$grace     = (int)($att['grace'] ?? 0);
$lateDays  = (int)($att['late_days'] ?? 0);
$leaveDays = (int)($att['leave_days'] ?? 0);
$avgHours  = round((float)($att['avg_hours'] ?? 0), 1);
$totalLogs = (int)($att['total_logs'] ?? 0);

$presentDays = $onTime + $grace + $lateDays; // days with clock-in (excl. leave)
$onTimeRate  = $presentDays > 0 ? round(($onTime / $presentDays) * 100, 1) : 0;

// 4. IDLAR submission count – employees with >=1 accomplishment in the period
$stmtIdlar = $pdo->prepare("
    SELECT COUNT(DISTINCT u.id) as submitted
    FROM users u
    JOIN attendance_logs al ON u.id = al.user_id{$dateFilter}
    JOIN accomplishments a ON al.id = a.log_id
    WHERE u.role='employee' AND u.is_active=1{$_divJoinFilter}
");
$stmtIdlar->execute(array_merge($_divParams, $dateParams));
$idlarSubmitted = (int)$stmtIdlar->fetch()['submitted'];
$idlarRate = $totalEmployees > 0 ? round(($idlarSubmitted / $totalEmployees) * 100, 1) : 0;

// 5. Per-division breakdown (superadmin only)
$divBreakdown = [];
if ($_canSeeAll) {
    $stmtDiv = $pdo->prepare("
        SELECT
            u.functional_division as division,
            COUNT(DISTINCT u.id) as total_employees,
            COUNT(DISTINCT CASE WHEN a.id IS NOT NULL THEN u.id END) as idlar_submitted,
            SUM(CASE WHEN al.am_status = 'on_time' THEN 1 ELSE 0 END) as on_time,
            SUM(CASE WHEN al.am_status = 'grace' THEN 1 ELSE 0 END) as grace,
            SUM(CASE WHEN al.am_status = 'late' THEN 1 ELSE 0 END) as late_days,
            SUM(CASE WHEN al.am_status IN ('leave','am_leave','pm_leave') THEN 1 ELSE 0 END) as leave_days,
            COALESCE(AVG(al.total_hours), 0) as avg_hours
        FROM users u
        LEFT JOIN attendance_logs al ON u.id = al.user_id{$dateFilter}
        LEFT JOIN accomplishments a ON al.id = a.log_id
        WHERE u.role='employee' AND u.is_active=1
        GROUP BY u.functional_division
        ORDER BY u.functional_division
    ");
    $stmtDiv->execute($dateParams);
    $divBreakdown = $stmtDiv->fetchAll();
}

// Helper to build URL preserving current filters
function analyticsUrl(array $overrides = []): string {
    global $period, $selMonth, $selYear, $selWeek, $selWeekYear, $selDivision;
    $params = array_merge([
        'period'   => $period,
        'month'    => $selMonth,
        'year'     => $selYear,
        'week'     => $selWeek,
        'wyear'    => $selWeekYear,
        'division' => $selDivision,
    ], $overrides);
    return '?' . http_build_query($params);
}

// Period label for headings
if ($period === 'weekly') {
    $periodLabel = 'Week ' . $selWeek . ', ' . $selWeekYear . ' (' . $weekStart->format('M j') . ' – ' . $weekEnd->format('M j') . ')';
} else {
    $periodLabel = date('F', mktime(0, 0, 0, $selMonth, 1)) . ' ' . $selYear;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <div class="top-bar mobile-hidden-topbar">
        <div class="page-title">
            <span class="page-icon"><?= icon('trending-up', '24') ?></span>
            <div>
                <h2>Analytics</h2>
                <p style="font-size:0.8rem;color:var(--text-light)">
                    <?= $periodLabel ?>
                    <?php if ($selDivision !== 'all'): ?> — <?= htmlspecialchars($selDivision) ?><?php endif; ?>
                </p>
            </div>
        </div>
        <a href="<?= in_array(getCurrentUserRole(), ['admin','superadmin','hr_timekeeping']) ? '/admin/profile.php' : '/employee/change_password.php' ?>" class="user-dropdown" style="text-decoration:none;color:inherit;">
            <div class="user-avatar">AD</div>
            <div class="user-meta">
                <div class="user-name"><?= htmlspecialchars(getCurrentUserName()) ?></div>
                <div class="user-role"><?= $_isSuperAdmin ? 'Super Admin' : ($_isHr ? 'HR Timekeeping' : 'Admin') ?></div>
            </div>
        </a>
    </div>

    <!-- Controls: Period toggle + Division filter + Date selectors -->
    <form method="GET" class="analytics-controls" style="margin-bottom:1.5rem;">
        <!-- Period toggle -->
        <div class="filter-bar" style="margin-bottom:0.75rem;">
            <a href="<?= analyticsUrl(['period' => 'monthly']) ?>" class="filter-btn <?= $period === 'monthly' ? 'active' : '' ?>">Monthly</a>
            <a href="<?= analyticsUrl(['period' => 'weekly']) ?>" class="filter-btn <?= $period === 'weekly' ? 'active' : '' ?>">Weekly</a>
        </div>

        <div class="form-row" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:0.75rem;">
            <?php if ($period === 'monthly'): ?>
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
            <?php else: ?>
                <div class="form-group">
                    <label>Week</label>
                    <select name="week" onchange="this.form.submit()">
                        <?php for ($w = 1; $w <= 53; $w++): ?>
                            <option value="<?= $w ?>" <?= $selWeek === $w ? 'selected' : '' ?>>Week <?= $w ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Year</label>
                    <select name="wyear" onchange="this.form.submit()">
                        <?php for ($y = (int)date('Y'); $y >= 2024; $y--): ?>
                            <option value="<?= $y ?>" <?= $selWeekYear === $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if ($_canSeeAll): ?>
                <div class="form-group">
                    <label>Division</label>
                    <select name="division" onchange="this.form.submit()">
                        <option value="all" <?= $selDivision === 'all' ? 'selected' : '' ?>>All Divisions</option>
                        <option value="OSDS" <?= $selDivision === 'OSDS' ? 'selected' : '' ?>>OSDS</option>
                        <option value="SGOD" <?= $selDivision === 'SGOD' ? 'selected' : '' ?>>SGOD</option>
                        <option value="CID" <?= $selDivision === 'CID' ? 'selected' : '' ?>>CID</option>
                    </select>
                </div>
            <?php endif; ?>

            <input type="hidden" name="period" value="<?= $period ?>">
            <?php if ($period === 'weekly'): ?>
                <input type="hidden" name="month" value="<?= $selMonth ?>">
                <input type="hidden" name="year" value="<?= $selYear ?>">
            <?php else: ?>
                <input type="hidden" name="week" value="<?= $selWeek ?>">
                <input type="hidden" name="wyear" value="<?= $selWeekYear ?>">
            <?php endif; ?>
            <?php if (!$_canSeeAll): ?>
                <input type="hidden" name="division" value="<?= htmlspecialchars($selDivision) ?>">
            <?php endif; ?>
        </div>
    </form>

    <!-- Attendance Stats Cards -->
    <div class="stats-grid analytics-stats" style="grid-template-columns:repeat(3,1fr);">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Total Employees</span>
                <span class="stat-icon"><?= icon('employees', '20') ?></span>
            </div>
            <div class="stat-value"><?= $totalEmployees ?></div>
            <div class="stat-subtitle"><?= $activeThisPeriod ?> active this <?= $period === 'weekly' ? 'week' : 'month' ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">On-Time Rate</span>
                <span class="stat-icon" style="color:var(--success)"><?= icon('check-circle', '20') ?></span>
            </div>
            <div class="stat-value" style="color:var(--success)"><?= $onTimeRate ?><span class="stat-unit">%</span></div>
            <div class="stat-subtitle"><?= $onTime ?> on-time of <?= $presentDays ?> present days</div>
            <div class="stat-bar"><div class="stat-bar-fill" style="width:<?= $onTimeRate ?>%;background:var(--success)"></div></div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Avg. Daily Hours</span>
                <span class="stat-icon"><?= icon('clock', '20') ?></span>
            </div>
            <div class="stat-value"><?= $avgHours ?><span class="stat-unit">hrs</span></div>
        </div>
    </div>

    <div class="stats-grid analytics-stats" style="grid-template-columns:repeat(3,1fr);">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Grace Period</span>
                <span class="stat-icon" style="color:var(--warning)"><?= icon('clock', '20') ?></span>
            </div>
            <div class="stat-value" style="color:var(--warning)"><?= $grace ?><span class="stat-unit">days</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Late</span>
                <span class="stat-icon" style="color:var(--danger)"><?= icon('alert-circle', '20') ?></span>
            </div>
            <div class="stat-value" style="color:var(--danger)"><?= $lateDays ?><span class="stat-unit">days</span></div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">On Leave</span>
                <span class="stat-icon" style="color:var(--info)"><?= icon('calendar', '20') ?></span>
            </div>
            <div class="stat-value" style="color:var(--info)"><?= $leaveDays ?><span class="stat-unit">days</span></div>
        </div>
    </div>

    <!-- IDLAR Submission Card -->
    <div class="card" style="margin-bottom:1.5rem;">
        <div class="card-header">
            <div class="card-title"><?= icon('idlar', '18') ?> IDLAR Submissions — <?= $periodLabel ?></div>
        </div>
        <div class="analytics-idlar-summary">
            <div class="analytics-idlar-big">
                <span class="analytics-idlar-num"><?= $idlarSubmitted ?></span>
                <span class="analytics-idlar-sep">/</span>
                <span class="analytics-idlar-den"><?= $totalEmployees ?></span>
                <span class="analytics-idlar-label">employees submitted</span>
            </div>
            <div class="analytics-idlar-rate">
                <span class="analytics-idlar-pct"><?= $idlarRate ?>%</span>
                <div class="stat-bar" style="margin-top:0.5rem;height:8px;">
                    <div class="stat-bar-fill" style="width:<?= $idlarRate ?>%;background:<?= $idlarRate >= 80 ? 'var(--success)' : ($idlarRate >= 50 ? 'var(--warning)' : 'var(--danger)') ?>;"></div>
                </div>
                <span class="analytics-idlar-rate-label">submission rate</span>
            </div>
        </div>
    </div>

    <?php if ($_canSeeAll && !empty($divBreakdown)): ?>
    <!-- Per-Division Breakdown Table -->
    <div class="card">
        <div class="card-header">
            <div class="card-title"><?= icon('filter', '18') ?> Division Breakdown</div>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Division</th>
                        <th>Employees</th>
                        <th>IDLAR Submitted</th>
                        <th>IDLAR Rate</th>
                        <th>Avg Hours</th>
                        <th>On Time</th>
                        <th>Grace</th>
                        <th>Late</th>
                        <th>Leave</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($divBreakdown as $div):
                        $dTotal    = (int)$div['total_employees'];
                        $dIdlar    = (int)$div['idlar_submitted'];
                        $dRate     = $dTotal > 0 ? round(($dIdlar / $dTotal) * 100, 1) : 0;
                        $dAvg      = round((float)$div['avg_hours'], 1);
                        $dOnTime   = (int)$div['on_time'];
                        $dGrace    = (int)$div['grace'];
                        $dLate     = (int)$div['late_days'];
                        $dLeave    = (int)$div['leave_days'];
                        $dPresent  = $dOnTime + $dGrace + $dLate;
                        $dOnRate   = $dPresent > 0 ? round(($dOnTime / $dPresent) * 100, 1) : 0;
                    ?>
                        <tr>
                            <td data-label="Division"><strong><?= htmlspecialchars($div['division'] ?? 'N/A') ?></strong></td>
                            <td data-label="Employees"><?= $dTotal ?></td>
                            <td data-label="IDLAR Submitted"><?= $dIdlar ?> / <?= $dTotal ?></td>
                            <td data-label="IDLAR Rate">
                                <div style="display:flex;align-items:center;gap:0.5rem;">
                                    <div class="stat-bar" style="flex:1;height:6px;margin:0;">
                                        <div class="stat-bar-fill" style="width:<?= $dRate ?>%;background:<?= $dRate >= 80 ? 'var(--success)' : ($dRate >= 50 ? 'var(--warning)' : 'var(--danger)') ?>;"></div>
                                    </div>
                                    <span style="font-size:0.8rem;font-weight:600;"><?= $dRate ?>%</span>
                                </div>
                            </td>
                            <td data-label="Avg Hours"><?= $dAvg ?>h</td>
                            <td data-label="On Time"><span class="badge badge-ontime"><?= $dOnTime ?></span></td>
                            <td data-label="Grace"><span class="badge badge-grace"><?= $dGrace ?></span></td>
                            <td data-label="Late"><span class="badge badge-late"><?= $dLate ?></span></td>
                            <td data-label="Leave"><span class="badge badge-leave"><?= $dLeave ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
