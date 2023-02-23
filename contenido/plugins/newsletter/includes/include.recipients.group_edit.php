<?php
/**
 * This file contains the Recipient group editor.
 *
 * @package Plugin
 * @subpackage Newsletter
 * @author Bjoern Behrens
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var cAuth $auth
 * @var cPermission $perm
 * @var cSession $sess
 * @var array $cfg
 * @var string $area
 * @var int $client
 * @var int $lang
 * @var int $frame
 */


// Initialization
$oPage = new cGuiPage("recipients.group_edit", "newsletter");
$oRGroups = new NewsletterRecipientGroupCollection();
$oRGroupMembers = new NewsletterRecipientGroupMemberCollection();
$oRGroup = new NewsletterRecipientGroup();

$action = $action ?? '';

$aFields = [
    "name" => [
        "field" => "name",
        "caption" => i18n("Name", 'newsletter'),
        "type" => "base,sort,search"
    ],
    "email" => [
        "field" => "email",
        "caption" => i18n("E-Mail", 'newsletter'),
        "type" => "base,sort,search"
    ],
    "confirmed" => [
        "field" => "confirmed",
        "caption" => i18n("Confirmed", 'newsletter'),
        "type" => "base"
    ],
    "deactivated" => [
        "field" => "deactivated",
        "caption" => i18n("Deactivated", 'newsletter'),
        "type" => "base"
    ],
];

$requestIdRecipientGroup = cSecurity::toInteger($_REQUEST['idrecipientgroup'] ?? '0');

if ($action == "recipientgroup_create" && $perm->have_perm_area_action($area, $action)) {
    $oRGroup = $oRGroups->create(" " . i18n("-- New group --", 'newsletter'));
    $requestIdRecipientGroup = $oRGroup->get("idnewsgroup");
    $oPage->reloadLeftBottomFrame(['idrecipientgroup' => $oRGroup->get("idnewsgroup")]);
    $sRefreshLeftTopScript = '<script type="text/javascript">Con.getFrame("left_top").refreshGroupOption(\'' . $requestIdRecipientGroup . '\', \'add\')</script>';
    $oPage->addScript($sRefreshLeftTopScript);
} elseif ($action == "recipientgroup_delete" && $perm->have_perm_area_action($area, $action)) {
    $oRGroups->delete($requestIdRecipientGroup);
    $sRefreshLeftTopScript = '<script type="text/javascript">Con.getFrame("left_top").refreshGroupOption(\'' . $requestIdRecipientGroup . '\', \'remove\')</script>';
    $oPage->addScript($sRefreshLeftTopScript);

    $requestIdRecipientGroup = 0;
    $oRGroup = new NewsletterRecipientGroup();
    $oPage->reloadLeftBottomFrame(['idrecipientgroup' => null]);
} else {
    $oRGroup->loadByPrimaryKey($requestIdRecipientGroup);
}

if (true === $oRGroup->isLoaded() && $oRGroup->get("idclient") == $client && $oRGroup->get("idlang") == $lang) {
    $aMessages = [];

    if ($action == "recipientgroup_save_group" && $perm->have_perm_area_action($area, $action)) {
        // Saving changes
        $bReload = false;

        $requestGroupName = $_REQUEST['groupname'] ?? '';
        if ($oRGroup->get("groupname") != $requestGroupName) {
            $oRGroups->resetQuery();
            $oRGroups->setWhere("groupname", stripslashes($requestGroupName));
            $oRGroups->setWhere("idclient", $client);
            $oRGroups->setWhere("idlang", $lang);
            $oRGroups->setWhere($oRGroup->getPrimaryKeyName(), $oRGroup->get($oRGroup->getPrimaryKeyName()), "!=");
            $oRGroups->query();

            if ($oRGroups->next()) {
                $aMessages[] = i18n("Could not set new group name: Group already exists", 'newsletter');
            } else {
                $bReload = true;

                $oRGroup->set("groupname", $requestGroupName);
            }
        }

        $requestAddUser = isset($_REQUEST['adduser']) && is_array($_REQUEST['adduser']) ? $_REQUEST['adduser'] : [];
        if (count($requestAddUser) > 0) {
            foreach ($requestAddUser as $iRcpID) {
                if (is_numeric($iRcpID)) {
                    $oRGroupMembers->create($requestIdRecipientGroup, $iRcpID);
                }
            }
        }

        $requestDefaultGroup = cSecurity::toInteger($_REQUEST['defaultgroup'] ?? '0');
        if ($oRGroup->get("defaultgroup") != $requestDefaultGroup) {
            $bReload = true;
            $oRGroup->set("defaultgroup", $requestDefaultGroup);
        }

        $oRGroup->store();

        if ($bReload) {
            $oPage->reloadLeftBottomFrame(['idrecipientgroup' => $oRGroup->get('idnewsgroup')]);
        }

        // Removing users from group (if specified)
        $requestDelUser = isset($_REQUEST['deluser']) && is_array($_REQUEST['deluser']) ? $_REQUEST['deluser'] : null;
        if ($perm->have_perm_area_action($area, "recipientgroup_recipient_delete") && is_array($requestDelUser)) {
            foreach ($requestDelUser as $iRcpID) {
                if (is_numeric($iRcpID)) {
                    $oRGroupMembers->remove($requestIdRecipientGroup, $iRcpID);
                }
            }
        }

        $sRefreshLeftTopScript = '<script type="text/javascript">Con.getFrame("left_top").refreshGroupOption(\'' . $requestIdRecipientGroup . '\', \'remove\');
                                    Con.getFrame("left_top").refreshGroupOption(\'' . $requestIdRecipientGroup . '\', \'add\', \'' . $requestGroupName . '\');</script>';
        $oPage->addScript($sRefreshLeftTopScript);
    }

    if (count($aMessages) > 0) {
        $oPage->displayWarning(implode("<br>", $aMessages)) . "<br>";
    }

    // Set default values
    $requestMemberElemPerPage = $_REQUEST['member_elemperpage'] ?? '';
    $requestOutsiderElemPerPage = $_REQUEST['outsider_elemperpage'] ?? '';
    $requestMemberPage = cSecurity::toInteger($_REQUEST['member_page'] ?? '0');
    $requestOutsiderPage = cSecurity::toInteger($_REQUEST['outsider_page'] ?? '0');
    $requestMemberSortOrder = !isset($_REQUEST['member_sortorder']) || $_REQUEST['member_sortorder'] !== 'ASC' ? 'ASC' : 'DESC';
    $requestOutsiderSortOrder = !isset($_REQUEST['outsider_sortorder']) || $_REQUEST['outsider_sortorder'] !== 'ASC' ? 'ASC' : 'DESC';
    $requestMemberFilter = $_REQUEST['member_filter'] ?? '';
    $requestOutsiderFilter = $_REQUEST['outsider_filter'] ?? '';
    $requestMemberSortBy = $_REQUEST['member_sortby'] ?? '';
    $requestOutsiderSortBy = $_REQUEST['outsider_sortby'] ?? '';
    $requestMemberSearchIn = $_REQUEST['member_searchin'] ?? '';
    $requestOutsiderSearchIn = $_REQUEST['outsider_searchin'] ?? '';

    $oUser = new cApiUser($auth->auth["uid"]);
    if (!is_numeric($requestMemberElemPerPage) || $requestMemberElemPerPage < 0) {
        $requestMemberElemPerPage = $oUser->getProperty("itemsperpage", $area . "_edit_member");
    }
    if (!is_numeric($requestMemberElemPerPage)) {
        $requestMemberElemPerPage = 25;
    }
    if ($requestMemberElemPerPage > 0) {
        // -- All -- will not be stored, as it may be impossible to change this
        // back to something more useful
        $oUser->setProperty("itemsperpage", $area . "_edit_member", $requestMemberElemPerPage);
    }

    if (!is_numeric($requestOutsiderElemPerPage) || $requestOutsiderElemPerPage < 0) {
        $requestOutsiderElemPerPage = $oUser->getProperty("itemsperpage", $area . "_edit_outsider");
    }
    if (!is_numeric($requestOutsiderElemPerPage)) {
        $requestOutsiderElemPerPage = 25;
    }
    if ($requestOutsiderElemPerPage > 0) {
        // -- All -- will not be stored, as it may be impossible to change this
        // back to something more useful
        $oUser->setProperty("itemsperpage", $area . "_edit_outsider", $requestOutsiderElemPerPage);
    }
    unset($oUser);

    if ($requestMemberPage <= 0 || $requestMemberElemPerPage == 0) {
        $requestMemberPage = 1;
    }

    if ($requestOutsiderPage <= 0 || $requestOutsiderElemPerPage == 0) {
        $requestOutsiderPage = 1;
    }

    // Output form
    $oForm = new cGuiTableForm("properties", "main.php?1", "get"); // Use "get"
                                                                   // for
                                                                   // folding
                                                                   // rows...
    $oForm->setVar("frame", $frame);
    $oForm->setVar("area", $area);
    $oForm->setVar("action", "recipientgroup_save_group");
    $oForm->setVar("idrecipientgroup", $requestIdRecipientGroup);
    $oForm->setSubmitJS("append_registered_parameters(this);");

    $oForm->addHeader(i18n("Edit group", 'newsletter'));

    $oTxtGroupName = new cHTMLTextbox("groupname", conHtmlentities(stripslashes($oRGroup->get("groupname"))), 40);
    $oForm->add(i18n("Group name", 'newsletter'), $oTxtGroupName->render());

    $oCkbDefault = new cHTMLCheckbox("defaultgroup", "1");
    $oCkbDefault->setChecked($oRGroup->get("defaultgroup"));
    $oForm->add(i18n("Default group", 'newsletter'), $oCkbDefault->toHtml(false));

    // Member list options folding row
    $oMemberListOptionRow = new cGuiFoldingRow(
        "a91f5540-52db-11db-b0de-0800200c9a66", i18n("Member list options", "newsletter"), "member"
    );

    $oSelItemsPerPage = new cHTMLSelectElement("member_elemperpage");
    $oSelItemsPerPage->autoFill([
        0 => i18n("-- All --", 'newsletter'),
        25 => 25,
        50 => 50,
        75 => 75,
        100 => 100
    ]);
    $oSelItemsPerPage->setDefault($requestMemberElemPerPage);

    $oSelSortBy = new cHTMLSelectElement("member_sortby");
    foreach ($aFields as $sKey => $aData) {
        if (cString::findFirstPos($aData["type"], "sort") !== false) {
            if ($requestMemberSortBy == "") {
                $requestMemberSortBy = $aData["field"];
            }
            $oOption = new cHTMLOptionElement($aData["caption"], $aData["field"]);
            $oSelSortBy->addOptionElement($aData["field"], $oOption);
        }
    }
    $oSelSortBy->setDefault($requestMemberSortBy);

    $oSelSortOrder = new cHTMLSelectElement("member_sortorder");
    $oSelSortOrder->autoFill([
        "ASC" => i18n("Ascending", 'newsletter'),
        "DESC" => i18n("Descending", 'newsletter')
    ]);
    $oSelSortOrder->setDefault($requestMemberSortOrder);

    $oTxtFilter = new cHTMLTextbox("member_filter", $requestMemberFilter, 16);

    $oSelSearchIn = new cHTMLSelectElement("member_searchin");
    $oOption = new cHTMLOptionElement(i18n("-- All fields --", 'newsletter'), "--all--");
    $oSelSearchIn->addOptionElement("all", $oOption);

    foreach ($aFields as $sKey => $aData) {
        if (cString::findFirstPos($aData["type"], "search") !== false) {
            $oOption = new cHTMLOptionElement($aData["caption"], $aData["field"]);
            $oSelSearchIn->addOptionElement($aData["field"], $oOption);
        }
    }
    $oSelSearchIn->setDefault($requestMemberSearchIn);

    $oSubmit = new cHTMLButton("submit", i18n("Apply", 'newsletter'));

    $sContent = '<div>' . PHP_EOL;
    $sContent .= '   <table>' . PHP_EOL;
    $sContent .= '      <tr>' . PHP_EOL;
    $sContent .= '         <td>' . i18n("Items / page", 'newsletter') . '</td>' . PHP_EOL;
    $sContent .= '         <td>' . $oSelItemsPerPage->render() . '</td>' . PHP_EOL;
    $sContent .= '      </tr>' . PHP_EOL;
    $sContent .= '      <tr>' . PHP_EOL;
    $sContent .= '         <td>' . i18n("Sort by", 'newsletter') . '</td>' . PHP_EOL;
    $sContent .= '         <td>' . $oSelSortBy->render() . '</td>' . PHP_EOL;
    $sContent .= '      </tr>' . PHP_EOL;
    $sContent .= '      <tr>' . PHP_EOL;
    $sContent .= '         <td>' . i18n("Sort order", 'newsletter') . '</td>' . PHP_EOL;
    $sContent .= '         <td>' . $oSelSortOrder->render() . '</td>' . PHP_EOL;
    $sContent .= '      </tr>' . PHP_EOL;
    $sContent .= '      <tr>' . PHP_EOL;
    $sContent .= '         <td>' . i18n("Search for", 'newsletter') . '</td>' . PHP_EOL;
    $sContent .= '         <td>' . $oTxtFilter->render() . '</td>' . PHP_EOL;
    $sContent .= '      </tr>' . PHP_EOL;
    $sContent .= '      <tr>' . PHP_EOL;
    $sContent .= '         <td>' . i18n("Search in", 'newsletter') . '</td>' . PHP_EOL;
    $sContent .= '         <td>' . $oSelSearchIn->render() . '</td>' . PHP_EOL;
    $sContent .= '      </tr>' . PHP_EOL;
    $sContent .= '      <tr>' . PHP_EOL;
    $sContent .= '         <td>&nbsp;</td>' . PHP_EOL;
    $sContent .= '         <td>' . $oSubmit->render() . '</td>' . PHP_EOL;
    $sContent .= '      </tr>' . PHP_EOL;
    $sContent .= '   </table>' . PHP_EOL;
    $sContent .= '</div>' . PHP_EOL;
    $oMemberListOptionRow->setContentData($sContent);

    // Members
    $oAddedRecipientList = new cGuiList();

    $oAddedRecipientList->setCell(0, 1, "<strong>" . i18n("Name", 'newsletter') . "</strong>");
    $oImgDel = new cHTMLImage("images/but_invert_selection.gif");
    $sLnkDelIcon = '<a title="' . i18n("Check all", 'newsletter')
        . '" href="javascript:void(0)" onclick="fncCheckDel(\'deluser[]\');">' . $oImgDel->render() . '</a>';
    $oAddedRecipientList->setCell(0, 2, $sLnkDelIcon);

    $groupMembers = new NewsletterRecipientGroupMemberCollection();
    $groupMembers->setWhere("idnewsgroup", $requestIdRecipientGroup);
    $groupMembers->query();

    $groupRecipients = [];
    while ($groupMember = $groupMembers->next()) {
           $groupRecipients[] = $groupMember->get('idnewsrcp');
    }

    $oInsiders = '';
    $aInsiders = [];
    $iMembers = 0;
    if (count($groupRecipients) > 0) {
        $oInsiders = new NewsletterRecipientCollection();
        $oInsiders->setWhere("idclient", $client);
        $oInsiders->setWhere("idlang", $lang);
        $oInsiders->setWhere("idnewsrcp", $groupRecipients, 'IN');

        // Get insiders for outsiders list (*sigh!*)
        // TODO: Ask user to have at least mySQL 4.1...
        $oInsiders->query();

        if ($oInsiders->count() > 0) {
            while ($oInsider = $oInsiders->next()) {
                $aInsiders[] = $oInsider->get($oInsider->getPrimaryKeyName());
            }
        }

        // Filter
        if ($requestMemberFilter != "") {
            if ($requestMemberSearchIn == "--all--" || $requestMemberSearchIn == "") {
                foreach ($aFields as $sKey => $aData) {
                    if (cString::findFirstPos($aData["type"], "search") !== false) {
                        $oInsiders->setWhereGroup("filter", $aData["field"], $requestMemberFilter, "LIKE");
                    }
                }
                $oInsiders->setInnerGroupCondition("filter", "OR");
            } else {
                $oInsiders->setWhere($requestMemberSearchIn, $requestMemberFilter, "LIKE");
            }
        }

        // If elemperpage is something else than "all", get item count based on
        // filters
        if ($requestMemberElemPerPage > 0) {
            $oInsiders->query();
            // Getting item count without limit (for page function) - better idea anybody (performance)?
            $iMembers = $oInsiders->count();

            $oInsiders->setLimit($requestMemberElemPerPage * ($requestMemberPage - 1), $requestMemberElemPerPage);
        } else {
            $iMembers = 0;
        }

        // Get data
        $sSortSQL = $requestMemberSortBy . " " . $requestMemberSortOrder;
        if ($requestMemberSortBy == "name") {
            // Name field may be empty, add email as sort criteria
            $sSortSQL .= ", email " . $requestMemberSortOrder;
        }

        $oInsiders->setOrder($sSortSQL);
        $oInsiders->query();

        $iItems = $oInsiders->count();
    } else {
        $iItems = 0;
    }

    if ($iItems == 0 && $requestMemberFilter == "" && ($requestMemberElemPerPage == 0 || $iMembers == 0)) {
        $oAddedRecipientList->setCell(1, 1, i18n("No recipients are added to this group yet", 'newsletter'));
        $oAddedRecipientList->setCell(1, 2, '&nbsp;');
    } elseif ($iItems == 0) {
        $oAddedRecipientList->setCell(1, 1, i18n("No recipients found", 'newsletter'));
        $oAddedRecipientList->setCell(1, 2, '&nbsp;');
    } else {
        while ($oRcp = $oInsiders->next()) {
            $iID = $oRcp->get("idnewsrcp");

            $sName = $oRcp->get("name");
            $sEMail = $oRcp->get("email");
            if (empty($sName)) {
                $sName = $sEMail;
            }
            $oAddedRecipientList->setCell($iID, 1, $sName . " (" . $sEMail . ")");

            if ($perm->have_perm_area_action($area, "recipientgroup_recipient_delete")) {
                $oCkbDel = new cHTMLCheckbox("deluser[]", $iID);
                $oAddedRecipientList->setCell($iID, 2, $oCkbDel->toHtml(false));
            } else {
                $oAddedRecipientList->setCell($iID, 2, "&nbsp;");
            }
        }
    }

    // Member list pager (-> below data, as iMembers is needed)
    $oPagerLink = new cHTMLLink();
    $oPagerLink->setLink("main.php");
    $oPagerLink->setCustom("member_elemperpage", $requestMemberElemPerPage);
    $oPagerLink->setCustom("member_filter", $requestMemberFilter);
    $oPagerLink->setCustom("member_sortby", $requestMemberSortBy);
    $oPagerLink->setCustom("member_sortorder", $requestMemberSortOrder);
    $oPagerLink->setCustom("member_searchin", $requestMemberSearchIn);
    $oPagerLink->setCustom("outsider_elemperpage", $requestOutsiderElemPerPage);
    $oPagerLink->setCustom("outsider_filter", $requestOutsiderFilter);
    $oPagerLink->setCustom("outsider_sortby", $requestOutsiderSortBy);
    $oPagerLink->setCustom("outsider_sortorder", $requestOutsiderSortOrder);
    $oPagerLink->setCustom("outsider_searchin", $requestOutsiderSearchIn);
    $oPagerLink->setCustom("idrecipientgroup", $requestIdRecipientGroup);
    $oPagerLink->setCustom("frame", $frame);
    $oPagerLink->setCustom("area", $area);
    // oPagerLink->enableAutomaticParameterAppend();
    $oPagerLink->setCustom("contenido", $sess->id);

    $oMemberPager = new cGuiObjectPager(
        "d82a3ff0-52d9-11db-b0de-0800200c9a66", $iMembers, $requestMemberElemPerPage,
        $requestMemberPage, $oPagerLink, "member_page", "inside"
    );
    $oMemberPager->setCaption(i18n("Member navigation", 'newsletter'));

    $oForm->add(
        i18n("Recipients in group", 'newsletter'),
        '<table border="0" cellspacing="0" cellpadding="0" width="100%">'
        . $oMemberListOptionRow->render() . $oMemberPager->render()
        . '<tr><td>' . $oAddedRecipientList->render()
        . i18n("Note: To delete recipients from this list, please mark<br>the checkboxes and click at save button.", 'newsletter')
        . '</td></tr></table>'
    );
    unset($oInsiders);
    unset($oMemberListOptionRow);
    unset($oMemberPager);
    unset($oAddedRecipientList);

    // Outsiders
    // Outsider list options folding row
    $oOutsiderListOptionRow = new cGuiFoldingRow(
        "ca633b00-52e9-11db-b0de-0800200c9a66", i18n("Outsider list options", 'newsletter'), "outsider"
    );

    $oSelItemsPerPage = new cHTMLSelectElement("outsider_elemperpage");
    $oSelItemsPerPage->autoFill([
        0 => i18n("-- All --", 'newsletter'),
        25 => 25,
        50 => 50,
        75 => 75,
        100 => 100
    ]);
    $oSelItemsPerPage->setDefault($requestOutsiderElemPerPage);

    $oSelSortBy = new cHTMLSelectElement("outsider_sortby");
    foreach ($aFields as $sKey => $aData) {
        if (cString::findFirstPos($aData["type"], "sort") !== false) {
            if ($requestOutsiderSortBy == "") {
                $requestOutsiderSortBy = $aData["field"];
            }
            $oOption = new cHTMLOptionElement($aData["caption"], $aData["field"]);
            $oSelSortBy->addOptionElement($aData["field"], $oOption);
        }
    }
    $oSelSortBy->setDefault($requestOutsiderSortBy);

    $oSelSortOrder = new cHTMLSelectElement("outsider_sortorder");
    $oSelSortOrder->autoFill([
        "ASC" => i18n("Ascending", 'newsletter'),
        "DESC" => i18n("Descending", 'newsletter')
    ]);
    $oSelSortOrder->setDefault($requestOutsiderSortOrder);

    $oTxtFilter = new cHTMLTextbox("outsider_filter", $requestOutsiderFilter, 16);

    $oSelSearchIn = new cHTMLSelectElement("outsider_searchin");
    $oOption = new cHTMLOptionElement(i18n("-- All fields --", 'newsletter'), "--all--");
    $oSelSearchIn->addOptionElement("all", $oOption);

    foreach ($aFields as $sKey => $aData) {
        if (cString::findFirstPos($aData["type"], "search") !== false) {
            $oOption = new cHTMLOptionElement($aData["caption"], $aData["field"]);
            $oSelSearchIn->addOptionElement($aData["field"], $oOption);
        }
    }
    $oSelSearchIn->setDefault($requestOutsiderSearchIn);

    $oSubmit = new cHTMLButton("submit", i18n("Apply", 'newsletter'));

    $sContent = '<div>' . PHP_EOL;
    $sContent .= '   <table>' . PHP_EOL;
    $sContent .= '      <tr>' . PHP_EOL;
    $sContent .= '         <td>' . i18n("Items / page", 'newsletter') . '</td>' . PHP_EOL;
    $sContent .= '         <td>' . $oSelItemsPerPage->render() . '</td>' . PHP_EOL;
    $sContent .= '      </tr>' . PHP_EOL;
    $sContent .= '      <tr>' . PHP_EOL;
    $sContent .= '         <td>' . i18n("Sort by", 'newsletter') . '</td>' . PHP_EOL;
    $sContent .= '         <td>' . $oSelSortBy->render() . '</td>' . PHP_EOL;
    $sContent .= '      </tr>' . PHP_EOL;
    $sContent .= '      <tr>' . PHP_EOL;
    $sContent .= '         <td>' . i18n("Sort order", 'newsletter') . '</td>' . PHP_EOL;
    $sContent .= '         <td>' . $oSelSortOrder->render() . '</td>' . PHP_EOL;
    $sContent .= '      </tr>' . PHP_EOL;
    $sContent .= '      <tr>' . PHP_EOL;
    $sContent .= '         <td>' . i18n("Search for", 'newsletter') . '</td>' . PHP_EOL;
    $sContent .= '         <td>' . $oTxtFilter->render() . '</td>' . PHP_EOL;
    $sContent .= '      </tr>' . PHP_EOL;
    $sContent .= '      <tr>' . PHP_EOL;
    $sContent .= '         <td>' . i18n("Search in", 'newsletter') . '</td>' . PHP_EOL;
    $sContent .= '         <td>' . $oSelSearchIn->render() . '</td>' . PHP_EOL;
    $sContent .= '      </tr>' . PHP_EOL;
    $sContent .= '      <tr>' . PHP_EOL;
    $sContent .= '         <td>&nbsp;</td>' . PHP_EOL;
    $sContent .= '         <td>' . $oSubmit->render() . '</td>' . PHP_EOL;
    $sContent .= '      </tr>' . PHP_EOL;
    $sContent .= '   </table>' . PHP_EOL;
    $sContent .= '</div>' . PHP_EOL;
    $oOutsiderListOptionRow->setContentData($sContent);

    // TODO: Try to enhance genericdb to get this working with the usual
    // objects...
    $oOutsiders = new NewsletterRecipientCollection();

    // This requires mySQL V4.1, at least...
    // TODO: Add mySQL server version switch
    // sSQL = "idclient = '".$client."' AND idlang = '".$lang."' AND ".
    // "idnewsrcp NOT IN (SELECT idnewsrcp FROM
    // ".cRegistry::getDbTableName('news_groupmembers')." WHERE idnewsgroup =
    // '".$requestIdRecipientGroup."')";

    // TODO: This works with every mySQL version but may be problematic, if a
    // group contains a lot of members (e.g. Oracle can't handle more than 1000
    // items in the brackets)
    $sSQL = "idclient = '" . $client . "' AND idlang = '" . $lang . "'";
    if (count($aInsiders) > 0) {
        $sSQL .= " AND idnewsrcp NOT IN ('" . implode("','", $aInsiders) . "')";
    }

    if ($requestOutsiderFilter != "") {
        $sSQLSearchIn = "";
        if ($requestOutsiderSearchIn == "--all--" || $requestOutsiderSearchIn == "") {
            foreach ($aFields as $sKey => $aData) {
                if (cString::findFirstPos($aData["type"], "search") !== false) {
                    if ($sSQLSearchIn !== "") {
                        $sSQLSearchIn .= " OR ";
                    }
                    $sSQLSearchIn .= $aData["field"] . " LIKE '%" . $requestOutsiderFilter . "%'";
                }
            }
        } else {
            $sSQLSearchIn .= $requestOutsiderSearchIn . " LIKE '%" . urlencode($requestOutsiderFilter) . "%'";
        }
        $sSQL .= " AND (" . $sSQLSearchIn . ")";
    }

    // If elemperpage is something else than "all", get item count based on
    // filters
    if ($requestOutsiderElemPerPage > 0) {
        $oOutsiders->flexSelect("", "", $sSQL, "");
        // Getting item count without limit (for page function) - better idea anyone (performance)?
        $iOutsiders = $oOutsiders->count();

        $sSQLLimit = " LIMIT " . $requestOutsiderElemPerPage * ($requestOutsiderPage - 1) . ", " . $requestOutsiderElemPerPage;
    } else {
        $iMembers = 0;
        $sSQLLimit = "";
    }

    // Get data
    $sSQLSort = " ORDER BY " . $requestOutsiderSortBy . " " . $requestOutsiderSortOrder;
    if ($requestOutsiderSortBy == "name") {
        // Name field may be empty, add email as sort criteria
        $sSQLSort .= ", email " . $requestOutsiderSortOrder;
    }

    $sSQL .= $sSQLSort . $sSQLLimit;
    $oOutsiders->flexSelect("", "", $sSQL, "");

    $aItems = [];
    while ($oRecipient = $oOutsiders->next()) {
        $sName = $oRecipient->get("name");
        $sEMail = $oRecipient->get("email");

        if (empty($sName)) {
            $sName = $sEMail;
        }
        $aItems[] = [
            $oRecipient->get("idnewsrcp"),
            $sName . " (" . $sEMail . ")"
        ];
    }

    $oSelUser = new cHTMLSelectElement("adduser[]");
    $oSelUser->setSize(25);
    $oSelUser->setStyle("width: 100%;");
    $oSelUser->setMultiselect();
    $oSelUser->autoFill($aItems);

    // Outsider list pager (-> below data, as iOutsiders is needed)
    $oPagerLink = new cHTMLLink();
    $oPagerLink->setLink("main.php");
    $oPagerLink->setCustom("member_elemperpage", $requestMemberElemPerPage);
    $oPagerLink->setCustom("member_filter", $requestMemberFilter);
    $oPagerLink->setCustom("member_sortby", $requestMemberSortBy);
    $oPagerLink->setCustom("member_sortorder", $requestMemberSortOrder);
    $oPagerLink->setCustom("member_searchin", $requestMemberSearchIn);
    $oPagerLink->setCustom("outsider_elemperpage", $requestOutsiderElemPerPage);
    $oPagerLink->setCustom("outsider_filter", $requestOutsiderFilter);
    $oPagerLink->setCustom("outsider_sortby", $requestOutsiderSortBy);
    $oPagerLink->setCustom("outsider_sortorder", $requestOutsiderSortOrder);
    $oPagerLink->setCustom("outsider_searchin", $requestOutsiderSearchIn);
    $oPagerLink->setCustom("idrecipientgroup", $requestIdRecipientGroup);
    $oPagerLink->setCustom("frame", $frame);
    $oPagerLink->setCustom("area", $area);
    // oPagerLink->enableAutomaticParameterAppend();
    $oPagerLink->setCustom("contenido", $sess->id);

    $oOutsiderPager = new cGuiObjectPager(
        "4d3a7330-52eb-11db-b0de-0800200c9a66", $iOutsiders, $requestOutsiderElemPerPage,
        $requestOutsiderPage, $oPagerLink, "outsider_page", "outside"
    );
    $oOutsiderPager->setCaption(i18n("Outsider navigation", 'newsletter'));

    $oForm->add(
        i18n("Add recipients", 'newsletter'),
        '<table data-foo="bar" border="0" cellspacing="0" cellpadding="0" width="100%">'
        . $oOutsiderListOptionRow->render() . $oOutsiderPager->render()
        . '<tr><td>' . $oSelUser->render() . '<br>'
        . i18n("Note: Hold &lt;Ctrl&gt; to<br>select multiple items.", 'newsletter')
        . '</td></tr></table>'
    );
    unset($oOutsiders);
    unset($oOutsiderListOptionRow);
    unset($oOutsiderPager);

    $sDelMarkScript = '
    <script type="text/javascript">
    /* Function to select all ckbDel boxes */
    function fncCheckDel(elementname) {
        var aBoxes = document.getElementsByName(elementname);

        if (aBoxes.length > 0) {
            for (var i = 0; i < aBoxes.length; i++) {
                if (aBoxes[i].checked) {
                   aBoxes[i].checked = false;
                } else {
                   aBoxes[i].checked = true;
                }
            }
        }
    }
    </script>';

    $oPage->addScript($sDelMarkScript);
    $oPage->addScript('cfoldingrow.js');
    $oPage->addScript('parameterCollector.js');

    $oPage->setContent($oForm);
}
$oPage->render();
