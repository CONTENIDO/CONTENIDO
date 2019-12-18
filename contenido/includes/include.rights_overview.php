<?php

/**
 * This file contains the backend page for the user overview.
 * TODO error handling!!!
 * TODO export functions to new cApiUser object!
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

global $mclient, $msysadmin, $mlang;

if (!($perm->have_perm_area_action($area, $action) || $perm->have_perm_area_action('user', $action))) {
    // access denied
    $notification->displayNotification("error", i18n("Permission denied"));
    return;
}

// @TODO Find a general solution for this!
if (defined('CON_STRIPSLASHES')) {
    $request = cString::stripSlashes($_REQUEST);
} else {
    $request = $_REQUEST;
}

if (!isset($request['userid'])) {
    // no user id, get out here
    return;
}

$aPerms = [];
$bError = false;
$sNotification = '';
$belang = cRegistry::getBackendLanguage();

// Action delete user
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

    $oUserColl->delete($request['userid']);

    $oGroupMemberColl = new cApiGroupMemberCollection();
    $oGroupMemberColl->deleteByUserId($request['userid']);

    $oRightColl = new cApiRightCollection();
    $oRightColl->deleteByUserId($request['userid']);

    $page->displayOk(i18n("User deleted"));
    $page->setReload();

    $page->abortRendering();
    $page->render();

    return;
}

// Action edit user
if ($action == 'user_edit') {

    if (is_array($mclient) && count($mclient) > 0) {

        // Prevent setting the permissions for a client without a language of that client
        foreach ($mclient as $selectedClient) {

            // Get all available languages for selected client
            $clientLanguageCollection = new cApiClientLanguageCollection();
            $availableLanguages = $clientLanguageCollection->getLanguagesByClient($selectedClient);

            if (!is_array($mlang) || count($mlang) == 0) {
                // User has no selected language
                $sNotification = $notification->returnNotification("warning", i18n("Please select a language for your selected client."));
                $bError = true;
            } else if ($availableLanguages == false) {
                // Client has no assigned language(s)
                $sNotification = $notification->returnNotification("warning", i18n("You can only assign users to a client with languages."));
                $bError = true;
            } else {

                // Client has one or more assigned language(s)
                foreach ($mlang as $selectedLanguage) {

                    if (!$clientLanguageCollection->hasLanguageInClients($selectedLanguage, $mclient)) {
                        // Selected language are not assigned to selected client
                        $sNotification = $notification->returnNotification("warning", i18n("You have to select a client with a language of that client."));
                        $bError = true;
                    }
                }

            }

        }

    }

    $aPerms = cRights::buildUserOrGroupPermsFromRequest();

    // update user values
    // New Class User, update password and other values
    $ocApiUser = new cApiUser($request['userid']);
    $ocApiUser->setRealName($request['realname']);
    $ocApiUser->setMail($request['email']);
    $ocApiUser->setTelNumber($request['telephone']);
    $ocApiUser->setAddressData($request['address_street'], $request['address_city'], $request['address_zip'], $request['address_country']);
    $ocApiUser->setUseWysi($request['wysi']);
    $ocApiUser->setValidDateFrom($request['valid_from']);
    $ocApiUser->setValidDateTo($request['valid_to']);
    $ocApiUser->setPerms($aPerms);

    // is a password set?
    $password = $request['password'];
    $passwordagain = $request['passwordagain'];

    // add slashes for compatiblity with old password hashes that included
    // magic quotes before the hashes where build
    $password = addslashes($password);
    $passwordagain = addslashes($passwordagain);

    $bPassOk = false;
    if (cString::getStringLength($password) > 0) {
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
    } else if (cString::getStringLength($password) === 0 && cString::getStringLength($passwordagain) === 0) {
        // it is okay if the password has not been changed - then the old
        // password is kept.
        $bPassOk = true;
    }

    $cleanRealname = preg_replace('/["\'\/\§$%&]/i', '', $request['realname']);
    if ($request['realname'] !== $cleanRealname) {
        $sNotification = $notification->returnNotification("warning", i18n("Special characters in username and name are not allowed."));
        $bError = true;
    }

    if (!$bError && (cString::getStringLength($password) == 0 || $bPassOk == true)) {
        if ($ocApiUser->store()) {
            $sNotification = $notification->returnNotification("ok", i18n("Changes saved"));
            $bError = true;
        } else {
            $sNotification = $notification->returnNotification("error", i18n("An error occured while saving user info."));
            $bError = true;
        }
    }
}

$oUser = new cApiUser($request['userid']);

// Action delete user property
if (!empty($request['del_userprop_type']) && !empty($request['del_userprop_name'])) {
    $oUser->deleteUserProperty($request['del_userprop_type'], $request['del_userprop_name']);
}

// Action edit user property
// @TODO  Add full support for editing values or remove the lines below
if (!empty($request['userprop_type']) && !empty($request['userprop_name'])) {
    $oUser->setUserProperty($request['userprop_type'], $request['userprop_name'], $request['userprop_value']);
}

if (count($aPerms) == 0 || $action == '' || !isset($action)) {
    $aPerms = explode(',', $oUser->getField('perms'));
}

$tpl->reset();
$tpl->set('s', 'NOTIFICATION', $sNotification);
$tpl->set('s', 'AREA', $area);
$tpl->set('s', 'FRAME', $frame);
$tpl->set('s', 'LANG', $lang);
$tpl->set('s', 'USERID', $request['userid']);
$tpl->set('s', 'GET_USERID', $request['userid']);
$tpl->set('s', 'SUBMITTEXT', i18n("Save changes"));
$tpl->set('s', 'CANCELTEXT', i18n("Discard changes"));
$tpl->set('s', 'CANCELLINK', $sess->url("main.php?area=$area&frame=4&userid={$request['userid']}"));
$tpl->set('s', 'PROPERTY', i18n("Property"));
$tpl->set('s', 'VALUE', i18n("Value"));

$tpl->set('d', 'ROW_ID', "username");
$tpl->set('d', 'CATNAME', i18n("Username"));
$tpl->set('d', 'CATFIELD', conHtmlSpecialChars($oUser->getField('username')) . '<img align="top" alt="" src="images/spacer.gif" height="20">');
$tpl->next();

$tpl->set('d', 'ROW_ID', "name");
$tpl->set('d', 'CATNAME', i18n("Name"));
$oTxtName = new cHTMLTextbox("realname", conHtmlSpecialChars($oUser->getField('realname')), 40, 255);
$tpl->set('d', 'CATFIELD', $oTxtName->render());
$tpl->next();

// @since 2006-07-04 Display password fields only if not authenticated via
// LDAP/AD
if ($msysadmin || $oUser->getField('password') != 'active_directory_auth') {
    $tpl->set('d', 'ROW_ID', "password");
    $tpl->set('d', 'CATNAME', i18n("New password"));
    $oTxtPass = new cHTMLPasswordbox('password', '', 40, 255);
    $oTxtPass->setAutofill(false);
    $oTxtPass->setAttribute('autocomplete', 'off');
    $tpl->set('d', 'CATFIELD', $oTxtPass->render());
    $tpl->next();

    $tpl->set('d', 'ROW_ID', "confirm_password");
    $tpl->set('d', 'CATNAME', i18n("Confirm new password"));
    $oTxtWord = new cHTMLPasswordbox('passwordagain', '', 40, 255);
    $oTxtWord->setAutofill(false);
    $oTxtWord->setAttribute('autocomplete', 'off');
    $tpl->set('d', 'CATFIELD', $oTxtWord->render());
    $tpl->next();
}

$tpl->set('d', 'ROW_ID', "email");
$tpl->set('d', 'CATNAME', i18n("E-Mail"));
$oTxtEmail = new cHTMLTextbox('email', conHtmlSpecialChars($oUser->getField('email')), 40, 255);
$tpl->set('d', 'CATFIELD', $oTxtEmail->render());
$tpl->next();

$tpl->set('d', 'ROW_ID', "phone_number");
$tpl->set('d', 'CATNAME', i18n("Phone number"));
$oTxtTel = new cHTMLTextbox('telephone', conHtmlSpecialChars($oUser->getField('telephone')), 40, 255);
$tpl->set('d', 'CATFIELD', $oTxtTel->render());
$tpl->next();

$tpl->set('d', 'ROW_ID', "street");
$tpl->set('d', 'CATNAME', i18n("Street"));
$oTxtStreet = new cHTMLTextbox('address_street', conHtmlSpecialChars($oUser->getField('address_street')), 40, 255);
$tpl->set('d', 'CATFIELD', $oTxtStreet->render());
$tpl->next();

$tpl->set('d', 'ROW_ID', "zip_code");
$tpl->set('d', 'CATNAME', i18n("ZIP code"));
$oTxtZip = new cHTMLTextbox('address_zip', conHtmlSpecialChars($oUser->getField('address_zip')), 10, 10);
$tpl->set('d', 'CATFIELD', $oTxtZip->render());
$tpl->next();

$tpl->set('d', 'ROW_ID', "city");
$tpl->set('d', 'CATNAME', i18n("City"));
$oTxtCity = new cHTMLTextbox('address_city', conHtmlSpecialChars($oUser->getField('address_city')), 40, 255);
$tpl->set('d', 'CATFIELD', $oTxtCity->render());
$tpl->next();

$tpl->set('d', 'ROW_ID', "country");
$tpl->set('d', 'CATNAME', i18n("Country"));
$oTxtLand = new cHTMLTextbox('address_country', conHtmlSpecialChars($oUser->getField('address_country')), 40, 255);
$tpl->set('d', 'CATFIELD', $oTxtLand->render());
$tpl->next();

$tpl->set('s', 'PATH_TO_CALENDER_PIC', cRegistry::getBackendUrl() . $cfg['path']['images'] . 'calendar.gif');

if (($lang_short = cString::getPartOfString(cString::toLowerCase($belang), 0, 2)) != "en") {
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
    $tpl->set('d', 'CATFIELD', $oCheckbox->toHtml(false));
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
        $sClientCheckboxes .= $oCheckbox->toHtml();
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
        $sClientCheckboxes .= $oCheckbox->toHtml();
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
        $sClientCheckboxes .= $oCheckbox->toHtml();
    }
}

if ($sClientCheckboxes != '') {
    $tpl->set('d', 'ROW_ID', "rights_languages");
    $tpl->set('d', 'CATNAME', i18n("Access languages"));
    $tpl->set('d', 'CATFIELD', $sClientCheckboxes);
    $tpl->next();
}

// user properties
$aProperties = $oUser->getUserProperties();
$sPropRows = '';
foreach ($aProperties as $entry) {
    // ommit system props
    if ('system' === $entry['type']) {
        continue;
    }
    $type = $entry['type'];
    $name = $entry['name'];
    $value = $entry['value'];
    $href = $sess->url("main.php?area=$area&frame=4&userid={$request['userid']}&del_userprop_type={$type}&del_userprop_name={$name}");
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
$tpl->set('d', 'CATFIELD', $oCheckbox->toHtml(false));
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
    $sCurrentValueFrom = '0000-00-00 00:00:00';
}

if (($sCurrentValueTo == '') || ($sCurrentValueTo == '0000-00-00 00:00:00')) {
    $sCurrentValueTo = '9999-99-99 99:99:99';
}

$sCurrentDate = date('Y-m-d H:i:s');

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
$aGroups = $oUser2->getGroupNamesByUserID($request['userid']);
if (count($aGroups) > 0) {
    asort($aGroups);
    foreach ($aGroups as $groupname) {
    	$sGroups .= conHtmlSpecialChars($groupname) . "<br />";
    }
} else {
    $sGroups = i18n("none");
}

$tpl->set('d', 'ROW_ID', "group_membership");
$tpl->set('d', 'CATNAME', i18n("Group membership"));
$tpl->set('d', 'CATFIELD', $sGroups);
$tpl->next();

// Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['rights_overview']);
