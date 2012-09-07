<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * MyContenido
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend includes
 * @version    1.1.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 *
 * {@internal
 *   created unknown
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2008-10-??, Bilal Arslan - moved password DB queries, added new ConUser object
 *   modified 2008-11-17, H. Librenz - method calls on new ConUser object modified, comments added
 *
 *   $Id: include.mycontenido_settings.php 904 2008-12-04 14:55:00Z timo.trautmann $:
 * }}
 *
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "class.ui.php");
cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "contenido/class.user.php");
cInclude('classes', 'class.conuser.php');

$user = new cApiUser($auth->auth["uid"]);

$noti = "";

if ($action == "mycontenido_editself")
{

	if (!isset($wysi))
	{
		$wysi = false;
	}

	if (!isset($javaedit))
	{
		$javaedit = false;
	}

	$error = false;

	if ($newpassword != "")
	{
    	if (md5($oldpassword) != $user->get("password"))
    	{
    		$error = i18n("Old password incorrect");
    	}

    	if (strcmp($newpassword, $newpassword2) != 0)
    	{
    		$error = i18n("Passwords don't match");
    	}


    	if ($error !== false)
    	{
    		$noti = $notification->returnNotification("error", $error)."<br>";
    	} else {
            // New Class User, update password
            $oUser = new ConUser($cfg, $db, $auth->auth['uid']);
            $iResult = $oUser->savePassword($newpassword);

            #$user->set("password", md5($newpassword));

            if ( $iResult == iConUser::PASS_OK ) {
                $noti = $notification->returnNotification("info", i18n("Password changed"))."<br>";
            } else {
                $noti = $notification->returnNotification("error", ConUser::getErrorString($iResult, $cfg));
            }

    	}
	}



	$user->set("email", $email);
	$user->set("wysi", $wysi);

	$user->setUserProperty("backend", "timeformat", $format);
	$user->setUserProperty("backend", "timeformat_date", $formatdate);
	$user->setUserProperty("backend", "timeformat_time", $formattime);
	$user->setUserProperty("modules", "java-edit", $javaedit);
    $user->store();
}


$settingsfor = sprintf(i18n("Settings for %s"), $user->get("username") . " (".$user->get("realname").")");

$form = new UI_Table_Form("settings");

$form->setVar("idlang", $lang);
$form->setVar("area", $area);
$form->setVar("action", "mycontenido_editself");
$form->setVar("frame", $frame);

$form->addHeader($settingsfor);

// @since 2006-07-04 Display password fields if not authenticated via LDAP/AD, only
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

$wysiwyg = new cHTMLCheckbox("wysi", 1);
$wysiwyg->setChecked($user->get("wysi"));
$wysiwyg->setLabelText(i18n("Use WYSIWYG Editor"));

$javaedit = new cHTMLCheckbox("javaedit", 1);
$javaedit->setChecked($user->getUserProperty("modules", "java-edit"));
$javaedit->setLabelText(i18n("Use JAVA Module Editor (experimental)"));

$form->add(i18n("Options"), array($wysiwyg,$javaedit));

$formathint = "<br>".i18n("The format is equal to PHP's date() function.");
$formathint.= "<br>";
$formathint.= i18n("Common date formattings").":";
$formathint.= "<br>";
$formathint.= "d M Y H:i => 01 Jan 2004 00:00";
$formathint.= "<br>";
$formathint.= "d.m.Y H:i:s => 01.01.2004 00:00:00";

$format = new cHTMLTextbox("format", $user->getUserProperty("backend", "timeformat"));
$format2 = new cHTMLTextbox("formatdate", $user->getUserProperty("backend", "timeformat_date"));
$format3 = new cHTMLTextbox("formattime", $user->getUserProperty("backend", "timeformat_time"));

$form->add(i18n("Date/Time format"), array($format, $formathint));
$form->add(i18n("Date format"), array($format2));
$form->add(i18n("Time format"), array($format3));

$page = new cPage;

$page->setContent(array($noti, $form, markSubMenuItem(3, true)));
$page->render();
?>