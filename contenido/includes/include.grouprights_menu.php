<?php

/**
 * This file contains the menu frame backend page for group management.
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

/**
 * @var cApiUser $currentuser
 */

global $tpl, $db, $classclient, $restriction;

$auth = cRegistry::getAuth();
$perm = cRegistry::getPerm();
$sess = cRegistry::getSession();
$cfg = cRegistry::getConfig();
$area = cRegistry::getArea();
$action = cRegistry::getAction();

$reqGroupId = cSecurity::toString($_REQUEST['groupid'] ?? '');
$restriction = cSecurity::toInteger($_REQUEST['restriction'] ?? '0');

$tpl->reset();

if (($action == "group_delete") && ($perm->have_perm_area_action($area, $action))) {
    $collection = new cApiGroupCollection();
    $collection->delete($reqGroupId);

    $collection = new cApiGroupMemberCollection();
    $collection->deleteBy('group_id', $reqGroupId);

    $collection = new cApiRightCollection();
    $collection->deleteBy('user_id', $reqGroupId);
}

$sql = "SELECT
            groupname, group_id, perms
        FROM
            " . $cfg["tab"]["groups"] . "
        ORDER BY
            groupname ASC";

if ($restriction == 1) {
    $sql = "SELECT
            A.groupname AS groupname, A.group_id as group_id, A.perms as perms
        FROM
            " . $cfg["tab"]["groups"] . " AS A,
            " . $cfg["tab"]["rights"] . " AS B,
            " . $cfg["tab"]["actions"] . " AS C
        WHERE
            C.name = 'front_allow' AND
            B.user_id = A.group_id AND
            C.idaction = B.idaction AND
            A.perms LIKE ''
        GROUP BY
            group_id
        ORDER BY
            groupname ASC";
}

if ($restriction == 3) {
    $sql = "SELECT
            A.groupname AS groupname, A.group_id as group_id, A.perms as perms
        FROM
            " . $cfg["tab"]["groups"] . " AS A,
            " . $cfg["tab"]["rights"] . " AS B,
            " . $cfg["tab"]["actions"] . " AS C
        WHERE
            C.name NOT LIKE 'front_allow' AND
            B.user_id = A.group_id AND
            C.idaction = B.idaction AND
            A.perms NOT LIKE ''
        GROUP BY
            group_id
        ORDER BY
            groupname ASC";
}
$db->query($sql);

$currentUserPerms = explode(',', $auth->auth['perm']);

$accessibleClients = $classclient->getAccessibleClients();

$rightsAreasHelper = new cRightsAreasHelper($currentuser, $auth, []);

$isAuthUserSysadmin = $rightsAreasHelper->isAuthSysadmin();

$canDeleteGroups = $perm->have_perm_area_action($area, 'groups_delete');

$menu = new cGuiMenu('group_rights_list');

$showLink = new cHTMLLink();
$showLink->setClass('show_item')
    ->setLink('javascript:void(0)')
    ->setAttribute('data-action', 'show_group');

$deleteLink = new cHTMLLink();
$deleteLink = $deleteLink->setClass('con_img_button')
    ->setLink('javascript:void(0)')
    ->setAttribute('data-action', 'delete_group')
    ->setContent(cHTMLImage::img($cfg['path']['images'] . 'delete.gif', i18n("Delete group")))
    ->render();

while ($db->nextRecord()) {
    $groupPerms = explode(',', $db->f('perms'));

    $rightsAreasHelper->setContextPermissions($groupPerms);

    $allow = false;

    // Sysadmin check
    if ($isAuthUserSysadmin) {
        $allow = true;
    }

    if (!$allow) {
        // Admin check
        foreach ($accessibleClients as $key => $value) {
            if (cPermission::checkClientPermission(cSecurity::toInteger($key), $groupPerms)) {
                $allow = true;
            }
        }
    }

    if (!$allow) {
        // Group check
        foreach ($groupPerms as $groupPermItem) {
            if (in_array($groupPermItem, $currentUserPerms)) {
                $allow = true;
            }
        }
    }

    if ($allow) {
        $groupId = $db->f('group_id');
        $groupname = conHtmlSpecialChars($db->f('groupname'));
        $groupname = cString::getPartOfString($groupname, 4);

        $menu->setId($groupId, $groupId);
        $menu->setLink($groupId, $showLink);
        $menu->setTitle($groupId, $groupname);

        if ($canDeleteGroups) {
            $menu->setActions($groupId, 'delete', $deleteLink);
        }

        if ($reqGroupId === $groupId) {
            $menu->setMarked($groupId);
        }
    }
}

$tpl->set('s', 'GENERIC_MENU', $menu->render(false));

$deleteMsg = i18n("Do you really want to delete the following group:<br><br>%s<br>");
$tpl->set('s', 'DELETE_MESSAGE', $deleteMsg);

// Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['grouprights_menu']);
