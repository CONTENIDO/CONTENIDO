<?php

/**
 * This file contains the trait for user group permissions.
 *
 * @since      CONTENIDO 4.10.2
 * @package    Core
 * @subpackage Permissions
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

trait cUserGroupPermissionsTrait
{
    /**
     * Returns the permissions array.
     *
     * This function must be implemented in the class which uses this trait.
     */
    abstract protected function _getPermissionsArray(): array;

    /**
     * Returns the list of user/group permissions.
     */
    public function getPermsArray(): array
    {
        return cPermission::permissionToArray($this->_getPermissionsArray());
    }

    /**
     * Checks if the user/group has system administrator permissions.
     */
    public function hasSysadminPermission(): bool
    {
        return cPermission::checkSysadminPermission($this->_getPermissionsArray());
    }

    /**
     * Checks if the user/group has client administrator permissions.
     */
    public function hasClientAdminPermission(?int $clientId = null): bool
    {
        return cPermission::checkClientAdminPermission(
            $clientId ?? cRegistry::getClientId(),
            $this->_getPermissionsArray()
        );
    }

    /**
     * Checks if the user/group has language permissions.
     */
    public function hasLanguagePermission(?int $languageId = null): bool
    {
        return cPermission::checkLanguagePermission(
            $languageId ?? cRegistry::getLanguageId(),
            $this->_getPermissionsArray()
        );
    }

    /**
     * Checks if the user/group has client permissions.
     */
    public function hasClientPermission(?int $clientId = null): bool
    {
        return cPermission::checkClientPermission(
            $clientId ?? cRegistry::getClientId(),
            $this->_getPermissionsArray()
        );
    }

    /**
     * Checks if the user/group has client and language permissions.
     */
    public function hasClientAndLanguagePermission(?int $clientId = null, ?int $languageId = null): bool
    {
        return cPermission::checkClientAndLanguagePermission(
            $clientId ?? cRegistry::getClientId(),
            $languageId ?? cRegistry::getLanguageId(),
            $this->_getPermissionsArray()
        );
    }
}
