<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Area management class
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
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "datatypes/class.datatype.php");
cInclude("classes", "contenido/class.user.php");

/* The UNIX Timestamp is the amount of seconds
   passed since Jan 1 1970 00:00:00 GMT */
define("cDateTime_UNIX"  , 1);

/* The ISO Date format is CCYY-MM-DD HH:mm:SS */
define("cDateTime_ISO"   , 2);

/* The locale format, as specified in the Contenido backend */
define("cDateTime_Locale", 3);

/* The locale format, as specified in the Contenido backend */
define("cDateTime_Locale_TimeOnly", 4);

/* The locale format, as specified in the Contenido backend */
define("cDateTime_Locale_DateOnly", 5);

/* The MySQL Timestamp is CCYYMMDDHHmmSS */
define("cDateTime_MySQL" , 6);

/* Custom format */
define("cDateTime_Custom" , 99);

define("cDateTime_Sunday", 0);
define("cDateTime_Monday", 1);
define("cDateTime_Tuesday", 2);
define("cDateTime_Wednesday", 3);
define("cDateTime_Thursday", 4);
define("cDateTime_Friday", 5);
define("cDateTime_Saturday", 6);

class cDatatypeDateTime extends cDatatype
{
	var $_iFirstDayOfWeek;
	
	/* This datatype stores its date format in ISO */
	
	function cDatatypeDateTime ()
	{
		$this->setTargetFormat(cDateTime_Locale);
		$this->setSourceFormat(cDateTime_UNIX);
		
		$this->_iYear = 1970;
		$this->_iMonth = 1;
		$this->_iDay = 1;
		$this->_iHour = 0;
		$this->_iMinute = 0;
		$this->_iSecond = 0;
		
		$this->setFirstDayOfWeek(cDateTime_Monday);
		parent::cDatatype();	
	}
	
	function setCustomTargetFormat ($targetFormat)
	{
		$this->_sCustomTargetFormat = $targetFormat;
	}
	
	function setCustomSourceFormat ($sourceFormat)
	{
		$this->_sCustomSourceFormat = $sourceFormat;
	}	
	
	function setSourceFormat ($cSourceFormat)
	{
		$this->_cSourceFormat = $cSourceFormat;	
	}
	
	function setTargetFormat ($cTargetFormat)
	{
		$this->_cTargetFormat = $cTargetFormat;	
	}	
	
	function setYear ($iYear)
	{
		$this->_iYear = $iYear;
	}
	
	function getYear ()
	{
		return ($this->_iYear);	
	}

	function setMonth ($iMonth)
	{
		$this->_iMonth = $iMonth;	
	}
	
	function getMonth ()
	{
		return ($this->_iMonth);
	}
	
	function setDay ($iDay)
	{
		$this->_iDay = $iDay;
	}
	
	function getDay ()
	{
		return ($this->_iDay);	
	}

	function getMonthName ($iMonth)
	{
		switch ($iMonth)
		{
			case 1:	return i18n("January");
			case 2: return i18n("February");
			case 3: return i18n("March");
			case 4: return i18n("April");
			case 5: return i18n("May");
			case 6: return i18n("June");
			case 7: return i18n("July");
			case 8: return i18n("August");
			case 9: return i18n("September");
			case 10: return i18n("October");
			case 11: return i18n("November");
			case 12: return i18n("December");
		}	
	}
	
	function getDayName ($iDayOfWeek)
	{
		switch ($iDayOfWeek)
		{
			case 0:	return i18n("Sunday");
			case 1:	return i18n("Monday");
			case 2: return i18n("Tuesday");
			case 3: return i18n("Wednesday");
			case 4: return i18n("Thursday");
			case 5: return i18n("Friday");
			case 6: return i18n("Saturday");
			case 7:	return i18n("Sunday");
			default: return false;
		}
	}
	
	function getDayOrder ()
	{
		$aDays = array(0, 1, 2, 3, 4, 5, 6);
		$aRemainderDays = array_splice($aDays, 0, $this->_iFirstDayOfWeek);
		
		$aReturnDays = array_merge($aDays, $aRemainderDays);
		
		return ($aReturnDays);
	}
	
	function getNumberOfMonthDays ($iMonth = false, $iYear = false)
	{
		if ($iMonth === false)
		{
			$iMonth = $this->_iMonth;	
		}
		
		if ($iYear === false)
		{
			$iYear = $this->_iYear;	
		}
		
		return date("t", mktime(0,0,0,$iMonth, 1, $iYear));
	}
	
	function setFirstDayOfWeek ($iDay)
	{
		$this->_iFirstDayOfWeek = $iDay;
	}
	
	function getFirstDayOfWeek ()
	{
		return $this->_iFirstDayOfWeek;
	}
	
	function getLeapDay ()
	{
		return end($this->getDayOrder());	
	}
	
	function set ($value, $iOverrideFormat = false)
	{
		if ($value == "")
		{
			return;	
		}
		
		if ($iOverrideFormat !== false)
		{
			$iFormat = $iOverrideFormat;	
		} else {
			$iFormat = $this->_cSourceFormat;
		}
		
		switch ($iFormat)
		{
			case cDateTime_UNIX:
				$sTemporaryTimestamp = $value;
				$this->_iYear =	date("Y", $sTemporaryTimestamp);
				$this->_iMonth = date("m", $sTemporaryTimestamp);				
				$this->_iDay = date("d", $sTemporaryTimestamp);
				$this->_iHour = date("H", $sTemporaryTimestamp);
				$this->_iMinute = date("i", $sTemporaryTimestamp);
				$this->_iSecond = date("s", $sTemporaryTimestamp);								
			
				break;
			case cDateTime_ISO:
				$sTemporaryTimestamp = strtotime($value);
				$this->_iYear =	date("Y", $sTemporaryTimestamp);
				$this->_iMonth = date("m", $sTemporaryTimestamp);				
				$this->_iDay = date("d", $sTemporaryTimestamp);
				$this->_iHour = date("H", $sTemporaryTimestamp);
				$this->_iMinute = date("i", $sTemporaryTimestamp);
				$this->_iSecond = date("s", $sTemporaryTimestamp);								
				break;
            case cDateTime_MySQL:
                $sTimeformat = "YmdHis";
				
				$targetFormat = str_replace('.', '\.', $sTimeformat);
				$targetFormat = str_replace("d", "([0-9]{2,2})", $targetFormat);
				$targetFormat = str_replace("m", "([0-9]{2,2})", $targetFormat);
				$targetFormat = str_replace("Y", "([0-9]{4,4})", $targetFormat);
				$targetFormat = str_replace("H", "([0-9]{2,2})", $targetFormat);
				$targetFormat = str_replace("i", "([0-9]{2,2})", $targetFormat);
				$targetFormat = str_replace("s", "([0-9]{2,2})", $targetFormat);				
				/* Match the placeholders */
				preg_match_all("/([a-zA-Z])/", $sTimeformat, $placeholderRegs);

				/* Match the date values */
				ereg($targetFormat, $value, $dateRegs);
				
				$finalDate = array();
				
				/* Map date entries to placeholders */
				foreach ($placeholderRegs[0] as $key => $placeholderReg)
				{
					if (isset($dateRegs[$key]))
					{
						$finalDate[$placeholderReg] = $dateRegs[$key+1];	
					}
				}

				/* Assign placeholders + data to the object's member variables */
				foreach ($finalDate as $placeHolder => $value)
				{
					switch ($placeHolder)
					{
						case "d": 
							$this->_iDay = $value;
							break;
						case "m":
							$this->_iMonth = $value;
							break;
						case "Y":
							$this->_iYear = $value;
							break;
						case "H": 
							$this->_iHour = $value;
							break;
						case "i":
							$this->_iMinute = $value;
							break;
						case "s":
							$this->_iSecond = $value;
							break;                            
						default:
							break;
					}
				}
				
				
				break;
			case cDateTime_Custom:
				/* Build a regular expression */
				
				$sourceFormat = str_replace('.', '\.', $this->_sCustomSourceFormat);
				$sourceFormat = str_replace("%d", "([0-9]{2,2})", $sourceFormat);
				$sourceFormat = str_replace("%m", "([0-9]{2,2})", $sourceFormat);
				$sourceFormat = str_replace("%Y", "([0-9]{4,4})", $sourceFormat);
				
				/* Match the placeholders */
				preg_match_all("/(%[a-zA-Z])/", $this->_sCustomSourceFormat, $placeholderRegs);

				
				/* Match the date values */
				ereg($sourceFormat, $value, $dateRegs);
				
				$finalDate = array();
				
				/* Map date entries to placeholders */
				foreach ($placeholderRegs[0] as $key => $placeholderReg)
				{
					if (isset($dateRegs[$key]))
					{
						$finalDate[$placeholderReg] = $dateRegs[$key+1];	
					}
				}

				/* Assign placeholders + data to the object's member variables */
				foreach ($finalDate as $placeHolder => $value)
				{
					switch ($placeHolder)
					{
						case "%d": 
							$this->_iDay = $value;
							break;
						case "%m":
							$this->_iMonth = $value;
							break;
						case "%Y":
							$this->_iYear = $value;
							break;
						default:
							break;
					}
				}
				break;
			default:
				break;
		}	
	}
	
	function get ($iOverrideFormat = false)
	{
		if ($iOverrideFormat !== false)
		{
			$iFormat = $iOverrideFormat;	
		} else {
			$iFormat = $this->_cSourceFormat;
		}
		
				
		switch ($iFormat)
		{
			case cDateTime_ISO:
				$sTemporaryTimestamp = mktime($this->_iHour, $this->_iMinute, $this->_iSecond , $this->_iMonth, $this->_iDay, $this->_iYear);
				return date("Y-m-d H:i:s", $sTemporaryTimestamp);
				break;
			case cDateTime_UNIX:
				$sTemporaryTimestamp = mktime($this->_iHour, $this->_iMinute, $this->_iSecond , $this->_iMonth, $this->_iDay, $this->_iYear);
				return ($sTemporaryTimestamp);
				break;
			case cDateTime_Custom:
				return strftime($this->_sCustomSourceFormat, mktime($this->_iHour, $this->_iMinute, $this->_iSecond, $this->_iMonth, $this->_iDay, $this->_iYear));
				break;
			case cDateTime_MySQL:
				$sTemporaryTimestamp = mktime($this->_iHour, $this->_iMinute, $this->_iSecond , $this->_iMonth, $this->_iDay, $this->_iYear);
				return date("YmdHis", $sTemporaryTimestamp);
				break;
			default:
				cError(__FILE__, __LINE__, "Not supported yet");
				break;
		}	
	}
	
	function render ($iOverrideFormat = false)
	{
		if ($iOverrideFormat !== false)
		{
			$iFormat = $iOverrideFormat;	
		} else {
			$iFormat = $this->_cTargetFormat;	
		}
		
		switch ($iFormat)
		{
			case cDateTime_Locale_TimeOnly:
				$sTimeformat = getEffectiveSetting("backend", "timeformat_time", "H:i:s");
				
				return date($sTimeformat, mktime($this->_iHour, $this->_iMinute, $this->iSecond, $this->_iMonth, $this->_iDay, $this->_iYear));

			case cDateTime_Locale_DateOnly:
				$sTimeformat = getEffectiveSetting("backend", "timeformat_date", "Y-m-d");
				
				return date($sTimeformat, mktime($this->_iHour, $this->_iMinute, $this->iSecond, $this->_iMonth, $this->_iDay, $this->_iYear));
			case cDateTime_Locale:
				$sTimeformat = getEffectiveSetting("backend", "timeformat", "Y-m-d H:i:s");
				
				return date($sTimeformat, mktime($this->_iHour, $this->_iMinute, $this->iSecond, $this->_iMonth, $this->_iDay, $this->_iYear));
			case cDateTime_Custom:
				return strftime($this->_sCustomTargetFormat, mktime($this->_iHour, $this->_iMinute, $this->_iSecond, $this->_iMonth, $this->_iDay, $this->_iYear));

				break;
		}	

	}
	
	function parse ($value)
	{
		if ($value == "")
		{	return;
		}
		switch ($this->_cTargetFormat)
		{
			case cDateTime_ISO:
				$sTemporaryTimestamp = strtotime($value);
				$this->_iYear =	date("Y", $sTemporaryTimestamp);
				$this->_iMonth = date("m", $sTemporaryTimestamp);				
				$this->_iDay = date("d", $sTemporaryTimestamp);
				$this->_iHour = date("H", $sTemporaryTimestamp);
				$this->_iMinute = date("i", $sTemporaryTimestamp);
				$this->_iSecond = date("s", $sTemporaryTimestamp);								
				break;
			case cDateTime_Locale_DateOnly:
				$sTimeformat = getEffectiveSetting("backend", "timeformat_date", "Y-m-d");
				
				$targetFormat = str_replace('.', '\.', $sTimeformat);
				$targetFormat = str_replace("d", "([0-9]{2,2})", $targetFormat);
				$targetFormat = str_replace("m", "([0-9]{2,2})", $targetFormat);
				$targetFormat = str_replace("Y", "([0-9]{4,4})", $targetFormat);
				
				/* Match the placeholders */
				preg_match_all("/([a-zA-Z])/", $sTimeformat, $placeholderRegs);

				/* Match the date values */
				ereg($targetFormat, $value, $dateRegs);
				
				$finalDate = array();
				
				/* Map date entries to placeholders */
				foreach ($placeholderRegs[0] as $key => $placeholderReg)
				{
					if (isset($dateRegs[$key]))
					{
						$finalDate[$placeholderReg] = $dateRegs[$key+1];	
					}
				}

				/* Assign placeholders + data to the object's member variables */
				foreach ($finalDate as $placeHolder => $value)
				{
					switch ($placeHolder)
					{
						case "d": 
							$this->_iDay = $value;
							break;
						case "m":
							$this->_iMonth = $value;
							break;
						case "Y":
							$this->_iYear = $value;
							break;
						default:
							break;
					}
				}
				
				
				break;
			case cDateTime_Locale:
				$sTimeformat = getEffectiveSetting("backend", "timeformat", "Y-m-d H:i:s");
				
				$targetFormat = str_replace('.', '\.', $sTimeformat);
				$targetFormat = str_replace("d", "([0-9]{2,2})", $targetFormat);
				$targetFormat = str_replace("m", "([0-9]{2,2})", $targetFormat);
				$targetFormat = str_replace("Y", "([0-9]{4,4})", $targetFormat);
				
				/* Match the placeholders */
				preg_match_all("/(%[a-zA-Z])/", $this->_sCustomTargetFormat, $placeholderRegs);

				/* Match the date values */
				ereg($targetFormat, $value, $dateRegs);
				
				$finalDate = array();
				
				/* Map date entries to placeholders */
				foreach ($placeholderRegs[0] as $key => $placeholderReg)
				{
					if (isset($dateRegs[$key]))
					{
						$finalDate[$placeholderReg] = $dateRegs[$key+1];	
					}
				}

				/* Assign placeholders + data to the object's member variables */
				foreach ($finalDate as $placeHolder => $value)
				{
					switch ($placeHolder)
					{
						case "%d": 
							$this->_iDay = $value;
							break;
						case "%m":
							$this->_iMonth = $value;
							break;
						case "%Y":
							$this->_iYear = $value;
							break;
						default:
							break;
					}
				}
				
				
				break;
			case cDateTime_Custom:
				/* Build a regular expression */
				
				$targetFormat = str_replace('.', '\.', $this->_sCustomTargetFormat);
				$targetFormat = str_replace("%d", "([0-9]{2,2})", $targetFormat);
				$targetFormat = str_replace("%m", "([0-9]{2,2})", $targetFormat);
				$targetFormat = str_replace("%Y", "([0-9]{4,4})", $targetFormat);
				
				/* Match the placeholders */
				preg_match_all("/(%[a-zA-Z])/", $this->_sCustomTargetFormat, $placeholderRegs);

				/* Match the date values */
				ereg($targetFormat, $value, $dateRegs);
				
				$finalDate = array();
				
				/* Map date entries to placeholders */
				foreach ($placeholderRegs[0] as $key => $placeholderReg)
				{
					if (isset($dateRegs[$key]))
					{
						$finalDate[$placeholderReg] = $dateRegs[$key+1];	
					}
				}

				/* Assign placeholders + data to the object's member variables */
				foreach ($finalDate as $placeHolder => $value)
				{
					switch ($placeHolder)
					{
						case "%d": 
							$this->_iDay = $value;
							break;
						case "%m":
							$this->_iMonth = $value;
							break;
						case "%Y":
							$this->_iYear = $value;
							break;
						default:
							break;
					}
				}
				break;
			default:
				break;
		}	
	}
}

?>