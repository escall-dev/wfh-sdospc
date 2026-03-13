<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdminOrHr();

$month  = isset($_GET['month'])   ? (int)$_GET['month']   : 0;
$year   = isset($_GET['year'])    ? (int)$_GET['year']     : 0;
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($month < 1 || $month > 12 || $year < 2024 || $userId <= 0) {
    die('Invalid parameters.');
}

// Get user info
$userStmt = $pdo->prepare("SELECT full_name, functional_division, position FROM users WHERE id = :uid AND role = 'employee'");
$userStmt->execute([':uid' => $userId]);
$user = $userStmt->fetch();
if (!$user) {
    die('Employee not found.');
}
$fullName  = $user['full_name'];
$position  = $user['position'] ?? '';
$monthName = date('F', mktime(0, 0, 0, $month, 1));

// Fetch all logs for the month, index by date
$logs = getAttendanceForMonth($pdo, $userId, $month, $year);
$logsByDate = [];
foreach ($logs as $log) {
    $logsByDate[$log['date']] = $log;
}

// Fetch OB entries for the month, grouped by date
$obRecords = getOBForMonth($pdo, $userId, $month, $year);
$obByDate = [];
foreach ($obRecords as $ob) {
    $obByDate[$ob['ob_date']][] = $ob;
}

$daysInMonth = (int)date('t', mktime(0, 0, 0, $month, 1, $year));

// Build one row per day for the full month
$allDays = [];
for ($d = 1; $d <= $daysInMonth; $d++) {
    $dateKey = sprintf('%04d-%02d-%02d', $year, $month, $d);
    $log = $logsByDate[$dateKey] ?? null;
    $dayOBs = $obByDate[$dateKey] ?? [];

    $row = [
        'day'        => (string)$d,
        'am_arrival' => $log && $log['time_in']   ? date('h:i', strtotime($log['time_in']))   : '',
        'am_depart'  => $log && $log['lunch_out'] ? date('h:i', strtotime($log['lunch_out'])) : '',
        'pm_arrival' => $log && $log['lunch_in']  ? date('h:i', strtotime($log['lunch_in']))  : '',
        'pm_depart'  => $log && $log['time_out']  ? date('h:i', strtotime($log['time_out']))  : '',
    ];

    // Overlay OB markers on time columns (display-only, no undertime impact)
    foreach ($dayOBs as $ob) {
        $obFrom = $ob['time_from'];
        $obTo   = $ob['time_to'];
        // OB covers AM period (any part before 12:00)
        if ($obFrom < '12:00:00') {
            if (!$row['am_arrival']) $row['am_arrival'] = 'OB';
            if (!$row['am_depart'])  $row['am_depart']  = 'OB';
        }
        // OB covers PM period (any part from 12:00 onwards)
        if ($obTo > '12:00:00' && $obFrom >= '12:00:00' || ($obTo > '13:00:00' && $obFrom < '12:00:00')) {
            if (!$row['pm_arrival']) $row['pm_arrival'] = 'OB';
            if (!$row['pm_depart'])  $row['pm_depart']  = 'OB';
        }
    }

    // Undertime: minutes short of 8 hours on logged days
    $undertimeMin = 0;
    if ($log && $log['total_hours']) {
        $short = max(0, 8 - (float)$log['total_hours']);
        $undertimeMin = (int)round($short * 60);
    }
    $row['under_hr']  = $undertimeMin > 0 ? (string)intdiv($undertimeMin, 60) : '';
    $row['under_min'] = $undertimeMin > 0 ? (string)($undertimeMin % 60)      : '';

    $allDays[] = $row;
}

// Total undertime for the month
$totalUndertimeMin = 0;
foreach ($logsByDate as $log) {
    if ($log['total_hours']) {
        $short = max(0, 8 - (float)$log['total_hours']);
        $totalUndertimeMin += (int)round($short * 60);
    }
}
$totalUndertimeStr = $totalUndertimeMin > 0
    ? intdiv($totalUndertimeMin, 60) . 'h ' . ($totalUndertimeMin % 60) . 'm'
    : '0';

// Load template
$templatePath = __DIR__ . '/../assets/annex/DailyTimeRecordFullMonth.docx';
if (!file_exists($templatePath)) {
    die('DTR template not found.');
}

$tp = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);

// Header placeholders
$tp->setValue('Name',          $fullName);
$tp->setValue('MonthCutoff',   $monthName . ' ' . $year);
$tp->setValue('totalUndertime', $totalUndertimeStr);

// Clone both columns with all days of the month — both copies are identical
$tp->cloneRow('day', $daysInMonth);
foreach ($allDays as $i => $row) {
    $n = $i + 1;
    $tp->setValue("day#{$n}",        $row['day']);
    $tp->setValue("am_arrival#{$n}", $row['am_arrival']);
    $tp->setValue("am_depart#{$n}",  $row['am_depart']);
    $tp->setValue("pm_arrival#{$n}", $row['pm_arrival']);
    $tp->setValue("pm_depart#{$n}",  $row['pm_depart']);
    $tp->setValue("under_hr#{$n}",   $row['under_hr']);
    $tp->setValue("under_min#{$n}",  $row['under_min']);
}

$tp->cloneRow('dayb', $daysInMonth);
foreach ($allDays as $i => $row) {
    $n = $i + 1;
    $tp->setValue("dayb#{$n}",        $row['day']);
    $tp->setValue("am_arrivalb#{$n}", $row['am_arrival']);
    $tp->setValue("am_departb#{$n}",  $row['am_depart']);
    $tp->setValue("pm_arrivalb#{$n}", $row['pm_arrival']);
    $tp->setValue("pm_departb#{$n}",  $row['pm_depart']);
    $tp->setValue("under_hrb#{$n}",   $row['under_hr']);
    $tp->setValue("under_minb#{$n}",  $row['under_min']);
}

// Stream the filled DOCX directly for download
$safeFullName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $fullName);
$docxFilename = "DTR_{$safeFullName}_{$monthName}_{$year}.docx";
$tmpFile = tempnam(sys_get_temp_dir(), 'dtr_') . '.docx';
$tp->saveAs($tmpFile);

header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $docxFilename . '"');
header('Content-Length: ' . filesize($tmpFile));
header('Cache-Control: private, max-age=0, must-revalidate');

readfile($tmpFile);
@unlink($tmpFile);
exit;
