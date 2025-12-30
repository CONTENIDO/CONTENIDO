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
 * @deprecated [2019-03-26] use {@see cApiClientLanguageCollection::hasLanguageInClients()} instead
 */
function checkLangInClients($aClients, $iLang, $aCfg, $oDb)
{
    cDeprecated("The function checkLangInClients() is deprecated, use cApiClientLanguageCollection::hasLanguageInClients() instead.");

    $oClientLanguageCollection = new cApiClientLanguageCollection();

    return $oClientLanguageCollection->hasLanguageInClients($iLang, $aClients);
}

/**
 * @deprecated [2019-03-26] use {@see cRights::copyRightsForElement()} instead
 */
function copyRightsForElement($area, $iditem, $newiditem, $idlang = false)
{
    cDeprecated("The function copyRightsForElement() is deprecated, use cRights::copyRightsForElement() instead.");
    return cRights::copyRightsForElement($area, $iditem, $newiditem, $idlang);
}

/**
 * @deprecated [2019-03-26] use {@see cRights::createRightsForElement()} instead
 */
function createRightsForElement($area, $iditem, $idlang = false)
{
    cDeprecated("The function createRightsForElement() is deprecated, use cRights::createRightsForElement() instead.");
    return cRights::createRightsForElement($area, $iditem, $idlang);
}

/**
 * @deprecated [2019-03-26] use {@see cRights::deleteRightsForElement()} instead
 */
function deleteRightsForElement($area, $iditem, $idlang = false)
{
    cDeprecated("The function deleteRightsForElement() is deprecated, use cRights::deleteRightsForElement() instead.");
    cRights::deleteRightsForElement($area, $iditem, $idlang);
}

/**
 * @deprecated [2019-03-26] use {@see cRights::buildUserOrGroupPermsFromRequest()} instead
 */
function buildUserOrGroupPermsFromRequest($bAddUserToClient = false)
{
    cDeprecated("The function buildUserOrGroupPermsFromRequest() is deprecated, use cRights::buildUserOrGroupPermsFromRequest() instead.");
    return cRights::buildUserOrGroupPermsFromRequest($bAddUserToClient);
}

/**
 * @deprecated [2019-03-26] use {@see cRights::saveRights()} instead
 */
function saveRights()
{
    cDeprecated("The function saveRights() is deprecated, use cRights::saveRights() instead.");
    return cRights::saveRights();
}

/**
 * @deprecated [2019-03-26] use {@see cRights::saveGroupRights()} instead
 */
function saveGroupRights()
{
    cDeprecated("The function saveGroupRights() is deprecated, use cRights::saveGroupRights() instead.");
    return cRights::saveGroupRights();
}

/**
 * @deprecated [2019-03-26] use {@see cRights::getRightsList()} instead
 */
function getRightsList()
{
    cDeprecated("The function getRightsList() is deprecated, use cRights::getRightsList() instead.");
    return cRights::getRightsList();
}
