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
 * @author     
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 
 *
 *   $Id: class.datatype.number.php 742 2008-08-27 11:06:12Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "datatype/class.datatype.php");

class cDatatypeNumber extends cDatatype
{
	var $_iPrecision;
	var $_sThousandSeparatorCharacter;
	var $_sDecimalPointCharacter;
		
	function cDatatypeNumber ()
	{
		global $i18nLanguage;
		
		/* Try to find out the current locale settings */
		$aLocaleSettings = cLocaleConv($i18nLanguage);
		
		$this->setDecimalPointCharacter($aLocaleSettings["mon_decimal_point"]);
		$this->setThousandSeparatorCharacter($aLocaleSettings["mon_thousands_sep"]);		
		
		cDatatype::cDatatype();	
	}
	
	function set ($value)
	{
		$this->_mValue = floatval($value);	
	}
	
	function get ()
	{
		return $this->_mValue;
	}
	
	function setPrecision ($iPrecision)
	{
		$this->_iPrecision = $iPrecision;	
	}
	
	function setDecimalPointCharacter ($sCharacter)
	{
		$this->_sDecimalPointCharacter = $sCharacter;
	}
	
	function getDecimalPointCharacter ()
	{
		return ($this->_sDecimalPointCharacter);
	}
	
	function setThousandSeparatorCharacter ($sCharacter)
	{
		$this->_sThousandSeparatorCharacter = $sCharacter;	
	}
	
	function getThousandSeparatorCharacter ()
	{
		return($this->_sThousandSeparatorCharacter);
	}	
	
	function parse ($value)
	{
		if ($this->_sDecimalPointCharacter == $this->_sThousandSeparatorCharacter)
		{
			cWarning(__FILE__, __LINE__, "Decimal point character cannot be the same as the thousand separator character. Current decimal point character is '{$this->_sDecimalPointCharacter}', current thousand separator character is '{$this->_sThousandSeparatorCharacter}'");	
			return;
		}
		
		/* Convert to standard english format */
		$value = str_replace($this->_sThousandSeparatorCharacter, "", $value);
		$value = str_replace($this->_sDecimalPointCharacter, ".", $value);
		
		$this->_mValue = floatval($value);
	}
	
	function render ()
	{
		return number_format($this->_mValue, $this->_iPrecision, $this->_sDecimalPointCharacter, $this->_sThousandSeparatorCharacter);
	}
}
?>