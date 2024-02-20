<?php

/**
 *
 * @package    Plugin
 * @subpackage FormAssistant
 * @author     Marcus Gnaß <marcus.gnass@4fb.de>
 * @copyright  four for business AG
 * @link       https://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$action = $_REQUEST['action'];

try {
    $ajaxHandler = new PifaAjaxHandler();
    $ajaxHandler->dispatch($action);
} catch (Exception $e) {
    Pifa::logException($e);
    Pifa::displayException($e);
}
