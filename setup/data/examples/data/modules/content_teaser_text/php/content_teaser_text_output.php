<?php

/**
 * description: standard teaser - text teaser
 *
 * @package Module
 * @subpackage ContentTeaserText
 * @author timo.trautmann@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

mi18n("MORE");

// Get teaser output

ob_start();

echo "CMS_TEASER[3]";

$teaser = ob_get_contents();

ob_end_clean();

// use smarty template to output header text
$tpl = cSmartyFrontend::getInstance();
$tpl->assign('backend', cRegistry::isBackendEditMode());
$tpl->assign('label', mi18n("LABEL_TEXT"));
$tpl->assign('teaser', $teaser);
$tpl->display('get.tpl');

?>