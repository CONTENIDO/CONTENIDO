<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Display languages
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.3
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created 2003-04-30
 *   modified 2008-06-24, Timo Trautmann, storage for valid from valid to added
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2008-11-17, H. Librenz - new ConUser class are used for user creation now, comments fixed, code formatted
 *   modified 2008-11-18, H. Librenz - values given during a submittion try are now resubmitted
 *   modified 2010-05-31, Ortwin Pinke, PHP >= 5.3, replace deprecated split-function with explode()
 *   modified 2011-02-07, Murat Purc, Cleanup, optimization and formatting
 *   modified 2011-09-01, Dominik Ziegler, prevent creating users without password
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
    $notification->displayNotification("error", i18n("Permission denied"));
    return;
}

$aPerms = array();
$sNotification = '';
$bError = false;

if ($action == 'user_createuser') {
    if ($username == '') {
        $sNotification = $notification->returnNotification("warning", i18n("Username can't be empty"));
        $bError = true;
    } else if ($password == '') {
        $sNotification = $notification->returnNotification("warning", i18n("Password can't be empty"));
        $bError = true;
    } else {
        $aPerms = buildUserOrGroupPermsFromRequest(true);

        $oUserCollection = new cApiUserCollection();
        $oUser = $oUserCollection->create($username);

        if (strcmp($password, $passwordagain) == 0) {

            // ok, both passwords given are equal, but is the password valid?
            $iPassCheck = $oUser->setPassword($password);

            if ($iPassCheck == cApiUser::PASS_OK) {
                // yes, it is....
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
                        // save user id and clean "old" values...
                        $sNotification = $notification->returnNotification("info", i18n("User created"));
                        $userid = $oUser->getUserId();

                        $username = '';
                        $realname = '';
                        $email = '';
                        $telephone = '';
                        $address_city = '';
                        $address_country = '';
                        $address_street = '';
                        $address_zip = '';
                        $wysi = '';
                        $valid_from = '';
                        $valid_to = '';
                        $aPerms = array();
                        $password = '';
                    }
                    else {
                        $sNotification = $notification->returnNotification("error", "Error saving the user to the database.");
                        $bError = true;
                    }
            } else {
                // oh oh, password is NOT valid. check it...
                $sNotification = $notification->returnNotification("warning", cApiUser::getErrorString($iPassCheck));
                $bError = true;
            }

        } else {
            $sNotification = $notification->returnNotification("warning", i18n("Passwords don't match"));
            $bError = true;
        }
    }
}


$tpl->reset();
$tpl->set('s','NOTIFICATION', $sNotification);

$form = '<form name="user_properties" method="post" action="' . $sess->url("main.php?") . '">
         ' . $sess->hidden_session(true) . '
         <input type="hidden" name="area" value="' . $area . '">
         <input type="hidden" name="action" value="user_createuser">
         <input type="hidden" name="frame" value="' . $frame . '">
         <input type="hidden" name="idlang" value="' . $lang . '">';

$tpl->set('s', 'FORM', $form);
$tpl->set('s', 'SUBMITTEXT', i18n("Save changes"));
$tpl->set('s', 'PROPERTY', i18n("Property"));
$tpl->set('s', 'VALUE', i18n("Value"));

$tpl->set('d', 'CATNAME', i18n("Username"));
$oTxtUser = new cHTMLTextbox('username', $username, 40, 32);
$tpl->set('d', 'CATFIELD', $oTxtUser->render());
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("Name"));
$oTxtName = new cHTMLTextbox('realname', $realname, 40, 255);
$tpl->set('d', 'CATFIELD', $oTxtName->render());
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("New password"));
$oTxtPass = new cHTMLPasswordbox('password', '', 40, 255);
$tpl->set('d', 'CATFIELD', $oTxtPass->render());
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("Confirm new password"));
$oTxtWord = new cHTMLPasswordbox('passwordagain', '', 40, 255);
$tpl->set('d', 'CATFIELD', $oTxtWord->render());
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("E-Mail"));
$oTxtEmail = new cHTMLTextbox('email', $email, 40, 255);
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


$tpl->set('s', 'PATH_TO_CALENDER_PIC',  $cfg['path']['contenido_fullhtml']. $cfg['path']['images'] . 'calendar.gif');

if (($lang_short = substr(strtolower($belang), 0, 2)) != "en") {
    $langscripts=  '<script type="text/javascript" src="scripts/datetimepicker/jquery-ui-timepicker-'.$lang_short.'.js"></script>
    <script type="text/javascript" src="scripts/jquery/jquery.ui.datepicker-'.$lang_short.'.js"></script>';
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
    	$oCheckbox = new cHTMLCheckbox("madmin[" . $idclient . "]", $idclient, "madmin[" . $idclient . "]".$idclient, in_array("admin[" . $idclient . "]", $aPerms));
    	$oCheckbox->setLabelText($item['name'] . "(" . $idclient . ")");
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
    	$oCheckbox = new cHTMLCheckbox("mclient[" . $idclient . "]", $idclient, "mclient[" . $idclient . "]".$idclient, in_array("client[" . $idclient . "]", $aPerms));
   		$oCheckbox->setLabelText($item['name'] . "(" . $idclient . ")");
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
    	$oCheckbox = new cHTMLCheckbox("mlang[" . $item['idlang'] . "]", $item['idlang'], "mlang[" . $item['idlang'] . "]".$item['idlang'], in_array("lang[" . $item['idlang'] . "]", $aPerms));
    	$oCheckbox->setLabelText($item['langname'] . "(" . $item['clientname'] . ")");
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

$sInputValidFrom = '<input type="text" id="valid_from" name="valid_from" value="' . $valid_from . '" />';


$tpl->set('d', 'CATNAME', i18n("Valid from"));
$tpl->set('d', 'CATFIELD', $sInputValidFrom);
$tpl->next();

$sInputValidTo  = '<input type="text" id="valid_to" name="valid_to" value="' . $valid_to . '" />';


$tpl->set('d', 'CATNAME', i18n("Valid to"));
$tpl->set('d', 'CATFIELD', $sInputValidTo);
$tpl->next();

// Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['rights_create']);

?>