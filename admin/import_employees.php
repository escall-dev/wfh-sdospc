<?php
$pageTitle = 'Import Employees';
$currentPage = 'employees';

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
requireAdmin();

$imported = 0;
$skipped = 0;
$errors = [];
$didImport = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'import') {
    $didImport = true;

    try {
        $dtrPdo = new PDO(
            "mysql:host=localhost;dbname=dtr_wfh;charset=utf8mb4",
            'root', '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (PDOException $e) {
        $errors[] = 'Cannot connect to dtr_wfh database. Make sure it is imported.';
        $dtrPdo = null;
    }

    if ($dtrPdo) {
        $dtrEmployees = $dtrPdo->query("SELECT employee_number, employee_name, functional_division, id_picture FROM employees ORDER BY id ASC")->fetchAll();

        foreach ($dtrEmployees as $emp) {
            $empNumber = trim($emp['employee_number']);
            $empName = trim($emp['employee_name']);
            $funcDiv = $emp['functional_division'] ?: null;
            $idPic = $emp['id_picture'] ?: null;

            if (empty($empNumber) || empty($empName)) {
                $skipped++;
                continue;
            }

            $check = $pdo->prepare("SELECT id FROM users WHERE employee_id = :eid");
            $check->execute([':eid' => $empNumber]);
            if ($check->fetch()) {
                $pdo->prepare("UPDATE users SET functional_division = :fd, id_picture = :ip WHERE employee_id = :eid")
                    ->execute([':fd' => $funcDiv, ':ip' => $idPic, ':eid' => $empNumber]);
                $skipped++;
                continue;
            }

            $nameParts = explode(' ', $empName);
            $lastName = end($nameParts);

            $hash = password_hash($empNumber, PASSWORD_DEFAULT);

            try {
                $ins = $pdo->prepare("INSERT INTO users (employee_id, last_name, full_name, functional_division, id_picture, role, password, is_active, must_change_password) VALUES (:eid, :ln, :fn, :fd, :ip, 'employee', :pw, 1, 1)");
                $ins->execute([
                    ':eid' => $empNumber,
                    ':ln' => $lastName,
                    ':fn' => $empName,
                    ':fd' => $funcDiv,
                    ':ip' => $idPic,
                    ':pw' => $hash
                ]);
                $imported++;
            } catch (PDOException $e) {
                $errors[] = "Failed to import {$empName} ({$empNumber}): " . $e->getMessage();
            }
        }
    }
}

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'employee'")->fetchColumn();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="main-content">
    <div class="top-bar">
        <div class="page-title">
            <span class="page-icon"><?= icon('import', '24') ?></span>
            <div>
                <h2>Import Employees</h2>
                <p style="font-size:0.8rem;color:var(--text-light);">Import employees from DTR/WFH database</p>
            </div>
        </div>
        <a href="/admin/employees.php" class="btn btn-secondary" style="width:auto;">
            <?= icon('arrow-right', '16') ?> Back to Employees
        </a>
    </div>

    <div class="card" style="margin-bottom:1.5rem;background:var(--info-bg);border-left:4px solid var(--info);">
        <div style="display:flex;gap:0.75rem;align-items:flex-start;">
            <span style="color:var(--info);flex-shrink:0;margin-top:2px;"><?= icon('info', '18') ?></span>
            <div style="font-size:0.85rem;color:var(--text-dark);line-height:1.6;">
                <p>This will import all employees from the <strong>dtr_wfh.employees</strong> table into the system.</p>
                <ul style="margin:0.5rem 0 0 1rem;">
                    <li>Each employee gets an account with their <strong>Employee Number as the default password</strong></li>
                    <li>They will be required to change their password on first login</li>
                    <li>Functional division and ID picture data will be imported</li>
                    <li>Existing employees (by Employee ID) will have their division and picture updated but won't be duplicated</li>
                </ul>
            </div>
        </div>
    </div>

    <?php if ($didImport): ?>
        <div class="card" style="margin-bottom:1.5rem;border-left:4px solid var(--success);background:var(--success-bg);">
            <h3 style="margin-bottom:0.5rem;"><?= icon('check-circle', '18') ?> Import Complete</h3>
            <div style="font-size:0.85rem;line-height:1.8;">
                <p><strong><?= $imported ?></strong> employees imported successfully</p>
                <p><strong><?= $skipped ?></strong> employees skipped (already exist or invalid)</p>
                <?php if (!empty($errors)): ?>
                    <div style="margin-top:0.5rem;color:var(--danger);">
                        <?php foreach ($errors as $err): ?>
                            <p><?= htmlspecialchars($err) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <div class="card-title"><?= icon('employees', '18') ?> Import from DTR Database</div>
            <span style="font-size:0.78rem;color:var(--text-light)">Current employees in system: <?= $totalUsers ?></span>
        </div>
        <form method="POST" style="margin-top:1rem;">
            <input type="hidden" name="action" value="import">
            <button type="submit" class="btn btn-primary" style="width:auto;" onclick="return confirm('This will import all employees from dtr_wfh database. Existing employees will have their division/picture updated. Continue?')">
                <?= icon('import', '16') ?> Import All Employees from DTR Database
            </button>
        </form>
    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
