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
 *   $Id: class.widgets.datefield.php 738 2008-08-27 10:21:19Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


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