-- author: Marcus Gnaß <marcus.gnass@4fb.de>
-- description: install script for PlugIn: FormAssistant (PIFA)

-- areas
INSERT IGNORE
    INTO con_area (idarea, parent_id, name, relevant, online, menuless)
    VALUES (100001, '0', 'form', 1, 1, 0);
INSERT IGNORE
    INTO con_area (idarea, parent_id, name, relevant, online, menuless)
    VALUES (100002, '100001', 'form_ajax', 1, 1, 0);

-- navigation
INSERT IGNORE
    INTO con_nav_main (idnavm ,location)
    VALUES (100001 , 'form_assistant/xml/lang_de_DE.xml;plugins/label');
INSERT IGNORE
    INTO con_nav_sub (idnavs, idnavm, idarea, level, location, online)
    VALUES (100001, 100001, 100001, 0, 'form_assistant/xml/lang_de_DE.xml;plugins/form_assistant/label', 1);

-- files
INSERT IGNORE
    INTO con_files (idfile, idarea, filename, filetype)
    VALUES (100001, 100001, 'form_assistant/includes/include.left_top.php', 'main');
INSERT IGNORE
    INTO con_files (idfile, idarea, filename, filetype)
    VALUES (100002, 100001, 'form_assistant/includes/include.left_bottom.php', 'main');
INSERT IGNORE
    INTO con_files (idfile, idarea, filename, filetype)
    VALUES (100003, 100001, 'form_assistant/includes/include.right_top.php', 'main');
INSERT IGNORE
    INTO con_files (idfile, idarea, filename, filetype)
    VALUES (100004, 100001, 'form_assistant/includes/include.right_bottom.php', 'main');
INSERT IGNORE
    INTO con_files (idfile, idarea, filename, filetype)
    VALUES (100005, 100002, 'form_assistant/includes/include.ajax.php', 'main');

-- mapping of files to frames
INSERT
    INTO con_frame_files (idframefile, idarea, idframe, idfile)
    VALUES (100001, 100001, 1, 100001);
INSERT
    INTO con_frame_files (idframefile, idarea, idframe, idfile)
    VALUES (100002, 100001, 2, 100002);
INSERT
    INTO con_frame_files (idframefile, idarea, idframe, idfile)
    VALUES (100003, 100001, 3, 100003);
INSERT
    INTO con_frame_files (idframefile, idarea, idframe, idfile)
    VALUES (100004, 100001, 4, 100004);
INSERT
    INTO con_frame_files (idframefile, idarea, idframe, idfile)
    VALUES (100005, 100002, 4, 100005);

/*
INSERT
    INTO `con_actions` (`idaction`, `idarea`, `alt_name`, `name`, `code`, `location`, `relevant`)
    VALUES (100001, 100001, '', '', '', '', 1);
*/

-- create record for of CMS_PIFAFORM content type
INSERT
    INTO con_type (idtype, `type`, code, description, status, author, created, lastmodified)
    VALUES ('100001', 'CMS_PIFAFORM', '', 'PIFA form', '0', '', NOW(), NOW());

-- create table for meta data of PIFA forms
CREATE TABLE con_pifa_form (
  idform         int(10) unsigned    NOT NULL AUTO_INCREMENT             COMMENT 'unique identifier for a ConForm form',
  idclient       int(10) unsigned    NOT NULL DEFAULT '0'                COMMENT 'id of form client',
  idlang         int(10) unsigned    NOT NULL DEFAULT '0'                COMMENT 'id of form language',
  name           varchar(1023)       NOT NULL DEFAULT 'new form'         COMMENT 'human readable name of form',
  data_table     varchar(64)         NOT NULL DEFAULT 'con_pifo_data'    COMMENT 'unique name of data table',
  method         enum('get','post')  NOT NULL DEFAULT 'post'             COMMENT 'method to be used for form submission',
  with_timestamp BOOLEAN            NOT NULL DEFAULT '1'                COMMENT 'if data table records have a timestamp',
  PRIMARY KEY  (idform)
) ENGINE=MyISAM
DEFAULT CHARSET=utf8
COMMENT='contains meta data of PIFA forms'
AUTO_INCREMENT=1;

-- create table for meta data of PIFA fields
CREATE TABLE IF NOT EXISTS con_pifa_field (
  idfield        int(10) unsigned    NOT NULL AUTO_INCREMENT COMMENT 'unique identifier for a ConForm field',
  idform         int(10) unsigned    NOT NULL DEFAULT '0'    COMMENT 'foreign key for the ConForm form',
  field_rank     int(10) unsigned    NOT NULL DEFAULT '0'    COMMENT 'rank of a field in a form',
  field_type     int(10) unsigned    NOT NULL DEFAULT '0'    COMMENT 'id which defines type of form field',
  column_name    varchar(64)         NOT NULL                COMMENT 'name of data table column to store values',
  label          varchar(1023)       DEFAULT NULL            COMMENT 'label to be shown in frontend',
  display_label  int(1)              NOT NULL DEFAULT '0'    COMMENT '1 means that the label will be displayed',
  default_value  varchar(1023)       DEFAULT NULL            COMMENT 'default value to be shown for form field',
  option_labels  varchar(1023)       DEFAULT NULL            COMMENT 'CSV of option labels',
  option_values  varchar(1023)       DEFAULT NULL            COMMENT 'CSV of option values',
  option_class   varchar(1023)       DEFAULT NULL            COMMENT 'class implementing external datasource',
  help_text      text                DEFAULT NULL            COMMENT 'help text to be shown for form field',
  obligatory     int(1)              NOT NULL DEFAULT '0'    COMMENT '1 means that a value is obligatory',
  rule           varchar(1023)       DEFAULT NULL            COMMENT 'regular expression to validate value',
  error_message  varchar(1023)       DEFAULT NULL            COMMENT 'error message to be shown for an invalid value',
  css_class      varchar(1023)       DEFAULT NULL            COMMENT 'CSS classes to be used for field wrapper',
  PRIMARY KEY  (idfield)
) ENGINE=MyISAM
DEFAULT CHARSET=utf8
COMMENT='contains meta data of PIFA fields'
AUTO_INCREMENT=1;
