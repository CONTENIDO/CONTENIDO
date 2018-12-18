<?php

/**
 * This file contains the editor page for content type CMS_TEXT.
 *
 * @package          Core
 * @subpackage       Backend_ContentType
 * @author           Jan Lengowski
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$backendUrl = cRegistry::getBackendUrl();
$frontendUrl = cRegistry::getFrontendUrl();

if (isset($area) && $area == 'con_content_list') {
    $tmp_area = $area;
    $href = "&action=10&idartlang=" . $idartlang . "&frame=4" . "&lang=" . $lang;
    $path1 = $backendUrl . "main.php?area=$tmp_area&idart=$idart&idcat=$idcat&changeview=edit&client=$client" . $href;
    $path2 = $path1;
    $inputHTML = '
    <input type="hidden" name="area" value="' . $area . '">
    <input type="hidden" name="frame" value="4">
    <input type="hidden" name="client" value="' . $client . '">';
} else {
    $tmp_area = 'con_editcontent';
    $path1 = $backendUrl . "external/backendedit/front_content.php?area=$tmp_area&idart=$idart&idcat=$idcat&changeview=edit&client=$client&lang=$lang";
    $path2 = $backendUrl . "external/backendedit/front_content.php?area=$tmp_area&idart=$idart&idcat=$idcat&changeview=edit&client=$client&lang=$lang";
    $inputHTML = "";
}

if ($doedit == "1") {
    conSaveContentEntry($idartlang, "CMS_TEXT", $typenr, $CMS_TEXT);
    conMakeArticleIndex($idartlang, $idart);
    conGenerateCodeForArtInAllCategories($idart);
    header("Location:" . $sess->url($path1) . "");
}

getAvailableContentTypes($idartlang);

header("Content-Type: text/html; charset={$encoding[$lang]}");

ob_start();

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
    <base href="<?php echo $frontendUrl; ?>">
    <title>include.CMS_TEXT.php</title>
{_META_HEAD_CONTENIDO_}
{_CSS_HEAD_CONTENIDO_FULLHTML_}
    <style type="text/css">
    body.cms_edit {margin: 19px;}
    .cms_edit_row {padding: 4px 0; margin: 0;}
    </style>
{_JS_HEAD_CONTENIDO_FULLHTML_}
</head>

<body class="cms_edit">
    <div class="cms_edit_wrap">
        <form method="post" action="<?php echo $backendUrl . $cfg["path"]["includes"] ?>include.backendedit.php">
            <input type="hidden" name="action" value="10">
            <input type="hidden" name="changeview" value="edit">
            <input type="hidden" name="doedit" value="1">
            <input type="hidden" name="idart" value="<?php echo $idart ?>">
            <input type="hidden" name="idartlang" value="<?php echo $idartlang ?>">
            <input type="hidden" name="idcat" value="<?php echo $idcat ?>">
            <input type="hidden" name="lang" value="<?php echo $lang ?>">
            <!--
            <input type="hidden" name="submit" value="editcontent">
            -->
            <input type="hidden" name="type" value="<?php echo $type ?>">
            <input type="hidden" name="typenr" value="<?php echo $typenr ?>">

            <p class="cms_edit_row text_medium">
                &nbsp;<?php echo $typenr?>.&nbsp;<?php echo $a_description[$type][$typenr]?>:&nbsp;
            </p>

            <div class="cms_edit_row">
                <textarea name="CMS_TEXT" rows="15" cols="90"><?php echo $a_content[$type][$typenr]?></textarea>
            </div>

            <div class="cms_edit_row">
                <a href="<?php echo $sess->url($path2) ?>"><img src="<?php echo $backendUrl . $cfg["path"]["images"] ?>but_cancel.gif" border="0"></a>
                <input type="image" name="submit" value="editcontent" src="<?php echo $backendUrl . $cfg["path"]["images"] ?>but_ok.gif" border="0">
            </div>
        </form>
    </div>
</body>
</html>
<?php

$output = ob_get_contents();
ob_end_clean();

$tpl = new cTemplate();
$tpl->generate($output);
