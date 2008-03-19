<?php
/**
 * file PropertyExtended.php
 * 
 * @version	2.0.0
 * @author Willi Man
 * @copyright four for business AG <www.4fb.de>
 * @created 10.18.2005
 * @modified 10.18.2005
 */

class PropertyExtended
{
		
	/**
     * Constructor
     * 
     * @param object $oDBInstance instance of class DB_Contenido
     * @param array $globalConfig
     * @param int $iClient
     */
	function PropertyExtended ($iClient, &$oDBInstance, &$globalConfig, &$globalAuth)
	{
		$this->oDBInstance = $oDBInstance;
		$this->globalConfig = $globalConfig;
		$this->globalAuth = $globalAuth;
		$this->iClient = $iClient;
		$this->sTable = $this->globalConfig['tab']['properties'];
		$this->bDebug = false;
	}
	
	/**
	 * Get property value.
	 *
	 * @return string 
	 */	
	function getPropertyValue ($sItemType, $sItemId, $sType, $sName)
	{
		
		$sqlString = "
		SELECT 
			value
		FROM 
			".$this->sTable." 
		WHERE 
			idclient = ".$this->iClient." AND itemtype = '".$sItemType."' AND itemid = '".$sItemId."' AND type = '".$sType."' AND name = '".$sName."' ";
			
		if ($this->bDebug) {echo "<pre>".$sqlString."</pre>";}
		
		$this->oDBInstance->query($sqlString);
		
		# $this->oDBInstance->Errno returns 0 (zero) if no error occurred.
		if ($this->oDBInstance->Errno == 0)
		{
			$this->oDBInstance->next_record();
			return (urldecode($this->oDBInstance->f('value')));
		}else
		{
			if ($this->bDebug) {echo "<pre>Mysql Error:".$this->oDBInstance->Error."(".$this->oDBInstance->Errno.")</pre>";}
			return ''; # error occurred.
		} 
	}
	
	/**
	 * Get properties by item type
	 *
	 * @return object 
	 */	
	function getPropertiesByItemType ($sItemType)
	{
		
		$sqlString = "
		SELECT 
			*
		FROM 
			".$this->sTable." 
		WHERE 
			idclient = ".$this->iClient." AND itemtype = '".$sItemType."' ";
			
		if ($this->bDebug) {echo "<pre>".$sqlString."</pre>";}
		
		$this->oDBInstance->query($sqlString);
		
		$aResult = array();
		while($oRow = mysql_fetch_object($this->oDBInstance->Query_ID))
		{
			$aResult[] = $oRow;
		}
		return $aResult;
		
		
	}
	
	/**
	 * Get item ids by value.
	 *
 	 * @param  string $sValue
	 * @return array
	 */	
	function getItemIdsByValue ($sValue, $sItemType, $sType)
	{	
		$sqlString = "
		SELECT 
			 itemid
		FROM 
			".$this->sTable." 
		WHERE 
			idclient = ".$this->iClient." AND itemtype = '".$sItemType."' AND type = '".$sType."' AND value = '".urlencode($sValue)."' ";
			
		if ($this->bDebug) {echo "<pre>".$sqlString."</pre>";}
		
		$this->oDBInstance->query($sqlString);
		
		# $this->oDBInstance->Errno returns 0 (zero) if no error occurred.
		if ($this->oDBInstance->Errno == 0)
		{
			$aResult = array();
			while ($this->oDBInstance->next_record())
			{
				$aResult[] = $this->oDBInstance->f('itemid');
			}
			return $aResult;
		}else
		{
			if ($this->bDebug) {echo "<pre>Mysql Error:".$this->oDBInstance->Error."(".$this->oDBInstance->Errno.")</pre>";}
			return array(); # error occurred.
		} 
	}
	
	/**
	 * Get item ids by value and name.
	 *
 	 * @param  string $sValue
	 * @return array 
	 */	
	function getItemIdsByValueAndName ($sValue, $sItemType, $sType, $sName)
	{	
		$sqlString = "
		SELECT 
			 itemid
		FROM 
			".$this->sTable." 
		WHERE 
			idclient = ".$this->iClient." AND itemtype = '".$sItemType."' AND type = '".$sType."' AND name LIKE '%".$sName."%' AND value = '".urlencode($sValue)."' ";
			
		if ($this->bDebug) {echo "<pre>".$sqlString."</pre>";}
		
		$this->oDBInstance->query($sqlString);
		
		# $this->oDBInstance->Errno returns 0 (zero) if no error occurred.
		if ($this->oDBInstance->Errno == 0)
		{
			$aResult = array();
			while ($this->oDBInstance->next_record())
			{
				$aResult[] = $this->oDBInstance->f('itemid');
			}
			return $aResult;
		}else
		{
			if ($this->bDebug) {echo "<pre>Mysql Error:".$this->oDBInstance->Error."(".$this->oDBInstance->Errno.")</pre>";}
			return array(); # error occurred.
		} 
	}		
	
	/**
	 * Check if property exist.
	 *
 	 * @param  string $sName
	 * @return boolean 
	 */	
	function checkProperty ($sItemType, $sItemId, $sType, $sName)
	{
		
		$sqlString = "
		SELECT 
			value
		FROM 
			".$this->sTable." 
		WHERE 
			idclient = ".$this->iClient." AND itemtype = '".$sItemType."' AND itemid = '".$sItemId."' AND type = '".$sType."' AND name = '".$sName."' ";
			
		if ($this->bDebug) {echo "<pre>".$sqlString."</pre>";}
		
		$this->oDBInstance->query($sqlString);
		
		# $this->oDBInstance->Errno returns 0 (zero) if no error occurred.
		if ($this->oDBInstance->Errno == 0)
		{
			if ($this->oDBInstance->next_record())
			{
				return true;
			}else
			{
				return false;
			}
		}else
		{
			if ($this->bDebug) {echo "<pre>Mysql Error:".$this->oDBInstance->Error."(".$this->oDBInstance->Errno.")</pre>";}
			return false; # error occurred.
		} 
		
	}	
	
	/**
	 * Set property.
	 *
 	 * @param  string $sName
	 * @param  string $sValue
	 * @return string 
	 */	
	function setProperty ($sItemType, $sItemId, $sType, $sName, $sValue)
	{
		$sValue = urlencode($sValue);
		
		$sqlString = "   
  		INSERT INTO ".$this->sTable."
        	(idproperty, idclient, itemtype, itemid, type, name, value, author, created, modified, modifiedby) 
		VALUES 
			(".$this->oDBInstance->nextid($this->sTable).", ".$this->iClient.", '".$sItemType."', '".$sItemId."', '".$sType."', '".$sName."', '".$sValue."', '".$this->globalAuth->auth["uid"]."', NOW(), NOW(), '".$this->globalAuth->auth["uid"]."')"; 
          	
		if ($this->bDebug) {echo "<pre>".$sqlString."</pre>";}
		
		$this->oDBInstance->query($sqlString);
		
		# $this->oDBInstance->Errno returns 0 (zero) if no error occurred.
		if ($this->oDBInstance->Errno == 0)
		{
			return true;
		}else
		{
			# error occurred.
			if ($this->bDebug) {echo "<pre>Mysql Error:".$this->oDBInstance->Error."(".$this->oDBInstance->Errno.")</pre>";}
			return false; 
		} 
	}	
	
	/**
	 * Chang property.
	 * 
 	 * @param  string $sName
	 * @param  string $sValue
	 * @return string 
	 */	
	function changeProperty ($sItemType, $sItemId, $sType, $sName, $sValue)
	{
	
		$sValue = urlencode($sValue);
		
		$sqlString = "   
  		UPDATE 
			".$this->sTable."
        SET 
			value = '".$sValue."',
			modifiedby = '".$this->globalAuth->auth["uid"]."',		
			modified = NOW()
		WHERE 
			idclient = ".$this->iClient." AND
			itemtype = '".$sItemType."' AND
			itemid = '".$sItemId."' AND		
			type = '".$sType."' AND
			name = '".$sName."' ";
		
		if ($this->bDebug) {echo "<pre>".$sqlString."</pre>";}
		
		$this->oDBInstance->query($sqlString);
		
		# $this->oDBInstance->Errno returns 0 (zero) if no error occurred.
		if ($this->oDBInstance->Errno == 0)
		{
			return true;
		}else
		{
			# error occurred.
			if ($this->bDebug) {echo "<pre>Mysql Error:".$this->oDBInstance->Error."(".$this->oDBInstance->Errno.")</pre>";}
			return false; 
		} 
	}		
	
}

?>