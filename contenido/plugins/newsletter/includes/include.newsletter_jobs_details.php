<?php
/**
 * This file contains the job details.
 *
 * @package Plugin
 * @subpackage Newsletter
 * @version SVN Revision $Rev:$
 *
 * @author Bjoern Behrens
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$backendUrl = cRegistry::getBackendUrl();

// Initialization
$oPage = new cGuiPage("newsletter_job_details", "newsletter");

if ($action == "news_job_run" && $perm->have_perm_area_action($area, $action) && is_numeric($_REQUEST["idnewsjob"])) {
    // Run job
    $oJob = new NewsletterJob($_REQUEST["idnewsjob"]);
    $iSendCount = $oJob->runJob();

    if ($oJob->get("dispatch") == 1 && $oJob->get("sendcount") < $oJob->get("rcpcount")) {
        // Send in chunks
        $sPathNext = $sess->url("main.php?area=$area&action=news_job_run&frame=4&idnewsjob=" . $_REQUEST["idnewsjob"]);

        // Calculating some statistics
        $iChunk = ceil($oJob->get("sendcount") / $oJob->get("dispatch_count"));
        $iChunks = ceil($oJob->get("rcpcount") / $oJob->get("dispatch_count"));

        // Dispatch count > send/recipient count, set values to 1, at least
        if ($iChunk == 0) {
            $iChunk = 1;
        }
        if ($iChunks == 0) {
            $iChunks = 1;
        }

        if ($oJob->get("dispatch_delay") == 0) {
            // Send manually
            $oForm = new cGuiTableForm("properties", $sPathNext);
            $oForm->addHeader(i18n("Report:", 'newsletter'));
            $oForm->add("", "");

            $oForm->add("", sprintf(i18n("Sending newsletter ... (chunk %s of %s, recipients: %s, sent: %s)", 'newsletter'), $iChunk, $iChunks, $oJob->get("rcpcount"), $oJob->get("sendcount")));

            $oForm->setActionButton("cancel", $backendUrl . "images/but_cancel.gif", i18n("Stop sending", 'newsletter'), "c");
            $oForm->setActionButton("submit", $backendUrl . "images/but_ok.gif", i18n("Send next chunk", 'newsletter'), "s", "news_job_run");
        } else {
            // Send automatically
            $oForm = new cGuiTableForm("properties");
            $oForm->addHeader(i18n("Report:", 'newsletter'));
            $oForm->add("", "");

            $oForm->add("", sprintf(i18n("Sending newsletter ... (chunk %s of %s, recipients: %s, sent: %s)", 'newsletter'), $iChunk, $iChunks, $oJob->get("rcpcount"), $oJob->get("sendcount")));

            $oPage->addMeta(array(
                'http-equiv' => 'refresh',
                'content' => $oJob->get("dispatch_delay") . '; URL=' . $sPathNext
            ));
            $oForm->unsetActionButton("submit");
            $oForm->setActionButton("cancel", $backendUrl . "images/but_cancel.gif", i18n("Stop sending", 'newsletter'), "c");
        }
    } else {
        // All newsletters should have been sent
        $oForm = new cGuiTableForm("properties");
        $oForm->addHeader(i18n("Report:", 'newsletter'));
        $oForm->add("", "");

        $oForm->add("", sprintf(i18n("The newsletter has been sent to %s recipients", 'newsletter'), $oJob->get("sendcount")));
        $oPage->setReload();
    }

    $oPage->setContent($notis . $oForm->render(true));
} elseif ($action == "news_job_delete" && $perm->have_perm_area_action($area, $action) && is_numeric($_REQUEST["idnewsjob"])) {
    $oJobs = new NewsletterJobCollection();
    $oJobs->delete($_REQUEST["idnewsjob"]);

    $oPage->setSubnav("blank", "news_jobs");
    $oPage->setReload();
    $oPage->setContent($notis);
} elseif ($action == "news_job_details" || $action == "news_job_detail_delete") {

    // Show job details (recipients)

    $oLogs = new NewsletterLogCollection();

    // Remove recipient from a job
    if ($action == "news_job_detail_delete" && is_numeric($_REQUEST["idnewslog"]) && $perm->have_perm_area_action($area, "news_job_detail_delete")) {
        $oLogs->delete($_REQUEST["idnewslog"]);
    }

    // Initialize
    $iNextPage = cSecurity::toInteger($_GET['nextpage']);
    if ($iNextPage <= 0) {
        $iNextPage = 1;
    }

    if ($_REQUEST["sortmode"] !== "DESC") {
        $_REQUEST["sortmode"] = "ASC";
    }

    $sDateFormat = getEffectiveSetting("dateformat", "full", "d.m.Y H:i");

    // Set default values
    $oUser = new cApiUser($auth->auth["uid"]);
    if (!isset($_REQUEST["elemperpage"]) || !is_numeric($_REQUEST["elemperpage"]) || $_REQUEST["elemperpage"] < 0) {
        $_REQUEST["elemperpage"] = $oUser->getProperty("itemsperpage", $area . "_job_details");
    }
    if (!is_numeric($_REQUEST["elemperpage"])) {
        $_REQUEST["elemperpage"] = 50;
    }
    if ($_REQUEST["elemperpage"] > 0) {
        // - All - will not be saved
        $oUser->setProperty("itemsperpage", $area . "_job_details", $_REQUEST["elemperpage"]);
    }

    $oFrmOptions = new cGuiTableForm("frmOptions");
    $oFrmOptions->setVar("contenido", $sess->id);
    $oFrmOptions->setVar("area", $area);
    $oFrmOptions->setVar("action", $action);
    $oFrmOptions->setVar("frame", $frame);
    $oFrmOptions->setVar("sortmode", $_REQUEST["sortmode"]);
    $oFrmOptions->setVar("sortby", $_REQUEST["sortby"]);
    $oFrmOptions->setVar("idnewsjob", $_REQUEST["idnewsjob"]);
    // $oFrmOptions->setVar("startpage", $startpage);
    // $oFrmOptions->setVar("appendparameters", $appendparameters);
    $oFrmOptions->addHeader(i18n("List options", 'newsletter'));

    $oSelElements = new cHTMLSelectElement("elemperpage");
    $oSelElements->setEvent("onchange", "document.forms.frmOptions.submit();");

    $aData = array(
        "0" => i18n("-All-", 'newsletter'),
        "50" => "50",
        "100" => "100",
        "250" => "250",
        "500" => "500"
    );
    $oSelElements->autoFill($aData);

    $oSelElements->setDefault($_REQUEST["elemperpage"]);

    // $oSelElements->setStyle('border:1px;border-style:solid;border-color:black;');
    $oFrmOptions->add(i18n("Items per page:", 'newsletter'), $oSelElements->render());

    // Ouput data
    $oList = new cGuiScrollList(true, "news_job_details");
    $oList->setCustom("idnewsjob", $_REQUEST["idnewsjob"]);
    $oList->setCustom("nextpage", $iNextPage);
    $oList->setCustom("elemperpage", $_REQUEST["elemperpage"]);

    $aCols = array(
        "rcpname",
        "rcpemail",
        "",
        "status",
        "sent"
    );
    $oList->setHeader(i18n("Recipient", 'newsletter'), i18n("E-Mail", 'newsletter'), i18n("Type", 'newsletter'), i18n("Status", 'newsletter'), i18n("Sent", 'newsletter'), i18n("Actions", 'newsletter'));
    $oList->setSortable(0, true);
    $oList->setSortable(1, true);
    $oList->setSortable(2, false);
    $oList->setSortable(3, true);
    $oList->setSortable(4, true);

    // Get data
    $oLogs->resetQuery();
    $oLogs->setWhere("idnewsjob", $_REQUEST["idnewsjob"]);

    $sBrowseLinks = "1";
    if ($_REQUEST["elemperpage"] > 0) {
        // First, get total data count
        $oLogs->query();
        $iRecipients = $oLogs->count(); // Getting item count without limit (for
                                        // page function) - better idea anybody
                                        // (performance)?

        if ($iRecipients > 0 && $iRecipients > $_REQUEST["elemperpage"]) {
            $sBrowseLinks = "";
            for ($i = 1; $i <= ceil($iRecipients / $_REQUEST["elemperpage"]); $i++) {
                // $iNext = (($i - 1) * $_REQUEST["elemperpage"]) + 1;
                if ($sBrowseLinks !== "") {
                    $sBrowseLinks .= "&nbsp;";
                }
                if ($iNextPage == $i) {
                    $sBrowseLinks .= $i . "\n"; // I'm on the current page, no
                                              // link
                } else {
                    $sBrowseLinks .= '<a href="' . $sess->url("main.php?area=$area&action=$action&frame=$frame&idnewsjob=" . $_REQUEST["idnewsjob"] . "&nextpage=$i&sortmode=" . $_REQUEST["sortmode"] . "&sortby=" . $_REQUEST["sortby"]) . '">' . $i . '</a>' . "\n";
                }
            }
        }

        $oLogs->setLimit($_REQUEST["elemperpage"] * ($iNextPage - 1), $_REQUEST["elemperpage"]);
    }

    if (!array_key_exists($_REQUEST["sortby"], $aCols)) {
        $_REQUEST["sortby"] = 0; // Sort by rcpname by default
    }
    $oLogs->setOrder($aCols[$_REQUEST["sortby"]] . " " . $_REQUEST["sortmode"]);
    $oLogs->query();

    $oImgDelete = new cHTMLImage("images/delete.gif");
    $oImgDelete->setAlt(i18n("Delete item", 'newsletter'));
    $sImgDelete = $oImgDelete->render();
    unset($oImgDelete);

    $iCount = 0;
    $aNewsType[] = array(); // Performance
    $aNewsType[0] = i18n("Text only", 'newsletter');
    $aNewsType[1] = i18n("HTML/Text", 'newsletter');
    while ($oLog = $oLogs->next()) {

        $sName = $oLog->get("rcpname");
        $sEMail = $oLog->get("rcpemail");

        switch ($oLog->get("status")) {
            case "pending":
                $sStatus = i18n("Waiting for sending", 'newsletter');
                break;
            case "sending":
                $sStatus = i18n("Sending", 'newsletter');
                break;
            case "successful":
                $sStatus = i18n("Successful", 'newsletter');
                break;
            default:
                $sStatus = sprintf(i18n("Error: %s", 'newsletter'), $oLog->get("status"));
        }

        if ($oLog->get("sent") == "0000-00-00 00:00:00") {
            $sSent = "-";
        } else {
            $sSent = date($sDateFormat, strtotime($oLog->get("sent")));
        }

        $sLnkRemove = "";
        if ($oLog->get("status") == "pending" && $perm->have_perm_area_action($area, "news_job_detail_delete")) {
            $oLnkRemove = new cHTMLLink();
            $oLnkRemove->setCLink("news_jobs", 4, "news_job_detail_delete");
            $oLnkRemove->setCustom("idnewsjob", $_REQUEST["idnewsjob"]);
            $oLnkRemove->setCustom("idnewslog", $oLog->get($oLog->primaryKey));
            $oLnkRemove->setCustom("sortby", $_REQUEST["sortby"]);
            $oLnkRemove->setCustom("sortmode", $_REQUEST["sortmode"]);
            $oLnkRemove->setContent($sImgDelete);

            $sLnkRemove = $oLnkRemove->render();
        }

        $oList->setData($iCount, $sName, $sEMail, $aNewsType[$oLog->get("rcpnewstype")], $sStatus, $sSent, $sLnkRemove);

        $iCount++;
    }

    // A little bit senseless, as the data is already sorted, but
    // we need the sortmode in the header link
    $oList->sort($_REQUEST["sortby"], $_REQUEST["sortmode"]);

    // HerrB: Hardcore UI for browsing elements ... sorry
    $sBrowseHTML = '<table class="generic" width="100%" cellspacing="0" cellpadding="2" border="0">
    <tr>
        <td><img src="images/spacer.gif" alt="" width="1" height="10"></td>
    </tr>
    <tr class="text_medium">
        <td> ' . sprintf(i18n("Go to page: %s", 'newsletter'), $sBrowseLinks) . '</td>
    </tr>
</table>';

    $oPage->setContent($oFrmOptions->render(false) . "<br>" . $oList->render(false) . $sBrowseHTML);
} else {
    // Just show the job data
    $oJob = new NewsletterJob($_REQUEST["idnewsjob"]);

    $oForm = new cGuiTableForm("properties");
    $oForm->setVar("frame", $frame);
    $oForm->setVar("area", $area);
    $oForm->setVar("action", "");
    $oForm->setVar("idnewsjob", $idnewsjob);

    $oForm->addHeader(i18n("Newsletter dispatch job", 'newsletter'));

    $oForm->add(i18n("Name", 'newsletter'), $oJob->get("name"));

    $sDateFormat = getEffectiveSetting("dateformat", "full", "d.m.Y H:i");
    switch ($oJob->get("status")) {
        case 1:
            $oForm->add(i18n("Status", 'newsletter'), i18n("Pending", 'newsletter'));
            break;
        case 2:
            $oForm->add(i18n("Status", 'newsletter'), sprintf(i18n("Sending (started: %s)", 'newsletter'), date($sDateFormat, strtotime($oJob->get("started")))));
            break;
        case 9:
            $oForm->add(i18n("Status", 'newsletter'), sprintf(i18n("Finished (started: %s, finished: %s)", 'newsletter'), date($sDateFormat, strtotime($oJob->get("started"))), date($sDateFormat, strtotime($oJob->get("finished")))));
            break;
    }

    $oForm->add(i18n("Statistics", 'newsletter'), sprintf(i18n("Planned: %s, Send: %s", 'newsletter'), $oJob->get("rcpcount"), $oJob->get("sendcount")));
    $oForm->add(i18n("From", 'newsletter'), $oJob->get("newsfrom") . " (" . $oJob->get("newsfromname") . ")");
    $oForm->add(i18n("Subject", 'newsletter'), $oJob->get("subject"));

    if ($oJob->get("type") == "html") {
        $oForm->add(i18n("Type", 'newsletter'), i18n("HTML and text"));

        $txtMessageHTML = new cHTMLTextarea("txtMessageHTML", $oJob->get("message_html"), 80, 20);
        $txtMessageHTML->setDisabled("disabled");

        $oForm->add(i18n("HTML Message", 'newsletter'), $txtMessageHTML->render());
    } else {
        $oForm->add(i18n("Type", 'newsletter'), i18n("Text only", 'newsletter'));
    }
    $txtMessageText = new cHTMLTextarea("txtMessageText", $oJob->get("message_text"), 80, 20);
    $txtMessageText->setDisabled("disabled");

    $oForm->add(i18n("Text Message", 'newsletter'), $txtMessageText->render());

    $aSendTo = unserialize($oJob->get("send_to"));
    switch ($aSendTo[0]) {
        case "all":
            $sSendToInfo = i18n("Send newsletter to all recipients", 'newsletter');
            break;
        case "default":
            $sSendToInfo = i18n("Send newsletter to the members of the default group", 'newsletter');
            break;
        case "selection":
            $sSendToInfo = i18n("Send newsletter to the members of the selected group(s):", 'newsletter');

            unset($aSendTo[0]);
            foreach ($aSendTo as $sGroup) {
                $sSendToInfo .= "<br>" . $sGroup;
            }
            break;
        case "single":
            $sSendToInfo = i18n("Send newsletter to single recipient:", 'newsletter');
            $sSendToInfo .= "<br>" . $aSendTo[1] . " (" . $aSendTo[2] . ")";
            break;
        default:
    }
    unset($aSendTo);

    $oForm->add(i18n("Recipients", 'newsletter'), $sSendToInfo);

    if ($oJob->get("use_cronjob") == 1) {
        $sOptionsInfo = i18n("Use cronjob: Enabled", 'newsletter');
    } else {
        $sOptionsInfo = i18n("Use cronjob: Not enabled", 'newsletter');
    }

    if ($oJob->get("dispatch")) {
        $sOptionsInfo .= "<br>" . sprintf(i18n("Dispatch: Enabled (block size: %s, delay: %s sec.)", 'newsletter'), $oJob->get("dispatch_count"), $oJob->get("dispatch_delay"));
    } else {
        $sOptionsInfo .= "<br>" . i18n("Dispatch: Disabled", 'newsletter');
    }

    $oForm->add(i18n("Options", 'newsletter'), $sOptionsInfo);
    $oForm->add(i18n("Author", 'newsletter'), $oJob->get("authorname"));
    $oForm->add(i18n("Created", 'newsletter'), $oJob->get("created"));

    // Just remove the "save changes" message (as it is not possible to remove
    // the image completely in ui_table_form)
    $oForm->setActionButton("submit", $backendUrl . "images/but_ok.gif", "", "s");

    $oPage->setContent($oForm->render(false));
}

$oPage->render();

?>