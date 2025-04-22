-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Εξυπηρετητής: 127.0.0.1
-- Χρόνος δημιουργίας: 22 Απρ 2025 στις 23:23:09
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
(1, 1, 4, 'accepted', '2025-04-21 16:11:45', NULL),
(2, 1, 5, 'accepted', '2025-04-21 16:11:45', NULL),
(3, 2, 1, '', '2025-04-21 16:24:14', NULL),
(4, 2, 4, 'accepted', '2025-04-21 16:24:14', NULL),
(5, 3, 1, 'accepted', '2025-04-21 18:13:05', NULL),
(6, 3, 4, 'accepted', '2025-04-21 18:13:05', NULL),
(7, 4, 1, 'accepted', '2025-04-21 18:17:33', NULL),
(8, 4, 4, 'accepted', '2025-04-21 18:17:33', NULL),
(9, 5, 1, 'accepted', '2025-04-21 18:26:06', NULL),
(10, 5, 4, 'accepted', '2025-04-21 18:26:06', NULL),
(11, 5, 5, 'cancelled', '2025-04-21 18:26:06', NULL),
(12, 6, 1, 'rejected', '2025-04-21 18:26:12', NULL),
(13, 6, 4, 'rejected', '2025-04-21 18:26:12', NULL),
(14, 6, 5, 'rejected', '2025-04-21 18:26:12', NULL);

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `topics`
--

CREATE TABLE `topics` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `summary` text DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_time` datetime NOT NULL DEFAULT current_timestamp(),
  `assigned_to` int(11) DEFAULT NULL,
  `status` enum('available','temporary','confirmed','cancelled','for examination','awaiting_committee') DEFAULT 'available',
  `exam_datetime` datetime DEFAULT NULL,
  `exam_mode` enum('onsite','online') DEFAULT 'onsite',
  `exam_location` varchar(255) DEFAULT NULL,
  `extra_links` text DEFAULT NULL,
  `draft_pdf_path` varchar(255) DEFAULT NULL,
  `repository_url` varchar(255) DEFAULT NULL,
  `final_grade` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Άδειασμα δεδομένων του πίνακα `topics`
--

INSERT INTO `topics` (`id`, `teacher_id`, `title`, `summary`, `pdf_path`, `created_at`, `assigned_time`, `assigned_to`, `status`, `exam_datetime`, `exam_mode`, `exam_location`, `extra_links`, `draft_pdf_path`, `repository_url`, `final_grade`) VALUES
(1, 1, 'nhddhdgfh', 'dhjgfhjsf', NULL, '2025-04-12 21:15:06', '2025-04-19 22:21:53', 2, 'awaiting_committee', NULL, 'onsite', NULL, NULL, NULL, NULL, NULL),
(2, 1, 'rjrjjrj', 'jejeje', NULL, '2025-04-13 21:13:59', '2025-04-19 22:21:53', 2, 'awaiting_committee', NULL, 'onsite', NULL, NULL, NULL, NULL, NULL),
(3, 1, 'gfgffgfgfc', 'gffdgfddrdf', 'uploads/67fc329d92ebf-Operating_Systems_Notes_201013F.pdf', '2025-04-13 21:54:37', '2025-04-19 22:27:10', 2, 'awaiting_committee', NULL, 'onsite', NULL, NULL, NULL, NULL, NULL),
(4, 1, 'kjdfhfd', 'hjdfgfhjsdf', NULL, '2025-04-16 12:21:44', '2025-04-21 14:55:52', 2, 'confirmed', NULL, 'onsite', NULL, NULL, NULL, NULL, NULL),
(5, 1, 'oikologia', 'tha kaneus mpla mpla', 'uploads/6803f383d98f0-Pick_Me_App(v0.1)1.pdf', '2025-04-19 19:03:31', '2025-04-19 22:21:53', 2, 'confirmed', NULL, 'onsite', NULL, NULL, NULL, NULL, NULL),
(6, 1, 'djkhfdskjh', 'kjdhfkjhsdljskdksljkjdsljsd', NULL, '2025-04-21 12:52:03', '2025-04-21 18:12:43', 2, 'awaiting_committee', NULL, 'onsite', NULL, NULL, NULL, NULL, NULL);

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
  `address` varchar(250) NOT NULL,
  `phone_mobile` int(11) NOT NULL,
  `phone_home` int(11) NOT NULL,
  `contact_email` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Άδειασμα δεδομένων του πίνακα `users`
--

INSERT INTO `users` (`id`, `am`, `name`, `surname`, `password`, `email`, `role`, `address`, `phone_mobile`, `phone_home`, `contact_email`) VALUES
(1, 0, 'papadopoulos', '', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'mar@gmail.com', 'teacher', '', 0, 0, ''),
(2, 1103077, 'valia', 'dionisiou', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'val@gmail.com', 'student', '52 oktobrioy 4545', 2147483647, 2147483647, 'marialena@gmail.com'),
(3, 0, '', '', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'elena@gmail.com', 'secretary', '', 0, 0, ''),
(4, 0, 'marial', 'karaiskoy', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'marial@gmail.com', 'teacher', '', 0, 0, ''),
(5, 0, 'panos', 'loutsos', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'lou@gmail.com', 'teacher', '', 0, 0, ''),
(6, 0, 'bill', 'tsou', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'tsou@gmail.com', 'teacher', '', 0, 0, ''),
(7, 0, 'ilias', 'makris', '$2y$10$MQWHKaP8rC2DVrk9AAO0T.TADkUCurzF1GyCtOs3LChG7vMrSqNRi', 'mac@gmail.com', '', '', 0, 0, '');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT για πίνακα `committee_requests`
--
ALTER TABLE `committee_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT για πίνακα `topics`
--
ALTER TABLE `topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT για πίνακα `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
