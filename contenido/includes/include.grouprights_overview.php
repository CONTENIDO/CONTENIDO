<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO Groups Overview Page
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.1.4
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created  2003-05-30
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2009-11-06, Murat Purc, replaced deprecated functions (PHP 5.3 ready)
 *   modified 2011-02-07, Murat Purc, Cleanup, optimization and formatting
 *
 *   $Id$:
 * }}
 *
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
$bError        = false;
$sNotification = '';
$aPerms        = array();

// edit group
if (($action == 'group_edit')) {
    $aPerms = buildUserOrGroupPermsFromRequest();
    $oGroup->setField('description', $description);
    $oGroup->setField('perms', implode(',', $aPerms));

    if ($oGroup->store()) {
        $sNotification = $notification->returnNotification("info", i18n("Changes saved"));
    } else {
        $sNotification = $notification->returnNotification("warn", i18n("Changes couldn't saved"));
        $bError = true;
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
$tpl->set('s','NOTIFICATION', $sNotification);

$form = '<form name="group_properties" method="post" action="'.$sess->url("main.php?").'">
             '.$sess->hidden_session(true).'
             <input type="hidden" name="area" value="'.$area.'">
             <input type="hidden" name="action" value="group_edit">
             <input type="hidden" name="frame" value="'.$frame.'">
             <input type="hidden" name="groupid" value="'.$groupid.'">
             <input type="hidden" name="idlang" value="'.$lang.'">';

$tpl->set('s', 'FORM', $form);
$tpl->set('s', 'GET_GROUPID', $groupid);

$tpl->set('s', 'BORDERCOLOR', $cfg["color"]["table_border"]);
$tpl->set('s', 'BGCOLOR', $cfg["color"]["table_dark"]);
$tpl->set('s', 'SUBMITTEXT', i18n("Save changes"));
$tpl->set('s', 'CANCELTEXT', i18n("Discard changes"));
$tpl->set('s', 'CANCELLINK', $sess->url("main.php?area=$area&frame=4&groupid=$groupid"));

$tpl->set('d', 'CATNAME', i18n("Property"));
$tpl->set('d', 'BGCOLOR',  $cfg["color"]["table_header"]);
$tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
$tpl->set('d', 'CATFIELD', i18n("Value"));
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("Groupname"));
$tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
$tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
$tpl->set('d', 'CATFIELD', stripslashes($oGroup->getGroupName()));
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("Description"));
$tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
$tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
$tpl->set('d', 'CATFIELD', formGenerateField('text', 'description', htmlentities(stripslashes($oGroup->getField('description')), ENT_QUOTES), 40, 255));
$tpl->next();

// permissions of current logged in user
$aAuthPerms = explode(',', $auth->auth['perm']);

// sysadmin perm
if (in_array('sysadmin', $aAuthPerms)) {
    $tpl->set('d', 'CATNAME', i18n("System administrator"));
    $tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
    $tpl->set('d', 'CATFIELD', formGenerateCheckbox('msysadmin', '1', in_array('sysadmin', $aPerms)));
    $tpl->next();
}

// clients admin perms
$oClientsCollection = new cApiClientCollection();
$aClients = $oClientsCollection->getAvailableClients();
$sClientCheckboxes = '';
foreach ($aClients as $idclient => $item) {
    if (in_array("admin[".$idclient."]", $aAuthPerms) || in_array('sysadmin', $aAuthPerms)) {
        $sClientCheckboxes .= formGenerateCheckbox("madmin[".$idclient."]", $idclient, in_array("admin[".$idclient."]", $aPerms), $item['name']." (".$idclient.")")."<br>";
    }
}

if ($sClientCheckboxes !== '' && !in_array('sysadmin', $aPerms)) {
    $tpl->set('d', 'CATNAME', i18n("Administrator"));
    $tpl->set('d', 'BORDERCOLOR',  $cfg["color"]["table_border"]);
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
    $tpl->set('d', 'CATFIELD', $sClientCheckboxes);
    $tpl->next();
}

// clients perms
$sClientCheckboxes = '';
foreach ($aClients as $idclient => $item) {
    if ((in_array("client[".$idclient."]", $aAuthPerms) || in_array('sysadmin', $aAuthPerms) || in_array("admin[".$idclient."]", $aAuthPerms)) && !in_array("admin[".$idclient."]", $aPerms)) {
        $sClientCheckboxes .= formGenerateCheckbox("mclient[".$idclient."]", $idclient, in_array("client[".$idclient."]", $aPerms), $item['name']." (". $idclient . ")")."<br>";
    }
}

if ($sClientCheckboxes != '' && !in_array('sysadmin', $aPerms)) {
    $tpl->set('d', 'CATNAME', i18n("Access clients"));
    $tpl->set('d', 'BORDERCOLOR',  $cfg["color"]["table_border"]);
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
    $tpl->set('d', 'CATFIELD', $sClientCheckboxes);
    $tpl->next();
}

// languages perms
$aClientsLanguages = getAllClientsAndLanguages();
$sClientCheckboxes = '';
foreach ($aClientsLanguages as $item) {
    if (($perm->have_perm_client("lang[".$item['idlang']."]") || $perm->have_perm_client("admin[".$item['idclient']."]")) && !in_array("admin[".$item['idclient']."]", $aPerms)) {
        $sClientCheckboxes .= formGenerateCheckbox("mlang[".$item['idlang']."]", $item['idlang'], in_array("lang[".$item['idlang']."]", $aPerms), $item['langname']." (". $item['clientname'] .")")."<br>";
    }
}

if ($sClientCheckboxes != '' && !in_array('sysadmin', $aPerms)) {
    $tpl->set('d', 'CATNAME', i18n("Access languages"));
    $tpl->set('d', 'BORDERCOLOR',  $cfg["color"]["table_border"]);
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
    $tpl->set('d', 'CATFIELD', $sClientCheckboxes);
    $tpl->next();
}

// group properties
$aProperties = $oGroup->getGroupProperties();
$sPropRows   = '';
foreach ($aProperties as $propertyId => $prop) {
    $type  = $prop['type'];
    $name  = $prop['name'];
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
    <table width="100%" cellspacing="0" cellpadding="2" style="border:1px solid '.$cfg["color"]["table_border"].';">
    <tr style="background-color:'.$cfg["color"]["table_header"].'" class="text_medium">
        <td>'.i18n("Area/Type").'</td>
        <td>'.i18n("Property").'</td>
        <td>'.i18n("Value").'</td>
        <td>&nbsp;</td>
    </tr>
    ' . $sPropRows . '
    <tr class="text_medium">
        <td><input class="text_medium"  type="text" size="16" maxlen="32" name="groupprop_type"></td>
        <td><input class="text_medium" type="text" size="16" maxlen="32" name="groupprop_name"></td>
        <td><input class="text_medium" type="text" size="32" name="groupprop_value"></td>
        <td>&nbsp;</td>
    </tr>
    </table>';

$tpl->set('d', 'CATNAME', i18n("User-defined properties"));
$tpl->set('d', 'BORDERCOLOR',  $cfg["color"]["table_border"]);
$tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
$tpl->set('d', 'CATFIELD', $table);
$tpl->next();

// Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['grouprights_overview']);

?>