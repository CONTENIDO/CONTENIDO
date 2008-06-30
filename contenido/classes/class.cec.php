<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido Extension Chainer (CEC)
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.0
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
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

class cApiCECRegistry
{
	var $_aChains;
	
	function cApiCECRegistry ()
	{
		$this->_aChains = array();
	}
	
	function registerChain ($sChainName)
	{
		$sChainName = Contenido_Security::escapeDB($sChainName, null);
		
		$aParam = array();
		$iNumArgs = func_num_args();

		for ($iCount = 0; $iCount < $iNumArgs; $iCount++)
		{
			$aParam[$iCount] = func_get_arg($iCount);
		}
		
		$this->_addChain($sChainName, $aParam);
	}
	
	function _addChain ($sChainName, $aParameters)
	{
		if (!is_array($aParameters))
		{
			cWarning(__FILE__, __LINE__, "_addChain received a non-array parameter for aParams!");
			return;	
		}
		$this->_aChains[$sChainName]["parameters"] = $aParameters;
		$this->_aChains[$sChainName]["functions"] = array();
	}
	
	function addChainFunction ($sChainName, $sFunctionName)
	{
		$bError = false;
		$sChainName 	= Contenido_Security::escapeDB($sChainName, null);
		$sFunctionName 	= Contenido_Security::escapeDB($sFunctionName, null);

		/* Check if the chain exists */
		if (!array_key_exists($sChainName, $this->_aChains))
		{
			cWarning(__FILE__, __LINE__, "Chain ".$sChainName." doesn't exist.");	
			$bError = true;
		}
		
		/* Check if the function exists */
		if (!function_exists($sFunctionName))
		{
			cWarning(__FILE__, __LINE__, "Function ".$sFunctionName." doesn't exist, can't add to chain ".$sChainName);	
			$bError = true;
		}
		
		/* Check if an error occured */
		if ($bError == true)
		{
			/* Yes, error occured, return false */
			return false;
		}

		$oChainItem = new pApiCECChainItem($sChainName, $sFunctionName, $this->_aChains[$sChainName]["parameters"]);
		array_push($this->_aChains[$sChainName]["functions"], $oChainItem);
		
		return true;
	}
	
	function getIterator ($sChainName)
	{
		$sChainName = Contenido_Security::escapeDB($sChainName, null);
		cInclude("classes", "class.iterator.php");
		
		$oIterator = new cIterator($this->_aChains[$sChainName]["functions"]);
		
		return ($oIterator);
	}
}

class pApiCECChainItem
{
	var $_sChainName;
	var $_sFunctionName;
	var $_aParameters;
	
	function pApiCECChainItem ($sChainName, $sFunctionName, $aParameters)
	{
		$this->_sChainName 		= Contenido_Security::escapeDB($sChainName, null);
		$this->_sFunctionName 	= Contenido_Security::escapeDB($sFunctionName, null);
		$this->_aParameters 	= $aParameters;
	}
	
	function execute ()
	{
		$args = func_get_args();
		return call_user_func_array($this->_sFunctionName, $args);
	}
}
?>