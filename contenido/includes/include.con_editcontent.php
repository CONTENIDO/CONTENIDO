<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Include for editing the content in an article
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.0.3
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * @todo replace code generation by Contenido_CodeGenerator (see contenido/classes/CodeGenerator)
 *
 * {@internal
 *   created  2003
 *   modified 2008-06-16, Holger Librenz, Hotfix: check for illegal calls added
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2009-10-29, Murat Purc, replaced deprecated functions (PHP 5.3 ready) and some formatting
 *   modified 2010-05-20, Murat Purc, removed request check during processing ticket [#CON-307]
 *   modified 2011-01-11, Rusmir Jusufovic, load output of moduls from file
 *   modified 2011-06-24, Rusmir Jusufovic, load layout code from file
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

if (!isset($idcat)) {
    cRegistry::shutdown();
    return;
}


$edit = 'true';
$db2 = cRegistry::getDb();
$scripts = '';
$cssData = '';
$jsData = '';


if ($action == 20 || $action == 10) {
//     echo '<pre>' . print_r($data, true) . '</pre>';exit;
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
        $data  = $_REQUEST['data'];
        $value = $_REQUEST['value'];
    }

    conGenerateCodeForArtInAllCategories($idart);
}

$areaCode = '';
if(isset($area) && $area == 'con_content_list'){
    $areaCode = '&area='.$area;
}
if ($action == 10) {
    header('Location: ' . $cfg['path']['contenido_fullhtml'] . $cfg['path']['includes']
        . "include.backendedit.php?type=$type&typenr=$typenr&client=$client&lang=$lang&idcat=$idcat&idart=$idart&idartlang=$idartlang&contenido=$contenido&lang=$lang$areaCode");
} else {
    //@fulai.zhang: Mark submenuitem 'Editor' in the CONTENIDO Backend (Area: Contenido --> Articles --> Editor)
    $markSubItem = markSubMenuItem(4, true);

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

    $contentform  = '
<form name="editcontent" method="post" action="' . $sess->url($cfg['path']['contenido_fullhtml'] . "external/backendedit/front_content.php?area=con_editcontent&idart=$idart&idcat=$idcat&lang=$lang&action=20&client=$client") . '">
    <input type="hidden" name="changeview" value="edit">
    <input type="hidden" name="data" value="">
</form>
';

    // generate code
    $code = conGenerateCode($idcat, $idart, $lang, $client, false, false);
    if($code == "0601") {
        markSubMenuItem("1");
        $code = "<script type='text/javascript'>location.href = '".$cfg['path']['contenido_fullhtml']."main.php?frame=4&area=con_editart&action=con_edit&idart=".$idart."&idcat=".$idcat."&contenido=".$contenido."'; console.log(location.href);</script>";
    } else {
        // inject some additional markup
        $code = str_ireplace_once("</head>", "$markSubItem $scripts\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=$encoding[$lang]\"></head>", $code);
        $code = str_ireplace_once_reverse("</body>", "$contentform</body>", $code);
        $code = str_ireplace_once("<head>", "<head>\n" . '<base href="' . $cfgClient[$client]["path"]["htmlpath"] . '">', $code);
    }

    if ($cfg["debug"]["codeoutput"]) {
        cDebug(htmlspecialchars($code));
    }

    chdir($cfgClient[$client]["path"]["frontend"]);
    eval("?>\n".$code."\n<?php\n");
}

cRegistry::shutdown();

?>