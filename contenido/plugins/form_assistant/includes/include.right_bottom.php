<?php

/**
 *
 * @package Plugin
 * @subpackage PIFA Form Asistant
 * @version SVN Revision $Rev:$
 * @author marcus.gnass
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') or die('Illegal call');

// create and render page
try {
	$page = new PifaRightBottomPage();
	$page->render();
} catch (Exception $e) {
    Util::logException($e);
    echo Util::notifyException($e);
}

?>