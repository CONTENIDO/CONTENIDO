<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Frontend group list
 *
 * @package CONTENIDO Backend Includes
 * @version 1.2.0
 * @author unknown
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since file available since CONTENIDO release <= 4.6
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

$page = new cGuiPage("frontend.group_menu");
$menu = new cGuiMenu();

$fegroups = new cApiFrontendGroupCollection();
$fegroups->select("idclient = '$client'", "", "groupname ASC");

while (($fegroup = $fegroups->next()) !== false) {
    $groupname = $fegroup->get("groupname");
    $idfegroup = $fegroup->get("idfrontendgroup");

    $link = new cHTMLLink();
    $link->setMultiLink("frontendgroups", "", "frontendgroups", "");
    $link->setCustom("idfrontendgroup", $idfegroup);

    $delTitle = i18n("Delete frontend group");
    $delDescr = sprintf(i18n("Do you really want to delete the following frontend group:<br><b>%s</b>"), conHtmlSpecialChars($groupname));
    $delete = '<a title="' . $delTitle . '" href="javascript:void(0)" onclick="showConfirmation(&quot;' . $delDescr . '&quot;, function() { deleteFrontendGroup(' . $idfegroup . '); });return false;"><img src="' . $cfg['path']['images'] . 'delete.gif" title="' . $delTitle . '" alt="' . $delTitle . '"></a>';

    $menu->setTitle($idfegroup, $groupname);
    $menu->setLink($idfegroup, $link);
    $menu->setImage($idfegroup, "", 0);
    $menu->setActions($idfegroup, 'delete', $delete);

    if ($_GET['idfrontendgroup'] == $idfegroup) {
        $menu->setMarked($idfegroup);
    }
}

$page->set('s', 'FORM', $menu->render());
$page->render();
