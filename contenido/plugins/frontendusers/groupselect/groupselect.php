<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * File for handling first name of frontend user
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.0
 * @author     Timo Trautmann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2009-08-14
 *
 *   $Id$:
 * }}
 * 
 */

global $db;

function frontendusers_groupselect_getTitle () {
	return i18n("Gruppen bearbeiten", "frontendusers_groupselect");
}

function frontendusers_groupselect_display () {
	global $client;
	$iIdfrontenduser = (int)$_REQUEST['idfrontenduser'];	
	
	//render select
	$fegroups = new FrontendGroupCollection;
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
	$oFEGroupMemberCollection = new FrontendGroupMemberCollection;
	$oFEGroupMemberCollection->setWhere('idfrontenduser', $iIdfrontenduser);
	$oFEGroupMemberCollection->addResultField('idfrontendgroup');
	$oFEGroupMemberCollection->query();

	$aFEGroup = array();
	while($oFEGroup = $oFEGroupMemberCollection->next())
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
	
	$groupmembers	= new FrontendGroupMemberCollection();
	
	$fegroups = new FrontendGroupCollection;
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
	return array('groupselect' => 'Gruppen bearbeiten');
}

function frontendusers_groupselect_getvalue ($key) {
	return '';
}
?>