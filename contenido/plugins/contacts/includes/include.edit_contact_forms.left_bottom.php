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

if($perm->have_perm_area_action($area, $_REQUEST['action'])) {
	$oContactTypes = new cContactTypes($db);
	$oContactProperties = new cContactProperties($db);
	$oContactData = new cContactData($db);
	$oContactActions = new cContactActions($db, $cfg);
	$oContactActions->iIdActionStart = $aContactPluginProperties['idactionstart'];
	$oContactActions->iIdActionEnd =  $aContactPluginProperties['idactionend'];
	$oContactActions->sActionPrefix = $aContactPluginProperties['actionprefix'];
	
	switch ($_REQUEST['action']) {
		case 'contact_type_delete':
			$oContactProperties->resetGetByProperties();
			$oContactProperties->addGetByProperty("idcontacttype", $_REQUEST['idcontacttype']);
			$aContactProperties = $oContactProperties->getContactProperties();
			if(count($aContactProperties) > 0) {
				foreach($aContactProperties as $iIdContactProperty => $aContactPropertyData) {
					$oContactData->deleteContactDataByProperty($iIdContactProperty);
					$oContactProperties->deleteContactProperty($iIdContactProperty);
				}							
			}
			
			$sContactType = $oContactTypes->getContactTypeById($_REQUEST['idcontacttype']);
			$oContactActions->deleteActionByName($aContactPluginProperties['actionprefix'] . $sContactType . "-$client-$lang");
			$oContactTypes->deleteContactType($_REQUEST['idcontacttype']);
			
			break;
			
		case 'contact_type_sync':
			$sContactType = $oContactTypes->getContactTypeById($_REQUEST['idcontacttype']);

			if(!$oContactTypes->getIdContactType($client, $lang, $sContactType) && $sContactType) {
				$oContactTypes->resetGetByProperties();
				$oContactTypes->addGetByProperty("idcontacttype", $_REQUEST['idcontacttype']);
				$aSynchContact = $oContactTypes->getContactTypes();
				
				$iIdContactTypeSync = $oContactTypes->storeContactType($aSynchContact[$_REQUEST['idcontacttype']]['label'], $client, $lang, $auth->auth['uid']);
				$oContactActions->storeAction($aContactPluginProperties['view_contacts_idarea'], $aContactPluginProperties['actionprefix'] . $sContactType . "-$client-$lang");
				
				$oContactProperties->resetGetByProperties();
				$oContactProperties->addGetByProperty("idcontacttype", $_REQUEST['idcontacttype']);
				$aContactPropertiesToCopy = $oContactProperties->getContactProperties();
				
				if(count($aContactPropertiesToCopy) > 0) {
					foreach($aContactPropertiesToCopy as $iIdContactProperty => $aContactPropertyValues) {
						$oContactProperties->storeContactProperty($aContactPropertyValues['label'], $iIdContactTypeSync, $auth->auth['uid']);
					}
				}
				
					
			}
			
			break;
	}
	
	$oContactTypes->resetGetByProperties();
	/*$oContactTypes->addGetByProperty("idclient", $client);
	$oContactTypes->addGetByProperty("idlang", $lang);*/
	
	$aContactTypes = $oContactTypes->getContactTypes();
	
	$delTitle = i18n("Delete a contact type", "contacts");
	$delDescr = i18n("Would you like really delete this contact type", "contacts") . ":<br><b>%s</b>";
	
	$sUiMenu = '';
	
	if(count($aContactTypes)) {
		$oUiMenu = new UI_Menu();
		$i = 0;
		foreach($aContactTypes as $iIdContactType => $aContactTypeData) {
			$oLink = new Link;
			
			$sButtons = '';
			
			$oContactTypes->resetGetByProperties();
			$oContactTypes->addGetByProperty("idclient", $client);
			$oContactTypes->addGetByProperty("idlang", $lang);
			$oContactTypes->addGetByProperty("type", $aContactTypeData["type"]);
			$aFoundedContacts = $oContactTypes->getContactTypes();
			
			if($aContactTypeData['idclient'] == $client && $aContactTypeData['idlang'] == $lang) {
				$oLink->setMultiLink($area, '', $area, 'contact_type_details');
				$oLink->setCustom('idcontacttype', $iIdContactType);
				$oLink->setAlt('Details');
				if($perm->have_perm_area_action($area, 'contact_type_delete')) {
					$sButtons .= '&nbsp;<a title="'.$delTitle.'" href="javascript://" onclick="box.confirm(\''.$delTitle.'\', \''.sprintf($delDescr, $aContactTypeData['label']).'\', \'deleteContactType(\\\''.$iIdContactType.'\\\')\')"><img src="'.$cfg['path']['images'].'delete.gif" border="0" title="'.$delTitle.'" alt="'.$delTitle.'"></a>';	
				}
			}
			else {
				if($perm->have_perm_area_action($area, 'contact_type_sync') && 
					count($aFoundedContacts)==0) {
					$oDupLink = new Link;
					$oDupLink->setCLink($area, 2, 'contact_type_sync');
					$oDupLink->setCustom('idcontacttype', $iIdContactType);
					$oDupLink->setAlt(i18n('Copy form to the current client/language'));
					$oDupLink->setContent('<img src="'.$cfg['path']['images'].'pfeil_links.gif" border="0" title="'.i18n("Copy form to the current client/language").'" alt="Copy form to the current client/language">');
						
					$sButtons .= $oDupLink->render();	
				}
				
			}
			
			if($sButtons) {
				$oUiMenu->setTitle($i, $aContactTypeData['label']);
				$oUiMenu->setImage($i, $cfg["path"]["images"] . 'article.gif');
				$oUiMenu->setLink($i, $oLink);
				$oUiMenu->setActions($i, 'contact_type_delete', $sButtons);
				$i++;
			}
		}
		
		$sUiMenu = $oUiMenu->render(false);	
	}
	
	$delScript = '
	    <script type="text/javascript">
	        /* Session-ID */
	        var sid = "'.$sess->id.'";
	
	        /* Create messageBox
	           instance */
	        box = new messageBox("", "", "", 0, 0);
	
	        /* Function for deleting
	           modules */
	
	        function deleteContactType(idcontacttype) {
	            url  = "main.php?area='.$area.'";
	            url += "&action=contact_type_delete";
	            url += "&frame=2";
	            url += "&idcontacttype=" + idcontacttype;
	            url += "&contenido=" + sid;
	            parent.parent.left.left_bottom.location.href = url;
	
	        }
			</script>';
	
	$msgboxInclude = '    <script type="text/javascript" src="scripts/messageBox.js.php?contenido='.$sess->id.'"></script>';
	
	$oUiPage->addScript('include', $msgboxInclude);
	$oUiPage->addScript('del',$delScript);
	$oUiPage->setMargin(0);
	$oUiPage->setContent($sUiMenu);
}

$oUiPage->render();


?>