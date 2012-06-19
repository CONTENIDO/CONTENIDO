<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Directory overview
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.8.2
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created 2003-12-30
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2008-07-31, Oliver Lohkemper, add CEC
 *   modified 2008-08-11, Timo Trautmann, added urlencode for meta storage in database
 *   modified 2008-10-16, Oliver Lohkemper, add copyright in upl_meta - CON-212
 *   modified 2010-09-20, Dominik Ziegler, implemented check for write permissions - CON-319
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude("includes", "functions.upl.php");

$page = new UI_Page();


//get language js files
if(($lang_short = substr(strtolower($belang), 0, 2)) != "en") {

	$langscripts=  '<script type="text/javascript" src="scripts/datetimepicker/jquery-ui-timepicker-'.$lang_short.'.js"></script>
	<script type="text/javascript" src="scripts/jquery/jquery.ui.datepicker-'.$lang_short.'.js"></script>';
}

$path_to_calender_pic =  $cfg['path']['contenido_fullhtml']. $cfg['path']['images'] . 'calendar.gif';


$page->addScript('tooltippstyle', '<link rel="stylesheet" type="text/css" href="styles/tipsy.css" />');
$page->addScript("cal", '<link rel="stylesheet" type="text/css" href="styles/datetimepicker/jquery-ui-timepicker-addon.css">
		<link rel="stylesheet" type="text/css" href="styles/smoothness/jquery-ui-1.8.20.custom.css">
		<script type="text/javascript" src="scripts/jquery/jquery.js"></script>
		<script type="text/javascript" src="scripts/jquery/jquery-ui.js"></script>
		<script type="text/javascript" src="scripts/datetimepicker/jquery-ui-timepicker-addon.js"></script>'
		. $langscripts);


$sTooltippScript = '<script type="text/javascript" src="scripts/jquery.tipsy.js"></script>
    	            <script type="text/javascript" src="scripts/registerTipsy.js"></script>';
$page->addScript("tooltip-js", $sTooltippScript);

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

$properties = new cApiPropertyCollection();
$uploads    = new cApiUploadCollection();

if (is_dbfs($_REQUEST["path"])) {
    $qpath = $_REQUEST["path"] . "/";
} else {
    $qpath = $_REQUEST["path"];
}

if ((is_writable($cfgClient[$client]["upl"]["path"].$path) || is_dbfs($path)) && (int) $client > 0) {
    $bDirectoryIsWritable = true;
} else {
    $bDirectoryIsWritable = false;
}

$uploads->select("idclient = '".$client."' AND dirname = '".$qpath."' AND filename='".$_REQUEST["file"]."'");

if ($upload = $uploads->next()) {

    // Which rows to display?
    $aListRows = array();
    $aListRows["filename"]    = i18n("File name");
    $aListRows["path"]        = i18n("Path");
    $aListRows["replacefile"] = i18n("Replace file");
    $aListRows["medianame"]   = i18n("Media name");
    $aListRows["description"] = i18n("Description");
    $aListRows["keywords"]    = i18n("Keywords");
    $aListRows["medianotes"]  = i18n("Internal notes");
    $aListRows["copyright"]   = i18n("Copyright");
    $aListRows["protected"]   = i18n("Protection");
    $aListRows["timecontrol"] = i18n("Time control");
    $aListRows["preview"]     = i18n("Preview");
    $aListRows["author"]      = i18n("Author");
    $aListRows["modified"]    = i18n("Last modified by");

    // Delete dbfs specific rows
    if (!is_dbfs($_REQUEST["path"])) {
        unset($aListRows['protected']);
        unset($aListRows['timecontrol']);
    }

    // Call chains to process the rows
    $_cecIterator = $_cecRegistry->getIterator("Contenido.Upl_edit.Rows");
    if ($_cecIterator->count() > 0) {
        while ($chainEntry = $_cecIterator->next()) {
            $newRowList = $chainEntry->execute($aListRows);
            if (is_array($newRowList)) {
                $aListRows = $newRowList;
            }
        }
    }


    $iIdupl = $upload->get("idupl");
    $sSql = "SELECT * FROM " . $cfg['tab']['upl_meta'] . "
             WHERE idupl = " . Contenido_Security::toInteger($iIdupl) . "
             AND idlang = " . Contenido_Security::toInteger($lang) . "
             LIMIT 0, 1";
    $db->query($sSql);

    if ($db->num_rows() > 0) {
        $db->next_record();
    }

    // Add rows to $form
    foreach ($aListRows as $sListRow => $sTitle) {
        $sCell = "";
        switch ($sListRow) {
            case "filename":
                $sCell = $_REQUEST["file"];
                break;

            case "path":
                $sCell = generateDisplayFilePath($qpath, 65);
                break;

            case "replacefile":
                $uplelement = new cHTMLUpload("file",40);
                $uplelement->setDisabled(!$bDirectoryIsWritable);
                $sCell = $uplelement->render();
                break;

            case "medianame":
                if ($db->f('medianame')) {

                    $medianame = Contenido_Security::unFilter($db->f('medianame'));

                } else {

                    $medianame = $properties->getValue("upload", $qpath.$_REQUEST["file"], "file", "medianame");

                }

                $mnedit = new cHTMLTextbox("medianame", $medianame, 60);
                $sCell = $mnedit->render();
                break;

            case "description":
                if ($db->f('description')) {

                    $sDescription = Contenido_Security::unFilter($db->f('description'));
                } else {

                    $sDescription = $upload->get("description");
                }

                $dsedit = new cHTMLTextarea("description", $sDescription);
                $sCell = $dsedit->render();
                break;

            case "keywords":
                if ($db->f('keywords')) {

                    $keywords = Contenido_Security::unFilter($db->f('keywords'));
                } else {

                    $keywords = $properties->getValue("upload", $qpath.$_REQUEST["file"], "file", "keywords");
                }

                $kwedit = new cHTMLTextarea("keywords", $keywords);
                $sCell = $kwedit->render();
                break;

            case "medianotes":
                if ($db->f('internal_notice')) {

                    $medianotes = Contenido_Security::unFilter($db->f('internal_notice'));
                } else {

                    $medianotes = $properties->getValue("upload", $qpath.$_REQUEST["file"], "file", "medianotes");
                }

                $moedit = new cHTMLTextarea("medianotes", $medianotes);
                $sCell = $moedit->render();
                break;

            case "copyright":
                if ($db->f('copyright')) {

                    $copyright = Contenido_Security::unFilter($db->f('copyright'));
                } else {

                    $copyright = $properties->getValue("upload", $qpath.$_REQUEST["file"], "file", "copyright");
                }

                $copyrightEdit = new cHTMLTextarea("copyright", $copyright);
                $sCell = $copyrightEdit->render();
                break;

            case "protected":
                $vprotected = $properties->getValue("upload", $qpath.$_REQUEST["file"], "file", "protected");
                $protected = new cHTMLCheckbox("protected", "1");
                $protected->setChecked($vprotected);
                $protected->setLabelText(i18n("Protected for non-logged in users"));
                $sCell = $protected->render();
                break;

            case "timecontrol":
                $iTimeMng   = (int) $properties->getValue("upload", $qpath.$_REQUEST["file"], "file", "timemgmt");
                $sStartDate = $properties->getValue("upload", $qpath.$_REQUEST["file"], "file", "datestart");
                $sEndDate   = $properties->getValue("upload", $qpath.$_REQUEST["file"], "file", "dateend");

                $oTimeCheckbox = new cHTMLCheckbox("timemgmt", i18n("Use time control"));
                $oTimeCheckbox->setChecked($iTimeMng);

                $sHtmlTimeMng  = "<table border='0' cellpadding='0' cellspacing='0' style='width: 100%;'>\n";
                $sHtmlTimeMng .= "<tr><td colspan='2'>" . $oTimeCheckbox->render() . "</td></tr>\n";
                $sHtmlTimeMng .= "<tr><td style='padding-left: 20px;'><label for='datestart'>" . i18n("Start date") . "</label></td>\n";
                $sHtmlTimeMng .= '<td><input type="text" name="datestart" id="datestart" value="' . $sStartDate . '"  size="20" maxlength="40" class="text_medium">' .
                    '</td></tr>';
                $sHtmlTimeMng .= "<tr><td style='padding-left: 20px;'><label for='dateend'>" . i18n("End date") . "</label></td>\n";
                $sHtmlTimeMng .= '<td><input type="text" name="dateend" id="dateend" value="' . $sEndDate . '"  size="20" maxlength="40" class="text_medium">' .
                    '</td></tr>';
                $sHtmlTimeMng .= "</table>\n";

                $sHtmlTimeMng .= '<script type="text/javascript">
                $(document).ready(function() {
	$("#datestart").datetimepicker({
    		 buttonImage:"'. $path_to_calender_pic.'",
  	        buttonImageOnly: true,
  	        showOn: "both",
  	        dateFormat: "yy-mm-dd",  
    	    onClose: function(dateText, inst) {
    	        var endDateTextBox = $("#dateend");
    	        if (endDateTextBox.val() != "") {
    	            var testStartDate = new Date(dateText);
    	            var testEndDate = new Date(endDateTextBox.val());
    	            if (testStartDate > testEndDate)
    	                endDateTextBox.val(dateText);
    	        }
    	        else {
    	            endDateTextBox.val(dateText);
    	        }
    	    },
    	    onSelect: function (selectedDateTime){
    	        var start = $(this).datetimepicker("getDate");
    	        $("#dateend").datetimepicker("option", "minDate", new Date(start.getTime()));
    	    }
    	});
    	$("#dateend").datetimepicker({
    		 buttonImage: "'. $path_to_calender_pic .'",
   	        buttonImageOnly: true,
   	        showOn: "both",
   	        dateFormat: "yy-mm-dd",
    	    onClose: function(dateText, inst) {
    	        var startDateTextBox = $("#datestart");
    	        if (startDateTextBox.val() != "") {
    	            var testStartDate = new Date(startDateTextBox.val());
    	            var testEndDate = new Date(dateText);
    	            if (testStartDate > testEndDate)
    	                startDateTextBox.val(dateText);
    	        }
    	        else {
    	            startDateTextBox.val(dateText);
    	        }
    	    },
    	    onSelect: function (selectedDateTime){
    	        var end = $(this).datetimepicker("getDate");
    	        $("#datestart").datetimepicker("option", "maxDate", new Date(end.getTime()) );
    	    }
    	});

});
                </script>';

                $sCell = $sHtmlTimeMng;
                break;

            case "preview":
                if (is_dbfs($_REQUEST["path"])) {
                    $sCell = '<a target="_blank" href="'.$sess->url($cfgClient[$client]["path"]["htmlpath"]."dbfs.php?file=".$qpath.$_REQUEST["file"]).'"><img class="bordered" src="'.uplGetThumbnail($qpath.$_REQUEST["file"], 350).'"></a>';
                } else {
                    $sCell = '<a target="_blank" href="'.$cfgClient[$client]["upl"]["htmlpath"].$qpath.$_REQUEST["file"].'"><img class="bordered" src="'.uplGetThumbnail($qpath.$_REQUEST["file"], 350).'"></a>';
                }
                break;

            case "author":
                $oUser = new cApiUser($upload->get("author"));
                $sCell = $oUser->get('username') . " (". $upload->get("created").")";
                break;

            case "modified":
                $oUser = new cApiUser($upload->get("modifiedby"));
                $sCell = $oUser->get('username') . " (". $upload->get("lastmodified").")";
                break;

            default:
                // Call chain to retrieve value
                $_cecIterator = $_cecRegistry->getIterator("Contenido.Upl_edit.RenderRows");

                if ($_cecIterator->count() > 0) {
                    $contents = array();
                    while ($chainEntry = $_cecIterator->next()) {
                        $contents[]  = $chainEntry->execute( $iIdupl, $qpath, $_REQUEST["file"], $sListRow);
                    }

                }
                $sCell = implode("", $contents);
        }
        $form->add($sTitle, $sCell);
    }

    $sScript = "";
    if (is_dbfs($_REQUEST["path"])) {
        $sScript = '

        <script type="text/javascript">
        var startcal = new calendar1(document.properties.elements["datestart"]);
        startcal.year_scroll = true;
        startcal.time_comp = true;
        var endcal = new calendar1(document.properties.elements["dateend"]);
        endcal.year_scroll = true;
        endcal.time_comp = true;

        </script>

        ';
    }
    
    if ($bDirectoryIsWritable == false) {
        $sErrorMessage = $notification->returnNotification("error", i18n("Directory not writable")  . ' (' . $cfgClient[$client]["upl"]["path"].$path . ')');
        $sErrorMessage .= '<br />';
    } else {
        $sErrorMessage = '';
    }

    $page->setContent($sErrorMessage . $form->render() . $sScript);
} else {
   $page->setContent(sprintf(i18n("Could not load file %s"),$_REQUEST["file"]));
}

$page->render();

?>