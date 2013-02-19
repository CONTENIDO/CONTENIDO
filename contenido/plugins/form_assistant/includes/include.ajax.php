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

$action = $_REQUEST['action'];

$ajaxHandler = new PifaAjaxHandler();
try {
    $ajaxHandler->dispatch($action);
} catch (Exception $e) {
    Pifa::logException($e);
    Pifa::displayException($e);
}

?>