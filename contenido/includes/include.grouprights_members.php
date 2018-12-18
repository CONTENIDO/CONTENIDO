<?php

/**
 * This file contains the backend page for user to group assigment.
 *
 * @package Core
 * @subpackage Backend
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$db2 = cRegistry::getDb();
// $page = new cTemplate();

$page = new cGuiPage('grouprights_memberselect', '', '1');

if (!$perm->have_perm_area_action($area, $action)) {
    $notification->displayNotification("error", i18n("Permission denied"));
    return;
} elseif (!isset($groupid)) {
    return;
}

if (($action == "group_deletemember") && ($perm->have_perm_area_action($area, $action))) {
    $aDeleteMembers = array();
    if (!is_array($_POST['user_in_group'])) {
        if ($_POST['user_in_group'] > 0) {
            $aDeleteMembers[] = $_POST['user_in_group'];
        }
    } else {
        $aDeleteMembers = $_POST['user_in_group'];
    }

    $groupMemberColl = new cApiGroupMemberCollection();
    foreach ($aDeleteMembers as $idgroupuser) {
        $groupMemberColl->delete((int) $idgroupuser);
    }

    $notification->displayNotification(cGuiNotification::LEVEL_OK, i18n("Removed member from group successfully!"));
}

if (($action == "group_addmember") && ($perm->have_perm_area_action($area, $action))) {
    if (is_array($newmember)) {
        foreach ($newmember as $key => $value) {
            $myUser = new cApiUser();

            if (!$myUser->loadByPrimaryKey($value)) {
                $myUser->loadUserByUsername($value);
            }

            if ($myUser->getField("user_id") == "") {
                continue;
            }

            $groupMemberColl = new cApiGroupMemberCollection();
            $groupMember = $groupMemberColl->fetchByUserIdAndGroupId($myUser->getField('user_id'), $groupid);

            if (!$groupMember) {
                // group member entry does not exists, create it
                $newGroupMember = $groupMemberColl->create($myUser->getField('user_id'), $groupid);
                if ($notiAdded == '') {
                    $notiAdded .= $myUser->getField('realname');
                } else {
                    $notiAdded .= ', ' . $myUser->getField('realname');
                }
            } else {
                // group member entry already exists
                if ($notiAlreadyExisting == '') {
                    $notiAlreadyExisting .= $myUser->getField('realname');
                } else {
                    $notiAlreadyExisting .= ', ' . $myUser->getField('realname');
                }
            }
        }

        $notification->displayNotification(cGuiNotification::LEVEL_OK, i18n("Added user to group successfully!"));
    }
}

$tab1 = $cfg["tab"]["groupmembers"];
$tab2 = $cfg['tab']['user'];

$sortby = getEffectiveSetting("backend", "sort_backend_users_by", "");

if ($sortby != '') {
    $sql = "SELECT " . $tab1 . ".idgroupuser, " . $tab1 . ".user_id FROM " . $tab1 . "
            INNER JOIN " . $tab2 . " ON " . $tab1 . ".user_id = " . $tab2 . ".user_id WHERE
            group_id = '" . $db->escape($groupid) . "' ORDER BY " . $tab2 . "." . $sortby;
} else {
    // how previous behaviour by default
    $sql = "SELECT " . $tab1 . ".idgroupuser, " . $tab1 . ".user_id FROM " . $tab1 . "
            INNER JOIN " . $tab2 . " ON " . $tab1 . ".user_id = " . $tab2 . ".user_id WHERE
            group_id = '" . $db->escape($groupid) . "' ORDER BY " . $tab2 . ".realname, " . $tab2 . ".username";
}

$db->query($sql);

$sInGroupOptions = '';
$aAddedUsers = array();
$myUser = new cApiUser();

while ($db->nextRecord()) {
    $myUser->loadByPrimaryKey($db->f("user_id"));
    $aAddedUsers[] = $myUser->getField("username");

    $sOptionLabel = $myUser->getField("realname") . ' (' . $myUser->getField("username") . ')';
    $sOptionValue = $db->f("idgroupuser");
    if ($sOptionValue != '' && $sOptionLabel != '') {
        $sInGroupOptions .= '<option value="' . $sOptionValue . '">' . $sOptionLabel . '</option>' . "\n";
    }
}

$page->set('s', 'IN_GROUP_OPTIONS', $sInGroupOptions);

// Sort user list by given criteria
$orderBy = getEffectiveSetting('backend', 'sort_backend_users_by', '');

$userColl = new cApiUserCollection();
$users = $userColl->getAccessibleUsers(explode(',', $auth->auth['perm']), false, $orderBy);

$bAddedUser = false;
$sNonGroupOptions = '';
if (is_array($users)) {
    foreach ($users as $key => $value) {
        if (!in_array($value["username"], $aAddedUsers)) {
            $bAddedUser = true;
            $sOptionLabel = $value["realname"] . " (" . $value["username"] . ")";
            $sOptionValue = $key;
            if ($sOptionValue != '' && $sOptionLabel != '') {
                $sNonGroupOptions .= '<option value="' . $sOptionValue . '">' . $sOptionLabel . '</option>' . "\n";
            }
        }
    }
}

$page->set('s', 'NON_GROUP_OPTIONS', $sNonGroupOptions);

$page->set('s', 'CATNAME', i18n("Manage group members"));
$page->set('s', 'CATFIELD', "&nbsp;");
$page->set('s', 'FORM_ACTION', $sess->url('main.php'));
$page->set('s', 'AREA', $area);
$page->set('s', 'GROUPID', $groupid);
$page->set('s', 'FRAME', $frame);
$page->set('s', 'IDLANG', $lang);
$page->set('s', 'RECORD_ID_NAME', 'groupid');
$page->set('s', 'ADD_ACTION', 'group_addmember');
$page->set('s', 'DELETE_ACTION', 'group_deletemember');
$page->set('s', 'STANDARD_ACTION', 'group_addmember');
$page->set('s', 'IN_GROUP_VALUE', $_POST['filter_in']);
$page->set('s', 'NON_GROUP_VALUE', $_POST['filter_non']);
$page->set('s', 'DISPLAY_OK', 'none');
$page->set('s', 'RELOADSCRIPT', '');

// Generate template
$page->render();
