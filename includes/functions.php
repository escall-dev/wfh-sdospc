<?php

function getProfilePictureUrl(?string $idPicture): string {
    if ($idPicture && file_exists(__DIR__ . '/../assets/id_pictures/' . $idPicture)) {
        return '/assets/id_pictures/' . rawurlencode($idPicture);
    }
    return '';
}

function renderUserAvatar(?string $picUrl, string $fallbackInitials, string $extraClass = ''): string {
    $cls = $extraClass ? " {$extraClass}" : '';
    if ($picUrl) {
        return '<img src="' . htmlspecialchars($picUrl) . '" alt="" class="user-avatar user-avatar-img' . $cls . '">';
    }
    return '<div class="user-avatar' . $cls . '">' . htmlspecialchars($fallbackInitials) . '</div>';
}

function getAmStatus(string $timeIn): string {
    $time = strtotime($timeIn);
    $start = strtotime('07:45');
    $onTimeEnd = strtotime('08:00');
    $graceEnd = strtotime('08:15');

    if ($time >= $start && $time <= $onTimeEnd) {
        return 'on_time';
    }
    if ($time > $onTimeEnd && $time <= $graceEnd) {
        return 'grace';
    }
    return 'late';
}

function calculateTotalHours(?string $timeIn, ?string $lunchOut, ?string $lunchIn, ?string $timeOut): ?float {
    // Absent AM: no time_in — count from lunch_in (PM only)
    $effectiveStart = $timeIn ?? $lunchIn;
    if (!$effectiveStart || !$timeOut) return null;

    $start = strtotime($effectiveStart);
    $end = strtotime($timeOut);
    $totalSeconds = $end - $start;

    // Only subtract lunch break when the employee had a full morning
    if ($timeIn && $lunchOut && $lunchIn) {
        $lunchStart = strtotime($lunchOut);
        $lunchEnd = strtotime($lunchIn);
        $totalSeconds -= ($lunchEnd - $lunchStart);
    }

    return round($totalSeconds / 3600, 2);
}

function isWithinWindow(string $action, ?array $todayLog = null, ?string $currentTime = null): array {
    // Friday-only check disabled for development
    // if ((int)date('N') !== 5) {
    //     return ['allowed' => false, 'message' => 'Clock-in/out is only available on Fridays.'];
    // }

    $now = $currentTime ?? date('H:i');
    $nowTime = strtotime($now);

    switch ($action) {
        case 'time_in':
            if ($nowTime >= strtotime('07:45') && $nowTime <= strtotime('08:15')) {
                return ['allowed' => true, 'message' => ''];
            }
            return ['allowed' => false, 'message' => 'Clock-in unavailable at this time.'];

        case 'lunch_out':
            // AM OUT: only at 12:00 PM for employees with an AM IN log
            if ($nowTime >= strtotime('12:00') && $nowTime <= strtotime('12:59')) {
                return ['allowed' => true, 'message' => ''];
            }
            return ['allowed' => false, 'message' => 'AM Out is only available at 12:00 PM to 12:59 PM.'];

        case 'lunch_in':
            $hasAmIn = $todayLog && !empty($todayLog['time_in']);
            $isAmLeave = $todayLog && ($todayLog['am_status'] ?? '') === 'am_leave';
            $amOutTime = $todayLog['lunch_out'] ?? null;
        
            // Lock PM IN after 1:15 PM
            if ($nowTime > strtotime('13:15')) {
                return ['allowed' => false, 'message' => 'PM In is locked after 1:15 PM.'];
            }
        
            // Enforce 15-minute interval after AM OUT
            if ($amOutTime) {
                $amOutTimestamp = strtotime($amOutTime);
                if ($nowTime < $amOutTimestamp + (15 * 60)) {
                    return [
                        'allowed' => false,
                        'message' => 'PM In is allowed 15 minutes after AM Out.'
                    ];
                }
            }
        
            // Main PM IN window
            if ($nowTime >= strtotime('12:15') && $nowTime <= strtotime('13:15')) {
                return ['allowed' => true, 'message' => ''];
            }
        
            // Early PM IN for employees who missed AM IN
            if ($nowTime >= strtotime('12:01') && $nowTime < strtotime('12:15')) {
                if ($hasAmIn || $isAmLeave) {
                    return ['allowed' => false, 'message' => 'PM In starts at 12:15 PM.'];
                }
                return ['allowed' => true, 'message' => ''];
            }
        
            return ['allowed' => false, 'message' => 'PM In is available from 12:15 PM to 1:15 PM.'];
            
        case 'time_out':
            // PM OUT: 5:00 PM - 6:00 PM only
            if ($nowTime >= strtotime('17:00') && $nowTime <= strtotime('18:00')) {
                return ['allowed' => true, 'message' => ''];
            }
            if ($nowTime > strtotime('18:00')) {
                return ['allowed' => false, 'message' => 'PM Out is locked after 6:00 PM.'];
            }
            return ['allowed' => false, 'message' => 'PM Out is only available from 5:00 PM to 6:00 PM.'];
    }

    return ['allowed' => false, 'message' => 'Invalid action.'];
}

function isTimeInWindowPassed(?string $currentTime = null): bool {
    // Friday-only check disabled for development
    // if ((int)date('N') !== 5) return false;
    $now = $currentTime ?? date('H:i');
    return strtotime($now) > strtotime('08:15');
}

function isPmInWindowPassed(?string $currentTime = null): bool {
    $now = $currentTime ?? date('H:i');
    return strtotime($now) > strtotime('13:15');
}
/**
 * Determine whether OB filing window is currently open.
 * Allowed window: 07:45 - 08:15 (server time).
 */
function isObFilingWindow(?string $currentTime = null): array {
    $now = $currentTime ?? date('H:i');
    $nowTs = strtotime($now);
    if ($nowTs >= strtotime('07:45') && $nowTs <= strtotime('08:15')) {
        return ['allowed' => true, 'message' => ''];
    }
    return ['allowed' => false, 'message' => 'Official Business filing is allowed only from 7:45 AM to 8:15 AM.'];
}
function isWholeDayLeave(?array $log): bool {
    return $log !== null && ($log['am_status'] ?? '') === 'leave';
}

function isAnyLeaveLog(?array $log): bool {
    return $log !== null && in_array($log['am_status'] ?? '', ['leave', 'am_leave', 'pm_leave']);
}

function isLeaveLog(?array $log): bool {
    return isWholeDayLeave($log);
}

function canMarkTodayLeave(?array $log, string $leaveType = 'whole_day'): bool {
    if (!$log) return true;
    // Already on any form of leave
    if (in_array($log['am_status'] ?? '', ['leave', 'am_leave', 'pm_leave'])) return false;
    if ($leaveType === 'pm') {
        // PM leave: can be marked as long as the PM session (lunch_in) hasn't started
        return !$log['lunch_in'] && !$log['time_out'];
    }
    if ($leaveType === 'am') {
        // AM leave: no AM clock data recorded yet
        return !$log['time_in'] && !$log['lunch_out'];
    }
    // Whole Day leave: no attendance action recorded yet
    return !$log['time_in'] && !$log['lunch_in'] && !$log['lunch_out'] && !$log['time_out'];
}

function getTodayLog(PDO $pdo, int $userId): ?array {
    $stmt = $pdo->prepare("SELECT * FROM attendance_logs WHERE user_id = :uid AND date = CURDATE()");
    $stmt->execute([':uid' => $userId]);
    return $stmt->fetch() ?: null;
}

function getCurrentStep(?array $log): int {
    if (!$log) return 1;
    $status = $log['am_status'] ?? '';
    // Absent AM: skips Time In and Lunch Out
    if ($status === 'absent') {
        if (!$log['lunch_in']) return 3;
        if (!$log['time_out']) return 4;
        return 5;
    }
    // Whole Day Leave: attendance is complete
    if ($status === 'leave') return 5;
    // AM Leave: skip AM session, advance to PM In (step 3)
    if ($status === 'am_leave') {
        if (!$log['lunch_in']) return 3;
        if (!$log['time_out']) return 4;
        return 5;
    }
    // PM Leave: AM session proceeds normally, PM is locked
    if ($status === 'pm_leave') {
        if (!$log['time_in']) return 1;
        if (!$log['lunch_out']) return 2;
        return 5; // PM is on leave, day complete after AM out
    }
    if (!$log['time_in']) return 1;
    if (!$log['lunch_out']) return 2;
    if (!$log['lunch_in']) return 3;
    if (!$log['time_out']) return 4;
    return 5; // all done
}

function getNextAction(int $step): string {
    $actions = [
        1 => 'time_in',
        2 => 'lunch_out',
        3 => 'lunch_in',
        4 => 'time_out'
    ];
    return $actions[$step] ?? 'complete';
}

function getNextActionLabel(int $step): string {
    $labels = [
        1 => 'AM In',
        2 => 'AM Out',
        3 => 'PM In',
        4 => 'PM Out'
    ];
    return $labels[$step] ?? 'Complete';
}

function getAttendanceForMonth(PDO $pdo, int $userId, int $month, int $year): array {
    $stmt = $pdo->prepare("
        SELECT * FROM attendance_logs
        WHERE user_id = :uid AND MONTH(date) = :month AND YEAR(date) = :year
        ORDER BY date ASC
    ");
    $stmt->execute([':uid' => $userId, ':month' => $month, ':year' => $year]);
    return $stmt->fetchAll();
}

function getAccomplishmentsForLog(PDO $pdo, int $logId): array {
    $stmt = $pdo->prepare("SELECT * FROM accomplishments WHERE log_id = :lid ORDER BY id ASC");
    $stmt->execute([':lid' => $logId]);
    return $stmt->fetchAll();
}

function getMonthlyStats(PDO $pdo, int $userId, int $month, int $year): array {
    $logs = getAttendanceForMonth($pdo, $userId, $month, $year);
    $totalDays = count($logs);
    $totalHours = 0;
    $onTime = 0;
    $grace = 0;
    $late = 0;

    foreach ($logs as $log) {
        $totalHours += (float)($log['total_hours'] ?? 0);
        if ($log['am_status'] === 'on_time') $onTime++;
        elseif ($log['am_status'] === 'grace') $grace++;
        elseif ($log['am_status'] === 'late') $late++;
    }

    $onTimeRate = $totalDays > 0 ? round(($onTime / $totalDays) * 100) : 0;

    return [
        'total_days' => $totalDays,
        'total_hours' => round($totalHours, 2),
        'on_time' => $onTime,
        'grace' => $grace,
        'late' => $late,
        'on_time_rate' => $onTimeRate
    ];
}

function formatTimeDisplay(?string $time): string {
    if (!$time) return '--';
    return date('h:i A', strtotime($time));
}

function getStatusBadgeClass(string $status): string {
    $map = [
        'on_time'  => 'badge-ontime',
        'grace'    => 'badge-grace',
        'late'     => 'badge-late',
        'absent'   => 'badge-absent',
        'leave'    => 'badge-leave',
        'am_leave' => 'badge-leave',
        'pm_leave' => 'badge-leave',
        'ob'       => 'badge-ob',
    ];
    return $map[$status] ?? 'badge-absent';
}

function getStatusLabel(string $status): string {
    $map = [
        'on_time'  => 'On Time',
        'grace'    => 'Grace',
        'late'     => 'Late',
        'absent'   => 'Absent',
        'leave'    => 'On Leave',
        'am_leave' => 'AM Leave',
        'pm_leave' => 'PM Leave',
        'ob'       => 'Official Business',
    ];
    return $map[$status] ?? 'Unknown';
}

function getHoursToday(PDO $pdo, int $userId): float {
    $log = getTodayLog($pdo, $userId);
    if (!$log || !$log['time_in']) return 0;

    if ($log['total_hours']) return (float)$log['total_hours'];

    $start = strtotime($log['time_in']);
    $now = time();
    $elapsed = $now - $start;

    if ($log['lunch_out'] && $log['lunch_in']) {
        $elapsed -= (strtotime($log['lunch_in']) - strtotime($log['lunch_out']));
    } elseif ($log['lunch_out'] && !$log['lunch_in']) {
        $elapsed -= ($now - strtotime($log['lunch_out']));
    }

    return round(max(0, $elapsed) / 3600, 1);
}

function getDaysThisMonth(PDO $pdo, int $userId): int {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as cnt FROM attendance_logs
        WHERE user_id = :uid AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())
    ");
    $stmt->execute([':uid' => $userId]);
    return (int)$stmt->fetch()['cnt'];
}

function getTotalHoursMonth(PDO $pdo, int $userId): float {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_hours), 0) as total FROM attendance_logs
        WHERE user_id = :uid AND MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE())
    ");
    $stmt->execute([':uid' => $userId]);
    return round((float)$stmt->fetch()['total'], 1);
}

function getOnTimeRate(PDO $pdo, int $userId): int {
    $stats = getMonthlyStats($pdo, $userId, (int)date('m'), (int)date('Y'));
    return $stats['on_time_rate'];
}

/**
 * Check whether the IDLAR submission window is open for a given log date.
 * Rules:
 *  - Only the current active week can be edited.
 *  - Window opens Friday 5:00 PM and closes Monday 11:59 PM.
 *  - On Monday the active week is still the previous ISO week (containing last Friday).
 */
function checkAccomplishmentWindow(string $logDateStr): array {
    $now           = time();
    $nowDayOfWeek  = (int)date('N'); // 1=Mon … 7=Sun
    $nowHour       = (int)date('G');
    $logTs         = strtotime($logDateStr);
    $logDayOfWeek  = (int)date('N', $logTs);

    // Monday of the log's week
    $logMondayTs     = strtotime(date('Y-m-d', $logTs) . ' -' . ($logDayOfWeek - 1) . ' days');

    // Determine the "active" week for editing.
    // On Monday the IDLAR window is still open for the PREVIOUS ISO week
    // (the week containing the Friday that opened the window).
    if ($nowDayOfWeek == 1) {
        // Monday: active week = previous ISO week
        $activeMondayTs = strtotime(date('Y-m-d') . ' -7 days');
    } else {
        // Tuesday–Sunday: active week = current ISO week
        $activeMondayTs = strtotime(date('Y-m-d') . ' -' . ($nowDayOfWeek - 1) . ' days');
    }

    if ($logMondayTs < $activeMondayTs) {
        return [
            'allowed' => false,
            'message' => 'Editing IDLAR entries from previous weeks is no longer allowed.'
        ];
    }

    if ($logMondayTs > $activeMondayTs) {
        return [
            'allowed' => false,
            'message' => 'Cannot add IDLAR entries for future weeks.'
        ];
    }

    // Active week matches – check the Friday 5 PM to Monday 23:59 window
    if ($nowDayOfWeek == 1) {
        // Monday: window is still open (until 23:59)
        return ['allowed' => true, 'message' => ''];
    }

    if ($nowDayOfWeek < 5 || ($nowDayOfWeek == 5 && $nowHour < 17)) {
        $msg = ($nowDayOfWeek == 5)
            ? 'IDLAR entries open today at 5:00 PM.'
            : 'IDLAR entries are only allowed from Friday 5:00 PM to Monday 11:59 PM. The window opens this Friday at 5:00 PM.';
        return ['allowed' => false, 'message' => $msg];
    }

    // Friday ≥ 17, Saturday, or Sunday → allowed
    return ['allowed' => true, 'message' => ''];
}

function getOBForDate(PDO $pdo, int $userId, string $date): array {
    $stmt = $pdo->prepare("
        SELECT * FROM official_business
        WHERE user_id = :uid AND ob_date = :dt
        ORDER BY time_from ASC
    ");
    $stmt->execute([':uid' => $userId, ':dt' => $date]);
    return $stmt->fetchAll();
}

function getOBForMonth(PDO $pdo, int $userId, int $month, int $year): array {
    $stmt = $pdo->prepare("
        SELECT * FROM official_business
        WHERE user_id = :uid AND MONTH(ob_date) = :month AND YEAR(ob_date) = :year
        ORDER BY ob_date ASC, time_from ASC
    ");
    $stmt->execute([':uid' => $userId, ':month' => $month, ':year' => $year]);
    return $stmt->fetchAll();
}

function getOBUpcoming(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("
        SELECT * FROM official_business
        WHERE user_id = :uid AND ob_date >= CURDATE()
        ORDER BY ob_date ASC, time_from ASC
    ");
    $stmt->execute([':uid' => $userId]);
    return $stmt->fetchAll();
}
