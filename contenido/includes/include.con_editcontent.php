<?php

/**
 * This file contains the backend page for editing articles content.
 *
 * @todo replace code generation by Contenido_CodeGenerator (see
 *       contenido/classes/CodeGenerator)
 *
 * @package    Core
 * @subpackage Backend
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $contenido, $type, $typenr, $encoding;

$idcat = cSecurity::toInteger(cRegistry::getCategoryId());
if ($idcat <= 0) {
    cRegistry::shutdown();
    return;
}

$backendPath = cRegistry::getBackendPath();
$backendUrl = cRegistry::getBackendUrl();

$edit = 'true';
$db2 = cRegistry::getDb();
$scripts = '';
$cssData = '';
$jsData = '';
$action = cRegistry::getAction();
$idartlang = cSecurity::toInteger(cRegistry::getArticleLanguageId());
$idart = cRegistry::getArticleId();
$cfg = cRegistry::getConfig();
$client = cRegistry::getClientId();
$lang = cSecurity::toInteger(cRegistry::getLanguageId());
$sess = cRegistry::getSession();

// Initialize $_REQUEST with common used keys to prevent PHP 'Undefined array key' warnings
foreach (['data', 'value', 'filelist_action'] as $_key) {
    if (!isset($_REQUEST[$_key])) {
        $_REQUEST[$_key] = '';
    }
}

$idArtLangVersion = $_REQUEST['idArtLangVersion'] ?? '';

$data = cSecurity::toString($_REQUEST['data']);

if (($action == 20 || $action == 10) && $_REQUEST['filelist_action'] != 'store') {
    if ($data != '') {
        $data = explode('||', cString::getPartOfString($data, 0, -2));
        foreach ($data as $value) {
            $value = explode('|', $value);
            if ($value[3] == '%$%EMPTY%$%') {
                $value[3] = '';
            } else {
                $value[3] = str_replace('%$%SEPERATOR%$%', '|', $value[3]);
            }
            conSaveContentEntry($value[0], 'CMS_' . $value[1], $value[2], $value[3]);
        }

        $versioning = new cContentVersioning();
        if ($versioning->getState() != $versioning::STATE_ADVANCED) {
            conMakeArticleIndex($idartlang, $idart);
        }

        // restore original values
        $data = $_REQUEST['data'];
        $value = $_REQUEST['value'];
    }

    conGenerateCodeForArtInAllCategories($idart);
}

$areaCode = '';
if (isset($area) && $area == 'con_content_list') {
    $areaCode = '&area=' . $area;
}
if ($action == 10) {
    header('Location: ' . $backendUrl . $cfg['path']['includes'] . "include.backendedit.php?type=$type&typenr=$typenr&client=$client&lang=$lang&idcat=$idcat&idart=$idart&idartlang=$idartlang&contenido=$contenido&lang=$lang$areaCode");
    return;
}

// @fulai.zhang: Mark submenuitem 'Editor' in the CONTENIDO Backend (Area:
// Contenido --> Articles --> Editor)
$markSubItem = markSubMenuItem(5, true);

// Replace vars in Script
$oScriptTpl = new cTemplate();

// Include wysiwyg editor class
$wysiwygeditor = cWYSIWYGEditor::getCurrentWysiwygEditorName();

// tinymce 3 not autoloaded, tinymce 4 and all custom editor classes must be
if ('tinymce3' === $wysiwygeditor) {
    include($cfg['path'][$wysiwygeditor . '_editorclass']);
}
switch ($wysiwygeditor) {
    case 'tinymce4':
        $oScriptTpl->set('s', '_PATH_CONTENIDO_TINYMCE_CSS_', $cfg['path']['all_wysiwyg_html'] . $wysiwygeditor . '/contenido/css/');
        $oEditor = new cTinyMCE4Editor('', '');
        break;
    default:
        $oScriptTpl->set('s', '_PATH_CONTENIDO_TINYMCE_CSS_', cRegistry::getBackendUrl() . 'styles/');
        $oEditor = new cTinyMCEEditor('', '');
        $oEditor->setToolbar('inline_edit');

        // Get configuration for popup and inline tiny
        $sConfigInlineEdit = $oEditor->getConfigInlineEdit();
        $sConfigFullscreen = $oEditor->getConfigFullscreen();
}


$jslibs = '';
// get scripts from editor class
$jslibs .= $oEditor->getScripts();
if ('tinymce3' === cString::getPartOfString($wysiwygeditor, 0, 8)
    && true === $oEditor->getGZIPMode()) {
    // tinyMCE_GZ.init call must be placed in its own script tag
    // User defined plugins and themes should be identical in both "inits"
    $jslibs .= <<<JS
<script type="text/javascript">
tinyMCE_GZ.init({
    plugins: '{$oEditor->getPlugins()}',
    themes: '{$oEditor->getThemes()}',
    disk_cache: true,
    debug: false
});
</script>
JS;
}
foreach ($cfg['path'][$wysiwygeditor . '_scripts'] as $onejs) {
    $jslibs .= cHTMLScript::external($onejs);
}
unset($onejs);
$oScriptTpl->set('s', '_WYSIWYG_JS_TAGS_', $jslibs);
unset($jslibs);

$oScriptTpl->set('s', 'JS_EDITCONTENT', $markSubItem);

// Set urls to file browsers
$oScriptTpl->set('s', 'IMAGE', $backendUrl . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
$oScriptTpl->set('s', 'FILE', $backendUrl . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=filebrowser');
$oScriptTpl->set('s', 'MEDIA', $backendUrl . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
$oScriptTpl->set('s', 'FRONTEND', cRegistry::getFrontendUrl());

// Add tiny options
if ('tinymce4' === $wysiwygeditor) {
    // set toolbar options for each CMS type that can be edited using a WYSIWYG editor
    $aTinyOptions = [];
    $oTypeColl = new cApiTypeCollection();
    $oTypeColl->select();
    while (false !== ($typeEntry = $oTypeColl->next())) {
        // specify a shortcut for type field
        $curType = $typeEntry->get('type');

        $contentTypeClassName = cTypeGenerator::getContentTypeClassName($curType);
        if (false === class_exists($contentTypeClassName)) {
            continue;
        }
        $cContentType = new $contentTypeClassName(null, 0, []);
        if (false === $cContentType->isWysiwygCompatible()) {
            continue;
        }
        $oEditor->setToolbar($curType, 'inline_edit');
    }

    // get configuration for inline editor
    $aConfigInlineEdit = $oEditor->getConfigInlineEdit();
    // Get configuration for fullscreen editor
    $aConfigFullscreen = $oEditor->getConfigFullscreen();

    foreach($aConfigInlineEdit as $sCmsType => $setting) {
        // Get configuration for popup and inline tiny
        $aTinyOptions[$sCmsType] = $aConfigInlineEdit[$sCmsType];
        $aTinyOptions[$sCmsType]['fullscreen_settings'] = $aConfigFullscreen[$sCmsType];
    }

    $oScriptTpl->set('s', 'TINY_OPTIONS', json_encode($aTinyOptions));
//     $oScriptTpl->set('s', 'TINY_OPTIONS', '[{' . $sTinyOptions . '},{' . $sCmsHtmlHeadConfig . '}]');
} else {
    $sTinyOptions= $sConfigInlineEdit . ",\nfullscreen_settings: {\n" . $sConfigFullscreen . "\n}";
    $oScriptTpl->set('s', 'TINY_OPTIONS', '{' . $sTinyOptions . '}');
}
$oScriptTpl->set('s', 'IDARTLANG', $idartlang);
$oScriptTpl->set('s', 'CLOSE', html_entity_decode(i18n('Close editor'), ENT_COMPAT | ENT_HTML401, cRegistry::getEncoding()));
$oScriptTpl->set('s', 'SAVE', html_entity_decode(i18n('Close editor and save changes'), ENT_COMPAT | ENT_HTML401, cRegistry::getEncoding()));
$oScriptTpl->set('s', 'QUESTION', html_entity_decode(i18n('You have unsaved changes.'), ENT_COMPAT | ENT_HTML401, cRegistry::getEncoding()));
$oScriptTpl->set('s', 'BACKEND_URL', cRegistry::getBackendUrl());

if (getEffectiveSetting('system', 'insite_editing_activated', 'true') == 'false') {
    $oScriptTpl->set('s', 'USE_TINY', '');
} else {
    $oScriptTpl->set('s', 'USE_TINY', '1');
}

$scripts = $oScriptTpl->generate($backendPath . $cfg['path']['templates'] . $cfg['templates']['con_editcontent'], 1);

$frontContentUrl = $backendUrl . 'external/backendedit/front_content.php';

$editContentForm = new cHTMLForm(
    'editcontent',
    $sess->url($frontContentUrl . "?area=con_editcontent&idart=$idart&idcat=$idcat&lang=$lang&client=$client")
);
$editContentForm->setVar('action', '20')
    ->setVar('changeview', 'edit')
    ->setVar('idArtLangVersion', '')
    ->setVar('copyTo', '')
    ->setVar('data', '');

global $selectedArticleId;
$selectedArticleId = !empty($idArtLangVersion) ? $idArtLangVersion : NULL;

$versioning = new cContentVersioning();
$articleType = $versioning->getArticleType(
    $idArtLangVersion, $idartlang, $action, $selectedArticleId
);
$code = '';
$selectElement = new cHTMLSelectElement('articleVersionSelect', '', 'selectVersionElement');

$versioningElement = '';

switch ($versioning->getState()) {
    case $versioning::STATE_SIMPLE:
        // Set as current
        if ($action == 'copyto') {
            if (is_numeric($idArtLangVersion) && $versioning->getState() == $versioning::STATE_SIMPLE
                && ($articleType == 'current' || $articleType == 'editable')) {
                $artLangVersion = new cApiArticleLanguageVersion(cSecurity::toInteger($idArtLangVersion));
                $artLangVersion->markAsCurrent('content');
                $selectedArticleId = 'current';

                // Execute cec hook
                cApiCecHook::execute('Contenido.Content.CopyToVersion', [
                    'idart' => cSecurity::toInteger($artLangVersion->get('idart')),
                    'idlang' => $lang
                ]);
            }
        }
        $selectedArticle = $versioning->getSelectedArticle($idArtLangVersion, $idartlang, $articleType, $selectedArticleId);

        // Get version numbers for Select Element
        $optionElementParameters = $versioning->getDataForSelectElement($idartlang, 'content');

        // Create Current and Editable Content Option Element
        $optionElement = new cHTMLOptionElement(i18n('Published Version'), 'current');
        if ($articleType == 'current') {
            $optionElement->setSelected(true);
        }
        $selectElement->appendOptionElement($optionElement);

        // Check if selected version is available, else select the next lower version
        $temp_id = $selectedArticleId;
        $temp_ids = [];

        foreach (array_values($optionElementParameters) as $key => $value) {
            $temp_ids[] = key($value);
        }
        if (!in_array($selectedArticleId, $temp_ids) && $selectedArticleId != 'current'
            && $selectedArticleId != 'editable' && $articleType != 'current' && $articleType != 'editable') {
            foreach ($temp_ids as $key => $value) {
                if ($value < $selectedArticleId) {
                    $temp_id = $value;
                    break;
                }
            }
        }

        // Create Content Version Option Elements
        foreach ($optionElementParameters as $key => $value) {
            $lastModified = $versioning->getTimeDiff($value[key($value)]);
            $optionElement = new cHTMLOptionElement('Version ' . $key . ': ' . $lastModified, key($value));
            //if ($idArtLangVersion == key($value) && $articleType != 'current') {
            //$optionElement->setSelected(true);
            //}
            //if (key($value) == $selectedArticleId) {
            if (key($value) == $temp_id) {
                $optionElement->setSelected(true);
            }
            $selectElement->appendOptionElement($optionElement);
        }

        // Create markAsCurrent Button/Label
        $markAsCurrentButton = new cHTMLButton(
            'markAsCurrentButton', i18n('Copy to published version'), 'markAsCurrentButton'
        );
        if ($articleType == 'current' || $articleType == 'editable' && $versioning->getState() == $versioning::STATE_SIMPLE) {
            $markAsCurrentButton->setAttribute('DISABLED');
        }

        $versioningInfoTextBox = new cGuiBackendHelpbox(i18n(
            "<strong>Simple-mode:</strong> Older content versions can be restored and reviewed "
            . "(Configurations under Administration/System configuration).<br/><br/>Changes only refer to contents itself!"
        ));

        // add code
        $versioningElement .= $versioning->getVersionSelectionField(
            'con_editcontent_list',
            $selectElement->toHtml(),
            $markAsCurrentButton,
            $versioningInfoTextBox,
            i18n('Select Article Version')
        );
        $versioningElement .= $versioning->getVersionSelectionFieldJavaScript('editcontent');

        break;
    case $versioning::STATE_ADVANCED:
        // Set as current/editable
        if ($action == 'copyto') {
            if (is_numeric($idArtLangVersion) && $articleType == 'current') {
                $artLangVersion = new cApiArticleLanguageVersion(cSecurity::toInteger($idArtLangVersion));
                $artLangVersion->markAsCurrent('content');
                $selectedArticleId = 'current';

                // Execute cec hook
                cApiCecHook::execute('Contenido.Content.CopyToVersion', [
                    'idart' => cSecurity::toInteger($artLangVersion->get('idart')),
                    'idlang' => $lang
                ]);
            } elseif (is_numeric($idArtLangVersion) && $articleType == 'editable') {
                $artLangVersion = new cApiArticleLanguageVersion(cSecurity::toInteger($idArtLangVersion));
                $artLangVersion->markAsEditable('content');
                $articleType = $versioning->getArticleType(
                    $idArtLangVersion, $idartlang, $action, $selectedArticleId
                );
                $selectedArticleId = 'editable';

                // Execute cec hook
                cApiCecHook::execute('Contenido.Content.CopyToVersion', [
                    'idart' => cSecurity::toInteger($artLangVersion->get('idart')),
                    'idlang' => $lang
                ]);
            } elseif ($idArtLangVersion == 'current') {
                $artLang = new cApiArticleLanguage($idartlang);
                $artLang->markAsEditable('content');
                $articleType = $versioning->getArticleType(
                    $idArtLangVersion, $idartlang, $action, $selectedArticleId
                );
                $selectedArticleId = 'editable';

                $artLangVersion = $versioning->createArticleLanguageVersion($artLang->toArray());

                // Execute cec hook
                cApiCecHook::execute('Contenido.Content.CopyToVersion', [
                    'idart' => cSecurity::toInteger($artLangVersion->get('idart')),
                    'idlang' => $lang
                ]);
            }
        }

        // load selected article
        $selectedArticle = $versioning->getSelectedArticle((int) $idArtLangVersion, $idartlang, $articleType);

        // Get version numbers for Select Element
        $optionElementParameters = $versioning->getDataForSelectElement($idartlang, 'content');

        // set elements/buttons
        if (isset($versioning->editableArticleId)) {
            $optionElement = new cHTMLOptionElement(i18n('Draft'), $versioning->getEditableArticleId($idartlang));
            if ($articleType == 'editable') {
                $optionElement->setSelected(true);
            }
            $selectElement->appendOptionElement($optionElement);
            if (count($optionElementParameters) > 0 ) {
                unset($optionElementParameters[max(array_keys($optionElementParameters))]);
            }
        }

        $optionElement = new cHTMLOptionElement(i18n('Published Version'), 'current');
        if ($articleType == 'current') {
            $optionElement->setSelected(true);
        }
        $selectElement->appendOptionElement($optionElement);

        // Check if selected version is available, else select the next lower version
        $temp_id = $selectedArticleId;
        $temp_ids = [];

        foreach (array_values($optionElementParameters) as $key => $value) {
            $temp_ids[] = key($value);
        }
        if (!in_array($selectedArticleId, $temp_ids) && $selectedArticleId != 'current'
            && $selectedArticleId != 'editable') {
            foreach ($temp_ids as $key => $value) {
                if ($value < $selectedArticleId) {
                    $temp_id = $value;
                    break;
                }
            }
        }

        // Create Content Version Option Elements
        foreach ($optionElementParameters as $key => $value) {
            $lastModified = $versioning->getTimeDiff($value[key($value)]);
            $optionElement = new cHTMLOptionElement('Revision ' . $key . ': ' . $lastModified, key($value));
            if ($articleType == 'version') {
                if ($idArtLangVersion == key($value)) {
                    $optionElement->setSelected(true);
                }
                if (key($value) == $temp_id) {
                    $optionElement->setSelected(true);
                }
            }
            $selectElement->appendOptionElement($optionElement);
        }

        // Create markAsCurrent Button
        if ($articleType == 'current' || $articleType == 'version') {
            $buttonTitle = i18n('Copy to draft');
        } elseif ($articleType == 'editable') {
            $buttonTitle = i18n('Publish draft');
        }
        $markAsCurrentButton = new cHTMLButton('markAsCurrentButton', $buttonTitle, 'markAsCurrentButton');

        // set info text
        $versioningInfoTextBox = new cGuiBackendHelpbox(i18n(
            '<strong>Mode advanced:</strong> '
            . 'Older content versions can be reviewed and restored. Unpublished drafts'
            . ' can be created (For further configurations please go to Administration/System/System configuration).<br/><br/>'
            . 'Changes are only related to Contents!'));

        // add code
        $versioningElement .= $versioning->getVersionSelectionField(
            'con_editcontent_list',
            $selectElement->toHtml(),
            $markAsCurrentButton,
            $versioningInfoTextBox,
            i18n('Select Article Version')
        );
        $versioningElement .= $versioning->getVersionSelectionFieldJavaScript('editcontent');

        break;
    case $versioning::STATE_DISABLED:
        // Versioning is disabled, don't show version select/copy controls

        // Set info text (Note: Text is not used at the moment!)
        #$versioningInfoText = i18n('For reviewing and restoring older article versions activate the article versioning under Administration/System/System configuration.');

        // load selected article
        $selectedArticle = $versioning->getSelectedArticle(NULL, $idartlang, $articleType);

        break;
    default:
        break;
}

// generate article code
if ($selectedArticle != NULL) {
    $editable = false;
    switch ($versioning->getState()) {
        case $versioning::STATE_ADVANCED:
            if ($articleType == 'editable') {
                $editable = true;
                $version = $selectedArticle->get('version');
            } elseif ($articleType == 'current') {
                $editable = false;
                $version = NULL;
            } elseif ($articleType == 'version') {
                $editable = false;
                $version = $selectedArticle->get('version');
            }
            break;
        case $versioning::STATE_SIMPLE:
            if ($articleType == 'editable' || $articleType == 'current') {
                $editable = true;
                $version = NULL;
            } elseif ($articleType == 'version') {
                $editable = false;
                $version = $selectedArticle->get('version');
            }
            break;
        case $versioning::STATE_DISABLED:
            $editable = true;
            $version = NULL;
            break;
        default:
            throw new cException('unknown');
    }

    // sets global $edit = false; needed for edit/view output in editor
    if (!$editable) {
        global $edit;
        $edit = false;
    }

    $code .= conGenerateCode($idcat, $idart, $lang, $client, false, false, true, $editable, $version);
}

if ($code == "0601") {
    markSubMenuItem("1");
    $code = "<script type='text/javascript'>location.href = '" . $backendUrl . "main.php?frame=4&area=con_editart&action=con_edit&idart=" . $idart . "&idcat=" . $idcat . "&contenido=" . $contenido . "'; /*console.log(location.href);*/</script>";
} else {
    // Inject some additional markup
#    $contentForms = $editContentForm->toHtml() . "\n" . $copyToForm->toHtml() . "\n";
    $contentForms = $editContentForm->toHtml() . "\n";
    $code = cString::iReplaceOnce("</head>", "$scripts\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=$encoding[$lang]\"></head>", $code);
    $code = cString::iReplaceOnceReverse("</body>", "$contentForms</body>", $code);
    $code = cString::iReplaceOnce("<head>", "<head>\n" . '<base href="' . cRegistry::getFrontendUrl() . '">', $code);
}

$code = preg_replace("/(<body[^>]*)>/i", "\${1}> \n $versioningElement", $code, 1);

if ($cfg["debug"]["codeoutput"]) {
    cDebug::out(conHtmlSpecialChars($code));
}

chdir(cRegistry::getFrontendPath());

eval("?>\n" . $code . "\n<?php\n");

cRegistry::shutdown();
