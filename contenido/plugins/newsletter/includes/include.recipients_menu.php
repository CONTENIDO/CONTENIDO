<?php
/**
 * This file contains the Recipient user list.
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

// ################################
// Initialization
// ################################
$oPage = new cGuiPage("recipients_menu", "newsletter");
$oMenu = new cGuiMenu();
$oClient = new cApiClient($client);
$oUser = new cApiUser($auth->auth["uid"]);
// $sLocation = $sess->url("main.php?area=$area&frame=$frame");

// Specify fields for search, sort and validation. Design makes enhancements
// using plugins possible (currently not implemented). If you are changing
// things here,
// remember to update include.newsletter_left_top.php, also.
// field: Field name in the db
// caption: Shown field name (-> user)
// base: Elements from core code (other type may be: "plugin")
// sort: Element can be used to be sorted by
// search: Element can be used to search in
$aFields = array();
$aFields["name"] = array(
    "field" => "name",
    "caption" => i18n("Name", 'newsletter'),
    "type" => "base,sort,search"
);
$aFields["email"] = array(
    "field" => "email",
    "caption" => i18n("E-Mail", 'newsletter'),
    "type" => "base,sort,search"
);
$aFields["confirmed"] = array(
    "field" => "confirmed",
    "caption" => i18n("Confirmed", 'newsletter'),
    "type" => "base"
);
$aFields["deactivated"] = array(
    "field" => "deactivated",
    "caption" => i18n("Deactivated", 'newsletter'),
    "type" => "base"
);

// ################################
// Store settings
// ################################
// Update purgetimeframe if submitted
// $sRefreshTop = '';
$iTimeframe = $oClient->getProperty("newsletter", "purgetimeframe");
if (isset($_REQUEST["txtPurgeTimeframe"]) && $_REQUEST["txtPurgeTimeframe"] > 0 && $_REQUEST["txtPurgeTimeframe"] != $iTimeframe && $perm->have_perm_area_action($area, "recipients_delete")) {
    $oClient->setProperty("newsletter", "purgetimeframe", $_REQUEST["txtPurgeTimeframe"]);
    // $sRefreshTop = '<script
    // type="text/javascript">Con.getFrame('left_top').purgetimeframe =
    // '.$_REQUEST["txtPurgeTimeframe"].'</script>';
}

// ################################
// Check external input
// ################################
// Items per page (value stored per area in user property)
if (!isset($_REQUEST["elemperpage"]) || !is_numeric($_REQUEST["elemperpage"]) || $_REQUEST["elemperpage"] < 0) {
    $_REQUEST["elemperpage"] = $oUser->getProperty("itemsperpage", $area);
}
if (!is_numeric($_REQUEST["elemperpage"])) {
    // This is the case, if the user property has never been set (first time
    // user)
    $_REQUEST["elemperpage"] = 25;
}
if ($_REQUEST["elemperpage"] > 0) {
    // -- All -- will not be stored, as it may be impossible to change this back
    // to something more useful
    $oUser->setProperty("itemsperpage", $area, $_REQUEST["elemperpage"]);
}

$_REQUEST["restrictgroup"] = (int) $_REQUEST["restrictgroup"];
if ($_REQUEST["restrictgroup"] == 0) {
    $_REQUEST["restrictgroup"] = "--all--";
}
$_REQUEST["page"] = (int) $_REQUEST["page"];
if ($_REQUEST["page"] <= 0 || $_REQUEST["elemperpage"] == 0) {
    $_REQUEST["page"] = 1;
}
// Sort order
if ($_REQUEST["sortorder"] != "DESC") {
    $_REQUEST["sortorder"] = "ASC";
}

// Check sort by and search in criteria
$bSortByFound = false;
$bSearchInFound = false;
foreach ($aFields as $sKey => $aData) {
    if ($aData["field"] == $_REQUEST["sortby"] && strpos($aData["type"], "sort") !== false) {
        $bSortByFound = true;
    }
    if ($aData["field"] == $_REQUEST["searchin"] && strpos($aData["type"], "search") !== false) {
        $bSearchInFound = true;
    }
}

if (!$bSortByFound) {
    $_REQUEST["sortby"] = "name"; // Default sort by field, possible values see
                                      // above
}
if (!$bSearchInFound) {
    $_REQUEST["searchin"] = "--all--";
}

// Free memory
unset($oUser);
unset($oClient);

// ################################
// Get data
// ################################
$oRecipients = new NewsletterRecipientCollection();

// Updating keys, if activated; all recipients of all clients!
if (getSystemProperty("newsletter", "updatekeys")) {
    $iUpdatedRecipients = $oRecipients->updateKeys();
    $oPage->displayInfo(sprintf(i18n("%d recipients, with no or incompatible key has been updated. Deactivate update function.", 'newsletter'), $iUpdatedRecipients));
}

$oRecipients->setWhere("idclient", $client);
$oRecipients->setWhere("idlang", $lang);

// sort by and sort order
$oRecipients->setOrder($_REQUEST["sortby"] . " " . $_REQUEST["sortorder"]);

// Show group
if ($_REQUEST["restrictgroup"] != "--all--") {
    $oRecipients->link("RecipientGroupMemberCollection");
    $oRecipients->setWhere("idnewsgroup", $_REQUEST["restrictgroup"]);
}

// Search for
if ($_REQUEST["filter"] != "") {
    if ($_REQUEST["searchin"] == "--all--" || $_REQUEST["searchin"] == "") {
        foreach ($aFields as $sKey => $aData) {
            if (strpos($aData["type"], "search") !== false) {
                $oRecipients->setWhereGroup("filter", $aData["field"], $_REQUEST["filter"], "LIKE");
            }
        }
        $oRecipients->setInnerGroupCondition("filter", "OR");
    } else {
        $oRecipients->setWhere($_REQUEST["searchin"], $_REQUEST["filter"], "LIKE");
    }
}

// Items / page
if ($_REQUEST["elemperpage"] > 0) {
    // Getting item count without limit (for page function) - better idea anyone
    // (performance)?
    $oRecipients->query();
    $iItemCount = $oRecipients->count();

    if ($_REQUEST["elemperpage"] * ($_REQUEST["page"]) >= $iItemCount + $_REQUEST["elemperpage"] && $_REQUEST["page"] != 1) {
        $_REQUEST["page"]--;
    }

    $oRecipients->setLimit($_REQUEST["elemperpage"] * ($_REQUEST["page"] - 1), $_REQUEST["elemperpage"]);
} else {
    $iItemCount = 0;
}

$oRecipients->query();

// Output data
$oMenu = new cGuiMenu();
$iMenu = 0;

// Store messages for repeated use (speeds performance, as i18n translation is
// only needed once)
$aMsg = array();
$aMsg["DelTitle"] = i18n("Delete recipient", 'newsletter');
$aMsg["DelDescr"] = i18n("Do you really want to delete the following recipient:<br>", 'newsletter');

while ($oRecipient = $oRecipients->next()) {
    $iMenu++;
    $idnewsrcp = $oRecipient->get("idnewsrcp");

    $sName = $oRecipient->get("name");
    if (empty($sName)) {
        $sName = $oRecipient->get("email");
    }

    $oLnk = new cHTMLLink();
    $oLnk->setMultiLink($area, "", $area, "");
    $oLnk->setCustom("idrecipient", $idnewsrcp);

    if ($oRecipient->get("deactivated") == 1 || $oRecipient->get("confirmed") == 0) {
        $oLnk->updateAttributes(array(
            "style" => "color:#A20000"
        ));
    }

    $oMenu->setTitle($iMenu, $sName);
    $oMenu->setLink($iMenu, $oLnk);

    if ($perm->have_perm_area_action("recipients", "recipients_delete")) {
        $oMenu->setActions($iMenu, "delete", '<a title="' . $aMsg["DelTitle"] . '" href="javascript://" onclick="showDelMsg(' . $idnewsrcp . ',\'' . addslashes($sName) . '\')"><img src="' . $cfg['path']['images'] . 'delete.gif" border="0" title="' . $aMsg["DelTitle"] . '" alt="' . $aMsg["DelTitle"] . '"></a>');
    }
}

$sExecScript = <<<JS
<script type="text/javascript">
function showDelMsg(lngId, strElement) {
    Con.showConfirmation("{$aMsg["DelDescr"]}<b>" + strElement + "</b>", function() {
        deleteRecipient(lngId);
    });
}

// Function for deleting recipients
function deleteRecipient(idrecipient) {
    var oForm = Con.getFrame("left_top").document.getElementById("options");

    var url = "main.php?area=recipients";
    url += "&action=recipients_delete";
    url += "&frame=4";
    url += "&idrecipient=" + idrecipient;
    url += "&contenido=" + Con.sid;
    url += get_registered_parameters();
    url += "&restrictgroup=" + oForm.restrictgroup.value;
    url += "&sortby=" + oForm.sortby.value;
    url += "&sortorder=" + oForm.sortorder.value;
    url += "&filter=" + oForm.filter.value;
    url += "&elemperpage=" + oForm.elemperpage.value;

    Con.getFrame("right_bottom").location.href = url;
}
</script>
JS;

$oPage->addScript($sExecScript);
$oPage->addScript('parameterCollector.js');
// $oPage->addScript('refreshTop', $sRefreshTop);

// generate current content for Object Pager�
$sPagerId = '0ed6d632-6adf-4f09-a0c6-1e38ab60e304';
$oPagerLink = new cHTMLLink();
$oPagerLink->setLink("main.php");
$oPagerLink->setTargetFrame('left_bottom');
$oPagerLink->setCustom("elemperpage", $_REQUEST["elemperpage"]);
$oPagerLink->setCustom("filter", $_REQUEST["filter"]);
$oPagerLink->setCustom("restrictgroup", $_REQUEST["restrictgroup"]);
$oPagerLink->setCustom("sortby", $_REQUEST["sortby"]);
$oPagerLink->setCustom("sortorder", $_REQUEST["sortorder"]);
$oPagerLink->setCustom("searchin", $_REQUEST["searchin"]);
$oPagerLink->setCustom("frame", $frame);
$oPagerLink->setCustom("area", $area);
$oPagerLink->enableAutomaticParameterAppend();
$oPagerLink->setCustom("contenido", $sess->id);
// Note, that after the "page" parameter no "pagerlink" parameter is specified -
// it is not used, as the JS below only uses the INNER html and the "pagerlink"
// parameter is
// set by ...left_top.html for the foldingrow itself
$oPager = new cGuiObjectPager($sPagerId, $iItemCount, $_REQUEST["elemperpage"], $_REQUEST["page"], $oPagerLink, "page");

// add slashes, to insert in javascript
$sPagerContent = $oPager->render(1);
$sPagerContent = str_replace('\\', '\\\\', $sPagerContent);
$sPagerContent = str_replace('\'', '\\\'', $sPagerContent);

// Send new object pager to left_top
$oPage->addScript('setPager.js');

$sRefreshPager = <<<JS
<script type="text/javascript">
var sNavigation = '{$sPagerContent}';
// Activate time to refresh pager folding row in left top
var oTimer = window.setInterval(function() {
    fncSetPager('{$sPagerId}', '{$_REQUEST["page"]}');
}, 200);
</script>
JS;

$oPage->addScript($sRefreshPager);

$oPage->setContent($oMenu);
$oPage->render();
