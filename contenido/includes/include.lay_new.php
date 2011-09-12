<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Link for "new layout"
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend includes
 * @version    1.0.1
 * @author     Olaf Niemann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 * 
 * {@internal 
 *   created 2003-03-27
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

$tpl->reset();

if ((int) $client > 0) {
    $tpl->set('s', 'ACTION', '<div style="height:2em;"><a class="addfunction" target="right_bottom" href="'.$sess->url("main.php?area=lay_edit&frame=4&action=lay_new").'">'.i18n("New Layout").'</a></div>');
} else {
    $tpl->set('s', 'ACTION', i18n('No Client selected'));
}

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['left_top']);
?>