CREATE TABLE `!PREFIX!_pi_user_forum` (`id_user_forum` int(11) NOT NULL auto_increment,`id_user_forum_parent` int(11) NOT NULL,`idart` int(11) NOT NULL default '0',`idcat` int(11) NOT NULL default '0',`idlang` int(5) NOT NULL default '0',`userid` int(6) NOT NULL default '0',`email` varchar(100) NOT NULL default '',`realname` varchar(50) NOT NULL default '',`forum` mediumtext NOT NULL,`forum_quote` mediumtext NOT NULL,`like` int(11) NOT NULL,`dislike` int(11) NOT NULL,`editedat` datetime NOT NULL default '0000-00-00 00:00:00',`editedby` varchar(50) NOT NULL default '',`timestamp` datetime NOT NULL default '0000-00-00 00:00:00',`ipaddress` varchar(32) NOT NULL default '',PRIMARY KEY  (`id_user_forum`)) ENGINE=MyISAM  AUTO_INCREMENT=1;
INSERT INTO `!PREFIX!_plugins` VALUES(1, 1, 'Smarty Wrapper', 'Provides smarty template engine for CONTENIDO Backend and Frontend', 'Bilal Arslan, Andreas Dieter', 'four for business AG', 'info@4fb.de', 'http://www.4fb.de', '1.0.0', 'smarty', '82b117e94bb2cbcbce4e56b79a7d0c23', '2013-02-14 15:10:51', 1);
INSERT INTO `!PREFIX!_plugins` VALUES(2, 1, 'Form Assistant', 'Generating forms in backend, includes data storage and mailing', 'Marcus Gnaß (4fb)', 'four for business AG', 'marcus.gnass@4fb.de', 'http://www.4fb.de', '1.0.0', 'form_assistant', '34E59F15-606A-81F4-1520-59E86230BE37', '2013-02-20 13:24:33', 1);
CREATE TABLE `!PREFIX!_pifa_contact` (`id` int(10) unsigned NOT NULL auto_increment COMMENT 'primary key',`salutation` varchar(255) default NULL,`first_name` varchar(255) default NULL,`last_name` varchar(255) default NULL,`company` varchar(255) default NULL,`street` varchar(255) default NULL,`street_number` varchar(255) default NULL,`plz` varchar(255) default NULL,`city` varchar(255) default NULL,`phone` varchar(255) default NULL,`email` varchar(255) default NULL,`message` text,`privacy` varchar(255) default NULL,PRIMARY KEY  (`id`)) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=11 ;
CREATE TABLE `!PREFIX!_pifa_field` (`idfield` int(10) unsigned NOT NULL auto_increment COMMENT 'unique identifier for a ConForm field',`idform` int(10) unsigned NOT NULL default '0' COMMENT 'foreign key for the ConForm form',`field_rank` int(10) unsigned NOT NULL default '0' COMMENT 'rank of a field in a form',`field_type` int(10) unsigned NOT NULL default '0' COMMENT 'id which defines type of form field',`column_name` varchar(64) NOT NULL COMMENT 'name of data table column to store values',`label` varchar(1023) default NULL COMMENT 'label to be shown in frontend',`display_label` int(1) NOT NULL default '0' COMMENT '1 means that the label will be displayed',`default_value` varchar(1023) default NULL COMMENT 'default value to be shown for form field',`option_labels` varchar(1023) default NULL COMMENT 'CSV of option labels',`option_values` varchar(1023) default NULL COMMENT 'CSV of option values',`option_class` varchar(1023) default NULL COMMENT 'class implementing external datasource',`help_text` text COMMENT 'help text to be shown for form field',`obligatory` int(1) NOT NULL default '0' COMMENT '1 means that a value is obligatory',`rule` varchar(1023) default NULL COMMENT 'regular expression to validate value',`error_message` varchar(1023) default NULL COMMENT 'error message to be shown for an invalid value',`css_class` varchar(1023) default NULL COMMENT 'CSS classes to be used for field wrapper',PRIMARY KEY  (`idfield`)) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COMMENT='contains meta data of PIFA fields' AUTO_INCREMENT=1 ;
INSERT INTO `!PREFIX!_pifa_field` VALUES(4, 1, 1, 6, 'salutation', 'Anrede', 1, NULL, 'Bitte wählen,Frau,Herr', ',Mrs,Mr', NULL, NULL, 1, NULL, 'Bitte wählen Sie die Anrede aus', NULL);
INSERT INTO `!PREFIX!_pifa_field` VALUES(5, 1, 2, 1, 'first_name', 'Vorname', 1, NULL, NULL, NULL, NULL, NULL, 1, NULL, 'Bitte geben Sie Ihren Vornamen ein', NULL);
INSERT INTO `!PREFIX!_pifa_field` VALUES(6, 1, 3, 1, 'last_name', 'Nachname', 1, NULL, NULL, NULL, NULL, NULL, 1, NULL, 'Bitte geben Sie Ihren Nachnamen ein', NULL);
INSERT INTO `!PREFIX!_pifa_field` VALUES(7, 1, 4, 1, 'company', 'Firma', 1, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL);
INSERT INTO `!PREFIX!_pifa_field` VALUES(8, 1, 5, 1, 'street', 'Straße', 1, NULL, NULL, NULL, NULL, NULL, 1, NULL, 'Bitte geben Sie die Straße ein', NULL);
INSERT INTO `!PREFIX!_pifa_field` VALUES(9, 1, 6, 1, 'street_number', 'Hausnummer', 1, NULL, NULL, NULL, NULL, NULL, 1, NULL, 'Bitte geben Sie die Hausnummer ein', NULL);
INSERT INTO `!PREFIX!_pifa_field` VALUES(10, 1, 7, 1, 'plz', 'Postleitzahl', 1, NULL, NULL, NULL, NULL, NULL, 1, NULL, 'Bitte geben Sie die Postleitzahl ein', NULL);
INSERT INTO `!PREFIX!_pifa_field` VALUES(11, 1, 8, 1, 'city', 'Ort', 1, NULL, NULL, NULL, NULL, NULL, 1, NULL, 'Bitte geben Sie den Ort ein', NULL);
INSERT INTO `!PREFIX!_pifa_field` VALUES(12, 1, 10, 1, 'phone', 'Telefon', 1, NULL, NULL, NULL, NULL, NULL, 1, NULL, 'Bitte geben Sie die Telefonnummer ein', NULL);
INSERT INTO `!PREFIX!_pifa_field` VALUES(13, 1, 11, 1, 'email', 'E-Mail-Adresse', 1, NULL, NULL, NULL, NULL, NULL, 1, NULL, 'Bitte geben Sie die E-Mail Adresse ein', NULL);
INSERT INTO `!PREFIX!_pifa_field` VALUES(14, 1, 9, 2, 'message', 'Ihre Nachricht', 1, NULL, NULL, NULL, NULL, NULL, 1, NULL, 'Bitte geben Sie die Nachricht ein', NULL);
INSERT INTO `!PREFIX!_pifa_field` VALUES(16, 1, 13, 13, '', 'Absenden', 1, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 'button_red');
INSERT INTO `!PREFIX!_pifa_field` VALUES(17, 1, 14, 14, '', 'Zurücksetzen', 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 'button_grey');
INSERT INTO `!PREFIX!_pifa_field` VALUES(18, 1, 12, 5, 'privacy', 'Datenschutzerklärung', 1, NULL, 'Ich aktzeptiere die Datenschutzerklärung', '1', NULL, NULL, 1, NULL, 'Bitte bestätigen Sie die Datenschutzerklärung', 'privacy');
CREATE TABLE `!PREFIX!_pifa_form` (`idform` int(10) unsigned NOT NULL auto_increment COMMENT 'unique identifier for a ConForm form',`idclient` int(10) unsigned NOT NULL default '0' COMMENT 'id of form client',`idlang` int(10) unsigned NOT NULL default '0' COMMENT 'id of form language',`name` varchar(1023) NOT NULL default 'new form' COMMENT 'human readable name of form',`data_table` varchar(64) NOT NULL default 'con_pifo_data' COMMENT 'unique name of data table',`method` enum('get','post') NOT NULL default 'post' COMMENT 'method to be used for form submission',PRIMARY KEY  (`idform`)) ENGINE=MyISAM COMMENT='contains meta data of PIFA forms' AUTO_INCREMENT=1 ;
INSERT INTO `!PREFIX!_pifa_form` VALUES(1, 1, 1, 'contact', 'con_pifa_contact', 'post');
INSERT INTO `!PREFIX!_area` (idarea, parent_id, name, relevant, online, menuless) VALUES (100001, '0', 'form', 1, 1, 0);
INSERT INTO `!PREFIX!_area` (idarea, parent_id, name, relevant, online, menuless) VALUES (100002, '100001', 'form_ajax', 1, 1, 0);
INSERT INTO `!PREFIX!_nav_sub` (idnavs, idnavm, idarea, level, location, online) VALUES (100001, 3, 100001, 0, 'form_assistant/xml/lang_de_DE.xml;plugins/form_assistant/label', 1);
INSERT INTO `!PREFIX!_files` (idfile, idarea, filename, filetype) VALUES (100001, 100001, 'form_assistant/includes/include.left_top.php', 'main');
INSERT INTO `!PREFIX!_files` (idfile, idarea, filename, filetype) VALUES (100002, 100001, 'form_assistant/includes/include.left_bottom.php', 'main');
INSERT INTO `!PREFIX!_files` (idfile, idarea, filename, filetype) VALUES (100003, 100001, 'form_assistant/includes/include.right_top.php', 'main');
INSERT INTO `!PREFIX!_files` (idfile, idarea, filename, filetype) VALUES (100004, 100001, 'form_assistant/includes/include.right_bottom.php', 'main');
INSERT INTO `!PREFIX!_files` (idfile, idarea, filename, filetype) VALUES (100005, 100002, 'form_assistant/includes/include.ajax.php', 'main');
INSERT INTO `!PREFIX!_frame_files` (idframefile, idarea, idframe, idfile) VALUES (100001, 100001, 1, 100001);
INSERT INTO `!PREFIX!_frame_files` (idframefile, idarea, idframe, idfile) VALUES (100002, 100001, 2, 100002);
INSERT INTO `!PREFIX!_frame_files` (idframefile, idarea, idframe, idfile) VALUES (100003, 100001, 3, 100003);
INSERT INTO `!PREFIX!_frame_files` (idframefile, idarea, idframe, idfile) VALUES (100004, 100001, 4, 100004);
INSERT INTO `!PREFIX!_frame_files` (idframefile, idarea, idframe, idfile) VALUES (100005, 100002, 4, 100005);
INSERT INTO `!PREFIX!_type` (idtype, `type`, code, description, status, author, created, lastmodified) VALUES ('100001', 'CMS_PIFAFORM', '', 'PIFA form', '0', '', NOW(), NOW());