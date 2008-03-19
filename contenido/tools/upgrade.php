<?php

/*****************************************
* File      :   upgrade.php
* Project   :   Contenido
* Descr     :   Contenido upgrade script
*
* Authors   :   Timo A. Hummel
*
* Created   :   20.06.2003
* Modified  :   20.06.2003
*
* © four for business AG, www.4fb.de
******************************************/

class DB_Upgrade extends DB_Sql {

  var $Host;
  var $Database;
  var $User;
  var $Password;

  var $Halt_On_Error = "report";

  //Konstruktor
  function DB_Upgrade()
  {
  	  global $setup_host, $setup_database, $setup_user, $setup_password;
      $this -> Host = $setup_host;
      $this -> Database = $setup_database;
      $this -> User = $setup_user;
      $this -> Password = $setup_password;
  }

  function haltmsg($msg) {
    $fp = fopen("logs/install.log.txt", "ab+");

    if (!$fp)
    {
    	die("Could not open file install.log.txt in directory ".getcwd());
	}
    $msg = sprintf("%s: error %s (%s) - %s\n",
      date("Y-M-D H:i:s"),
      $this->Errno,
      $this->Error,
      $msg);
     echo $msg;
    fputs($fp, $msg);
    fclose($fp);
  }

  function copyResultToArray ()
  {
  		$values = array();

  		$metadata = $this->metadata();

		if (!is_array($metadata))
		{
			return false;
		}

		foreach ($metadata as $entry)
		{
			$values[$entry['name']] = $this->f($entry['name']);
		}

		return $values;
  }
}



$db = new DB_Upgrade;


# Create Contenido classes
//$notification = new Contenido_Notification;

dbUpgradeTable($db, $prefix."_art", 'idart', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_art", 'idclient', 'int(10)', '', 'MUL', '0', '','');

dbUpgradeTable($db, $prefix."_art_lang", 'idartlang', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_art_lang", 'idart', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'idlang', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'idtplcfg', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'title', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'pagetitle', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'summary', 'text', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'artspec', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'created', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'lastmodified', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'author', 'varchar(32)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'modifiedby', 'varchar(32)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'published', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'publishedby', 'varchar(32)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'online', 'tinyint(1)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'redirect', 'int(6)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'redirect_url', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'artsort', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'timemgmt', 'tinyint(1)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'datestart', 'datetime', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'dateend', 'datetime', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'status', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'free_use_01', 'mediumint(7)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'free_use_02', 'mediumint(7)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'free_use_03', 'mediumint(7)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'time_move_cat', 'mediumint(7)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'time_target_cat', 'mediumint(7)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'time_online_move', 'mediumint(7)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'external_redirect', 'char(1)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_art_lang", 'locked', 'int(1)', '', '', '0', '','');

dbUpgradeTable($db, $prefix."_cat", 'idcat', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_cat", 'idclient', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_cat", 'parentid', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_cat", 'preid', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_cat", 'postid', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_cat", 'status', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_cat", 'author', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_cat", 'created', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_cat", 'lastmodified', 'datetime', '', '', '0000-00-00 00:00:00', '','');

dbUpgradeTable($db, $prefix."_cat_art", 'idcatart', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_cat_art", 'idcat', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_cat_art", 'idart', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_cat_art", 'is_start', 'tinyint(1)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_cat_art", 'status', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_cat_art", 'author', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_cat_art", 'created', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_cat_art", 'lastmodified', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_cat_art", 'createcode', 'tinyint(1)', '', '', '1', '','');

dbUpgradeTable($db, $prefix."_cat_tree", 'idtree', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_cat_tree", 'idcat', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_cat_tree", 'level', 'int(2)', '', '', '0', '','');

dbUpgradeTable($db, $prefix."_cat_lang", 'idcatlang', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_cat_lang", 'idcat', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_cat_lang", 'idlang', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_cat_lang", 'idtplcfg', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_cat_lang", 'name', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_cat_lang", 'visible', 'tinyint(1)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_cat_lang", 'public', 'tinyint(1)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_cat_lang", 'status', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_cat_lang", 'author', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_cat_lang", 'created', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_cat_lang", 'lastmodified', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_cat_lang", 'startidartlang', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_cat_lang", 'urlname', 'varchar(64)', '', '', '', '','');

dbUpgradeTable($db, $prefix."_chartable", 'encoding', 'varchar(32)', '', '', 'iso-8859-1', '','');
dbUpgradeTable($db, $prefix."_chartable", 'charid', 'tinyint(1) unsigned', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_chartable", 'normalized_char', 'varchar(5)', '', '', '0', '','');

dbUpgradeTable($db, $prefix."_clients", 'idclient', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_clients", 'name', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_clients", 'author', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_clients", 'created', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_clients", 'lastmodified', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_clients", 'path', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_clients", 'frontendpath', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_clients", 'htmlpath', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_clients", 'errsite_cat', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_clients", 'errsite_art', 'int(10)', '', '', '0', '','');

dbUpgradeTable($db, $prefix."_clients_lang", 'idclientslang', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_clients_lang", 'idclient', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_clients_lang", 'idlang', 'int(10)', '', '', '0', '','');

dbUpgradeTable($db, $prefix."_code", 'idcode', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_code", 'idcatart', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_code", 'idlang', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_code", 'idclient', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_code", 'code', 'longtext', '', '', '', '','');

dbUpgradeTable($db, $prefix."_content", 'idcontent', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_content", 'idartlang', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_content", 'idtype', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_content", 'typeid', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_content", 'value', 'longtext', '', '', '', '','');
dbUpgradeTable($db, $prefix."_content", 'version', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_content", 'author', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_content", 'created', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_content", 'lastmodified', 'datetime', '', '', '0000-00-00 00:00:00', '','');

dbUpgradeTable($db, $prefix."_lang", 'idlang', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_lang", 'name', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_lang", 'active', 'tinyint(1)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_lang", 'author', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_lang", 'created', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_lang", 'lastmodified', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_lang", 'encoding', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_lang", 'direction', 'char(3)', '', '', 'ltr', '','');

dbUpgradeTable($db, $prefix."_lay", 'idlay', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_lay", 'idclient', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_lay", 'name', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_lay", 'description', 'text', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_lay", 'deletable', 'tinyint(1)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_lay", 'code', 'text', '', '', '', '','');
dbUpgradeTable($db, $prefix."_lay", 'author', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_lay", 'created', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_lay", 'lastmodified', 'datetime', '', '', '0000-00-00 00:00:00', '','');

dbUpgradeTable($db, $prefix."_mod", 'idmod', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_mod", 'idclient', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_mod", 'name', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_mod", 'type', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_mod", 'description', 'text', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_mod", 'deletable', 'tinyint(1)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_mod", 'input', 'longtext', '', '', '', '','');
dbUpgradeTable($db, $prefix."_mod", 'output', 'longtext', '', '', '', '','');
dbUpgradeTable($db, $prefix."_mod", 'template', 'text', '', '', '', '','');
dbUpgradeTable($db, $prefix."_mod", 'static', 'tinyint(1)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_mod", 'author', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_mod", 'created', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_mod", 'lastmodified', 'datetime', '', '', '0000-00-00 00:00:00', '','');

dbUpgradeTable($db, $prefix."_news", 'idnews', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_news", 'idart', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_news", 'idclient', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_news", 'idlang', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_news", 'welcome', 'tinyint(1)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_news", 'name', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_news", 'subject', 'text', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_news", 'message', 'text', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_news", 'newsfrom', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_news", 'newsdate', 'datetime', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_news", 'author', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_news", 'created', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_news", 'modified', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_news", 'modifiedby', 'varchar(32)', '', '', '', '','');

dbUpgradeTable($db, $prefix."_news_rcp", 'idnewsrcp', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_news_rcp", 'idclient', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_news_rcp", 'idlang', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_news_rcp", 'email', 'varchar(255)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_news_rcp", 'confirmed', 'tinyint(1)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_news_rcp", 'confirmeddate', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_news_rcp", 'lastaction', 'varchar(32)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_news_rcp", 'name', 'varchar(255)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_news_rcp", 'hash', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_news_rcp", 'deactivated', 'int(1)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_news_rcp", 'author', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_news_rcp", 'created', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_news_rcp", 'lastmodified', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_news_rcp", 'modifiedby', 'varchar(32)', '', '', '', '','');

dbUpgradeTable($db, $prefix."_news_groups", 'idnewsgroup', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_news_groups", 'idclient', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_news_groups", 'idlang', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_news_groups", 'groupname', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_news_groups", 'defaultgroup', 'tinyint(1)', '', '', '0', '','');

dbUpgradeTable($db, $prefix."_news_groupmembers", 'idnewsgroupmember', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_news_groupmembers", 'idnewsgroup', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_news_groupmembers", 'idnewsrcp', 'int(10)', '', '', '0', '','');

dbUpgradeTable($db, $prefix."_stat", 'idstat', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_stat", 'idcatart', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_stat", 'idlang', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_stat", 'idclient', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_stat", 'visited', 'int(6)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_stat", 'visitdate', 'varchar(14)', 'YES', '', '', '','');

dbUpgradeTable($db, $prefix."_stat_archive", 'idstatarch', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_stat_archive", 'archived', 'varchar(6)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_stat_archive", 'idcatart', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_stat_archive", 'idlang', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_stat_archive", 'idclient', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_stat_archive", 'visited', 'int(6)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_stat_archive", 'visitdate', 'varchar(14)', 'YES', '', '', '','');

dbUpgradeTable($db, $prefix."_status", 'idstatus', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_status", 'description', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_status", 'author', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_status", 'created', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_status", 'lastmodified', 'datetime', '', '', '0000-00-00 00:00:00', '','');

dbUpgradeTable($db, $prefix."_template", 'idclient', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_template", 'idlay', 'int(10)', 'YES', 'MUL', '', '','');
dbUpgradeTable($db, $prefix."_template", 'idtpl', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_template", 'idtplcfg', 'int(10)', 'YES', 'MUL', '', '','');
dbUpgradeTable($db, $prefix."_template", 'name', 'varchar(255)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_template", 'description', 'varchar(255)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_template", 'deletable', 'tinyint(1)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_template", 'status', 'int(10)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_template", 'defaulttemplate', 'tinyint(1)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_template", 'author', 'varchar(32)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_template", 'created', 'varchar(14)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_template", 'lastmodified', 'timestamp(14)', 'YES', '', '', '','');

dbUpgradeTable($db, $prefix."_template_conf", 'idtplcfg', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_template_conf", 'idtpl', 'int(10)', 'YES', 'MUL', '', '','');
dbUpgradeTable($db, $prefix."_template_conf", 'status', 'int(10)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_template_conf", 'author', 'varchar(255)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_template_conf", 'created', 'varchar(14)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_template_conf", 'lastmodified', 'timestamp(14)', 'YES', '', '', '','');

dbUpgradeTable($db, $prefix."_type", 'idtype', 'int(6)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_type", 'type', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_type", 'code', 'text', '', '', '', '','');
dbUpgradeTable($db, $prefix."_type", 'description', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_type", 'status', 'int(11)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_type", 'author', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_type", 'created', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_type", 'lastmodified', 'datetime', '', '', '0000-00-00 00:00:00', '','');

dbUpgradeTable($db, $prefix."_upl", 'idupl', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_upl", 'idclient', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_upl", 'filename', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_upl", 'dirname', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_upl", 'filetype', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_upl", 'size', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_upl", 'description', 'text', '', '', '', '','');
dbUpgradeTable($db, $prefix."_upl", 'status', 'int(11)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_upl", 'author', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_upl", 'created', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_upl", 'lastmodified', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_upl", 'modifiedby', 'varchar(32)', '', '', '', '','');

dbUpgradeTable($db, $prefix."_keywords", 'idkeyword', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_keywords", 'keyword', 'varchar(50)', '', 'MUL', '', '',' ');
dbUpgradeTable($db, $prefix."_keywords", 'exp', 'text', '', '', '', '','');
dbUpgradeTable($db, $prefix."_keywords", 'auto', 'text', '', '', '', '','');
dbUpgradeTable($db, $prefix."_keywords", 'self', 'text', '', '', '', '','');
dbUpgradeTable($db, $prefix."_keywords", 'idlang', 'int(10)', '', 'MUL', '0', '','');

dbUpgradeTable($db, $prefix."_area", 'idarea', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_area", 'parent_id', 'varchar(255)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_area", 'name', 'varchar(255)', '', 'MUL', '', '','');
dbUpgradeTable($db, $prefix."_area", 'relevant', 'tinyint(1)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_area", 'online', 'tinyint(1)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_area", 'menuless', 'tinyint(1)', '', '', '0', '','');

dbUpgradeTable($db, $prefix."_actions", 'idaction', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_actions", 'idarea', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_actions", 'alt_name', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_actions", 'name', 'varchar(255)', '', 'MUL', '', '','');
dbUpgradeTable($db, $prefix."_actions", 'code', 'text', '', '', '', '','');
dbUpgradeTable($db, $prefix."_actions", 'location', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_actions", 'relevant', 'tinyint(1)', '', '', '0', '','');

dbUpgradeTable($db, $prefix."_nav_main", 'idnavm', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_nav_main", 'location', 'varchar(255)', '', '', '', '','');

dbUpgradeTable($db, $prefix."_nav_sub", 'idnavs', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_nav_sub", 'idnavm', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_nav_sub", 'idarea', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_nav_sub", 'level', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_nav_sub", 'location', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_nav_sub", 'online', 'tinyint(1)', '', '', '0', '','');

dbUpgradeTable($db, $prefix."_rights", 'idright', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_rights", 'user_id', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_rights", 'idarea', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_rights", 'idaction', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_rights", 'idcat', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_rights", 'idclient', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_rights", 'idlang', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_rights", 'type', 'tinyint(1)', '', '', '0', '','');

dbUpgradeTable($db, $prefix."_container", 'idcontainer', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_container", 'idtpl', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_container", 'number', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_container", 'idmod', 'int(10)', 'YES', '', '', '','');

dbUpgradeTable($db, $prefix."_container_conf", 'idcontainerc', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_container_conf", 'idtplcfg', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_container_conf", 'number', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_container_conf", 'container', 'text', 'YES', '', '', '','');

dbUpgradeTable($db, $prefix."_files", 'idfile', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_files", 'idarea', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_files", 'filename', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_files", 'filetype', 'varchar(4)', '', '', 'main', '','');

dbUpgradeTable($db, $prefix."_frame_files", 'idframefile', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_frame_files", 'idarea', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_frame_files", 'idframe', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_frame_files", 'idfile', 'int(10)', '', '', '0', '','');

dbUpgradeTable($db, $prefix."_plugins", 'idplugin', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_plugins", 'idclient', 'int(10)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_plugins", 'name', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_plugins", 'description', 'text', '', '', '', '','');
dbUpgradeTable($db, $prefix."_plugins", 'path', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_plugins", 'installed', 'tinyint(1)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_plugins", 'active', 'tinyint(1)', 'YES', '', '', '','');

dbUpgradeTable($db, $prefix."_phplib_active_sessions", 'sid', 'varchar(32)', '', 'PRI', '', '','', true);
dbUpgradeTable($db, $prefix."_phplib_active_sessions", 'name', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_phplib_active_sessions", 'val', 'longblob', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_phplib_active_sessions", 'changed', 'varchar(14)', '', '', '', '','');

dbUpgradeTable($db, $prefix."_phplib_auth_user_md5", 'user_id', 'varchar(32)', '', 'PRI', '', '','', true);
dbUpgradeTable($db, $prefix."_phplib_auth_user_md5", 'username', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_phplib_auth_user_md5", 'password', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_phplib_auth_user_md5", 'perms', 'mediumtext', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_phplib_auth_user_md5", 'realname', 'varchar(255)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_phplib_auth_user_md5", 'email', 'varchar(255)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_phplib_auth_user_md5", 'telephone', 'varchar(255)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_phplib_auth_user_md5", 'address_street', 'varchar(255)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_phplib_auth_user_md5", 'address_zip', 'varchar(10)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_phplib_auth_user_md5", 'address_city', 'varchar(255)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_phplib_auth_user_md5", 'address_country', 'varchar(255)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_phplib_auth_user_md5", 'wysi', 'tinyint(2)', 'YES', '', '', '','');

dbUpgradeTable($db, $prefix."_actionlog", 'idlog', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_actionlog", 'user_id', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_actionlog", 'idclient', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_actionlog", 'idlang', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_actionlog", 'idaction', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_actionlog", 'idcatart', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_actionlog", 'logtimestamp', 'datetime', 'YES', '', '0000-00-00 00:00:00', '','');

dbUpgradeTable($db, $prefix."_link", 'idlink', 'int(6)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_link", 'idartlang', 'int(10)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_link", 'idcat', 'int(10)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_link", 'idart', 'int(10)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_link", 'linkpath', 'varchar(255)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_link", 'internal', 'tinyint(1)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_link", 'active', 'tinyint(1)', 'YES', '', '', '','');

dbUpgradeTable($db, $prefix."_meta_type", 'idmetatype', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_meta_type", 'metatype', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_meta_type", 'fieldtype', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_meta_type", 'maxlength', 'int(11)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_meta_type", 'fieldname', 'varchar(255)', '', '', 'name', '','');

dbUpgradeTable($db, $prefix."_meta_tag", 'idmetatag', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_meta_tag", 'idartlang', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_meta_tag", 'idmetatype', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_meta_tag", 'metavalue', 'text', '', '', '', '','');

dbUpgradeTable($db, $prefix."_groups", 'group_id', 'varchar(32)', '', 'PRI', '', '','', true);
dbUpgradeTable($db, $prefix."_groups", 'groupname', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_groups", 'perms', 'varchar(255)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_groups", 'description', 'varchar(255)', 'YES', '', '', '','');

dbUpgradeTable($db, $prefix."_group_prop", 'idgroupprop', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_group_prop", 'group_id', 'varchar(32)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_group_prop", 'type', 'varchar(32)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_group_prop", 'name', 'varchar(32)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_group_prop", 'value', 'text', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_group_prop", 'idcatlang', 'int(11)', '', '', '0', '','');

dbUpgradeTable($db, $prefix."_groupmembers", 'idgroupuser', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_groupmembers", 'group_id', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_groupmembers", 'user_id', 'varchar(32)', '', '', '', '','');

dbUpgradeTable($db, $prefix."_config", 'idconfig', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_config", 'abs_path', 'varchar(255)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_config", 'url_path', 'varchar(255)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_config", 'css_path', 'varchar(255)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_config", 'js_path', 'varchar(255)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_config", 'filename', 'varchar(127)', 'YES', '', '', '','');

dbUpgradeTable($db, $prefix."_config_client", 'idconfc', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_config_client", 'idclient', 'int(10)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_config_client", 'abs_path', 'varchar(255)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_config_client", 'url_path', 'varchar(255)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_config_client", 'css_path', 'varchar(255)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_config_client", 'js_path', 'varchar(255)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_config_client", 'filename', 'varchar(127)', 'YES', '', '', '','');

dbUpgradeTable($db, $prefix."_data", 'iddata', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_data", 'idclient', 'int(10)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_data", 'abs_path', 'varchar(255)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_data", 'url_path', 'varchar(255)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_data", 'dir_hide', 'varchar(255)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_data", 'dir_not', 'varchar(255)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_data", 'ext_not', 'varchar(255)', 'YES', '', '', '','');

dbUpgradeTable($db, $prefix."_sequence", 'seq_name', 'varchar(127)', '', 'PRI', '', '','', true);
dbUpgradeTable($db, $prefix."_sequence", 'nextid', 'int(10)', '', '', '0', '','');

dbUpgradeTable($db, $prefix."_user_prop", 'iduserprop', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_user_prop", 'user_id', 'varchar(32)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_user_prop", 'type', 'varchar(32)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_user_prop", 'name', 'varchar(32)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_user_prop", 'value', 'text', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_user_prop", 'idcatlang', 'int(11)', '', '', '0', '','');

dbUpgradeTable($db, $prefix."_inuse", 'idinuse', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_inuse", 'type', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_inuse", 'objectid', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_inuse", 'session', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_inuse", 'userid', 'varchar(32)', '', '', '', '','');

dbUpgradeTable($db, $prefix."_system_prop", 'idsystemprop', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_system_prop", 'type', 'varchar(32)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_system_prop", 'name', 'varchar(32)', 'YES', '', '', '','');
dbUpgradeTable($db, $prefix."_system_prop", 'value', 'text', 'YES', '', '', '','');

dbUpgradeTable($db, $prefix."_art_spec", 'idartspec', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_art_spec", 'client', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_art_spec", 'lang', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_art_spec", 'artspec', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_art_spec", 'online', 'tinyint(1)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_art_spec", 'artspecdefault', 'tinyint(1)', '', '', '0', '','');

dbUpgradeTable($db, $prefix."_mod_history", 'idmodhistory', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_mod_history", 'idmod', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_mod_history", 'idclient', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_mod_history", 'name', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_mod_history", 'type', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_mod_history", 'description', 'text', '', '', '', '','');
dbUpgradeTable($db, $prefix."_mod_history", 'input', 'longtext', '', '', '', '','');
dbUpgradeTable($db, $prefix."_mod_history", 'output', 'longtext', '', '', '', '','');
dbUpgradeTable($db, $prefix."_mod_history", 'template', 'text', '', '', '', '','');
dbUpgradeTable($db, $prefix."_mod_history", 'changedby', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_mod_history", 'changed', 'int(10)', '', '', '0', '','');

dbUpgradeTable($db, $prefix."_properties", 'idproperty', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_properties", 'idclient', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_properties", 'itemtype', 'varchar(64)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_properties", 'itemid', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_properties", 'type', 'varchar(64)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_properties", 'name', 'varchar(64)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_properties", 'value', 'text', '', '', '', '','');
dbUpgradeTable($db, $prefix."_properties", 'author', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_properties", 'created', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_properties", 'modified', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_properties", 'modifiedby', 'varchar(32)', '', '', '', '','');

dbUpgradeTable($db, $prefix."_frontendusers", 'idfrontenduser', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_frontendusers", 'idclient', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_frontendusers", 'username', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_frontendusers", 'password', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_frontendusers", 'active', 'tinyint(1)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_frontendusers", 'author', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_frontendusers", 'created', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_frontendusers", 'modified', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_frontendusers", 'modifiedby', 'varchar(32)', '', '', '', '','');

dbUpgradeTable($db, $prefix."_frontendgroups", 'idfrontendgroup', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_frontendgroups", 'idclient', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_frontendgroups", 'groupname', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_frontendgroups", 'defaultgroup', 'tinyint(1)', '', '', '0', '','');

dbUpgradeTable($db, $prefix."_frontendgroupmembers", 'idfrontendgroupmember', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_frontendgroupmembers", 'idfrontendgroup', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_frontendgroupmembers", 'idfrontenduser', 'int(10)', '', '', '0', '','');

dbUpgradeTable($db, $prefix."_communications", 'idcommunication', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_communications", 'idclient', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_communications", 'comtype', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_communications", 'subject', 'varchar(255)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_communications", 'message', 'text', '', '', '', '','');
dbUpgradeTable($db, $prefix."_communications", 'recipient', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_communications", 'author', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_communications", 'created', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_communications", 'modifiedby', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_communications", 'modified', 'datetime', '', '', '0000-00-00 00:00:00', '','');

dbUpgradeTable($db, $prefix."_mod_translations", 'idmodtranslation', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_mod_translations", 'idmod', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_mod_translations", 'idlang', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_mod_translations", 'original', 'text', '', '', '', '','');
dbUpgradeTable($db, $prefix."_mod_translations", 'translation', 'text', '', '', '', '','');

dbUpgradeTable($db, $prefix."_frontendpermissions", 'idfrontendpermission', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_frontendpermissions", 'idfrontendgroup', 'int(10)', '', 'MUL', '0', '','');
dbUpgradeTable($db, $prefix."_frontendpermissions", 'idlang', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_frontendpermissions", 'plugin', 'varchar(255)', '', 'MUL', '', '','');
dbUpgradeTable($db, $prefix."_frontendpermissions", 'action', 'varchar(255)', '', 'MUL', '', '','');
dbUpgradeTable($db, $prefix."_frontendpermissions", 'item', 'varchar(255)', '', 'MUL', '', '','');

dbUpgradeTable($db, $prefix."_dbfs", 'iddbfs', 'int(10)', '', 'PRI', '0', '','', true);
dbUpgradeTable($db, $prefix."_dbfs", 'idclient', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_dbfs", 'dirname', 'text', '', '', '', '','');
dbUpgradeTable($db, $prefix."_dbfs", 'filename', 'text', '', '', '', '','');
dbUpgradeTable($db, $prefix."_dbfs", 'mimetype', 'varchar(64)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_dbfs", 'size', 'int(10)', '', '', '0', '','');
dbUpgradeTable($db, $prefix."_dbfs", 'content', 'longblob', '', '', '', '','');
dbUpgradeTable($db, $prefix."_dbfs", 'created', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_dbfs", 'author', 'varchar(32)', '', '', '', '','');
dbUpgradeTable($db, $prefix."_dbfs", 'modified', 'datetime', '', '', '0000-00-00 00:00:00', '','');
dbUpgradeTable($db, $prefix."_dbfs", 'modifiedby', 'varchar(32)', '', '', '', '','');

?>
