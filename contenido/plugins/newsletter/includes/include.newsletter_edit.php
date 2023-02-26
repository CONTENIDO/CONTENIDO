<?php

/**
 * This file contains the Frontend user editor.
 *
 * @package    Plugin
 * @subpackage Newsletter
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var cAuth $auth
 * @var cPermission $perm
 * @var string $area
 * @var int $client
 * @var int $lang
 * @var int $frame
 */

cInclude("includes", "functions.con.php");

// Initialization
$oPage = new cGuiPage("newsletter_edit", "newsletter");
$oRcpGroups = new NewsletterRecipientGroupCollection();
$oClientLang = new cApiClientLanguage(false, $client, $lang);
$oNewsletters = new NewsletterCollection();

// Ensure to have numeric newsletter id
if (isset($idnewsletter)) {
    $idnewsletter = (int) $idnewsletter;
}

// Include plugins
if (cHasPlugins('newsletters')) {
    cIncludePlugins('newsletters');
}

$action = $action ?? '';

if ($action == "news_create" && $perm->have_perm_area_action($area, "news_create")) {
    // Create new newsletter
    $oNewsletter = $oNewsletters->create(i18n("-- New newsletter --", 'newsletter'));
    $idnewsletter = $oNewsletter->get("idnews");
    $oPage->setSubnav("idnewsletter=$idnewsletter", "news");
    $oPage->reloadLeftBottomFrame(['idnewsletter' => $idnewsletter]);

    // Populating default values
    $oNewsletter->set("newsfrom", $oClientLang->getProperty("newsletter", "newsfrom"));
    $oNewsletter->set("newsfromname", $oClientLang->getProperty("newsletter", "newsfromname"));

    $sValue = $oClientLang->getProperty("newsletter", "sendto");
    if ($sValue == "") {
        $sValue = "all";
    }
    $oNewsletter->set("send_to", $sValue);

    $oNewsletter->set("send_ids", $oClientLang->getProperty("newsletter", "sendids"));

    $iValue = $oClientLang->getProperty("newsletter", "use_cronjob");
    if (!is_numeric($iValue)) {
        $iValue = 0;
    }
    $oNewsletter->set("use_cronjob", $iValue);

    $iValue = $oClientLang->getProperty("newsletter", "dispatch");
    if (!is_numeric($iValue)) {
        $iValue = 0;
    }
    $oNewsletter->set("dispatch", $iValue);

    $iValue = $oClientLang->getProperty("newsletter", "dispatchcount");
    if (!is_numeric($iValue)) {
        $iValue = 50;
    }
    $oNewsletter->set("dispatch_count", $iValue);

    $iValue = $oClientLang->getProperty("newsletter", "dispatchdelay");
    if (!is_numeric($iValue)) {
        $iValue = 5;
    }
    $oNewsletter->set("dispatch_delay", $iValue);
    $oNewsletter->store();
    // show message
    $oPage->displayOk(i18n("Created newsletter successfully!", 'newsletter'));
} elseif ($action == "news_duplicate" && $perm->have_perm_area_action($area, "news_create")) {
    // Copy newsletter
    $oNewsletter = $oNewsletters->duplicate($idnewsletter);

    // Update subnav with new ID
    $oPage->setSubnav("idnewsletter=" . $oNewsletter->get("idnews"), "news");
    $oPage->reloadLeftBottomFrame(['idnewsletter' => $oNewsletter->get("idnews")]);
    // show message
    $oPage->displayOk(i18n("Dupplicate newsletter successfully!", 'newsletter'));
} elseif ($action == "news_delete" && $perm->have_perm_area_action($area, "news_delete")) {
    // Delete newsletter
    // If it is an html newsletter, delete html message article, also
    $oNewsletter = new Newsletter($idnewsletter);

    if ($oNewsletter->get("type") == "html" && $oNewsletter->get("idart") > 0) {
        conDeleteArt($oNewsletter->get("idart"));
    }

    // Delete newsletter
    $oNewsletters->delete($idnewsletter);
    $oNewsletter = new Newsletter(); // Generate empty newsletter object

    // Setting blank subnav - "blank" doesn't mean anything special, it just
    // can't be empty and must not contain "idnewsletter" as this is checked
    // in the _subnav file.
    $oPage->setSubnav("blank", "news");
    $oPage->reloadLeftBottomFrame(['idnewsletter' => null]);
    $oPage->displayOk(i18n("Deleted newsletter successfully!", 'newsletter'));
} elseif ($action == "news_add_job" && $perm->have_perm_area_action($area, "news_add_job")) {
    // Create job
    $oJobs = new NewsletterJobCollection();
    $oJob = $oJobs->create($idnewsletter, $oClientLang->getProperty("newsletter", "idcatart"));
    unset($oJobs);

    if ($oJob) {
        $oPage->displayOk(i18n("Newsletter dispatch job has been added for this newsletter", 'newsletter'));
    } else {
        $oPage->displayError(i18n("Newsletter dispatch job has been not been added! Please check newsletter details", 'newsletter'));
    }

    $oNewsletter = new Newsletter($idnewsletter);
} elseif ($action == "news_send_test" && ($perm->have_perm_area_action($area, "news_create") || $perm->have_perm_area_action($area, "news_save") || $perm->have_perm_area_action($area, "news_add_job"))) {
    // Send test newsletter
    $oUser = new cApiUser($auth->auth["uid"]);

    // Subnav gets not updated otherwise (no multilink from newsletter_menu)
    $oPage->setSubnav("idnewsletter=" . $idnewsletter, "news");

    // Get test destination
    if ($perm->have_perm_area_action($area, "news_send_test")) {
        $iTestIDNewsGroup = (int) $oUser->getProperty("newsletter", "test_idnewsgrp_lang" . $lang);
//        $iTestIDNewsGroup = 0;
    } else {
        // If user doesn't have the news_send_test right, just send to himself
        $iTestIDNewsGroup = 0;
    }

    // Get encoding
    $oLang = new cApiLanguage($lang);
    $sEncoding = $oLang->get("encoding");
    unset($oLang);

    // Send test newsletter
    $oNewsletter = new Newsletter($idnewsletter);
    $aRecipients = [];

    if ($iTestIDNewsGroup == 0) {
        // Send test newsletter to current user email address
        $sName = $oUser->get("realname") ?? '';
        $sEMail = $oUser->get("email");

        $bSend = $oNewsletter->sendEMail($oClientLang->getProperty("newsletter", "idcatart"), $sEMail, $sName, true, $sEncoding);
        if ($bSend) {
            $aRecipients[] = $sName . " (" . $sEMail . ")";
        } else {
            $aRecipients[] = i18n("None", 'newsletter');
        }
    } else {
        $bSend = $oNewsletter->sendDirect($oClientLang->getProperty("newsletter", "idcatart"), 0, $iTestIDNewsGroup, $aRecipients, $sEncoding);
    }
    unset($oUser);

    if ($bSend) {
        $oPage->displayOk(i18n("Test newsletter has been sent to:", 'newsletter') . "<br />" . implode("<br />", $aRecipients) . "<br />");
    } else {
        $oPage->displayWarning(i18n("Test newsletter has not been sent (partly or completely):", 'newsletter') . "<br />" . i18n("Successful:", 'newsletter') . "<br />" . implode("<br />", $aRecipients) . "<br />" . i18n("Error messages:", 'newsletter') . "<br />" . $oNewsletter->_sError);
    }
} else {
    // No action, just get selected newsletter (if any newsletter was selected)
    if (isset($idnewsletter)) {
        $oNewsletter = new Newsletter($idnewsletter);
    } else {
        $oNewsletter = new Newsletter();
    }
}

if (true === $oNewsletter->isLoaded() && $oNewsletter->get("idclient") == $client && $oNewsletter->get("idlang") == $lang) {
    $requestOptSendTo = $_REQUEST['optSendTo'] ?? '';
    $requestCkbWelcome = cSecurity::toInteger($_REQUEST['ckbWelcome'] ?? '0');
    $requestTxtDispatchCount = cSecurity::toInteger($_REQUEST['txtDispatchCount'] ?? '0');
    $requestTxtDispatchDelay = cSecurity::toInteger($_REQUEST['txtDispatchDelay'] ?? '0');
    $requestSelTemplate = cSecurity::toInteger($_REQUEST['selTemplate'] ?? '0');
    $requestTxtName = $_REQUEST['txtName'] ?? '';
    $requestTxtFromEMail = $_REQUEST['txtFromEMail'] ?? '';
    $requestTxtFromName = $_REQUEST['txtFromName'] ?? '';
    $requestTxtSubject = $_REQUEST['txtSubject'] ?? '';
    $requestSelGroup = $_REQUEST['selGroup'] ?? '';
    $requestCkbCronJob = cSecurity::toInteger($_REQUEST['ckbCronJob'] ?? '0');
    $requestCkbDispatch = cSecurity::toInteger($_REQUEST['ckbDispatch'] ?? '0');
    $requestCkbDefault = cSecurity::toInteger($_REQUEST['ckbSetDefault'] ?? '0');

    // Check and set values
    if ($requestOptSendTo == "") {
        $requestOptSendTo = $oNewsletter->get("send_to");
    }

    if ($requestTxtDispatchCount <= 0) {
        $requestTxtDispatchCount = $oNewsletter->get("dispatch_count");
    }

    // Note, that for DispatchDelay 0 is possible (= send chunks manually)
    if ($requestTxtDispatchDelay < 0) {
        $requestTxtDispatchDelay = $oNewsletter->get("dispatch_delay");
    }

    // Only set template id to 0 if it has been specified (as something not
    // useful).
    // This prevents deleting of the template id, if type setting is changed to
    // "text"
    if ($requestSelTemplate < 0) {
        $requestSelTemplate = 0;
    }

    if ($action == "news_save" && $perm->have_perm_area_action($area, $action)) {
        // Save changes
        $aMessages = [];

        // Changing e.g. \' back to ' (magic_quotes)
        $sName = stripslashes($requestTxtName);
        $sName = conHtmlSpecialChars($sName);
        $sFromEMail = stripslashes($requestTxtFromEMail);
        $sFromName = stripslashes($requestTxtFromName);
        $sSubject = stripslashes($requestTxtSubject);

        if ($oNewsletter->get("name") != $sName || $oNewsletter->get("welcome") != $requestCkbWelcome || $oNewsletter->get("newsfrom") != $sFromEMail) {
            // Only reload, if something visible has changed
            $oPage->reloadLeftBottomFrame(['idnewsletter' => $oNewsletter->get("idnews")]);
        }

        if ($oNewsletter->get("name") != $sName) {
            // Check, if item with same name exists
            $oNewsletters->setWhere("name", $sName);
            $oNewsletters->setWhere("idclient", $client);
            $oNewsletters->setWhere("idlang", $lang);
            $oNewsletters->setWhere($oNewsletter->getPrimaryKeyName(), $oNewsletter->get($oNewsletter->getPrimaryKeyName()), "!=");
            $oNewsletters->query();

            if ($oNewsletters->next()) {
                $aMessages[] = i18n("Could not set new newsletter name: name already exists", 'newsletter');
            } else {
                $oNewsletter->set("name", $sName);
                if ($oNewsletter->get("idart") > 0) {
                    // Update also HTML newsletter article title, if newsletter
                    // name has been changed
                    $oArticles = new cApiArticleLanguageCollection();
                    $oArticles->setWhere("idlang", $lang);
                    $oArticles->setWhere("idart", $oNewsletter->get("idart"));
                    $oArticles->query();

                    if ($oArticle = $oArticles->next()) {
                        $oArticle->set("title", sprintf(i18n("Newsletter: %s", 'newsletter'), $oNewsletter->get("name")));
                        $oArticle->store();
                    }
                    unset($oArticle, $oArticles);
                }
            }
        }
        if ($oClientLang->getProperty("newsletter", "html_newsletter") == "true") {
            $oNewsletter->set("type", $_REQUEST['selType'] ?? '');
        } else {
            $oNewsletter->set("type", "text");
        }
        $oNewsletter->set("newsfrom", $sFromEMail);
        $oNewsletter->set("newsfromname", $sFromName);
        $oNewsletter->set("subject", $sSubject);

        // Options
        $oNewsletter->set("welcome", $requestCkbWelcome);

        // Check out if there are any plugins
        if (cHasPlugins('newsletters')) {
            cCallPluginStore('newsletters');
        }

        // If "selected groups" have been selected and no group specified, set
        // selection to "all"
        if ($requestOptSendTo == "selection" && !is_array($requestSelGroup)) {
            $aMessages[] = i18n("'Send to recipients in selected groups' has been selected, but no group has been specified. Selection has been set to 'Send to all recipients'", 'newsletter');
            $requestOptSendTo = "all";
        }
        $oNewsletter->set("send_to", $requestOptSendTo);
        $oNewsletter->set("send_ids", serialize($requestSelGroup));

        if (getEffectiveSetting("newsletter", "option-cronjob-available", "false") == "true") {
            // Only store changes, if cronjob option is available
            if ($requestCkbCronJob === 1) {
                $oNewsletter->set("use_cronjob", 1);
            } else {
                $oNewsletter->set("use_cronjob", 0);
            }
        }

        if ($requestCkbDispatch === 1) {
            $oNewsletter->set("dispatch", 1);
        } else {
            $oNewsletter->set("dispatch", 0);
        }

        $oNewsletter->set("dispatch_count", $requestTxtDispatchCount);
        $oNewsletter->set("dispatch_delay", $requestTxtDispatchDelay);

        $oNewsletter->store(); // Note, that the properties are stored, anyway

        // Storing from (e-mail), from (name) and options as default
        if ($requestCkbDefault) {
            $oClientLang->setProperty("newsletter", "newsfrom", $sFromEMail);
            $oClientLang->setProperty("newsletter", "newsfromname", $sFromName);
            $oClientLang->setProperty("newsletter", "sendto", $requestOptSendTo);
            $oClientLang->setProperty("newsletter", "sendgroups", serialize($requestSelGroup));
            if ($requestCkbCronJob === 1) {
                $oClientLang->setProperty("newsletter", "use_cronjob", "1");
            } else {
                $oClientLang->setProperty("newsletter", "use_cronjob", "0");
            }
            if ($requestCkbDispatch === 1) {
                $oClientLang->setProperty("newsletter", "dispatch", "1");
            } else {
                $oClientLang->setProperty("newsletter", "dispatch", "0");
            }
            $oClientLang->setProperty("newsletter", "dispatchcount", $requestTxtDispatchCount);
            $oClientLang->setProperty("newsletter", "dispatchdelay", $requestTxtDispatchDelay);
        }

        if (count($aMessages) > 0) {
            $oPage->displayWarning(implode("<br>", $aMessages));
        } else {
            // show message
            $oPage->displayOk(i18n("Saved changes successfully!", 'newsletter'));
        }
    } else {
        $requestSelGroup = unserialize($oNewsletter->get("send_ids"));
        if (!is_array($requestSelGroup)) {
            $requestSelGroup = unserialize($oClientLang->getProperty("newsletter", "sendgroups"));
            if (!is_array($requestSelGroup)) {
                $requestSelGroup = [];
            }
        }

        $requestCkbDispatch = 0;
        if ($oNewsletter->get("dispatch") == 1) {
            $requestCkbDispatch = 1;
        } elseif ($oNewsletter->get("dispatch") == "" && $oClientLang->getProperty("newsletter", "dispatch") == "true") {
            $requestCkbDispatch = 1;
        }
    }

    $oForm = new cGuiTableForm("properties");
    $oForm->setVar("frame", $frame);
    $oForm->setVar("area", $area);
    $oForm->setVar("action", "news_save");
    $oForm->setVar("idnewsletter", $oNewsletter->get("idnews"));

    $oForm->addHeader(i18n("Edit newsletter", 'newsletter'));

    $oTxtName = new cHTMLTextbox("txtName", $oNewsletter->get("name"), 40);
    $oForm->add(i18n("Name", 'newsletter'), $oTxtName->render());

    $oSelType = new cHTMLSelectElement("selType");
    $oSelType->setClass('text_medium');
    $aItems = [];
    $aItems[] = [
        "text",
        i18n("Text only", 'newsletter')
    ];
    if ($oClientLang->getProperty("newsletter", "html_newsletter") == "true") {
        $aItems[] = [
            "html",
            i18n("HTML and text", 'newsletter')
        ];
    } else {
        // Just in case the global setting was switched off
        // TODO: Should this setting be stored?
        $oNewsletter->set("type", "text");
    }
    $oSelType->autoFill($aItems);
    $oSelType->setDefault($oNewsletter->get("type"));

    $oForm->add(i18n("Type", 'newsletter'), $oSelType->render());

    $oTxtFromEMail = new cHTMLTextbox("txtFromEMail", $oNewsletter->get("newsfrom"), 40);
    $oTxtFromName = new cHTMLTextbox("txtFromName", $oNewsletter->get("newsfromname"), 40);
    $oTxtSubject = new cHTMLTextarea("txtSubject", $oNewsletter->get("subject"), 80, 2);

    $oForm->add(i18n("From (E-Mail)", 'newsletter'), $oTxtFromEMail->render());
    $oForm->add(i18n("From (Name)", 'newsletter'), $oTxtFromName->render() . "&nbsp;" . i18n("optional", 'newsletter'));
    $oForm->add(i18n("Subject", 'newsletter'), $oTxtSubject->render());

    // Send options
    $oSendToAll = new cHTMLRadiobutton("optSendTo", "all");
    $oSendToAll->setLabelText("&nbsp;" . i18n("Send newsletter to all recipients", 'newsletter'))
        ->setAttribute("data-action-change", "send_to_change");
    $oSendToDefault = new cHTMLRadiobutton("optSendTo", "default", "optSendTo_default");
    $oSendToDefault->setLabelText("&nbsp;" . i18n("Send newsletter to the members of the default group", 'newsletter'))
        ->setAttribute("data-action-change", "send_to_change");
    $oSendToGroups = new cHTMLRadiobutton("optSendTo", "selection", "optSendTo_selection");
    $oSendToGroups->setLabelText("&nbsp;" . i18n("Send newsletter to the members of the selected group(s):", 'newsletter'))
        ->setAttribute("data-action-change", "send_to_change");

    $oRcpGroups->setWhere("idclient", $client);
    $oRcpGroups->setWhere("idlang", $lang);
    $oRcpGroups->setOrder("defaultgroup DESC, groupname ASC");
    $oRcpGroups->query();

    $aItems = [];
    while ($oRcpGroup = $oRcpGroups->next()) {
        $sGroupName = $oRcpGroup->get("groupname");
        if ($oRcpGroup->get("defaultgroup")) {
            $sGroupName = $sGroupName . "*";
        }
        $aItems[] = [
            $oRcpGroup->get("idnewsgroup"),
            $sGroupName
        ];
    }

    $oSelGroup = new cHTMLSelectElement("selGroup[]", "", "groupselect");
    $oSelGroup->setClass('text_medium');
    $oSelGroup->setSize(10);
    $oSelGroup->setStyle("width: 350px; margin-top: 5px; margin-bottom: 5px; margin-left: 25px;");
    $oSelGroup->setMultiselect();
    $oSelGroup->setAlt(i18n("Note: Hold <Ctrl> to select multiple items.", 'newsletter'));
    $oSelGroup->autoFill($aItems);

    // No groups in the list, sendToGroups and group list-box disabled
    if (count($aItems) == 0) {
        $oSendToGroups->setDisabled(true);
        if ($requestOptSendTo == "selection") {
            $requestOptSendTo = "all";
        }
    } elseif (is_array($requestSelGroup)) {
        $oSelGroup->setSelected($requestSelGroup);
    }

    switch ($requestOptSendTo) {
        case "default":
            $oSendToDefault->setChecked(true);
            $oSelGroup->setDisabled(true);
            break;
        case "selection":
            $oSendToGroups->setChecked(true);
            break;
        default:
            $oSendToAll->setChecked(true);
            $oSelGroup->setDisabled(true);
    }

    // Recipients
    $html = new cHTMLDiv($oSendToAll->toHtml(), 'mgb5') . PHP_EOL;
    $html .= new cHTMLDiv($oSendToDefault->toHtml(), 'mgb5') . PHP_EOL;
    $html .= new cHTMLDiv($oSendToGroups->toHtml(), 'mgb5') . PHP_EOL;
    $html .= $oSelGroup->render();

    $oForm->add(i18n("Recipients", 'newsletter'), $html);

    // Options
    $ckbWelcome = new cHTMLCheckbox("ckbWelcome", "1");
    $ckbWelcome->setLabelText("&nbsp;" . i18n("Welcome-Newsletter", 'newsletter'))
        ->setChecked($oNewsletter->get("welcome"));

    // Generate disabled cronjob element
    // Provide only "Use cronjob" option, if it has been explicitly enabled
    // (and the admin knows, what he is doing - like using a real cronjob, not a
    // simulated one...)
    // Note, that the run_newsletter_job.php file has not been added to the
    // cronjob
    // list in the 'cronjobs' folder - as it may be used, but not via cronjob
    // simulation
    $ckbCronJob = new cHTMLCheckbox("ckbCronJob", "1", "", $oNewsletter->get("use_cronjob"), true);
    $ckbCronJob->setLabelText("&nbsp;" . i18n("Use cronjob", 'newsletter'));

    if (getEffectiveSetting("newsletter", "option-cronjob-available", "false") == "true") {
        // Enable cronjob checkbox
        $ckbCronJob->setDisabled(false);
    } else {
        // Give the user a hint
        $helpBox = new cGuiBackendHelpbox(i18n("Option has to be enabled as client setting - see techref for details", 'newsletter'));
        $ckbCronJob->appendMarkup('&nbsp;&nbsp;' . $helpBox->render());
    }

    $oCkbDispatch = new cHTMLCheckbox("ckbDispatch", "enabled");
    $oCkbDispatch->setLabelText("&nbsp;" . i18n("Send in blocks:", 'newsletter'))
        ->setChecked($oNewsletter->get("dispatch"))
        ->setAttribute("data-action-init", "dispatch_change")
        ->setAttribute("data-action-change", "dispatch_change");

    $oTxtDispatchCount = new cHTMLTextbox("txtDispatchCount", $oNewsletter->get("dispatch_count"), 4);
    $oTxtDispatchDelay = new cHTMLTextbox("txtDispatchDelay", $oNewsletter->get("dispatch_delay"), 4);
    $oTxtDispatchDelay->setAlt(i18n("Note: Set to 0 to send chunks manually.", 'newsletter'));
    $oCkbSaveAsDefault = new cHTMLCheckbox("ckbSetDefault", "1");
    $oCkbSaveAsDefault->setLabelText("&nbsp;" . i18n("Save option settings as default", 'newsletter'));

    $oForm->add(i18n("Options", 'newsletter'),
        new cHTMLDiv($ckbWelcome->toHtml(), 'mgb5')
        . new cHTMLDiv($ckbCronJob->toHtml(), 'mgb5')
        . new cHTMLDiv($oCkbDispatch->toHtml() . "&nbsp;&nbsp;&nbsp;&nbsp;" . i18n("Recipients per block:", 'newsletter')
            . "&nbsp;" . $oTxtDispatchCount->render() . "&nbsp;" . i18n("Delay between blocks:", 'newsletter')
            . "&nbsp;" . $oTxtDispatchDelay->render() . "&nbsp;" . i18n("sec.", 'newsletter') . "<br>", 'mgb5')
        . $oCkbSaveAsDefault->toHtml()
    );

    $oUser = new cApiUser($oNewsletter->get("author"));
    $oForm->add(i18n("Author", 'newsletter'), $oUser->get('username') . " (" . cDate::formatDatetime($oNewsletter->get("created")) . ")");
    $oUser = new cApiUser($oNewsletter->get("modifiedby"));
    $oForm->add(i18n("Last modified by", 'newsletter'), $oUser->get('username') . " (" . cDate::formatDatetime($oNewsletter->get("modified")) . ")");

    $sExecScript = '
    <script type="text/javascript">
    (function(Con, $) {
        function actionDispatchChange($element) {
            $("input[name=\'txtDispatchCount\']").prop("disabled", !$element.prop("checked"));
            $("input[name=\'txtDispatchDelay\']").prop("disabled", !$element.prop("checked"));
        }

        function actionSendToChange($element) {
            $("#groupselect").prop("disabled", $element.val() !== "selection");
        }

        $("form [data-action-init]").each(function(pos, element) {
            var $element = $(element),
                actionInit = $element.data("action-init");
            if (actionInit === "dispatch_change") {
                actionDispatchChange($element);
            }
        });

        $("form [data-action-change]").live("change", function () {
            var $element = $(this),
                action = $element.data("action-change");
            if (action === "send_to_change") {
                actionSendToChange($element);
            } else if (action === "dispatch_change") {
                actionDispatchChange($element);
            }
        });
    })(Con, Con.$);
    </script>';
    $oPage->setContent([$oForm, $sExecScript]);
} else {
    $oPage->setContent("");
}

$oPage->render();
