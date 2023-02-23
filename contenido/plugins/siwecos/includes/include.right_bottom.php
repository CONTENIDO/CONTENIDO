<?php

/**
 *
 * @package    Plugin
 * @subpackage SIWECOS
 * @author     Fulai Zhang <fulai.zhang@4fb.de>
 * @copyright  four for business AG
 * @link       https://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// create and render page
try {
    $page = new SIWECOSRightBottomPage();
    $page->render();
} catch (Exception $e) {
    SIWECOS::logException($e);
    echo SIWECOS::notifyException($e);
}
