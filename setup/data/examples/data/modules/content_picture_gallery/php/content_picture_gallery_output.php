<?php

/**
 * description: picture gallery
 *
 * @package    Module
 * @subpackage ContentPictureGallery
 * @author     Timo.trautmann@4fb.de
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

$filelistIndex = 1;

$art = new cApiArticleLanguage();
$art->loadByArticleAndLanguageId(cRegistry::getArticleId(), cRegistry::getLanguageId());
$contentValue = $art->getContent("FILELIST", $filelistIndex);

$filelist = new cContentTypeFilelist($contentValue, $filelistIndex, []);
$files = $filelist->getConfiguredFiles();

$pictures = [];

if (count($files) > 0) {
    foreach ($files as $file) {
        $pathThumb = $file['path'] . '/' . $file['filename'];

        $record = [];
        $record['thumb'] = cApiImgScale($pathThumb, 319, 199);
        $record['lightbox'] = $cfgClient[$client]['upload'] . $pathThumb;
        $record['description'] = $file['metadata']['description'] ?? '';
        $record['copyright'] = $file['metadata']['copyright'] ?? '';

        $pictures[] = $record;
    }
}

$tpl = cSmartyFrontend::getInstance();
$tpl->assign('pictures', $pictures);

// Translations
$tpl->assign('back', mi18n("Back"));
$tpl->assign('forward', mi18n("Forward"));

$tpl->display('picture_gallery.tpl');

if (cRegistry::isBackendEditMode()) {
    echo "CMS_FILELIST[1]";
}

?>