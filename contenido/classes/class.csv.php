<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * CSV Handling class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.4
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *
 *   $Id: class.csv.php 528 2008-07-02 13:29:28Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

class CSV
{
	
	var $_data = array();
	var $_delimiter;
	
	function CSV ()
	{
		$this->_delimiter = ";";
	}
	
	function setRow ($row)
	{
		$args = func_num_args();
		
		for ($arg=1;$arg<$args;$arg++)
		{
			$ma = func_get_arg($arg);
			$this->setCell($row, $arg, $ma);	
		}	
	}
	
	function setCell($row, $cell, $data)
	{
		$row 	= Contenido_Security::escapeDB($row);
		$cell 	= Contenido_Security::escapeDB($cell);
		$data 	= Contenido_Security::escapeDB($data);
		
		$data = str_replace('"', '""', $data);
		$this->_data[$row][$cell] = '"'.$data.'"';
	}
	
	function setDelimiter ($delimiter)
	{
		$this->_delimiter = $delimiter;
	}
	
	function make ()
	{
		foreach ($this->_data as $row => $line)
		{
			$out .= implode($this->_delimiter, $line);
			$out .= "\r\n";	
		}
		
		return $out;
	}
}
?>