<?php
/**
 * This file contains the backend page for the group overview.
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.rights.php');
$page = new cGuiPage('grouprights_overview', '', '0');

if (!$perm->have_perm_area_action($area, $action)) {
    // access denied
    $notification->displayNotification('error', i18n('Permission denied'));
    return;
}

// @TODO Find a general solution for this!
if (defined('CON_STRIPSLASHES')) {
    $request = cString::stripSlashes($_REQUEST);
} else {
    $request = $_REQUEST;
}

if (!isset($request['groupid'])) {
    // no group id, get out here
    return;
}

// create group instance
$oGroup = new cApiGroup($request['groupid']);
$bError = false;
$sNotification = '';
$aPerms = array();

// Info message for a new group
if ($_REQUEST['created'] == 1) {
    $sNotification = $notification->returnNotification("info", i18n("New group created. Now you can edit and configure your new group."));
}

// Action edit group
if (($action == 'group_edit')) {
    $bError = false;

    if (isset($mlang) && is_array($mlang)) {
        if (0 < count($mlang) && !isset($mclient)) {
            $sNotification = $notification->returnNotification("error", i18n("If you want to assign a language to a group you need to give it access to the client too."));
            $bError = true;
        } else {
            foreach ($mlang as $ilang) {
                if (!checkLangInClients($mclient, $ilang, NULL, NULL)) {
                    $sNotification = $notification->returnNotification("error", i18n("If you want to assign a language to a group you need to give it access to the client too."));
                    $bError = true;
                    break;
                }
            }
        }
    }

    if (!$bError) {
        $aPerms = buildUserOrGroupPermsFromRequest();
        $oGroup->setField('description', $request['description']);
        $oGroup->setField('perms', implode(',', $aPerms));

        if ($oGroup->store()) {
            $sNotification = $notification->returnNotification("info", i18n("Changes saved"));
        } else {
            $sNotification = $notification->returnNotification("error", i18n("Changes couldn't be saved"));
            $bError = true;
        }
    }
}

// Action delete group property
if (!empty($request['del_groupprop_type']) && !empty($request['del_groupprop_name'])) {
    $oGroup->deleteGroupProperty($request['del_groupprop_type'], $request['del_groupprop_name']);
}

// Action add group property
if (!empty($request['groupprop_type']) && !empty($request['groupprop_name'])) {
    $oGroup->setGroupProperty($request['groupprop_type'], $request['groupprop_name'], $request['groupprop_value']);
}

$aPerms = explode(',', $oGroup->getField('perms'));

// $page->reset();
$page->set('s', 'NOTIFICATION', $sNotification);

$form = '<form name="group_properties" method="post" action="' . $sess->url("main.php?") . '">
             <input type="hidden" name="area" value="' . $area . '">
             <input type="hidden" name="action" value="group_edit">
             <input type="hidden" name="frame" value="' . $frame . '">
             <input type="hidden" name="groupid" value="' . $request['groupid'] . '">
             <input type="hidden" name="idlang" value="' . $lang . '">';

$page->set('s', 'FORM', $form);
$page->set('s', 'GET_GROUPID', $request['groupid']);
$page->set('s', 'SUBMITTEXT', i18n("Save changes"));
$page->set('s', 'CANCELTEXT', i18n("Discard changes"));
$page->set('s', 'CANCELLINK', $sess->url("main.php?area=$area&frame=4&groupid={$request['groupid']}"));
$page->set('s', 'PROPERTY', i18n("Property"));
$page->set('s', 'VALUE', i18n("Value"));

$page->set('d', 'CATNAME', i18n("Groupname"));
$page->set('d', 'CATFIELD', stripslashes($oGroup->getGroupName(true)));
$page->next();

$page->set('d', 'CATNAME', i18n("Description"));
$oTxtDesc = new cHTMLTextbox('description', conHtmlSpecialChars($oGroup->getField('description')), 40, 255);
$page->set('d', 'CATFIELD', $oTxtDesc->render());
$page->next();

// permissions of current logged in user
$aAuthPerms = explode(',', $auth->auth['perm']);

// sysadmin perm
if (in_array('sysadmin', $aAuthPerms)) {
    $page->set('d', 'CATNAME', i18n("System administrator"));
    $oCheckbox = new cHTMLCheckbox('msysadmin', '1', 'msysadmin1', in_array('sysadmin', $aPerms));
    $page->set('d', 'CATFIELD', $oCheckbox->toHTML(false));
    $page->next();
}

// clients admin perms
$oClientsCollection = new cApiClientCollection();
$aClients = $oClientsCollection->getAvailableClients();
$sClientCheckboxes = '';
foreach ($aClients as $idclient => $item) {
    if (in_array("admin[" . $idclient . "]", $aAuthPerms) || in_array('sysadmin', $aAuthPerms)) {
        $oCheckbox = new cHTMLCheckbox("madmin[" . $idclient . "]", $idclient, "madmin[" . $idclient . "]" . $idclient, in_array("admin[" . $idclient . "]", $aPerms));
        $oCheckbox->setLabelText($item['name'] . " (" . $idclient . ")");
        $sClientCheckboxes .= $oCheckbox->toHTML();
    }
}

if ($sClientCheckboxes !== '' && !in_array('sysadmin', $aPerms)) {
    $page->set('d', 'CATNAME', i18n("Administrator"));
    $page->set('d', 'CATFIELD', $sClientCheckboxes);
    $page->next();
}

// clients perms
$sClientCheckboxes = '';
foreach ($aClients as $idclient => $item) {
    if ((in_array("client[" . $idclient . "]", $aAuthPerms) || in_array('sysadmin', $aAuthPerms) || in_array("admin[" . $idclient . "]", $aAuthPerms)) && !in_array("admin[" . $idclient . "]", $aPerms)) {
        $oCheckbox = new cHTMLCheckbox("mclient[" . $idclient . "]", $idclient, "mclient[" . $idclient . "]" . $idclient, in_array("client[" . $idclient . "]", $aPerms));
        $oCheckbox->setLabelText($item['name'] . " (" . $idclient . ")");
        $sClientCheckboxes .= $oCheckbox->toHTML();
    }
}

if ($sClientCheckboxes != '' && !in_array('sysadmin', $aPerms)) {
    $page->set('d', 'CATNAME', i18n("Access clients"));
    $page->set('d', 'CATFIELD', $sClientCheckboxes);
    $page->next();
}

// languages perms
$aClientsLanguages = getAllClientsAndLanguages();
$sClientCheckboxes = '';
foreach ($aClientsLanguages as $item) {
    if (($perm->have_perm_client("lang[" . $item['idlang'] . "]") || $perm->have_perm_client("admin[" . $item['idclient'] . "]")) && !in_array("admin[" . $item['idclient'] . "]", $aPerms)) {
        $oCheckbox = new cHTMLCheckbox("mlang[" . $item['idlang'] . "]", $item['idlang'], "mlang[" . $item['idlang'] . "]" . $item['idlang'], in_array("lang[" . $item['idlang'] . "]", $aPerms));
        $oCheckbox->setLabelText($item['langname'] . " (" . $item['clientname'] . ")");
        $sClientCheckboxes .= $oCheckbox->toHTML();
    }
}

if ($sClientCheckboxes != '' && !in_array('sysadmin', $aPerms)) {
    $page->set('d', 'CATNAME', i18n("Access languages"));
    $page->set('d', 'CATFIELD', $sClientCheckboxes);
    $page->next();
}

// group properties
$aProperties = $oGroup->getGroupProperties();
$sPropRows = '';
foreach ($aProperties as $propertyId => $prop) {
    $type = $prop['type'];
    $name = $prop['name'];
    $value = $prop['value'];
    $sPropRows .= '
    <tr class="text_medium">
        <td>' . $type . '</td>
        <td>' . $name . '</td>
        <td>' . $value . '</td>
        <td>
            <a href="' . $sess->url("main.php?area=$area&frame=4&groupid={$request['groupid']}&del_groupprop_type=$type&del_groupprop_name=$name") . '"><img src="images/delete.gif" border="0" alt="' . i18n("Delete") . '" title="' . i18n("Delete") . '"></a>
        </td>
    </tr>';
}

$table = '
    <table class="generic" width="100%" cellspacing="0" cellpadding="2">
    <tr>
        <th>' . i18n("Area/Type") . '</th>
        <th>' . i18n("Property") . '</th>
        <th>' . i18n("Value") . '</th>
    </tr>
    ' . $sPropRows . '
    <tr class="text_medium">
        <td><input class="text_medium"  type="text" size="16" maxlen="32" name="groupprop_type"></td>
        <td><input class="text_medium" type="text" size="16" maxlen="32" name="groupprop_name"></td>
        <td><input class="text_medium" type="text" size="32" name="groupprop_value"></td>
    </tr>
    </table>';

$page->set('d', 'CATNAME', i18n("User-defined properties"));
$page->set('d', 'CATFIELD', $table);
$page->next();

// Generate template
$page->render();
