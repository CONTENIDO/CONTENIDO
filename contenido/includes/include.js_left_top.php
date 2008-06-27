<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * left_top frame
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
 *   created 2003-05-09
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

$tpl->set('s', 'ID', 'oTplSel');
$tpl->set('s', 'CLASS', 'text_medium');
$tpl->set('s', 'OPTIONS', '');
$tpl->set('s', 'CAPTION', '');
$tpl->set('s', 'SESSID', $sess->id);


$tpl->set('s', 'ACTION', $select);

$tmp_mstr = '<a class="addfunction" href="javascript:conMultiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';
$area = "style";
$mstr = sprintf($tmp_mstr, 'right_top',
                                   $sess->url("main.php?area=js&frame=3"),
                                   'right_bottom',
                                   $sess->url("main.php?area=js&frame=4&action=js_create"),
                                   i18n("Create script"));
if ((int) $client > 0) {
    $tpl->set('s', 'NEWSCRIPT', $mstr);
} else {
    $tpl->set('s', 'NEWSCRIPT', i18n('No Client selected'));
}
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['js_left_top']);
?>