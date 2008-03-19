<?php
/*****************************************
* File      :   $RCSfile: class.csv.php,v $
* Project   :   Contenido
* Descr     :   CSV Handling class
* Modified  :   $Date: 2005/09/15 12:24:58 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.csv.php,v 1.4 2005/09/15 12:24:58 timo.hummel Exp $
******************************************/


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