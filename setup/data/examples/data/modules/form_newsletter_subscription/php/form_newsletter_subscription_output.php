<?php
/**
 * Description: Newsletter form output
 *
 * @package Module
 * @subpackage FormNewsletterSubscription
 * @version SVN Revision $Rev:$
 *
 * @author unknown
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (class_exists('NewsletterJobCollection')) {

    // Initialisation
    $oClientLang = new cApiClientLanguage(false, cRegistry::getClientId(), cRegistry::getLanguageId());

    /*
     * Used variables: JoinSel: Selection, which group will be joined (Default,
     * Selected, UserSelected) JoinMultiple: If JoinSel = UserSelected then: If
     * more than one group has been specified, select more than one JoinGroups:
     * Selected group(s) JoinMessageType: Message type for new recipients: User
     * select (user), text or html OptNewWindow: Open handler window in new
     * browser window?
     */
    $aSettings = array(
        'JoinSel' => $oClientLang->getProperty('newsletter', 'joinsel'),
        'JoinMultiple' => $oClientLang->getProperty('newsletter', 'joinmultiple'),
        'JoinGroups' => $oClientLang->getProperty('newsletter', 'joingroups'),
        'JoinMessageType' => $oClientLang->getProperty('newsletter', 'joinmessagetype'),
        'OptNewWindow' => "CMS_VALUE[4]"
    );

    $sTemplate = 'get.tpl';
    $cmsLinkeditor = "CMS_LINKEDITOR[1]";
    if ($aSettings["JoinSel"] == "" || ($aSettings["JoinSel"] == "UserSelected" && $aSettings["JoinGroups"] == "")) {
        $aSettings["JoinSel"] = "Default";
    }

    $tpl = cSmartyFrontend::getInstance();

    $tpl->assign('FORM_ACTION', 'front_content.php?changelang=' . cRegistry::getLanguageId() . '&idcatart=' . $oClientLang->getProperty('newsletter', 'idcatart'));
    unset($oClientLang);

    if ($aSettings["OptNewWindow"]) {
        $tpl->assign('FORM_TARGET', ' target="_blank"');
    } else {
        $tpl->assign('FORM_TARGET', '');
    }
    $tpl->assign('EMAILNAME', mi18n("NAME"));
    $tpl->assign('EMAIL', mi18n("E_MAIL"));

    $sTmpHTML = "";
    if ($aSettings["JoinSel"] == "UserSelected") {
        // Late include to increase performance

        $oRcpGroups = new NewsletterRecipientGroupCollection();
        $oRcpGroups->setWhere('idclient', cRegistry::getClientId());
        $oRcpGroups->setWhere('idlang', cRegistry::getLanguageId());
        $oRcpGroups->setWhere('defaultgroup', '0');
        $oRcpGroups->setWhere('idnewsgroup', explode(',', $aSettings["JoinGroups"]), 'IN');
        $oRcpGroups->setOrder('groupname ASC');
        $oRcpGroups->query();

        // oRcpGroups->select("idclient = '$client' AND idlang = '$lang' AND
        // defaultgroup = '0' AND idnewsgroup IN
        // (".$aSettings["JoinGroups"].")","",
        // "groupname ASC");

        if ($oRcpGroups->Count() > 0) {
            $oLblGroupSel = new cHTMLLabel(mi18n("SELECT"), "selNewsletterGroup");

            $oSelGroup = new cHTMLSelectElement("selNewsletterGroup[]", "", "selNewsletterGroup");
            $oSelGroup->setSize(2);
            $oSelGroup->setClass("");

            if ($aSettings["JoinMultiple"] == "enabled") {
                $oSelGroup->setMultiselect();
            }

            while (false !== $oRcpGroup = $oRcpGroups->next()) {
                $iID = $oRcpGroup->get("idnewsgroup");
                $oOption = new cHTMLOptionElement($oRcpGroup->get("groupname"), $iID);
                $oSelGroup->addOptionElement($iID, $oOption);
            }

            $sTmpHTML .= '         ' . $oLblGroupSel->toHTML() . "\n";
            $sTmpHTML .= '         ' . $oSelGroup->render() . "\n";
            $sTmpHTML .= '         <br class="y"/>';
        }
    }
    // You may like to add here additional rows for fields used in recipient- or
    // frontenduser-plugins
    // $sTmpHTML .= '...';

    if ($aSettings['JoinMessageType'] == 'user') {
        $oLblType = new cHTMLLabel(mi18n("TYPE"), "selNewsletterType");

        $oSelType = new cHTMLSelectElement("selNewsletterType", "", "selNewsletterType");
        $oSelType->setSize(1);
        $oSelType->setClass("");

        $oOption = new cHTMLOptionElement(mi18n("TEXT_ONLY"), 0);
        $oSelType->addOptionElement(0, $oOption);
        $oOption = new cHTMLOptionElement(mi18n("HTML"), 1);
        $oSelType->addOptionElement(1, $oOption);

        // $sTmpHTML .= ' '.$oLblType->toHTML()."\n";
        $sTmpHTML .= '         ' . $oSelType->render() . "\n";
        $sTmpHTML .= '         <br class="y"/>';
    }
    $tpl->assign('EXTRAHTML', $sTmpHTML);

    $tpl->assign('NEWSLETTER', mi18n("NEWSLETTER_SRC"));
    $tpl->assign('SUBSCRIBE', mi18n("SUBSCRIBE_SRC"));
    $tpl->assign('DELETE', mi18n("UNSUBSCRIBE"));

    $tpl->assign("ABSCHICKEN", mi18n("SUBMIT"));
    $tpl->assign("LOESCHEN", mi18n("DELETE"));
    $tpl->assign('JOIN', mi18n("JOIN"));
    $tpl->assign('LINKEDITOR', $cmsLinkeditor);
    $tpl->assign('PRIVACY_TEXT_PART1', mi18n("READ_AND_ACCEPT_1"));

    $tpl->assign('PRIVACY_TEXT_PART2', mi18n("READ_AND_ACCEPT_2 "));

    $tpl->display($sTemplate);
}

?>