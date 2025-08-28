-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Εξυπηρετητής: 127.0.0.1
-- Χρόνος δημιουργίας: 28 Αυγ 2025 στις 21:31:19
-- Έκδοση διακομιστή: 10.4.32-MariaDB
-- Έκδοση PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Βάση δεδομένων: `web`
--

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `committee_grades`
--

CREATE TABLE `committee_grades` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `grade` float DEFAULT NULL,
  `submitted_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Άδειασμα δεδομένων του πίνακα `committee_grades`
--

INSERT INTO `committee_grades` (`id`, `topic_id`, `teacher_id`, `grade`, `submitted_at`) VALUES
(1, 2, 1, 10, '2025-08-24 14:44:25'),
(2, 5, 1, 10, '2025-08-24 14:46:06'),
(3, 36, 4, 5, '2025-08-24 14:47:54'),
(4, 33, 4, 5, '2025-08-24 15:18:23'),
(5, 37, 1, 6, '2025-08-28 22:28:39');

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `committee_requests`
--

CREATE TABLE `committee_requests` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `status` enum('pending','accepted','rejected','cancelled') DEFAULT 'pending',
  `requested_at` datetime NOT NULL DEFAULT current_timestamp(),
  `responded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Άδειασμα δεδομένων του πίνακα `committee_requests`
--

INSERT INTO `committee_requests` (`id`, `topic_id`, `teacher_id`, `status`, `requested_at`, `responded_at`) VALUES
(18, 8, 5, 'accepted', '2025-04-23 00:46:43', '2025-04-23 00:47:13'),
(19, 8, 6, 'accepted', '2025-04-23 00:46:43', '2025-04-23 00:48:04'),
(20, 9, 1, 'accepted', '2025-04-23 21:05:28', '2025-04-23 21:06:00'),
(21, 9, 4, 'accepted', '2025-04-23 21:05:28', '2025-04-23 21:06:24'),
(22, 9, 5, 'cancelled', '2025-04-23 21:05:28', NULL),
(32, 14, 1, 'accepted', '2025-05-01 02:35:14', '2025-05-01 02:35:31'),
(33, 14, 4, 'accepted', '2025-05-01 02:35:14', '2025-05-01 02:36:52'),
(54, 25, 1, 'accepted', '2025-05-02 20:04:51', '2025-05-02 20:05:41'),
(55, 25, 4, 'accepted', '2025-05-02 20:04:51', '2025-05-02 20:05:11'),
(56, 26, 1, 'accepted', '2025-05-02 20:04:55', '2025-05-02 20:05:42'),
(57, 26, 4, 'accepted', '2025-05-02 20:04:55', '2025-05-02 20:05:13'),
(60, 28, 1, 'accepted', '2025-05-11 18:52:39', '2025-05-11 18:52:59'),
(61, 28, 4, 'accepted', '2025-05-11 18:52:39', '2025-05-11 18:53:16'),
(66, 33, 4, 'accepted', '2025-08-20 16:51:16', NULL),
(67, 33, 1, 'accepted', '2025-08-20 16:51:24', NULL),
(68, 33, 5, 'pending', '2025-08-20 16:51:29', NULL),
(69, 33, 6, 'pending', '2025-08-20 16:54:14', NULL),
(78, 36, 1, 'accepted', '2025-08-20 18:11:59', NULL),
(79, 36, 4, 'accepted', '2025-08-20 18:11:59', NULL),
(80, 36, 5, 'pending', '2025-08-20 18:11:59', NULL),
(81, 36, 6, 'pending', '2025-08-20 18:11:59', NULL),
(82, 38, 1, 'accepted', '2025-08-28 18:44:58', NULL),
(83, 38, 4, 'pending', '2025-08-28 18:44:58', NULL),
(84, 38, 5, 'cancelled', '2025-08-28 18:44:58', '2025-08-28 18:45:44'),
(85, 38, 6, 'cancelled', '2025-08-28 18:44:58', '2025-08-28 18:45:44');

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `notes`
--

CREATE TABLE `notes` (
  `id` int(11) NOT NULL,
  `topic_id` int(11) UNSIGNED NOT NULL,
  `teacher_id` int(11) UNSIGNED NOT NULL,
  `note_text` text NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Άδειασμα δεδομένων του πίνακα `notes`
--

INSERT INTO `notes` (`id`, `topic_id`, `teacher_id`, `note_text`, `created_at`) VALUES
(4, 2, 1, 'γηηφφγ', '2025-05-02 03:13:40'),
(5, 2, 1, 'βνωβφψφ μαρ', '2025-05-02 03:27:38'),
(6, 1, 4, 'bnvgnvb', '2025-05-02 03:50:39'),
(7, 2, 4, 'νβω', '2025-05-02 03:51:31'),
(8, 1, 4, 'ηξβ', '2025-05-02 03:53:54'),
(9, 1, 4, 'ηγωηγφ', '2025-05-02 03:55:14'),
(10, 1, 4, 'nbnvv', '2025-05-11 18:53:37'),
(13, 37, 1, 'ησησησ', '0000-00-00 00:00:00'),
(14, 36, 1, 'ξσξσξσ', '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `student_submissions`
--

CREATE TABLE `student_submissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `topic_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `comments` varchar(500) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Άδειασμα δεδομένων του πίνακα `student_submissions`
--

INSERT INTO `student_submissions` (`id`, `topic_id`, `student_id`, `file_path`, `comments`, `uploaded_at`) VALUES
(1, 19, 2, 'submissions/68b0534e9eef4-68b041e14cdad-ΘΕΜΑΤΑ-ΘΕΩΡΙΑ ΥΠΟΛΟΓΙΣΜΟΥ.docx', NULL, '2025-08-28 13:02:06'),
(2, 19, 2, 'submissions/68b055ffef3e4-68b041e14cdad-ΘΕΜΑΤΑ-ΘΕΩΡΙΑ ΥΠΟΛΟΓΙΣΜΟΥ.docx', NULL, '2025-08-28 13:13:35'),
(3, 19, 2, NULL, 'ηγφηγ', '2025-08-28 13:29:54'),
(4, 19, 2, 'submissions/68b05b40a250a-68b041e14cdad-ΘΕΜΑΤΑ-ΘΕΩΡΙΑ ΥΠΟΛΟΓΙΣΜΟΥ.docx', 'hdnhdhd', '2025-08-28 13:36:00'),
(5, 25, 2, NULL, 'hgfhgfhgfhgf', '2025-08-28 13:53:12');

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `topics`
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
  `status` enum('completed','available','temporary','confirmed','cancelled','for examination','awaiting_committee') DEFAULT 'available',
  `deadline` date DEFAULT NULL,
  `exam_datetime` datetime DEFAULT NULL,
  `exam_mode` enum('onsite','online') DEFAULT 'onsite',
  `exam_location` varchar(255) DEFAULT NULL,
  `extra_links` text DEFAULT NULL,
  `draft_pdf_path` varchar(255) DEFAULT NULL,
  `repository_url` varchar(255) DEFAULT NULL,
  `final_grade` float DEFAULT NULL,
  `cancel_reason` varchar(200) NOT NULL,
  `cancel_info` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Άδειασμα δεδομένων του πίνακα `topics`
--

INSERT INTO `topics` (`id`, `teacher_id`, `title`, `summary`, `pdf_path`, `created_at`, `assigned_time`, `confirmed_time`, `assigned_to`, `status`, `deadline`, `exam_datetime`, `exam_mode`, `exam_location`, `extra_links`, `draft_pdf_path`, `repository_url`, `final_grade`, `cancel_reason`, `cancel_info`) VALUES
(1, 1, 'nhddhdgfh', 'dhjgfhjsf', NULL, '2025-04-12 21:15:06', '2025-04-19 22:21:53', NULL, 2, 'cancelled', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', ''),
(2, 1, 'rjrjjrj', 'jejeje', NULL, '2025-04-13 21:13:59', '2025-04-19 22:21:53', NULL, 2, 'cancelled', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', ''),
(5, 1, 'oikologia', 'tha kaneus mpla mpla', 'uploads/6803f383d98f0-Pick_Me_App(v0.1)1.pdf', '2025-04-19 19:03:31', '2025-04-19 22:21:53', NULL, 2, 'completed', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', ''),
(10, 1, 'sex', 'ante gamisou', 'uploads/680a5e0f8c515-Σιδηροπούλου Βαρβάρα - ΔΕ - MetaCook.pdf', '2025-04-20 15:51:43', '2025-04-20 18:51:59', '2020-04-20 18:51:59', 2, 'cancelled', NULL, '0000-00-00 00:00:00', 'onsite', 'https://github.com/m444r/web/blob/main/web.sql', '', NULL, NULL, NULL, 'από Διδάσκοντα', 'Γ.Σ. 4/4454'),
(11, 1, 'ceid', 'tha mpla mpla\r\n', NULL, '2025-04-28 12:37:24', '2025-04-28 15:38:10', NULL, 2, 'cancelled', NULL, '2020-05-13 14:02:00', 'online', 'lallala', '', NULL, NULL, NULL, '', ''),
(13, 4, 'test', '', NULL, '2025-04-30 23:28:39', '2025-05-01 02:28:49', NULL, 2, 'cancelled', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', ''),
(14, 5, 'testtt', '', NULL, '2025-04-30 23:34:32', '2025-05-01 02:34:44', NULL, 7, 'confirmed', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', ''),
(16, 1, 'lllllll', 'lllllllll', NULL, '2025-05-02 00:33:45', '2025-05-02 03:33:53', NULL, 2, 'cancelled', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', ''),
(17, 1, 'lala', 'lalalalla', NULL, '2025-05-02 14:35:01', '2025-05-02 17:35:10', NULL, 2, 'for examination', NULL, '2027-09-02 12:12:00', 'onsite', 'Αίθουσα Γ', NULL, NULL, NULL, NULL, '', ''),
(18, 1, 'MAR', 'HJDSDH', NULL, '2025-05-02 14:53:50', '2025-05-02 17:53:58', '2020-05-02 17:54:54', 2, 'cancelled', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', ''),
(19, 1, 'loulou', 'msh', NULL, '2025-05-02 16:18:44', '2025-05-02 19:18:52', '2020-05-02 19:20:38', 2, 'confirmed', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', ''),
(20, 1, 'mpou', 'bdbd', NULL, '2025-05-02 16:42:00', '2025-05-02 19:42:09', '2025-05-02 19:43:11', 2, 'cancelled', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', ''),
(21, 1, 'lalalalla', '', NULL, '2025-05-02 16:45:42', '2025-05-02 19:46:08', '2025-05-02 19:47:32', 2, 'cancelled', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', ''),
(22, 1, 'pouo', '', NULL, '2025-05-02 16:45:48', '2025-05-02 19:46:12', '2025-05-02 19:47:34', 2, 'cancelled', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', ''),
(23, 1, 'nananana', '', NULL, '2025-05-02 16:45:54', '2025-05-02 19:46:14', '2025-05-02 19:47:35', 2, 'cancelled', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', ''),
(25, 1, 'ppppppppp', '', NULL, '2025-05-02 17:04:00', '2025-05-02 20:04:20', '2020-05-02 20:05:41', 2, 'confirmed', '0000-00-00', NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, 'από Διδάσκοντα', 'Γ.Σ. 44/2020'),
(27, 1, 'hhhhhhhhhh', '', NULL, '2025-05-02 17:04:10', '2025-05-02 20:04:26', '2025-05-02 20:05:43', 2, 'cancelled', NULL, '2040-04-04 03:03:00', 'onsite', NULL, NULL, NULL, NULL, NULL, '', ''),
(28, 1, 'sex 2', 'lalalla', NULL, '2025-05-11 15:51:07', '2025-05-11 18:51:22', '2020-05-11 18:53:16', 2, 'completed', '2025-08-31', '2025-08-30 18:25:00', 'online', NULL, NULL, NULL, NULL, NULL, '', ''),
(29, 1, 'sexara', 'mplampka', NULL, '2025-05-16 19:14:07', '2025-05-16 22:14:24', '2025-05-16 22:15:57', 2, 'completed', '2025-08-29', '2040-04-04 03:03:00', 'onsite', NULL, NULL, NULL, NULL, NULL, '', ''),
(30, 1, 'ceidddd', 'lalalallalaa', NULL, '2025-05-27 11:09:54', '2025-05-27 14:10:28', '2025-05-27 14:13:20', 2, 'completed', '2020-05-05', '2040-04-04 03:03:00', 'online', NULL, NULL, NULL, NULL, NULL, '', ''),
(31, 1, 'ela ela', 'mplampla', 'uploads/68a5cb902e080-CA_DOMES_DED.pdf', '2025-08-20 13:20:16', '2025-08-20 14:20:16', NULL, 2, '', '2025-08-28', NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', ''),
(32, 1, 'hhh', 'ajjajaj', NULL, '2025-08-20 14:02:36', '2025-08-20 15:02:36', NULL, 2, 'cancelled', '2025-08-30', NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', ''),
(33, 4, 'kk', 'mmm', NULL, '2025-08-20 14:04:39', '2025-08-20 15:04:39', '2025-08-04 17:25:28', 2, 'completed', '2025-08-31', '2025-08-22 17:25:14', 'onsite', NULL, NULL, NULL, NULL, NULL, '', ''),
(34, 1, 'ena ena', 'lalala', NULL, '2025-08-20 16:42:53', '2025-08-20 17:42:53', '2016-08-16 13:48:32', 2, 'cancelled', '2025-08-24', NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, 'από Διδάσκοντα', 'Γ.Σ. 3333/333333'),
(35, 1, 'mpla', 'nn', NULL, '2025-08-20 17:07:57', '2020-08-20 18:07:57', NULL, 2, 'completed', '2025-08-31', NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', ''),
(36, 4, 'llalala', 'mmm', NULL, '2025-08-20 17:11:29', '2025-08-20 18:11:29', '2022-08-03 17:30:23', 2, 'completed', '2025-08-29', '2025-08-22 17:30:40', 'onsite', NULL, NULL, NULL, NULL, NULL, '', ''),
(37, 1, 'νιαοθ', 'λαλα', NULL, '2025-08-22 14:34:16', '2025-08-22 15:34:16', '2020-08-22 15:34:16', NULL, 'for examination', '2025-08-30', NULL, 'onsite', NULL, NULL, NULL, NULL, 6, 'από Διδάσκοντα', 'Γ.Σ. 22/2222'),
(38, 6, 'μιοθ', 'ξηξξ', NULL, '2025-08-22 14:49:41', '2025-08-22 15:49:41', '2025-08-28 18:45:44', 2, 'confirmed', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', ''),
(39, 6, 'mmllala', ';alla', NULL, '2025-08-22 14:57:27', '2025-08-22 15:57:27', '2020-08-22 15:57:27', NULL, 'available', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', ''),
(40, 1, 'ηξδσγηγσ', 'ηδσωηγξ', NULL, '2025-08-24 15:19:01', '2025-08-24 16:19:01', NULL, 2, 'for examination', '2025-08-02', NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', ''),
(41, 1, 'hdhdh', 'dhddjd', NULL, '2025-08-27 11:08:07', '2025-08-27 14:08:07', NULL, 2, 'awaiting_committee', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', ''),
(42, 1, 'mpla ,plla μμμ', 'jdjd νξνν', 'uploads/68b041e14cdad-ΘΕΜΑΤΑ-ΘΕΩΡΙΑ ΥΠΟΛΟΓΙΣΜΟΥ.docx', '2025-08-27 11:09:00', '2025-08-27 14:09:00', NULL, NULL, 'available', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', ''),
(43, 1, 'νσνσν', 'λαλλα', NULL, '2025-08-28 11:48:34', '2025-08-28 14:48:34', NULL, NULL, 'available', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', ''),
(44, 4, 'βγγφ', 'βνγγ', NULL, '2025-08-28 16:31:54', '2025-08-28 20:28:42', NULL, 2, 'temporary', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', ''),
(45, 4, 'ehjhjeh', 'heh', NULL, '2025-08-28 17:29:25', '2025-08-28 20:34:00', NULL, 2, 'awaiting_committee', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', ''),
(46, 4, 'ηξηξ', 'γηγ', NULL, '2025-08-28 17:34:28', '2025-08-28 20:34:39', NULL, 2, 'awaiting_committee', NULL, NULL, 'onsite', NULL, NULL, NULL, NULL, NULL, '', '');

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `am` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
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
  `postcode` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Άδειασμα δεδομένων του πίνακα `users`
--

INSERT INTO `users` (`id`, `am`, `name`, `surname`, `password`, `email`, `role`, `mobile_telephone`, `landline_telephone`, `contact_email`, `father_name`, `street`, `number`, `city`, `postcode`) VALUES
(1, 0, 'papadopoulos', 'loulis', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'mar@gmail.com', 'teacher', 0, 0, '', '', '', 0, '', 0),
(2, 1103077, 'valia', 'dionisiou', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'val@gmail.com', 'student', 2147483647, 2147483647, 'marialena@gmail.com', 'george', 'ermoy', 10, 'athens', 26221),
(3, 0, '', '', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'elena@gmail.com', 'secretary', 0, 0, '', '', '', 0, '', 0),
(4, 0, 'marial', 'karaiskoy', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'marial@gmail.com', 'teacher', 0, 0, '', '', '', 0, '', 0),
(5, 0, 'panos', 'loutsos', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'lou@gmail.com', 'teacher', 0, 0, '', '', '', 0, '', 0),
(6, 0, 'bill', 'tsou', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'tsou@gmail.com', 'teacher', 0, 0, '', '', '', 0, '', 0),
(7, 0, 'ilias', 'makris', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'mac@gmail.com', 'student', 0, 0, '', '', '', 0, '', 0);

--
-- Ευρετήρια για άχρηστους πίνακες
--

--
-- Ευρετήρια για πίνακα `committee_grades`
--
ALTER TABLE `committee_grades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `topic_id` (`topic_id`,`teacher_id`);

--
-- Ευρετήρια για πίνακα `committee_requests`
--
ALTER TABLE `committee_requests`
  ADD PRIMARY KEY (`id`);

--
-- Ευρετήρια για πίνακα `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topic_id` (`topic_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Ευρετήρια για πίνακα `student_submissions`
--
ALTER TABLE `student_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_submission_topic` (`topic_id`),
  ADD KEY `fk_submission_student` (`student_id`);

--
-- Ευρετήρια για πίνακα `topics`
--
ALTER TABLE `topics`
  ADD PRIMARY KEY (`id`);

--
-- Ευρετήρια για πίνακα `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT για άχρηστους πίνακες
--

--
-- AUTO_INCREMENT για πίνακα `committee_grades`
--
ALTER TABLE `committee_grades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT για πίνακα `committee_requests`
--
ALTER TABLE `committee_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT για πίνακα `notes`
--
ALTER TABLE `notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT για πίνακα `student_submissions`
--
ALTER TABLE `student_submissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT για πίνακα `topics`
--
ALTER TABLE `topics`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT για πίνακα `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Περιορισμοί για άχρηστους πίνακες
--

--
-- Περιορισμοί για πίνακα `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`),
  ADD CONSTRAINT `notes_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`);

--
-- Περιορισμοί για πίνακα `student_submissions`
--
ALTER TABLE `student_submissions`
  ADD CONSTRAINT `fk_submission_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_submission_topic` FOREIGN KEY (`topic_id`) REFERENCES `topics` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
