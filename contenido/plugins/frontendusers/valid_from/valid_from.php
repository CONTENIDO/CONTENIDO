<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Plugin valid from for frontend users
 *
 * Requirements: 
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Plugins
 * @subpackage Frontendusers
 * @version    0.2
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created  Unknown
 *
 *   $Id$: 
 * }}
 * 
 */

function frontendusers_valid_from_getTitle ()
{
	return i18n("Valid from");	
}

function frontendusers_valid_from_display()
{
	global $feuser,$db,$belang,$cfg;
	
	$langscripts = '';
	
	if(($lang_short = substr(strtolower($belang), 0, 2)) != "en") {
		 
		$langscripts=  '<script type="text/javascript" src="scripts/datetimepicker/jquery-ui-timepicker-'.$lang_short.'.js"></script>
		<script type="text/javascript" src="scripts/jquery/jquery.ui.datepicker-'.$lang_short.'.js"></script>';
	}
	
	$path_to_calender_pic =  $cfg['path']['contenido_fullhtml']. $cfg['path']['images'] . 'calendar.gif';
	
	$template  = '%s';
    
	$currentValue = $feuser->get("valid_from");
	
	if ($currentValue == '') {
		$currentValue = '0000-00-00';
	}
	$currentValue = str_replace('00:00:00', '', $currentValue);
	
	$sValidFrom = ' <link rel="stylesheet" type="text/css" href="styles/datetimepicker/jquery-ui-timepicker-addon.css">
    				<link rel="stylesheet" type="text/css" href="styles/smoothness/jquery-ui-1.8.20.custom.css">
    				<script type="text/javascript" src="scripts/jquery/jquery.js"></script>
    				<script type="text/javascript" src="scripts/jquery/jquery-ui.js"></script>
    				<script type="text/javascript" src="scripts/datetimepicker/jquery-ui-timepicker-addon.js"></script>';
	$sValidFrom .= $langscripts;
	
	$sValidFrom .= '<input type="text" id="valid_from" name="valid_from" value="'.$currentValue.'" />';
	$sValidFrom .= '<script type="text/javascript">
	
 	$(document).ready(function() {
	$("#valid_from").datetimepicker({
    		 buttonImage:"'. $path_to_calender_pic.'",
  	        buttonImageOnly: true,
  	        showOn: "both",
  	        dateFormat: "yy-mm-dd",  
    	    onClose: function(dateText, inst) {
    	        var endDateTextBox = $("#valid_to");
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
    	        $("#valid_to").datetimepicker("option", "minDate", new Date(start.getTime()));
    	    }
    	});

});
</script>';
	
	return sprintf($template,$sValidFrom);
}

function frontendusers_valid_from_wantedVariables ()
{
	return (array("valid_from"));	
}

function frontendusers_valid_from_store ($variables)
{
	global $feuser;
	
	$feuser->set("valid_from", $variables["valid_from"], false);
}
?>
