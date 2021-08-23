<?php

/**
 * description: standard teaser - sliding element
 *
 * @package Module
 * @subpackage ContentTeaserSlider
 * @author timo.trautmann@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

mi18n("MORE");

// use smarty template to output label
$tpl = cSmartyFrontend::getInstance();
$tpl->assign('backend', cRegistry::isBackendEditMode());
$tpl->assign('label', mi18n("LABEL_TEXT"));
$tpl->display('get.tpl');

echo "CMS_TEASER[1]";

?>