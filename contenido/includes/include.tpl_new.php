<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Link f�r "neues Template"
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.1
 * @author     Olaf Niemann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created  2003-03-27
 *   modified 2008-06-27, Dominik Ziegler, add security fix
 *   modified 2010-05-20, Murat Purc, removed request check during processing ticket [#CON-307]
 *
 *   $Id: include.tpl_new.php 1157 2010-05-20 14:10:43Z xmurrix $:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


$tpl->reset();
if ((int) $client > 0) {
    $tpl->set('s', 'ACTION', '<div style="height:2em"><a class="addfunction" target="right_bottom" href="'.$sess->url("main.php?area=tpl_edit&frame=4&action=tpl_new").'">'.i18n("New template").'</a></div>');
} else {
    $tpl->set('s', 'ACTION', i18n('No client selected'));
}
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['left_top']);
?>
