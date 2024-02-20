<?php

/**
 * This file contains the editor page for content type CMS_HTML.
 *
 * @package    Core
 * @subpackage Backend_ContentType
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var int $idartlang
 * @var int $lang
 * @var int $idart
 * @var int $idcat
 * @var int $client
 * @var int $typenr
 * @var string $CMS_HTML
 * @var string $type
 * @var cSession $sess
 * @var array $encoding
 * @var array $cfg
 * @var array $a_description
 */

$backendUrl = cRegistry::getBackendUrl();
$frontendUrl = cRegistry::getFrontendUrl();
$doedit = $doedit ?? '0';

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

if ($doedit == "1" || $doedit == "2") {
    //1: save; 2: refresh;
    conSaveContentEntry($idartlang, "CMS_HTML", $typenr, $CMS_HTML);
    conMakeArticleIndex($idartlang, $idart);
    conGenerateCodeForArtInAllCategories($idart);
}
if ($doedit == "1") {
    //save
    header("Location:" . $sess->url($path1) . "");
}

getAvailableContentTypes($idartlang);

header("Content-Type: text/html; charset={$encoding[$lang]}");

ob_start();

?>
    <!DOCTYPE html>
    <html>
    <head>
        <base href="<?php echo $frontendUrl; ?>">
        <title>include.CMS_HTMLHEAD.php</title>
        {_META_HEAD_CONTENIDO_}
        {_CSS_HEAD_CONTENIDO_FULLHTML_}
        <style type="text/css">
            body.cms_edit {
                margin: 19px;
            }

            .cms_edit_row {
                padding: 4px 0;
                margin: 0;
            }
        </style>
        {_JS_HEAD_CONTENIDO_FULLHTML_}
    </head>

    <body class="cms_edit">
    <div class="cms_edit_wrap">
        <form method="post" name="editcontent"
              action="<?php echo $backendUrl . $cfg['path']['includes'] ?>include.backendedit.php">
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
            <?php echo $inputHTML ?>

            <p class="cms_edit_row text_medium">
                &nbsp;<?php echo $typenr ?>.&nbsp;<?php echo $a_description[$type][$typenr] ?? '' ?>
                :&nbsp;
            </p>

            <div class="cms_edit_row">

                <?php

                // Include wysiwyg editor class
                $wysiwygeditor = cWYSIWYGEditor::getCurrentWysiwygEditorName();

                // tinymce 3 not autoloaded, tinymce 4 and all custom editor classes must be
                if ($wysiwygeditor === 'tinymce3') {
                    include($cfg['path'][$wysiwygeditor . '_editorclass']);
                }

                // load editor
                include($cfg['path'][$wysiwygeditor . '_editor']);

                ?>

            </div>

            <div class="con_form_action_control cms_edit_row">
                <input class="con_img_button mg0" type="image" name="submit" value="editcontent"
                       src="<?php echo $backendUrl . $cfg['path']['images'] ?>but_ok.gif"
                       alt="<?php echo i18n("Save and close editor") ?>"
                       title="<?php echo i18n("Save and close editor") ?>">
                <input class="con_img_button" type="image" name="save" value="editcontent"
                       src="<?php echo $backendUrl . $cfg['path']['images'] ?>but_refresh.gif"
                       onclick="document.forms[0].doedit.value='2';document.forms[0].submit();"
                       alt="<?php echo i18n("Save without leaving the editor") ?>"
                       title="<?php echo i18n("Save without leaving the editor") ?>">
                <a class="con_img_button" data-tiny-role="cancel"
                   href="<?php echo $sess->url($path2) ?>"><img
                            src="<?php echo $backendUrl . $cfg['path']['images'] ?>but_cancel.gif"
                            alt="<?php echo i18n("Cancel") ?>" title="<?php echo i18n("Cancel") ?>"></a>
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
