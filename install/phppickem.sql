-- PHP Pick 'Em Clean Install
-- Season: 2025–2026
-- Timezone: America/New_York (Eastern)
-- Generated: May 2025
-- https://it.megocollector.com

SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
START TRANSACTION;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- =========================================================
-- DIVISIONS
-- =========================================================

DROP TABLE IF EXISTS `nflp_divisions`;
CREATE TABLE `nflp_divisions` (
  `divisionID` int(11) NOT NULL,
  `conference` varchar(3) NOT NULL,
  `division` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO `nflp_divisions` VALUES
(1,'AFC','North'),
(2,'AFC','South'),
(3,'AFC','East'),
(4,'AFC','West'),
(5,'NFC','North'),
(6,'NFC','South'),
(7,'NFC','East'),
(8,'NFC','West');

ALTER TABLE `nflp_divisions`
  ADD PRIMARY KEY (`divisionID`);

-- =========================================================
-- TEAMS (BallDontLie / ESPN compatible)
-- =========================================================

DROP TABLE IF EXISTS `nflp_teams`;
CREATE TABLE `nflp_teams` (
  `teamID` varchar(10) NOT NULL,
  `divisionID` int(11) NOT NULL,
  `city` varchar(50) NOT NULL,
  `team` varchar(50) NOT NULL,
  `displayName` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO `nflp_teams` VALUES
('ARI',8,'Arizona','Cardinals',NULL),
('ATL',6,'Atlanta','Falcons',NULL),
('BAL',1,'Baltimore','Ravens',NULL),
('BUF',3,'Buffalo','Bills',NULL),
('CAR',6,'Carolina','Panthers',NULL),
('CHI',5,'Chicago','Bears',NULL),
('CIN',1,'Cincinnati','Bengals',NULL),
('CLE',1,'Cleveland','Browns',NULL),
('DAL',7,'Dallas','Cowboys',NULL),
('DEN',4,'Denver','Broncos',NULL),
('DET',5,'Detroit','Lions',NULL),
('GB',5,'Green Bay','Packers',NULL),
('HOU',2,'Houston','Texans',NULL),
('IND',2,'Indianapolis','Colts',NULL),
('JAX',2,'Jacksonville','Jaguars',NULL),
('KC',4,'Kansas City','Chiefs',NULL),
('MIA',3,'Miami','Dolphins',NULL),
('MIN',5,'Minnesota','Vikings',NULL),
('NE',3,'New England','Patriots',NULL),
('NO',6,'New Orleans','Saints',NULL),
('NYG',7,'New York','Giants','New York Giants'),
('NYJ',3,'New York','Jets','New York Jets'),
('LV',4,'Las Vegas','Raiders',NULL),
('PHI',7,'Philadelphia','Eagles',NULL),
('PIT',1,'Pittsburgh','Steelers',NULL),
('LAC',4,'Los Angeles','Chargers',NULL),
('SEA',8,'Seattle','Seahawks',NULL),
('SF',8,'San Francisco','49ers',NULL),
('LAR',8,'Los Angeles','Rams',NULL),
('TB',6,'Tampa Bay','Buccaneers',NULL),
('TEN',2,'Tennessee','Titans',NULL),
('WSH',7,'Washington','Commanders',NULL);

ALTER TABLE `nflp_teams`
  ADD PRIMARY KEY (`teamID`);

-- =========================================================
-- SCHEDULE (CLEAN – Eastern Time)
-- =========================================================

DROP TABLE IF EXISTS `nflp_schedule`;
CREATE TABLE `nflp_schedule` (
  `gameID` int(11) NOT NULL,
  `weekNum` int(11) NOT NULL,
  `gameTimeEastern` datetime DEFAULT NULL,
  `homeID` varchar(10) NOT NULL,
  `homeScore` int(11) DEFAULT NULL,
  `visitorID` varchar(10) NOT NULL,
  `visitorScore` int(11) DEFAULT NULL,
  `overtime` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

-- =========================================================
-- USERS
-- =========================================================

DROP TABLE IF EXISTS `nflp_users`;
CREATE TABLE `nflp_users` (
  `userID`     int(11)      NOT NULL AUTO_INCREMENT,
  `userName`   varchar(50)  NOT NULL,
  `password`   varchar(255) NOT NULL,
  `salt`       varchar(64)  NOT NULL DEFAULT '',
  `firstname`  varchar(50)  NOT NULL DEFAULT '',
  `lastname`   varchar(50)  NOT NULL DEFAULT '',
  `email`      varchar(100) NOT NULL DEFAULT '',
  `status`     tinyint(1)   NOT NULL DEFAULT 1,
  `is_admin`   tinyint(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (`userID`),
  UNIQUE KEY `userName` (`userName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

-- =========================================================
-- PICKS
-- =========================================================

DROP TABLE IF EXISTS `nflp_picks`;
CREATE TABLE `nflp_picks` (
  `pickID`  varchar(10) NOT NULL,
  `userID`  int(11)     NOT NULL,
  `gameID`  int(11)     NOT NULL,
  `points`  int(11)     NOT NULL DEFAULT 1,
  PRIMARY KEY (`userID`, `gameID`),
  KEY `idx_picks_gameID`  (`gameID`),
  KEY `idx_picks_userID`  (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

-- =========================================================
-- PICK SUMMARY (per-user, per-week metadata)
-- =========================================================

DROP TABLE IF EXISTS `nflp_picksummary`;
CREATE TABLE `nflp_picksummary` (
  `weekNum`          int(11) NOT NULL,
  `userID`           int(11) NOT NULL,
  `showPicks`        tinyint(1) NOT NULL DEFAULT 1,
  `tieBreakerPoints` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`weekNum`, `userID`),
  KEY `idx_picksummary_userID` (`userID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

-- =========================================================
-- EMAIL TEMPLATES
-- =========================================================

DROP TABLE IF EXISTS `nflp_email_templates`;
CREATE TABLE `nflp_email_templates` (
  `email_template_key`   varchar(50)  NOT NULL,
  `email_template_title` varchar(100) NOT NULL,
  `subject`              varchar(200) NOT NULL,
  `message`              text         NOT NULL,
  PRIMARY KEY (`email_template_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `nflp_email_templates` VALUES
('WEEKLY_PICKS_REMINDER',
 'Weekly Picks Reminder',
 '⏰ Week {week} Picks Are Due — Don\'t Miss the Kickoff!',
 '<p>Hey {player}! 👋</p><p>Just a friendly nudge — your Week {week} picks are <strong>not yet complete</strong>! The first game kicks off <strong>{first_game} ET</strong> and once that whistle blows, the deadline is gone. 🏈</p><p>Don\'t let your rivals score easy points while you\'re sitting on the sidelines. Lock in your picks now:</p><p><a href="{site_url}">👉 Submit My Week {week} Picks</a></p><p>May the odds (and the refs) be ever in your favor. 🤞</p><p><em>— Your Pick\'Em Commissioner</em></p>'),
('WEEKLY_RESULTS_REMINDER',
 'Weekly Results',
 '🏆 Week {previousWeek} Results — Who Crushed It?',
 '<p>Hey {player}! 🎉</p><p>Week {previousWeek} is officially in the books, and the scoreboard doesn\'t lie!</p><p>🥇 <strong>This week\'s winner(s):</strong> {winners} — finishing with a scorching <strong>{winningScore} out of {possibleScore}</strong>. Buy them a hot dog, they earned it! 🌭</p><hr /><p>📊 <strong>Overall Standings (Top 3):</strong><br />{currentLeaders}</p><p>🎯 <strong>Best Pick Percentages (Top 5):</strong><br />{bestPickRatios}</p><hr /><p>Think you can do better? Week {week} picks are open — prove it! 😤</p><p><a href="{site_url}">👉 Make My Week {week} Picks</a></p><p><em>— Your Pick\'Em Commissioner</em></p>'),
('FINAL_RESULTS',
 'Season Final Results',
 '🏈 The Season Is Over — Here\'s How It All Shook Out!',
 '<p>Hey {player}! 🎊</p><p>That\'s a wrap on another PHP Pick \'Em season! 18 weeks of touchdowns, upsets, and at least one or two picks you\'d rather forget. 😅</p><p>🥇 <strong>Week {previousWeek} winner(s):</strong> {winners} — closed out the season with <strong>{winningScore} out of {possibleScore}</strong>. Legendary stuff. 🐐</p><hr /><p>🏆 <strong>Final Standings (Top 3):</strong><br />{currentLeaders}</p><p>🎯 <strong>Season Pick Percentages (Top 5):</strong><br />{bestPickRatios}</p><hr /><p>Thanks for playing! Whether you dominated all season or rallied in the final weeks, it\'s been a blast competing with you. See you next fall! 🍂</p><p><a href="{site_url}">🏈 Visit the Site</a> &nbsp;|&nbsp; <a href="{rules_url}">📋 Review the Rules</a></p><p><em>— Your Pick\'Em Commissioner</em></p>'),
('SEASON_KICKOFF',
 'Season Kickoff',
 '🏈 It\'s That Time Again — PHP Pick \'Em Season Starts NOW!',
 '<p>Hey {player}! 🎉</p><p>Your favorite PHP Pick \'Em pool is BACK and the competition is already heating up. 🔥 Can you dethrone last year\'s champ, or will you spend another season saying "I was robbed by overtime"? 😂</p><p>🗓️ <strong>Week 1 kicks off {first_game} ET</strong> — that\'s your deadline, so get your picks in before the opening whistle!</p><p>New here? No problem — check out the rules before you dive in:</p><p><a href="{rules_url}">📋 Read the Rules</a> &nbsp;|&nbsp; <a href="{site_url}">🏈 Make My Week 1 Picks</a></p><p>May your picks be sharp and your upsets be plentiful. Good luck out there! 🤞</p><p><em>— Your Pick\'Em Commissioner</em></p>');

-- =========================================================
-- INSERT 2025–26 SCHEDULE
-- =========================================================

-- (PASTE FULL INSERT BLOCK FROM nfl_schedule_2025.sql HERE)
-- This is intentionally external to avoid transcription errors.
-- Use EXACT output from buildSchedule.php.

-- =========================================================
-- INDEXES
-- =========================================================

ALTER TABLE `nflp_schedule`
  ADD PRIMARY KEY (`gameID`),
  ADD KEY `weekNum` (`weekNum`),
  ADD KEY `HomeID` (`homeID`),
  ADD KEY `VisitorID` (`visitorID`);

ALTER TABLE `nflp_schedule`
  MODIFY `gameID` int(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT=1;

-- =========================================================
-- COMMIT
-- =========================================================

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

