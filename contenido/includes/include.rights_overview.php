<?php
/**
 * This file contains the backend page for the user overview.
 * TODO error handling!!!
 * TODO export functions to new cApiUser object!
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

if (!($perm->have_perm_area_action($area, $action) || $perm->have_perm_area_action('user', $action))) {
    // access denied
    $notification->displayNotification("error", i18n("Permission denied"));
    return;
}

if (!isset($userid)) {
    // no user id, get out here
    return;
}

$aPerms = array();
$bError = false;
$sNotification = '';

// delete user
if ($action == 'user_delete') {
    $oUserColl = new cApiUserCollection();

    $page = new cGuiPage("rights_overview");

    // Prevent deletion of last system administrator
    $oUserColl->query();
    if ($oUserColl->count() == 1) {
        $page->displayCriticalError(i18n("You are the last system administrator of this installation. You can not delete yourself."));
        $page->render();
        exit();
    }

    $oUserColl->delete($userid);

    $oGroupMemberColl = new cApiGroupMemberCollection();
    $oGroupMemberColl->deleteByUserId($userid);

    $oRightColl = new cApiRightCollection();
    $oRightColl->deleteByUserId($userid);

    $page->displayInfo(i18n("User deleted"));
    $page->setReload();

    $page->abortRendering();
    $page->render();

    return;
}

// edit user
if ($action == 'user_edit') {
    $aPerms = buildUserOrGroupPermsFromRequest();

    // update user values
    // New Class User, update password and other values
    $realname = stripslashes($realname);

    $ocApiUser = new cApiUser($userid);
    $ocApiUser->setRealName($realname);
    $ocApiUser->setMail($email);
    $ocApiUser->setTelNumber($telephone);
    $ocApiUser->setAddressData($address_street, $address_city, $address_zip, $address_country);
    $ocApiUser->setUseWysi($wysi);
    $ocApiUser->setValidDateFrom($valid_from);
    $ocApiUser->setValidDateTo($valid_to);
    $ocApiUser->setPerms($aPerms);

    // is a password set?
    $bPassOk = false;
    if (strlen($password) > 0) {
        // yes --> check it...
        if (strcmp($password, $passwordagain) == 0) {
            // set password....
            $iPasswordSaveResult = $ocApiUser->setPassword($password);

            // fine, passwords are the same, but is the password valid?
            if ($iPasswordSaveResult != cApiUser::PASS_OK) {
                // oh oh, password is NOT valid. check it...
                $sPassError = cApiUser::getErrorString($iPasswordSaveResult);
                $sNotification = $notification->returnNotification("error", $sPassError);
                $bError = true;
            } else {
                $bPassOk = true;
            }
        } else {
            $sNotification = $notification->returnNotification("error", i18n("Passwords don't match"));
            $bError = true;
        }
    } else if (strlen($password) === 0 && strlen($passwordagain) === 0) {
        // it is okay if the password has not been changed - then the old
        // password is kept.
        $bPassOk = true;
    }

    $cleanRealname = preg_replace('/["\'\/\§$%&]/i', '', $realname);
    if ($realname !== $cleanRealname) {
        $sNotification = $notification->returnNotification("warning", i18n("Special characters in username and name are not allowed."));
        $bError = true;
    }

    if (!$bError && (strlen($password) == 0 || $bPassOk == true)) {
        if ($ocApiUser->store()) {
            $sNotification = $notification->returnNotification("info", i18n("Changes saved"));
            $bError = true;
        } else {
            $sNotification = $notification->returnNotification("error", i18n("An error occured while saving user info."));
            $bError = true;
        }
    }
}

$oUser = new cApiUser($userid);

// delete user property
if (is_string($del_userprop_type) && is_string($del_userprop_name)) {
    $oUser->deleteUserProperty($del_userprop_type, $del_userprop_name);
}

// edit user property
if (is_string($userprop_type) && is_string($userprop_name) && is_string($userprop_value) && !empty($userprop_type) && !empty($userprop_name)) {
    $oUser->setUserProperty($userprop_type, $userprop_name, $userprop_value);
}

if (count($aPerms) == 0 || $action == '' || !isset($action)) {
    $aPerms = explode(',', $oUser->getField('perms'));
}

$tpl->reset();
$tpl->set('s', 'NOTIFICATION', $sNotification);

$tpl->set("s", "AREA", $area);
$tpl->set("s", "FRAME", $frame);
$tpl->set("s", "LANG", $lang);
$tpl->set("s", "USERID", $userid);
$tpl->set('s', 'GET_USERID', $userid);
$tpl->set('s', 'SUBMITTEXT', i18n("Save changes"));
$tpl->set('s', 'CANCELTEXT', i18n("Discard changes"));
$tpl->set('s', 'CANCELLINK', $sess->url("main.php?area=$area&frame=4&userid=$userid"));
$tpl->set('s', 'PROPERTY', i18n("Property"));
$tpl->set('s', 'VALUE', i18n("Value"));

$tpl->set('d', 'ROW_ID', "username");
$tpl->set('d', 'CATNAME', i18n("Username"));
$tpl->set('d', 'CATFIELD', $oUser->getField('username') . '<img align="top" src="images/spacer.gif" height="20">');
$tpl->next();

$tpl->set('d', 'ROW_ID', "name");
$tpl->set('d', 'CATNAME', i18n("Name"));
$oTxtName = new cHTMLTextbox("realname", htmlspecialchars($oUser->getField('realname')), 40, 255);
$tpl->set('d', 'CATFIELD', $oTxtName->render());
$tpl->next();

// @since 2006-07-04 Display password fields only if not authenticated via
// LDAP/AD
if ($msysadmin || $oUser->getField('password') != 'active_directory_auth') {
    $tpl->set('d', 'ROW_ID', "password");
    $tpl->set('d', 'CATNAME', i18n("New password"));
    $oTxtPass = new cHTMLPasswordbox('password', '', 40, 255);
    $tpl->set('d', 'CATFIELD', $oTxtPass->render());
    $tpl->next();

    $tpl->set('d', 'ROW_ID', "confirm_password");
    $tpl->set('d', 'CATNAME', i18n("Confirm new password"));
    $oTxtWord = new cHTMLPasswordbox('passwordagain', '', 40, 255);
    $tpl->set('d', 'CATFIELD', $oTxtWord->render());
    $tpl->next();
}

$tpl->set('d', 'ROW_ID', "email");
$tpl->set('d', 'CATNAME', i18n("E-Mail"));
$oTxtEmail = new cHTMLTextbox('email', $oUser->getField('email'), 40, 255);
$tpl->set('d', 'CATFIELD', $oTxtEmail->render());
$tpl->next();

$tpl->set('d', 'ROW_ID', "phone_number");
$tpl->set('d', 'CATNAME', i18n("Phone number"));
$oTxtTel = new cHTMLTextbox('telephone', $oUser->getField('telephone'), 40, 255);
$tpl->set('d', 'CATFIELD', $oTxtTel->render());
$tpl->next();

$tpl->set('d', 'ROW_ID', "street");
$tpl->set('d', 'CATNAME', i18n("Street"));
$oTxtStreet = new cHTMLTextbox('address_street', $oUser->getField('address_street'), 40, 255);
$tpl->set('d', 'CATFIELD', $oTxtStreet->render());
$tpl->next();

$tpl->set('d', 'ROW_ID', "zip_code");
$tpl->set('d', 'CATNAME', i18n("ZIP code"));
$oTxtZip = new cHTMLTextbox('address_zip', $oUser->getField('address_zip'), 10, 10);
$tpl->set('d', 'CATFIELD', $oTxtZip->render());
$tpl->next();

$tpl->set('d', 'ROW_ID', "city");
$tpl->set('d', 'CATNAME', i18n("City"));
$oTxtCity = new cHTMLTextbox('address_city', $oUser->getField('address_city'), 40, 255);
$tpl->set('d', 'CATFIELD', $oTxtCity->render());
$tpl->next();

$tpl->set('d', 'ROW_ID', "country");
$tpl->set('d', 'CATNAME', i18n("Country"));
$oTxtLand = new cHTMLTextbox('address_country', $oUser->getField('address_country'), 40, 255);
$tpl->set('d', 'CATFIELD', $oTxtLand->render());
$tpl->next();

$tpl->set('s', 'PATH_TO_CALENDER_PIC', cRegistry::getBackendUrl() . $cfg['path']['images'] . 'calendar.gif');

if (($lang_short = substr(strtolower($belang), 0, 2)) != "en") {
    $langscripts = '<script type="text/javascript" src="scripts/jquery/plugins/timepicker-' . $lang_short . '.js"></script>
    <script type="text/javascript" src="scripts/jquery/plugins/datepicker-' . $lang_short . '.js"></script>';
    $tpl->set('s', 'CAL_LANG', $langscripts);
} else {
    $tpl->set('s', 'CAL_LANG', '');
}

// permissions of current logged in user
$aAuthPerms = explode(',', $auth->auth['perm']);

// sysadmin perm
if (in_array('sysadmin', $aAuthPerms)) {
    $tpl->set('d', 'ROW_ID', "rights_sysadmin");
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

if ($sClientCheckboxes !== '') {
    $tpl->set('d', 'ROW_ID', "rights_admin");
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

if ($sClientCheckboxes !== '') {
    $tpl->set('d', 'ROW_ID', "rights_clients");
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

if ($sClientCheckboxes != '') {
    $tpl->set('d', 'ROW_ID', "rights_languages");
    $tpl->set('d', 'CATNAME', i18n("Access languages"));
    $tpl->set('d', 'CATFIELD', $sClientCheckboxes);
    $tpl->next();
}

// user properties
$aProperties = $oUser->getUserProperties(false);
$sPropRows = '';
foreach ($aProperties as $entry) {
    // ommit system props
    if ('system' === $entry['type']) {
        continue;
    }
    $type = $entry['type'];
    $name = $entry['name'];
    $value = $entry['value'];
    $href = $sess->url("main.php?area=$area&frame=4&userid=$userid&del_userprop_type=$type&del_userprop_name=$name");
    $sPropRows .= '
        <tr>
            <td>' . $type . '</td>
            <td>' . $name . '</td>
            <td>' . $value . '</td>
            <td><a href="' . $href . '"><img src="images/delete.gif" border="0" alt="' . i18n('Delete') . '" title="' . i18n('Delete') . '"></a></td>
        </tr>';
}
$table = '
    <table class="generic" width="100%" cellspacing="0" cellpadding="2">
    <tr>
        <th>' . i18n("Area/Type") . '</th>
        <th>' . i18n("Property") . '</th>
        <th>' . i18n("Value") . '</th>
        <th>' . i18n("Delete") . '</th>
    </tr>
    ' . $sPropRows . '
    <tr>
        <td><input class="text_medium" type="text" size="16" maxlen="32" name="userprop_type"></td>
        <td><input class="text_medium" type="text" size="16" maxlen="32" name="userprop_name"></td>
        <td><input class="text_medium" type="text" size="32" name="userprop_value"></td>
        <td>&nbsp;</td>
        </tr>
    </table>';

$tpl->set('d', 'ROW_ID', "user_defined_properties");
$tpl->set('d', 'CATNAME', i18n("User-defined properties"));
$tpl->set('d', 'CATFIELD', $table);
$tpl->next();

// wysiwyg
$tpl->set('d', 'ROW_ID', "use_wysiwyg");
$tpl->set('d', 'CATNAME', i18n("Use WYSIWYG-Editor"));
$oCheckbox = new cHTMLCheckbox('wysi', '1', 'wysi1', $oUser->getField('wysi'));
$tpl->set('d', 'CATFIELD', $oCheckbox->toHTML(false));
$tpl->next();

// account active data (from-to)
$sCurrentValueFrom = str_replace('00:00:00', '', $oUser->getField('valid_from'));
$sCurrentValueFrom = trim(str_replace('0000-00-00', '', $sCurrentValueFrom));

$sInputValidFrom = '<input type="text" id="valid_from" name="valid_from" value="' . $sCurrentValueFrom . '">';

$tpl->set('d', 'ROW_ID', "tr_valid_from");
$tpl->set('d', 'CATNAME', i18n("Valid from"));
$tpl->set('d', 'CATFIELD', $sInputValidFrom);
$tpl->next();

$sCurrentValueTo = str_replace('00:00:00', '', $oUser->getField('valid_to'));
$sCurrentValueTo = trim(str_replace('0000-00-00', '', $sCurrentValueTo));

$sInputValidTo = '<input type="text" id="valid_to" name="valid_to" value="' . $sCurrentValueTo . '">';

$tpl->set('d', 'ROW_ID', "tr_valid_to");
$tpl->set('d', 'CATNAME', i18n("Valid to"));
$tpl->set('d', 'CATFIELD', $sInputValidTo);
$tpl->next();

// account active or not
if ($sCurrentValueFrom == '') {
    $sCurrentValueFrom = '0000-00-00';
}

if (($sCurrentValueTo == '') || ($sCurrentValueTo == '0000-00-00')) {
    $sCurrentValueTo = '9999-99-99';
}

$sCurrentDate = date('Y-m-d');

if (($sCurrentValueFrom > $sCurrentDate) || ($sCurrentValueTo < $sCurrentDate)) {
    $sAccountState = i18n("This account is currently inactive.");
    $sAccountColor = 'red';
} else {
    $sAccountState = i18n("This account is currently active.");
    $sAccountColor = 'green';
}

$tpl->set('d', 'ROW_ID', "active");
$tpl->set('d', 'CATNAME', '&nbsp;');
$tpl->set('d', 'CATFIELD', '<span style="color:' . $sAccountColor . ';">' . $sAccountState . '</span>');
$tpl->next();

// Show backend user's group memberships
$oUser2 = new cApiUser();
$aGroups = $oUser2->getGroupNamesByUserID($userid);
if (count($aGroups) > 0) {
    asort($aGroups);
    $sGroups = implode("<br>", $aGroups);
} else {
    $sGroups = i18n("none");
}

$tpl->set('d', 'ROW_ID', "group_membership");
$tpl->set('d', 'CATNAME', i18n("Group membership"));
$tpl->set('d', 'CATFIELD', $sGroups);
$tpl->next();

// Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['rights_overview']);
