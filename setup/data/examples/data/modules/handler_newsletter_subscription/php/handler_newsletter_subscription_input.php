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
    $aSettings['FrontendConfirm'] = 'ActivateUser';
}
if ($aSettings['FrontendDel'] == '') {
    $aSettings['FrontendDel'] = 'DeleteUser';
}

// Build form fields list and initialize request values
$aAvailableFields = [
    'ckbFrontendLink',
    'ckbJoinMultiple',
    'ckbUpdateHandlerID',
    'hidAction',
    'hidJoinGroups',
    'radJoin',
    'selGroup',
    'selHandlerCatArt',
    'selMessageType',
    'txtSender',
];
$aFormFields = [];
$aRequest = [];
foreach ($aAvailableFields as $value) {
    $aFormFields[$value] = $value . $cCurrentContainer;
    $aRequest[$value] = $_REQUEST[$value . $cCurrentContainer] ?? '';
}

if (!is_numeric($aRequest['selHandlerCatArt']) || $aRequest['selHandlerCatArt'] < 0) {
    $aRequest['selHandlerCatArt'] = 0;
}

// Saving changes, if any
if ($aRequest['hidAction'] == 'save') {
    if ($aRequest['radJoin'] != '') {
        $aSettings['JoinSel'] = $aRequest['radJoin'];
        $oClientLang->setProperty('newsletter', 'joinsel', $aSettings['JoinSel']);
    }
    if ($aRequest['ckbJoinMultiple'] != $aSettings['JoinMultiple']) {
        $aSettings['JoinMultiple'] = $aRequest['ckbJoinMultiple'];
        $oClientLang->setProperty('newsletter', 'joinmultiple', $aSettings['JoinMultiple']);
    }
    if (isset($aRequest['selGroup']) && is_array($aRequest['selGroup'])) {
        $aSettings['JoinGroups'] = implode(',', $aRequest['selGroup']);
        $oClientLang->setProperty('newsletter', 'joingroups', $aSettings['JoinGroups']);
    }
    if ($aRequest['selMessageType'] != $aSettings['JoinMessageType']) {
        $aSettings['JoinMessageType'] = $aRequest['selMessageType'];
        $oClientLang->setProperty('newsletter', 'joinmessagetype', $aSettings['JoinMessageType']);
    }
    if ($aRequest['ckbFrontendLink'] != $aSettings['FrontendLink']) {
        $aSettings['FrontendLink'] = $aRequest['ckbFrontendLink'];
        $oClient->setProperty('newsletter', 'frontendlink', $aSettings['FrontendLink']);
    }
    if ($aRequest['ckbUpdateHandlerID'] == 'enabled') {
        // Trick: If UpdateHandlerID is enabled, save id as client setting
        $iHandlerCatArt = $aRequest['selHandlerCatArt'];
        $oClientLang->setProperty('newsletter', 'idcatart', $iHandlerCatArt);
    }
    if (isValidMail($aRequest['txtSender']) && $aRequest['txtSender'] != $aSettings['SenderEMail']) {
        $aSettings['SenderEMail'] = $aRequest['txtSender'];
        $oClient->setProperty('global', 'sender-email', $aSettings['SenderEMail']);
    }
}

// Getting current handler article id
$iHandlerCatArt = $oClientLang->getProperty('newsletter', 'idcatart');
unset($oClientLang, $oClient);

$sCssStyle = '';
$sJavaScript = '';

// Show options
$oCfgTable  = new UI_Config_Table();

$oHidAction = new cHTMLHiddenField($aFormFields['hidAction'], 'save');

$oTxtSender = new cHTMLTextbox($aFormFields['txtSender'], $aSettings['SenderEMail'], 30);

$oCfgTable->setCell('sender', 0, mi18n("SENDER_EMAIL_COLON"));
$oCfgTable->setCell('sender', 1, $oHidAction->render().$oTxtSender->render());

$oSelHandlerCatArt = new cHTMLInputSelectElement($aFormFields['selHandlerCatArt'], 1, '', true);
$oOption           = new cHTMLOptionElement(mi18n("PLEASE_SELECT"), '');
$oSelHandlerCatArt->addOptionElement(0, $oOption);
$oSelHandlerCatArt->addCategories(0, true, false, false, true, true);
$oSelHandlerCatArt->setDefault($iHandlerCatArt);

$oCkbUpdate = new cHTMLCheckbox($aFormFields['ckbUpdateHandlerID'], 'enabled');
$oCkbUpdate->setEvent('click', 'document.forms[0].' . $aFormFields['selHandlerCatArt'] . '.disabled = !this.checked;');
$oCkbUpdate->setLabelText(mi18n("UPDATE"));

$oCfgTable->setCell('handler', 0, mi18n("HANDLER_ARTICLE_COLON"));
$oCfgTable->setCellClass('handler', 1, $aFormFields['ckbUpdateHandlerID'] . '_wrapper');
$oCfgTable->setCell('handler', 1, $oSelHandlerCatArt->render() . "\n" . $oCkbUpdate->toHtml());
$sCssStyle .= '<style>.' .  $aFormFields['ckbUpdateHandlerID'] . '_wrapper .checkbox_wrapper {display:inline-block;}</style>';

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
    $oRadJoinDefault = new cHTMLRadioButton($aFormFields['radJoin'], 'Default', '', true);
    $oRadJoinDefault->setLabelText(mi18n("DEFAULT_GROUP"));
    $oCfgTable->setCell('join_01', 1, $oRadJoinDefault->toHtml());
} else {
    // Groups available, show different group join options

    // Join default group only
    $checked = $aSettings['JoinSel'] === 'Default';
    $oRadJoinDefault = new cHTMLRadioButton($aFormFields['radJoin'], 'Default', '', $checked);
    $oRadJoinDefault->setLabelText(mi18n("DEFAULT_GROUP"));
    $oCfgTable->setCell('join_01', 1, $oRadJoinDefault->toHtml());

    // Join admin selected groups automatically
    $checked = $aSettings['JoinSel'] === 'Selected';
    $oRadJoinSelected = new cHTMLRadioButton($aFormFields['radJoin'], 'Selected', '', $checked);
    $oRadJoinSelected->setLabelText(mi18n("SELECTED_GROUP_S"));
    $oCfgTable->setCell('join_02', 0, '');
    $oCfgTable->setCell('join_02', 1, $oRadJoinSelected->toHtml());

    // Join the groups the user has selected (-> provide a list for the user), optionally, the user may select more than one group
    if ($aSettings['JoinSel'] == 'UserSelected') {
        $oRadJoinUserSel  = new cHTMLRadioButton($aFormFields['radJoin'], 'UserSelected', '', true);
        $oCkbJoinMultiple = new cHTMLCheckbox($aFormFields['ckbJoinMultiple'], 'enabled', '', $aSettings['JoinMultiple']);
    } else {
        $oRadJoinUserSel  = new cHTMLRadioButton($aFormFields['radJoin'], 'UserSelected');
        $oCkbJoinMultiple = new cHTMLCheckbox($aFormFields['ckbJoinMultiple'], 'enabled', '', false, true);
    }
    $oRadJoinUserSel->setLabelText(mi18n("GROUP_S_USER_SELECTED"));
    $oCkbJoinMultiple->setLabelText(mi18n("GROUP_SELECTION_MULTIPLE"));
    $oCfgTable->setCell('join_03', 0, '');
    $oCfgTable->setCell('join_03', 1, $oRadJoinUserSel->toHtml() . "<br />\n" . $oCkbJoinMultiple->toHtml());

    $oCfgTable->setCell('groups', 0, mi18n("SELECT_GROUP_S_COLON"));

    // Show groups
    // Trick: To save multiple selections in <select>-Element, add some JS which saves the
    // selection, comma separated in a hidden input field on change.
    $sJavaScript .= '
<script type="text/javascript">
(function(Con, $) {
    $(function() {
        var radJoin = $("input[name=' . $aFormFields['radJoin'] . ']"),
            selGoup = $("#' . $aFormFields['selGroup'] . '"),
            ckbJoinMultiple = $("input[name=' . $aFormFields['ckbJoinMultiple'] . ']"),
            hidJoinGroups = $("input[name=' . $aFormFields['hidJoinGroups'] . ']");

        // Enable/disable Goup selectbox and JoinMultiple checkbox depending on
        // checked Join radiobutton.
        radJoin.on("change", function() {
            var checkedRadJoin = radJoin.filter(":checked");
            selGoup.prop("disabled", checkedRadJoin.val() !== "UserSelected");
            ckbJoinMultiple.prop("disabled", checkedRadJoin.val() !== "UserSelected");
            selGoup.val("").trigger("change");
        });

        // Reset group selection when JoinMultiple checkbox status changes
        ckbJoinMultiple.on("change", function() {
            selGoup.val("").trigger("change");
        });

        // Handle group selection, prevent multiple selection, if JoinMultiple
        // checkbox is not checked and take over values to hidden JoinGroups.
        selGoup.on("change", function() {
            if (!ckbJoinMultiple.prop("checked")) {
                // Multiple selection not allowed
                if (selGoup.val() && selGoup.val().length > 1) {
                    var selLength = selGoup.val().length;
                    selGoup.val(selGoup.val()[selLength - 1]);
                }                
            }
            hidJoinGroups.val(selGoup.val() ? selGoup.val().join(",") : "");
        });
    });
})(Con, Con.$);
</script>
';

    $disabled = $aSettings['JoinSel'] === 'Default';
    $oSelGroup = new cHTMLSelectElement($aFormFields['selGroup'], '', '', $disabled);
    $oSelGroup->setID($aFormFields['selGroup']);
    $oSelGroup->setSize(5);
    $oSelGroup->setMultiselect();

    $aGroups = explode(',', $aSettings['JoinGroups']);
    while ($oRcpGroup = $oRcpGroups->next()) {
        $iID = $oRcpGroup->get('idnewsgroup');
        $selected = in_array($iID, $aGroups);
        $oOption = new cHTMLOptionElement($oRcpGroup->get('groupname'), $iID, $selected);
        $oSelGroup->addOptionElement($iID, $oOption);
    }

    $oHidGroups = new cHTMLHiddenField($aFormFields['hidJoinGroups'], $aSettings['JoinGroups']);
    $oCfgTable->setCell('groups', 1, $oSelGroup->render() . $oHidGroups->render());
}

// Options: Message type (user [->selectbox], text or html)
$oCfgTable->setCell('options_01', 0, mi18n("OPTIONS_COLON"));

$oSelMsgType = new cHTMLSelectElement($aFormFields['selMessageType']);
$oSelMsgType->autoFill([
    'user' => mi18n("USER_SELECTED"),
    'text' => mi18n("TEXT_ONLY"),
    'html' => mi18n("HTML_AND_TEXT"),
]);
$oSelMsgType->setDefault($aSettings['JoinMessageType']);

$oCfgTable->setCell('options_01', 1, mi18n("DEFAULT_MESSAGE_TYPE_COLON") . ' ' . $oSelMsgType->render());

// Frontend Link
$oCfgTable->setCell('link_01', 0, mi18n("FRONTEND_USERS_COLON"));
$oCkbLink = new cHTMLCheckbox($aFormFields['ckbFrontendLink'], 'enabled', '', $aSettings['FrontendLink']);
$oCkbLink->setLabelText(mi18n("ACTIVATE_LINK"));

$sJavaScript .= '
<script type="text/javascript">
(function(Con, $) {
    $(function() {
        var ckbFrontendLink = $("input[name=' . $aFormFields['ckbFrontendLink'] . ']");

        // Enable/disable frontend user function radio buttons depending on
        // checked state of FrontendLink checkbox
        ckbFrontendLink.on("change", function() {
            var checked = ckbFrontendLink.prop("checked");
            $("input[name=\'CMS_VAR[5]\']").prop("disabled", !checked);
            $("input[name=\'CMS_VAR[6]\']").prop("disabled", !checked);
        });
    });
})(Con, Con.$);
</script>
';

$oCfgTable->setCell('link_01', 1, $oCkbLink->toHtml());

// Link: Activation options
$oCfgTable->setCell('link_02', 0, '');

switch ($aSettings['FrontendConfirm']) {
    case 'Nothing':
        $oRadActivateUser    = new cHTMLRadioButton("CMS_VAR[5]", 'ActivateUser', '', false);
        $oRadActivateNothing = new cHTMLRadioButton("CMS_VAR[5]", 'Nothing',      '', true);
        break;
    default:
        $oRadActivateUser    = new cHTMLRadioButton("CMS_VAR[5]", 'ActivateUser', '', true);
        $oRadActivateNothing = new cHTMLRadioButton("CMS_VAR[5]", 'Nothing',      '', false);
}

switch ($aSettings['FrontendDel']) {
    case 'DisableUser':
        $oRadDelDelete  = new cHTMLRadioButton("CMS_VAR[6]", 'DeleteUser',  '', false);
        $oRadDelDisable = new cHTMLRadioButton("CMS_VAR[6]", 'DisableUser', '', true);
        $oRadDelNothing = new cHTMLRadioButton("CMS_VAR[6]", 'Nothing',     '', false);
        break;
    case 'Nothing':
        $oRadDelDelete  = new cHTMLRadioButton("CMS_VAR[6]", 'DeleteUser',  '', false);
        $oRadDelDisable = new cHTMLRadioButton("CMS_VAR[6]", 'DisableUser', '', false);
        $oRadDelNothing = new cHTMLRadioButton("CMS_VAR[6]", 'Nothing',     '', true);
        break;
    default:
        $oRadDelDelete  = new cHTMLRadioButton("CMS_VAR[6]", 'DeleteUser',  '', true);
        $oRadDelDisable = new cHTMLRadioButton("CMS_VAR[6]", 'DisableUser', '', false);
        $oRadDelNothing = new cHTMLRadioButton("CMS_VAR[6]", 'Nothing',     '', false);
}

$oRadActivateUser->setLabelText(mi18n("ACTIVATE"));
$oRadActivateNothing->setLabelText(mi18n("NO_CHANGES"));
$oRadDelDelete->setLabelText(mi18n("DELETE"));
$oRadDelDisable->setLabelText(mi18n("DISABLE"));
$oRadDelNothing->setLabelText(mi18n("NO_CHANGES"));

if ($aSettings['FrontendLink'] == '') {
    $oRadActivateUser->setDisabled(true);
    $oRadActivateNothing->setDisabled(true);
    $oRadDelDelete->setDisabled(true);
    $oRadDelDisable->setDisabled(true);
    $oRadDelNothing->setDisabled(true);
}
$oCfgTable->setCell('link_02', 1,
    mi18n("CONFIRMATION_MEANS_COLON") . "<br />\n"
    . $oRadActivateUser->toHtml()
    . $oRadActivateNothing->toHtml()
);

// Link: Cancellation options
$oCfgTable->setCell('link_03', 0, '');

$oCfgTable->setCell('link_03', 1,
    mi18n("CANCELLATION_MEANS_COLON") . "<br />\n"
    . $oRadDelDelete->toHtml()
    . $oRadDelDisable->toHtml()
    . $oRadDelNothing->toHtml()
);

echo $sCssStyle . "\n";
$oCfgTable->render(true);
echo $sJavaScript . "\n";

?><?php