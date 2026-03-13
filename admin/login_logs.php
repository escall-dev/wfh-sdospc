<?php
date_default_timezone_set("Asia/Manila");
$pageTitle = 'Login & Clock Logs';
$currentPage = 'login_logs';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
requireSuperAdmin();


if (isset($_GET['export']) && $_GET['export'] === 'csv') {

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="login_logs_' . date('Y-m-d') . '.csv"');

    $out = fopen('php://output', 'w');

    // CSV Headers
    fputcsv($out, [
        'ID',
        'User',
        'Action',
        'IP Address',
        'Device Fingerprint',
        'User Agent',
        'Date & Time'
    ]);

    // Query login_logs
    $stmt = $pdo->prepare("
        SELECT 
            login_logs.id,
            users.full_name,
            login_logs.action,
            login_logs.ip_address,
            login_logs.device_fingerprint,
            login_logs.user_agent,
            login_logs.created_at
        FROM login_logs
        LEFT JOIN users ON login_logs.user_id = users.id
        ORDER BY login_logs.created_at DESC
    ");
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, [
            $row['id'],
            $row['full_name'],
            $row['action'],
            $row['ip_address'],
            $row['device_fingerprint'],
            $row['user_agent'],
            $row['created_at']
        ]);
    }

    fclose($out);
    exit;
}

// ── Filters ──────────────────────────────────────────────
$filterDateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
$filterDateTo   = $_GET['date_to']   ?? date('Y-m-d');
$filterUser     = (int)($_GET['user_id'] ?? 0);
$filterAction   = $_GET['action']    ?? 'all';
$filterFlagged  = isset($_GET['flagged']) && $_GET['flagged'] === '1';
$page           = max(1, (int)($_GET['p'] ?? 1));
$perPage        = 50;
$offset         = ($page - 1) * $perPage;

// Validate dates
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDateFrom)) $filterDateFrom = date('Y-m-d', strtotime('-7 days'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDateTo))   $filterDateTo   = date('Y-m-d');

$validActions = ['login','time_in','lunch_out','lunch_in','time_out','emergency_time_out'];
if (!in_array($filterAction, $validActions)) $filterAction = 'all';

// ── Build WHERE clause ───────────────────────────────────
$where  = " WHERE ll.created_at BETWEEN :dfrom AND DATE_ADD(:dto, INTERVAL 1 DAY)";
$params = [':dfrom' => $filterDateFrom . ' 00:00:00', ':dto' => $filterDateTo . ' 23:59:59'];

if ($filterUser > 0) {
    $where .= " AND ll.user_id = :uid";
    $params[':uid'] = $filterUser;
}
if ($filterAction !== 'all') {
    $where .= " AND ll.action = :act";
    $params[':act'] = $filterAction;
}

// ── Detect flagged device fingerprints ───────────────────
// A fingerprint is flagged if it appears for MORE THAN ONE distinct user
// within the filtered date range (non-empty fingerprints only).
$flaggedFpStmt = $pdo->prepare("
    SELECT device_fingerprint, GROUP_CONCAT(DISTINCT u.full_name ORDER BY u.full_name SEPARATOR ', ') AS shared_names
    FROM login_logs ll
    JOIN users u ON u.id = ll.user_id
    WHERE ll.created_at BETWEEN :dfrom AND DATE_ADD(:dto, INTERVAL 1 DAY)
      AND ll.device_fingerprint != ''
    GROUP BY ll.device_fingerprint
    HAVING COUNT(DISTINCT ll.user_id) > 1
");
$flaggedFpStmt->execute([':dfrom' => $filterDateFrom . ' 00:00:00', ':dto' => $filterDateTo . ' 23:59:59']);
$flaggedFingerprints = [];
foreach ($flaggedFpStmt->fetchAll() as $row) {
    $flaggedFingerprints[$row['device_fingerprint']] = $row['shared_names'];
}

// Apply flagged filter to WHERE clause
if ($filterFlagged && !empty($flaggedFingerprints)) {
    $fpList = implode(',', array_map(fn($fp) => $pdo->quote($fp), array_keys($flaggedFingerprints)));
    $where .= " AND ll.device_fingerprint IN ({$fpList})";
} elseif ($filterFlagged) {
    // No flagged entries exist — force zero results
    $where .= " AND 1=0";
}

// ── Total count ──────────────────────────────────────────
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM login_logs ll{$where}");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) $page = $totalPages;

// ── Fetch logs ───────────────────────────────────────────
$logsStmt = $pdo->prepare("
    SELECT
        ll.id,
        ll.user_id,
        u.full_name,
        u.employee_id,
        u.functional_division,
        u.position,
        ll.action,
        ll.ip_address,
        ll.device_fingerprint,
        ll.user_agent,
        ll.created_at
    FROM login_logs ll
    JOIN users u ON u.id = ll.user_id
    {$where}
    ORDER BY ll.created_at DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$logsStmt->execute($params);
$logs = $logsStmt->fetchAll();

// ── Summary stats ─────────────────────────────────────────
$totalFlagged  = 0;
foreach ($logs as $log) {
    if (!empty($log['device_fingerprint']) && isset($flaggedFingerprints[$log['device_fingerprint']])) {
        $totalFlagged++;
    }
}
$uniqueFpsStmt = $pdo->prepare("
    SELECT COUNT(DISTINCT device_fingerprint) FROM login_logs ll {$where} AND ll.device_fingerprint != ''
");
$uniqueFpsStmt->execute($params);
$uniqueDevices = (int)$uniqueFpsStmt->fetchColumn();

// ── All employees for the filter dropdown ────────────────
$empStmt = $pdo->prepare("SELECT id, full_name, employee_id FROM users WHERE is_active=1 ORDER BY full_name");
$empStmt->execute();
$allEmployees = $empStmt->fetchAll();

// ── Label helpers ─────────────────────────────────────────
$actionLabels = [
    'login'              => 'Login',
    'time_in'            => 'AM In',
    'lunch_out'          => 'AM Out',
    'lunch_in'           => 'PM In',
    'time_out'           => 'PM Out',
    'emergency_time_out' => 'Emergency Out',
];
$actionBadges = [
    'login'              => 'badge-logged-in',
    'time_in'            => 'badge-present',
    'lunch_out'          => 'badge-grace',
    'lunch_in'           => 'badge-ontime',
    'time_out'           => 'badge-done',
    'emergency_time_out' => 'badge-late',
];

function buildUrl(array $overrides = []): string {
    global $filterDateFrom, $filterDateTo, $filterUser, $filterAction, $filterFlagged, $page;
    $params = array_merge([
        'date_from' => $filterDateFrom,
        'date_to'   => $filterDateTo,
        'user_id'   => $filterUser ?: '',
        'action'    => $filterAction,
        'flagged'   => $filterFlagged ? '1' : '',
        'p'         => $page,
    ], $overrides);
    $params = array_filter($params, fn($v) => $v !== '' && $v !== null);
    return '?' . http_build_query($params);
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

?>

<main class="main-content">

    <!-- Top bar (desktop) -->
    <div class="top-bar mobile-hidden-topbar">
        <div class="page-title">
            <span class="page-icon"><?= icon('mac-log', '24') ?></span>
            <div>
                <h2>Login &amp; Clock Logs</h2>
                <p style="font-size:0.8rem;color:var(--text-light)">Device fingerprint monitoring for buddy-punch detection</p>
            </div>
        </div>
        <a href="/admin/profile.php" class="user-dropdown" style="text-decoration:none;color:inherit;">
            <div class="user-avatar"><?= strtoupper(substr(getCurrentUserName(), 0, 2)) ?></div>
            <div class="user-meta">
                <div class="user-name"><?= htmlspecialchars(getCurrentUserName()) ?></div>
                <div class="user-role">Super Admin</div>
            </div>
        </a>
    </div>

    <!-- Mobile page header -->
    <div class="mobile-page-header mobile-only" style="padding:1rem 1rem 0;">
        <h2 style="font-size:1.1rem;font-weight:700;color:var(--text-dark);display:flex;align-items:center;gap:0.5rem;">
            <?= icon('mac-log', '20') ?> Login &amp; Clock Logs
        </h2>
        <p style="font-size:0.78rem;color:var(--text-light);margin-top:0.2rem;">Device fingerprint / buddy-punch monitoring</p>
    </div>

    <div class="content-area">

        <!-- Alert banner if flagged entries found -->
        <?php if (!empty($flaggedFingerprints)): ?>
        <div class="alert-banner alert-banner-warning" style="display:flex;align-items:flex-start;gap:0.75rem;background:var(--warning-bg);border:1px solid #f59e0b;border-radius:var(--radius);padding:1rem 1.25rem;margin-bottom:1rem;">
            <?= icon('alert-triangle', '20') ?>
            <div>
                <strong style="color:#92400e;">Suspected Buddy-Punching Detected</strong>
                <p style="font-size:0.85rem;color:#92400e;margin-top:0.15rem;">
                    <?= count($flaggedFingerprints) ?> device fingerprint(s) were used to log in or clock actions for <strong>multiple employees</strong> in the selected date range.
                    Flagged entries are highlighted below.
                </p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stats row -->
        <div class="stats-row" style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.25rem;">
            <div class="stat-card" style="background:var(--white);border-radius:var(--radius);padding:1rem 1.25rem;box-shadow:var(--shadow);">
                <div class="stat-label" style="font-size:0.75rem;color:var(--text-light);text-transform:uppercase;letter-spacing:.5px;margin-bottom:0.35rem;">Total Logs</div>
                <div class="stat-value" style="font-size:1.6rem;font-weight:700;color:var(--primary);"><?= number_format($totalRows) ?></div>
            </div>
            <div class="stat-card" style="background:var(--white);border-radius:var(--radius);padding:1rem 1.25rem;box-shadow:var(--shadow);">
                <div class="stat-label" style="font-size:0.75rem;color:var(--text-light);text-transform:uppercase;letter-spacing:.5px;margin-bottom:0.35rem;">Flagged</div>
                <div class="stat-value" style="font-size:1.6rem;font-weight:700;color:<?= !empty($flaggedFingerprints) ? 'var(--danger)' : 'var(--success)' ?>;"><?= count($flaggedFingerprints) ?></div>
            </div>
            <div class="stat-card" style="background:var(--white);border-radius:var(--radius);padding:1rem 1.25rem;box-shadow:var(--shadow);">
                <div class="stat-label" style="font-size:0.75rem;color:var(--text-light);text-transform:uppercase;letter-spacing:.5px;margin-bottom:0.35rem;">Unique Devices</div>
                <div class="stat-value" style="font-size:1.6rem;font-weight:700;color:var(--text-dark);"><?= $uniqueDevices ?></div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card" style="margin-bottom:1.25rem;padding:0;">
            <div class="card-header" style="padding:0.85rem 1.25rem;border-bottom:1px solid var(--border);">
                <div class="card-title">
                    <span class="card-icon"><?= icon('filter', '18') ?></span> Filters
                </div>
            </div>
            <form method="GET" action="" style="padding:1rem 1.25rem;">
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:0.75rem;margin-bottom:0.75rem;">

                    <div class="form-group" style="margin:0;">
                        <label style="font-size:0.78rem;color:var(--text-medium);display:block;margin-bottom:0.3rem;">From Date</label>
                        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($filterDateFrom) ?>"
                            style="width:100%;padding:0.45rem 0.75rem;border:1px solid var(--border);border-radius:8px;font-size:0.85rem;">
                    </div>

                    <div class="form-group" style="margin:0;">
                        <label style="font-size:0.78rem;color:var(--text-medium);display:block;margin-bottom:0.3rem;">To Date</label>
                        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($filterDateTo) ?>"
                            style="width:100%;padding:0.45rem 0.75rem;border:1px solid var(--border);border-radius:8px;font-size:0.85rem;">
                    </div>

                    <div class="form-group" style="margin:0;">
                        <label style="font-size:0.78rem;color:var(--text-medium);display:block;margin-bottom:0.3rem;">Employee</label>
                        <select name="user_id" style="width:100%;padding:0.45rem 0.75rem;border:1px solid var(--border);border-radius:8px;font-size:0.85rem;background:var(--white);">
                            <option value="">All Employees</option>
                            <?php foreach ($allEmployees as $emp): ?>
                                <option value="<?= $emp['id'] ?>" <?= $filterUser === (int)$emp['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($emp['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin:0;">
                        <label style="font-size:0.78rem;color:var(--text-medium);display:block;margin-bottom:0.3rem;">Action</label>
                        <select name="action" style="width:100%;padding:0.45rem 0.75rem;border:1px solid var(--border);border-radius:8px;font-size:0.85rem;background:var(--white);">
                            <option value="all" <?= $filterAction === 'all' ? 'selected' : '' ?>>All Actions</option>
                            <?php foreach ($actionLabels as $key => $label): ?>
                                <option value="<?= $key ?>" <?= $filterAction === $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin:0;display:flex;align-items:flex-end;">
                        <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.85rem;cursor:pointer;padding-bottom:0.1rem;">
                            <input type="checkbox" name="flagged" value="1" <?= $filterFlagged ? 'checked' : '' ?> style="width:16px;height:16px;accent-color:var(--danger);">
                            <span style="color:var(--danger);font-weight:600;">Flagged only</span>
                        </label>
                    </div>

                </div>
                <div style="display:flex;gap:0.6rem;flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary" style="font-size:0.85rem;padding:0.45rem 1.1rem;">
                        <?= icon('search', '15') ?> Apply Filters
                    </button>
                    <a href="?" class="btn btn-secondary" style="font-size:0.85rem;padding:0.45rem 1.1rem;">Reset</a>
                </div>
            </form>
        </div>

        <!-- Results -->
        <div class="card" style="padding:0;">
            <div class="card-header" style="padding:0.85rem 1.25rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;">
                <div class="card-title">
                    <span class="card-icon"><?= icon('history', '18') ?></span>
                    Activity Log
                    <span style="font-size:0.8rem;font-weight:400;color:var(--text-light);margin-left:0.5rem;">(<?= number_format($totalRows) ?> records)</span>
                </div>
                <?php if (!empty($flaggedFingerprints)): ?>
                    <span class="badge badge-mac-flagged" style="font-size:0.75rem;">
                        <?= icon('alert-triangle', '13') ?> <?= count($flaggedFingerprints) ?> shared device(s)
                    </span>
                <a href="?date=<?= $selectedDate ?>&export=csv" class="btn btn-secondary btn-sm"><?= icon('download', '14') ?> Export CSV</a>

                <?php endif; ?>
            </div>

            <?php if (empty($logs)): ?>
                <div style="text-align:center;padding:3rem 1rem;color:var(--text-light);">
                    <?= icon('search', '36') ?>
                    <p style="margin-top:0.75rem;font-size:0.9rem;">No logs found for the selected filters.</p>
                </div>
            <?php else: ?>

                <!-- Desktop Table -->
                <div class="table-wrapper mobile-hidden-table">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Employee</th>
                                <th>Division / Position</th>
                                <th>Action</th>
                                <th>MAC / Device ID</th>
                                <th>IP Address</th>
                                <th>Date &amp; Time</th>
                                <th>Flag Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $i => $log):
                                $fp = $log['device_fingerprint'];
                                $isFlagged = !empty($fp) && isset($flaggedFingerprints[$fp]);
                                $sharedWith = $isFlagged ? $flaggedFingerprints[$fp] : '';
                                // Remove current user's name from the shared list for the flag message
                                $otherNames = array_filter(
                                    array_map('trim', explode(',', $sharedWith)),
                                    fn($n) => $n !== $log['full_name']
                                );
                                $flagMsg = !empty($otherNames) ? 'MAC address same to: ' . implode(', ', $otherNames) : '';
                                $macDisplay = $fp ? (substr($fp, 0, 8) . '-' . substr($fp, 8, 8) . '-' . substr($fp, 16, 8) . '-' . substr($fp, 24)) : '—';
                            ?>
                            <tr class="<?= $isFlagged ? 'log-row-flagged' : '' ?>">
                                <td style="color:var(--text-light);font-size:0.8rem;"><?= $offset + $i + 1 ?></td>
                                <td>
                                    <div style="font-weight:600;font-size:0.875rem;"><?= htmlspecialchars($log['full_name']) ?></div>
                                    <div style="font-size:0.75rem;color:var(--text-light);"><?= htmlspecialchars($log['employee_id']) ?></div>
                                </td>
                                <td>
                                    <?php if ($log['functional_division']): ?>
                                        <span class="badge badge-ob" style="font-size:0.7rem;margin-bottom:0.25rem;display:inline-block;"><?= htmlspecialchars($log['functional_division']) ?></span><br>
                                    <?php endif; ?>
                                    <span style="font-size:0.78rem;color:var(--text-medium);"><?= htmlspecialchars($log['position'] ?: '—') ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $actionBadges[$log['action']] ?? 'badge-pending' ?>" style="font-size:0.75rem;">
                                        <?= $actionLabels[$log['action']] ?? $log['action'] ?>
                                    </span>
                                </td>
                                <td>
                                    <code class="mac-code <?= $isFlagged ? 'mac-code-flagged' : '' ?>" title="<?= htmlspecialchars($log['user_agent'] ?? '') ?>">
                                        <?= htmlspecialchars($macDisplay) ?>
                                    </code>
                                </td>
                                <td style="font-size:0.8rem;color:var(--text-medium);"><?= htmlspecialchars($log['ip_address'] ?: '—') ?></td>
                                <td style="font-size:0.82rem;white-space:nowrap;">
                                    <?= date('M j, Y', strtotime($log['created_at'])) ?><br>
                                    <span style="color:var(--text-light);"><?= date('h:i:s A', strtotime($log['created_at'])) ?></span>
                                </td>
                                <td>
                                    <?php if ($isFlagged): ?>
                                        <span class="badge badge-mac-flagged" style="font-size:0.73rem;white-space:normal;line-height:1.4;max-width:200px;display:inline-block;">
                                            <?= icon('alert-triangle', '12') ?> <?= htmlspecialchars($flagMsg) ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color:var(--text-light);font-size:0.8rem;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Mobile Cards -->
                <div class="log-cards-mobile mobile-only-block" style="padding:0.75rem;">
                    <?php foreach ($logs as $i => $log):
                        $fp = $log['device_fingerprint'];
                        $isFlagged = !empty($fp) && isset($flaggedFingerprints[$fp]);
                        $sharedWith = $isFlagged ? $flaggedFingerprints[$fp] : '';
                        $otherNames = array_filter(
                            array_map('trim', explode(',', $sharedWith)),
                            fn($n) => $n !== $log['full_name']
                        );
                        $flagMsg = !empty($otherNames) ? 'MAC address same to: ' . implode(', ', $otherNames) : '';
                        $macDisplay = $fp ? (substr($fp, 0, 8) . '-' . substr($fp, 8, 8) . '-' . substr($fp, 16, 8) . '-' . substr($fp, 24)) : '—';
                    ?>
                    <div class="log-card <?= $isFlagged ? 'log-card-flagged' : '' ?>">
                        <div class="log-card-header">
                            <div>
                                <div class="log-card-name"><?= htmlspecialchars($log['full_name']) ?></div>
                                <div class="log-card-empid"><?= htmlspecialchars($log['employee_id']) ?>
                                    <?php if ($log['functional_division']): ?> &middot; <?= htmlspecialchars($log['functional_division']) ?><?php endif; ?>
                                </div>
                                <?php if ($log['position']): ?>
                                <div style="font-size:0.7rem;color:var(--text-light);margin-top:0.1rem;"><?= htmlspecialchars($log['position']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div style="text-align:right;">
                                <span class="badge <?= $actionBadges[$log['action']] ?? 'badge-pending' ?>" style="font-size:0.72rem;">
                                    <?= $actionLabels[$log['action']] ?? $log['action'] ?>
                                </span>
                                <div style="font-size:0.72rem;color:var(--text-light);margin-top:0.25rem;">
                                    <?= date('M j, Y h:i A', strtotime($log['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                        <div class="log-card-detail">
                            <span class="log-card-label">Device ID</span>
                            <code class="mac-code <?= $isFlagged ? 'mac-code-flagged' : '' ?>" style="font-size:0.72rem;word-break:break-all;">
                                <?= htmlspecialchars($macDisplay) ?>
                            </code>
                        </div>
                        <div class="log-card-detail">
                            <span class="log-card-label">IP Address</span>
                            <span style="font-size:0.78rem;"><?= htmlspecialchars($log['ip_address'] ?: '—') ?></span>
                        </div>
                        <?php if ($isFlagged): ?>
                        <div class="log-card-flag">
                            <?= icon('alert-triangle', '13') ?>
                            <?= htmlspecialchars($flagMsg) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination-bar" style="padding:0.85rem 1.25rem;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;">
                <span style="font-size:0.8rem;color:var(--text-light);">
                    Page <?= $page ?> of <?= $totalPages ?> &nbsp;&middot;&nbsp; <?= number_format($totalRows) ?> records
                </span>
                <div style="display:flex;gap:0.4rem;flex-wrap:wrap;">
                    <?php if ($page > 1): ?>
                        <a href="<?= buildUrl(['p' => $page - 1]) ?>" class="btn btn-secondary" style="font-size:0.8rem;padding:0.35rem 0.8rem;">&laquo; Prev</a>
                    <?php endif; ?>
                    <?php
                        $start = max(1, $page - 2);
                        $end   = min($totalPages, $page + 2);
                        for ($pg = $start; $pg <= $end; $pg++):
                    ?>
                        <a href="<?= buildUrl(['p' => $pg]) ?>"
                           class="btn <?= $pg === $page ? 'btn-primary' : 'btn-secondary' ?>"
                           style="font-size:0.8rem;padding:0.35rem 0.8rem;min-width:36px;text-align:center;"><?= $pg ?></a>
                    <?php endfor; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= buildUrl(['p' => $page + 1]) ?>" class="btn btn-secondary" style="font-size:0.8rem;padding:0.35rem 0.8rem;">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /.content-area -->

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
