SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

CREATE DATABASE IF NOT EXISTS `phishingator` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `phishingator`;

CREATE TABLE `phg_campaigns` (
  `id_campaign` smallint(5) UNSIGNED NOT NULL,
  `id_by_user` mediumint(8) UNSIGNED NOT NULL,
  `id_email` smallint(5) UNSIGNED NOT NULL,
  `id_website` smallint(5) UNSIGNED NOT NULL,
  `id_onsubmit` tinyint(3) UNSIGNED NOT NULL,
  `id_ticket` int(10) UNSIGNED DEFAULT NULL,
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `time_active_since` time NOT NULL,
  `time_active_to` time NOT NULL,
  `date_active_since` date NOT NULL,
  `date_active_to` date NOT NULL,
  `date_added` datetime NOT NULL,
  `send_users_notification` tinyint(1) NOT NULL DEFAULT 1,
  `users_notification_language` SET('cz','en') NOT NULL DEFAULT 'cz',
  `visible` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `phg_campaigns_onsubmit` (
  `id_onsubmit` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visible` tinyint(1) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `phg_campaigns_onsubmit` (`id_onsubmit`, `name`, `url`, `visible`) VALUES
(1, 'Bez reakce', NULL, 1),
(2, 'Zobrazit vzdělávací stránku s indiciemi (po zadání čehokoliv)', NULL, 1),
(3, 'Zobrazit vzdělávací stránku s indiciemi (pouze po zadání platných přihlašovacích údajů, jinak zobrazit chybovou hlášku)', NULL, 1),
(4, 'Zobrazit chybovou hlášku o nesprávných přihlašovacích údajích', NULL, 1),
(5, 'Nechat uživatele dvakrát zadat přihlašovací údaje a po druhém zadání zobrazit vzdělávací stránku s indiciemi', NULL, 1);

CREATE TABLE `phg_campaigns_recipients` (
  `id_recipient` int(10) UNSIGNED NOT NULL,
  `id_campaign` smallint(5) UNSIGNED NOT NULL,
  `id_user` mediumint(8) UNSIGNED NOT NULL,
  `id_sign_by_user` mediumint(8) UNSIGNED DEFAULT NULL,
  `sign_date` datetime DEFAULT NULL,
  `signed` tinyint(1) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `phg_campaigns_stats` (
  `id_stats` int(10) UNSIGNED NOT NULL,
  `id_campaign` smallint(5) UNSIGNED NOT NULL,
  `id_action` tinyint(3) UNSIGNED NOT NULL,
  `action_type` tinyint(3) UNSIGNED NOT NULL,
  `action_value` smallint(5) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `phg_campaigns_stats_groups` (
  `id_group_stats` int(10) UNSIGNED NOT NULL,
  `id_campaign` smallint(5) UNSIGNED NOT NULL,
  `id_action` tinyint(3) UNSIGNED NOT NULL,
  `group_name` varchar(12) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_value` smallint(5) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `phg_captured_data` (
  `id_captured_data` int(10) UNSIGNED NOT NULL,
  `id_campaign` smallint(5) UNSIGNED NOT NULL,
  `id_user` mediumint(8) UNSIGNED NOT NULL,
  `id_action` tinyint(3) UNSIGNED NOT NULL,
  `used_email` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  `used_group` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visit_datetime` datetime DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `browser_fingerprint` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_json` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reported` tinyint(1) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `phg_captured_data_actions` (
  `id_action` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hex_color` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `css_color_class` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `phg_captured_data_actions` (`id_action`, `name`, `hex_color`, `css_color_class`) VALUES
(1, 'bez reakce', '00c851', 'success'),
(2, 'návštěva stránky', '33b5e5', 'info'),
(3, 'zadání neplatných údajů', 'ffbb33', 'warning'),
(4, 'zadání platných údajů', 'ff4444', 'danger'),
(5, 'zobrazení vzdělávací stránky', '004085', 'primary'),
(6, 'nahlášení phishingu', '563d7c', 'purple');

CREATE TABLE `phg_captured_data_end` (
  `id_captured_data` int(10) UNSIGNED NOT NULL,
  `id_campaign` smallint(5) UNSIGNED NOT NULL,
  `id_user` mediumint(8) UNSIGNED NOT NULL,
  `id_action` tinyint(3) UNSIGNED NOT NULL,
  `used_email` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  `used_group` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  `visit_datetime` datetime DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `browser_fingerprint` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_json` mediumtext COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `phg_emails` (
  `id_email` smallint(5) UNSIGNED NOT NULL,
  `id_by_user` mediumint(8) UNSIGNED NOT NULL,
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sender_name` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sender_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_added` datetime NOT NULL,
  `html` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `quishing` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `hidden` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `visible` tinyint(1) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `phg_emails_indications` (
  `id_indication` int(10) UNSIGNED NOT NULL,
  `id_by_user` mediumint(8) UNSIGNED NOT NULL,
  `id_email` smallint(5) UNSIGNED NOT NULL,
  `position` tinyint(3) UNSIGNED NOT NULL,
  `expression` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_added` datetime NOT NULL,
  `visible` tinyint(1) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `phg_sent_emails` (
  `id_event` int(10) UNSIGNED NOT NULL,
  `id_campaign` smallint(5) UNSIGNED NOT NULL,
  `id_email` smallint(5) UNSIGNED NOT NULL,
  `id_user` mediumint(8) UNSIGNED NOT NULL,
  `date_sent` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `phg_sent_notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_campaign` smallint(5) UNSIGNED NOT NULL,
  `id_user` mediumint(8) UNSIGNED NOT NULL,
  `id_notification_type` tinyint(3) UNSIGNED NOT NULL,
  `date_sent` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `phg_users` (
  `id_user` mediumint(8) UNSIGNED NOT NULL,
  `id_by_user` mediumint(8) UNSIGNED DEFAULT NULL,
  `id_user_group` smallint(5) UNSIGNED NOT NULL,
  `url` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  `departments` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recieve_email` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `email_limit` smallint(4) DEFAULT NULL,
  `date_added` datetime NOT NULL,
  `inactive` tinyint(1) UNSIGNED NOT NULL DEFAULT 0,
  `visible` tinyint(1) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `phg_users_groups` (
  `id_user_group` smallint(5) UNSIGNED NOT NULL,
  `id_by_user` mediumint(8) UNSIGNED NOT NULL,
  `id_parent_group` smallint(5) UNSIGNED DEFAULT NULL,
  `role` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `emails_restrictions` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `ldap_groups` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_added` datetime NOT NULL,
  `visible` tinyint(1) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `phg_users_groups` (`id_user_group`, `id_by_user`, `id_parent_group`, `role`, `name`, `description`, `emails_restrictions`, `ldap_groups`, `date_added`, `visible`) VALUES
(1, 1, NULL, 1, 'Administrátoři', '', '', '', '0000-00-00 00:00:00', 1),
(2, 1, NULL, 2, 'Správci testů', '', '', '', '0000-00-00 00:00:00', 1),
(3, 1, NULL, 3, 'Uživatelé', '', '', '', '0000-00-00 00:00:00', 1);

CREATE TABLE `phg_users_login_log` (
  `id_record` int(10) UNSIGNED NOT NULL,
  `id_user` mediumint(8) UNSIGNED NOT NULL,
  `login_datetime` datetime NOT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `phg_users_participation_log` (
  `id_record` int(10) UNSIGNED NOT NULL,
  `id_user` mediumint(8) UNSIGNED NOT NULL,
  `date_participation` datetime NOT NULL,
  `logged` tinyint(1) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `phg_users_roles` (
  `id_user_role` tinyint(3) UNSIGNED NOT NULL,
  `value` tinyint(1) UNSIGNED NOT NULL,
  `name` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `phg_users_roles` (`id_user_role`, `value`, `name`) VALUES
(1, 0, 'Administrátor'),
(2, 1, 'Správce testů'),
(3, 2, 'Uživatel');

CREATE TABLE `phg_websites` (
  `id_website` smallint(5) UNSIGNED NOT NULL,
  `id_by_user` mediumint(8) UNSIGNED NOT NULL,
  `id_template` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_added` datetime NOT NULL,
  `active` tinyint(1) NOT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `phg_websites_preview` (
  `id_preview` int(10) UNSIGNED NOT NULL,
  `id_website` smallint(5) UNSIGNED NOT NULL,
  `id_user` mediumint(8) UNSIGNED NOT NULL,
  `id_campaign` smallint(5) UNSIGNED DEFAULT NULL,
  `hash` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_active_since` datetime NOT NULL,
  `date_active_to` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `phg_websites_templates` (
  `id_website_template` tinyint(3) UNSIGNED NOT NULL,
  `name` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `server_dir` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cloned` tinyint(1) UNSIGNED NOT NULL DEFAULT 1,
  `visible` tinyint(1) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `phg_websites_templates` (`id_website_template`, `name`, `server_dir`, `cloned`, `visible`) VALUES
(1, 'Univerzální přihlašovací formulář', '/var/www/phishingator/templates/websites/1-generic-login/', 0, 1),
(2, 'Ubohý přihlašovací formulář s logem', '/var/www/phishingator/templates/websites/2-unstyled-login/', 0, 1);


ALTER TABLE `phg_campaigns`
  ADD PRIMARY KEY (`id_campaign`),
  ADD KEY `id_by_user` (`id_by_user`),
  ADD KEY `id_email` (`id_email`),
  ADD KEY `id_website` (`id_website`),
  ADD KEY `id_onsubmit` (`id_onsubmit`);

ALTER TABLE `phg_campaigns_onsubmit`
  ADD PRIMARY KEY (`id_onsubmit`);

ALTER TABLE `phg_campaigns_recipients`
  ADD PRIMARY KEY (`id_recipient`),
  ADD KEY `id_campaign` (`id_campaign`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_sign_by_user` (`id_sign_by_user`);

ALTER TABLE `phg_campaigns_stats`
  ADD PRIMARY KEY (`id_stats`),
  ADD KEY `id_campaign` (`id_campaign`),
  ADD KEY `id_action` (`id_action`);

ALTER TABLE `phg_campaigns_stats_groups`
  ADD PRIMARY KEY (`id_group_stats`),
  ADD KEY `id_campaign` (`id_campaign`),
  ADD KEY `id_action` (`id_action`);

ALTER TABLE `phg_captured_data`
  ADD PRIMARY KEY (`id_captured_data`),
  ADD KEY `id_campaign` (`id_campaign`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_action` (`id_action`);

ALTER TABLE `phg_captured_data_actions`
  ADD PRIMARY KEY (`id_action`);

ALTER TABLE `phg_captured_data_end`
  ADD PRIMARY KEY (`id_captured_data`);

ALTER TABLE `phg_emails`
  ADD PRIMARY KEY (`id_email`),
  ADD KEY `id_by_user` (`id_by_user`);

ALTER TABLE `phg_emails_indications`
  ADD PRIMARY KEY (`id_indication`),
  ADD KEY `id_by_user` (`id_by_user`),
  ADD KEY `id_email` (`id_email`);

ALTER TABLE `phg_sent_emails`
  ADD PRIMARY KEY (`id_event`),
  ADD KEY `id_campaign` (`id_campaign`),
  ADD KEY `id_email` (`id_email`),
  ADD KEY `id_user` (`id_user`);

ALTER TABLE `phg_sent_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_campaign` (`id_campaign`),
  ADD KEY `id_user` (`id_user`);

ALTER TABLE `phg_users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `url` (`url`),
  ADD KEY `id_by_user` (`id_by_user`),
  ADD KEY `id_user_group` (`id_user_group`),
  ADD KEY `idx_email_visible` (`email`, `visible`);

ALTER TABLE `phg_users_groups`
  ADD PRIMARY KEY (`id_user_group`),
  ADD KEY `id_by_user` (`id_by_user`),
  ADD KEY `id_parent_group` (`id_parent_group`);

ALTER TABLE `phg_users_login_log`
  ADD PRIMARY KEY (`id_record`),
  ADD KEY `id_user` (`id_user`);

ALTER TABLE `phg_users_participation_log`
  ADD PRIMARY KEY (`id_record`),
  ADD KEY `id_user` (`id_user`);

ALTER TABLE `phg_users_roles`
  ADD PRIMARY KEY (`id_user_role`);

ALTER TABLE `phg_websites`
  ADD PRIMARY KEY (`id_website`),
  ADD KEY `id_by_user` (`id_by_user`),
  ADD KEY `id_template` (`id_template`);

ALTER TABLE `phg_websites_preview`
  ADD PRIMARY KEY (`id_preview`),
  ADD KEY `id_website` (`id_website`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_campaign` (`id_campaign`);

ALTER TABLE `phg_websites_templates`
  ADD PRIMARY KEY (`id_website_template`);


ALTER TABLE `phg_campaigns`
  MODIFY `id_campaign` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `phg_campaigns_onsubmit`
  MODIFY `id_onsubmit` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

ALTER TABLE `phg_campaigns_recipients`
  MODIFY `id_recipient` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `phg_campaigns_stats`
  MODIFY `id_stats` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `phg_campaigns_stats_groups`
  MODIFY `id_group_stats` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `phg_captured_data`
  MODIFY `id_captured_data` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `phg_captured_data_actions`
  MODIFY `id_action` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `phg_captured_data_end`
  MODIFY `id_captured_data` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `phg_emails`
  MODIFY `id_email` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `phg_emails_indications`
  MODIFY `id_indication` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `phg_sent_emails`
  MODIFY `id_event` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `phg_sent_notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `phg_users`
  MODIFY `id_user` mediumint(8) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `phg_users_groups`
  MODIFY `id_user_group` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `phg_users_login_log`
  MODIFY `id_record` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `phg_users_participation_log`
  MODIFY `id_record` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `phg_users_roles`
  MODIFY `id_user_role` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

ALTER TABLE `phg_websites`
  MODIFY `id_website` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `phg_websites_preview`
  MODIFY `id_preview` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE `phg_websites_templates`
  MODIFY `id_website_template` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;


ALTER TABLE `phg_campaigns`
  ADD CONSTRAINT `phg_campaigns_ibfk_1` FOREIGN KEY (`id_by_user`) REFERENCES `phg_users` (`id_user`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `phg_campaigns_ibfk_2` FOREIGN KEY (`id_email`) REFERENCES `phg_emails` (`id_email`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `phg_campaigns_ibfk_3` FOREIGN KEY (`id_website`) REFERENCES `phg_websites` (`id_website`),
  ADD CONSTRAINT `phg_campaigns_ibfk_4` FOREIGN KEY (`id_onsubmit`) REFERENCES `phg_campaigns_onsubmit` (`id_onsubmit`);

ALTER TABLE `phg_campaigns_recipients`
  ADD CONSTRAINT `phg_campaigns_recipients_ibfk_1` FOREIGN KEY (`id_campaign`) REFERENCES `phg_campaigns` (`id_campaign`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `phg_campaigns_recipients_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `phg_users` (`id_user`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `phg_campaigns_recipients_ibfk_3` FOREIGN KEY (`id_sign_by_user`) REFERENCES `phg_users` (`id_user`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `phg_campaigns_stats`
  ADD CONSTRAINT `phg_campaigns_stats_ibfk_1` FOREIGN KEY (`id_campaign`) REFERENCES `phg_campaigns` (`id_campaign`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `phg_campaigns_stats_ibfk_2` FOREIGN KEY (`id_action`) REFERENCES `phg_captured_data_actions` (`id_action`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `phg_campaigns_stats_groups`
  ADD CONSTRAINT `phg_campaigns_stats_groups_ibfk_1` FOREIGN KEY (`id_campaign`) REFERENCES `phg_campaigns` (`id_campaign`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `phg_campaigns_stats_groups_ibfk_2` FOREIGN KEY (`id_action`) REFERENCES `phg_captured_data_actions` (`id_action`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `phg_captured_data`
  ADD CONSTRAINT `phg_captured_data_ibfk_1` FOREIGN KEY (`id_campaign`) REFERENCES `phg_campaigns` (`id_campaign`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `phg_captured_data_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `phg_users` (`id_user`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `phg_emails`
  ADD CONSTRAINT `phg_emails_ibfk_1` FOREIGN KEY (`id_by_user`) REFERENCES `phg_users` (`id_user`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `phg_emails_indications`
  ADD CONSTRAINT `phg_emails_indications_ibfk_1` FOREIGN KEY (`id_by_user`) REFERENCES `phg_users` (`id_user`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `phg_emails_indications_ibfk_2` FOREIGN KEY (`id_email`) REFERENCES `phg_emails` (`id_email`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `phg_sent_emails`
  ADD CONSTRAINT `phg_sent_emails_ibfk_1` FOREIGN KEY (`id_campaign`) REFERENCES `phg_campaigns` (`id_campaign`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `phg_sent_emails_ibfk_2` FOREIGN KEY (`id_email`) REFERENCES `phg_emails` (`id_email`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `phg_sent_emails_ibfk_3` FOREIGN KEY (`id_user`) REFERENCES `phg_users` (`id_user`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `phg_sent_notifications`
  ADD CONSTRAINT `phg_sent_notifications_ibfk_1` FOREIGN KEY (`id_campaign`) REFERENCES `phg_campaigns` (`id_campaign`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `phg_sent_notifications_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `phg_users` (`id_user`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `phg_users`
  ADD CONSTRAINT `phg_users_ibfk_1` FOREIGN KEY (`id_by_user`) REFERENCES `phg_users` (`id_user`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `phg_users_ibfk_2` FOREIGN KEY (`id_user_group`) REFERENCES `phg_users_groups` (`id_user_group`) ON DELETE NO ACTION ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
