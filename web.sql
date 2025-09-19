-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 19, 2025 at 03:47 PM
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
(2, 5, 1, 10, '2025-08-24 14:46:06'),
(3, 36, 4, 5, '2025-08-24 14:47:54'),
(4, 33, 4, 5.5, '2025-08-24 15:18:23'),
(5, 37, 1, 6, '2025-08-28 22:28:39'),
(7, 17, 1, 0, '2025-08-28 22:44:35'),
(8, 33, 1, 5, '2025-08-28 23:13:52');

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
(167, 58, 4, 'pending', NULL, '2025-09-18 00:15:01', NULL),
(168, 58, 1, 'accepted', NULL, '2025-09-18 00:15:01', '2025-09-18 00:27:02'),
(169, 66, 4, 'pending', NULL, '2025-09-18 00:29:40', NULL),
(170, 66, 1, 'pending', NULL, '2025-09-18 00:29:40', NULL);

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

--
-- Dumping data for table `notes`
--

INSERT INTO `notes` (`id`, `topic_id`, `teacher_id`, `note_text`, `created_at`) VALUES
(17, 46, 4, 'mmmmmm', '2025-09-05 16:20:38'),
(18, 46, 4, 'lklklklklklklkl', '2025-09-05 16:21:19'),
(29, 58, 2, 'Σημεισωση 1', '2025-09-15 21:05:17'),
(30, 59, 2, 'Σημεισωση 2', '2025-09-15 21:05:26');

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
  `library_link` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `topics`
--

INSERT INTO `topics` (`id`, `teacher_id`, `title`, `summary`, `pdf_path`, `created_at`, `assigned_time`, `confirmed_time`, `assigned_to`, `status`, `deadline`, `exam_datetime`, `exam_mode`, `exam_location`, `extra_links`, `draft_pdf_path`, `repository_url`, `final_grade`, `cancel_reason`, `cancel_info`, `presentation_date`, `presentation_location`, `submitted_materials`, `submission_date`, `progress_notes`, `library_link`) VALUES
(46, 4, 'ηξηξ', 'γηγ', NULL, '2025-08-28 17:34:28', '0000-00-00 00:00:00', NULL, NULL, 'available', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', NULL, NULL, NULL, NULL, NULL, NULL),
(58, 2, 'Θεμα 1', 'Paid was hill sir high. For him precaution any advantages dissimilar comparison few terminated projecting. Prevailed discovery immediate objection of ye at. Repair summer one winter living feebly pretty his. In so sense am known these since. Shortly respect ask cousins brought add tedious nay. Expect relied do we genius is. On as around spirit of hearts genius. Is raptures daughter branched laughter peculiar in settling.', NULL, '2025-09-15 18:03:31', '2025-09-17 01:18:46', NULL, 2, 'awaiting_committee', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', NULL, NULL, NULL, NULL, NULL, NULL),
(59, 2, 'Θεμα 2', 'Θεμα 2', NULL, '2025-09-15 18:03:45', '2025-09-15 21:03:45', NULL, NULL, 'available', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', '', NULL, NULL, NULL, NULL, NULL, NULL),
(66, 1, 'Θεμα 1', 'Θεμα 1', '../uploads/68cb18adef775_CA (2).pdf', '2025-09-17 20:23:09', '2025-09-17 23:54:44', NULL, 8, 'awaiting_committee', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', '', NULL, NULL, NULL, NULL, NULL, NULL),
(73, 1, 'Θεμα 3', 'Θεμα 3', NULL, '2025-09-17 20:38:11', '2025-09-17 23:54:14', NULL, NULL, 'cancelled', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', '', NULL, NULL, NULL, NULL, NULL, NULL),
(74, 1, 'Θεμα 4', 'Θεμα 4', NULL, '2025-09-17 20:38:17', '2025-09-17 23:38:17', NULL, NULL, 'available', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', '', NULL, NULL, NULL, NULL, NULL, NULL),
(75, 1, 'Θεμα 5', 'Θεμα 5', NULL, '2025-09-17 20:38:22', '2025-09-17 23:38:22', NULL, NULL, 'available', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', '', NULL, NULL, NULL, NULL, NULL, NULL),
(76, 1, 'Θεμα 6', 'Θεμα 6', NULL, '2025-09-17 20:38:27', '2025-09-17 23:38:27', NULL, NULL, 'available', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', '', NULL, NULL, NULL, NULL, NULL, NULL),
(77, 1, 'Θεμα 7', 'Θεμα 7', NULL, '2025-09-17 20:38:33', '2025-09-17 23:38:33', NULL, NULL, 'available', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', '', NULL, NULL, NULL, NULL, NULL, NULL),
(78, 1, 'Θεμα 8', 'Θεμα 8', '../uploads/68cb27db8e95d_CA-_1.pdf', '2025-09-17 21:27:55', '2025-09-18 00:27:55', NULL, NULL, 'available', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', '', NULL, NULL, NULL, NULL, NULL, NULL);

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
(2, 1103077, 'Βάλια', 'Παναγοπούλου', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'val@gmail.com', 'student', 694489822, 2147483647, 'val@gmail.com', 'Γιώργος', '', 10, 'Πάτρα', 26221, 'uploads/profile_pictures/student_2_1758052931.jpg'),
(4, 0, 'Γιάννης', 'Βασιλόπουλος', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'vas@gmail.com', 'teacher', 0, 0, '', '', '', 0, '', 0, NULL),
(8, 1100502, 'Έλενα', 'Βίτσιου', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'elena@email.com', 'student', 2147483647, 2147483647, '', '', '', 0, 'Άρτα', 47100, NULL),
(9, 1103077, 'Μαριαλένα', 'Καραίσκου', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'mar@email.com', 'student', 2147483647, 2147483647, '', '', '', 0, 'Αθήνα', 10432, NULL),
(10, 1100638, 'Φαίδρα', 'Μποζίκη', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'faidra@email.com', 'student', 2147483647, 2147483647, '', '', '', 0, 'Πάτρα', 26212, NULL),
(11, 1100703, 'Κλέντι', 'Σαλτσάι', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'klenti@email.com', 'student', 2147483647, 2147483647, '', '', '', 0, 'Πάτρα', 26213, NULL),
(13, 0, 'Μαρία', 'Παπαδοπούλου', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'maria@email.com', 'secretary', 697788991, 261153829, '', '', '', 0, 'Πάτρα', 26212, NULL);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `committee_requests`
--
ALTER TABLE `committee_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=171;

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `student_submissions`
--
ALTER TABLE `student_submissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `topics`
--
ALTER TABLE `topics`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

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
