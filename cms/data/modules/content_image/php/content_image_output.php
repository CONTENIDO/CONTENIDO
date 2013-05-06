<?php

/**
 * description: standard image
 *
 * @package Module
 * @subpackage ContentImage
 * @version SVN Revision $Rev:$
 *
 * @author marcus.gnass@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

// assert framework initialization
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

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

// When in backend edit mode add a label so the author
// knows what to type in the shown field.
if (cRegistry::isBackendEditMode()) {
    $label = mi18n("LABEL_IMAGE");
} else {
    $label = NULL;
}

// use smarty template to output header text
$tpl = cSmartyFrontend::getInstance();
$tpl->assign('label', $label);
$tpl->assign('editor', $imageEditor);
$tpl->assign('image', $image);
$tpl->display('get.tpl');

?>