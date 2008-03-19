<?php

cInclude("classes", "class.ui.php");
cInclude("plugins", "contacts/classes/class.contacttypes.php");
cInclude("plugins", "contacts/classes/class.contactproperties.php");
cInclude("plugins", "contacts/classes/class.contactdata.php");
cInclude("plugins", "contacts/classes/class.contactactions.php");
cInclude("classes", "class.client.php");
cInclude("classes", "class.lang.php");

global $aContactPluginProperties, $belang;

$cfg['tab']['contact_types'] = $cfg['sql']['sqlprefix'].'_pi_contact_types';
$cfg['tab']['contact_properties'] = $cfg['sql']['sqlprefix'].'_pi_contact_properties';
$cfg['tab']['contact_data'] = $cfg['sql']['sqlprefix'].'_pi_contact_data';

$belang_temp = $belang;

$belang = "en_US";

$db = new DB_Contenido();
$oClient = new Client();
$oLang = new Language();


$aContactPluginProperties = array();
$aContactPluginProperties['actionprefix'] = "contact_plugin_";
$aContactPluginProperties['idactionstart'] = 100100; 
$aContactPluginProperties['idactionend'] = 101000; 
$aContactPluginProperties['view_contacts_idarea'] = 100000; 

$oContactTypes = new cContactTypes($db);
$oContactActions = new cContactActions($db, $cfg);
$aContactPluginAction = $oContactActions->getAvalibleActions();

if(count($aContactPluginAction) > 0) {
	foreach($aContactPluginAction as $sActionName) {
		$sTemp = substr($sActionName, strlen($aContactPluginProperties['actionprefix']), strlen($sActionName) - strlen($aContactPluginProperties['actionprefix']));
		$aSplittedAction = explode("-", $sTemp);
		$sContactLabel = $oContactTypes->getContactLabelByType($aSplittedAction[0], $aSplittedAction[1], $aSplittedAction[2]);
		$sClientName = $oClient->getClientname($aSplittedAction[1]);
		$oLang->loadByPrimaryKey($aSplittedAction[2]);
		$sLangName = $oLang->getField("name");
		$lngAct["view_contacts"][$sActionName] = i18n("View", "contacts") . " \"{$sContactLabel}\" " . i18n("by", "contacts") . " $sClientName ($sLangName)"; 
	}
}
//echo $belang."dfsg";

$lngAct["view_contacts"]["contact_data_view"] = i18n("View contact data", "contacts");
$lngAct["view_contacts"]["contact_data_delete"] = i18n("Delete contact data", "contacts");
$lngAct["view_contacts"]["contact_data_export"] = i18n("Export contact data", "contacts");
$lngAct["edit_contact_types"]["contact_type_create"] = i18n("Create new contact forms", "contacts");
$lngAct["edit_contact_types"]["contact_type_store"] = i18n("Store new contact forms", "contacts");
$lngAct["edit_contact_types"]["contact_type_delete"] = i18n("Delete contact forms", "contacts");
$lngAct["edit_contact_types"]["contact_properties_overview"] = i18n("View contact fields", "contacts");
$lngAct["edit_contact_types"]["contact_property_set_order"] = i18n("Change order of the contact fields", "contacts");
$lngAct["edit_contact_types"]["contact_property_add"] = i18n("Add a contact field", "contacts");
$lngAct["edit_contact_types"]["contact_property_delete"] = i18n("Delete a contact field", "contacts");
$lngAct["edit_contact_types"]["contact_type_details"] = i18n("View details of a contact type", "contacts");

$belang = $belang_temp;

?>
