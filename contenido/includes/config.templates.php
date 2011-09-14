<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * CONTENIDO Template Configurations
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend includes
 * @version    1.4.0
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * 
 * {@internal 
 *   created 2004-02-24
 *   modified 2008-06-25, Frederic Schneider, add security fix
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

global $cfg;

/* IMPORTANT! Put your modifications into the file "config.local.php"
   to prevent that your changes are overwritten during a system update. */
   
$cfg['templates']['widgets']['left_top'] = 'widgets/template.widgets.left_top.html';

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
$cfg['templates']['generic_subnav']       = 'template.generic_subnav.html';
$cfg['templates']['generic_form']         = 'template.generic_form.html';
$cfg['templates']['generic_table_form']   = 'template.generic_table_form.html';
$cfg['templates']['generic_page']		      = 'template.generic_page.html';
$cfg['templates']['generic_list_row']	    = 'template.generic_list_row.html';
$cfg['templates']['generic_list']		      = 'template.generic_list.html';
$cfg['templates']['left_top']             = 'template.left_top.html';
$cfg['templates']['right_top_blank']      = 'template.right_top_blank.html';

$cfg['templates']['admin_frontend']      = 'template.admin_frontend.html';

/* TODO: HerrB: Not needed anymore (including the files!)*/
$cfg['templates']['newsletter_left_top']  = 'template.newsletter_left_top.html';
$cfg['templates']['newsletter_menu']      = 'template.newsletter_menu.html';
$cfg['templates']['newsletter_edit']      = 'template.newsletter_edit.html';
$cfg['templates']['recipient_left_top']   = 'template.recipient_left_top.html';
$cfg['templates']['recipient_menu']       = 'template.recipient_menu.html';
$cfg['templates']['recipient_edit']       = 'template.recipient_edit.html';

$cfg['templates']['con_edit_form']        = 'template.con_edit_form.html';
$cfg['templates']['con_str_overview']     = 'template.con_str_overview.html';
$cfg['templates']['con_art_overview']     = 'template.con_art_overview.html';
$cfg['templates']['con_left_top']         = 'template.con_left_top.html';
$cfg['templates']['con_subnav']           = 'template.con_subnav.html';
$cfg['templates']['con_subnav_noleft']    = 'template.con_subnav_noleft.html';

$cfg['templates']['str_overview']         = 'template.str_overview.html';

$cfg['templates']['upl_left_top']         = 'template.upl_left_top.html';
$cfg['templates']['upl_dirs_overview']    = 'template.upl_dirs_overview.html';
$cfg['templates']['upl_files_overview']   = 'template.upl_files_overview.html';


$cfg['templates']['lay_overview']         = 'template.lay_overview.html';
$cfg['templates']['lay_edit_form']        = 'template.lay_edit_form.html';
$cfg['templates']['lay_left_top']        = 'template.lay_left_top.html';

$cfg['templates']['mod_overview']         = 'template.mod_overview.html';
$cfg['templates']['mod_edit_form']        = 'template.mod_edit_form.html';
$cfg['templates']['mod_left_top']         = 'template.mod_left_top.html';

$cfg['templates']['tpl_overview']         = 'template.tpl_overview.html';
$cfg['templates']['tpl_edit_form']        = 'template.tpl_edit_form.html';

$cfg['templates']['files_overview'] = 'template.files_overview.html';

$cfg['templates']['style_left_top']       = 'template.style_left_top.html';
$cfg['templates']['js_left_top']          = 'template.js_left_top.html';
$cfg['templates']['html_tpl_left_top']          = 'template.html_tpl_left_top.html';

$cfg['templates']['stat_left_top']        = 'template.stat_left_top.html';
$cfg['templates']['stat_overview']        = 'template.stat_overview.html';
$cfg['templates']['stat_top']             = 'template.stat_top.html';
$cfg['templates']['stat_menu']            = 'template.stat_menu.html';

$cfg['templates']['rights_left_top']      = 'template.rights_left_top.html';
$cfg['templates']['rights_menu']          = 'template.rights_menu.html';
$cfg['templates']['rights_overview']      = 'template.rights_overview.html';
$cfg['templates']['rights_details']       = 'template.rights_details.html';
$cfg['templates']['rights_create']        = 'template.rights_create.html';
$cfg['templates']['rights_inc']           = 'template.rights_inc.html';

$cfg['templates']['log_menu']            = 'template.log_menu.html';
$cfg['templates']['log_main']            = 'template.log_main.html';

$cfg['templates']['left_top_blank']       = 'template.left_top_blank.html';
$cfg['templates']['subnav_blank']         = 'template.subnav_blank.html';

$cfg['templates']['tplcfg_edit_form']     = 'template.tplcfg_edit_form.html';

$cfg['templates']['lang_overview']        = 'template.lang_overview.html';
$cfg['templates']['lang_menu']            = 'template.lang_menu.html';
$cfg['templates']['lang_edit']            = 'template.lang_edit.html';
$cfg['templates']['lang_left_top']        = 'template.lang_left_top.html';

$cfg['templates']['client_menu']          = 'template.client_menu.html';
$cfg['templates']['client_edit']          = 'template.client_edit.html';
$cfg['templates']['client_left_top']      = 'template.client_left_top.html';
$cfg['templates']['client_subnav']        = 'template.client_subnav.html';

$cfg['templates']['mycontenido_settings'] = 'template.mycontenido_settings.html';
$cfg['templates']['mycontenido_overview'] = 'template.mycontenido_overview.html';
$cfg['templates']['mycontenido_start']    = 'template.mycontenido_start.html';
$cfg['templates']['mycontenido_lastarticles']  = 'template.mycontenido_lastarticles.html';
$cfg['templates']['mycontenido_subnav']  = 'template.mycontenido_subnav.html';

$cfg['templates']['grouprights_left_top'] = 'template.grouprights_left_top.html';
$cfg['templates']['grouprights_create']   = 'template.grouprights_create.html';
$cfg['templates']['grouprights_subnav']   = 'template.grouprights_subnav.html';
$cfg['templates']['grouprights_memberlist']   = 'template.grouprights_memberlist.html';
$cfg['templates']['grouprights_memberselect']   = 'template.grouprights_memberselect.html';
$cfg['templates']['grouprights_details']   = 'template.grouprights_details.html';
$cfg['templates']['grouprights_menu']   = 'template.grouprights_menu.html';
$cfg['templates']['grouprights_overview']   = 'template.grouprights_overview.html';

$cfg['templates']['welcome']              = 'template.welcome.html';
$cfg['templates']['welcome_update']       = 'template.welcome_update.html';
$cfg['templates']['info']                 = 'template.info.html';
$cfg['templates']['symbolhelp']           = 'template.symbolhelp.html';

$cfg['templates']['systam_variables']	  = 'template.system_variables.html';
$cfg['templates']['system_subnav']		  = 'template.system_subnav.html';
$cfg['templates']['system_errorreport']   = 'template.system_errorreport.html';
$cfg['templates']['systam_variables_mailattach']	= 'template.system_sysval_mailattach.html';

$cfg['templates']['request_password']      = 'template.request_password.html';
$cfg['templates']['file_subnav']         = 'template.file_subnav.html';
$cfg['templates']['blank']                = 'template.blank.html';
$cfg['templates']['system_purge']	= 'template.system_purge.html';

$cfg['templates']['inuse_tpl']	= 'template.inuse_tpl.html';
$cfg['templates']['inuse_lay_mod']	= 'template.inuse_lay_mod.html';

$cfg['templates']['default_subnav']	= 'template.default_subnav.html';


?>