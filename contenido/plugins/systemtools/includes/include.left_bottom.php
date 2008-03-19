<?php
/**
 * Plugin Systemtools
 *
 * @file include.left_bottom.php
 * @project Contenido
 * 
 * @version	1.1.0
 * @author Willi Man
 * @copyright four for business AG <www.4fb.de>
 * @created 24.08.2005
 * @modified 24.08.2005
 * @modified 21.12.2005
 * @modified 22.02.2006
 *
 */

include_once('config.plugin.php');

#### only system administrators who are defined in config.plugin.php have permission to access
if (ereg("sysadmin", $auth->auth['perm']) AND in_array($auth->auth['uname'], $arrayOfValidUsers))
{
	
	$sHTMLTemplate = '<div style="margin: 10px; padding: 0px;"><a target="right_bottom" alt="{title}" title="{title}" href="{url}" >{text}</a></div>';
	
	$tpl->reset();
	$tpl->set('s', 'title', i18n("Cleanup con_code", "systemtools"));
	$tpl->set('s', 'text', i18n("Cleanup con_code", "systemtools"));
	$sAction = 'cleanup_con_code';
	$tpl->set('s', 'url', 'main.php?area='.$area.'&frame=4&'.$area.'_action='.$sAction.'&contenido='.$sess->id);
	$sHTMLOutput = $tpl->generate($sHTMLTemplate, true);
	
	$tpl->reset();
	$tpl->set('s', 'title', i18n("Cleanup con_inuse", "systemtools"));
	$tpl->set('s', 'text', i18n("Cleanup con_inuse", "systemtools"));
	$sAction = 'cleanup_con_inuse';
	$tpl->set('s', 'url', 'main.php?area='.$area.'&frame=4&'.$area.'_action='.$sAction.'&contenido='.$sess->id);
	$sHTMLOutput .= $tpl->generate($sHTMLTemplate, true);
	
	$tpl->reset();
	$tpl->set('s', 'title', i18n("Cleanup con_phplib_active_sessions", "systemtools"));
	$tpl->set('s', 'text', i18n("Cleanup con_phplib_active_sessions", "systemtools"));
	$sAction = 'cleanup_con_phplib_active_sessions';
	$tpl->set('s', 'url', 'main.php?area='.$area.'&frame=4&'.$area.'_action='.$sAction.'&contenido='.$sess->id);
	$sHTMLOutput .= $tpl->generate($sHTMLTemplate, true);
	
	$tpl->reset();
	$tpl->set('s', 'title', i18n("Cleanup unused sessions", "systemtools"));
	$tpl->set('s', 'text', i18n("Cleanup unused sessions", "systemtools"));
	$sAction = 'cleanup_unused_session';
	$tpl->set('s', 'url', 'main.php?area='.$area.'&frame=4&'.$area.'_action='.$sAction.'&contenido='.$sess->id);
	$sHTMLOutput .= $tpl->generate($sHTMLTemplate, true);
	
	$tpl->reset();
	$tpl->set('s', 'title', i18n("Clear cache directory", "systemtools"));
	$tpl->set('s', 'text', i18n("Clear cache directory", "systemtools"));
	$sAction = 'clear_cache_directory';
	$tpl->set('s', 'url', 'main.php?area='.$area.'&frame=4&'.$area.'_action='.$sAction.'&contenido='.$sess->id);
	$sHTMLOutput .= $tpl->generate($sHTMLTemplate, true);
	
	$tpl->reset();
	$tpl->set('s', 'title', i18n("Archive statistic", "systemtools"));
	$tpl->set('s', 'text', i18n("Archive statistic", "systemtools"));
	$sAction = 'archive_statistic';
	$tpl->set('s', 'url', 'main.php?area='.$area.'&frame=4&'.$area.'_action='.$sAction.'&contenido='.$sess->id);
	$sHTMLOutput .= $tpl->generate($sHTMLTemplate, true);
	
	$tpl->reset();
	$tpl->set('s', 'title', i18n("Phpinfo", "systemtools"));
	$tpl->set('s', 'text', i18n("Phpinfo", "systemtools"));
	$sAction = 'display_phpinfo';
	$tpl->set('s', 'url', 'main.php?area='.$area.'&frame=4&'.$area.'_action='.$sAction.'&contenido='.$sess->id);
	$sHTMLOutput .= $tpl->generate($sHTMLTemplate, true);
	
	$tpl->reset();
	$tpl->set('s', 'title', i18n("Synchronise language", "systemtools"));
	$tpl->set('s', 'text', i18n("Synchronise language", "systemtools"));
	$sAction = 'synchronise_language';
	$tpl->set('s', 'url', 'main.php?area='.$area.'&frame=4&'.$area.'_action='.$sAction.'&contenido='.$sess->id);
	$sHTMLOutput .= $tpl->generate($sHTMLTemplate, true);
	
	$tpl->reset();
	$tpl->set('s', 'title', i18n("Generate index of articles", "systemtools"));
	$tpl->set('s', 'text', i18n("Generate index of articles", "systemtools"));
	$sAction = 'generate_index_of_articles';
	$tpl->set('s', 'url', 'main.php?area='.$area.'&frame=4&'.$area.'_action='.$sAction.'&contenido='.$sess->id);
	$sHTMLOutput .= $tpl->generate($sHTMLTemplate, true);
	
	$tpl->reset();
	$tpl->set('s', 'title', i18n("Convert startarticles", "systemtools"));
	$tpl->set('s', 'text', i18n("Convert startarticles", "systemtools"));
	$sAction = 'convert_startarticles';
	$tpl->set('s', 'url', 'main.php?area='.$area.'&frame=4&'.$area.'_action='.$sAction.'&contenido='.$sess->id);
	$sHTMLOutput .= $tpl->generate($sHTMLTemplate, true);
	
	$tpl->reset();
	$tpl->set('s', 'title', i18n("Export database", "systemtools"));
	$tpl->set('s', 'text', i18n("Export database", "systemtools"));
	$sAction = 'export_database';
	$tpl->set('s', 'url', 'main.php?area='.$area.'&frame=4&'.$area.'_action='.$sAction.'&contenido='.$sess->id);
	$sHTMLOutput .= $tpl->generate($sHTMLTemplate, true);
	
	$tpl->reset();
	$tpl->set('s', 'title', i18n("Database dumps", "systemtools"));
	$tpl->set('s', 'text', i18n("Database dumps", "systemtools"));
	$sAction = 'display_databasedumps';
	$tpl->set('s', 'url', 'main.php?area='.$area.'&frame=4&'.$area.'_action='.$sAction.'&contenido='.$sess->id);
	$sHTMLOutput .= $tpl->generate($sHTMLTemplate, true);
	
	$tpl->reset();
	$tpl->set('s', 'title', i18n("Copy client", "systemtools"));
	$tpl->set('s', 'text', i18n("Copy client", "systemtools"));
	$sAction = 'copy_client';
	$tpl->set('s', 'url', 'main.php?area='.$area.'&frame=4&'.$area.'_action='.$sAction.'&contenido='.$sess->id);
	$sHTMLOutput .= $tpl->generate($sHTMLTemplate, true);
	
	$tpl->reset();
	$tpl->set('s', 'title', i18n("Compare modules of clients", "systemtools"));
	$tpl->set('s', 'text', i18n("Compare modules of clients", "systemtools"));
	$sAction = 'compare_client_modules';
	$tpl->set('s', 'url', 'main.php?area='.$area.'&frame=4&'.$area.'_action='.$sAction.'&contenido='.$sess->id);
	$sHTMLOutput .= $tpl->generate($sHTMLTemplate, true);
	
	$tpl->reset();
	$tpl->set('s', 'title', i18n("Compare layouts of clients", "systemtools"));
	$tpl->set('s', 'text', i18n("Compare layouts of clients", "systemtools"));
	$sAction = 'compare_client_layouts';
	$tpl->set('s', 'url', 'main.php?area='.$area.'&frame=4&'.$area.'_action='.$sAction.'&contenido='.$sess->id);
	$sHTMLOutput .= $tpl->generate($sHTMLTemplate, true);
	
	/*
	$tpl->reset();
	$tpl->set('s', 'title', i18n("Dump upload directory", "systemtools"));
	$tpl->set('s', 'text', i18n("Dump upload directory", "systemtools"));
	$sAction = 'dump_upload_directory';
	$tpl->set('s', 'url', 'main.php?area='.$area.'&frame=4&'.$area.'_action='.$sAction.'&contenido='.$sess->id);
	$sHTMLOutput .= $tpl->generate($sHTMLTemplate, true);
	*/

}else
{
	$sHTMLOutput = $notification->returnNotification("error", i18n("No permission.", "systemtools"));
}

$tpl->set('s', 'content', $sHTMLOutput);
$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');

?>