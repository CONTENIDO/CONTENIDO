<?php

/**
 * description: standard date
 *
 * @package Module
 * @subpackage content_header_first
 * @version SVN Revision $Rev:$
 * @author marcus.gnass@4fb.de
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// get text from content type HTML with index 1
$date = "CMS_DATE[1]";

if (cRegistry::isBackendEditMode()) {
    // When in backend edit mode add a label so the author knows what to type
    // in the shown field.
    $label = mi18n("LABEL_DATE");
}

// use smarty template to output header text
$tpl = Contenido_SmartyWrapper::getInstance();
$tpl->assign('label', $label);
$tpl->assign('date', $date);
$tpl->display('content_date/template/get.tpl');

?>