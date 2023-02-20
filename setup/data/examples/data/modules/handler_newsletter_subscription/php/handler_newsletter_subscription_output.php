<?php
/**
 * Description: Newsletter handler outout
 *
 * @package Module
 * @subpackage HandlerNewsletterSubscription
 * @author unknown
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!class_exists('NewsletterJobCollection')) {
    echo mi18n("ERROR_CLASS");
} else {

    // Initialisation
    $oClientLang = new cApiClientLanguage(false, $client, $lang);
    $oClient = new cApiClient($client);
    $oRecipients = new NewsletterRecipientCollection();
    $sMessage = " ";
    unset($recipient); // Unset any existing recipient objects - note, that it
                       // must be $recipient for the plugins...

    $frontendURL = cRegistry::getFrontendUrl();

    /*
     * Used variables: JoinSel: Selection, which group will be joined (Default,
     * Selected, User specified) JoinMultiple: If JoinSel = UserSelected then:
     * More than one group may be selected JoinGroups: Selected group(s)
     * JoinMessageType: Message type for new recipients: User select (user),
     * text or html FrontendLink: Link to Frontend Users enabled?
     * FrontendConfirm: Confirmation of newsletter subscription means: Activate
     * frontend account, nothing FrontendDel: Cancellation of newsletter
     * subscription means: Delete frontend account, Deactivate account, nothing
     * SenderEMail: Sender e-mail address HandlerID: ID of handler article
     * ChangeEMailID: ID of change e-mail handler article ???
     */
    $aSettings = [
        'JoinSel'         => $oClientLang->getProperty('newsletter', 'joinsel'),
        'JoinMultiple'    => $oClientLang->getProperty('newsletter', 'joinmultiple'),
        'JoinGroups'      => $oClientLang->getProperty('newsletter', 'joingroups'),
        'JoinMessageType' => $oClientLang->getProperty('newsletter', 'joinmessagetype'),
        // Note: Stored for client, as frontendusers are language independent
        'FrontendLink'    => $oClient->getProperty('newsletter', 'frontendlink'),
        'FrontendConfirm' => "CMS_VALUE[5]",
        'FrontendDel'     => "CMS_VALUE[6]",
        // This one could be recycled by other modules...
        'SenderEMail'     => $oClient->getProperty('global', 'sender-email'),
        'HandlerID'       => $oClientLang->getProperty('newsletter', 'idcatart'),
    ];

    $sTemplate = 'get.tpl';

    // If there is no selection option set or if no groups has been selected,
    // activate option Default
    if ($aSettings['JoinSel'] == '' || $aSettings['JoinGroups'] == '') {
        $aSettings['JoinSel'] = 'Default';
    }
    if ($aSettings['FrontendConfirm'] == '') {
        $aSettings['FrontendConfirm'] = 'ActivateUser';
    }
    if ($aSettings['FrontendDel'] == '') {
        $aSettings['FrontendDel'] = 'DeleteUser';
    }

    if (isset($_POST['action']) && $_POST['action'] === 'subscribe') {
        if (!isset($_POST['email']) || !$_POST['email']) {
            $sMessage = mi18n("SPECIFY_EMAIL");
        } elseif (!isValidMail($_POST['email'])) {
            $sMessage = mi18n("SPECIFY_VALID_EMAIL");
        } elseif ($oRecipients->emailExists($_POST['email'])) {
            $sMessage = mi18n("EMAIL_REGISTERED");
        } elseif ($_POST['privacy'] != 1) {
            $sMessage = mi18n("ACCEPT_POLICY");
        } else {
            $sEMail = preg_replace('/[\r\n]+/', '', stripslashes($_POST['email']));
            $sName = stripslashes($_POST['emailname']);

            // Which newsletter type should the recipient receive?
            switch ($aSettings['JoinMessageType']) {
                case 'user':
                    // 1 = html, 0 = text;
                    $iMessageType = $_POST['selNewsletterType'] == 1 ? 1 : 0;
                    break;
                case 'html':
                    $iMessageType = 1; // html
                    break;
                default:
                    $iMessageType = 0; // Default: text
            }

            // Analyze group specification
            switch ($aSettings['JoinSel']) {
                case 'Selected':
                    $recipient = $oRecipients->create($sEMail, $sName, 0, $aSettings['JoinGroups'], $iMessageType);
                    break;
                case 'UserSelected':
                    $iSelCount = count($_POST['selNewsletterGroup']);

                    if ($iSelCount == 0) {
                        // No group selected
                        $recipient = $oRecipients->create($sEMail, $sName, 0, '', $iMessageType);
                    } else {
                        if ($iSelCount > 1 && $aSettings['JoinMultiple'] != 'enabled') {
                            $sMessage = mi18n("SELECT_ONE_GROUP");
                        } else {
                            // Recipient wants to join special groups
                            $aGroups = explode(',', $aSettings['JoinGroups']);

                            // Check, if received data is valid and matches the
                            // group selection
                            $bError = false;
                            foreach ($_POST['selNewsletterGroup'] as $iIDGroup) {
                                if (!is_numeric($iIDGroup) || !in_array($iIDGroup, $aGroups)) {
                                    $bError = true;
                                    break;
                                }
                            }

                            if ($bError) {
                                $sMessage = mi18n("ERROR_REQUEST");
                            } else {
                                $recipient = $oRecipients->create($sEMail, $sName, 0, implode(',', $_POST['selNewsletterGroup']));
                            }
                        }
                    }
                    break;
                default:
                    $recipient = $oRecipients->create($sEMail, $sName, 0, '', $iMessageType);
            }

            if ($recipient) {
                // Add here code, if you like to store additional information
                // per >recipient< (see frontenduser below)
                // Example: $recipient->setProperty('contact', 'firstname',
                // $_REQUEST['firstname']);
                // contact/firstname have to match the values used in the
                // firstname-recipient-plugin
                // $_REQUEST['firstname'] contains the data from the input-field
                // firstname in the
                // Form module (-> there has to be a field with this name)
                // Note: You should check the values you get (safety)!!!

                $sBody = mi18n("TXTMAILSUBSCRIBE") . "\n" . $frontendURL
                    . 'front_content.php?changelang=' . $lang
                    . '&idcatart=' . $aSettings['HandlerID']
                    . '&confirm=' . $recipient->get('hash') . "\n\n";

                $mailer = new cMailer();
                $from = [
                    $aSettings['SenderEMail'] => $aSettings['SenderEMail'],
                ];
                $recipients = $mailer->sendMail($from, $sEMail, mi18n("NEWSLETTER_CONFIRMATION"), $sBody);

                if ($recipients > 0) {
                    $sMessage = mi18n("SUBCRIBED");

                    if ($aSettings['FrontendLink'] === 'enabled') {
                        $oFrontendUsers = new cApiFrontendUserCollection();

                        if (!$oFrontendUsers->userExists($sEMail)) {
                            if ($frontenduser = $oFrontendUsers->create($sEMail)) {
                                // it's 'frontenduser' (instead of
                                // oFrontendUser) for plugins...
                                // Add here code, if you like to store
                                // additional information per >frontenduser<
                                // Example:
                                // $frontenduser->setProperty('contact',
                                // 'firstname', $_REQUEST['firstname']);
                                // contact/firstname have to match the values
                                // used in the
                                // firstname-frontenduser-plugin
                                // $_REQUEST['firstname']
                                // contains the data from the input-field
                                // firstname in the
                                // Form module (-> there has to be a field with
                                // this name)
                                // Note: You should check the values you get
                                // (safety)!!!
                                if ($aSettings['FrontendConfirm'] === 'ActivateUser') {
                                    // Inform about frontend user account
                                    // creation
                                    $sMessage .= mi18n("TXT_AFTER_CONFIRMATION");
                                }
                            } else {
                                $sMessage .= mi18n("TXT_PROBLEM_CREATING_ACCOUNT");
                            }
                        }
                    }
                } else {
                    $sMessage = mi18n("TXT_SENDING_CONFIRMATION_MAIL");
                }
            } else {
                $sMessage = mi18n("TXT_PROBLEM_SUBSCRIBING_EMAIL");
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        if (!isset($_POST['email']) || !$_POST['email']) {
            $sMessage = mi18n("SPECIFY_EMAIL");
        } elseif (!isValidMail($_POST['email'])) {
            $sMessage = mi18n("SPECIFY_VALID_EMAIL");
        } elseif ($recipient = $oRecipients->emailExists($_POST['email'])) {
            $sBody = mi18n("TXTMAILDELETE") . "\n" . $frontendURL
                . 'front_content.php?changelang=' . $lang
                . '&idcatart=' . $aSettings['HandlerID']
                . '&unsubscribe=' . $recipient->get('hash') . "\n\n";

            $mailer = new cMailer();
            $from = [
                $aSettings['SenderEMail'] => $aSettings['SenderEMail'],
            ];
            $recipients = $mailer->sendMail($from, $recipient->get('email'), mi18n("NEWSLETTER_CANCEL"), $sBody);

            if ($recipients > 0) {
                $sMessage = mi18n("TXT_SUBSCRIBER_EMAIL_SEND");
            } else {
                $sMessage = mi18n("PROBLEM_SENDING_CANCELATION_EMAIL");
            }
        } else {
            $sMessage = mi18n("EMAIL_NOT_FOUND");
        }
    } elseif (isset($_GET['confirm']) && cString::getStringLength($_GET['confirm']) == 30 && cString::isAlphanumeric($_GET['confirm'])) {
        $oRecipients->setWhere('idclient', $client);
        $oRecipients->setWhere('idlang', $lang);
        $oRecipients->setWhere('hash', $_GET['confirm']);
        $oRecipients->query();

        if (($recipient = $oRecipients->next()) !== false) {
            // For some reason, $recipient may get invalid later on - save id
            // ... and email
            $iID = $recipient->get('idnewsrcp');
            $sEMail = $recipient->get('email');
            $recipient->set('confirmed', 1);
            $recipient->set('confirmeddate', date('Y-m-d H:i:s'), false);
            $recipient->set('deactivated', 0);
            $recipient->store();

            $sMessage = mi18n("CONFIRMED_SUBSCRIPTION_NEWSLETTER");

            $oNewsletters = new NewsletterCollection();
            $oNewsletters->setWhere('idclient', $client);
            $oNewsletters->setWhere('idlang', $lang);
            $oNewsletters->setWhere('welcome', '1');
            $oNewsletters->query();

            if (($oNewsletter = $oNewsletters->next()) !== false) {
                $aRecipients = []; // Needed, as used by reference
                $oNewsletter->sendDirect($aSettings['HandlerID'], $iID, false, $aRecipients);
                $sMessage .= mi18n("WELCOME_NEWSLETTER");
            }

            if ($aSettings['FrontendLink'] === 'enabled' && $aSettings['FrontendConfirm'] === 'ActivateUser') {
                $oFrontendUsers = new cApiFrontendUserCollection();
                $oFrontendUsers->setWhere('idclient', $client);
                $oFrontendUsers->setWhere('username', $sEMail);
                $oFrontendUsers->query();

                if (($frontenduser = $oFrontendUsers->next()) !== false) {
                    $frontenduser->set('active', 1);
                    $sPassword = cString::getPartOfString(md5(rand()), 0, 8); // Generating
                                                            // password
                    $frontenduser->set('password', $sPassword);
                    $frontenduser->store();

                    $sMessage .= mi18n("TXT_ACCOUNT_ACTIVATED");
                    $sMessage .= mi18n("USERNAME_COLON") . $sEMail . "\n\n" . $sPassword;

                    $sBody = mi18n("TXTMAILPASSWORD") . "\n\n"
                        . mi18n("USERNAME_COLON") . $sEMail . "\n"
                        . mi18n("PASSWORD_COLON") . $sPassword . "\n\n"
                        . mi18n("LOGIN_CLICK") . $frontendURL . 'front_content.php?changelang=' . $lang;

                    $mailer = new cMailer();
                    $from = [
                        $aSettings['SenderEMail'] => $aSettings['SenderEMail'],
                    ];
                    $recipients = $mailer->sendMail($from, $sEMail, mi18n("WEBSITE_ACCOUNT"), $sBody);

                    if ($recipients > 0) {
                        $sMessage .= mi18n("ACOUNT_DETAILS_EMAIL");
                    } else {
                        $sMessage .= mi18n("TXT_PROBLEM_ACCOUNTDETAILS");
                    }
                } else {
                    $sMessage .= mi18n("PROBLEM_ACTIVATING_EMAIL_ACCOUNT");
                }
            }
        } else {
            $sMessage = mi18n("PROBLEM_CONFIRMING_SUBSCRIPTION");
        }
    } elseif (isset($_GET['stop']) && cString::getStringLength($_GET['stop']) == 30 && cString::isAlphanumeric($_GET['stop'])) {
        $oRecipients->setWhere('idclient', $client);
        $oRecipients->setWhere('idlang', $lang);
        $oRecipients->setWhere('hash', $_GET['stop']);
        $oRecipients->query();

        if (($recipient = $oRecipients->next()) !== false) {
            $recipient->set('deactivated', 1);
            $recipient->store();
            $sMessage = mi18n("NEWSLETTER_SUBSCRIPTION_PAUSED");
        } else {
            $sMessage = mi18n("PROBLEM_PAUSING_NEWSLETTER_SUBSCRIPTION");
        }
    } elseif (isset($_GET['goon']) && cString::getStringLength($_GET['goon']) == 30 && cString::isAlphanumeric($_GET['goon'])) {
        $oRecipients->setWhere('idclient', $client);
        $oRecipients->setWhere('idlang', $lang);
        $oRecipients->setWhere('hash', $_GET['goon']);
        $oRecipients->query();

        if (($recipient = $oRecipients->next()) !== false) {
            $recipient->set('deactivated', 0);
            $recipient->store();
            $sMessage = mi18n("NEWSLETTER_SUBSCRIPTION_RESUMED");
        } else {
            $sMessage = mi18n("PROBLEM_RESUMING_NEWSLETTER_SUBSCRIPTION");
        }
    } elseif (isset($_GET['unsubscribe']) && cString::getStringLength($_GET['unsubscribe']) == 30 && cString::isAlphanumeric($_GET['unsubscribe'])) {
        $oRecipients->setWhere('idclient', $client);
        $oRecipients->setWhere('idlang', $lang);
        $oRecipients->setWhere('hash', $_GET['unsubscribe']);
        $oRecipients->query();

        if (($recipient = $oRecipients->next()) !== false) {
            // Saving recipient e-mail address for frontend account
            $sEMail = $recipient->get('email');
            $oRecipients->delete($recipient->get('idnewsrcp'));

            $sMessage = mi18n("EMAIL_ADDRESS_REMOVED");

            if ($aSettings['FrontendLink'] === 'enabled') {
                $oFrontendUsers = new cApiFrontendUserCollection();
                $oFrontendUsers->setWhere('idclient', $client);
                $oFrontendUsers->setWhere('username', $sEMail);
                $oFrontendUsers->query();

                if (($frontenduser = $oFrontendUsers->next()) !== false) {
                    switch ($aSettings['FrontendDel']) {
                        case 'DeleteUser': // Deleting frontend account
                            $oFrontendUsers->delete($frontenduser->get('idfrontenduser'));
                            $sMessage .= mi18n("WEBSITE_ACCOUNT_DELETED");
                            break;
                        case 'DisableUser': // Disabling frontend account
                            $frontenduser->set('active', 0);
                            $frontenduser->store();
                            $sMessage .= mi18n("WEBSITE_ACCOUNT_DISABLED");
                            break;
                        default:
                    }
                }
            }
        } else {
            $sMessage = mi18n("PROBLEM_REMOVING_EMAIL_ADDRESS");
        }
    }

    $tpl = cSmartyFrontend::getInstance();
    $tpl->assign('CONTENT', $sMessage);
    $tpl->display($sTemplate);
}

?>