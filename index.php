<?php
require_once __DIR__ . '/includes/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$role = getCurrentUserRole();
if (in_array($role, ['admin', 'superadmin', 'hr_timekeeping'])) {
    header('Location: /admin/dashboard.php');
} else {
    header('Location: /employee/dashboard.php');
}
exit;
