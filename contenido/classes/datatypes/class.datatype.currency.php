<?php

define("cDatatypeCurrency_Left", 1);
define("cDatatypeCurrency_Right", 2);

class cDatatypeCurrency extends cDatatypeNumber
{
	var $_cCurrencyLocation;
	var $_sCurrencySymbol;
	
	function cDatatypeCurrency ()
	{
		cDatatypeNumber::cDataTypeNumber();	
		
		$this->setCurrencySymbolLocation(cDatatypeCurrency_Right);
		$this->setCurrencySymbol("€");
	}
	
	function setCurrencySymbol ($sSymbol)
	{
		$this->_sCurrencySymbol = $sSymbol;
	}
	
	function getCurrencySymbol ()
	{
		return ($this->_sCurrencySymbol);	
	}
	
	function setCurrencySymbolLocation ($cLocation)
	{
		switch ($cLocation)
		{
			case cDatatypeCurrency_Left:
			case cDatatypeCurrency_Right:
				$this->_cCurrencyLocation = $cLocation;
				break;
			default:
				cWarning(__FILE__, __LINE__, "Warning: No valid cDatatypeCurrency_* Constant given. Available values: cDatatypeCurrency_Left, cDatatypeCurrency_Right");
				return;
				break;
		}
	}
	
	function render ()
	{
		$value = parent::render();
		
		switch ($this->_cCurrencyLocation)
		{
			case cDatatypeCurrency_Left:
				return sprintf("%s %s", $this->_sCurrencySymbol, $value);
				break;
			case cDatatypeCurrency_Right:
				return sprintf("%s %s", $value, $this->_sCurrencySymbol);
				break;				
		}
	}
	
}
?>