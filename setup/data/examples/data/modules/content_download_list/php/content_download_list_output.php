<?php

/**
 * description: standard download list
 *
 * @package    Module
 * @subpackage ContentDownloadList
 * @author     Timo.trautmann@4fb.de
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

// assert framework initialization
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// When in backend edit mode add a label so the author
// knows what to type in the shown field.
if (cRegistry::isBackendEditMode()) {
    $label = mi18n("LABEL_HEADER_DOWNLOADLIST");
} else {
    $label = '';
}

ob_start();
echo "CMS_FILELIST[1]";
$filelist = ob_get_contents();
ob_end_clean();

// use smarty template to output header text
$tpl = cSmartyFrontend::getInstance();
$tpl->assign('label', $label);
$tpl->assign('filelist', $filelist);
$tpl->display('get.tpl');

?>