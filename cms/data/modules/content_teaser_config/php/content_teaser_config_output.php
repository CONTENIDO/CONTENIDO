<?php

/**
 * description: standard article doorway configuration
 *
 * @package Module
 * @subpackage ContentTeaser
 * @version SVN Revision $Rev:$
 *
 * @author timo.trautmann@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
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