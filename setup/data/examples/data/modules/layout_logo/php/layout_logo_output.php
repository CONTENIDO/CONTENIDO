<?php

/**
 * description: site logo
 *
 * @package Module
 * @subpackage LayoutLogo
 * @version SVN Revision $Rev:$
 *
 * @author timo.trautmann@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

$clientConfig = cRegistry::getClientConfig(cRegistry::getClientId());

// use template to display navigation
$tpl = cSmartyFrontend::getInstance();
$tpl->assign('href', $clientConfig['path']['htmlpath']);
$tpl->display('get.tpl');

?>