<?php
/**
 * Plugin Systemtools
 * 
 * Description:
 * This plugin is a collection of administration tools
 * It provides following functions
 * - cleanup relation con_code
 * - cleanup relation con_inuse
 * - cleanup relation con_phplib_active_sessions
 * - cleanup unused sessions
 * - clear cache directory
 * - archive statistic
 * - display Phpinfo
 * - synchronise language
 * - generate index of articles
 * - convert startarticle
 * - export database
 * - display databasedumps
 * - copy client
 * - compare modules of clients
 * - compare layouts of clients
 * 
 * @file include.right_bottom.php
 * @project Contenido
 *
 * @see config.plugin.php
 *
 * @version	1.5.0
 * @author Willi Man
 * @copyright four for business AG <www.4fb.de>
 * @created 24.08.2005
 * @modified 15.11.2005
 * @modified 21.12.2005
 * @modified 22.02.2006
 * @modified 27.02.2006, additional options to copy a client
 * 
 * $Id: include.right_bottom.php,v 1.1 2006/11/07 09:56:50 willi.man Exp $
 */

#### only system administrators who are defined in config.plugin.php have permission to access
include_once($cfg['path']['contenido'].$cfg['path']['plugins'].'systemtools/includes/config.plugin.php');
global $arrayOfValidUsers;
if (ereg("sysadmin", $auth->auth['perm']) AND in_array($auth->auth['uname'], $arrayOfValidUsers))
{
	ini_set('max_execution_time', 1000);
	cInclude("classes", "class.ui.php");
	include_once($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'classes/systemfunctions.php');
	include_once($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'classes/html_views.php');
	include_once($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'classes/LogFile.php');
	
	$aLanguages = getLanguageNamesByClient($client);
	
	switch ($_REQUEST[$area.'_action'])
	{
		###########################################################################################
		# cleanup con_code
		case 'cleanup_con_code':
			if ($_REQUEST['do_cleanup_con_code'] == 'true')
			{
				if(deleteFromConCode($db, $cfg))
				{
					$sHTMLOutput = $notification->messageBox("info", i18n("All entries in table con_code deleted.", "systemtools"),0);
					$tpl->set('s', 'content', $sHTMLOutput);
					$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
				}
			}else
			{
				$tpl->reset();
				$tpl->set('s', 'title', i18n("Cleanup con_code", "systemtools"));
				$tpl->set('s', 'area', $area);
				$tpl->set('s', 'frame', $frame);
				$tpl->set('s', 'session', $sess->id);
				$tpl->set('s', 'action_name', $area.'_action');
				$tpl->set('s', 'action_value', 'cleanup_con_code');
				$tpl->set('s', 'action2_name', 'do_cleanup_con_code');
				$tpl->set('s', 'action2_value', 'true');
				$tpl->set('s', 'notification', i18n("Remove all entries in table con_code.", "systemtools"));
				$sHTMLWarning = $notification->messageBox("warning", i18n("Complete code regeneration will be forced! If there are many articles, this could decrease the performance of the webserver dramatically!", "systemtools"),0);
				$tpl->set('s', 'warning', $sHTMLWarning);
				$tpl->set('s', 'options', '');
                $tpl->set('s', 'confirm_header', i18n("Do you really want to", "systemtools"));
				$tpl->set('s', 'confirm', i18n("Empty table con_code?", "systemtools"));
				$sHTMLOutput = $tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/system_action.html', true);
				$tpl->set('s', 'content', $sHTMLOutput);
				$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
			}
			break;
		###########################################################################################
		# cleanup con_inuse	
		case 'cleanup_con_inuse':
			if ($_REQUEST['do_cleanup_con_inuse'] == 'true')
			{
				if (deleteFromConInuse($db, $cfg))
				{
					$sHTMLOutput = $notification->messageBox("info", i18n("All entries in table con_inuse deleted.", "systemtools"),0);
					$tpl->set('s', 'content', $sHTMLOutput);
					$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
				}
			}else
			{
				$tpl->reset();
				$tpl->set('s', 'title', i18n("Cleanup con_inuse", "systemtools"));
				$tpl->set('s', 'area', $area);
				$tpl->set('s', 'frame', $frame);
				$tpl->set('s', 'session', $sess->id);
				$tpl->set('s', 'action_name', $area.'_action');
				$tpl->set('s', 'action_value', 'cleanup_con_inuse');
				$tpl->set('s', 'action2_name', 'do_cleanup_con_inuse');
				$tpl->set('s', 'action2_value', 'true');
				$tpl->set('s', 'notification', i18n("Remove all entries in table con_inuse.", "systemtools"));
				$tpl->set('s', 'warning', '');
				$tpl->set('s', 'options', '');
                $tpl->set('s', 'confirm_header', i18n("Do you really want to", "systemtools"));
				$tpl->set('s', 'confirm', i18n("Empty table con_inuse?", "systemtools"));
				$sHTMLOutput = $tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/system_action.html', true);
				$tpl->set('s', 'content', $sHTMLOutput);
				$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
			}
			break;
		###########################################################################################
		# cleanup con_phplib_active_sessions
		case 'cleanup_con_phplib_active_sessions':
			if ($_REQUEST['do_cleanup_con_phplib_active_sessions'] == 'true')
			{
				if (deleteFromConPhplibActiveSessions($db, $cfg))
				{
					$sHTMLOutput = $notification->messageBox("info", i18n("All entries in table con_phplib_active_sessions deleted.", "systemtools"),0);
					$tpl->set('s', 'content', $sHTMLOutput);
					$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
				}
			}else
			{
				$iNumberOfActiveSessions = getNumberOfActiveSessions($db, $cfg);
				$tpl->reset();
				$tpl->set('s', 'title', i18n("Cleanup con_phplib_active_sessions", "systemtools"));
				$tpl->set('s', 'area', $area);
				$tpl->set('s', 'frame', $frame);
				$tpl->set('s', 'session', $sess->id);
				$tpl->set('s', 'action_name', $area.'_action');
				$tpl->set('s', 'action_value', 'cleanup_con_phplib_active_sessions');
				$tpl->set('s', 'action2_name', 'do_cleanup_con_phplib_active_sessions');
				$tpl->set('s', 'action2_value', 'true');
				$tpl->set('s', 'notification', i18n("Remove all entries in table con_phplib_active_sessions.".'<br>'.i18n("Number of active sessions:").' '.$iNumberOfActiveSessions, "systemtools"));
				$tpl->set('s', 'warning', '');
				$tpl->set('s', 'options', '');
                $tpl->set('s', 'confirm_header', i18n("Do you really want to", "systemtools"));
				$tpl->set('s', 'confirm', i18n("Empty table con_phplib_active_sessions?", "systemtools"));
				$sHTMLOutput = $tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/system_action.html', true);
				$tpl->set('s', 'content', $sHTMLOutput);
				$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
			}
			break;
		###########################################################################################
		# cleanup unused sessions	
		case 'cleanup_unused_session':
			if ($_REQUEST['do_cleanup_unused_session'] == 'true')
			{
				cleanupSessions();
				$sHTMLOutput = $notification->messageBox("info", i18n("All unused sessions deleted.", "systemtools"),0);
				$tpl->set('s', 'content', $sHTMLOutput);
				$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
			}else
			{
				$iNumberOfActiveSessions = getNumberOfActiveSessions($db, $cfg);
				$tpl->reset();
				$tpl->set('s', 'title', i18n("Cleanup unused sessions", "systemtools"));
				$tpl->set('s', 'area', $area);
				$tpl->set('s', 'frame', $frame);
				$tpl->set('s', 'session', $sess->id);
				$tpl->set('s', 'action_name', $area.'_action');
				$tpl->set('s', 'action_value', 'cleanup_unused_session');
				$tpl->set('s', 'action2_name', 'do_cleanup_unused_session');
				$tpl->set('s', 'action2_value', 'true');
				$tpl->set('s', 'notification', i18n("Normally this function will be called via Pseudo-cron. Expired sessions and related InUse entries will be removed.".'<br>'.i18n("Number of active sessions:").' '.$iNumberOfActiveSessions, "systemtools"));
				$tpl->set('s', 'warning', '');
				$tpl->set('s', 'options', '');
                $tpl->set('s', 'confirm_header', i18n("Do you really want to", "systemtools"));
				$tpl->set('s', 'confirm', i18n("Cleanup unused sessions?", "systemtools"));
				$sHTMLOutput = $tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/system_action.html', true);
				$tpl->set('s', 'content', $sHTMLOutput);
				$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
			}
			break;
		###########################################################################################
		# clear cache directory	
		case 'clear_cache_directory':
			if ($_REQUEST['do_clear_cache_directory'] == 'true')
			{
				if (clearCacheDirectory($client, $cfgClient))
				{
					$sHTMLOutput = $notification->messageBox("info", i18n("All files in directory", "systemtools")." ".$cfgClient[$client]["path"]["frontend"]." ".i18n("deleted.", "systemtools"),0);
					$tpl->set('s', 'content', $sHTMLOutput);
					$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
				}else
				{
					$sHTMLOutput = $notification->messageBox("error", i18n("Could not clear cache directory."),0);
					$tpl->set('s', 'content', $sHTMLOutput);
					$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
				}
			}else
			{
				$tpl->reset();
				$tpl->set('s', 'title', i18n("Clear cache directory", "systemtools"));
				$tpl->set('s', 'area', $area);
				$tpl->set('s', 'frame', $frame);
				$tpl->set('s', 'session', $sess->id);
				$tpl->set('s', 'action_name', $area.'_action');
				$tpl->set('s', 'action_value', 'clear_cache_directory');
				$tpl->set('s', 'action2_name', 'do_clear_cache_directory');
				$tpl->set('s', 'action2_value', 'true');
				$tpl->set('s', 'notification', i18n("Delete all files in directory", "systemtools")." ".$cfgClient[$client]["path"]["frontend"].'cache/');
				$tpl->set('s', 'warning', '');
				$tpl->set('s', 'options', '');
                $tpl->set('s', 'confirm_header', i18n("Do you really want to", "systemtools"));
				$tpl->set('s', 'confirm', i18n("Clear cache directory?", "systemtools"));
				$sHTMLOutput = $tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/system_action.html', true);
				$tpl->set('s', 'content', $sHTMLOutput);
				$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
			}
			break;
		###########################################################################################		
		# archive statistic
		case 'archive_statistic':
			if ($_REQUEST['do_archive_statistic'] == 'true')
			{
				include_once($cfg['path']['contenido'].$cfg['path']['includes'].'functions.stat.php');
				
				$iYear = date("Y");
				$iMonth = date("m");
				
				if ($iMonth == 1)
				{
					$iMonth = 12;
					$iYear = $iYear -1;
				} else {
					$iMonth = $iMonth -1;
				}
				statsArchive(sprintf("%04d%02d", $iYear, $iMonth));
		
				$sHTMLOutput = $notification->messageBox("info", i18n("Current statistic archived.", "systemtools"),0);
				$tpl->set('s', 'content', $sHTMLOutput);
				$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
			}else
			{
				$sDATE_PreviousMonth = mktime(0, 0, 0, date("m")-1, date("d"),  date("Y"));
				$tpl->reset();
				$tpl->set('s', 'title', i18n("Archive current statistic to archive", "systemtools").' '.date('F', $sDATE_PreviousMonth).' '.date("Y"));
				$tpl->set('s', 'area', $area);
				$tpl->set('s', 'frame', $frame);
				$tpl->set('s', 'session', $sess->id);
				$tpl->set('s', 'action_name', $area.'_action');
				$tpl->set('s', 'action_value', 'archive_statistic');
				$tpl->set('s', 'action2_name', 'do_archive_statistic');
				$tpl->set('s', 'action2_value', 'true');
				$tpl->set('s', 'notification', i18n("Normally this function will be called via Pseudo-cron. This function archives all current statistic entries (in table 'con_stat') to archive-table 'con_stat_archive'. The entries will be assiociated to the previous month.", "systemtools"));
				$sHTMLWarning = $notification->messageBox("warning", i18n("Archiving the statistic should be done at the first of a month.", "systemtools"), 0);
				$tpl->set('s', 'warning', $sHTMLWarning);
				$tpl->set('s', 'options', '');
                $tpl->set('s', 'confirm_header', i18n("Do you really want to", "systemtools"));
				$tpl->set('s', 'confirm', i18n("Archive statistic?", "systemtools"));
				$sHTMLOutput = $tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/system_action.html', true);
				$tpl->set('s', 'content', $sHTMLOutput);
				$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
			}
			break;
		###########################################################################################
		# display Phpinfo	
		case 'display_phpinfo':
			phpinfo();
			break;
		###########################################################################################
		# synchronise language
		case 'synchronise_language':
			include_once($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'classes/easysync.php');
			break;
		###########################################################################################
		# generate index of articles	
		case 'generate_index_of_articles':
			if ($_REQUEST['do_generate_index_of_articles'] == 'true')
			{
				include_once($cfg['path']['contenido'].$cfg['path']['includes'].'functions.con2.php');
				conGenerateKeywords($client, $lang);
				$sHTMLOutput = $notification->messageBox("info", i18n("Index generated.", "systemtools"),0);
				$tpl->set('s', 'content', $sHTMLOutput);
				$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
			}else
			{
				$tpl->reset();
				$tpl->set('s', 'title', i18n("Generate index of all articles for client", "systemtools")." ".getClientName($client)." (".$client.") ".i18n("in language", "systemtools")." ".$aLanguages[$lang]." (".$lang.")");
				$tpl->set('s', 'area', $area);
				$tpl->set('s', 'frame', $frame);
				$tpl->set('s', 'session', $sess->id);
				$tpl->set('s', 'action_name', $area.'_action');
				$tpl->set('s', 'action_value', 'generate_index_of_articles');
				$tpl->set('s', 'action2_name', 'do_generate_index_of_articles');
				$tpl->set('s', 'action2_value', 'true');
				$tpl->set('s', 'notification', i18n("Generate index in table con_keywords.", "systemtools"));
				$sHTMLWarning = $notification->messageBox("warning", i18n("If there are many articles, this could take up to several minutes.", "systemtools"),0);
				$tpl->set('s', 'warning', $sHTMLWarning);
				$tpl->set('s', 'options', '');
                $tpl->set('s', 'confirm_header', i18n("Do you really want to", "systemtools"));
				$tpl->set('s', 'confirm', i18n("Generate index of all articles?", "systemtools"));
				$sHTMLOutput = $tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/system_action.html', true);
				$tpl->set('s', 'content', $sHTMLOutput);
				$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
			}
			break;
		###########################################################################################
		# convert startarticle	
		case 'convert_startarticles':
			if ($_REQUEST['do_convert_startarticles'] == 'true')
			{
				include_once($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'classes/convert_startarticles.php');
				$sNotification = convertStartarticles($db, $cfg);
				$sHTMLOutput = $notification->messageBox("info", $sNotification,0);
				$tpl->set('s', 'content', $sHTMLOutput);
				$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
			}else
			{
				$tpl->reset();
				$tpl->set('s', 'title', i18n("Convert startarticles", "systemtools"));
				$tpl->set('s', 'area', $area);
				$tpl->set('s', 'frame', $frame);
				$tpl->set('s', 'session', $sess->id);
				$tpl->set('s', 'action_name', $area.'_action');
				$tpl->set('s', 'action_value', 'convert_startarticles');
				$tpl->set('s', 'action2_name', 'do_convert_startarticles');
				$tpl->set('s', 'action2_value', 'true');
				$tpl->set('s', 'notification', i18n("Startarticle upgrade from Contenido 4.4.x or 4.3.x to 4.5 or later.", "systemtools")."<br>".i18n("Startarticles defined in table con_cat_art will be converted to table con_cat_lang.", "systemtools"));
				$tpl->set('s', 'warning', '');
				$tpl->set('s', 'options', '');
                $tpl->set('s', 'confirm_header', i18n("Do you really want to", "systemtools"));
				$tpl->set('s', 'confirm', i18n("Convert startarticles?", "systemtools"));
				$sHTMLOutput = $tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/system_action.html', true);
				$tpl->set('s', 'content', $sHTMLOutput);
				$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
			}
			break;
		###########################################################################################	
		# export database	
		case 'export_database':
			if ($_REQUEST['do_export_database'] == 'true')
			{
				if (isset($_REQUEST['selected_tables']) AND count($_REQUEST['selected_tables']) > 0)
				{
					if (eregi("exec", ini_get('disable_functions')))
					{
						$sHTMLOutput = $notification->messageBox("error", "Program Execution Function exec() is disabled.",0);
						$tpl->set('s', 'content', $sHTMLOutput);
						$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
					}else
					{	
						if ($_REQUEST['extended_insert'] == 'true')
						{
							$bExtended = true;
						}else
						{
							$bExtended = false;
						}
						
						$mixedStatus = backupDatabase ($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'db-dump/', $_REQUEST['selected_tables'], $bExtended, $cfg, $cfgClient, $client, $lang, $db);
						if ($mixedStatus === false)
						{
							$sHTMLOutput = $notification->messageBox("error", "Directory ".$cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'db-dump/ doesn\'t exist or is not writable. Maybe the system command mysqldump is not available.',0 );
							$tpl->set('s', 'content', $sHTMLOutput);
							$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
						}else
						{
							$sFilePath = $cfg['path']['contenido_fullhtml'].$cfg['path']['plugins'].__plugin_systemtools_path__.'db-dump/'.$mixedStatus;
							$sURL = '<a href="'.$sFilePath.'">'.$mixedStatus.'</a>';
							$sHTMLOutput = $notification->messageBox("info", "MySQL dump done.<p>".$sURL."</p>", 0);
							$tpl->set('s', 'content', $sHTMLOutput);
							$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
						}
					}
				}else
				{
					$sHTMLOutput = $notification->messageBox("info", "No tables selected.", 0);
					$tpl->set('s', 'content', $sHTMLOutput);
					$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
				}
			}else
			{
				$aTableStatusInformation = getTableStatus($db);
				
				$sHTMLTableOptions = generateTableOptions($aTableStatusInformation);
				$aTableLengthRows = sumOfTableLengthRows($aTableStatusInformation);
				$sHTMLCheckUncheck = '<a href="#" onclick="setCheckboxes(\'systemtool\', true, \'selected_tables[]\'); return false;">Check All</a>&nbsp;/&nbsp;<a href="#" onclick="setCheckboxes(\'systemtool\', false, \'selected_tables[]\'); return false;">Uncheck All</a>';
				
				$sNote = i18n("This function is based on", "systemtools").' <u>'.i18n("Program Execution Function", "systemtools").'</u> '.i18n("exec(). The external command", "systemtools").' <u>'.i18n("mysqldump", "systemtools").'</u> '.i18n("(if available) will be called via exec()").'.<br>'.i18n("This makes sense if the MySQL dump is very big. It is not intended to relpace phpMyAdmin.", "systemtools").'<br>'.i18n("The dump will be stored in directory").' '.$cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'db-dump/';
				$sWarning = (strlen(ini_get('disable_functions')) > 0) ? i18n("There are following disabled functions:", "systemtools").' '.ini_get('disable_functions').'<br>'.i18n("Function", "systemtools").' "exec" '.i18n("is required!", "systemtools") : '';
				$sWarningSafemode = (ini_get('safe_mode') == '1') ? i18n("safe_mode is active!", "systemtools").'<br>Required functions can be restricted/disabled by safe mode' : '';
				$sHTMLWarning = '';
				if (strlen($sWarning) > 0 OR strlen($sWarningSafemode) > 0)
				{
					$sHTMLWarning = $notification->messageBox("warning", $sWarning.'<br>'.$sWarningSafemode, 0);
				}
				
				$sHTMLCheckbox = '<input type="checkbox" name="extended_insert" value="true"> <b>'.i18n("Use extended inserts", "systemtools").'</b>';
				
				$tpl->reset();
				$tpl->set('s', 'title', i18n("Export database", "systemtools")." ".$db->Database." on host ".$db->Host);
				$tpl->set('s', 'area', $area);
				$tpl->set('s', 'frame', $frame);
				$tpl->set('s', 'session', $sess->id);
				$tpl->set('s', 'action_name', $area.'_action');
				$tpl->set('s', 'action_value', 'export_database');
				$tpl->set('s', 'action2_name', 'do_export_database');
				$tpl->set('s', 'action2_value', 'true');
				$tpl->set('s', 'notification', $sNote);
				$tpl->set('s', 'warning', $sHTMLWarning);
				$tpl->set('s', 'options', $sHTMLTableOptions."<p>".$sHTMLCheckUncheck."</p><p><b>Sum of rows:</b> ".$aTableLengthRows[1]." <b>Total length:</b> ".$aTableLengthRows[0]." </p>");
				$tpl->set('s', 'options2', $sHTMLCheckbox);
                $tpl->set('s', 'confirm_header', i18n("Do you really want to", "systemtools"));
				$tpl->set('s', 'confirm', i18n("Export database?", "systemtools"));
				$sHTMLOutput = $tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/system_action_db_export.html', true);
				$tpl->set('s', 'content', $sHTMLOutput);
				$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
			}
			break;
		###########################################################################################
		# display databasedumps	
		case 'display_databasedumps':
			if ($_REQUEST['delete_file'] == 'true')
			{	
				if (file_exists($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'db-dump/'.$_REQUEST['file_to_delete']))
				{
					if(unlink($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'db-dump/'.$_REQUEST['file_to_delete']))
					{
			    		$sHTMLOutput = $notification->messageBox("info", "File ".$_REQUEST['file_to_delete']." deleted.", 0);
						$tpl->set('s', 'content', $sHTMLOutput);
						$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
					}else
					{
						$sHTMLOutput = $notification->messageBox("error", "File ".$_REQUEST['file_to_delete']." could not be deleted.",0);
						$tpl->set('s', 'content', $sHTMLOutput);
						$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
					}
				}
			}else
			{
				$aFiles = getFiles($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'db-dump/');
				#print_r($aFiles);
				$sHTMLOutput = generateFileOverview($aFiles, $cfg['path']['contenido_fullhtml'].$cfg['path']['plugins'].__plugin_systemtools_path__.'db-dump/', $area.'_action', 'display_databasedumps', 'delete_file', 'true', $area, $frame, $sess);
				$tpl->reset();
				$tpl->set('s', 'content', $sHTMLOutput);
				$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
			}
			break;	
		###########################################################################################
		# copy client	
		case 'copy_client':
			
			/**
			 * TODO: relation con_art_spec and con_container_conf
			 * TODO: copy frontend directory
			 * TODO: userinterface
			 * TODO: more abstraction
			 */
			
			include_once($cfg['path']['contenido'].$cfg['path']['includes'].'functions.str.php');
			include_once($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'classes/PropertyExtended.php');
			include_once($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'pear/HTML/Select.php');
			
			
			if ($_REQUEST['do_copy_client'] == 'true' AND is_int((int)$_REQUEST['source_client']) AND $_REQUEST['source_client'] > 0)
			{
				$tpl->reset();
				$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page_open.html');
				
				$sLogfile = $cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'logs/systemtool.txt';
				
				$bDebug = false;
				$bDisplaySystemMessage = false;
				
			 	###########################################################################################
				# Source Client
				$iSourceClient = $_REQUEST['source_client'];
				
				###########################################################################################
				# Create Target Client
				
				$sSourceClientName = tool_getClientName ($iSourceClient, $cfg, $db, $bDebug);
				$iTargetClient = tool_setClient ('Copy of '.$sSourceClientName, '', '', 0, 0, $cfg, $db, $auth, $bDebug);
				
				if ($bDisplaySystemMessage) {print "<pre>TargetClient "; print $iTargetClient ; print "</pre>";}
				$notification->displayNotification("info", "Target client 'Copy of ".$sSourceClientName."' (".$iTargetClient.") created.");
				
				###########################################################################################
				# Languages
				
				$aLanguages = tool_getLanguagesByClient($iSourceClient, $cfg, $db, $bDebug);
				#print "<pre>Languages<br>"; print_r($aLanguages); print "</pre>";
				
				$aSourceTargetLanguageMapping = array();
				$sHTMLNotification = '';
				for ($i = 0; $i < count($aLanguages); $i++)
				{
					$oLanguage = &$aLanguages[$i];
					$iNewLanguageId = tool_CreateLanguage($iTargetClient, $oLanguage->name, $oLanguage->active, $oLanguage->encoding, $oLanguage->direction, $cfg, $db, $auth, $bDebug);
					# Source-Language <==> Target-Language Mapping!
					$aSourceTargetLanguageMapping[$oLanguage->idlang] = $iNewLanguageId;
					$sHTMLNotification .= "language id ".$iNewLanguageId."<br>";
				}
				
				if ($bDisplaySystemMessage) {print "<pre>LanguageMapping<br>"; print_r($aSourceTargetLanguageMapping); print "</pre>";}
				$notification->displayNotification("info", "New languages created.<br>".$sHTMLNotification);
				
				###########################################################################################
				# Layouts
				
				$aLayouts = tool_getLayoutsByClient($iSourceClient, $cfg, $db, $bDebug); 
				
				$aSourceTargetLayoutMapping = array();
				$sHTMLNotification = '';
				for ($i = 0; $i < count($aLayouts); $i++)
				{
					$oLayout = &$aLayouts[$i];
					
					$iNewLayoutId = tool_createLayout($iTargetClient, $oLayout->name, $oLayout->description, $oLayout->code, $auth, $cfg, $db, $bDebug, $bDebug, $sLogfile);
					
					# Source-Layout <==> Target-Layout Mapping!
					$aSourceTargetLayoutMapping[$oLayout->idlay] = $iNewLayoutId;
					$sHTMLNotification .= "layout id ".$iNewLayoutId."<br>";
				}
				
				if ($bDisplaySystemMessage) {print "<pre>LayoutMapping<br>"; print_r($aSourceTargetLayoutMapping); print "</pre>";}
				$notification->displayNotification("info", "New layouts created.<br>".$sHTMLNotification);
				
				###########################################################################################
				# Modules
				
				$aModules = tool_getModulesByClient($iSourceClient, $cfg, $db, $bDebug);
				
				$aSourceTargetModuleMapping = array();
				$sHTMLNotification = '';
				for ($i = 0; $i < count($aModules); $i++)
				{
					$oModule = &$aModules[$i];
					
					$iNewModuleId = tool_createModule($iTargetClient, $oModule->name, $oModule->description, $oModule->input, $oModule->output, "", $oModule->type, $db, $auth, $cfg, $bDebug, $bDebug, $sLogfile);
					
					# Source-Module <==> Target-Module Mapping!
					$aSourceTargetModuleMapping[$oModule->idmod] = $iNewModuleId;
					$sHTMLNotification .= "module id ".$iNewModuleId."<br>";
				}
				
				if ($bDisplaySystemMessage) {print "<pre>ModuleMapping<br>"; print_r($aSourceTargetModuleMapping); print "</pre>";}
				$notification->displayNotification("info", "New modules created.<br>".$sHTMLNotification);
				
				###########################################################################################
				# Templates
				
				$aTemplates = tool_getTemplatesByClient($iSourceClient, $cfg, $db, $bDebug);
				
				$aSourceTargetTemplateMapping = array();
				$sHTMLNotification = '';
				for ($i = 0; $i < count($aTemplates); $i++)
				{
					$oTemplate = &$aTemplates[$i];
					
					# map layout
					$iMapNewLayoutId = $aSourceTargetLayoutMapping[$oTemplate->idlay];
					$iNewTemplateId = tool_createTemplate($iTargetClient, $iMapNewLayoutId, $oTemplate->name, $oTemplate->description, $oTemplate->defaulttemplate, $db, $auth, $cfg, $bDebug, $bDebug, $sLogFile = '');

					# Source-Template <==> Target-Template Mapping!
					$aSourceTargetTemplateMapping[$oTemplate->idtpl] = $iNewTemplateId;
					$sHTMLNotification .= "template id ".$iNewTemplateId."<br>";
				}
				
				if ($bDisplaySystemMessage) {print "<pre>TemplateMapping<br>"; print_r($aSourceTargetTemplateMapping); print "</pre>";}
				$notification->displayNotification("info", "New templates created.<br>".$sHTMLNotification);
				
				# copy complete client including
				if ($_REQUEST['clone'] == 'complete')
				{
				
					###########################################################################################
					# Container
					
					$aTemplates = tool_getTemplatesByClient($iSourceClient, $cfg, $db, $bDebug);
					
					$aSourceContainerModuleMapping = array();
					for ($i = 0; $i < count($aTemplates); $i++)
					{
						$oTemplate = &$aTemplates[$i];
						$aSourceContainerModuleMapping = tool_getContainerModuleMappingByTemplate($oTemplate->idtpl, $cfg, $db, $bDebug); 
						
						$aTargetContainerModuleMapping = array();
						for ($m = 0; $m < count($aSourceContainerModuleMapping); $m++)
						{
							$oContainer = &$aSourceContainerModuleMapping[$m];
							
							# map container module
							$iMapNewModuleId = $aSourceTargetModuleMapping[$oContainer->idmod];
							# map container template
							$iMapNewTemplateId = $aSourceTargetTemplateMapping[$oContainer->idtpl];
							tool_placeModuleToContainerByTemplate($iMapNewTemplateId, $oContainer->number, $iMapNewModuleId, $cfg, $db, $bDebug);
						}
					}
					
					$notification->displayNotification("info", "Container module mapping for new templates created.<br>");
					
					###########################################################################################
					# Category
					
					$aCategories = tool_getCategoriesByClient($iSourceClient, $cfg, $db, $bDebug);
					#print "<pre>Categories<br>"; print_r($aCategories); print "</pre>";
					
					$aSourceTargetCategoryMapping = array();
					$sHTMLNotification = '';
					for ($i = 0; $i < count($aCategories); $i++)
					{
						$oCategory = &$aCategories[$i];
						$iNewCategoryId = $db->nextid($cfg["tab"]["cat"]);
						# Source-Category <==> Target-Category Mapping!
						$aSourceTargetCategoryMapping[$oCategory->idcat] = $iNewCategoryId;
						$sHTMLNotification .= "category id ".$iNewCategoryId."<br>";
					}
					
					if ($bDisplaySystemMessage) {print "<pre>CategoryMapping<br>"; print_r($aSourceTargetCategoryMapping); print "</pre>";}
					
					for ($i = 0; $i < count($aCategories); $i++)
					{
						$oCategory = &$aCategories[$i];
						# map parent category
						$iNewParentId = ($oCategory->parentid == 0) ? 0 : $aSourceTargetCategoryMapping[$oCategory->parentid];
						# map pre category
						$iNewPreId = ($oCategory->preid == 0) ? 0 : $aSourceTargetCategoryMapping[$oCategory->preid];
						# map post category
						$iNewPostId = ($oCategory->postid == 0) ? 0 : $aSourceTargetCategoryMapping[$oCategory->postid];
						tool_CreateCategoryByGivenCategoryId($aSourceTargetCategoryMapping[$oCategory->idcat], $iTargetClient, $iNewParentId, $iNewPreId, $iNewPostId, $cfg, $db, $auth, $bDebug);
					}
					
					$notification->displayNotification("info", "New categories created.<br>".$sHTMLNotification);
					
					###########################################################################################
					# Articles
					
					$aArticles = tool_getArticlesByClient($iSourceClient, $cfg, $db, $bDebug);
					#print "<pre>Articles<br>"; print_r($aArticles); print "</pre>";
					
					$aSourceTargetArticleMapping = array();
					$sHTMLNotification = '';
					for ($i = 0; $i < count($aArticles); $i++)
					{
						$oArticle = &$aArticles[$i];
						$iNewArticleId = tool_CreateArticle($iTargetClient, $cfg, $db, $auth, $bDebug);
						# Source-Article <==> Target-Article Mapping!
						$aSourceTargetArticleMapping[$oArticle->idart] = $iNewArticleId;
						$sHTMLNotification .= "article id ".$iNewArticleId."<br>";
					}
					
					if ($bDisplaySystemMessage) {print "<pre>ArticleMapping<br>"; print_r($aSourceTargetArticleMapping); print "</pre>";}
					$notification->displayNotification("info", "New articles created.<br>".$sHTMLNotification);
					
					###########################################################################################
					# Category-Article relation
					
					$aCategoryArticle = tool_getCategoryArticlesByClient($iSourceClient, $cfg, $db, $bDebug);
					#print "<pre>CategoryArticle<br>"; print_r($aCategoryArticle); print "</pre>";
					
					$aSourceTargetCategoryArticleMapping = array();
					for ($i = 0; $i < count($aCategoryArticle); $i++)
					{
						$oCategoryArticle = &$aCategoryArticle[$i];
						# map category, article
						$iNewCategorArticleId = tool_CreateCategoryArticle($aSourceTargetCategoryMapping[$oCategoryArticle->idcat], $aSourceTargetArticleMapping[$oCategoryArticle->idart], $oCategoryArticle->is_start, $oCategoryArticle->status, $oCategoryArticle->createcode, $cfg, $db, $auth, $bDebug);
						# Source-CategoryArticle <==> Target-CategoryArticle Mapping!
						$aSourceTargetCategoryArticleMapping[$oCategoryArticle->idcatart] = $iNewCategorArticleId;
					}
					
					if ($bDisplaySystemMessage) {print "<pre>CategoryArticleMapping<br>"; print_r($aSourceTargetCategoryArticleMapping); print "</pre>";}
					$notification->displayNotification("info", "Category article relations created.<br>");
					
					###########################################################################################
					# Article-Language relation
					
					$aArticleLanguage = tool_getArticleLanguageByClient($iSourceClient, $cfg, $db, $bDebug);
					#print "<pre>ArticleLanguage<br>"; print_r($aArticleLanguage); print "</pre>";
					
					$aSourceTargetArticleLanguageMapping = array();
					for ($i = 0; $i < count($aArticleLanguage); $i++)
					{
						$oArticleLanguage = &$aArticleLanguage[$i];
						#print "<pre>oArticleLanguage ".$oArticleLanguage->idart." <pre>";
						
						# map idtplcfg
						#print "<pre>oArticleLanguage->idtplcfg ".$oArticleLanguage->idtplcfg."<pre>";
						if ($oArticleLanguage->idtplcfg > 0)
						{
							$iSourceTemplateId = tool_getTemplateByTemplateConfiguration($oArticleLanguage->idtplcfg, $cfg, $db, $bDebug);
							#print "<pre>SourceTemplateId ".$iSourceTemplateId."<pre>";
							$iTargetTemplateId = $aSourceTargetTemplateMapping[$iSourceTemplateId];
							#print "<pre>TargetTemplateId ".$iTargetTemplateId."<pre>";
							if (is_int((int)$iTargetTemplateId) AND $iTargetTemplateId > 0) 
							{ 
								$iTargetTemplateConfigurationId = tool_setTemplateConfiguration($iTargetTemplateId, $cfg, $db, $auth, $bDebug);
							}else
							{
								$iTargetTemplateConfigurationId = 0;
							}
						}else
						{
							$iTargetTemplateConfigurationId = 0;
						}
						
						#print "<pre>TargetTemplateConfigurationId ".$iTargetTemplateConfigurationId."<pre>";
						
						# map article, language
						$iNewArticleLanguageId = tool_CreateArticleLanguage($aSourceTargetArticleMapping[$oArticleLanguage->idart], $aSourceTargetLanguageMapping[$oArticleLanguage->idlang], $iTargetTemplateConfigurationId, $oArticleLanguage->title, $oArticleLanguage->pagetitle, $oArticleLanguage->summary, $oArticleLanguage->artspec, $oArticleLanguage->online, $oArticleLanguage->redirect, $oArticleLanguage->redirect_url, $oArticleLanguage->artsort, $oArticleLanguage->timemgmt, $oArticleLanguage->datestart, $oArticleLanguage->dateend, $cfg, $db, $auth, $bDebug);
						# Source-ArticleLanguage <==> Target-ArticleLanguage Mapping!
						$aSourceTargetArticleLanguageMapping[$oArticleLanguage->idartlang] = $iNewArticleLanguageId;
					}
					
					if ($bDisplaySystemMessage) {print "<pre>ArticleLanguageMapping<br>"; print_r($aSourceTargetArticleLanguageMapping); print "</pre>";}
					$notification->displayNotification("info", "Article language relations created.<br>");
					
					###########################################################################################
					# Category-Language relation
					
					$aCategoryLanguage = tool_getCategoryLanguageByClient($iSourceClient, $cfg, $db, $bDebug);
					#print "<pre>CategoryLanguage<br>"; print_r($aCategoryLanguage); print "</pre>";
					
					for ($i = 0; $i < count($aCategoryLanguage); $i++)
					{
						$oCategoryLanguage = &$aCategoryLanguage[$i];
						
						#print "<pre>oCategoryLanguage ".$oCategoryLanguage->idcat." ".$oCategoryLanguage->name."<pre>";
						#print "<pre>oCategoryLanguage->idtplcfg ".$oCategoryLanguage->idtplcfg."<pre>";
						
						# map idtplcfg
						if ($oCategoryLanguage->idtplcfg > 0)
						{
							$iSourceTemplateId = tool_getTemplateByTemplateConfiguration($oCategoryLanguage->idtplcfg, $cfg, $db, $bDebug);
							#print "<pre>SourceTemplateId ".$iSourceTemplateId."<pre>";
							$iTargetTemplateId = $aSourceTargetTemplateMapping[$iSourceTemplateId];
							#print "<pre>TargetTemplateId ".$iTargetTemplateId."<pre>";
							if (is_int((int)$iTargetTemplateId) AND $iTargetTemplateId > 0) 
							{ 
								$iTargetTemplateConfigurationId = tool_setTemplateConfiguration($iTargetTemplateId, $cfg, $db, $auth, $bDebug);
							}else
							{
								$iTargetTemplateConfigurationId = 0;
							}
						}else
						{
							$iTargetTemplateConfigurationId = 0;
						}
						
						#print "<pre>TargetTemplateConfigurationId ".$iTargetTemplateConfigurationId."<pre>";
						#print "<pre>oCategoryLanguage startidartlang ".$oCategoryLanguage->startidartlang."<pre>";
						# map startarticle
						if ($oCategoryLanguage->startidartlang == 0)
						{
							$iTargetStartArticleLanguageId = 0;
						}elseif($oCategoryLanguage->startidartlang > 0)
						{
							$iTargetStartArticleLanguageId = $aSourceTargetArticleLanguageMapping[$oCategoryLanguage->startidartlang];
							if (!is_int((int)$iTargetStartArticleLanguageId) OR $iTargetStartArticleLanguageId < 0)
							{
								$iTargetStartArticleLanguageId = 0;
							}
						}else
						{
							$iTargetStartArticleLanguageId = 0;
						}
						
						#print "<pre>TargetStartArticleLanguageId ".$iTargetStartArticleLanguageId."<pre>";
						# map category, language
						tool_CreateCategoryLanguage($aSourceTargetCategoryMapping[$oCategoryLanguage->idcat], $aSourceTargetLanguageMapping[$oCategoryLanguage->idlang], $iTargetTemplateConfigurationId, $oCategoryLanguage->name, $oCategoryLanguage->visible, $oCategoryLanguage->public, $oCategoryLanguage->status, $iTargetStartArticleLanguageId, $oCategoryLanguage->urlname, $cfg, $db, $auth, $bDebug);	
					}
					
					$notification->displayNotification("info", "Category language relations created.<br>");
					
					###########################################################################################
					# Upload
					
					$aUpload = tool_getUploadElementsByClient($iSourceClient, $cfg, $db, $bDebug);
					$aSourceTargetUploadMapping = array();
					for ($i = 0; $i < count($aUpload); $i++)
					{
						$oUpload = &$aUpload[$i];
						#print "<pre>Upload<br>"; print_r($oUpload); print "</pre>";
						
						$iNewUploadId = tool_setUploadElement ($iTargetClient, $oUpload->filename, $oUpload->dirname, $oUpload->filetype, $oUpload->size, $oUpload->description, $cfg, $db, $auth, $bDebug);
						#print "<pre>NewUploadId "; print($iNewUploadId); print "</pre>";
						# Source-Upload <==> Target-Upload Mapping!
						$aSourceTargetUploadMapping[$oUpload->idupl] = $iNewUploadId;
						
					}
					
					if ($bDisplaySystemMessage) {print "<pre>UploadMapping<br>"; print_r($aSourceTargetUploadMapping); print "</pre>";}
					$notification->displayNotification("info", "Upload relation cloned.<br>");
			
					###########################################################################################
					# Content
					
					$aKeysOfSourceTargetArticleLanguageMapping = array_keys($aSourceTargetArticleLanguageMapping);
					#print "<pre>ArticleLanguageMapping<br>"; print_r($aSourceTargetArticleLanguageMapping); print "</pre>";
					
					for ($i = 0; $i < count($aKeysOfSourceTargetArticleLanguageMapping); $i++)
					{
						$iSourceArticleLanguageId = $aKeysOfSourceTargetArticleLanguageMapping[$i];
						#print "<pre>SourceArticleLanguageId ".$iSourceArticleLanguageId."<pre>";
						$aArticleContent = tool_getContentByArticleLanguageId($iSourceArticleLanguageId, $cfg, $db, $bDebug);
						
						for ($p = 0; $p < count($aArticleContent); $p++)
						{
							$oArticleContent = &$aArticleContent[$p];
							# map article-language
							$iTargetArticleLanguageId = $aSourceTargetArticleLanguageMapping[$iSourceArticleLanguageId];
							#print "<pre>TargetArticleLanguageId ".$iTargetArticleLanguageId."<pre>";
							
							# if image content type (CMS_IMG)
							if ($oArticleContent->idtype == 4)
							{
								# map upload id 
								$iTargetUploadId = $aSourceTargetUploadMapping[$oArticleContent->value];
								tool_setContent($iTargetArticleLanguageId, $oArticleContent->idtype, $oArticleContent->typeid, $iTargetUploadId, $cfg, $db, $auth, $bDebug);
							}elseif ($oArticleContent->idtype == 6) # if link content type (CMS_LINK)
							{
								# map category-article id
								if (is_int((int)$oArticleContent->value) AND $oArticleContent->value > 0)
								{
									# internal link
									$sContent = $aSourceTargetCategoryArticleMapping[$oArticleContent->value];
								}else
								{
									$sContent = $oArticleContent->value;
								}
								tool_setContent($iTargetArticleLanguageId, $oArticleContent->idtype, $oArticleContent->typeid, $sContent, $cfg, $db, $auth, $bDebug);
							}else
							{
								tool_setContent($iTargetArticleLanguageId, $oArticleContent->idtype, $oArticleContent->typeid, $oArticleContent->value, $cfg, $db, $auth, $bDebug);
							}
						}
					}
					
					$notification->displayNotification("info", "Content cloned.<br>");
					
					###########################################################################################
					# Client Settings
					
					$oSourceClientProperties = new PropertyExtended($iSourceClient, $db, $cfg, $auth);
					$oSourceClientProperties->bDebug = $bDebug;
					
					$oTargetClientProperties = new PropertyExtended($iTargetClient, $db, $cfg, $auth);
					$oTargetClientProperties->bDebug = $bDebug;
					
					$aSourceClientProperties = $oSourceClientProperties->getPropertiesByItemType('clientsetting');
					#print "<pre>SourceClientProperties<br>"; print_r($aSourceClientProperties); print "</pre>";
					
					for ($i = 0; $i < count($aSourceClientProperties); $i++)
					{
						$oSourceClientProperty = &$aSourceClientProperties[$i];
						$oTargetClientProperties->setProperty($oSourceClientProperty->itemtype, $iTargetClient, $oSourceClientProperty->type, $oSourceClientProperty->name, urldecode($oSourceClientProperty->value));
					}
	
					$notification->displayNotification("info", "Client settings cloned.<br>");
					
					###########################################################################################
					# create pseudo tree-structure in con_cat_tree
					strRemakeTreeTable();
				}
				
				$notification->displayNotification("info", "Copy of client '".$sSourceClientName."' done.<br>");
				
				$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page_close.html');
				
			}else	
			{
				
				$aClients = tool_getClients($cfg, $db, false); 
				$aClientOptions = array();
				for ($i = 0; $i < count($aClients); $i++)
				{
					$oClient = &$aClients[$i];
					$aClientOptions[$oClient->name." (".$oClient->idclient.")"] = $oClient->idclient; 
				}
				
				# generate select options 
				$oSelect = new HTML_Select;
				$oSelect->HTML_Select("source_client", 1, false, array("class" => "text_medium"));
				$oSelect->addOption(i18n("Select source client", "systemtools"), '');
				$oSelect->loadArray($aClientOptions, $_REQUEST['source_client']);
				$sSelectOptions = $oSelect->toHtml();

				$tpl->reset();
				$tpl->set('s', 'title', i18n("Copy client", "systemtools"));
				$tpl->set('s', 'area', $area);
				$tpl->set('s', 'frame', $frame);
				$tpl->set('s', 'session', $sess->id);
				$tpl->set('s', 'action_name', $area.'_action');
				$tpl->set('s', 'action_value', 'copy_client');
				$tpl->set('s', 'action2_name', 'do_copy_client');
				$tpl->set('s', 'action2_value', 'true');
				$tpl->set('s', 'notification', i18n("A copy of the source client will be created", "systemtools").".<br>".i18n("Therefore a new client will be created", "systemtools").".<br>".i18n("The corresponding languages, layouts, modules, templates, categories, articles and clientsettings of the source client will be cloned to the target client", "systemtools").".");
				$sHTMLWarning = $notification->messageBox("warning", i18n("Please backup your database!", "systemtools")."<br><br>".i18n("Note: The frontend files (css, images, js, ...) of the source client will not be copied.", "systemtools")." ".i18n("Please copy the frontend files of the source client manually.", "systemtools")."<br>".i18n("Remember to update the server-path and web-address under 'Administration -- Clients' and to change the values '\$load_lang' and '\$load_client' in file __frontend__path__/config.php.", "systemtools")."<br>", 0);
				$tpl->set('s', 'warning', $sHTMLWarning);
				$tpl->set('s', 'options', $sSelectOptions);
                $tpl->set('s', 'confirm_header', i18n("Do you really want to", "systemtools"));
				$tpl->set('s', 'confirm', i18n("Copy client?", "systemtools"));
				$tpl->set('s', 'notification2', i18n("Complete copy of the source client: languages, layouts, modules, templates, categories, articles and clientsettings", "systemtools"));
				$tpl->set('s', 'notification3', i18n("Only languages, layouts, modules, templates", "systemtools"));
				$sHTMLOutput = $tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/system_action_copy_clients.html', true);
				$tpl->set('s', 'content', $sHTMLOutput);
				$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
				
			}
			break;
			
		###########################################################################################	
		# compare clients modules
		case 'compare_client_modules':
		
			/**
			 * TODO: make second parameter of object My_Text_Diff_Renderer optional.
			 * TODO: more options
			 */
		
			include_once ($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'classes/external/pear/Text_Diff-0.1.1/Diff.php');
			include_once ($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'classes/external/pear/Text_Diff-0.1.1/Diff/MyRenderer.php');
			include_once($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'pear/HTML/Select.php');
			
			if ($_REQUEST['do_compare_client_modules'] == 'true' AND is_int((int)$_REQUEST['source_client']) AND $_REQUEST['source_client'] > 0 AND is_int((int)$_REQUEST['target_client']) AND $_REQUEST['target_client'] > 0)
			{
			
				$iSourceClient = $_REQUEST['source_client'];
				$iTargetClient = $_REQUEST['target_client'];
				
				$sHTMLOutput = '';
				
				$arrayModulesSourceClient = tool_getModulesByClientIndexedByName($iSourceClient, $cfg, $db, false);
				#print "<pre>"; print_r($arrayModulesSourceClient); print "</pre>";
				
				$arrayModulesTargetClient = tool_getModulesByClientIndexedByName($iTargetClient, $cfg, $db, false);
				#print "<pre>"; print_r($arrayModulesTargetClient); print "</pre>";
				
				$arrayModulesSourceClientKeys = array_keys($arrayModulesSourceClient);
				#print "<pre>"; print_r($arrayModulesSourceClientKeys); print "</pre>";
				
				$arrayModulesTargetClientKeys = array_keys($arrayModulesTargetClient);
				#print "<pre>"; print_r($arrayModulesTargetClientKeys); print "</pre>";
				
				$tpl->reset();
				
				$tpl->set('s', 'title1', 'Modules of Client: <span style="color: blue;">'.tool_getClientName($iSourceClient, $cfg, $db, false).'</span>');
				$sHTML_List_Template = '<ul>{elements}</ul>';
				$sListElements = '';
				for ($j = 0; $j < count($arrayModulesSourceClientKeys); $j++)
				{
					$sListElements .= '<li>'.$arrayModulesSourceClientKeys[$j].'</li>';
				}
				$tpl->set('s', 'list1', $sListElements);
				
				$tpl->set('s', 'title2', 'Modules of Client: <span style="color: blue;">'.tool_getClientName($iTargetClient, $cfg, $db, false).'</span>');
				$sListElements = '';
				for ($k = 0; $k < count($arrayModulesTargetClientKeys); $k++)
				{
					$sListElements .= '<li>'.$arrayModulesTargetClientKeys[$k].'</li>';
				}
				$tpl->set('s', 'list2', $sListElements);
				
				$sHTMLOutput = $tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/system_action_compare_clients_intro.html', true);
				
				for ($i = 0; $i < count($arrayModulesSourceClientKeys); $i++)
				{
					$objModuleSourceClient = &$arrayModulesSourceClient[$arrayModulesSourceClientKeys[$i]];
					#print "<pre>"; print_r($objModuleSourceClient); print "</pre>";
					
					$arrayFileSourceClient = array();
					$arrayFileTargetClient = array();
					
					if (array_key_exists($objModuleSourceClient->name, $arrayModulesTargetClient))
					{
						
						$objModuleTargetClient = &$arrayModulesTargetClient[$objModuleSourceClient->name];
						
						#### Compare module input 
						/* Load the lines of each file. */
						$arrayFileSourceClient = explode("\n", $objModuleSourceClient->input);
						$arrayFileTargetClient = explode("\n", $objModuleTargetClient->input);
						
						/* Create the Diff object. */
						$objDiff = &new Text_Diff($arrayFileSourceClient, $arrayFileTargetClient);
						
						#print "<pre>"; print_r($objDiff); print "</pre>";
						
						/* Output the diff in table format. */
						$arrayParams = array();
						$objRenderer = &new My_Text_Diff_Renderer('Compare module <span style="color: red;">INPUT</span>: <span style="color: blue;">'.$objModuleSourceClient->name.'</span>', false, $arrayParams, $cfg, $cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/system_action_compare_clients.html');
						$sHTMLOutput .= $objRenderer->render($objDiff);
						
						#### Compare module output
						/* Load the lines of each file. */
						$arrayFileSourceClient = explode("\n", $objModuleSourceClient->output);
						$arrayFileTargetClient = explode("\n", $objModuleTargetClient->output);
						
						#print "<pre>arrayFileSourceClient <br>"; print_r($arrayFileSourceClient); print "</pre>";
						#print "<pre>arrayFileTargetClient <br>"; print_r($arrayFileTargetClient); print "</pre>";
						
						/* Create the Diff object. */
						$objDiff = &new Text_Diff($arrayFileSourceClient, $arrayFileTargetClient);
						
						#print "<pre>"; print_r($objDiff); print "</pre>";
						
						/* Output the diff in table format. */
						$arrayParams = array();
						$objRenderer = &new My_Text_Diff_Renderer('Compare module <span style="color: red;">OUTPUT</span>: <span style="color: blue;">'.$objModuleSourceClient->name.'</span>', false, $arrayParams, $cfg, $cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/system_action_compare_clients.html');
						$sHTMLOutput .= $objRenderer->render($objDiff);
						
					}
				}
	
				$tpl->reset();
				$tpl->set('s', 'content', $sHTMLOutput);
				$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
			
			}else
			{
			
				$aClients = tool_getClients($cfg, $db, false); 
				#print "<pre>Clients<br>"; print_r($aClients); print "</pre>";
				$aClientOptions = array();
				for ($i = 0; $i < count($aClients); $i++)
				{
					$oClient = &$aClients[$i];
					$aClientOptions[$oClient->name." (".$oClient->idclient.")"] = $oClient->idclient; 
				}
				
				#print "<pre>ClientOptions<br>"; print_r($aClientOptions); print "</pre>";
				
				# generate select options 
				$oSelect = new HTML_Select;
				$oSelect->HTML_Select("source_client", 1, false, array("class" => "text_medium"));
				$oSelect->addOption(i18n("Select source client", "systemtools"), '');
				$oSelect->loadArray($aClientOptions, $_REQUEST['source_client']);
				$sSelectOptions = $oSelect->toHtml();
				
				# generate select options 
				$oSelect2 = new HTML_Select;
				$oSelect2->HTML_Select("target_client", 1, false, array("class" => "text_medium"));
				$oSelect2->addOption(i18n("Select target client", "systemtools"), '');
				$oSelect2->loadArray($aClientOptions, $_REQUEST['target_client']);
				$sSelectOptions2 = $oSelect2->toHtml();
	
				$tpl->reset();
				$tpl->set('s', 'title', i18n("Compare modules of clients", "systemtools"));
				$tpl->set('s', 'area', $area);
				$tpl->set('s', 'frame', $frame);
				$tpl->set('s', 'session', $sess->id);
				$tpl->set('s', 'action_name', $area.'_action');
				$tpl->set('s', 'action_value', 'compare_client_modules');
				$tpl->set('s', 'action2_name', 'do_compare_client_modules');
				$tpl->set('s', 'action2_value', 'true');
				$tpl->set('s', 'notification', i18n("Compare modules", "systemtools").".<br>");
				$tpl->set('s', 'warning', '');
				$tpl->set('s', 'options', $sSelectOptions.$sSelectOptions2);
                $tpl->set('s', 'confirm_header', i18n("Do you really want to", "systemtools"));
				$tpl->set('s', 'confirm', i18n("Compare modules of clients?", "systemtools"));
				$sHTMLOutput = $tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/system_action.html', true);
				$tpl->set('s', 'content', $sHTMLOutput);
				$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
			
			}
			break;
			
		###########################################################################################	
		# compare clients layouts	
		case 'compare_client_layouts':
		
			/**
			 * TODO: make second parameter of object My_Text_Diff_Renderer optional.
			 */
		
			include_once ($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'classes/external/pear/Text_Diff-0.1.1/Diff.php');
			include_once ($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'classes/external/pear/Text_Diff-0.1.1/Diff/MyRenderer.php');
			include_once($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'pear/HTML/Select.php');
			
			if ($_REQUEST['do_compare_client_layouts'] == 'true' AND is_int((int)$_REQUEST['source_client']) AND $_REQUEST['source_client'] > 0 AND is_int((int)$_REQUEST['target_client']) AND $_REQUEST['target_client'] > 0)
			{
			
				$iSourceClient = $_REQUEST['source_client'];
				$iTargetClient = $_REQUEST['target_client'];
				
				$sHTMLOutput = '';
				
				$arrayLayoutsSourceClient = tool_getLayoutsByClientIndexedByName($iSourceClient, $cfg, $db, false);
				#print "<pre>"; print_r($arrayLayoutsSourceClient); print "</pre>";
				#
				$arrayLayoutsTargetClient = tool_getLayoutsByClientIndexedByName($iTargetClient, $cfg, $db, false);
				#print "<pre>"; print_r($arrayLayoutsTargetClient); print "</pre>";
				
				$arrayLayoutsSourceClientKeys = array_keys($arrayLayoutsSourceClient);
				#print "<pre>"; print_r($arrayLayoutsSourceClientKeys); print "</pre>";
				
				$arrayLayoutsTargetClientKeys = array_keys($arrayLayoutsTargetClient);
				#print "<pre>"; print_r($arrayLayoutsTargetClientKeys); print "</pre>";
				
				$tpl->reset();
				
				$tpl->set('s', 'title1', 'Layouts of Client: <span style="color: blue;">'.tool_getClientName($iSourceClient, $cfg, $db, false).'</span>');
				$sHTML_List_Template = '<ul>{elements}</ul>';
				$sListElements = '';
				for ($j = 0; $j < count($arrayLayoutsSourceClientKeys); $j++)
				{
					$sListElements .= '<li>'.$arrayLayoutsSourceClientKeys[$j].'</li>';
				}
				$tpl->set('s', 'list1', $sListElements);
				
				$tpl->set('s', 'title2', 'Layouts of Client: <span style="color: blue;">'.tool_getClientName($iTargetClient, $cfg, $db, false).'</span>');
				$sListElements = '';
				for ($k = 0; $k < count($arrayLayoutsTargetClientKeys); $k++)
				{
					$sListElements .= '<li>'.$arrayLayoutsTargetClientKeys[$k].'</li>';
				}
				$tpl->set('s', 'list2', $sListElements);
				
				$sHTMLOutput = $tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/system_action_compare_clients_intro.html', true);
				
				for ($i = 0; $i < count($arrayLayoutsSourceClientKeys); $i++)
				{
					$objLayoutSourceClient = &$arrayLayoutsSourceClient[$arrayLayoutsSourceClientKeys[$i]];
					#print "<pre>"; print_r($objLayoutSourceClient); print "</pre>";
					
					$arrayFileSourceClient = array();
					$arrayFileTargetClient = array();
					
					if (array_key_exists($objLayoutSourceClient->name, $arrayLayoutsTargetClient))
					{
						
						$objLayoutTargetClient = &$arrayLayoutsTargetClient[$objLayoutSourceClient->name];
						
						#### Compare layout
						/* Load the lines of each file. */
						$arrayFileSourceClient = explode("\n", $objLayoutSourceClient->code);
						$arrayFileTargetClient = explode("\n", $objLayoutTargetClient->code);
						
						#print "<pre>arrayFileSourceClient <br>"; print_r($arrayFileSourceClient); print "</pre>";
						#print "<pre>arrayFileTargetClient <br>"; print_r($arrayFileTargetClient); print "</pre>";
						
						/* Create the Diff object. */
						$objDiff = &new Text_Diff($arrayFileSourceClient, $arrayFileTargetClient);
						
						#print "<pre>"; print_r($objDiff); print "</pre>";
						
						/* Output the diff in table format. */
						$arrayParams = array();
						$objRenderer = &new My_Text_Diff_Renderer('Compare layout: <span style="color: blue;">'.$objLayoutSourceClient->name.'</span>', false, $arrayParams, $cfg, $cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/system_action_compare_clients.html');
						$sHTMLOutput .= $objRenderer->render($objDiff);
						
					}
				}
	
				$tpl->reset();
				$tpl->set('s', 'content', $sHTMLOutput);
				$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
			
			}else
			{
			
				$aClients = tool_getClients($cfg, $db, false); 
				#print "<pre>Clients<br>"; print_r($aClients); print "</pre>";
				$aClientOptions = array();
				for ($i = 0; $i < count($aClients); $i++)
				{
					$oClient = &$aClients[$i];
					$aClientOptions[$oClient->name." (".$oClient->idclient.")"] = $oClient->idclient; 
				}
				
				#print "<pre>ClientOptions<br>"; print_r($aClientOptions); print "</pre>";
				
				# generate select options 
				$oSelect = new HTML_Select;
				$oSelect->HTML_Select("source_client", 1, false, array("class" => "text_medium"));
				$oSelect->addOption(i18n("Select source client", "systemtools"), '');
				$oSelect->loadArray($aClientOptions, $_REQUEST['source_client']);
				$sSelectOptions = $oSelect->toHtml();
				
				# generate select options 
				$oSelect2 = new HTML_Select;
				$oSelect2->HTML_Select("target_client", 1, false, array("class" => "text_medium"));
				$oSelect2->addOption(i18n("Select target client", "systemtools"), '');
				$oSelect2->loadArray($aClientOptions, $_REQUEST['target_client']);
				$sSelectOptions2 = $oSelect2->toHtml();
	
				$tpl->reset();
				$tpl->set('s', 'title', i18n("Compare layouts of clients", "systemtools"));
				$tpl->set('s', 'area', $area);
				$tpl->set('s', 'frame', $frame);
				$tpl->set('s', 'session', $sess->id);
				$tpl->set('s', 'action_name', $area.'_action');
				$tpl->set('s', 'action_value', 'compare_client_layouts');
				$tpl->set('s', 'action2_name', 'do_compare_client_layouts');
				$tpl->set('s', 'action2_value', 'true');
				$tpl->set('s', 'notification', i18n("Compare layouts", "systemtools").".<br>");
				$tpl->set('s', 'warning', '');
				$tpl->set('s', 'options', $sSelectOptions.$sSelectOptions2);
                $tpl->set('s', 'confirm_header', i18n("Do you really want to", "systemtools"));
				$tpl->set('s', 'confirm', i18n("Compare layouts of clients?", "systemtools"));
				$sHTMLOutput = $tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/system_action.html', true);
				$tpl->set('s', 'content', $sHTMLOutput);
				$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
			
			}
			break;
		###########################################################################################
		# dump upload directory	
		/*
		case 'dump_upload_directory':
		
			print "<pre>tar upload directory</pre>";
			print "<pre>".$cfgClient[$client]['path']['frontend'].$cfgClient[$client]['upload'].'</pre>';
			
			if(chdir($cfgClient[$client]['path']['frontend']))
			{
				$sCmd = 'tar -cvzf upload_'.time().'_.tar.gz '.$cfgClient[$client]['upload'];
				print "<pre>".$sCmd."</pre>";
				exec($sCmd);
			}
			
			print "<pre>done</pre>";
			
			break;
		*/
		###########################################################################################	
		/**
		 * TODO: action display_client_modules
		 * TODO: action display_client_layouts
		 * 
		 * TODO: action export_client_modules
		 * TODO: action export_client_layouts
		 * 
		 * TODO: action compare_client_html_templates
		 * TODO: action compare_client_styles 
		 */
						
		###########################################################################################	
		# default			
		default:
		
			/**
			 * TODO: print some infos
			 */
			 
			$tpl->set('s', 'content', '');
			$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
			break;
	}
	
}else
{
	$sHTMLOutput = $notification->messageBox("error", i18n("No permission.", "systemtools"),0);
	$tpl->set('s', 'content', $sHTMLOutput);
	$tpl->generate($cfg['path']['contenido'].$cfg['path']['plugins'].__plugin_systemtools_path__.'templates/page.html');
}



?>