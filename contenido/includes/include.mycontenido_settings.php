<?php
/**
 * This file contains the backend page for the personal user settings.
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Unknown
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$cpage = new cGuiPage("mycontenido_settings", "", "2");

$user = new cApiUser($auth->auth["uid"]);

if ($action == "mycontenido_editself") {

    $notidisplayed = false;

    if (!isset($wysi)) {
        $wysi = false;
    }

    $error = false;

    if ($newpassword != "") {
        if ($user->encodePassword($oldpassword) != $user->get("password")) {
            $error = i18n("Old password incorrect");
        }

        if (strcmp($newpassword, $newpassword2) != 0) {
            $error = i18n("Passwords don't match");
        }

        if ($error !== false) {
            $cpage->displayError($error);
        } else {
            // New Class User, update password

            $iResult = $user->savePassword($newpassword);

            // user->set("password", md5($newpassword));

            if ($iResult == cApiUser::PASS_OK) {
                $notidisplayed = true;
                $cpage->displayOk(i18n("Changes saved"));
            } else {
                $notidisplayed = true;
                $cpage->displayError(cApiUser::getErrorString($iResult));
            }
        }
    }

    if ($user->get("realname") != $name) {
        $user->set("realname", $name);
    }
    if ($user->get("email") != $email) {
        $user->set("email", $email);
    }
    if ($user->get("telephone") != $phonenumber) {
        $user->set("telephone", $phonenumber);
    }
    if ($user->get("address_street") != $street) {
        $user->set("address_street", $street);
    }
    if ($user->get("address_zip") != $zip) {
        $user->set("address_zip", $zip);
    }
    if ($user->get("address_city") != $city) {
        $user->set("address_city", $city);
    }
    if ($user->get("address_country") != $country) {
        $user->set("address_country", $country);
    }
    if ($user->get("wysi") != $wysi) {
        $user->set("wysi", $wysi);
    }

    if (true === cString::validateDateFormat($format)) {
        $user->setUserProperty("dateformat", "full", $format);
    } else {
        $notidisplayed = true;
        $cpage->displayError(i18n("Date/Time format is not correct."));
    }
    if (true === cString::validateDateFormat($formatdate)) {
        $user->setUserProperty("dateformat", "date", $formatdate);
    } else {
        $notidisplayed = true;
        $cpage->displayError(i18n("Date format is not correct."));
    }
    if (true === cString::validateDateFormat($formattime)) {
        $user->setUserProperty("dateformat", "time", $formattime);
    } else {
        $notidisplayed = true;
        $cpage->displayError(i18n("Time format is not correct."));
    }

    if ($user->store() && !$notidisplayed) {
        $cpage->displayOk(i18n("Changes saved"));
    } else if (!$notidisplayed) {
        $cpage->displayError(i18n("An error occured while saving user info."));
    }
}

$username = $user->get('username');
$realname = $user->get('realname');
if (!empty($realname)) {
    $username .= ' (' . $realname . ')';
}
$settingsfor = sprintf(i18n("Settings for %s"), $username);

$form = new cGuiTableForm("settings");

$form->setVar("idlang", $lang);
$form->setVar("area", $area);
$form->setVar("action", "mycontenido_editself");
$form->setVar("frame", $frame);

$form->addHeader($settingsfor);

$realname = new cHTMLTextbox("name", $user->get("realname"));
$form->add(i18n("Name"), $realname);

// @since 2006-07-04 Display password fields if not authenticated via LDAP/AD,
// only
if ($user->get("password") != 'active_directory_auth') {
    $oldpassword = new cHTMLPasswordbox("oldpassword");
    $newpassword = new cHTMLPasswordbox("newpassword");
    $newpassword2 = new cHTMLPasswordbox("newpassword2");

    $form->add(i18n("Old password"), $oldpassword);
    $form->add(i18n("New password"), $newpassword);
    $form->add(i18n("Confirm new password"), $newpassword2);
}

$email = new cHTMLTextbox("email", $user->get("email"));
$form->add(i18n("E-Mail"), $email);

$phone = new cHTMLTextbox("phonenumber", $user->get("telephone"));
$form->add(i18n("Phone number"), $phone);

$street = new cHTMLTextbox("street", $user->get("address_street"));
$form->add(i18n("Street"), $street);

$zipcode = new cHTMLTextbox("zip", $user->get("address_zip"), "10", "10");
$form->add(i18n("ZIP code"), $zipcode);

$city = new cHTMLTextbox("city", $user->get("address_city"));
$form->add(i18n("City"), $city);

$country = new cHTMLTextbox("country", $user->get("address_country"));
$form->add(i18n("Country"), $country);

$wysiwyg = new cHTMLCheckbox("wysi", 1);
$wysiwyg->setChecked($user->get("wysi"));
$wysiwyg->setLabelText(i18n("Use WYSIWYG Editor"));

$form->add(i18n("Options"), array(
    $wysiwyg
));

$formathint = "<br>" . i18n("The format is equal to PHP's date() function.");
$formathint .= "<br>";
$formathint .= i18n("Common date formattings") . ":";
$formathint .= "<br>";
$formathint .= "d M Y H:i => 01 Jan 2004 00:00";
$formathint .= "<br>";
$formathint .= "d.m.Y H:i:s => 01.01.2004 00:00:00";

// $form->addSubHeader(i18n("Time format"));
// $form->add(i18n("Date/Time format"), $fulldateformat->render().'
// '.generateInfoIcon(i18n("FORMAT_DATE_TIME")));
// $form->add(i18n("Date format"), $dateformat->render().'
// '.generateInfoIcon(i18n("FORMAT_DATE")));
// $form->add(i18n("Time format"), $timeformat->render().'
// '.generateInfoIcon(i18n("FORMATE_TIME")));
// $form->add(i18n("Date/Time locale"), $dateLocale->render().'
// '.generateInfoIcon(i18n("LANUAGE_DATE_TIME")));

$format = new cHTMLTextbox("format", $user->getUserProperty("dateformat", "full"));
$format2 = new cHTMLTextbox("formatdate", $user->getUserProperty("dateformat", "date"));
$format3 = new cHTMLTextbox("formattime", $user->getUserProperty("dateformat", "time"));

$infoButton = new cGuiBackendHelpbox(i18n("FORMAT_DATE_TIME"));

$form->add(i18n("Date/Time format"), array(
    $format,
    ' ',
    $infoButton->render(),
    $formathint
));
$infoButton->setHelpText(i18n("FORMAT_DATE"));
$form->add(i18n("Date format"), array(
    $format2,
    ' ',
    $infoButton->render()
));
$infoButton->setHelpText(i18n("FORMATE_TIME"));
$form->add(i18n("Time format"), array(
    $format3,
    ' ',
    $infoButton->render()
));

$cpage->setContent(array(
    $form
));
$cpage->render();
