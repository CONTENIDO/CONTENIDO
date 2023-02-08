?><?php

/**
 * Description: Newsletter handler input
 *
 * @version    1.0.0
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 */

// Initialisation
$oClientLang       = new cApiClientLanguage(false, $client, $lang);
$oClient           = new cApiClient($client);

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
];

// Setting default values
// If there is no selection option set or if no groups has been selected, activate option Default
if ($aSettings['JoinSel'] == '' || ($aSettings['JoinSel'] == 'UserSelected' && $aSettings['JoinGroups'] == '')) {
    $aSettings['JoinSel'] = 'Default';
}
if ($aSettings['FrontendConfirm'] == '') {
    $aSettings['FrontendConfirm'] = "ActivateUser";
}
if ($aSettings['FrontendDel'] == '') {
    $aSettings['FrontendDel'] = "DeleteUser";
}
if (!is_numeric($_REQUEST['selHandlerCatArt'.$cCurrentContainer]) || $_REQUEST['selHandlerCatArt'.$cCurrentContainer] < 0) {
    $_REQUEST['selHandlerCatArt'.$cCurrentContainer] = 0;
}

// Saving changes, if any
if ($_REQUEST['hidAction'.$cCurrentContainer] == 'save') {
    if ($_REQUEST['radJoin'.$cCurrentContainer] != '') {
        $aSettings['JoinSel'] = $_REQUEST['radJoin'.$cCurrentContainer];
        $oClientLang->setProperty('newsletter', 'joinsel', $aSettings['JoinSel']);
    }
    if ($_REQUEST['ckbJoinMultiple'.$cCurrentContainer] != $aSettings['JoinMultiple']) {
        $aSettings['JoinMultiple'] = $_REQUEST['ckbJoinMultiple'.$cCurrentContainer];
        $oClientLang->setProperty('newsletter', 'joinmultiple', $aSettings['JoinMultiple']);
    }
    if (isset($_REQUEST['selGroup'.$cCurrentContainer]) && is_array($_REQUEST['selGroup'.$cCurrentContainer])) {
        $aSettings['JoinGroups'] = implode(',', $_REQUEST['selGroup'.$cCurrentContainer]);
        $oClientLang->setProperty('newsletter', 'joingroups', $aSettings['JoinGroups']);
    }
    if ($_REQUEST['selMessageType'.$cCurrentContainer] != $aSettings['JoinMessageType']) {
        $aSettings['JoinMessageType'] = $_REQUEST['selMessageType'.$cCurrentContainer];
        $oClientLang->setProperty('newsletter', 'joinmessagetype', $aSettings['JoinMessageType']);
    }
    if ($_REQUEST['ckbFrontendLink'.$cCurrentContainer] != $aSettings['FrontendLink']) {
        $aSettings['FrontendLink'] = $_REQUEST['ckbFrontendLink'.$cCurrentContainer];
        $oClient->setProperty('newsletter', 'frontendlink', $aSettings['FrontendLink']);
    }
    if ($_REQUEST['ckbUpdateHandlerID'.$cCurrentContainer] == 'enabled') {
        // Trick: If UpdateHandlerID is enabled, save id as client setting
        $iHandlerCatArt = $_REQUEST['selHandlerCatArt'.$cCurrentContainer];
        $oClientLang->setProperty('newsletter', 'idcatart', $iHandlerCatArt);
    }
    if (isValidMail($_REQUEST['txtSender'.$cCurrentContainer]) && $_REQUEST['txtSender'.$cCurrentContainer] != $aSettings['SenderEMail']) {
        $aSettings['SenderEMail'] = $_REQUEST['txtSender'.$cCurrentContainer];
        $oClient->setProperty('global', 'sender-email', $aSettings['SenderEMail']);
    }
}

// Getting current handler article id
$iHandlerCatArt = $oClientLang->getProperty('newsletter', 'idcatart');
unset($oClientLang, $oClient);

// Show options
$oCfgTable  = new UI_Config_Table();

$oHidAction = new cHTMLHiddenField('hidAction'.$cCurrentContainer, 'save');

$oTxtSender = new cHTMLTextbox("txtSender".$cCurrentContainer, $aSettings['SenderEMail'], 30);

$oCfgTable->setCell('sender', 0, mi18n("SENDER_EMAIL_COLON"));
$oCfgTable->setCell('sender', 1, $oHidAction->render().$oTxtSender->render());

$oSelHandlerCatArt = new cHTMLInputSelectElement('selHandlerCatArt'.$cCurrentContainer, 1, '', true);
$oOption           = new cHTMLOptionElement(mi18n("PLEASE_SELECT"), '');
$oSelHandlerCatArt->addOptionElement(0, $oOption);
$oSelHandlerCatArt->addCategories(0, true, false, false, true, true);
$oSelHandlerCatArt->setDefault($iHandlerCatArt);

$oCkbUpdate        = new cHTMLCheckbox('ckbUpdateHandlerID'.$cCurrentContainer, 'enabled');
$oCkbUpdate->setEvent('click', 'if (this.checked) {document.forms[0].selHandlerCatArt'.$cCurrentContainer.'.disabled = false;} else {document.forms[0].selHandlerCatArt'.$cCurrentContainer.'.disabled = true;}');

$oCfgTable->setCell('handler', 0, mi18n("HANDLER_ARTICLE_COLON"));
$oCfgTable->setCell('handler', 1, $oSelHandlerCatArt->render()."\n ".$oCkbUpdate->toHtml(false).mi18n("UPDATE"));

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

$oCfgTable->setCell('join_01', 0, mi18n("JOIN_COLON"));

if ($oRcpGroups->count() == 0) {
    // No groups available, only default group possible
    $oRadJoinDefault = new cHTMLRadioButton('radJoin'.$cCurrentContainer, 'Default', '', true);
    $oCfgTable->setCell('join_01', 1, $oRadJoinDefault->toHtml(false).mi18n("DEFAULT_GROUP"));
} else {
    // Groups available, show different group join options

    // Join default group only
    if ($aSettings['JoinSel'] == 'Default') {
        $oRadJoinDefault = new cHTMLRadioButton('radJoin'.$cCurrentContainer, 'Default', '', true);
    } else {
        $oRadJoinDefault = new cHTMLRadioButton('radJoin'.$cCurrentContainer, 'Default');
    }
    $oCfgTable->setCell('join_01', 1, $oRadJoinDefault->toHtml(false).mi18n("DEFAULT_GROUP"));

    // Join admin selected groups automatically
    if ($aSettings['JoinSel'] == 'Selected') {
        $oRadJoinSelected = new cHTMLRadioButton('radJoin'.$cCurrentContainer, 'Selected', '', true);
    } else {
        $oRadJoinSelected = new cHTMLRadioButton('radJoin'.$cCurrentContainer, 'Selected');
    }
    $oCfgTable->setCell('join_02', 0, '');
    $oCfgTable->setCell('join_02', 1, $oRadJoinSelected->toHtml(false).mi18n("SELECTED_GROUP_S"));

    // Join the groups the user has selected (-> provide a list for the user), optionally, the user may select more than one group
    if ($aSettings['JoinSel'] == 'UserSelected') {
        $oRadJoinUserSel  = new cHTMLRadioButton('radJoin'.$cCurrentContainer, 'UserSelected', '', true);
        $oCkbJoinMultiple = new cHTMLCheckbox('ckbJoinMultiple'.$cCurrentContainer, 'enabled', '', $aSettings['JoinMultiple']);
    } else {
        $oRadJoinUserSel  = new cHTMLRadioButton('radJoin'.$cCurrentContainer, 'UserSelected');
        $oCkbJoinMultiple = new cHTMLCheckbox('ckbJoinMultiple'.$cCurrentContainer, 'enabled', '', false, true);
    }
    //$oRadJoinUserSel->setEvent('click', "document.forms[0].elements['ckbJoinMultiple".$cCurrentContainer."'].disabled = false; document.forms[0].selGroup".$cCurrentContainer.".disabled = false;");
    $oCfgTable->setCell('join_03', 0, '');
    $oCfgTable->setCell('join_03', 1, $oRadJoinUserSel->toHtml(false).mi18n("GROUP_S_USER_SELECTED").'<br />'."\n".$oCkbJoinMultiple->toHtml(false).mi18n("GROUP_SELECTION_MULTIPLE"));

    $oCfgTable->setCell('groups', 0, mi18n("SELECT_GROUP_S_COLON"));

    // Show groups
    // Trick: To save multiple selections in <select>-Element, add some JS which saves the
    // selection, comma separated in a hidden input field on change.
    $sSkript = '
<script type="text/javascript"><!--
function fncUpdateSel() {
    var strSel = "";
    for (i = 0; i < document.forms[0].selGroup'.$cCurrentContainer.'.length; i++) {
        if (document.forms[0].selGroup'.$cCurrentContainer.'.options[i].selected == true) {
            if (strSel != "") {
                strSel = strSel + ",";
            }
            strSel = strSel + document.forms[0].selGroup'.$cCurrentContainer.'.options[i].value;
        }
    }
    document.forms[0].elements["hidJoinGroups'.$cCurrentContainer.'"].value = strSel;
}
//--></script>
';

    if ($aSettings['JoinSel'] == 'Default') {
        $oSelGroup = new cHTMLSelectElement('selGroup'.$cCurrentContainer, '', '', true);
    } else {
        $oSelGroup = new cHTMLSelectElement('selGroup'.$cCurrentContainer, '');
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

    $oHidGroups = new cHTMLHiddenField('hidJoinGroups'.$cCurrentContainer, $aSettings['JoinGroups']);
    $oCfgTable->setCell('groups', 1, $sSkript.$oSelGroup->render().$oHidGroups->render());
}

// Options: Message type (user [->selectbox], text or html)
$oCfgTable->setCell('options_01', 0, mi18n("OPTIONS_COLON"));

$oSelMsgType = new cHTMLSelectElement('selMessageType'.$cCurrentContainer);
$oOption = new cHTMLOptionElement(mi18n("USER_SELECTED"), "user");
$oSelMsgType->addOptionElement(0, $oOption);
$oOption = new cHTMLOptionElement(mi18n("TEXT_ONLY"), "text");
$oSelMsgType->addOptionElement(1, $oOption);
$oOption = new cHTMLOptionElement(mi18n("HTML_AND_TEXT"), "html");
$oSelMsgType->addOptionElement(2, $oOption);
$oSelMsgType->setDefault($aSettings['JoinMessageType']);

$oCfgTable->setCell('options_01', 1, mi18n("DEFAULT_MESSAGE_TYPE_COLON").' '.$oSelMsgType->render());

// Frontend Link
$oCfgTable->setCell('link_01', 0, mi18n("FRONTEND_USERS_COLON"));
$oCkbLink = new cHTMLCheckbox('ckbFrontendLink'.$cCurrentContainer, 'enabled', '', $aSettings['FrontendLink']);

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

$oCfgTable->setCell('link_01', 1, $oCkbLink->toHtml(false).mi18n("ACTIVATE_LINK"));

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
$oCfgTable->setCell('link_02', 1, mi18n("CONFIRMATION_MEANS_COLON").'<br />'.
    $oRadActivateUser->toHtml(false).mi18n("ACTIVATE").
    $oRadActivateNothing->toHtml(false).mi18n("NO_CHANGES"));

// Link: Cancellation options
$oCfgTable->setCell('link_03', 0, '');

$oCfgTable->setCell('link_03', 1, mi18n("CANCELLATION_MEANS_COLON").'<br />'.
    $oRadDelDelete->toHtml(false).mi18n("DELETE").
    $oRadDelDisable->toHtml(false).mi18n("DISABLE").
    $oRadDelNothing->toHtml(false).mi18n("NO_CHANGES"));

$oCfgTable->render(true);

?><?php