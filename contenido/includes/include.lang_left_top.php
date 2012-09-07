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
 *   created 2003-05-08
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id: include.lang_left_top.php 351 2008-06-27 11:30:37Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

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

$iClientcount = count($clients);

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

if ($perm->have_perm_area_action($area, "lang_newlanguage") && $iClientcount > 0) {
    $tpl->set('s', 'NEWLANG', '<a class="addfunction" href="javascript:languageNewConfirm()">'.i18n("Create language").'</a>');
} else if ($iClientcount == 0) {
    $tpl->set('s', 'NEWLANG', i18n('No Client selected'));
} else {
    $tpl->set('s', 'NEWLANG', '');
}

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['lang_left_top']);

?>