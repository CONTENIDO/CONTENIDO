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
 * @author     Willi Mann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-12-10
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id: include.html_tpl_left_top.php 350 2008-06-27 11:19:47Z frederic.schneider $:
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
$area = "htmltpl";
$mstr = sprintf($tmp_mstr, 'right_top',
                                   $sess->url("main.php?area=htmltpl&frame=3"),
                                   'right_bottom',
                                   $sess->url("main.php?area=htmltpl&frame=4&action=htmltpl_create"),
                                   i18n("Create module template"));
if ((int) $client > 0) {
    $tpl->set('s', 'NEWSTYLE', $mstr);
} else {
    $tpl->set('s', 'NEWSTYLE', i18n("No client selected"));
}

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['html_tpl_left_top']);
?>