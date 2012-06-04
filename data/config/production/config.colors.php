<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO Color Configurations
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.1
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created 2004-02-24
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

global $cfg;

/* IMPORTANT! Put your modifications into the file "config.local.php"
   to prevent that your changes are overwritten during a system update. */

$cfg['color']['table_header']           = '#E2E2E2';
$cfg['color']['table_subheader']        = '#FFFFFF';
$cfg['color']['table_light']            = '#FFFFFF';
$cfg['color']['table_dark']             = '#FFFFFF';
$cfg['color']['table_border']           = '#B3B3B3';
$cfg['color']['table_light_active']     = '#ecf1b2';
$cfg['color']['table_dark_active']      = '#ecf1b2';
$cfg['color']['table_dark_sync']        = '#ddecf9';
$cfg['color']['table_light_sync']       = '#ddecf9';
$cfg['color']['table_light_offline']    = '#E9E5E5';
$cfg['color']['table_active']            = '#ECF1B2';
$cfg['color']['table_dark_offline']     = '#E2D9D9';
$cfg['color']['notify_error']           = '#d73211'; // @deprecated 2012-02-10
$cfg['color']['notify_warning']         = '#fea513'; // @deprecated 2012-02-10
$cfg['color']['notify_info']            = '#bfcf00'; // @deprecated 2012-02-10
$cfg['color']['notify']                 = '#006600'; // @deprecated 2012-02-10

?>