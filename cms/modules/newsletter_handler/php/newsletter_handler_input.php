?><?php
/**
 * Description: Newsletter handler input
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

cInclude('classes', 'class.input.helper.php'); # Note, this file is located in <client-path>/includes!

// Initialisation
$oClientLang = new cApiClientLanguage(false, $client, $lang);
$oClient     = new cApiClient($client);

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
 *  ChangeEMailID:   ID of change e-mail handler article
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
);

// Setting default values
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
if (!is_numeric($_REQUEST['selHandlerCatArt'.$cnumber]) || $_REQUEST['selHandlerCatArt'.$cnumber] < 0) {
    $_REQUEST['selHandlerCatArt'.$cnumber] = 0;
}

// Saving changes, if any
if ($_REQUEST['hidAction'.$cnumber] == 'save') {
    if ($_REQUEST['radJoin'.$cnumber] != '' && $_REQUEST['radJoin'.$cnumber] != $aSettings['JoinSel']) {
        $aSettings['JoinSel'] = $_REQUEST['radJoin'.$cnumber];
        $oClientLang->setProperty('newsletter', 'joinsel', $aSettings['JoinSel']);
    }
    if ($_REQUEST['ckbJoinMultiple'.$cnumber] != $aSettings['JoinMultiple']) {
        $aSettings['JoinMultiple'] = $_REQUEST['ckbJoinMultiple'.$cnumber];
        $oClientLang->setProperty('newsletter', 'joinmultiple', $aSettings['JoinMultiple']);
    }
    if ($_REQUEST['hidJoinGroups'.$cnumber] != '' && $_REQUEST['hidJoinGroups'.$cnumber] != $aSettings['JoinGroups']) {
        $aSettings['JoinGroups'] = $_REQUEST['hidJoinGroups'.$cnumber];
        $oClientLang->setProperty('newsletter', 'joingroups', $aSettings['JoinGroups']);
    }
    if ($_REQUEST['selMessageType'.$cnumber] != $aSettings['JoinMessageType']) {
        $aSettings['JoinMessageType'] = $_REQUEST['selMessageType'.$cnumber];
        $oClientLang->setProperty('newsletter', 'joinmessagetype', $aSettings['JoinMessageType']);
    }
    if ($_REQUEST['ckbFrontendLink'.$cnumber] != $aSettings['FrontendLink']) {
        $aSettings['FrontendLink'] = $_REQUEST['ckbFrontendLink'.$cnumber];
        $oClient->setProperty('newsletter', 'frontendlink', $aSettings['FrontendLink']);
    }
    if ($_REQUEST['ckbUpdateHandlerID'.$cnumber] == 'enabled') {
        // Trick: If UpdateHandlerID is enabled, save id as client setting
        $iHandlerCatArt = $_REQUEST['selHandlerCatArt'.$cnumber];
        $oClientLang->setProperty('newsletter', 'idcatart', $iHandlerCatArt);
    }
    if (isValidMail($_REQUEST['txtSender'.$cnumber]) && $_REQUEST['txtSender'.$cnumber] != $aSettings['SenderEMail']) {
        $aSettings['SenderEMail'] = $_REQUEST['txtSender'.$cnumber];
        $oClient->setProperty('global', 'sender-email', $aSettings['SenderEMail']);
    }
}

// Getting current handler article id
$iHandlerCatArt = $oClientLang->getProperty('newsletter', 'idcatart');
unset($oClientLang, $oClient);

// Show options
$oCfgTable  = new UI_Config_Table();

$oHidAction = new cHTMLHiddenField('hidAction'.$cnumber, 'save');

$oTxtSender = new cHTMLTextbox("txtSender".$cnumber, $aSettings['SenderEMail'], 30);

$oCfgTable->setCell('sender', 0, mi18n("Sender e-mail:"));
$oCfgTable->setCell('sender', 1, $oHidAction->render().$oTxtSender->render());

$oSelHandlerCatArt = new cHTMLInputSelectElement('selHandlerCatArt'.$cnumber, 1, '', true);
$oOption           = new cHTMLOptionElement(mi18n("- Please select -"), '');
$oSelHandlerCatArt->addOptionElement(0, $oOption);
$oSelHandlerCatArt->addCategories(0, true, false, false, true, true);
$oSelHandlerCatArt->setDefault($iHandlerCatArt);

$oCkbUpdate        = new cHTMLCheckbox('ckbUpdateHandlerID'.$cnumber, 'enabled');
$oCkbUpdate->setEvent('click', 'if (this.checked) {document.forms[0].selHandlerCatArt'.$cnumber.'.disabled = false;} else {document.forms[0].selHandlerCatArt'.$cnumber.'.disabled = true;}');

$oCfgTable->setCell('handler', 0, mi18n("Handler Article:"));
$oCfgTable->setCell('handler', 1, $oSelHandlerCatArt->render()."\n&nbsp;".$oCkbUpdate->toHTML(false).mi18n("Update"));

// Getting newsletter groups (if any)
$oRcpGroups = new NewsletterRecipientGroupCollection();
$oRcpGroups->setWhere('idclient', $client);
$oRcpGroups->setWhere('idlang',   $lang);
$oRcpGroups->setWhere('defaultgroup', '0');
$oRcpGroups->setOrder('defaultgroup DESC, groupname ASC');
$oRcpGroups->query();

// Join options
// If newsletter groups are available, provide group options, otherwise show only
// 'Default' option. This is necessary, as there may have been groups specified (and used)
// but they have been deleted, later on.

$oCfgTable->setCell('join_01', 0, mi18n("Join:"));

if ($oRcpGroups->Count() == 0) {
    // No groups available, only default group possible
    $oRadJoinDefault = new cHTMLRadioButton('radJoin'.$cnumber, 'Default', '', true);
    $oCfgTable->setCell('join_01', 1, $oRadJoinDefault->toHTML(false).mi18n("default group"));
} else {
    // Groups available, show different group join options

    // Join default group only
    if ($aSettings['JoinSel'] == 'Default') {
        $oRadJoinDefault = new cHTMLRadioButton('radJoin'.$cnumber, 'Default', '', true);
    } else {
        $oRadJoinDefault = new cHTMLRadioButton('radJoin'.$cnumber, 'Default');
    }
    $oRadJoinDefault->setEvent('click', "document.forms[0].elements['ckbJoinMultiple".$cnumber."'].disabled = true; document.forms[0].selGroup".$cnumber.".disabled = true;");
    $oCfgTable->setCell('join_01', 1, $oRadJoinDefault->toHTML(false).mi18n("default group"));

    // Join admin selected groups automatically
    if ($aSettings['JoinSel'] == 'Selected') {
        $oRadJoinSelected = new cHTMLRadioButton('radJoin'.$cnumber, 'Selected', '', true);
    } else {
        $oRadJoinSelected = new cHTMLRadioButton('radJoin'.$cnumber, 'Selected');
    }
    $oRadJoinSelected->setEvent('click', "document.forms[0].elements['ckbJoinMultiple".$cnumber."'].disabled = false; document.forms[0].selGroup".$cnumber.".disabled = false;");
    $oCfgTable->setCell('join_02', 0, '');
    $oCfgTable->setCell('join_02', 1, $oRadJoinSelected->toHTML(false).mi18n("selected group(s)"));

    // Join the groups the user has selected (-> provide a list for the user), optionally, the user may select more than one group
    if ($aSettings['JoinSel'] == 'UserSelected') {
        $oRadJoinUserSel  = new cHTMLRadioButton('radJoin'.$cnumber, 'UserSelected', '', true);
        $oCkbJoinMultiple = new cHTMLCheckbox('ckbJoinMultiple'.$cnumber, 'enabled', '', $aSettings['JoinMultiple']);
    } else {
        $oRadJoinUserSel  = new cHTMLRadioButton('radJoin'.$cnumber, 'UserSelected');
        $oCkbJoinMultiple = new cHTMLCheckbox ('ckbJoinMultiple'.$cnumber, 'enabled', '', false, true);
    }
    $oRadJoinUserSel->setEvent('click', "document.forms[0].elements['ckbJoinMultiple".$cnumber."'].disabled = false; document.forms[0].selGroup".$cnumber.".disabled = false;");
    $oCfgTable->setCell('join_03', 0, '');
    $oCfgTable->setCell('join_03', 1, $oRadJoinUserSel->toHTML(false).mi18n("group(s) as selected by user").'<br />'."\n".$oCkbJoinMultiple->toHTML(false).mi18n("Multiple group selection allowed"));

    $oCfgTable->setCell('groups', 0, mi18n("Select group(s):"));

    // Show groups
    // Trick: To save multiple selections in <select>-Element, add some JS which saves the
    // selection, comma separated in a hidden input field on change.
    $sSkript = '              <script type="text/javascript"><!--'."\n".
             '                  function fncUpdateSel() {'."\n".
             '                     var strSel = "";'."\n".
             '                     for (i = 0; i < document.forms[0].selGroup'.$cnumber.'.length; i++) {'."\n".
             '                        if (document.forms[0].selGroup'.$cnumber.'.options[i].selected == true) {'."\n".
             '                           if (strSel != "")'."\n".
             '                              strSel = strSel + ",";'."\n".
             '                           strSel = strSel + document.forms[0].selGroup'.$cnumber.'.options[i].value;'."\n".
             '                        }'."\n".
             '                     }'."\n".
             '                     document.forms[0].elements["hidJoinGroups'.$cnumber.'"].value = strSel;'."\n".
             '                  }'."\n".
             '                  //--></script>'."\n";

    if ($aSettings['JoinSel'] == 'Default') {
        $oSelGroup = new cHTMLSelectElement('selGroup'.$cnumber, '', '', true);
    } else {
        $oSelGroup = new cHTMLSelectElement('selGroup'.$cnumber, '');
    }
    $oSelGroup->setSize(5);
    $oSelGroup->setMultiselect();
    $oSelGroup->setEvent('change', "fncUpdateSel()");

    $aGroups = explode(',', $aSettings['JoinGroups']);
    while ($oRcpGroup = $oRcpGroups->next()) {
        $iID = $oRcpGroup->get('idnewsgroup');
        if (in_array($iID, $aGroups)) {
            $oOption = new cHTMLOptionElement($oRcpGroup->get('groupname'), $iID, true);
        } else {
            $oOption = new cHTMLOptionElement($oRcpGroup->get('groupname'), $iID, false);
        }
        $oSelGroup->addOptionElement($iID, $oOption);
    }

    $oHidGroups = new cHTMLHiddenField('hidJoinGroups'.$cnumber, $aSettings['JoinGroups']);
    $oCfgTable->setCell('groups', 1, $sSkript.$oSelGroup->render().$oHidGroups->render());
}

// Options: Message type (user [->selectbox], text or html)
$oCfgTable->setCell('options_01', 0, mi18n("Options:"));

$oSelMsgType = new cHTMLSelectElement('selMessageType'.$cnumber);
$oOption = new cHTMLOptionElement(mi18n("User selected"), "user");
$oSelMsgType->addOptionElement(0, $oOption);
$oOption = new cHTMLOptionElement(mi18n("Text only"), "text");
$oSelMsgType->addOptionElement(1, $oOption);
$oOption = new cHTMLOptionElement(mi18n("HTML and text"), "html");
$oSelMsgType->addOptionElement(2, $oOption);
$oSelMsgType->setDefault($aSettings['JoinMessageType']);

$oCfgTable->setCell('options_01', 1, mi18n("Default message type:").'&nbsp;'.$oSelMsgType->render());

// Frontend Link
$oCfgTable->setCell('link_01', 0, mi18n("Frontend Users:"));
$oCkbLink = new cHTMLCheckbox('ckbFrontendLink'.$cnumber, 'enabled', '', $aSettings['FrontendLink']);

$sSkript = "if (this.checked) {
              document.forms[0].elements['CMS_VAR[5]'][0].disabled = false;
              document.forms[0].elements['CMS_VAR[5]'][1].disabled = false;
              document.forms[0].elements['CMS_VAR[6]'][0].disabled = false;
              document.forms[0].elements['CMS_VAR[6]'][1].disabled = false;
              document.forms[0].elements['CMS_VAR[6]'][2].disabled = false;
           } else {
              document.forms[0].elements['CMS_VAR[5]'][0].disabled = true;
              document.forms[0].elements['CMS_VAR[5]'][1].disabled = true;
              document.forms[0].elements['CMS_VAR[6]'][0].disabled = true;
              document.forms[0].elements['CMS_VAR[6]'][1].disabled = true;
              document.forms[0].elements['CMS_VAR[6]'][2].disabled = true;}";
$oCkbLink->setEvent("click", $sSkript);

$oCfgTable->setCell('link_01', 1, $oCkbLink->toHTML(false).mi18n("Activate Link"));

// Link: Activation options
$oCfgTable->setCell('link_02', 0, '');

switch ($aSettings['FrontendConfirm']) {
    case "Nothing":
        $oRadActivateUser    = new cHTMLRadioButton("CMS_VAR[5]", "ActivateUser", "", false);
        $oRadActivateNothing = new cHTMLRadioButton("CMS_VAR[5]", "Nothing",      "", true);
        break;
    default:
        $oRadActivateUser    = new cHTMLRadioButton("CMS_VAR[5]", "ActivateUser", "", true);
        $oRadActivateNothing = new cHTMLRadioButton("CMS_VAR[5]", "Nothing",      "", false);
}

switch ($aSettings['FrontendDel']) {
    case "DisableUser":
        $oRadDelDelete  = new cHTMLRadioButton("CMS_VAR[6]", "DeleteUser",  "", false);
        $oRadDelDisable = new cHTMLRadioButton("CMS_VAR[6]", "DisableUser", "", true);
        $oRadDelNothing = new cHTMLRadioButton("CMS_VAR[6]", "Nothing",     "", false);
        break;
    case "Nothing":
        $oRadDelDelete  = new cHTMLRadioButton("CMS_VAR[6]", "DeleteUser",  "", false);
        $oRadDelDisable = new cHTMLRadioButton("CMS_VAR[6]", "DisableUser", "", false);
        $oRadDelNothing = new cHTMLRadioButton("CMS_VAR[6]", "Nothing",     "", true);
        break;
    default:
        $oRadDelDelete  = new cHTMLRadioButton("CMS_VAR[6]", "DeleteUser",  "", true);
        $oRadDelDisable = new cHTMLRadioButton("CMS_VAR[6]", "DisableUser", "", false);
        $oRadDelNothing = new cHTMLRadioButton("CMS_VAR[6]", "Nothing",     "", false);
}

if ($aSettings['FrontendLink'] == '') {
    $oRadActivateUser->setDisabled(true);
    $oRadActivateNothing->setDisabled(true);
    $oRadDelDelete->setDisabled(true);
    $oRadDelDisable->setDisabled(true);
    $oRadDelNothing->setDisabled(true);
}
$oCfgTable->setCell('link_02', 1, mi18n("Confirmation means:").'<br />'.
                    $oRadActivateUser->toHTML(false).mi18n("Activate").
                    $oRadActivateNothing->toHTML(false).mi18n("No changes"));

// Link: Cancellation options
$oCfgTable->setCell('link_03', 0, '');

$oCfgTable->setCell('link_03', 1, mi18n("Cancellation means:").'<br />'.
                    $oRadDelDelete->toHTML(false).mi18n("Delete").
                    $oRadDelDisable->toHTML(false).mi18n("Disable").
                    $oRadDelNothing->toHTML(false).mi18n("No changes"));

$oCfgTable->render(true);

?><?php