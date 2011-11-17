<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Plugin valid to for frontend users
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
 *   $Id: config.plugin.php 1709 2011-11-17 00:50:30Z xmurrix $: 
 * }}
 * 
 */

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
	
	$feuser->set("valid_to", $variables["valid_to"]);
}
?>
