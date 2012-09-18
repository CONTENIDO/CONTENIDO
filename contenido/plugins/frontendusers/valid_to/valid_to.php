<?php
function frontendusers_valid_to_getTitle ()
{
	return i18n("Valid to");	
}

function frontendusers_valid_to_display ()
{
	global $feuser,$db,$belang;
	
	$template  = '%s';
    
	$currentValue = $feuser->get("valid_to");
	
	if ($currentValue == '') {
		$currentValue = '0000-00-00';
	}
	$currentValue = str_replace('00:00:00', '', $currentValue);
	
	// js-includes are defined in valid_from
	$sValidFrom = '<input type="text" id="valid_to" name="valid_to" value="'.$currentValue.'" />&nbsp;<img src="images/calendar.gif" id="trigger_to" /">';
	$sValidFrom .= '<script type="text/javascript">
  Calendar.setup(
    {
		inputField  : "valid_to",
		ifFormat    : "%Y-%m-%d",
		button      : "trigger_to",
		weekNumbers	: true,
		firstDay	:	1
    }
  );
</script>';
	
	return sprintf($template,$sValidFrom);
}

function frontendusers_valid_to_wantedVariables ()
{
	return (array("valid_to"));	
}

function frontendusers_valid_to_store ($variables)
{
	global $feuser;
	
	$feuser->set("valid_to", $variables["valid_to"], false);
}
?>
