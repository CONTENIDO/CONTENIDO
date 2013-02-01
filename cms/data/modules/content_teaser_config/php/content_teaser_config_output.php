<?php

/**
 * description: standard article doorway configuration
 *
 * @package Module
 * @subpackage content_teaser
 * @author timo.trautmann@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

// assert framework initialization
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// get text from content type HTML with index 1
$teaserImageEditor = "CMS_IMGEDITOR [100]";
$teaserImage = "CMS_IMG [100]";

// When in backend edit mode add a label so the author
// knows what to type in the shown field.
if (cRegistry::isBackendEditMode()) {
    $label = mi18n("LABEL_TEASERIMAGE");
} else {
    $label = NULL;
}

// use smarty template to output header text
$tpl = Contenido_SmartyWrapper::getInstance();
global $force;
if (1 == $force) {
    $tpl->clearAllCache();
}
$tpl->assign('isBackendEditMode', cRegistry::isBackendEditMode()? 'true' : 'false');
$tpl->assign('label', $label);
$tpl->assign('image', $teaserImage);
$tpl->assign('editor', $teaserImageEditor);
$tpl->display('content_teaser_config/template/get.tpl');

?>