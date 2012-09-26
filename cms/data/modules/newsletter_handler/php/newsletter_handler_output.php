<?php
/**
 * Description: Newsletter handler outout
 *
 * @version    1.0.0
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 *
 * {@internal
 *   created unknown
 *   $Id$
 * }}
 */

// Initialisation
$oClientLang = new cApiClientLanguage(false, $client, $lang);
$oClient     = new cApiClient($client);
$oRecipients = new NewsletterRecipientCollection;
$sMessage = "&nbsp;";
unset($recipient); // Unset any existing recipient objects - note, that it must be $recipient for the plugins...

$frontendURL = cRegistry::getFrontendUrl();

/*
 *  Used variables:
 *  JoinSel:         Selection, which group will be joined (Default, Selected, User specified)
 *  JoinMultiple:    If JoinSel = UserSelected then: More than one group may be selected
 *  JoinGroups:      Selected group(s)
 *  JoinMessageType: Message type for new recipients: User select (user), text or html
 *  FrontendLink:    Link to Frontend Users enabled?
 *  FrontendConfirm: Confirmation of newsletter subscription means: Activate frontend account, nothing
 *  FrontendDel:     Cancellation of newsletter subscription means: Delete frontend account, Deactivate account, nothing
 *  SenderEMail:     Sender e-mail address
 *  HandlerID:       ID of handler article
 *  ChangeEMailID:   ID of change e-mail handler article ???
 */
$aSettings = array(
    'JoinSel'         => $oClientLang->getProperty('newsletter', 'joinsel'),
    'JoinMultiple'    => $oClientLang->getProperty('newsletter', 'joinmultiple'),
    'JoinGroups'      => $oClientLang->getProperty('newsletter', 'joingroups'),
    'JoinMessageType' => $oClientLang->getProperty('newsletter', 'joinmessagetype'),
    'FrontendLink'    => $oClient->getProperty('newsletter', 'frontendlink'), # Note: Stored for client, as frontendusers are language independent
    'FrontendConfirm' => "CMS_VALUE[5]",
    'FrontendDel'     => "CMS_VALUE[6]",
    'SenderEMail'     => $oClient->getProperty('global', 'sender-email'), # This one could be recycled by other modules...
    'HandlerID'       => $oClientLang->getProperty('newsletter', 'idcatart'),
);

$sTemplate = 'newsletter_handler.html';

if (!isset($oPage) || !is_object($oPage)) {
    $oPage = new cTemplate();
}
$oPage->reset();

// If there is no selection option set or if no groups has been selected, activate option Default
if ($aSettings['JoinSel'] == '' || $aSettings['JoinGroups'] == '') {
    $aSettings['JoinSel'] = "Default";
}
if ($aSettings['FrontendConfirm'] == '') {
    $aSettings['FrontendConfirm'] = "ActivateUser";
}
if ($aSettings['FrontendDel'] == '') {
    $aSettings['FrontendDel'] = "DeleteUser";
}

if ($_POST['action'] == "subscribe") {
    if (!isset($_POST['email']) || !$_POST['email']) {
        $sMessage = mi18n("Please specify an e-mail address.");
    } elseif (!isValidMail($_POST['email']) || strpos($_POST['email'], ",") != false || strpos($_POST['email'], ";") != false) {
        $sMessage = mi18n("Please specify a valid e-mail address.");
    } elseif ($oRecipients->emailExists($_POST['email'])) {
        $sMessage = mi18n("This e-mail address has been already registered for the newsletter.");
    } elseif ($_POST['privacy'] != 1){
        $sMessage = mi18n("Please acept our privacy policy!");
    } else {
        $sEMail = preg_replace('/[\r\n]+/', '', stripslashes($_POST['email']));
        $sName  = stripslashes($_POST["emailname"]);

        // Which newsletter type should the recipient receive?
        switch ($aSettings['JoinMessageType']) {
            case "user":
                if ($_POST["selNewsletterType"] == 1) {
                    $iMessageType = 1; // html
                } else {
                    $iMessageType = 0; // text
                }
                break;
            case "html":
                $iMessageType = 1; // html
                break;
            default:
                $iMessageType = 0; // Default: text
        }

        // Analyze group specification
        switch ($aSettings['JoinSel']) {
            case "Selected":
                $recipient = $oRecipients->create($sEMail, $sName, 0, $aSettings['JoinGroups'], $iMessageType);
                break;
            case "UserSelected":
                $iSelCount = count($_POST['selNewsletterGroup']);

                if ($iSelCount == 0) {
                    $recipient = $oRecipients->create($sEMail, $sName, 0, "", $iMessageType); // No group selected
                } else {
                    if ($iSelCount > 1 && $aSettings['JoinMultiple'] != "enabled") {
                        $sMessage = mi18n("Please select one group, only.");
                    } else {
                        // Recipient wants to join special groups
                        $aGroups = explode(",", $aSettings['JoinGroups']);

                        // Check, if received data is valid and matches the group selection
                        $bError = false;
                        foreach ($_POST['selNewsletterGroup'] as $iIDGroup) {
                            if (!is_numeric($iIDGroup) || !in_array($iIDGroup, $aGroups)) {
                                $bError = true;
                                break;
                            }
                        }

                        if ($bError) {
                            $sMessage = mi18n("There was an error processing your request. Please ask the webmaster for help.");
                        } else {
                            $recipient = $oRecipients->create($sEMail, $sName, 0, implode(",", $_POST['selNewsletterGroup']));
                        }
                    }
                }
                break;
            default:
                $recipient = $oRecipients->create($sEMail, $sName, 0, "", $iMessageType);
        }

        if ($recipient) {
            // Add here code, if you like to store additional information per >recipient< (see frontenduser below)
            // Example: $recipient->setProperty("contact", "firstname", $_REQUEST["firstname"]);
            // contact/firstname have to match the values used in the firstname-recipient-plugin
            // $_REQUEST["firstname"] contains the data from the input-field firstname in the
            // Form module (-> there has to be a field with this name)
            // Note: You should check the values you get (safety)!!!

            $sBody = mi18n("txtMailSubscribe")."\n".$frontendURL."front_content.php?changelang=".$lang."&idcatart=".$aSettings['HandlerID']."&confirm=".$recipient->get("hash")."\n\n";

            $mailer = new cMailer();
            $from = array($aSettings['SenderEMail'] => $aSettings['SenderEMail']);
            $recipients = $mailer->sendMail($from, $sEMail, mi18n("Newsletter: Confirmation"), $sBody);

            if ($recipients > 0) {
                $sMessage = mi18n("Dear subscriber,<br>your e-mail address is now subscribed for our newsletter. You will now receive an e-mail asking you to confirm your subscription.");

                if ($aSettings['FrontendLink'] == "enabled") {
                    $oFrontendUsers = new cApiFrontendUserCollection();

                    if (!$oFrontendUsers->userExists($sEMail)) {
                        if ($frontenduser = $oFrontendUsers->create($sEMail)) { // it's "frontenduser" (instead of oFrontendUser) for plugins...
                            // Add here code, if you like to store additional information per >frontenduser<
                            // Example: $frontenduser->setProperty("contact", "firstname", $_REQUEST["firstname"]);
                            // contact/firstname have to match the values used in the firstname-frontenduser-plugin
                            // $_REQUEST["firstname"] contains the data from the input-field firstname in the
                            // Form module (-> there has to be a field with this name)
                            // Note: You should check the values you get (safety)!!!

                            if ($aSettings['FrontendConfirm'] == "ActivateUser") {
                                // Inform about frontend user account creation
                                $sMessage .= mi18n("<br><br>After the confirmation you will also receive a password which you can use with your e-mail address to logon to special areas on this website.");
                            }
                        } else {
                            $sMessage .= mi18n("<br><br>Sorry, there was a problem creating your website account. Please ask the webmaster for help.");
                        }
                    }
                }
            } else {
                $sMessage = mi18n("Sorry, there was a problem sending the confirmation mail to your e-mail address. Please ask the webmaster for help.");
            }
        } else {
            $sMessage = mi18n("Sorry, there was a problem subscribing your e-mail address for the newsletter. Please ask the webmaster for help.");
        }
    }
} elseif ($_POST['action'] == "delete") {
    if (!isset($_POST['email']) || !$_POST['email']) {
        $sMessage = mi18n("Please specify an e-mail address.");
    } elseif (!isValidMail($_POST['email']) || strpos($_POST['email'], ",") != false || strpos($_POST['email'], ";") != false) {
        $sMessage = mi18n("Please specify a valid e-mail address.");
    } elseif ($recipient = $oRecipients->emailExists($_POST['email'])) {
        $sBody = mi18n("txtMailDelete")."\n" . $frontendURL . "front_content.php?changelang=".$lang."&idcatart=".$aSettings['HandlerID']."&unsubscribe=".$recipient->get("hash")."\n\n";

        $mailer = new cMailer();
        $from = array($aSettings['SenderEMail'] => $aSettings['SenderEMail']);
        $recipients = $mailer->sendMail($from, $recipient->get('email'), mi18n("Newsletter: Cancel subscription"), $sBody);

        if ($recipients > 0) {
            $sMessage = mi18n("Dear subscriber,<br>a mail has been sent to your e-mail address. Please confirm the cancelation of the newsletter subscription.");
        } else {
            $sMessage = mi18n("Sorry, there was a problem sending you the cancelation confirmation e-mail. Please ask the webmaster for help.");
        }
    } else {
        $sMessage = mi18n("Sorry, the e-mail address was not found.");
    }
} elseif (strlen($_GET['confirm']) == 30 && isAlphanumeric($_GET['confirm'])) {
    $oRecipients->setWhere("idclient", $client);
    $oRecipients->setWhere("idlang", $lang);
    $oRecipients->setWhere("hash", $_GET['confirm']);
    $oRecipients->query();

    if (($recipient = $oRecipients->next()) !== false) {
        $iID    = $recipient->get("idnewsrcp"); // For some reason, $recipient may get invalid later on - save id
        $sEMail = $recipient->get("email");     // ... and email
        $recipient->set("confirmed", 1);
        $recipient->set("confirmeddate", date("Y-m-d H:i:s"), false);
        $recipient->set("deactivated", 0);
        $recipient->store();

        $sMessage = mi18n("Thank you! You have confirmed your subscription to our newsletter!");

        $oNewsletters = new NewsletterCollection;
        $oNewsletters->setWhere("idclient", $client);
        $oNewsletters->setWhere("idlang", $lang);
        $oNewsletters->setWhere("welcome", '1');
        $oNewsletters->query();

        if (($oNewsletter = $oNewsletters->next()) !== false) {
            $aRecipients = array(); // Needed, as used by reference
            $oNewsletter->sendDirect($aSettings['HandlerID'], $iID, false, $aRecipients);
            $sMessage .= mi18n(" The welcome newsletter is already on the way to you!");
        }

        if ($aSettings['FrontendLink'] == "enabled" && $aSettings['FrontendConfirm'] == "ActivateUser") {
            $oFrontendUsers = new cApiFrontendUserCollection();
            $oFrontendUsers->setWhere("idclient", $client);
            $oFrontendUsers->setWhere("username", $sEMail);
            $oFrontendUsers->query();

            if (($frontenduser = $oFrontendUsers->next()) !== false) {
                $frontenduser->set("active", 1);
                $sPassword = substr(md5(rand()),0,8); // Generating password
                $frontenduser->set("password", $sPassword);
                $frontenduser->store();

                $sMessage .= mi18n("<br><br>Additionally, your website account has been activated. You can now use the following username and password to log in to access special areas on our website:<br>");
                $sMessage .= mi18n("Username: ").$sEMail.mi18n("<br>Password: ").$sPassword;

                $sBody = mi18n("txtMailPassword")."\n\n".mi18n("Username: ").$sEMail."\n".mi18n("Password: ").$sPassword."\n\n".mi18n("Click here to login: "). $frontendURL ."front_content.php?changelang=".$lang;

                $mailer = new cMailer();
                $from = array($aSettings['SenderEMail'] => $aSettings['SenderEMail']);
                $recipients = $mailer->sendMail($from, $sEMail, mi18n("Website account"), $sBody);

                if ($recipients > 0) {
                    $sMessage .= mi18n("<br><br>The account details and the password has also been sent to your mail account.");
                } else {
                    $sMessage .= mi18n("<br><br><b>Sorry, there was a problem sending you the account details by mail. Please remember the given password.</b><b>");
                }
            } else {
                $sMessage .= mi18n("<br><br>Sorry, there was a problem activating your website account, also. Please ask the webmaster for help.");
            }
        }
    } else {
        $sMessage = mi18n("Sorry, there was a problem confirming your subscription. Please ask the webmaster for help.");
    }
} elseif (strlen($_GET['stop']) == 30 && isAlphanumeric($_GET['stop'])) {
    $oRecipients->setWhere("idclient", $client);
    $oRecipients->setWhere("idlang", $lang);
    $oRecipients->setWhere("hash", $_GET['stop']);
    $oRecipients->query();

    if (($recipient = $oRecipients->next()) !== false) {
        $recipient->set("deactivated", 1);
        $recipient->store();
        $sMessage = mi18n("Your newsletter subscription has been paused.");
    } else {
        $sMessage = mi18n("Sorry, there was a problem pausing your newsletter subscription. Please ask the webmaster for help.");
    }
} elseif (strlen($_GET['goon']) == 30 && isAlphanumeric($_GET['goon'])) {
    $oRecipients->setWhere("idclient", $client);
    $oRecipients->setWhere("idlang", $lang);
    $oRecipients->setWhere("hash", $_GET['goon']);
    $oRecipients->query();

    if (($recipient = $oRecipients->next()) !== false) {
        $recipient->set("deactivated", 0);
        $recipient->store();
        $sMessage = mi18n("Newsletter subscription has been resumed.");
    } else {
        $sMessage = mi18n("Sorry, there was a problem resuming your newsletter subscription. Please ask the webmaster for help.");
    }
} elseif (strlen($_GET['unsubscribe']) == 30 && isAlphanumeric($_GET['unsubscribe'])) {
    $oRecipients->setWhere("idclient", $client);
    $oRecipients->setWhere("idlang", $lang);
    $oRecipients->setWhere("hash", $_GET['unsubscribe']);
    $oRecipients->query();

    if (($recipient = $oRecipients->next()) !== false) {
        $sEMail = $recipient->get("email"); // Saving recipient e-mail address for frontend account
        $oRecipients->delete($recipient->get("idnewsrcp"));

        $sMessage = mi18n("Your e-mail address has been removed from our list of newsletter recipients.");

        if ($aSettings['FrontendLink'] == "enabled") {
            $oFrontendUsers = new cApiFrontendUserCollection();
            $oFrontendUsers->setWhere("idclient", $client);
            $oFrontendUsers->setWhere("username", $sEMail);
            $oFrontendUsers->query();

            if (($frontenduser = $oFrontendUsers->next()) !== false) {
                switch ($aSettings['FrontendDel']) {
                    case "DeleteUser": // Deleting frontend account
                        $oFrontendUsers->delete($frontenduser->get("idfrontenduser"));
                        $sMessage .= mi18n(" Your website account has been deleted.");
                        break;
                    case "DisableUser": // Disabling frontend account
                        $frontenduser->set("active", 0);
                        $frontenduser->store();
                        $sMessage .= mi18n(" Your website account has been disabled.");
                        break;
                    default:
                }
            }
        }
    } else {
        $sMessage = mi18n("Sorry, there was a problem removing your e-mail address. Please ask the webmaster for help.");
    }
}

$oPage->set('s', 'CONTENT', $sMessage);
$oPage->generate('templates/'.$sTemplate);

?>