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

/**
 * @deprecated This variables shouldn't be used anymore. Please use CSS to change colors
 */

$cfg['color']['table_header']           = '#00FF01';
$cfg['color']['table_subheader']        = '#00FF02';
$cfg['color']['table_light']            = '#00FF03';
$cfg['color']['table_dark']             = '#00FF04';
$cfg['color']['table_border']           = '#00FF05';
$cfg['color']['table_light_active']     = '#00FF06';
$cfg['color']['table_dark_active']      = '#00FF07';
$cfg['color']['table_dark_sync']        = '#00FF08';
$cfg['color']['table_light_sync']       = '#00FF09';
$cfg['color']['table_light_offline']    = '#00FF0A';
$cfg['color']['table_active']           = '#00FF0B';
$cfg['color']['table_dark_offline']     = '#00FF0C';
$cfg['color']['notify_error']           = '#00FF0D'; // @deprecated 2012-02-10
$cfg['color']['notify_warning']         = '#00FF0E'; // @deprecated 2012-02-10
$cfg['color']['notify_info']            = '#00FF0F'; // @deprecated 2012-02-10
$cfg['color']['notify']                 = '#00FF10'; // @deprecated 2012-02-10

?>