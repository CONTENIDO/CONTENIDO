<?php

/**
 * description: copyright notice configurator
 *
 * @package    Module
 * @subpackage ConfigCopyrightNotice
 * @author     marcus.gnass@4fb.de
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
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