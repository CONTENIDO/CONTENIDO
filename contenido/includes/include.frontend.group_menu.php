<?php

/**
 * This file contains the menu frame backend page in frontend group management.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var int $client
 * @var array $cfg
 */

$page = new cGuiPage("frontend.group_menu");
$menu = new cGuiMenu();

$requestIdFrontendGroup = $_GET['idfrontendgroup'] ?? '';

$fegroups = new cApiFrontendGroupCollection();
$fegroups->select("idclient = '$client'", "", "groupname ASC");

while (($fegroup = $fegroups->next()) !== false) {
    $groupname = $fegroup->get("groupname");
    $idfegroup = $fegroup->get("idfrontendgroup");

    $link = new cHTMLLink();
    $link->setClass('show_item')
        ->setLink('javascript:void(0)')
        ->setAttribute('data-action', 'show_frontendgroup');

    $delTitle = i18n("Delete frontend group");
    $deleteLink = '
        <a class="con_img_button" href="javascript:void(0)" data-action="delete_frontendgroup" title="' . $delTitle . '">
            <img src="' . $cfg['path']['images'] . 'delete.gif" title="' . $delTitle . '" alt="' . $delTitle . '">
        </a>';

    $delTooltip = sprintf(i18n('Id of this group: %s'), $idfegroup);

    $menu->setId($idfegroup, $idfegroup);
    $menu->setTitle($idfegroup, conHtmlSpecialChars($groupname));
    $menu->setLink($idfegroup, $link);
    $menu->setImage($idfegroup, "", 0);
    $menu->setActions($idfegroup, 'delete', $deleteLink);
    $menu->setTooltip($idfegroup, $delTooltip);

    if ($requestIdFrontendGroup == $idfegroup) {
        $menu->setMarked($idfegroup);
    }
}

$page->addScript('parameterCollector.js');

$message = i18n("Do you really want to delete the following frontend group:<br><b>%s</b>");
$page->set("s", "DELETE_MESSAGE", $message);

$page->set('s', 'FORM', $menu->render(false));
$page->render();
