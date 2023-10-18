<?php

/**
 * This file contains the groupselect extension of the frontend user plugin.
 *
 * @package    Plugin
 * @subpackage FrontendUsers
 * @author     Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $db;

/**
 * @return string
 */
function frontendusers_groupselect_getTitle () {
    return i18n('Groupname');
}

/**
 * @return string
 * @throws cDbException
 * @throws cException
 */
function frontendusers_groupselect_display() {
    $client = cSecurity::toInteger(cRegistry::getClientId());

    $iIdFrontendUser = cSecurity::toInteger($_REQUEST['idfrontenduser'] ?? '0');

    //render select
    $feGroups = new cApiFrontendGroupCollection();
    $feGroups->setWhere('idclient', $client);
    $feGroups->query();

    $aFEGroups = [];

    while ($feGroup = $feGroups->next()) {
        $aFEGroups[$feGroup->get('idfrontendgroup')] = $feGroup->get('groupname');
    }

    $oSelect = new cHTMLSelectElement('groupselect[]');
    $oSelect->autoFill($aFEGroups);
    $oSelect->setMultiselect();
    $oSelect->setSize(5);
    $oSelect->setStyle('width:265px;');

    //mark groups
    $oFEGroupMemberCollection = new cApiFrontendGroupMemberCollection;
    $oFEGroupMemberCollection->setWhere('idfrontenduser', $iIdFrontendUser);
    $oFEGroupMemberCollection->addResultField('idfrontendgroup');
    $oFEGroupMemberCollection->query();

    $aFEGroup = [];
    while ($oFEGroup = $oFEGroupMemberCollection->next()) {
        $aFEGroup[] = $oFEGroup->get('idfrontendgroup');
    }

    $oSelect->setDefault($aFEGroup);

    return $oSelect->render();
}

/**
 * @return array
 */
function frontendusers_groupselect_wantedVariables() {
    return (['groupselect']);
}

/**
 * @param $variables
 *
 * @return bool
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function frontendusers_groupselect_store($variables) {
    $client = cSecurity::toInteger(cRegistry::getClientId());

    $groups = $_REQUEST['groupselect'] ?? null;
    $iIdFrontendUser = cSecurity::toInteger($_REQUEST['idfrontenduser'] ?? '0');
    if (!is_array($groups)) {
        $groups = [];
    }

    $groupmembers = new cApiFrontendGroupMemberCollection();

    $feGroups = new cApiFrontendGroupCollection();
    $feGroups->setWhere('idclient', $client);
    $feGroups->query();

    while (($feGroup = $feGroups->next()) !== false) {
        $idFrontendGroup = $feGroup->get('idfrontendgroup');
        $groupmembers->remove($idFrontendGroup, $iIdFrontendUser);
        if (in_array($idFrontendGroup, $groups)) {
            $groupmembers->create($idFrontendGroup, $iIdFrontendUser);
        }
    }

    return true;
}

/**
 * @return array
 */
function frontendusers_groupselect_canonicalVariables() {
    //FFBCON-812
    return [];
}

/**
 * @param $key
 *
 * @return string
 */
function frontendusers_groupselect_getvalue($key) {
    return '';
}
