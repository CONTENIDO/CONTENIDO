<?php

/**
 * description: copyright notice configurator
 *
 * @package Module
 * @subpackage ConfigCopyrightNotice
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

if (cRegistry::isBackendEditMode()) {

    $text = "CMS_HTML[1]";

    // use smarty template to output header text
    $tpl = cSmartyFrontend::getInstance();
    $tpl->assign('label', mi18n("LABEL_COPYRIGHT"));
    $tpl->assign('text', $text);
    $tpl->display('get.tpl');
}

?>