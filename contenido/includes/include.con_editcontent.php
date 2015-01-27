<?php
/**
 * This file contains the backend page for editing articles content.
 *
 * @todo replace code generation by Contenido_CodeGenerator (see
 *       contenido/classes/CodeGenerator)
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Jan Lengowski
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
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
$markSubItem = markSubMenuItem(5, true);

// Include wysiwyg editor class
if (false === ($wysiwygeditor = getEffectiveSetting('wysiwyg', 'editor', false))) {
    $wysiwygeditor = $cfg['wysiwyg']['editor'];
}
// tinymce 3 not autoloaded, tinymce 4 and all custom editors must be
if ('tinymce3' === $wysiwygeditor) {
    include($cfg['path'][$wysiwygeditor . '_editorclass']);
}
switch ($wysiwygeditor) {
    case 'tinymce4':
        $oEditor = new cTinyMCE4Editor('', '');
        $oEditor->setToolbar('inline_edit');

        // Get configuration for popup and inline tiny
        $sConfigInlineEdit = $oEditor->getConfigInlineEdit();
        $sConfigFullscreen = $oEditor->getConfigFullscreen();

        break;
    default:
        $oEditor = new cTinyMCEEditor('', '');
        $oEditor->setToolbar('inline_edit');

        // Get configuration for popup and inline tiny
        $sConfigInlineEdit = $oEditor->getConfigInlineEdit();
        $sConfigFullscreen = $oEditor->getConfigFullscreen();
}

// Replace vars in Script
$oScriptTpl = new cTemplate();

$jslibs = '';
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
$oScriptTpl->set('s', '_WYSIWYG_JS_TAGS_', $jslibs);
unset($jslibs);

$oScriptTpl->set('s', 'JS_EDITCONTENT', $markSubItem);

// Set urls to file browsers
$oScriptTpl->set('s', 'IMAGE', $backendUrl . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
$oScriptTpl->set('s', 'FILE', $backendUrl . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=filebrowser');
$oScriptTpl->set('s', 'MEDIA', $backendUrl . 'frameset.php?area=upl&contenido=' . $sess->id . '&appendparameters=imagebrowser');
$oScriptTpl->set('s', 'FRONTEND', cRegistry::getFrontendUrl());

// Add tiny options
$oScriptTpl->set('s', 'TINY_OPTIONS', $sConfigInlineEdit);
$oScriptTpl->set('s', 'TINY_FULLSCREEN', $sConfigFullscreen);
$oScriptTpl->set('s', 'IDARTLANG', $idartlang);
$oScriptTpl->set('s', 'CLOSE', utf8_encode(html_entity_decode(i18n('Close editor'))));
$oScriptTpl->set('s', 'SAVE', utf8_encode(html_entity_decode(i18n('Close editor and save changes'))));
$oScriptTpl->set('s', 'QUESTION', i18n('Do you want to save changes?'));
$oScriptTpl->set('s', 'BACKEND_URL', cRegistry::getBackendUrl());

if (getEffectiveSetting('system', 'insite_editing_activated', 'true') == 'false') {
    $oScriptTpl->set('s', 'USE_TINY', '');
} else {
    $oScriptTpl->set('s', 'USE_TINY', '1');
}

// check if file with list of client plugins is supplied
if ('true' === getEffectiveSetting('tinymce4', 'contenido_load_client_plugins', false)) {
        // disallow any file not pointing into tinymce 4 config folder of client
        // to do that use fixed paths
        if ('true' === getEffectiveSetting('tinymce4', 'contenido_load_all_client_plugins')) {
            // fixed path for plugins to load
            $pluginFolderPath = cRegistry::getFrontendPath() . 'external/wysiwyg/tinymce4/contenido/client_plugins/plugins/';
            // look for all plugins (they are folders) in plugin folder
            $pluginFolderList = cDirHandler::read($pluginFolderPath, false, true);
            $tiny4ClientPlugins = array();
            foreach ($pluginFolderList as $pluginFolderName) {
                $pluginPath = $pluginFolderPath . $pluginFolderName . '/';
                // replace lagging frontend path with frontend url
                $pluginUrl = substr_replace($pluginPath, cRegistry::getFrontendUrl(), 0, strlen(cRegistry::getFrontendPath()));
                // check if minified version of plugin exists
                if (true === cFileHandler::exists($pluginPath . 'plugin.min.js')) {
                    $tiny4ClientPlugins[] = (object) array('name' => $pluginFolderName,
                                                           'path' => $pluginUrl . 'plugin.min.js');
                }  else {
                    // check if non-minified version of plugin exists
                    if (true === cFileHandler::exists($pluginPath . 'plugin.js')) {
                        $tiny4ClientPlugins[] = (object) array('name' => $pluginFolderName,
                                                               'path' => $pluginUrl . 'plugin.js');
                    }
                }
            }
            $oScriptTpl->set('s', 'CLIENT_PLUGINS', json_encode($tiny4ClientPlugins));
        } else {
            // load only specific plugins from config file
            $tiny4ClientPlugins = cRegistry::getFrontendPath() . 'data/tinymce4config/clientplugins.json';
            if (cFileHandler::exists($tiny4ClientPlugins)
            && cFileHandler::readable($tiny4ClientPlugins)) {
                $oScriptTpl->set('s', 'CLIENT_PLUGINS', cFileHandler::read($tiny4ClientPlugins));
            }
        }
} else {
    // no client plugins to load
    $oScriptTpl->set('s', 'CLIENT_PLUGINS', '[]');
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
    $code = cString::iReplaceOnce("</head>", "$scripts\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=$encoding[$lang]\"></head>", $code);
    $code = cString::iReplaceOnceReverse("</body>", "$contentform</body>", $code);
    $code = cString::iReplaceOnce("<head>", "<head>\n" . '<base href="' . cRegistry::getFrontendUrl() . '">', $code);
}

if ($cfg["debug"]["codeoutput"]) {
    cDebug::out(conHtmlSpecialChars($code));
}

chdir(cRegistry::getFrontendPath());

eval("?>\n" . $code . "\n<?php\n");

cRegistry::shutdown();

?>