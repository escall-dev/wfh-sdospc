<?php
$pageTitle = 'Clock In/Out';
$currentPage = 'clock';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
requireEmployee();

$userId = getCurrentUserId();
$todayLog = getTodayLog($pdo, $userId);
$currentStep = getCurrentStep($todayLog);
$nextAction = getNextAction($currentStep);
$nextLabel = getNextActionLabel($currentStep);
$progress = min(100, ($currentStep - 1) * 25);
if ($currentStep === 5) $progress = 100;

$isFriday = true; // Friday-only check disabled for development
// $isFriday = ((int)date('N') === 5);

$logStatus = $todayLog['am_status'] ?? '';
$isAmLeave = $logStatus === 'am_leave';
$isPmLeave = $logStatus === 'pm_leave';
$isWholeLeave = $logStatus === 'leave';

// Absent AM: time_in window has closed and the employee never clocked in (not on AM leave)
$isAbsentAM = !$isAmLeave && !$isWholeLeave && !($todayLog && $todayLog['time_in']) && isTimeInWindowPassed();
if ($isAbsentAM && $currentStep === 1) {
    $currentStep = 3;
    $nextAction = getNextAction($currentStep);
    $nextLabel = getNextActionLabel($currentStep);
    $progress = 50;
}

// PM In skip: PM In window has passed without PM In being logged → advance to PM Out
$isPmInSkipped = false;
if (!$isPmLeave && !$isWholeLeave && !($todayLog && $todayLog['lunch_in']) && $currentStep === 3 && isPmInWindowPassed()) {
    $isPmInSkipped = true;
    $currentStep = 4;
    $nextAction = getNextAction($currentStep);
    $nextLabel = getNextActionLabel($currentStep);
    $progress = 75;
}

// === Timeline status variables (used in Today's Summary card) ===
$nowHi = date('H:i');
$nowTs = strtotime($nowHi);

// AM In
if ($todayLog && $todayLog['time_in']) {
    $amInSt = 'completed';
} elseif ($isAmLeave || $isWholeLeave) {
    $amInSt = 'leave';
} elseif ($isAbsentAM) {
    $amInSt = 'skipped';
} elseif ($nowTs >= strtotime('07:45') && $nowTs <= strtotime('08:15')) {
    $amInSt = 'active';
} elseif ($nowTs > strtotime('08:15')) {
    $amInSt = 'locked';
} else {
    $amInSt = 'pending';
}

// AM Out
if ($todayLog && $todayLog['lunch_out']) { 
    $amOutSt = 'completed'; } 
elseif ($isAmLeave || $isWholeLeave || $isAbsentAM) { 
    $amOutSt = 'skipped'; } 
elseif ($todayLog && $todayLog['time_in']) { 
    if ($nowTs >= strtotime('12:00') && $nowTs < strtotime('13:00')) { 
        $amOutSt = 'active'; // Allowed to log AM Out 
    } 
    elseif ($nowTs >= strtotime('13:00')) { $amOutSt = 'locked'; // Missed window 
} 
    
else { 
    $amOutSt = 'pending'; // Too early 
} 
    
} 
else { 
    $amOutSt = 'pending'; }

// PM In
if ($todayLog && $todayLog['lunch_in']) {
    $pmInSt = 'completed';
} elseif ($isPmLeave || $isWholeLeave) {
    $pmInSt = 'leave';
} elseif ($isPmInSkipped) {
    $pmInSt = 'skipped';
} elseif (isPmInWindowPassed()) {
    $pmInSt = 'locked';
} elseif ($nowTs >= strtotime('12:01')) {
    $pmInWin = isWithinWindow('lunch_in', $todayLog);
    $pmInSt = $pmInWin['allowed'] ? 'active' : 'pending';
} else {
    $pmInSt = 'pending';
}

// PM Out
if ($todayLog && $todayLog['time_out']) {
    $pmOutSt = 'completed';
} elseif ($isPmLeave || $isWholeLeave) {
    $pmOutSt = 'leave';
} elseif ($nowTs > strtotime('18:00')) {
    $pmOutSt = 'locked';
} elseif ($nowTs >= strtotime('17:00')) {
    $pmOutSt = 'active';
} else {
    $pmOutSt = 'pending';
}

$serverTime = date('H:i:s');
$serverDate = date('Y-m-d');

$accomplishments = [];
if ($todayLog) {
    $stmtAcc = $pdo->prepare("SELECT item_text FROM accomplishments WHERE log_id = :lid ORDER BY id ASC");
    $stmtAcc->execute([':lid' => $todayLog['id']]);
    $accomplishments = $stmtAcc->fetchAll(PDO::FETCH_COLUMN);
}

// Fetch upcoming OB entries for this employee
$obEntries = getOBUpcoming($pdo, $userId);
// Also fetch OB specifically for today so the clock view can reflect OB status
$obToday = getOBForDate($pdo, $userId, $serverDate);
$hasObToday = !empty($obToday);

// OB filing window (UI + JS): allow filing only during 07:45 - 08:15
$obWindowCheck = isObFilingWindow();
$obWindowAllowed = $obWindowCheck['allowed'];
$obWindowMessage = $obWindowCheck['message'];
// Also fetch OB specifically for today so the clock view can reflect OB status
$obToday = getOBForDate($pdo, $userId, $serverDate);
$hasObToday = !empty($obToday);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <div class="top-bar">
        <div class="page-title">
            <span class="page-icon"><?= icon('clock', '24') ?></span>
            <h2>Attendance Portal</h2>
        </div>
        <a href="/employee/change_password.php" class="user-dropdown" style="text-decoration:none;color:inherit;">
            <?= renderUserAvatar(getProfilePictureUrl(getCurrentUserPicture()), strtoupper(substr(getCurrentUserName(), 0, 1))) ?>
            <div class="user-meta">
                <div class="user-name"><?= htmlspecialchars(getCurrentUserName()) ?></div>
                <div class="user-role"><?= $_roleDisplay ?></div>
            </div>
        </a>
    </div>

    <div class="content-grid" style="margin-bottom:1.5rem;">
        <div class="clock-display">
            <div class="clock-date" id="clock-date"></div>
            <div class="clock-time" id="clock-time">--:--:-- --</div>
            <div class="clock-status" style="margin-top:0.75rem;">
                <?php if ($isWholeLeave): ?>
                    <span class="badge badge-leave"><?= icon('calendar', '12') ?> Whole Day Leave</span>
                <?php elseif ($isAmLeave): ?>
                    <span class="badge badge-leave"><?= icon('calendar', '12') ?> AM Leave</span>
                <?php elseif ($isPmLeave): ?>
                    <span class="badge badge-leave"><?= icon('calendar', '12') ?> PM Leave</span>
                <?php elseif ($todayLog && $todayLog['time_in']): ?>
                    <span class="badge badge-logged-in"><?= icon('check', '12') ?> Logged In</span>
                <?php elseif ($hasObToday): ?>
                    <span class="badge badge-ob"><?= icon('briefcase', '12') ?> Official Business</span> 
                <?php elseif ($isAbsentAM && $isPmInSkipped): ?>
                    <span class="badge badge-absent"><?= icon('alert-triangle', '12') ?> AM Absent</span>
                    <span class="badge badge-absent"><?= icon('alert-triangle', '12') ?> PM Absent</span>
                <?php elseif ($isAbsentAM): ?>
                    <span class="badge badge-absent"><?= icon('alert-triangle', '12') ?> AM Absent</span>
                <?php else: ?>
                    <span class="badge badge-pending">Not Clocked In</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-title" style="margin-bottom:0.75rem;">
                <span><?= icon('clock', '18') ?></span> Today's Attendance
            </div>
            <div class="flex-between mb-1">
                <span style="font-size:0.82rem;color:var(--text-medium)">Current Step</span>
                <strong style="font-size:0.85rem"><?= $currentStep <= 4 ? "$currentStep of 4" : "Complete" ?></strong>
            </div>
            <div class="flex-between mb-1">
                <span style="font-size:0.82rem;color:var(--text-medium)">Next Action</span>
                <strong style="font-size:0.85rem;color:var(--primary)"><?= $currentStep <= 4 ? $nextLabel : 'All Done' ?></strong>
            </div>
        </div>
    </div>

    <div class="content-grid-wide">
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <span class="card-icon"><?= icon('check-circle', '18') ?></span>
                    Log Your Attendance
                </div>
                <span style="font-size:0.8rem;color:var(--text-light)">Step <?= min($currentStep, 4) ?> of 4</span>
            </div>

            <div class="progress-section">
                <div class="progress-header">
                    <span>Progress</span>
                    <span id="progress-pct"><?= $progress ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar-fill" id="progress-fill" style="width: <?= $progress ?>%"></div>
                </div>
            </div>

            <?php if ($currentStep <= 4): ?>
            <div class="next-action-card">
                <div class="next-action-label">NEXT ACTION</div>
                <div class="next-action-value">
                    <?php
                    $actionIcons = ['time_in' => 'log-in', 'lunch_out' => 'coffee', 'lunch_in' => 'log-in', 'time_out' => 'log-out'];
                    echo icon($actionIcons[$nextAction] ?? 'clock', '20') . ' ' . $nextLabel;
                    ?>
                </div>
            </div>
            <?php else: ?>
            <div class="next-action-card" style="background:var(--success-bg);">
                <div class="next-action-value" style="color:var(--success)"><?= icon('check-circle', '20') ?> All steps completed for today!</div>
            </div>
            <?php endif; ?>

            <?php
                $windowCheck   = ($currentStep <= 4) ? isWithinWindow($nextAction, $todayLog) : ['allowed' => true, 'message' => ''];
                $windowAllowed = $windowCheck['allowed'];
                $windowMessage = $windowCheck['message'];
            ?>
            <div style="margin-top:1.25rem;">
                <?php if (!$isFriday): ?>
                    <div style="padding:0.75rem 1rem;background:var(--bg-muted,#f3f4f6);border-radius:8px;text-align:center;font-size:0.85rem;color:var(--text-medium);">
                        <?= icon('lock', '14') ?> Clock-in/out is only available on <strong>Fridays</strong>.
                    </div>
                <?php elseif ($currentStep <= 4 && $windowAllowed): ?>
                    <button class="btn btn-primary" id="clock-action-btn" onclick="submitClockAction('<?= $nextAction ?>')">
                        <?= icon('clock', '16') ?> Log <?= $nextLabel ?>
                    </button>
                <?php elseif ($currentStep <= 4): ?>
                    <button class="btn btn-primary" disabled style="opacity:0.55;cursor:not-allowed;">
                        <?= icon('lock', '16') ?> Log <?= $nextLabel ?>
                    </button>
                    <div style="margin-top:0.5rem;padding:0.6rem 0.75rem;background:#fee2e2;color:#dc2626;border-radius:6px;font-size:0.82rem;">
                        <?= htmlspecialchars($windowMessage) ?>
                    </div>
                <?php else: ?>
                    <button class="btn btn-primary" disabled><?= icon('check', '16') ?> All Steps Complete</button>
                <?php endif; ?>

                <?php if ($isFriday && !$isPmLeave && !$isWholeLeave && $todayLog && $todayLog['time_in'] && !$todayLog['time_out']): ?>
                    <button class="btn btn-danger-outline" style="width:100%;margin-top:0.75rem;" onclick="submitEmergencyOut()">
                        <?= icon('alert-triangle', '16') ?> Emergency Time Out
                    </button>
                <?php endif; ?>

                <?php
                    $alreadyLeave    = $todayLog && in_array($todayLog['am_status'] ?? '', ['leave', 'am_leave', 'pm_leave']);
                    $alreadyLeaveType = $todayLog['am_status'] ?? null;
                    $leaveTypeLabels = ['leave' => 'Whole Day Leave', 'am_leave' => 'AM Leave', 'pm_leave' => 'PM Leave'];
                    // AM leave requires no AM clock data recorded
                    $canAmLeave = $isFriday && !$alreadyLeave && !($todayLog && ($todayLog['time_in'] || $todayLog['lunch_out']));
                    // Whole Day leave requires no clock data at all
                    $canWholeDayLeave = $isFriday && !$alreadyLeave && !($todayLog && ($todayLog['time_in'] || $todayLog['lunch_out'] || $todayLog['lunch_in'] || $todayLog['time_out']));
                    // PM leave requires that the PM session (lunch_in) hasn't started yet
                    $canPmLeave = $isFriday && !$alreadyLeave && !($todayLog && ($todayLog['lunch_in'] || $todayLog['time_out']));
                    $anyLeaveAvailable = $canAmLeave || $canWholeDayLeave || $canPmLeave;
                ?>
                <?php if ($alreadyLeave): ?>
                    <button class="btn btn-secondary" disabled
                        style="width:100%;margin-top:0.75rem;opacity:0.5;cursor:not-allowed;">
                        <?= icon('calendar', '16') ?> <?= htmlspecialchars($leaveTypeLabels[$alreadyLeaveType] ?? 'On Leave') ?> Today
                    </button>
                <?php elseif ($anyLeaveAvailable): ?>
                    <button class="btn btn-secondary" id="leave-today-btn"
                        style="width:100%;margin-top:0.75rem;"
                        onclick="showLeaveModal()">
                        <?= icon('calendar', '16') ?> Mark as Leave Today
                    </button>
                <?php else: ?>
                    <button class="btn btn-secondary" disabled
                        style="width:100%;margin-top:0.75rem;opacity:0.5;cursor:not-allowed;"
                        title="<?= !$isFriday ? 'Available on Fridays only.' : 'Leave cannot be marked at this stage.' ?>">
                        <?= icon('calendar', '16') ?> Mark as Leave Today
                    </button>
                <?php endif; ?>
            </div>

            <?php if ($isFriday && !$isWholeLeave): ?>
               <?php if ($obWindowAllowed): ?>
                <div style="margin-top:0.75rem;">
                    <button class="btn btn-secondary" id="ob-btn"
                        style="width:100%;justify-content:center;"
                        onclick="showOBModal()">
                        <?= icon('briefcase', '16') ?> File Official Business
                    </button>
                </div>
                <?php else: ?>
                <div style="margin-top:0.75rem;">
                    <button class="btn btn-secondary" disabled style="width:100%;justify-content:center;opacity:0.6;cursor:not-allowed;">
                        <?= icon('briefcase', '16') ?> File Official Business
                    </button>
                    <div style="margin-top:0.5rem;padding:0.6rem 0.75rem;background:#fee2e2;color:#dc2626;border-radius:6px;font-size:0.82rem;">
                        <?= htmlspecialchars($obWindowMessage ?: 'Official Business filing is allowed only from 7:45 AM to 8:15 AM.') ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!empty($obEntries)): ?>
            <div style="margin-top:1rem;">
                <h4 style="font-size:0.82rem;color:var(--text-medium);margin-bottom:0.5rem;"><?= icon('briefcase', '14') ?> Upcoming Official Business</h4>
                <?php foreach ($obEntries as $ob):
                    $obWindow = checkAccomplishmentWindow($ob['ob_date']);
                    $canDelete = $obWindow['allowed'];
                ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:0.5rem 0.65rem;background:var(--bg);border-radius:8px;margin-bottom:0.4rem;font-size:0.8rem;">
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:600;color:var(--text-dark);">
                            <?= date('M j, Y', strtotime($ob['ob_date'])) ?>
                            <span class="badge badge-ob" style="font-size:0.68rem;padding:0.15rem 0.4rem;margin-left:0.3rem;">OB</span>
                        </div>
                        <div style="color:var(--text-medium);margin-top:2px;">
                            <?= date('h:i A', strtotime($ob['time_from'])) ?> – <?= date('h:i A', strtotime($ob['time_to'])) ?>
                            &middot; <?= htmlspecialchars($ob['location']) ?>
                        </div>
                        <div style="color:var(--text-light);margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <?= htmlspecialchars($ob['reason']) ?>
                        </div>
                    </div>
                    <?php if ($canDelete): ?>
                    <button onclick="deleteOB(<?= (int)$ob['id'] ?>)" class="btn btn-danger-outline" style="padding:0.25rem 0.5rem;font-size:0.72rem;margin-left:0.5rem;flex-shrink:0;" title="Delete OB">
                        <?= icon('x', '12') ?>
                    </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div id="clock-message" style="margin-top:0.75rem;font-size:0.82rem;display:none;padding:0.6rem;border-radius:6px;"></div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title" style="color:var(--danger);">
                    <span class="card-icon"><?= icon('clock', '18') ?></span>
                    Today's Summary
                </div>
                <span style="font-size:0.75rem;color:var(--text-light)">Your attendance timeline</span>
            </div>

            <div class="timeline" id="timeline">
                <?php
                // Helper closures for timeline rendering
                $tItemClass = function(string $st): string {
                    return match($st) { 'completed' => 'done', 'active' => 'active', 'locked' => 'locked', default => '' };
                };
                $tDotIcon = function(string $st, string $stepIcon): string {
                    return match($st) {
                        'completed' => icon('check', '16'),
                        'leave'     => icon('calendar', '14'),
                        'skipped'   => icon('minus', '14'),
                        'locked'    => icon('lock', '14'),
                        default     => icon($stepIcon, '14'),
                    };
                };
                $tBadge = function(string $st): string {
                    return match($st) {
                        'completed' => '<span class="badge badge-done timeline-badge">' . icon('check', '12') . ' Done</span>',
                        'leave'     => '<span class="badge badge-leave timeline-badge">' . icon('calendar', '12') . ' Leave</span>',
                        'skipped'   => '<span class="badge badge-skipped timeline-badge">' . icon('minus', '12') . ' Skipped</span>',
                        'locked'    => '<span class="badge badge-locked timeline-badge">' . icon('lock', '12') . ' Locked</span>',
                        default     => '',
                    };
                };
                $tSubtext = function(string $st, ?string $time = null): string {
                    if ($st === 'completed' && $time) return '<div class="timeline-time">' . formatTimeDisplay($time) . '</div>';
                    $msg = match($st) {
                        'completed' => 'Done',
                        'leave'     => 'On Leave',
                        'skipped'   => 'Skipped',
                        'locked'    => 'Locked',
                        'active'    => 'In progress',
                        default     => 'Pending',
                    };
                    $color = match($st) {
                        'leave'   => 'var(--info)',
                        'locked'  => 'var(--danger)',
                        'skipped' => 'var(--text-light)',
                        'active'  => 'var(--primary)',
                        default   => 'var(--text-medium)',
                    };
                    return '<div class="timeline-status" style="color:' . $color . ';">' . $msg . '</div>';
                };
                ?>

                <div class="timeline-item <?= $tItemClass($amInSt) ?>">
                    <div class="timeline-dot"><?= $tDotIcon($amInSt, 'log-in') ?></div>
                    <div class="timeline-content">
                        <div class="timeline-title">AM In</div>
                        <?= $tSubtext($amInSt, $todayLog['time_in'] ?? null) ?>
                    </div>
                    <?= $tBadge($amInSt) ?>
                </div>

                <div class="timeline-item <?= $tItemClass($amOutSt) ?>">
                    <div class="timeline-dot"><?= $tDotIcon($amOutSt, 'coffee') ?></div>
                    <div class="timeline-content">
                        <div class="timeline-title">AM Out</div>
                        <?= $tSubtext($amOutSt, $todayLog['lunch_out'] ?? null) ?>
                    </div>
                    <?= $tBadge($amOutSt) ?>
                </div>

                <div class="timeline-item <?= $tItemClass($pmInSt) ?>">
                    <div class="timeline-dot"><?= $tDotIcon($pmInSt, 'log-in') ?></div>
                    <div class="timeline-content">
                        <div class="timeline-title">PM In</div>
                        <?= $tSubtext($pmInSt, $todayLog['lunch_in'] ?? null) ?>
                    </div>
                    <?= $tBadge($pmInSt) ?>
                </div>

                <div class="timeline-item <?= $tItemClass($pmOutSt) ?>">
                    <div class="timeline-dot"><?= $tDotIcon($pmOutSt, 'log-out') ?></div>
                    <div class="timeline-content">
                        <div class="timeline-title">PM Out</div>
                        <?= $tSubtext($pmOutSt, $todayLog['time_out'] ?? null) ?>
                    </div>
                    <?= $tBadge($pmOutSt) ?>
                </div>
            </div>
        </div>
    </div>

<?php
$leaveVarsScript = 'window.__leaveCanAm = ' . json_encode($canAmLeave ?? false) . ';'
    . 'window.__leaveCanWholeDay = ' . json_encode($canWholeDayLeave ?? false) . ';'
    . 'window.__leaveCanPm = ' . json_encode($canPmLeave ?? false) . ';'
    . 'window.__leaveCalIcon = ' . json_encode(icon('calendar', '16')) . ';'
    . 'window.__obEmployeeId = ' . json_encode($_SESSION['employee_id'] ?? '') . ';'
    . 'window.__obFullName = ' . json_encode($_SESSION['full_name'] ?? '') . ';';

$inlineScript = $leaveVarsScript . "\n" . <<<'JS'
startLiveClock('clock-time');
updateDateDisplay('clock-date');

async function submitClockAction(action) {
    const btn = document.getElementById('clock-action-btn');
    const msgDiv = document.getElementById('clock-message');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner spinner-white"></span> Processing...';
    msgDiv.style.display = 'none';

    const accomplishments = getAccomplishments();

    const result = await fetchAPI('/api/clock_action.php', {
        action: action,
        accomplishments: accomplishments,
        device_fp: getDeviceFingerprint()
    });

    if (result.success) {
        showToast(result.message, 'success');
        setTimeout(() => location.reload(), 1000);
    } else {
        msgDiv.style.display = 'block';
        msgDiv.style.background = '#fee2e2';
        msgDiv.style.color = '#dc2626';
        msgDiv.textContent = result.message;
        showToast(result.message, 'error');
        btn.disabled = false;
        const labelMap = { time_in: 'AM In', lunch_out: 'AM Out', lunch_in: 'PM In', time_out: 'PM Out' };
        btn.innerHTML = 'Log ' + (labelMap[action] || action.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()));
    }
}

function submitEmergencyOut() {
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.style.zIndex = '10001';

    const alertIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>';

    overlay.innerHTML = `
        <div class="modal" style="max-width:380px;text-align:center;">
            <div style="width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;background:rgba(220,38,38,0.1);color:#dc2626;">${alertIcon}</div>
            <h3 style="margin-bottom:0.5rem;">Emergency Time Out</h3>
            <p style="margin:0 0 1.25rem;color:var(--text-medium);line-height:1.6;font-size:0.85rem;">Are you sure you want to use Emergency Time Out? This will log your time out immediately.</p>
            <div style="display:flex;flex-direction:column;gap:0.5rem;">
                <button type="button" class="btn btn-danger" id="emergency-confirm-btn" style="width:100%;justify-content:center;">Confirm Emergency Out</button>
                <button type="button" class="btn btn-secondary" id="emergency-cancel-btn" style="width:100%;justify-content:center;">Cancel</button>
            </div>
        </div>
    `;

    document.body.appendChild(overlay);
    requestAnimationFrame(() => overlay.classList.add('show'));

    const closeModal = () => {
        overlay.classList.remove('show');
        setTimeout(() => overlay.remove(), 180);
    };

    overlay.querySelector('#emergency-cancel-btn').addEventListener('click', closeModal);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) closeModal(); });
    document.addEventListener('keydown', function onEsc(e) {
        if (e.key === 'Escape') { closeModal(); document.removeEventListener('keydown', onEsc); }
    });

    overlay.querySelector('#emergency-confirm-btn').addEventListener('click', async () => {
        const confirmBtn = overlay.querySelector('#emergency-confirm-btn');
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<span class="spinner spinner-white"></span> Processing...';

        const result = await fetchAPI('/api/clock_action.php', {
            action: 'emergency_time_out',
            accomplishments: getAccomplishments(),
            device_fp: getDeviceFingerprint()
        });

        if (result.success) {
            closeModal();
            showToast(result.message, 'warning');
            setTimeout(() => location.reload(), 1000);
        } else {
            closeModal();
            showToast(result.message, 'error');
        }
    });
}

function showLeaveModal() {
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.style.zIndex = '10001';

    const canAm = window.__leaveCanAm;
    const canWholeDay = window.__leaveCanWholeDay;
    const canPm = window.__leaveCanPm;

    let buttons = '';
    const sunIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>';
    const moonIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>';
    const calIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>';
    const calIconLg = '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>';
    if (canAm) {
        buttons += `<button type="button" class="btn btn-secondary" data-leave="am" style="width:100%;justify-content:center;padding:0.65rem 1rem;font-size:0.88rem;">
            ${sunIcon} AM Leave
            <span style="display:block;font-size:0.72rem;color:var(--text-light);font-weight:400;margin-top:2px;">Morning session only</span>
        </button>`;
    }
    if (canPm) {
        buttons += `<button type="button" class="btn btn-secondary" data-leave="pm" style="width:100%;justify-content:center;padding:0.65rem 1rem;font-size:0.88rem;">
            ${moonIcon} PM Leave
            <span style="display:block;font-size:0.72rem;color:var(--text-light);font-weight:400;margin-top:2px;">Afternoon session only</span>
        </button>`;
    }
    if (canWholeDay) {
        buttons += `<button type="button" class="btn btn-secondary" data-leave="whole_day" style="width:100%;justify-content:center;padding:0.65rem 1rem;font-size:0.88rem;">
            ${calIcon} Whole Day
            <span style="display:block;font-size:0.72rem;color:var(--text-light);font-weight:400;margin-top:2px;">Entire day leave</span>
        </button>`;
    }

    overlay.innerHTML = `
        <div class="modal" style="max-width:380px;text-align:center;">
            <div style="width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;background:rgba(29,78,216,0.1);color:#1d4ed8;">${calIconLg}</div>
            <h3 style="margin-bottom:0.5rem;">Mark as Leave Today</h3>
            <p style="margin:0 0 1.25rem;color:var(--text-medium);line-height:1.6;font-size:0.85rem;">Select the type of leave you want to file for today.</p>
            <div style="display:flex;flex-direction:column;gap:0.5rem;margin-bottom:1rem;">
                ${buttons}
            </div>
            <button type="button" class="btn btn-secondary" data-leave-cancel style="width:100%;justify-content:center;">Cancel</button>
        </div>
    `;

    document.body.appendChild(overlay);
    requestAnimationFrame(() => overlay.classList.add('show'));

    const closeModal = () => {
        overlay.classList.remove('show');
        setTimeout(() => overlay.remove(), 180);
    };

    overlay.querySelector('[data-leave-cancel]').addEventListener('click', closeModal);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) closeModal(); });
    document.addEventListener('keydown', function onEsc(e) {
        if (e.key === 'Escape') { closeModal(); document.removeEventListener('keydown', onEsc); }
    });

    overlay.querySelectorAll('[data-leave]').forEach(btn => {
        btn.addEventListener('click', () => {
            const leaveType = btn.getAttribute('data-leave');
            closeModal();
            submitLeaveToday(leaveType);
        });
    });
}

async function submitLeaveToday(leaveType) {
    const labels = { am: 'AM Leave', pm: 'PM Leave', whole_day: 'Whole Day Leave' };
    const label = labels[leaveType] || 'On Leave';
    const note = leaveType === 'pm'
        ? 'Your AM session will be preserved.'
        : leaveType === 'am'
        ? 'AM session will be locked. You can still log PM In and PM Out.'
        : 'This will lock all clock actions for today.';

    if (!await confirmAction(`Mark today as ${label}? ${note}`)) return;

    const btn = document.getElementById('leave-today-btn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner spinner-white"></span> Processing...';
    }

    const result = await fetchAPI('/api/mark_leave_today.php', { leave_type: leaveType });

    if (result.success) {
        showToast(result.message, 'success');
        setTimeout(() => location.reload(), 1000);
    } else {
        showToast(result.message, 'error');
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = window.__leaveCalIcon + ' Mark as Leave Today';
        }
    }
}

function showOBModal() {
    if (typeof window.__obWindowAllowed !== 'undefined' && !window.__obWindowAllowed) {
        showToast(window.__obWindowMessage || 'Official Business filing is allowed only from 7:45 AM to 8:15 AM.', 'error');
        return;
    }
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.style.zIndex = '10001';

    const briefcaseIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>';

    const today = new Date().toISOString().split('T')[0];

    overlay.innerHTML = `
        <div class="modal" style="max-width:440px;text-align:left;">
            <div style="text-align:center;margin-bottom:1rem;">
                <div style="width:56px;height:56px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 0.75rem;background:rgba(194,65,12,0.1);color:#c2410c;">${briefcaseIcon}</div>
                <h3 style="margin-bottom:0.25rem;">File Official Business</h3>
                <p style="color:var(--text-medium);font-size:0.82rem;">Fill in the details of your official business activity.</p>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.6rem;margin-bottom:0.6rem;">
                <div>
                    <label style="display:block;font-size:0.75rem;font-weight:600;color:var(--text-medium);margin-bottom:0.25rem;">Employee No.</label>
                    <input type="text" value="${window.__obEmployeeId}" readonly
                        style="width:100%;padding:0.45rem 0.6rem;border:1.5px solid var(--border);border-radius:6px;font-size:0.82rem;background:var(--bg);color:var(--text-medium);">
                </div>
                <div>
                    <label style="display:block;font-size:0.75rem;font-weight:600;color:var(--text-medium);margin-bottom:0.25rem;">Full Name</label>
                    <input type="text" value="${window.__obFullName}" readonly
                        style="width:100%;padding:0.45rem 0.6rem;border:1.5px solid var(--border);border-radius:6px;font-size:0.82rem;background:var(--bg);color:var(--text-medium);">
                </div>
            </div>
            <div style="margin-bottom:0.6rem;">
                <label style="display:block;font-size:0.75rem;font-weight:600;color:var(--text-medium);margin-bottom:0.25rem;">Date of Official Business</label>
                <input type="date" id="ob-date" value="${today}" min="${today}"
                    style="width:100%;padding:0.45rem 0.6rem;border:1.5px solid var(--border);border-radius:6px;font-size:0.82rem;">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.6rem;margin-bottom:0.6rem;">
                <div>
                    <label style="display:block;font-size:0.75rem;font-weight:600;color:var(--text-medium);margin-bottom:0.25rem;">Time From</label>
                    <input type="time" id="ob-time-from" value="09:00"
                        style="width:100%;padding:0.45rem 0.6rem;border:1.5px solid var(--border);border-radius:6px;font-size:0.82rem;">
                </div>
                <div>
                    <label style="display:block;font-size:0.75rem;font-weight:600;color:var(--text-medium);margin-bottom:0.25rem;">Time To</label>
                    <input type="time" id="ob-time-to" value="11:00"
                        style="width:100%;padding:0.45rem 0.6rem;border:1.5px solid var(--border);border-radius:6px;font-size:0.82rem;">
                </div>
            </div>
            <div style="margin-bottom:0.6rem;">
                <label style="display:block;font-size:0.75rem;font-weight:600;color:var(--text-medium);margin-bottom:0.25rem;">Reason / Description</label>
                <textarea id="ob-reason" maxlength="500" rows="2" placeholder="e.g. Document submission to division office"
                    style="width:100%;padding:0.45rem 0.6rem;border:1.5px solid var(--border);border-radius:6px;font-size:0.82rem;resize:vertical;"></textarea>
            </div>
            <div style="margin-bottom:1rem;">
                <label style="display:block;font-size:0.75rem;font-weight:600;color:var(--text-medium);margin-bottom:0.25rem;">Location / Office Visited</label>
                <input type="text" id="ob-location" maxlength="300" placeholder="e.g. SDO San Pedro City, Division Office"
                    style="width:100%;padding:0.45rem 0.6rem;border:1.5px solid var(--border);border-radius:6px;font-size:0.82rem;">
            </div>
            <div style="display:flex;flex-direction:column;gap:0.5rem;">
                <button type="button" class="btn btn-primary" id="ob-submit-btn" style="width:100%;justify-content:center;">Submit Official Business</button>
                <button type="button" class="btn btn-secondary" id="ob-cancel-btn" style="width:100%;justify-content:center;">Cancel</button>
            </div>
        </div>
    `;

    document.body.appendChild(overlay);
    requestAnimationFrame(() => overlay.classList.add('show'));

    const closeModal = () => {
        overlay.classList.remove('show');
        setTimeout(() => overlay.remove(), 180);
    };

    overlay.querySelector('#ob-cancel-btn').addEventListener('click', closeModal);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) closeModal(); });
    document.addEventListener('keydown', function onEsc(e) {
        if (e.key === 'Escape') { closeModal(); document.removeEventListener('keydown', onEsc); }
    });

    overlay.querySelector('#ob-submit-btn').addEventListener('click', async () => {
        const btn = overlay.querySelector('#ob-submit-btn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner spinner-white"></span> Submitting...';

        const data = {
            ob_date: overlay.querySelector('#ob-date').value,
            time_from: overlay.querySelector('#ob-time-from').value,
            time_to: overlay.querySelector('#ob-time-to').value,
            reason: overlay.querySelector('#ob-reason').value,
            location: overlay.querySelector('#ob-location').value
        };

        const result = await fetchAPI('/api/submit_ob.php', data);

        if (result.success) {
            closeModal();
            showToast(result.message, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast(result.message, 'error');
            btn.disabled = false;
            btn.innerHTML = 'Submit Official Business';
        }
    });
}

async function deleteOB(obId) {
    if (!await confirmAction('Delete this Official Business entry?')) return;

    const result = await fetchAPI('/api/delete_ob.php', { ob_id: obId });

    if (result.success) {
        showToast(result.message, 'success');
        setTimeout(() => location.reload(), 1000);
    } else {
        showToast(result.message, 'error');
    }
}
JS;

require_once __DIR__ . '/../includes/footer.php';
?>
