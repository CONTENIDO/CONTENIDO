<?php
    $tpl = Contenido_SmartyWrapper::getInstance();
    $filelistIndex = 1;

    $art = new Article(cRegistry::getArticleLanguageId(), cRegistry::getClientId(), cRegistry::getLanguageId());
    $contentValue = $art->getContent("FILELIST", $filelistIndex);

    $filelist = new cContentTypeFilelist($contentValue, $filelistIndex, array());
    $files = $filelist->getConfiguredFiles();

    $pictures = array();

    foreach ($files as $file) {
        $path = 'upload/' . $file['path'] . '/' . $file['filename'];

        $record = array();
        $record['thumb'] = cApiImgScale($path, 319, 199);
        $record['lightbox'] = $path;
        $record['description'] = $file['metadata']['description'];
        $record['copyright'] = $file['metadata']['copyright'];

        array_push($pictures, $record);
    }

    $tpl->assign('pictures', $pictures);

    $tpl->display('picture_gallery.tpl');

    if (cRegistry::isBackendEditMode()) {
        echo "CMS_FILELIST[1]";
    }

?>