<?php

/**
 * This file contains the backend page for editing meta tags.
 *
 * @package Core
 * @subpackage Backend
 * @author Fulai Zhang
 * @author ilja.schwarz@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.str.php');
cInclude('includes', 'functions.pathresolver.php');

// ugly globals that are used in this script
global $tpl, $contenido, $notification, $idcatart, $idtpl;
global $syncoptions, $tmp_notification, $versioningState;

$db = cRegistry::getDb();
$perm = cRegistry::getPerm();
$auth = cRegistry::getAuth();
$belang = cRegistry::getBackendLanguage();
$frame = cRegistry::getFrame();
$action = cRegistry::getAction();
$idart = cRegistry::getArticleId();
$cfg = cRegistry::getConfig();
$client = cRegistry::getClientId();
$lang = cRegistry::getLanguageId();
$sess = cRegistry::getSession();
$area = cRegistry::getArea();
$idcat = cRegistry::getCategoryId();
$idcatlang = cRegistry::getCategoryLanguageId();

// Reset template
$tpl->reset();

// Admin rights
$isAdmin = cPermission::checkAdminPermission($auth->getPerms());

// Check permissions
if (!$perm->have_perm_area_action($area, 'con_meta_edit') && !$perm->have_perm_area_action_item($area, 'con_meta_edit', $idcat)) {
    // User has no permission to see this form
    $notifications[] = $notification->returnNotification('error', i18n('Permission denied'));
    return;
}

$versioning = new cContentVersioning();

// Initialize $_REQUEST with common used keys to prevent PHP 'Undefined array key' warnings
foreach (['idArtLangVersion'] as $_key) {
    if (!isset($_REQUEST[$_key])) {
        $_REQUEST[$_key] = '';
    }
}

// Get article (version) data
$art = new cApiArticleLanguage();
$art->loadByArticleAndLanguageId(cSecurity::toInteger($idart), cSecurity::toInteger($lang));

if ($_REQUEST['idArtLangVersion'] == NULL && $versioning->getState() == 'advanced') {
    $art = new cApiArticleLanguageVersion($versioning->getEditableArticleId($art->getField('idartlang')));
    //$art = new cApiArticleLanguageVersion($versioning->getEditableArticleId($idArtLang));
} elseif ($versioning->getState() == 'advanced' && $_REQUEST['idArtLangVersion'] != 'current'
    || $versioning->getState() == 'simple' && ($_REQUEST['idArtLangVersion'] != NULL
    && is_numeric ($_REQUEST['idArtLangVersion']) || is_numeric ($_REQUEST['idArtLangVersion']))) {
    $art = new cApiArticleLanguageVersion((int) $_REQUEST['idArtLangVersion']);
}

// if there is no (editable) version yet, output the published version
if (!$art->isLoaded()) {
    $art = new cApiArticleLanguage();
    $art->loadByArticleAndLanguageId(cSecurity::toInteger($idart), cSecurity::toInteger($lang));
}
global $selectedArticleId;
if ($_REQUEST['idArtLangVersion'] != NULL) {
    $selectedArticleId = $_REQUEST['idArtLangVersion'];
}
$articleType = $versioning->getArticleType(
    $_REQUEST['idArtLangVersion'],
    $art->getField('idartlang'),
    $action,
    $selectedArticleId
);

// Set as current/editable
switch ($versioning->getState()) {
    case 'advanced':
        if ($action == 'copyto') {
            if (is_numeric($_REQUEST['idArtLangVersion']) && $articleType == 'current') {
                // editable->current
                $artLangVersion = NULL;
                $artLangVersion = new cApiArticleLanguageVersion((int) $_REQUEST['idArtLangVersion']);
                if (isset($artLangVersion)) {
                    $artLangVersion->markAsCurrent('meta');
                    $selectedArticleId = 'current';
                }

                // Execute cec hook
                cApiCecHook::execute('Contenido.Content.CopyToVersion', [
                    'idart' => $artLangVersion->get("idart"),
                    'idlang' => cRegistry::getLanguageId()
                ]);
            } elseif (is_numeric($_REQUEST['idArtLangVersion']) && $articleType == 'editable') {
                // version->editable
                $artLangVersion = new cApiArticleLanguageVersion((int) $_REQUEST['idArtLangVersion']);
                $artLangVersion->markAsEditable('meta');
                $articleType = $versioning->getArticleType($_REQUEST['idArtLangVersion'], (int) $_REQUEST['idartlang'], $action, $selectedArticleId);
                $selectedArticleId = 'editable';

                // Execute cec hook
                cApiCecHook::execute('Contenido.Content.CopyToVersion', [
                    'idart' => $artLangVersion->get("idart"),
                    'idlang' => cRegistry::getLanguageId()
                ]);
            } elseif ($_REQUEST['idArtLangVersion'] == 'current') {
                // current->editable
                $artLang = new cApiArticleLanguage((int) $_REQUEST['idartlang']);
                $artLang->markAsEditable('meta');
                $articleType = $versioning->getArticleType($_REQUEST['idArtLangVersion'], (int) $_REQUEST['idartlang'], $action, $selectedArticleId);
                $selectedArticleId = 'editable';

                // Execute cec hook
                cApiCecHook::execute('Contenido.Content.CopyToVersion', [
                    'idart' => $artLang->get("idart"),
                    'idlang' => cRegistry::getLanguageId()
                ]);
            }
        }

        break;
    case 'simple':
        if ($action == 'copyto') {
            if (is_numeric($_REQUEST['idArtLangVersion'])) {
                $artLangVersion = new cApiArticleLanguageVersion((int) $_REQUEST['idArtLangVersion']);
                $artLangVersion->markAsCurrent('meta');
                $selectedArticleId = 'current';
            }
        }

        break;
    default:
        break;
}

// Check is form edit available
$disabled = '';
if ($art->getField('created')) {
    // Get cApiInUseCollection
    $col = new cApiInUseCollection();

    // Remove all own marks
    $col->removeSessionMarks($sess->id);
    $obj = $col->checkMark('article', $art->getField('idartlang'));

    if ($obj === false || $obj->get('userid') == $auth->auth['uid']) {
        $col->markInUse('article', $art->getField('idartlang'), $sess->id, $auth->auth['uid']);
        $disabled = '';
    } else {
        $vuser = new cApiUser($obj->get('userid'));
        $inUseUser = $vuser->getField('username');
        $inUseUserRealName = $vuser->getField('realname');
        $disabled = 'disabled="disabled"';

        $message = sprintf(i18n('Article is in use by %s (%s)'), $inUseUser, $inUseUserRealName);
        $notifications[] = $notification->returnNotification('warning', $message);
        $tpl->set("s", "REASON", sprintf(i18n('Article is in use by %s (%s)'), $inUseUser, $inUseUserRealName));
    }

    if ($art->getField('locked') == 1 && false === $isAdmin) {
        $disabled = 'disabled="disabled"';
        $tpl->set('s', 'DISABLED', ' ' . $disabled);
        $notifications[] = $notification->returnNotification('warning', i18n('This article is currently frozen and can not be edited!'));
        $tpl->set("s", "REASON", i18n('This article is currently frozen and can not be edited!'));
    } elseif ($versioning->getState() == 'advanced' && $articleType == 'editable'
        || $versioning->getState() == 'simple' && $articleType != 'version'
        || $versioning->getState() == 'disabled'){
        $tpl->set('s', 'DISABLED', '');
        $tpl->set("s", "REASON", "");
    } else {
        $disabled = 'disabled="disabled"';
        $tpl->set('s', 'DISABLED', ' ' . $disabled);
    }

    if ($disabled == '') {
        $tpl->set('s', 'IS_DATETIMEPICKER_DISABLED', 0);
        $tpl->set('s', 'BUTTONIMAGE', 'but_ok.gif');
        $tpl->set('s', 'BUTTONDISABLE', $disabled);
        $tpl->set("s", "REASON", "");
    } else {
        $tpl->set('s', 'IS_DATETIMEPICKER_DISABLED', 1);
        $tpl->set('s', 'BUTTONIMAGE', 'but_ok_off.gif');
        $tpl->set('s', 'BUTTONDISABLE', $disabled);
        $tpl->set("s", "REASON", "");
    }
}

// Assign head values to page
$lang_short = cString::getPartOfString(cString::toLowerCase($belang), 0, 2);
$langscripts = '';
if ($lang_short != 'en') {
    $langscripts = '
<script type="text/javascript" src="scripts/jquery/plugins/timepicker-' . $lang_short . '.js"></script>
<script type="text/javascript" src="scripts/jquery/plugins/datepicker-' . $lang_short . '.js"></script>
    ';
}
$tpl->set('s', 'CAL_LANG', $langscripts);
$tpl->set('s', 'DISPLAY_MENU', 1);
$tpl->set('s', 'SYNCOPTIONS', -1);

// Assign body values
$breadcrumb = renderBackendBreadcrumb($syncoptions, true, true);
$tpl->set('s', 'CATEGORY', $breadcrumb);

// Assign form values
$tpl->set('s', 'ACTION', $sess->url('main.php?area=' . $area . '&frame=' . $frame . '&action=con_meta_saveart'));

// Assign form hidden elements values
$tpl->set('s', 'IDART', $idart);
$tpl->set('s', 'IDCAT', $idcat);
$tpl->set('s', 'TMP_FIRSTEDIT', $art->getField('created') ? 0 : 1);
$tpl->set('s', 'IDARTLANG', $art->getField('idartlang'));
$tpl->set('s', 'TITEL', i18n('SEO administration'));

// Assign notification
if (isset($tmp_notification)) {
    $tpl->set('s', 'NOTIFICATION', '<tr><td colspan="4">' . $tmp_notification . '<br></td></tr>');
} else {
    $tpl->set('s', 'NOTIFICATION', '');
}
if (!empty($notifications)) {
        error_log(implode('', $notifications));
    $tpl->set('s', 'NOTIFICATIONS', implode('', $notifications));
} else {
    $tpl->set('s', 'NOTIFICATIONS', '');
}

// Assign form page seo elements values (incl. CON-2696 change, undo CON-2532 changes)
$tpl->set('s', 'LINK', $art->getLink());

$tpl->set('s', 'FULL_LINK', cUri::getInstance()->build([
    'idart' => $art->get('idart'),
    'lang' => $art->get('idlang')
], true));

$tpl->set('s', 'PAGE_TITLE', conHtmlSpecialChars(cSecurity::unFilter(stripslashes($art->getField('pagetitle')))));

$tpl->set('s', 'ALIAS', cSecurity::unFilter(stripslashes($art->getField('urlname'))));

// Assign Meta-Tags elements
$availableTags = conGetAvailableMetaTagTypes();
$managedTypes = [
    'author',
    'description',
    'expires',
    'keywords',
    'revisit-after',
    'robots',
    'copyright'
];

$metaPreview = [];

// Set meta tags values
foreach ($availableTags as $key => $value) {
    $contentMetaValue = conGetMetaValue($art->getField('idartlang'), $key, $art->getField('version'));
    $contentMetaValue = str_replace('"', '', $contentMetaValue);

    $metaPreview[] = [
        'fieldname' => $value['fieldname'],
        'name' => $value['metatype'],
        'content' => cSecurity::unFilter(stripslashes($contentMetaValue))
    ];

    // Set meta values to inputs
    if (in_array($value['metatype'], $managedTypes)) {
        if ($value['metatype'] == 'robots') {
            if (conGetMetaValue($art->getField('idartlang'), $key) == '') {
                conSetMetaValue($art->getField('idartlang'), $key, 'index, follow');

                $i = 0;
                foreach ($metaPreview as $k => $metaRow) {
                    if ($metaRow['name'] == 'robots') {
                        $metaPreview[$i]['content'] = 'index, follow';
                        break;
                    }
                    $i++;
                }
            }

            // Set robots checkboxes
            $robot_array = explode(', ', conHtmlSpecialChars($contentMetaValue));
            foreach ($robot_array as $instruction) {
                $tpl->set('s', cString::toUpperCase($instruction), 'checked');
            }
        } else {
            $metaType = conHtmlSpecialChars(cSecurity::unFilter(stripslashes($contentMetaValue)));
            $tpl->set('s', cString::toUpperCase($value['metatype']), str_replace('\\', '', $metaType));
        }

        continue;
    }

    // Create add and edit MetaTag form BLOCK
    switch ($value['fieldtype']) {
        case 'text':
            $element = '<input ' . $disabled . '
                            class="metaTag"
                            type="text"
                            name="META' . $value['metatype'] . '"
                            id="META' . $value['metatype'] . '"
                            size="24"
                            maxlength=' . $value['maxlength'] . '
                            value="' . conHtmlSpecialChars($contentMetaValue) . '">';

            break;
        case 'textarea':
            $element = '<textarea ' . $disabled . '
                            class="metaTag"
                            name="META' . $value['metatype'] . '"
                            id="META' . $value['metatype'] . '"
                            rows="3"
                        >' . cSecurity::unFilter(stripslashes($contentMetaValue)) . '</textarea>';

            break;
        case 'date':
            $element = '<input ' . $disabled . '
                            class="metaTag datepickerTextbox"
                            type="text"
                            name="META' . $value['metatype'] . '"
                            id="META' . $value['metatype'] . '"
                            size="24"
                            maxlength=' . $value['maxlength'] . '
                            value="' . conHtmlSpecialChars($contentMetaValue) . '">';

            break;
    }

    $tpl->set('d', 'ARTICLE_LANGUAGE_ID', $art->getField('idartlang'));
    $tpl->set('d', 'ARTICLE_ID', $idart);
    $tpl->set('d', 'CAT_ID', $idcat);
    $tpl->set('d', 'METAFIELDTYPE', $element);
    $tpl->set('d', 'METATITLE', $value['metatype'] . ':');

    if ($versioning->getState() == 'simple' && $articleType == 'current'
            || $versioning->getState() == 'advanced' && $articleType == 'editable'
            || $versioning->getState() == 'disabled' && ($art->getField('locked') != 1 || cPermission::checkSysadminPermission($auth->getPerms()))) {
        $tpl->set('d', 'CURSOR', 'pointer');
        $tpl->set('d', 'DELETE_META',
            "Con.showConfirmation('" .
                    i18n('Are you sure to delete this Meta tag?') . "' ,
                    function() {
                        deleteMetaTag(" . $value['idmetatype'] . "," . $idart . "," . $idcat . "," . $art->getField('idartlang') . ");
                    }
            ); "
        );
    } else {
        $tpl->set('d', 'CURSOR', 'default');
        $tpl->set('d', 'DELETE_META', '');

    }
    $tpl->next();
}

$tpl->set('s', 'SITEMAP_PRIO', $art->getField('sitemapprio'));

switch ($versioning->getState()) {
    case 'advanced':
        $optionElementParameters = $versioning->getDataForSelectElement($art->getField('idartlang'), 'seo');

        // set editable element
        $selectElement = new cHTMLSelectElement('articleVersionSelect', '', 'selectVersionElement');
        if (isset($versioning->editableArticleId)) {
            $optionElement = new cHTMLOptionElement(i18n('Draft'), $versioning->getEditableArticleId($art->getField('idartlang'))); //key($optionElementParameters[max(array_keys($optionElementParameters))]));
            if ($articleType == 'editable') {
                $optionElement->setSelected(true);
            }
            $selectElement->appendOptionElement($optionElement);
            if (count($optionElementParameters) > 0) {
                unset($optionElementParameters[max(array_keys($optionElementParameters))]);
            }
        }

        // check if selected version is availible, else select the next lower version
        $temp_id = $selectedArticleId;
        $temp_ids = [];

        foreach (array_values($optionElementParameters) AS $key => $value) {
            $temp_ids[] = key($value);
        }
        if (!in_array($selectedArticleId, $temp_ids) && $selectedArticleId != 'current'
            && $selectedArticleId != 'editable' && $articleType != 'current' && $articleType != 'editable') {
            foreach ($temp_ids AS $key => $value) {
                if ($value < $selectedArticleId) {
                    $temp_id = $value;
                    break;
                }
            }
        }

        // Create Metatag Version Option Elements
        $optionElement = new cHTMLOptionElement(i18n('Published Version'), 'current');
        if ($articleType == 'current') {
            $optionElement->setSelected(true);
        }
        $selectElement->appendOptionElement($optionElement);

        foreach ($optionElementParameters AS $key => $value) {
            $lastModified = $versioning->getTimeDiff($value[key($value)]);
            $optionElement = new cHTMLOptionElement('Revision ' . $key . ': ' . $lastModified, key($value));
            //if ($articleType == 'version') {
                if ($selectedArticleId == key($value)) {
                    $optionElement->setSelected(true);
                }
            //}
            $selectElement->appendOptionElement($optionElement);
        }
        $selectElement->setEvent("onchange", "selectVersion.idArtLangVersion.value=$('#selectVersionElement option:selected').val();selectVersion.submit()");

        $tpl->set("s", "ACTION2", $sess->url('main.php?area=' . $area . '&frame=' . $frame . '&action=con_meta_change_version'));
        $tpl->set("s", "ACTION3", $sess->url('main.php?area=' . $area . '&frame=' . $frame . '&action=copyto'));

        // Create markAsCurrent Button
        if ($articleType == 'current' || $articleType == 'version') {
            $buttonTitle = i18n('Copy to draft');
        } elseif ($articleType == 'editable') {
            $buttonTitle = i18n('Publish draft');
        }
        $markAsCurrentButton = new cHTMLButton('markAsCurrentButton', $buttonTitle, 'copytobutton');

        $infoButton = new cGuiBackendHelpbox(i18n('<strong>Advanced-mode:</strong> '
                . 'Older SEO versions can be reviewed and restored. Unpublished drafts can be created (For further configurations please go to Administration/System/System configuration).<br/><br/>'
                . 'Changes are only related to SEO!'));

        // box to select article version
        $versioningBox = new cHTMLTableRow();

        $versioningHeadRow = new cHTMLTableRow();
        $versioningHeadText = new cHTMLTableHead();
        $versioningHeadText->setContent(i18n('Select Article Version'));
        $versioningHeadText->setAttribute('colspan', 2);
        $versioningHeadRow->appendContent($versioningHeadText);
        $versioningBox->appendContent($versioningHeadRow);

        $versionBoxDescription = new cHTMLTableData(i18n("Select Article Version"));
        $versionBoxDescription->setClass('leftData');
        $versionBoxDescription->setStyle('border-top:1px solid #B3B3B3;');
        $versioningBox->appendContent($versionBoxDescription);

        $versionBoxData = new cHTMLTableData();
        $versionBoxData->setStyle('border-top:1px solid #B3B3B3;');
        $versionBoxData->setAttribute('colspan', 3);
        $versionBoxData->appendContent($selectElement);
        $versionBoxData->appendContent(' ');
        $versionBoxData->appendContent($markAsCurrentButton);
        $versionBoxData->appendContent(' ');
        $versionBoxData->appendContent($infoButton);
        $versioningBox->appendContent($versionBoxData);

        $tpl->set('s', 'ARTICLE_VERSIONING_BOX', $versioningBox);

        break;
    case 'simple':
        $optionElementParameters = $versioning->getDataForSelectElement($art->getField('idartlang'), 'seo');

        // Create Metatag Version Option Elements
        $selectElement = new cHTMLSelectElement('articleVersionSelect', '', 'selectVersionElement');
        $optionElement = new cHTMLOptionElement(i18n('Published Version'), 'current');
        if ($articleType == 'current') {
            $optionElement->setSelected(true);
        }
        $selectElement->appendOptionElement($optionElement);

        // check if selected version is availible, else select the next lower version
        $temp_id = $selectedArticleId;
        $temp_ids = [];

        foreach (array_values($optionElementParameters) AS $key => $value) {
            $temp_ids[] = key($value);
        }
        if (!in_array($selectedArticleId, $temp_ids) && $selectedArticleId != 'current'
            && $selectedArticleId != 'editable') {
            foreach ($temp_ids AS $key => $value) {
                if ($value < $selectedArticleId) {
                    $temp_id = $value;
                    break;
                }
            }
        }

        foreach ($optionElementParameters AS $key => $value) {
            $lastModified = $versioning->getTimeDiff($value[key($value)]);
            $optionElement = new cHTMLOptionElement('Revision ' . $key . ': ' . $lastModified, key($value));
            //if ($articleType == 'version') {
                if ($temp_id == key($value)) {
                    $optionElement->setSelected(true);
                }
            //}
            $selectElement->appendOptionElement($optionElement);
        }
        $selectElement->setEvent("onchange", "selectVersion.idArtLangVersion.value=$('#selectVersionElement option:selected').val();selectVersion.submit()");

        $tpl->set("s", "ACTION2", $sess->url('main.php?area=' . $area . '&frame=' . $frame . '&action=con_meta_change_version'));
        $tpl->set("s", "ACTION3", $sess->url('main.php?area=' . $area . '&frame=' . $frame . '&action=copyto'));

        // Create markAsCurrent Button
        $markAsCurrentButton = new cHTMLButton('markAsCurrentButton', i18n('Copy to published version'), 'copytobutton');
        if ($articleType == 'current' || $articleType == 'editable' && $versioningState == 'simple') {
            $markAsCurrentButton->setAttribute('DISABLED');
        }

        $infoButton = new cGuiBackendHelpbox(i18n('<strong>Simple-mode:</strong> '
                . 'Older SEO versions can be reviewed and restored (For further configurations please go to Administration/System/System configuration).<br/><br/>'
                . 'Changes are only related to SEO!'));

        // box to select article version
        $versioningBox = new cHTMLTableRow();

        $versioningHeadRow = new cHTMLTableRow();
        $versioningHeadText = new cHTMLTableHead();
        $versioningHeadText->setContent(i18n('Select Article Version'));
        $versioningHeadText->setAttribute('colspan', 2);
        $versioningHeadRow->appendContent($versioningHeadText);
        $versioningBox->appendContent($versioningHeadRow);

        $versionBoxDescription = new cHTMLTableData(i18n("Select Article Version"));
        $versionBoxDescription->setClass('leftData');
        $versionBoxDescription->setStyle('border-top:1px solid #B3B3B3;');
        $versioningBox->appendContent($versionBoxDescription);

        $versionBoxData = new cHTMLTableData();
        $versionBoxData->setStyle('border-top:1px solid #B3B3B3;');
        $versionBoxData->setAttribute('colspan', 3);
        $versionBoxData->appendContent($selectElement);
        $versionBoxData->appendContent(' ');
        $versionBoxData->appendContent($markAsCurrentButton);
        $versionBoxData->appendContent(' ');
        $versionBoxData->appendContent($infoButton);
        $versioningBox->appendContent($versionBoxData);

        $tpl->set('s', 'ARTICLE_VERSIONING_BOX', $versioningBox);

        break;
    case 'disabled':
         // do not show box to select article version when article versioning is disabled
        $tpl->set('s', 'ARTICLE_VERSIONING_BOX', '');
        break;
    default:
        break;
}

$infoButton = new cGuiBackendHelpbox(i18n('The title-tag is one of the most important on-page factors for SEO and is not longer than 60 characters. It includes top keywords and the branding.'));
$tpl->set("s", "INFO_BUTTON_PAGE_TITLE", $infoButton->render());

$infoButton->setHelpText(i18n('The description-tag describes the article in a short way (not more than 150 characters). The content should be related to the title-tag and the H1-tag.'));
$tpl->set("s", "INFO_BUTTON_DESCRIPTION", $infoButton->render());

$infoButton->setHelpText(i18n('No more than 6 Keywords should be used.'));
$tpl->set("s", "INFO_BUTTON_KEYWORDS", $infoButton->render());

$infoButton->setHelpText(i18n('The frequency of the revisit after tag depends on new publications of the content. Nevertheless the robots decide on their own when to visit.'));
$tpl->set("s", "INFO_BUTTON_REVISIT", $infoButton->render());

$infoButton->setHelpText(i18n('The robot-tag sets certain rules for search engines. You can tell it to not index certain articles or to keep pictures in this article out of its index. It has a high relevance for SEO. Only relevant and most visited articles should be indexed.'));
$tpl->set("s", "INFO_BUTTON_ROBOTS", $infoButton->render());

$infoButton->setHelpText(i18n('The avarage value for the sitemap priority is 0.5. Only important articles should have a value no more than 0.8.'));
$tpl->set("s", "INFO_BUTTON_SITEMAP_PRIORITY", $infoButton->render());

$infoButton->setHelpText(i18n('The refresh rate is focused on the content.'));
$tpl->set("s", "INFO_BUTTON_SITEMAP_FREQUENCY", $infoButton->render());

$tpl->set('s', 'SELECTED_' . $art->getField('changefreq'), 'selected');
$sitemapChangeFrequencies = [
    '',
    'always',
    'hourly',
    'daily',
    'weekly',
    'monthly',
    'yearly',
    'never'
];
foreach ($sitemapChangeFrequencies as $value) {
    $tpl->set('s', 'SELECTED_' . $value, '');
}

// Assign additional rows
$additionalRows = ''; // call the chain to add additional rows
$cecRegistry = cApiCecRegistry::getInstance();
$cecIterator = $cecRegistry->getIterator('Contenido.Backend.ConMetaEditFormAdditionalRows');
while (false !== $chainEntry = $cecIterator->next()) {
    $additionalRows .= $chainEntry->execute($idart, $lang, $client, $art->getField('locked'));
}
$tpl->set('s', 'ADDITIONAL_ROWS', $additionalRows);

// Assign add new meta
$result = [
    'metatype' => '',
    'fieldtype' => [
        'text',
        'textarea',
        'date'
    ],
    'maxlength' => '255',
    'fieldname' => 'name'
];
$tpl2 = new cTemplate();
$infoButton->setHelpText(i18n('Attribute content has to begin with a letter and can be followed by letters, digits or the following chars: . : _ - '));
$tpl2->set('s', 'METATITLE', i18n('New meta tag') . ' ' . $infoButton->render());
$sql = "SHOW FIELDS
        FROM `" . $cfg['tab']['meta_type'] . "`";
$db->query($sql);

while ($db->nextRecord()) {
    if ($db->f('Field') == 'idmetatype') {
        continue;
    }

    switch ($db->f('Field')) {
        case 'fieldtype':
            $tpl2->set('d', 'METATITLE', i18n('Field Type'));
            break;
        case 'metatype':
            $tpl2->set('d', 'METATITLE', i18n('Attribute content'));
            break;
        case 'maxlength':
            $tpl2->set('d', 'METATITLE', i18n('Max Length'));
            break;
        case 'fieldname':
            $tpl2->set('d', 'METATITLE', i18n('Meta Attribute'));
            break;
        default:
            $tpl2->set('d', 'METATITLE', i18n($db->f('Field')));
            break;
    }

    if (is_array($result[$db->f('Field')])) {
        $str = '<select id="META' . $db->f('Field') . '" name="META' . $db->f('Field') . '" ' . $disabled . '>';
        foreach ($result[$db->f('Field')] as $item) {
            $str .= '<option value="' . $item . '">' . $item . '</option>';
        }
        $str .= '<select>';
    } else {
        $str = '<input
                    type="text"
                    ' . $disabled . '
                    onblur="restoreOnBlur(this, \'' . $result[$db->f('Field')] . '\')"
                    onfocus="clearOnFocus(this, \'' . $result[$db->f('Field')] . '\');"
                    value="' . $result[$db->f('Field')] . '"
                    maxlength="255"
                    id="META' . $db->f('Field') . '"
                    name="META' . $db->f('Field') . '"
                    class="text_medium"
                >';
    }

    $tpl2->set('d', 'METAFIELDTYPE', $str);
    $tpl2->next();
}

// accessible by the current user (sysadmin client admin) anymore.
if (cPermission::checkSysadminPermission($auth->getPerms())) {
    // disable/grey out button if a non-editable version is selected
    if ($versioning->getState() == 'simple' && $articleType != 'current'
            || $versioning->getState() == 'advanced' && $articleType != 'editable') {
        $tpl->set('s', 'ADDMETABTN', '<img src="images/but_art_new_off.png" id="addMetaDisabled" alt="">');
    } else {
        $tpl->set('s', 'ADDMETABTN', '<img src="images/but_art_new.gif" id="addMeta" alt="">');
    }
    $tpl->set('s', 'ADDNEWMETA', $tpl2->generate($cfg['path']['templates'] . $cfg['templates']['con_meta_addnew'], true));
} else {
    $tpl->set('s', 'ADDMETABTN', '&nbsp;');
    $tpl->set('s', 'ADDNEWMETA', '&nbsp;');
}

// call the chain to create meta tags to display any additional tags in the
// preview
$_cecIterator = cRegistry::getCecRegistry()->getIterator('Contenido.Content.CreateMetatags');
if ($_cecIterator->count() > 0) {
    while (false !== $chainEntry = $_cecIterator->next()) {
        $metaPreview = $chainEntry->execute($metaPreview);
    }
}

$tpl2 = new cTemplate();
foreach ($metaPreview as $metaRow) {
    if ($metaRow['content'] == '') {
        $tpl2->set('d', 'META_SHOWN', 'display: none;');
    } else {
        $tpl2->set('d', 'META_SHOWN', '');
    }
    $tpl2->set('d', 'META_NAME', $metaRow['fieldname']);
    $tpl2->set('d', 'META_TYPE', $metaRow['name']);
    $tpl2->set('d', 'META_CONTENT', $metaRow['content']);
    $tpl2->next();
}

// render metatags preview
$tpl->set('s', 'META_TAGS', $tpl2->generate($cfg['path']['templates'] . 'template.con_meta_edit_form_preview.html', true));

// Assign bottom js values
if (0 != $idart && 0 != $idcat) {
    $script = 'artObj.setProperties("' . $idart . '", "' . $art->getField('idartlang') . '", "';
    $script .= $idcat . '", "' . $idcatlang . '", "' . $idcatart . '", "' . $lang . '");';
} else {
    $script = 'artObj.reset();';
}
$tpl->set('s', 'DATAPUSH', $script);
$tpl->set('s', 'PATH_TO_CALENDER_PIC', cRegistry::getBackendUrl() . $cfg['path']['images'] . 'calendar.gif');

// Genereate the Template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['con_meta_edit_form']);
