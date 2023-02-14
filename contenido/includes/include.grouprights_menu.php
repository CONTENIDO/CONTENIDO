<?php

/**
 * This file contains the menu frame backend page for group management.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $tpl, $db, $classclient, $restriction;

$auth = cRegistry::getAuth();
$perm = cRegistry::getPerm();
$sess = cRegistry::getSession();
$cfg = cRegistry::getConfig();
$area = cRegistry::getArea();
$action = cRegistry::getAction();

$reqGroupId = (isset($_REQUEST['groupid'])) ? cSecurity::toString($_REQUEST['groupid']) : '';
$restriction = (isset($_REQUEST['restriction'])) ? cSecurity::toInteger($_REQUEST['restriction']) : 0;

$tpl->reset();

if (($action == "group_delete") && ($perm->have_perm_area_action($area, $action))) {
    $sql = "DELETE FROM " . $cfg["tab"]["groups"] . " WHERE group_id = '" . $db->escape($reqGroupId) . "'";
    $db->query($sql);

    $sql = "DELETE FROM " . $cfg["tab"]["groupmembers"] . " WHERE group_id = '" . $db->escape($reqGroupId) . "'";
    $db->query($sql);

    $sql = "DELETE FROM " . $cfg["tab"]["rights"] . " WHERE user_id = '" . $db->escape($reqGroupId) . "'";
    $db->query($sql);
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

$thisperm = explode(',', $auth->auth['perm']);

$accessibleClients = $classclient->getAccessibleClients();

/**
 * @var cApiUser $currentuser
 */
$rightsAreasHelper = new cRightsAreasHelper($currentuser, $auth, []);

$isAuthUserSysadmin = $rightsAreasHelper->isAuthSysadmin();

while ($db->nextRecord()) {
    $groupperm = explode(',', $db->f('perms'));

    $rightsAreasHelper->setContextPermissions($groupperm);

    $allow = false;

    // Sysadmin check
    if ($isAuthUserSysadmin) {
        $allow = true;
    }

    if (!$allow) {
        // Admin check
        foreach ($accessibleClients as $key => $value) {
            if (cPermission::checkClientPermission($key, $groupperm)) {
                $allow = true;
            }
        }
    }

    if (!$allow) {
        // Group check
        foreach ($groupperm as $localperm) {
            if (in_array($localperm, $thisperm)) {
                $allow = true;
            }
        }
    }

    if ($allow) {
        $groupid = $db->f("group_id");
        $groupname = conHtmlSpecialChars($db->f("groupname"));
        $groupname = cString::getPartOfString($groupname, 4);

        $area = "groups";

        if ($reqGroupId == $groupid) {
            $tpl->set('d', 'ATTRIBUTES', 'id="marked" data-id="' . $groupid . '"');
        } else {
            $tpl->set('d', 'ATTRIBUTES', 'data-id="' . $groupid . '"');
        }

        $tpl->set('d', 'ICON', '');

        $showLink = '<a href="javascript:void(0)" class="show_item" data-action="show_group">' . $groupname . '</a>';
        $tpl->set('d', 'TEXT', $showLink);

        if ($perm->have_perm_area_action('groups', "groups_delete")) {
            $delTitle = i18n("Delete group");
            $deleteLink = '<a href="javascript:void(0)" data-action="delete_group" title="' . $delTitle . '"><img src="' . $cfg['path']['images'] . 'delete.gif" title="' . $delTitle . '" alt="' . $delTitle . '"></a>';
        } else {
            $deleteLink = '&nbsp;';
        }
        $tpl->set('d', 'DELETE', $deleteLink);

        $tpl->next();
    }
}

$deleteMsg = i18n("Do you really want to delete the following group:<br><br>%s<br>");
$tpl->set('s', 'DELETE_MESSAGE', $deleteMsg);

// Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['grouprights_menu']);
