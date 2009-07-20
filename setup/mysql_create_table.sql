
--
-- Table structure for table `demo`
--

DROP TABLE IF EXISTS `demo`;
CREATE TABLE `demo` (
  `id` int(11) NOT NULL auto_increment,
  `filename` varchar(64) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `filename` (`filename`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `player`
--

DROP TABLE IF EXISTS `player`;
CREATE TABLE `player` (
  `id` int(11) NOT NULL auto_increment,
  `steam_id` varchar(32) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `steam_id` (`steam_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `demo_player`
--

DROP TABLE IF EXISTS `demo_player`;
CREATE TABLE `demo_player` (
  `demo_id` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `name` varchar(32) NOT NULL ,
  KEY `demo_id` (`demo_id`),
  KEY `player_id` (`player_id`),
  KEY `name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


