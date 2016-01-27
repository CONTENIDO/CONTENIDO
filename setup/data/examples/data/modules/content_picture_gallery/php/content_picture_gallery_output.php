<?php

/**
 * description: google map
 *
 * @package Module
 * @subpackage ContentPictureGallery
 * @author timo.trautmann@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

$filelistIndex = 1;

$art = new cApiArticleLanguage();
$art->loadByArticleAndLanguageId(cRegistry::getArticleId(), cRegistry::getLanguageId());
$contentValue = $art->getContent("FILELIST", $filelistIndex);

$filelist = new cContentTypeFilelist($contentValue, $filelistIndex, array());
$files = $filelist->getConfiguredFiles();

$pictures = array();

if (count($files) > 0) {
    foreach ($files as $file) {
        $pathThumb = $file['path'] . '/' . $file['filename'];

        $record = array();
        $record['thumb'] = cApiImgScale($pathThumb, 319, 199);
        $record['lightbox'] = $cfgClient[$client]['upload'] . $pathThumb;
        $record['description'] = $file['metadata']['description'];
        $record['copyright'] = $file['metadata']['copyright'];

        array_push($pictures, $record);
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