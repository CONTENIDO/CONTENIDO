<?php

/******************************************
* File      :   include.lang_left_top.php
* Project   :   Contenido
*
*
* Author    :   Timo A. Hummel
* Created   :   08.05.2003
* Modified  :   08.05.2003
*
* © four for business AG
******************************************/


$tpl->set('s', 'CLASS', 'text_medium');
$tpl->set('s', 'OPTIONS', '');
$tpl->set('s', 'CAPTION', '');
$tpl->set('s', 'SESSID', $sess->id);

$tpl->set('s', 'ACTION', '');
$tpl->set('s', 'SID', $sess->id);

$clients = $classclient->getAccessibleClients();


$tpl2 = new Template;
$tpl2->set('s', 'ID', 'editclient');
$tpl2->set('s', 'NAME', 'editclient');
$tpl2->set('s', 'CLASS', 'text_medium');
$tpl2->set('s', 'OPTIONS', 'onchange="langChangeClient()"');

foreach ($clients as $key => $value) {

        if ($client == $key)
        {
        	$selected = "selected";
        } else {
        	$selected = "";
        }

		if (strlen($value['name']) > 15)
		{
			$value['name'] = substr($value['name'],0,12). "...";
		}

        $tpl2->set('d', 'VALUE',    $key);
        $tpl2->set('d', 'CAPTION',  $value['name']);
        $tpl2->set('d', 'SELECTED', $selected);
        $tpl2->next();

}

$select = $tpl2->generate($cfg["path"]["templates"] . $cfg['templates']['generic_select'], true);

$tpl->set('s', 'CLIENTSELECT', $select);

if ($perm->have_perm_area_action($area, "lang_newlanguage")) {
    $tpl->set('s', 'NEWLANG', '<a class="addfunction" href="javascript:languageNewConfirm()">'.i18n("Create language").'</a>');
} else {
    $tpl->set('s', 'NEWLANG', '');
}

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['lang_left_top']);

?>
