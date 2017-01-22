-- phpMyAdmin SQL Dump
-- version 4.5.1
-- http://www.phpmyadmin.net
--
-- Φιλοξενητής: 127.0.0.1
-- Χρόνος δημιουργίας: 16 Δεκ 2016 στις 09:48:37
-- Έκδοση διακομιστή: 10.1.13-MariaDB
-- Έκδοση PHP: 5.6.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Βάση δεδομένων: `questionnaire`
--

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `dk_answers`
--

CREATE TABLE `dk_answers` (
  `id` int(11) NOT NULL,
  `questionnaire_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer` text NOT NULL,
  `user_id` int(11) NOT NULL,
  `time` datetime NOT NULL,
  `type` enum('radio','check','freetext','file') NOT NULL,
  `filename` text,
  `hashname` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `dk_channel`
--

CREATE TABLE `dk_channel` (
  `id` int(11) NOT NULL,
  `title` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Άδειασμα δεδομένων του πίνακα `dk_channel`
--

INSERT INTO `dk_channel` (`id`, `title`) VALUES
  (1, 'Ονομαστική Αξιολόγηση'),
  (2, 'Ανώνυμη Αξιολόγηση'),
  (3, 'Αξιολόγηση Token'),
  (4, 'Αξιολόγηση API');

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `dk_ips`
--

CREATE TABLE `dk_ips` (
  `id` int(11) NOT NULL,
  `ip` text NOT NULL,
  `timestamp` datetime NOT NULL,
  `questionnaire_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `dk_lessons`
--

CREATE TABLE `dk_lessons` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `dk_question`
--

CREATE TABLE `dk_question` (
  `id` int(11) NOT NULL,
  `question` text NOT NULL,
  `type` enum('radio','check','freetext','file') NOT NULL,
  `template` int(1) NOT NULL DEFAULT '0',
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `dk_questionnaire`
--

CREATE TABLE `dk_questionnaire` (
  `id` int(11) NOT NULL,
  `title` varchar(30) NOT NULL,
  `description` text NOT NULL,
  `time_begins` datetime NOT NULL,
  `time_ends` datetime NOT NULL,
  `last_edit_time` datetime NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `last_editor` int(11) NOT NULL DEFAULT '0',
  `template` int(1) NOT NULL DEFAULT '0',
  `lockedtime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `dk_questionnaire_channel`
--

CREATE TABLE `dk_questionnaire_channel` (
  `id` int(11) NOT NULL,
  `id_questionnaire` int(11) NOT NULL,
  `id_channel` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `dk_questionnaire_lessons`
--

CREATE TABLE `dk_questionnaire_lessons` (
  `id` int(11) NOT NULL,
  `questionnaire_id` int(11) NOT NULL,
  `lessons_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `dk_questionnaire_questions`
--

CREATE TABLE `dk_questionnaire_questions` (
  `id` int(11) NOT NULL,
  `questionnaire_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `order_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `dk_question_options`
--

CREATE TABLE `dk_question_options` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `pick` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `dk_tokens`
--

CREATE TABLE `dk_tokens` (
  `id` int(11) NOT NULL,
  `token_code` varchar(30) NOT NULL,
  `user_id` int(11) NOT NULL,
  `questionnaire_id` int(11) NOT NULL,
  `seira` varchar(20) NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `used` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Δομή πίνακα για τον πίνακα `dk_users`
--

CREATE TABLE `dk_users` (
  `id` int(11) NOT NULL,
  `username` varchar(150) NOT NULL,
  `email` varchar(30) NOT NULL,
  `password` char(32) NOT NULL,
  `type` int(1) NOT NULL,
  `activated` int(1) NOT NULL DEFAULT '0',
  `last_name` varchar(35) NOT NULL,
  `first_name` varchar(30) NOT NULL,
  `telephone` varchar(10) NOT NULL,
  `aem` int(10) DEFAULT NULL,
  `academic_id` int(10) DEFAULT NULL,
  `status_data` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Ευρετήρια για άχρηστους πίνακες
--

--
-- Ευρετήρια για πίνακα `dk_answers`
--
ALTER TABLE `dk_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `questionnaire_id` (`questionnaire_id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `fk_user_id` (`user_id`);

--
-- Ευρετήρια για πίνακα `dk_channel`
--
ALTER TABLE `dk_channel`
  ADD PRIMARY KEY (`id`);

--
-- Ευρετήρια για πίνακα `dk_ips`
--
ALTER TABLE `dk_ips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_questionnaire_id` (`questionnaire_id`);

--
-- Ευρετήρια για πίνακα `dk_lessons`
--
ALTER TABLE `dk_lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Ευρετήρια για πίνακα `dk_question`
--
ALTER TABLE `dk_question`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Ευρετήρια για πίνακα `dk_questionnaire`
--
ALTER TABLE `dk_questionnaire`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_questionnaire_user_id` (`user_id`),
  ADD KEY `fk_questionnaire_last_editor_id` (`last_editor`);

--
-- Ευρετήρια για πίνακα `dk_questionnaire_channel`
--
ALTER TABLE `dk_questionnaire_channel`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_id_questionnaire_channel` (`id_questionnaire`),
  ADD KEY `fk_id_channel_questionnaire` (`id_channel`);

--
-- Ευρετήρια για πίνακα `dk_questionnaire_lessons`
--
ALTER TABLE `dk_questionnaire_lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_lessons_id` (`lessons_id`),
  ADD KEY `fk_questionnaire_lessons_id` (`questionnaire_id`);

--
-- Ευρετήρια για πίνακα `dk_questionnaire_questions`
--
ALTER TABLE `dk_questionnaire_questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `questionnaire_id` (`questionnaire_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Ευρετήρια για πίνακα `dk_question_options`
--
ALTER TABLE `dk_question_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Ευρετήρια για πίνακα `dk_tokens`
--
ALTER TABLE `dk_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_questionnaire_token_id` (`questionnaire_id`),
  ADD KEY `fk_user_token_id` (`user_id`);

--
-- Ευρετήρια για πίνακα `dk_users`
--
ALTER TABLE `dk_users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT για άχρηστους πίνακες
--

--
-- AUTO_INCREMENT για πίνακα `dk_answers`
--
ALTER TABLE `dk_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT για πίνακα `dk_channel`
--
ALTER TABLE `dk_channel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT για πίνακα `dk_ips`
--
ALTER TABLE `dk_ips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT για πίνακα `dk_lessons`
--
ALTER TABLE `dk_lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;
--
-- AUTO_INCREMENT για πίνακα `dk_question`
--
ALTER TABLE `dk_question`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;
--
-- AUTO_INCREMENT για πίνακα `dk_questionnaire`
--
ALTER TABLE `dk_questionnaire`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;
--
-- AUTO_INCREMENT για πίνακα `dk_questionnaire_channel`
--
ALTER TABLE `dk_questionnaire_channel`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;
--
-- AUTO_INCREMENT για πίνακα `dk_questionnaire_lessons`
--
ALTER TABLE `dk_questionnaire_lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
--
-- AUTO_INCREMENT για πίνακα `dk_questionnaire_questions`
--
ALTER TABLE `dk_questionnaire_questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;
--
-- AUTO_INCREMENT για πίνακα `dk_question_options`
--
ALTER TABLE `dk_question_options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;
--
-- AUTO_INCREMENT για πίνακα `dk_tokens`
--
ALTER TABLE `dk_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=671;
--
-- AUTO_INCREMENT για πίνακα `dk_users`
--
ALTER TABLE `dk_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
--
-- Περιορισμοί για άχρηστους πίνακες
--

--
-- Περιορισμοί για πίνακα `dk_answers`
--
ALTER TABLE `dk_answers`
  ADD CONSTRAINT `dk_answers_ibfk_1` FOREIGN KEY (`questionnaire_id`) REFERENCES `dk_questionnaire` (`id`),
  ADD CONSTRAINT `dk_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `dk_question` (`id`),
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `dk_users` (`id`);

--
-- Περιορισμοί για πίνακα `dk_ips`
--
ALTER TABLE `dk_ips`
  ADD CONSTRAINT `fk_questionnaire_id` FOREIGN KEY (`questionnaire_id`) REFERENCES `dk_questionnaire` (`id`);

--
-- Περιορισμοί για πίνακα `dk_lessons`
--
ALTER TABLE `dk_lessons`
  ADD CONSTRAINT `dk_lessons_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `dk_users` (`id`);

--
-- Περιορισμοί για πίνακα `dk_question`
--
ALTER TABLE `dk_question`
  ADD CONSTRAINT `question_user_id` FOREIGN KEY (`user_id`) REFERENCES `dk_users` (`id`);

--
-- Περιορισμοί για πίνακα `dk_questionnaire`
--
ALTER TABLE `dk_questionnaire`
  ADD CONSTRAINT `fk_questionnaire_last_editor_id` FOREIGN KEY (`last_editor`) REFERENCES `dk_users` (`id`),
  ADD CONSTRAINT `fk_questionnaire_user_id` FOREIGN KEY (`user_id`) REFERENCES `dk_users` (`id`);

--
-- Περιορισμοί για πίνακα `dk_questionnaire_channel`
--
ALTER TABLE `dk_questionnaire_channel`
  ADD CONSTRAINT `fk_id_channel_questionnaire` FOREIGN KEY (`id_channel`) REFERENCES `dk_channel` (`id`),
  ADD CONSTRAINT `fk_id_questionnaire_channel` FOREIGN KEY (`id_questionnaire`) REFERENCES `dk_questionnaire` (`id`);

--
-- Περιορισμοί για πίνακα `dk_questionnaire_lessons`
--
ALTER TABLE `dk_questionnaire_lessons`
  ADD CONSTRAINT `fk_lessons_id` FOREIGN KEY (`lessons_id`) REFERENCES `dk_lessons` (`id`),
  ADD CONSTRAINT `fk_questionnaire_lessons_id` FOREIGN KEY (`questionnaire_id`) REFERENCES `dk_questionnaire` (`id`);

--
-- Περιορισμοί για πίνακα `dk_questionnaire_questions`
--
ALTER TABLE `dk_questionnaire_questions`
  ADD CONSTRAINT `dk_questionnaire_questions_ibfk_1` FOREIGN KEY (`questionnaire_id`) REFERENCES `dk_questionnaire` (`id`),
  ADD CONSTRAINT `dk_questionnaire_questions_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `dk_question` (`id`);

--
-- Περιορισμοί για πίνακα `dk_question_options`
--
ALTER TABLE `dk_question_options`
  ADD CONSTRAINT `question_id` FOREIGN KEY (`question_id`) REFERENCES `dk_question` (`id`);

--
-- Περιορισμοί για πίνακα `dk_tokens`
--
ALTER TABLE `dk_tokens`
  ADD CONSTRAINT `fk_questionnaire_token_id` FOREIGN KEY (`questionnaire_id`) REFERENCES `dk_questionnaire` (`id`),
  ADD CONSTRAINT `fk_user_token_id` FOREIGN KEY (`user_id`) REFERENCES `dk_users` (`id`);
