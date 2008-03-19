<?php

/******************************************
* File      :   include.style_left_top.php
* Project   :   Contenido 
*
*
* Author    :   Timo A. Hummel
* Created   :   09.05.2003
* Modified  :   09.05.2003
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
$area = "style";
$mstr = sprintf($tmp_mstr, 'right_top',
                                   $sess->url("main.php?area=js&frame=3"),
                                   'right_bottom',
                                   $sess->url("main.php?area=js&frame=4&action=js_create"),
                                   i18n("Create script"));
$tpl->set('s', 'NEWSCRIPT', $mstr);

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['js_left_top']);
?>
