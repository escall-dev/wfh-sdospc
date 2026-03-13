<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

requireAdminOrHr();

$month  = isset($_GET['month'])   ? (int)$_GET['month']   : 0;
$year   = isset($_GET['year'])    ? (int)$_GET['year']     : 0;
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

$fullName  = $user['full_name'];
$division  = $user['functional_division'] ?? '';
$position  = $user['position'] ?? '';
$recommendingPosition = $user['recommending'] ?? '';
$monthName = date('F', mktime(0, 0, 0, $month, 1));
$safeFullName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $fullName);

// ─── GENERATE DTR PDF ───────────────────────────────────────────────────────

$logs = getAttendanceForMonth($pdo, $userId, $month, $year);
$logsByDate = [];
foreach ($logs as $log) {
    $logsByDate[$log['date']] = $log;
}
$daysInMonth = (int)date('t', mktime(0, 0, 0, $month, 1, $year));

// Fetch OB entries for the month, grouped by date
$obRecords = getOBForMonth($pdo, $userId, $month, $year);
$obByDate = [];
foreach ($obRecords as $ob) {
    $obByDate[$ob['ob_date']][] = $ob;
}

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
    foreach ($dayOBs as $obEntry) {
        $obFrom = $obEntry['time_from'];
        $obTo   = $obEntry['time_to'];
        if ($obFrom < '12:00:00') {
            if (!$row['am_arrival']) $row['am_arrival'] = 'OB';
            if (!$row['am_depart'])  $row['am_depart']  = 'OB';
        }
        if ($obTo > '12:00:00' && $obFrom >= '12:00:00' || ($obTo > '13:00:00' && $obFrom < '12:00:00')) {
            if (!$row['pm_arrival']) $row['pm_arrival'] = 'OB';
            if (!$row['pm_depart'])  $row['pm_depart']  = 'OB';
        }
    }
    $undertimeMin = 0;
    if ($log && $log['total_hours']) {
        $short = max(0, 8 - (float)$log['total_hours']);
        $undertimeMin = (int)round($short * 60);
    }
    $row['under_hr']  = $undertimeMin > 0 ? (string)intdiv($undertimeMin, 60) : '';
    $row['under_min'] = $undertimeMin > 0 ? (string)($undertimeMin % 60)      : '';
    $allDays[] = $row;
}

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

$dtrTemplatePath = __DIR__ . '/../assets/annex/DailyTimeRecordFullMonth.docx';
if (!file_exists($dtrTemplatePath)) {
    die('DTR template not found.');
}

$tp = new \PhpOffice\PhpWord\TemplateProcessor($dtrTemplatePath);
$tp->setValue('Name',          $fullName);
$tp->setValue('MonthCutoff',   $monthName . ' ' . $year);
$tp->setValue('totalUndertime', $totalUndertimeStr);

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

$dtrDocxPath = tempnam(sys_get_temp_dir(), 'dtr_') . '.docx';
$tp->saveAs($dtrDocxPath);

// ─── GENERATE IDLAR PDF ─────────────────────────────────────────────────────

$recommendingPositionMap = [
    'AO V - ADMINISTRATIVE' => 'ADMINISTRATIVE OFFICER V - ADMINISTRATIVE',
    'SGOD CHIEF'            => 'CHIEF EDUCATION SUPERVISOR, SGOD',
    'CID CHIEF'             => 'CHIEF EDUCATION SUPERVISOR, CID',
];
$recommendingDisplayMap = [
    'AO V - ADMINISTRATIVE' => 'Administrative Officer V - Administrative',
    'SGOD CHIEF'            => 'Chief Education Supervisor, SGOD',
    'CID CHIEF'             => 'Chief Education Supervisor, CID',
];

$recoName = '';
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

$logAccomplishments = [];
foreach ($logs as $log) {
    $logAccomplishments[$log['id']] = getAccomplishmentsForLog($pdo, $log['id']);
}

$idlarTemplatePath = __DIR__ . '/../assets/annex/Annex-D-Individual-Daily-Logs-and-Accomplishment-Report-IDLAR.docx';
if (!file_exists($idlarTemplatePath)) {
    @unlink($dtrDocxPath);
    die('IDLAR template not found.');
}

$idlarTp = new \PhpOffice\PhpWord\TemplateProcessor($idlarTemplatePath);
$idlarTp->setValue('NOE', $fullName);
$idlarTp->setValue('POSITION', $position);
$idlarTp->setValue('OFFICEDIV', $division);
$idlarTp->setValue('RECO', $recoName);
$idlarTp->setValue('POSITIONRECO', $displayPosition);

$datesCovered = date('F j, Y', mktime(0, 0, 0, $month, 1, $year))
    . ' - '
    . date('F j, Y', mktime(0, 0, 0, $month, $daysInMonth, $year));
$idlarTp->setValue('DATESCOVERED', $datesCovered);

$idlarLogsByDate = [];
foreach ($logs as $log) {
    $idlarLogsByDate[$log['date']] = $log;
}

$fridayDays = [];
for ($d = 1; $d <= $daysInMonth; $d++) {
    $ts = mktime(0, 0, 0, $month, $d, $year);
    if ((int)date('N', $ts) === 5) {
        $fridayDays[] = sprintf('%04d-%02d-%02d', $year, $month, $d);
    }
}

$rowCount = count($fridayDays);
$idlarTp->cloneRow('DATE', $rowCount);

foreach ($fridayDays as $i => $dateStr) {
    $rowNum = $i + 1;
    $log = $idlarLogsByDate[$dateStr] ?? null;
    $dateLabel = date('M j, Y (D)', strtotime($dateStr));

    if ($log) {
        $timeIn   = formatTimeDisplay($log['time_in']);
        $timeOut  = formatTimeDisplay($log['time_out']);
        $lunchOut = formatTimeDisplay($log['lunch_out']);
        $lunchIn  = formatTimeDisplay($log['lunch_in']);
        $timeLogs = "Time In:     {$timeIn}\nLunch Out:   {$lunchOut}\nLunch In:    {$lunchIn}\nTime Out:    {$timeOut}";

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

    $idlarTp->setValue("DATE#{$rowNum}", $dateLabel);
    $idlarTp->setValue("TIMELOGS#{$rowNum}", $timeLogs);
    $idlarTp->setValue("ACCOMPLISHMENT#{$rowNum}", $accStr);
}

$idlarDocxPath = tempnam(sys_get_temp_dir(), 'idlar_') . '.docx';
$idlarTp->saveAs($idlarDocxPath);

// ─── MERGE BOTH DOCX FILES INTO ONE ─────────────────────────────────────────
// Strategy: copy the DTR DOCX, then inject the IDLAR body XML after a page
// break inside DTR's word/document.xml — preserving DTR styles/relationships.

$zipDtr   = new ZipArchive();
$zipIdlar = new ZipArchive();

if ($zipDtr->open($dtrDocxPath) !== true || $zipIdlar->open($idlarDocxPath) !== true) {
    @unlink($dtrDocxPath);
    @unlink($idlarDocxPath);
    die('Failed to open DOCX files for merging.');
}

$dtrXml        = $zipDtr->getFromName('word/document.xml');
$dtrStylesXml  = $zipDtr->getFromName('word/styles.xml');
$zipDtr->close();

$idlarXml       = $zipIdlar->getFromName('word/document.xml');
$idlarStylesXml = $zipIdlar->getFromName('word/styles.xml');
$zipIdlar->close();

if ($dtrXml === false || $idlarXml === false) {
    @unlink($dtrDocxPath);
    @unlink($idlarDocxPath);
    die('Failed to read document XML from DOCX files.');
}

// Build merged styles.xml: start with DTR styles, then add any IDLAR styles
// whose w:styleId doesn't already exist in DTR (prevents conflicts).
$mergedStylesXml = $dtrStylesXml;
if ($dtrStylesXml !== false && $idlarStylesXml !== false) {
    // Collect all styleIds already in DTR
    preg_match_all('/w:styleId="([^"]+)"/', $dtrStylesXml, $dtrStyleIds);
    $existingIds = array_flip($dtrStyleIds[1]);

    // Extract individual <w:style ...>...</w:style> blocks from IDLAR
    preg_match_all('/<w:style\b[^>]*>.*?<\/w:style>/s', $idlarStylesXml, $idlarStyles);
    $toInject = '';
    foreach ($idlarStyles[0] as $styleBlock) {
        if (preg_match('/w:styleId="([^"]+)"/', $styleBlock, $idM)) {
            if (!isset($existingIds[$idM[1]])) {
                $toInject .= $styleBlock;
            }
        }
    }
    // Also grab <w:docDefaults> from IDLAR only if DTR doesn't have it
    if (strpos($dtrStylesXml, '<w:docDefaults') === false) {
        if (preg_match('/<w:docDefaults\b.*?<\/w:docDefaults>/s', $idlarStylesXml, $ddM)) {
            $toInject = $ddM[0] . $toInject;
        }
    }

    if ($toInject !== '') {
        $mergedStylesXml = preg_replace('/<\/w:styles>/', $toInject . '</w:styles>', $dtrStylesXml, 1);
    }
}

// Extract IDLAR inner body content (everything between <w:body> and </w:body>)
if (!preg_match('/<w:body>(.*)<\/w:body>/s', $idlarXml, $idlarMatch)) {
    @unlink($dtrDocxPath);
    @unlink($idlarDocxPath);
    die('Failed to parse IDLAR document XML.');
}
$idlarBodyContent = $idlarMatch[1];

// Extract DTR's final <w:sectPr> (direct child of <w:body>) so we can preserve
// the DTR column/page layout as its own section, separate from the IDLAR layout.
$dtrSectPr = '';
if (preg_match('/(<w:sectPr\b.*?<\/w:sectPr>)\s*<\/w:body>/s', $dtrXml, $sectMatch)) {
    $dtrSectPr = $sectMatch[1];
}

// Remove the final sectPr from DTR body — it will become a section-break paragraph
$dtrXmlNoSectPr = preg_replace('/<w:sectPr\b.*?<\/w:sectPr>(\s*<\/w:body>)/s', '$1', $dtrXml, 1);

// Wrap DTR's sectPr in a paragraph pPr — this is the OOXML "next page section break"
// that ends the DTR section while keeping its layout (columns, margins, orientation).
$sectionBreakPara = $dtrSectPr !== ''
    ? '<w:p><w:pPr>' . $dtrSectPr . '</w:pPr></w:p>'
    : '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';

// Inject section-break paragraph + full IDLAR body (with its own sectPr as final section)
// before DTR's closing </w:body>.
$mergedXml = preg_replace(
    '/<\/w:body>/',
    $sectionBreakPara . $idlarBodyContent . '</w:body>',
    $dtrXmlNoSectPr,
    1
);

// Write merged XML back into the DTR DOCX copy
$mergedDocxPath = tempnam(sys_get_temp_dir(), 'combined_') . '.docx';
copy($dtrDocxPath, $mergedDocxPath);

$zipOut = new ZipArchive();
if ($zipOut->open($mergedDocxPath) !== true) {
    @unlink($dtrDocxPath);
    @unlink($idlarDocxPath);
    @unlink($mergedDocxPath);
    die('Failed to open merged DOCX for writing.');
}
$zipOut->addFromString('word/document.xml', $mergedXml);
if ($mergedStylesXml !== false && $mergedStylesXml !== $dtrStylesXml) {
    $zipOut->addFromString('word/styles.xml', $mergedStylesXml);
}
$zipOut->close();

$mergedFilename = "DTR_IDLAR_{$safeFullName}_{$monthName}_{$year}.docx";
header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . $mergedFilename . '"');
header('Content-Length: ' . filesize($mergedDocxPath));
header('Cache-Control: private, max-age=0, must-revalidate');

readfile($mergedDocxPath);

@unlink($dtrDocxPath);
@unlink($idlarDocxPath);
@unlink($mergedDocxPath);
exit;
