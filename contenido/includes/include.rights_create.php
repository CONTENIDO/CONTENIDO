<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Display languages
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend includes
 * @version    1.0.2
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 *
 * {@internal
 *   created 2003-04-30
 *   modified 2008-06-24, Timo Trautmann, storage for valid from valid to added
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2008-11-17, H. Librenz - new ConUser class are used for user creation now, comments fixed, code formatted
 *   modified 2008-11-18, H. Librenz - values given during a submittion try are now resubmitted
 *   modified 2010-05-31, Ortwin Pinke, PHP >= 5.3, replace deprecated split-function with explode()
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
    } else {

        $aPerms = buildUserOrGroupPermsFromRequest(true);

        $oUser = new ConUser($cfg, $db);

        if (strcmp($password, $passwordagain) == 0) {

            // ok, both passwords given are equal, but is the password valid?
            $iPassCheck = $oUser->setPassword($password);

            if ($iPassCheck == iConUser::PASS_OK) {
                // yes, it is....
                try {
                    $oUser->setUserName($username);
                    $oUser->setRealName($realname);
                    $oUser->setMail($email);
                    $oUser->setTelNumber($telephone);
                    $oUser->setStreet($address_street);
                    $oUser->setCity($address_city);
                    $oUser->setZip($address_zip);
                    $oUser->setCountry($address_country);
                    $oUser->setUseTiny($wysi);
                    $oUser->setValidDateFrom($valid_from);
                    $oUser->setValidDateTo($valid_to);
                    $oUser->setPerms($aPerms);
                    $oUser->setPassword($password);

                    if ($oUser->save()) {
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

                } catch (ConUserException $cue) {
                    switch ($cue->getCode()) {
                        case iConUser::EXCEPTION_USERNAME_EXISTS:
                            $sNotification = $notification->returnNotification("warning", i18n("Username already exists"));
                            $bError = true;
                            break;
                        default:
                            $sNotification = $notification->returnNotification("warning", i18n("Unknown error") . ": " . $cue->getMessage());
                            $bError = true;
                            break;
                    }
                }
            } else {
                // oh oh, password is NOT valid. check it...
                $sNotification = $notification->returnNotification("warning", ConUser::getErrorString($iPassCheck, $cfg));
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
$tpl->set('s', 'BORDERCOLOR', $cfg["color"]["table_border"]);
$tpl->set('s', 'BGCOLOR', $cfg["color"]["table_dark"]);
$tpl->set('s', 'SUBMITTEXT', i18n("Save changes"));

$tpl->set('d', 'CATNAME', i18n("Property"));
$tpl->set('d', 'BGCOLOR', $cfg["color"]["table_header"]);
$tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
$tpl->set('d', 'CATFIELD', i18n("Value"));
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("Username"));
$tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
$tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
$tpl->set('d', 'CATFIELD', formGenerateField('text', 'username', $username, 40, 32));
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("Name"));
$tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
$tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
$tpl->set('d', 'CATFIELD', formGenerateField('text', 'realname', $realname, 40, 255));
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("New password"));
$tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
$tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
$tpl->set('d', 'CATFIELD', formGenerateField('password', 'password', '', 40, 255));
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("Confirm new password"));
$tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
$tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
$tpl->set('d', 'CATFIELD', formGenerateField('password', 'passwordagain', '', 40, 255));
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("E-Mail"));
$tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
$tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
$tpl->set('d', 'CATFIELD', formGenerateField('text', 'email', $email, 40, 255));
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("Phone number"));
$tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
$tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
$tpl->set('d', 'CATFIELD', formGenerateField('text', 'telephone', $telephone, 40, 255));
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("Street"));
$tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
$tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
$tpl->set('d', 'CATFIELD', formGenerateField('text', 'address_street', $address_street, 40, 255));
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("ZIP code"));
$tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
$tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
$tpl->set('d', 'CATFIELD', formGenerateField('text', 'address_zip', $address_zip, 10, 10));
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("City"));
$tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
$tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
$tpl->set('d', 'CATFIELD', formGenerateField('text', 'address_city', $address_city, 40, 255));
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("Country"));
$tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
$tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
$tpl->set('d', 'CATFIELD', formGenerateField('text', 'address_country', $address_country, 40, 255));
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
    if (in_array("admin[" . $idclient . "]", $aAuthPerms) || in_array('sysadmin', $aAuthPerms)) {
        $sClientCheckboxes .= formGenerateCheckbox("madmin[" . $idclient . "]", $idclient, in_array("admin[" . $idclient . "]", $aPerms), $item['name'] . "(" . $idclient . ")") . "<br>";
    }
}

if ($sClientCheckboxes !== '') {
    $tpl->set('d', 'CATNAME', i18n("Administrator"));
    $tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
    $tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
    $tpl->set('d', 'CATFIELD', $sClientCheckboxes);
    $tpl->next();
}

// clients perms
$sClientCheckboxes = '';
foreach ($aClients as $idclient => $item) {
    if (in_array("client[" . $idclient . "]", $aAuthPerms) || in_array('sysadmin', $aAuthPerms) || in_array("admin[" . $idclient . "]", $aAuthPerms)) {
        $sClientCheckboxes .= formGenerateCheckbox("mclient[" . $idclient . "]", $idclient, in_array("client[" . $idclient . "]", $aPerms), $item['name'] . "(" . $idclient . ")") . "<br>";
    }
}

$tpl->set('d', 'CATNAME', i18n("Access clients"));
$tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
$tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
$tpl->set('d', 'CATFIELD', $sClientCheckboxes);
$tpl->next();

// languages perms
$aClientsLanguages = getAllClientsAndLanguages();
$sClientCheckboxes = '';
foreach ($aClientsLanguages as $item) {
    if ($perm->have_perm_client("lang[" . $item['idlang'] . "]") || $perm->have_perm_client("admin[" . $item['idclient'] . "]")) {
        $sClientCheckboxes .= formGenerateCheckbox("mlang[" . $item['idlang'] . "]", $item['idlang'], in_array("lang[" . $item['idlang'] . "]", $aPerms), $item['langname'] . "(" . $item['clientname'] . ")") . "<br>";
    }
}

$tpl->set('d', 'CATNAME', i18n("Access languages"));
$tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
$tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
$tpl->set('d', 'CATFIELD', $sClientCheckboxes);
$tpl->next();

$tpl->set('d', 'CATNAME', i18n("Use WYSIWYG-Editor"));
$tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
$tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
$tpl->set('d', 'CATFIELD', formGenerateCheckbox('wysi', '1', ((int) $wysi == 1)));
$tpl->next();

$sInputValidFrom = '<style type="text/css">@import url(./scripts/jscalendar/calendar-contenido.css);</style>
                <script type="text/javascript" src="./scripts/jscalendar/calendar.js"></script>
                <script type="text/javascript" src="./scripts/jscalendar/lang/calendar-' . substr(strtolower($belang), 0, 2) . '.js"></script>
                <script type="text/javascript" src="./scripts/jscalendar/calendar-setup.js"></script>';
$sInputValidFrom .= '<input type="text" id="valid_from" name="valid_from" value="' . $valid_from . '" />&nbsp;<img src="images/calendar.gif" id="trigger" /">';
$sInputValidFrom .= '<script type="text/javascript">
                     Calendar.setup({
                         inputField:  "valid_from",
                         ifFormat:    "%Y-%m-%d",
                         button:      "trigger",
                         weekNumbers: true,
                         firstDay:    1
                     });
                     </script>';

$tpl->set('d', 'CATNAME', i18n("Valid from"));
$tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
$tpl->set('d', 'BGCOLOR', $cfg["color"]["table_dark"]);
$tpl->set('d', 'CATFIELD', $sInputValidFrom);
$tpl->next();

$sInputValidTo  = '<input type="text" id="valid_to" name="valid_to" value="' . $valid_to . '" />&nbsp;<img src="images/calendar.gif" id="trigger_to" /">';
$sInputValidTo .= '<script type="text/javascript">
                   Calendar.setup({
                       inputField:  "valid_to",
                       ifFormat:    "%Y-%m-%d",
                       button:      "trigger_to",
                       weekNumbers: true,
                       firstDay:    1
                   });
                   </script>';

$tpl->set('d', 'CATNAME', i18n("Valid to"));
$tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
$tpl->set('d', 'BGCOLOR', $cfg["color"]["table_light"]);
$tpl->set('d', 'CATFIELD', $sInputValidTo);
$tpl->next();

// Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['rights_create']);

?>