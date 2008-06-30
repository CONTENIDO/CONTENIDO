<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Root Driver for GenericDB 
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.3
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2005-08-29
 *   modified 2008-05-23 Added Debug_DevNull and Debug_VisibleAdv
 *   
 *   $Id: class.gdb.driver.php,v 1.3 2005/08/29 15:41:07 timo.hummel Exp $
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

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
