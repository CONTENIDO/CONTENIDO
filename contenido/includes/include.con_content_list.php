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

$page = new cGuiPage("con_content_list");

if (!($perm->have_perm_area_action("con", "savecontype") || $perm->have_perm_area_action_item("con", "savecontype", $idcat) || $perm->have_perm_area_action("con", "deletecontype") || $perm->have_perm_area_action_item("con", "deletecontype", $idcat))) {
    // $page->displayCriticalError(i18n("Permission denied")); (Apparently one of the action files already displays this error message)
    $page->abortRendering();
    $page->render();
    die();
}

// save / set value
if (($action == 'savecontype' || $action == 10)) {
    if ($perm->have_perm_area_action("con", "savecontype") || $perm->have_perm_area_action_item("con", "savecontype", $idcat)) {
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

            $notification->displayNotification("info", i18n("Changes saved"));
        }

        conGenerateCodeForArtInAllCategories($idart);
    } else {
        $page->displayError(i18n("Permission denied"));
    }
} else if ($action == 'deletecontype') {
    if ($perm->have_perm_area_action("con", "deletecontype") || $perm->have_perm_area_action_item("con", "deletecontype", $idcat)) {
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
            $notification->displayNotification("info", i18n("Changes saved"));

            conGenerateCodeForArtInAllCategories($idart);
        }
    } else {
        $page->displayError(i18n("Permission denied"));
    }
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
    "CMS_FILELIST"
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
    while ($db->nextRecord() && $db->f("value") != '') {
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
$markSubItem = markSubMenuItem(5, true);

// Include tiny class
include ($backendPath . 'external/wysiwyg/tinymce3/editorclass.php');
$oEditor = new cTinyMCEEditor('', '');
$oEditor->setToolbar('inline_edit');

// Get configuration for popup und inline tiny
$sConfigInlineEdit = $oEditor->getConfigInlineEdit();
$sConfigFullscreen = $oEditor->getConfigFullscreen();

// Replace vars in Script

$page->set('s', 'CONTENIDO_FULLHTML', $backendUrl);

// Set urls to file browsers
$page->set('s', 'IMAGE', $backendUrl . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
$page->set('s', 'FILE', $backendUrl . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=filebrowser');
$page->set('s', 'FLASH', $backendUrl . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
$page->set('s', 'MEDIA', $backendUrl . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
$page->set('s', 'FRONTEND', cRegistry::getFrontendUrl());

// Add tiny options and fill function leave_check()
$page->set('s', 'TINY_OPTIONS', $sConfigInlineEdit);
$page->set('s', 'TINY_FULLSCREEN', $sConfigFullscreen);
$page->set('s', 'IDARTLANG', $idartlang);
$page->set('s', 'CON_PATH', $backendUrl);
$page->set('s', 'CLOSE', i18n('Close editor'));
$page->set('s', 'SAVE', i18n('Close editor and save changes'));
$page->set('s', 'QUESTION', i18n('Do you want to save changes?'));

if (getEffectiveSetting('system', 'insite_editing_activated', 'true') == 'false') {
    $page->set('s', 'USE_TINY', '');
} else {
    $page->set('s', 'USE_TINY', 'swapTiny(this);');
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
            if (in_array($name, array_keys($result)) && $result[$name][$idtype] != '') {
                if (in_array($name . "[" . $idtype . "]", $currentTypes)) {
                    $class = '';
                } else {
                    $class = ' noactive';
                }
                $page->set("d", "EXTRA_CLASS", $class);
                $page->set("d", "NAME", $name);
                $page->set("d", "ID_TYPE", $idtype);
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
                    include ($typeCodeFile);
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
                    $replacements[$val] = $tmp . '<a href="#" onclick="showConfirmation(\'' . i18n("Are you sure you want to delete this content type from this article?") . '\', function() { setcontent(\'1\',\'' . $path . '\'); });">
                <img border="0" src="' . $backendUrl . 'images/delete.gif">
                </a>';
                    $keycode[$type][$val] = $tmp . '<a href="#" onclick="showConfirmation(\'' . i18n("Are you sure you want to delete this content type from this article?") . '\', function() { setcontent(\'1\',\'' . $path . '\'); });">
                <img border="0" src="' . $backendUrl . 'images/delete.gif">
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
