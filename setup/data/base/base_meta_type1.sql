DELETE FROM !PREFIX!_meta_type WHERE idmetatype < 10000;
INSERT INTO !PREFIX!_meta_type VALUES('1', 'author', 'text', '256', 'name');
INSERT INTO !PREFIX!_meta_type VALUES('2', 'date', 'date', '64', 'name');
INSERT INTO !PREFIX!_meta_type VALUES('3', 'description', 'textarea', '48', 'name');
INSERT INTO !PREFIX!_meta_type VALUES('4', 'expires', 'date', '64', 'http-equiv');
INSERT INTO !PREFIX!_meta_type VALUES('5', 'keywords', 'textarea', '48', 'name');
INSERT INTO !PREFIX!_meta_type VALUES('6', 'revisit-after', 'text', '64', 'name');
INSERT INTO !PREFIX!_meta_type VALUES('7', 'robots', 'text', '64', 'name');
INSERT INTO !PREFIX!_meta_type VALUES('8', 'copyright', 'textarea', '100', 'name');