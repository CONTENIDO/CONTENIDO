<?php
cInclude("classes", "datatypes/class.datatype.datetime.php");

class cDatefield extends cHTMLTextbox
{
	var $_oDate;
	
	function cDatefield ($name, $initvalue, $width = 10)
	{
		$this->_oDate = new cDatatypeDateTime;
		
		$this->_oDate->set($initvalue);
		
		parent::cHTMLTextbox($name, $initvalue, $width);	
		
	}
	
	function render ()
	{
		if ($this->_oDate->get(cDateTime_ISO) != "1970-01-01")
		{
			if ($this->_oDate->_cTargetFormat == cDateTime_Custom)
			{
				parent::setValue($this->_oDate->render());
			} else {
				parent::setValue($this->_oDate->render(cDateTime_Locale_DateOnly));
			}
		}
		
		return parent::render();	
	}
	
}
?>