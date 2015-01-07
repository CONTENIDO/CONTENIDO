<?php
/**
 * This file contains the cronjob to delete old password reset requests from users.
 *
 * @package    Core
 * @subpackage Cronjob
 *
 * @author     Thomas Stauer
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    define('CON_FRAMEWORK', true);
}

// CONTENIDO path
$contenidoPath = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../')) . '/';

// CONTENIDO startup process
include_once($contenidoPath . 'includes/startup.php');

global $cfg;

if (!isRunningFromWeb() || function_exists('runJob') || $area == 'cronjobs') {
    $db = cRegistry::getDb();

    $oApiPasswordRequestCol = new cApiUserPasswordRequestCollection();
    $requests = $oApiPasswordRequestCol->fetchAvailableRequests();

    // do maintainance for all user password requests
    foreach ($requests as $oApiUserPasswordRequest) {
        // get time of password reset request
        $reqTime = $oApiUserPasswordRequest->get('request');

        // check if password request is too old and considered outdated
        // by default 1 day old requests are outdated
        if (false === $outdatedStr = getEffectiveSetting('pw_reset', 'outdated_threshold', false)
        || '' === $outdatedStr) {
            $outdatedStr = '-1 day';
        }
        // convert times to DateTime objects for comparison
        // force all data to be compared using UTC timezone
        $outdated = new DateTime('now', new DateTimeZone('UTC'));
        $outdated->modify($outdatedStr);
        $expiration = new DateTime($oApiUserPasswordRequest->get('expiration'), new DateTimeZone('UTC'));
        if (false === $oApiUserPasswordRequest->get('expiration')
        || '' === $oApiUserPasswordRequest->get('expiration')
        || $expiration < $outdated) {echo $oApiUserPasswordRequest->get($oApiUserPasswordRequest->primaryKey);
            // delete password request as it is considered outdated
            $oApiPasswordRequestCol->delete($oApiUserPasswordRequest->get($oApiUserPasswordRequest->primaryKey));
        }
    }
}

?>