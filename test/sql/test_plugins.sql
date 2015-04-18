DROP TABLE IF EXISTS `!PREFIX!_plugins`;

CREATE TABLE `test_plugins` (
  `idplugin` int(11) NOT NULL AUTO_INCREMENT,
  `idclient` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `author` varchar(255) NOT NULL,
  `copyright` varchar(255) NOT NULL,
  `mail` varchar(255) NOT NULL,
  `website` varchar(255) NOT NULL,
  `version` varchar(10) NOT NULL,
  `folder` varchar(255) NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `executionorder` int(11) NOT NULL DEFAULT '0',
  `installed` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `active` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`idplugin`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

INSERT INTO `!PREFIX!_plugins` VALUES (1,1,'Smarty Wrapper','Provides smarty template engine for CONTENIDO Backend and Frontend','Bilal Arslan, Andreas Dieter','four for business AG','info@4fb.de','http://www.4fb.de','1.0.0','smarty','82b117e94bb2cbcbce4e56b79a7d0c23',1,'2013-02-14 15:10:51',1),(2,1,'Form Assistant','Generating forms in backend, includes data storage and mailing','Marcus Gnaß (4fb)','four for business AG','marcus.gnass@4fb.de','http://www.4fb.de','1.0.0','form_assistant','34E59F15-606A-81F4-1520-59E86230BE37',2,'2013-02-20 13:24:33',1),(3,1,'User Forum','Administration of user forum entries (Article comments)','Claus Schunk (4fb)','four for business AG','claus.schunk@4fb.de','http://www.4fb.de','1.0.0','user_forum','34E59F15-606A-81F4-1520-59E86230BE38',3,'2013-05-15 14:29:01',1);
