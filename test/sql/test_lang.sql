DROP TABLE IF EXISTS `!PREFIX!_lang`;

CREATE TABLE `!PREFIX!_lang` (
	`idlang` int(11) NOT NULL auto_increment,
	`name` varchar(255),
	`active` tinyint(1),
	`author` varchar(32),
	`created` datetime,
	`lastmodified` datetime,
	`encoding` varchar(32),
	`direction` char(3),
  PRIMARY KEY  (`idlang`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=0;


INSERT INTO `!PREFIX!_lang` VALUES(1, 'deutsch', 1, '48a365b4ce1e322a55ae9017f3daf0c0', '2012-11-10 18:08:21', '2012-11-10 18:09:21', 'utf-8', 'ltr');
INSERT INTO `!PREFIX!_lang` VALUES(2, 'english', 1, '48a365b4ce1e322a55ae9017f3daf0c0', '2012-11-10 18:08:34', '2012-11-10 18:09:50', 'utf-8', 'ltr');
