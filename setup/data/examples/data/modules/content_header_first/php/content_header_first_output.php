<?php

/**
 * description: standard first header (H1)
 * Header will not be output if no or an empty text is given.
 *
 * @package Module
 * @subpackage ContentHeaderFirst
 * @author marcus.gnass@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

// assert framework initialization
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// get header from content type HTMLHEAD with index 1
$header = "CMS_HTMLHEAD[1]";

// When in backend edit mode add a label so the author
// knows what to type in the shown field.
// When not in backend edit mode any tags are removed
// for the template is responsible for displaying the
// given text as a header.
if (cRegistry::isBackendEditMode()) {
    $label = mi18n("LABEL_HEADER_FIRST");
} else {
    $label = '';
    $header = str_replace('&nbsp;', ' ', $header);
    $header = strip_tags($header);
    $header = trim($header);
}

// use smarty template to output header text
$tpl = cSmartyFrontend::getInstance();
$tpl->assign('label', $label);
$tpl->assign('header', $header);
$tpl->display('get.tpl');

?>