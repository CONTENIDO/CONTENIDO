<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO Groups Overview Page
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.1.4
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude('includes', 'functions.rights.php');


if (!$perm->have_perm_area_action($area, $action)) {
    // access denied
    $notification->displayNotification('error', i18n('Permission denied'));
    return;
}

if (!isset($groupid)) {
    // no group id, get out here
    return;
}

// create group instance
$oGroup = new cApiGroup($groupid);
$bError = false;
$sNotification = '';
$aPerms = array();

// edit group
if (($action == 'group_edit')) {
    $bError = false;

    if (isset($mlang) && count($mlang > 0) && (!isset($mclient))) {
        $sNotification = $notification->returnNotification("error", i18n("If you want to assign a language to a group you need to give it access to the client too."));
        $bError = true;
    } else {
        foreach ($mlang as $ilang) {
            if (!checkLangInClients($mclient, $ilang, null, null)) {
                $sNotification = $notification->returnNotification("error", i18n("If you want to assign a language to a group you need to give it access to the client too."));
                $bError = true;
                break;
            }
        }
    }

    if (!$bError) {
        $aPerms = buildUserOrGroupPermsFromRequest();
        $oGroup->setField('description', $description);
        $oGroup->setField('perms', implode(',', $aPerms));

        if ($oGroup->store()) {
            $sNotification = $notification->returnNotification("info", i18n("Changes saved"));
        } else {
            $sNotification = $notification->returnNotification("error", i18n("Changes couldn't be saved"));
            $bError = true;
        }
    }
}

// delete group property
if (is_string($del_groupprop_type) && is_string($del_groupprop_name)) {
    $oGroup->deleteGroupProperty($del_groupprop_type, $del_groupprop_name);
}

// add group property
if (is_string($groupprop_type) && is_string($groupprop_name) && is_string($groupprop_value)
        && !empty($groupprop_type) && !empty($groupprop_name)) {
    $oGroup->setGroupProperty($groupprop_type, $groupprop_name, $groupprop_value);
}


$aPerms = explode(',', $oGroup->getField('perms'));

$tpl->reset();
$tpl->set('s', 'NOTIFICATION', $sNotification);

$form = '<form name="group_properties" method="post" action="' . $sess->url("main.php?") . '">
             <input type="hidden" name="area" value="' . $area . '">
             <input type="hidden" name="action" value="group_edit">
             <input type="hidden" name="frame" value="' . $frame . '">
             <input type="hidden" name="groupid" value="' . $groupid . '">
             <input type="hidden" name="idlang" value="' . $lang . '">';

$tpl->set('s', 'FORM', $form);
$tpl->set('s', 'GET_GROUPID', $groupid);

$tpl->set('s', 'SUBMITTEXT', i18n("Save changes"));
$tpl->set('s', 'CANCELTEXT', i18n("Discard changes"));
$tpl->set('s', 'CANCELLINK', $sess->url("main.php?area=$area&frame=4&groupid=$groupid"));

$tpl->set('s', 'PROPERTY', i18n("Property"));
$tpl->set('s', 'VALUE', i18n("Value"));

$tpl->set('d', 'CATNAME', i18n("Groupname"));
$tpl->set('d', 'CATFIELD', stripslashes($oGroup->getGroupName(true)));
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("Description"));
$oTxtDesc = new cHTMLTextbox('description', conHtmlentities(stripslashes($oGroup->getField('description')), ENT_QUOTES), 40, 255);
$tpl->set('d', 'CATFIELD', $oTxtDesc->render());
$tpl->next();

// permissions of current logged in user
$aAuthPerms = explode(',', $auth->auth['perm']);

// sysadmin perm
if (in_array('sysadmin', $aAuthPerms)) {
    $tpl->set('d', 'CATNAME', i18n("System administrator"));
    $oCheckbox = new cHTMLCheckbox('msysadmin', '1', 'msysadmin1', in_array('sysadmin', $aPerms));
    $tpl->set('d', 'CATFIELD', $oCheckbox->toHTML(false));
    $tpl->next();
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
    $tpl->set('d', 'CATNAME', i18n("Administrator"));
    $tpl->set('d', 'CATFIELD', $sClientCheckboxes);
    $tpl->next();
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
    $tpl->set('d', 'CATNAME', i18n("Access clients"));
    $tpl->set('d', 'CATFIELD', $sClientCheckboxes);
    $tpl->next();
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
    $tpl->set('d', 'CATNAME', i18n("Access languages"));
    $tpl->set('d', 'CATFIELD', $sClientCheckboxes);
    $tpl->next();
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
            <a href="' . $sess->url("main.php?area=$area&frame=4&groupid=$groupid&del_groupprop_type=$type&del_groupprop_name=$name") . '"><img src="images/delete.gif" border="0" alt="' . i18n("Delete") . '" title="' . i18n("Delete") . '"></a>
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

$tpl->set('d', 'CATNAME', i18n("User-defined properties"));
$tpl->set('d', 'CATFIELD', $table);
$tpl->next();

// Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['grouprights_overview']);
