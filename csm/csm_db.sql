-- phpMyAdmin SQL Dump
-- version 5.1.1deb5ubuntu1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 12, 2026 at 11:33 AM
-- Server version: 8.0.45-0ubuntu0.22.04.1
-- PHP Version: 8.1.2-1ubuntu2.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `csm_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `csm_submission`
--

CREATE TABLE `csm_submission` (
  `age` tinyint NOT NULL,
  `sex` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `customer_type` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `offices` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `sub_offices` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `services` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `cc1` enum('Yes','No') COLLATE utf8mb4_general_ci NOT NULL,
  `cc2` enum('Yes','No') COLLATE utf8mb4_general_ci NOT NULL,
  `cc3` enum('Yes','No') COLLATE utf8mb4_general_ci NOT NULL,
  `sqd1` tinyint NOT NULL,
  `sqd2` tinyint NOT NULL,
  `sqd3` tinyint NOT NULL,
  `sqd4` tinyint NOT NULL,
  `sqd5` tinyint NOT NULL,
  `sqd6` tinyint NOT NULL,
  `sqd7` tinyint NOT NULL,
  `sqd8` tinyint NOT NULL,
  `remarks` text COLLATE utf8mb4_general_ci NOT NULL,
  `control_number` bigint NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `school_office` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `csm_submission`
--

INSERT INTO `csm_submission` (`age`, `sex`, `customer_type`, `offices`, `sub_offices`, `services`, `cc1`, `cc2`, `cc3`, `sqd1`, `sqd2`, `sqd3`, `sqd4`, `sqd5`, `sqd6`, `sqd7`, `sqd8`, `remarks`, `control_number`, `full_name`, `school_office`, `created_at`) VALUES
(49, 'Female', 'Government', 'Information and Communication Technology Office', '', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 5, 5, 5, 5, '', 10100071596897, '', '', '2026-02-05 08:55:33'),
(39, 'Female', 'Government', 'Admin (Cash, Personnel, Records, Supply, General Services, Procurement)', 'General Services', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 5, 5, 5, 5, '', 11846524929910, 'KAREN MACALOS', 'OSDS', '2026-02-04 07:09:30'),
(27, 'Female', 'Government', 'Information and Communication Technology Office', '', 'Troubleshooting of ICT equipment', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 0, 5, 5, 5, 'Mabilis pong nag assist ', 11923273953699, '', '', '2026-02-04 06:52:23'),
(36, 'Male', 'Citizen', 'Legal Office', '', 'Correction of Entries in School Record', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 0, 5, 5, 5, '', 12599450825239, 'Ronrick I. Omalin', 'Landayan Elementary School', '2026-03-05 02:39:10'),
(36, 'Male', 'Citizen', 'Legal Office', '', 'Correction of Entries in School Record', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 0, 5, 5, 5, '', 14390812583997, 'Ronrick I. Omalin', 'Landayan Elementary School', '2026-03-05 02:39:24'),
(49, 'Female', 'Government', 'Information and Communication Technology Office', '', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 5, 5, 5, 5, '', 19399010874110, '', '', '2026-02-05 08:55:31'),
(31, 'Female', 'Government', 'Finance (Accounting, Budget)', 'Budget', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 0, 5, 5, 5, 'Very accommodating', 20047781239129, '', '', '2026-03-12 01:25:32'),
(23, 'Female', 'Government', 'Admin (Cash, Personnel, Records, Supply, General Services, Procurement)', 'Property and Supply', 'Request/Issuance of Supplies', 'No', 'No', 'No', 5, 5, 5, 5, 0, 5, 5, 5, '', 20136227237288, '', '', '2026-02-13 08:30:35'),
(34, 'Female', 'Government', 'CID - Curriculum Implementation Division (LRMS, Instructional Management, PSDS)', 'Alternative Learning System', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 0, 5, 5, 5, 'Thank you for accomodating our requests!', 23399583240915, 'ROWENA JUNE B. MIRONDO', 'CID', '2026-02-13 01:33:57'),
(32, 'Male', 'Government', 'Finance (Accounting, Budget)', 'Budget', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 0, 5, 5, 5, 'Smooth Workshop Seminar', 23801997796277, '', '', '2026-02-18 06:59:08'),
(34, 'Female', 'Government', 'CID - Curriculum Implementation Division (LRMS, Instructional Management, PSDS)', 'Alternative Learning System', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 0, 5, 5, 5, 'Thank you for accommodating our requests!', 24564695580340, 'ROWENA JUNE B. MIRONDO', 'CID', '2026-02-13 01:34:20'),
(48, 'Not Specified', 'Government', 'Admin (Cash, Personnel, Records, Supply, General Services, Procurement)', 'Property and Supply', 'Property and Equipment Clearance', 'No', 'No', 'No', 5, 5, 5, 5, 0, 5, 5, 5, '', 27577535412143, '', '', '2026-02-13 08:37:19'),
(34, 'Female', 'Government', 'CID - Curriculum Implementation Division (LRMS, Instructional Management, PSDS)', 'Alternative Learning System', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 0, 5, 5, 5, 'Thank you for accomodating our requests!', 28138931137930, 'ROWENA JUNE B. MIRONDO', 'CID', '2026-02-13 01:34:00'),
(33, 'Male', 'Government', 'Admin (Cash, Personnel, Records, Supply, General Services, Procurement)', 'Property and Supply', 'Property and Equipment Clearance', 'No', 'No', 'No', 5, 5, 5, 5, 0, 5, 5, 5, '', 29464305217648, '', '', '2026-02-13 08:38:20'),
(27, 'Female', 'Government', 'Admin (Cash, Personnel, Records, Supply, General Services, Procurement)', 'Records', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 4, 4, 4, 4, 0, 4, 4, 4, '', 34628609123788, 'Micah Joy O. Alao', 'Records', '2026-02-02 01:00:03'),
(32, 'Not Specified', 'Government', 'Admin (Cash, Personnel, Records, Supply, General Services, Procurement)', 'Property and Supply', 'Property and Equipment Clearance', 'No', 'No', 'No', 5, 5, 5, 5, 0, 5, 5, 5, '', 36169342170701, '', '', '2026-02-13 08:33:29'),
(39, 'Female', 'Government', 'Information and Communication Technology Office', '', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 0, 5, 5, 5, 'great and fast service, thank you so much', 36922142609604, '', '', '2026-01-30 03:30:10'),
(43, 'Not Specified', 'Government', 'Admin (Cash, Personnel, Records, Supply, General Services, Procurement)', 'Property and Supply', 'Property and Equipment Clearance', 'No', 'No', 'No', 4, 4, 4, 4, 0, 4, 4, 4, '', 37312283793009, '', '', '2026-02-13 08:35:28'),
(34, 'Female', 'Government', 'CID - Curriculum Implementation Division (LRMS, Instructional Management, PSDS)', 'Alternative Learning System', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 0, 5, 5, 5, 'Thank you for accommodating our requests!', 37615336654620, 'ROWENA JUNE B. MIRONDO', 'CID', '2026-02-13 01:34:23'),
(32, 'Male', 'Government', 'Information and Communication Technology Office', '', 'Troubleshooting of ICT equipment', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 5, 5, 5, 5, '2nd Sample', 38184757508291, 'Eljohn Beleta', 'Division Office', '2026-01-27 01:39:24'),
(34, 'Female', 'Government', 'CID - Curriculum Implementation Division (LRMS, Instructional Management, PSDS)', 'Alternative Learning System', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 0, 5, 5, 5, 'Thank you for accommodating our requests!', 40339990623786, 'ROWENA JUNE B. MIRONDO', 'CID', '2026-02-13 01:34:06'),
(23, 'Female', 'Government', 'Information and Communication Technology Office', '', 'Troubleshooting of ICT equipment', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 0, 5, 5, 5, 'Fast transaction, all of the staff are one call away! Thank you so much Sir JR and Sir EJ. ', 40960977939515, '', '', '2026-02-18 07:15:53'),
(23, 'Male', 'Government', 'Finance (Accounting, Budget)', 'Budget', 'ORS-Obligation Request and Status', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 1, 5, 5, 5, '', 43963776512473, '', '', '2026-02-13 02:02:47'),
(61, 'Female', 'Government', 'Information and Communication Technology Office', '', 'Troubleshooting of ICT equipment', 'Yes', 'Yes', 'Yes', 4, 4, 0, 3, 0, 3, 3, 4, '', 45428326266693, 'Carolina B. Alcantara', 'OSDS-Budget Unit', '2026-02-05 07:44:15'),
(23, 'Male', 'Government', 'Finance (Accounting, Budget)', 'Budget', 'ORS-Obligation Request and Status', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 1, 5, 5, 5, '', 47737656644094, '', '', '2026-02-13 01:57:18'),
(49, 'Female', 'Government', 'Information and Communication Technology Office', '', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 5, 5, 5, 5, '', 47961900473325, '', '', '2026-02-05 08:55:29'),
(27, 'Female', 'Government', 'Information and Communication Technology Office', '', 'Troubleshooting of ICT equipment', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 0, 5, 5, 5, 'Mabilis pong nag assist ', 49333679632293, '', '', '2026-02-04 06:52:30'),
(39, 'Not Specified', 'Government', 'Admin (Cash, Personnel, Records, Supply, General Services, Procurement)', 'Property and Supply', 'Property and Equipment Clearance', 'No', 'No', 'No', 5, 5, 5, 5, 0, 5, 5, 5, '', 49748139604923, '', '', '2026-02-13 08:31:27'),
(23, 'Male', 'Government', 'Finance (Accounting, Budget)', 'Budget', 'ORS-Obligation Request and Status', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 1, 5, 5, 5, '', 50116759746740, '', '', '2026-02-13 02:05:09'),
(23, 'Male', 'Government', 'Finance (Accounting, Budget)', 'Budget', 'ORS-Obligation Request and Status', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 1, 5, 5, 5, '', 50728431840446, '', '', '2026-02-13 01:55:14'),
(23, 'Male', 'Government', 'Finance (Accounting, Budget)', 'Budget', 'ORS-Obligation Request and Status', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 1, 5, 5, 5, '', 52159200558176, '', '', '2026-02-13 01:56:42'),
(49, 'Female', 'Government', 'Information and Communication Technology Office', '', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 5, 5, 5, 5, '', 52829003312823, '', '', '2026-02-05 08:55:33'),
(23, 'Male', 'Government', 'Finance (Accounting, Budget)', 'Budget', 'ORS-Obligation Request and Status', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 1, 5, 5, 5, '', 55681929830430, '', '', '2026-02-13 01:57:56'),
(23, 'Female', 'Government', 'Admin (Cash, Personnel, Records, Supply, General Services, Procurement)', 'Personnel', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 5, 5, 5, 5, '', 60888019777962, 'Emellou Oribia', 'OSDS - Personnel Unit', '2026-02-13 02:19:40'),
(34, 'Not Specified', 'Government', 'Admin (Cash, Personnel, Records, Supply, General Services, Procurement)', 'Property and Supply', 'Request/Issuance of Supplies', 'No', 'No', 'No', 5, 5, 5, 5, 0, 5, 5, 5, '', 65146897308239, '', '', '2026-02-13 08:28:20'),
(23, 'Male', 'Government', 'Admin (Cash, Personnel, Records, Supply, General Services, Procurement)', 'Procurement', 'Procurement-related', 'Yes', 'Yes', 'Yes', 1, 1, 1, 1, 0, 1, 1, 1, '.', 66425793102690, 'John Dannrill Cruz', 'Procurement ', '2026-02-05 08:08:04'),
(38, 'Not Specified', 'Government', 'Admin (Cash, Personnel, Records, Supply, General Services, Procurement)', 'Property and Supply', 'Property and Equipment Clearance', 'No', 'No', 'No', 5, 5, 5, 5, 0, 5, 5, 5, '', 67908514869033, '', '', '2026-02-13 08:32:34'),
(39, 'Female', 'Government', 'Admin (Cash, Personnel, Records, Supply, General Services, Procurement)', 'General Services', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 5, 5, 5, 5, '', 71014703740463, 'KAREN MACALOS', 'OSDS', '2026-02-04 07:09:27'),
(34, 'Male', 'Government', 'Information and Communication Technology Office', '', 'Troubleshooting of ICT equipment', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 5, 5, 5, 5, 'sample remarks', 71224881165288, 'Joe-Bren Consuelo', 'Division Office', '2026-02-19 07:05:16'),
(32, 'Male', 'Government', 'Information and Communication Technology Office', '', 'Troubleshooting of ICT equipment', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 5, 5, 5, 5, 'Sample', 71654835957626, 'Eljohn Beleta', 'Division Office', '2026-01-27 01:15:41'),
(27, 'Female', 'Government', 'Information and Communication Technology Office', '', 'Troubleshooting of ICT equipment', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 0, 5, 5, 5, 'Mabilis pong nag assist ', 73256146078993, '', '', '2026-02-04 06:52:22'),
(27, 'Female', 'Government', 'Admin (Cash, Personnel, Records, Supply, General Services, Procurement)', 'Records', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 4, 4, 4, 4, 0, 4, 4, 4, '', 73453873988975, 'Micah Joy O. Alao', 'Records', '2026-02-03 06:09:07'),
(21, 'Male', 'Citizen', 'Information and Communication Technology Office', '', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 0, 5, 5, 5, 'SOLID MGA TAGA ICT ', 75000887084650, 'Alexander Joerenz Escallente', 'SAN PEDRO CITY POLYTECHNIC COLLEGE', '2026-02-13 07:47:40'),
(23, 'Female', 'Government', 'Admin (Cash, Personnel, Records, Supply, General Services, Procurement)', 'Property and Supply', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 0, 5, 5, 5, '', 81187671418626, '', '', '2026-03-10 06:25:51'),
(34, 'Female', 'Government', 'CID - Curriculum Implementation Division (LRMS, Instructional Management, PSDS)', 'Alternative Learning System', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 0, 5, 5, 5, 'Thank you for accommodating our requests!', 85553887914106, 'ROWENA JUNE B. MIRONDO', 'CID', '2026-02-13 01:34:08'),
(34, 'Male', 'Government', 'Information and Communication Technology Office', '', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 0, 5, 5, 5, 'GOODjob!', 87797384557981, 'Christopher Tabrilla', 'SDO San pedro City', '2026-01-29 00:58:21'),
(49, 'Female', 'Government', 'Information and Communication Technology Office', '', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 5, 5, 5, 5, '', 88397523759513, '', '', '2026-02-05 08:55:29'),
(31, 'Female', 'Government', 'Finance (Accounting, Budget)', 'Budget', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 0, 5, 5, 5, 'Very accommodating', 88964218642385, '', '', '2026-03-12 01:25:30'),
(37, 'Male', 'Government', 'Information and Communication Technology Office', '', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 0, 0, 0, 4, 0, 0, 5, 5, 'Thx Sir Eljohn for the assistance. ', 89325566261259, 'Mark P Sicat', 'ESTRELLA ELEMENTARY SCHOOL', '2026-02-03 00:54:28'),
(-1, 'Not Specified', 'Government', 'Admin (Cash, Personnel, Records, Supply, General Services, Procurement)', 'Property and Supply', 'Property and Equipment Clearance', 'No', 'No', 'No', 5, 5, 5, 5, 0, 5, 5, 5, '', 89435343236625, '', '', '2026-02-13 08:34:33'),
(36, 'Male', 'Citizen', 'Legal Office', '', 'Correction of Entries in School Record', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 0, 5, 5, 5, '', 90138838862637, 'Ronrick I. Omalin', 'Landayan Elementary School', '2026-03-05 02:39:16'),
(39, 'Female', 'Government', 'Admin (Cash, Personnel, Records, Supply, General Services, Procurement)', 'General Services', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 5, 5, 5, 5, '', 92940814059899, 'KAREN MACALOS', 'OSDS', '2026-02-04 07:09:28'),
(49, 'Female', 'Government', 'Information and Communication Technology Office', '', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 5, 5, 5, 5, '', 93343811677942, '', '', '2026-02-05 08:55:27'),
(49, 'Female', 'Government', 'Information and Communication Technology Office', '', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 5, 5, 5, 5, '', 94458775157306, '', '', '2026-02-05 08:55:32'),
(34, 'Not Specified', 'Government', 'Admin (Cash, Personnel, Records, Supply, General Services, Procurement)', 'Property and Supply', 'Request/Issuance of Supplies', 'No', 'No', 'No', 5, 5, 5, 5, 0, 5, 5, 5, '', 95056049398815, '', '', '2026-02-13 08:29:23'),
(44, 'Female', 'Government', 'Finance (Accounting, Budget)', 'Budget', 'Other requests/inquiries', 'Yes', 'Yes', 'Yes', 5, 5, 5, 5, 0, 5, 5, 5, 'Very accommodating, my queries was answered. Thank you budget unit!', 95274632753983, '', '', '2026-02-18 07:08:20'),
(0, 'Male', 'Government', 'Information and Communication Technology Office', '', 'Troubleshooting of ICT equipment', 'Yes', 'Yes', 'Yes', 4, 4, 4, 4, 0, 4, 4, 4, 'fast transaction.', 96112691163288, 'EDWIN JOSEPH DE PERALTA', 'SDO SAN PEDRO', '2026-02-23 06:03:00');

-- --------------------------------------------------------

--
-- Table structure for table `offices`
--

CREATE TABLE `offices` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offices`
--

INSERT INTO `offices` (`id`, `name`) VALUES
(1, 'SDS - Schools Division Superintendent'),
(2, 'ASDS - Assistant Schools Division Superintendent'),
(3, 'Admin (Cash, Personnel, Records, Supply, General Services, Procurement)'),
(4, 'CID - Curriculum Implementation Division (LRMS, Instructional Management, PSDS)'),
(5, 'Finance (Accounting, Budget)'),
(6, 'Information and Communication Technology Office'),
(7, 'Legal Office'),
(8, 'SGOD - School Governance and Operations Division (M&E, SocMob, Planning & Research, HRD, Facilities, School Health)');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `sub_office_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `sub_office_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `sub_office_name`, `sub_office_id`) VALUES
(1, 'Cash Advance', 'Cash', 1),
(2, 'Other requests/inquiries', 'Cash', 1),
(3, 'General Services-related', 'General Services', 5),
(4, 'Other requests/inquiries', 'General Services', 5),
(5, 'Procurement-related', 'Procurement', 6),
(6, 'Other requests/inquiries', 'Procurement', 6),
(7, 'Application - Teaching Position', 'Personnel', 2),
(8, 'Application - Non-teaching/Teaching-related', 'Personnel', 2),
(9, 'Appointment (new, promotion, transfer, etc.)', 'Personnel', 2),
(10, 'COE-Certificate of Employment', 'Personnel', 2),
(11, 'Correction of Name/Change of Status', 'Personnel', 2),
(12, 'ERF-Equivalent Record Form', 'Personnel', 2),
(13, 'Leave Application', 'Personnel', 2),
(14, 'Loan Approval and Verification', 'Personnel', 2),
(15, 'Retirement', 'Personnel', 2),
(16, 'Service Record', 'Personnel', 2),
(17, 'Terminal leave', 'Personnel', 2),
(18, 'Other requests/inquiries', 'Personnel', 2),
(19, 'CAV-Certification, Authentication, Verification', 'Records', 3),
(20, 'Certified True Copy (CTC)/Photocopy of documents', 'Records', 3),
(21, 'Non-Certified True Copy documents', 'Records', 3),
(22, 'Receiving & releasing of documents', 'Records', 3),
(23, 'Other requests/inquiries', 'Records', 3),
(24, 'Feedback/Complaint', 'Records', 3),
(25, 'Inspection/Acceptance/Distribution of LRs, Supplies, Equipment', 'Property and Supply', 4),
(26, 'Property and Equipment Clearance', 'Property and Supply', 4),
(27, 'Request/Issuance of Supplies', 'Property and Supply', 4),
(28, 'Other requests/inquiries', 'Property and Supply', 4),
(29, 'Access to LR Portal', 'LRMS - Learning Resource Management Section', 7),
(30, 'Borrowing of books/learning materials', 'LRMS - Learning Resource Management Section', 7),
(31, 'Contextualized Learning Resources', 'LRMS - Learning Resource Management Section', 7),
(32, 'Quality Assurance of Supplementary Learning Resources', 'LRMS - Learning Resource Management Section', 7),
(33, 'Instructional Supervision', 'Instructional Management Section', 8),
(34, 'Technical assistance', 'PSDS - Public School District Supervisor', 9),
(35, 'Other requests/inquiries', 'PSDS - Public School District Supervisor', 9),
(36, 'ALS Enrollment', 'Alternative Learning System', 18),
(37, 'Other requests/inquiries', 'Alternative Learning System', 18),
(38, 'Accounting-related', 'Accounting', 10),
(39, 'Posting/Updating of Disbursement', 'Accounting', 10),
(40, 'Other requests/inquiries', 'Accounting', 10),
(41, 'ORS-Obligation Request and Status', 'Budget', 11),
(42, 'Other requests/inquiries', 'Budget', 11);

-- --------------------------------------------------------

--
-- Table structure for table `sub_offices`
--

CREATE TABLE `sub_offices` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `office_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `office_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sub_offices`
--

INSERT INTO `sub_offices` (`id`, `name`, `office_name`, `office_id`) VALUES
(1, 'Cash', 'Admin (Cash, Personnel, Records, Supply, General Services, Procurement)', 3),
(2, 'Personnel', 'Admin (Cash, Personnel, Records, Supply, General Services, Procurement)', 3),
(3, 'Records', 'Admin (Cash, Personnel, Records, Supply, General Services, Procurement)', 3),
(4, 'Property and Supply', 'Admin (Cash, Personnel, Records, Supply, General Services, Procurement)', 3),
(5, 'General Services', 'Admin (Cash, Personnel, Records, Supply, General Services, Procurement)', 3),
(6, 'Procurement', 'Admin (Cash, Personnel, Records, Supply, General Services, Procurement)', 3),
(7, 'LRMS - Learning Resource Management Section', 'CID - Curriculum Implementation Division (LRMS, Instructional Management, PSDS)', 4),
(8, 'Instructional Management Section', 'CID - Curriculum Implementation Division (LRMS, Instructional Management, PSDS)', 4),
(9, 'PSDS - Public School District Supervisor', 'CID - Curriculum Implementation Division (LRMS, Instructional Management, PSDS)', 4),
(10, 'Accounting', 'Finance (Accounting, Budget)', 5),
(11, 'Budget', 'Finance (Accounting, Budget)', 5),
(12, 'Education Facilities', 'SGOD - School Governance and Operations Division (M&E, SocMob, Planning & Research, HRD, Facilities, School Health)', 8),
(13, 'HRD - Human Resource Development', 'SGOD - School Governance and Operations Division (M&E, SocMob, Planning & Research, HRD, Facilities, School Health)', 8),
(14, 'Planning & Research', 'SGOD - School Governance and Operations Division (M&E, SocMob, Planning & Research, HRD, Facilities, School Health)', 8),
(15, 'School Health', 'SGOD - School Governance and Operations Division (M&E, SocMob, Planning & Research, HRD, Facilities, School Health)', 8),
(16, 'SMME - School Management Monitoring and Evaluation Section', 'SGOD - School Governance and Operations Division (M&E, SocMob, Planning & Research, HRD, Facilities, School Health)', 8),
(17, 'SocMob - Social Mobilization and Networking', 'SGOD - School Governance and Operations Division (M&E, SocMob, Planning & Research, HRD, Facilities, School Health)', 8),
(18, 'Alternative Learning System', 'CID - Curriculum Implementation Division (LRMS, Instructional Management, PSDS)', 4);

-- --------------------------------------------------------

--
-- Table structure for table `unit_services`
--

CREATE TABLE `unit_services` (
  `office_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `office_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `unit_services`
--

INSERT INTO `unit_services` (`office_name`, `id`, `name`, `office_id`) VALUES
('Information and Communication Technology Office', 1, 'Create/delete/rename/reset user accounts', 6),
('Information and Communication Technology Office', 2, 'Troubleshooting of ICT equipment', 6),
('Information and Communication Technology Office', 3, 'Uploading of publications', 6),
('Information and Communication Technology Office', 4, 'Other requests/inquiries', 6),
('Legal Office', 5, 'Certificate of No Pending Case', 7),
('Legal Office', 6, 'Correction of Entries in School Record', 7),
('Legal Office', 7, 'Feedback/Complaints', 7),
('Legal Office', 8, 'Sites titling', 7),
('SDS - Schools Division Superintendent', 9, 'Travel authority', 1),
('SDS - Schools Division Superintendent', 10, 'Other requests/inquiries', 1),
('SDS - Schools Division Superintendent', 11, 'Feedback/Complaint', 1),
('ASDS - Assistant Schools Division Superintendent', 12, 'BAC', 2),
('ASDS - Assistant Schools Division Superintendent', 13, 'Other requests/inquiries', 2),
('ASDS - Assistant Schools Division Superintendent', 14, 'Feedback/Complaint', 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `csm_submission`
--
ALTER TABLE `csm_submission`
  ADD PRIMARY KEY (`control_number`),
  ADD UNIQUE KEY `control_number` (`control_number`);

--
-- Indexes for table `offices`
--
ALTER TABLE `offices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `services_ibfk_1` (`sub_office_id`);

--
-- Indexes for table `sub_offices`
--
ALTER TABLE `sub_offices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sub_offices_ibfk_1` (`office_id`);

--
-- Indexes for table `unit_services`
--
ALTER TABLE `unit_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `unit_services_ibfk_1` (`office_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `csm_submission`
--
ALTER TABLE `csm_submission`
  MODIFY `control_number` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99597425970191;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `services_ibfk_1` FOREIGN KEY (`sub_office_id`) REFERENCES `sub_offices` (`id`);

--
-- Constraints for table `sub_offices`
--
ALTER TABLE `sub_offices`
  ADD CONSTRAINT `sub_offices_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`);

--
-- Constraints for table `unit_services`
--
ALTER TABLE `unit_services`
  ADD CONSTRAINT `unit_services_ibfk_1` FOREIGN KEY (`office_id`) REFERENCES `offices` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
