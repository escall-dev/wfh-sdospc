<?php
$pageTitle = 'Attendance History';
$currentPage = 'history';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
requireEmployee();

$userId = getCurrentUserId();

$filterMonth = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$filterYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$whereClause = "WHERE user_id = :uid";
$params = [':uid' => $userId];

if ($filterMonth > 0) {
    $whereClause .= " AND MONTH(date) = :month";
    $params[':month'] = $filterMonth;
}
$whereClause .= " AND YEAR(date) = :year";
$params[':year'] = $filterYear;

$countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM attendance_logs $whereClause");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetch()['total'];
$totalPages = max(1, ceil($totalRows / $perPage));

$stmt = $pdo->prepare("SELECT * FROM attendance_logs $whereClause ORDER BY date DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Fetch OB dates for the displayed logs to show badge
$obDates = [];
if (!empty($logs)) {
    $logDates = array_column($logs, 'date');
    $placeholders = implode(',', array_fill(0, count($logDates), '?'));
    $obStmt = $pdo->prepare("SELECT DISTINCT ob_date FROM official_business WHERE user_id = ? AND ob_date IN ($placeholders)");
    $obStmt->execute(array_merge([$userId], $logDates));
    $obDates = array_column($obStmt->fetchAll(), 'ob_date');
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <div class="top-bar">
        <div class="page-title">
            <span class="page-icon"><?= icon('history', '24') ?></span>
            <h2>Attendance History</h2>
        </div>
        <a href="/employee/change_password.php" class="user-dropdown" style="text-decoration:none;color:inherit;">
            <?= renderUserAvatar(getProfilePictureUrl(getCurrentUserPicture()), strtoupper(substr(getCurrentUserName(), 0, 1))) ?>
            <div class="user-meta">
                <div class="user-name"><?= htmlspecialchars(getCurrentUserName()) ?></div>
                <div class="user-role"><?= $_roleDisplay ?></div>
            </div>
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title"><?= icon('calendar', '18') ?> Full Attendance Log</div>
        </div>

        <form method="GET" class="form-row history-filter-row" style="margin-bottom:1rem;">
            <div class="form-group">
                <label>Month</label>
                <select name="month" onchange="this.form.submit()">
                    <option value="0">All Months</option>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $filterMonth === $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Year</label>
                <select name="year" onchange="this.form.submit()">
                    <?php for ($y = (int)date('Y'); $y >= 2024; $y--): ?>
                        <option value="<?= $y ?>" <?= $filterYear === $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </form>

        <div class="table-wrapper history-table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time In</th>
                        <th>Lunch Out</th>
                        <th>Lunch In</th>
                        <th>Time Out</th>
                        <th>Total Hours</th>
                        <th>AM Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="7" class="text-center" style="padding:2rem;">No records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td data-label="Date"><strong><?= date('M j, Y', strtotime($log['date'])) ?></strong><?php if (in_array($log['date'], $obDates)): ?> <span class="badge badge-ob">OB</span><?php endif; ?><br><small style="color:var(--text-light)"><?= date('l', strtotime($log['date'])) ?></small></td>
                                <td data-label="Time In"><?= formatTimeDisplay($log['time_in']) ?></td>
                                <td data-label="Lunch Out"><?= formatTimeDisplay($log['lunch_out']) ?></td>
                                <td data-label="Lunch In"><?= formatTimeDisplay($log['lunch_in']) ?></td>
                                <td data-label="Time Out"><?= formatTimeDisplay($log['time_out']) ?></td>
                                <td data-label="Total Hours"><?= $log['total_hours'] ? $log['total_hours'] . 'h' : '--' ?></td>
                                <td data-label="AM Status">
                                    <?php if ($log['am_status']): ?>
                                        <span class="badge <?= getStatusBadgeClass($log['am_status']) ?>"><?= getStatusLabel($log['am_status']) ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-absent">--</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?month=<?= $filterMonth ?>&year=<?= $filterYear ?>&page=<?= $page - 1 ?>">&laquo;</a>
                <?php else: ?>
                    <span class="disabled">&laquo;</span>
                <?php endif; ?>

                <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                    <?php if ($p === $page): ?>
                        <span class="active"><?= $p ?></span>
                    <?php else: ?>
                        <a href="?month=<?= $filterMonth ?>&year=<?= $filterYear ?>&page=<?= $p ?>"><?= $p ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?month=<?= $filterMonth ?>&year=<?= $filterYear ?>&page=<?= $page + 1 ?>">&raquo;</a>
                <?php else: ?>
                    <span class="disabled">&raquo;</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
