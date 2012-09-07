<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Excel handling class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.2
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
 *   $Id: class.excel.php 528 2008-07-02 13:29:28Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("pear", "Spreadsheet/Excel/Writer.php");

class ExcelWorksheet
{
	var $_data = array();
	var $_title;
	var $_filename;
	
	function ExcelWorksheet ($title, $filename)
	{
		$this->_title 		= Contenido_Security::escapeDB($title, null);
		$this->_filename 	= Contenido_Security::escapeDB($filename, null);
	}
	
	function setRow ($row)
	{
		$row = Contenido_Security::escapeDB($row, null);
		$args = func_num_args();
		
		for ($arg=1;$arg<$args;$arg++)
		{
			$ma = func_get_arg($arg);
			$this->setCell($row, $arg, $ma);	
		}	
	}
	
	function setCell($row, $cell, $data)
	{
		$row 	= Contenido_Security::escapeDB($row, null);
		$cell 	= Contenido_Security::escapeDB($cell, null);
		$data 	= Contenido_Security::escapeDB($data, null);
		$this->_data[$row][$cell] = $data;
	}
	
	function make ()
	{
				
		$workbook = new Spreadsheet_Excel_Writer();
		$workbook->send($this->_filename);
		
		$worksheet = & $workbook->addWorksheet($this->_title);
		
		foreach ($this->_data as $row => $line)
		{
			foreach ($line as $col => $coldata)
			{
				$worksheet->writeString($row-1, $col-1, $coldata);
			}

		}
		
		$workbook->close();
	}
}
?>