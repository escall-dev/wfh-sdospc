-- WFH-SDOSPC Database Backup
-- Generated: 2026-03-11 14:26:23
-- Database: wfh_sdospc

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';

-- Table: accomplishments
DROP TABLE IF EXISTS `accomplishments`;
CREATE TABLE `accomplishments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `log_id` int(11) NOT NULL,
  `item_text` varchar(300) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `log_id` (`log_id`),
  CONSTRAINT `accomplishments_ibfk_1` FOREIGN KEY (`log_id`) REFERENCES `attendance_logs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: attendance_logs
DROP TABLE IF EXISTS `attendance_logs`;
CREATE TABLE `attendance_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `lunch_out` time DEFAULT NULL,
  `lunch_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `total_hours` decimal(5,2) DEFAULT NULL,
  `am_status` enum('on_time','grace','late','absent','leave','am_leave','pm_leave') DEFAULT NULL,
  `is_emergency` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_date` (`user_id`,`date`),
  CONSTRAINT `attendance_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `attendance_logs` (`id`, `user_id`, `date`, `time_in`, `lunch_out`, `lunch_in`, `time_out`, `total_hours`, `am_status`, `is_emergency`, `created_at`) VALUES
('29', '17', '2026-03-11', NULL, NULL, NULL, NULL, NULL, 'am_leave', '0', '2026-03-11 14:10:09');

-- Table: db_backups
DROP TABLE IF EXISTS `db_backups`;
CREATE TABLE `db_backups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_name` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `backup_type` enum('scheduled','manual','pre_deletion') NOT NULL,
  `description` text DEFAULT NULL,
  `tables_included` text DEFAULT NULL,
  `file_size` bigint(20) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `db_backups_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `db_backups` (`id`, `backup_name`, `file_name`, `backup_type`, `description`, `tables_included`, `file_size`, `created_by`, `created_at`) VALUES
('6', 'wfh_sdospc_full_2026-03-11_142416', 'wfh_sdospc_full_2026-03-11_142416.sql', 'manual', 'Manual backup by Super Admin', 'accomplishments, attendance_logs, db_backups, idlar_attachments, official_business, users', '38361', '3', '2026-03-11 14:24:16');

-- Table: idlar_attachments
DROP TABLE IF EXISTS `idlar_attachments`;
CREATE TABLE `idlar_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `log_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `log_id` (`log_id`),
  CONSTRAINT `idlar_attachments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `idlar_attachments_ibfk_2` FOREIGN KEY (`log_id`) REFERENCES `attendance_logs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: official_business
DROP TABLE IF EXISTS `official_business`;
CREATE TABLE `official_business` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ob_date` date NOT NULL,
  `time_from` time NOT NULL,
  `time_to` time NOT NULL,
  `duration_hours` decimal(5,2) NOT NULL,
  `reason` varchar(500) NOT NULL,
  `location` varchar(300) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_ob` (`user_id`,`ob_date`,`time_from`),
  CONSTRAINT `official_business_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `official_business` (`id`, `user_id`, `ob_date`, `time_from`, `time_to`, `duration_hours`, `reason`, `location`, `created_at`) VALUES
('1', '17', '2026-03-12', '09:00:00', '11:00:00', '2.00', 'hahsdsdhasv', 'raam batangas', '2026-03-11 14:12:53');

-- Table: users
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(20) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `functional_division` enum('OSDS','SGOD','CID') DEFAULT NULL,
  `position` varchar(200) DEFAULT NULL,
  `recommending` varchar(200) DEFAULT NULL,
  `id_picture` varchar(255) DEFAULT NULL,
  `role` enum('employee','admin','superadmin','hr_timekeeping') DEFAULT 'employee',
  `password` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `must_change_password` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_employee_id` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=139 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `employee_id`, `last_name`, `full_name`, `functional_division`, `position`, `recommending`, `id_picture`, `role`, `password`, `is_active`, `must_change_password`, `created_at`) VALUES
('3', '061920', 'Escallente', 'escall', NULL, NULL, NULL, NULL, 'superadmin', '$2y$10$F2rdDfFaHuf.WkymVzUWPenmooTd/lBrLHVRs2F15lYbi8F9VMLj2', '1', '0', '2026-03-09 21:56:32'),
('5', '4569779', 'JURADA', 'LORINA B. JURADA', 'OSDS', 'Administrative Officer IV', 'AO V - ADMINISTRATIVE', '4569779_LORINA_B__JURADA.png', 'employee', '$2y$10$cQ/MZpbwR1ujAF30VraDL.CjSP2IS8zN79Ua8wDqwhhB6JkpHs6k.', '1', '1', '2026-03-09 23:48:34'),
('6', '6458804', 'MORESCA', 'MICHAEL ANGELO S. MORESCA', 'OSDS', 'Administrative Assistant III', 'AO V - ADMINISTRATIVE', '6458804_MICHAEL_ANGELO_S__MORESCA.png', 'employee', '$2y$10$dEXc2VyBjup28YrkNXAI6O5O2rZQLQKWAZMYJ8HXsCuIev3sQq766', '1', '1', '2026-03-09 23:48:34'),
('7', '6458802', 'GRIJALDO', 'FLOYD CHRISTOPHER A. GRIJALDO', 'OSDS', NULL, 'AO V - ADMINISTRATIVE', '6458802_FLOYD_CHRISTOPHER_A__GRIJALDO.png', 'employee', '$2y$10$cMkAd8iCciOeIgDHYPhApexm/bODSIwBv1bfw0WEl1zXSWVHXj89K', '1', '1', '2026-03-09 23:48:34'),
('8', '6459300', 'TORIANO', 'AIKO A. TORIANO', 'OSDS', NULL, 'AO V - ADMINISTRATIVE', '6459300_AIKO_A__TORIANO.png', 'employee', '$2y$10$WyBr8GJD.NVYzKLdPia42OisW7CpoSJz9HL/xoh5I3/Do6HxfG8oq', '1', '1', '2026-03-09 23:48:34'),
('9', '6458809', 'CAJANO', 'MARY KRIS ANNE B. CAJANO', 'OSDS', 'Administrative Assistant III', 'AO V - ADMINISTRATIVE', '6458809_MARY_KRIS_ANNE_B__CAJANO.png', 'employee', '$2y$10$c1KjREPyl345k.zuaWe86upRHYUukYYz/dyFALZkC4EHX0BubRdxi', '1', '1', '2026-03-09 23:48:34'),
('10', '6466198', 'SOPEÑA', 'ARCEL F. SOPEÑA', 'OSDS', 'Administrative Officer II', 'AO V - ADMINISTRATIVE', '6466198_ARCEL_F__SOPE__A.png', 'employee', '$2y$10$h3aDPkKfba5aD2jVN1ZqTOY2jm2YNY.sKTuaC/Fws38jgrYPY.nZm', '1', '1', '2026-03-09 23:48:34'),
('11', '1000002', 'BELLEN', 'LEEROYD S. BELLEN', 'OSDS', 'LSB Clerk', 'AO V - ADMINISTRATIVE', '1000002_LEEROYD_S__BELLEN.png', 'employee', '$2y$10$m3ay8r1cWyQvrFIfDC/ADO4HMtSCl7t3VB6o6et28ptcTsdWtmCBq', '1', '1', '2026-03-09 23:48:35'),
('12', '6519582', 'ORIBIA', 'EMELLOU S. ORIBIA', 'OSDS', 'Administrative Assistant III', 'AO V - ADMINISTRATIVE', '1000003_EMELLOU_S__ORIBIA.png', 'employee', '$2y$10$RlXntOeFHomvAXH32W0c4eeV2yfDTpEYsV4HfO7K.S9rZWuabQCtW', '1', '1', '2026-03-09 23:48:35'),
('13', '6519579', 'OLIVENZA', 'ABIGAIL A. OLIVENZA', 'OSDS', 'Administrative Assistant III', 'AO V - ADMINISTRATIVE', '1000004_ABIGAIL_A__OLIVENZA.png', 'employee', '$2y$10$cyaSNgBLh4lfPBlaBkbLbOXJtiHZhtokIMVDM5LoBU6muzIw0.xA.', '1', '1', '2026-03-09 23:48:35'),
('14', '092193', 'BELETA', 'ELJOHN BELETA', 'OSDS', 'LSB Clerk', 'AO V - ADMINISTRATIVE', '092193_ELJOHN_BELETA.png', 'employee', '$2y$10$mdkJ9x62.Y0qcPNRo01Loe8oN1/39JgoFHA0IiCS6ew6IkvKd0Wfm', '1', '1', '2026-03-09 23:48:35'),
('15', '060701', 'JR', 'ROGELIO SAPITULA JR', 'OSDS', 'LSB Clerk', 'AO V - ADMINISTRATIVE', '060701_JR_SAPITULA.png', 'employee', '$2y$10$Sck1b.jmFDlH.agNvyNGFO9.Fw7rhaas.6UYJvGX..mkcByU7Acnm', '1', '1', '2026-03-09 23:48:35'),
('16', '6459308', 'LEOSALA', 'LYKA JANE LEOSALA', 'OSDS', 'IT Officer I', 'AO V - ADMINISTRATIVE', '6459308_LYKA_JANE_LEOSALA.png', 'employee', '$2y$10$UmHfkR3HdpjVc8R0ArJBE.IM2lrE/3Amwtd96w/JSmVMh2LGYVgW6', '1', '1', '2026-03-09 23:48:35'),
('17', '6404524', 'AGUJA', 'PAUL JEREMY I. AGUJA', 'OSDS', 'Administrative Officer V - Administrative', 'ASSISTANT SCHOOLS DIVISION SUPERINTEDENT', '6404524_Paul_Jeremy_I_Aguja.png', 'admin', '$2y$10$lH7NXfBr9vW/fpkzKSrgNefzMrwmAdxOS/la9KqILybUnL1GsJIr2', '1', '0', '2026-03-09 23:48:35'),
('18', '5909780', 'ELEN', 'ANNRA T. ELEN', 'OSDS', 'Administrative Officer IV', 'AO V - ADMINISTRATIVE', '5909780_ANNRA_T__ELEN.png', 'employee', '$2y$10$KYC7n1bIPuHaUM/Minoz7e9Pes86W2F/JreT7yrEzU8X8fag6unx.', '1', '1', '2026-03-09 23:48:35'),
('19', '6465358', 'PERALTA', 'EDWIN JOSEPH B. DE PERALTA', 'OSDS', 'Administrative Aide VI', 'AO V - ADMINISTRATIVE', '6465358_EDWIN_JOSEPH_B__DE_PERALTA.png', 'employee', '$2y$10$0YmdgK87EcS/QAs1xNtpPu1g4piP94jxj7luqq6guWXmO3aA1UErW', '1', '1', '2026-03-09 23:48:35'),
('20', '1000005', 'MARIÑAS', 'JUN JUN DC. MARIÑAS', 'OSDS', 'LSB Clerk', 'AO V - ADMINISTRATIVE', '1000005_JUN_JUN_DC__MARI__AS.png', 'employee', '$2y$10$b3ckKiB4TDwYlBN2OwmcX.ASpa4Moq/t0p9ZHP0GdmwBXcL8YFCYO', '1', '1', '2026-03-09 23:48:35'),
('21', '1000006', 'ALAO', 'MICAH JOY O. ALAO', 'OSDS', 'LSB Clerk', 'AO V - ADMINISTRATIVE', '1000006_MICAH_JOY_O__ALAO.png', 'employee', '$2y$10$QlfRDhXf5/QYd/xJg1VjZe/xrpxG29kRZ9onYs6rYMLhpkr/a7wtu', '1', '1', '2026-03-09 23:48:35'),
('22', '1000007', 'DELLORO', 'BERNADETTE DELLORO', 'OSDS', 'LSB Clerk', 'AO V - ADMINISTRATIVE', '1000007_BERNADETTE_DELLORO.png', 'employee', '$2y$10$GTpmaYHvJEeY8FqpGXdVXegt/30CRlj0KHjCqCbsI.buiOOfD0H1e', '1', '1', '2026-03-09 23:48:35'),
('23', '6404953', 'ECHON', 'NERIE VIEN C. ECHON', 'OSDS', 'Administrative Officer II', 'AO V - ADMINISTRATIVE', '6404953_NERIE_VIEN_C__ECHON.png', 'employee', '$2y$10$Jts7G5/S2XV6.WoN6ZyNoeTAk4GLTL3Xdgc2K4eCSN7hfp8AAg9pG', '1', '1', '2026-03-09 23:48:35'),
('24', '0005794', 'ALCANTARA', 'CAROLINA B. ALCANTARA', 'OSDS', 'Administrative Officer V', 'AO V - ADMINISTRATIVE', '0005794_CAROLINA_B_ALCANTARA.png', 'employee', '$2y$10$NJDw/duwtrdcVEqUWYRIPOA2XtzbIcyU5VCBDXZp6C/EpFAq4BvCi', '1', '1', '2026-03-09 23:48:35'),
('25', '6397830', 'RENDORA', 'MICHELLE G. RENDORA', 'OSDS', 'Administrative Assistant III', 'AO V - ADMINISTRATIVE', '6397830_MICHELLE_G_RENDORA.png', 'employee', '$2y$10$B00i1AHG8oPglOEolo5Z.OWEDDiQhHzwm3ny5dctvjo6M1Ouul812', '1', '1', '2026-03-09 23:48:35'),
('26', '5485802', 'COMIA', 'CHRISTOPER E. COMIA', 'OSDS', 'Administrative Officer IV', 'AO V - ADMINISTRATIVE', '5485802_CHRISTOPER_E_COMIA.png', 'employee', '$2y$10$ER4bHxXFrFfBaYRmGe2eWONlSmmpHN3xCVcacYm0CiT9yA/UNTxC2', '1', '1', '2026-03-09 23:48:35'),
('27', '6459299', 'DAITOL', 'DANIEL M. DAITOL', 'OSDS', 'Administrative Assistant III', 'AO V - ADMINISTRATIVE', '6459299_DANIEL_M_DAITOL.png', 'employee', '$2y$10$XXTK7hefRT4ldb1zN7hjnu/eApaqbtF4OD2KoVZdGR3Ky//uV/i9u', '1', '1', '2026-03-09 23:48:35'),
('28', '4360172', 'PACIA', 'MA. CRISTINA N. PACIA', 'OSDS', 'Administrative Officer IV', 'AO V - ADMINISTRATIVE', '4360172_MA_CRISTINA_N_PACIA.png', 'employee', '$2y$10$c.Yet3ZXgpNrQSw6w/HsmOXGpht23gIbrrJn387JyVmHVpWY3G10e', '1', '1', '2026-03-09 23:48:35'),
('29', '6459305', 'AGUILAR', 'KRISTINE KATE R. AGUILAR', 'OSDS', 'Administrative Aide VI', 'AO V - ADMINISTRATIVE', '6459305_KRISTINE_KATE_R_AGUILAR.png', 'employee', '$2y$10$ZPRxRO01onTsJrMVuOvFFe/1ffB2NzUkNXyBJ45fheVDZVwUFloOq', '1', '1', '2026-03-09 23:48:35'),
('30', '6313204', 'ABRIGO', 'ZAIDA A. ABRIGO', 'OSDS', 'Accountant III', 'AO V - ADMINISTRATIVE', '6313204_ZAIDA_A_ABRIGO.png', 'employee', '$2y$10$MHYRL/3Bd9z6DMTeHhBnyuZv/LfAqcSzCdV/kZIFNU0VbQiiDzKDK', '1', '1', '2026-03-09 23:48:35'),
('31', '6459301', 'ALARCON', 'LOIDA GRACE B. ALARCON', 'OSDS', 'Administrative Assistant II', 'AO V - ADMINISTRATIVE', '6459301_LOIDA_GRACE_B_ALARCON.png', 'employee', '$2y$10$ECu4AVZGmeDUIp8KM5En1.lRZz6s6eIK6Gft6KgbKyu/SY9lf2jpO', '1', '1', '2026-03-09 23:48:36'),
('32', '1000011', 'DOROL', 'MARY JACQUELINE J. DOROL', 'OSDS', 'LSB Clerk', 'AO V - ADMINISTRATIVE', '1000011_MARY_JACQUELINE_J_DOROL.png', 'employee', '$2y$10$qWHWPjQE.QJ/Rd8.AjojX.hEvmyMC4tIAXE/Wq7xAxTaZkXZX8N8G', '1', '1', '2026-03-09 23:48:36'),
('33', '6458826', 'LAUDE', 'SHEILA MAE D. LAUDE', 'OSDS', 'Attorney III', 'AO V - ADMINISTRATIVE', '6458826_SHEILA_MAE_D_LAUDE.png', 'employee', '$2y$10$pfXWPXKhB/3dV3nJhMPV0eihJ0F1BLgf7ogjCM7LKQfS2cLxP5wrK', '1', '1', '2026-03-09 23:48:36'),
('34', '6458806', 'CABREJAS', 'GIZELLE E. CABREJAS', 'OSDS', 'Legal Assistant I', 'AO V - ADMINISTRATIVE', '6458806_GIZELLE_E_CABREJAS.png', 'employee', '$2y$10$uYuFcUVu7m1m5SQFHv/Mpe8fNazW7Fkz0WIBpnjEE67KVZAXcYIVK', '1', '1', '2026-03-09 23:48:36'),
('35', '6468178', 'PERFAS', 'BABY HAZEL ANN D. PERFAS', 'OSDS', 'Administrative Assistant III', 'AO V - ADMINISTRATIVE', '6468178_HAZEL_PERFAS.png', 'employee', '$2y$10$nQiKdclMRfWIuq08ao2mNOCk4.galKhSQzzyqTqzbr9tCfanOelXq', '1', '1', '2026-03-09 23:48:36'),
('36', '6472858', 'VICENCIO', 'ADRIAN GERALD JAMES O. VICENCIO', 'CID', 'Administrative Aide VI', 'CID CHIEF', '6472858_Adrian_vicencio.png', 'employee', '$2y$10$Pp7.Wu/9beweyI4maKNfoeYeMPT9grx8jrBNF70wnXWxfRktPwADq', '1', '1', '2026-03-09 23:48:36'),
('37', '1000009', 'BONOAN', 'LARRY M. BONOAN', 'OSDS', 'LSB Clerk', 'AO V - ADMINISTRATIVE', '1000009_LARRY_BONOAN.png', 'employee', '$2y$10$U0bFdPYEpiBUppeOSna9tO8/6dXs6EA9U4NTT1VOfmChGiyted1yW', '1', '1', '2026-03-09 23:48:36'),
('38', '6471110', 'JOSUE', 'CRISTAL O. JOSUE', 'OSDS', 'Administrative Assistant III', 'AO V - ADMINISTRATIVE', '6471110_CRISTAL _JOSUE.png', 'employee', '$2y$10$.It7hi09YA/dkT1KW9j7tuVrnn0spnIzXhjYQfxhkfBA8xgt.W2Gu', '1', '1', '2026-03-09 23:48:36'),
('39', '6463758', 'PAGANA', 'PIETRO PAOLO S. PAGANA', 'OSDS', 'Administrative Assistant III', 'AO V - ADMINISTRATIVE', '6463758_petro_paulo_pagana.png', 'employee', '$2y$10$9sW95ZhU.81Vbsan/VHuvubtzV4BtIlJTdWcc036hDDZ1w4G0ZDfq', '1', '1', '2026-03-09 23:48:36'),
('40', '1000010', 'GOMEZ', 'MARIA DANICA B. GOMEZ', 'OSDS', 'LSB Clerk', 'AO V - ADMINISTRATIVE', '1000010_MARIA_DANICA.png', 'employee', '$2y$10$vh/6ZKG8b0YjmAdDoEQTguHQs69qqWzNbIyDGs2qyq./4255sfqxm', '1', '1', '2026-03-09 23:48:36'),
('41', '6466229', 'ALFONSO', 'FERNAN JOSEPH C. ALFONSO', 'OSDS', 'Administrative Assistant III', 'AO V - ADMINISTRATIVE', '6466229_fernan_joseph_alfonso.png', 'employee', '$2y$10$IeMwI/568p6an3l.w6azUuR3lqEbwFvNclXbw1lpfB0Fd7FNs7B96', '1', '1', '2026-03-09 23:48:36'),
('42', '6467340', 'MARQUEZ', 'MA. THERESA D. MARQUEZ', 'OSDS', 'Administrative Assistant III', 'AO V - ADMINISTRATIVE', '6467340_matheresa_marquez.png', 'employee', '$2y$10$uxLgeI7/13Jc5CFj2T9cCeSX4PGpLVLxzFzgz6FyPDiIEvtIOw/rG', '1', '1', '2026-03-09 23:48:36'),
('43', '6471111', 'ANGELES', 'ARLENE R. ANGELES', 'OSDS', 'Administrative Aide VI', 'AO V - ADMINISTRATIVE', '6471111_ARLENE_R_ANGELES.jpg', 'employee', '$2y$10$U0iiv/A1CAHRS3OQwyAULOB16wme/.b.CkAlCFPOIWNLBxiQRWzj6', '1', '1', '2026-03-09 23:48:36'),
('44', '6459306', 'DEFANTE', 'MARVIN O. DEFANTE', 'OSDS', NULL, 'AO V - ADMINISTRATIVE', '6459306_MARVIN_O_DEFANTE.png', 'employee', '$2y$10$9UAC.2vA/5.Po/7BSNQMkujgin7bAQdOWIQqtFEXem1mWeh9nkxYS', '1', '1', '2026-03-09 23:48:36'),
('45', '4645589', 'JR.', 'FREDERICK G. BYRD JR.', 'SGOD', 'CHIEF EDUCATION SUPERVISOR, SGOD', 'ASSISTANT SCHOOLS DIVISION SUPERINTENDENT', '4645589_FREDERICK_G_BYRD_JR.png', 'admin', '$2y$10$f7NUf9v70GBp.v.jIWX.ouOCP7LGOTWLw6/af3wU5nB14mQ4txFOa', '1', '0', '2026-03-09 23:48:36'),
('46', '4130587', 'PETRASANTA', 'VIVIAN L. PETRASANTA', 'SGOD', 'Education Program Supervisor', 'SGOD CHIEF', '4130587_VIVIAN_L_PETRASANTA.png', 'employee', '$2y$10$i0DTYlX/kqFwHgas6XEV/eokct/shAjPWN2LwBexSBlwCqpkLlfMO', '1', '1', '2026-03-09 23:48:36'),
('47', '6313312', 'JASA', 'JEROME Q. JASA', 'SGOD', 'Project Development Officer II', 'SGOD CHIEF', '6313312_JEROME_Q_JASA.png', 'employee', '$2y$10$40sBvOCLqg7BQqX/2r.YoeNlijpHeAd6HrB3jEtWMKZ2Ujr1X2LyC', '1', '1', '2026-03-09 23:48:36'),
('48', '4569771', 'BARRAGO', 'EMMANUELLE M. BARRAGO', 'SGOD', 'Education Program Specialist II', 'SGOD CHIEF', '4569771_EMMANUELLE_M_BARRAGO.png', 'employee', '$2y$10$8HSL2H9Yo.ZcfAvPv1VPj.mfNSzLXafECEAvRQkcOOZsEJrHYKgQO', '1', '1', '2026-03-09 23:48:36'),
('49', '6317759', 'LAREÑA', 'JENNETH J. LAREÑA', 'SGOD', 'Education Program Specialist II', 'SGOD CHIEF', '6317759_JENNETH_J_LAREA.png', 'employee', '$2y$10$CyEmitVb636JjhEQ6AsRbecfk.TuJo7e3Ign4tjI5uZPtmFpsZJyK', '1', '1', '2026-03-09 23:48:36'),
('50', '6317826', 'ATERRADO', 'ARMIE JOYCE P. ATERRADO', 'SGOD', 'Education Program Specialist II', 'SGOD CHIEF', '6317826_ARMIE_JOYCE_P_ATERRADO.png', 'employee', '$2y$10$3Oh/BaW73tJBobKvItFg2O3jj/kxzR1Wvv1Cwe6J13vIMt7wKQZse', '1', '1', '2026-03-09 23:48:37'),
('51', '4252033', 'LIMJUICO', 'NICANOR A. LIMJUICO', 'SGOD', 'Senior Education Program Specialist', 'SGOD CHIEF', '4252033_NICANOR_A_LIMJUICO.png', 'employee', '$2y$10$CtVd7zjx0ZhSBed2bZTnGeO.br387oQD9vEHBB.GyxVs/h0FNZtVe', '1', '1', '2026-03-09 23:48:37'),
('52', '4460121', 'PARTO', 'LAURENCE E. PARTO', 'SGOD', 'Senior Education Program Specialist', 'SGOD CHIEF', '4460121_LAURENCE_E_PARTO.png', 'employee', '$2y$10$seIher8e4tja8H/L7Tk36uZs427c6/vEKUKr164hXX94iHuUgef7u', '1', '1', '2026-03-09 23:48:37'),
('53', '4569589', 'AGUILAR', 'MARYROSE S. AGUILAR', 'SGOD', 'Senior Education Program Specialist', 'SGOD CHIEF', '4569589_MARYROSE_S_AGUILAR.png', 'employee', '$2y$10$3ggUAgtviqNPbUkCfd8ICuyZLqNxAf3W8YaKfcisQSRemDLgRT0jC', '1', '1', '2026-03-09 23:48:37'),
('54', '6460131', 'SANTOS', 'SHERWIN P. SANTOS', 'SGOD', 'Engineer III', 'SGOD CHIEF', '6460131_SHERWIN_P_SANTOS.png', 'employee', '$2y$10$ZWUsbcOOepQD4WL63He02O43u8ADURPFKYTHuQJKiIi1corVXEE56', '1', '1', '2026-03-09 23:48:37'),
('55', '6317489', 'GUAB-DAGANDAN', 'ORIMAR M. GUAB-DAGANDAN', 'SGOD', 'Senior Education Program Specialist', 'SGOD CHIEF', '6317489_ORIMAR_M_GUAB-DAGANDAN.png', 'employee', '$2y$10$BUdZDZk1u9Sg/wHwSMsn3O2b0zf.oVgX4J14MA/zaJ9QXogvUaQMm', '1', '1', '2026-03-09 23:48:37'),
('56', '6458797', 'VILLANUEVA', 'JECEL B. VILLANUEVA', 'SGOD', 'Nurse II', 'SGOD CHIEF', '6458797_JECEL_B_VILLANUEVA.png', 'employee', '$2y$10$BtlGPxnwaokHJfPDc5IZwOscfNOJNE6qIQKGmwH5XhevgxJQwTs/u', '1', '1', '2026-03-09 23:48:37'),
('57', '5017335', 'JAVIER', 'ABIGAIL HAZEL M. JAVIER', 'SGOD', 'Nurse II', 'SGOD CHIEF', '5017335_ABIGAIL_HAZEL_M_JAVIER.png', 'employee', '$2y$10$bsiWK3m2TOCcp3xVjHFhZuamzylM33J501WXlBxI2SA0Iu0X9229G', '1', '1', '2026-03-09 23:48:37'),
('58', '6458800', 'ASEOCHE', 'JAMIE LEE V. ASEOCHE', 'SGOD', 'Nurse II', 'SGOD CHIEF', '6458800_JAMIE_LEE_V_ASEOCHE.png', 'employee', '$2y$10$Ps1EayJm4vgySPkn8DydzeTjRbGYI67tJ6sqmvf09bUSJAJ40GoIe', '1', '1', '2026-03-09 23:48:37'),
('59', '6458799', 'HONDUNA', 'MA. SOFIA P. HONDUNA', 'SGOD', 'Dentist II', 'SGOD CHIEF', '6458799_MA_SOFIA_P_HONDUNA.png', 'employee', '$2y$10$hxru1GQPUjeSqZJByO4M/eG6Dj3FITuvpSwVaRIqfjhYRQPpFAF5W', '1', '1', '2026-03-09 23:48:37'),
('60', '6458798', 'MORALES', 'KAYZLE LYNNE T. MORALES', 'SGOD', 'Nurse II', 'SGOD CHIEF', '6458798_KAYZLE_LYNNE_T_MORALES.png', 'employee', '$2y$10$3e9Ya.HNUcdP.s4X0ktVy.l26Zi8OyjpvvUCn7ZpgQsa3dGZa.q.y', '1', '1', '2026-03-09 23:48:37'),
('61', '6458807', 'BARICUATRO', 'RUPERT F. BARICUATRO', 'SGOD', 'Nurse II', 'SGOD CHIEF', '6458807_RUPERT_F_BARICUATRO.png', 'employee', '$2y$10$p0X6nUPZuR.YrgH09eWVkezpMMlMQfmaqNks3AMInodudrKeORENK', '1', '1', '2026-03-09 23:48:37'),
('62', '6461345', 'AMBAYEC', 'JENINA R. AMBAYEC', 'SGOD', 'Dentist II', 'SGOD CHIEF', '6461345_JENINA_R_AMBAYEC.png', 'employee', '$2y$10$6rvOdCVrNAF1/F8KEzzwkOrDMnwd0GUccDU2/Ei7jsOKOFbT.U7.G', '1', '1', '2026-03-09 23:48:37'),
('63', '1000015', 'GARCIA', 'FELISSA MAE S. GARCIA', 'SGOD', 'LSB Clerk', 'SGOD CHIEF', '1000015_FELISSA_MAE_S_GARCIA.png', 'employee', '$2y$10$Y.3bulcAyw/fkMAZvcv47uHmnrWsH2rL4ylFWNdRjrseXUovj/m9e', '1', '1', '2026-03-09 23:48:37'),
('64', '1000016', 'MERCADO', 'ANA MARIE M. MERCADO', 'SGOD', 'LSB Clerk', 'SGOD CHIEF', '1000016_ANA_MARIE_MERCADO.png', 'employee', '$2y$10$cQAfVuypLMrD/qPqgsL4BOoA4gLjwRzOp.zT8R8mDmUKl30tbNBYK', '1', '1', '2026-03-09 23:48:37'),
('65', '1000017', 'MANALO', 'SHIENA PEARL M. MANALO', 'SGOD', 'LSB Clerk', 'SGOD CHIEF', '1000017_SHIENA_PEARL_MANALO.png', 'employee', '$2y$10$D6f28mp6JzYV7lFt6GxpLeaAIX4bRXC5QNG3pnhaepoNULM3EwGXy', '1', '1', '2026-03-09 23:48:37'),
('66', '4369177', 'VALENZUELA', 'ERMA S. VALENZUELA', 'CID', 'CHIEF EDUCATION SUPERVISOR, CID', 'ASSISTANT SCHOOLS DIVISION SUPERINTEDENT', '4369177_ERMA_S_VALENZUELA.png', 'admin', '$2y$10$wt0YF4ekMiI1VObrxSGY.OMCU9Jax6yQwsu5HecB/8icLCbmud4Mq', '1', '0', '2026-03-09 23:48:37'),
('67', '4246363', 'VINZON', 'LAILYN C. VINZON', 'CID', 'Education Program Supervisor English', 'CID CHIEF', '4246363_LAILYN_C_VINZON.png', 'employee', '$2y$10$nfTlz8Km1rpAY2dsyYXBc.RNLRhIjaTxv4Dc4Emgu/d2tmBSqu/Pu', '1', '1', '2026-03-09 23:48:37'),
('68', '0006248', 'JR.', 'ERNESTO C. CABERTE JR.', 'CID', 'Education Program Supervisor Filipino', 'CID CHIEF', '0006248_ERNESTO_C_CABERTE_JR.png', 'employee', '$2y$10$bhsVClPTq6mblILdcqrMSeZJFeo9nL.qCvAhcTDlQq0UXQod6L1La', '1', '1', '2026-03-09 23:48:37'),
('69', '4265330', 'LALLABBAN', 'MARIA BELYNDA L. LALLABBAN', 'CID', 'Education Program Supervisor Science', 'CID CHIEF', '4265330_MARIA_BELYNDA_L_LALLABBAN.png', 'employee', '$2y$10$gQ8Z7/nSH1HzteScBIDYIOcenXODZTsapU2NH2TxbwDSqAOPcGPf2', '1', '1', '2026-03-09 23:48:37'),
('70', '4364184', 'PASCO', 'ASHER H. PASCO', 'CID', 'Education Program Supervisor Araling Panlipunan', 'CID CHIEF', '4364184_ASHER_H_PASCO.png', 'employee', '$2y$10$ru4vdPjYQ2Is4P9OGoHgeef8ieJSe94xy24f9s8TenxfVC4cr2QKe', '1', '1', '2026-03-09 23:48:38'),
('71', '6313346', 'MARTINEZ', 'MARITES R. MARTINEZ', 'CID', 'Education Program Supervisor MAPEH', 'CID CHIEF', '6313346_MARITES_R_MARTINEZ.png', 'employee', '$2y$10$uNiYA2rVZtM4g3Zb12cJguRpVG4Yt0y9kdmvbfVdeaNyTC2XnbNVW', '1', '1', '2026-03-09 23:48:38'),
('72', '4645662', 'SUYAO', 'CHARINA C. SUYAO', 'CID', 'Education Program Supervisor TLE', 'CID CHIEF', '4645662_CHARINA_C_SUYAO.png', 'employee', '$2y$10$/1vPqPKeaevhi1cC5hkbIeIZNgdHlGs03kqyLW7/DhxiT0MCOvcTa', '1', '1', '2026-03-09 23:48:38'),
('73', '4167298', 'BAUYON', 'CRISPENIANA P. BAUYON', 'CID', 'Education Program Supervisor Values Education', 'CID CHIEF', '4167298_CRISPENIANA_P_BAUYON.png', 'employee', '$2y$10$t658cai05kApDRDBAv8ZEe7RlyRPDQPMPvWJMPpeVQRFCZsvpVb5C', '1', '1', '2026-03-09 23:48:38'),
('74', '4239981', 'SANTOS', 'NIDA C. SANTOS', 'CID', 'Education Program Supervisor Kindergarten', 'CID CHIEF', '4239981_NIDA_C_SANTOS.png', 'employee', '$2y$10$bgpukbOolui9q3pRN5QqteEiLOGgNQ4vmvHsP.PxbcW0EobnkaGPi', '1', '1', '2026-03-09 23:48:38'),
('75', '6519578', 'PAPANDAYAN', 'ASHNIE HAYA A. PAPANDAYAN', 'OSDS', 'Administrative Aide VI', 'AO V - ADMINISTRATIVE', '1000018_ASHNIE_HAYA_A_PAPANDAYAN.png', 'employee', '$2y$10$15G8NqVjFlHBHQnZu0kwkO2Yy1PXgt2tytW4y.jZ/DOAzMLsK0BkO', '1', '1', '2026-03-09 23:48:38'),
('76', '4267890', 'GRAFIL', 'REGINAL G. GRAFIL', 'CID', 'Education Program Supervisor Mathematics', 'CID CHIEF', '4267890_REGINAL_GRAFIL.png', 'employee', '$2y$10$MQmZl2vB0.Yya8mVcJkZAeN3ebmzdeHkjIOTLMU5qX/6WZqhjXBN6', '1', '1', '2026-03-09 23:48:38'),
('77', '4459552', 'CRISOLOGO', 'RAINIEL VICTOR M. CRISOLOGO', 'CID', 'Public Schools District Supervisor', 'CID CHIEF', '4459552_RAINIEL_VICTOR_M_CRISOLOGO.png', 'employee', '$2y$10$WHCSqY7we8S5GDZdp0Y3iuHs4JWwg0nwlJBftGVEr2VCdPKjKregm', '1', '1', '2026-03-09 23:48:38'),
('78', '4245736', 'SORIANO', 'RENANTE R. SORIANO', 'CID', 'Public Schools District Supervisor', 'CID CHIEF', '4245736_RENANTE_R_SORIANO.png', 'employee', '$2y$10$1Noo/WwKO1Fq9QW.lKquHuHbZsFvNPYp6iZkt8/tCN96o/rRlYg0q', '1', '1', '2026-03-09 23:48:38'),
('79', '4360263', 'TEC', 'JOHN DANIEL P. TEC', 'CID', 'Public Schools District Supervisor', 'CID CHIEF', '4360263_JOHN_DANIEL_P_TEC.png', 'employee', '$2y$10$XGjRewCp.VxHItD6tWjL6e9KpiYQodR3jmANuRHX1XXjWjcfj0OP2', '1', '1', '2026-03-09 23:48:38'),
('80', '6519577', 'PECHAYCO', 'DOVAN MOISES L. PECHAYCO', 'OSDS', 'Administrative Assistant III', 'AO V - ADMINISTRATIVE', '1000008_DOVAN_PECHAYCO.png', 'employee', '$2y$10$494qmVSNh9NpN5kvb6rJGOC85rTayHxSsqcHFGlwX69qcfrpTAXFu', '1', '1', '2026-03-09 23:48:38'),
('81', '4184290', 'TOLENTINO', 'LUCIA F. TOLENTINO', 'CID', 'Public Schools District Supervisor', 'CID CHIEF', '4184290_LUCIA_F_TOLENTINO.png', 'employee', '$2y$10$eG7TbHtm9rENANUv9e40Gur89VmV6YrdvMX3Ayq/AjbYXVMg/MHFG', '1', '1', '2026-03-09 23:48:38'),
('82', '6382031', 'BRITOS', 'SHIRLEY J. BRITOS', 'CID', 'Public Schools District Supervisor', 'CID CHIEF', '6382031_SHIRLEY_J_BRITOS.png', 'employee', '$2y$10$GDh3nPqjFyi7EEUK3DTAu.olimmpInLoSRc0VgCtuVuI67XMKMWii', '1', '1', '2026-03-09 23:48:38'),
('83', '4560318', 'PLACINO', 'MERLINA J. PLACINO', 'CID', 'Public Schools District Supervisor', 'CID CHIEF', '4560318_MERLINA_J_PLACINO.png', 'employee', '$2y$10$X1MiANvBYgO3bvd4r0LLOOAoBhRZYl0lNZOkePrzRqgdNDbLsEqLy', '1', '1', '2026-03-09 23:48:38'),
('84', '4428856', 'MIRASOL', 'VENANCIO T. MIRASOL', 'CID', 'Public Schools District Supervisor', 'CID CHIEF', '4428856_VENANCIO_T_MIRASOL.png', 'employee', '$2y$10$qlOtyAhV1NWFHs3uXYe6S.YBoYC1ZchnpexRdJ872pS23733aeyyC', '1', '1', '2026-03-09 23:48:38'),
('85', '4253275', 'CARRILLO', 'EMMA E. CARRILLO', 'CID', 'Public Schools District Supervisor', 'CID CHIEF', '4253275_EMMA_E_CARRILLO.png', 'employee', '$2y$10$RAySd02YTHKX1eRr0Ul8z.oW.F3dS.r7GANeLO/CkVtaJBQ1g/zlu', '1', '1', '2026-03-09 23:48:38'),
('86', '4177257', 'RAMIREZ', 'REGINA N. RAMIREZ', 'CID', 'Public Schools District Supervisor', 'CID CHIEF', '4177257_REGINA_N_RAMIREZ.png', 'employee', '$2y$10$IdzWtIOKHPbiN9d6xNPxdumlnR50m6mHFI3SBeY.OmO24.9Jmv8Ka', '1', '1', '2026-03-09 23:48:38'),
('87', '4423948', 'MABALE', 'ROSALIE M. MABALE', 'CID', 'Public Schools District Supervisor', 'CID CHIEF', '4423948_ROSALI_MABALE.png', 'employee', '$2y$10$UCHcy3qeJyS3s0wm4790H.l.fG07AC6BxHxwnO/1H68KGCcH1quSS', '1', '1', '2026-03-09 23:48:38'),
('88', '4401488', 'AMIL', 'EMELINDA O. AMIL', 'CID', 'Public Schools District Supervisor', 'CID CHIEF', '4401488_emelinda_amil.png', 'employee', '$2y$10$g9Z61MeND/8qzGuPt2qBBOwQV9hWq2EGi.QrnFA30TQhJhRkOJgxi', '1', '1', '2026-03-09 23:48:38'),
('89', '4648268', 'BANAAG', 'APRIL CLAIRE  P. MANLANGIT - BANAAG', 'CID', 'Education Program Specialist II ALS', 'CID CHIEF', '4648268_april_claire_manlangit_banaag.png', 'employee', '$2y$10$h78pLEOnGwBIH0xqjuwTX.AuNSFGvmHcbQT2DW94rQbJQww3fSDdG', '1', '1', '2026-03-09 23:48:39'),
('90', '4650404', 'MIRONDO', 'ROWENA JUNE B. MIRONDO', 'CID', 'Education Program Specialist II ALS', 'CID CHIEF', '4650404_ROWENA_MIRONDO.png', 'employee', '$2y$10$6Imfz2jKJFIX.B8TXoGUp.pYDk/mOvG/3E4HwCrMmx.390yeBydTG', '1', '1', '2026-03-09 23:48:39'),
('91', '6459296', 'ALORA', 'CARL ANTHONY B. ALORA', 'CID', 'Librarian II', 'CID CHIEF', '6459296_CARL_ANTHONY_B_ALORA.png', 'employee', '$2y$10$TXUeyU.ApYMgxPLsK5Rmme.BMMS9kqgADK9WRyk./Tm8eltBw8Wt2', '1', '1', '2026-03-09 23:48:39'),
('92', '5499944', 'TORRENUEVA', 'RICKY P. TORRENUEVA', 'CID', 'Education Program Supervisor LRMDS', 'CID CHIEF', '5499944_RICKY_P_TORRENUEVA.png', 'employee', '$2y$10$MAkmeKNkv6pnlSEibGi1..Onyk9lFQ4oK6nHtjw0svzueRnJ1iKRW', '1', '1', '2026-03-09 23:48:39'),
('93', '6465299', 'SORIANO', 'MARY GRACE O. SORIANO', 'CID', NULL, 'CID CHIEF', NULL, 'employee', '$2y$10$RgYyyvZ39lzrH8I0yt4JW.Htyws0MZSLDB7AorBEzBykb3ihBR41O', '1', '1', '2026-03-09 23:48:39'),
('94', '1000029', 'DELFIN', 'MARY GLECER D. DELFIN', 'CID', 'LSB Clerk', 'CID CHIEF', '1000029_MARY_GLECER_D_DELFIN.png', 'employee', '$2y$10$Yq55DSMlcpH6uYpSkQSkiucW4tsgmc9TilUaBRYmhOYKN8ykGxZLe', '1', '1', '2026-03-09 23:48:39'),
('95', '1000030', 'BASILAN', 'BRIANNE B. BASILAN', 'CID', 'LSB Clerk', 'CID CHIEF', '1000030_BRIANNE_BASILAN.png', 'employee', '$2y$10$MDTvpftimnok83FDeTuzS.yFIvF28AJLxiKnQoOZZK0XNyzf2juz2', '1', '1', '2026-03-09 23:48:39'),
('96', '1000031', 'JR.', 'WILLIAM C. TEMPROSA JR.', 'OSDS', 'LSB Watchman', 'AO V - ADMINISTRATIVE', '1000031_WILLIAM_TEMPROSA.png', 'employee', '$2y$10$yE7.l.3rnOuDvSlwrkLfM.uF4sGKOIYvqU58qr2WSxJ0S4Ta0zrtS', '1', '1', '2026-03-09 23:48:39'),
('97', '1000019', 'IRINCO', 'ROWENA P. IRINCO', 'OSDS', 'LSB Utility', 'AO V - ADMINISTRATIVE', '1000019_ROWENA_IRINCO.png', 'employee', '$2y$10$iF5t44xSQVaQahYv/Q2eXe6Y4e36nd84RCxnITW4FYhxRl7mCjY1K', '1', '1', '2026-03-09 23:48:39'),
('98', '1000020', 'CARDAÑO', 'EVANGELINE F. CARDAÑO', 'OSDS', 'LSB Watchman', 'AO V - ADMINISTRATIVE', '1000020_EVANGELINE_F__CARDA__O.png', 'employee', '$2y$10$05I36bq.WwOLUPdmKlGTa.0T9uwh9h9Fh2ijlIlh1RCFvroHDjyLS', '1', '1', '2026-03-09 23:48:39'),
('99', '1000021', 'PENTECOSTE', 'JEFFREY D. PENTECOSTE', 'OSDS', 'LSB Utility', 'AO V - ADMINISTRATIVE', '1000021_JEFFREY_PENTECOSTE.png', 'employee', '$2y$10$pUlgI9JuuDKM4QeR6sTLW.ZzzE3uFUHFMDRh8jAjytiNvkTQH04Q6', '1', '1', '2026-03-09 23:48:39'),
('100', '1000022', 'MANALO', 'ABEL A. MANALO', 'OSDS', 'LSB Watchman', 'AO V - ADMINISTRATIVE', '1000022_ABEL_A_MANALO.png', 'employee', '$2y$10$4qpZ95JzM54siC1koKi3bOB.lOKDTCF7mwkaolEOP.evz3KeKWMdm', '1', '1', '2026-03-09 23:48:39'),
('101', '1000023', 'JR.', 'JAIME O. AMIL JR.', 'OSDS', NULL, 'AO V - ADMINISTRATIVE', '1000023_JAIME_O_AMIL_JR.png', 'employee', '$2y$10$ycaX5RHaxGBd/ny0r2WqOulcxROSmwA3.11ktUoQVgnyUXhoOpJDm', '1', '1', '2026-03-09 23:48:39'),
('102', '1000025', 'REMOQUILLO', 'CHRISTIAN JAMES C. REMOQUILLO', 'OSDS', 'LSB Clerk', 'AO V - ADMINISTRATIVE', '1000025_CHRISTIAN_REMOQUILLO.png', 'employee', '$2y$10$ekHF7wqLUGAly5KScyotgeNdqbgEsEX9kvqDPJ9QH7hg5..a/EJ3i', '1', '1', '2026-03-09 23:48:39'),
('103', '1000026', 'PENTECOSTE', 'JOHN EDWARD I. PENTECOSTE', 'OSDS', 'LSB Driver', 'AO V - ADMINISTRATIVE', '1000026_JOHN_EDWARD_PENTECOSTE.png', 'employee', '$2y$10$4KAUJ8wHLSLtbMI3S0SpIO./Df4oFNr3luL4K8SYBX2bvjARTcy5.', '1', '1', '2026-03-09 23:48:39');

INSERT INTO `users` (`id`, `employee_id`, `last_name`, `full_name`, `functional_division`, `position`, `recommending`, `id_picture`, `role`, `password`, `is_active`, `must_change_password`, `created_at`) VALUES
('104', '1000027', 'ABIAD', 'MARIANO L. ABIAD', 'OSDS', 'LSB Watchman', 'AO V - ADMINISTRATIVE', '1000010_MARIANO_ABIAD.png', 'employee', '$2y$10$5yjdLd7pG.67528Kvgveh.ctUAhSAP16lN8jrmM2N5PE8fy8dcEEe', '1', '1', '2026-03-09 23:48:39'),
('105', '1000028', 'POLIQUIT', 'CARLYLE POLIQUIT', 'OSDS', 'LSB Utility', 'AO V - ADMINISTRATIVE', '1000028_CARYLE_POLIQUIT.png', 'employee', '$2y$10$KolayjpsQZKcerTZ7QdKsuZ0G2Qch5ZmICIM6/0Jf0SmwjWMP9Ry2', '1', '1', '2026-03-09 23:48:39'),
('106', '6472855', 'METRAN', 'MARLON P. METRAN', 'OSDS', NULL, 'AO V - ADMINISTRATIVE', '6472855_MARLON_P__METRAN.png', 'employee', '$2y$10$aPuuad0oaf0hpTrIW8ZftuXkrjBHvDYNS2siZkUx/JW8hj7xLCHKC', '1', '1', '2026-03-09 23:48:39'),
('107', '1000034', 'TAPIA', 'CLAUDINE S. TAPIA', 'CID', NULL, 'CID CHIEF', '1000034_CLAUDINE_TAPIA.png', 'employee', '$2y$10$eRbWYoL4M.Wqrjs/IkDSeuJjkerQrQ5fRUEx3ejVmIKbBS9.VJu0i', '1', '1', '2026-03-09 23:48:39'),
('108', '1000035', 'MAGO', 'CARLO MAGO', 'OSDS', 'Division Driver', 'AO V - ADMINISTRATIVE', '1000035_carlo_mago.png', 'employee', '$2y$10$rkfsfwc69M9a5WWTsEO1W.nSQFY8JPjZ0luUsrbrQV9pzkHsATHgm', '1', '1', '2026-03-09 23:48:39'),
('109', '6464868', 'JASA', 'JAYPEE Q. JASA', 'OSDS', NULL, 'AO V - ADMINISTRATIVE', '6464868_JAYPEE_JASA.png', 'employee', '$2y$10$BkTSn9uSdE0WpBBaMtyQNO2/ALB0V6lO4ctxxmTrYuq6842UMzwXi', '1', '1', '2026-03-09 23:48:40'),
('110', '6459605', 'FIEDALAN', 'JESSA M. FIEDALAN', 'OSDS', 'Administrative Assistant III', 'AO V - ADMINISTRATIVE', '6459605_jessa_fiedalan.png', 'employee', '$2y$10$O/i1g6kxQcj0c4oi7rQTXuwXvHby/jRoYClXiL1b99PJuDQQyrP7K', '1', '1', '2026-03-09 23:48:40'),
('111', '12345', 'Access', 'Test Access', 'OSDS', NULL, 'AO V - ADMINISTRATIVE', '12345_Test_Access.png', 'employee', '$2y$10$2ww2mTGgZtvt1jZd9sb0M.hKizLydGFA/4zF9UzuUG3xXAd5er/GC', '1', '1', '2026-03-09 23:48:40'),
('112', '1000032', 'Lozande', 'Rose Jean Lozande', 'SGOD', 'Administrative Support II', 'SGOD CHIEF', '1000032_ROSE_JEAN_LOZANDE.png', 'employee', '$2y$10$cuE8VDCN./FgRqHhF1JLcel88rHIDZm0onDfpD/fHPKlR82zuqJ1.', '1', '1', '2026-03-09 23:48:40'),
('113', '6475363', 'VALDERAMA', 'CHARLYN JOY N. VALDERAMA', 'OSDS', 'Administrative Assistant I', 'AO V - ADMINISTRATIVE', '6475363_charlynjoy_valderrama.png', 'employee', '$2y$10$0iHWqY8c/UKGewEm0x1Wte2iMu/7MLXiW1isrQbAR2QKmFzujXRNe', '1', '1', '2026-03-09 23:48:40'),
('114', '1000037', 'AUSTRIA', 'MARVIN R. AUSTRIA', 'OSDS', 'LSB Clerk', 'AO V - ADMINISTRATIVE', '1000037_marvin_austria.png', 'employee', '$2y$10$7qhLf8r2sXuSgsMdcZVg2OtWMF7it/bPv8y1enzt0F2r4qr5yeaNa', '1', '1', '2026-03-09 23:48:40'),
('115', '1000038', 'MACALINAO', 'MARIA KLARIES R. MACALINAO', 'OSDS', 'LSB Clerk', 'AO V - ADMINISTRATIVE', '1000038_mk_macalinao.png', 'employee', '$2y$10$TumRFaDnovbfH5QwsE2zDeZyb.4vw/ggMfk53LZ4OAMoigyZnkyQO', '1', '1', '2026-03-09 23:48:40'),
('116', '6519567', 'ZAPA', 'MARY VIANNEY R. ZAPA', 'SGOD', 'Medical Officer III', 'SGOD CHIEF', '6519567_mary_vianney_zapa.png', 'employee', '$2y$10$70eef6jagBT/9RoLWj5aOeLtacb9pGBjPAr.fDOX.cazrj0DDeKtS', '1', '1', '2026-03-09 23:48:40'),
('117', '1000039', 'JR.', 'ROLANDO YABUT JAGONASE JR.', 'OSDS', 'LSB Utility', 'AO V - ADMINISTRATIVE', '1000039_rolando_jagonase.png', 'employee', '$2y$10$4ZrQzu9ZlJptA5816TMQw.zJlbmStkl.KggjIVHt3hIVRgZyI1zRq', '1', '1', '2026-03-09 23:48:40'),
('118', '1000040', 'MACALOS', 'KAREN B MACALOS', 'OSDS', 'LSB Clerk', 'AO V - ADMINISTRATIVE', '1000040_karen_macalos.png', 'employee', '$2y$10$tE1OAbjKgoy9LOyYqE4mf.EJM35EegSNoSnWUHDeuDZQ6gJnNw/6G', '1', '1', '2026-03-09 23:48:40'),
('119', '6519576', 'TABRILLA', 'CHRISTOPHER JOHN C. TABRILLA', 'OSDS', 'Administrative Aide VI', 'AO V - ADMINISTRATIVE', '6519576_christopher_john_tabrilla.png', 'employee', '$2y$10$rAs6Q63Dd47dGxDDi4WXjumZ/W.hJbmoMkqHLly4sFz9PFs.7ZJp6', '1', '1', '2026-03-09 23:48:40'),
('120', '1000043', 'Brazas', 'Princess Leanna Brazas', 'SGOD', 'LSB Clerk', 'SGOD CHIEF', '1000043_princess_leanna_brazas.png', 'employee', '$2y$10$2Rt5krHEanbGs/UQwBk43eaFjlc3FvoKXxDaGf0Fws75GX3qT5a96', '1', '1', '2026-03-09 23:48:40'),
('121', '4537187', 'CONSUELO', 'JOE BREN L. CONSUELO', 'OSDS', 'OIC Assistant Schools Division Superintendent', 'SCHOOLS DIVISION SUPERINTEDENT  ', '4537187_JOE_BREN_L__CONSUELO.png', 'employee', '$2y$10$OgFti0DhKypW.0H5aNClsO2yuT0Q7h6GD9Nrlh.r8AgV19er5bTU.', '1', '1', '2026-03-09 23:48:40'),
('122', '6519580', 'BORLAZA', 'URAYJAN M. BORLAZA', 'CID', 'Planning Development Officer II', 'CID CHIEF', '6519580_urayjan_borlaza.png', 'employee', '$2y$10$jAZ4G9IQ5UvKZy10GHYnGuUT3EfQMnMHErf6hKBnXRmg3tUkyI.ja', '1', '1', '2026-03-09 23:48:40'),
('123', '6404956', 'CRUZ', 'SARAH LYNNE G. DELA CRUZ', 'OSDS', 'Administrative Officer IV', 'AO V - ADMINISTRATIVE', NULL, 'employee', '$2y$10$4CaqiRMdt0mqCaxJbRt4T..cgNHmDtVRabBk8GgqoMmU3IjmTs6rG', '1', '1', '2026-03-09 23:48:40'),
('124', '6519581', 'OLIVENZA', 'PAMELA M. OLIVENZA', 'OSDS', 'Administrative Aide VI', 'AO V - ADMINISTRATIVE', '6519581_Pamela_Olivenza.png', 'employee', '$2y$10$tI4xGh3XEDR2xX5184Izo.8ptyoqxkZ54/X88NuRjXtAdA/h6k92u', '1', '1', '2026-03-09 23:48:40'),
('125', '4176916', 'ESCUADRO', 'ENCARNACION T. ESCUADRO', 'SGOD', NULL, 'SGOD CHIEF', NULL, 'employee', '$2y$10$M99T.8vDa.XLW98OEp4g4.1K9CBVdAy9BtM4w3ffNpFFiT46QpBAG', '1', '1', '2026-03-09 23:48:40'),
('126', '1000042', 'BACARESAS', 'CEDRICK V. BACARESAS', 'OSDS', 'On Job Training', 'AO V - ADMINISTRATIVE', '1000042_Cedrick_Bacaresas.png', 'employee', '$2y$10$FLZEXmm1AKBMqUCf50yBvORoql9JZQyDswagtQY6WB/6Z6gfeTUfe', '1', '1', '2026-03-09 23:48:40'),
('127', '1000046', 'PINEDES', 'REDGINE A. PINEDES', 'OSDS', 'On Job Training', 'AO V - ADMINISTRATIVE', '1000046_Redgine_Pinedes.png', 'employee', '$2y$10$DT8Io7V6aLSt.TX0PU8LG.1JU4snn0GE02cGnzPkmI4yzdV0Mi9vO', '1', '0', '2026-03-09 23:48:40'),
('128', '1000044', 'LOVERES', 'ALGEN D. LOVERES', 'OSDS', 'On Job Training', 'AO V - ADMINISTRATIVE', '1000044_Algen_Loveres.png', 'employee', '$2y$10$Hux/1.7rAgQaASitv0dOcO7.LJnFYq6ljNEhz3O5b2pXzkW3fxeu.', '1', '0', '2026-03-09 23:48:41'),
('129', '1000045', 'ESCALLENTE', 'ALEXANDER  JOERENZ E. ESCALLENTE', 'OSDS', 'On Job Training', 'AO V - ADMINISTRATIVE', '1000045_Alexander_escallente.png', 'employee', '$2y$10$aPptZXfUYRC7YMGLbkkQ8eGY2AZS5orIcOaQLL9Sc7wU3PALkwwCK', '1', '0', '2026-03-09 23:48:41'),
('130', '1000047', 'FRANCISCO', 'ELAINE JULIA FRANCISCO', 'OSDS', 'LSB Clerk', 'AO V - ADMINISTRATIVE', NULL, 'hr_timekeeping', '$2y$10$lVXzPYzEkZ4vQITlkVVnPeMf5WGlym2wQ.yz1hF0SHxZDDjstgETy', '1', '0', '2026-03-09 23:48:41'),
('131', '1000048', 'CRUZ', 'JOHN DANNRILL CRUZ', 'OSDS', 'LSB Clerk', 'AO V - ADMINISTRATIVE', NULL, 'employee', '$2y$10$S5ZE.0ki8KRhUIlZh8BOT.1rim5cvi7IceYbdPD9mBtenfZyZeaLi', '1', '1', '2026-03-09 23:48:41'),
('132', '1000049', 'LIMOSINERO', 'SHAIRA MAE REYES LIMOSINERO', 'OSDS', 'LSB Clerk', 'AO V - ADMINISTRATIVE', NULL, 'employee', '$2y$10$cVZkHFVYDxGLEw/K9vT4PuzoRmWQbFlug1O0/rM82nBkcNfMeIsf.', '1', '1', '2026-03-09 23:48:41'),
('133', '6457105', 'BITUIN', 'JOHN CARLO A. BITUIN', 'SGOD', NULL, 'SGOD CHIEF', NULL, 'employee', '$2y$10$rtx1MPt9kXNb.8lAVZELSOhNbcjp7kfgYhEFwg8enPb9UEIY0IZGa', '1', '1', '2026-03-09 23:48:41'),
('134', '1000050', 'PEDERNAL', 'ALEXIA JASNEL S. PEDERNAL', 'SGOD', NULL, 'SGOD CHIEF', NULL, 'employee', '$2y$10$Bk1Lph8Xrh0JqJ0SDqIX/e/cSfoEgX.ZhbYUxcdB26EAaRhJ5bGom', '1', '1', '2026-03-09 23:48:41'),
('135', '1000051', 'RAMOS', 'MEL APRYLL A. RAMOS', 'SGOD', NULL, 'SGOD CHIEF', NULL, 'employee', '$2y$10$fZ9cuW6JoX702aE5VUgktufStAw8k8M9hUvzBo7TguMNVHyhLNE8u', '1', '1', '2026-03-09 23:48:41'),
('136', '4128196', 'FAJARDO', 'EDITA R. FAJARDO', 'CID', NULL, 'CID CHIEF', NULL, 'employee', '$2y$10$OxW2FPs81aISFzLBicsLLulYc9lX2qRlrZgBtNJkQPCozatD6cviO', '1', '1', '2026-03-09 23:48:41'),
('137', '1000052', 'JR.', 'HIPOLITO M. FERNANDEZ JR.', 'SGOD', NULL, 'SGOD CHIEF', NULL, 'employee', '$2y$10$0PBk2gtWZ/VDXpQTXB/dTu42enjHMR5X62NZQFmR0HBXz5KxuC5vG', '1', '1', '2026-03-09 23:48:41');

SET FOREIGN_KEY_CHECKS=1;
