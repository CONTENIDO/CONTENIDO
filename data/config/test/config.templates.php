<?php

/**
 * This file contains the configuration variables for template names.
 *
 * @package    Core
 * @subpackage Backend_ConfigFile
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $cfg;

/* IMPORTANT! Put your modifications into the file "config.local.php"
   to prevent that your changes are overwritten during a system update. */

$cfg['templates']['widgets']['left_top']  = 'widgets/template.widgets.left_top.html';

$cfg['templates']['main_loginform']       = 'main.loginform.html';
$cfg['templates']['frameset']             = 'frameset.html';
$cfg['templates']['frameset_content']     = 'frameset_content.html';
$cfg['templates']['frameset_menuless_content'] = 'frameset_menuless_content.html';
$cfg['templates']['frameset_left']        = 'frameset_content_left.html';
$cfg['templates']['frameset_right']       = 'frameset_content_right.html';
$cfg['templates']['header']               = 'header.html';
$cfg['templates']['submenu']              = 'submenu.html'; # 2nd layer
$cfg['templates']['subnav']               = 'template.subnav.html'; # 3rd layer
$cfg['templates']['generic_list']         = 'generic_list.html';
$cfg['templates']['generic_select']       = 'template.select.html';
$cfg['templates']['generic_left_top']     = 'template.generic_left_top.html';
$cfg['templates']['generic_menu']         = 'template.generic_menu.html';
$cfg['templates']['generic_form']         = 'template.generic_form.html';
$cfg['templates']['generic_table_form']   = 'template.generic_table_form.html';
$cfg['templates']['generic_page']         = 'template.generic_page.html';
$cfg['templates']['generic_page_html5']   = 'template.generic_page_html5.html';
$cfg['templates']['generic_list_row']     = 'template.generic_list_row.html';
$cfg['templates']['generic_list_head']    = 'template.generic_list_head.html';
$cfg['templates']['generic_list']         = 'template.generic_list.html';
$cfg['templates']['input_helper_row']     = 'template.input_helper_row.html';
$cfg['templates']['input_helper']         = 'template.input_helper.html';
$cfg['templates']['left_top']             = 'template.left_top.html';
$cfg['templates']['left_top_blank']       = 'template.left_top_blank.html';
$cfg['templates']['right_top_blank']      = 'template.right_top_blank.html';

$cfg['templates']['frontend_left_top']        = 'template.frontend_left_top.html';
$cfg['templates']['frontend_left_top_filter'] = 'template.frontend_left_top_filter.html';

$cfg['templates']['con_edit_form']        = 'template.con_edit_form.html';
$cfg['templates']['con_edit_form_cat']    = 'template.con_edit_form_categories.html';
$cfg['templates']['con_meta_edit_form']   = 'template.con_meta_edit_form.html';
$cfg['templates']['con_meta_addnew']      = 'template.con_meta_addnew.html';
$cfg['templates']['con_editcontent']      = 'template.con_editcontent.html';
$cfg['templates']['con_str_overview']     = 'template.con_str_overview.html';
$cfg['templates']['con_art_overview']     = 'template.con_art_overview.html';
$cfg['templates']['con_left_top']         = 'template.con_left_top.html';
$cfg['templates']['con_subnav']           = 'template.con_subnav.html';
$cfg['templates']['con_left_top_art_search'] = 'template.con_left_top_art_search.html';
$cfg['templates']['con_left_top_cat_edit'] = 'template.con_left_top_cat_edit.html';
$cfg['templates']['con_left_top_sync']    = 'template.con_left_top_sync.html';

$cfg['templates']['str_overview']         = 'template.str_overview.html';

$cfg['templates']['upl_left_top']         = 'template.upl_left_top.html';
$cfg['templates']['upl_dirs_overview']    = 'template.upl_dirs_overview.html';

$cfg['templates']['lay_overview']         = 'template.lay_overview.html';
$cfg['templates']['lay_edit_form']        = 'template.lay_edit_form.html';
$cfg['templates']['lay_left_top']         = 'template.lay_left_top.html';

$cfg['templates']['mod_left_top']         = 'template.mod_left_top.html';
$cfg['templates']['mod_left_top_filter']  = 'template.mod_left_top_filter.html';

$cfg['templates']['tpl_overview']         = 'template.tpl_overview.html';

$cfg['templates']['files_overview']       = 'template.files_overview.html';

$cfg['templates']['style_left_top']       = 'template.style_left_top.html';
$cfg['templates']['js_left_top']          = 'template.js_left_top.html';
$cfg['templates']['html_tpl_left_top']    = 'template.html_tpl_left_top.html';

$cfg['templates']['stat_left_top']        = 'template.stat_left_top.html';
$cfg['templates']['stat_overview']        = 'template.stat_overview.html';
$cfg['templates']['stat_top']             = 'template.stat_top.html';
$cfg['templates']['stat_menu']            = 'template.stat_menu.html';

$cfg['templates']['rights_left_top']      = 'template.rights_left_top.html';
$cfg['templates']['rights_left_top_filter'] = 'template.rights_left_top_filter.html';
$cfg['templates']['rights_menu']          = 'template.rights_menu.html';
$cfg['templates']['rights_overview']      = 'template.rights_overview.html';
$cfg['templates']['rights_create']        = 'template.rights_create.html';
$cfg['templates']['rights']               = 'template.rights.html';
$cfg['templates']['include.rights']       = 'template.rights.html';  // @deprecated: Use $cfg['templates']['rights'], name doesn't follows naming conventions!!!

$cfg['templates']['log_main']             = 'template.log_main.html';

$cfg['templates']['subnav_blank']         = 'template.subnav_blank.html';

$cfg['templates']['tplcfg_edit_form']     = 'template.tplcfg_edit_form.html';

$cfg['templates']['lang_overview']        = 'template.lang_overview.html';
$cfg['templates']['lang_left_top']        = 'template.lang_left_top.html';

$cfg['templates']['client_menu']          = 'template.client_menu.html';
$cfg['templates']['client_edit']          = 'template.client_edit.html';
$cfg['templates']['client_left_top']      = 'template.client_left_top.html';

$cfg['templates']['mycontenido_settings'] = 'template.mycontenido_settings.html';
$cfg['templates']['mycontenido_lastarticles'] = 'template.mycontenido_lastarticles.html';

$cfg['templates']['grouprights_left_top'] = 'template.grouprights_left_top.html';
$cfg['templates']['grouprights_create']   = 'template.grouprights_create.html';
$cfg['templates']['grouprights_memberselect'] = 'template.grouprights_memberselect.html';
$cfg['templates']['grouprights_menu']     = 'template.grouprights_menu.html';
$cfg['templates']['grouprights_overview'] = 'template.grouprights_overview.html';

$cfg['templates']['welcome']              = 'template.welcome.html';
$cfg['templates']['welcome_update']       = 'template.welcome_update.html';
$cfg['templates']['info']                 = 'template.info.html';
$cfg['templates']['symbolhelp']           = 'template.symbolhelp.html';

$cfg['templates']['system_variables']     = 'template.system_variables.html';
$cfg['templates']['system_variables_block'] = 'template.system_variables_block.html';
$cfg['templates']['system_log_variables'] = 'template.system_log_variables.html';

$cfg['templates']['request_password']     = 'template.request_password.html';
$cfg['templates']['file_subnav']          = 'template.file_subnav.html';
$cfg['templates']['blank']                = 'template.blank.html';
$cfg['templates']['system_purge']         = 'template.system_purge.html';

$cfg['templates']['inuse_tpl']            = 'template.inuse_tpl.html';
$cfg['templates']['inuse_lay_mod']        = 'template.inuse_lay_mod.html';

$cfg['templates']['debug_visibleadv']     = 'template.debug.visibleadv.html';
$cfg['templates']['debug_header']         = 'template.debug.header.html';
$cfg['templates']['debug_visible']        = 'template.debug.visible.html';

$cfg['templates']['front_loginform']      = 'template.front.loginform.html';

$cfg['templates']['breadcrumb']           = 'template.breadcrumb.html';

$cfg['templates']['con_edit_form_synclang'] = 'template.con_edit_form_synclang.html';
$cfg['templates']['con_edit_form_sync'] = 'template.con_edit_form_sync.html';

?>