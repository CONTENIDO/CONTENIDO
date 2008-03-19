<?php

/******************************************
* File      :   include.html_tpl_left_top.php
* Project   :   Contenido 
*
*
* Author    :   Willi Man
* Created   :   10.12.2003
* Modified  :   10.12.2003
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
$area = "htmltpl";
$mstr = sprintf($tmp_mstr, 'right_top',
                                   $sess->url("main.php?area=htmltpl&frame=3"),
                                   'right_bottom',
                                   $sess->url("main.php?area=htmltpl&frame=4&action=htmltpl_create"),
                                   i18n("Create module template"));
$tpl->set('s', 'NEWSTYLE', $mstr);

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['html_tpl_left_top']);
?>
