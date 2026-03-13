<?php
if (!isset($pageTitle)) $pageTitle = 'WFH Attendance Portal';
if (!isset($currentPage)) $currentPage = '';

if (isset($pdo) && function_exists('isSuperAdmin') && isSuperAdmin()) {
    $checkMonth = $_SESSION['backup_checked_this_month'] ?? '';
    if ($checkMonth !== date('Y-m')) {
        require_once __DIR__ . '/backup_helpers.php';
        try {
            checkAndRunScheduledBackup($pdo, getCurrentUserId());
        } catch (Exception $e) { /* silent */ }
        $_SESSION['backup_checked_this_month'] = date('Y-m');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - SDO San Pedro City</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= filemtime($_SERVER['DOCUMENT_ROOT'] . '/assets/css/style.css') ?>">
    <link rel="icon" href="/assets/img-ref/SDO_Sanpedro_Logo.png">
</head>
<body>
<div class="app-layout">
