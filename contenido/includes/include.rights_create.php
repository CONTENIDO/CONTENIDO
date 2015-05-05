<?php
/**
 * This file contains the backend page for creating new users.
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
    $notification->displayNotification("error", i18n("Permission denied"));
    return;
}

if (!isset($wysi)) {
    $wysi = 1;
}

$aPerms = array();
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
    } else if ($username !== $cleanUsername || $realname !== $cleanRealname) {
        $sNotification = $notification->returnNotification("warning", i18n("Special characters in username and name are not allowed."));
        $bError = true;
    } else if ($password == '') {
        $sNotification = $notification->returnNotification("warning", i18n("Password can't be empty"));
        $bError = true;
    } else {

    	if (count($mclient) > 0) {

	    	// Prevent setting the permissions for a client without a language of that client
	        foreach ($mclient as $selectedclient) {

	        	// Get all available languages for selected client
	        	$clientLanguageCollection = new cApiClientLanguageCollection();
	        	$availablelanguages = $clientLanguageCollection->getLanguagesByClient($selectedclient);

	        	if (count($mlang) == 0) {
	        		// User has no selected language
	        		$sNotification = $notification->returnNotification("warning", i18n("Please select a language for your selected client."));
	        		$bError = true;
	        	} else if ($availablelanguages == false) {
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
		if ($bError == false) {
	        $aPerms = buildUserOrGroupPermsFromRequest(true);

	        if (cApiUser::usernameExists($username)) {
	            // username already exists
	            $sNotification = $notification->returnNotification("warning", i18n("Username already exists"));
	            $bError = true;
	        } else if (($passCheck = cApiUser::checkPasswordMask($password)) !== cApiUser::PASS_OK) {
	            // password is not valid
	            $sNotification = $notification->returnNotification("warning", cApiUser::getErrorString($passCheck));
	            $bError = true;
	        } else if (strcmp($password, $passwordagain) == 0) {
	            // username is okay, password is valid and both passwords given are
	            // equal
	            $oUserCollection = new cApiUserCollection();
	            $oUser = $oUserCollection->create($username);
	            $oUser->setPassword($password);
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
	                $sNotification = $notification->returnNotification("info", i18n("User created"));
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
	                $aPerms = array();
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
$oTxtPass->setAttribute('autocomplete', 'off');
$tpl->set('d', 'CATFIELD', $oTxtPass->render());
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("Confirm new password"));
$oTxtWord = new cHTMLPasswordbox('passwordagain', '', 40, 255);
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
    $tpl->set('d', 'CATNAME', i18n("Administrator"));
    $tpl->set('d', 'CATFIELD', $sClientCheckboxes);
    $tpl->next();
}

// clients perms
$sClientCheckboxes = '';
foreach ($aClients as $idclient => $item) {
    if (in_array("client[" . $idclient . "]", $aAuthPerms) || in_array('sysadmin', $aAuthPerms) || in_array("admin[" . $idclient . "]", $aAuthPerms)) {
        $oCheckbox = new cHTMLCheckbox("mclient[" . $idclient . "]", $idclient, "mclient[" . $idclient . "]" . $idclient, in_array("client[" . $idclient . "]", $aPerms));
        $oCheckbox->setLabelText($item['name'] . " (" . $idclient . ")");
        $sClientCheckboxes .= $oCheckbox->toHTML();
    }
}

$tpl->set('d', 'CATNAME', i18n("Access clients"));
$tpl->set('d', 'CATFIELD', $sClientCheckboxes);
$tpl->next();

// languages perms
$aClientsLanguages = getAllClientsAndLanguages();
$sClientCheckboxes = '';
foreach ($aClientsLanguages as $item) {
    if ($perm->have_perm_client("lang[" . $item['idlang'] . "]") || $perm->have_perm_client("admin[" . $item['idclient'] . "]")) {
        $oCheckbox = new cHTMLCheckbox("mlang[" . $item['idlang'] . "]", $item['idlang'], "mlang[" . $item['idlang'] . "]" . $item['idlang'], in_array("lang[" . $item['idlang'] . "]", $aPerms));
        $oCheckbox->setLabelText($item['langname'] . " (" . $item['clientname'] . ")");
        $sClientCheckboxes .= $oCheckbox->toHTML();
    }
}

$tpl->set('d', 'CATNAME', i18n("Access languages"));
$tpl->set('d', 'CATFIELD', $sClientCheckboxes);
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("Use WYSIWYG-Editor"));
$oCheckbox = new cHTMLCheckbox('wysi', '1', 'wysi1', ((int) $wysi == 1));
$tpl->set('d', 'CATFIELD', $oCheckbox->toHTML(false));
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
