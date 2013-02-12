<?php

/**
 * description: site logo
 *
 * @package Module
 * @subpackage layout_logo
 * @author timo.trautmann@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */


// use template to display navigation
$tpl = Contenido_SmartyWrapper::getInstance();
global $force;
if (1 == $force) {
    $tpl->clearAllCache();
}

$clientConfig = cRegistry::getClientConfig(cRegistry::getClientId());

$tpl->assign('href', $clientConfig['path']['htmlpath']);
$tpl->display('get.tpl');

?>