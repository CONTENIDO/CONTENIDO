<?php

/**
 * description: standard date
 *
 * @package Module
 * @subpackage ContentDate
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

// get text from content type HTML with index 1
$date = "CMS_DATE[1]";

// When in backend edit mode add a label so the author
// knows what to type in the shown field.
if (cRegistry::isBackendEditMode()) {
    $label = mi18n("LABEL_DATE");
} else {
    $label = NULL;
}

// use smarty template to output header text
$tpl = cSmartyFrontend::getInstance();
$tpl->assign('label', $label);
$tpl->assign('date', $date);
$tpl->display('get.tpl');

?>