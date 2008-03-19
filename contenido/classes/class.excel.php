<?php
/*****************************************
* File      :   $RCSfile: class.excel.php,v $
* Project   :   Contenido
* Descr     :   Excel Handling class
* Modified  :   $Date: 2005/06/29 11:54:46 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.excel.php,v 1.2 2005/06/29 11:54:46 timo.hummel Exp $
******************************************/
cInclude("pear", "Spreadsheet/Excel/Writer.php");

class ExcelWorksheet
{
	
	var $_data = array();
	var $_title;
	var $_filename;
	
	function ExcelWorksheet ($title, $filename)
	{
		$this->_title = $title;
		$this->_filename = $filename;
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