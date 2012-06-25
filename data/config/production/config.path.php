<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO Path Configurations
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.9.2
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created  2004-02-24
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

global $cfg;

/* IMPORTANT! Put your modifications into the file 'config.local.php'
   to prevent that your changes are overwritten during a system update. */

$cfg['path']['contenido_html']      = '../contenido/';

$cfg['path']['includes']            = 'includes/';

$cfg['path']['xml']                 = 'xml/';
$cfg['path']['images']              = 'images/';
$cfg['path']['classes']             = 'classes/';

$cfg['path']['cronjobs']            = 'cronjobs/';
$cfg['path']['scripts']             = 'scripts/';
$cfg['path']['styles']              = 'styles/';
$cfg['path']['plugins']             = 'plugins/';

$cfg['path']['locale']              = 'locale/';
$cfg['path']['temp']                = 'data/temp/';
$cfg['path']['external']            = 'external/';

$cfg['path']['frontendtemplate']    = 'external/frontend/';
$cfg['path']['templates']           = 'templates/standard/';

$cfg['path']['repository']          = $cfg['path']['plugins'] . 'repository/';

$cfg['path']['interfaces']          = $cfg['path']['classes'] . 'interfaces/';
$cfg['path']['exceptions']          = $cfg['path']['classes'] . 'exceptions/';

$cfg['path']['modules']             = 'modules/';
$cfg['path']['layouts']             = 'layouts/';

$cfg['path']['logs']                = 'data/logs/';
$cfg['path']['contenido_logs']      = $cfg['path']['frontend'] . '/' . $cfg['path']['logs'];

$cfg['path']['cronlog']             = 'data/cronlog/';
$cfg['path']['contenido_cronlog']   = $cfg['path']['frontend'] . '/' . $cfg['path']['cronlog'];

$cfg['path']['maillog']             = 'data/maillog';
$cfg['path']['contenido_maillog']   = $cfg['path']['frontend'] .'/' . $cfg['path']['maillog'];

$cfg['path']['cache']               = 'data/cache/';
$cfg['path']['contenido_cache']     = $cfg['path']['frontend'] . '/' . $cfg['path']['cache'];

$cfg['path']['locale']              = 'data/locale/';
$cfg['path']['contenido_locale']    = $cfg['path']['frontend'] . '/' . $cfg['path']['locale'];

$cfg['path']['contenido_temp']      = $cfg['path']['frontend'] . '/' . $cfg['path']['temp'];

?>