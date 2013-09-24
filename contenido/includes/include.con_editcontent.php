<?php
/**
 * This file contains the backend page for editing articles content.
 *
 * @todo replace code generation by Contenido_CodeGenerator (see contenido/classes/CodeGenerator)
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Jan Lengowski
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if (!isset($idcat)) {
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

$data = cSecurity::toString($_REQUEST['data']);
if ($action == 20 || $action == 10) {
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
$markSubItem = markSubMenuItem(4, true);

// Include tiny class
include ($backendPath . 'external/wysiwyg/tinymce3/editorclass.php');
$oEditor = new cTinyMCEEditor('', '');
$oEditor->setToolbar('inline_edit');

// Get configuration for popup und inline tiny
$sConfigInlineEdit = $oEditor->getConfigInlineEdit();
$sConfigFullscreen = $oEditor->getConfigFullscreen();

// Replace vars in Script
$oScriptTpl = new cTemplate();

$oScriptTpl->set('s', 'CONTENIDO_FULLHTML', $backendUrl);

// Set urls to file browsers
$oScriptTpl->set('s', 'IMAGE', $backendUrl . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
$oScriptTpl->set('s', 'FILE', $backendUrl . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=filebrowser');
$oScriptTpl->set('s', 'FLASH', $backendUrl . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
$oScriptTpl->set('s', 'MEDIA', $backendUrl . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
$oScriptTpl->set('s', 'FRONTEND', cRegistry::getFrontendUrl());

// Add tiny options and fill function leave_check()
$oScriptTpl->set('s', 'TINY_OPTIONS', $sConfigInlineEdit);
$oScriptTpl->set('s', 'TINY_FULLSCREEN', $sConfigFullscreen);
$oScriptTpl->set('s', 'IDARTLANG', $idartlang);
$oScriptTpl->set('s', 'CON_PATH', cRegistry::getBackendUrl());
$oScriptTpl->set('s', 'CLOSE', utf8_encode(html_entity_decode(i18n('Close editor'))));
$oScriptTpl->set('s', 'SAVE', utf8_encode(html_entity_decode(i18n('Close editor and save changes'))));
$oScriptTpl->set('s', 'QUESTION', i18n('Do you want to save changes?'));

if (getEffectiveSetting('system', 'insite_editing_activated', 'true') == 'false') {
    $oScriptTpl->set('s', 'USE_TINY', '');
} else {
    $oScriptTpl->set('s', 'USE_TINY', 'swapTiny(this);');
}

$scripts = $oScriptTpl->generate($backendPath . $cfg['path']['templates'] . $cfg['templates']['con_editcontent'], 1);

$contentform = '
<form name="editcontent" method="post" action="' . $sess->url($backendUrl . "external/backendedit/front_content.php?area=con_editcontent&idart=$idart&idcat=$idcat&lang=$lang&action=20&client=$client") . '">
<input type="hidden" name="changeview" value="edit">
<input type="hidden" name="data" value="">
</form>
';

// generate code
$code = conGenerateCode($idcat, $idart, $lang, $client, false, false);
if ($code == "0601") {
    markSubMenuItem("1");
    $code = "<script type='text/javascript'>location.href = '" . $backendUrl . "main.php?frame=4&area=con_editart&action=con_edit&idart=" . $idart . "&idcat=" . $idcat . "&contenido=" . $contenido . "'; /*console.log(location.href);*/</script>";
} else {
    // inject some additional markup
    $code = cString::iReplaceOnce("</head>", "$markSubItem $scripts\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=$encoding[$lang]\"></head>", $code);
    $code = cString::iReplaceOnceReverse("</body>", "$contentform</body>", $code);
    $code = cString::iReplaceOnce("<head>", "<head>\n" . '<base href="' . cRegistry::getFrontendUrl() . '">', $code);
}

if ($cfg["debug"]["codeoutput"]) {
    cDebug::out(conHtmlSpecialChars($code));
}

chdir(cRegistry::getFrontendPath());
eval("?>\n\n<!-- asdasd -->\n" . $code . "\n<?php\n");

cRegistry::shutdown();

?>