INSERT INTO !PREFIX!_area VALUES ('700', '0', 'mod_rewrite', '1', '1', '1');
INSERT INTO !PREFIX!_area VALUES ('701', 'mod_rewrite', 'mod_rewrite_expert', '1', '1', '1');
INSERT INTO !PREFIX!_area VALUES ('702', 'mod_rewrite', 'mod_rewrite_test', '1', '1', '1');

INSERT INTO !PREFIX!_actions VALUES ('700', '700', '', 'mod_rewrite', '', '', '1');
INSERT INTO !PREFIX!_actions VALUES ('701', '700', '', 'mod_rewrite_expert', '', '', '1');
INSERT INTO !PREFIX!_actions VALUES ('702', '700', '', 'mod_rewrite_test', '', '', '1');

INSERT INTO !PREFIX!_files VALUES ('700', '52', 'include.subnav_blank.php', 'main');
INSERT INTO !PREFIX!_files VALUES ('701', '700', 'mod_rewrite/includes/include.mod_rewrite_content.php', 'main');
INSERT INTO !PREFIX!_files VALUES ('702', '701', 'mod_rewrite/includes/include.mod_rewrite_contentexpert.php', 'main');
INSERT INTO !PREFIX!_files VALUES ('703', '702', 'mod_rewrite/includes/include.mod_rewrite_contenttest.php', 'main');

INSERT INTO !PREFIX!_frame_files VALUES ('700', '52', '3', '700');
INSERT INTO !PREFIX!_frame_files VALUES ('701', '700', '4', '701');
INSERT INTO !PREFIX!_frame_files VALUES ('702', '701', '4', '702');
INSERT INTO !PREFIX!_frame_files VALUES ('703', '702', '4', '703');

INSERT INTO !PREFIX!_nav_sub VALUES('700', '3', '700', '0', 'mod_rewrite/xml/;navigation/extra/mod_rewrite/main', '1');
INSERT INTO !PREFIX!_nav_sub VALUES('701', '0', '700', '1', 'mod_rewrite/xml/;navigation/extra/mod_rewrite/settings', '1');
INSERT INTO !PREFIX!_nav_sub VALUES('702', '0', '701', '1', 'mod_rewrite/xml/;navigation/extra/mod_rewrite/expert', '1');
INSERT INTO !PREFIX!_nav_sub VALUES('703', '0', '702', '1', 'mod_rewrite/xml/;navigation/extra/mod_rewrite/test', '1');
