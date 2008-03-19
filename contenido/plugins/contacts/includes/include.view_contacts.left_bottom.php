<?php
/***********************************************
* Plugin Contacts Management
*
* File			:	include.edit_contact_forms.left_bottom.php
* Version		:	1.0	
*
* Author		:	Maxim Spivakovsky
* Copyright		:	four for business AG
* Created		:	06-03-2006
* Modified		:	06-03-2006
************************************************/

$oUiPage = new UI_Page;
$oContactActions = new cContactActions($db, $cfg);
$aContactPluginAction = $oContactActions->getAvalibleActions();

if($perm->have_perm_area_action($area, $_REQUEST['action'])) {
	$oContactTypes = new cContactTypes($db);
	$oContactProperties = new cContactProperties($db);
	
	$oContactTypes->addGetByProperty("idclient", $client);
	$oContactTypes->addGetByProperty("idlang", $lang);
	
	$aContactTypes = $oContactTypes->getContactTypes();
	
	$sUiMenu = '';
	
	if(count($aContactTypes)) {
		$oUiMenu = new UI_Menu();
		$i = 0;
		foreach($aContactTypes as $iIdContactType => $aContactTypeData) {
			if($perm->have_perm_area_action($area, $aContactPluginProperties['actionprefix'] . $aContactTypeData['type'] . "-$client-$lang")) {
				$oLink = new Link;
				$oLink->setCLink($area, 4, $aContactPluginProperties['actionprefix'] . $aContactTypeData['type'] . "-$client-$lang");
				//$oLink->setCustom('action2', 'contact_data_view');
				$oLink->setCustom('idcontacttype', $iIdContactType);
				$oLink->setAlt(i18n('View', "contacts"));
				
				$oUiMenu->setTitle($i, $aContactTypeData['label']);
				$oUiMenu->setImage($i, $cfg["path"]["images"] . 'article.gif');
				$oUiMenu->setLink($i, $oLink);
				$i++;
			}
		}
		
		$sUiMenu = $oUiMenu->render(false);	
	}
	
	$oUiPage->setMargin(0);
	$oUiPage->setContent($sUiMenu);
}

$oUiPage->render();


?>