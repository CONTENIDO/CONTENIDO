<?php

/**
 * This file contains the backend page for the group overview.
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

/**
 * @var cApiUser $currentuser
 * @var cGuiNotification $notification
 */

global $mclient;

$auth = cRegistry::getAuth();
$perm = cRegistry::getPerm();
$sess = cRegistry::getSession();
$frame = cRegistry::getFrame();
$lang = cRegistry::getLanguageId();
$area = cRegistry::getArea();
$action = cRegistry::getAction();

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
$aPerms = [];

// Info message for a new group
if (isset($request['created']) && $request['created'] == 1) {
    $sNotification = $notification->returnNotification("ok", i18n("New group created. Now you can edit and configure your new group."));
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
                $clientLangColl = new cApiClientLanguageCollection();
                if (!$clientLangColl->hasLanguageInClients($ilang, $mclient)) {
                    $sNotification = $notification->returnNotification("error", i18n("If you want to assign a language to a group you need to give it access to the client too."));
                    $bError = true;
                    break;
                }
            }
        }
    }

    if (!$bError) {
        $aPerms = cRights::buildUserOrGroupPermsFromRequest();
        $oGroup->setField('description', $request['description']);
        $oGroup->setField('perms', implode(',', $aPerms));

        if ($oGroup->store()) {
            $sNotification = $notification->returnNotification("ok", i18n("Changes saved"));
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
$page->set('d', 'CATFIELD', stripslashes(conHtmlSpecialChars($oGroup->getGroupName(true))));
$page->next();

$page->set('d', 'CATNAME', i18n("Description"));
$oTxtDesc = new cHTMLTextbox('description', conHtmlSpecialChars($oGroup->getField('description') ?? ''), 40, 255);
$page->set('d', 'CATFIELD', $oTxtDesc->render());
$page->next();

// Build perm checkboxes and properties table with the helper
$rightsAreasHelper = new cRightsAreasHelper($currentuser, $auth, $aPerms);
$isAuthUserSysadmin = $rightsAreasHelper->isAuthSysadmin();
$isContextSysadmin = $rightsAreasHelper->isContextSysadmin();

// Sysadmin perm checkbox
if ($isAuthUserSysadmin) {
    $page->set('d', 'CATNAME', i18n("System administrator"));
    $oCheckbox = new cHTMLCheckbox('msysadmin', '1', 'msysadmin1', $isContextSysadmin);
    $page->set('d', 'CATFIELD', $oCheckbox->toHtml(false));
    $page->next();
}

// Clients admin perms checkboxes
$aClients = $rightsAreasHelper->getAvailableClients();
$sClientCheckboxes = $rightsAreasHelper->renderClientAdminCheckboxes($aClients);
if (!empty($sClientCheckboxes) && !$isContextSysadmin) {
    $page->set('d', 'CATNAME', i18n("Administrator"));
    $page->set('d', 'CATFIELD', $sClientCheckboxes);
    $page->next();
}

// Clients perms checkboxes
$sClientCheckboxes = '';
foreach ($aClients as $idclient => $item) {
    $hasAuthUserClientPerm = $rightsAreasHelper->hasAuthClientPerm($idclient);
    $isAuthUserClientAdmin = $rightsAreasHelper->isAuthClientAdmin($idclient);
    $isContextClientAdmin = $rightsAreasHelper->isContextClientAdmin($idclient);
    if (($hasAuthUserClientPerm || $isAuthUserSysadmin || $isAuthUserClientAdmin) && !$isContextClientAdmin) {
        $sClientCheckboxes .= $rightsAreasHelper->renderClientPermCheckbox($idclient, $item['name']);
    }
}
if (!empty($sClientCheckboxes) && !$isContextSysadmin) {
    $page->set('d', 'CATNAME', i18n("Access clients"));
    $page->set('d', 'CATFIELD', $sClientCheckboxes);
    $page->next();
}

// Languages perms checkboxes
$aClientsLanguages = getAllClientsAndLanguages();
$sClientCheckboxes = '';
foreach ($aClientsLanguages as $item) {
    $hasLanguagePerm = $rightsAreasHelper->hasAuthLanguagePerm($item['idlang']);
    $isAuthUserClientAdmin = $rightsAreasHelper->isAuthClientAdmin($item['idclient']);
    $isContextClientAdmin = $rightsAreasHelper->isContextClientAdmin($item['idclient']);
    if (($hasLanguagePerm || $isAuthUserClientAdmin) && !$isContextClientAdmin) {
        $sClientCheckboxes .= $rightsAreasHelper->renderLanguagePermCheckbox(
            $item['idlang'], $item['langname'], $item['clientname']
        );
    }
}
if (!empty($sClientCheckboxes) && !$isContextSysadmin) {
    $page->set('d', 'CATNAME', i18n("Access languages"));
    $page->set('d', 'CATFIELD', $sClientCheckboxes);
    $page->next();
}

// Group properties
$aProperties = $oGroup->getGroupProperties();
foreach ($aProperties as $pos => $entry) {
    $aProperties[$pos]['href'] = $sess->url("main.php?area=$area&frame=4&groupid={$request['groupid']}&del_groupprop_type={$entry['type']}&del_groupprop_name={$entry['name']}");
}
$table = $rightsAreasHelper->renderPropertiesTable(
    $aProperties, 'groupprop_type', 'groupprop_name', 'groupprop_value'
);

$page->set('d', 'CATNAME', i18n("User-defined properties"));
$page->set('d', 'CATFIELD', $table);
$page->next();

// Generate template
$page->render();
