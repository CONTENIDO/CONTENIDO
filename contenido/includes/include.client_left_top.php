<?php

/******************************************
* File      :   include.stat_left_top.php
* Project   :   Contenido 
*
*
* Author    :   Timo A. Hummel
* Created   :   29.04.2003
* Modified  :   29.04.2003
*
* © four for business AG
******************************************/

$tpl->set('s', 'ID', 'oTplSel');
$tpl->set('s', 'CLASS', 'text_medium');
$tpl->set('s', 'OPTIONS', '');
$tpl->set('s', 'CAPTION', '');
$tpl->set('s', 'SESSID', $sess->id);


$tpl->set('s', 'ACTION', $select);

$tmp_mstr = '<a class="addfunction" href="javascript:conMultiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';
$area = "client";
$mstr = sprintf($tmp_mstr, 'right_top',
                                   $sess->url("main.php?area=client_edit&frame=3"),
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
