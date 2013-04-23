--START_QUERY--
CREATE TABLE IF NOT EXISTS `PUT_TBL_LOG_HERE` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `what` varchar(60) CHARACTER SET PUT_DB_CHARSET_HERE COLLATE PUT_DB_COLLATION_HERE NOT NULL DEFAULT '',
  `who` mediumint(9) DEFAULT NULL,
  `ip` varchar(23) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `ex` varchar(250) CHARACTER SET PUT_DB_CHARSET_HERE COLLATE PUT_DB_COLLATION_HERE DEFAULT NULL,
  `line` int NOT NULL DEFAULT 0,
  `when` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `what` (`what`,`who`)
) ENGINE=MyISAM DEFAULT CHARSET=PUT_DB_CHARSET_HERE COLLATE=PUT_DB_COLLATION_HERE AUTO_INCREMENT=1 ;

--START_QUERY--
CREATE TABLE IF NOT EXISTS `PUT_TBL_INFO_HERE` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tname` varchar(36) CHARACTER SET PUT_DB_CHARSET_HERE COLLATE PUT_DB_COLLATION_HERE NOT NULL DEFAULT '',
  `fname` varchar(36) CHARACTER SET PUT_DB_CHARSET_HERE COLLATE PUT_DB_COLLATION_HERE NOT NULL DEFAULT '',
  `class` varchar(36) CHARACTER SET PUT_DB_CHARSET_HERE COLLATE PUT_DB_COLLATION_HERE NOT NULL DEFAULT '',
  `label` varchar(250) CHARACTER SET PUT_DB_CHARSET_HERE COLLATE PUT_DB_COLLATION_HERE NOT NULL DEFAULT '',
  `okmax` bigint(20) DEFAULT NULL,
  `okmin` bigint(20) DEFAULT NULL,
  `okempty` tinyint(1) NOT NULL DEFAULT 1,
  `oknull` tinyint(1) NOT NULL DEFAULT 1,
  `indexed` tinyint(1) NOT NULL DEFAULT 0,
  `searchable` tinyint(1) NOT NULL DEFAULT 1,
  `order` int(5) NOT NULL DEFAULT 0,
  `show` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `orderby` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `odirasc` tinyint(1) NOT NULL DEFAULT 1,
  `default` varchar(250) CHARACTER SET PUT_DB_CHARSET_HERE COLLATE PUT_DB_COLLATION_HERE DEFAULT NULL,
  `allowed1` varchar(250) CHARACTER SET PUT_DB_CHARSET_HERE COLLATE PUT_DB_COLLATION_HERE NOT NULL DEFAULT '',
  `allowed2` varchar(250) CHARACTER SET PUT_DB_CHARSET_HERE COLLATE PUT_DB_COLLATION_HERE NOT NULL DEFAULT '',
  `allowed3` varchar(250) CHARACTER SET PUT_DB_CHARSET_HERE COLLATE PUT_DB_COLLATION_HERE NOT NULL DEFAULT '',
  `allowed4` varchar(250) CHARACTER SET PUT_DB_CHARSET_HERE COLLATE PUT_DB_COLLATION_HERE NOT NULL DEFAULT '',
  `comment1` varchar(500) CHARACTER SET PUT_DB_CHARSET_HERE COLLATE PUT_DB_COLLATION_HERE NOT NULL DEFAULT '',
  `comment2` varchar(500) CHARACTER SET PUT_DB_CHARSET_HERE COLLATE PUT_DB_COLLATION_HERE NOT NULL DEFAULT '',
  `comment3` varchar(500) CHARACTER SET PUT_DB_CHARSET_HERE COLLATE PUT_DB_COLLATION_HERE NOT NULL DEFAULT '',
  `actions1` varchar(500) CHARACTER SET PUT_DB_CHARSET_HERE COLLATE PUT_DB_COLLATION_HERE NOT NULL DEFAULT '',
  `actions2` varchar(500) CHARACTER SET PUT_DB_CHARSET_HERE COLLATE PUT_DB_COLLATION_HERE NOT NULL DEFAULT '',
  `actions3` varchar(500) CHARACTER SET PUT_DB_CHARSET_HERE COLLATE PUT_DB_COLLATION_HERE NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tname_fname` (`tname`,`fname`),
  KEY `tname` (`tname`),
  KEY `order` (`order`)
) ENGINE=MyISAM DEFAULT CHARSET=PUT_DB_CHARSET_HERE COLLATE=PUT_DB_COLLATION_HERE AUTO_INCREMENT=0 ;

--START_QUERY--
CREATE TABLE IF NOT EXISTS `PUT_TBL_USERS_HERE` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `group` varchar(60) CHARACTER SET PUT_DB_CHARSET_HERE COLLATE PUT_DB_COLLATION_HERE NOT NULL DEFAULT '' COMMENT 'role, group for permissions',
  `login` varchar(60) CHARACTER SET PUT_DB_CHARSET_HERE COLLATE PUT_DB_COLLATION_HERE NOT NULL DEFAULT '' COMMENT 'user name',
  `name` varchar(100) CHARACTER SET PUT_DB_CHARSET_HERE COLLATE PUT_DB_COLLATION_HERE NOT NULL DEFAULT '' COMMENT 'real name',
  `email` varchar(100) CHARACTER SET PUT_DB_CHARSET_HERE COLLATE PUT_DB_COLLATION_HERE NOT NULL DEFAULT '',
  `pass` varchar(32) CHARACTER SET latin1 NOT NULL DEFAULT '' COMMENT 'md5',
  `added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=PUT_DB_CHARSET_HERE COLLATE=PUT_DB_COLLATION_HERE AUTO_INCREMENT=109;

--START_QUERY--
CREATE TABLE IF NOT EXISTS `PUT_TBL_META_HERE` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tname` varchar(60) COLLATE utf8_bin NOT NULL,
  `fname` varchar(60) COLLATE utf8_bin NOT NULL,
  `key` varchar(100) COLLATE utf8_bin NOT NULL,
  `value` varchar(2000) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tname_fname_key` (`tname`,`fname`,`key`),
  KEY `tname_fname` (`tname`,`fname`),
  KEY `tname` (`tname`),
  KEY `key` (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=PUT_DB_CHARSET_HERE COLLATE=PUT_DB_COLLATION_HERE AUTO_INCREMENT=1;

--START_QUERY--
REPLACE INTO `PUT_TBL_USERS_HERE`(`group`,`login`,`name`,`pass`,`email`) VALUES('root','PUT_ROOT_USER_HERE','PUT_ROOT_NAME_HERE','PUT_ROOT_PASS_HERE','PUT_ROOT_EMAIL_HERE');

--START_QUERY--
INSERT IGNORE INTO `PUT_TBL_INFO_HERE` (`tname`, `fname`, `class`, `label`, `okmax`, `okmin`, `okempty`, `oknull`, `indexed`, `searchable`, `order`, `show`, `orderby`, `odirasc`, `default`, `allowed1`, `allowed2`, `allowed3`, `allowed4`) VALUES
('', '', NULL, NULL, NULL, NULL, 1, 1, 0, NULL, 1000, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_USERS_HERE', '', '', 'TF Users', NULL, NULL, 1, 1, 0, NULL, -10, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_USERS_HERE', 'login', 'string', 'User Name', NULL, NULL, 1, 0, 0, 1, 90, 0, 10, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_USERS_HERE', 'id', 'pkey', 'Id', NULL, NULL, 1, 0, 0, 0, -30, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_USERS_HERE', 'name', 'string', 'Real Name', NULL, NULL, 1, 0, 0, 1, 80, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_USERS_HERE', 'group', 'string', 'Group', NULL, NULL, 1, 0, 0, 1, 70, 0, 20, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_USERS_HERE', 'added', 'timestamp', 'Added', NULL, NULL, 1, 0, 0, 0, -10, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_USERS_HERE', 'pass', 'md5', 'Password MD5', NULL, NULL, 1, 0, 0, 1, 60, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', '', '', 'TF Tables Structure', NULL, NULL, 1, 1, 0, NULL, 0, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', 'id', 'pkey', 'Id', NULL, NULL, 1, 0, 0, 0, 1010, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', 'tname', 'string', 'Table-name', NULL, NULL, 1, 0, 0, 1, 1002, 0, 100, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', 'fname', 'string', 'Field-name', NULL, NULL, 1, 0, 0, 1, 1001, 0, 80, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', 'class', 'TFclass', 'Field Type Class', NULL, NULL, 1, 0, 0, 1, 90, 0, 0, 1, 'string', 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', 'label', 'string', 'Label', NULL, NULL, 1, 0, 0, 1, 80, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', 'okmax', 'number', 'Max value', NULL, NULL, 1, 0, 0, 0, 60, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', 'okmin', 'number', 'Min value', NULL, NULL, 1, 0, 0, 0, 60, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', 'okempty', 'boolean', 'Allow Empty?', NULL, NULL, 1, 0, 0, 1, 60, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', 'oknull',  'boolean', 'Allow Null?', NULL, NULL, 1, 0, 0, 0, 60, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', 'indexed',  'boolean', 'Indexed?', NULL, NULL, 1, 0, 0, 0, 60, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', 'searchable', 'boolean', 'Searchable?', NULL, NULL, 1, 0, 0, 0, 60, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', 'order', 'order', 'Show order', NULL, NULL, 1, 0, 0, 0, 60, 0, 90, 0, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', 'show', 'number', 'Show?', NULL, NULL, 1, 0, 0, 0, 60, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', 'orderby', 'order', 'Sort order', NULL, NULL, 1, 0, 0, 0, 60, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', 'odirasc', 'boolean', 'Sort Ascending?', NULL, NULL, 1, 0, 0, 0, 60, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', 'default', 'string', 'Default value', NULL, NULL, 1, 0, 0, 1, 80, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', 'allowed1', 'tfxkeys', 'Groups can read', NULL, NULL, 1, 0, 0, 1, 20, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', 'allowed2', 'tfxkeys', 'Groups can edit', NULL, NULL, 1, 0, 0, 1, 20, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', 'allowed3', 'tfxkeys', 'Groups can add', NULL, NULL, 1, 0, 0, 1, 20, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', 'allowed4', 'tfxkeys', 'Groups can delete', NULL, NULL, 1, 0, 0, 1, 20, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', 'comment1', 'string', 'Comment on Read', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', 'comment2', 'string', 'Comment on Edit', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', 'comment3', 'string', 'Comment on Add', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', 'labelnew', 'string', 'Label on New', NULL, NULL, 1, 0, 0, 1, 50, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', 'actions1', 'string', 'Actions on Read', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', 'actions2', 'string', 'Actions on Edit', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_INFO_HERE', 'actions3', 'string', 'Actions on Add', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 1, NULL, 'root', 'root', 'root', 'root'),
('PUT_TBL_META_HERE','','','TF Structure Parameters',NULL,NULL,1,1,0,NULL,0,0,0,1,'','root','root','root','root'),
('PUT_TBL_META_HERE','id','pkey','id',NULL,NULL,1,1,1,0,-70,0,0,1,'','','','',''),
('PUT_TBL_META_HERE','tname','string','Table',NULL,NULL,1,1,0,1,70,0,0,1,'','root','','root','root'),
('PUT_TBL_META_HERE','fname','string','Field',NULL,NULL,1,1,0,1,70,0,0,1,'','root','','root','root'),
('PUT_TBL_META_HERE','key','string','Key',60,NULL,1,1,1,1,90,0,0,1,'','root','root','root','root'),
('PUT_TBL_META_HERE','value','string','Value',60,NULL,1,1,1,1,90,0,0,1,'','root','root','root','root'),
('PUT_TBL_LOG_HERE','','','Log',NULL,NULL,1,1,0,NULL,0,0,0,1,'','root','root','root','root'),
('PUT_TBL_LOG_HERE','when', 'timestamp','When',NULL,NULL,1,1,0,0,80,0,0,1,'','root','root','root','root'),
('PUT_TBL_LOG_HERE','ex','string','more info',250,NULL,1,1,0,1,60,0,0,1,'','root','root','root','root'),
('PUT_TBL_LOG_HERE','ip','string','IP',23,NULL,1,1,0,1,50,0,0,1,'','root','root','root','root'),
('PUT_TBL_LOG_HERE','who','xkey','Who',NULL,NULL,1,1,0,1,70,0,0,1,'','root','root','root','root'),
('PUT_TBL_LOG_HERE','what','string','What',60,NULL,1,1,1,1,90,0,0,1,'','root','root','root','root'),
('PUT_TBL_LOG_HERE','id','pkey','id',NULL,NULL,1,1,1,0,-70,0,0,1,'','','','','');

--START_QUERY--
INSERT IGNORE INTO `PUT_TBL_META_HERE` (`tname`,`fname`,`key`,`value`) VALUES
('PUT_TBL_INFO_HERE','tname', 'pattern','^(?!___)[a-zA-Z0-9\\-_]+$'),
('PUT_TBL_INFO_HERE','fname', 'pattern','^(?!___)[a-zA-Z0-9\\-_]+$'),
('PUT_TBL_INFO_HERE','allowed1', 'xtable','PUT_TBL_USERS_HERE'),('PUT_TBL_INFO_HERE','allowed1','xname','group'),('PUT_TBL_INFO_HERE','allowed1','xkey','group'),('PUT_TBL_INFO_HERE','allowed1','strict','1'),('PUT_TBL_INFO_HERE','allowed1','xclass','string'),
('PUT_TBL_INFO_HERE','allowed2', 'xtable','PUT_TBL_USERS_HERE'),('PUT_TBL_INFO_HERE','allowed2','xname','group'),('PUT_TBL_INFO_HERE','allowed2','xkey','group'),('PUT_TBL_INFO_HERE','allowed2','strict','1'),('PUT_TBL_INFO_HERE','allowed2','xclass','string'),
('PUT_TBL_INFO_HERE','allowed3', 'xtable','PUT_TBL_USERS_HERE'),('PUT_TBL_INFO_HERE','allowed3','xname','group'),('PUT_TBL_INFO_HERE','allowed3','xkey','group'),('PUT_TBL_INFO_HERE','allowed3','strict','1'),('PUT_TBL_INFO_HERE','allowed3','xclass','string'),
('PUT_TBL_INFO_HERE','allowed4', 'xtable','PUT_TBL_USERS_HERE'),('PUT_TBL_INFO_HERE','allowed4','xname','group'),('PUT_TBL_INFO_HERE','allowed4','xkey','group'),('PUT_TBL_INFO_HERE','allowed4','strict','1'),('PUT_TBL_INFO_HERE','allowed4','xclass','string'),
('PUT_TBL_LOG_HERE','who', 'xtable','PUT_TBL_USERS_HERE'),('PUT_TBL_LOG_HERE','who','xname','CONCAT(`$xtable`.`name`,\' (\',`$xtable`.`login`,\')\')'),('PUT_TBL_LOG_HERE','who','xkey','id'),('PUT_TBL_LOG_HERE','who','strict','1'),('PUT_TBL_LOG_HERE','who','xclass','string');
