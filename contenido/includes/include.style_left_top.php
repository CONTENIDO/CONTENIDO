<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Left bottom
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
 *   $Id: include.style_left_top.php 368 2008-06-27 14:23:40Z frederic.schneider $:
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

$tmp_mstr = '<div style="height:2em;line-height:2em;"><a class="addfunction" href="javascript:conMultiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a></div>';
$area = "style";
$mstr = sprintf($tmp_mstr, 'right_top',
                                   $sess->url("main.php?area=style&frame=3"),
                                   'right_bottom',
                                   $sess->url("main.php?area=style&frame=4&action=style_create"),
                                   i18n("Create style"));
if ((int) $client > 0) {
    $tpl->set('s', 'NEWSTYLE', $mstr);
} else {
    $tpl->set('s', 'NEWSTYLE', i18n("No client selected"));
}
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['style_left_top']);
?>