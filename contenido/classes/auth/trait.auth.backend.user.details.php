<?php

/**
 * This file contains the backend user details trait for usage in authentication handler classes.
 *
 * @since      CONTENIDO 4.10.2
 * @package    Core
 * @subpackage Authentication
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

trait cAuthBackendUserDetailsTrait
{
    /**
     * Populates the given user details object with backend user information based on the provided username.
     *
     * @param stdClass $userDetails The object where the user details will be loaded.
     * @param string $username The username to identify and fetch user details.
     */
    private function loadBackendUserDetails(stdClass $userDetails, string $username)
    {
        try {
            $userItem = (new cApiUserCollection())->fetchUserForLoginAttempt($username);
            if ($userItem) {
                $userDetails->userId = $userItem->getId();
                $userDetails->perm = $userItem->get('perms');
                $userDetails->password = $userItem->get('password');
                $userDetails->salt = $userItem->get('salt');
            }
        } catch (cDbException|cException $e) {
            $e->log();
        }
    }
}
