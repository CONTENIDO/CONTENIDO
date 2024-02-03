?><?php

/**
 * Description: Newsletter form input
 *
 * @version    1.0.0
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 */

/**
 * @var int $cCurrentContainer
 */

$lang = cSecurity::toInteger(cRegistry::getLanguageId());
$client = cSecurity::toInteger(cRegistry::getClientId());

// Initialisation
$oClientLang       = new cApiClientLanguage(false, $client, $lang);

/*
 *  Used variables:
 *  JoinSel:         Selection, which group will be joined (Default, Selected, UserSelected)
 *  JoinMultiple:    If JoinSel = UserSelected then: If more than one group has been specified, select more than one
 *  JoinGroups:      Selected group(s)
 *  JoinMessageType: Message type for new recipients: User select (user), text or html
 *  OptNewWindow:    Open handler window in new browser window?
 */

$aSettings = [
    'JoinSel'         => $oClientLang->getProperty('newsletter', 'joinsel'),
    'JoinMultiple'    => $oClientLang->getProperty('newsletter', 'joinmultiple'),
    'JoinGroups'      => $oClientLang->getProperty('newsletter', 'joingroups'),
    'JoinMessageType' => $oClientLang->getProperty('newsletter', 'joinmessagetype'),
    'OptNewWindow'    => "CMS_VALUE[4]",
];

// Setting default values
/*
 *  If nothing is set or if the option 'UserSelected' has been activated, but no groups
 *  have been selected, set option 'Default'. Note, that requiring to select groups when option
 *  'Selected' has been activated doesn't make so much sense here (even, as it is possible to do).
 *  Why? Because the groups to be joined have to be specified on the handler page, not here...
 *  To prevent users to select a group (or not to save the selection) when the option 'Selected' is
 *  active should make more problems than just to ignore the selection in the Output area
 */

if ($aSettings['JoinSel'] == '' || ($aSettings['JoinSel'] == 'UserSelected' && $aSettings['JoinGroups'] == '')) {
    $aSettings['JoinSel'] = 'Default';
}

// Build form fields list and initialize request values
$aAvailableFields = [
    'ckbJoinMultiple',
    'ckbUpdateHandlerID',
    'hidAction',
    'hidJoinGroups',
    'radJoin',
    'selGroup',
    'selHandlerCatArt',
    'selMessageType',
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
    if ($aRequest['radJoin'] != '' && $aRequest['radJoin'] != $aSettings['JoinSel']) {
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
    if ($aRequest['ckbUpdateHandlerID'] == 'enabled') {
        // Trick: If UpdateHandlerID is enabled, save id as client setting
        $iHandlerCatArt = $aRequest['selHandlerCatArt'];
        $oClientLang->setProperty('newsletter', 'idcatart', $iHandlerCatArt);
    }
}

// Getting current handler article id
$iHandlerCatArt = $oClientLang->getProperty('newsletter', 'idcatart');
unset($oClientLang);

$sCssStyle = '';
$sJavaScript = '';

// Showing options
$oCfgTable = new UI_Config_Table();

$oHidAction = new cHTMLHiddenField($aFormFields['hidAction'], 'save');

$oSelHandlerCatArt = new cHTMLInputSelectElement($aFormFields['selHandlerCatArt'], '', '', true);
$oOption           = new cHTMLOptionElement(mi18n("PLEASE_SELECT"), '');
$oSelHandlerCatArt->addOptionElement(0, $oOption);
$oSelHandlerCatArt->addCategories(0, true, false, false, true);
$oSelHandlerCatArt->setDefault($iHandlerCatArt);

$oCkbUpdate = new cHTMLCheckbox($aFormFields['ckbUpdateHandlerID'], 'enabled');
$oCkbUpdate->setEvent('click', 'document.forms[0].' . $aFormFields['selHandlerCatArt'] . '.disabled = !this.checked;');
$oCkbUpdate->setLabelText(mi18n("UPDATE"));

$oCfgTable->setCell('handler', 0, mi18n("HANDLER_ARTICLE"));
$oCfgTable->setCellClass('handler', 1, $aFormFields['ckbUpdateHandlerID'] . '_wrapper');
$oCfgTable->setCell('handler', 1, $oHidAction->render() . $oSelHandlerCatArt->render() . "\n " . $oCkbUpdate->toHtml());
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
    $oRadJoinUserSel->setLabelText(mi18n("GROUP_USER_SELECTED"));
    $oCkbJoinMultiple->setLabelText(mi18n("MULTIPLE_GROUP_SELECTION"));
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

$oCfgTable->setCell('options_01', 1, mi18n("DEFAULT_MESSAGE_TYPE") . ' ' . $oSelMsgType->render());

// Options: Open handler article in new window?
$oCfgTable->setCell('options_02', 0, '');
$oCkbNewWindow = new cHTMLCheckbox("CMS_VAR[4]", 'enabled', '', $aSettings['OptNewWindow'] === 'enabled');
$oCkbNewWindow->setLabelText(mi18n("HANDLER_NEW_WINDOW"));
$oCfgTable->setCell('options_02', 1, $oCkbNewWindow->toHtml());

echo $sCssStyle . "\n";
$oCfgTable->render(true);
echo $sJavaScript . "\n";

