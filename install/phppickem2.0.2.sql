-- phpMyAdmin SQL Dump
-- version 4.0.10.20
-- https://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 24, 2018 at 09:21 PM
-- Server version: 5.6.40
-- PHP Version: 5.3.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `nflpickem`
--

-- --------------------------------------------------------

--
-- Table structure for table `nflp_comments`
--

DROP TABLE IF EXISTS `nflp_comments`;
CREATE TABLE `nflp_comments` (
  `commentID` int(11) NOT NULL AUTO_INCREMENT,
  `userID` int(11) NOT NULL,
  `subject` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment` longtext COLLATE utf8_unicode_ci NOT NULL,
  `postDateTime` datetime DEFAULT NULL,
  PRIMARY KEY (`commentID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nflp_divisions`
--

DROP TABLE IF EXISTS `nflp_divisions`;
CREATE TABLE `nflp_divisions` (
  `divisionID` int(11) NOT NULL AUTO_INCREMENT,
  `conference` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `division` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`divisionID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=9 ;

--
-- Dumping data for table `nflp_divisions`
--

INSERT INTO `nflp_divisions` (`divisionID`, `conference`, `division`) VALUES
(1, 'AFC', 'North'),
(2, 'AFC', 'South'),
(3, 'AFC', 'East'),
(4, 'AFC', 'West'),
(5, 'NFC', 'North'),
(6, 'NFC', 'South'),
(7, 'NFC', 'East'),
(8, 'NFC', 'West');

-- --------------------------------------------------------

--
-- Table structure for table `nflp_email_templates`
--

DROP TABLE IF EXISTS `nflp_email_templates`;
CREATE TABLE `nflp_email_templates` (
  `email_template_key` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `email_template_title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `default_subject` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `default_message` text COLLATE utf8_unicode_ci,
  `subject` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`email_template_key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `nflp_email_templates`
BEGIN;
INSERT INTO `nflp_email_templates` VALUES ('WEEKLY_PICKS_REMINDER', 'Weekly Picks Reminder', 'NFL Pick \'Em Week {week} Reminder', 'Hello {player},<br /><br />You are receiving this email because you do not yet have all of your picks in for week {week}.&nbsp; This is your reminder.&nbsp; The first game is {first_game} (Eastern), so to receive credit for that game, you\'ll have to make your pick before then.<br /><br />Links:<br />&nbsp;- NFL Pick \'Em URL: {site_url}<br />&nbsp;- Help/Rules: {rules_url}<br /><br />Good Luck!<br />', 'NFL Pick \'Em Week {week} Reminder', 'Hello {player},<br /><br />You are receiving this email because you do not yet have all of your picks in for week {week}.&nbsp; This is your reminder.&nbsp; The first game is {first_game} (Eastern), so to receive credit for that game, you\'ll have to make your pick before then.<br /><br />Links:<br />&nbsp;- NFL Pick \'Em URL: {site_url}<br />&nbsp;- Help/Rules: {rules_url}<br /><br />Good Luck!<br />'), ('WEEKLY_RESULTS_REMINDER', 'Last Week Results/Reminder', 'NFL Pick \'Em Week {previousWeek} Standings/Reminder', 'Congratulations this week go to {winners} for winning week {previousWeek}.  The winner(s) had {winningScore} out of {possibleScore} picks correct.<br /><br />The current leaders are:<br />{currentLeaders}<br /><br />The most accurate players are:<br />{bestPickRatios}<br /><br />*Reminder* - Please make your picks for week {week} before {first_game} (Eastern).<br /><br />Links:<br />&nbsp;- NFL Pick \'Em URL: {site_url}<br />&nbsp;- Help/Rules: {rules_url}<br /><br />Good Luck!<br />', 'NFL Pick \'Em Week {previousWeek} Standings/Reminder', 'Congratulations this week go to {winners} for winning week {previousWeek}.  The winner(s) had {winningScore} out of {possibleScore} picks correct.<br /><br />The current leaders are:<br />{currentLeaders}<br /><br />The most accurate players are:<br />{bestPickRatios}<br /><br />*Reminder* - Please make your picks for week {week} before {first_game} (Eastern).<br /><br />Links:<br />&nbsp;- NFL Pick \'Em URL: {site_url}<br />&nbsp;- Help/Rules: {rules_url}<br /><br />Good Luck!<br />'), ('FINAL_RESULTS', 'Final Results', 'NFL Pick \'Em 2015 Final Results', 'Congratulations this week go to {winners} for winning week\r\n{previousWeek}. The winner(s) had {winningScore} out of {possibleScore}\r\npicks correct.<br /><br /><span style=\"font-weight: bold;\">Congratulations to {final_winner}</span> for winning NFL Pick \'Em 2015!&nbsp; {final_winner} had {final_winningScore} wins and had a pick ratio of {picks}/{possible} ({pickpercent}%).<br /><br />Top Wins:<br />{currentLeaders}<br /><br />The most accurate players are:<br />{bestPickRatios}<br /><br />Thanks for playing, and I hope to see you all again for NFL Pick \'Em 2012!', 'NFL Pick \'Em 2015 Final Results', 'Congratulations this week go to {winners} for winning week\r\n{previousWeek}. The winner(s) had {winningScore} out of {possibleScore}\r\npicks correct.<br /><br /><span style=\"font-weight: bold;\">Congratulations to {final_winner}</span> for winning NFL Pick \'Em 2015!&nbsp; {final_winner} had {final_winningScore} wins and had a pick ratio of {picks}/{possible} ({pickpercent}%).<br /><br />Top Wins:<br />{currentLeaders}<br /><br />The most accurate players are:<br />{bestPickRatios}<br /><br />Thanks for playing, and I hope to see you all again for NFL Pick \'Em 2018!');
COMMIT;

-- --------------------------------------------------------

--
-- Table structure for table `nflp_picks`
--

DROP TABLE IF EXISTS `nflp_picks`;
CREATE TABLE `nflp_picks` (
  `userID` int(11) NOT NULL,
  `gameID` int(11) NOT NULL,
  `pickID` varchar(10) NOT NULL,
  `points` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`userID`,`gameID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `nflp_picksummary`
--

DROP TABLE IF EXISTS `nflp_picksummary`;
CREATE TABLE `nflp_picksummary` (
  `weekNum` int(11) NOT NULL DEFAULT '0',
  `userID` int(11) NOT NULL DEFAULT '0',
  `tieBreakerPoints` int(11) NOT NULL DEFAULT '0',
  `showPicks` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`weekNum`,`userID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=FIXED;

-- --------------------------------------------------------

--
-- Table structure for table `nflp_schedule`
--

DROP TABLE IF EXISTS `nflp_schedule`;
CREATE TABLE `nflp_schedule` (
  `gameID` int(11) NOT NULL AUTO_INCREMENT,
  `weekNum` int(11) NOT NULL,
  `gameTimeEastern` datetime DEFAULT NULL,
  `homeID` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `homeScore` int(11) DEFAULT NULL,
  `visitorID` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `visitorScore` int(11) DEFAULT NULL,
  `overtime` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`gameID`),
  KEY `GameID` (`gameID`),
  KEY `HomeID` (`homeID`),
  KEY `VisitorID` (`visitorID`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=257 ;

--
-- Dumping data for table `nflp_schedule`
--

INSERT INTO `nflp_schedule` (`gameID`, `weekNum`, `gameTimeEastern`, `homeID`, `homeScore`, `visitorID`, `visitorScore`, `overtime`) VALUES
(1, 1, '2018-09-06 20:20:00', 'PHI', NULL, 'ATL', NULL, 0),
(2, 1, '2018-09-09 13:00:00', 'BAL', NULL, 'BUF', NULL, 0),
(3, 1, '2018-09-09 13:00:00', 'CLE', NULL, 'PIT', NULL, 0),
(4, 1, '2018-09-09 13:00:00', 'IND', NULL, 'CIN', NULL, 0),
(5, 1, '2018-09-09 13:00:00', 'MIA', NULL, 'TEN', NULL, 0),
(6, 1, '2018-09-09 13:00:00', 'MIN', NULL, 'SF', NULL, 0),
(7, 1, '2018-09-09 13:00:00', 'NE', NULL, 'HOU', NULL, 0),
(8, 1, '2018-09-09 13:00:00', 'NO', NULL, 'TB', NULL, 0),
(9, 1, '2018-09-09 13:00:00', 'NYG', NULL, 'JAX', NULL, 0),
(10, 1, '2018-09-09 16:05:00', 'LAC', NULL, 'KC', NULL, 0),
(11, 1, '2018-09-09 16:25:00', 'ARI', NULL, 'WAS', NULL, 0),
(12, 1, '2018-09-09 16:25:00', 'CAR', NULL, 'DAL', NULL, 0),
(13, 1, '2018-09-09 16:25:00', 'DEN', NULL, 'SEA', NULL, 0),
(14, 1, '2018-09-09 20:20:00', 'GB', NULL, 'CHI', NULL, 0),
(15, 1, '2018-09-10 19:10:00', 'DET', NULL, 'NYJ', NULL, 0),
(16, 1, '2018-09-10 22:20:00', 'OAK', NULL, 'LAR', NULL, 0),
(17, 2, '2018-09-13 20:20:00', 'CIN', NULL, 'BAL', NULL, 0),
(18, 2, '2018-09-16 13:00:00', 'ATL', NULL, 'CAR', NULL, 0),
(19, 2, '2018-09-16 13:00:00', 'BUF', NULL, 'LAC', NULL, 0),
(20, 2, '2018-09-16 13:00:00', 'GB', NULL, 'MIN', NULL, 0),
(21, 2, '2018-09-16 13:00:00', 'NO', NULL, 'CLE', NULL, 0),
(22, 2, '2018-09-16 13:00:00', 'NYJ', NULL, 'MIA', NULL, 0),
(23, 2, '2018-09-16 13:00:00', 'PIT', NULL, 'KC', NULL, 0),
(24, 2, '2018-09-16 13:00:00', 'TB', NULL, 'PHI', NULL, 0),
(25, 2, '2018-09-16 13:00:00', 'TEN', NULL, 'HOU', NULL, 0),
(26, 2, '2018-09-16 13:00:00', 'WAS', NULL, 'IND', NULL, 0),
(27, 2, '2018-09-16 16:05:00', 'LAR', NULL, 'ARI', NULL, 0),
(28, 2, '2018-09-16 16:05:00', 'SF', NULL, 'DET', NULL, 0),
(29, 2, '2018-09-16 16:25:00', 'DEN', NULL, 'OAK', NULL, 0),
(30, 2, '2018-09-16 16:25:00', 'JAX', NULL, 'NE', NULL, 0),
(31, 2, '2018-09-16 20:20:00', 'DAL', NULL, 'NYG', NULL, 0),
(32, 2, '2018-09-17 20:15:00', 'CHI', NULL, 'SEA', NULL, 0),
(33, 3, '2018-09-20 20:20:00', 'CLE', NULL, 'NYJ', NULL, 0),
(34, 3, '2018-09-23 13:00:00', 'ATL', NULL, 'NO', NULL, 0),
(35, 3, '2018-09-23 13:00:00', 'BAL', NULL, 'DEN', NULL, 0),
(36, 3, '2018-09-23 13:00:00', 'CAR', NULL, 'CIN', NULL, 0),
(37, 3, '2018-09-23 13:00:00', 'HOU', NULL, 'NYG', NULL, 0),
(38, 3, '2018-09-23 13:00:00', 'JAX', NULL, 'TEN', NULL, 0),
(39, 3, '2018-09-23 13:00:00', 'KC', NULL, 'SF', NULL, 0),
(40, 3, '2018-09-23 13:00:00', 'MIA', NULL, 'OAK', NULL, 0),
(41, 3, '2018-09-23 13:00:00', 'MIN', NULL, 'BUF', NULL, 0),
(42, 3, '2018-09-23 13:00:00', 'PHI', NULL, 'IND', NULL, 0),
(43, 3, '2018-09-23 13:00:00', 'WAS', NULL, 'GB', NULL, 0),
(44, 3, '2018-09-23 16:05:00', 'LAR', NULL, 'LAC', NULL, 0),
(45, 3, '2018-09-23 16:25:00', 'ARI', NULL, 'CHI', NULL, 0),
(46, 3, '2018-09-23 16:25:00', 'SEA', NULL, 'DAL', NULL, 0),
(47, 3, '2018-09-23 20:20:00', 'DET', NULL, 'NE', NULL, 0),
(48, 3, '2018-09-24 20:15:00', 'TB', NULL, 'PIT', NULL, 0),
(49, 4, '2018-09-27 20:20:00', 'LAR', NULL, 'MIN', NULL, 0),
(50, 4, '2018-09-30 13:00:00', 'JAX', NULL, 'NYJ', NULL, 0),
(51, 4, '2018-09-30 13:00:00', 'NE', NULL, 'MIA', NULL, 0),
(52, 4, '2018-09-30 13:00:00', 'TEN', NULL, 'PHI', NULL, 0),
(53, 4, '2018-09-30 13:00:00', 'ATL', NULL, 'CIN', NULL, 0),
(54, 4, '2018-09-30 13:00:00', 'CHI', NULL, 'TB', NULL, 0),
(55, 4, '2018-09-30 13:00:00', 'DAL', NULL, 'DET', NULL, 0),
(56, 4, '2018-09-30 13:00:00', 'GB', NULL, 'BUF', NULL, 0),
(57, 4, '2018-09-30 13:00:00', 'IND', NULL, 'HOU', NULL, 0),
(58, 4, '2018-09-30 16:05:00', 'ARI', NULL, 'SEA', NULL, 0),
(59, 4, '2018-09-30 16:05:00', 'OAK', NULL, 'CLE', NULL, 0),
(60, 4, '2018-09-30 16:25:00', 'LAC', NULL, 'SF', NULL, 0),
(61, 4, '2018-09-30 16:25:00', 'NYG', NULL, 'NO', NULL, 0),
(62, 4, '2018-09-30 20:20:00', 'PIT', NULL, 'BAL', NULL, 0),
(63, 4, '2018-10-01 20:15:00', 'DEN', NULL, 'KC', NULL, 0),
(64, 5, '2018-10-04 20:20:00', 'NE', NULL, 'IND', NULL, 0),
(65, 5, '2018-10-07 13:00:00', 'BUF', NULL, 'TEN', NULL, 0),
(66, 5, '2018-10-07 13:00:00', 'CAR', NULL, 'NYG', NULL, 0),
(67, 5, '2018-10-07 13:00:00', 'CIN', NULL, 'MIA', NULL, 0),
(68, 5, '2018-10-07 13:00:00', 'CLE', NULL, 'BAL', NULL, 0),
(69, 5, '2018-10-07 13:00:00', 'DET', NULL, 'GB', NULL, 0),
(70, 5, '2018-10-07 13:00:00', 'KC', NULL, 'JAX', NULL, 0),
(71, 5, '2018-10-07 13:00:00', 'NYJ', NULL, 'DEN', NULL, 0),
(72, 5, '2018-10-07 13:00:00', 'PIT', NULL, 'ATL', NULL, 0),
(73, 5, '2018-10-07 16:05:00', 'LAC', NULL, 'OAK', NULL, 0),
(74, 5, '2018-10-07 16:25:00', 'PHI', NULL, 'MIN', NULL, 0),
(75, 5, '2018-10-07 16:25:00', 'SF', NULL, 'ARI', NULL, 0),
(76, 5, '2018-10-07 16:25:00', 'SEA', NULL, 'LAR', NULL, 0),
(77, 5, '2018-10-07 20:20:00', 'HOU', NULL, 'DAL', NULL, 0),
(78, 5, '2018-10-08 20:15:00', 'NO', NULL, 'WAS', NULL, 0),
(79, 6, '2018-10-11 20:20:00', 'NYG', NULL, 'PHI', NULL, 0),
(80, 6, '2018-10-14 13:00:00', 'ATL', NULL, 'TB', NULL, 0),
(81, 6, '2018-10-14 13:00:00', 'CIN', NULL, 'PIT', NULL, 0),
(82, 6, '2018-10-14 13:00:00', 'CLE', NULL, 'LAC', NULL, 0),
(83, 6, '2018-10-14 13:00:00', 'HOU', NULL, 'BUF', NULL, 0),
(84, 6, '2018-10-14 13:00:00', 'MIA', NULL, 'CHI', NULL, 0),
(85, 6, '2018-10-14 13:00:00', 'MIN', NULL, 'ARI', NULL, 0),
(86, 6, '2018-10-14 13:00:00', 'NYJ', NULL, 'IND', NULL, 0),
(87, 6, '2018-10-14 13:00:00', 'OAK', NULL, 'SEA', NULL, 0),
(88, 6, '2018-10-14 13:00:00', 'WAS', NULL, 'CAR', NULL, 0),
(89, 6, '2018-10-14 16:05:00', 'DEN', NULL, 'LAR', NULL, 0),
(90, 6, '2018-10-14 16:25:00', 'DAL', NULL, 'JAX', NULL, 0),
(91, 6, '2018-10-14 16:25:00', 'TEN', NULL, 'BAL', NULL, 0),
(92, 6, '2018-10-14 20:20:00', 'NE', NULL, 'KC', NULL, 0),
(93, 6, '2018-10-15 20:15:00', 'GB', NULL, 'SF', NULL, 0),
(94, 7, '2018-10-18 20:20:00', 'ARI', NULL, 'DEN', NULL, 0),
(95, 7, '2018-10-21 21:30:00', 'LAC', NULL, 'TEN', NULL, 0),
(96, 7, '2018-10-21 13:00:00', 'CHI', NULL, 'NE', NULL, 0),
(97, 7, '2018-10-21 13:00:00', 'IND', NULL, 'BUF', NULL, 0),
(98, 7, '2018-10-21 13:00:00', 'JAX', NULL, 'HOU', NULL, 0),
(99, 7, '2018-10-21 13:00:00', 'KC', NULL, 'CIN', NULL, 0),
(100, 7, '2018-10-21 13:00:00', 'MIA', NULL, 'DET', NULL, 0),
(101, 7, '2018-10-21 13:00:00', 'NYJ', NULL, 'MIN', NULL, 0),
(102, 7, '2018-10-21 13:00:00', 'PHI', NULL, 'CAR', NULL, 0),
(103, 7, '2018-10-21 13:00:00', 'TB', NULL, 'CLE', NULL, 0),
(104, 7, '2018-10-21 16:05:00', 'BAL', NULL, 'NO', NULL, 0),
(105, 7, '2018-10-21 16:25:00', 'WAS', NULL, 'DAL', NULL, 0),
(106, 7, '2018-10-21 20:20:00', 'SF', NULL, 'LAR', NULL, 0),
(107, 7, '2018-10-22 20:15:00', 'ATL', NULL, 'NYG', NULL, 0),
(108, 8, '2018-10-25 20:20:00', 'HOU', NULL, 'MIA', NULL, 0),
(109, 8, '2018-10-28 21:30:00', 'JAX', NULL, 'PHI', NULL, 0),
(110, 8, '2018-10-28 13:00:00', 'CAR', NULL, 'BAL', NULL, 0),
(111, 8, '2018-10-28 13:00:00', 'CHI', NULL, 'NYJ', NULL, 0),
(112, 8, '2018-10-28 13:00:00', 'CIN', NULL, 'TB', NULL, 0),
(113, 8, '2018-10-28 13:00:00', 'DET', NULL, 'SEA', NULL, 0),
(114, 8, '2018-10-28 13:00:00', 'KC', NULL, 'DEN', NULL, 0),
(115, 8, '2018-10-28 13:00:00', 'NYG', NULL, 'WAS', NULL, 0),
(116, 8, '2018-10-28 13:00:00', 'PIT', NULL, 'CLE', NULL, 0),
(117, 8, '2018-10-28 16:05:00', 'OAK', NULL, 'IND', NULL, 0),
(118, 8, '2018-10-28 16:25:00', 'ARI', NULL, 'SF', NULL, 0),
(119, 8, '2018-10-28 16:25:00', 'LAR', NULL, 'GB', NULL, 0),
(120, 8, '2018-10-28 20:20:00', 'MIN', NULL, 'NO', NULL, 0),
(121, 8, '2018-10-29 20:15:00', 'BUF', NULL, 'NE', NULL, 0),
(122, 9, '2018-11-01 20:20:00', 'SF', NULL, 'OAK', NULL, 0),
(123, 9, '2018-11-04 13:00:00', 'BUF', NULL, 'CHI', NULL, 0),
(124, 9, '2018-11-04 13:00:00', 'CAR', NULL, 'TB', NULL, 0),
(125, 9, '2018-11-04 13:00:00', 'CLE', NULL, 'KC', NULL, 0),
(126, 9, '2018-11-04 13:00:00', 'MIA', NULL, 'NYJ', NULL, 0),
(127, 9, '2018-11-04 13:00:00', 'BAL', NULL, 'PIT', NULL, 0),
(128, 9, '2018-11-04 13:00:00', 'MIN', NULL, 'DET', NULL, 0),
(129, 9, '2018-11-04 13:00:00', 'WAS', NULL, 'ATL', NULL, 0),
(130, 9, '2018-11-04 16:05:00', 'DEN', NULL, 'HOU', NULL, 0),
(131, 9, '2018-11-04 16:05:00', 'SEA', NULL, 'LAC', NULL, 0),
(132, 9, '2018-11-04 16:25:00', 'NO', NULL, 'LAR', NULL, 0),
(133, 9, '2018-11-04 20:20:00', 'NE', NULL, 'GB', NULL, 0),
(134, 9, '2018-11-05 20:15:00', 'DAL', NULL, 'TEN', NULL, 0),
(135, 10, '2018-11-08 20:20:00', 'PIT', NULL, 'CAR', NULL, 0),
(136, 10, '2018-11-11 13:00:00', 'CIN', NULL, 'NO', NULL, 0),
(137, 10, '2018-11-11 13:00:00', 'CLE', NULL, 'ATL', NULL, 0),
(138, 10, '2018-11-11 13:00:00', 'GB', NULL, 'MIA', NULL, 0),
(139, 10, '2018-11-11 13:00:00', 'IND', NULL, 'JAX', NULL, 0),
(140, 10, '2018-11-11 13:00:00', 'CHI', NULL, 'DET', NULL, 0),
(141, 10, '2018-11-11 13:00:00', 'KC', NULL, 'ARI', NULL, 0),
(142, 10, '2018-11-11 13:00:00', 'NYJ', NULL, 'BUF', NULL, 0),
(143, 10, '2018-11-11 13:00:00', 'TB', NULL, 'WAS', NULL, 0),
(144, 10, '2018-11-11 13:00:00', 'TEN', NULL, 'NE', NULL, 0),
(145, 10, '2018-11-11 16:05:00', 'OAK', NULL, 'LAC', NULL, 0),
(146, 10, '2018-11-11 16:25:00', 'LAR', NULL, 'SEA', NULL, 0),
(147, 10, '2018-11-11 20:20:00', 'PHI', NULL, 'DAL', NULL, 0),
(148, 10, '2018-11-12 20:15:00', 'SF', NULL, 'NYG', NULL, 0),
(149, 11, '2018-11-15 20:20:00', 'SEA', NULL, 'GB', NULL, 0),
(150, 11, '2018-11-18 13:00:00', 'BAL', NULL, 'CIN', NULL, 0),
(151, 11, '2018-11-18 13:00:00', 'CHI', NULL, 'MIN', NULL, 0),
(152, 11, '2018-11-18 13:00:00', 'DET', NULL, 'CAR', NULL, 0),
(153, 11, '2018-11-18 13:00:00', 'IND', NULL, 'TEN', NULL, 0),
(154, 11, '2018-11-18 13:00:00', 'ATL', NULL, 'DAL', NULL, 0),
(155, 11, '2018-11-18 13:00:00', 'NYG', NULL, 'TB', NULL, 0),
(156, 11, '2018-11-18 13:00:00', 'WAS', NULL, 'HOU', NULL, 0),
(157, 11, '2018-11-18 13:00:00', 'NO', NULL, 'PHI', NULL, 0),
(158, 11, '2018-11-18 16:05:00', 'ARI', NULL, 'OAK', NULL, 0),
(159, 11, '2018-11-18 16:05:00', 'LAC', NULL, 'DEN', NULL, 0),
(160, 11, '2018-11-18 20:20:00', 'JAX', NULL, 'PIT', NULL, 0),
(161, 11, '2018-11-19 20:15:00', 'LAR', NULL, 'KC', NULL, 0),
(162, 12, '2018-11-22 12:30:00', 'DET', NULL, 'CHI', NULL, 0),
(163, 12, '2018-11-22 16:30:00', 'DAL', NULL, 'WAS', NULL, 0),
(164, 12, '2018-11-22 20:20:00', 'NO', NULL, 'ATL', NULL, 0),
(165, 12, '2018-11-25 13:00:00', 'CIN', NULL, 'CLE', NULL, 0),
(166, 12, '2018-11-25 13:00:00', 'CAR', NULL, 'SEA', NULL, 0),
(167, 12, '2018-11-25 13:00:00', 'BUF', NULL, 'JAX', NULL, 0),
(168, 12, '2018-11-25 13:00:00', 'BAL', NULL, 'OAK', NULL, 0),
(169, 12, '2018-11-25 13:00:00', 'IND', NULL, 'MIA', NULL, 0),
(170, 12, '2018-11-25 13:00:00', 'NYJ', NULL, 'NE', NULL, 0),
(171, 12, '2018-11-25 13:00:00', 'PHI', NULL, 'NYG', NULL, 0),
(172, 12, '2018-11-25 13:00:00', 'TB', NULL, 'SF', NULL, 0),
(173, 12, '2018-11-25 16:05:00', 'LAC', NULL, 'ARI', NULL, 0),
(174, 12, '2018-11-25 16:25:00', 'DEN', NULL, 'PIT', NULL, 0),
(175, 12, '2018-11-25 20:20:00', 'MIN', NULL, 'GB', NULL, 0),
(176, 12, '2018-11-26 20:15:00', 'HOU', NULL, 'TEN', NULL, 0),
(177, 13, '2018-11-29 20:20:00', 'DAL', NULL, 'NO', NULL, 0),
(178, 13, '2018-12-02 13:00:00', 'ATL', NULL, 'BAL', NULL, 0),
(179, 13, '2018-12-02 13:00:00', 'CIN', NULL, 'DEN', NULL, 0),
(180, 13, '2018-12-02 13:00:00', 'DET', NULL, 'LAR', NULL, 0),
(181, 13, '2018-12-02 13:00:00', 'GB', NULL, 'ARI', NULL, 0),
(182, 13, '2018-12-02 13:00:00', 'HOU', NULL, 'CLE', NULL, 0),
(183, 13, '2018-12-02 13:00:00', 'JAX', NULL, 'IND', NULL, 0),
(184, 13, '2018-12-02 13:00:00', 'MIA', NULL, 'BUF', NULL, 0),
(185, 13, '2018-12-02 13:00:00', 'NYG', NULL, 'CHI', NULL, 0),
(186, 13, '2018-12-02 13:00:00', 'PIT', NULL, 'LAC', NULL, 0),
(187, 13, '2018-12-02 13:00:00', 'TB', NULL, 'CAR', NULL, 0),
(188, 13, '2018-12-02 16:05:00', 'OAK', NULL, 'KC', NULL, 0),
(189, 13, '2018-12-02 16:05:00', 'TEN', NULL, 'NYJ', NULL, 0),
(190, 13, '2018-12-02 16:25:00', 'NE', NULL, 'MIN', NULL, 0),
(191, 13, '2018-12-02 20:20:00', 'SEA', NULL, 'SF', NULL, 0),
(192, 13, '2018-12-03 20:15:00', 'PHI', NULL, 'WAS', NULL, 0),
(193, 14, '2018-12-06 20:20:00', 'TEN', NULL, 'JAX', NULL, 0),
(194, 14, '2018-12-09 13:00:00', 'BUF', NULL, 'NYJ', NULL, 0),
(195, 14, '2018-12-09 13:00:00', 'CHI', NULL, 'LAR', NULL, 0),
(196, 14, '2018-12-09 13:00:00', 'CLE', NULL, 'CAR', NULL, 0),
(197, 14, '2018-12-09 13:00:00', 'GB', NULL, 'ATL', NULL, 0),
(198, 14, '2018-12-09 13:00:00', 'HOU', NULL, 'IND', NULL, 0),
(199, 14, '2018-12-09 13:00:00', 'KC', NULL, 'BAL', NULL, 0),
(200, 14, '2018-12-09 13:00:00', 'MIA', NULL, 'NE', NULL, 0),
(201, 14, '2018-12-09 13:00:00', 'TB', NULL, 'NO', NULL, 0),
(202, 14, '2018-12-09 13:00:00', 'WAS', NULL, 'NYG', NULL, 0),
(203, 14, '2018-12-09 16:05:00', 'LAC', NULL, 'CIN', NULL, 0),
(204, 14, '2018-12-09 16:05:00', 'SF', NULL, 'DEN', NULL, 0),
(205, 14, '2018-12-09 16:25:00', 'ARI', NULL, 'DET', NULL, 0),
(206, 14, '2018-12-09 16:25:00', 'DAL', NULL, 'PHI', NULL, 0),
(207, 14, '2018-12-09 20:20:00', 'OAK', NULL, 'PIT', NULL, 0),
(208, 14, '2018-12-10 20:15:00', 'SEA', NULL, 'MIN', NULL, 0),
(209, 15, '2018-12-13 20:20:00', 'KC', NULL, 'LAC', NULL, 0),
(210, 15, '2018-12-15 16:30:00', 'DEN', NULL, 'CLE', NULL, 0),
(211, 15, '2018-12-15 16:30:00', 'NYJ', NULL, 'HOU', NULL, 0),
(212, 15, '2018-12-16 13:00:00', 'CHI', NULL, 'GB', NULL, 0),
(213, 15, '2018-12-16 13:00:00', 'BUF', NULL, 'DET', NULL, 0),
(214, 15, '2018-12-16 13:00:00', 'BAL', NULL, 'TB', NULL, 0),
(215, 15, '2018-12-16 13:00:00', 'ATL', NULL, 'ARI', NULL, 0),
(216, 15, '2018-12-16 13:00:00', 'CIN', NULL, 'OAK', NULL, 0),
(217, 15, '2018-12-16 13:00:00', 'IND', NULL, 'DAL', NULL, 0),
(218, 15, '2018-12-16 13:00:00', 'JAX', NULL, 'WAS', NULL, 0),
(219, 15, '2018-12-16 13:00:00', 'MIN', NULL, 'MIA', NULL, 0),
(220, 15, '2018-12-16 13:00:00', 'NYG', NULL, 'TEN', NULL, 0),
(221, 15, '2018-12-16 16:05:00', 'SF', NULL, 'SEA', NULL, 0),
(222, 15, '2018-12-16 16:25:00', 'PIT', NULL, 'NE', NULL, 0),
(223, 15, '2018-12-16 20:20:00', 'LAR', NULL, 'PHI', NULL, 0),
(224, 15, '2018-12-17 20:15:00', 'CAR', NULL, 'NO', NULL, 0),
(225, 16, '2018-12-23 13:00:00', 'IND', NULL, 'NYG', NULL, 0),
(226, 16, '2018-12-23 13:00:00', 'MIA', NULL, 'JAX', NULL, 0),
(227, 16, '2018-12-23 13:00:00', 'TEN', NULL, 'WAS', NULL, 0),
(228, 16, '2018-12-23 13:00:00', 'CAR', NULL, 'ATL', NULL, 0),
(229, 16, '2018-12-23 13:00:00', 'CLE', NULL, 'CIN', NULL, 0),
(230, 16, '2018-12-23 13:00:00', 'DAL', NULL, 'TB', NULL, 0),
(231, 16, '2018-12-23 13:00:00', 'DET', NULL, 'MIN', NULL, 0),
(232, 16, '2018-12-23 13:00:00', 'NE', NULL, 'BUF', NULL, 0),
(233, 16, '2018-12-23 13:00:00', 'NYJ', NULL, 'GB', NULL, 0),
(234, 16, '2018-12-23 13:00:00', 'PHI', NULL, 'HOU', NULL, 0),
(235, 16, '2018-12-23 16:05:00', 'LAC', NULL, 'BAL', NULL, 0),
(236, 16, '2018-12-23 16:05:00', 'ARI', NULL, 'LAR', NULL, 0),
(237, 16, '2018-12-23 16:05:00', 'SF', NULL, 'CHI', NULL, 0),
(238, 16, '2018-12-23 16:25:00', 'NO', NULL, 'PIT', NULL, 0),
(239, 16, '2018-12-23 20:20:00', 'SEA', NULL, 'KC', NULL, 0),
(240, 16, '2018-12-24 20:15:00', 'OAK', NULL, 'DEN', NULL, 0),
(241, 17, '2018-12-30 13:00:00', 'BAL', NULL, 'CLE', NULL, 0),
(242, 17, '2018-12-30 13:00:00', 'GB', NULL, 'DET', NULL, 0),
(243, 17, '2018-12-30 13:00:00', 'HOU', NULL, 'JAX', NULL, 0),
(244, 17, '2018-12-30 13:00:00', 'KC', NULL, 'OAK', NULL, 0),
(245, 17, '2018-12-30 13:00:00', 'MIN', NULL, 'CHI', NULL, 0),
(246, 17, '2018-12-30 13:00:00', 'NE', NULL, 'NYJ', NULL, 0),
(247, 17, '2018-12-30 13:00:00', 'NO', NULL, 'CAR', NULL, 0),
(248, 17, '2018-12-30 13:00:00', 'NYG', NULL, 'DAL', NULL, 0),
(249, 17, '2018-12-30 13:00:00', 'PIT', NULL, 'CIN', NULL, 0),
(250, 17, '2018-12-30 13:00:00', 'TB', NULL, 'ATL', NULL, 0),
(251, 17, '2018-12-30 13:00:00', 'TEN', NULL, 'IND', NULL, 0),
(252, 17, '2018-12-30 13:00:00', 'WAS', NULL, 'PHI', NULL, 0),
(253, 17, '2018-12-30 13:00:00', 'BUF', NULL, 'MIA', NULL, 0),
(254, 17, '2018-12-30 16:25:00', 'SEA', NULL, 'ARI', NULL, 0),
(255, 17, '2018-12-30 16:25:00', 'DEN', NULL, 'LAC', NULL, 0),
(256, 17, '2018-12-30 16:25:00', 'LAR', NULL, 'SF', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `nflp_teams`
--

DROP TABLE IF EXISTS `nflp_teams`;
CREATE TABLE `nflp_teams` (
  `teamID` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `divisionID` int(11) NOT NULL,
  `city` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `team` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  `displayName` varchar(50) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`teamID`),
  KEY `ID` (`teamID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `nflp_teams`
--

INSERT INTO `nflp_teams` (`teamID`, `divisionID`, `city`, `team`, `displayName`) VALUES
('ARI', 8, 'Arizona', 'Cardinals', NULL),
('ATL', 6, 'Atlanta', 'Falcons', NULL),
('BAL', 1, 'Baltimore', 'Ravens', NULL),
('BUF', 3, 'Buffalo', 'Bills', NULL),
('CAR', 6, 'Carolina', 'Panthers', NULL),
('CHI', 5, 'Chicago', 'Bears', NULL),
('CIN', 1, 'Cincinnati', 'Bengals', NULL),
('CLE', 1, 'Cleveland', 'Browns', NULL),
('DAL', 7, 'Dallas', 'Cowboys', NULL),
('DEN', 4, 'Denver', 'Broncos', NULL),
('DET', 5, 'Detroit', 'Lions', NULL),
('GB', 5, 'Green Bay', 'Packers', NULL),
('HOU', 2, 'Houston', 'Texans', NULL),
('IND', 2, 'Indianapolis', 'Colts', NULL),
('JAX', 2, 'Jacksonville', 'Jaguars', NULL),
('KC', 4, 'Kansas City', 'Chiefs', NULL),
('MIA', 3, 'Miami', 'Dolphins', NULL),
('MIN', 5, 'Minnesota', 'Vikings', NULL),
('NE', 3, 'New England', 'Patriots', NULL),
('NO', 6, 'New Orleans', 'Saints', NULL),
('NYG', 7, 'New York', 'Giants', 'NY Giants'),
('NYJ', 3, 'New York', 'Jets', 'NY Jets'),
('OAK', 4, 'Oakland', 'Raiders', NULL),
('PHI', 7, 'Philadelphia', 'Eagles', NULL),
('PIT', 1, 'Pittsburgh', 'Steelers', NULL),
('LAC', 4, 'Los Angeles', 'Chargers', NULL),
('SEA', 8, 'Seattle', 'Seahawks', NULL),
('SF', 8, 'San Francisco', '49ers', NULL),
('LAR', 8, 'Los Angeles', 'Rams', NULL),
('TB', 6, 'Tampa Bay', 'Buccaneers', NULL),
('TEN', 2, 'Tennessee', 'Titans', NULL),
('WAS', 7, 'Washington', 'Redskins', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `nflp_users`
--

DROP TABLE IF EXISTS `nflp_users`;
CREATE TABLE `nflp_users` (
  `userID` int(11) NOT NULL AUTO_INCREMENT,
  `userName` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `salt` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `firstname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `status` tinyint(1) DEFAULT '1',
  `is_admin` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`userID`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `nflp_users`
--

INSERT INTO `nflp_users` VALUES ('1', 'admin', 'jl7LZ1B7ZNUq/RnVqnFmuwRXvMkO/DD5', 'Cb8Jjj0OPy', 'Admin', 'Admin', 'admin@yourdomain.com', '1', '1');



SET FOREIGN_KEY_CHECKS = 1;
