<?php
/**
 * This file contains the backend page for displaying all content of an article.
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Fulai Zhang
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$backendPath = cRegistry::getBackendPath();
$backendUrl = cRegistry::getBackendUrl();

cInclude('includes', 'functions.str.php');
cInclude('includes', 'functions.pathresolver.php');

if (!isset($idcat)) {
    cRegistry::shutdown();
    return;
}

$edit = 'true';
$scripts = '';
// export only these content types
$allowedContentTypes = array(
    "CMS_HTMLHEAD",
    "CMS_HTML",
    "CMS_TEXT",
    "CMS_LINK",
    "CMS_LINKTARGET",
    "CMS_LINKDESCR",
    "CMS_HEAD",
    "CMS_DATE",
	"CMS_RAW"
);

$page = new cGuiPage("con_content_list");

$templateFile = cRegistry::getConfigValue('path', 'templates', '') . cRegistry::getConfigValue('templates', 'generic_page_html5');
$page->setPageBase($templateFile);

$jslibs = '';
$aNotifications = array();

// Include wysiwyg editor class
$wysiwygeditor = cWYSIWYGEditor::getCurrentWysiwygEditorName();

// tinymce 3 not autoloaded, tinymce 4 is
// use blacklist in case customer has own editor that is not autoloaded
if ('tinymce3' === $wysiwygeditor) {
    include($cfg['path'][$wysiwygeditor . '_editorclass']);
}
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
$jslibs .= $oEditor->_getScripts();
if ('tinymce3' === substr($wysiwygeditor, 0, 8)
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
    $jslibs .= '<script src="' . $onejs . '" type="text/javascript"></script>';
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

// save / set value
if (($action == 'savecontype' || $action == 10)) {
    if ($perm->have_perm_area_action($area, "savecontype") || $perm->have_perm_area_action_item($area, "savecontype", $idcat)) {
        if ($data != '') {
            $data = explode('||', substr($data, 0, -2));
            foreach ($data as $value) {
                $value = explode('|', $value);
                if ($value[3] == '%$%EMPTY%$%') {
                    $value[3] = '';
                } else {
                    $value[3] = str_replace('%$%SEPERATOR%$%', '|', $value[3]);
                }
                conSaveContentEntry($value[0], 'CMS_' . $value[1], $value[2], $value[3]);
            }

            conMakeArticleIndex($idartlang, $idart);

            // restore orginal values
            $data = $_REQUEST['data'];
            $value = $_REQUEST['value'];

            $aNotifications[] = $notification->returnNotification("info", i18n("Changes saved"));
        }

        conGenerateCodeForArtInAllCategories($idart);
    } else {
        $page->displayError(i18n("Permission denied"));
    }
} else if ($action == 'deletecontype') {
    if ($perm->have_perm_area_action($area, "deletecontype") || $perm->have_perm_area_action_item($area, "deletecontype", $idcat)) {
       if (isset($_REQUEST['idcontent']) && is_numeric($_REQUEST['idcontent'])) {
            $oContentColl = new cApiContentCollection();

            $linkedTypes = array(
                4 => 22, // if a CMS_IMG is deleted, the corresponding
                         // CMS_IMAGEEDITOR will be deleted too
                22 => 4 // the same goes for the other way round
            );

            $contentItem = new cApiContent((int) $_REQUEST["idcontent"]);
            if (isset($linkedTypes[$contentItem->get("idtype")])) {
                $linkedIds = $oContentColl->getIdsByWhereClause("`idartlang`='" . $idartlang . "' AND `idtype`='" . $linkedTypes[$contentItem->get("idtype")] . "' AND `value`='" . $contentItem->get("value") . "'");
                foreach ($linkedIds as $linkedId) {
                    $oContentColl->delete($linkedId);
                }
            }
            $oContentColl->delete((int) $_REQUEST['idcontent']);
            $aNotifications[] = $notification->returnNotification("info", i18n("Changes saved"));

            conGenerateCodeForArtInAllCategories($idart);
        }
    } else {
        $page->displayError(i18n("Permission denied"));
    }
} else if ($action == 'exportrawcontent') {

    // extended class to add CDATA to content
    class SimpleXMLExtended extends SimpleXMLElement{
        public function addCData($cdata_text){
            $node= dom_import_simplexml($this);
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
    $conColl = new cApiContentCollection();
    $contentIds = $conColl->getIdsByWhereClause('idartlang="'. $cApiArticleLanguage->get("idartlang") .'"');

    // iterate through content and add get data
    foreach ($contentIds as $contentId) {
        //load content object
        $content = new cApiContent($contentId);
        // if loaded get data and add to xml
        if($content->isLoaded()) {
            $type = new cApiType($content->get("idtype"));

            if($type->isLoaded() && in_array($type->get("type"), $allowedContentTypes)) {
                foreach ($_POST as $key => $contentType) {
                    if($key == $type->get("type") && $contentType == $content->get("typeid")) {
                        //create content element
                        $contentNode = $articleNode->addChild("content");
                        $contentNode->addCData($content->get("value"));
                        $contentNode->addAttribute("type", $type->get("type"));
                        $contentNode->addAttribute("id", $content->get("typeid"));
                    }
                }
            }

        }
    }
    // output data as xml
    header('Content-Type: application/xml;');
    header('Content-Disposition: attachment; filename='.$cApiArticleLanguage->get('title').';');
    ob_clean();
    echo $articleElement->asXML();
    exit;
} else if ($action == "importrawcontent") {
    // import raw data into article

    // init vars
    $error = false;

    //get file from request
    $rawDataFile = $_FILES['rawfile']['tmp_name'];

    // check file exist
    if(strlen($rawDataFile) > 0 && isset($_FILES['rawfile'])) {

        // read file from tmp upload folder
        $rawData = file_get_contents($rawDataFile);

        // try init xml and import data
        try {
            $xmlDocument = new SimpleXMLElement($rawData);

            foreach ($xmlDocument->children() as $articleNode) {
                $articleId = $articleNode->attributes()->id;

                // check article id exists in xml
                if($articleId > 0) {

                    // load article by artice id and language
                    $articleLanguage = new cApiArticleLanguage();
                    $articleLanguage->loadByMany(array("idart" => $articleId, "idlang" => cRegistry::getLanguageId()));

                    // check is article loaded
                    if($articleLanguage->isLoaded()) {

                        // read xml childrens
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
                                    conSetMetaValue($articleLanguage->get('idartlang'), 3, $child);

                                    break;
                                case 'seo_keywords':
                                    conSetMetaValue($articleLanguage->get('idartlang'), 5, $child);

                                    break;
                                case 'seo_copyright':
                                    conSetMetaValue($articleLanguage->get('idartlang'), 8, $child);

                                    break;
                                case 'seo_author':
                                    conSetMetaValue($articleLanguage->get('idartlang'), 1, $child);

                                    break;
                                case 'content':
                                    $type = $child->attributes()->type;
                                    $typeid  = $child->attributes()->id;

                                    $typeEntry = new cApiType();
                                    $typeEntry->loadBy("type", $type);

                                    if(strlen($type) > 0 && $typeid > 0 && in_array($typeEntry->get("type"), $allowedContentTypes)) {
                                        if(isset($_POST['overwritecontent']) && $_POST['overwritecontent'] == 1) {
                                            conSaveContentEntry($articleLanguage->get('idartlang'), $type, $typeid, $child);
                                        } else {

                                            $contentEntry = new cApiContent();

                                            $contentEntry->loadByMany(array("idtype" => $typeEntry->get("idtype"), "typeid" => $typeid, "idartlang" => $articleLanguage->get('idartlang')));
                                            if(!$contentEntry->isLoaded()) {
                                                conSaveContentEntry($articleLanguage->get('idartlang'), $type, $typeid, $child);
                                            }
                                        }
                                    } else {

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
            if($error === false) {
                $page->displayInfo(i18n("Raw data was imported successfully"));
            }

        } catch (Exception $e) {
            $page->displayError(i18n("Error: The XML file is not valid"));
        }
    } else {
        $page->displayWarning(i18n("Please choose a file"));
    }
}
if (count($aNotifications) > 0) {
    $sNotifications = '';
    foreach ($aNotifications as $curNotification) {
        $sNotifications .= $curNotification . '<br />';
    }
    $page->set('s', 'NOTIFICATIONS', $sNotifications);
} else {
    $page->set('s', 'NOTIFICATIONS', '');
}

// get active value

$result = array();
$aList = array();
$currentTypes = array();
$sortID = array(
    "CMS_HTMLHEAD",
    "CMS_HEAD",
    "CMS_HTML",
    "CMS_TEXT",
    "CMS_IMG",
    "CMS_IMGDESCR",
    "CMS_IMGEDITOR",
    "CMS_LINK",
    "CMS_LINKTARGET",
    "CMS_LINKDESCR",
    "CMS_LINKEDITOR",
    "CMS_DATE",
    "CMS_TEASER",
    "CMS_FILELIST",
	"CMS_RAW"
);

$aIdtype = array();
$sql = "SELECT DISTINCT typeid FROM %s WHERE idartlang = %d ORDER BY typeid";
$db->query($sql, $cfg["tab"]["content"], $_REQUEST["idartlang"]);
while ($db->nextRecord()) {
    $aIdtype[] = $db->f("typeid");
}

foreach ($sortID as $name) {
    // $sql = "SELECT b.idtype as idtype, b.type as name, a.typeid as id,
    // a.value as value FROM " . $cfg["tab"]["content"] . " as a, " .
    // $cfg["tab"]["type"] . " as b WHERE a.idartlang = " .
    // cSecurity::toInteger($_REQUEST["idartlang"]) . " AND a.idtype = b.idtype
    // AND b.type = '" . cSecurity::toString($name) . "' ORDER BY idtype,
    // typeid, idcontent";
    $sql = "SELECT b.idtype as idtype, b.type as name, a.typeid as id, a.value as value FROM %s AS a, %s AS b " . "WHERE a.idartlang = %d AND a.idtype = b.idtype AND b.type = '%s' ORDER BY idtype, typeid, idcontent";
    $db->query($sql, $cfg["tab"]["content"], $cfg["tab"]["type"], $_REQUEST["idartlang"], $name);
    while ($db->nextRecord()) {
        $result[$db->f("name")][$db->f("id")] = $db->f("value");
        if (!in_array($db->f("name"), $aList)) {
            $aList[$db->f("idtype")] = $db->f("name");
        }
    }
}

$currentTypes = _getCurrentTypes($currentTypes, $aList);
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
    $aTinyOptions = array();
    $oTypeColl = new cApiTypeCollection();
    $oTypeColl->select();
    while (false !== ($typeEntry = $oTypeColl->next())) {
        // specify a shortcut for type field
        $curType = $typeEntry->get('type');

        $contentTypeClassName = cTypeGenerator::getContentTypeClassName($curType);
        $cContentType = new $contentTypeClassName(null, 0, array());
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
        $oEditor->setToolbar($sCmsType, 'inline_edit');
        $aTinyOptions[$sCmsType] = $aConfigInlineEdit[$sCmsType];
        $aTinyOptions[$sCmsType]['fullscreen_settings'] = $aConfigFullscreen[$sCmsType];
    }
    $page->set('s', 'TINY_OPTIONS', json_encode($aTinyOptions));
    //$page->set('s', 'TINY_OPTIONS', '[{' . $sTinyOptions . '},{' . $sCmsHtmlHeadConfig . '}]');
} else {
    $sTinyOptions= $sConfigInlineEdit . ",\nfullscreen_settings: {\n" . $sConfigFullscreen . "\n}";
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


if (getEffectiveSetting('system', 'insite_editing_activated', 'true') == 'false') {
    $page->set('s', 'USE_TINY', '');
} else {
    $page->set('s', 'USE_TINY', '1');
}

// Show path of selected category to user
$breadcrumb = renderBackendBreadcrumb($syncoptions, true, true);
$page->set('s', 'CATEGORY', $breadcrumb);

if (count($result) <= 0) {
    $page->displayInfo(i18n("Article has no raw data"));
    $page->abortRendering();
    // $layoutcode .= '<div>--- ' . i18n("none") . ' ---</div>';
} else {
    foreach ($aIdtype as $idtype) {
        foreach ($sortID as $name) {
            if (in_array($name, array_keys($result)) && isset($result[$name][$idtype])) {
                if (in_array($name . "[" . $idtype . "]", $currentTypes)) {
                    $class = '';
                } else {
                    $class = ' noactive';
                }
                $page->set("d", "EXTRA_CLASS", $class);
                $page->set("d", "NAME", $name);
                $page->set("d", "ID_TYPE", $idtype);
                if(in_array($name, $allowedContentTypes)) {
                    $page->set("d", "EXPORT_CONTENT",  '<input type="checkbox" class="rawtypes" name="' . $name .'" value="' .$idtype .'" checked="checked">');
                    $page->set('d', 'EXPORT_CONTENT_LABEL', i18n("Export"));
                } else {
                    $page->set("d", "EXPORT_CONTENT", '');
                    $page->set('d', 'EXPORT_CONTENT_LABEL', '');
                }
                $page->next();
            }
        }
    }
}

// breadcrumb onclick
if (!isset($syncfrom)) {
    $syncfrom = -1;
}
$syncoptions = $syncfrom;
$page->set("s", "SYNCHOPTIONS", $syncoptions);

$page->set("s", "IDART", $idart);
$page->set("s", "IDCAT", $idcat);
$page->set("s", "IDLANG", $lang);
$page->set("s", "IDARTLANG", $idartlang);
$page->set("s", "IDCLIENT", $client);

// generate code
$code = _processCmsTags($aList, $result, true, $page->render(NULL, true));

if ($code == "0601") {
    markSubMenuItem("1");
    $code = "<script type='text/javascript'>location.href = '" . $backendUrl . "main.php?frame=4&area=con_content_list&action=con_content&idart=" . $idart . "&idcat=" . $idcat . "&contenido=" . $contenido . "'; /*console.log(location.href);*/</script>";
} else {
    // inject some additional markup
    $code = cString::iReplaceOnce("</head>", "$markSubItem $scripts\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=$encoding[$lang]\"></head>", $code);
}

if ($cfg["debug"]["codeoutput"]) {
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
 * @param array $aList CMS_...tags list
 * @param array $contentList all CMS variables
 * @param bool $saveKeywords Flag to save collected keywords during replacement
 *        process.
 * @param array $contentList Assoziative list of CMS variables
 */
function _processCmsTags($aList, $contentList, $saveKeywords = true, $layoutCode) {
    // #####################################################################
    // NOTE: Variables below are required in included/evaluated content type
    // codes!
    global $db, $db2, $sess, $cfg, $code, $cfgClient, $encoding, $notification;

    // NOTE: Variables below are additionally required in included/evaluated
    // content type codes within backend edit mode!
    global $edit, $editLink, $belang;

    $idcat = $_REQUEST['idcat'];
    $idart = $_REQUEST['idart'];
    $lang = $_REQUEST['lang'];
    $client = $_REQUEST['client'];
    $idartlang = $_REQUEST['idartlang'];
    $contenido = $_REQUEST['contenido'];


    // Get locked status (article freeze)
    $cApiArticleLanguage = new cApiArticleLanguage(cSecurity::toInteger($idartlang));
    $locked = $cApiArticleLanguage->getField('locked');

    // If article is locked show notification
    if ($locked == 1) {
        $notification->displayNotification('warning', i18n('This article is currently frozen and can not be edited!'));
    }

    if (!is_object($db2)) {
        $db2 = cRegistry::getDb();
    }
    // End: Variables required in content type codes
    // #####################################################################

    $match = array();
    $keycode = array();

    // $a_content is used by included/evaluated content type codes below
    $a_content = $contentList;

    // Select all cms_type entries
    $_typeList = array();
    $oTypeColl = new cApiTypeCollection();
    $oTypeColl->select();
    while ($oType = $oTypeColl->next()) {
        $_typeList[] = $oType->toObject();
    }

    // Replace all CMS_TAGS[]
    foreach ($_typeList as $_typeItem) {
        $key = strtolower($_typeItem->type);
        $type = $_typeItem->type;
        if (in_array($type, $aList)) {
            // Try to find all CMS_{type}[{number}] values, e. g. CMS_HTML[1]
            // $tmp = preg_match_all('/(' . $type . ')\[+([a-z0-9_]+)+\]/i',
            // $this->_layoutCode, $match);
            $tmp = preg_match_all('/(' . $type . '\[+(\d)+\])/i', $layoutCode, $match);

            $a_[$key] = $match[0];

            $success = array_walk($a_[$key], 'extractNumber');

            $search = array();
            $replacements = array();

            $backendPath = cRegistry::getBackendPath();

            $typeCodeFile = $backendPath . 'includes/type/code/include.' . $type . '.code.php';
            $cTypeClassFile = $backendPath . 'classes/content_types/class.content.type.' . strtolower(str_replace('CMS_', '', $type)) . '.php';
            // classname format: CMS_HTMLHEAD -> cContentTypeHtmlhead
            $className = 'cContentType' . ucfirst(strtolower(str_replace('CMS_', '', $type)));

            foreach ($a_[$key] as $val) {
                if (cFileHandler::exists($cTypeClassFile)) {
                    $tmp = $a_content[$_typeItem->type][$val];
                    $cTypeObject = new $className($tmp, $val, $a_content);
                    if (cRegistry::isBackendEditMode() && $locked == 0) {
                        $tmp = $cTypeObject->generateEditCode();
                    } else {
                        $tmp = $cTypeObject->generateViewCode();
                    }
                } else if (cFileHandler::exists($typeCodeFile)) {
                    // include CMS type code
                    include($typeCodeFile);
                } elseif (!empty($_typeItem->code)) {
                    // old version, evaluate CMS type code
                    cDeprecated("Move code for $type from table into file system (contenido/includes/type/code/)");
                    eval($_typeItem->code);
                }
                $sql = "SELECT a.idcontent
                    FROM " . $cfg["tab"]["content"] . " as a, " . $cfg["tab"]["type"] . " as b
                    WHERE a.idartlang=" . cSecurity::toInteger($_REQUEST["idartlang"]) . " AND a.idtype=b.idtype AND a.typeid = " . cSecurity::toInteger($val) . " AND b.type = '" . cSecurity::toString($type) . "'
                    ORDER BY a.idartlang, a.idtype, a.typeid";
                $db->query($sql);
                while ($db->nextRecord()) {
                    $idcontent = $db->f("idcontent");
                }
                $backendUrl = cRegistry::getBackendUrl();

                $search[$val] = sprintf('%s[%s]', $type, $val);
                $path = $backendUrl . 'main.php?area=con_content_list&action=deletecontype&changeview=edit&idart=' . $idart . '&idartlang=' . $idartlang . '&idcat=' . $idcat . '&client=' . $client . '&lang=' . $lang . '&frame=4&contenido=' . $contenido . '&idcontent=' . $idcontent;
                if ($_typeItem->idtype == 20 || $_typeItem->idtype == 21) {
                    $tmp = str_replace('";?>', '', $tmp);
                    $tmp = str_replace('<?php echo "', '', $tmp);
                    // echo
                    // "<textarea>"."?".">\n".stripslashes($tmp)."\n\";?"."><"."?php\n"."</textarea>";
                }

                if ($locked == 0) { // No freeze
                    $replacements[$val] = $tmp . '<a href="#" onclick="Con.showConfirmation(\'' . i18n("Are you sure you want to delete this content type from this article?") . '\', function() { Con.Tiny.setContent(\'1\',\'' . $path . '\'); }); return false;">
                <img alt="" border="0" src="' . $backendUrl . 'images/delete.gif">
                </a>';
                    $keycode[$type][$val] = $tmp . '<a href="#" onclick="Con.showConfirmation(\'' . i18n("Are you sure you want to delete this content type from this article?") . '\', function() { Con.Tiny.setContent(\'1\',\'' . $path . '\'); }); return false;">
                <img alt="" border="0" src="' . $backendUrl . 'images/delete.gif">
                </a>';
                } else { // Freeze status
                    $replacements[$val] = $tmp;
                    $keycode[$type][$val] = $tmp;
                }
            }

            $code = str_ireplace($search, $replacements, $layoutCode);
            // execute CEC hook
            $code = cApiCecHook::executeAndReturn('Contenido.Content.conGenerateCode', $code);
            $layoutCode = stripslashes($code);
        }
    }
    $layoutCode = str_ireplace("<<", "[", $layoutCode);
    $layoutCode = str_ireplace(">>", "]", $layoutCode);
    return $layoutCode;
}

/**
 * Processes get all existing active CMS_... tags within passed code
 *
 * @param array $r active CMS variables
 * @param array $aList CMS_...tags list
 */
function _getCurrentTypes($r, $aList) {
    $idcat = $_REQUEST['idcat'];
    $idart = $_REQUEST['idart'];
    $lang = $_REQUEST['lang'];
    $client = $_REQUEST['client'];
    global $db, $db2, $sess, $cfg, $code, $cfgClient, $encoding;

    // Select all cms_type entries
    $_typeList = array();
    $oTypeColl = new cApiTypeCollection();
    $oTypeColl->select();
    while ($oType = $oTypeColl->next()) {
        $_typeList[] = $oType->toObject();
    }

    // generate code
    $code = conGenerateCode($idcat, $idart, $lang, $client, false, false, false);
    foreach ($_typeList as $_typeItem) {
        $type = $_typeItem->type;
        if (in_array($type, $aList)) {
            // Try to find all CMS_{type}[{number}] values, e. g. CMS_HTML[1]
            $tmp = preg_match_all('/(' . $type . '\[+(\d)+\])/i', $code, $match);
            foreach ($match[0] as $s) {
                if (!in_array($s, $r)) {
                    array_push($r, $s);
                }
            }
        }
    }
    return $r;
}
