<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Client management class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.12
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2007-06-24
 *
 *   $Id: class.client.php 742 2008-08-27 11:06:12Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


cInclude('classes', 'class.genericdb.php');

class cApiClientCollection extends ItemCollection
{
	/**
	 * Constructor
	 */
	function cApiClientCollection()
	{
		global $cfg;
		cInclude("classes", "contenido/class.clientslang.php");
		
		parent::ItemCollection($cfg['tab']['clients'], 'idclient');
		$this->_setItemClass("cApiClient");
		$this->_setJoinPartner("cApiClientLanguageCollection");
	}
	
	/**
     * getAvailableClients()
     * Returns all clients available in the system
     * @return array   Array with id and name entries
     */
	function getAvailableClients()
	{
        $aClients = array();

		$this->select();

        while ($oItem = $this->next())
        {
			$aNewEntry['name'] = $oItem->get('name');
			$aClients[$oItem->get('idclient')] = $aNewEntry;
        }

        return ($aClients);
	}
	
    /**
     * getAvailableClients()
     * Returns all clients available in the system
     * @return array   Array with id and name entries
     */
	function getAccessibleClients()
    {
    	global $perm;
    	
    	$aClients = array();
    	
    	$this->select();
    	
    	while ($oItem = $this->next())
    	{
    		if ($perm->have_perm_client("client[".$oItem->get('idclient')."]") ||
    			$perm->have_perm_client("admin[".$oItem->get('idclient')."]") ||
    			$perm->have_perm_client())
    		{
    			$aNewEntry['name'] = $oItem->get('name');
    			$aClients[$oItem->get('idclient')] = $aNewEntry;
    		}
    	}
    
    	return ($aClients);
    }
    
    /**
     * getClientname()
     * Returns the clientname of the given clientid
     * @return string  Clientname if found, or emptry string if not.
     */
    function getClientname($idclient)
    {
    	$this->select("idclient='$idclient'");
    	if ($oItem = $this->next())
    	{
    		return $oItem->get('name');
    	} else
    	{
    		return i18n("No client");
    	}
    }
    
    /**
     * hasLanguageAssigned()
     * Returns if the given client has a language
     * @return bool  true if the client has a language
     */
    function hasLanguageAssigned($idclient)
    {
    	global $cfg;
    
    	$db = new DB_Contenido;
    
    	$sql = "SELECT
    				idlang
    			FROM
    			". $cfg["tab"]["clients_lang"]."
    			WHERE
    				idclient = \"".$idclient."\"";
    
    	$db->query($sql);
    	if ($db->next_record())
    	{
    		return (true);
    	} else {
    		return (false);
    	}
    }
    
}

/**
 * Class cApiClientCollection
 * @author Marco Jahn <Marco.Jahn@4fb.de>
 * @version 1.0
 * @copyright four for business 2004
 */
class cApiClient extends Item
{
	var $idclient;
	
	/**
	 * Constructor
	 *
	 * @param integer client to load
	 */
	function cApiClient($idclient = false)
	{
		global $cfg;
		
		cInclude("classes", "contenido/class.clientslang.php");
	
		parent::Item($cfg['tab']['clients'], 'idclient');
		
		if ($idclient !== false)
		{
			$this->loadByPrimaryKey($idclient);	
		}
	}
	
	/**
	 * Static accessor to the singleton instance.
	 * 
	 * @return Object Reference to the singleton instance.
	 */
	public static function getInstance($iClient = false) {
		
		static $oCurrentInstance;
		
		if (!$iClient) {
			/*
			 * Use global $client
			 */
			$iClient = $GLOBALS['client'];
		}
		
		if (!isset($oCurrentInstance[$iClient])) {
			$oCurrentInstance[$iClient] = new cApiClient($iClient);
		}
		
		return $oCurrentInstance[$iClient];
	}
	
	function loadByPrimaryKey ($value)
	{
		if (parent::loadByPrimaryKey($value) == true) 
        {
			$this->idclient = $value;
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
	function setProperty($mType, $mName, $mValue, $mIdproperty = 0)
	{
		// Runtime on-demand allocation of the properties object
		if (!is_object($this->properties))
		{
			$this->properties = new PropertyCollection;
			$this->properties->changeClient($this->idclient);
		}
        
		$this->properties->setValue('clientsetting', $this->idclient, $mType, $mName, $mValue, $mIdproperty);
	}
	
	function getProperty($mType, $mName)
	{
		// Runtime on-demand allocation of the properties object
		if (!is_object($this->properties))
		{
			$this->properties = new PropertyCollection;
			$this->properties->changeClient($this->idclient);
		}
		
		return $this->properties->getValue('clientsetting', $this->idclient, $mType, $mName);
	}
	
	function deleteProperty($idprop)
	{
		// Runtime on-demand allocation of the properties object
		if (!is_object($this->properties))
		{
			$this->properties = new PropertyCollection;
			$this->properties->changeClient($this->idclient);
		}
		
		$this->properties->delete($idprop);
	}
	
	function getProperties()
	{
		// Runtime on-demand allocation of the properties object
		if (!is_object($this->properties))
		{
			$this->properties = new PropertyCollection;
			$this->properties->changeClient($this->idclient);
		}
		
		$this->properties->select("itemid='".$this->idclient."' AND itemtype='clientsetting'", "", "type, name, value ASC");
		
		if ($this->properties->count() > 0)
		{
			$aArray = array();
			
			while ($oItem = $this->properties->next())
			{
				$aArray[$oItem->get('idproperty')]['type'] = $oItem->get('type');
				$aArray[$oItem->get('idproperty')]['name'] = $oItem->get('name');
				$aArray[$oItem->get('idproperty')]['value'] = $oItem->get('value');
			}
			
			return $aArray;
		} else
		{
			return false;	
		}
	}
	
	function getPropertiesByType($mType)
	{
		// Runtime on-demand allocation of the properties object
		if (!is_object($this->properties))
		{
			$this->properties = new PropertyCollection;
			$this->properties->changeClient($this->idclient);
		}
		
		return $this->properties->getValuesByType('clientsetting', $this->idclient, $mType);
	}
   
	function hasLanguages ()
	{
		$cApiClientLanguageCollection = new cApiClientLanguageCollection;
		$cApiClientLanguageCollection->setWhere("idclient", $this->get("idclient"));
		$cApiClientLanguageCollection->query();
		
		if ($cApiClientLanguageCollection->next())
		{
			return true;	
		} else {
			return false;	
		}
	}
}

?>