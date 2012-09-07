<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido Frontend list
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.0
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-10-09
 *   modified 2008-06-25, Frederic Schneider, add security fix
 *
 *   $Id: functions.frontend.list.php 309 2008-06-26 10:06:56Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

/**
 * Class FrontendList
 * Class for scrollable frontend lists
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 */
class FrontendList
{
	/**
     * Wrap for a single item
     * @var string
     */
	var $itemwrap;
	
	/**
     * Wrap for table start
     * @var string
     */	
	var $startwrap;
	
	/**
     * Wrap for table end
     * @var string
     */	
	var $endwrap;
	
	/**
     * Data container
     * @var array
     */	
	var $data = Array();
	
	/**
     * Number of records displayed per page
     * @var string
     */	
	var $resultsPerPage;

	/**
     * Start page
     * @var string
     */	
	var $listStart;
	

	/**
     * Creates a new FrontendList object.
 	 *
	 * The placeholder for item wraps are the same as for
	 * sprintf. See the documentation for sprintf.
	 * 
	 * Caution: Make sure that percentage signs are written as %%.
	 *
     * @param $startwrap	Wrap for the list start
	 * @param $endwrap		Wrap for the list end
	 * @param $itemwrap		Wrap for a single item
     */	
	function FrontendList ($startwrap, $endwrap, $itemwrap)
	{
		$this->resultsPerPage = 0;
		$this->listStart = 1;
		
		$this->itemwrap = $itemwrap;
		$this->startwrap = $startwrap;
		$this->endwrap = $endwrap;	
	}

	/**
     * Sets data.
 	 *
	 * Note: This function eats as many parameters as you specify.
	 * 
	 * Example:
	 * $obj->setData(0, "foo", "bar");
	 *
	 * Make sure that the amount of parameters stays the same for all
	 * setData calls in a single object.
	 * 
     * @param $index	Numeric index
	 * @param ...	Additional parameters (data)
     */		
	function setData ($index)
	{
		
		$numargs = func_num_args();
		
		for ($i=1;$i<$numargs;$i++)
		{
			$this->data[$index][$i] = func_get_arg($i);
		}
	}

	/**
     * Sets the number of records per page.
	 * 
     * @param $numresults	Amount of records per page
     */			
	function setResultsPerPage ($numresults)
	{
		$this->resultsPerPage = $numresults;
	}	

	/**
     * Sets the starting page number.
	 * 
     * @param $startpage	Page number on which the list display starts
     */			
	function setListStart ($startpage)
	{
		$this->listStart = $startpage;
	}

	/**
     * Returns the current page.
	 * 
     * @param $none
	 * @returns Current page number
     */		
	function getCurrentPage ()
	{
		if ($this->resultsPerPage == 0)
		{
			return 1;	
		}
		
		return ($this->listStart);
	}
	
	/**
     * Returns the amount of pages.
	 * 
     * @param $none
	 * @returns Amount of pages
     */		
	function getNumPages ()
	{
		return (ceil(count($this->data) / $this->resultsPerPage));
	}	

	/**
     * Sorts the list by a given field and a given order.
	 * 
     * @param $field	Field index
	 * @param $order	Sort order (see php's sort documentation)
     */	
	function sort ($field, $order)
	{
		$this->data = array_csort($this->data, "$field", $order);
	}
	
	/**
     * Field converting facility.
	 * Needs to be overridden in the child class to work properbly.
	 * 
     * @param $field	Field index
	 * @param $value 	Field value
     */		
	function convert ($field, $value)
	{
		return $value;	
	}
	
	/**
     * Outputs or optionally returns 
	 * 
     * @param $return	If true, returns the list
     */			
	function output ($return = false)
	{
		$output = $this->startwrap;
		
		$currentpage = $this->getCurrentPage();
		
		$itemstart = (($currentpage-1)*$this->resultsPerPage)+1;
		
		if ($this->resultsPerPage == 0)
		{
			$itemend = count($this->data) - ($itemstart-1);
		} else {
			$itemend = $currentpage*$this->resultsPerPage;
		}
		
		if ($itemend > count($this->data))
		{
			$itemend = count($this->data);
		}
			
		for ($i=$itemstart;$i<$itemend+1;$i++)
		{
			$items = "";
			
			foreach ($this->data[$i-1] as $key => $value)
			{
				$items .= ", '".addslashes($this->convert($key, $value))."'";
			}
			
			$execute = '$output .= sprintf($this->itemwrap '.$items.');';
			
			eval($execute);

		}
		
		$output .= $this->endwrap;
		
		$output = stripslashes($output);
		
		if ($return == true)
		{
			return $output;
		} else {
			echo $output;
		}
	}
}



?>