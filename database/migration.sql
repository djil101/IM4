-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: mysql33j11.db.hostpoint.internal
-- Erstellungszeit: 14. Jun 2026 um 11:43
-- Server-Version: 10.11.18-MariaDB-log
-- PHP-Version: 8.3.31

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `afopulax_im4`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `devices`
--

DROP TABLE IF EXISTS `devices`;
CREATE TABLE `devices` (
  `id` int(11) NOT NULL,
  `serial_nr` varchar(100) NOT NULL,
  `family_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `device_data`
--

DROP TABLE IF EXISTS `device_data`;
CREATE TABLE `device_data` (
  `id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `bewegung` tinyint(1) NOT NULL DEFAULT 0,
  `laerm` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `families`
--

DROP TABLE IF EXISTS `families`;
CREATE TABLE `families` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `family_invites`
--

DROP TABLE IF EXISTS `family_invites`;
CREATE TABLE `family_invites` (
  `id` int(11) NOT NULL,
  `family_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(64) NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `notification_settings`
--

DROP TABLE IF EXISTS `notification_settings`;
CREATE TABLE `notification_settings` (
  `id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `quiet_from` time DEFAULT NULL,
  `quiet_until` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `family_id` int(11) DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `wake_events`
--

DROP TABLE IF EXISTS `wake_events`;
CREATE TABLE `wake_events` (
  `id` int(11) NOT NULL,
  `device_id` int(11) NOT NULL,
  `trigger_type` enum('bewegung','laerm','beides') NOT NULL,
  `triggered_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `devices`
--
ALTER TABLE `devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `serial_nr` (`serial_nr`),
  ADD KEY `family_id` (`family_id`);

--
-- Indizes für die Tabelle `device_data`
--
ALTER TABLE `device_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_id` (`device_id`);

--
-- Indizes für die Tabelle `families`
--
ALTER TABLE `families`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `family_invites`
--
ALTER TABLE `family_invites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `family_id` (`family_id`);

--
-- Indizes für die Tabelle `notification_settings`
--
ALTER TABLE `notification_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_device` (`device_id`);

--
-- Indizes für die Tabelle `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_family` (`family_id`);

--
-- Indizes für die Tabelle `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Indizes für die Tabelle `wake_events`
--
ALTER TABLE `wake_events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_id` (`device_id`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `devices`
--
ALTER TABLE `devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `device_data`
--
ALTER TABLE `device_data`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `families`
--
ALTER TABLE `families`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `family_invites`
--
ALTER TABLE `family_invites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `notification_settings`
--
ALTER TABLE `notification_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT für Tabelle `wake_events`
--
ALTER TABLE `wake_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `devices`
--
ALTER TABLE `devices`
  ADD CONSTRAINT `devices_ibfk_1` FOREIGN KEY (`family_id`) REFERENCES `families` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `device_data`
--
ALTER TABLE `device_data`
  ADD CONSTRAINT `device_data_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `family_invites`
--
ALTER TABLE `family_invites`
  ADD CONSTRAINT `family_invites_ibfk_1` FOREIGN KEY (`family_id`) REFERENCES `families` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `notification_settings`
--
ALTER TABLE `notification_settings`
  ADD CONSTRAINT `fk_notif_device` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_family` FOREIGN KEY (`family_id`) REFERENCES `families` (`id`) ON DELETE SET NULL;

--
-- Constraints der Tabelle `wake_events`
--
ALTER TABLE `wake_events`
  ADD CONSTRAINT `wake_events_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE;
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
