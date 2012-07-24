<?php
/**
 * Description: Newsletter form output
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

/*
 *  Used variables:
 *  JoinSel:         Selection, which group will be joined (Default, Selected, UserSelected)
 *  JoinMultiple:    If JoinSel = UserSelected then: If more than one group has been specified, select more than one
 *  JoinGroups:      Selected group(s)
 *  JoinMessageType: Message type for new recipients: User select (user), text or html
 *  OptNewWindow:    Open handler window in new browser window?
 */
$aSettings = array(
    'JoinSel'         => $oClientLang->getProperty('newsletter', 'joinsel'),
    'JoinMultiple'    => $oClientLang->getProperty('newsletter', 'joinmultiple'),
    'JoinGroups'      => $oClientLang->getProperty('newsletter', 'joingroups'),
    'JoinMessageType' => $oClientLang->getProperty('newsletter', 'joinmessagetype'),
    'OptNewWindow'    => "CMS_VALUE[4]",
);

$sTemplate = 'newsletter_form.html';
$cmsLinkeditor = "CMS_LINKEDITOR[1]";
if ($aSettings["JoinSel"] == "" || ($aSettings["JoinSel"] == "UserSelected" && $aSettings["JoinGroups"] == "")) {
    $aSettings["JoinSel"]= "Default";
}

if (!isset($oPage) || !is_object($oPage)) {
    $oPage = new cTemplate();
}
$oPage->reset();

$oPage->set('s', 'FORM_ACTION', 'front_content.php?changelang='.$lang.'&amp;idcatart='.
            $oClientLang->getProperty('newsletter', 'idcatart'));
unset($oClientLang);

if ($aSettings["OptNewWindow"]) {
    $oPage->set('s', 'FORM_TARGET', ' target="_blank"');
} else {
    $oPage->set('s', 'FORM_TARGET', '');
}
$oPage->set('s', 'EMAILNAME', mi18n("Name"));
$oPage->set('s', 'EMAIL',     mi18n("E-Mail"));

$sTmpHTML = "";
if ($aSettings["JoinSel"] == "UserSelected") {
    // Late include to increase performance

    $oRcpGroups = new NewsletterRecipientGroupCollection();
    $oRcpGroups->setWhere('idclient', $client);
    $oRcpGroups->setWhere('idlang',   $lang);
    $oRcpGroups->setWhere('defaultgroup', '0');
    $oRcpGroups->setWhere('idnewsgroup', explode(',', $aSettings["JoinGroups"]), 'IN');
    $oRcpGroups->setOrder('groupname ASC');
    $oRcpGroups->query();

    #$oRcpGroups->select("idclient = '$client' AND idlang = '$lang' AND defaultgroup = '0' AND idnewsgroup IN (".$aSettings["JoinGroups"].")","", "groupname ASC");

    if ($oRcpGroups->Count() > 0) {
        $oLblGroupSel = new cHTMLLabel(mi18n("Select"), "selNewsletterGroup");

        $oSelGroup = new cHTMLSelectElement("selNewsletterGroup[]", "", "selNewsletterGroup");
        $oSelGroup->setSize(2);
        $oSelGroup->setClass("");

        if ($aSettings["JoinMultiple"] == "enabled") {
            $oSelGroup->setMultiselect();
        }

        while ($oRcpGroup = $oRcpGroups->next()) {
            $iID = $oRcpGroup->get("idnewsgroup");
            $oOption = new cHTMLOptionElement($oRcpGroup->get("groupname"), $iID);
            $oSelGroup->addOptionElement($iID, $oOption);
        }

        $sTmpHTML .= '         '.$oLblGroupSel->toHTML()."\n";
        $sTmpHTML .= '         '.$oSelGroup->render()."\n";
        $sTmpHTML .= '         <br class="y"/>';
    }
}
// You may like to add here additional rows for fields used in recipient- or frontenduser-plugins
// $sTmpHTML .= '...';

if ($aSettings['JoinMessageType'] == 'user') {
    $oLblType = new cHTMLLabel(mi18n("Type"), "selNewsletterType");

    $oSelType = new cHTMLSelectElement("selNewsletterType", "", "selNewsletterType");
    $oSelType->setSize(1);
    $oSelType->setClass("");

    $oOption = new cHTMLOptionElement(mi18n("Text only"), 0);
    $oSelType->addOptionElement(0, $oOption);
    $oOption = new cHTMLOptionElement(mi18n("HTML"), 1);
    $oSelType->addOptionElement(1, $oOption);

    //$sTmpHTML .= '         '.$oLblType->toHTML()."\n";
    $sTmpHTML .= '         '.$oSelType->render()."\n";
    $sTmpHTML .= '         <br class="y"/>';
}
$oPage->set('s', 'EXTRAHTML', $sTmpHTML);

$oPage->set('s', 'NEWSLETTER', mi18n("Newsletter"));
$oPage->set('s', 'SUBSCRIBE',  mi18n("Subscribe"));
$oPage->set('s', 'DELETE',     mi18n("Unsubscribe"));

$oPage->set("s", "ABSCHICKEN", mi18n("submit"));
$oPage->set("s", "LOESCHEN", mi18n("delete"));
$oPage->set('s', 'JOIN', mi18n("Join"));
$oPage->set('s', 'LINKEDITOR',$cmsLinkeditor );
$oPage->set('s', 'PRIVACY_TEXT_PART1', mi18n("Ich habe die"));

$oPage->set('s', 'PRIVACY_TEXT_PART2', mi18n(" gelesen und bin damit einverstanden. "));

$oPage->generate('templates/'.$sTemplate);

?>