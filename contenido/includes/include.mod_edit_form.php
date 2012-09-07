<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Edit modules
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.1
 * @author     Olaf Niemann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-01-21
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id: include.mod_edit_form.php 692 2008-08-15 14:33:58Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes","contenido/class.layout.php");
cInclude("classes","contenido/class.module.php");
cInclude("classes","class.ui.php");
cInclude("classes","class.htmlelements.php");
cInclude("classes","widgets/class.widgets.page.php");
cInclude("includes","functions.upl.php");
cInclude("external", "edit_area/class.edit_area.php");

$noti				= "";
$bOptionUseJava		= getEffectiveSetting("modules", "java-edit", false);
$sOptionDebugRows	= getEffectiveSetting("modules", "show-debug-rows", "never");

if (!isset($idmod)) $idmod = 0;

if ($action == "mod_delete")
{
	$modules = new cApiModuleCollection;
	$modules->delete($idmod);
}
if (($action == "mod_new") && (!$perm->have_perm_area_action_anyitem($area, $action)))
{
    $notification->displayNotification("error", i18n("No permission"));
} else {
	if ($action == "mod_new")
	{
		$modules = new cApiModuleCollection;
		$module = $modules->create(i18n("- Unnamed Module -"));
		$module->set("description", implode("\n", array(i18n("<your module description>"), "", i18n("Author: "), i18n("Version:"))));
		$module->store();
	} else {
		$module = new cApiModule;
		$module->loadByPrimaryKey($idmod);
	}
	
    if ($action == "mod_importexport_module")
    {
    	if ($mode == "export")
    	{
    		$name = uplCreateFriendlyName($module->get("name"));
    		
    		if ($name != "")
    		{
    			$module->export($name.".xml");
    		}
    	}
    	if ($mode == "import")
    	{
    		if (file_exists($_FILES["upload"]["tmp_name"]))
    		{    		
    			if (!$module->import($_FILES["upload"]["tmp_name"]))
    			{
    				$noti .= sprintf(i18n("Error while importing XML file: %s"), $module->_error). "<br>";	
    			} else {
    				// Load the item again (clearing slashes from import)
    				$module->loadByPrimaryKey($module->get($module->primaryKey));
    			}
    		}
    	}
    }

	$idmod = $module->get("idmod");
	
	if (!$perm->have_perm_area_action_item("mod_edit","mod_edit",$idmod))
	{
		$link = new cHTMLLink;
		$link->setCLink("mod_translate", 4, "");
		$link->setCustom("idmod", $idmod);
		
		header("Location: ".$link->getHREF());
	} else {
    	$oInUse = new InUseCollection;
    	list($bInUse, $message) = $oInUse->checkAndMark("idmod", $idmod, true, i18n("Module is in use by %s (%s)"), true, "main.php?area=$area&frame=$frame&idmod=$idmod");
    	unset ($oInUse);
    	
    	if ($bInUse == true)
    	{
    		$message .= "<br>";
    		$disabled = 'disabled="disabled"';
    	} else {
    		$disabled = "";
    	}

    	$page = new cPage;
    	$form = new UI_Table_Form("mod_edit");
    	$form->setVar("area","mod_edit");
    	$form->setVar("frame", $frame);
    	$form->setVar("idmod", $idmod);
    	
    	if (!$bInUse)
    	{
    		$form->setVar("action", "mod_edit");
    	}
    	$form->setWidth("100%");
    	        
       	$form->addHeader(i18n("Edit module"));
                
    	$name		= new cHTMLTextbox("name", $module->get("name"),60);
    	$descr		= new cHTMLTextarea("descr", htmlspecialchars($module->get("description")), 100, 5);
    	
    	// Get input and output code; if specified, prepare row fields
    	$sInputData		= htmlspecialchars($module->get("input"));
    	$sOutputData	= htmlspecialchars($module->get("output"));
    	
    	if ($sOptionDebugRows !== "never" && $bOptionUseJava == false)
    	{
    		$iInputNewLines		= substr_count($sInputData,  "\n") + 2; // +2: Just sanity, to have at least two more lines than the code
    		$iOutputNewLines	= substr_count($sOutputData, "\n") + 2; // +2: Just sanity, to have at least two more lines than the code
    		
    		// Have at least 15 + 2 lines (15 = code textarea lines count)
    		if ($iInputNewLines < 21)
    		{
    			$iInputNewLines = 21;
    		}
    		if ($iOutputNewLines < 21)
    		{
    			$iOutputNewLines = 21;
    		}

			// Calculate how many characters are needed (e.g. 2 for lines ip to 99)
    		$iInputNewLineChars		= strlen($iInputNewLines);
    		$iOutputNewLineChars	= strlen($iOutputNewLines);
    		if ($iInputNewLineChars > $iOutputNewLineChars)
    		{
				$iChars = $iInputNewLineChars;
    		} else {
				$iChars = $iOutputNewLineChars;
    		}
	    	unset($iInputNewLineChars);
    		unset($iOutputNewLineChars);
    		    	
    		$sRows = "";
	    	for ($i = 1; $i <= $iInputNewLines; $i++)
	    	{
    			if ($sRows)
    			{
    				$sRows .= "\r\n";
    			}
    			$sRows .= sprintf("%0".$iChars."d", $i);
    		}
    		$oInputRows = new cHTMLTextarea("txtInputRows", $sRows, $iChars, 20);
    		
			$sRows = "";
			for ($i = 1; $i <= $iOutputNewLines; $i++)
			{
				if ($sRows)
				{
					$sRows .= "\r\n";
				}
				$sRows .= sprintf("%0".$iChars."d", $i);
			}
			$oOutputRows = new cHTMLTextarea("txtOutputRows", $sRows, $iChars, 20);
			
			$oInputRows->updateAttributes(array("wrap" => "off"));
			$oOutputRows->updateAttributes(array("wrap" => "off"));
			
    		$oInputRows->updateAttributes(array("readonly" => "true"));
			$oOutputRows->updateAttributes(array("readonly" => "true"));
			
			$oInputRows->setStyle("font-family: monospace;");
			$oOutputRows->setStyle("font-family: monospace;");
			$oOutputRows->setStyle("font-family: monospace;");
    	}
    	
    	$input	= new cHTMLTextarea("input",  $sInputData, 100, 20, 'input');
    	$output = new cHTMLTextarea("output", $sOutputData, 100, 20, 'output'); 
    	
    	// Style the fields
    	$input->updateAttributes(array("wrap" => "off"));
    	$output->updateAttributes(array("wrap" => "off"));
    	
    	$name->setDisabled($disabled);
    	$descr->setDisabled($disabled);
    	$input->setDisabled($disabled);
    	$output->setDisabled($disabled);
    	
    	$descr->setStyle("width: 100%; font-family: monospace;");
    	$input->setStyle("width: 100%; font-family: monospace;");
    	$output->setStyle("width: 100%; font-family: monospace;");
    	
    	// Check, if tabs may be inserted in text areas (instead jumping to next element)
    	if (getEffectiveSetting("modules", "edit-with-tabs", "false") == "true")
    	{
    		$sTabScript = '<script type="text/javascript"><!--
/**
* Insert a tab at the current text position in a textarea
* Jan Dittmer, jdittmer@ppp0.net, 2005-05-28
* Inspired by http://www.forum4designers.com/archive22-2004-9-127735.html
* Tested on: 
*   Mozilla Firefox 1.0.3 (Linux)
*   Mozilla 1.7.8 (Linux)
*   Epiphany 1.4.8 (Linux)
*   Internet Explorer 6.0 (Linux)
* Does not work in: 
*   Konqueror (no tab inserted, but focus stays)
* Fix for IE "free focus" problem:
*   Idea from mastercomputers from New Zealand
*   http://www.antilost.com/community/index.php?showtopic=134&pid=1022&st=0&#entry1022
*   integrated by HerrB
*/
function insertTab(event, obj) {
	var tabKeyCode = 9;

	if (event.which) // mozilla
		var keycode = event.which;
	else // ie
		var keycode = event.keyCode;

	if (keycode == tabKeyCode) {
		if (event.type == "keydown") {
			if (obj.setSelectionRange) { // mozilla
				var s = obj.selectionStart;
				var e = obj.selectionEnd;
				obj.value = obj.value.substring(0, s) + "\t" + obj.value.substr(e);
				obj.setSelectionRange(s + 1, s + 1);
				obj.focus();
			} else if (obj.createTextRange) { // ie
				document.selection.createRange().text = "\t";
				setTimeout("document.getElementById(\'"+obj.id+"\').focus();",0);
			} else {
				// unsupported browsers
			}
		}
		if (event.returnValue) // ie ?
			event.returnValue = false;
		if (event.preventDefault) // dom
			event.preventDefault();
		return false; // should work in all browsers
	} else {
		return true;
	}
}
//--></script>';
    		$page->addScript("tabScript", $sTabScript);
    		
	    	$input->setEvent("onkeydown",	"return insertTab(event,this);");
	    	$output->setEvent("onkeydown",	"return insertTab(event,this);");
    	}
    	
    	// Prepare type select box
    	$typeselect = new cHTMLSelectElement("type");
    	
    	$db2 = new DB_Contenido;
    	$sql = "SELECT type FROM ".$cfg["tab"]["mod"]." ".
			   "WHERE idclient = '".$client."' GROUP BY type"; // This query can't be designed using GenericDB...
    	$db2->query($sql);
    	
		$aTypes = array();	
    	while ($db2->next_record())
    	{
    		if ($db2->f("type") != "")
    		{
    			$aTypes[] = $db2->f("type");
    		}
    	}
    	
    	// Read existing layouts
    	$oLayouts = new cApiLayoutCollection;
    	$oLayouts->setWhere("idclient", $client);
    	$oLayouts->query();
    	
    	while ($oLayout = $oLayouts->next())
    	{
    		$aTypes = array_merge(explode(";",$oLayout->getProperty("layout", "used-types")), $aTypes);	
    	}
    	$aTypes = array_unique($aTypes);
    	
		foreach ($aTypes as $sType)
		{
			$typearray[$sType] = $sType;	
		}
		unset ($aTypes);
		
		if (is_array($typearray)) {
			asort($typearray);
			$typeselect->autoFill(array_merge(array("" => "-- ".i18n("Custom")." --"), $typearray));
		} else {
			$typeselect->autoFill(array("" => "-- ".i18n("Custom")." --"));
		}
		
    	$typeselect->setEvent("change", 'if (document.forms["mod_edit"].elements["type"].value == 0) { document.forms["mod_edit"].elements["customtype"].disabled=0;} else {document.forms["mod_edit"].elements["customtype"].disabled=1;}');
    	$typeselect->setDisabled($disabled);
    	
		$custom = new cHTMLTextbox("customtype", "");
		$custom->setDisabled($disabled);
		
		if ($module->get("type") == "" || $module->get("type") == "0")
		{  
			$typeselect->setDefault("0");
		} else {
			$typeselect->setDefault($module->get("type"));
			$custom->setDisabled("disabled");
		}		
    
		$modulecheck = getSystemProperty("system", "modulecheck");
        
        $inputok  = true;
        $outputok = true;
		if ($modulecheck !== "false")
		{
			$outputok = modTestModule($module->get("output"), $module->get("idmod") . "o",true);
			if (!$outputok)
			{
				$errorMessage = sprintf(i18n("Error in module. Error location: %s"),$modErrorMessage);
				$outled = '<img align="right" src="images/but_online_no.gif" alt="'.$errorMessage.'" title="'.$errorMessage.'">';
			} else {
				$okMessage = i18n("Module successfully compiled");
				$outled = '<img align="right" src="images/but_online.gif" alt="'.$okMessage.'" title="'.$okMessage.'">';
			}
            
			$inputok = modTestModule($module->get("input"), $module->get("idmod"). "i");
			if (!$inputok)
			{
				$errorMessage = sprintf(i18n("Error in module. Error location: %s"),$modErrorMessage);
				$inled = '<img align="right" src="images/but_online_no.gif" alt="'.$errorMessage.'" title="'.$errorMessage.'">';
			} else {
				$okMessage = i18n("Module successfully compiled");
				$inled = '<img align="right" src="images/but_online.gif" alt="'.$okMessage.'" title="'.$okMessage.'">';
			}
			
			// Store error information in the database (to avoid re-eval for module overview/menu)
			if ($inputok && $outputok) {
				$sStatus = "none";
			} else if ($inputok) {
				$sStatus = "input";
			} else if ($outputok) {
				$sStatus = "output";
			} else {
				$sStatus = "both";
			}
			
			// If status has been changed, store and show in overview
			$sPrevStatus = $module->get("error");
			if ($sPrevStatus !== $sStatus)
			{
				$module->set("error", $sStatus);
				$module->store();
				
				$page->setReload();
			}
		}
            
		$form->add(i18n("Name"), $name->render());
		$form->add(i18n("Type"), $typeselect->render().$custom->render());	
		$form->add(i18n("Description"), $descr->render());
        	
		if ($bOptionUseJava == false)
		{
			if ($sOptionDebugRows == "always" || ($sOptionDebugRows == "onerror" && (!$inputok || !$outputok)))
			{
				$sSyncScript = '<script type="text/javascript"><!--
function scrolltheother() {
	var oICArea = document.mod_edit.input;
    var oOCArea = document.mod_edit.output;
	var oIRArea = document.mod_edit.txtInputRows;
	var oORArea = document.mod_edit.txtOutputRows;

	oIRArea.scrollTop = oICArea.scrollTop;
	oORArea.scrollTop = oOCArea.scrollTop;

	setTimeout("scrolltheother()", 10);
}
window.onload = scrolltheother;
				//--></script>
                ';
				$page->addScript("syncScript", $sSyncScript);
				
				$form->add('<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr><td style="vertical-align: top;">'.i18n("Input").'</td><td style="vertical-align: top;">'.$inled.'</td><td style="padding-left: 5px; vertical-align: top;">'.$oInputRows->render().'</td></tr></table>', $input->render());
                $form->add('<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr><td style="vertical-align: top;">'.i18n("Output").'</td><td style="vertical-align: top;">'.$outled.'</td><td style="padding-left: 5px; vertical-align: top;">'.$oOutputRows->render().'</td></tr></table>', $output->render());
			} else {
				$form->add('<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr><td style="vertical-align: top;">'.i18n("Input").'</td><td style="vertical-align: top;">'.$inled.'</td></tr></table>', $input->render());
                $form->add('<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr><td style="vertical-align: top;">'.i18n("Output").'</td><td style="vertical-align: top;">'.$outled.'</td></tr></table>', $output->render());
			}
		} else {
			$inputApplet  = '<applet id="einput" codebase="'.$cfg["path"]["contenido_fullhtml"].'applets/" code="Test.class" width="100%" height="400"></applet>';
			$inputField = '<input type="hidden" name="input" id="input" value="">';
        		
			$outputApplet = '<applet id="eoutput" codebase="'.$cfg["path"]["contenido_fullhtml"].'applets/" code="Test.class" width="100%" height="400"></applet>';
			$outputField = '<input type="hidden" name="output" id="output" value="">';
        		
			$form->add('<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr><td>'.i18n("Input").'</td><td align="right">'.$inled.'</td></tr></table>', $inputApplet.$inputField);
			$form->add('<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr><td>'.i18n("Output").'</td><td align="right">'.$outled.'</td></tr></table>',  $outputApplet.$outputField);
        		
			$applet = '<script language="JavaScript">document.applets[\'einput\'].setText(\''.str_replace(array("\n","\r"), array('\n', '\r'), addslashes($module->get("input"))).'\');</script>';
			$applet .= '<script language="JavaScript">document.applets[\'eoutput\'].setText(\''.str_replace(array("\n","\r"), array('\n', '\r'), addslashes($module->get("output"))).'\');</script>';
        		
			$form->setSubmitJS("window.document.mod_edit.input.value = window.document.applets['einput'].getText();".
							   "window.document.mod_edit.output.value = window.document.applets['eoutput'].getText();");	
		}
        	
		$noti = "";
        	
		if ($module->isOldModule())
		{
			$noti .= $notification->returnNotification("warning", i18n("This module uses variables and/or functions which are probably not available in this Contenido version. Please make sure that you use up-to-date modules."));
			$noti .= "<br>";
		}
        	
		if ($idmod != 0)
		{
			$import = new cHTMLRadiobutton("mode", "import");
			$export = new cHTMLRadiobutton("mode", "export");

			$import->setLabelText(i18n("Import from file"));
			$export->setLabelText(i18n("Export to file"));
                
			$import->setEvent("click", "document.getElementById('vupload').style.display = '';");
			$export->setEvent("click", "document.getElementById('vupload').style.display = 'none';");

			$upload = new cHTMLUpload("upload");
			                
			if ($module->get("input") != "" && $module->get("output") != "")
			{
				$export->setChecked("checked");
			} else {
				$import->setChecked("checked");
			}
			$form2 = new UI_Table_Form("export");
			$form2->setVar("action", "mod_importexport_module");
			$form2->setVar("use_encoding", "false");
			$form2->addHeader("Import/Export");
			$form2->add(i18n("Mode"), array($export, "<br>", $import));
			if ($module->get("input") != "" && $module->get("output") != "")
			{                
				$form2->add(i18n("File"), $upload, "vupload", "display: none;");
			} else {
				$form2->add(i18n("File"), $upload, "vupload");
			}
			$form2->setVar("area", $area);
			$form2->setVar("frame", $frame);
			$form2->setVar("idmod", $idmod);
			$form2->custom["submit"]["accesskey"] = '';
		    
            $sScript = '<script type="text/javascript">
                            if (document.getElementById(\'scroll\')) {
                                document.getElementById(\'scroll\').onmousedown = triggerClickOn;
                                document.getElementById(\'scroll\').onmouseup = triggerClickOff;
                                document.getElementById(\'scroll\').style.paddingTop=\'4px\';
								document.getElementById(\'scroll\').style.paddingBottom=\'5px\';
								
                            }
                        </script>';
            
			$page->setContent($noti.$message.$form->render().$applet."<br>".$form2->render().$sScript);
		} else {
		}
    		
		$page->setSubnav("idmod=$idmod", "mod");
		if ($action)
		{
            if (stripslashes($idmod > 0)) {
                $sReloadScript = "<script type=\"text/javascript\">
                                         var left_bottom = parent.parent.frames['left'].frames['left_bottom'];
                                         if (left_bottom) {
                                             var href = left_bottom.location.href;
                                             href = href.replace(/&idmod[^&]*/, '');
                                             left_bottom.location.href = href+'&idmod='+'".$idmod."';

                                         }
                                </script>";
            } else {
                $sReloadScript = "";
            }

			// Only reload overview/menu page, if something may have changed
			$page->addScript('reload', $sReloadScript);
		}
		if (!($action == "mod_importexport_module" && $mode == "export"))
		{
            $oEditAreaInput = new EditArea('input', 'php', substr(strtolower($belang), 0, 2), true, $cfg, !$bInUse);
            $oEditAreaOutput = new EditArea('output', 'php', substr(strtolower($belang), 0, 2), false, $cfg, !$bInUse);
            $page->addScript('editarea', $oEditAreaInput->renderScript().$oEditAreaOutput->renderScript());
			            
            $page->render();
		}
	}
}

?>