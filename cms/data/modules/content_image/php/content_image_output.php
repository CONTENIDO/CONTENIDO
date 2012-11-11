<?php

/**
 * description: standard image
 *
 * @package Module
 * @subpackage content_image
 * @version SVN Revision $Rev:$
 * @author marcus.gnass@4fb.de
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// get image source from content type IMG with index 1
$imageSource = "CMS_IMG[1]";
// get description as content type IMGDESCR with index 1
$imageDescription = "CMS_IMGDESCR[1]";
// get editor as content type IMGEDITOR with index 1
// skip IMGEDITOR in frontend cause it displays the image too!
if (cRegistry::isBackendEditMode()) {
	$imageEditor = "CMS_IMGEDITOR[1]";
}

// build class containing all data necessary to display image
// therefor the image dimensions have to be determined
if (0 < strlen($imageSource)) {
    $clientConfig = cRegistry::getClientConfig(cRegistry::getClientId());
    $filename = str_replace($clientConfig["upl"]["htmlpath"], $clientConfig["upl"]["path"], $imageSource);
    list($imageWidth, $imageHeight) = getimagesize($filename);
    $image = new stdClass();
    $image->src = $imageSource;
    $image->alt = $imageDescription;
    $image->width = $imageWidth;
    $image->height = $imageHeight;
} else {
    $image = NULL;
}

// When in backend edit mode add labels so the author knows what to type in
// the shown field.
if (cRegistry::isBackendEditMode()) {
    $labelImage = mi18n("LABEL_IMAGE");
}

// use smarty template to output header text
$tpl = Contenido_SmartyWrapper::getInstance();
global $force;
if (1 == $force) {
    $tpl->clearAllCache();
}
$tpl->assign('labelImage', $labelImage);
$tpl->assign('editor', $imageEditor);
$tpl->assign('image', $image);
$tpl->display('content_image/template/get.tpl');

?>