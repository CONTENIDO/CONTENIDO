<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Date selector
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.6
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 205-09-15
 *   
 *   $Id: class.widgets.dateselect.php,v 1.6 2005/09/15 12:15:17 timo.hummel Exp $
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


cInclude("classes", "class.htmlelements.php");

class cDropdownDateSelect
{

	/**
	 * Day, month and year selectors
     * @var object
     * @access private
	 */	
	var $_daySelect;
	var $_monthSelect;
	var $_yearSelect;
	
	/**
	 * Order of the elements 
     * @var string
     * @access private
	 */		
	var $_order;
		
	function cDropdownDateSelect ($prefix)
	{
		$this->_daySelect = new cHTMLSelectElement($prefix."_day");
		$this->_monthSelect = new cHTMLSelectElement($prefix."_month");
		$this->_yearSelect = new cHTMLSelectElement($prefix."_year");
		
		/* Fill days */
		for ($day = 1; $day < 32; $day++)
		{
			$days[sprintf("%02d", $day)] = $day;	
		}
		
		$this->_daySelect->autoFill($days);

		/* Fill months */
		for ($month = 1; $month < 13; $month++)
		{
			$months[sprintf("%02d", $month)] = getCanonicalMonth($month);	
		}
		
		$this->_monthSelect->autoFill($months);	
		
		/* Fill years */
		$currentYear = date("Y");
		for ($year = $currentYear-20; $year < $currentYear +20; $year++)
		{
			$years[$year] = $year;	
		}
		
		$this->_yearSelect->autoFill($years);			
		
		$this->setTimestamp(time());
		
		/* Apply old values */
		if (isset($_POST[$prefix."_day"]))
		{
			$this->_daySelect->setDefault($_POST[$prefix."_day"]);
		}

		if (isset($_POST[$prefix."_month"]))
		{
			$this->_monthSelect->setDefault($_POST[$prefix."_month"]);
		}

		if (isset($_POST[$prefix."_year"]))
		{
			$this->_yearSelect->setDefault($_POST[$prefix."_year"]);
		}				
		
		$this->setOrder("dmy");
		
	}
	
	/**
	 * Sets the day, month and year boxes using a timestamp
	 *
	 * @param $timestamp int Timestamp to set
	 */		
	function setTimestamp ($timestamp)
	{
		$day   = date("d", $timestamp);
		$month = date("m", $timestamp);
		$year  = date("Y", $timestamp);

		$this->_daySelect->setDefault($day);
		$this->_monthSelect->setDefault($month);
		$this->_yearSelect->setDefault($year);
		
	}

	/**
	 * Sets the ID for all three select boxes.
	 * 
	 * Note: The parameter id is only the prefix. Your ID is postfixed
	 * by _day, _month and _year for each select box.
	 *
	 * @param $id string Prefix ID to set
	 */			
	function setId ($id)
	{
		$this->_daySelect->setId($id."_day");
		$this->_monthSelect->setId($id."_month");
		$this->_yearSelect->setId($id."_year");	
	}

	/**
	 * Gets the current timestamp
	 *
	 * @param $timestamp int Timestamp to set
	 */			
	function getTimestamp ($prefix = false)
	{
		if (!is_object($this->_daySelect))
		{
			/* Called statically */
    		if (isset($_POST[$prefix."_day"]))
    		{
    			$day = $_POST[$prefix."_day"];
    		}
    
    		if (isset($_POST[$prefix."_month"]))
    		{
    			$month = $_POST[$prefix."_month"];
    		}
    
    		if (isset($_POST[$prefix."_year"]))
    		{
    			$year = $_POST[$prefix."_year"];
    		}			
		} else {
    		$day = $this->_daySelect->getDefault();
    		$month = $this->_monthSelect->getDefault();
    		$year = $this->_yearSelect->getDefault();
		}
		
		return mktime(0,0,0, $month, $day, $year);
	}
	
	/**
	 * Sets the order of the day, month and year boxes
	 *
	 * Pass a string with "d", "m" and "y" in the desired order.
	 *
	 * Example:
	 * $test->setOrder("mdy");
	 *
	 * @param $order string Order with the keys "d", "m" and "y"
	 */		
	function setOrder ($order)
	{
		$this->_order = $order;
	}

	/**
	 * sets the element to a disabled state
	 *
	 * @param none
	 */	
	function setDisabled ($disabled)
	{
		$this->_daySelect->setDisabled($disabled);
		$this->_monthSelect->setDisabled($disabled);
		$this->_yearSelect->setDisabled($disabled);	
	}
	
	/**
	 * Renders the elements
	 *
	 * @param none
	 */		
	function render ()
	{
		$output = "";
		
		for ($char = 0; $char < strlen($this->_order); $char++)
		{
			$mychar = substr($this->_order, $char, 1);
			
			switch ($mychar)
			{
				case "d": $output .= $this->_daySelect->render(); break;
				case "m": $output .= $this->_monthSelect->render(); break;
				case "y": $output .= $this->_yearSelect->render(); break;
				default: break;
			}
		}
		
		return ($output);
	}
	

}

?>