<?php
/**
 * This file contains the groupselect extension of the frontend user plugin.
 *
 * @package    Plugin
 * @subpackage FrontendUsers
 * @version    SVN Revision $Rev:$
 *
 * @author     Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $db;

function frontendusers_groupselect_getTitle () {
    return i18n("Groupname");
}

function frontendusers_groupselect_display () {
    global $client;
    $iIdfrontenduser = (int)$_REQUEST['idfrontenduser'];

    //render select
    $fegroups = new cApiFrontendGroupCollection();
    $fegroups->setWhere("idclient", $client);
    $fegroups->query();

    $aFEGroups = array();

    while ($fegroup = $fegroups->next())
    {
        $aFEGroups[$fegroup->get("idfrontendgroup")] = $fegroup->get("groupname");
    }

    $oSelect = new cHTMLSelectElement("groupselect[]");
    $oSelect->autoFill($aFEGroups);
    $oSelect->setMultiselect();
    $oSelect->setSize(5);
    $oSelect->setStyle('width:265px;');

    //mark groups
    $oFEGroupMemberCollection = new cApiFrontendGroupMemberCollection;
    $oFEGroupMemberCollection->setWhere('idfrontenduser', $iIdfrontenduser);
    $oFEGroupMemberCollection->addResultField('idfrontendgroup');
    $oFEGroupMemberCollection->query();

    $aFEGroup = array();
    while ($oFEGroup = $oFEGroupMemberCollection->next())
    {
        $aFEGroup[] = $oFEGroup->get("idfrontendgroup");
    }

    $oSelect->setDefault($aFEGroup);

    return $oSelect->render();
}

function frontendusers_groupselect_wantedVariables () {
    return (array("groupselect"));
}

function frontendusers_groupselect_store ($variables) {
    global $client;

    $groups = $_REQUEST['groupselect'];
    $iIdfrontenduser = (int)$_REQUEST['idfrontenduser'];
    if (!is_array($groups)) {
        $groups = array();
    }

    $groupmembers    = new cApiFrontendGroupMemberCollection();

    $fegroups = new cApiFrontendGroupCollection();
    $fegroups->setWhere("idclient", $client);
    $fegroups->query();

    $aFEGroups = array();

    while ($fegroup = $fegroups->next())
    {
        $groupmembers->remove($fegroup->get("idfrontendgroup"), $iIdfrontenduser);
        if (in_array($fegroup->get("idfrontendgroup"), $groups)) {
            $groupmembers->create($fegroup->get("idfrontendgroup"), $iIdfrontenduser);
        }
    }

    return true;
}

function frontendusers_groupselect_canonicalVariables () {
    //FFBCON-812
    return array();
}

function frontendusers_groupselect_getvalue ($key) {
    return '';
}
?>