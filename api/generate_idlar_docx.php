<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAdminOrHr();

$month = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$year = isset($_GET['year']) ? (int)$_GET['year'] : 0;
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($month < 1 || $month > 12 || $year < 2024 || $userId <= 0) {
    die('Invalid parameters.');
}

// Get user info
$userStmt = $pdo->prepare("SELECT full_name, functional_division, position, recommending FROM users WHERE id = :uid AND role = 'employee'");
$userStmt->execute([':uid' => $userId]);
$user = $userStmt->fetch();
if (!$user) {
    die('Employee not found.');
}
$fullName = $user['full_name'];
$division = $user['functional_division'] ?? '';
$position = $user['position'] ?? '';
$recommendingPosition = $user['recommending'] ?? '';

// Map recommending short codes to actual position titles stored in users table (for DB lookup)
$recommendingPositionMap = [
    'AO V - ADMINISTRATIVE' => 'ADMINISTRATIVE OFFICER V - ADMINISTRATIVE',
    'SGOD CHIEF'            => 'CHIEF EDUCATION SUPERVISOR, SGOD',
    'CID CHIEF'             => 'CHIEF EDUCATION SUPERVISOR, CID',
];

// Display labels for ${POSITIONRECO} in the document
$recommendingDisplayMap = [
    'AO V - ADMINISTRATIVE' => 'Administrative Officer V - Administrative',
    'SGOD CHIEF'            => 'Chief Education Supervisor, SGOD',
    'CID CHIEF'             => 'Chief Education Supervisor, CID',
];

// Look up the full name of the person whose position matches the recommending value
$recoName = '';
$lookupPosition = '';
$displayPosition = '';
if ($recommendingPosition !== '') {
    $lookupPosition  = $recommendingPositionMap[$recommendingPosition] ?? $recommendingPosition;
    $displayPosition = $recommendingDisplayMap[$recommendingPosition] ?? $lookupPosition;
    $recoStmt = $pdo->prepare("SELECT full_name FROM users WHERE position = :pos LIMIT 1");
    $recoStmt->execute([':pos' => $lookupPosition]);
    $recoRow = $recoStmt->fetch();
    if ($recoRow) {
        $recoName = $recoRow['full_name'];
    }
}

$logs = getAttendanceForMonth($pdo, $userId, $month, $year);

$logAccomplishments = [];
foreach ($logs as $log) {
    $logAccomplishments[$log['id']] = getAccomplishmentsForLog($pdo, $log['id']);
}

$monthName = date('F', mktime(0, 0, 0, $month, 1));

// Load the DOCX template
$templatePath = __DIR__ . '/../assets/annex/Annex-D-Individual-Daily-Logs-and-Accomplishment-Report-IDLAR.docx';
if (!file_exists($templatePath)) {
    die('IDLAR template not found.');
}

$templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);

// Fill header placeholders
$templateProcessor->setValue('NOE', $fullName);
$templateProcessor->setValue('POSITION', $position);
$templateProcessor->setValue('OFFICEDIV', $division);
$templateProcessor->setValue('RECO', $recoName);
$templateProcessor->setValue('POSITIONRECO', $displayPosition);

// Always show the full month range
$daysInMonth  = (int)date('t', mktime(0, 0, 0, $month, 1, $year));
$datesCovered = date('F j, Y', mktime(0, 0, 0, $month, 1, $year))
              . ' - '
              . date('F j, Y', mktime(0, 0, 0, $month, $daysInMonth, $year));
$templateProcessor->setValue('DATESCOVERED', $datesCovered);

// Index existing logs by date for quick lookup
$logsByDate = [];
foreach ($logs as $log) {
    $logsByDate[$log['date']] = $log;
}

// Build one row for every Friday of the month
$allDays = [];
for ($d = 1; $d <= $daysInMonth; $d++) {
    $ts = mktime(0, 0, 0, $month, $d, $year);
    if ((int)date('N', $ts) === 5) { // 5 = Friday
        $allDays[] = sprintf('%04d-%02d-%02d', $year, $month, $d);
    }
}

$rowCount = count($allDays);
$templateProcessor->cloneRow('DATE', $rowCount);

foreach ($allDays as $i => $dateStr) {
    $rowNum = $i + 1;
    $log    = $logsByDate[$dateStr] ?? null;

    $dateLabel = date('M j, Y (D)', strtotime($dateStr));

    if ($log) {
        $timeIn   = formatTimeDisplay($log['time_in']);
        $timeOut  = formatTimeDisplay($log['time_out']);
        $lunchOut = formatTimeDisplay($log['lunch_out']);
        $lunchIn  = formatTimeDisplay($log['lunch_in']);

        $timeLogs = "Time In:     {$timeIn}\n"
                  . "Lunch Out:   {$lunchOut}\n"
                  . "Lunch In:    {$lunchIn}\n"
                  . "Time Out:    {$timeOut}";

        $accTexts = [];
        if (!empty($logAccomplishments[$log['id']])) {
            foreach ($logAccomplishments[$log['id']] as $acc) {
                $accTexts[] = '- ' . $acc['item_text'];
            }
        }
        $accStr = !empty($accTexts) ? implode("\n", $accTexts) : '';
    } else {
        $timeLogs = '';
        $accStr   = '';
    }

    $templateProcessor->setValue("DATE#{$rowNum}", $dateLabel);
    $templateProcessor->setValue("TIMELOGS#{$rowNum}", $timeLogs);
    $templateProcessor->setValue("ACCOMPLISHMENT#{$rowNum}", $accStr);
}

// Stream the filled DOCX directly for download
$safeFullName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $fullName);
$docxFilename = "IDLAR_{$safeFullName}_{$monthName}_{$year}.docx";
$tmpFile = tempnam(sys_get_temp_dir(), 'idlar_') . '.docx';
$templateProcessor->saveAs($tmpFile);

header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $docxFilename . '"');
header('Content-Length: ' . filesize($tmpFile));
header('Cache-Control: private, max-age=0, must-revalidate');

readfile($tmpFile);
@unlink($tmpFile);
exit;
