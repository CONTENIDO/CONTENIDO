<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Contenido Path Configurations
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend includes
 * @version    1.9.1
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 *
 * {@internal
 *   created  2004-02-24
 *   modified 2008-06-25, Frederic Schneider, add security fix
 *   modified 2008-11-16, H. Librenz - added interfaces and exception path
 *   modified 2010-05-20, Murat Purc, removed request check during processing ticket [#CON-307]
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

global $cfg;

/* IMPORTANT! Put your modifications into the file "config.local.php"
   to prevent that your changes are overwritten during a system update. */

$cfg['path']['contenido_html']          = '../contenido/';

$cfg['path']['statfile']                = 'statfile/';
$cfg['path']['includes']                = 'includes/';

$cfg['path']['xml']                     = 'xml/';
$cfg['path']['images']                  = 'images/';
$cfg['path']['classes']                 = 'classes/';

$cfg["path"]["cronjobs"]				= 'cronjobs/';
$cfg['path']['scripts']                 = 'scripts/';
$cfg['path']['styles']                  = 'styles/';
$cfg["path"]['plugins']				    = 'plugins/';

$cfg['path']['locale']                  = 'locale/';
$cfg['path']['temp']                  	= 'temp/';
$cfg['path']['external']                = 'external/';

$cfg['path']['frontendtemplate']        = 'external/frontend/';
$cfg['path']['templates']               = 'templates/standard/';
$cfg['path']['xml']                     = 'xml/';

$cfg['path']['repository']				= $cfg["path"]['plugins'] . 'repository/';
$cfg['path']['modules']					= 'modules/';

$cfg['path']['interfaces']              = $cfg['path']['classes'] . 'interfaces/';
$cfg['path']['exceptions']              = $cfg['path']['classes'] . 'exceptions/';
?>