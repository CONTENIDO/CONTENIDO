<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * 
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Backend Classes
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
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

class cDatatypeNumber extends cDatatype
{
	protected $_iPrecision;
	protected $_sThousandSeparatorCharacter;
	protected $_sDecimalPointCharacter;
		
	public function __construct()
	{
		global $_i18nTranslation;
		
		/* Try to find out the current locale settings */
		$aLocaleSettings = cLocaleConv($_i18nTranslation['language']);
		
		$this->setDecimalPointCharacter($aLocaleSettings["mon_decimal_point"]);
		$this->setThousandSeparatorCharacter($aLocaleSettings["mon_thousands_sep"]);		
		
		cDatatype::__construct();	
	}
	
	/**
	* @deprecated [2012-01-19] use __construct instead
	*/
	public function cDatatypeNumber() {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }
	
	public function set ($value)
	{
		$this->_mValue = floatval($value);	
	}
	
	public function get ()
	{
		return $this->_mValue;
	}
	
	public function setPrecision ($iPrecision)
	{
		$this->_iPrecision = $iPrecision;	
	}
	
	public function setDecimalPointCharacter ($sCharacter)
	{
		$this->_sDecimalPointCharacter = $sCharacter;
	}
	
	public function getDecimalPointCharacter ()
	{
		return ($this->_sDecimalPointCharacter);
	}
	
	public function setThousandSeparatorCharacter ($sCharacter)
	{
		$this->_sThousandSeparatorCharacter = $sCharacter;	
	}
	
	public function getThousandSeparatorCharacter ()
	{
		return($this->_sThousandSeparatorCharacter);
	}	
	
	public function parse ($value)
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
	
	public function render ()
	{
		return number_format($this->_mValue, $this->_iPrecision, $this->_sDecimalPointCharacter, $this->_sThousandSeparatorCharacter);
	}
}
?>