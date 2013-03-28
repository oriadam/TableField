--START_QUERY--
CREATE TABLE IF NOT EXISTS `PUT_TBL_LOG_HERE` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `what` varchar(60) COLLATE PUT_DB_COLLATION_HERE NOT NULL DEFAULT '',
  `who` mediumint(9) DEFAULT NULL,
  `ip` varchar(23) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL,
  `ex` varchar(255) COLLATE PUT_DB_COLLATION_HERE DEFAULT NULL,
  `line` int NOT NULL DEFAULT 0,
  `when` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `what` (`what`,`who`)
) ENGINE=MyISAM DEFAULT CHARSET=PUT_DB_CHARSET_HERE COLLATE=PUT_DB_COLLATION_HERE AUTO_INCREMENT=1 ;

--START_QUERY--
CREATE TABLE IF NOT EXISTS `PUT_TBL_INFO_HERE` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tname` varchar(36) COLLATE PUT_DB_COLLATION_HERE DEFAULT '',
  `fname` varchar(36) COLLATE PUT_DB_COLLATION_HERE DEFAULT '',
  `class` varchar(36) COLLATE PUT_DB_COLLATION_HERE DEFAULT NULL,
  `label` varchar(255) COLLATE PUT_DB_COLLATION_HERE DEFAULT NULL,
  `okmax` bigint(20) DEFAULT NULL,
  `okmin` bigint(20) DEFAULT NULL,
  `okempty` tinyint(1) DEFAULT '1',
  `oknull` tinyint(1) DEFAULT '1',
  `indexed` tinyint(1) DEFAULT '0',
  `searchable` tinyint(1) DEFAULT NULL,
  `order` int(5) NOT NULL DEFAULT '0',
  `show` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `orderby` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `odirasc` tinyint(1) NOT NULL DEFAULT '1',
  `default` varchar(255) COLLATE PUT_DB_COLLATION_HERE DEFAULT NULL,
  `usersview` varchar(255) COLLATE PUT_DB_COLLATION_HERE DEFAULT NULL,
  `usersedit` varchar(255) COLLATE PUT_DB_COLLATION_HERE DEFAULT NULL,
  `usersnew` varchar(255) COLLATE PUT_DB_COLLATION_HERE DEFAULT NULL,
  `usersdel` varchar(255) COLLATE PUT_DB_COLLATION_HERE DEFAULT NULL,
  `commentview` varchar(512) COLLATE PUT_DB_COLLATION_HERE DEFAULT NULL,
  `commentedit` varchar(512) COLLATE PUT_DB_COLLATION_HERE DEFAULT NULL,
  `commentnew` varchar(512) COLLATE PUT_DB_COLLATION_HERE DEFAULT NULL,
  `labelnew` varchar(255) COLLATE PUT_DB_COLLATION_HERE NOT NULL,
  `commentdel` varchar(512) COLLATE PUT_DB_COLLATION_HERE DEFAULT NULL,
  `actionsview` varchar(512) COLLATE PUT_DB_COLLATION_HERE DEFAULT NULL,
  `actionsedit` varchar(512) COLLATE PUT_DB_COLLATION_HERE DEFAULT NULL,
  `actionsnew` varchar(512) COLLATE PUT_DB_COLLATION_HERE DEFAULT NULL,
  `actionsdel` varchar(512) COLLATE PUT_DB_COLLATION_HERE DEFAULT NULL,
  `remarks` varchar(512) COLLATE PUT_DB_COLLATION_HERE DEFAULT NULL,
  `params` varchar(512) COLLATE PUT_DB_COLLATION_HERE DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tname_fname` (`tname`,`fname`),
  KEY `tname` (`tname`),
  KEY `order` (`order`)
) ENGINE=MyISAM DEFAULT CHARSET=PUT_DB_CHARSET_HERE COLLATE=PUT_DB_COLLATION_HERE AUTO_INCREMENT=0 ;

--START_QUERY--
CREATE TABLE IF NOT EXISTS `PUT_TBL_USERS_HERE` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `group` varchar(60) COLLATE PUT_DB_COLLATION_HERE NOT NULL DEFAULT '',
  `login` varchar(60) COLLATE PUT_DB_COLLATION_HERE NOT NULL DEFAULT '' COMMENT 'user name',
  `name` varchar(100) COLLATE PUT_DB_COLLATION_HERE NOT NULL DEFAULT '' COMMENT 'real name',
  `email` varchar(100) COLLATE PUT_DB_COLLATION_HERE NOT NULL DEFAULT '' COMMENT 'for password reset',
  `pass` varchar(32) CHARACTER SET latin1 COLLATE PUT_DB_COLLATION_HERE NOT NULL DEFAULT '' COMMENT 'md5',
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
INSERT IGNORE INTO `PUT_TBL_INFO_HERE` (`tname`, `fname`, `class`, `label`, `okmax`, `okmin`, `okempty`, `oknull`, `indexed`, `searchable`, `order`, `show`, `orderby`, `odirasc`, `default`, `usersview`, `usersedit`, `usersnew`, `usersdel`, `commentview`, `commentedit`, `commentnew`, `labelnew`, `commentdel`, `actionsview`, `actionsedit`, `actionsnew`, `actionsdel`, `remarks`) VALUES
('', '', NULL, NULL, NULL, NULL, 1, 1, 0, NULL, 1000, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, '', '', '', '', NULL),
('PUT_TBL_USERS_HERE', '', '', 'TF Users', NULL, NULL, 1, 1, 0, NULL, -10, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_USERS_HERE', 'login', 'string', 'User Name', NULL, NULL, 1, 0, 0, 1, 90, 0, 10, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_USERS_HERE', 'id', 'pkey', 'Id', NULL, NULL, 1, 0, 0, 0, -30, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_USERS_HERE', 'name', 'string', 'Real Name', NULL, NULL, 1, 0, 0, 1, 80, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_USERS_HERE', 'group', 'string', 'Group', NULL, NULL, 1, 0, 0, 1, 70, 0, 20, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_USERS_HERE', 'added', 'timestamp', 'Added', NULL, NULL, 1, 0, 0, 0, -10, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_USERS_HERE', 'pass', 'md5', 'Password MD5', NULL, NULL, 1, 0, 0, 1, 60, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', '', '', 'TF Tables Structure', NULL, NULL, 1, 1, 0, NULL, 0, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'id', 'pkey', 'Id', NULL, NULL, 1, 0, 0, 0, 1010, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'tname', 'string', 'Table-name', NULL, NULL, 1, 0, 0, 1, 1002, 0, 100, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'fname', 'string', 'Field-name', NULL, NULL, 1, 0, 0, 1, 1001, 0, 80, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'class', 'TFclass', 'Field Type Class', NULL, NULL, 1, 0, 0, 1, 90, 0, 0, 1, 'string', 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'label', 'string', 'Label', NULL, NULL, 1, 0, 0, 1, 80, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'okmax', 'number', 'Max value', NULL, NULL, 1, 0, 0, 0, 60, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'okmin', 'number', 'Min value', NULL, NULL, 1, 0, 0, 0, 60, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'okempty', 'boolean', 'Allow Empty?', NULL, NULL, 1, 0, 0, 1, 60, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'oknull',  'boolean', 'Allow Null?', NULL, NULL, 1, 0, 0, 0, 60, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'indexed',  'boolean', 'Indexed?', NULL, NULL, 1, 0, 0, 0, 60, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'searchable', 'boolean', 'Searchable?', NULL, NULL, 1, 0, 0, 0, 60, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'order', 'order', 'Show order', NULL, NULL, 1, 0, 0, 0, 60, 0, 90, 0, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'show', 'number', 'Show?', NULL, NULL, 1, 0, 0, 0, 60, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'orderby', 'order', 'Sort order', NULL, NULL, 1, 0, 0, 0, 60, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'odirasc', 'boolean', 'Sort Ascending?', NULL, NULL, 1, 0, 0, 0, 60, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'default', 'string', 'Default value', NULL, NULL, 1, 0, 0, 1, 80, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'usersview', 'tfxkeys', 'Groups-Read', NULL, NULL, 1, 0, 0, 1, 20, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'usersedit', 'tfxkeys', 'Groups-Edit', NULL, NULL, 1, 0, 0, 1, 20, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'usersnew', 'tfxkeys', 'Groups-Add', NULL, NULL, 1, 0, 0, 1, 20, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'usersdel', 'tfxkeys', 'Groups-Delete', NULL, NULL, 1, 0, 0, 1, 20, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'commentview', 'string', 'Comment on Read', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'commentedit', 'string', 'Comment on Edit', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'commentnew', 'string', 'Comment on Add', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'labelnew', 'string', 'Label on New', NULL, NULL, 1, 0, 0, 1, 50, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'commentdel', 'string', 'Command on Delete', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'actionsview', 'string', 'Actions on Read', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'actionsedit', 'string', 'Actions on Edit', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'actionsnew', 'string', 'Actions on Add', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'actionsdel', 'string', 'Actions on Delete', NULL, NULL, 1, 0, 0, 1, 0, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_INFO_HERE', 'remarks', 'string', 'Remarks', NULL, NULL, 1, 0, 0, 1, 40, 0, 0, 1, NULL, 'root', 'root', 'root', 'root', NULL, NULL, NULL, '', NULL, NULL, NULL, NULL, NULL, NULL),
('PUT_TBL_META_HERE','','','TF Structure Parameters',NULL,NULL,1,1,0,NULL,0,0,0,1,'','root','root','root','root',NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL),
('PUT_TBL_META_HERE','id','pkey','id',NULL,NULL,1,1,1,0,-70,0,0,1,'','','','','',NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL);
('PUT_TBL_META_HERE','tname','string','Table',NULL,NULL,1,1,0,1,70,0,0,1,'','root','','root','root',NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL),
('PUT_TBL_META_HERE','fname','string','Field',NULL,NULL,1,1,0,1,70,0,0,1,'','root','','root','root',NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL),
('PUT_TBL_META_HERE','key','string','Key',60,NULL,1,1,1,1,90,0,0,1,'','root','root','root','root',NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL),
('PUT_TBL_META_HERE','value','string','Value',60,NULL,1,1,1,1,90,0,0,1,'','root','root','root','root',NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL),
('PUT_TBL_LOG_HERE','','','Log',NULL,NULL,1,1,0,NULL,0,0,0,1,'','root','root','root','root',NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL),
('PUT_TBL_LOG_HERE','when', 'timestamp','When',NULL,NULL,1,1,0,0,80,0,0,1,'','root','root','root','root',NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL),
('PUT_TBL_LOG_HERE','ex','string','more info',255,NULL,1,1,0,1,60,0,0,1,'','root','root','root','root',NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL),
('PUT_TBL_LOG_HERE','ip','string','IP',23,NULL,1,1,0,1,50,0,0,1,'','root','root','root','root',NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL),
('PUT_TBL_LOG_HERE','who','xkey','Who',NULL,NULL,1,1,0,1,70,0,0,1,'','root','root','root','root',NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL),
('PUT_TBL_LOG_HERE','what','string','What',60,NULL,1,1,1,1,90,0,0,1,'','root','root','root','root',NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL),
('PUT_TBL_LOG_HERE','id','pkey','id',NULL,NULL,1,1,1,0,-70,0,0,1,'','','','','',NULL,NULL,NULL,'',NULL,NULL,NULL,NULL,NULL,NULL);

--START_QUERY--
INSERT IGNORE INTO `PUT_TBL_META_HERE` (`tname`,`fname`,`key`,`value`) VALUES
('PUT_TBL_INFO_HERE','usersview', 'xtable','PUT_TBL_USERS_HERE'),('PUT_TBL_INFO_HERE','usersview','xname','group'),('PUT_TBL_INFO_HERE','usersview','xkey','group'),('PUT_TBL_INFO_HERE','usersview','strict','1'),('PUT_TBL_INFO_HERE','usersview','xclass','string'),
('PUT_TBL_INFO_HERE','usersdel' , 'xtable','PUT_TBL_USERS_HERE'),('PUT_TBL_INFO_HERE','usersdel' ,'xname','group'),('PUT_TBL_INFO_HERE','usersdel' ,'xkey','group'),('PUT_TBL_INFO_HERE','usersdel' ,'strict','1'),('PUT_TBL_INFO_HERE','usersdel' ,'xclass','string'),
('PUT_TBL_INFO_HERE','usersedit', 'xtable','PUT_TBL_USERS_HERE'),('PUT_TBL_INFO_HERE','usersedit','xname','group'),('PUT_TBL_INFO_HERE','usersedit','xkey','group'),('PUT_TBL_INFO_HERE','usersedit','strict','1'),('PUT_TBL_INFO_HERE','usersedit','xclass','string'),
('PUT_TBL_INFO_HERE','usersnew' , 'xtable','PUT_TBL_USERS_HERE'),('PUT_TBL_INFO_HERE','usersnew' ,'xname','group'),('PUT_TBL_INFO_HERE','usersnew' ,'xkey','group'),('PUT_TBL_INFO_HERE','usersnew' ,'strict','1'),('PUT_TBL_INFO_HERE','usersnew' ,'xclass','string'),
('PUT_TBL_LOG_HERE','who', 'xtable','PUT_TBL_USERS_HERE'),('PUT_TBL_LOG_HERE','who','xname','CONCAT(`$xtable`.`name`,\' (\',`$xtable`.`login`,\')\')'),('PUT_TBL_LOG_HERE','who','xkey','id'),('PUT_TBL_LOG_HERE','who','strict','1'),('PUT_TBL_LOG_HERE','who','xclass','string');
