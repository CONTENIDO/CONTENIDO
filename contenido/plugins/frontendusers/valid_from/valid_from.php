<?php
function frontendusers_valid_from_getTitle ()
{
	return i18n("Valid from");	
}

function frontendusers_valid_from_display ()
{
	global $feuser,$db,$belang;
	
	$template  = '%s';
    
	$currentValue = $feuser->get("valid_from");
	
	if ($currentValue == '') {
		$currentValue = '0000-00-00';
	}
	$currentValue = str_replace('00:00:00', '', $currentValue);
	
	$sValidFrom = '<style type="text/css">@import url(./scripts/jscalendar/calendar-contenido.css);</style>
<script type="text/javascript" src="./scripts/jscalendar/calendar.js"></script>
<script type="text/javascript" src="./scripts/jscalendar/lang/calendar-'.substr(strtolower($belang),0,2).'.js"></script>
<script type="text/javascript" src="./scripts/jscalendar/calendar-setup.js"></script>';
	$sValidFrom .= '<input type="text" id="valid_from" name="valid_from" value="'.$currentValue.'" />&nbsp;<img src="images/calendar.gif" id="trigger" /">';
	$sValidFrom .= '<script type="text/javascript">
  Calendar.setup(
    {
		inputField  : "valid_from",
		ifFormat    : "%Y-%m-%d",
		button      : "trigger",
		weekNumbers	: true,
		firstDay	:	1
    }
  );
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
	
	$feuser->set("valid_from", $variables["valid_from"]);
}
?>
