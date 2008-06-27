<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Directory overview
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.7.0
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-12-30
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "class.ui.php");
cInclude("classes", "class.htmlelements.php");
cInclude("classes", "class.properties.php");
cInclude("includes", "functions.upl.php");

$page = new UI_Page;
$page->addScript("cal1", '<style type="text/css">@import url(./scripts/jscalendar/calendar-contenido.css);</style>');
$page->addScript("cal2", '<script type="text/javascript" src="./scripts/jscalendar/calendar.js"></script>');
$page->addScript("cal3", '<script type="text/javascript" src="./scripts/jscalendar/lang/calendar-'.substr(strtolower($belang),0,2).'.js"></script>');
$page->addScript("cal4", '<script type="text/javascript" src="./scripts/jscalendar/calendar-setup.js"></script>');

$form = new UI_Table_Form("properties");
$form->setVar("frame", $frame);
$form->setVar("area", "upl");
$form->setVar("path", $_REQUEST["path"]);
$form->setVar("file", $_REQUEST["file"]);
$form->setVar("action", "upl_modify_file");
$form->setVar("startpage", $_REQUEST["startpage"]);
$form->setVar("sortby", $_REQUEST["sortby"]);
$form->setVar("sortmode", $_REQUEST["sortmode"]);
$form->setVar("thumbnailmode", $_REQUEST["thumbnailmode"]);
$form->addHeader(i18n("Edit"));

$properties = new PropertyCollection;
$uploads = new UploadCollection;

if (is_dbfs($_REQUEST["path"]))
{
	$qpath = $_REQUEST["path"] . "/"; 	
} else {
	$qpath = $_REQUEST["path"];	
}

$uploads->select("idclient = '$client' AND dirname = '$qpath' AND filename='".$_REQUEST["file"]."'");

if ($upload = $uploads->next())
{
	$keywords 	= $properties->getValue("upload", $qpath.$_REQUEST["file"], "file", "keywords");
	$medianame 	= $properties->getValue("upload", $qpath.$_REQUEST["file"], "file", "medianame");
	$medianotes = $properties->getValue("upload", $qpath.$_REQUEST["file"], "file", "medianotes");
	$vprotected = $properties->getValue("upload", $qpath.$_REQUEST["file"], "file", "protected");
	$copyright 	= $properties->getValue("upload", $qpath.$_REQUEST["file"], "file", "copyright");
	
	$sDescription = $upload->get("description");
	$iTimeMng = (int)$properties->getValue("upload", $qpath.$_REQUEST["file"], "file", "timemgmt");
	$sStartDate = $properties->getValue("upload", $qpath.$_REQUEST["file"], "file", "datestart");
	$sEndDate = $properties->getValue("upload", $qpath.$_REQUEST["file"], "file", "dateend");
	
	// modified 2007/07/13: If entry in con_upl_meta then overwrite values for keywords, medianame and medianotes
	$iIdupl = $upload->get("idupl");
	$sSql = "SELECT * FROM " . $cfg['tab']['upl_meta'] . " WHERE idupl = $iIdupl AND idlang = $lang " .
			"LIMIT 0, 1";
	$db->query($sSql);
	if ($db->num_rows() > 0)
	{
		$db->next_record();
		$medianame = stripslashes($db->f('medianame'));
		$medianotes = stripslashes($db->f('internal_notice'));
		$keywords = stripslashes($db->f('keywords'));
		$sDescription = stripslashes($db->f('description'));
	}
	
	$kwedit = new cHTMLTextarea("keywords", $keywords);
	$mnedit = new cHTMLTextbox("medianame", $medianame,60);
	$moedit = new cHTMLTextarea("medianotes", $medianotes);
	$dsedit = new cHTMLTextarea("description", $sDescription);
	$protected = new cHTMLCheckbox("protected", "1");
	$protected->setChecked($vprotected);
	$protected->setLabelText(i18n("Protected for non-logged in users"));
	$copyrightEdit = new cHTMLTextarea("copyright", $copyright);
	
	if (is_dbfs($_REQUEST["path"]))	{
		$thumbnail = '<a target="_blank" href="'.$sess->url($cfgClient[$client]["path"]["htmlpath"]."dbfs.php?file=".$qpath.$_REQUEST["file"]).'"><img style="padding: 10px; background: white; border: 1px; border-style: solid; border-color: '.$cfg["color"]["table_border"].';" src="'.uplGetThumbnail($qpath.$_REQUEST["file"], 350).'"></a>';
	}	else {
		$thumbnail = '<a target="_blank" href="'.$cfgClient[$client]["upl"]["htmlpath"].$qpath.$_REQUEST["file"].'"><img style="padding: 10px; background: white; border: 1px; border-style: solid; border-color: '.$cfg["color"]["table_border"].';" src="'.uplGetThumbnail($qpath.$_REQUEST["file"], 350).'"></a>';
	}
	
	$uplelement = new cHTMLUpload("file",40);
	
    $qpath = generateDisplayFilePath($qpath, 65);

	$form->add(i18n("File name"), $_REQUEST["file"]);
	$form->add(i18n("Path"), $qpath);
	$form->add(i18n("Replace file"), $uplelement->render());
	$form->add(i18n("Media name"), $mnedit->render());
	$form->add(i18n("Description"), $dsedit->render());
	$form->add(i18n("Keywords"), $kwedit->render());
	$form->add(i18n("Internal notes"), $moedit->render());
	$form->add(i18n("Copyright"), $copyrightEdit->render());
	

	if (is_dbfs($_REQUEST["path"]))	{
		$form->add(i18n("Protection"), $protected->render());
	
		$oTimeCheckbox = new cHTMLCheckbox("timemgmt", i18n("Use time control"));
		$oTimeCheckbox->setChecked($iTimeMng);
		$sHtmlTimeMng = "<table border='0' cellpadding='0' cellspacing='0' style='width: 100%;'>\n";
		$sHtmlTimeMng .= "<tr><td colspan='2'>" . $oTimeCheckbox->render() . "</td></tr>\n";
		$sHtmlTimeMng .= "<tr><td style='padding-left: 20px;'>" . i18n("Start date") . "</td>\n";
	
		$sHtmlTimeMng .= '<td><input type="text" name="datestart" id="datestart" value="' . $sStartDate . '"  size="20" ' .
							'maxlength="40" class="text_medium">' .
							'&nbsp;<img src="images/calendar.gif" id="trigger_start" width="16" height="16" border="0" alt="" /></td></tr>';

		$sHtmlTimeMng .= "<tr><td style='padding-left: 20px;'>" . i18n("End date") . "</td>\n";
		
		$sHtmlTimeMng .= '<td><input type="text" name="dateend" id="dateend" value="' . $sEndDate . '"  size="20" ' .
							'maxlength="40" class="text_medium">' .
							'&nbsp;<img src="images/calendar.gif" id="trigger_end" width="16" height="16" border="0" alt="" /></td></tr>';
		$sHtmlTimeMng .= "</table>\n";
		
		$sHtmlTimeMng .= '<script type="text/javascript">
	  					Calendar.setup(
	    					{
	      					inputField  : "datestart",
	      					ifFormat    : "%Y-%m-%d",
	      					button      : "trigger_start",
	      					weekNumbers	: true,
	      					firstDay	:	1
	    					}
	  					);
						</script>';
	
		$sHtmlTimeMng .= '<script type="text/javascript">
	  					Calendar.setup(
	    					{
	      					inputField  : "dateend",
	      					ifFormat    : "%Y-%m-%d",
	      					button      : "trigger_end",
	      					weekNumbers	: true,
	      					firstDay	:	1
	    					}
	  					);
						</script>';
		
		$form->add(i18n("Time control"), $sHtmlTimeMng);
	}
	$form->add(i18n("Preview"), $thumbnail);
	$form->add(i18n("Author"), $classuser->getUserName($upload->get("author")) . " (". $upload->get("created").")" ); 
	$form->add(i18n("Last modified by"), $classuser->getUserName($upload->get("modifiedby")). " (". $upload->get("lastmodified").")" );
	
	$sScript = "";
	if (is_dbfs($_REQUEST["path"])) {
		$sScript = "" .
					"\n\n\n<script language='JavaScript'>\n
					var startcal = new calendar1(document.properties.elements['datestart']);\n
				 	startcal.year_scroll = true;\n
					startcal.time_comp = true;\n
				   	var endcal = new calendar1(document.properties.elements['dateend']);\n
				   	endcal.year_scroll = true;\n
					endcal.time_comp = true;\n</script>\n\n\n";
	}
	
    $sScriptinBody = '<script type="text/javascript" src="scripts/wz_tooltip.js"></script>
                      <script type="text/javascript" src="scripts/tip_balloon.js"></script>';
	$page->addScript('style', '<link rel="stylesheet" type="text/css" href="styles/tip_balloon.css" />');
    
	$page->setContent($sScriptinBody.$form->render() . $sScript);
}
else 
{
	$page->setContent(sprintf(i18n("Could not load file %s"),$_REQUEST["file"]));
}

$page->render();

?>