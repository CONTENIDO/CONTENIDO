<?php

/**
 * This file contains the editor page for content type CMS_HEAD.
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
 * @var string $CMS_HEAD
 * @var string $type
 * @var string $contenido
 * @var cSession $sess
 * @var array $encoding
 * @var array $cfg
 * @var array $a_description
 * @var array $a_content
 */

$backendUrl = cRegistry::getBackendUrl();
$frontendUrl = cRegistry::getFrontendUrl();
$doedit = $doedit ?? '0';

if (isset($area) && $area == 'con_content_list') {
    $tmp_area = $area;
    $path1 = $backendUrl . 'main.php?area=con_content_list&action=10&changeview=edit&idart=' . $idart . '&idartlang=' . $idartlang .
        '&idcat=' . $idcat . '&client=' . $client . '&lang=' . $lang . '&frame=4&contenido=' . $contenido;
    $path2 = $path1;
} else {
    $tmp_area = "con_editcontent";
    $path1 = $backendUrl . "external/backendedit/front_content.php?area=$tmp_area&idart=$idart&idcat=$idcat&changeview=edit&client=$client&lang=$lang";
    $path2 = $backendUrl . "external/backendedit/front_content.php?area=$tmp_area&idart=$idart&idcat=$idcat&changeview=edit&client=$client&lang=$lang";
}

if ($doedit == "1") {
    conSaveContentEntry($idartlang, "CMS_HEAD", $typenr, $CMS_HEAD);
    conMakeArticleIndex($idartlang, $idart);
    conGenerateCodeForArtInAllCategories($idart);
    header("Location:" . $sess->url($path1) . "");
}

getAvailableContentTypes($idartlang);

header("Content-Type: text/html; charset={$encoding[$lang]}");

ob_start();

?>
    <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN"
            "http://www.w3.org/TR/html4/frameset.dtd">
    <html>
    <head>
        <base href="<?php echo $frontendUrl; ?>">
        <title>include.CMS_HEAD.php</title>
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
        <form method="post"
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

            <?php if ($type == "CMS_HEAD") : ?>

                <p class="cms_edit_row text_medium">
                    &nbsp;<?php echo $typenr ?>
                    .&nbsp;<?php echo $a_description[$type][$typenr] ?? '' ?>:&nbsp;
                </p>

                <div class="cms_edit_row">
                    <input type="text" name="CMS_HEAD"
                           value="<?php echo conHtmlSpecialChars($a_content[$type][$typenr] ?? '') ?>"
                           size="90">
                </div>

            <?php endif; ?>

            <div class="con_form_action_control cms_edit_row">
                <input class="con_img_button mg0" type="image" name="submit" value="editcontent"
                       src="<?php echo $backendUrl . $cfg['path']['images'] ?>but_ok.gif"
                       alt="<?php echo i18n('Save changes') ?>"
                       title="<?php echo i18n('Save changes') ?>">
                <a class="con_img_button" href="<?php echo $sess->url($path2) ?>"><img
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
