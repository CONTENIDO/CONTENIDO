INSERT INTO !PREFIX!_actions VALUES('950', '950', '','cronjob_overview','','','1');
INSERT INTO !PREFIX!_actions VALUES('951', '950', '','crontab_edit', '', '', '1');
INSERT INTO !PREFIX!_actions VALUES('952', '950', '','cronjob_execute', '','', '1');

INSERT INTO !PREFIX!_area VALUES ('950', '0', 'cronjob', '1', '1','0');

INSERT INTO !PREFIX!_files VALUES ('951', '950', 'cronjobs_overview/includes/include.left_top.php', 'main');
INSERT INTO !PREFIX!_files VALUES ('952', '950', 'cronjobs_overview/includes/include.left_bottom.php', 'main');
INSERT INTO !PREFIX!_files VALUES ('953', '950', 'cronjobs_overview/includes/include.right_top.php', 'main');
INSERT INTO !PREFIX!_files VALUES ('954', '950', 'cronjobs_overview/includes/include.right_bottom.php', 'main');


INSERT INTO !PREFIX!_frame_files VALUES ('951', '950', '1', '951');
INSERT INTO !PREFIX!_frame_files VALUES ('952', '950', '2', '952');
INSERT INTO !PREFIX!_frame_files VALUES ('953', '950', '3', '953');
INSERT INTO !PREFIX!_frame_files VALUES ('954', '950', '4', '954');

INSERT INTO !PREFIX!_nav_sub VALUES ('950', '5', '950', '0', 'cronjobs_overview/xml/cronjobs_overview.xml;plugin/cronjob', '1');
