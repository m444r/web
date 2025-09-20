-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 20, 2025 at 08:35 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `web`
--

-- --------------------------------------------------------

--
-- Table structure for table `committee_grades`
--

CREATE TABLE `committee_grades` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `grade` float DEFAULT NULL,
  `submitted_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `committee_grades`
--

INSERT INTO `committee_grades` (`id`, `topic_id`, `teacher_id`, `grade`, `submitted_at`) VALUES
(23, 86, 1, 10, '2025-09-19 22:53:50'),
(24, 87, 1, 8, '2025-09-19 22:53:59'),
(25, 86, 4, 8, '2025-09-19 22:54:17'),
(26, 87, 4, 8, '2025-09-19 22:54:21');

-- --------------------------------------------------------

--
-- Table structure for table `committee_requests`
--

CREATE TABLE `committee_requests` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `status` enum('pending','accepted','rejected','cancelled') DEFAULT 'pending',
  `accepted_at` timestamp NULL DEFAULT NULL,
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `responded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `committee_requests`
--

INSERT INTO `committee_requests` (`id`, `topic_id`, `teacher_id`, `status`, `accepted_at`, `requested_at`, `responded_at`) VALUES
(175, 86, 4, 'accepted', NULL, '2025-09-19 22:50:26', '2025-09-19 22:51:04'),
(176, 86, 1, 'accepted', NULL, '2025-09-19 22:50:26', '2025-09-19 22:51:54'),
(177, 87, 4, 'accepted', NULL, '2025-09-19 22:50:41', '2025-09-19 22:51:04'),
(178, 87, 1, 'accepted', NULL, '2025-09-19 22:50:41', '2025-09-19 22:51:54'),
(183, 92, 4, 'accepted', NULL, '2025-09-20 19:06:30', '2025-09-20 19:07:32'),
(184, 92, 1, 'accepted', NULL, '2025-09-20 19:06:30', '2025-09-20 19:07:18');

-- --------------------------------------------------------

--
-- Table structure for table `notes`
--

CREATE TABLE `notes` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) UNSIGNED NOT NULL,
  `teacher_id` int(11) UNSIGNED NOT NULL,
  `note_text` text NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_submissions`
--

CREATE TABLE `student_submissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `topic_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `comments` varchar(500) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `submission_type` enum('draft','final','presentation','other') NOT NULL DEFAULT 'other',
  `links` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_submissions`
--

INSERT INTO `student_submissions` (`id`, `topic_id`, `student_id`, `file_path`, `comments`, `uploaded_at`, `submission_type`, `links`) VALUES
(7, 92, 10, 'submissions/68ced1e272f48-Ergastiriaki_Askisi_24-25-1.0 (2).pdf', 'Θεμα 1 σχολια', '2025-09-20 16:10:10', 'other', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `topics`
--

CREATE TABLE `topics` (
  `id` int(11) UNSIGNED NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `summary` text DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_time` datetime NOT NULL DEFAULT current_timestamp(),
  `confirmed_time` datetime DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `status` enum('completed','for_grade','available','confirmed','cancelled','for examination','awaiting_committee') DEFAULT 'available',
  `deadline` date DEFAULT NULL,
  `exam_datetime` datetime DEFAULT NULL,
  `exam_mode` enum('onsite','online') DEFAULT 'onsite',
  `exam_location` varchar(255) DEFAULT NULL,
  `extra_links` text DEFAULT NULL,
  `draft_pdf_path` varchar(255) DEFAULT NULL,
  `repository_url` varchar(255) DEFAULT NULL,
  `final_grade` float DEFAULT NULL,
  `cancel_reason` varchar(200) NOT NULL,
  `cancel_info` varchar(200) NOT NULL,
  `presentation_date` timestamp NULL DEFAULT NULL,
  `presentation_location` varchar(255) DEFAULT NULL,
  `submitted_materials` text DEFAULT NULL,
  `submission_date` timestamp NULL DEFAULT NULL,
  `progress_notes` text DEFAULT NULL,
  `library_link` varchar(500) DEFAULT NULL,
  `protocol_ap` varchar(100) DEFAULT NULL,
  `nemertes_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `topics`
--

INSERT INTO `topics` (`id`, `teacher_id`, `title`, `summary`, `pdf_path`, `created_at`, `assigned_time`, `confirmed_time`, `assigned_to`, `status`, `deadline`, `exam_datetime`, `exam_mode`, `exam_location`, `extra_links`, `draft_pdf_path`, `repository_url`, `final_grade`, `cancel_reason`, `cancel_info`, `presentation_date`, `presentation_location`, `submitted_materials`, `submission_date`, `progress_notes`, `library_link`, `protocol_ap`, `nemertes_url`) VALUES
(85, 4, 'Θεμα 1', 'Θεμα 1', NULL, '2025-09-19 19:49:37', '2025-09-19 22:49:37', NULL, NULL, 'available', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(86, 4, 'Θεμα 2', 'Θεμα 2', NULL, '2025-09-19 19:49:45', '2025-09-19 22:49:52', '2025-09-19 22:51:54', 9, 'completed', NULL, '2026-03-19 23:04:39', 'online', NULL, NULL, NULL, NULL, 10, '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(87, 4, 'Θεμα 3', 'Θεμα 3', NULL, '2025-09-19 19:50:06', '2025-09-19 22:50:12', '2025-09-19 22:51:54', 8, 'completed', NULL, '2026-09-09 22:31:57', 'online', NULL, NULL, NULL, NULL, 8, '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(92, 1, 'Θεμα 1', 'Θεμα 1', '../uploads/68cec9f240408_Ergastiriaki_Askisi_24-25-1.0 (2).pdf', '2025-09-20 15:36:18', '2025-09-20 19:04:52', '2025-09-20 19:07:32', 10, 'for examination', NULL, '2025-10-22 09:30:00', '', 'Γ', NULL, NULL, NULL, NULL, '', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `am` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `surname` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` varchar(100) NOT NULL,
  `mobile_telephone` int(11) NOT NULL,
  `landline_telephone` int(11) NOT NULL,
  `contact_email` varchar(100) NOT NULL,
  `father_name` varchar(100) NOT NULL,
  `street` varchar(100) NOT NULL,
  `number` int(11) NOT NULL,
  `city` varchar(100) NOT NULL,
  `postcode` int(11) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `am`, `name`, `surname`, `password`, `email`, `role`, `mobile_telephone`, `landline_telephone`, `contact_email`, `father_name`, `street`, `number`, `city`, `postcode`, `profile_picture`) VALUES
(1, 0, 'Ελένη', 'Βογιατζάκη', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'el@gmail.com', 'teacher', 212121, 5454, '', '', 'Μαιζώνος', 20, 'Πάτρα', 15344, 'uploads/profile_pictures/teacher_1_1758062022.jpg'),
(2, 1100651, 'Βάλια', 'Παναγοπούλου', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'val@gmail.com', 'student', 694489822, 2147483647, 'val@gmail.com', 'Γιώργος', '', 10, 'Πάτρα', 26221, 'uploads/profile_pictures/student_2_1758052931.jpg'),
(4, 0, 'Γιάννης', 'Βασιλόπουλος', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'vas@gmail.com', 'teacher', 0, 0, '', '', '', 0, '', 0, NULL),
(8, 1100502, 'Έλενα', 'Βίτσιου', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'elena@gmail.com', 'student', 2147483647, 2147483647, '', '', '', 0, 'Άρτα', 47100, NULL),
(9, 1103077, 'Μαριαλένα', 'Καραίσκου', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'mar@gmail.com', 'student', 2147483647, 2147483647, '', '', '', 0, 'Αθήνα', 10432, NULL),
(10, 1100638, 'Φαίδρα', 'Μποζίκη', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'faidra@gmail.com', 'student', 2147483647, 2147483647, '', '', '', 0, 'Πάτρα', 26212, NULL),
(11, 1100703, 'Κλέντι', 'Σαλτσάι', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'klenti@gmail.com', 'student', 2147483647, 2147483647, '', '', '', 0, 'Πάτρα', 26213, NULL),
(13, 0, 'Μαρία', 'Παπαδοπούλου', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'maria@gmail.com', 'secretary', 697788991, 261153829, '', '', '', 0, 'Πάτρα', 26212, NULL),
(14, 1104321, 'Άλεξ', 'Γιανούλης', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'alex@gmail.com', 'student', 0, 0, 'alex@gmail.com', '', '', 0, '', 0, NULL),
(15, 1100897, 'Γεωργία', 'Μηλωνά', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'go@gmail.com', 'student', 0, 0, 'go@gmail.com', '', '', 0, '', 0, NULL),
(18, 1100289, 'Φαίδων ', 'Πανταζής', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'fe@gmail.com', 'student', 0, 0, 'fe@gmail.com', '', '', 0, '', 0, NULL),
(19, 1100562, 'Θράσος', 'Διαμαντής', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'thra@gmail.com', 'student', 0, 0, 'thra@gmail.com', '', '', 0, '', 0, NULL),
(20, 1100123, 'Δημήτρης ', 'Μαρούλης', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'dhm@gmail.com', 'student', 0, 0, 'dhm@gmail.com', '', '', 0, '', 0, NULL),
(21, 0, 'Χρήστος', 'Μακρής', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'xri@gmail.com', 'teacher', 0, 0, 'xri@gmail.com', '', '', 0, '', 0, NULL),
(22, 0, 'Άρης ', 'Ηλίας', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'ar@gmail.com', 'teacher', 0, 0, 'ar@gmail.com', '', '', 0, '', 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `committee_grades`
--
ALTER TABLE `committee_grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `topic_id` (`topic_id`,`teacher_id`);

--
-- Indexes for table `committee_requests`
--
ALTER TABLE `committee_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_committee_requests_status` (`status`);

--
-- Indexes for table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topic_id` (`topic_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `student_submissions`
--
ALTER TABLE `student_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_submission_topic` (`topic_id`),
  ADD KEY `fk_submission_student` (`student_id`);

--
-- Indexes for table `topics`
--
ALTER TABLE `topics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_assignment` (`assigned_to`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `committee_grades`
--
ALTER TABLE `committee_grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `committee_requests`
--
ALTER TABLE `committee_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=185;

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `student_submissions`
--
ALTER TABLE `student_submissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `topics`
--
ALTER TABLE `topics`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`),
  ADD CONSTRAINT `notes_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `student_submissions`
--
ALTER TABLE `student_submissions`
  ADD CONSTRAINT `fk_submission_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_submission_topic` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
