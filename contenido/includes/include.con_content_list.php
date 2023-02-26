<?php

/**
 * This file contains the backend page for displaying all content of an article.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Fulai Zhang
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $data, $idartlang, $notification, $syncoptions, $name, $idtype, $encoding, $contenido, $locked;

$idcat = cSecurity::toInteger(cRegistry::getCategoryId());
if ($idcat <= 0) {
    cRegistry::shutdown();
    return;
}

$backendPath = cRegistry::getBackendPath();
$backendUrl = cRegistry::getBackendUrl();
$sess = cRegistry::getSession();
$perm = cRegistry::getPerm();
$cfg = cRegistry::getConfig();
$area = cRegistry::getArea();
$action = cRegistry::getAction();
$idart = cRegistry::getArticleId();
$client = cRegistry::getClientId();
$lang = cSecurity::toInteger(cRegistry::getLanguageId());

cInclude('includes', 'functions.str.php');
cInclude('includes', 'functions.pathresolver.php');

$idartlang = cSecurity::toInteger($_REQUEST['idartlang'] ?? '0');
$idArtLangVersion = $_REQUEST['idArtLangVersion'] ?? '';

$sess->register('encoding');

// Initialize encodings
if (!isset($encoding) || !is_array($encoding) || count($encoding) == 0) {
    // Get encodings of all languages
    $encoding  = [];
    $oLangColl = new cApiLanguageCollection();
    $oLangColl->select('');
    while ($oLang = $oLangColl->next()) {
        $encoding[$oLang->get('idlang')] = $oLang->get('encoding');
    }
}

$edit = 'true';
$scripts = '';
// export only these content types
$allowedContentTypes = [
    "CMS_HTMLHEAD",
    "CMS_HTML",
    "CMS_TEXT",
    "CMS_LINK",
    "CMS_LINKTARGET",
    "CMS_LINKDESCR",
    "CMS_HEAD",
    "CMS_DATE",
    "CMS_RAW",
    "CMS_IMG",
    "CMS_IMGDESCR",
    "CMS_TEASER"
];

$versioning = new cContentVersioning();
$versioningState = $versioning->getState();

$page = new cGuiPage("con_content_list");

$templateFile = cRegistry::getConfigValue('path', 'templates', '') . cRegistry::getConfigValue('templates', 'generic_page_html5');
$page->setPageBase($templateFile);

$jslibs = '';
$aNotifications = [];

// Include wysiwyg editor class
$wysiwygeditor = cWYSIWYGEditor::getCurrentWysiwygEditorName();

// tinymce 3 not autoloaded, tinymce 4 and all custom editor classes must be
if ('tinymce3' === $wysiwygeditor) {
    include($cfg['path'][$wysiwygeditor . '_editorclass']);
}
$sConfigInlineEdit = '';
$sConfigFullscreen = '';
switch ($wysiwygeditor) {
    case 'tinymce4':
        $page->set('s', '_PATH_CONTENIDO_TINYMCE_CSS_', $cfg['path']['all_wysiwyg_html'] . $wysiwygeditor . '/contenido/css/');
        $oEditor = new cTinyMCE4Editor('', '');
        break;
    default:
        $page->set('s', '_PATH_CONTENIDO_TINYMCE_CSS_', $cfg['path']['contenido_fullhtml'] . 'styles/');
        $oEditor = new cTinyMCEEditor('', '');
        $oEditor->setToolbar('inline_edit');

        // Get configuration for popup and inline tiny
        $sConfigInlineEdit = $oEditor->getConfigInlineEdit();
        $sConfigFullscreen = $oEditor->getConfigFullscreen();
}

// get scripts from editor class
$jslibs .= $oEditor->getScripts();
if ('tinymce3' === cString::getPartOfString($wysiwygeditor, 0, 8)
    && true === $oEditor->getGZIPMode()
) {
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

$page->set('s', '_WYSIWYG_JS_TAGS_', $jslibs);
unset($jslibs);

if (!($perm->have_perm_area_action($area, "savecontype") || $perm->have_perm_area_action_item($area, "savecontype", $idcat) || $perm->have_perm_area_action("con", "deletecontype") || $perm->have_perm_area_action_item("con", "deletecontype", $idcat))) {
    // $page->displayCriticalError(i18n("Permission denied")); (Apparently one of the action files already displays this error message)
    $page->abortRendering();
    $page->render();
    die();
}

// save / set value from content
if (($action == 'savecontype' || $action == 10)) {
    if ($perm->have_perm_area_action($area, "savecontype") || $perm->have_perm_area_action_item($area, "savecontype", $idcat)) {
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
            if ($versioning->getState() != 'advanced') {
                conMakeArticleIndex($idartlang, $idart);
            }

            // restore original values
            $data = $_REQUEST['data'] ?? '';
            $value = $_REQUEST['value'] ?? '';

            $aNotifications[] = $notification->returnNotification("ok", i18n("Changes saved"));
        }

        conGenerateCodeForArtInAllCategories($idart);
    } else {
        $page->displayError(i18n("Permission denied"));
    }
} elseif ($action == 'deletecontype') {
    if ($perm->have_perm_area_action($area, "deletecontype") || $perm->have_perm_area_action_item($area, "deletecontype", $idcat)) {
        if (isset($_REQUEST['idcontent']) && is_numeric($_REQUEST['idcontent'])) {
            $oContentColl = new cApiContentCollection();

            $linkedTypes = [
                4 => 22, // if a CMS_IMG is deleted, the corresponding
                // CMS_IMAGEEDITOR will be deleted too
                22 => 4 // the same goes for the other way round
            ];

            switch ($versioningState) {
                case 'simple':
                    $oContentColl = new cApiContentCollection();
                    $content = new cApiContent();
                    $contentVersionColl = new cApiContentVersionCollection();
                    $contentItem = new cApiContent((int)$_REQUEST['idcontent']);
                    if (isset($linkedTypes[$contentItem->get("idtype")])) {
                        $linkedIds = $oContentColl->getIdsByWhereClause("`idartlang`='" . $idartlang . "' AND `idtype`='" . $linkedTypes[$contentItem->get("idtype")] . "' AND `value`='" . $contentItem->get("value") . "'");
                        foreach ($linkedIds as $linkedId) {
                            $oContentColl->delete($linkedId);
                        }
                    }

                    $artLang = new cApiArticleLanguage($idartlang);
                    $artLangVersion = $versioning->createArticleLanguageVersion($artLang->toArray());
                    $artLangVersion->markAsCurrentVersion(1);

                    $content->loadByPrimaryKey((int)$_REQUEST['idcontent']);
                    $parameters = $content->toArray();
                    $parameters['deleted'] = 1;
                    $parameters['version'] = $artLangVersion->get('version');
                    $contentVersionColl->create($parameters);
                    $oContentColl->delete((int)$_REQUEST['idcontent']);

                    break;
                case 'advanced':
                    $oContentVersionColl = new cApiContentVersionCollection();
                    $contentVersionItem = new cApiContentVersion((int)$_REQUEST['idcontent']);
                    /*if (isset($linkedTypes[$contentVersionItem->get("idtype")])) {
                        $linkedIds = $oContentVersionColl->getIdsByWhereClause("`idcontent`='" . (int) $_REQUEST['idcontent'] . "' AND `idartlang`='" . $idartlang . "' AND `idtype`='" . $linkedTypes[$contentVersionItem->get("idtype")] . "' AND `value`='" . $contentVersionItem->get("value") . "'");
                      foreach ($linkedIds as $linkedId) {
                        $contentVersionItem->delete($linkedId);
                      }
                     }*/

                    $contentParameters = $contentVersionItem->values;
                    $contentParameters['version'] = $contentParameters['version'] + 1;
                    $contentParameters['deleted'] = 1;
                    unset($contentParameters['idcontentversion']);

                    $versioning->createContentVersion($contentParameters);

                    break;
                case 'disabled':
                    $oContentColl = new cApiContentCollection();
                    $contentItem = new cApiContent((int)$_REQUEST['idcontent']);
                    if (isset($linkedTypes[$contentItem->get("idtype")])) {
                        $linkedIds = $oContentColl->getIdsByWhereClause("`idartlang`='" . $idartlang . "' AND `idtype`='" . $linkedTypes[$contentItem->get("idtype")] . "' AND `value`='" . $contentItem->get("value") . "'");
                        foreach ($linkedIds as $linkedId) {
                            $oContentColl->delete($linkedId);
                        }
                    }
                    $oContentColl->delete((int)$_REQUEST['idcontent']);

                    break;
                default:
                    break;
            }

            $oContentColl->delete((int)$_REQUEST['idcontent']);
            $aNotifications[] = $notification->returnNotification("ok", i18n("Changes saved"));

            // Execute cec hook
            cApiCecHook::execute('Contenido.Article.DeletecontentType', [
                'idcontent' => (int) $_REQUEST['idcontent'],
                'idart' => cRegistry::getArticleId(),
                'idlang' => cRegistry::getArticleLanguageId(),
                'idartlang' => cRegistry::getArticleLanguageId()
            ]);

            conGenerateCodeForArtInAllCategories($idart);
        }
    } else {
        $page->displayError(i18n("Permission denied"));
    }
} elseif ($action == 'exportrawcontent') {

    /**
     * extended class to add CDATA to content
     */
    class SimpleXMLExtended extends SimpleXMLElement
    {

        /**
         *
         * @param string $cdata_text
         */
        public function addCData($cdata_text)
        {
            $node = dom_import_simplexml($this);
            $no = $node->ownerDocument;
            $node->appendChild($no->createCDATASection($cdata_text));
        }

    }

    // load article language object
    $cApiArticleLanguage = new cApiArticleLanguage(cSecurity::toInteger($idartlang));
    // create xml element articles
    $articleElement = new SimpleXMLExtended('<?xml version="1.0" encoding="UTF-8"?><articles></articles>');

    // add child element article
    $articleNode = $articleElement->addChild("article");
    $articleNode->addAttribute("id", $cApiArticleLanguage->get('idart'));

    // add seo infos to xml
    $titleNode = $articleNode->addChild("title");
    $titleNode->addCData($cApiArticleLanguage->get('title'));

    $summaryNode = $articleNode->addChild("shortdesc");
    $summaryNode->addCData($cApiArticleLanguage->get('summary'));

    $pageTitleNode = $articleNode->addChild("seo_title");
    $pageTitleNode->addCData($cApiArticleLanguage->get('pagetitle'));

    $seoDescrNode = $articleNode->addChild("seo_description");
    $seoDescrNode->addCData(conGetMetaValue($cApiArticleLanguage->get('idartlang'), 3));

    $keywordsNode = $articleNode->addChild("seo_keywords");
    $keywordsNode->addCData(conGetMetaValue($cApiArticleLanguage->get('idartlang'), 5));

    $copyrightNode = $articleNode->addChild("seo_copyright");
    $copyrightNode->addCData(conGetMetaValue($cApiArticleLanguage->get('idartlang'), 8));

    $seoauthorNode = $articleNode->addChild("seo_author");
    $seoauthorNode->addCData(conGetMetaValue($cApiArticleLanguage->get('idartlang'), 1));

    // load content id's for article
    if ($_POST['versionnumber'] == 'current' || $_POST['versionnumber'] == 'undefined'
        || $_POST['versionnumber'] == "''" || $_POST['versionnumber'] == ""
    ) {
        $conColl = new cApiContentCollection();
        $contentIds = $conColl->getIdsByWhereClause('idartlang = "' . $cApiArticleLanguage->get("idartlang") . '"');
    } else {
        $artLangVersion = new cApiArticleLanguageVersion((int)$_POST['versionnumber']);
        $conVersionColl = new cApiContentVersionCollection();
        $where = "(idcontent, version) IN (
                SELECT idcontent, max(version)
                FROM " . $cfg['tab']['content_version']
            . " WHERE idartlang = " . $cApiArticleLanguage->get("idartlang")
            . " AND version <= " . $artLangVersion->get('version')
            . " GROUP BY idtype, typeid)";
        $contentIds = $conVersionColl->getIdsByWhereClause($where);
    }

    // iterate through content and add get data
    foreach ($contentIds as $contentId) {
        // load content object
        if ($_POST['versionnumber'] == 'current' || $_POST['versionnumber'] == 'undefined'
            || $_POST['versionnumber'] == "''" || $_POST['versionnumber'] == ""
        ) {
            $content = new cApiContent($contentId);
        } else {
            $content = new cApiContentVersion($contentId);
        }
        // if loaded get data and add to xml
        if ($content->isLoaded()) {
            $type = new cApiType($content->get("idtype"));
            if ($type->isLoaded() && in_array($type->get("type"), $allowedContentTypes)) {
                // create content element
                $contentNode = $articleNode->addChild("content");
                $contentNode->addCData($content->get("value"));
                $contentNode->addAttribute("type", $type->get("type"));
                $contentNode->addAttribute("id", $content->get("typeid"));
            }
        }
    }

    // output data as xml
    header('Content-Type: application/xml;');
    $filename = str_replace(" ", "_", $cApiArticleLanguage->get('title'));
    header('Content-Disposition: attachment; filename=' . $filename . '.xml;');
    ob_clean();
    echo $articleElement->asXML();
    exit;
} elseif ($action == "importrawcontent") {
    // import raw data into article
    // init vars
    $error = false;

    //get file from request
    $rawDataFile = $_FILES['rawfile']['tmp_name'];

    // check file exist
    if (cString::getStringLength($rawDataFile) > 0 && isset($_FILES['rawfile'])) {

        // read file from tmp upload folder
        $rawData = file_get_contents($rawDataFile);

        // try init xml and import data
        try {
            $xmlDocument = new SimpleXMLElement($rawData);

            foreach ($xmlDocument->children() as $articleNode) {
                $articleId = cRegistry::getArticleId();

                // check article id exists in xml
                if ($articleId > 0) {

                    // load article by artice id and language
                    $articleLanguage = new cApiArticleLanguage();
                    $articleLanguage->loadByMany(["idart" => $articleId, "idlang" => $lang]);

                    $versioning = new cContentVersioning();
                    $version = NULL;
                    if ($versioning->getState() != 'disabled') {
                        // create article version
                        $artLangVersion = $versioning->createArticleLanguageVersion($articleLanguage->toArray());
                        $artLangVersion->markAsCurrentVersion(1);
                        $version = $artLangVersion->get('version');
                    }


                    // check is article loaded
                    if ($articleLanguage->isLoaded()) {

                        // read xml children
                        foreach ($articleNode->children() as $key => $child) {
                            // switch xml tag and exec business logic
                            switch ($key) {
                                case 'title':
                                    $articleLanguage->set("title", $child);
                                    $articleLanguage->store();

                                    break;
                                case 'shortdesc':
                                    $articleLanguage->set("summary", $child);
                                    $articleLanguage->store();

                                    break;
                                case 'seo_title':
                                    $articleLanguage->set("pagetitle", $child);
                                    $articleLanguage->store();

                                    break;
                                case 'seo_description':
                                    conSetMetaValue($articleLanguage->get('idartlang'), 3, $child, $version);

                                    break;
                                case 'seo_keywords':
                                    conSetMetaValue($articleLanguage->get('idartlang'), 5, $child, $version);

                                    break;
                                case 'seo_copyright':
                                    conSetMetaValue($articleLanguage->get('idartlang'), 8, $child, $version);

                                    break;
                                case 'seo_author':
                                    conSetMetaValue($articleLanguage->get('idartlang'), 1, $child, $version);

                                    break;
                                case 'content':
                                    $type = $child->attributes()->type;
                                    $typeid = $child->attributes()->id;

                                    $typeEntry = new cApiType();
                                    $typeEntry->loadBy("type", $type);

                                    if (cString::getStringLength($type) > 0 && $typeid > 0 && in_array($typeEntry->get("type"), $allowedContentTypes)) {
                                        if (isset($_POST['overwritecontent']) && $_POST['overwritecontent'] == 1) {
                                            conSaveContentEntry($articleLanguage->get('idartlang'), $type, $typeid, $child);
                                        } else {
                                            if ($versioningState == 'simple' || $versioningState == 'disabled') {
                                                $contentEntry = new cApiContent();
                                                $contentEntry->loadByMany(["idtype" => $typeEntry->get("idtype"), "typeid" => $typeid, "idartlang" => $articleLanguage->get('idartlang')]);
                                            } elseif ($versioningState == 'advanced') {
                                                $contentEntryVersionCollection = new cApiContentVersionCollection();
                                                $where = 'idtype = ' . $typeEntry->get("idtype") . ' AND typeid = ' . $typeid . ' AND idartlang = ' . $articleLanguage->get('idartlang');
                                                $ids = $contentEntryVersionCollection->getIdsByWhereClause($where);
                                                $contentEntry = new cApiContentVersion(max($ids));
                                                if ($contentEntry->isLoaded()) {
                                                    if ($contentEntry->get('deleted')) {
                                                        $contentEntry = new cApiContent();
                                                    }
                                                }
                                            }
                                            if (!$contentEntry->isLoaded()) {
                                                conSaveContentEntry($articleLanguage->get('idartlang'), $type, $typeid, $child);
                                            }
                                        }
                                    }

                                    break;
                                case 'default':
                                    break;
                            }
                        }
                    } else {
                        $page->displayError(i18n("Can not load article"));
                        $error = true;
                    }
                } else {
                    $page->displayError(i18n("Can not find article"));
                    $error = true;
                }
            }

            if ($error === false) {
                $page->displayOk(i18n("Raw data was imported successfully"));
            }
        } catch (Exception $e) {
            $page->displayError(i18n("Error: The XML file is not valid"));
        }
    } else {
        $page->displayWarning(i18n("Please choose a file"));
    }
}

$page->set('s', 'NOTIFICATIONS', implode('<br />', $aNotifications));

global $selectedArticleId;
$selectedArticleId = !empty($idArtLangVersion) ? $idArtLangVersion : NULL;

$result = [];
$list = [];
$articleType = $versioning->getArticleType(
    $idArtLangVersion, $idartlang, $action, $selectedArticleId
);

// Check if current user is admin
$isAdmin = cRegistry::getPerm()::checkAdminPermission(cRegistry::getAuth()->getPerms());

// Get locked status (article freeze)
$cApiArticleLanguage = new cApiArticleLanguage($idartlang);
$isLocked = cSecurity::toBoolean($cApiArticleLanguage->getField('locked'));

$versioningElement = '';

switch ($versioningState) {
    case 'simple':
        // get selected article
        $selectedArticle = $versioning->getSelectedArticle($idArtLangVersion, $idartlang, $articleType, $selectedArticleId);

        // Set as current/editable
        if ($action == 'copyto') {
            if (is_numeric($idArtLangVersion) && $articleType == 'editable') {
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

        // Get Content or Content Version
        $content = $selectedArticle->getContent();
        if ($selectedArticle->isLoaded() && is_array($content)) {
            $result = array_change_key_case($selectedArticle->getContent(), CASE_UPPER);
            $result = $versioning->sortResults($result);
        }

        // Set $list
        $list = $versioning->getList($idartlang, $articleType);

        // Get version numbers for Select Element
        $optionElementParameters = $versioning->getDataForSelectElement($idartlang, 'content');

        // Create Current and Editable Content Option Element
        $selectElement = new cHTMLSelectElement('articleVersionSelect', '', 'selectVersionElement');
        $optionElement = new cHTMLOptionElement(i18n('Published Version'), 'current');
        if ($articleType == 'current') {
            $optionElement->setSelected(true);
        }
        $selectElement->appendOptionElement($optionElement);

        // Check if selected version is available, else select the next lower version
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

        // Create Content Version Option Elements
        foreach ($optionElementParameters AS $key => $value) {
            $lastModified = $versioning->getTimeDiff($value[key($value)]);
            $optionElement = new cHTMLOptionElement('Version ' . $key . ': ' . $lastModified, key($value));
            //if ($articleType == 'version') {
            //if ($selectedArticle->get('version') == $key) {
            //$optionElement->setSelected(true);
            //}
            if (key($value) == $temp_id) {
                $optionElement->setSelected(true);
            }
            //}
            $selectElement->appendOptionElement($optionElement);
        }

        // Create code/output
        // Set import labels
        if ($articleType != 'version' && !$isLocked && $isAdmin) {
            $page->set('s', 'DISABLED', '');
        } else {
            $page->set('s', 'DISABLED', 'DISABLED');
        }
        // Create markAsCurrent Button/Label
        $page->set('s', 'COPY_LABEL', i18n('Copy Version'));
        $markAsCurrentButton = new cHTMLButton('markAsCurrentButton', i18n('Copy to published version'), 'markAsCurrentButton');
        if ($articleType == 'current' || $articleType == 'editable' && $versioningState == 'simple' || ($isLocked && !$isAdmin)) {
            $markAsCurrentButton->setAttribute('DISABLED');
        }

        $versioningInfoTextBox = new cGuiBackendHelpbox(i18n(
            "<strong>Simple-mode:</strong> Older content versions can be restored and reviewed "
            . "(Configurations under Administration/System configuration).<br/><br/>"
            . "Changes only refer to contents itself!"
        ));

        $versioningElement .= $versioning->getVersionSelectionField(
            '',
            $selectElement,
            $markAsCurrentButton,
            $versioningInfoTextBox,
            i18n("Select Article Version")
        );
        $versioningElement .= $versioning->getVersionSelectionFieldJavaScript('editcontent');

        break;
    case 'advanced':

        // update selected article id after import or change
        if (isset($_POST['changeview']) || isset($_POST['import'])) {
            $selectedArticleId = $versioning->getEditableArticleId($idartlang);
        }

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

        // get selected article
        $selectedArticle = $versioning->getSelectedArticle($idArtLangVersion, $idartlang, $articleType);
        // Get Content or Content Version and sort
        $content = $selectedArticle->getContent();
        if ($selectedArticle->isLoaded() && is_array($content)) {
            $result = array_change_key_case($content, CASE_UPPER);
            $result = $versioning->sortResults($result);
        }

        // Set $list
        $list = $versioning->getList($idartlang, $articleType);

        // Get version numbers for Select Element
        $optionElementParameters = $versioning->getDataForSelectElement($idartlang, 'content');

        // Create Current and Editable Content Option Element and Select Element
        $selectElement = new cHTMLSelectElement('articleVersionSelect', '', 'selectVersionElement');

        if (isset($versioning->editableArticleId)) {
            $optionElement = new cHTMLOptionElement(i18n('Draft'), $versioning->getEditableArticleId($idartlang));
            if ($articleType == 'editable') {
                $optionElement->setSelected(true);
            }
            $selectElement->appendOptionElement($optionElement);
            unset($optionElementParameters[max(array_keys($optionElementParameters))]);
        }


        $optionElement = new cHTMLOptionElement(i18n('Published Version'), 'current');
        if ($articleType == 'current') {
            $optionElement->setSelected(true);
        }
        $selectElement->appendOptionElement($optionElement);

        // Check if selected version is available, else select the next lower version
        $temp_id = $selectedArticleId;
        $temp_ids = [];

        foreach (array_values($optionElementParameters) AS $key => $value) {
            $temp_ids[] = key($value);
        }
        if ($_POST['changeview'] != 'edit' && !in_array($selectedArticleId, $temp_ids) && $selectedArticleId != 'current'
            && $selectedArticleId != 'editable' && $selectedArticleId != $versioning->editableArticleId
        ) {
            foreach ($temp_ids AS $key => $value) {
                if ($value < $selectedArticleId) {
                    $temp_id = $value;
                    break;
                }
            }
        }

        // Create Content Version Option Elements
        foreach ($optionElementParameters AS $key => $value) {
            $lastModified = $versioning->getTimeDiff($value[key($value)]);
            $optionElement = new cHTMLOptionElement('Revision ' . $key . ': ' . $lastModified, key($value));
            if (key($value) == $temp_id) {
                $optionElement->setSelected(true);
            }
            $selectElement->appendOptionElement($optionElement);
        }

        // Create code/output

        $page->set('s', 'COPY_LABEL', i18n('Copy Version'));
        // Set import labels
        if ($articleType == 'editable') {
            $page->set('s', 'DISABLED', '');
        } else {
            $page->set('s', 'DISABLED', 'DISABLED');
        }

        // Create markAsCurrent Button
        if ($articleType == 'current' || $articleType == 'version') {
            $buttonTitle = i18n('Copy to draft');
        } elseif ($articleType == 'editable') {
            $buttonTitle = i18n('Publish draft');
        }
        $markAsCurrentButton = new cHTMLButton('markAsCurrentButton', $buttonTitle, 'markAsCurrentButton');

        $versioningInfoTextBox = new cGuiBackendHelpbox(i18n(
            '<strong>Mode advanced:</strong> '
            . 'Older content versions can be reviewed and restored. Unpublished drafts'
            . ' can be created (For further configurations please go to Administration/System/System configuration).<br/><br/>'
            . 'Changes are only related to Contents!'
        ));

        $versioningElement .= $versioning->getVersionSelectionField(
            '',
            $selectElement,
            $markAsCurrentButton,
            $versioningInfoTextBox,
            i18n("Select Article Version")
        );
        $versioningElement .= $versioning->getVersionSelectionFieldJavaScript('editcontent');

        break;
    case 'disabled':
        // Versioning is disabled, don't show version select/copy controls

        // Set info text (Note: Text is not used at the moment!)
        #$versioningInfoText = i18n('For reviewing and restoring older article versions activate the article versioning under Administration/System/System configuration.');

        // Get selected article
        $selectedArticle = $versioning->getSelectedArticle($idArtLangVersion, $idartlang, $articleType);

        // Get Content/set $result
        $content = $selectedArticle->getContent();

        if ($selectedArticle->isLoaded() && is_array($content)) {
            $result = array_change_key_case($content, CASE_UPPER);
            $result = $versioning->sortResults($result);
        }

        // Set $list
        $list = $versioning->getList($idartlang, $articleType);

        // Set import labels
        $page->set('s', 'DISABLED', '');

        break;
    default:
        break;
}

$page->set('s', 'ARTICLE_VERSIONING_BOX', $versioningElement);

//$currentTypes = _getCurrentTypes($currentTypes, $aList);
// print_r($currentTypes);
// create Layoutcode
// if ($action == 'con_content') {
// @fulai.zhang: Mark submenuitem 'Editor' in the CONTENIDO Backend (Area:
// Contenido --> Articles --> Editor)
$markSubItem = markSubMenuItem(4, true);


// Replace vars in Script
// Set urls to file browsers
$page->set('s', 'IMAGE', $backendUrl . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
$page->set('s', 'FILE', $backendUrl . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=filebrowser');
$page->set('s', 'MEDIA', $backendUrl . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
$page->set('s', 'FRONTEND', cRegistry::getFrontendUrl());

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

    foreach ($aConfigInlineEdit as $sCmsType => $setting) {
        $oEditor->setToolbar($sCmsType, 'inline_edit');
        $aTinyOptions[$sCmsType] = $aConfigInlineEdit[$sCmsType];
        $aTinyOptions[$sCmsType]['fullscreen_settings'] = $aConfigFullscreen[$sCmsType];
    }
    $page->set('s', 'TINY_OPTIONS', json_encode($aTinyOptions));
    //$page->set('s', 'TINY_OPTIONS', '[{' . $sTinyOptions . '},{' . $sCmsHtmlHeadConfig . '}]');
} else {
    $sTinyOptions = $sConfigInlineEdit . ",\nfullscreen_settings: {\n" . $sConfigFullscreen . "\n}";
    $page->set('s', 'TINY_OPTIONS', '{' . $sTinyOptions . '}');
}
$page->set('s', 'TINY_OPTIONS', $sConfigInlineEdit);
$page->set('s', 'TINY_FULLSCREEN', $sConfigFullscreen);
$page->set('s', 'IDARTLANG', $idartlang);
$page->set('s', 'CLOSE', html_entity_decode(i18n('Close editor'), ENT_COMPAT | ENT_HTML401, cRegistry::getEncoding()));
$page->set('s', 'SAVE', html_entity_decode(i18n('Close editor and save changes'), ENT_COMPAT | ENT_HTML401, cRegistry::getEncoding()));
$page->set('s', 'QUESTION', html_entity_decode(i18n('You have unsaved changes.'), ENT_COMPAT | ENT_HTML401, cRegistry::getEncoding()));
$page->set('s', 'BACKEND_URL', cRegistry::getBackendUrl());

// Add export and import translations
$page->set('s', 'EXPORT_RAWDATA', i18n("Export raw data"));
$page->set('s', 'IMPORT_RAWDATA', i18n("Import raw data"));
$page->set('s', 'EXPORT_LABEL', i18n("Raw data export"));
$page->set('s', 'IMPORT_LABEL', i18n("Raw data import"));
$page->set('s', 'OVERWRITE_DATA_LABEL', i18n("Overwrite data"));

$page->set('s', 'HIDE', (!$isLocked || $isAdmin) ? '' : 'style="display:none;"');

if (getEffectiveSetting('system', 'insite_editing_activated', 'true') == 'false') {
    $page->set('s', 'USE_TINY', '');
} else {
    $page->set('s', 'USE_TINY', '1');
}

// Show path of selected category to user
$breadcrumb = renderBackendBreadcrumb($syncoptions, true, true);
$page->set('s', 'CATEGORY', $breadcrumb);
if (count($result) <= 0) {
    $page->displayInfo(i18n('Article has no raw data'));
} else {
    foreach ($result AS $type => $typeIdValue) {
        foreach ($typeIdValue AS $typeId => $value) {
            if (($articleType == 'editable' || $articleType == 'current' && ($versioningState == 'disabled' || $versioningState == 'simple'))) {
                $class = '';
            } elseif ($articleType == 'current' || $articleType == 'version') {
                $class = ' noactive';
            }
            $page->set("d", "EXTRA_CLASS", $class);
            $page->set("d", "NAME", $type);
            $page->set("d", "ID_TYPE", $typeId);
            if (in_array($name, $allowedContentTypes)) {
                $page->set("d", "EXPORT_CONTENT", '<input type="checkbox" class="rawtypes" name="' . $name . '" value="' . $idtype . '" checked="checked">');
                $page->set('d', 'EXPORT_CONTENT_LABEL', i18n("Export"));
            } else {
                $page->set("d", "EXPORT_CONTENT", '');
                $page->set('d', 'EXPORT_CONTENT_LABEL', '');
            }
            $page->next();
        }
    }
}

// breadcrumb onclick
if (!isset($syncfrom)) {
    $syncfrom = -1;
}
$syncoptions = $syncfrom;
$page->set('s', 'SYNCHOPTIONS', $syncoptions);

$page->set('s', 'IDART', $idart);
$page->set('s', 'IDCAT', $idcat);
$page->set('s', 'IDLANG', $lang);
$page->set('s', 'IDARTLANG', $idartlang);
$page->set('s', 'IDCLIENT', $client);

// If article is locked show notification
if ($isLocked && !$isAdmin) {
    $page->displayWarning(i18n('This article is currently frozen and can not be edited!'));
}

$code = _processCmsTags($list, $result, true, $page->render(NULL, true), $articleType, $versioningState, $selectedArticle->get('version'), $isLocked, $isAdmin);

if ($code == '0601') {
    markSubMenuItem('1');
    $code = "<script type='text/javascript'>location.href = '" . $backendUrl . "main.php?frame=4&area=con_content_list&action=con_content&idart=" . $idart . "&idcat=" . $idcat . "&contenido=" . $contenido . "'; /*console.log(location.href);*/</script>";
} else {
    // inject some additional markup
    $code = cString::iReplaceOnce("</head>", "$markSubItem $scripts\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=$encoding[$lang]\"></head>", $code);
}

if ($cfg['debug']['codeoutput']) {
    cDebug::out(conHtmlSpecialChars($code));
}

// show ContentTypeList
chdir(cRegistry::getFrontendPath());
eval("?>\n" . $code . "\n<?php\n");
// }
cRegistry::shutdown();

/**
 * Processes replacements of all existing CMS_...
 * tags within passed code
 *
 * @param array $list
 *         CMS_...tags list
 * @param array $contentList
 *         Associative list of CMS variables
 * @param bool  $saveKeywords
 *         Flag to save collected keywords during replacement process.
 * @param       $layoutCode
 * @param       $articleType
 * @param       $versioningState
 * @param       $version
 * @param bool  $isLocked
 * @param bool  $isAdmin
 *
 * @return mixed
 *
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function _processCmsTags(
    $list, $contentList, $saveKeywords, $layoutCode, $articleType,
    $versioningState, $version, $isLocked, $isAdmin
)
{
    #region REQUIRED_GLOBAL_VARIABLES #####################
    // NOTE: Variables below are required in included/evaluated content type codes!
    global $db, $db2, $sess, $cfg, $code, $cfgClient, $encoding, $notification;

    // NOTE: Variables below are additionally required in included/evaluated
    // content type codes within backend edit mode!
    global $edit, $editLink, $belang;

    $idcat = cSecurity::toInteger($_REQUEST['idcat'] ?? '0');
    $idart = cSecurity::toInteger($_REQUEST['idart'] ?? '0');
    $lang = cSecurity::toInteger($_REQUEST['lang'] ?? '0');
    $client = cSecurity::toInteger($_REQUEST['client'] ?? '0');
    $idartlang = cSecurity::toInteger($_REQUEST['idartlang'] ?? '0');
    $contenido = $_REQUEST['contenido'] ?? '';

    if (!is_object($db2)) {
        $db2 = cRegistry::getDb();
    }
    // End: Variables required in content type codes
    #endregion REQUIRED_GLOBAL_VARIABLES ##################

    $match = [];
    $keycode = [];
    $code = '';

    // $a_content is used by included/evaluated content type codes below
    $a_content = $contentList;

    // Select  cms_type entries existing in selected article
    if (empty($list)) {
        $list[0] = 0;
    }

    $_typeList = [];
    $oTypeColl = new cApiTypeCollection();
    $oTypeColl->select('`idtype` IN (' . implode(',', array_map(function ($i) {
        return (int)$i;
    }, array_keys($list))) . ')');
    if (0 < $oTypeColl->count()) {
        while (false !== ($oType = $oTypeColl->next())) {
            $_typeList[] = $oType->toObject();
        }
    }

    // Replace all CMS_TAGS[]
    foreach ($_typeList as $_typeItem) {
        $key = cString::toLowerCase($_typeItem->type);
        $type = $_typeItem->type;

        // Try to find all CMS_{type}[{number}] values, e.g. CMS_HTML[1]
        // At $match[2] you found your typeid
        $tmp = preg_match_all('/(' . $type . '\[+(\d+)\])/i', $layoutCode, $match);

        $a_[$key] = $match[2]; //all typeids

        $search = [];
        $replacements = [];

        $backendPath = cRegistry::getBackendPath();

        $typeCodeFile = $backendPath . 'includes/type/code/include.' . $type . '.code.php';
        $cTypeClassFile = $backendPath . 'classes/content_types/class.content.type.' . cString::toLowerCase(str_replace('CMS_', '', $type)) . '.php';
        // classname format: CMS_HTMLHEAD -> cContentTypeHtmlhead
        $className = 'cContentType' . ucfirst(cString::toLowerCase(str_replace('CMS_', '', $type)));

        // Indexes of content typ fields
        foreach ($a_[$key] as $val) {
            if (cFileHandler::exists($cTypeClassFile)) {
                $tmp = $a_content[$_typeItem->type][$val];
                $cTypeObject = new $className($tmp, $val, $a_content);
                if (cRegistry::isBackendEditMode() && (!$isLocked || $isAdmin) && $articleType == 'editable' || ($articleType == 'current' && ($versioningState == 'disabled' || $versioningState == 'simple'))) {
                    $tmp = $cTypeObject->generateEditCode();
                } else {
                    $tmp = $cTypeObject->generateViewCode();
                }
                // double escape the generated code string to avoid violating string syntax
                // e.g. when title of cContentTypeFilelist contains a ' character
                $tmp = str_replace('\'', '\\\'', $tmp);
            } elseif (cFileHandler::exists($typeCodeFile)) {
                // include CMS type code
                include($typeCodeFile);
            } elseif (!empty($_typeItem->code)) {
                // old version, evaluate CMS type code
                cDeprecated("Move code for $type from table into file system (contenido/includes/type/code/)");
                eval($_typeItem->code);
            }

            $versioning = new cContentVersioning();
            $idcontent = $versioning->getContentId($idartlang, cSecurity::toInteger($val), cSecurity::toString($type), $versioningState, $articleType, $version);

            $backendUrl = cRegistry::getBackendUrl();
            $num = (int)$val;
            $search[$num] = sprintf('%s[%s]', $type, $val);

            $path = $backendUrl . 'main.php?area=con_content_list&action=deletecontype&changeview=edit&idart=' . $idart . '&idartlang=' . $idartlang . '&idcat=' . $idcat . '&client=' . $client . '&lang=' . $lang . '&frame=4&contenido=' . $contenido . '&idcontent=' . $idcontent;

            if ($_typeItem->idtype == 20 || $_typeItem->idtype == 21) {
                $tmp = str_replace('";?>', '', $tmp);
                $tmp = str_replace('<?php echo "', '', $tmp);
                // echo
                // "<textarea>"."?".">\n".stripslashes($tmp)."\n\";?"."><"."?php\n"."</textarea>";
            }

            // can delete article content if all conditions are fulfilled:
            // article is not frozen or admin accesses page (admin can do everything, even when article is frozen)
            // article can be edited or (article is published version and versioning is turned off or set to simple mode)
            if ((!$isLocked || $isAdmin) && ($articleType == 'editable' || ($articleType == 'current' && ($versioningState == 'disabled' || $versioningState == 'simple')))) { // No freeze
                $replacements[$num]   = $tmp . '<span class="con_content_type_controls"><a href="javascript:void(0)" class="con_img_button con_img_button_content_type" data-con-action="delete_content_type" data-con-idcontent="' . $idcontent . '">
            <img src="' . $backendUrl . 'images/delete.gif" alt="">
            </a></span>';
                $keycode[$type][$num] = $tmp . '<span class="con_content_type_controls"></span><a href="javascript:void(0)" class="con_img_button con_img_button_content_type" data-con-action="delete_content_type" data-con-idcontent="' . $idcontent . '">
            <img src="' . $backendUrl . 'images/delete.gif" alt="">
            </a></span>';
            } else { // Freeze status
                $replacements[$num] = $tmp;
                $keycode[$type][$num] = $tmp;
            }
        }//var_dump($search);echo "<hr />";
        // remove slashes (legacy) on replacements only to avoid
        // stripping slashes repeatedly times on already stripped string
        foreach ($replacements as $replacementIdx => $curReplacement) {
            $replacements[$replacementIdx] = stripslashes($curReplacement);
        }
        $code = str_ireplace($search, $replacements, $layoutCode);
    }

    // execute CEC hook
    $layoutCode = cApiCecHook::executeAndReturn('Contenido.Content.conGenerateCode', $code);

    if (!empty($layoutCode)) {
        $pathTemplate = cRegistry::getBackendUrl() . 'main.php?area=con_content_list&action=deletecontype&changeview=edit&idart=' . $idart . '&idartlang=' . $idartlang . '&idcat=' . $idcat . '&client=' . $client . '&lang=' . $lang . '&frame=4&contenido=' . $contenido . '&idcontent=%';
        $jsCode = '
<script type="text/javascript">
    (function(Con, $) {
        var pathTemplate = "' . $pathTemplate . '",
            confirmDeletionText = "' . i18n("Are you sure you want to delete this content type from this article?") . '";
        $(function() {
            $("#con_content_list [con-action]").live("click", function() {
                var $element = $(this),
                    action = $element.data("con-action");
                if (action === "delete_content_type") {
                    var path = pathTemplate.replace("%s", $element.data("con-idcontent"));
                    Con.showConfirmation(confirmDeletionText, function() {
                        console.log("Con.Tiny.setContent", path);
                        //Con.Tiny.setContent("1", path);
                    });
                }
            });

        });
    })(Con, Con.$);
</script>
        ';

        $layoutCode = str_ireplace('</body>', $jsCode . "\n</body>", $layoutCode);

        $layoutCode = str_ireplace("<<", "[", $layoutCode);

        $layoutCode = str_ireplace(">>", "]", $layoutCode);
    }

    return $layoutCode;
}
