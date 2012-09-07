<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Language to client mapping class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.4
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2007-05-25
 *
 *   $Id: class.clientslang.php 742 2008-08-27 11:06:12Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude('classes', 'class.security.php');

class cApiClientLanguageCollection extends ItemCollection
{
/**
	 * Constructor
	 */
	function cApiClientLanguageCollection()
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["clients_lang"], "idclientslang");
		$this->_setItemClass("cApiClientLanguage");
	}	
}

class cApiClientLanguage extends Item
{
	var $idclient;
	
	/**
	 * Constructor
	 *
	 * @param integer idclientslang	If specified, load item
	 * @param integer idclient		If idclient and idlang specified, load item; ignored, if idclientslang specified
	 * @param integer idlang		If idclient and idlang specified, load item; ignored, if idclientslang specified
	 */
	function cApiClientLanguage ($idclientslang = false, $idclient = false, $idlang = false)
	{
		global $cfg;
		parent::Item($cfg["tab"]["clients_lang"], "idclientslang");
		
		if ($idclientslang !== false)
		{
			$this->loadByPrimaryKey($idclientslang);	
		} else if ($idclient !== false && $idlang !== false)
		{
			/* One way, but the other should be faster
			$oCollection = new cApiClientLanguageCollection;
			$oCollection->setWhere("idclient", $idclient);
			$oCollection->setWhere("idlang", $idlang);
			$oCollection->query();
			
			if ($oItem = $oCollection->next())
			{ 
				$this->loadByPrimaryKey($oItem->get($oItem->primaryKey));
			} */ 
			
			$sSQL = "SELECT ".$this->primaryKey." FROM ".$this->table.
					#" WHERE idclient = '".$idclient."' AND idlang = '".$idlang."'";
					" WHERE idclient = '" . Contenido_Security::escapeDB($idclient, $this->db) . "' AND idlang = '" . Contenido_Security::escapeDB($idlang, $this->db) . "'";

			/* Query the database */
			$this->db->query($sSQL);
			
			if ($this->db->next_record()) {
				$this->loadByPrimaryKey($this->db->f($this->primaryKey));
			}
		}
	}
	
	function loadByPrimaryKey ($iID)
	{
		if (parent::loadByPrimaryKey($iID) == true)
		{
			$this->idclient = $this->get("idclient");
            return true;
		}
        return false;
	}
	
	/**
	 * Set client property
	 *
	 * @param type	 	mixed Type of the data to store (arbitary data)
 	 * @param name		mixed Entry name
	 * @param value		mixed Value
	 */
	function setProperty($mType, $mName, $mValue)
	{
		/* Runtime on-demand allocation of the properties object */
		if (!is_object($this->properties))
		{
			$this->properties = new PropertyCollection;
			$this->properties->changeClient($this->idclient);
		}
		$this->properties->setValue($this->primaryKey, $this->get($this->primaryKey), $mType, $mName, $mValue);
	}
	
	function getProperty($mType, $mName)
	{
		/* Runtime on-demand allocation of the properties object */
		if (!is_object($this->properties))
		{
			$this->properties = new PropertyCollection;
			$this->properties->changeClient($this->idclient);
		}
		
		return $this->properties->getValue($this->primaryKey, $this->get($this->primaryKey), $mType, $mName);
	}
	
	function deleteProperty($idprop)
	{
		/* Runtime on-demand allocation of the properties object */
		if (!is_object($this->properties))
		{
			$this->properties = new PropertyCollection;
			$this->properties->changeClient($this->idclient);
		}
		
		$this->properties->delete($idprop);
	}
    
    function getPropertiesByType($mType)
	{
		// Runtime on-demand allocation of the properties object
		if (!is_object($this->properties))
		{
			$this->properties = new PropertyCollection;
			$this->properties->changeClient($this->idclient);
		}
		
		return $this->properties->getValuesByType('idclientslang', $this->idclient, $mType);
	}
	
	function getProperties()
	{
		/* Runtime on-demand allocation of the properties object */
		if (!is_object($this->properties))
		{
			$this->properties = new PropertyCollection;
			$this->properties->changeClient($this->idclient);
		}
		
		$this->properties->select("itemtype='".Contenido_Security::escapeDB($this->primaryKey, $this->db)."' AND itemid='".Contenido_Security::escapeDB($this->get($this->primaryKey), $this->db)."'", "", "type, value ASC");
		
		if ($this->properties->count() > 0)
		{
			$aArray = array();
			
			while ($oItem = $this->properties->next())
			{
				$aArray[$oItem->get('idproperty')]['type']	= $oItem->get('type');
				$aArray[$oItem->get('idproperty')]['name']	= $oItem->get('name');
				$aArray[$oItem->get('idproperty')]['value']	= $oItem->get('value');
			}
			
			return $aArray;
		} else
		{
			return false;	
		}
	}
}
?>