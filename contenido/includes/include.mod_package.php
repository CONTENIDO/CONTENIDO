<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Module package specification, import, export
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.2.0
 * @author     HerrB
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2008-06-27, OliverL fix import module translation bug, checkin timo.trautmann (http://forum.contenido.org/viewtopic.php?t=19064)
 *
 *   $Id: include.mod_package.php 691 2008-08-15 13:01:59Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes",  "contenido/class.module.php");
cInclude("classes",  "contenido/class.clientslang.php");
cInclude("classes",  "contenido/class.lang.php");
cInclude("classes",  "contenido/class.layout.php");
cInclude("classes",  "class.ui.php");
cInclude("classes",  "class.htmlelements.php");
cInclude("classes",  "widgets/class.widgets.page.php");
cInclude("includes", "functions.upl.php");

$sNoti		= "";
$idmod		= (int)$idmod;

function getFiles ($sPath, $sFileType, &$sNoti)
{
	global $notification, $client;
	
	$bError = false;
	$aFiles = array();
	switch ($sPath)
	{
		case "layouts":
			$oLayouts = new cApiLayoutCollection;
		    $oLayouts->setWhere("idclient", $client);
		    $oLayouts->setOrder("name");
		    $oLayouts->query();
		    
		    if ($oLayouts->count() > 0)
		    {
		    	while ($oLayout = $oLayouts->next())
		    	{
		    		$aFiles[$oLayout->get($oLayout->primaryKey)] = $oLayout->get("name");
			    }
	    	}    		
		
			break;
		case "languages":
			$oClientLangs = new cApiClientLanguageCollection;
		    $oClientLangs->setWhere("idclient", $client);
		    $oClientLangs->query();
	    
	    	$aLangs = array();
	    	while ($oClientLang = $oClientLangs->next())
	    	{
	    		$aLangs[] = $oClientLang->get("idlang");
	    	}
	    
	    	$oLangs = new cApiLanguageCollection;
	    	$oLangs->setOrder("name");
	    	$oLangs->query();
	    
		    if ($oLangs->count() > 0)
		    {	    	
			    while ($oLang = $oLangs->next())
			    {
			    	$iID = $oLang->get($oLang->primaryKey);
			    	
			    	if (in_array($iID, $aLangs))
			    	{
			    		$aFiles[] = array($iID, strtolower($oLang->get("name")).' ('.$iID.')' );  // Edit: 2008-06-27 By: OliverL
			    	}
			    }
		    }
			
			break;
		default:
			// Real file
			if ($iHandle = opendir($sPath))
			{
				$aFiles = array();
				
				while ($sFile = readdir($iHandle))
				{
					if ($sFile != "." && $sFile != ".." && substr($sFile, (strlen($sFile) - (strlen($sFileType) + 1)), (strlen($sFileType) + 1)) == ".$sFileType")
					{
						if (is_readable($sPath.$sFile))
						{
							$aFiles[$sFile] = $sFile;
						} else {
							$bError = true;
		    				$sNoti .= $notification->returnNotification("error", $sFile." ".i18n("is not readable!")) . "<br /";
		    			}
					}
				}
				closedir($iHandle);
				
				if (count($aFiles) > 0)
				{
					asort($aFiles);
				}
			} else {
				$bError = true;
				$sNoti .= $notification->returnNotification("error", i18n("Directory is not existing or readable!")."<br/>$sPath")  . "<br /";	
			}
	}
	if ($bError)
	{
		return array();
	} else {
		return $aFiles;
	}
}

function displayFiles ($aFiles, $aSelected, &$oForm, $sCaption, $sField, $sDisabled)
{
	// Display files
	if (count($aFiles) == 0)
	{
		$oForm->add($sCaption, i18n("No elements available"));
	} else {
		$oSelFiles = new cHTMLSelectElement($sField."[]"); // []: Make sure, we are getting an array...
		$oSelFiles->setSize(15);
    	$oSelFiles->setStyle("width: 100%;");
    	$oSelFiles->setMultiselect();
		
		if (count($aSelected) == 0)
		{
			$oCkbNone = new cHTMLCheckbox($sField . "None", "none", "", true);
			$oSelFiles->setDisabled("disabled");
		} else {
			$oCkbNone = new cHTMLCheckbox($sField . "None", "none");
			$oSelFiles->setDisabled($sDisabled);
		}
		$oCkbNone->setDisabled($sDisabled);
		$oCkbNone->setEvent("click", "if (this.checked) { document.frmPackage.elements['" . $sField . "[]'].disabled = true; } else { document.frmPackage.elements['" . $sField . "[]'].disabled = false; }");
		    			
		$iCounter = 1;
		foreach ($aFiles as $sID => $sFile)
		{
			if (is_array($sFile))   // Edit: 2008-06-27 By: OliverL
			{
				if (in_array($sFile[0], $aSelected))
				{
					$oOption = new cHTMLOptionElement(htmlspecialchars($sFile[1]), $sFile[0], true);
				} else {
					$oOption = new cHTMLOptionElement(htmlspecialchars($sFile[1]), $sFile[0]);
				}
			} else {
				if (in_array($sID, $aSelected))
				{
					$oOption = new cHTMLOptionElement(htmlspecialchars($sFile), $sID, true);
				} else {
					$oOption = new cHTMLOptionElement(htmlspecialchars($sFile), $sID);
				}
			} // End-Edit
			$oSelFiles->addOptionElement($iCounter, $oOption);
			$iCounter++;
		}
		
		$oForm->add($sCaption, $oCkbNone->toHTML(false) . "&nbsp;" . i18n("None") . "<br />" . $oSelFiles->render());
	}
}
	
if ($idmod > 0 && $perm->have_perm_area_action_item("mod_edit", "mod_edit", $idmod))
{
	// Specify available detail areas. Note, that everything is treated as "file", including layouts and
	// translation languages.
	$aFileTypes = array("jsfiles"  => 		array("suffix" => "js", 	"path" => $cfgClient[$client]["js"]["path"],  "caption" => i18n("Javascript files"), 		"field" => "selJSFiles"), 
						"tplfiles" => 		array("suffix" => "html",	"path" => $cfgClient[$client]["tpl"]["path"], "caption" => i18n("Module template files"),	"field" => "selTplFiles"),
						"cssfiles" => 		array("suffix" => "css", 	"path" => $cfgClient[$client]["css"]["path"], "caption" => i18n("Style files"), 			"field" => "selCSSFiles"),
						"layouts" => 		array("suffix" => "", 		"path" => "layouts", 						  "caption" => i18n("Layouts"), 				"field" => "selLayouts"),
						"translations" => 	array("suffix" => "", 		"path" => "languages", 						  "caption" => i18n("Translations"), 			"field" => "selLanguages"));
	
	$oModule = new cApiModule;
	$oModule->loadByPrimaryKey($idmod);

	if ($action == "mod_importexport_package")
	{
		$sTmpPackageFile = $cfg['path']['contenido'] . $cfg['path']['temp'] . "package_" . md5($auth->auth["uid"]) . ".xml";
		
		switch ($mode)
		{
			case "export":
		    	$sFileName = uplCreateFriendlyName($oModule->get("name") . "_package");
	    		
		    	if ($sFileName != "")
	    		{
	    			$oModule->exportPackage($sFileName . ".xml");
	    		}
	    		break;
	    	case "import1":
		    	if ($_FILES["upload"]["tmp_name"] != "") // file_exists() doesn't work with safe_mode/basedir
		    	{
		    		move_uploaded_file($_FILES["upload"]["tmp_name"], $sTmpPackageFile); 
		    		
		    		$oPage = new cPage;	
	    			if (!$aResult = $oModule->getPackageOverview($sTmpPackageFile))
	    			{
	    				$sNoti .= sprintf(i18n("Error while importing XML file: %s"), $oModule->_error). "<br>";
	    				
	    				$oPage->setContent($sNoti);
						$oPage->render();
	    			} else {
						$oForm = new UI_Table_Form("frmImportExport");
						$oForm->setVar("area", "mod_package");
						$oForm->setVar("frame", $frame);
						$oForm->setVar("idmod", $idmod);
						$oForm->setVar("action", "");
						$oForm->setVar("mode", "import2");
						$oForm->setVar("use_encoding", "false");
						
						$oForm->addHeader("Import/Export");
						$oForm->setWidth("100%");
						
						$oForm->add(i18n("Name"), $aResult["name"]);

						$sMsg				= array();
						$sMsg["Action"] 	= "%s&nbsp;" . i18n("Skip") . "&nbsp;%s&nbsp;" . i18n("Append") . "&nbsp;%s&nbsp;" . i18n("Overwrite");
						$sMsg["OK"]			= i18n("OK");
						$sMsg["Assign"]		= i18n("Assign");
						$sMsg["Conflict"]	= i18n("Conflict");
						$sMsg["Ignored"]	= i18n("Ignored");
						$sMsg["- Select -"]	= i18n("- Select -");
						
						// Files
						foreach ($aFileTypes as $sFileType => $aFileType)
						{
							$oLstFiles = new UI_List;
							$oLstFiles->setWidth("100%");
							$oLstFiles->setBorder(1);
							
							$oLstFiles->setCell(0, 1, "<strong>" . i18n("Status") . "</strong>");
							$oLstFiles->setCell(0, 2, "<strong>" . i18n("Name") . "</strong>");
							$oLstFiles->setCell(0, 3, "<strong>" . i18n("Action") . "</strong>");
							$oLstFiles->setCellAlignment(0, 3, "right");
							
							$aDataFiles = getFiles($aFileType["path"], $aFileType["suffix"], $sNoti);
							
							$iCounter = 1;
							if (is_array($aResult[$sFileType]))
							{
								foreach ($aResult[$sFileType] as $sFile)
								{
									$oLstFiles->setCell($iCounter, 1, htmlspecialchars($sFile));
									
									switch ($aFileType["path"])
									{
										case "languages":
											// Languages have to be assigned, they won't be added or something
											if (is_array($aDataFiles))
											{
												$oSelLang = new cHTMLSelectElement("selAssignTrans[".htmlspecialchars($sFile)."]");
												// $oSelLang->autoFill(array_merge(array(0 => $sMsg["- Select -"]), $aDataFiles)); // Old Version
												$oSelLang->autoFill(array_merge(array(array(0 , $sMsg["- Select -"])), $aDataFiles));  // Edit: 2008-06-27 By: OliverL
												
												// Try to assign existing language
												if (in_array($sFile, $aDataFiles))
												{
													$oSelLang->setDefault(array_search(strtolower($sFile), $aDataFiles));
												}
												
												$oLstFiles->setCell($iCounter, 2, $sMsg["Assign"]);
												$oLstFiles->setCell($iCounter, 3, $oSelLang->render());
											} else {
												$oLstFiles->setCell($iCounter, 2, $sMsg["Ignored"]);
												$oLstFiles->setCell($iCounter, 3, "-");
											}
											break;
										default:
											if (is_array($aDataFiles) && in_array($sFile, $aDataFiles))
											{
												$oRadSkip 		= new cHTMLRadiobutton("radItem[".$sFileType."][".htmlspecialchars($sFile)."]", "skip", "", true);
												$oRadAppend 	= new cHTMLRadiobutton("radItem[".$sFileType."][".htmlspecialchars($sFile)."]", "append");
												$oRadOverwrite	= new cHTMLRadiobutton("radItem[".$sFileType."][".htmlspecialchars($sFile)."]", "overwrite");
												
												$oLstFiles->setCell($iCounter, 2, $sMsg["Conflict"]);
												$oLstFiles->setCell($iCounter, 3, sprintf($sMsg["Action"], $oRadSkip->toHTML(false), $oRadAppend->toHTML(false), $oRadOverwrite->toHTML(false)));
											} else {
												$oLstFiles->setCell($iCounter, 2, $sMsg["OK"]);
												$oLstFiles->setCell($iCounter, 3, "-");
											}
									}
									
									$oLstFiles->setCellAlignment($iCounter, 3, "right");
									$iCounter++;
								}
								
								$oForm->add($aFileType["caption"], $oLstFiles->render());
							}
							
							$oForm->setActionButton("cancel", $cfg['path']['contenido_fullhtml']."images/but_cancel.gif", i18n("Cancel"), "c");			    		
			    			$oForm->setActionButton("submit", $cfg['path']['contenido_fullhtml']."images/but_ok.gif", i18n("Import"), "s", "mod_importexport_package");				
						}
						$oPage->setContent($sNoti.$oForm->render());
						$oPage->render();	    				
		    		}
		    	}
		    	break;
		    case "import2":
		    	if (file_exists($sTmpPackageFile))
		    	{
		    		$aOptions = array();
		    		
		    		if (is_array($_REQUEST["radItem"]))
		    		{
		    			$aOptions["items"] = $_REQUEST["radItem"];
		    		} else {
		    			$aOptions["items"] = array();
		    		}
		    				    		
		    		if (is_array($_REQUEST["selAssignTrans"]))
		    		{
		    			foreach ($_REQUEST["selAssignTrans"] as $sPackageLang => $iAssignLang)
		    			{
		    				if ($iAssignLang > 0)
		    				{
								$aOptions["translations"][$sPackageLang] = $iAssignLang;
		    				}
		    			}
		    		} else {
		    			$aOptions["translations"] = array();
		    		}
		    		
		    		$oPage = new cPage;
		    		
		    		$oForm = new UI_Table_Form("frmImportExport");
		    		// See below for area
		    		$oForm->setVar("frame", $frame);
			    	$oForm->setVar("idmod", $idmod);
			    	$oForm->setVar("action", "");
						
					$oForm->addHeader("Import/Export");
					$oForm->setWidth("100%");

	    			if (!$oModule->importPackage($sTmpPackageFile, $aOptions))
	    			{
	    				$sNoti .= sprintf(i18n("Error while importing XML file: %s"), $oModule->_error). "<br>";
	    				
	    				$oForm->setVar("area", "mod_package");
	    				
	    				$oForm->add(i18n("Status"), i18n("Import was not succesful, please check data and try again"));
	    				$oForm->setActionButton("submit", $cfg['path']['contenido_fullhtml']."images/but_ok.gif", i18n("Retry"), "s");	
	    			} else {
	    				$oForm->setVar("area", "mod_edit");
	    				
	    				$oForm->add(i18n("Status"), i18n("Import succesfully finished"));
	    				$oForm->setActionButton("submit", $cfg['path']['contenido_fullhtml']."images/but_ok.gif", i18n("Finish"), "s");
		    		}
		    		
		    		unlink($sTmpPackageFile);
						
					$oPage->setContent($sNoti.$oForm->render());
		    		$oPage->setReload();
		    		$oPage->render();
		    	}
		    	break;
	    }
	} else {
		if ($action == "mod_edit")
		{
			$oModule->set("package_guid", stripslashes($_REQUEST["txtGUID"]));
			
			$aData = array();
			foreach ($aFileTypes as $sFileType => $aFileType)
			{
				if (!isset($_REQUEST[$aFileType["field"]]) || isset($_REQUEST[$aFileType["field"] . "None"])) {
					$aData[$sFileType] = array();
				} else {
					$aData[$sFileType] = $_REQUEST[$aFileType["field"]];
				}
			}
						
			$oModule->set("package_data", serialize($aData));
			$oModule->store(true); // Store changes - without history or updating mod code in articles			
		}

		$oInUse = new InUseCollection;
		list($bInUse, $sMessage) = $oInUse->checkAndMark("idmod", $idmod, true, i18n("Module is in use by %s (%s)"), true, "main.php?area=$area&frame=$frame&idmod=$idmod");
		unset ($oInUse);
		
		if ($bInUse == true)
		{
			$sMessage .= "<br>";
			$sDisabled = 'disabled="disabled"';
		} else {
			$sDisabled = "";
		}

		$oPage = new cPage;
		$oForm = new UI_Table_Form("frmPackage");
		$oForm->setVar("area", "mod_package");
		$oForm->setVar("frame", $frame);
		$oForm->setVar("idmod", $idmod);
		
		if (!$bInUse)
    	{
			$oForm->setVar("action", "mod_edit");
    	}
		$oForm->setWidth("100%");
			    
	   	$oForm->addHeader(i18n("Edit package"));
	   	$oForm->add(i18n("Name"), $oModule->get("name"));
	    
	    // Get serialized data; ensure, that detail fields are arrays
	    $aData = unserialize($oModule->get("package_data"));
	    if (!is_array($aData))
	    {
	    	$aData = array();
	    }
	    foreach ($aFileTypes as $sFileType => $aFileType)
	    {
	    	if (!is_array($aData[$sFileType]))
	    	{
	    		$aData[$sFileType] = array();
	    	}
	    }
	    
	    // Module package GUID
		$oTxtGUID = new cHTMLTextbox("txtGUID", $oModule->get("package_guid"), 64);
		$oTxtGUID->setDisabled($sDisabled);
		
		$oForm->add(i18n("Package GUID"), $oTxtGUID->render());
		
		// Files
		foreach ($aFileTypes as $sFileType => $aFileType)
		{
			$aFiles = getFiles($aFileType["path"], $aFileType["suffix"], $sNoti);
			displayFiles($aFiles, $aData[$sFileType], $oForm, $aFileType["caption"], $aFileType["field"], $sDisabled);
		}
			    
		$oRadImport = new cHTMLRadiobutton("mode", "import1");
		$oRadExport = new cHTMLRadiobutton("mode", "export");
	
		$oRadImport->setLabelText(i18n("Import from file"));
		$oRadExport->setLabelText(i18n("Export to file"));
	        
		$oRadImport->setEvent("click", "document.getElementById('vupload').style.display = '';");
		$oRadExport->setEvent("click", "document.getElementById('vupload').style.display = 'none';");
	
		$oUpl = new cHTMLUpload("upload");
		                
		if ($oModule->get("input") != "" && $oModule->get("output") != "")
		{
			$oRadExport->setChecked("checked");
		} else {
			$oRadImport->setChecked("checked");
		}
		$oForm2 = new UI_Table_Form("frmImportExport");
		$oForm2->setVar("action", "mod_importexport_package");
		$oForm2->setVar("use_encoding", "false");
		$oForm2->addHeader("Import/Export");
		$oForm2->add(i18n("Mode"), array($oRadExport, "<br>", $oRadImport));
		
		if ($oModule->get("input") != "" && $oModule->get("output") != "")
		{                
			$oForm2->add(i18n("File"), $oUpl, "vupload", "display: none;");
		} else {
			$oForm2->add(i18n("File"), $oUpl, "vupload");
		}
		$oForm2->setVar("area",  $area);
		$oForm2->setVar("frame", $frame);
		$oForm2->setVar("idmod", $idmod);
		$oForm2->custom["submit"]["accesskey"] = '';
			
		$oPage->setContent($sNoti.$sMessage.$oForm->render()."<br>".$oForm2->render());
	    		
		//$oPage->setSubnav("idmod=$idmod", "mod");
		$oPage->render();
	}
}

?>