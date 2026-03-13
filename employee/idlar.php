<?php
$pageTitle = 'Monthly IDLAR';
$currentPage = 'idlar';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
requireEmployee();

$userId = getCurrentUserId();

// Schedule check is handled by checkAccomplishmentWindow() in functions.php

$selMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$selYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$stats = getMonthlyStats($pdo, $userId, $selMonth, $selYear);
$logs = getAttendanceForMonth($pdo, $userId, $selMonth, $selYear);

$logAccomplishments = [];
foreach ($logs as $log) {
    $logAccomplishments[$log['id']] = getAccomplishmentsForLog($pdo, $log['id']);
}

$stmtMonths = $pdo->prepare("
    SELECT DISTINCT YEAR(date) as yr, MONTH(date) as mo, COUNT(*) as cnt
    FROM attendance_logs WHERE user_id = :uid
    GROUP BY yr, mo ORDER BY yr DESC, mo DESC
");
$stmtMonths->execute([':uid' => $userId]);
$availableMonths = $stmtMonths->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <div class="top-bar">
        <div class="page-title">
            <span class="page-icon"><?= icon('idlar', '24') ?></span>
            <h2>Monthly IDLAR</h2>
        </div>
        <a href="/employee/change_password.php" class="user-dropdown" style="text-decoration:none;color:inherit;">
            <?= renderUserAvatar(getProfilePictureUrl(getCurrentUserPicture()), strtoupper(substr(getCurrentUserName(), 0, 1))) ?>
            <div class="user-meta">
                <div class="user-name"><?= htmlspecialchars(getCurrentUserName()) ?></div>
                <div class="user-role"><?= $_roleDisplay ?></div>
            </div>
        </a>
    </div>

    <div style="display:flex;gap:0.75rem;align-items:flex-start;margin-bottom:1rem;">
        <span style="color:var(--info);flex-shrink:0;margin-top:2px;"><?= icon('info', '18') ?></span>
        <p style="font-size:0.85rem;color:var(--text-dark);line-height:1.6;">
            Add accomplishments for each working day, then generate your monthly IDLAR document.
            Type in the input field next to each date and press Enter or click + to submit. You can add multiple accomplishments per day.
        </p>
    </div>

    <form method="GET" class="form-row idlar-filter-row" style="margin-bottom:1.5rem;">
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

    <div class="idlar-summary">
        <div class="summary-item">
            <div class="summary-value"><?= $stats['total_days'] ?></div>
            <div class="summary-label">Working Days</div>
        </div>
        <div class="summary-item">
            <div class="summary-value"><?= $stats['on_time'] ?></div>
            <div class="summary-label">On Time</div>
        </div>
        <div class="summary-item">
            <div class="summary-value"><?= $stats['grace'] ?></div>
            <div class="summary-label">Grace Period</div>
        </div>
        <div class="summary-item">
            <div class="summary-value"><?= $stats['total_hours'] ?></div>
            <div class="summary-label">Total Hours</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <?= icon('calendar', '18') ?> <?= date('F', mktime(0, 0, 0, $selMonth, 1)) ?> <?= $selYear ?>
            </div>
            <span style="font-size:0.78rem;color:var(--text-light)"><?= $stats['total_days'] ?> working days</span>
        </div>

        <?php if (empty($logs)): ?>
            <div class="empty-state">
                <div class="empty-icon"><?= icon('file-text', '40') ?></div>
                <p>No attendance records for this month.</p>
            </div>
        <?php else: ?>
            <div class="table-wrapper idlar-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time In</th>
                            <th>Lunch Out</th>
                            <th>Lunch In</th>
                            <th>Time Out</th>
                            <th>Hours</th>
                            <th>Status</th>
                            <th>Accomplishments</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td data-label="Date">
                                    <strong><?= date('M j', strtotime($log['date'])) ?></strong><br>
                                    <small style="color:var(--text-light)"><?= date('l', strtotime($log['date'])) ?></small>
                                </td>
                                <td data-label="Time In"><?= formatTimeDisplay($log['time_in']) ?></td>
                                <td data-label="Lunch Out"><?= formatTimeDisplay($log['lunch_out']) ?></td>
                                <td data-label="Lunch In"><?= formatTimeDisplay($log['lunch_in']) ?></td>
                                <td data-label="Time Out"><?= formatTimeDisplay($log['time_out']) ?></td>
                                <td data-label="Hours"><?= $log['total_hours'] ? $log['total_hours'] . 'h' : '--' ?></td>
                                <td data-label="Status">
                                    <?php if ($log['am_status']): ?>
                                        <span class="badge <?= getStatusBadgeClass($log['am_status']) ?>"><?= getStatusLabel($log['am_status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Accomplishments" style="min-width:260px;">
                                    <div id="acc-list-<?= $log['id'] ?>">
                                        <?php if (!empty($logAccomplishments[$log['id']])): ?>
                                            <ul class="accomplishment-list">
                                                <?php foreach ($logAccomplishments[$log['id']] as $acc): ?>
                                                    <li id="acc-item-<?= $acc['id'] ?>">
                                                        <?= htmlspecialchars($acc['item_text']) ?>
                                                        <button type="button" class="attachment-delete" onclick="deleteAccomplishment(<?= $acc['id'] ?>)" title="Remove"><?= icon('x', '12') ?></button>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                    <?php
                                        $windowCheck = checkAccomplishmentWindow($log['date']);
                                    ?>
                                    <?php if ($windowCheck['allowed']): ?>
                                    <?php $isLeave = ($log['am_status'] === 'leave'); ?>
                                    <div style="margin-top:0.5rem;">
                                        <textarea id="acc-input-<?= $log['id'] ?>" placeholder="Type accomplishment here..." maxlength="300" rows="2"
                                               style="width:100%;box-sizing:border-box;padding:0.45rem 0.6rem;font-size:0.83rem;border:1px solid var(--border);border-radius:8px;resize:vertical;line-height:1.5;font-family:inherit;"
                                               onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();submitAccomplishment(<?= $log['id'] ?>);}"></textarea>
                                        <button type="button" class="btn btn-primary btn-sm" style="margin-top:0.35rem;width:100%;font-size:0.8rem;" onclick="submitAccomplishment(<?= $log['id'] ?>)">
                                            <?= icon('plus', '12') ?> Add
                                        </button>
                                        <button type="button"
                                            id="leave-btn-<?= $log['id'] ?>"
                                            class="btn <?= $isLeave ? 'btn-secondary' : 'btn-danger-outline' ?> btn-sm"
                                            style="margin-top:0.35rem;width:100%;font-size:0.8rem;"
                                            onclick="toggleLeave(<?= $log['id'] ?>, this)">
                                            <?= $isLeave ? icon('x-circle', '12') . ' Unmark Leave' : icon('calendar', '12') . ' Mark as Leave' ?>
                                        </button>
                                    </div>
                                    <?php else: ?>
                                    <small style="color:var(--text-muted,#888);font-size:0.78rem;"><?= htmlspecialchars($windowCheck['message']) ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>


    </div>


<?php
$inlineScript = <<<'JS'
async function submitAccomplishment(logId) {
    var input = document.getElementById('acc-input-' + logId);
    var text = input.value.trim();
    if (!text) {
        showToast('Please enter an accomplishment.', 'warning');
        return;
    }
    if (text.length > 300) {
        showToast('Accomplishment must be 300 characters or less.', 'error');
        return;
    }

    var result = await fetchAPI('/api/save_accomplishment.php', { log_id: logId, item_text: text });
    if (result.success) {
        showToast(result.message, 'success');
        input.value = '';

        var listDiv = document.getElementById('acc-list-' + logId);
        var ul = listDiv.querySelector('ul.accomplishment-list');
        if (!ul) {
            ul = document.createElement('ul');
            ul.className = 'accomplishment-list';
            listDiv.appendChild(ul);
        }
        var li = document.createElement('li');
        li.id = 'acc-item-' + result.id;
        li.innerHTML = escapeHtml(result.item_text) + ' <button type="button" class="attachment-delete" onclick="deleteAccomplishment(' + result.id + ')" title="Remove">&times;</button>';
        ul.appendChild(li);
    } else {
        showToast(result.message || 'Failed to save.', 'error');
    }
}

async function deleteAccomplishment(accId) {
    if (!confirm('Remove this accomplishment?')) return;
    var result = await fetchAPI('/api/delete_accomplishment.php', { accomplishment_id: accId });
    if (result.success) {
        var el = document.getElementById('acc-item-' + accId);
        if (el) el.remove();
        showToast('Accomplishment removed.', 'success');
    } else {
        showToast(result.message || 'Failed to delete.', 'error');
    }
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}

async function toggleLeave(logId, btn) {
    var result = await fetchAPI('/api/mark_leave.php', { log_id: logId });
    if (result.success) {
        showToast(result.message, 'success');
        setTimeout(() => location.reload(), 800);
    } else {
        showToast(result.message || 'Failed.', 'error');
    }
}
JS;

require_once __DIR__ . '/../includes/footer.php';
?>
