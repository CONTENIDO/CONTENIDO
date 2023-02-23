<?php

/**
 * description: standard article doorway configuration
 *
 * Allows to select an image which will be used as teaser image by the module
 * content_teaser_image.
 *
 * @package    Module
 * @subpackage ContentTeaser
 * @author     Timo.trautmann@4fb.de
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

// assert framework initialization
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$tpl = cSmartyFrontend::getInstance();
$tpl->assign('isBackendEditMode', cRegistry::isBackendEditMode());
$tpl->assign('label', mi18n("LABEL_TEASERIMAGE"));
$tpl->assign('image', "CMS_IMG[100]");
$tpl->assign('editor', "CMS_IMGEDITOR[100]");
$tpl->display('get.tpl');

?>