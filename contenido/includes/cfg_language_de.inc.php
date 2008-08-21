<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Language string for "Deutsch" (German)
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.0
 * @author     Olaf Niemann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2002-03-02
 *   modified 2008-06-25, Frederic Schneider, add security fix
 *   modified 2008-07-04, Dominik Ziegler, fixed bug CON-169
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("includes", "functions.i18n.php");

global $lngArea, $lngLogin, $lngErr, $a_description, $lngAct;

$lngLogin["pleaselogin"]                        	= "Bitte geben Sie Ihren Benutzernamen und Ihr Paßwort ein.";
$lngLogin["username"]                        		= "Benutzername";
$lngLogin["password"]                        		= "Paßwort";
$lngLogin["invalidlogin"]                			= "Entweder ist Ihr Benutzername oder Ihr Paßwort ungültig.<br>Bitte versuchen Sie es nochmal!";

$lngLogout["thanksforusingcontenido"]           	= "Vielen Dank, dass sie Contenido benutzt haben. Bis bald.";
$lngLogout["youareloggedout"]                		= "Sie sind jetzt abgemeldet.";
$lngLogout["backtologin1"]                			= "Hier kommen Sie wieder zur";
$lngLogout["backtologin2"]                			= "Anmeldung";

//Datas for Areas which are not in the Navigation
$lngArea["con_artlist"]                        		= " - Artikelliste";
$lngArea["con_editart"]                        		= " - Artikel bearbeiten";
$lngArea["lay_edit"]                            	= " - Layout bearbeiten";
$lngArea["mod_edit"]                            	= " - Modul bearbeiten";
$lngArea["tpl_edit"]                            	= " - Template bearbeiten";
$lngArea["news_edit"]                           	= " - Newsletter bearbeiten";

$lngCon["actionsconf"]                         		= "Aktionen";
$lngCon["allarts"]                        			= "Alle Artikeln";
$lngCon["artname"]                          		= "Artikelnname";
$lngCon["artoff"]                        			= "Artikel offline schalten";
$lngCon["arton"]                        			= "Artikel online schalten";
$lngCon["artswithoutcategory"]                		= "Artikeln ohne Zuordnung";
$lngCon["author"]                            		= "Redakteur";
$lngCon["created"]                            		= "Datum der Erstellung";
$lngCon["dateend"]                            		= "Enddatum";
$lngCon["datestart"]                          		= "Startdatum";
$lngCon["defaulttemplate"]                			= "Template";
$lngCon["imgagedescription"]                		= "Beschreibung";
$lngCon["lastmodified"]                    			= "Datum der letzten Änderung";
$lngCon["moduleincontainer"]                		= "Modul in Container";
$lngCon["noarts"]                            		= "Es gibt keine Artikel.";
$lngCon["noartsinthiscategory"]         			= "Es gibt keine Artikel in dieser Kategorie.";
$lngCon["nostartingart"]                 			= "Nicht als StartArtikel konfigurieren.";
$lngCon["online"]                            		= "Online";
$lngCon["preview"]                        			= "Vorschau";
$lngCon["properties"]                    			= "Eigenschaften";
$lngCon["startart"]                           		= "StartArtikel";
$lngCon["structure"]                            	= "Kategorie";
$lngCon["structureandarts"]                			= "Kategorie / Artikeln";
$lngCon["summary"]                            		= "Zusammenfassung";
$lngCon["redirect"]                        			= "Weiterleitung";
$lngCon["redirect_url"]                       		= "Weiterleitungsadresse";
$lngCon["template"]                            		= "Template";
$lngCon["title"]                           			= "Titel";
$lngCon["keywordart"]                       		= "Keywords Artikel";
$lngCon["keywordcat"]                        		= "Keywords Kategorie";
$lngCon["keywordautoart"]                   		= "Automatisch generiert";
$lngCon["unconfigured"]                      		= "unkonfiguriert";
$lngCon["dynhead"]                         			= "Überschrift";
$lngCon["dyntext"]                         			= "Text";
$lngCon["dynimg"]                         			= "Bild";
$lngCon["dynlink"]                         			= "Link";
$lngCon["actions"]["10"]                 			= "Artikeln der gewählten Kategorie";
$lngCon["actions"]["11"]                 			= "Liste aller Artikeln";
$lngCon["actions"]["12"]                 			= "Liste der Artikeln ohne Zuordnung";

$lngAct["con"]["con_lock"] 							= i18n("Freeze article");
$lngAct["con"]["con_makecatonline"] 				= i18n("Make category online");
$lngAct["con"]["con_changetemplate"] 				= i18n("Change template");
$lngAct["con"]["con_makestart"]                    	= i18n("Set start article");
$lngAct["con"]["con_makeonline"]                   	= i18n("Make article online");
$lngAct["con"]["con_synccat"]                    	= i18n("Syncronize Category");
$lngAct["con"]["con_syncarticle"]                   = i18n("Syncronize Article");
$lngAct["con"]["con_makepublic"]                   	= i18n("Protect category");
$lngAct["con"]["con_deleteart"]                    	= i18n("Delete article");
$lngAct["con"]["con_tplcfg_edit"]   				= i18n("Edit template configuration");
$lngAct["con"]["con_duplicate"] 					= i18n("Duplicate Article");
$lngAct["con"]["con_expand"]						= i18n("Expand boxes");
$lngAct["con_edittpl"]["10"]            			= i18n("Configure template");
$lngAct["con_editart"]["con_newart"]           		= i18n("Create article");
$lngAct["con_editart"]["35"]           				= i18n("Configure article");
$lngAct["con_editart"]["con_saveart"]           	= i18n("Save article");
$lngAct["con_editart"]["remove_assignments"]       	= i18n("Remove assignments");
$lngAct["con_editcontent"]["15"]        			= i18n("Edit article");
$lngAct["con_editart"]["con_edit"]        			= i18n("Edit article properties");
$lngAct["con_editcontent"]["con_editart"]        	= i18n("Edit article");
$lngAct["con_tplcfg"]["con_edddittemplate"] 		= i18n("Help");

$lngAct["str"]["str_renamecat"]                    	= i18n("Rename category");
$lngAct["str"]["str_newcat"]                    	= i18n("New category");
$lngAct["str"]["str_makevisible"]                   = i18n("Set category on- or offline");
$lngAct["str"]["50"]                    			= i18n("Disable category");
$lngAct["str"]["str_makepublic"]        			= i18n("Protect category");
$lngAct["str"]["front_allow"]        				= i18n("Frontend access");
$lngAct["str"]["str_deletecat"]            			= i18n("Delete category");
$lngAct["str"]["str_moveupcat"]                    	= i18n("Move category up");
$lngAct["str"]["str_movedowncat"]                   = i18n("Move category down");
$lngAct["str"]["str_movesubtree"]      				= i18n("Move category");
$lngAct["str"]["str_newtree"]           			= i18n("Create new tree");
$lngAct["str"]["str_duplicate"]						= i18n("Duplicate category");
$lngAct["str_tplcfg"]["str_tplcfg"]     			= i18n("Configure category");
$lngAct["str_tplcfg"]["tplcfg_edit"]     			= i18n("Edit category");

$lngAct["upl"]["upl_mkdir"]             			= i18n("Create directory");
$lngAct["upl"]["upl_upload"]            			= i18n("Upload files");
$lngAct["upl"]["upl_delete"]            			= i18n("Delete files");
$lngAct["upl"]["upl_rmdir"]							= i18n("Remove directory");
$lngAct["upl"]["upl_renamedir"]						= i18n("Rename directory");
$lngAct["upl"]["upl_modify_file"]					= i18n("Modify file");
$lngAct["upl"]["upl_renamefile"]					= i18n("Rename file");
$lngAct["upl"]["upl_multidelete"]					= i18n("Multidelete Files");
$lngAct["upl"]["21"]                    			= i18n("Delete file");
$lngAct["upl"]["40"]                    			= i18n("Upload files");
$lngAct["upl"]["31"]                    			= i18n("Create directory");

$lngAct["lay"]["lay_delete"]                    	= i18n("Delete layout");
$lngAct["lay_edit"]["lay_edit"]             		= i18n("Modify layout");
$lngAct["lay_edit"]["lay_new"]          			= i18n("Create layout");
$lngAct["lay_history"]["lay_history_manage"]    	= i18n("Manage History");
$lngAct["lay_history"]["history_truncate"]    	    = i18n("Truncate History");


$lngAct["mod"]["mod_delete"]                   		= i18n("Delete module");
$lngAct["mod_history"]["mod_history_manage"]    	= i18n("Manage History");
$lngAct["mod_history"]["history_truncate"]    	    = i18n("Truncate History");
$lngAct["mod_edit"]["mod_edit"]               		= i18n("Edit module");
$lngAct["mod_edit"]["mod_new"]          			= i18n("Create module");
$lngAct["mod_edit"]["mod_importexport_module"]		= i18n("Import/Export module");
$lngAct["mod_translate"]["mod_translation_save"] 	= i18n("Translate modules");
$lngAct["mod_translate"]["mod_importexport_translation"] = i18n("Translation import/export");
$lngAct["mod_package"]["mod_importexport_package"]	= i18n("Import/Export package");

$lngAct["tpl"]["tpl_delete"]            			= i18n("Delete template");
$lngAct["tpl_edit"]["tpl_edit"]         			= i18n("Edit template");
$lngAct["tpl_edit"]["tpl_new"]          			= i18n("Create template");
$lngAct["tpl_edit"]["tpl_duplicate"]				= i18n("Duplicate template");
$lngAct["tpl"]["tpl_duplicate"]         			= i18n("Duplicate template");
$lngAct["tpl_visual"]["tpl_visedit"]        		= i18n("Visual edit");

$lngAct["user"]["user_create"]          			= i18n("Create user");
$lngAct["user"]["user_delete"]          			= i18n("Delete user");
$lngAct["user_areas"]["user_saverightsarea"]		= i18n("Save user area rights");
$lngAct["user_create"]["user_createuser"]      		= i18n("Create user");
$lngAct["user_rights"]["10"]            			= i18n("Edit rights");
$lngAct["user_overview"]["user_edit"]            	= i18n("Edit user");

$lngAct["groups_members"]["group_deletemember"] 	= i18n("Delete group members");
$lngAct["groups_members"]["group_addmember"] 		= i18n("Add group members");
$lngAct["groups_overview"]["group_edit"]            = i18n("Edit group");
$lngAct["groups_create"]["group_create"]          	= i18n("Create group");
$lngAct["groups"]["group_delete"]          			= i18n("Delete group");

$lngAct["stat"]["stat_show"]                   		= i18n("Show statistics");

$lngAct["lang"]["lang_activatelanguage"] 			= i18n("Activate language");
$lngAct["lang"]["lang_deactivatelanguage"] 			= i18n("Deactivate language");
$lngAct["lang"]["lang_renamelanguage"]				= i18n("Rename language");
$lngAct["lang_edit"]["lang_newlanguage"] 			= i18n("Create language");
$lngAct["lang_edit"]["lang_deletelanguage"] 		= i18n("Delete language");
$lngAct["lang_edit"]["lang_edit"] 					= i18n("Edit language");

$lngAct["linkchecker"]["linkchecker"]               = i18n("Linkchecker");
$lngAct["linkchecker"]["whitelist_view"]            = i18n("Linkchecker Whitelist");

$lngAct["plug"]["10"]                    			= i18n("Install/Remove plugins");

$lngAct["style"]["style_edit"]          			= i18n("Modify CSS");
$lngAct["style"]["style_create"]        			= i18n("Create CSS");
$lngAct["style"]["style_delete"]        			= i18n("Delete CSS");
$lngAct["style_history"]["style_history_manage"]    	= i18n("Manage History");
$lngAct["style_history"]["history_truncate"]    	    = i18n("Truncate History");

$lngAct["js"]["js_edit"]                			= i18n("Edit script");
$lngAct["js"]["js_delete"]             				= i18n("Delete script");
$lngAct["js"]["js_create"]              			= i18n("Create script");
$lngAct["js_history"]["js_history_manage"]    	= i18n("Manage History");
$lngAct["js_history"]["history_truncate"]    	    = i18n("Truncate History");

$lngAct["htmltpl"]["htmltpl_edit"]          		= i18n("Modify HTML-Template");
$lngAct["htmltpl"]["htmltpl_create"]        		= i18n("Create HTML-Template");
$lngAct["htmltpl"]["htmltpl_delete"]        		= i18n("Delete HTML-Template");
$lngAct["htmltpl_history"]["htmltpl_history_manage"]    	= i18n("Manage History");
$lngAct["htmltpl_history"]["history_truncate"]    	    = i18n("Truncate History");

$lngAct["news"]["news_save"]                   					= i18n("Edit newsletter");
$lngAct["news"]["news_create"] 									= i18n("Create newsletter");
$lngAct["news"]["news_delete"]									= i18n("Delete newsletter");
$lngAct["news"]["news_duplicate"]								= i18n("Duplicate newsletter");
$lngAct["news"]["news_add_job"]									= i18n("Add newsletter dispatch job");
$lngAct["news"]["news_html_settings"]							= i18n("Change global HTML newsletter settings");
$lngAct["news"]["news_send_test"]								= i18n("Send test newsletter (to groups)");
$lngAct["news_jobs"]["news_job_delete"]							= i18n("Delete dispatch job");
$lngAct["news_jobs"]["news_job_detail_delete"]					= i18n("Remove recipient from dispatch job");
$lngAct["news_jobs"]["news_job_run"]							= i18n("Run job");
$lngAct["news_jobs"]["news_job_details"]						= i18n("View dispatch job details");

$lngAct["recipients"]["recipients_save"] 						= i18n("Edit recipient");
$lngAct["recipients"]["recipients_create"] 						= i18n("Create recipient");
$lngAct["recipients"]["recipients_delete"] 						= i18n("Delete recipient");
$lngAct["recipients"]["recipients_purge"] 						= i18n("Purge recipients");
$lngAct["recipients_import"]["recipients_import"] 				= i18n("Import recipients");
$lngAct["recipients_import"]["recipients_import_exec"]			= i18n("Execute recipients import");
$lngAct["recipientgroups"]["recipientgroup_delete"] 			= i18n("Delete recipient group");
$lngAct["recipientgroups"]["recipientgroup_create"] 			= i18n("Create recipient group");
$lngAct["recipientgroups"]["recipientgroup_recipient_delete"] 	= i18n("Delete recipient from group");
$lngAct["recipientgroups"]["recipientgroup_save_group"] 		= i18n("Save recipient group");

$lngAct["mycontenido_settings"]["mycontenido_editself"] 		= i18n("Edit own MyContenido settings");
$lngAct["mycontenido_tasks"]["mycontenido_tasks_delete"] 		= i18n("Delete reminder item");
$lngAct["mycontenido_tasks"]["todo_save_item"]					= i18n("Save todo item");

$lngAct["client_edit"]["client_new"] 							= i18n("Create client");
$lngAct["client_edit"]["client_edit"]							= i18n("Edit client");
$lngAct["client"]["client_delete"]								= i18n("Remove client");
$lngAct["client_settings"]["clientsettings_delete_item"]		= i18n("Delete clientsetting");
$lngAct["client_settings"]["clientsettings_edit_item"]			= i18n("Edit clientsetting");
$lngAct["client_settings"]["clientsettings_save_item"]			= i18n("Save clientsetting");
$lngAct["client_articlespec"]["client_artspec_save"]			= i18n("Create/Edit articlespecifications");
$lngAct["client_articlespec"]["client_artspec_delete"]			= i18n("Delete articlespecifications");
$lngAct["client_articlespec"]["client_artspec_default"]			= i18n("Define default articlespecification");
$lngAct["client_articlespec"]["client_artspec_edit"] 			= i18n("Edit articlespecifications");
$lngAct["client_articlespec"]["client_artspec_online"]			= i18n("Make articlespecifications online");

$lngAct["frontend"]["frontend_save_user"]						= i18n("Save frontenduser");
$lngAct["frontend"]["frontend_create"]							= i18n("Create frontenduser");
$lngAct["frontend"]["frontend_delete"]							= i18n("Delete frontenduser");
$lngAct["frontendgroups"]["frontendgroup_delete"]				= i18n("Delete frontendgroup");
$lngAct["frontendgroups"]["frontendgroup_save_group"]			= i18n("Save frontendgroup");
$lngAct["frontendgroups"]["frontendgroup_create"]				= i18n("Create frontendgroup");
$lngAct["frontendgroups"]["frontendgroup_create"]				= i18n("Create frontendgroup");
$lngAct["frontendgroups"]["frontendgroup_user_add"]				= i18n("Add frontendusers");
$lngAct["frontendgroups"]["frontendgroups_user_delete"]			= i18n("Delete frontenduser");
$lngAct["frontendgroups_rights"]["fegroups_save_perm"]			= i18n("Save frontendgroup permissions");

$lngAct["system_settings"]["systemsettings_delete_item"]		= i18n("Delete system property");
$lngAct["system_settings"]["systemsettings_edit_item"]			= i18n("Edit system property");
$lngAct["system_settings"]["systemsettings_save_item"]			= i18n("Save system property");

$lngAct["system"]["emptyLog"]									= i18n("Empty log");
$lngAct["system_configuration"]["edit_sysconf"]					= i18n("Edit Systemconfigration");

$lngAct["logs"]["log_show"] 						= i18n("Show log");

$lngAct["login"]["login"] 							= i18n("Login");
$lngAct["login"]["request_pw"]						= i18n("Request password?");

$lngAct["note"]["note_delete"]						= i18n("Delete note");
$lngAct["note"]["note_save_item"]					= i18n("Save note");

$lngAct[""]["sendMail"]								= i18n("Send mail");
$lngAct[""]["fake_permission_action"]				= i18n("Fake permissions");

$lngStr["actions"]["10"]    						= "Neuer Baum";
$lngStr["structure"]       							= "Kategorie";
$lngStr["properties"]       						= "Aktionen";
$lngStr["makeinvisible"]                 			= "Diese Kategorie offline setzen.";
$lngStr["makevisible"]                        		= "Diese Kategorie online setzen.";
$lngStr["protect"]                         			= "Diese Kategorie sch&uuml;tzen.";
$lngStr["makepublic"]                         		= "Diese Kategorie frei zug&auml;nglich machen.";
$lngStr["moveup"]                         			= "Diese Kategorie eins nachoben verschieben.";
$lngStr["tofirstlevel"]                         	= "Diese Kategorie in die oberste Ebene umh&auml;ngen.";
$lngStr["movehere"]                         		= "Die zuvor ausgew&auml;hlte Kategorie hierher umhaengen.";
$lngStr["movesubtree"]                         		= "Diese Kategorie (und den darunterliegenden Teilbaum) umhaengen.";

$lngLay["lay"]["lay_new"]                 			= "Neues Layout";
$lngLay["layoutname"]                         		= "Name des Layouts";
$lngLay["description"]                         		= "Beschreibung";
$lngLay["code"]                                 	= "Code";
$lngLay["notemplates"]                         		= "Es gibt keine Layouts.";

$lngMod["actions"]["10"]                 			= "Neues Modul";
$lngMod["modulename"]                         		= "Name des Moduls";
$lngMod["description"]                         		= "Beschreibung";
$lngMod["input"]                                 	= "Input";
$lngMod["output"]                        			= "Output";
$lngMod["nomodules"]                         		= "Es gibt keine Module.";
$lngMod["cmsvariables"]                        		= "CMS_Variablen";

$lngTpl["actions"]["10"]                            = "Neues Template";
$lngTpl["templatename"]                         	= "Name des Templates";
$lngTpl["description"]                         		= "Beschreibung";
$lngTpl["container"]                        		= "Container";
$lngTpl["notemplates"]                         		= "Es gibt keine Templates.";
$lngTpl["layout"]                        			= "Layout";


$lngUpl["description"]                            	= 'Beschreibung';
$lngUpl['action']                                	= 'Aktionen';
$lngUpl['delfolder']                         		= 'Verzeichnis löschen';
$lngUpl['delfile']                         			= 'Datei löschen';
$lngUpl['directoriesandfiles']                 		= 'Verzeichnisse / Dateien';
$lngUpl['opendirectory']                        	= 'Verzeichnis öffnen';
$lngUpl['closedirectory']                   		= 'Verzeichnis schließen';
$lngUpl['file']                                 	= 'Datei';
$lngUpl['fileopen']                         		= 'Datei öffnen';
$lngUpl['popupclose']                        		= 'Fenster schliessen';
$lngUpl['renamefolder']                         	= 'Verzeichnis umbenennen';
$lngUpl['renamefile']                         		= 'Datei umbenennen';
$lngUpl['description']                       		= 'Beschreibung';
$lngUpl['dirisempty']                        		= 'Verzeichnis ist leer';
$lngUpl['upload']                                	= 'Dateien hochladen';
$lngUpl['delete']                                	= 'löschen';
$lngUpl["filesize"]                          		= 'Dateigröße';

$lngUser["username"]                        		= "Benutzername";
$lngUser["password"]                         		= "Paßwort";
$lngUser["level"]                         			= "Berechtigungen";
$lngUser["action"]                         			= "Aktionen";
$lngUser["create"]                         			= "Erstellen";
$lngUser["kill"]                                 	= "Löschen";
$lngUser["edit"]                         			= "Bearbeiten";
$lngUser["error"]                               	= "Fehler";
$lngUser["nopermissiontocreateusers"]           	= "Sie haben keine Berechtigung Benutzer zu erstellen.";
$lngUser["pleasefilloutusernameandpassword"] 		= "Bitte füllen Sie <b>Benutzername</b> und <b>Paßwort</b> aus";
$lngUser["usernamealreadyexists"]        			= "Benutzername existiert bereits";
$lngUser["failed"]                        			= "<b>Fehlgeschlagen:</b>";
$lngUser["usercreated"]                        		= "Benutzer erstellt";
$lngUser["nopermissiontoeditusers"]        			= "Sie haben keine Berechtigung Benutzer zu bearbeiten";
$lngUser["passwordchanged1"]                		= "Paßwort von";
$lngUser["passwordchanged2"]                		= "geändert";
$lngUser["nopermissiontodeleteusers"]        		= "Sie haben keine Berechtigung Benutzer zu löschen";
$lngUser["userdeleted"]                        		= "Benutzer gelöscht";

$lngLang["language"]                            	= "Sprache";
$lngLang["active"]                              	= "aktiv";
$lngLang["actions"]                             	= "Aktionen";
$lngLang["rename"]                              	= "umbenennen";
$lngLang["delete"]                              	= "löschen";
$lngLang["notactive"]                           	= "deaktiviert";
$lngLang["newlanguage"]                        		= "Neue Sprache";

$lngStat["structureandarts"]                 		= "Kategorie / Artikeln";
$lngStat["numberofarts"]                 			= "Anzahl der Artikeln";
$lngStat["total"]                         			= "Total";
$lngStat["inthislanguage"]                 			= "In dieser Sprache";
$lngStat["sum"]                                		= "Summe";

$lngForm["nothing"]                        			= "--- ".i18n("None")." ---";
$lngForm["all"]                                		= "--- Alles ---";

$lngAll["yes"]                                 		= "ja";
$lngAll["no"]                                 		= "nein";
$lngAll["default"]                         			= "default";
$lngAll["defaultdoesnotexist"]                 		= "Kein Default-Template eingestellt";
$lngAll["logout"]                       			= "Logout";
$lngAll["back"]                                		= "Zur&uumlck";

$mod["font"]                                		= "Schriftart";
$mod["errorfont"]                          			= "Schriftart für Fehlermeldungen";
$mod["inputformfont"]                          		= "Schriftart für die Eingabefelder";
$mod["select"]                                  	= "Auswahlm&ouml;glichkeiten";
$mod["number"]                                		= "Nummer";
$mod["picforsend"]                        			= "Bild für den Sendebutton";

$modLink["click"]                         			= "Bitte klicken Sie hier.";

$modNews["inputname"]                        		= "Feld für Namen";
$modNews["email"]                        			= "E-Mail Adresse";
$modNews["name"]                                	= "Name (freiwillig)";
$modNews["subcribe"]                          		= "anmelden";
$modNews["unsubcribe"]                        		= "abmelden";
$modNews["both"]                                	= "beides";
$modNews["headline"]                        		= "Stets die neusten Informationen per E-Mail.";
$modNews["subcribemessage"]                			= "Wir haben Ihre Daten in unsere Datenbank aufgenommen.";
$modNews["unsubcribemessage"]                		= "Wir haben Sie aus unserem Newsletterverteiler gel&ouml;scht.";
$modNews["stopmessage"]                        		= "Der Newsletterempfang wurde deaktiviert.";
$modNews["goonmessage"]                        		= "Der Newsletterempfang wurde aktiviert.";

$modLogin["error"]                        			= "Logindaten sind nicht korrekt.";
$modLogin["send"]                        			= "Login now";
$modLogin["sendout"]                        		= "logout";
$modLogin["name"]                        			= "Bitte Login-Namen eintragen";
$modLogin["password"]                        		= "Bitte Passwort eintragen";
$modLogin["login"]                        			= "Bitte klicken um einzuloggen";
$modLogin["logout"]                        			= "Bitte klicken um auszuloggen";
$modLogin["picforlogout"]                			= "Bild für Logout";
?>
