SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

DROP TABLE IF EXISTS `nflp_schedule`;
CREATE TABLE IF NOT EXISTS `nflp_schedule` (
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
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC AUTO_INCREMENT=273;

INSERT INTO `nflp_schedule` (`gameID`, `weekNum`, `gameTimeEastern`, `homeID`, `homeScore`, `visitorID`, `visitorScore`, `overtime`) VALUES
(1, 1, '2022-09-09 00:20:00', 'LA', NULL,'BUF', NULL, 0),
(2, 1, '2022-09-11 17:00:00', 'ATL', NULL,'NO', NULL, 0),
(3, 1, '2022-09-11 17:00:00', 'CHI', NULL,'SF', NULL, 0),
(4, 1, '2022-09-11 17:00:00', 'CIN', NULL,'PIT', NULL, 0),
(5, 1, '2022-09-11 17:00:00', 'DET', NULL,'PHI', NULL, 0),
(6, 1, '2022-09-11 17:00:00', 'MIA', NULL,'NE', NULL, 0),
(7, 1, '2022-09-11 17:00:00', 'NYJ', NULL,'BAL', NULL, 0),
(8, 1, '2022-09-11 17:00:00', 'WAS', NULL,'JAX', NULL, 0),
(9, 1, '2022-09-11 17:00:00', 'CAR', NULL,'CLE', NULL, 0),
(10, 1, '2022-09-11 17:00:00', 'HOU', NULL,'IND', NULL, 0),
(11, 1, '2022-09-11 20:25:00', 'TEN', NULL,'NYG', NULL, 0),
(12, 1, '2022-09-11 20:25:00', 'MIN', NULL,'GB', NULL, 0),
(13, 1, '2022-09-11 20:25:00', 'ARI', NULL,'KC', NULL, 0),
(14, 1, '2022-09-11 20:25:00', 'LAC', NULL,'LV', NULL, 0),
(15, 1, '2022-09-12 00:20:00', 'DAL', NULL,'TB', NULL, 0),
(16, 1, '2022-09-13 00:15:00', 'SEA', NULL,'DEN', NULL, 0),
(17, 2, '2022-09-16 00:15:00', 'KC', NULL,'LAC', NULL, 0),
(18, 2, '2022-09-18 17:00:00', 'CLE', NULL,'NYJ', NULL, 0),
(19, 2, '2022-09-18 17:00:00', 'DET', NULL,'WAS', NULL, 0),
(20, 2, '2022-09-18 17:00:00', 'NO', NULL,'TB', NULL, 0),
(21, 2, '2022-09-18 17:00:00', 'NYG', NULL,'CAR', NULL, 0),
(22, 2, '2022-09-18 17:00:00', 'PIT', NULL,'NE', NULL, 0),
(23, 2, '2022-09-18 17:00:00', 'JAX', NULL,'IND', NULL, 0),
(24, 2, '2022-09-18 17:00:00', 'BAL', NULL,'MIA', NULL, 0),
(25, 2, '2022-09-18 20:05:00', 'LA', NULL,'ATL', NULL, 0),
(26, 2, '2022-09-18 20:05:00', 'SF', NULL,'SEA', NULL, 0),
(27, 2, '2022-09-18 20:25:00', 'DAL', NULL,'CIN', NULL, 0),
(28, 2, '2022-09-18 20:25:00', 'DEN', NULL,'HOU', NULL, 0),
(29, 2, '2022-09-18 20:25:00', 'LV', NULL,'ARI', NULL, 0),
(30, 2, '2022-09-19 00:20:00', 'GB', NULL,'CHI', NULL, 0),
(31, 2, '2022-09-19 23:15:00', 'BUF', NULL,'TEN', NULL, 0),
(32, 2, '2022-09-20 00:30:00', 'PHI', NULL,'MIN', NULL, 0),
(33, 3, '2022-09-23 00:15:00', 'CLE', NULL,'PIT', NULL, 0),
(34, 3, '2022-09-25 17:00:00', 'CHI', NULL,'HOU', NULL, 0),
(35, 3, '2022-09-25 17:00:00', 'TEN', NULL,'LV', NULL, 0),
(36, 3, '2022-09-25 17:00:00', 'IND', NULL,'KC', NULL, 0),
(37, 3, '2022-09-25 17:00:00', 'MIA', NULL,'BUF', NULL, 0),
(38, 3, '2022-09-25 17:00:00', 'MIN', NULL,'DET', NULL, 0),
(39, 3, '2022-09-25 17:00:00', 'NE', NULL,'BAL', NULL, 0),
(40, 3, '2022-09-25 17:00:00', 'NYJ', NULL,'CIN', NULL, 0),
(41, 3, '2022-09-25 17:00:00', 'WAS', NULL,'PHI', NULL, 0),
(42, 3, '2022-09-25 17:00:00', 'CAR', NULL,'NO', NULL, 0),
(43, 3, '2022-09-25 20:05:00', 'LAC', NULL,'JAX', NULL, 0),
(44, 3, '2022-09-25 20:25:00', 'ARI', NULL,'LA', NULL, 0),
(45, 3, '2022-09-25 20:25:00', 'SEA', NULL,'ATL', NULL, 0),
(46, 3, '2022-09-25 20:25:00', 'TB', NULL,'GB', NULL, 0),
(47, 3, '2022-09-26 00:20:00', 'DEN', NULL,'SF', NULL, 0),
(48, 3, '2022-09-27 00:15:00', 'NYG', NULL,'DAL', NULL, 0),
(49, 4, '2022-09-30 00:15:00', 'CIN', NULL,'MIA', NULL, 0),
(50, 4, '2022-10-02 13:30:00', 'NO', NULL,'MIN', NULL, 0),
(51, 4, '2022-10-02 17:00:00', 'ATL', NULL,'CLE', NULL, 0),
(52, 4, '2022-10-02 17:00:00', 'DAL', NULL,'WAS', NULL, 0),
(53, 4, '2022-10-02 17:00:00', 'DET', NULL,'SEA', NULL, 0),
(54, 4, '2022-10-02 17:00:00', 'IND', NULL,'TEN', NULL, 0),
(55, 4, '2022-10-02 17:00:00', 'NYG', NULL,'CHI', NULL, 0),
(56, 4, '2022-10-02 17:00:00', 'PHI', NULL,'JAX', NULL, 0),
(57, 4, '2022-10-02 17:00:00', 'PIT', NULL,'NYJ', NULL, 0),
(58, 4, '2022-10-02 17:00:00', 'BAL', NULL,'BUF', NULL, 0),
(59, 4, '2022-10-02 17:00:00', 'HOU', NULL,'LAC', NULL, 0),
(60, 4, '2022-10-02 20:05:00', 'CAR', NULL,'ARI', NULL, 0),
(61, 4, '2022-10-02 20:25:00', 'GB', NULL,'NE', NULL, 0),
(62, 4, '2022-10-02 20:25:00', 'LV', NULL,'DEN', NULL, 0),
(63, 4, '2022-10-03 00:20:00', 'TB', NULL,'KC', NULL, 0),
(64, 4, '2022-10-04 00:15:00', 'SF', NULL,'LA', NULL, 0),
(65, 5, '2022-10-07 00:15:00', 'DEN', NULL,'IND', NULL, 0),
(66, 5, '2022-10-09 13:30:00', 'GB', NULL,'NYG', NULL, 0),
(67, 5, '2022-10-09 17:00:00', 'BUF', NULL,'PIT', NULL, 0),
(68, 5, '2022-10-09 17:00:00', 'CLE', NULL,'LAC', NULL, 0),
(69, 5, '2022-10-09 17:00:00', 'MIN', NULL,'CHI', NULL, 0),
(70, 5, '2022-10-09 17:00:00', 'NE', NULL,'DET', NULL, 0),
(71, 5, '2022-10-09 17:00:00', 'NO', NULL,'SEA', NULL, 0),
(72, 5, '2022-10-09 17:00:00', 'NYJ', NULL,'MIA', NULL, 0),
(73, 5, '2022-10-09 17:00:00', 'TB', NULL,'ATL', NULL, 0),
(74, 5, '2022-10-09 17:00:00', 'WAS', NULL,'TEN', NULL, 0),
(75, 5, '2022-10-09 17:00:00', 'JAX', NULL,'HOU', NULL, 0),
(76, 5, '2022-10-09 20:05:00', 'CAR', NULL,'SF', NULL, 0),
(77, 5, '2022-10-09 20:25:00', 'LA', NULL,'DAL', NULL, 0),
(78, 5, '2022-10-09 20:25:00', 'ARI', NULL,'PHI', NULL, 0),
(79, 5, '2022-10-10 00:20:00', 'BAL', NULL,'CIN', NULL, 0),
(80, 5, '2022-10-11 00:15:00', 'KC', NULL,'LV', NULL, 0),
(81, 6, '2022-10-14 00:15:00', 'CHI', NULL,'WAS', NULL, 0),
(82, 6, '2022-10-16 17:00:00', 'ATL', NULL,'SF', NULL, 0),
(83, 6, '2022-10-16 17:00:00', 'CLE', NULL,'NE', NULL, 0),
(84, 6, '2022-10-16 17:00:00', 'GB', NULL,'NYJ', NULL, 0),
(85, 6, '2022-10-16 17:00:00', 'IND', NULL,'JAX', NULL, 0),
(86, 6, '2022-10-16 17:00:00', 'MIA', NULL,'MIN', NULL, 0),
(87, 6, '2022-10-16 17:00:00', 'NO', NULL,'CIN', NULL, 0),
(88, 6, '2022-10-16 17:00:00', 'NYG', NULL,'BAL', NULL, 0),
(89, 6, '2022-10-16 17:00:00', 'PIT', NULL,'TB', NULL, 0),
(90, 6, '2022-10-16 20:05:00', 'LA', NULL,'CAR', NULL, 0),
(91, 6, '2022-10-16 20:05:00', 'SEA', NULL,'ARI', NULL, 0),
(92, 6, '2022-10-16 20:25:00', 'KC', NULL,'BUF', NULL, 0),
(93, 6, '2022-10-17 00:20:00', 'PHI', NULL,'DAL', NULL, 0),
(94, 6, '2022-10-18 00:15:00', 'LAC', NULL,'DEN', NULL, 0),
(95, 7, '2022-10-21 00:15:00', 'ARI', NULL,'NO', NULL, 0),
(96, 7, '2022-10-23 17:00:00', 'CIN', NULL,'ATL', NULL, 0),
(97, 7, '2022-10-23 17:00:00', 'DAL', NULL,'DET', NULL, 0),
(98, 7, '2022-10-23 17:00:00', 'TEN', NULL,'IND', NULL, 0),
(99, 7, '2022-10-23 17:00:00', 'WAS', NULL,'GB', NULL, 0),
(100, 7, '2022-10-23 17:00:00', 'CAR', NULL,'TB', NULL, 0),
(101, 7, '2022-10-23 17:00:00', 'JAX', NULL,'NYG', NULL, 0),
(102, 7, '2022-10-23 17:00:00', 'BAL', NULL,'CLE', NULL, 0),
(103, 7, '2022-10-23 20:05:00', 'DEN', NULL,'NYJ', NULL, 0),
(104, 7, '2022-10-23 20:05:00', 'LV', NULL,'HOU', NULL, 0),
(105, 7, '2022-10-23 20:25:00', 'LAC', NULL,'SEA', NULL, 0),
(106, 7, '2022-10-23 20:25:00', 'SF', NULL,'KC', NULL, 0),
(107, 7, '2022-10-24 00:20:00', 'MIA', NULL,'PIT', NULL, 0),
(108, 7, '2022-10-25 00:15:00', 'NE', NULL,'CHI', NULL, 0),
(109, 8, '2022-10-28 00:15:00', 'TB', NULL,'BAL', NULL, 0),
(110, 8, '2022-10-30 13:30:00', 'JAX', NULL,'DEN', NULL, 0),
(111, 8, '2022-10-30 17:00:00', 'ATL', NULL,'CAR', NULL, 0),
(112, 8, '2022-10-30 17:00:00', 'DAL', NULL,'CHI', NULL, 0),
(113, 8, '2022-10-30 17:00:00', 'DET', NULL,'MIA', NULL, 0),
(114, 8, '2022-10-30 17:00:00', 'MIN', NULL,'ARI', NULL, 0),
(115, 8, '2022-10-30 17:00:00', 'NO', NULL,'LV', NULL, 0),
(116, 8, '2022-10-30 17:00:00', 'NYJ', NULL,'NE', NULL, 0),
(117, 8, '2022-10-30 17:00:00', 'PHI', NULL,'PIT', NULL, 0),
(118, 8, '2022-10-30 20:05:00', 'HOU', NULL,'TEN', NULL, 0),
(119, 8, '2022-10-30 20:25:00', 'IND', NULL,'WAS', NULL, 0),
(120, 8, '2022-10-30 20:25:00', 'LA', NULL,'SF', NULL, 0),
(121, 8, '2022-10-30 20:25:00', 'SEA', NULL,'NYG', NULL, 0),
(122, 8, '2022-10-31 00:20:00', 'BUF', NULL,'GB', NULL, 0),
(123, 8, '2022-11-01 00:15:00', 'CLE', NULL,'CIN', NULL, 0),
(124, 9, '2022-11-04 00:15:00', 'HOU', NULL,'PHI', NULL, 0),
(125, 9, '2022-11-06 18:00:00', 'ATL', NULL,'LAC', NULL, 0),
(126, 9, '2022-11-06 18:00:00', 'CHI', NULL,'MIA', NULL, 0),
(127, 9, '2022-11-06 18:00:00', 'CIN', NULL,'CAR', NULL, 0),
(128, 9, '2022-11-06 18:00:00', 'DET', NULL,'GB', NULL, 0),
(129, 9, '2022-11-06 18:00:00', 'NE', NULL,'IND', NULL, 0),
(130, 9, '2022-11-06 18:00:00', 'NYJ', NULL,'BUF', NULL, 0),
(131, 9, '2022-11-06 18:00:00', 'WAS', NULL,'MIN', NULL, 0),
(132, 9, '2022-11-06 18:00:00', 'JAX', NULL,'LV', NULL, 0),
(133, 9, '2022-11-06 21:05:00', 'ARI', NULL,'SEA', NULL, 0),
(134, 9, '2022-11-06 21:25:00', 'TB', NULL,'LA', NULL, 0),
(135, 9, '2022-11-07 01:20:00', 'KC', NULL,'TEN', NULL, 0),
(136, 9, '2022-11-08 01:15:00', 'NO', NULL,'BAL', NULL, 0),
(137, 10, '2022-11-11 01:15:00', 'CAR', NULL,'ATL', NULL, 0),
(138, 10, '2022-11-13 14:30:00', 'TB', NULL,'SEA', NULL, 0),
(139, 10, '2022-11-13 18:00:00', 'BUF', NULL,'MIN', NULL, 0),
(140, 10, '2022-11-13 18:00:00', 'CHI', NULL,'DET', NULL, 0),
(141, 10, '2022-11-13 18:00:00', 'TEN', NULL,'DEN', NULL, 0),
(142, 10, '2022-11-13 18:00:00', 'KC', NULL,'JAX', NULL, 0),
(143, 10, '2022-11-13 18:00:00', 'MIA', NULL,'CLE', NULL, 0),
(144, 10, '2022-11-13 18:00:00', 'NYG', NULL,'HOU', NULL, 0),
(145, 10, '2022-11-13 18:00:00', 'PIT', NULL,'NO', NULL, 0),
(146, 10, '2022-11-13 21:05:00', 'LV', NULL,'IND', NULL, 0),
(147, 10, '2022-11-13 21:25:00', 'GB', NULL,'DAL', NULL, 0),
(148, 10, '2022-11-13 21:25:00', 'LA', NULL,'ARI', NULL, 0),
(149, 10, '2022-11-14 01:20:00', 'SF', NULL,'LAC', NULL, 0),
(150, 10, '2022-11-15 01:15:00', 'PHI', NULL,'WAS', NULL, 0),
(151, 11, '2022-11-18 01:15:00', 'GB', NULL,'TEN', NULL, 0),
(152, 11, '2022-11-20 18:00:00', 'ATL', NULL,'CHI', NULL, 0),
(153, 11, '2022-11-20 18:00:00', 'BUF', NULL,'CLE', NULL, 0),
(154, 11, '2022-11-20 18:00:00', 'IND', NULL,'PHI', NULL, 0),
(155, 11, '2022-11-20 18:00:00', 'NE', NULL,'NYJ', NULL, 0),
(156, 11, '2022-11-20 18:00:00', 'NO', NULL,'LA', NULL, 0),
(157, 11, '2022-11-20 18:00:00', 'NYG', NULL,'DET', NULL, 0),
(158, 11, '2022-11-20 18:00:00', 'BAL', NULL,'CAR', NULL, 0),
(159, 11, '2022-11-20 18:00:00', 'HOU', NULL,'WAS', NULL, 0),
(160, 11, '2022-11-20 21:05:00', 'DEN', NULL,'LV', NULL, 0),
(161, 11, '2022-11-20 21:25:00', 'MIN', NULL,'DAL', NULL, 0),
(162, 11, '2022-11-20 21:25:00', 'PIT', NULL,'CIN', NULL, 0),
(163, 11, '2022-11-21 01:20:00', 'LAC', NULL,'KC', NULL, 0),
(164, 11, '2022-11-22 01:15:00', 'ARI', NULL,'SF', NULL, 0),
(165, 12, '2022-11-24 17:30:00', 'DET', NULL,'BUF', NULL, 0),
(166, 12, '2022-11-24 21:30:00', 'DAL', NULL,'NYG', NULL, 0),
(167, 12, '2022-11-25 01:20:00', 'MIN', NULL,'NE', NULL, 0),
(168, 12, '2022-11-27 18:00:00', 'CLE', NULL,'TB', NULL, 0),
(169, 12, '2022-11-27 18:00:00', 'TEN', NULL,'CIN', NULL, 0),
(170, 12, '2022-11-27 18:00:00', 'MIA', NULL,'HOU', NULL, 0),
(171, 12, '2022-11-27 18:00:00', 'NYJ', NULL,'CHI', NULL, 0),
(172, 12, '2022-11-27 18:00:00', 'WAS', NULL,'ATL', NULL, 0),
(173, 12, '2022-11-27 18:00:00', 'CAR', NULL,'DEN', NULL, 0),
(174, 12, '2022-11-27 18:00:00', 'JAX', NULL,'BAL', NULL, 0),
(175, 12, '2022-11-27 21:05:00', 'ARI', NULL,'LAC', NULL, 0),
(176, 12, '2022-11-27 21:05:00', 'SEA', NULL,'LV', NULL, 0),
(177, 12, '2022-11-27 21:25:00', 'KC', NULL,'LA', NULL, 0),
(178, 12, '2022-11-27 21:25:00', 'SF', NULL,'NO', NULL, 0),
(179, 12, '2022-11-28 01:20:00', 'PHI', NULL,'GB', NULL, 0),
(180, 12, '2022-11-29 01:15:00', 'IND', NULL,'PIT', NULL, 0),
(181, 13, '2022-12-02 01:15:00', 'NE', NULL,'BUF', NULL, 0),
(182, 13, '2022-12-04 18:00:00', 'ATL', NULL,'PIT', NULL, 0),
(183, 13, '2022-12-04 18:00:00', 'CHI', NULL,'GB', NULL, 0),
(184, 13, '2022-12-04 18:00:00', 'DET', NULL,'JAX', NULL, 0),
(185, 13, '2022-12-04 18:00:00', 'MIN', NULL,'NYJ', NULL, 0),
(186, 13, '2022-12-04 18:00:00', 'NYG', NULL,'WAS', NULL, 0),
(187, 13, '2022-12-04 18:00:00', 'PHI', NULL,'TEN', NULL, 0),
(188, 13, '2022-12-04 18:00:00', 'BAL', NULL,'DEN', NULL, 0),
(189, 13, '2022-12-04 18:00:00', 'HOU', NULL,'CLE', NULL, 0),
(190, 13, '2022-12-04 21:05:00', 'LA', NULL,'SEA', NULL, 0),
(191, 13, '2022-12-04 21:05:00', 'SF', NULL,'MIA', NULL, 0),
(192, 13, '2022-12-04 21:25:00', 'CIN', NULL,'KC', NULL, 0),
(193, 13, '2022-12-04 21:25:00', 'LV', NULL,'LAC', NULL, 0),
(194, 13, '2022-12-05 01:20:00', 'DAL', NULL,'IND', NULL, 0),
(195, 13, '2022-12-06 01:15:00', 'TB', NULL,'NO', NULL, 0),
(196, 14, '2022-12-09 01:15:00', 'LA', NULL,'LV', NULL, 0),
(197, 14, '2022-12-11 18:00:00', 'BUF', NULL,'NYJ', NULL, 0),
(198, 14, '2022-12-11 18:00:00', 'CIN', NULL,'CLE', NULL, 0),
(199, 14, '2022-12-11 18:00:00', 'DAL', NULL,'HOU', NULL, 0),
(200, 14, '2022-12-11 18:00:00', 'DET', NULL,'MIN', NULL, 0),
(201, 14, '2022-12-11 18:00:00', 'TEN', NULL,'JAX', NULL, 0),
(202, 14, '2022-12-11 18:00:00', 'NYG', NULL,'PHI', NULL, 0),
(203, 14, '2022-12-11 18:00:00', 'PIT', NULL,'BAL', NULL, 0),
(204, 14, '2022-12-11 21:05:00', 'DEN', NULL,'KC', NULL, 0),
(205, 14, '2022-12-11 21:25:00', 'SF', NULL,'TB', NULL, 0),
(206, 14, '2022-12-11 21:25:00', 'SEA', NULL,'CAR', NULL, 0),
(207, 14, '2022-12-12 01:20:00', 'LAC', NULL,'MIA', NULL, 0),
(208, 14, '2022-12-13 01:15:00', 'ARI', NULL,'NE', NULL, 0),
(209, 15, '2022-12-16 01:15:00', 'SEA', NULL,'SF', NULL, 0),
(210, 15, '2022-12-17 18:00:00', 'MIN', NULL,'IND', NULL, 0),
(211, 15, '2022-12-17 21:40:00', 'CLE', NULL,'BAL', NULL, 0),
(212, 15, '2022-12-18 01:15:00', 'BUF', NULL,'MIA', NULL, 0),
(213, 15, '2022-12-18 18:00:00', 'CHI', NULL,'PHI', NULL, 0),
(214, 15, '2022-12-18 18:00:00', 'NO', NULL,'ATL', NULL, 0),
(215, 15, '2022-12-18 18:00:00', 'NYJ', NULL,'DET', NULL, 0),
(216, 15, '2022-12-18 18:00:00', 'CAR', NULL,'PIT', NULL, 0),
(217, 15, '2022-12-18 18:00:00', 'JAX', NULL,'DAL', NULL, 0),
(218, 15, '2022-12-18 18:00:00', 'HOU', NULL,'KC', NULL, 0),
(219, 15, '2022-12-18 21:05:00', 'DEN', NULL,'ARI', NULL, 0),
(220, 15, '2022-12-18 21:05:00', 'LV', NULL,'NE', NULL, 0),
(221, 15, '2022-12-18 21:25:00', 'LAC', NULL,'TEN', NULL, 0),
(222, 15, '2022-12-18 21:25:00', 'TB', NULL,'CIN', NULL, 0),
(223, 15, '2022-12-19 01:20:00', 'WAS', NULL,'NYG', NULL, 0),
(224, 15, '2022-12-20 01:15:00', 'GB', NULL,'LA', NULL, 0),
(225, 16, '2022-12-23 01:15:00', 'NYJ', NULL,'JAX', NULL, 0),
(226, 16, '2022-12-24 18:00:00', 'CHI', NULL,'BUF', NULL, 0),
(227, 16, '2022-12-24 18:00:00', 'CLE', NULL,'NO', NULL, 0),
(228, 16, '2022-12-24 18:00:00', 'KC', NULL,'SEA', NULL, 0),
(229, 16, '2022-12-24 18:00:00', 'MIN', NULL,'NYG', NULL, 0),
(230, 16, '2022-12-24 18:00:00', 'NE', NULL,'CIN', NULL, 0),
(231, 16, '2022-12-24 18:00:00', 'CAR', NULL,'DET', NULL, 0),
(232, 16, '2022-12-24 18:00:00', 'BAL', NULL,'ATL', NULL, 0),
(233, 16, '2022-12-24 19:00:00', 'TEN', NULL,'HOU', NULL, 0),
(234, 16, '2022-12-24 21:05:00', 'SF', NULL,'WAS', NULL, 0),
(235, 16, '2022-12-24 21:25:00', 'DAL', NULL,'PHI', NULL, 0),
(236, 16, '2022-12-25 01:15:00', 'PIT', NULL,'LV', NULL, 0),
(237, 16, '2022-12-25 18:00:00', 'MIA', NULL,'GB', NULL, 0),
(238, 16, '2022-12-25 21:30:00', 'LA', NULL,'DEN', NULL, 0),
(239, 16, '2022-12-26 01:20:00', 'ARI', NULL,'TB', NULL, 0),
(240, 16, '2022-12-27 01:15:00', 'IND', NULL,'LAC', NULL, 0),
(241, 17, '2022-12-30 01:15:00', 'TEN', NULL,'DAL', NULL, 0),
(242, 17, '2023-01-01 18:00:00', 'ATL', NULL,'ARI', NULL, 0),
(243, 17, '2023-01-01 18:00:00', 'DET', NULL,'CHI', NULL, 0),
(244, 17, '2023-01-01 18:00:00', 'KC', NULL,'DEN', NULL, 0),
(245, 17, '2023-01-01 18:00:00', 'NE', NULL,'MIA', NULL, 0),
(246, 17, '2023-01-01 18:00:00', 'NYG', NULL,'IND', NULL, 0),
(247, 17, '2023-01-01 18:00:00', 'PHI', NULL,'NO', NULL, 0),
(248, 17, '2023-01-01 18:00:00', 'TB', NULL,'CAR', NULL, 0),
(249, 17, '2023-01-01 18:00:00', 'WAS', NULL,'CLE', NULL, 0),
(250, 17, '2023-01-01 18:00:00', 'HOU', NULL,'JAX', NULL, 0),
(251, 17, '2023-01-01 21:05:00', 'LV', NULL,'SF', NULL, 0),
(252, 17, '2023-01-01 21:05:00', 'SEA', NULL,'NYJ', NULL, 0),
(253, 17, '2023-01-01 21:25:00', 'GB', NULL,'MIN', NULL, 0),
(254, 17, '2023-01-01 21:25:00', 'LAC', NULL,'LA', NULL, 0),
(255, 17, '2023-01-02 01:20:00', 'BAL', NULL,'PIT', NULL, 0),
(256, 17, '2023-01-03 01:30:00', 'CIN', NULL,'BUF', NULL, 0),
(257, 18, '2023-01-07 21:30:00', 'LV', NULL,'KC', NULL, 0),
(258, 18, '2023-01-08 01:15:00', 'JAX', NULL,'TEN', NULL, 0),
(259, 18, '2023-01-08 18:00:00', 'ATL', NULL,'TB', NULL, 0),
(260, 18, '2023-01-08 18:00:00', 'BUF', NULL,'NE', NULL, 0),
(261, 18, '2023-01-08 18:00:00', 'CHI', NULL,'MIN', NULL, 0),
(262, 18, '2023-01-08 18:00:00', 'CIN', NULL,'BAL', NULL, 0),
(263, 18, '2023-01-08 18:00:00', 'IND', NULL,'HOU', NULL, 0),
(264, 18, '2023-01-08 18:00:00', 'MIA', NULL,'NYJ', NULL, 0),
(265, 18, '2023-01-08 18:00:00', 'NO', NULL,'CAR', NULL, 0),
(266, 18, '2023-01-08 18:00:00', 'PIT', NULL,'CLE', NULL, 0),
(267, 18, '2023-01-08 21:25:00', 'DEN', NULL,'LAC', NULL, 0),
(268, 18, '2023-01-08 21:25:00', 'PHI', NULL,'NYG', NULL, 0),
(269, 18, '2023-01-08 21:25:00', 'SF', NULL,'ARI', NULL, 0),
(270, 18, '2023-01-08 21:25:00', 'SEA', NULL,'LA', NULL, 0),
(271, 18, '2023-01-08 21:25:00', 'WAS', NULL,'DAL', NULL, 0),
(272, 18, '2023-01-09 01:20:00', 'GB', NULL,'DET', NULL, 0);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;