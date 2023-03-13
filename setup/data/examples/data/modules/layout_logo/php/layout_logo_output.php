<?php

/**
 * description: site logo
 *
 * @package    Module
 * @subpackage LayoutLogo
 * @author     Timo.trautmann@4fb.de
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

// use template to display navigation
$tpl = cSmartyFrontend::getInstance();
$tpl->assign('href', cRegistry::getFrontendUrl());
$tpl->display('get.tpl');

?>