<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido Color Configurations
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.0
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2004-02-24
 *   modified 2008-06-25, Frederic Schneider, add security fix
 *
 *   $Id: config.colors.php 309 2008-06-26 10:06:56Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

global $cfg;

/* IMPORTANT! Put your modifications into the file "config.local.php"
   to prevent that your changes are overwritten during a system update. */
   
$cfg['color']['table_header']           = '#E2E2E2';
$cfg['color']['table_subheader']		= '#FFFFFF';
$cfg['color']['table_light']            = '#FFFFFF';
$cfg['color']['table_dark']             = '#FFFFFF';
$cfg['color']['table_border']           = '#B3B3B3';
$cfg['color']['table_light_active']     = '#ecf1b2';
$cfg['color']['table_dark_active']      = '#ecf1b2';
$cfg['color']['table_dark_sync']        = '#ddecf9';
$cfg['color']['table_light_sync']       = '#ddecf9';
//$cfg['color']['table_light_active']     = '#FCEEEE';
//$cfg['color']['table_dark_active']      = '#F9DDDD';
$cfg['color']['table_light_offline']    = '#E9E5E5';
$cfg['color']['table_active']			= '#ECF1B2';
$cfg['color']['table_dark_offline']     = '#E2D9D9';
$cfg['color']['notify_error']           = '#d73211';
$cfg['color']['notify_warning']         = '#fea513';
$cfg['color']['notify_info']            = '#bfcf00';
$cfg['color']['notify']                 = '#006600';

?>