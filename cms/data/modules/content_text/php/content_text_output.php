<?php

/**
 * description: standard text
 *
 * @package Module
 * @subpackage content_header_first
 * @version SVN Revision $Rev:$
 * @author marcus.gnass@4fb.de
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// get text as content type HTML with index 1
$text = "CMS_HTML[1]";

if (cRegistry::isBackendEditMode()) {
    // When in backend edit mode add a label so the author knows what to type
    // in the shown field.
    $label = mi18n("LABEL_TEXT");
}

// use smarty template to output header text
$tpl = Contenido_SmartyWrapper::getInstance();
$tpl->assign('label', $label);
$tpl->assign('text', $text);
$tpl->display('content_text/template/get.tpl');

?>