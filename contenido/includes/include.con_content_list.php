<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Content Type list for editing the content in an article
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.3
 * @author     Fulai zhang
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * @todo replace code generation by Contenido_CodeGenerator (see contenido/classes/CodeGenerator)
 *
 * {@internal
 *   created  2012
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude('includes', 'functions.str.php');
cInclude('includes', 'functions.pathresolver.php');

if (!isset($idcat)) {
    cRegistry::shutdown();
    return;
}

$edit = 'true';
$scripts = '';

//save / set value
if ($action == 'savecontype' || $action == 10) {
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
    }

    conGenerateCodeForArtInAllCategories($idart);
} else if ($action == 'deletecontype') {
    if (isset($_REQUEST['idcontent']) && is_numeric($_REQUEST['idcontent'])) {
        $oContentColl = new cApiContentCollection();
        $oContentColl->delete((int) $_REQUEST['idcontent']);
    }
}

//get active value

$result = array();
$aList = array();
$typeAktuell = array();
$sortID = array("CMS_HTMLHEAD", "CMS_HEAD", "CMS_HTML", "CMS_HTMLTEXT", "CMS_TEXT",
    "CMS_IMG", "CMS_IMGDESCR", "CMS_IMGTITLE", "CMS_IMGEDIT", "CMS_IMGEDITOR",
    "CMS_EASYIMGEDIT", "CMS_LINK", "CMS_LINKTARGET", "CMS_LINKDESCR", "CMS_LINKTITLE",
    "CMS_LINKEDIT", "CMS_SIMPLELINKEDIT", "CMS_LINKEDITOR", "CMS_RAWLINK", "CMS_SWF",
    "CMS_DATE", "CMS_TEASER", "CMS_FILELIST");

$aIdtype = array();
$sql = "SELECT DISTINCT typeid FROM %s WHERE idartlang = %d ORDER BY typeid";
$db->query($sql, $cfg["tab"]["content"], $_REQUEST["idartlang"]);
while ($db->next_record()) {
    $aIdtype[] = $db->f("typeid");
}

foreach ($sortID as $name) {
//    $sql = "SELECT b.idtype as idtype, b.type as name, a.typeid as id, a.value as value FROM " . $cfg["tab"]["content"] . " as a, " . $cfg["tab"]["type"] . " as b WHERE a.idartlang = " . cSecurity::toInteger($_REQUEST["idartlang"]) . " AND a.idtype = b.idtype AND b.type = '" . cSecurity::toString($name) . "' ORDER BY idtype, typeid, idcontent";
    $sql = "SELECT b.idtype as idtype, b.type as name, a.typeid as id, a.value as value FROM %s AS a, %s AS b "
         . "WHERE a.idartlang = %d AND a.idtype = b.idtype AND b.type = '%s' ORDER BY idtype, typeid, idcontent";
    $db->query($sql, $cfg["tab"]["content"], $cfg["tab"]["type"], $_REQUEST["idartlang"], $name);
    while ($db->next_record()) {
        $result[$db->f("name")][$db->f("id")] = $db->f("value");
        if (!in_array($db->f("name"), $aList)) {
            $aList[$db->f("idtype")] = $db->f("name");
        }
    }
}

$typeAktuell = getAktuellType($typeAktuell, $aList);
//print_r($result);
//create Layoutcode
//if ($action == 'con_content') {
//@fulai.zhang: Mark submenuitem 'Editor' in the CONTENIDO Backend (Area: Contenido --> Articles --> Editor)
$markSubItem = markSubMenuItem(5, true);

//Include tiny class
include($cfg['path']['contenido'] . 'external/wysiwyg/tinymce3/editorclass.php');
$oEditor = new cTinyMCEEditor('', '');
$oEditor->setToolbar('inline_edit');

//Get configuration for popup und inline tiny
$sConfigInlineEdit = $oEditor->getConfigInlineEdit();
$sConfigFullscreen = $oEditor->getConfigFullscreen();


//Replace vars in Script
$oScriptTpl = new cTemplate();

$oScriptTpl->set('s', 'CONTENIDO_FULLHTML', $cfg['path']['contenido_fullhtml']);

//Set urls to file browsers
$oScriptTpl->set('s', 'IMAGE', $cfg['path']['contenido_fullhtml'] . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
$oScriptTpl->set('s', 'FILE', $cfg['path']['contenido_fullhtml'] . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=filebrowser');
$oScriptTpl->set('s', 'FLASH', $cfg['path']['contenido_fullhtml'] . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
$oScriptTpl->set('s', 'MEDIA', $cfg['path']['contenido_fullhtml'] . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
$oScriptTpl->set('s', 'FRONTEND', $cfgClient[$client]['path']['htmlpath']);

//Add tiny options and fill function leave_check()
$oScriptTpl->set('s', 'TINY_OPTIONS', $sConfigInlineEdit);
$oScriptTpl->set('s', 'TINY_FULLSCREEN', $sConfigFullscreen);
$oScriptTpl->set('s', 'IDARTLANG', $idartlang);
$oScriptTpl->set('s', 'CON_PATH', $cfg['path']['contenido_fullhtml']);
$oScriptTpl->set('s', 'CLOSE', i18n('Close editor'));
$oScriptTpl->set('s', 'SAVE', i18n('Close editor and save changes'));
$oScriptTpl->set('s', 'QUESTION', i18n('Do you want to save changes?'));

if (getEffectiveSetting('system', 'insight_editing_activated', 'true') == 'false') {
    $oScriptTpl->set('s', 'USE_TINY', '');
} else {
    $oScriptTpl->set('s', 'USE_TINY', 'swapTiny(this);');
}

$scripts = $oScriptTpl->generate($cfg['path']['contenido'] . $cfg['path']['templates'] . $cfg['templates']['con_editcontent'], 1);

$contentform = '
    <form name="editcontent" method="post" action="' . $sess->url($cfg['path']['contenido_fullhtml'] . "main.php?area=con_content_list&action=savecontype&idart=$idart&idcat=$idcat&lang=$lang&idartlang=$idartlang&frame=4&client=$client") . '">
        <input type="hidden" name="changeview" value="edit">
        <input type="hidden" name="data" value="">
    </form>
    ';

$layoutcode = '<html>
        <head>
            <title></title>
            <meta http-equiv="expires" content="0">
            <meta http-equiv="cache-control" content="no-cache">
            <meta http-equiv="pragma" content="no-cache">
            <link rel="stylesheet" type="text/css" href="../contenido/styles/contenido.css">
            <script type="text/javascript" src="../contenido/scripts/general.js"></script>
            <script type="text/javascript" src="../contenido/scripts/jquery/jquery.js"></script>
            <style>
            .contypeList {
                border: 1px solid #B3B3B3;
                padding: 10px;
                margin: 10px 0;
            }
            .noactive {
                border: 1px solid red;
            }
            .contypeList div {
                min-height: 13px;
            }
            </style>
        </head>
        <body style="margin: 10px">';
//Show path of selected category to user
$catString = '';
prCreateURLNameLocationString($idcat, ' > ', $catString, true, 'breadcrumb');
$sql = "SELECT * FROM " . $cfg["tab"]["art_lang"] . " WHERE idart=" . cSecurity::toInteger($idart) . " AND idlang=" . cSecurity::toInteger($lang);
$db->query($sql);
$db->next_record();
$layoutcode .= '<div id="categorypath" class="categorypath">' . i18n("Sie sind hier") . ": " . $catString . ' > ' . htmlspecialchars($db->f("title")) . '</div><p style="display:block;font-weight:bold;">' . i18n("Content Verwaltung") . '</p>';

if (count($result) <= 0) {
    $layoutcode .= '<div>--- ' . i18n("kein") . ' ---</div>';
} else {
    foreach ($aIdtype as $idtype) {
        foreach ($sortID as $name) {
            if (in_array($name, array_keys($result)) && count($result[$name]) >= $idtype) {

                if (in_array($name . "[" . $idtype . "]", $typeAktuell)) {
                    $class = '';
                } else {
                    $class = ' noactive';
                }
                $layoutcode .= '<div class="contypeList' . $class . '">
                <div class="headline">' . $name . '<<' . $idtype . '>>:</div>' . $name . '[' . $idtype . ']</div>';
            }
        }
    }
}

//breadcrumb onclick
if (!isset($syncfrom)) {
    $syncfrom = -1;
}
$syncoptions = $syncfrom;
$layoutcode .= "<script type='text/javascript'>
        $(document).ready(function(){
            $('div#categorypath > a').click(function () {
                var url = $(this).attr('href');
                var sVal = url.split('idcat=');
                var aVal = sVal[1].split('&');
                var iIdcat = aVal[0];
                sVal = url.split('idtpl=');
                aVal = sVal[1].split('&');
                var iIdtpl = aVal[0];
                var path = url.split('?');
                conMultiLink('right_top', path[0] + '?area=con&frame=3&idcat=' + iIdcat + '&idtpl=' + iIdtpl + '&display_menu=1&syncoptions=" . $syncoptions . "&contenido=" . $contenido . "',
                'right_bottom', url,
                'left_bottom', path[0] + '?area=con&frame=2&idcat=' + iIdcat + '&idtpl=' + iIdtpl + '&contenido=" . $contenido . "');
                return false;
            });
        });
    </script>";
$layoutcode .= '</body></html>';

// generate code
$code = _processCmsTags($aList, $result, true, $layoutcode);

if ($code == "0601") {
    markSubMenuItem("1");
    $code = "<script type='text/javascript'>location.href = '" . $cfg['path']['contenido_fullhtml'] . "main.php?frame=4&area=con_content_list&action=con_content&idart=" . $idart . "&idcat=" . $idcat . "&contenido=" . $contenido . "'; console.log(location.href);</script>";
} else {
    // inject some additional markup
    $code = str_ireplace_once("</head>", "$markSubItem $scripts\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=$encoding[$lang]\"></head>", $code);
    $code = str_ireplace_once_reverse("</body>", "$contentform</body>", $code);
    $code = str_ireplace_once("<head>", "<head>\n" . '<base href="' . $cfgClient[$client]["path"]["htmlpath"] . '">', $code);
}

if ($cfg["debug"]["codeoutput"]) {
    cDebug(htmlspecialchars($code));
}

//show ContentTypeList
chdir($cfgClient[$client]["path"]["frontend"]);
eval("?>\n" . $code . "\n<?php\n");
//}

cRegistry::shutdown();

/**
 * Processes replacements of all existing CMS_... tags within passed code
 *
 * @param  array   $aList  CMS_...tags list
 * @param  array   $contentList  all CMS variables
 * @param  bool    $saveKeywords  Flag to save collected keywords during replacement process.
 * @param  array   $contentList  Assoziative list of CMS variables
 */
function _processCmsTags($aList, $contentList, $saveKeywords = true, $layoutCode) {
    // #####################################################################
    // NOTE: Variables below are required in included/evaluated content type
    // codes!
    global $db, $db2, $sess, $cfg, $code, $cfgClient, $encoding;

    // NOTE: Variables below are additionally required in included/evaluated
    // content type codes within backend edit mode!
    global $edit, $editLink, $belang;

    $idcat = $_REQUEST['idcat'];
    $idart = $_REQUEST['idart'];
    $lang = $_REQUEST['lang'];
    $client = $_REQUEST['client'];
    $idartlang = $_REQUEST['idartlang'];
    $contenido = $_REQUEST['contenido'];

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
    $sql = 'SELECT `idtype`, `type`, `code`, `class` FROM `' . $cfg['tab']['type'] . '`';
    $db->query($sql);
    $_typeList = array();
    while ($db->next_record()) {
        $_typeList[] = $db->toObject();
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

            $typeCodeFile = $cfg['path']['contenido'] . 'includes/type/code/include.' . $type . '.code.php';
            $cTypeClassFile = $cfg['path']['contenido'] . 'classes/content_types/class.ctype.' . strtolower(str_replace('CMS_', '', $type)) . '.php';

            foreach ($a_[$key] as $val) {
                if (cFileHandler::exists($cTypeClassFile)) {
                    $tmp = $a_content[$_typeItem->type][$val];
                    $cTypeObject = new $_typeItem->class($tmp, $val, $a_content);
                    if (cRegistry::isBackendEditMode()) {
                        $tmp = $cTypeObject->generateEditCode();
                    } else {
                        $tmp = $cTypeObject->generateViewCode();
                    }
                } else if (cFileHandler::exists($typeCodeFile)) {
                    // include CMS type code
                    include ($typeCodeFile);
                } elseif (!empty($_typeItem->code)) {
                    // old version, evaluate CMS type code
                    cDeprecated("Move code for $type from table into file system (contenido/includes/cms/code/)");
                    eval($_typeItem->code);
                }
                $sql = "SELECT a.idcontent
                    FROM " . $cfg["tab"]["content"] . " as a, " . $cfg["tab"]["type"] . " as b
                    WHERE a.idartlang=" . cSecurity::toInteger($_REQUEST["idartlang"]) .
                        " AND a.idtype=b.idtype AND a.typeid = " . cSecurity::toInteger($val) . " AND b.type = '" . cSecurity::toString($type) . "'
                    ORDER BY a.idartlang, a.idtype, a.typeid";
                $db->query($sql);
                while ($db->next_record()) {
                    $idcontent = $db->f("idcontent");
                }
                $search[$val] = sprintf('%s[%s]', $type, $val);
                $path = $cfg['path']['contenido_fullhtml'] . 'main.php?area=con_content_list&action=deletecontype&changeview=edit&idart=' . $idart . '&idartlang=' . $idartlang .
                        '&idcat=' . $idcat . '&client=' . $client . '&lang=' . $lang . '&frame=4&contenido=' . $contenido . '&idcontent=' . $idcontent;
                if ($_typeItem->idtype == 20 || $_typeItem->idtype == 21) {
                    $tmp = str_replace('";?>', '', $tmp);
                    $tmp = str_replace('<?php echo "', '', $tmp);
                    //echo "<textarea>"."?".">\n".stripslashes($tmp)."\n\";?"."><"."?php\n"."</textarea>";
                }
                $replacements[$val] = $tmp .
                        '<a style="text-decoration:none;" href="javascript:setcontent(\'1\',\'' . $path . '\');">
                <img border="0" src="' . $cfg['path']['contenido_fullhtml'] . 'images/delete.gif">
                </a>';
                $keycode[$type][$val] = $tmp .
                        '<a style="text-decoration:none;" href="javascript:setcontent(\'1\',\'' . $path . '\');">
                <img border="0" src="' . $cfg['path']['contenido_fullhtml'] . 'images/delete.gif">
                </a>';
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
 * @param  array   $r  active CMS variables
 * @param  array   $aList  CMS_...tags list
 */
function getAktuellType($r, $aList) {
    $idcat = $_REQUEST['idcat'];
    $idart = $_REQUEST['idart'];
    $lang = $_REQUEST['lang'];
    $client = $_REQUEST['client'];
    global $db, $db2, $sess, $cfg, $code, $cfgClient, $encoding;

    // Select all cms_type entries
    $sql = 'SELECT idtype, type, code FROM ' . $cfg['tab']['type'];
    $db->query($sql);
    $_typeList = array();
    while ($db->next_record()) {
        $_typeList[] = $db->toObject();
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

?>