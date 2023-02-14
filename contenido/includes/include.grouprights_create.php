<?php

/**
 * This file contains the backend page for creating new groups.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Unknown
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var cPermission $perm
 * @var cSession $sess
 * @var cTemplate $tpl
 * @var cGuiNotification $notification
 * @var cAuth $auth
 * @var cApiUser $currentuser
 * @var array $cfg
 * @var int $frame
 * @var string $area
 */

$action = cRegistry::getAction();

if (!$perm->have_perm_area_action($area, $action)) {
    // access denied
    $notification->displayNotification('error', i18n('Permission denied'));
    return;
}

$groupname = $groupname ?? '';
$description = $description ?? '';

$lang = cSecurity::toInteger(cRegistry::getLanguageId());

// create group instance
$bError        = false;
$sNotification = '';
$aPerms        = [];
$groupId       = NULL;

if ($action == 'group_create') {
    $aPerms = cRights::buildUserOrGroupPermsFromRequest();

    if ($groupname == '') {
        $groupname = cApiGroup::PREFIX . i18n("New Group");
    }

    $groupname = stripcslashes(preg_replace("/\"/", "", ($groupname)));
    $description = stripcslashes(preg_replace("/\"/", "", ($description)));

    $oGroup = new cApiGroup();
    $oGroup->loadGroupByGroupname($groupname);
    if ($oGroup->isLoaded()) {
        $sNotification = $notification->returnNotification("warning", sprintf(i18n("Group name <strong>%s</strong> already exists"), $groupname));
        $bError = true;
    } else {
        $oGroupColl = new cApiGroupCollection();
        $oGroup = $oGroupColl->create($groupname, implode(',', $aPerms), $description);
        if (is_object($oGroup)) {
            $groupId = $oGroup->getGroupId();
        } else {
            $sNotification = $notification->returnNotification("error", i18n("Group couldn't created"));
            $bError = true;
        }
    }
}

$tpl->reset();
$tpl->set('s', 'NOTIFICATION', $sNotification);
$tpl->set('s', 'GROUPID', $groupId);

$form = '<form name="group_properties" method="post" action="' . $sess->url("main.php?") . '">
             <input type="hidden" name="area" value="' . $area . '">
             <input type="hidden" name="action" value="group_create">
             <input type="hidden" name="frame" value="' . $frame . '">
             <input type="hidden" name="idlang" value="' . $lang . '">';

$tpl->set('s', 'FORM', $form);
$tpl->set('s', 'SUBMITTEXT', i18n("Save changes"));

$tpl->set('s', 'PROPERTY', i18n("Property"));
$tpl->set('s', 'VALUE', i18n("Value"));

$tpl->set('d', 'CATNAME', i18n("Group name"));
$oTxtName = new cHTMLTextbox('groupname', conHtmlSpecialChars($groupname), 40, 255);
$tpl->set('d', 'CATFIELD', $oTxtName->render());

$tpl->next();

$tpl->set('d', 'CATNAME', i18n("Description"));
$oTxtDesc = new cHTMLTextbox('description', $description, 40, 255);
$tpl->set('d', 'CATFIELD', $oTxtDesc->render());
$tpl->next();

// Build perm checkboxes and properties table with the helper
$rightsAreasHelper = new cRightsAreasHelper($currentuser, $auth, $aPerms);
$isAuthUserSysadmin = $rightsAreasHelper->isAuthSysadmin();
$isContextSysadmin = $rightsAreasHelper->isContextSysadmin();

// Sysadmin perm checkbox
if ($isAuthUserSysadmin) {
    $tpl->set('d', 'CATNAME', i18n("System administrator"));
    $defaultsysadmin = new cHTMLCheckbox("msysadmin", "1", "msysadmin1", $isContextSysadmin);
    $tpl->set('d', 'CATFIELD', $defaultsysadmin->toHtml(false));
    $tpl->next();
}

// Clients admin perms checkboxes
$aClients = $rightsAreasHelper->getAvailableClients();
$sClientCheckboxes = $rightsAreasHelper->renderClientAdminCheckboxes($aClients);
if ($sClientCheckboxes !== '') {
    $tpl->set('d', 'CATNAME', i18n("Administrator"));
    $tpl->set('d', 'CATFIELD', $sClientCheckboxes);
    $tpl->next();
}

// Clients perms checkboxes
$sClientCheckboxes = '';
foreach ($aClients as $idclient => $item) {
    $hasAuthUserClientPerm = $rightsAreasHelper->hasAuthClientPerm($idclient);
    $isAuthUserClientAdmin = $rightsAreasHelper->isAuthClientAdmin($idclient);
    if ($hasAuthUserClientPerm || $isAuthUserSysadmin || $isAuthUserClientAdmin) {
        $sClientCheckboxes .= $rightsAreasHelper->renderClientPermCheckbox($idclient, $item['name']);
    }
}
if (empty($sClientCheckboxes)) {
    $sClientCheckboxes = i18n("No client");
}
$tpl->set('d', 'CATNAME', i18n("Access clients"));
$tpl->set('d', 'CATFIELD', $sClientCheckboxes);
$tpl->next();

// Languages perms checkboxes
$aClientsLanguages = getAllClientsAndLanguages();
$sClientCheckboxes = '';
foreach ($aClientsLanguages as $item) {
    $hasLanguagePerm = $rightsAreasHelper->hasAuthLanguagePerm($item['idlang']);
    $isAuthUserClientAdmin = $rightsAreasHelper->isAuthClientAdmin($item['idclient']);
    if ($hasLanguagePerm || $isAuthUserClientAdmin) {
        $sClientCheckboxes .= $rightsAreasHelper->renderLanguagePermCheckbox(
            $item['idlang'], $item['langname'], $item['clientname']
        );
    }
}
if (empty($sClientCheckboxes)) {
    $sClientCheckboxes = i18n("No language");
}

$tpl->set('d', 'CATNAME', i18n("Access languages"));
$tpl->set('d', 'CATFIELD', $sClientCheckboxes);
$tpl->next();

// Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['grouprights_create']);
