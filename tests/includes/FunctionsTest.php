<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/functions.php';

class FunctionsTest extends TestCase
{
    // ─── getAmStatus ───────────────────────────────────────────

    public function test_getAmStatus_returns_on_time_at_0745(): void
    {
        $this->assertSame('on_time', getAmStatus('07:45'));
    }

    public function test_getAmStatus_returns_on_time_at_0800(): void
    {
        $this->assertSame('on_time', getAmStatus('08:00'));
    }

    public function test_getAmStatus_returns_grace_at_0801(): void
    {
        $this->assertSame('grace', getAmStatus('08:01'));
    }

    public function test_getAmStatus_returns_grace_at_0815(): void
    {
        $this->assertSame('grace', getAmStatus('08:15'));
    }

    public function test_getAmStatus_returns_late_at_0816(): void
    {
        $this->assertSame('late', getAmStatus('08:16'));
    }

    public function test_getAmStatus_returns_late_at_0900(): void
    {
        $this->assertSame('late', getAmStatus('09:00'));
    }

    public function test_getAmStatus_returns_late_before_0745(): void
    {
        $this->assertSame('late', getAmStatus('07:30'));
    }

    // ─── calculateTotalHours ───────────────────────────────────

    public function test_calculateTotalHours_full_day_with_lunch(): void
    {
        $result = calculateTotalHours('08:00', '12:00', '13:00', '17:00');
        $this->assertSame(8.0, $result);
    }

    public function test_calculateTotalHours_absent_am_pm_only(): void
    {
        $result = calculateTotalHours(null, null, '13:00', '17:00');
        $this->assertSame(4.0, $result);
    }

    public function test_calculateTotalHours_no_lunch_break(): void
    {
        $result = calculateTotalHours('08:00', null, null, '17:00');
        $this->assertSame(9.0, $result);
    }

    public function test_calculateTotalHours_returns_null_when_no_start(): void
    {
        $this->assertNull(calculateTotalHours(null, null, null, '17:00'));
    }

    public function test_calculateTotalHours_returns_null_when_no_end(): void
    {
        $this->assertNull(calculateTotalHours('08:00', '12:00', '13:00', null));
    }

    public function test_calculateTotalHours_returns_null_when_all_null(): void
    {
        $this->assertNull(calculateTotalHours(null, null, null, null));
    }

    public function test_calculateTotalHours_short_lunch(): void
    {
        $result = calculateTotalHours('08:00', '12:00', '12:30', '17:00');
        $this->assertSame(8.5, $result);
    }

    // ─── isWithinWindow — time_in ──────────────────────────────

    public function test_time_in_allowed_at_0745(): void
    {
        $result = isWithinWindow('time_in', null, '07:45');
        $this->assertTrue($result['allowed']);
    }

    public function test_time_in_allowed_at_0800(): void
    {
        $result = isWithinWindow('time_in', null, '08:00');
        $this->assertTrue($result['allowed']);
    }

    public function test_time_in_allowed_at_0815(): void
    {
        $result = isWithinWindow('time_in', null, '08:15');
        $this->assertTrue($result['allowed']);
    }

    public function test_time_in_rejected_at_0744(): void
    {
        $result = isWithinWindow('time_in', null, '07:44');
        $this->assertFalse($result['allowed']);
    }

    public function test_time_in_rejected_at_0816(): void
    {
        $result = isWithinWindow('time_in', null, '08:16');
        $this->assertFalse($result['allowed']);
    }

    public function test_time_in_rejected_at_1200(): void
    {
        $result = isWithinWindow('time_in', null, '12:00');
        $this->assertFalse($result['allowed']);
    }

    // ─── isWithinWindow — lunch_out (AM OUT) ───────────────────

    public function test_lunch_out_allowed_at_1200(): void
    {
        $result = isWithinWindow('lunch_out', null, '12:00');
        $this->assertTrue($result['allowed']);
    }

    public function test_lunch_out_rejected_at_1159(): void
    {
        $result = isWithinWindow('lunch_out', null, '11:59');
        $this->assertFalse($result['allowed']);
    }

    public function test_lunch_out_rejected_at_1201(): void
    {
        $result = isWithinWindow('lunch_out', null, '12:01');
        $this->assertFalse($result['allowed']);
    }

    public function test_lunch_out_rejected_at_1230(): void
    {
        $result = isWithinWindow('lunch_out', null, '12:30');
        $this->assertFalse($result['allowed']);
    }

    // ─── isWithinWindow — lunch_in (PM IN) ─────────────────────

    public function test_lunch_in_at_1201_allowed_for_absent_am(): void
    {
        $result = isWithinWindow('lunch_in', null, '12:01');
        $this->assertTrue($result['allowed']);
    }

    public function test_lunch_in_at_1201_blocked_for_employee_with_am_in(): void
    {
        $log = ['time_in' => '08:00', 'lunch_out' => '12:00', 'lunch_in' => null, 'time_out' => null, 'am_status' => 'on_time'];
        $result = isWithinWindow('lunch_in', $log, '12:01');
        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('1:00 PM to 1:15 PM', $result['message']);
    }

    public function test_lunch_in_at_1300_allowed_for_regular_employee(): void
    {
        $log = ['time_in' => '08:00', 'lunch_out' => '12:00', 'lunch_in' => null, 'time_out' => null, 'am_status' => 'on_time'];
        $result = isWithinWindow('lunch_in', $log, '13:00');
        $this->assertTrue($result['allowed']);
    }

    public function test_lunch_in_at_1315_allowed(): void
    {
        $log = ['time_in' => '08:00', 'lunch_out' => '12:00', 'lunch_in' => null, 'time_out' => null, 'am_status' => 'on_time'];
        $result = isWithinWindow('lunch_in', $log, '13:15');
        $this->assertTrue($result['allowed']);
    }

    public function test_lunch_in_locked_after_1315(): void
    {
        $result = isWithinWindow('lunch_in', null, '13:16');
        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('locked after 1:15 PM', $result['message']);
    }

    public function test_lunch_in_locked_at_1400(): void
    {
        $log = ['time_in' => '08:00', 'lunch_out' => '12:00', 'lunch_in' => null, 'time_out' => null, 'am_status' => 'on_time'];
        $result = isWithinWindow('lunch_in', $log, '14:00');
        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('locked', $result['message']);
    }

    public function test_lunch_in_rejected_at_1230_for_absent_am(): void
    {
        $result = isWithinWindow('lunch_in', null, '12:30');
        $this->assertFalse($result['allowed']);
    }

    public function test_lunch_in_rejected_at_1259_for_employee_with_am_in(): void
    {
        $log = ['time_in' => '08:00', 'lunch_out' => '12:00', 'lunch_in' => null, 'time_out' => null, 'am_status' => 'on_time'];
        $result = isWithinWindow('lunch_in', $log, '12:59');
        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('1:00 PM to 1:15 PM', $result['message']);
    }

    public function test_lunch_in_at_1300_allowed_for_absent_am(): void
    {
        $result = isWithinWindow('lunch_in', null, '13:00');
        $this->assertTrue($result['allowed']);
    }

    // ─── isWithinWindow — time_out (PM OUT) ────────────────────

    public function test_time_out_allowed_at_1700(): void
    {
        $result = isWithinWindow('time_out', null, '17:00');
        $this->assertTrue($result['allowed']);
    }

    public function test_time_out_allowed_at_1730(): void
    {
        $result = isWithinWindow('time_out', null, '17:30');
        $this->assertTrue($result['allowed']);
    }

    public function test_time_out_allowed_at_1800(): void
    {
        $result = isWithinWindow('time_out', null, '18:00');
        $this->assertTrue($result['allowed']);
    }

    public function test_time_out_rejected_at_1659(): void
    {
        $result = isWithinWindow('time_out', null, '16:59');
        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('5:00 PM to 6:00 PM', $result['message']);
    }

    public function test_time_out_locked_at_1801(): void
    {
        $result = isWithinWindow('time_out', null, '18:01');
        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('locked after 6:00 PM', $result['message']);
    }

    public function test_time_out_locked_at_2300(): void
    {
        $result = isWithinWindow('time_out', null, '23:00');
        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('locked after 6:00 PM', $result['message']);
    }

    // ─── isWithinWindow — invalid action ───────────────────────

    public function test_invalid_action_rejected(): void
    {
        $result = isWithinWindow('bogus_action', null, '12:00');
        $this->assertFalse($result['allowed']);
        $this->assertSame('Invalid action.', $result['message']);
    }

    // ─── isTimeInWindowPassed ──────────────────────────────────

    public function test_time_in_window_passed_at_0816(): void
    {
        $this->assertTrue(isTimeInWindowPassed('08:16'));
    }

    public function test_time_in_window_not_passed_at_0815(): void
    {
        $this->assertFalse(isTimeInWindowPassed('08:15'));
    }

    public function test_time_in_window_not_passed_at_0700(): void
    {
        $this->assertFalse(isTimeInWindowPassed('07:00'));
    }

    public function test_time_in_window_passed_at_1200(): void
    {
        $this->assertTrue(isTimeInWindowPassed('12:00'));
    }

    // ─── isLeaveLog ────────────────────────────────────────────

    public function test_isLeaveLog_returns_true_for_leave(): void
    {
        $this->assertTrue(isLeaveLog(['am_status' => 'leave']));
    }

    public function test_isLeaveLog_returns_false_for_am_leave(): void
    {
        $this->assertFalse(isLeaveLog(['am_status' => 'am_leave']));
    }

    public function test_isLeaveLog_returns_false_for_pm_leave(): void
    {
        $this->assertFalse(isLeaveLog(['am_status' => 'pm_leave']));
    }

    public function test_isLeaveLog_returns_false_for_on_time(): void
    {
        $this->assertFalse(isLeaveLog(['am_status' => 'on_time']));
    }

    public function test_isLeaveLog_returns_false_for_null(): void
    {
        $this->assertFalse(isLeaveLog(null));
    }

    public function test_isLeaveLog_returns_false_for_missing_key(): void
    {
        $this->assertFalse(isLeaveLog(['time_in' => '08:00']));
    }

    // ─── canMarkTodayLeave ─────────────────────────────────────

    public function test_canMarkTodayLeave_true_when_no_log(): void
    {
        $this->assertTrue(canMarkTodayLeave(null));
    }

    public function test_canMarkTodayLeave_false_when_time_in_exists(): void
    {
        $log = ['time_in' => '08:00', 'lunch_out' => null, 'lunch_in' => null, 'time_out' => null, 'am_status' => 'on_time'];
        $this->assertFalse(canMarkTodayLeave($log));
    }

    public function test_canMarkTodayLeave_false_when_already_leave(): void
    {
        $log = ['time_in' => null, 'lunch_out' => null, 'lunch_in' => null, 'time_out' => null, 'am_status' => 'leave'];
        $this->assertFalse(canMarkTodayLeave($log));
    }

    public function test_canMarkTodayLeave_true_when_log_empty_no_actions(): void
    {
        $log = ['time_in' => null, 'lunch_out' => null, 'lunch_in' => null, 'time_out' => null, 'am_status' => 'absent'];
        $this->assertTrue(canMarkTodayLeave($log));
    }

    public function test_canMarkTodayLeave_false_when_lunch_in_exists(): void
    {
        $log = ['time_in' => null, 'lunch_out' => null, 'lunch_in' => '13:00', 'time_out' => null, 'am_status' => 'absent'];
        $this->assertFalse(canMarkTodayLeave($log));
    }

    public function test_canMarkTodayLeave_am_true_when_no_am_actions(): void
    {
        $log = ['time_in' => null, 'lunch_out' => null, 'lunch_in' => null, 'time_out' => null, 'am_status' => 'absent'];
        $this->assertTrue(canMarkTodayLeave($log, 'am'));
    }

    public function test_canMarkTodayLeave_am_false_when_time_in_exists(): void
    {
        $log = ['time_in' => '08:00', 'lunch_out' => null, 'lunch_in' => null, 'time_out' => null, 'am_status' => 'on_time'];
        $this->assertFalse(canMarkTodayLeave($log, 'am'));
    }

    public function test_canMarkTodayLeave_pm_true_when_am_done_no_pm(): void
    {
        $log = ['time_in' => '08:00', 'lunch_out' => '12:00', 'lunch_in' => null, 'time_out' => null, 'am_status' => 'on_time'];
        $this->assertTrue(canMarkTodayLeave($log, 'pm'));
    }

    // ─── getCurrentStep ────────────────────────────────────────

    public function test_getCurrentStep_returns_1_for_null_log(): void
    {
        $this->assertSame(1, getCurrentStep(null));
    }

    public function test_getCurrentStep_returns_1_when_no_time_in(): void
    {
        $log = ['time_in' => null, 'lunch_out' => null, 'lunch_in' => null, 'time_out' => null, 'am_status' => 'on_time'];
        $this->assertSame(1, getCurrentStep($log));
    }

    public function test_getCurrentStep_returns_2_after_time_in(): void
    {
        $log = ['time_in' => '08:00', 'lunch_out' => null, 'lunch_in' => null, 'time_out' => null, 'am_status' => 'on_time'];
        $this->assertSame(2, getCurrentStep($log));
    }

    public function test_getCurrentStep_returns_3_after_lunch_out(): void
    {
        $log = ['time_in' => '08:00', 'lunch_out' => '12:00', 'lunch_in' => null, 'time_out' => null, 'am_status' => 'on_time'];
        $this->assertSame(3, getCurrentStep($log));
    }

    public function test_getCurrentStep_returns_4_after_lunch_in(): void
    {
        $log = ['time_in' => '08:00', 'lunch_out' => '12:00', 'lunch_in' => '13:00', 'time_out' => null, 'am_status' => 'on_time'];
        $this->assertSame(4, getCurrentStep($log));
    }

    public function test_getCurrentStep_returns_5_when_complete(): void
    {
        $log = ['time_in' => '08:00', 'lunch_out' => '12:00', 'lunch_in' => '13:00', 'time_out' => '17:00', 'am_status' => 'on_time'];
        $this->assertSame(5, getCurrentStep($log));
    }

    public function test_getCurrentStep_absent_am_returns_3_when_no_lunch_in(): void
    {
        $log = ['time_in' => null, 'lunch_out' => null, 'lunch_in' => null, 'time_out' => null, 'am_status' => 'absent'];
        $this->assertSame(3, getCurrentStep($log));
    }

    public function test_getCurrentStep_absent_am_returns_4_after_lunch_in(): void
    {
        $log = ['time_in' => null, 'lunch_out' => null, 'lunch_in' => '13:00', 'time_out' => null, 'am_status' => 'absent'];
        $this->assertSame(4, getCurrentStep($log));
    }

    public function test_getCurrentStep_absent_am_returns_5_when_done(): void
    {
        $log = ['time_in' => null, 'lunch_out' => null, 'lunch_in' => '13:00', 'time_out' => '17:00', 'am_status' => 'absent'];
        $this->assertSame(5, getCurrentStep($log));
    }

    public function test_getCurrentStep_am_leave_returns_3_when_no_pm(): void
    {
        $log = ['time_in' => null, 'lunch_out' => null, 'lunch_in' => null, 'time_out' => null, 'am_status' => 'am_leave'];
        $this->assertSame(3, getCurrentStep($log));
    }

    public function test_getCurrentStep_am_leave_returns_4_after_lunch_in(): void
    {
        $log = ['time_in' => null, 'lunch_out' => null, 'lunch_in' => '13:00', 'time_out' => null, 'am_status' => 'am_leave'];
        $this->assertSame(4, getCurrentStep($log));
    }

    public function test_getCurrentStep_am_leave_returns_5_when_done(): void
    {
        $log = ['time_in' => null, 'lunch_out' => null, 'lunch_in' => '13:00', 'time_out' => '17:00', 'am_status' => 'am_leave'];
        $this->assertSame(5, getCurrentStep($log));
    }

    public function test_getCurrentStep_pm_leave_returns_1_when_no_time_in(): void
    {
        $log = ['time_in' => null, 'lunch_out' => null, 'lunch_in' => null, 'time_out' => null, 'am_status' => 'pm_leave'];
        $this->assertSame(1, getCurrentStep($log));
    }

    public function test_getCurrentStep_pm_leave_returns_2_after_time_in(): void
    {
        $log = ['time_in' => '08:00', 'lunch_out' => null, 'lunch_in' => null, 'time_out' => null, 'am_status' => 'pm_leave'];
        $this->assertSame(2, getCurrentStep($log));
    }

    public function test_getCurrentStep_pm_leave_returns_5_after_lunch_out(): void
    {
        $log = ['time_in' => '08:00', 'lunch_out' => '12:00', 'lunch_in' => null, 'time_out' => null, 'am_status' => 'pm_leave'];
        $this->assertSame(5, getCurrentStep($log));
    }

    public function test_getCurrentStep_whole_leave_returns_5(): void
    {
        $log = ['time_in' => null, 'lunch_out' => null, 'lunch_in' => null, 'time_out' => null, 'am_status' => 'leave'];
        $this->assertSame(5, getCurrentStep($log));
    }

    // ─── isAnyLeaveLog ─────────────────────────────────────────

    public function test_isAnyLeaveLog_true_for_leave(): void
    {
        $this->assertTrue(isAnyLeaveLog(['am_status' => 'leave']));
    }

    public function test_isAnyLeaveLog_true_for_am_leave(): void
    {
        $this->assertTrue(isAnyLeaveLog(['am_status' => 'am_leave']));
    }

    public function test_isAnyLeaveLog_true_for_pm_leave(): void
    {
        $this->assertTrue(isAnyLeaveLog(['am_status' => 'pm_leave']));
    }

    public function test_isAnyLeaveLog_false_for_on_time(): void
    {
        $this->assertFalse(isAnyLeaveLog(['am_status' => 'on_time']));
    }

    public function test_isAnyLeaveLog_false_for_null(): void
    {
        $this->assertFalse(isAnyLeaveLog(null));
    }

    // ─── getNextAction ─────────────────────────────────────────

    public function test_getNextAction_step_1(): void
    {
        $this->assertSame('time_in', getNextAction(1));
    }

    public function test_getNextAction_step_2(): void
    {
        $this->assertSame('lunch_out', getNextAction(2));
    }

    public function test_getNextAction_step_3(): void
    {
        $this->assertSame('lunch_in', getNextAction(3));
    }

    public function test_getNextAction_step_4(): void
    {
        $this->assertSame('time_out', getNextAction(4));
    }

    public function test_getNextAction_step_5_returns_complete(): void
    {
        $this->assertSame('complete', getNextAction(5));
    }

    public function test_getNextAction_invalid_step_returns_complete(): void
    {
        $this->assertSame('complete', getNextAction(99));
    }

    // ─── getNextActionLabel ────────────────────────────────────

    public function test_getNextActionLabel_step_1(): void
    {
        $this->assertSame('AM In', getNextActionLabel(1));
    }

    public function test_getNextActionLabel_step_2(): void
    {
        $this->assertSame('AM Out', getNextActionLabel(2));
    }

    public function test_getNextActionLabel_step_3(): void
    {
        $this->assertSame('PM In', getNextActionLabel(3));
    }

    public function test_getNextActionLabel_step_4(): void
    {
        $this->assertSame('PM Out', getNextActionLabel(4));
    }

    public function test_getNextActionLabel_step_5(): void
    {
        $this->assertSame('Complete', getNextActionLabel(5));
    }

    // ─── formatTimeDisplay ─────────────────────────────────────

    public function test_formatTimeDisplay_formats_morning(): void
    {
        $this->assertSame('08:00 AM', formatTimeDisplay('08:00:00'));
    }

    public function test_formatTimeDisplay_formats_afternoon(): void
    {
        $this->assertSame('01:30 PM', formatTimeDisplay('13:30:00'));
    }

    public function test_formatTimeDisplay_returns_dash_for_null(): void
    {
        $this->assertSame('--', formatTimeDisplay(null));
    }

    public function test_formatTimeDisplay_handles_short_format(): void
    {
        $this->assertSame('05:00 PM', formatTimeDisplay('17:00'));
    }

    // ─── getStatusBadgeClass ───────────────────────────────────

    public function test_getStatusBadgeClass_on_time(): void
    {
        $this->assertSame('badge-ontime', getStatusBadgeClass('on_time'));
    }

    public function test_getStatusBadgeClass_grace(): void
    {
        $this->assertSame('badge-grace', getStatusBadgeClass('grace'));
    }

    public function test_getStatusBadgeClass_late(): void
    {
        $this->assertSame('badge-late', getStatusBadgeClass('late'));
    }

    public function test_getStatusBadgeClass_absent(): void
    {
        $this->assertSame('badge-absent', getStatusBadgeClass('absent'));
    }

    public function test_getStatusBadgeClass_leave(): void
    {
        $this->assertSame('badge-leave', getStatusBadgeClass('leave'));
    }

    public function test_getStatusBadgeClass_unknown(): void
    {
        $this->assertSame('badge-absent', getStatusBadgeClass('unknown_status'));
    }

    // ─── getStatusLabel ────────────────────────────────────────

    public function test_getStatusLabel_on_time(): void
    {
        $this->assertSame('On Time', getStatusLabel('on_time'));
    }

    public function test_getStatusLabel_grace(): void
    {
        $this->assertSame('Grace', getStatusLabel('grace'));
    }

    public function test_getStatusLabel_late(): void
    {
        $this->assertSame('Late', getStatusLabel('late'));
    }

    public function test_getStatusLabel_absent(): void
    {
        $this->assertSame('Absent', getStatusLabel('absent'));
    }

    public function test_getStatusLabel_leave(): void
    {
        $this->assertSame('On Leave', getStatusLabel('leave'));
    }

    public function test_getStatusLabel_unknown(): void
    {
        $this->assertSame('Unknown', getStatusLabel('bogus'));
    }
}
