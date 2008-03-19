<?php
/*****************************************
* File      :   $RCSfile: class.gdb.driver.php,v $
* Project   :   Contenido
* Descr     :   Root Driver for GenericDB 
* Modified  :   $Date: 2005/08/29 15:41:07 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.gdb.driver.php,v 1.3 2005/08/29 15:41:07 timo.hummel Exp $
******************************************/

class gdbDriver
{
	var $_sEncoding;
	
	var $_oItemClassInstance;
	
	function gdbDriver ()
	{}
	
	function setEncoding ($sEncoding)
	{
		$this->_sEncoding = $sEncoding;
	}
	
	function setItemClassInstance ($oInstance)
	{
		$this->_oItemClassInstance = $oInstance;
	}
	
	function buildJoinQuery ($destinationTable, $destinationClass, $destinationPrimaryKey, $sourceClass, $primaryKey)
	{
		
	}
	
	function buildOperator ($sField, $sOperator, $sRestriction)
	{
	}
}

?>
