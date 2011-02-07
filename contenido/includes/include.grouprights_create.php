<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Contenido Create Group Function
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend includes
 * @version    1.7.2
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
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

if(!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude('includes', 'functions.rights.php');


if (!$perm->have_perm_area_action($area, $action)) {
    // access denied
    $notification->displayNotification('error', i18n('Permission denied'));
    return;
}


// create group instance
$oGroup = new Group();
$bError        = false;
$sNotification = '';
$aPerms        = array();

if ($action == 'group_create') {
    $aPerms = buildUserOrGroupPermsFromRequest();

    if ($groupname == '') {
        $groupname = 'grp_' . i18n("New Group");
    }

    if (substr($groupname, 0, 4) != 'grp_') {
        $groupname = 'grp_' . $groupname;
    }
    $newgroupid = md5($groupname);

    $oGroup->setField('groupname', Contenido_Security::escapeDB($groupname, $db));
    $oGroup->setField('perms', Contenido_Security::escapeDB(implode(',', $aPerms), $db));
    $oGroup->setField('description', Contenido_Security::escapeDB($description, $db));
    $oGroup->setField('group_id', Contenido_Security::escapeDB($newgroupid, $db));
    if ($oGroup->insert()) {
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

$tpl->reset();
$tpl->set('s','NOTIFICATION', $sNotification);

$form = '<form name="group_properties" method="post" action="'.$sess->url("main.php?").'">
             '.$sess->hidden_session(true).'
             <input type="hidden" name="area" value="'.$area.'">
             <input type="hidden" name="action" value="group_create">
             <input type="hidden" name="frame" value="'.$frame.'">
             <input type="hidden" name="idlang" value="'.$lang.'">';

$tpl->set('s', 'FORM', $form);
$tpl->set('s', 'BORDERCOLOR', $cfg["color"]["table_border"]);
$tpl->set('s', 'BGCOLOR', $cfg["color"]["table_dark"]);
$tpl->set('s', 'SUBMITTEXT', i18n("Save changes"));

$tpl->set('d', 'CATNAME', i18n("Property"));
$tpl->set('d', 'BGCOLOR',  $cfg["color"]["table_header"]);
$tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
$tpl->set('d', 'CATFIELD', i18n("Value"));
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("Group name"));
$tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
$tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
if ($action == 'group_create' && !$bError) {
    $tpl->set('d', 'CATFIELD', substr($groupname, 4));
} else {
    $tpl->set('d', 'CATFIELD', formGenerateField('text', 'groupname', stripslashes(substr($groupname, 4)), 40, 32));
}
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("Description"));
$tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
$tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
$tpl->set('d', 'CATFIELD', formGenerateField('text', 'description', stripslashes($description), 40, 255));
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
        $sClientCheckboxes .= formGenerateCheckbox("madmin[".$idclient."]", $idclient, in_array("admin[".$idclient."]", $aPerms), $item['name'] . " (".$idclient.")")."<br>";
    }
}

if ($sClientCheckboxes !== '') {
    $tpl->set('d', 'CATNAME', i18n("Administrator"));
    $tpl->set('d', 'BORDERCOLOR',  $cfg["color"]["table_border"]);
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
    $tpl->set('d', 'CATFIELD', $sClientCheckboxes);
    $tpl->next();
}

// clients perms
$sClientCheckboxes = '';
foreach ($aClients as $idclient => $item) {
    if (in_array("client[".$idclient."]", $aAuthPerms) || in_array('sysadmin', $aAuthPerms) || in_array("admin[".$idclient."]", $aAuthPerms)) {
        $sClientCheckboxes .= formGenerateCheckbox("mclient[".$idclient."]", $idclient, in_array("client[".$idclient."]", $aPerms), $item['name'] . " (". $idclient . ")")."<br>";
    }
}

$tpl->set('d', 'CATNAME', i18n("Access clients"));
$tpl->set('d', 'BORDERCOLOR',  $cfg["color"]["table_border"]);
$tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
$tpl->set('d', 'CATFIELD', $sClientCheckboxes);
$tpl->next();


// languages perms
$aClientsLanguages = getAllClientsAndLanguages();
$sClientCheckboxes = '';
foreach ($aClientsLanguages as $item) {
    if ($perm->have_perm_client("lang[".$item['idlang']."]") || $perm->have_perm_client("admin[".$item['idclient']."]")) {
        $sClientCheckboxes .= formGenerateCheckbox("mlang[".$item['idlang']."]", $item['idlang'], in_array("lang[".$item['idlang']."]", $aPerms), $item['langname']." (". $item['clientname'] .")")."<br>";
    }
}

$tpl->set('d', 'CATNAME', i18n("Access languages"));
$tpl->set('d', 'BORDERCOLOR',  $cfg["color"]["table_border"]);
$tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
$tpl->set('d', 'CATFIELD', $sClientCheckboxes);
$tpl->next();

# Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['grouprights_create']);

?>