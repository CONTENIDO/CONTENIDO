<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Frontend user editor
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.0
 * @author     Björn Behrens (HerrB)
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2007-01-01, Björn Behrens (HerrB)
 *   modified 2008-06-27, Dominik Ziegler, add security fix
 *
 *   $Id: include.recipients_import.php 665 2008-08-10 15:20:20Z HerrB $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "widgets/class.widgets.page.php");
cInclude("classes", "class.ui.php");
cInclude("classes", "class.htmlelements.php");
cInclude("classes", "class.newsletter.recipients.php");

$oPage       = new cPage;
$oRecipients = new RecipientCollection;

if (is_array($cfg['plugins']['recipients'])) {
	foreach ($cfg['plugins']['recipients'] as $plugin) {
		plugin_include("recipients", $plugin."/".$plugin.".php");	
	}
}

// Check form data
if ($_REQUEST["selDelimiter"] == "") {
	$_REQUEST["selDelimiter"] = "tab";
}

$aFields 		= array();
$aFieldDetails	= array();
$aFields["name"]							= strtolower(i18n("Name"));
$aFieldDetails["name"]["fieldtype"]			= "field"; 	// field, plugin or group
$aFieldDetails["name"]["mandatory"]			= false;	// true or false
$aFieldDetails["name"]["type"]				= "string"; // string, boolean or date
$aFieldDetails["name"]["link"]				= false;	// plugin name for plugins, recipient group id for groups
$aFieldDetails["name"]["col"]				= -1;		// Stores column index where this field has been found
$aFields["email"]							= strtolower(i18n("Mail"));
$aFieldDetails["email"]["fieldtype"]		= "field";
$aFieldDetails["email"]["mandatory"]		= true;
$aFieldDetails["email"]["type"]				= "string";
$aFieldDetails["email"]["link"]				= false;
$aFieldDetails["email"]["col"]				= -1;
$aFields["deactivated"]						= strtolower(i18n("Deactivated"));
$aFieldDetails["deactivated"]["fieldtype"]	= "field";
$aFieldDetails["deactivated"]["mandatory"]	= false;
$aFieldDetails["deactivated"]["type"]		= "boolean";
$aFieldDetails["deactivated"]["link"]		= false;
$aFieldDetails["deactivated"]["col"]		= -1;
$aFields["confirmed"]						= strtolower(i18n("Confirmed"));
$aFieldDetails["confirmed"]["fieldtype"]	= "field";
$aFieldDetails["confirmed"]["mandatory"]	= false;
$aFieldDetails["confirmed"]["type"]			= "boolean";
$aFieldDetails["confirmed"]["link"]			= false;
$aFieldDetails["confirmed"]["col"]			= -1;
$aFields["confirmeddate"]					= strtolower(i18n("Confirmed Date"));
$aFieldDetails["confirmeddate"]["fieldtype"]= "field";
$aFieldDetails["confirmeddate"]["mandatory"]= false;
$aFieldDetails["confirmeddate"]["type"]		= "date";
$aFieldDetails["confirmeddate"]["link"]		= false;
$aFieldDetails["confirmeddate"]["col"]		= -1;
$aFields["news_type"]						= strtolower(i18n("Message type"));
$aFieldDetails["news_type"]["fieldtype"]	= "field";
$aFieldDetails["news_type"]["mandatory"]	= false;
$aFieldDetails["news_type"]["type"]			= "boolean";
$aFieldDetails["news_type"]["link"]			= false;
$aFieldDetails["news_type"]["col"]			= -1;

// Check out if there are any plugins
if (is_array($cfg['plugins']['recipients']))
{
	foreach ($cfg['plugins']['recipients'] as $sPlugin)
	{
		if (function_exists("recipients_".$sPlugin."_wantedVariables") && function_exists("recipients_".$sPlugin."_canonicalVariables"))
		{
			$aPluginTitles = call_user_func("recipients_".$sPlugin."_canonicalVariables");
    		$aPluginFields = call_user_func("recipients_".$sPlugin."_wantedVariables");
    		foreach ($aPluginFields as $sField)
    		{
    			//if ($_REQUEST["ckb".$sField]) {
    				$aFields[$sField]					= strtolower(str_replace(" ","", $aPluginTitles[$sField]));
    				$aFieldDetails[$sField]["fieldtype"]= "plugin";
    				$aFieldDetails[$sField]["mandatory"]= false;
    				$aFieldDetails[$sField]["type"]		= "string";
    				$aFieldDetails[$sField]["link"]		= $sPlugin;
    				$aFieldDetails[$sField]["col"]		= -1;
    			//}
    		}
    	}
	}
}

// Get groups
$oRcpGroups = new RecipientGroupCollection;
$oRcpGroups->setWhere("idclient", $client);
$oRcpGroups->setWhere("idlang", $lang);
$oRcpGroups->setOrder("groupname");
$oRcpGroups->query();

while ($oRcpGroup = $oRcpGroups->next())
{
	$sField = "g" . $oRcpGroup->get($oRcpGroup->primaryKey);
	
	$sGroupName = $oRcpGroup->get("groupname");
	$sGroupName = str_replace(" ", "", $sGroupName);
	$sGroupName = str_replace("\t", "", $sGroupName);
	$sGroupName = str_replace("\n", "", $sGroupName);
	$sGroupName = str_replace("\r", "", $sGroupName);
	$sGroupName = str_replace("\0", "", $sGroupName);
	$sGroupName = str_replace("\x0B;", "", $sGroupName);
	
	// Only PHP5!
	//$sGroupName = str_replace(str_split(" \t\n\r\0\x0B;"), "", $oRcpGroup->get("groupname"));
	
	$aFields[$sField]					= strtolower(htmlentities(trim(i18n("Group") . "_" . $sGroupName)));
	$aFieldDetails[$sField]["fieldtype"]= "group";
	$aFieldDetails[$sField]["mandatory"]= false;
	$aFieldDetails[$sField]["type"]		= "string";
	$aFieldDetails[$sField]["link"]		= $oRcpGroup->get($oRcpGroup->primaryKey);
	$aFieldDetails[$sField]["col"]		= -1;
}

if ($action == "recipients_import_exec" && $perm->have_perm_area_action("recipients", "recipients_create")) {	
	$_REQUEST["txtData"] = trim(stripslashes($_REQUEST["txtData"]));

	if ($_REQUEST["txtData"]) {
		switch ($_REQUEST["selDelimiter"])
		{
			case "semicolon":
				$sDelimiter = ";";
				break;
			default:
				$sDelimiter = "\t"; //chr(9);		
		}
		
		//echo "<pre>".nl2br(stripslashes($_REQUEST["txtData"]))."</pre>";
		$aLines 		= explode("\n", stripslashes($_REQUEST["txtData"]));
		$iAdded     	= 0;
		$iDublettes 	= 0;
		$iInvalid   	= 0;
		$iRow       	= 0;
		$iCol       	= 0;
		$bStop      	= false;
		$sMessage		= "";
		$aMessage   	= array();
		$aInvalidLines	= array();
		$oGroupMembers	= new RecipientGroupMemberCollection;

		foreach($aLines as $sLine) {
			$iRow++;

			$aParts = explode($sDelimiter, trim($sLine));
			
			if ($iRow == 1) {
				$aInvalidLines[] = $sLine;
				
				foreach ($aParts as $sHeader) {
					$sKey = array_search(strtolower(htmlentities(trim($sHeader))), $aFields);
					if ($sKey === false) {
						$aMessage[] = sprintf(i18n("Given column header '%s' unknown, column ignored"), $sHeader);
                	} else {
                		$aFieldDetails[$sKey]["col"] = $iCol;
						$iCol++;
					}
				}
				foreach ($aFieldDetails as $sKey => $aDetails) {
					if ($aDetails["mandatory"] && $aDetails["col"] == -1) {
						$aMessage[] = sprintf(i18n("Mandatory column '%s' wasn't found, import stopped"), $aDetails[$sKey]);
						$bStop = true;
					}
				}
				if ($bStop) {
					exit;
				} else {
					$_REQUEST["txtData"] = "";
            	}
			} else {
				$sEMail = trim($aParts[$aFieldDetails["email"]["col"]]);
				if ($aFieldDetails["name"]["col"] > -1) {
					$sName = trim($aParts[$aFieldDetails["name"]["col"]]);
					if ($sName == "") {
						$sName = $sEMail;
					}
				} else {
					$sName = $sEMail;
				}
				if ($sEMail == "") {
					$aMessage[] = sprintf(i18n("Item with empty mail address found, item ignored (name: %s, row: %s)"), $sName, $iRow);
					$aInvalidLines[] = $sLine;
					$iInvalid++;
				} else if (!isValidMail($sEMail)) {
					$aMessage[] = sprintf(i18n("Mail address '%s' is invalid, item ignored (row: %s)"), $sEMail, $iRow);
					$aInvalidLines[] = $sLine;
					$iInvalid++;
				} else if ($oRecipients->emailExists($sEMail)) {
               		$aMessage[] = sprintf(i18n("Recipient with mail address '%s' already exists, item skipped (row: %s)"), $sEMail, $iRow);
               		$aInvalidLines[] = $sLine;
					$iDublettes++;
				} else {
					unset ($sLine);
					
					// Must be $recipient for plugins
					if ($recipient = $oRecipients->create($sEMail, $sName))
					{
						$iID = $recipient->get($recipient->primaryKey);
						$iAdded++;
						
						unset($aPluginValue);
						$aPluginValue = array();
						
						foreach ($aFieldDetails as $sKey => $aDetails)
						{
							if ($aDetails["col"] > -1)
							{
								switch ($aDetails["fieldtype"])
								{
									case "field":
										switch ($aDetails["type"])
										{
											case "boolean":
												$sValue = strtolower(trim($aParts[$aDetails["col"]]));
												
												// html is only treated as "true", to get html messages for recipients
												// - quick and dirty... 
												if ($sValue == "yes" || $sValue == i18n("yes") || $sValue == "true" || 
													(is_numeric($sValue) && $sValue > 0) ||
													$sValue == "html")
												{
													$recipient->set($sKey, 1);
													
													if ($sKey == "confirmed") {
														// Ensure, that if a recipient is confirmed, a confirmed date 
														// is available. As "confirmeddate" will be set after "confirmed"
														// a specified confirmeddate will overwrite this default
														$recipient->set("confirmeddate", date("Y-m-d H:i:s"), false);
													}
												} else {
													$recipient->set($sKey, 0);
												}
												break;
											case "date":
												// TODO: Check conversion: Result may be unpredictable...
												$sValue = trim($aParts[$aDetails["col"]]);
												$recipient->set($sKey, date("Y-m-d H:i:s", strtotime($sValue)), false);
												break;
											default:
												$sValue = trim($aParts[$aDetails["col"]]);
												$recipient->set($sKey, $sValue);
										}
										break;
									case "plugin":
										// type may be mentioned here, also, but as plugins currently can't
										// specify the type, just treat everything as string
										
										// There may be plugins which store more than one value per plugin_store-
										// function. As the plugin_store parameter is an array of values, collect
										// all values in an array for later storing... unfortunately, that means,
										// that we have to go through the fields array second time per item *sigh*
										$aPluginValue[$aDetails["link"]][$sKey] = trim($aParts[$aDetails["col"]]);
										break;
									case "group":
										// Add recipient to group
										$sValue = strtolower(trim($aParts[$aDetails["col"]]));
											
										if ($sValue == "yes" || $sValue == i18n("yes") || 
											$sValue == "true" || (is_numeric($sValue) && $sValue > 0))
										{
											$oGroupMembers->create($aDetails["link"], $iID);
										}
										break;
								}
							}
						}
						// Store all base data
						$recipient->store();
						
						// Store plugin data (to store plugin data, only, where the column has been found in the data
						// should be faster than going through all plugins and store mostly empty arrays) 
						$sCurrentPlugin = "";
						foreach ($aFieldDetails as $sKey => $aDetails)
						{
							if ($aDetails["col"] > -1 && 
								$aDetails["fieldtype"] == "plugin" && 
								$aDetails["link"] !== $sCurrentPlugin)
							{
								$sCurrentPlugin = $aDetails["link"];
								
								call_user_func("recipients_".$sCurrentPlugin."_store", $aPluginValue[$sCurrentPlugin]);
							}
						}
					}
				}
			}
		}
		if (count($aInvalidLines) > 1) {
			$_REQUEST["txtData"] = implode("\n", $aInvalidLines);
		}
		if (count($aMessage) > 0) {
			$sMessage = $notification->returnNotification("warning", implode("<br />", $aMessage))."<br />";
		}
		$sMessage .= $notification->returnNotification("info", sprintf(i18n("%d recipients added, %d recipients skipped (email already exists) and %d invalid recipients/e-mail adresses ignored. Invalid recipients are shown (if any)."), $iAdded, $iDublettes, $iInvalid));
		if ($iAdded > 0) {
			$oPage->setReload();
		}
	}
}
		
$oForm = new UI_Table_Form("properties");
$oForm->setVar("frame", $frame);
$oForm->setVar("area", $area);
$oForm->setVar("action", "recipients_import_exec");

$oForm->addHeader(i18n("Import recipients"));

$oSelDelimiter = new cHTMLSelectElement("selDelimiter");
$aItems = array();
$aItems[] = array("tab", i18n("Tab"));
$aItems[] = array("semicolon", i18n("Semicolon"));		
$oSelDelimiter->autoFill($aItems);
$oSelDelimiter->setDefault($_REQUEST["selDelimiter"]);
$oForm->add(i18n("Delimiter"), $oSelDelimiter->render());

$oAreaData = new cHTMLTextarea("txtData", $_REQUEST["txtData"], 80, 20);

$sInfo = '<a href="javascript:fncShowHide(\'idInfoText\');"><strong>'.i18n("Import information").'</strong></a>'.
		 '<div id="idInfoText" style="display: none">'.
		 '<br /><br /><strong>'.i18n("Specify colum types:").'</strong>'.
		 i18n("<br />The first line must contain the column names; this specifies the column order.<br />&lt;column name&gt;[delimiter]&lt;column name&gt;...").
		 '<br /><br /><strong>'.i18n("Data structure:").'</strong><br />'.
		 i18n("The recipients have to be entered using the following format:<br />&lt;data&gt;[Delimiter]&lt;data&gt;... - each recipient in a new line.").
		 '<br /><br /><strong>'.i18n("Example:").'</strong>'.
		 i18n("<br />name;mail;confirmed<br />Smith;jon.smith@example.org;1").
		 '<br /><br /><strong>'.i18n("The following column names will be recognized:").'</strong><br />'.
		 implode("<br />\n", $aFields);
		 			  
$oForm->add(i18n("Recipients"), $oAreaData->render()."<br />".$sInfo);
unset ($sInfo);

$sExecScript = '
<script type="text/javascript">
    /* Enabled/Disable group box */
    function fncShowHide(strItemID) {
        objItem = document.getElementById(strItemID);

        if (objItem.style.display == "none") {
           objItem.style.display = "inline";
        } else {
           objItem.style.display = "none";
        }
    }
	</script>';

$oPage->addScript('execscript', $sExecScript);
$oPage->setContent($sMessage.$oForm->render(true));
$oPage->render();

?>