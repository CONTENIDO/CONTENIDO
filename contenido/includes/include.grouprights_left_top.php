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

// The following lines unset all right objects since 
// I don't know (or I was unable to find out) if they
// are global and/or session variables - so if you are
// switching between groups and user management, we are
// safe.
unset($right_list);
unset($rights_list_old);
unset($rights_perms);
$right_list = "";
$rights_list_old = "";
$rights_perms = "";

$tpl->set('s', 'ID', 'oTplSel');
$tpl->set('s', 'CLASS', 'text_medium');
$tpl->set('s', 'OPTIONS', '');
$tpl->set('s', 'SESSID', $sess->id);
$tpl->set('s', 'SID', $sess->id);


$tpl2 = new Template;
$tpl2->set('s', 'NAME', 'restrict');
$tpl2->set('s', 'CLASS', 'text_medium');
$tpl2->set('s', 'OPTIONS', 'onchange="groupChangeRestriction()"');

$limit = array(
			"2" => i18n("All"),
			"1" => i18n("Frontend only"),
			"3" => i18n("Backend only"));
			
foreach ($limit as $key => $value) {

        if ($restrict == $key)
        {
        	$selected = "selected";
        } else {
        	$selected = "";
        } 

        $tpl2->set('d', 'VALUE',    $key);
        $tpl2->set('d', 'CAPTION',  $value);
        $tpl2->set('d', 'SELECTED', $selected);
        $tpl2->next();

}

$select = $tpl2->generate($cfg["path"]["templates"] . $cfg['templates']['generic_select'], true);

//$tpl->set('s', 'CAPTION', $select);
$tpl->set('s', 'CAPTION', '');

$tmp_mstr = '<a class="addfunction" href="javascript:conMultiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a>';
$area = "group";
$mstr = sprintf($tmp_mstr, 'right_top',
                                   $sess->url("main.php?area=groups_create&frame=3"),
                                   'right_bottom',
                                   $sess->url("main.php?area=groups_create&frame=4"),
                                   i18n("Create group"));
$tpl->set('s', 'NEWGROUP', $mstr);

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['grouprights_left_top']);
?>
