<?php
/*****************************************
* File      :   $RCSfile: class.clientslang.php,v $
* Project   :   Contenido
* Descr     :   Language to client mapping class
* Modified  :   $Date: 2007/05/25 08:06:29 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.clientslang.php,v 1.4 2007/05/25 08:06:29 bjoern.behrens Exp $
******************************************/

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
					" WHERE idclient = '".$idclient."' AND idlang = '".$idlang."'";

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
		}
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
	
	function getProperties()
	{
		/* Runtime on-demand allocation of the properties object */
		if (!is_object($this->properties))
		{
			$this->properties = new PropertyCollection;
			$this->properties->changeClient($this->idclient);
		}
		
		$this->properties->select("itemtype='".$this->primaryKey."' AND itemid='".$this->get($this->primaryKey)."'", "", "type, value ASC");
		
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