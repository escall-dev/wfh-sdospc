<?php
$pageTitle = 'Employee Dashboard';
$currentPage = 'dashboard';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
requireEmployee();

$userId = getCurrentUserId();
$fullName = getCurrentUserName();

$hoursToday = getHoursToday($pdo, $userId);
$daysThisMonth = getDaysThisMonth($pdo, $userId);
$totalHoursMonth = getTotalHoursMonth($pdo, $userId);
$onTimeRate = getOnTimeRate($pdo, $userId);

$month = (int)date('m');
$year = (int)date('Y');
$monthLogs = getAttendanceForMonth($pdo, $userId, $month, $year);
$monthlyStats = getMonthlyStats($pdo, $userId, $month, $year);

$hour = (int)date('H');
if ($hour < 12) $greeting = 'Good Morning';
elseif ($hour < 18) $greeting = 'Good Afternoon';
else $greeting = 'Good Evening';

$todayLog = getTodayLog($pdo, $userId);

$stmtRecent = $pdo->prepare("
    SELECT * FROM attendance_logs WHERE user_id = :uid ORDER BY date DESC LIMIT 5
");
$stmtRecent->execute([':uid' => $userId]);
$recentLogs = $stmtRecent->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <!-- Mobile-only greeting header -->
    <div class="mobile-greeting-header">
        <div>
            <div class="mobile-greeting"><?= htmlspecialchars($greeting) ?></div>
            <div class="mobile-greeting-name"><?= htmlspecialchars($fullName) ?></div>
        </div>
        <?= renderUserAvatar(getProfilePictureUrl(getCurrentUserPicture()), strtoupper(substr($fullName, 0, 1) . substr(strrchr($fullName, ' ') ?: $fullName, 1, 1))) ?>
    </div>
    <!-- Mobile-only clock widget -->
    <div class="mobile-clock-widget">
        <span class="mcw-icon"><?= icon('clock', '32') ?></span>
        <div class="mcw-info">
            <div class="mcw-time" id="mcw-time"></div>
            <div class="mcw-date" id="mcw-date"></div>
        </div>
    </div>
    <div class="top-bar mobile-hidden-topbar">
        <div class="page-title">
            <span class="page-icon"><?= icon('user', '24') ?></span>
            <div>
                <h2>Employee Dashboard</h2>
                <p style="font-size:0.8rem;color:var(--text-light);">Track your attendance, hours, and performance metrics</p>
            </div>
        </div>
        <a href="/employee/change_password.php" class="user-dropdown" style="text-decoration:none;color:inherit;">
            <?= renderUserAvatar(getProfilePictureUrl(getCurrentUserPicture()), strtoupper(substr($fullName, 0, 1) . substr(strrchr($fullName, ' ') ?: $fullName, 1, 1))) ?>
            <div class="user-meta">
                <div class="user-name"><?= htmlspecialchars($fullName) ?></div>
                <div class="user-role"><?= $_roleDisplay ?></div>
            </div>
        </a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Hours Today</span>
                <span class="stat-icon"><?= icon('clock', '20') ?></span>
            </div>
            <div class="stat-value"><?= $hoursToday ?><span class="stat-unit">hrs</span></div>
            <div class="stat-bar"><div class="stat-bar-fill" style="width: <?= min(100, ($hoursToday / 8) * 100) ?>%"></div></div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Days This Month</span>
                <span class="stat-icon"><?= icon('calendar', '20') ?></span>
            </div>
            <div class="stat-value"><?= $daysThisMonth ?><span class="stat-unit">days</span></div>
            <div class="stat-subtitle">Days attended this month</div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">Total Hours</span>
                <span class="stat-icon"><?= icon('stopwatch', '20') ?></span>
            </div>
            <div class="stat-value"><?= $totalHoursMonth ?><span class="stat-unit">hrs</span></div>
            <div class="stat-subtitle">Accumulated this month</div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <span class="stat-label">On-Time Rate</span>
                <span class="stat-icon"><?= icon('trending-up', '20') ?></span>
            </div>
            <div class="stat-value"><?= $onTimeRate ?><span class="stat-unit">%</span></div>
            <div class="stat-subtitle">Keep it up!</div>
            <div class="stat-bar"><div class="stat-bar-fill" style="width: <?= $onTimeRate ?>%"></div></div>
        </div>
    </div>

    <!-- Monthly Summary Card -->
    <div class="card monthly-summary-card">
        <div class="card-title" style="margin-bottom:1rem;font-size:1rem;font-weight:700;">Monthly Summary</div>
        <div class="monthly-summary-grid">
            <div class="ms-item">
                <span class="ms-icon ms-ontime"><?= icon('check-circle', '28') ?></span>
                <div class="ms-value"><?= $monthlyStats['on_time'] ?></div>
                <div class="ms-label">On Time</div>
            </div>
            <div class="ms-item">
                <span class="ms-icon ms-late"><?= icon('clock', '28') ?></span>
                <div class="ms-value"><?= $monthlyStats['late'] ?></div>
                <div class="ms-label">Late</div>
            </div>
            <div class="ms-item">
                <span class="ms-icon ms-total"><?= icon('calendar', '28') ?></span>
                <div class="ms-value"><?= $monthlyStats['total_days'] ?></div>
                <div class="ms-label">Total Days</div>
            </div>
        </div>
    </div>

    <div class="content-grid">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <span class="card-icon"><?= icon('calendar', '18') ?></span>
                    This Month's Attendance
                </div>
                <span style="font-size:0.75rem;color:var(--text-light)">Complete attendance record</span>
            </div>
            <?php if (empty($monthLogs)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><?= icon('file-text', '40') ?></div>
                    <p>No attendance records this month.</p>
                </div>
            <?php else: ?>
                <?php foreach (array_reverse($monthLogs) as $log): ?>
                    <div class="attendance-item">
                        <div class="att-icon present"><?= icon('clock', '18') ?></div>
                        <div class="att-date">
                            <div class="att-date-main"><?= date('l, M j', strtotime($log['date'])) ?></div>
                            <div class="att-date-sub"><?= formatTimeDisplay($log['time_in']) ?> - <?= formatTimeDisplay($log['time_out']) ?></div>
                        </div>
                        <span class="badge badge-present">Present</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <span class="card-icon"><?= icon('clock', '18') ?></span>
                    Recent Attendance
                </div>
                <span style="font-size:0.75rem;color:var(--text-light)">Latest work logs</span>
            </div>
            <?php if (!empty($recentLogs)): ?>
                <?php $latest = $recentLogs[0]; ?>
                <div class="recent-log">
                    <div class="log-date">
                        <span><?= $latest['date'] ?></span>
                        <?php if ($latest['total_hours']): ?>
                            <span class="badge badge-<?= $latest['total_hours'] > 0 ? 'ontime' : 'absent' ?>"><?= $latest['total_hours'] ?>h</span>
                        <?php endif; ?>
                    </div>
                    <div class="log-times">
                        <div class="log-time-item">
                            <div class="log-time-label">AM In</div>
                            <div class="log-time-value"><?= formatTimeDisplay($latest['time_in']) ?></div>
                        </div>
                        <div class="log-time-item">
                            <div class="log-time-label">PM Out</div>
                            <div class="log-time-value"><?= formatTimeDisplay($latest['time_out']) ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <a href="/employee/history.php" class="btn btn-primary" style="margin-top:1rem;">
                View Full History <?= icon('arrow-right', '16') ?>
            </a>
        </div>
    </div>

<?php
$inlineScript = <<<'JS'
(function () {
    function updateMobClock() {
        var now = new Date();
        var t = document.getElementById('mcw-time');
        var d = document.getElementById('mcw-date');
        if (!t) return;
        t.textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
        d.textContent = now.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
    }
    updateMobClock();
    setInterval(updateMobClock, 1000);
})();
JS;
require_once __DIR__ . '/../includes/footer.php'; ?>
