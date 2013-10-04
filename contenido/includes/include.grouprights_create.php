<?php
/**
 * This file contains the backend page for creating new groups.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Unknown
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.rights.php');

if (!$perm->have_perm_area_action($area, $action)) {
    // access denied
    $notification->displayNotification('error', i18n('Permission denied'));
    return;
}

// create group instance
$bError        = false;
$sNotification = '';
$aPerms        = array();

if ($action == 'group_create') {
    $aPerms = buildUserOrGroupPermsFromRequest();

    if ($groupname == '') {
        $groupname = cApiGroup::PREFIX . i18n("New Group");
    }
    $groupname = stripcslashes(preg_replace("/\"/", "", ($groupname)));

    $oGroup = new cApiGroup();
    $oGroup->loadGroupByGroupname($groupname);
    if ($oGroup->isLoaded()) {
        $sNotification = $notification->returnNotification("info", i18n("Groupname already exists"));
        $bError = true;
    } else {
        $oGroupColl = new cApiGroupCollection();
        //$description = stripcslashes(preg_replace("/\"/", "", ($description)));
        $oGroup = $oGroupColl->create($groupname, implode(',', $aPerms), $description);
        if (is_object($oGroup)) {
            // clean "old" values...
            $sNotification = $notification->returnNotification("info", i18n("group created"));
            $groupname   = '';
            $aPerms      = array();
            $description = '';
        } else {
            $sNotification = $notification->returnNotification("info", i18n("Group couldn't created"));
            $bError = true;
        }
    }
}

$tpl->reset();
$tpl->set('s', 'NOTIFICATION', $sNotification);

$form = '<form name="group_properties" method="post" action="'.$sess->url("main.php?").'">
             <input type="hidden" name="area" value="'.$area.'">
             <input type="hidden" name="action" value="group_create">
             <input type="hidden" name="frame" value="'.$frame.'">
             <input type="hidden" name="idlang" value="'.$lang.'">';

$tpl->set('s', 'FORM', $form);
$tpl->set('s', 'SUBMITTEXT', i18n("Save changes"));

$tpl->set('s', 'PROPERTY', i18n("Property"));
$tpl->set('s', 'VALUE', i18n("Value"));

$tpl->set('d', 'CATNAME', i18n("Group name"));
if ($action == 'group_create' && !$bError) {
    $tpl->set('d', 'CATFIELD', cApiGroup::getUnprefixedGroupName($groupname));
} else {
    $oTxtName = new cHTMLTextbox('groupname', stripcslashes(preg_replace("/\"/", "", (cApiGroup::getUnprefixedGroupName($groupname)))), 40, 32);
    $tpl->set('d', 'CATFIELD', $oTxtName->render());
}
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("Description"));
$oTxtDesc = new cHTMLTextbox('description', stripcslashes(preg_replace("/\"/", "", ($description))), 40, 255);
$tpl->set('d', 'CATFIELD', $oTxtDesc->render());
$tpl->next();

// permissions of current logged in user
$aAuthPerms = explode(',', $auth->auth['perm']);

// sysadmin perm
if (in_array('sysadmin', $aAuthPerms)) {
    $tpl->set('d', 'CATNAME', i18n("System administrator"));
    $defaultsysadmin = new cHTMLCheckbox("msysadmin", "1", "msysadmin1", in_array('sysadmin', $aPerms));
    $tpl->set('d', 'CATFIELD', $defaultsysadmin->toHTML(false));
    $tpl->next();
}

// clients admin perms
$oClientsCollection = new cApiClientCollection();
$aClients = $oClientsCollection->getAvailableClients();
$sClientCheckboxes = '';
foreach ($aClients as $idclient => $item) {
    if (in_array("admin[".$idclient."]", $aAuthPerms) || in_array('sysadmin', $aAuthPerms)) {
        $defaultadmin = new cHTMLCheckbox("madmin[".$idclient."]", $idclient, "madmin[".$idclient."]".$idclient, in_array("admin[".$idclient."]", $aPerms));
        $defaultadmin->setLabelText($item['name'] . " (".$idclient.")");
        $sClientCheckboxes .= $defaultadmin->toHTML(true);
    }
}

if ($sClientCheckboxes !== '') {
    $tpl->set('d', 'CATNAME', i18n("Administrator"));
    $tpl->set('d', 'CATFIELD', $sClientCheckboxes);
    $tpl->next();
}

// clients perms
$sClientCheckboxes = '';
foreach ($aClients as $idclient => $item) {
    if (in_array("client[".$idclient."]", $aAuthPerms) || in_array('sysadmin', $aAuthPerms) || in_array("admin[".$idclient."]", $aAuthPerms)) {
        $defaultperms = new cHTMLCheckbox("mclient[".$idclient."]", $idclient, "mclient[".$idclient."]".$idclient, in_array("client[".$idclient."]", $aPerms));
        $defaultperms->setLabelText($item['name'] . " (". $idclient . ")");
        $sClientCheckboxes .= $defaultperms->toHTML(true);
    }
}

$tpl->set('d', 'CATNAME', i18n("Access clients"));
$tpl->set('d', 'CATFIELD', $sClientCheckboxes);
$tpl->next();

// languages perms
$aClientsLanguages = getAllClientsAndLanguages();
$sClientCheckboxes = '';
foreach ($aClientsLanguages as $item) {
    if ($perm->have_perm_client("lang[".$item['idlang']."]") || $perm->have_perm_client("admin[".$item['idclient']."]")) {
        $defaultlanguages = new cHTMLCheckbox("mlang[".$item['idlang']."]", $item['idlang'], "mlang[".$item['idlang']."]".$item['idlang'], in_array("lang[".$item['idlang']."]", $aPerms));
        $defaultlanguages->setLabelText($item['langname']." (". $item['clientname'] .")");
        $sClientCheckboxes .= $defaultlanguages->toHTML(true);
    }
}

$tpl->set('d', 'CATNAME', i18n("Access languages"));
$tpl->set('d', 'CATFIELD', $sClientCheckboxes);
$tpl->next();

// Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['grouprights_create']);
