<?php

/**
 * This file contains the left top frame backend page for group rights management.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $tpl;

$cfg = cRegistry::getConfig();
$perm = cRegistry::getPerm();
$sess = cRegistry::getSession();

$restrict = cSecurity::toString($_REQUEST['restrict'] ?? '');

// The following lines unset all right objects since I don't know (or I was unable
// to find out) if they are global and/or session variables - so if you are
// switching between groups and user management, we are safe.
unset($right_list);
unset($rights_list_old);
unset($rights_perms);
$right_list = "";
$rights_list_old = "";
$rights_perms = "";

$tpl->set('s', 'ID', 'oTplSel');
$tpl->set('s', 'CLASS', 'text_medium');
$tpl->set('s', 'OPTIONS', '');

$tpl2 = new cTemplate;
$tpl2->set('s', 'NAME', 'restrict');
$tpl2->set('s', 'CLASS', 'text_medium');
$tpl2->set('s', 'OPTIONS', 'onchange="groupChangeRestriction()"');

$limit = [
    "2" => i18n("All"),
    "1" => i18n("Frontend only"),
    "3" => i18n("Backend only")
];

foreach ($limit as $key => $value) {
    $selected = ($restrict == $key) ? "selected" : "";
    $tpl2->set('d', 'VALUE', $key);
    $tpl2->set('d', 'CAPTION', $value);
    $tpl2->set('d', 'SELECTED', $selected);
    $tpl2->next();
}

$select = $tpl2->generate($cfg["path"]["templates"] . $cfg['templates']['generic_select'], true);


$tpl->set('s', 'CAPTION', '');

$tmp_mstr = '<div class="top_left_action"><a class="addfunction" href="javascript:Con.multiLink(\'%s\', \'%s\', \'%s\', \'%s\')">%s</a></div>';
// $area = "group"; What is the purpose of this???
$mstr = sprintf($tmp_mstr, 'right_top', $sess->url("main.php?area=groups_create&frame=3"), 'right_bottom', $sess->url("main.php?area=groups_create&frame=4"), i18n("Create group"));
if ($perm->have_perm_area_action("groups_create", "group_create")) {
    $tpl->set('s', 'NEWGROUP', $mstr);
} else {
    $tpl->set('s', 'NEWGROUP', '<div class="top_left_action"><a class="addfunction_disabled" href="#">' . i18n("No permission to create groups") . '</a></div>');
}

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['grouprights_left_top']);
