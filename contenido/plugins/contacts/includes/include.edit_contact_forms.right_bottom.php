<?php
/***********************************************
* Plugin Contacts Management
*
* File			:	include.edit_contact_forms.right_bottom.php
* Version		:	1.0	
*
* Author		:	Maxim Spivakovsky
* Copyright		:	four for business AG
* Created		:	06-03-2006
* Modified		:	06-03-2006
************************************************/

$sHtmlOutput = '';
$bShowForm = false;

$oUiPage = new UI_Page;
$oContactTypes = new cContactTypes($db);     
$oContactData = new cContactData($db);
$oContactActions = new cContactActions($db, $cfg);
$oContactActions->iIdActionStart = $aContactPluginProperties['idactionstart'];
$oContactActions->iIdActionEnd =  $aContactPluginProperties['idactionend'];
$oContactActions->sActionPrefix = $aContactPluginProperties['actionprefix'];


$aContactTypeActions = 
	array("contact_type_create", "contact_type_store", "contact_type_details");

$aContactPropertyActions = 
	array("contact_properties_overview", "contact_property_add", "contact_property_delete", "contact_property_set_order");


/*contact type part*/
if(	in_array($_REQUEST['action'], $aContactTypeActions) && 
	$perm->have_perm_area_action($area, $_REQUEST['action'])) {

	$bValidIdContactType = $oContactTypes->existsContactType($_REQUEST['idcontacttype']); 

	switch($_REQUEST['action']) {
		case 'contact_type_create':
			$bShowForm = true;
			break;
			
		case 'contact_type_store':
			$sContactTypeLabel = substr(strip_tags($_REQUEST['contact_type_label']), 0, 30);
			
			$oContactTypes->resetGetByProperties();
			$oContactTypes->addGetByProperty("idclient", $client);
			$oContactTypes->addGetByProperty("idlang", $lang);
			$oContactTypes->addGetByProperty("type", $oContactTypes->makeContactType($sContactTypeLabel));
			
			$aContactTypes = $oContactTypes->getContactTypes();
			
			if(count($aContactTypes) > 0) {
				$sHtmlOutput = $notification->returnNotification("error", i18n("This label already exists!", "contacts"));
				$bShowForm = true;
			}
			else {
				$iIdContactType = $oContactTypes->storeContactType($sContactTypeLabel, $client, $lang, $auth->auth['uid']);
				$sContactType = $oContactTypes->getContactTypeById($iIdContactType);
				$oContactActions->storeAction($aContactPluginProperties['view_contacts_idarea'], $aContactPluginProperties['actionprefix'] . $sContactType . "-$client-$lang");
				$sHtmlOutput = $notification->returnNotification("info", '"' . $sContactTypeLabel . "' " . i18n("was created!", "contacts"));
			}
			
			break;
		
		case 'contact_type_details':
			if(!$bValidIdContactType) {
				break;
			}
			$oContactTypes->resetGetByProperties();
			$oContactTypes->addGetByProperty("idclient", $client);
			$oContactTypes->addGetByProperty("idlang", $lang);
			$oContactTypes->addGetByProperty("idcontacttype", $_REQUEST['idcontacttype']);
	
			$aContactTypes = $oContactTypes->getContactTypes();
			
			if(count($aContactTypes) > 0) {
				$oUiList= new UI_List();
				$oUiList->setWidth('200px');
				$oUiList->setBorder("1");
				$oUiList->setPadding("4");
				
				$oUser = new User();
				
				foreach($aContactTypes as $iIdContactType => $aContactTypeData) {
					$oUiList->setCell(0, 0, "<strong>" . $aContactTypeData["label"] . "</strong>");
					$oUiList->setCell(0, 1, "&nbsp;");
					$oUiList->setCell(1, 0, "&nbsp;");
					$oUiList->setCell(1, 1, "&nbsp;");

					$oUiList->setCell(2, 0, i18n("Type:", "contacts"));
					$oUiList->setCell(2, 1, $aContactTypeData["type"]);

					
					$oUiList->setCell(3, 0, i18n("Created:", "contacts"));
					$oUiList->setCell(3, 1, $aContactTypeData["created"]);
					
					$oUser->loadUserByUserID($aContactTypeData["createdby"]);
					$oUiList->setCell(4, 0, i18n("Created by:", "contacts"));
					$oUiList->setCell(4, 1, $oUser->getField("username"));
					
					$oUiList->setCell(5, 0, i18n("Modified:", "contacts"));
					$oUiList->setCell(5, 1, $aContactTypeData["modified"]);
	
					$oUser->loadUserByUserID($aContactTypeData["modifiedby"]);
					$oUiList->setCell(6, 0, i18n("Modified by:", "contacts"));
					$oUiList->setCell(6, 1, $oUser->getField("username"));
				}
				
				$sHtmlOutput .= '<br>' . $oUiList->render();
			}
			
			break;
	}
	
	if($bShowForm) {
		$oUiTableForm = new UI_Table_Form('form_contact_type_create');
		$oUiTableForm->addHeader(i18n("Add contact type", "contacts"));
		
		$oLabel = new cHTMLTextbox('contact_type_label', $_REQUEST['contact_type_label']);
		$oLabel->setMaxLength("30");
	
		$oUiTableForm->setVar('action', 'contact_type_store');
		$oUiTableForm->setVar('frame', '4');
		$oUiTableForm->setVar('area', $area);	
		
		$oUiTableForm->setWidth('200px');
		$oUiTableForm->add(i18n('Label ', "contacts"), $oLabel->toHtml());
		
		$sHtmlOutput .= '<br>' . $oUiTableForm->render();
		
	}
}
/*contact property part*/
elseif(in_array($_REQUEST['action'], $aContactPropertyActions) && 
		$perm->have_perm_area_action($area, $_REQUEST['action'])) {
	$oContactProperties = new cContactProperties($db);
	
	$bValidIdContactType = $oContactTypes->existsContactType($_REQUEST['idcontacttype']); 
	
	switch($_REQUEST['action']) {
		case 'contact_property_add':
			if(!$bValidIdContactType) {
				break;
			}
			
			$sContactPropertyLabel = substr(strip_tags($_REQUEST['contact_property_label']), 0, 30);
			
			$oContactProperties->resetGetByProperties();
			$oContactProperties->addGetByProperty("idcontacttype", $_REQUEST['idcontacttype']);
			$oContactProperties->addGetByProperty("type", $oContactProperties->makeContactPropertyType($sContactPropertyLabel));
			
			$aContactProperties = $oContactProperties->getContactProperties();
			
			if(count($aContactProperties) > 0) {
				$sHtmlOutput = $notification->returnNotification("error", i18n("This label already exists!", "contacts"));
			}
			else {
				$oContactProperties->storeContactProperty($sContactPropertyLabel, $_REQUEST['idcontacttype'], $auth->auth['uid']);
			}
			break;
			
		case 'contact_property_set_order':
			if(!$bValidIdContactType) {
				break;
			}
			
			if(count($_REQUEST['ordernum']) > 0) {
				foreach($_REQUEST['ordernum'] as $ordernum => $iIdContactProperty) {	
					$oContactProperties->updateAttr($iIdContactProperty, "ordernum", $ordernum);
				}
			}
			break;
			
		case 'contact_property_delete':
			if(!$bValidIdContactType) {
				break;
			}
			$oContactProperties->resetGetByProperties();
			$oContactProperties->addGetByProperty("idcontactproperty", $_REQUEST['idcontactproperty']);
			$aContactProperties = $oContactProperties->getContactProperties();
			if(count($aContactProperties) > 0) {
				foreach($aContactProperties as $iIdContactProperty => $aContactPropertyData) {
					$oContactProperties->rewriteFailedOrder($_REQUEST['idcontacttype'], $aContactPropertyData['ordernum']);
				}							
			}
			$oContactData->deleteContactDataByProperty($_REQUEST['idcontactproperty']);
			$oContactProperties->deleteContactProperty($_REQUEST['idcontactproperty']);		
			break;
	}

	if($bValidIdContactType) {
		$oContactProperties->resetGetByProperties();
		$oContactProperties->addGetByProperty("idcontacttype", $_REQUEST['idcontacttype']);
		$aContactProperties = $oContactProperties->getContactProperties();
		
		$iPropertiesCount = count($aContactProperties);
		
		if($iPropertiesCount > 0) {
			$oUiTableForm = new UI_Table_Form('form_contact_properties_order');
			$oUiTableForm->addHeader(i18n("Order of fields", "contacts"));
		
			$oUiTableForm->setVar('action', 'contact_property_set_order');
			$oUiTableForm->setVar('frame', '4');
			$oUiTableForm->setVar('area', $area);	
			$oUiTableForm->setVar('idcontacttype', $_REQUEST['idcontacttype']);	
			//$oUiTableForm->setWidth('200px');
			
			$i = 0;
			$delTitle = i18n("Delete a contact field", "contacts");
			$delDescr = i18n("Would you like really delete this contact field", "contacts") . ":<br><b>%s</b>";
			
			foreach($aContactProperties as $iIdContactProperty => $aContactPropertyData) {
				$sRadios = "";
				for($j=0; $j < $iPropertiesCount; $j++) {
					$oHTMLRadiobuttonc = new cHTMLRadiobutton("ordernum[".($j+1)."]", $iIdContactProperty, "", (($j+1)==$aContactPropertyData['ordernum']?1:0));
					$oHTMLRadiobuttonc->setEvent("onchange", "fixorder(this, ".($j+1).")");
					$sRadios .= $oHTMLRadiobuttonc->toHtml(false);
				}
				
				$sDeleteButton = '';
				if($perm->have_perm_area_action($area, 'contact_property_delete')) {
					$sDeleteButton = '&nbsp;&nbsp;&nbsp;<a title="'.$delTitle.'" href="javascript://" onclick="box.confirm(\''.$delTitle.'\', \''.sprintf($delDescr, $aContactPropertyData['label']).'\', \'deleteContactProperty(\\\''.$iIdContactProperty.'\\\')\')"><img src="'.$cfg['path']['images'].'delete.gif" border="0" title="'.$delTitle.'" alt="'.$delTitle.'"></a>';	
				}
				
				$oUiTableForm->add($aContactPropertyData['label'] . " (<i>type: {$aContactPropertyData['type']}</i>)", $sRadios . $sDeleteButton);
				$i++;
			}	
			$sHtmlOutput .= '<br>' . $oUiTableForm->render();
		}
		
		$oUiTableForm = new UI_Table_Form('form_contact_type_create');
		$oUiTableForm->addHeader(i18n("Add contact property", "contacts"));
		
		$oLabel = new cHTMLTextbox('contact_property_label', $_REQUEST['contact_property_label']);
		$oLabel->setMaxLength("30");
		
	
		$oUiTableForm->setVar('action', 'contact_property_add');
		$oUiTableForm->setVar('frame', '4');
		$oUiTableForm->setVar('area', $area);	
		$oUiTableForm->setVar('idcontacttype', $_REQUEST['idcontacttype']);	
		
		$oUiTableForm->setWidth('200px');
		$oUiTableForm->add(i18n('Label', "contacts") . " ", $oLabel->toHtml());
		
		$sHtmlOutput .= '<br>' . $oUiTableForm->render();
	}
	
}

$sFixOrderJs =  '
    <script language="javascript" type="text/javascript">
		function fixorder(currradio, order) {
			elcount = 0;			
			
			for(i=0; i<document.forms[0].elements.length; i++) {
				oRadioNode = document.forms[0].elements["ordernum["+(i+1)+"]"];
				if(oRadioNode) {
					elcount++;
					for(j=0; j<oRadioNode.length; j++) {
						oRadioObject = oRadioNode[j];
						if(order!=(i+1) && currradio.value==oRadioObject.value) {
							oRadioObject.checked = false;
						}
					}
				}
				else {
					break;
				}
			}
		}
	</script>';

$delScript = '
    <script type="text/javascript">
        /* Session-ID */
        var sid = "'.$sess->id.'";

        /* Create messageBox
           instance */
        box = new messageBox("", "", "", 0, 0);

        /* Function for deleting
           modules */

        function deleteContactProperty(idcontactproperty) {
            url  = "main.php?area='.$area.'";
            url += "&action=contact_property_delete";
            url += "&frame=4";
            url += "&idcontacttype='.$_REQUEST['idcontacttype'].'";
            url += "&idcontactproperty=" + idcontactproperty;
            url += "&contenido=" + sid;
            parent.parent.right.right_bottom.location.href = url;

        }
		</script>';

$msgboxInclude = '<script type="text/javascript" src="scripts/messageBox.js.php?contenido='.$sess->id.'"></script>';


$oUiPage->addScript("fixorder", $sFixOrderJs);
$oUiPage->addScript('include', $msgboxInclude);
$oUiPage->addScript('del',$delScript);
$oUiPage->setContent($sHtmlOutput);
$oUiPage->render();

?>