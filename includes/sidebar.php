<?php
require_once __DIR__ . '/icons.php';

$role = getCurrentUserRole();
$fullName = getCurrentUserName();
$initials = '';
$nameParts = explode(' ', $fullName ?? '');
if (count($nameParts) >= 2) {
    $initials = strtoupper(substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1));
} elseif (count($nameParts) === 1) {
    $initials = strtoupper(substr($nameParts[0], 0, 2));
}
$_sidebarPicUrl = getProfilePictureUrl(getCurrentUserPicture());
$_profileUrl = in_array($role, ['admin', 'superadmin', 'hr_timekeeping']) ? '/admin/profile.php' : '/employee/change_password.php';

$employeeNav = [
    ['url' => '/employee/dashboard.php', 'icon' => 'dashboard', 'label' => 'Dashboard', 'key' => 'dashboard'],
    ['url' => '/employee/clock.php', 'icon' => 'clock', 'label' => 'Clock In/Out', 'key' => 'clock'],
    ['url' => '/employee/history.php', 'icon' => 'history', 'label' => 'Attendance History', 'key' => 'history'],
    ['url' => '/employee/idlar.php', 'icon' => 'idlar', 'label' => 'Monthly IDLAR', 'key' => 'idlar'],
];

$_myAttendanceItems = [
    ['divider' => true, 'label' => 'My Attendance'],
    ['url' => '/employee/clock.php', 'icon' => 'clock', 'label' => 'Clock In/Out', 'key' => 'clock'],
    ['url' => '/employee/history.php', 'icon' => 'history', 'label' => 'Attendance History', 'key' => 'history'],
    ['url' => '/employee/idlar.php', 'icon' => 'idlar', 'label' => 'Monthly IDLAR', 'key' => 'idlar'],
];

$adminNav = [
    ['url' => '/admin/dashboard.php', 'icon' => 'dashboard', 'label' => 'Dashboard', 'key' => 'dashboard'],
    ['url' => '/admin/employees.php', 'icon' => 'employees', 'label' => 'Employees', 'key' => 'employees'],
    ['url' => '/admin/reports.php', 'icon' => 'reports', 'label' => 'Reports', 'key' => 'reports'],
    ['url' => '/admin/analytics.php', 'icon' => 'trending-up', 'label' => 'Analytics', 'key' => 'analytics'],
    ['url' => '/admin/profile.php', 'icon' => 'user', 'label' => 'My Profile', 'key' => 'profile'],
];

if ($role === 'superadmin') {
    $adminNav[] = ['url' => '/admin/login_logs.php', 'icon' => 'mac-log', 'label' => 'Login Logs', 'key' => 'login_logs'];
    $adminNav[] = ['url' => '/admin/backup_restore.php', 'icon' => 'hard-drive', 'label' => 'Backup & Restore', 'key' => 'backup_restore'];
    $adminNav[] = ['url' => '/admin/danger_zone.php', 'icon' => 'alert-triangle', 'label' => 'Danger Zone', 'key' => 'danger_zone'];
} else {
    // admin role: also an employee — can clock in/out, submit IDLAR, etc.
    $adminNav = array_merge($adminNav, $_myAttendanceItems);
}

$hrNav = [
    ['url' => '/admin/dashboard.php', 'icon' => 'dashboard', 'label' => 'Dashboard', 'key' => 'dashboard'],
    ['url' => '/admin/employees.php', 'icon' => 'employees', 'label' => 'Employees', 'key' => 'employees'],
    ['url' => '/admin/reports.php', 'icon' => 'reports', 'label' => 'Reports', 'key' => 'reports'],
    ['url' => '/admin/analytics.php', 'icon' => 'trending-up', 'label' => 'Analytics', 'key' => 'analytics'],
    ['url' => '/admin/profile.php', 'icon' => 'user', 'label' => 'My Profile', 'key' => 'profile'],
    ...$_myAttendanceItems,
];

if ($role === 'hr_timekeeping') {
    $navItems = $hrNav;
} elseif (in_array($role, ['admin', 'superadmin'])) {
    $navItems = $adminNav;
} else {
    $navItems = $employeeNav;
}

$_roleLabels = ['superadmin' => 'Super Admin', 'hr_timekeeping' => 'HR Timekeeping'];
if (isset($_roleLabels[$role])) {
    $_roleDisplay = $_roleLabels[$role];
} elseif ($role === 'employee' || $role === 'admin') {
    $_position = getCurrentUserPosition();
    $_division = getCurrentUserDivision();
    if ($_position && $_division) {
        $_roleDisplay = htmlspecialchars($_position) . ' &bull; ' . htmlspecialchars($_division);
    } elseif ($_position) {
        $_roleDisplay = htmlspecialchars($_position);
    } elseif ($_division) {
        $_roleDisplay = htmlspecialchars($_division);
    } else {
        $_roleDisplay = ucfirst($role);
    }
    unset($_position, $_division);
} else {
    $_roleDisplay = ucfirst($role ?? '');
}
?>

<!-- Desktop Sidebar -->
<aside class="sidebar">
    <div class="sidebar-header">
        <img src="/assets/img-ref/SDO_Sanpedro_Logo.png" alt="SDO San Pedro City" class="sidebar-logo">
        <h3>SDO San Pedro City</h3>
        <span class="sidebar-subtitle">Work From Home</span>
    </div>
    <nav class="sidebar-nav">
        <?php foreach ($navItems as $item): ?>
            <?php if (!empty($item['divider'])): ?>
                <div class="nav-section-label"><?= htmlspecialchars($item['label']) ?></div>
            <?php else: ?>
                <a href="<?= $item['url'] ?>" class="nav-item <?= ($currentPage === $item['key']) ? 'active' : '' ?>">
                    <span class="nav-icon"><?= icon($item['icon'], '19') ?></span>
                    <?= $item['label'] ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
        <a href="<?= $_profileUrl ?>" class="sidebar-user sidebar-user-link">
            <?php if ($_sidebarPicUrl): ?>
                <img src="<?= $_sidebarPicUrl ?>" alt="" class="user-avatar user-avatar-img">
            <?php else: ?>
                <div class="user-avatar"><?= $initials ?></div>
            <?php endif; ?>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($fullName ?? '') ?></div>
                <div class="user-role"><?= $_roleDisplay ?></div>
            </div>
        </a>
        <a href="/logout.php" class="nav-item" style="margin-top: 0.5rem; padding-left: 0;">
            <span class="nav-icon"><?= icon('logout', '19') ?></span> Logout
        </a>
    </div>
</aside>

<?php ob_start(); ?>
<!-- Mobile Bottom Navigation -->
<nav class="bottom-nav">
    <div class="bottom-nav-inner <?= $role === 'superadmin' ? 'bottom-nav-wide' : '' ?>">
        <?php if ($role === 'employee'): ?>
            <a href="/employee/dashboard.php" class="bottom-nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <span class="bnav-icon"><?= icon('dashboard', '22') ?></span>
                Dashboard
            </a>
            <a href="/employee/idlar.php" class="bottom-nav-item <?= $currentPage === 'idlar' ? 'active' : '' ?>">
                <span class="bnav-icon"><?= icon('idlar', '22') ?></span>
                IDLAR
            </a>
            <a href="/employee/clock.php" class="bottom-nav-item <?= $currentPage === 'clock' ? 'active' : '' ?>">
                <span class="bnav-icon"><?= icon('clock', '22') ?></span>
                Clock In/Out
            </a>
            <a href="/employee/history.php" class="bottom-nav-item <?= $currentPage === 'history' ? 'active' : '' ?>">
                <span class="bnav-icon"><?= icon('history', '22') ?></span>
                History
            </a>
            <a href="/logout.php" class="bottom-nav-item">
                <span class="bnav-icon"><?= icon('logout', '22') ?></span>
                Logout
            </a>
        <?php elseif ($role === 'hr_timekeeping'): ?>
            <a href="/admin/dashboard.php" class="bottom-nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <span class="bnav-icon"><?= icon('dashboard', '22') ?></span>
                Dashboard
            </a>
            <a href="/admin/reports.php" class="bottom-nav-item <?= $currentPage === 'reports' ? 'active' : '' ?>">
                <span class="bnav-icon"><?= icon('reports', '22') ?></span>
                Reports
            </a>
            <a href="/employee/clock.php" class="bottom-nav-item <?= $currentPage === 'clock' ? 'active' : '' ?>">
                <span class="bnav-icon"><?= icon('clock', '22') ?></span>
                Clock In/Out
            </a>
            <a href="/employee/idlar.php" class="bottom-nav-item <?= $currentPage === 'idlar' ? 'active' : '' ?>">
                <span class="bnav-icon"><?= icon('idlar', '22') ?></span>
                IDLAR
            </a>
            <a href="/logout.php" class="bottom-nav-item">
                <span class="bnav-icon"><?= icon('logout', '22') ?></span>
                Logout
            </a>
        <?php elseif ($role === 'superadmin'): ?>
            <a href="/admin/dashboard.php" class="bottom-nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>" data-label="Dashboard" aria-label="Dashboard" title="Dashboard">
                <span class="bnav-icon"><?= icon('dashboard', '22') ?></span>
                <span class="bnav-label">Dashboard</span>
            </a>
            <a href="/admin/employees.php" class="bottom-nav-item <?= $currentPage === 'employees' ? 'active' : '' ?>" data-label="Employees" aria-label="Employees" title="Employees">
                <span class="bnav-icon"><?= icon('employees', '22') ?></span>
                <span class="bnav-label">Employees</span>
            </a>
            <a href="/admin/reports.php" class="bottom-nav-item <?= $currentPage === 'reports' ? 'active' : '' ?>" data-label="Reports" aria-label="Reports" title="Reports">
                <span class="bnav-icon"><?= icon('reports', '22') ?></span>
                <span class="bnav-label">Reports</span>
            </a>
            <a href="/admin/analytics.php" class="bottom-nav-item <?= $currentPage === 'analytics' ? 'active' : '' ?>" data-label="Analytics" aria-label="Analytics" title="Analytics">
                <span class="bnav-icon"><?= icon('trending-up', '22') ?></span>
                <span class="bnav-label">Analytics</span>
            </a>
            <a href="/admin/profile.php" class="bottom-nav-item <?= $currentPage === 'profile' ? 'active' : '' ?>" data-label="My Profile" aria-label="My Profile" title="My Profile">
                <span class="bnav-icon"><?= icon('user', '22') ?></span>
                <span class="bnav-label">My Profile</span>
            </a>
            <a href="/admin/login_logs.php" class="bottom-nav-item <?= $currentPage === 'login_logs' ? 'active' : '' ?>" data-label="Logs" aria-label="Logs" title="Logs">
                <span class="bnav-icon"><?= icon('mac-log', '22') ?></span>
                <span class="bnav-label">Logs</span>
            </a>
            <a href="/admin/backup_restore.php" class="bottom-nav-item <?= $currentPage === 'backup_restore' ? 'active' : '' ?>" data-label="Backup" aria-label="Backup" title="Backup">
                <span class="bnav-icon"><?= icon('hard-drive', '22') ?></span>
                <span class="bnav-label">Backup</span>
            </a>
            <a href="/admin/danger_zone.php" class="bottom-nav-item danger-zone-item <?= $currentPage === 'danger_zone' ? 'active' : '' ?>" data-label="Danger Zone" aria-label="Danger Zone" title="Danger Zone">
                <span class="bnav-icon"><?= icon('alert-triangle', '22') ?></span>
                <span class="bnav-label">Danger Zone</span>
            </a>
            <a href="/logout.php" class="bottom-nav-item" data-label="Logout" aria-label="Logout" title="Logout">
                <span class="bnav-icon"><?= icon('logout', '22') ?></span>
                <span class="bnav-label">Logout</span>
            </a>
        <?php else: ?>
            <a href="/admin/dashboard.php" class="bottom-nav-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                <span class="bnav-icon"><?= icon('dashboard', '22') ?></span>
                Dashboard
            </a>
            <a href="/admin/employees.php" class="bottom-nav-item <?= $currentPage === 'employees' ? 'active' : '' ?>">
                <span class="bnav-icon"><?= icon('employees', '22') ?></span>
                Employees
            </a>
            <a href="/employee/clock.php" class="bottom-nav-item <?= $currentPage === 'clock' ? 'active' : '' ?>">
                <span class="bnav-icon"><?= icon('clock', '22') ?></span>
                Clock In/Out
            </a>
            <a href="/employee/idlar.php" class="bottom-nav-item <?= $currentPage === 'idlar' ? 'active' : '' ?>">
                <span class="bnav-icon"><?= icon('idlar', '22') ?></span>
                IDLAR
            </a>
            <a href="/logout.php" class="bottom-nav-item">
                <span class="bnav-icon"><?= icon('logout', '22') ?></span>
                Logout
            </a>
        <?php endif; ?>
    </div>
</nav>
<?php $_bottomNavHtml = ob_get_clean(); ?>
