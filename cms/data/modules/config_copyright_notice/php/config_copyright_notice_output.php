<?php

/**
 * description: copyright notice configurator
 *
 * @package Module
 * @subpackage config_copyright_notice
 * @version SVN Revision $Rev:$
 * @author marcus.gnass@4fb.de
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

if (cRegistry::isBackendEditMode()) {

	$label = mi18n("LABEL_COPYRIGHT");
	$text = "CMS_HTML[1]";

    // use smarty template to output header text
    $tpl = Contenido_SmartyWrapper::getInstance();
    global $force;
    if (1 == $force) {
        $tpl->clearAllCache();
    }
    $tpl->assign('label', $label);
    $tpl->assign('text', $text);
    $tpl->display('config_copyright_notice/template/get.tpl');

}

?>