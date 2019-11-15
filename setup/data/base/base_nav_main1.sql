DELETE FROM `!PREFIX!_nav_main` WHERE idnavm < 10000;
INSERT INTO `!PREFIX!_nav_main` (idnavm, name, location) VALUES ('1', 'content', 'navigation/content/main');
INSERT INTO `!PREFIX!_nav_main` (idnavm, name, location) VALUES ('2', 'style', 'navigation/style/main');
INSERT INTO `!PREFIX!_nav_main` (idnavm, name, location) VALUES ('4', 'statistic', 'navigation/statistic/main');
INSERT INTO `!PREFIX!_nav_main` (idnavm, name, location) VALUES ('5', 'administration', 'navigation/administration/main');
INSERT INTO `!PREFIX!_nav_main` (idnavm, name, location) VALUES ('3', 'extra', 'navigation/extra/main');
