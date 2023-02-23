<?php

/**
 * This file contains the backend page for creating new users.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var cApiUser $currentuser
 * @var cGuiNotification $notification
 * @var cTemplate $tpl
 */

// Form variables from POST
global $username, $realname, $mclient, $mlang, $password, $passwordagain, $email, $telephone,
       $address_street, $address_city, $address_zip, $address_country, $wysi, $valid_from, $valid_to;

$auth = cRegistry::getAuth();
$belang = cRegistry::getBackendLanguage();
$cfg = cRegistry::getConfig();
$action = cRegistry::getAction();
$area = cRegistry::getArea();
$frame = cRegistry::getFrame();
$lang = cRegistry::getLanguageId();
$perm = cRegistry::getPerm();
$sess = cRegistry::getSession();

if (!$perm->have_perm_area_action($area, $action)) {
    $notification->displayNotification("error", i18n("Permission denied"));
    return;
}

$wysi = cSecurity::toInteger($wysi ?? '1');
$username = $username ?? '';
$realname = $realname ?? '';

$aPerms = [];
$sNotification = '';
$bError = false;
$userId = NULL;

if ($action == 'user_createuser') {
    $username = stripslashes(trim($username));
    $realname = stripslashes(trim($realname));

    $cleanUsername = preg_replace('/["\'\/\§$%&]/i', '', $username);
    $cleanRealname = preg_replace('/["\'\/\§$%&]/i', '', $realname);

    if ($username == '') {
        $sNotification = $notification->returnNotification("warning", i18n("Username can't be empty"));
        $bError = true;
    } elseif ($username !== $cleanUsername || $realname !== $cleanRealname) {
        $sNotification = $notification->returnNotification("warning", i18n("Special characters in username and name are not allowed."));
        $bError = true;
    } elseif ($password == '') {
        $sNotification = $notification->returnNotification("warning", i18n("Password can't be empty"));
        $bError = true;
    } else {
        if (is_array($mclient) && count($mclient) > 0) {
            // Prevent setting the permissions for a client without a language of that client
            foreach ($mclient as $selectedclient) {
                // Get all available languages for selected client
                $clientLanguageCollection = new cApiClientLanguageCollection();
                $availablelanguages = $clientLanguageCollection->getLanguagesByClient($selectedclient);

                if (count($mlang) == 0) {
                    // User has no selected language
                    $sNotification = $notification->returnNotification("warning", i18n("Please select a language for your selected client."));
                    $bError = true;
                } elseif ($availablelanguages == false) {
                    // Client has no assigned language(s)
                    $sNotification = $notification->returnNotification("warning", i18n("You can only assign users to a client with languages."));
                    $bError = true;
                } else {
                    // Client has one or more assigned language(s)
                    foreach ($mlang as $selectedlanguage) {
                        if (!$clientLanguageCollection->hasLanguageInClients($selectedlanguage, $mclient)) {
                            // Selected language are not assigned to selected client
                            $sNotification = $notification->returnNotification("warning", i18n("You have to select a client with a language of that client."));
                            $bError = true;
                        }
                    }
                }
            }
        }

        // If we have no errors, continue to create a user
        if (!$bError) {
            $aPerms = cRights::buildUserOrGroupPermsFromRequest(true);

            if (cApiUser::usernameExists($username)) {
                // username already exists
                $sNotification = $notification->returnNotification("warning", i18n("Username already exists"));
                $bError = true;
            } elseif (($passCheck = cApiUser::checkPasswordMask($password)) !== cApiUser::PASS_OK) {
                // password is not valid
                $sNotification = $notification->returnNotification("warning", cApiUser::getErrorString($passCheck));
                $bError = true;
            } elseif (strcmp($password, $passwordagain) == 0) {
                // username is okay, password is valid and both passwords given are
                // equal
                $oUserCollection = new cApiUserCollection();
                $oUser = $oUserCollection->create($username);
                $result = $oUser->setPassword($password);
                $oUser->setRealName($realname);
                $oUser->setMail($email);
                $oUser->setTelNumber($telephone);
                $oUser->setStreet($address_street);
                $oUser->setCity($address_city);
                $oUser->setZip($address_zip);
                $oUser->setCountry($address_country);
                $oUser->setUseWysi($wysi);
                $oUser->setValidDateFrom($valid_from);
                $oUser->setValidDateTo($valid_to);
                $oUser->setPerms($aPerms);

                if ($oUser->store()) {
                    // show success message and clean "old" values
                    $sNotification = $notification->returnNotification("ok", i18n("User created"));
                    $username = '';
                    $realname = '';
                    $email = '';
                    $telephone = '';
                    $address_city = '';
                    $address_country = '';
                    $address_street = '';
                    $address_zip = '';
                    $wysi = 1;
                    $valid_from = '';
                    $valid_to = '';
                    $aPerms = [];
                    $password = '';
                    $userId = $oUser->getUserId();
                } else {
                    $sNotification = $notification->returnNotification("error", "Error saving the user to the database.");
                    $bError = true;
                }
            } else {
                $sNotification = $notification->returnNotification("warning", i18n("Passwords don't match"));
                $bError = true;
            }
        }
    }
}

$tpl->reset();
$tpl->set('s', 'NOTIFICATION', $sNotification);
$tpl->set('s', 'USERID', $userId);

$form = '<form name="user_properties" method="post" action="' . $sess->url("main.php?") . '">
        <input type="hidden" name="area" value="' . $area . '">
        <input type="hidden" name="action" value="user_createuser">
        <input type="hidden" name="frame" value="' . $frame . '">
        <input type="hidden" name="idlang" value="' . $lang . '">';

$tpl->set('s', 'FORM', $form);
$tpl->set('s', 'SUBMITTEXT', i18n("Save changes"));
$tpl->set('s', 'PROPERTY', i18n("Property"));
$tpl->set('s', 'VALUE', i18n("Value"));

$tpl->set('d', 'CATNAME', i18n("Username"));
$oTxtUser = new cHTMLTextbox('username', htmlspecialchars($username), 40, 32);
$oTxtUser->setAttribute('autocomplete', 'off');
$tpl->set('d', 'CATFIELD', $oTxtUser->render());
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("Name"));
$oTxtName = new cHTMLTextbox('realname', htmlspecialchars($realname), 40, 255);
$oTxtName->setAttribute('autocomplete', 'off');
$tpl->set('d', 'CATFIELD', $oTxtName->render());
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("New password"));
$oTxtPass = new cHTMLPasswordbox('password', '', 40, 255);
$oTxtPass->setAutofill(false);
$oTxtPass->setAttribute('autocomplete', 'off');
$tpl->set('d', 'CATFIELD', $oTxtPass->render());
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("Confirm new password"));
$oTxtWord = new cHTMLPasswordbox('passwordagain', '', 40, 255);
$oTxtWord->setAutofill(false);
$oTxtWord->setAttribute('autocomplete', 'off');
$tpl->set('d', 'CATFIELD', $oTxtWord->render());
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("E-Mail"));
$oTxtEmail = new cHTMLTextbox('email', $email, 40, 255);
$oTxtEmail->setAttribute('autocomplete', 'off');
$tpl->set('d', 'CATFIELD', $oTxtEmail->render());
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("Phone number"));
$oTxtTel = new cHTMLTextbox('telephone', $telephone, 40, 255);
$tpl->set('d', 'CATFIELD', $oTxtTel->render());
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("Street"));
$oTxtStreet = new cHTMLTextbox('address_street', $address_street, 40, 255);
$tpl->set('d', 'CATFIELD', $oTxtStreet->render());
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("ZIP code"));
$oTxtZip = new cHTMLTextbox('address_zip', $address_zip, 10, 10);
$tpl->set('d', 'CATFIELD', $oTxtZip->render());
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("City"));
$oTxtCity = new cHTMLTextbox('address_city', $address_city, 40, 255);
$tpl->set('d', 'CATFIELD', $oTxtCity->render());
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("Country"));
$oTxtLand = new cHTMLTextbox('address_country', $address_country, 40, 255);
$tpl->set('d', 'CATFIELD', $oTxtLand->render());
$tpl->next();

$tpl->set('s', 'PATH_TO_CALENDER_PIC', cRegistry::getBackendUrl() . $cfg['path']['images'] . 'calendar.gif');

if (($lang_short = cString::getPartOfString(cString::toLowerCase($belang), 0, 2)) != "en") {
    $langscripts = cHTMLScript::external(cAsset::backend('scripts/jquery/plugins/timepicker-' . $lang_short . '.js')) . "\n"
        . cHTMLScript::external(cAsset::backend('scripts/jquery/plugins/datepicker-' . $lang_short . '.js'));
    $tpl->set('s', 'CAL_LANG', $langscripts);
} else {
    $tpl->set('s', 'CAL_LANG', '');
}

// Build perm checkboxes and properties table with the helper
$rightsAreasHelper = new cRightsAreasHelper($currentuser, $auth, $aPerms);
$isAuthUserSysadmin = $rightsAreasHelper->isAuthSysadmin();
$isContextSysadmin = $rightsAreasHelper->isContextSysadmin();

// Sysadmin perm checkbox
if ($isAuthUserSysadmin) {
    $tpl->set('d', 'CATNAME', i18n("System administrator"));
    $oCheckbox = new cHTMLCheckbox('msysadmin', '1', 'msysadmin1', $isContextSysadmin);
    $tpl->set('d', 'CATFIELD', $oCheckbox->toHtml(false));
    $tpl->next();
}

// Clients admin perms checkboxes
$aClients = $rightsAreasHelper->getAvailableClients();
$sCheckboxes = $rightsAreasHelper->renderClientAdminCheckboxes($aClients);
if (!empty($sCheckboxes)) {
    $tpl->set('d', 'CATNAME', i18n("Administrator"));
    $tpl->set('d', 'CATFIELD', $sCheckboxes);
    $tpl->next();
}

// Clients perms checkboxes
$sCheckboxes = '';
foreach ($aClients as $idclient => $item) {
    $hasAuthUserClientPerm = $rightsAreasHelper->hasAuthClientPerm($idclient);
    $isAuthUserClientAdmin = $rightsAreasHelper->isAuthClientAdmin($idclient);
    if ($hasAuthUserClientPerm || $isAuthUserClientAdmin || $isAuthUserSysadmin) {
        $sCheckboxes .= $rightsAreasHelper->renderClientPermCheckbox($idclient, $item['name']);
    }
}
$tpl->set('d', 'CATNAME', i18n("Access clients"));
$tpl->set('d', 'CATFIELD', $sCheckboxes);
$tpl->next();

// Languages perms checkboxes
$aClientsLanguages = $rightsAreasHelper->getAllClientsAndLanguages();
$sCheckboxes = '';
foreach ($aClientsLanguages as $item) {
    $hasLanguagePerm = $rightsAreasHelper->hasAuthLanguagePerm($item['idlang']);
    $isAuthUserClientAdmin = $rightsAreasHelper->isAuthClientAdmin($item['idclient']);
    if ($hasLanguagePerm || $isAuthUserClientAdmin) {
        $sCheckboxes .= $rightsAreasHelper->renderLanguagePermCheckbox(
            $item['idlang'], $item['langname'], $item['clientname']
        );
    }
}
$tpl->set('d', 'CATNAME', i18n("Access languages"));
$tpl->set('d', 'CATFIELD', $sCheckboxes);
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("Use WYSIWYG-Editor"));
$oCheckbox = new cHTMLCheckbox('wysi', '1', 'wysi1', ((int) $wysi == 1));
$tpl->set('d', 'CATFIELD', $oCheckbox->toHtml(false));
$tpl->next();

$sInputValidFrom = '<input type="text" id="valid_from" name="valid_from" value="' . $valid_from . '">';

$tpl->set('d', 'CATNAME', i18n("Valid from"));
$tpl->set('d', 'CATFIELD', $sInputValidFrom);
$tpl->next();

$sInputValidTo = '<input type="text" id="valid_to" name="valid_to" value="' . $valid_to . '">';

$tpl->set('d', 'CATNAME', i18n("Valid to"));
$tpl->set('d', 'CATFIELD', $sInputValidTo);
$tpl->next();

// Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['rights_create']);
