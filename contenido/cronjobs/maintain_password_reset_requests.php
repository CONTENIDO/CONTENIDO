<?php
/**
 * This file contains the cronjob to delete old password reset requests from users.
 *
 * @package    Core
 * @subpackage Cronjob
 *
 * @author     Thomas Stauer
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

// CONTENIDO path
$contenidoPath = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../')) . '/';

// CONTENIDO startup process
include_once($contenidoPath . 'includes/startup.php');

$area = $area ?? '';

if (!isRunningFromWeb() || function_exists('runJob') || $area == 'cronjobs') {
    // Do the maintenance for all user password requests,
    // clear the password requests table of old entries.
    $oApiPasswordRequestCol = new cApiUserPasswordRequestCollection();
    $numDeleted = $oApiPasswordRequestCol->deleteExpired();
}
