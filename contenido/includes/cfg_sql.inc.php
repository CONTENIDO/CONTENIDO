<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Definition of SQL-vars
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.0
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-01-21
 *   modified 2008-06-25, Frederic Schneider, add security fix
 *
 *   $Id: cfg_sql.inc.php 710 2008-08-21 11:37:00Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

global $cfg;

$cfg["tab"]["art"]							   = $cfg['sql']['sqlprefix']."_art";
$cfg["tab"]["art_lang"] 					   = $cfg['sql']['sqlprefix']."_art_lang";
$cfg["tab"]["cat"]							   = $cfg['sql']['sqlprefix']."_cat";
$cfg["tab"]["cat_art"]						   = $cfg['sql']['sqlprefix']."_cat_art";
$cfg["tab"]["cat_tree"] 					   = $cfg['sql']['sqlprefix']."_cat_tree";
$cfg["tab"]["cat_lang"] 					   = $cfg['sql']['sqlprefix']."_cat_lang";
$cfg["tab"]["clients"]						   = $cfg['sql']['sqlprefix']."_clients";
$cfg["tab"]["clients_lang"] 				   = $cfg['sql']['sqlprefix']."_clients_lang";
$cfg["tab"]["code"] 						   = $cfg['sql']['sqlprefix']."_code";
$cfg["tab"]["content"]						   = $cfg['sql']['sqlprefix']."_content";
$cfg["tab"]["lang"] 						   = $cfg['sql']['sqlprefix']."_lang";
$cfg["tab"]["lay"]							   = $cfg['sql']['sqlprefix']."_lay";
$cfg["tab"]["mod"]							   = $cfg['sql']['sqlprefix']."_mod";
$cfg["tab"]["news"] 						   = $cfg['sql']['sqlprefix']."_news";
$cfg["tab"]["news_rcp"] 					   = $cfg['sql']['sqlprefix']."_news_rcp";
$cfg["tab"]["news_groups"]					   = $cfg['sql']['sqlprefix']."_news_groups";
$cfg["tab"]["news_groupmembers"]			   = $cfg['sql']['sqlprefix']."_news_groupmembers";
$cfg["tab"]["news_jobs"]                       = $cfg['sql']['sqlprefix']."_news_jobs";
$cfg["tab"]["news_log"]                        = $cfg['sql']['sqlprefix']."_news_log";
$cfg["tab"]["stat"] 						   = $cfg['sql']['sqlprefix']."_stat";
$cfg["tab"]["stat_archive"] 				   = $cfg['sql']['sqlprefix']."_stat_archive";
$cfg["tab"]["status"]						   = $cfg['sql']['sqlprefix']."_status";
$cfg["tab"]["tpl"]							   = $cfg['sql']['sqlprefix']."_template";
$cfg["tab"]["tpl_conf"] 					   = $cfg['sql']['sqlprefix']."_template_conf";
$cfg["tab"]["type"] 						   = $cfg['sql']['sqlprefix']."_type";
$cfg["tab"]["upl"]							   = $cfg['sql']['sqlprefix']."_upl";
$cfg["tab"]["keywords"] 					   = $cfg['sql']['sqlprefix']."_keywords";
$cfg["tab"]["area"] 						   = $cfg['sql']['sqlprefix']."_area";
$cfg["tab"]["actions"]						   = $cfg['sql']['sqlprefix']."_actions";
$cfg["tab"]["nav_main"] 					   = $cfg['sql']['sqlprefix']."_nav_main";
$cfg["tab"]["nav_sub"]						   = $cfg['sql']['sqlprefix']."_nav_sub";
$cfg["tab"]["rights"]						   = $cfg['sql']['sqlprefix']."_rights";
$cfg["tab"]["container"]					   = $cfg['sql']['sqlprefix']."_container";
$cfg["tab"]["container_conf"]				   = $cfg['sql']['sqlprefix']."_container_conf";
$cfg["tab"]["files"]						   = $cfg['sql']['sqlprefix']."_files";
$cfg["tab"]["framefiles"]					   = $cfg['sql']['sqlprefix']."_frame_files";
$cfg["tab"]["plugins"]						   = $cfg['sql']['sqlprefix']."_plugins";
$cfg["tab"]["phplib_active_sessions"]		   = $cfg['sql']['sqlprefix']."_phplib_active_sessions";
$cfg["tab"]["phplib_auth_user_md5"] 		   = $cfg['sql']['sqlprefix']."_phplib_auth_user_md5";
$cfg["tab"]["actionlog"]					   = $cfg['sql']['sqlprefix']."_actionlog";
$cfg["tab"]["link"] 						   = $cfg['sql']['sqlprefix']."_link";
$cfg["tab"]["meta_type"]					   = $cfg['sql']['sqlprefix']."_meta_type";
$cfg["tab"]["meta_tag"] 					   = $cfg['sql']['sqlprefix']."_meta_tag";
$cfg["tab"]["groups"]						   = $cfg['sql']['sqlprefix']."_groups";
$cfg["tab"]["group_prop"]					   = $cfg['sql']['sqlprefix']."_group_prop";
$cfg["tab"]["groupmembers"] 				   = $cfg['sql']['sqlprefix']."_groupmembers";
$cfg["tab"]["config"]						   = $cfg['sql']['sqlprefix']."_config";
$cfg["tab"]["config_client"]				   = $cfg['sql']['sqlprefix']."_config_client";
$cfg["tab"]["data"] 						   = $cfg['sql']['sqlprefix']."_data";
$cfg["tab"]["sequence"] 					   = $cfg['sql']['sqlprefix']."_sequence";
$cfg["tab"]["user_prop"]					   = $cfg['sql']['sqlprefix']."_user_prop";
$cfg["tab"]["inuse"]						   = $cfg['sql']['sqlprefix']."_inuse";
$cfg["tab"]["system_prop"]					   = $cfg['sql']['sqlprefix']."_system_prop";
$cfg["tab"]["art_spec"] 					   = $cfg['sql']['sqlprefix']."_art_spec";
$cfg["tab"]["properties"]					   = $cfg['sql']['sqlprefix']."_properties";
$cfg["tab"]["frontendusers"]				   = $cfg['sql']['sqlprefix']."_frontendusers";
$cfg["tab"]["frontendgroups"]				   = $cfg['sql']['sqlprefix']."_frontendgroups";
$cfg["tab"]["frontendgroupmembers"] 		   = $cfg['sql']['sqlprefix']."_frontendgroupmembers";
$cfg["tab"]["communications"]				   = $cfg['sql']['sqlprefix']."_communications";
$cfg["tab"]["art_spec"] 					   = $cfg['sql']['sqlprefix']."_art_spec";
$cfg["tab"]["mod_translations"] 			   = $cfg['sql']['sqlprefix']."_mod_translations";
$cfg["tab"]["frontendpermissions"]			   = $cfg['sql']['sqlprefix']."_frontendpermissions";
$cfg["tab"]["dbfs"] 						   = $cfg['sql']['sqlprefix']."_dbfs";
$cfg["tab"]["chartable"]					   = $cfg['sql']['sqlprefix']."_chartable";
$cfg["tab"]["upl_meta"]					       = $cfg['sql']['sqlprefix']."_upl_meta";
$cfg["tab"]["online_user"]					   = $cfg['sql']['sqlprefix']."_online_user";
$cfg["tab"]["stat_heap_table"]				   = $cfg['sql']['sqlprefix']."_stat_heap_table";
$cfg["tab"]["file_information"]                = $cfg['sql']['sqlprefix']."_file_information";
?>
