<?php
/**
 * This file contains the global configuration variable $lngAct for translated action names.
 *
 * @package          Core
 * @subpackage       Backend_ConfigFile
 * @version          SVN Revision $Rev:$
 *
 * @author           Olaf Niemann
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.i18n.php');

global $lngAct;

$lngAct['con']['con_lock']                          = i18n('Freeze article');
$lngAct['con']['con_makecatonline']                 = i18n('Make category online');
$lngAct['con']['con_changetemplate']                = i18n('Change template');
$lngAct['con']['con_makestart']                     = i18n('Set start article');
$lngAct['con']['con_makeonline']                    = i18n('Make article online');
$lngAct['con']['con_synccat']                       = i18n('Syncronize category');
$lngAct['con']['con_syncarticle']                   = i18n('Syncronize article');
$lngAct['con']['con_makepublic']                    = i18n('Protect category');
$lngAct['con']['con_deleteart']                     = i18n('Delete article');
$lngAct['con']['con_tplcfg_edit']                   = i18n('Edit template configuration');
$lngAct['con']['con_duplicate']                     = i18n('Duplicate article');
$lngAct['con']['con_expand']                        = i18n('Expand boxes');
$lngAct['con_edittpl']['10']                        = i18n('Configure template');
$lngAct['con_editart']['con_newart']                = i18n('Create article');
$lngAct['con_editart']['35']                        = i18n('Configure article');
$lngAct['con_editart']['con_saveart']               = i18n('Save article');
$lngAct['con_editart']['remove_assignments']        = i18n('Remove assignments');
$lngAct['con_editart']['con_edit']                  = i18n('Edit article properties');
$lngAct['con_editcontent']['15']                    = i18n('Edit article');
$lngAct['con_editcontent']['con_editart']           = i18n('Edit article');
$lngAct['con_tplcfg']['con_edddittemplate']         = i18n('Help');
$lngAct['con_content_list']['con_content']          = i18n('List content entries');
$lngAct['con_content_list']['savecontype']          = i18n('Edit content list entry');
$lngAct['con_content_list']['deletecontype']        = i18n('Delete content list entry');
$lngAct['con_translate']['con_translate_view']      = i18n('View translations');
$lngAct['con_translate']['con_translate_edit']      = i18n('Edit translations');

$lngAct['str']['str_renamecat']                     = i18n('Rename category');
$lngAct['str']['str_newcat']                        = i18n('New category');
$lngAct['str']['str_makevisible']                   = i18n('Set category on- or offline');
$lngAct['str']['50']                                = i18n('Disable category');
$lngAct['str']['str_makepublic']                    = i18n('Protect category');
$lngAct['str']['front_allow']                       = i18n('Frontend access');
$lngAct['str']['str_deletecat']                     = i18n('Delete category');
$lngAct['str']['str_moveupcat']                     = i18n('Move category up');
$lngAct['str']['str_movedowncat']                   = i18n('Move category down');
$lngAct['str']['str_movesubtree']                   = i18n('Move category');
$lngAct['str']['str_newtree']                       = i18n('Create new tree');
$lngAct['str']['str_duplicate']                     = i18n('Duplicate category');
$lngAct['str_tplcfg']['str_tplcfg']                 = i18n('Configure category');
$lngAct['str_tplcfg']['tplcfg_edit']                = i18n('Edit category');

$lngAct['upl']['upl_mkdir']                         = i18n('Create directory');
$lngAct['upl']['upl_upload']                        = i18n('Upload files');
$lngAct['upl']['upl_delete']                        = i18n('Delete files');
$lngAct['upl']['upl_rmdir']                         = i18n('Remove directory');
$lngAct['upl']['upl_renamedir']                     = i18n('Rename directory');
$lngAct['upl']['upl_modify_file']                   = i18n('Modify file');
$lngAct['upl']['upl_renamefile']                    = i18n('Rename file');
$lngAct['upl']['upl_multidelete']                   = i18n('Multidelete files');
$lngAct['upl']['21']                                = i18n('Delete file');
$lngAct['upl']['40']                                = i18n('Upload files');
$lngAct['upl']['31']                                = i18n('Create directory');

$lngAct['lay']['lay_delete']                        = i18n('Delete layout');
$lngAct['lay_edit']['lay_edit']                     = i18n('Modify layout');
$lngAct['lay_edit']['lay_new']                      = i18n('Create layout');
$lngAct['lay_edit']['lay_sync']                     = i18n('Synchronize layouts');
$lngAct['lay_history']['lay_history_manage']        = i18n('Manage history');
$lngAct['lay_history']['history_truncate']          = i18n('Truncate history');


$lngAct['mod']['mod_delete']                        = i18n('Delete module');
$lngAct['mod_history']['mod_history_manage']        = i18n('Manage history');
$lngAct['mod_history']['history_truncate']          = i18n('Truncate history');
$lngAct['mod_edit']['mod_edit']                     = i18n('Edit module');
$lngAct['mod_edit']['mod_new']                      = i18n('Create module');
$lngAct['mod_edit']['mod_sync']                     = i18n('Synchronize modules');
$lngAct['mod_edit']['mod_importexport_module']      = i18n('Import/Export module');
$lngAct['mod_translate']['mod_translation_save']    = i18n('Translate modules');
$lngAct['mod_translate']['mod_importexport_translation'] = i18n('Translation import/export');
$lngAct['mod_package']['mod_importexport_package']  = i18n('Import/Export package');

$lngAct['tpl']['tpl_delete']                        = i18n('Delete template');
$lngAct['tpl_edit']['tpl_edit']                     = i18n('Edit template');
$lngAct['tpl_edit']['tpl_new']                      = i18n('Create template');
$lngAct['tpl_edit']['tpl_duplicate']                = i18n('Duplicate template');
$lngAct['tpl']['tpl_duplicate']                     = i18n('Duplicate template');
$lngAct['tpl_visual']['tpl_visedit']                = i18n('Visual edit');

$lngAct['user']['user_create']                      = i18n('Create user');
$lngAct['user']['user_delete']                      = i18n('Delete user');
$lngAct['user_areas']['user_saverightsarea']        = i18n('Save user area rights');
$lngAct['user_create']['user_createuser']           = i18n('Create user');
$lngAct['user_rights']['10']                        = i18n('Edit rights');
$lngAct['user_overview']['user_edit']               = i18n('Edit user');

$lngAct['groups_members']['group_deletemember']     = i18n('Delete group members');
$lngAct['groups_members']['group_addmember']        = i18n('Add group members');
$lngAct['groups_overview']['group_edit']            = i18n('Edit group');
$lngAct['groups_create']['group_create']            = i18n('Create group');
$lngAct['groups']['group_delete']                   = i18n('Delete group');

$lngAct['stat']['stat_show']                        = i18n('Show statistics');

$lngAct['lang']['lang_activatelanguage']            = i18n('Activate language');
$lngAct['lang']['lang_deactivatelanguage']          = i18n('Deactivate language');
$lngAct['lang']['lang_renamelanguage']              = i18n('Rename language');
$lngAct['lang_edit']['lang_newlanguage']            = i18n('Create language');
$lngAct['lang_edit']['lang_deletelanguage']         = i18n('Delete language');
$lngAct['lang_edit']['lang_edit']                   = i18n('Edit language');

$lngAct['linkchecker']['linkchecker']               = i18n('Linkchecker');
$lngAct['linkchecker']['whitelist_view']            = i18n('Linkchecker Whitelist');

$lngAct['plug']['10']                               = i18n('Install/Remove plugins');

$lngAct['style']['style_edit']                      = i18n('Modify CSS');
$lngAct['style']['style_create']                    = i18n('Create CSS');
$lngAct['style']['style_delete']                    = i18n('Delete CSS');
$lngAct['style_history']['style_history_manage']    = i18n('Manage history');
$lngAct['style_history']['history_truncate']        = i18n('Truncate history');

$lngAct['js']['js_edit']                            = i18n('Edit script');
$lngAct['js']['js_delete']                          = i18n('Delete script');
$lngAct['js']['js_create']                          = i18n('Create script');
$lngAct['js_history']['js_history_manage']          = i18n('Manage history');
$lngAct['js_history']['history_truncate']           = i18n('Truncate history');

$lngAct['htmltpl']['htmltpl_edit']                  = i18n('Modify HTML template');
$lngAct['htmltpl']['htmltpl_create']                = i18n('Create HTML template');
$lngAct['htmltpl']['htmltpl_delete']                = i18n('Delete HTML template');
$lngAct['htmltpl_history']['htmltpl_history_manage'] = i18n('Manage history');
$lngAct['htmltpl_history']['history_truncate']      = i18n('Truncate history');

$lngAct['news']['news_save']                        = i18n('Edit newsletter');
$lngAct['news']['news_create']                      = i18n('Create newsletter');
$lngAct['news']['news_delete']                      = i18n('Delete newsletter');
$lngAct['news']['news_duplicate']                   = i18n('Duplicate newsletter');
$lngAct['news']['news_add_job']                     = i18n('Add newsletter dispatch job');
$lngAct['news']['news_html_settings']               = i18n('Change global HTML newsletter settings');
$lngAct['news']['news_send_test']                   = i18n('Send test newsletter (to groups)');
$lngAct['news_jobs']['news_job_delete']             = i18n('Delete dispatch job');
$lngAct['news_jobs']['news_job_detail_delete']      = i18n('Remove recipient from dispatch job');
$lngAct['news_jobs']['news_job_run']                = i18n('Run job');
$lngAct['news_jobs']['news_job_details']            = i18n('View dispatch job details');

$lngAct['recipients']['recipients_save']                       = i18n('Edit recipient');
$lngAct['recipients']['recipients_create']                     = i18n('Create recipient');
$lngAct['recipients']['recipients_delete']                     = i18n('Delete recipient');
$lngAct['recipients']['recipients_purge']                      = i18n('Purge recipients');
$lngAct['recipients_import']['recipients_import']              = i18n('Import recipients');
$lngAct['recipients_import']['recipients_import_exec']         = i18n('Execute recipients import');
$lngAct['recipientgroups']['recipientgroup_delete']            = i18n('Delete recipient group');
$lngAct['recipientgroups']['recipientgroup_create']            = i18n('Create recipient group');
$lngAct['recipientgroups']['recipientgroup_recipient_delete']  = i18n('Delete recipient from group');
$lngAct['recipientgroups']['recipientgroup_save_group']        = i18n('Save recipient group');

$lngAct['mycontenido_settings']['mycontenido_editself']        = i18n('Edit own MyContenido settings');
$lngAct['mycontenido_tasks']['mycontenido_tasks_delete']       = i18n('Delete reminder item');
$lngAct['mycontenido_tasks']['todo_save_item']                 = i18n('Save todo item');

$lngAct['client_edit']['client_new']                           = i18n('Create client');
$lngAct['client_edit']['client_edit']                          = i18n('Edit client');
$lngAct['client']['client_delete']                             = i18n('Remove client');
$lngAct['client_settings']['clientsettings_delete_item']       = i18n('Delete client setting');
$lngAct['client_settings']['clientsettings_edit_item']         = i18n('Edit client setting');
$lngAct['client_settings']['clientsettings_save_item']         = i18n('Save client setting');
$lngAct['client_articlespec']['client_artspec_save']           = i18n('Create/Edit article specifications');
$lngAct['client_articlespec']['client_artspec_delete']         = i18n('Delete article specifications');
$lngAct['client_articlespec']['client_artspec_default']        = i18n('Define default article specification');
$lngAct['client_articlespec']['client_artspec_edit']           = i18n('Edit article specifications');
$lngAct['client_articlespec']['client_artspec_online']         = i18n('Make article specifications online');

$lngAct['frontend']['frontend_save_user']                      = i18n('Save frontend user');
$lngAct['frontend']['frontend_create']                         = i18n('Create frontend user');
$lngAct['frontend']['frontend_delete']                         = i18n('Delete frontend user');
$lngAct['frontendgroups']['frontendgroup_delete']              = i18n('Delete frontend group');
$lngAct['frontendgroups']['frontendgroup_save_group']          = i18n('Save frontend group');
$lngAct['frontendgroups']['frontendgroup_create']              = i18n('Create frontend group');
$lngAct['frontendgroups']['frontendgroup_create']              = i18n('Create frontend group');
$lngAct['frontendgroups']['frontendgroup_user_add']            = i18n('Add frontend users');
$lngAct['frontendgroups']['frontendgroups_user_delete']        = i18n('Delete frontend user');
$lngAct['frontendgroups_rights']['fegroups_save_perm']         = i18n('Save frontend group permissions');

$lngAct['system_settings']['systemsettings_delete_item']       = i18n('Delete system property');
$lngAct['system_settings']['systemsettings_edit_item']         = i18n('Edit system property');
$lngAct['system_settings']['systemsettings_save_item']         = i18n('Save system property');

$lngAct['system']['empty_log']                                 = i18n('Empty log');
$lngAct['system_configuration']['edit_sysconf']                = i18n('Edit system configration');
$lngAct['system_purge']['do_purge']                            = i18n('Do system purge');

$lngAct['logs']['log_show']                           = i18n('Show log');

$lngAct['login']['login']                             = i18n('Login');
$lngAct['login']['request_pw']                        = i18n('Request password?');

$lngAct['note']['note_delete']                        = i18n('Delete note');
$lngAct['note']['note_save_item']                     = i18n('Save note');

$lngAct['']['send_mail']                              = i18n('Send mail');
$lngAct['']['fake_permission_action']                 = i18n('Fake permissions');

$lngAct['']['login']                 = i18n('User login');

?>