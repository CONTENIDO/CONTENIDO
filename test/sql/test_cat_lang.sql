DROP TABLE IF EXISTS `!PREFIX!_cat_lang`;

CREATE TABLE `!PREFIX!_cat_lang` (
  `idcatlang` int(11) NOT NULL auto_increment,
  `idcat` int(11) NOT NULL default '0',
  `idlang` int(11) NOT NULL default '0',
  `idtplcfg` int(11) NOT NULL default '0',
  `name` varchar(255) NOT NULL,
  `visible` tinyint(1) NOT NULL default '0',
  `public` tinyint(1) NOT NULL default '0',
  `status` int(11) NOT NULL default '0',
  `author` varchar(32) NOT NULL,
  `created` datetime NOT NULL default NOW(),
  `lastmodified` datetime NULL,
  `startidartlang` int(11) NOT NULL default '0',
  `urlname` varchar(64) NOT NULL,
  `urlpath` varchar(255) NOT NULL,
  PRIMARY KEY  (`idcatlang`),
  KEY `idcat` (`idcat`),
  KEY `idlang` (`idlang`),
  KEY `idtplcfg` (`idtplcfg`),
  KEY `idlang_2` (`idlang`,`visible`),
  KEY `idlang_3` (`idlang`,`idcat`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=0;

INSERT INTO `!PREFIX!_cat_lang` (`idcatlang`, `idcat`, `idlang`, `idtplcfg`, `name`, `visible`, `public`, `status`, `author`, `created`, `lastmodified`, `startidartlang`, `urlname`, `urlpath`) VALUES
(1, 1, 1, 2, 'Hauptnavigation', 1, 1, 0, 'sysadmin', '2012-11-10 18:10:44', '2013-02-20 12:19:17', 117, 'Hauptnavigation', ''),
(2, 2, 1, 3, 'Systemseiten', 1, 1, 0, 'sysadmin', '2012-11-10 18:10:58', '2012-11-11 04:59:44', 0, 'Systemseiten', ''),
(4, 4, 1, 12, 'Servicenavigation', 1, 1, 0, 'sysadmin', '2012-11-11 01:42:29', '2013-02-11 17:28:20', 0, 'Servicenavigation', ''),
(5, 5, 1, 13, 'Philosophie', 1, 1, 0, 'sysadmin', '2012-11-11 01:51:33', '2013-02-07 12:01:19', 13, 'Philosophie', ''),
(6, 6, 1, 14, 'Fakten und Funktionen', 1, 1, 0, 'sysadmin', '2012-11-11 01:51:41', '2013-02-14 09:40:30', 47, 'Fakten-und-Funktionen', ''),
(8, 8, 1, 26, 'Features dieser Website', 1, 1, 0, 'sysadmin', '2012-11-11 04:42:33', '2012-11-11 05:12:29', 24, 'features_website', ''),
(9, 9, 1, 27, 'Navigation', 1, 1, 0, 'sysadmin', '2012-11-11 04:44:06', '2012-11-11 05:13:57', 29, 'navigation', ''),
(10, 10, 1, 28, 'Content', 1, 1, 0, 'sysadmin', '2012-11-11 04:44:30', '2012-11-11 05:15:35', 31, 'content', ''),
(11, 11, 1, 29, 'Geschlossener Bereich', 1, 0, 0, 'sysadmin', '2012-11-11 04:45:07', '2013-02-15 15:26:04', 33, 'geschlossener_bereich', ''),
(12, 12, 1, 30, 'Bildergalerie', 1, 1, 0, 'sysadmin', '2012-11-11 04:46:58', '2012-11-11 05:06:07', 19, 'bildergalerie', ''),
(13, 13, 1, 31, 'Teaser', 1, 1, 0, 'sysadmin', '2012-11-11 04:47:12', '2013-02-12 13:08:32', 32, 'teaser', ''),
(44, 43, 1, 64, 'Basissystem', 1, 1, 0, 'sysadmin', '2013-02-11 17:16:38', '2013-02-11 17:17:27', 53, 'Basissystem', ''),
(45, 44, 1, 65, 'Inhaltspflege', 1, 1, 0, 'sysadmin', '2013-02-11 17:16:57', '2013-02-11 17:19:48', 54, 'Inhaltspflege', ''),
(46, 45, 1, 66, 'Plugins', 1, 1, 0, 'sysadmin', '2013-02-11 17:21:59', '2013-02-11 17:23:59', 55, 'Plugins', ''),
(39, 1, 2, 58, 'Main Navigation', 1, 1, 0, 'sysadmin', '2012-11-10 18:10:44', '2013-02-20 12:34:57', 118, 'Main-Navigation', ''),
(25, 25, 1, 43, 'Fehlerseite', 1, 1, 0, 'sysadmin', '2012-11-11 04:51:06', '2012-11-11 05:03:45', 21, 'fehlerseite', ''),
(26, 26, 1, 44, 'Suchergebnisse', 1, 1, 0, 'sysadmin', '2012-11-11 04:51:25', '2012-11-11 05:02:30', 20, 'suchergebnisse', ''),
(27, 27, 1, 45, 'Datenschutz', 1, 1, 0, 'sysadmin', '2012-11-11 04:51:49', '2012-11-11 05:02:01', 18, 'datenschutz', ''),
(28, 28, 1, 46, 'HTML-Newsletter Templates', 1, 1, 0, 'sysadmin', '2012-11-11 04:52:18', '2012-11-11 05:08:49', 17, 'newsletter_templates', ''),
(29, 29, 1, 47, 'HTML-Newsletter', 1, 1, 0, 'sysadmin', '2012-11-11 04:52:37', '2012-11-11 05:27:31', 45, 'newsletter', ''),
(30, 30, 1, 48, 'XML Sitemap', 1, 1, 0, 'sysadmin', '2012-11-11 04:52:55', '2012-11-11 04:58:13', 16, 'sitemap', ''),
(47, 46, 1, 67, 'Dienstleistungen 4fb', 1, 1, 0, 'sysadmin', '2013-02-11 17:32:37', '2013-02-11 17:38:49', 56, 'Dienstleistungen-4fb', ''),
(40, 39, 1, 60, 'Kopfnavigation', 1, 1, 0, 'sysadmin', '2013-01-18 16:14:25', '2013-01-18 16:14:25', 0, 'Kopfnavigation', ''),
(41, 40, 1, 61, 'Kontakt', 1, 1, 0, 'sysadmin', '2013-01-18 16:14:41', '2013-02-20 13:34:07', 51, 'Kontakt', ''),
(42, 41, 1, 91, 'Login', 1, 1, 0, 'sysadmin', '2013-01-18 16:14:49', '2013-02-13 14:16:45', 52, 'Login', ''),
(48, 47, 1, 68, 'Sitemap', 1, 1, 0, 'sysadmin', '2013-02-12 08:39:44', '2013-02-12 08:42:01', 57, 'HTML-Sitemap', ''),
(49, 48, 1, 72, 'Linkliste', 1, 1, 0, 'sysadmin', '2013-02-12 08:46:39', '2013-02-12 08:47:01', 59, 'Linkliste', ''),
(50, 49, 1, 77, 'Downloadliste', 1, 1, 0, 'sysadmin', '2013-02-12 09:08:46', '2013-02-12 09:09:04', 62, 'Downloadliste', ''),
(51, 50, 1, 80, 'Suchergebnisse', 1, 1, 0, 'sysadmin', '2013-02-12 09:14:45', '2013-02-12 09:15:06', 63, 'Suchergebnisse', ''),
(52, 51, 1, 82, 'Implementierung', 1, 1, 0, 'sysadmin', '2013-02-13 09:19:46', '2013-02-13 09:23:26', 65, 'Implementierung', ''),
(53, 52, 1, 83, 'Upgrade', 1, 1, 0, 'sysadmin', '2013-02-13 09:23:37', '2013-02-13 09:25:36', 66, 'Upgrade', ''),
(54, 53, 1, 84, 'Einfach einfach', 1, 1, 0, 'sysadmin', '2013-02-13 09:43:59', '2013-02-13 09:51:28', 69, 'Einfach-einfach', ''),
(55, 54, 1, 85, 'Einfach benutzen', 1, 1, 0, 'sysadmin', '2013-02-13 09:44:48', '2013-02-22 09:17:49', 67, 'Einfach-benutzen', ''),
(56, 55, 1, 86, 'Einfach grenzenlos', 1, 1, 0, 'sysadmin', '2013-02-13 09:45:37', '2013-02-22 09:17:47', 68, 'Einfach-grenzenlos', ''),
(57, 56, 1, 90, 'Blog', 1, 1, 0, 'sysadmin', '2013-02-13 14:16:08', '2013-02-14 13:08:03', 77, 'Blog', ''),
(58, 57, 1, 92, 'Newsletter', 1, 1, 0, 'sysadmin', '2013-02-13 14:19:06', '2013-02-13 14:19:24', 71, 'Newsletter', ''),
(59, 5, 2, 98, 'Philosophy', 1, 1, 0, 'sysadmin', '2012-11-11 01:51:33', '2013-02-20 09:02:28', 78, 'Philosophy', ''),
(60, 54, 2, 99, 'Just Publish', 1, 1, 0, 'sysadmin', '2013-02-13 09:44:48', '2013-02-20 09:02:37', 79, 'Just-Publish', ''),
(64, 44, 2, 103, 'Content', 1, 1, 0, 'sysadmin', '2013-02-11 17:16:57', '2013-02-21 11:41:40', 83, 'Content', ''),
(61, 55, 2, 100, 'Just Unlimited', 1, 1, 0, 'sysadmin', '2013-02-13 09:45:37', '2013-02-20 09:02:45', 80, 'Just-Unlimited', ''),
(62, 53, 2, 101, 'Just Simple', 1, 1, 0, 'sysadmin', '2013-02-13 09:43:59', '2013-02-21 11:35:06', 81, 'Just-Simple', ''),
(63, 6, 2, 102, 'Facts and Functions', 1, 1, 0, 'sysadmin', '2012-11-11 01:51:41', '2013-02-21 12:32:22', 82, 'Facts-and-Functions', ''),
(65, 43, 2, 104, 'Basis System', 1, 1, 0, 'sysadmin', '2013-02-11 17:16:38', '2013-02-21 17:29:09', 84, 'Basis-System', ''),
(66, 45, 2, 105, 'Plugins', 1, 1, 0, 'sysadmin', '2013-02-11 17:21:59', '2013-02-20 09:20:39', 85, 'Plugins', ''),
(67, 8, 2, 106, 'Features', 1, 1, 0, 'sysadmin', '2012-11-11 04:42:33', '2013-02-20 09:35:50', 86, 'Features', ''),
(68, 9, 2, 107, 'Navigation', 1, 1, 0, 'sysadmin', '2012-11-11 04:44:06', '2013-02-20 09:27:52', 87, 'navigation', ''),
(69, 10, 2, 108, 'Content', 1, 1, 0, 'sysadmin', '2012-11-11 04:44:30', '2013-02-20 09:28:18', 88, 'content', ''),
(70, 39, 2, 109, 'Head navigation', 0, 1, 0, 'sysadmin', '2013-01-18 16:14:25', '2013-02-20 09:36:02', 0, 'Head-navigation', ''),
(71, 40, 2, 110, 'Contact', 1, 1, 0, 'sysadmin', '2013-01-18 16:14:41', '2013-02-20 09:36:12', 89, 'Contact', ''),
(72, 47, 2, 112, 'Sitemap', 1, 1, 0, 'sysadmin', '2013-02-12 08:39:44', '2013-02-20 09:29:05', 90, 'HTML-Sitemap', ''),
(73, 41, 2, 114, 'Login', 1, 1, 0, 'sysadmin', '2013-01-18 16:14:49', '2013-02-20 09:29:20', 91, 'Login', ''),
(74, 4, 2, 115, 'Servicenavigation', 0, 1, 0, 'sysadmin', '2012-11-11 01:42:29', '2013-02-20 11:50:09', 0, 'Servicenavigation', ''),
(75, 2, 2, 116, 'System Pages', 0, 1, 0, 'sysadmin', '2012-11-10 18:10:58', '2013-02-20 09:36:41', 0, 'System-Pages', ''),
(76, 25, 2, 119, 'Error page', 1, 1, 0, 'sysadmin', '2012-11-11 04:51:06', '2013-02-20 09:36:50', 0, 'Error-page', ''),
(77, 26, 2, 120, 'Search results', 1, 1, 0, 'sysadmin', '2012-11-11 04:51:25', '2013-02-20 09:37:00', 101, 'Search-results', ''),
(78, 27, 2, 122, 'Privacy', 1, 1, 0, 'sysadmin', '2012-11-11 04:51:49', '2013-02-20 09:37:22', 102, 'Privacy', ''),
(81, 13, 2, 126, 'Teaser', 1, 1, 0, 'sysadmin', '2012-11-11 04:47:12', '2013-02-20 09:41:06', 108, 'teaser', ''),
(79, 30, 2, 123, 'XML Sitemap', 1, 1, 0, 'sysadmin', '2012-11-11 04:52:55', '2013-02-20 09:33:28', 103, 'sitemap', ''),
(80, 50, 2, 124, 'Search results', 1, 1, 0, 'sysadmin', '2013-02-12 09:14:45', '2013-02-20 09:37:33', 104, 'Search-results', ''),
(82, 12, 2, 128, 'Picture Gallery', 1, 1, 0, 'sysadmin', '2012-11-11 04:46:58', '2013-02-20 09:45:04', 109, 'Picture-Gallery', ''),
(83, 11, 2, 130, 'Protected Area', 1, 0, 0, 'sysadmin', '2012-11-11 04:45:07', '2013-02-20 09:45:18', 110, 'Protected-Area', ''),
(84, 48, 2, 131, 'Link list', 1, 1, 0, 'sysadmin', '2013-02-12 08:46:39', '2013-02-20 15:54:48', 111, 'Link-list', ''),
(85, 49, 2, 133, 'Download list', 1, 1, 0, 'sysadmin', '2013-02-12 09:08:46', '2013-02-21 17:53:43', 112, 'Download-list', ''),
(86, 56, 2, 135, 'Blog', 1, 1, 0, 'sysadmin', '2013-02-13 14:16:08', '2013-02-20 10:02:19', 113, 'Blog', '');