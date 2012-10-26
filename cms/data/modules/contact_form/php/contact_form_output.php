<?php
/**
 * Description: Contact Form Output
 *
 * @version 1.0.2
 * @author Andreas Lindner
 * @copyright four for business AG <www.4fb.de>
 *
 * {@internal
 *   created 2005-08-12
 *   $Id$
 * }}
 */

if (!isset($tpl) || !is_object($tpl)) {
    $tpl = new cTemplate();
} else {
    $tpl->reset();
}

$isXHTML = getEffectiveSetting('generator', 'xhtml', 'false');
$br = $isXHTML ? '<br />' : '<br>';

if (!isset($_POST['send'])) {
    // Form has not been sent yet, create contact form

    $amp = ('true' == $isXHTML) ? '&' : '&';

    $formAction = $sess->url("front_content.php?idcat={$idcat}{$amp}idart={$idart}{$amp}parentid={$parentid}");
    $tpl->set('s', 'form_action', $formAction);
    $tpl->set('s', "ANREDE", mi18n("TITLE"));
    $tpl->set('s', "ANREDE_OPTION1", mi18n("MR"));
    $tpl->set('s', "ANREDE_OPTION2", mi18n("MS"));
    $tpl->set('s', "NACHNAME", mi18n("LASTNAME"));
    $tpl->set('s', "VORNAME", mi18n("FIRSTNAME"));
    $tpl->set('s', "FIRMA", mi18n("FIRM"));
    $tpl->set('s', "STRASSE", mi18n("STREET/NR"));
    $tpl->set('s', "PLZORT", mi18n("POSTCODE/CITY"));
    $tpl->set('s', "TELEFON", mi18n("TELEPHONE"));
    $tpl->set('s', "EMAIL", mi18n("E-MAIL"));
    $tpl->set('s', "ANLIEGEN", mi18n("MESSAGE"));
    $tpl->set('s', "PFLICHTFELDER", mi18n("TXT_FILL_ALL_FIELDS"));
    $tpl->set('s', "ABSCHICKEN", mi18n("SEND"));
    $tpl->set('s', "LOESCHEN", mi18n("DELETE"));

    $tpl->generate('templates/kontaktformular.html');
} elseif ($_POST['send'] == 1) {
    // Form has been sent, check user input

    $errorMsg = array();
    if ($_POST['Anrede'] == '') {
        $errorMsg[] = mi18n("TXT_ENTER_TITLE");
    }
    if ($_POST['Vorname'] == '') {
        $errorMsg[] = mi18n("TXT_ENTER_FIRSTNAME");
    }
    if ($_POST['Nachname'] == '') {
        $errorMsg[] = mi18n("TXT_ENTER_LASTNAME");
    }
    if ($_POST['Firma'] == '') {
        $errorMsg[] = mi18n("TXT_ENTER_FIRM");
    }
    if ($_POST['Strasse'] == '') {
        $errorMsg[] = mi18n("TXT_ENTER_STREET");
    }
    if ($_POST['PLZOrt'] == '') {
        $errorMsg[] = mi18n("TXT_ENTER_POSTCODE/CITY");
    }
    if ($_POST['Telefon'] == '') {
        $errorMsg[] = mi18n("TXT_ENTER_TELEPHONE");
    }
    if ($_POST['EMail'] == '') {
        $errorMsg[] = mi18n("TXT_ENTER_EMAIL");
    }
    if ($_POST['Nachricht'] == '') {
        $errorMsg[] = mi18n("TXT_ENTER_MESSAGE");
    }

    if (count($errorMsg) > 0) {
        // Errors have been found
        echo '<p>'
           . '<strong>' . mi18n("TXT_ERROR_SENDING") . '</strong>' . $br
           . implode($br, $errorMsg) . $br
           . '<a href="javascript:history.back();"> ' . mi18n("BACK") . '</a>'
           . '</p>';
    } else {
        // No errors, create and send mail
        switch (strtolower("CMS_VALUE[4]")) {
            case "smtp":
                $host = "CMS_VALUE[5]";
                $user = "CMS_VALUE[6]";
                $password = "CMS_VALUE[7]";
                if (is_numeric("CMS_VALUE[8]")) {
                    $port = (int) "CMS_VALUE[8]";
                } else {
                    $port = 25;
                }
                $transport = cMailer::constructTransport($host, $port, null, $user, $password);
                $mailer = new cMailer($transport);
                break;
            case "mail":
            // backwards compatibility: use mail if sendmail is defined
            case "sendmail":
                $transport = cMailer::constructTransport(null, null);
                $mailer = new cMailer($transport);
                break;
            default :
        }

        $mailBody = '<html><head></head><body bgcolor="#ffffff"><table cellspacing="0" cellpadding="2" border="0">';
        if (is_array($_POST)) {
            foreach ($_POST as $key => $value) {
                if ($key != 'send') {
                    $mailBody .= "<tr><td>$key</td><td>$value</td></tr>";
                }
            }
        }
        $mailBody .= '</table></body></html>';
        $from = array("CMS_VALUE[0]" => "CMS_VALUE[2]");
        $to = "CMS_VALUE[1]";
        $subject = "CMS_VALUE[3]";

        $recipients = $mailer->sendMail($from, $to, $subject, $mailBody, null, null, null, false, 'text/html');

        // Display message after mail is sent
        if ($recipients > 0) {
            echo mi18n("TXT_REQUEST_SUBMITED") . $br;
        } else {
            echo mi18n("TXT_REQUEST_NOT_SUBMITED") . $br;
        }
    }
}

?>