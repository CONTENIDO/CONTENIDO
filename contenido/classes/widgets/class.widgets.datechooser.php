<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * 
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0
 * @author     Unknwon
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 
 *   
 *   $Id: class.widgets.datechooser.php 738 2008-08-27 10:21:19Z timo.trautmann $: 
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


cInclude("classes", "widgets/class.widgets.datefield.php");
cInclude("classes", "widgets/class.widgets.calendar.php");

class cDateChooser extends cDatefield
{
	var $_oCalendar;
	var $_oImage;
	var $_oButton;
	
	function cDateChooser ($name, $initValue = false)
	{
		parent::cDatefield($name, "");
		
		$this->_oImage = new cHTMLImage;
		$this->_oImage->setSrc("images/pfeil_runter.gif");
		$this->_oImage->setStyle("margin-left: 2px; cursor: pointer;");

		
		$this->_oCalendar = new cCalendarControl;
		
		$this->_oDate->setSourceFormat(cDateTime_ISO);
		$this->_oDate->setTargetFormat(cDateTime_Locale_DateOnly);
		
		if ($initValue === false)
		{
			$this->_oDate->set(date("Y-m-d H:i:s"));
		} else {
			$this->_oDate->set($initValue);
		}
		
		$this->_aSelectIDs = array();
	}
	
	function setSelectsToHide ($aSelectIDs)
	{
		if (!is_array($aSelectIDs))
		{
			return;	
		}
		foreach ($aSelectIDs as $key => $data)
		{
			$aSelectIDs[$key] = '"'.$data.'"';	
		}
		$this->_aSelectIDs = $aSelectIDs;	
	}

	function setReadOnly ($bReadOnly = true)
	{
		$this->_bReadOnly = $bReadOnly;
	}
		
	function render ()
	{
		if ($this->_bReadOnly)
		{
			$this->updateAttributes(array ("readonly" => "readonly"));
		}
				
		$sDatefield = parent::render();
		$sTimeformat = getEffectiveSetting("backend", "timeformat_date", "Y-m-d");
		
		$parseScript = '
			<script language="JavaScript">

			function {wid}_passToWidget (dateFormat, date)
			{
				result = {wid}_parseLocaleDate(dateFormat, date);

				if (result["d"] && result["m"] && result["Y"])
				{
					{cid}_setDefaultDay(result["Y"], result["m"], result["d"]);
					{cid}_setCalendar(result["Y"], result["m"]);
				}
			}

			function {wid}_datefieldSetter ()
			{
				document.getElementById("'.$this->getId().'").value = {wid}_renderLocaleDate("'.$sTimeformat.'", {cid}_markedYear, {cid}_markedMonth, {cid}_markedDay);
				document.getElementById("{did}_display").style.display = "none";
				{wid}_hideselects (false);

			}

			function {wid}_renderLocaleDate (dateFormat, year, month, day)
			{
				dateFormat = dateFormat.replace(/Y/g, year);
				dateFormat = dateFormat.replace(/m/g, {wid}_LZ(month));
				dateFormat = dateFormat.replace(/d/g, {wid}_LZ(day));
				
				return dateFormat;
			}

			function {wid}_LZ(x) {
				x = parseInt(x);
			    return (x >= 10 || x < 0 ? "" : "0") + x;
			}

			function {wid}_hideselects (bhide)
			{
				var hide = new Array('.implode(",",$this->_aSelectIDs).');

				for (var i=0; i < hide.length; i++)
				{
					res = document.getElementById(hide[i]);

					if (res)
					{
						if (bhide)
						{
							res.style.visibility = "hidden";
						} else {
							res.style.visibility = "visible";
						}
					}
				}
			}

			function {wid}_parseLocaleDate (format, date)
			{
				
				customFormat = "/"+format+"/";

				customFormat = customFormat.replace(/Y/g, "(Y)");
				customFormat = customFormat.replace(/m/g, "(m)");
				customFormat = customFormat.replace(/d/g, "(d)");
				customFormat = customFormat.replace(/\./g, "\\\.");

				regFormat = "/" + format + "/";
				regFormat = regFormat.replace(/Y/g, "([0-9]{4,4})");
				regFormat = regFormat.replace(/m/g, "([0-9]{1,2})");
				regFormat = regFormat.replace(/d/g, "([0-9]{1,2})");
				regFormat = regFormat.replace(/\./g, "\\\.");

				result = date.match(eval(regFormat));
				result2 = format.match(eval(customFormat));
	
				if (result2 && result)
				{
					if (result2.length == result.length)
					{
						var myDate = new Object();
						for (var i = 1; i < result2.length; i++)
						{
							
							myDate[result2[i]] = result[i];
						}
						return myDate;

					}
				
				}

				return new Object();
				
			}
			</script>
		';
		
		$sEventRegister = '<script language="JavaScript">{cid}_attachClickCallback({wid}_datefieldSetter);</script>';
		$clickScript = "if (!document.getElementById('{wid}').disabled) { if (document.getElementById('{did}_display').style.display == 'none') { {wid}_passToWidget('".getEffectiveSetting("backend", "timeformat_date", "Y-m-d")."', document.getElementById('".$this->getId()."').value); document.getElementById('{did}_display').style.display = 'block'; {wid}_hideselects(true); } else { document.getElementById('{did}_display').style.display = 'none'; {wid}_hideselects(false); } }";
		
		$div = new cHTMLDiv;
		
		$this->_oImage->setEvent("click", $clickScript);
		
		$final = $parseScript.'<table cellspacing="0" cellpadding="0"><tr><td>'.$sDatefield.'</td><td>'.$this->_oImage->render().'</td></tr></table><div id="{did}_display" style="background-color: #E8E8EE; display: none; position: absolute; border: 1px solid black;">'.$this->_oCalendar->render().'</div>'.$sEventRegister;
		
		$final = str_replace("{wid}", $this->getId(), $final);
		$final = str_replace("{cid}", $this->_oCalendar->getId(), $final);
		
		$final = str_replace("{did}", $div->getId(), $final);
		
		return ($final);
		
			
	}
}
?>