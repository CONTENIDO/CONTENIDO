<?php
/**
 * $RCSfile$
 *
 * Description: Contact Form Output
 *
 * @version 1.0.2
 * @author Andreas Lindner
 * @copyright four for business AG <www.4fb.de>
 *
 * {@internal
 *   created 2005-08-12
 *   modified 2008-04-11 Rudi Bieller Changes concerning new layout
 *   modified 2011-08-03 Murat Purc, bugfix [CON-409]
 *   modified 2011-11-09 Murat Purc, added configuration for SMTP port
 * }}
 *
 * $Id$
 */

if (!isset($tpl) || !is_object($tpl)) {
    $tpl = new Template();
} else {
    $tpl->reset();
}

$isXHTML = getEffectiveSetting('generator', 'xhtml', 'false');
$br = $isXHTML ? '<br />' : '<br>';

if (!isset($_POST['send'])) {
    // Form has not been sent yet, create contact form

    $amp = ('true' == $isXHTML) ? '&amp;' : '&';

    $formAction = $sess->url("front_content.php?idcat={$idcat}{$amp}idart={$idart}{$amp}parentid={$parentid}");
    $tpl->set('s', 'form_action', $formAction);
    $tpl->set('s', "ANREDE", mi18n("Anrede"));
    $tpl->set('s', "ANREDE_OPTION1", mi18n("Herr"));
    $tpl->set('s', "ANREDE_OPTION2", mi18n("Frau"));
    $tpl->set('s', "NACHNAME", mi18n("Name"));
    $tpl->set('s', "VORNAME", mi18n("Vorname"));
    $tpl->set('s', "FIRMA", mi18n("Firma"));
    $tpl->set('s', "STRASSE", mi18n("Straße/Nr."));
    $tpl->set('s', "PLZORT", mi18n("PLZ/Ort"));
    $tpl->set('s', "TELEFON", mi18n("Telefon"));
    $tpl->set('s', "EMAIL", mi18n("E-Mail"));
    $tpl->set('s', "ANLIEGEN", mi18n("Nachricht"));
    $tpl->set('s', "PFLICHTFELDER", mi18n("Bitte alle Felder ausfüllen"));
    $tpl->set('s', "ABSCHICKEN", mi18n("abschicken"));
    $tpl->set('s', "LOESCHEN", mi18n("löschen"));

    $tpl->generate($cfgClient[$client]['path']['frontend'] . 'templates/kontaktformular.html');
} elseif ($_POST['send'] == 1) {
    // Form has been sent, check user input

    $errorMsg = array();
    if ($_POST['Anrede'] == '') {
        $errorMsg[] = mi18n("Bitte w&auml;hlen Sie Anrede aus!");
    }
    if ($_POST['Vorname'] == '') {
        $errorMsg[] = mi18n("Bitte geben Sie Ihren Vornamen ein!");
    }
    if ($_POST['Nachname'] == '') {
        $errorMsg[] = mi18n("Bitte geben Sie Ihren Namen ein!");
    }
    if ($_POST['Firma'] == '') {
        $errorMsg[] = mi18n("Bitte geben Sie Ihre Firma ein!");
    }
    if ($_POST['Strasse'] == '') {
        $errorMsg[] = mi18n("Bitte geben Sie Ihre Stra&szlig;e ein!");
    }
    if ($_POST['PLZOrt'] == '') {
        $errorMsg[] = mi18n("Bitte geben Sie Ihre PLZ/Ort ein!");
    }
    if ($_POST['Telefon'] == '') {
        $errorMsg[] = mi18n("Bitte geben Sie Ihre Telefonnummer ein!");
    }
    if ($_POST['EMail'] == '') {
        $errorMsg[] = mi18n("Bitte geben Sie Ihre E-Mail-Adresse ein!");
    }
    if ($_POST['Nachricht'] == '') {
        $errorMsg[] = mi18n("Bitte geben Sie Ihre Nachricht fur uns ein!");
    }

    if (count($errorMsg) > 0) {
        // Errors have been found
        echo '<p>'
           . '<strong>' . mi18n("Beim Versenden sind folgende Fehler aufgetreten:") . '</strong>' . $br
           . implode($br, $errorMsg) . $br
           . '<a href="javascript:history.back();">&lsaquo; ' . mi18n("zur&uuml;ck") . '</a>'
           . '</p>';
    } else {
        // No errors, create and send mail
        $mail = new PHPMailer();
        $mailBody = '<html><head></head><body bgcolor="#ffffff"><table cellspacing="0" cellpadding="2" border="0">';

        if (is_array($_POST)) {
            foreach ($_POST as $key => $value) {
                if ($key != 'send') {
                    $mailBody .= "<tr><td>$key</td><td>$value</td></tr>";
                }
            }
        }

        $mailBody .= '</table></bo'.'dy></html>';
        $mail->Host = "localhost";
        $mail->IsHTML(true);

        // Get mailer from settings
        switch (strtolower("CMS_VALUE[4]")) {
            case "smtp" :
                $mail->IsSMTP();
                $host = "CMS_VALUE[5]";
                $user = "CMS_VALUE[6]";
                $password = "CMS_VALUE[7]";
                if (($host != '') && ($user != '') && ($password != '')) {
                    $mail->SMTPAuth = true;
                    $mail->Host = $host;
                    $mail->Username = $user;
                    $mail->Password = $password;
                    if (is_numeric("CMS_VALUE[8]")) {
                        $mail->Port = (int) "CMS_VALUE[8]";
                    }
                }
                break;
            case "mail" :
                $mail->IsMail();
                break;
            case "sendmail" :
                $mail->IsSendmail();
                break;
            case "qmail" :
                $mail->IsQmail();
                break;
            default :
                }
        $mail->From = "CMS_VALUE[0]";
        $mail->FromName = "CMS_VALUE[2]";
        $mail->AddAddress("CMS_VALUE[1]", "");
        $mail->Subject = "CMS_VALUE[3]";
        $mail->Body = $mailBody;
        $mail->WordWrap = 50;
        $mail->Send();

        // Display message after mail is sent
        echo mi18n("Ihr Anliegen wurde uns übermittelt. Vielen Dank!") . $br;
    }
}
?>