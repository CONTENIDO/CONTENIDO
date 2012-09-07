<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Displays all last edited articles of a category
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.1
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-05-08
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id: include.info.php 350 2008-06-27 11:19:47Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

# Generate template
$tpl->reset();

$message = sprintf(i18n("You can find many information and a community forum on the <a href=\"http://forum.contenido.org\" target=\"_blank\">Contenido Portal</a>"));

$tpl->set('s', 'VERSION', $cfg['version']);
$tpl->set('s', 'PORTAL', $message); 
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['info']);

?>