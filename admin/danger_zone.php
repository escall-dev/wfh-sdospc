<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireSuperAdmin();

$pageTitle = 'Danger Zone';
$currentPage = 'danger_zone';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$isLocked = false;
$lockMinutes = 0;
if (isset($_SESSION['dz_failed_attempts'], $_SESSION['dz_lockout_until'])
    && $_SESSION['dz_failed_attempts'] >= 3
    && time() < $_SESSION['dz_lockout_until']) {
    $isLocked = true;
    $lockMinutes = ceil(($_SESSION['dz_lockout_until'] - time()) / 60);
}

// Get counts for display
$countLogs = $pdo->query("SELECT COUNT(*) FROM attendance_logs")->fetchColumn();
$countAccomp = $pdo->query("SELECT COUNT(*) FROM accomplishments")->fetchColumn();
$countIdlar = $pdo->query("SELECT COUNT(*) FROM idlar_attachments")->fetchColumn();
$countUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role != 'superadmin'")->fetchColumn();
?>

<main class="main-content">
    <div class="page-header">
        <h2 class="dz-page-title"><?= icon('alert-triangle', '24') ?> Danger Zone</h2>
        <p class="page-subtitle">Critical system operations — proceed with extreme caution</p>
    </div>

    <!-- Danger Zone Content (hidden until gate password verified) -->
    <div id="danger-zone-content" style="display:none;">

        <div class="dz-banner">
            <?= icon('alert-triangle', '20') ?>
            <span>You are in the <strong>Danger Zone</strong>. All actions below are <strong>irreversible</strong>. Each action requires password re-verification.</span>
        </div>

        <div class="dz-section-title">
            <h3>Data Management</h3>
            <p>Select an individual action to perform. Each requires password confirmation.</p>
        </div>

        <div class="dz-grid">

            <!-- Card 1: Clear Attendance Logs -->
            <div class="dz-action-card" id="card-attendance">
                <div class="dz-action-icon dz-icon-red">
                    <?= icon('clock', '28') ?>
                </div>
                <div class="dz-action-body">
                    <h4>Clear Attendance Logs</h4>
                    <p>Delete all time-in/out records, total hours, days present, and attendance statuses for every employee.</p>
                    <div class="dz-action-meta">
                        <span class="dz-count"><?= number_format($countLogs) ?> record(s)</span>
                    </div>
                </div>
                <div class="dz-action-affected">
                    <span class="dz-affected-label">Also clears:</span>
                    <span class="dz-affected-tag">Accomplishments</span>
                    <span class="dz-affected-tag">IDLAR Attachments</span>
                </div>
                <button type="button" class="btn btn-danger-zone dz-action-btn" onclick="startAction('clear_attendance', 'Clear All Attendance Logs', 'This will permanently delete all attendance logs, along with their associated accomplishments and IDLAR attachments. All time-in/out records, total hours, and days present will be lost.')">
                    <?= icon('trash', '16') ?> Clear Attendance
                </button>
            </div>

            <!-- Card 2: Clear Accomplishments -->
            <div class="dz-action-card" id="card-accomplishments">
                <div class="dz-action-icon dz-icon-orange">
                    <?= icon('clipboard', '28') ?>
                </div>
                <div class="dz-action-body">
                    <h4>Clear Accomplishments</h4>
                    <p>Delete all daily accomplishment entries submitted by employees across all attendance records.</p>
                    <div class="dz-action-meta">
                        <span class="dz-count"><?= number_format($countAccomp) ?> record(s)</span>
                    </div>
                </div>
                <button type="button" class="btn btn-danger-zone dz-action-btn" onclick="startAction('clear_accomplishments', 'Clear All Accomplishments', 'This will permanently delete all accomplishment records submitted by employees. Attendance logs will remain intact.')">
                    <?= icon('trash', '16') ?> Clear Accomplishments
                </button>
            </div>

            <!-- Card 3: Clear IDLAR Attachments -->
            <div class="dz-action-card" id="card-idlar">
                <div class="dz-action-icon dz-icon-purple">
                    <?= icon('paperclip', '28') ?>
                </div>
                <div class="dz-action-body">
                    <h4>Clear IDLAR Attachments</h4>
                    <p>Delete all uploaded IDLAR attachment files from the database and the server file system.</p>
                    <div class="dz-action-meta">
                        <span class="dz-count"><?= number_format($countIdlar) ?> file(s)</span>
                    </div>
                </div>
                <button type="button" class="btn btn-danger-zone dz-action-btn" onclick="startAction('clear_idlar', 'Clear All IDLAR Attachments', 'This will permanently delete all IDLAR attachment records and their physical files from the server. Attendance logs will remain intact.')">
                    <?= icon('trash', '16') ?> Clear IDLAR Files
                </button>
            </div>

            <!-- Card 4: Reset All Passwords -->
            <div class="dz-action-card" id="card-passwords">
                <div class="dz-action-icon dz-icon-dark">
                    <?= icon('key', '28') ?>
                </div>
                <div class="dz-action-body">
                    <h4>Reset All Passwords</h4>
                    <p>Reset every employee and admin password back to their Employee ID. Users must change it on next login.</p>
                    <div class="dz-action-meta">
                        <span class="dz-count"><?= number_format($countUsers) ?> user(s) affected</span>
                    </div>
                </div>
                <div class="dz-action-affected">
                    <span class="dz-affected-label">Not affected:</span>
                    <span class="dz-affected-tag dz-tag-safe">Your password</span>
                </div>
                <button type="button" class="btn btn-danger-zone dz-action-btn" onclick="startAction('reset_passwords', 'Reset All User Passwords', 'All employee and admin passwords will be reset to their Employee IDs. Every user (except you) will be forced to change their password on next login. Your Super Admin password will NOT be changed.')">
                    <?= icon('key', '16') ?> Reset Passwords
                </button>
            </div>

        </div>

        <div class="dz-retained-info">
            <div class="dz-retained-header">
                <?= icon('shield', '18') ?>
                <h4>Always Retained</h4>
            </div>
            <div class="dz-retained-items">
                <span><?= icon('check', '14') ?> Employee profiles</span>
                <span><?= icon('check', '14') ?> Employee IDs</span>
                <span><?= icon('check', '14') ?> User roles &amp; status</span>
                <span><?= icon('check', '14') ?> Division assignments</span>
                <span><?= icon('check', '14') ?> Profile photos</span>
                <span><?= icon('check', '14') ?> Super Admin password</span>
            </div>
        </div>
    </div>

    <!-- Locked out message -->
    <div id="lockout-message" style="display:none;">
        <div class="dz-lockout-card">
            <div class="dz-lockout-icon"><?= icon('lock', '40') ?></div>
            <h3>Access Temporarily Locked</h3>
            <p>Too many failed password attempts. Please wait <strong id="lockout-remaining"><?= $lockMinutes ?></strong> minute(s) before trying again.</p>
            <a href="/admin/dashboard.php" class="btn btn-primary">Return to Dashboard</a>
        </div>
    </div>

<!-- ===== MODAL 1: Gate Warning ===== -->
<div class="modal-overlay" id="modal-gate-warning">
    <div class="modal dz-modal" style="max-width:520px;">
        <div class="dz-modal-banner">
            <div class="dz-modal-banner-icon"><?= icon('alert-triangle', '36') ?></div>
            <h3>Danger Zone Ahead</h3>
            <p>This area contains destructive system operations</p>
        </div>
        <div class="modal-body" style="padding:1.5rem;">
            <p style="margin-bottom:1.25rem; line-height:1.7; color:var(--text-dark); font-size:0.95rem;">
                You are about to enter a restricted section that contains
                <strong>irreversible operations</strong> which can permanently delete system data including:
            </p>
            <div class="dz-warning-list">
                <div class="dz-warning-item">
                    <span class="dz-wi-icon"><?= icon('clock', '16') ?></span>
                    <span>Attendance logs, total hours &amp; days present</span>
                </div>
                <div class="dz-warning-item">
                    <span class="dz-wi-icon"><?= icon('clipboard', '16') ?></span>
                    <span>Accomplishment records</span>
                </div>
                <div class="dz-warning-item">
                    <span class="dz-wi-icon"><?= icon('paperclip', '16') ?></span>
                    <span>IDLAR data &amp; uploaded attachments</span>
                </div>
                <div class="dz-warning-item">
                    <span class="dz-wi-icon"><?= icon('key', '16') ?></span>
                    <span>Employee &amp; admin passwords</span>
                </div>
            </div>
            <div class="dz-modal-notice">
                <?= icon('alert-circle', '16') ?>
                <span>These actions <strong>cannot be undone</strong>. Proceed only if you are absolutely certain.</span>
            </div>
        </div>
        <div class="dz-modal-footer">
            <a href="/admin/dashboard.php" class="btn btn-secondary">
                <?= icon('arrow-right', '16') ?> Go Back
            </a>
            <button type="button" class="btn btn-danger-zone" onclick="openGatePassword()">
                I Understand, Proceed
            </button>
        </div>
    </div>
</div>

<!-- ===== MODAL 2: Gate Password Verification ===== -->
<div class="modal-overlay" id="modal-gate-password">
    <div class="modal dz-modal" style="max-width:440px;">
        <div class="dz-modal-banner dz-modal-banner-dark">
            <div class="dz-modal-banner-icon"><?= icon('shield', '32') ?></div>
            <h3>Identity Verification</h3>
            <p>Confirm your Super Admin credentials</p>
        </div>
        <div class="modal-body" style="padding:1.5rem;">
            <div id="gate-error" class="dz-field-error" style="display:none;"></div>
            <div class="dz-input-group">
                <label for="gate-password"><?= icon('lock', '14') ?> Password</label>
                <div class="dz-input-wrapper">
                    <input type="password" id="gate-password" class="form-control" placeholder="Enter your password" autocomplete="off">
                </div>
            </div>
            <div class="dz-input-group">
                <label for="gate-confirm"><?= icon('lock', '14') ?> Confirm Password</label>
                <div class="dz-input-wrapper">
                    <input type="password" id="gate-confirm" class="form-control" placeholder="Re-enter your password" autocomplete="off">
                </div>
            </div>
            <p id="gate-attempts" class="dz-attempts-info" style="display:none;"></p>
        </div>
        <div class="dz-modal-footer">
            <a href="/admin/dashboard.php" class="btn btn-secondary">Cancel</a>
            <button type="button" class="btn btn-primary" id="btn-gate-submit" onclick="submitGatePassword()">
                <?= icon('shield', '16') ?> Verify &amp; Enter
            </button>
        </div>
    </div>
</div>

<!-- ===== MODAL 3: Action Warning (dynamic) ===== -->
<div class="modal-overlay" id="modal-action-warning">
    <div class="modal dz-modal" style="max-width:500px;">
        <div class="dz-modal-banner">
            <div class="dz-modal-banner-icon"><?= icon('alert-triangle', '36') ?></div>
            <h3 id="action-warning-title">Are You Sure?</h3>
            <p>This action is permanent and irreversible</p>
        </div>
        <div class="modal-body" style="padding:1.5rem;">
            <p id="action-warning-desc" style="line-height:1.7; color:var(--text-dark); font-size:0.95rem; margin-bottom:1.25rem;"></p>
            <div class="dz-modal-notice" style="margin-bottom:0;">
                <?= icon('alert-circle', '16') ?>
                <span>This action <strong>CANNOT be undone</strong>. All affected data will be permanently lost.</span>
            </div>
        </div>
        <div class="dz-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('modal-action-warning')">Cancel</button>
            <button type="button" class="btn btn-danger-zone" onclick="openActionPassword()">
                Yes, I'm Sure
            </button>
        </div>
    </div>
</div>

<!-- ===== MODAL 4: Action Password Re-verification ===== -->
<div class="modal-overlay" id="modal-action-password">
    <div class="modal dz-modal" style="max-width:440px;">
        <div class="dz-modal-banner">
            <div class="dz-modal-banner-icon"><?= icon('shield', '32') ?></div>
            <h3>Final Confirmation</h3>
            <p id="action-pw-subtitle">Re-enter your password to proceed</p>
        </div>
        <div class="modal-body" style="padding:1.5rem;">
            <div id="action-error" class="dz-field-error" style="display:none;"></div>
            <div class="dz-input-group">
                <label for="action-password"><?= icon('lock', '14') ?> Password</label>
                <div class="dz-input-wrapper">
                    <input type="password" id="action-password" class="form-control" placeholder="Enter your password" autocomplete="off">
                </div>
            </div>
            <div class="dz-input-group">
                <label for="action-confirm"><?= icon('lock', '14') ?> Confirm Password</label>
                <div class="dz-input-wrapper">
                    <input type="password" id="action-confirm" class="form-control" placeholder="Re-enter your password" autocomplete="off">
                </div>
            </div>
            <p id="action-attempts" class="dz-attempts-info" style="display:none;"></p>
        </div>
        <div class="dz-modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('modal-action-password')">Cancel</button>
            <button type="button" class="btn btn-danger-zone" id="btn-action-submit" onclick="submitActionPassword()">
                <?= icon('alert-triangle', '16') ?> Confirm &amp; Execute
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    var BASE = '';
    var isLocked = <?= $isLocked ? 'true' : 'false' ?>;
    var currentAction = '';
    var currentActionTitle = '';

    // --- Modal helpers (must be defined first) ---
    window.closeModal = function(id) {
        document.getElementById(id).classList.remove('show');
    };
    window.openModal = function(id) {
        document.getElementById(id).classList.add('show');
    };

    // On page load: show lockout or gate warning
    if (isLocked) {
        document.getElementById('lockout-message').style.display = '';
    } else {
        openModal('modal-gate-warning');
    }

    ['modal-action-warning', 'modal-action-password'].forEach(function(id) {
        document.getElementById(id).addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('show');
        });
    });

    // --- Gate flow ---
    window.openGatePassword = function() {
        closeModal('modal-gate-warning');
        clearFields('gate');
        openModal('modal-gate-password');
    };

    window.submitGatePassword = function() {
        var pw = document.getElementById('gate-password').value;
        var cpw = document.getElementById('gate-confirm').value;
        var errorEl = document.getElementById('gate-error');
        var btn = document.getElementById('btn-gate-submit');

        errorEl.style.display = 'none';
        if (!pw || !cpw) { showFieldError(errorEl, 'Both password fields are required.'); return; }
        if (pw !== cpw) { showFieldError(errorEl, 'Passwords do not match.'); return; }

        btn.disabled = true;
        btn.textContent = 'Verifying...';

        fetchDZ('verify_password', pw, cpw).then(function(data) {
            btn.disabled = false;
            btn.innerHTML = '<?= icon("shield", "16") ?> Verify & Enter';
            if (data.locked) { handleLockout(data.remaining_minutes); return; }
            if (!data.success) {
                showFieldError(errorEl, data.message);
                updateAttempts('gate-attempts', data.attempts_left);
                return;
            }
            closeModal('modal-gate-password');
            document.getElementById('danger-zone-content').style.display = '';
            showToast('Identity verified. You may proceed.', 'warning');
        });
    };

    // --- Action flow (dynamic for any action) ---
    window.startAction = function(action, title, description) {
        currentAction = action;
        currentActionTitle = title;
        document.getElementById('action-warning-title').textContent = title;
        document.getElementById('action-warning-desc').textContent = description;
        openModal('modal-action-warning');
    };

    window.openActionPassword = function() {
        closeModal('modal-action-warning');
        clearFields('action');
        document.getElementById('action-pw-subtitle').textContent = 'Re-enter your password to ' + currentActionTitle.toLowerCase();
        openModal('modal-action-password');
    };

    window.submitActionPassword = function() {
        var pw = document.getElementById('action-password').value;
        var cpw = document.getElementById('action-confirm').value;
        var errorEl = document.getElementById('action-error');
        var btn = document.getElementById('btn-action-submit');

        errorEl.style.display = 'none';
        if (!pw || !cpw) { showFieldError(errorEl, 'Both password fields are required.'); return; }
        if (pw !== cpw) { showFieldError(errorEl, 'Passwords do not match.'); return; }

        btn.disabled = true;
        btn.innerHTML = '<?= icon("alert-triangle", "16") ?> Processing...';

        fetchDZ(currentAction, pw, cpw).then(function(data) {
            if (data.locked) { handleLockout(data.remaining_minutes); return; }
            if (!data.success) {
                btn.disabled = false;
                btn.innerHTML = '<?= icon("alert-triangle", "16") ?> Confirm & Execute';
                showFieldError(errorEl, data.message);
                updateAttempts('action-attempts', data.attempts_left);
                return;
            }

            closeModal('modal-action-password');
            showToast(data.message, 'success');

            // Find the card and mark it as completed
            var cardMap = {
                'clear_attendance': 'card-attendance',
                'clear_accomplishments': 'card-accomplishments',
                'clear_idlar': 'card-idlar',
                'reset_passwords': 'card-passwords'
            };
            var cardId = cardMap[currentAction];
            if (cardId) {
                var card = document.getElementById(cardId);
                card.classList.add('dz-card-done');
                var actionBtn = card.querySelector('.dz-action-btn');
                actionBtn.disabled = true;
                actionBtn.innerHTML = '<?= icon("check-circle", "16") ?> Completed';
                actionBtn.className = 'btn btn-secondary dz-action-btn';
                var countEl = card.querySelector('.dz-count');
                if (countEl) countEl.textContent = '0 record(s)';
            }
        });
    };

    // --- Helpers ---
    function fetchDZ(action, password, confirmPassword) {
        return fetch(BASE + '/api/danger_zone_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ action: action, password: password, confirm_password: confirmPassword })
        })
        .then(function(r) { return r.json(); })
        .catch(function() { return { success: false, message: 'Network error. Please try again.' }; });
    }

    function showFieldError(el, msg) {
        el.textContent = msg;
        el.style.display = 'block';
    }

    function updateAttempts(elId, left) {
        var el = document.getElementById(elId);
        if (left !== undefined && left !== null) {
            el.textContent = left + ' attempt(s) remaining';
            el.style.display = 'block';
            if (left <= 1) { el.style.color = 'var(--danger)'; el.style.fontWeight = '600'; }
        }
    }

    function clearFields(prefix) {
        document.getElementById(prefix + '-password').value = '';
        document.getElementById(prefix + '-confirm').value = '';
        document.getElementById(prefix + '-error').style.display = 'none';
        var att = document.getElementById(prefix + '-attempts');
        att.textContent = '';
        att.style.display = 'none';
    }

    function handleLockout(minutes) {
        ['modal-gate-warning', 'modal-gate-password', 'modal-action-warning', 'modal-action-password'].forEach(closeModal);
        document.getElementById('danger-zone-content').style.display = 'none';
        document.getElementById('lockout-message').style.display = '';
        document.getElementById('lockout-remaining').textContent = minutes;
        showToast('Too many failed attempts. Locked for ' + minutes + ' minute(s).', 'error');
    }

    ['gate-password', 'gate-confirm'].forEach(function(id) {
        document.getElementById(id).addEventListener('keydown', function(e) { if (e.key === 'Enter') submitGatePassword(); });
    });
    ['action-password', 'action-confirm'].forEach(function(id) {
        document.getElementById(id).addEventListener('keydown', function(e) { if (e.key === 'Enter') submitActionPassword(); });
    });
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
