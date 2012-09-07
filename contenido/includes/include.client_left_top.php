<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido Client Left Top Include
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.0
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-04-29
 *   modified 2008-06-26, Dominik Ziegler, add security fix
 *
 *   $Id: include.client_left_top.php 338 2008-06-27 09:02:23Z frederic.schneider $:
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
$area = "client";
$mstr = sprintf($tmp_mstr, 'right_top',
                                   $sess->url("main.php?area=client&frame=3"),
                                   'right_bottom',
                                   $sess->url("main.php?area=client_edit&action=client_new&frame=4"),
                                   i18n("Create client"));
if (strpos($auth->auth["perm"],"sysadmin") !== false)
{
	$tpl->set('s', 'NEWCLIENT', $mstr);
} else {
	$tpl->set('s', 'NEWCLIENT', '&nbsp;');
}

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['client_left_top']);
?>
