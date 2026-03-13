-- WFH-SDOSPC Pre-Deletion Backup (Data Only)
-- Generated: 2026-03-11 17:37:12
-- Database: u813957308_wfh_sdospc
-- Type: Pre-deletion snapshot

SET FOREIGN_KEY_CHECKS=0;

-- Table: attendance_logs (2 rows)
INSERT IGNORE INTO `attendance_logs` (`id`, `user_id`, `date`, `time_in`, `lunch_out`, `lunch_in`, `time_out`, `total_hours`, `am_status`, `is_emergency`, `created_at`) VALUES
('30', '128', '2026-03-11', NULL, NULL, NULL, NULL, NULL, 'pm_leave', '0', '2026-03-11 09:12:58'),
('31', '129', '2026-03-11', NULL, NULL, NULL, NULL, NULL, 'leave', '0', '2026-03-11 09:13:06');

-- Table `accomplishments`: 0 rows (empty)

-- Table `idlar_attachments`: 0 rows (empty)

SET FOREIGN_KEY_CHECKS=1;
