<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/backup_helpers.php';

requireSuperAdmin();

$pageTitle = 'Backup & Restore';
$currentPage = 'backup_restore';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';

$stats = getBackupStats($pdo);

$scheduled = $pdo->query("SELECT b.*, u.full_name AS created_by_name FROM db_backups b LEFT JOIN users u ON b.created_by = u.id WHERE b.backup_type = 'scheduled' ORDER BY b.created_at DESC")->fetchAll();
$preDeletion = $pdo->query("SELECT b.*, u.full_name AS created_by_name FROM db_backups b LEFT JOIN users u ON b.created_by = u.id WHERE b.backup_type = 'pre_deletion' ORDER BY b.created_at DESC")->fetchAll();
$manual = $pdo->query("SELECT b.*, u.full_name AS created_by_name FROM db_backups b LEFT JOIN users u ON b.created_by = u.id WHERE b.backup_type = 'manual' ORDER BY b.created_at DESC")->fetchAll();
?>

<main class="main-content">
    <div class="page-header">
        <h2><?= icon('hard-drive', '24') ?> Backup & Restore</h2>
        <p class="page-subtitle">Manage database backups, restore deleted data, and download copies</p>
    </div>

    <!-- Stats -->
    <div class="bk-stats-grid">
        <div class="bk-stat-card">
            <div class="bk-stat-icon bk-stat-icon-blue"><?= icon('database', '22') ?></div>
            <div class="bk-stat-info">
                <span class="bk-stat-value"><?= $stats['total_backups'] ?></span>
                <span class="bk-stat-label">Total Backups</span>
            </div>
        </div>
        <div class="bk-stat-card">
            <div class="bk-stat-icon bk-stat-icon-green"><?= icon('hard-drive', '22') ?></div>
            <div class="bk-stat-info">
                <span class="bk-stat-value"><?= $stats['total_size_formatted'] ?></span>
                <span class="bk-stat-label">Storage Used</span>
            </div>
        </div>
        <div class="bk-stat-card">
            <div class="bk-stat-icon bk-stat-icon-orange"><?= icon('clock', '22') ?></div>
            <div class="bk-stat-info">
                <span class="bk-stat-value"><?= $stats['last_backup'] ? date('M j, Y', strtotime($stats['last_backup'])) : 'Never' ?></span>
                <span class="bk-stat-label">Last Backup</span>
            </div>
        </div>
        <div class="bk-stat-card">
            <div class="bk-stat-icon bk-stat-icon-purple"><?= icon('calendar', '22') ?></div>
            <div class="bk-stat-info">
                <span class="bk-stat-value"><?= date('M 1, Y', strtotime($stats['next_scheduled'])) ?></span>
                <span class="bk-stat-label">Next Scheduled</span>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bk-tabs">
        <button class="bk-tab active" data-tab="scheduled">
            <?= icon('calendar', '16') ?> Scheduled
            <span class="bk-tab-count"><?= count($scheduled) ?></span>
        </button>
        <button class="bk-tab" data-tab="deleted">
            <?= icon('refresh-cw', '16') ?> Recently Deleted
            <span class="bk-tab-count"><?= count($preDeletion) ?></span>
        </button>
        <button class="bk-tab" data-tab="manual">
            <?= icon('archive', '16') ?> Manual
            <span class="bk-tab-count"><?= count($manual) ?></span>
        </button>
    </div>

    <!-- Tab: Scheduled Backups -->
    <div class="bk-tab-panel active" id="panel-scheduled">
        <div class="bk-panel-header">
            <div class="bk-panel-info">
                <?= icon('info', '16') ?>
                <span>Automatic backups are created on the 1st of each month when a Super Admin logs in. They contain a full copy of all database tables.</span>
            </div>
        </div>

        <?php if (empty($scheduled)): ?>
            <div class="bk-empty">
                <?= icon('calendar', '40') ?>
                <h4>No Scheduled Backups Yet</h4>
                <p>A backup will be automatically created on your next login at the start of the month.</p>
            </div>
        <?php else: ?>
            <div class="table-wrapper bk-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Backup Name</th>
                            <th>Description</th>
                            <th>Tables</th>
                            <th>Size</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($scheduled as $b): ?>
                        <tr data-backup-id="<?= $b['id'] ?>">
                            <td data-label="Backup Name">
                                <div class="bk-name-cell">
                                    <?= icon('database', '16') ?>
                                    <span><?= htmlspecialchars($b['backup_name']) ?></span>
                                </div>
                            </td>
                            <td data-label="Description"><?= htmlspecialchars($b['description'] ?? '') ?></td>
                            <td data-label="Tables"><span class="bk-badge bk-badge-blue">All Tables</span></td>
                            <td data-label="Size"><?= formatFileSize((int) $b['file_size']) ?></td>
                            <td data-label="Date"><?= date('M j, Y g:i A', strtotime($b['created_at'])) ?></td>
                            <td data-label="Actions">
                                <div class="bk-actions">
                                    <a href="/api/backup_action.php?action=download&id=<?= $b['id'] ?>" class="btn btn-sm btn-primary" title="Download">
                                        <?= icon('download', '14') ?> Download
                                    </a>
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="confirmDelete(<?= $b['id'] ?>, '<?= htmlspecialchars(addslashes($b['backup_name'])) ?>')" title="Delete">
                                        <?= icon('trash', '14') ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tab: Recently Deleted -->
    <div class="bk-tab-panel" id="panel-deleted">
        <div class="bk-panel-header">
            <div class="bk-panel-info bk-panel-info-warning">
                <?= icon('alert-circle', '16') ?>
                <span>These are automatic snapshots created before each Danger Zone action. You can restore them to recover deleted data.</span>
            </div>
        </div>

        <?php if (empty($preDeletion)): ?>
            <div class="bk-empty">
                <?= icon('check-circle', '40') ?>
                <h4>No Deleted Data</h4>
                <p>When you perform a Danger Zone action, a snapshot of the affected data is saved here for recovery.</p>
            </div>
        <?php else: ?>
            <div class="table-wrapper bk-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Tables Affected</th>
                            <th>Size</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preDeletion as $b): ?>
                        <tr data-backup-id="<?= $b['id'] ?>">
                            <td data-label="Description">
                                <div class="bk-name-cell">
                                    <?= icon('alert-triangle', '16') ?>
                                    <span><?= htmlspecialchars($b['description'] ?? $b['backup_name']) ?></span>
                                </div>
                            </td>
                            <td data-label="Tables Affected">
                                <?php
                                $tables = explode(', ', $b['tables_included'] ?? '');
                                foreach ($tables as $t):
                                    $t = trim($t);
                                    if ($t):
                                ?>
                                    <span class="bk-badge bk-badge-orange"><?= htmlspecialchars($t) ?></span>
                                <?php endif; endforeach; ?>
                            </td>
                            <td data-label="Size"><?= formatFileSize((int) $b['file_size']) ?></td>
                            <td data-label="Date"><?= date('M j, Y g:i A', strtotime($b['created_at'])) ?></td>
                            <td data-label="Actions">
                                <div class="bk-actions">
                                    <button type="button" class="btn btn-sm btn-success" onclick="confirmRestore(<?= $b['id'] ?>, '<?= htmlspecialchars(addslashes($b['description'] ?? $b['backup_name'])) ?>')" title="Restore">
                                        <?= icon('refresh-cw', '14') ?> Restore
                                    </button>
                                    <a href="/api/backup_action.php?action=download&id=<?= $b['id'] ?>" class="btn btn-sm btn-primary" title="Download">
                                        <?= icon('download', '14') ?>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="confirmDelete(<?= $b['id'] ?>, '<?= htmlspecialchars(addslashes($b['backup_name'])) ?>')" title="Delete">
                                        <?= icon('trash', '14') ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tab: Manual Backups -->
    <div class="bk-tab-panel" id="panel-manual">
        <div class="bk-panel-header">
            <button type="button" class="btn btn-primary" id="btn-create-manual" onclick="createManualBackup()">
                <?= icon('plus', '16') ?> Create Manual Backup
            </button>
        </div>

        <?php if (empty($manual)): ?>
            <div class="bk-empty" id="manual-empty">
                <?= icon('archive', '40') ?>
                <h4>No Manual Backups</h4>
                <p>Click the button above to create a full database backup at any time.</p>
            </div>
        <?php else: ?>
            <div class="table-wrapper bk-table-wrapper" id="manual-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Backup Name</th>
                            <th>Description</th>
                            <th>Tables</th>
                            <th>Size</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="manual-table-body">
                        <?php foreach ($manual as $b): ?>
                        <tr data-backup-id="<?= $b['id'] ?>">
                            <td data-label="Backup Name">
                                <div class="bk-name-cell">
                                    <?= icon('archive', '16') ?>
                                    <span><?= htmlspecialchars($b['backup_name']) ?></span>
                                </div>
                            </td>
                            <td data-label="Description"><?= htmlspecialchars($b['description'] ?? '') ?></td>
                            <td data-label="Tables"><span class="bk-badge bk-badge-green">All Tables</span></td>
                            <td data-label="Size"><?= formatFileSize((int) $b['file_size']) ?></td>
                            <td data-label="Date"><?= date('M j, Y g:i A', strtotime($b['created_at'])) ?></td>
                            <td data-label="Actions">
                                <div class="bk-actions">
                                    <a href="/api/backup_action.php?action=download&id=<?= $b['id'] ?>" class="btn btn-sm btn-primary" title="Download">
                                        <?= icon('download', '14') ?> Download
                                    </a>
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="confirmDelete(<?= $b['id'] ?>, '<?= htmlspecialchars(addslashes($b['backup_name'])) ?>')" title="Delete">
                                        <?= icon('trash', '14') ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<!-- Restore Password Modal -->
<div class="modal-overlay" id="modal-restore">
    <div class="modal" style="max-width:460px;">
        <div class="modal-header">
            <h3><?= icon('refresh-cw', '20') ?> Restore Backup</h3>
            <button type="button" class="btn-close" onclick="closeModal('modal-restore')"><?= icon('x', '18') ?></button>
        </div>
        <div class="modal-body">
            <div class="bk-restore-warning">
                <?= icon('alert-triangle', '18') ?>
                <div>
                    <strong>This will restore data from the selected backup.</strong>
                    <p id="restore-desc"></p>
                </div>
            </div>
            <div id="restore-error" class="dz-field-error" style="display:none;"></div>
            <div class="form-group" style="margin-top:1rem;">
                <label for="restore-password"><?= icon('lock', '14') ?> Enter your password to confirm</label>
                <input type="password" id="restore-password" class="form-control" placeholder="Super Admin password" autocomplete="off">
            </div>
        </div>
        <div style="display:flex; gap:0.75rem; justify-content:flex-end; padding:0 1.5rem 1.5rem;">
            <button type="button" class="btn btn-secondary" onclick="closeModal('modal-restore')">Cancel</button>
            <button type="button" class="btn btn-success" id="btn-restore-submit" onclick="submitRestore()">
                <?= icon('refresh-cw', '16') ?> Restore Now
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    var BASE = '';
    var restoreId = null;

    // Tab switching
    document.querySelectorAll('.bk-tab').forEach(function(tab) {
        tab.addEventListener('click', function() {
            document.querySelectorAll('.bk-tab').forEach(function(t) { t.classList.remove('active'); });
            document.querySelectorAll('.bk-tab-panel').forEach(function(p) { p.classList.remove('active'); });
            tab.classList.add('active');
            document.getElementById('panel-' + tab.dataset.tab).classList.add('active');
        });
    });

    // Modal helpers
    window.closeModal = function(id) {
        document.getElementById(id).classList.remove('show');
    };

    // Create manual backup
    window.createManualBackup = function() {
        var btn = document.getElementById('btn-create-manual');
        btn.disabled = true;
        btn.innerHTML = '<?= icon("clock", "16") ?> Creating backup...';

        fetch(BASE + '/api/backup_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'create_manual' })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.innerHTML = '<?= icon("plus", "16") ?> Create Manual Backup';
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(function() { location.reload(); }, 800);
            } else {
                showToast(data.message || 'Failed to create backup.', 'error');
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.innerHTML = '<?= icon("plus", "16") ?> Create Manual Backup';
            showToast('Network error.', 'error');
        });
    };

    // Delete backup
    window.confirmDelete = function(id, name) {
        if (typeof confirmAction === 'function') {
            confirmAction('Are you sure you want to permanently delete the backup "' + name + '"?', {
                title: 'Delete Backup',
                confirmText: 'Delete',
                variant: 'danger'
            }).then(function(ok) {
                if (ok) doDelete(id);
            });
        } else if (confirm('Delete backup "' + name + '"?')) {
            doDelete(id);
        }
    };

    function doDelete(id) {
        fetch(BASE + '/api/backup_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'delete', backup_id: id })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                showToast('Backup deleted.', 'success');
                var row = document.querySelector('tr[data-backup-id="' + id + '"]');
                if (row) row.remove();
                updateTabCounts();
            } else {
                showToast(data.message || 'Failed to delete.', 'error');
            }
        })
        .catch(function() { showToast('Network error.', 'error'); });
    }

    // Restore backup
    window.confirmRestore = function(id, desc) {
        restoreId = id;
        document.getElementById('restore-desc').textContent = desc;
        document.getElementById('restore-password').value = '';
        document.getElementById('restore-error').style.display = 'none';
        document.getElementById('btn-restore-submit').disabled = false;
        document.getElementById('btn-restore-submit').innerHTML = '<?= icon("refresh-cw", "16") ?> Restore Now';
        document.getElementById('modal-restore').classList.add('show');
    };

    window.submitRestore = function() {
        var pw = document.getElementById('restore-password').value;
        var errEl = document.getElementById('restore-error');
        var btn = document.getElementById('btn-restore-submit');

        if (!pw) {
            errEl.textContent = 'Password is required.';
            errEl.style.display = 'block';
            return;
        }

        errEl.style.display = 'none';
        btn.disabled = true;
        btn.innerHTML = '<?= icon("refresh-cw", "16") ?> Restoring...';

        fetch(BASE + '/api/backup_action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'restore', backup_id: restoreId, password: pw })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                closeModal('modal-restore');
                showToast(data.message, 'success');
            } else {
                btn.disabled = false;
                btn.innerHTML = '<?= icon("refresh-cw", "16") ?> Restore Now';
                errEl.textContent = data.message;
                errEl.style.display = 'block';
            }
        })
        .catch(function() {
            btn.disabled = false;
            btn.innerHTML = '<?= icon("refresh-cw", "16") ?> Restore Now';
            showToast('Network error.', 'error');
        });
    };

    document.getElementById('restore-password').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') submitRestore();
    });

    document.getElementById('modal-restore').addEventListener('click', function(e) {
        if (e.target === this) closeModal('modal-restore');
    });

    function updateTabCounts() {
        var panels = {
            scheduled: document.querySelectorAll('#panel-scheduled tbody tr').length,
            deleted: document.querySelectorAll('#panel-deleted tbody tr').length,
            manual: document.querySelectorAll('#panel-manual tbody tr').length
        };
        document.querySelectorAll('.bk-tab').forEach(function(tab) {
            var key = tab.dataset.tab;
            var countEl = tab.querySelector('.bk-tab-count');
            if (countEl && panels[key] !== undefined) {
                countEl.textContent = panels[key];
            }
        });
    }
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
