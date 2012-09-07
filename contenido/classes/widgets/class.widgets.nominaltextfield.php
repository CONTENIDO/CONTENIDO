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
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 
 *   
 *   $Id: class.widgets.nominaltextfield.php 738 2008-08-27 10:21:19Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


cInclude("plugins", "general/classes/class.datatype.number.php");
cInclude("plugins", "general/classes/class.datatype.currency.php");

class cNominalNumberField extends cHTMLTextbox
{
	var $_oNumber;
	var $_bRealtimeNominalFormat;
	
	function cNominalNumberField ($name, $initvalue, $width)
	{
		global $belang;
		
		$this->_oNumber = new cDatatypeNumber;
		$this->_oNumber->set($initvalue);
		
		$this->disableRealtimeNominalFormat();
		
		parent::cHTMLTextbox($name, $initvalue, $width);	
	}
	
	function enableRealtimeNominalFormat ()
	{
		$this->_bRealtimeNominalFormat = true;
	}
	
	function disableRealtimeNominalFormat ()
	{
		$this->_bRealtimeNominalFormat = false;
	}
	
	function render ()
	{
		parent::setValue($this->_oNumber->render());
				
		if ($this->_bRealtimeNominalFormat)
		{
			
			$decimalChar = $this->_oNumber->getDecimalPointCharacter();
			$thousandChar = $this->_oNumber->getThousandSeparatorCharacter();
			
			parent::setEvent("change", "nominal_format_custom(this, '".$decimalChar."', '".$thousandChar."')");
		}
		return parent::render();
	}
	

}

class cNominalCurrencyField extends cNominalNumberField
{
	var $_oNumber;
	var $_bRealtimeNominalFormat;
	
	function cNominalCurrencyField ($name, $initvalue, $width)
	{
		parent::cNominalNumberField($name, $initvalue, $width);	
		
		$this->_oNumber = new cDatatypeCurrency;
		$this->_oNumber->set($initvalue);

		$this->disableRealtimeNominalFormat();
		

	}
	
	function enableRealtimeNominalFormat ()
	{
		$this->_bRealtimeNominalFormat = true;
	}
	
	function disableRealtimeNominalFormat ()
	{
		$this->_bRealtimeNominalFormat = false;
	}	
	
	function render ()
	{
		parent::setValue($this->_oNumber->render());
		
		if ($this->_bRealtimeNominalFormat)
		{
			$decimalChar = $this->_oNumber->getDecimalPointCharacter();
			$thousandChar = $this->_oNumber->getThousandSeparatorCharacter();
			
			$currencySign = $this->_oNumber->getCurrencySymbol();
			
			parent::setEvent("change", "nominal_format_custom(this, '".$decimalChar."', '".$thousandChar."', '".$currencySign."')");
		}
		return parent::render();
	}
	

}
?>