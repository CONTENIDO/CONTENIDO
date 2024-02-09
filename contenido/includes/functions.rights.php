<?php

/**
 * This file contains the CONTENIDO rights functions.
 *
 * These functions now are all deprecated. The class cRights should be used instead.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Martin Horwath
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Function checks if a language is associated with a given list of clients
 *
 * @param array $aClients
 *         array of clients to check
 * @param int $iLang
 *         language id which should be checked
 * @param array $aCfg
 *         CONTENIDO configruation array (not needed anymore)
 * @param cDb $oDb
 *         CONTENIDO database object (not needed anymore)
 *
 * @return bool
 *         If language id corresponds to list of clients true otherwise false.
 *
 * @throws cDbException
 * @deprecated [2019-03-26] use cApiClientLanguageCollection::hasLanguageInClients() instead
 *
 */
function checkLangInClients($aClients, $iLang, $aCfg, $oDb)
{
    $oClientLanguageCollection = new cApiClientLanguageCollection();

    return $oClientLanguageCollection->hasLanguageInClients($iLang, $aClients);
}

/**
 * Duplicate rights for any element.
 *
 * @param string $area
 *         Main area name (e.g. 'lay', 'mod', 'str', 'tpl', etc.)
 * @param int $iditem
 *         ID of element to copy
 * @param int $newiditem
 *         ID of the new element
 * @param bool $idlang
 *         ID of language, if passed only rights for this language
 *         will be created, otherwise for all existing languages
 *
 * @return bool
 *         True on success otherwise false
 *
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 * @deprecated [2019-03-26] use cRights::copyRightsForElement() instead
 *
 */
function copyRightsForElement($area, $iditem, $newiditem, $idlang = false)
{
    return cRights::copyRightsForElement($area, $iditem, $newiditem, $idlang);
}

/**
 * Create rights for any element
 *
 * @param string $area
 *         Main area name (e.g. 'lay', 'mod', 'str', 'tpl', etc.)
 * @param int $iditem
 *         ID of new element
 * @param bool $idlang
 *         ID of language, if passed only rights for this language
 *         will be created, otherwise for all existing languages
 *
 * @return bool
 *         True on success otherwise false
 *
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 * @deprecated [2019-03-26] use cRights::createRightsForElement() instead
 *
 */
function createRightsForElement($area, $iditem, $idlang = false)
{
    return cRights::createRightsForElement($area, $iditem, $idlang);
}

/**
 * Delete rights for any element
 *
 * @param string $area
 *         main area name
 * @param int $iditem
 *         ID of new element
 * @param bool $idlang
 *         ID of lang parameter
 *
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 * @deprecated [2019-03-26] use cRights::deleteRightsForElement() instead
 *
 */
function deleteRightsForElement($area, $iditem, $idlang = false)
{
    cRights::deleteRightsForElement($area, $iditem, $idlang);
}

/**
 * Builds user/group permissions (sysadmin, admin, client and language) by
 * processing request variables ($msysadmin, $madmin, $mclient, $mlang) and
 * returns the build permissions array.
 *
 * @param bool $bAddUserToClient
 *         Flag to add current user to current client, if no client is specified.
 *
 * @return array
 *
 * @throws cDbException
 * @deprecated [2019-03-26] use cRights::buildUserOrGroupPermsFromRequest() instead
 *
 */
function buildUserOrGroupPermsFromRequest($bAddUserToClient = false)
{
    return cRights::buildUserOrGroupPermsFromRequest($bAddUserToClient);
}

/**
 * @return bool
 *
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 * @deprecated [2019-03-26] use cRights::saveRights() instead
 */
function saveRights()
{
    return cRights::saveRights();
}

/**
 * @return bool
 *
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 * @deprecated [2019-03-26] use cRights::saveGroupRights() instead
 */
function saveGroupRights()
{
    return cRights::saveGroupRights();
}

/**
 * Build list of rights for all relevant and online areas except "login" and their relevant actions.
 *
 * @return array
 * @deprecated [2019-03-26] use cRights::getRightsList() instead
 */
function getRightsList()
{
    return cRights::getRightsList();
}
