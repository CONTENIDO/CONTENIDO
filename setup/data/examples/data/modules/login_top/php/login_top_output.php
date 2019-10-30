<?php

/**
 * description: top login
 *
 * @package Module
 * @subpackage LoginTop
 * @author timo.trautmann@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

// assert framework initialization
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// get client settings
$loginIdart = getEffectiveSetting('login', 'idart', 1);

$active = '';
$link = 'front_content.php?idart=' . $loginIdart;
$label = mi18n("LOGIN");

$curIdart = cRegistry::getArticleId();
if ($curIdart == $loginIdart) {
	$active = 'active';
}

$curAuth = cRegistry::getAuth();
if ($curAuth->auth['uid'] != '' && $curAuth->auth['uid'] != 'nobody') {
	$link = 'front_content.php?logout=true';
	$label = mi18n("LOGOUT");
}

// use template to display navigation
$tpl = cSmartyFrontend::getInstance();
$tpl->assign('link', $link);
$tpl->assign('active', $active);
$tpl->assign('label', $label);
$tpl->display('get.tpl');

?>