-- phpMyAdmin SQL Dump
-- version 3.4.2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jun 28, 2011 at 01:47 AM
-- Server version: 5.5.10
-- PHP Version: 5.3.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `iohelix_games`
--

-- --------------------------------------------------------

--
-- Table structure for table `ph_chat`
--

CREATE TABLE IF NOT EXISTS `ph_chat` (
  `chat_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `message` text COLLATE latin1_general_ci NOT NULL,
  `from_id` int(10) unsigned NOT NULL DEFAULT '0',
  `game_id` int(10) unsigned NOT NULL DEFAULT '0',
  `private` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`chat_id`),
  KEY `game_id` (`game_id`),
  KEY `private` (`private`),
  KEY `from_id` (`from_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ph_game`
--

CREATE TABLE IF NOT EXISTS `ph_game` (
  `game_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `white_id` int(10) unsigned DEFAULT NULL,
  `black_id` int(10) unsigned DEFAULT NULL,
  `state` enum('Waiting','Playing','Finished','Draw') COLLATE latin1_general_ci NOT NULL DEFAULT 'Playing',
  `extra_info` text COLLATE latin1_general_ci,
  `winner_id` int(10) unsigned DEFAULT NULL,
  `setup_id` int(10) unsigned NOT NULL,
  `paused` tinyint(1) NOT NULL DEFAULT '0',
  `create_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modify_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`game_id`),
  KEY `state` (`state`),
  KEY `white_id` (`white_id`),
  KEY `black_id` (`black_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ph_game_history`
--

CREATE TABLE IF NOT EXISTS `ph_game_history` (
  `game_id` int(10) unsigned NOT NULL DEFAULT '0',
  `move` varchar(255) COLLATE latin1_general_ci DEFAULT NULL,
  `hits` varchar(255) COLLATE latin1_general_ci DEFAULT NULL,
  `board` varchar(87) COLLATE latin1_general_ci NOT NULL,
  `extra_info` text COLLATE latin1_general_ci,
  `move_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `game_id` (`game_id`),
  KEY `move_date` (`move_date`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ph_game_nudge`
--

CREATE TABLE IF NOT EXISTS `ph_game_nudge` (
  `game_id` int(10) unsigned NOT NULL DEFAULT '0',
  `player_id` int(10) unsigned NOT NULL DEFAULT '0',
  `nudged` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `game_player` (`game_id`,`player_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ph_message`
--

CREATE TABLE IF NOT EXISTS `ph_message` (
  `message_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subject` varchar(255) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `message` text COLLATE latin1_general_ci NOT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`message_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ph_message_glue`
--

CREATE TABLE IF NOT EXISTS `ph_message_glue` (
  `message_glue_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` int(10) unsigned NOT NULL DEFAULT '0',
  `from_id` int(10) unsigned NOT NULL DEFAULT '0',
  `to_id` int(10) unsigned NOT NULL DEFAULT '0',
  `send_date` datetime DEFAULT NULL,
  `expire_date` datetime DEFAULT NULL,
  `view_date` datetime DEFAULT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`message_glue_id`),
  KEY `outbox` (`from_id`,`message_id`),
  KEY `created` (`create_date`),
  KEY `expire_date` (`expire_date`),
  KEY `inbox` (`to_id`,`from_id`,`send_date`,`deleted`),
  KEY `message_id` (`message_id`,`to_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ph_ph_player`
--

CREATE TABLE IF NOT EXISTS `ph_ph_player` (
  `player_id` int(11) unsigned NOT NULL DEFAULT '0',
  `is_admin` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `allow_email` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `color` varchar(25) COLLATE latin1_general_ci NOT NULL DEFAULT 'blue_white',
  `invite_opt_out` tinyint(1) NOT NULL DEFAULT '0',
  `max_games` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `wins` smallint(5) unsigned NOT NULL DEFAULT '0',
  `draws` smallint(5) unsigned NOT NULL DEFAULT '0',
  `losses` smallint(5) unsigned NOT NULL DEFAULT '0',
  `last_online` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `id` (`player_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ph_settings`
--

CREATE TABLE IF NOT EXISTS `ph_settings` (
  `setting` varchar(255) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `value` text COLLATE latin1_general_ci NOT NULL,
  `notes` text COLLATE latin1_general_ci,
  `sort` smallint(5) unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY `setting` (`setting`),
  KEY `sort` (`sort`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Dumping data for table `ph_settings`
--

INSERT INTO `ph_settings` (`setting`, `value`, `notes`, `sort`) VALUES
('site_name', 'Your Site', 'The name of your site', 10),
('default_color', 'c_red_black.css', 'The default theme color for the script pages', 20),
('nav_links', '<a href="/">Home</a>', 'HTML code for your site''s navigation links to display on the script pages', 30),
('from_email', 'auto.mail@yoursite.net', 'The email address used to send game emails', 40),
('to_email', 'you@yoursite.net', 'The email address to send admin notices to (comma separated)', 50),
('new_users', '1', '(1/0) Allow new users to register (0 = off)', 60),
('approve_users', '0', '(1/0) Require admin approval for new users (0 = off)', 70),
('confirm_email', '0', '(1/0) Require email confirmation for new users (0 = off)', 80),
('max_users', '0', 'Max users allowed to register (0 = off)', 90),
('default_pass', 'change!me', 'The password to use when resetting a user''s password', 100),
('expire_users', '45', 'Number of days until untouched user accounts are deleted (0 = off)', 110),
('save_games', '1', '(1/0) Save games in the ''games'' directory on the server (0 = off)', 120),
('expire_finished_games', '7', 'Number of days until finished games are deleted (0 = off)', 128),
('expire_games', '30', 'Number of days until untouched games are deleted (0 = off)', 130),
('nudge_flood_control', '24', 'Number of hours between nudges. (-1 = no nudging, 0 = no flood control)', 135),
('timezone', 'UTC', 'The timezone to use for dates (<a href="http://www.php.net/manual/en/timezones.php">List of Timezones</a>)', 140),
('long_date', 'M j, Y g:i a', 'The long format for dates (<a href="http://www.php.net/manual/en/function.date.php">Date Format Codes</a>)', 150),
('short_date', 'Y.m.d H:i', 'The short format for dates (<a href="http://www.php.net/manual/en/function.date.php">Date Format Codes</a>)', 160),
('debug_pass', '', 'The DEBUG password to use to set temporary DEBUG status for the script', 170),
('DB_error_log', '1', '(1/0) Log database errors to the ''logs'' directory on the server (0 = off)', 180),
('DB_error_email', '0', '(1/0) Email database errors to the admin email addresses given (0 = off)', 190);

-- --------------------------------------------------------

--
-- Table structure for table `ph_setup`
--

CREATE TABLE IF NOT EXISTS `ph_setup` (
  `setup_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `board` varchar(87) COLLATE latin1_general_ci NOT NULL,
  `reflection` enum('Origin','Short','Long','None') COLLATE latin1_general_ci NOT NULL DEFAULT 'Origin',
  `has_horus` tinyint(1) NOT NULL DEFAULT '0',
  `has_tower` tinyint(1) NOT NULL DEFAULT '0',
  `used` int(11) NOT NULL DEFAULT '0',
  `silver_wins` int(10) unsigned NOT NULL DEFAULT '0',
  `draws` int(10) unsigned NOT NULL DEFAULT '0',
  `red_wins` int(10) unsigned NOT NULL DEFAULT '0',
  `shortest_game` int(10) unsigned DEFAULT NULL,
  `longest_game` int(10) unsigned DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'the player id of the player that created the setup',
  PRIMARY KEY (`setup_id`),
  KEY `has_horus` (`has_horus`),
  KEY `has_tower` (`has_tower`),
  KEY `active` (`active`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

--
-- Dumping data for table `ph_setup`
--

INSERT INTO `ph_setup` (`setup_id`, `name`, `board`, `reflection`, `has_horus`, `has_sphynx`, `has_anubis`, `has_tower`, `used`, `created`, `created_by`) VALUES
(NULL, 'Classic', '4wpwb2/2c7/3D6/a1C1xy1b1D/b1D1YX1a1C/6b3/7A2/2DWPW4', 'Origin', 0, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Dynasty', '4cwb3/5p4/a3cwy3/b1x1D1B3/3d1b1X1D/3YWA3C/4P5/3DWA4', 'Origin', 0, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Imhotep', '4wpwy2/10/3D2a3/aC2By2bD/bD2Yd2aC/3C2b3/10/2YWPW4', 'Origin', 0, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Osiris', '1Y3cp1bC/2w3wb2/6D3/a3ix3D/b3XI3C/3b6/2DW3W2/aD1PA3y1', 'Origin', 1, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Isis', '6wpb1/a1x3cw2/1X8/a1a1DI2c1/1A2ib1C1C/8x1/2WA3X1C/1DPW6', 'Origin', 1, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Classic 2', '4wpwb2/2c7/3D6/a1C1xi1b1D/b1D1IX1a1C/6b3/7A2/2DWPW4', 'Origin', 1, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Dynasty 2', '4cwb3/5p4/a3cwy3/b1h1D1B3/3d1b1H1D/3YWA3C/4P5/3DWA4', 'Origin', 1, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Imhotep 2', '4wpwy2/10/3D2a3/aC2Bi2bD/bD2Id2aC/3C2b3/10/2YWPW4', 'Origin', 1, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Khufu', '4wpwb2/5cb3/a5D3/4yX1a1D/b1C1Xy4/3b5C/3DA5/2DWPW4', 'Origin', 0, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Imseti', '1B1wpb4/2Xbcw4/10/a3xc3D/b3AX3C/10/4WADx2/4DPW1d1', 'Origin', 0, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Nefertiti', '4w1w3/3c1pb3/2C1cy1c2/a1Y6D/b6y1C/2A1YA1a2/3DP1A3/3W1W4', 'Origin', 0, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Rameses', '3w1pwb2/4bc4/2Cb2x3/a4X3D/b3x4C/3X2Da2/4AD4/2DWP1W3', 'Origin', 0, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Amarna', '1CBcwpw3/4bcb3/10/a2x2x3/3X2X2C/10/3DAD4/3WPWAda1', 'Origin', 0, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Saqqara', '3cwp1wb1/4bxb3/a2D6/4X4D/b4x4/6b2C/3DXD4/1DW1PWA3', 'Origin', 0, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Djoser''s Step', '3cw1w1b1/5p1b2/4bxb3/a4y3D/b3Y4C/3DXD4/2D1P5/1D1W1WA3', 'Origin', 0, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Horemheb', '3c3b2/4wpw3/3x1x1b2/a3c1b2D/b2D1A3C/2D1X1X3/3WPW4/2D3A3', 'Origin', 0, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Senet', '4cwb3/a2c1p1b2/4xwy3/5b3C/a3D5/3YWX4/2D1P1A2C/3DWA4', 'Origin', 0, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Tutankhamun', '3w4b1/a1cpb5/3w1b4/b1x1y1b3/3D1Y1X1D/4D1W3/5DPA1C/1D4W3', 'Origin', 0, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Offla', '3c1pwb2/4cwb3/2X2x4/a4D3D/b3b4C/4X2x2/3DWA4/2DWP1A3', 'Origin', 0, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Ebana', '3xwpwb2/5x4/2DA6/a1CB5D/b5da1C/6cb2/4X5/2DWPWX3', 'Origin', 0, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Qa''a', '3xwpwb2/4cy4/8bD/3c2b2C/a2D2A3/bD8/4YA4/2DWPWX3', 'Origin', 0, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Qa''a 2', '3xwpwb2/4cy4/8bD/3c2b1hC/aH1D2A3/bD8/4YA4/2DWPWX3', 'Origin', 1, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Seti I', '1XB1cw1pb1/a4cwy1D/6b3/10/10/3D6/b1YWA4C/1DP1WA1dx1', 'Origin', 0, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Seti II', '1XB1cw1pb1/a4cwy1D/6b3/5i4/4I5/3D6/b1YWA4C/1DP1WA1dx1', 'Origin', 1, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Ay', '4cw1wbC/3CB5/5x4/a3Ypb3/3DPy3C/4X5/5da3/aDW1WA4', 'Origin', 0, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Horemheb 2', '3c3b2/4wpw3/3h1x1b2/a3c1b2D/b2D1A3C/2D1X1H3/3WPW4/2D3A3', 'Origin', 1, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Akhenaten 2', '1B1c1p1bc1/4cwb3/h1y7/4da1X1H/h1x1CB4/7Y1H/3DWA4/1AD1P1A1d1', 'Origin', 1, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Akhenaten', '1B1c1p1bc1/4cwb3/a1y7/4da1X1D/b1x1CB4/7Y1C/3DWA4/1AD1P1A1d1', 'Origin', 0, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Dendera', '2c4b2/1x2da2y1/3d2a3/2d1pi1a2/2C1IP1B2/3C2B3/1Y2CB2X1/2D4A2', 'Origin', 1, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Nefertiti B', '4w1w3/3c1pb3/a1C1cy1c2/b1Y7/7y1D/2A1YA1a1C/3DP1A3/3W1W4', 'Origin', 0, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Tutankhamun B', '4w3b1/a2cpb4/4w1b3/b2x1y1b2/2D1Y1X2D/3D1W4/4DPA2C/1D3W4', 'Origin', 0, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Setup No. 1', '4wpw3/5b4/4b1c2C/a4xy1bD/bD1YX4C/a2A1D4/4D5/3WPW4', 'Origin', 0, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Diamond', '3cwb4/4p5/3Dw5/aC2yx2cD/bA2XY2aC/5Wb3/5P4/4DWA3', 'Origin', 0, 0, 0, 0, 0, NOW(), 0),
(NULL, 'Ivory', '2cpw1B2C/3w6/1a6c1/aC2x1X3/3x1X2aC/1A6C1/6W3/a2d1WPA2', 'Origin', 0, 0, 0, 0, 0, NOW(), 0);

-- --------------------------------------------------------

--
-- Table structure for table `ph_stats`
--

DROP TABLE IF EXISTS `ph_stats`;
CREATE TABLE IF NOT EXISTS `ph_stats` (
  `player_id` int(10) unsigned NOT NULL,
  `game_id` int(10) unsigned NOT NULL,
  `setup_id` int(10) unsigned NOT NULL,
  `color` enum('white','black') COLLATE latin1_general_ci NOT NULL,
  `win` tinyint(1) NOT NULL DEFAULT '0',
  `move_count` int(10) unsigned NOT NULL DEFAULT '0',
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `hour_count` float(8,3) NOT NULL DEFAULT '0.000',
  UNIQUE KEY `player_id` (`player_id`,`game_id`,`setup_id`),
  KEY `move_count` (`move_count`),
  KEY `hour_count` (`hour_count`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `player`
--

CREATE TABLE IF NOT EXISTS `player` (
  `player_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(20) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `first_name` varchar(20) COLLATE latin1_general_ci DEFAULT NULL,
  `last_name` varchar(20) COLLATE latin1_general_ci DEFAULT NULL,
  `email` varchar(255) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `timezone` varchar(255) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `is_admin` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `password` varchar(32) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `alt_pass` varchar(32) COLLATE latin1_general_ci NOT NULL DEFAULT '',
  `ident` varchar(32) COLLATE latin1_general_ci DEFAULT NULL,
  `token` varchar(32) COLLATE latin1_general_ci DEFAULT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_approved` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`player_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
