<?php
/***********************************************
* Plugin Contacts Management
*
* File			:	include.view_contacts.right_bottom.php
* Version		:	1.0	
*
* Author		:	Maxim Spivakovsky
* Copyright		:	four for business AG
* Created		:	06-03-2006
* Modified		:	06-03-2006
************************************************/

$aContactDataActions = 
	array("contact_data_view", "contact_data_delete", "contact_data_export");

$oContactActions = new cContactActions($db, $cfg);
$aContactPluginAction = $oContactActions->getAvalibleActions();

$aContactDataActions = array_merge($aContactDataActions, $aContactPluginAction);

if(	in_array($_REQUEST['action'], $aContactDataActions) && 
	$perm->have_perm_area_action($area, $_REQUEST['action'])) {

	$sHtmlOutput = '';
	$oUiPage = new UI_Page;
	$oContactTypes = new cContactTypes($db);     
	$oContactProperties = new cContactProperties($db);     
	$oContactData = new cContactData($db);     
	$oScrollList = new cScrollList();
	
	$sHtmlOutput = "";
	$aExtraLinks = array();
	$aExtraLinks['sortby'] = $_REQUEST["sortby"] ? $_REQUEST["sortby"] : 1;  		
	$aExtraLinks['sortmode'] = $_REQUEST["sortmode"] ? $_REQUEST["sortmode"] : "DESC";
	$aExtraLinks['idcontacttype'] = $_REQUEST['idcontacttype'];  		
	$aExtraLinks['liststart'] = $_REQUEST['liststart'] ? $_REQUEST['liststart'] : 1;  		
	$aExtraLinks['perpage'] = $_REQUEST['perpage'] ? $_REQUEST['perpage'] : 10;  		

	if($_REQUEST['action'] == "contact_data_delete") {
		if(count($_REQUEST["contact"]) > 0) {
			foreach($_REQUEST["contact"] as $iIdContactData) {
				$oContactData->deleteContactDataGroup($iIdContactData);
			}
		}
	} 
	
	/*create contact data table*/
	$oScrollList->header[0] = '<input type="checkbox" name="c_all" value="false" onClick="this.value=check()">';
	
	$oContactProperties->resetGetByProperties();
	$oContactProperties->addGetByProperty("idcontacttype", $_REQUEST['idcontacttype']);
	$aContactProperties = $oContactProperties->getContactProperties(array("ordernum"));

	$aFillOrder = array();

	$i = 1;
	foreach($aContactProperties as $iIdContactProperty => $aContactPropertyData) {
		$oScrollList->header[$i] = $aContactPropertyData['label'];
		$oScrollList->setSortable($i, true);
		$aFillOrder[] = $iIdContactProperty;
		$i++;
	}
	$oScrollList->setCustom("idcontacttype", $aExtraLinks['idcontacttype']);
	$oScrollList->setCustom("liststart", $aExtraLinks['liststart']);
	$oScrollList->setCustom("perpage", $aExtraLinks['perpage']);
	$oScrollList->sortlink->_targetaction = 'contact_data_view';
	$oScrollList->setResultsPerPage($aExtraLinks['perpage']);
	$oScrollList->setListStart($aExtraLinks['liststart']);

	$aContactData = $oContactData->getContactData($_REQUEST["idcontacttype"]);
	
	$i = 0;
	foreach($aContactData as $iIdContactDataGroup => $aContactDatas) {
		$j = 1;
		$sCheckBox = '<input type="checkbox" name="contact[]" value="'.$iIdContactDataGroup.'">';
		$oScrollList->data[$i][$j] = $sCheckBox;
		foreach($aFillOrder as $iIdContactProperty) {
			$j++;
			$oScrollList->data[$i][$j] = $aContactDatas[$iIdContactProperty];
		}
		$i++;
	}
	
	$oScrollList->sort($aExtraLinks['sortby'], $aExtraLinks['sortmode']);
		
 	$oScrollList->objTable->updateAttributes(array('width' => '100%'));	
	
	if($_REQUEST['action'] != "contact_data_export") {
		/*create "select page" and "per page" inputs*/
		$oUiTableForm = new UI_Table_Form('form_contact_data_paging');
		$oUiTableForm->addHeader(i18n('Navigation'));
		
		/*select box: current page*/
		$oHTMLSelectElement = new cHTMLSelectElement("liststart");
		$aSelectBoxAutoFill = array();   
		$aSelectBoxAutoFill[] = array("", "Go to");
		$iPagesCount = $oScrollList->getNumPages();
		for($i=0; $i < $iPagesCount; $i++) {
			$aSelectBoxAutoFill[] = array($i+1, "Page " . ($i+1));
		}
		
		$oHTMLSelectElement->autoFill($aSelectBoxAutoFill);
		$oHTMLSelectElement->setDefault($aExtraLinks['liststart']);
		$oHTMLSelectElement->setEvent("onchange", "this.form.submit();");
		
		$oPerPage = new cHTMLTextbox('perpage', $aExtraLinks['perpage']);
		$oPerPage->setMaxLength("5");
		$oPerPage->setWidth("5");
		//$oPerPage->setValue($)
		
		$oUiTableForm->setVar('action', 'contact_data_view');
		$oUiTableForm->setVar('frame', '4');
		$oUiTableForm->setVar('area', $area);	
		$oUiTableForm->setVar('idcontacttype', $_REQUEST['idcontacttype']);	
		$oUiTableForm->setVar("sortby", $aExtraLinks['sortby']);
		$oUiTableForm->setVar("sortmode", $aExtraLinks['sortmode']);
		
		//$oUiTableForm->setWidth('200px');
		$oUiTableForm->add("&nbsp;" . $oHTMLSelectElement->toHtml() . "&nbsp;", "&nbsp;Per Page " . $oPerPage->toHtml());
		
		$sHtmlOutput .= '<br>' . $oUiTableForm->render();
		
		$sDelButton = '
			<br>' . i18n("Delete selected contacts", "contacts") . '<a title="'.i18n("Delete selected contacts", "contacts").'" href="javascript://" onclick="box.confirm(\''.i18n('Delete selected contacts', "contacts").'\', \''.
							i18n('Would you like really delete selected contacts?', "contacts").'\', \'deleteContactData()\')">'.
							'<img src="images/delete.gif" '.
							'border="0" title="'.i18n("Delete contacts", "contacts").'" alt="'.i18n("Delete contacts", "contacts").'"></a>';
		
		$oPageForm = new UI_Form("form_contact_delete_data");
		$oPageForm->setVar('action', 'contact_data_delete');
		$oPageForm->setVar('frame', '4');
		$oPageForm->setVar('area', $area);	
		$oPageForm->setVar('idcontacttype', $_REQUEST['idcontacttype']);	
		$oPageForm->setVar("sortby", $aExtraLinks['sortby']);
		$oPageForm->setVar("sortmode", $aExtraLinks['sortmode']);
		$oPageForm->setVar("liststart", $aExtraLinks['liststart']);
		$oPageForm->setVar("perpage", $aExtraLinks['perpage']);
	
		$oExportLink = new Link;
		$oExportLink->setCLink($area, 4, 'contact_data_export');
		$oExportLink->setCustom('idcontacttype', $_REQUEST['idcontacttype']);
		$oExportLink->setCustom('sortby', $aExtraLinks['sortby']);
		$oExportLink->setCustom('sortmode', $aExtraLinks['sortmode']);
		$oExportLink->setAlt('Export contact data to Excel');
		$oExportLink->setContent('Export data to Excel');
	
		
		$oPageForm->add("scrolllist", $oScrollList->render());
		$oPageForm->add("deletebutton", $sDelButton);
		$oPageForm->add("exportlink", "<br><br>" . $oExportLink->render());
		
		$sHtmlOutput .= "<br>" . $oPageForm->render();
		
		$sCheckBusttonJS =
			'<script type="text/javascript" language="JavaScript">
				var checkflag = false;
				function check() {
					oCheckBoxColl = document.getElementsByName("contact[]");
					if (!checkflag) {
						for (i = 0; i < oCheckBoxColl.length; i++) {
							//alert(oCheckBoxColl[i]);
							oCheckBoxColl[i].checked = true;
						}
						checkflag = true;
					}
					else {
						for (i = 0; i < oCheckBoxColl.length; i++) {
							
							oCheckBoxColl[i].checked = false; 
						}
						checkflag = false;
					}
				}
			</script>';
			
		
		$sDelScript = '
		    <script type="text/javascript">
		        /* Session-ID */
		        var sid = "'.$sess->id.'";
		
		        /* Create messageBox
		           instance */
		        box = new messageBox("", "", "", 0, 0);
		
		        /* Function for deleting
		           modules */
		
		        function deleteContactData() {
					document.form_contact_delete_data.submit();
		        }
				</script>';
		
		$msgboxInclude = '<script type="text/javascript" src="scripts/messageBox.js.php?contenido='.$sess->id.'"></script>';
		
		$oUiPage->addScript('include', $msgboxInclude);
		$oUiPage->addScript('delscript',$sDelScript);
		$oUiPage->addScript('check_all',$sCheckBusttonJS);
		
		
		$oUiPage->setMargin(10);
		$oUiPage->setContent($sHtmlOutput);
		$oUiPage->render();
	}
	else {
		cInclude("plugins",'contacts/pear/Spreadsheet/Writer.php');
		
		$sExportFileName = $oContactTypes->getContactTypeById($_REQUEST['idcontacttype']);
		$sExportFileName = $sExportFileName ? $sExportFileName : "noname";
		
		$sExportFileName .= date("_d.m.Y"); 
		
		$workbook = new Spreadsheet_Excel_Writer();
		$workbook->send("$sExportFileName.xls");
		
		$workbook->setCustomColor(11, 255, 255, 255); //text color (title)
		$workbook->setCustomColor(12, 169, 174, 194); //bgcolor title
		$workbook->setCustomColor(13, 000, 000, 000); //text color(content)
		$workbook->setCustomColor(14, 232, 232, 238); //bgcolor content
		$workbook->setCustomColor(15, 244, 244, 247); //bgcolor2 content
		
		$format_title =& $workbook->addFormat();
		$format_title->setBold();
		$format_title->setColor(11);
		$format_title->setPattern(1);
		$format_title->setFgColor(12);
		
		$format_str1 =& $workbook->addFormat();
		$format_str1->setColor(13);
		$format_str1->setPattern(1);
		$format_str1->setFgColor(14);
		
		$format_str2 =& $workbook->addFormat();
		$format_str2->setColor(13);
		$format_str2->setPattern(1);
		$format_str2->setFgColor(15);
		
		$worksheet =& $workbook->addWorksheet("test");
		$worksheet->setColumn(0, 0, 18);
		
		for($i=1; $i<count($oScrollList->header); $i++) {
			$worksheet->write(0, $i-1, $oScrollList->header[$i], $format_title);
		}

		for($i=0; $i<count($oScrollList->data); $i++) {
			for($j=2; $j<(count($oScrollList->data[$i])+1); $j++) {
				$worksheet->write($i+1, $j-2, $oScrollList->data[$i][$j], $i%2==0 ? $format_str1 : $format_str2);
			}
		}
		
		$workbook->close();
	}
}

?>