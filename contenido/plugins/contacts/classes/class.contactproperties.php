<?php
/**********************************************************************************
* File			:   class.contacttypes.php
* Project		:   Contact Form Administration Plugin
*
* Author		:   Maxim Spivakovsky
*               
* Created		:   28.02.2006
* Modified		:   01.03.2006
*
* © four for business AG, www.4fb.de
***********************************************************************************/

class cContactProperties {

	var $oDBI;
	var $aGetByProperties = array("1=1");

	function cContactProperties ($oDBI)	{
		$this->oDBI = $oDBI;
	}

	function resetGetByProperties() {
		$this->aGetByProperties = array("1=1");
	}
	
	function addGetByProperty($sPropertyName, $sPropertyValue) {
		$this->aGetByProperties[] = $sPropertyName . "='" . urlencode($sPropertyValue) . "'";
	}
	
	function getIdContactProperty($iIdContactType, $sContactProperty) {
        global $cfg;
        
		$sql = "SELECT
					idcontactproperty
				FROM
					".$cfg['tab']['contact_properties']."
				WHERE
					idcontacttype='".$iIdContactType."' AND
					type='".$sContactProperty."'";
		$this->oDBI->query($sql);
		
		if($this->oDBI->num_rows() > 0) {
			$this->oDBI->next_record();
			return $this->oDBI->f("idcontactproperty");
		}
		else {
			return false;
		}
	}

	function getContactProperties($sOrderBy = array("'idcontactproperty'")) {
        global $cfg;
        
		$aExtractedFields = array("idcontacttype", "type", "label", "ordernum", "created", "createdby", "modified", "modifiedby");
		$sql = "SELECT
					*
				FROM
					".$cfg['tab']['contact_properties']."
				WHERE " . (implode(" AND ", $this->aGetByProperties)) . "
				ORDER BY " . (implode(" ,", $sOrderBy));
					
		$this->oDBI->query($sql);
		
		$aResults = array();
		
		if($this->oDBI->num_rows() > 0) {
			while($this->oDBI->next_record()) {
				$iIdRecord = $this->oDBI->f('idcontactproperty');
				foreach($aExtractedFields as $sFieldName) {
					$aResults[$iIdRecord][$sFieldName] = urldecode($this->oDBI->f($sFieldName));
				}
			}
		}
		
		return $aResults;
	}
	
	function getCountContactProperties($iIdContactType) {
        global $cfg;
        
		$sql = "SELECT
					count(idcontactproperty) as count
				FROM
					".$cfg['tab']['contact_properties']."
				WHERE
					idcontacttype='".$iIdContactType."'";
		$this->oDBI->query($sql);
		
		$this->oDBI->next_record();
		
		return $this->oDBI->f("count"); 
	}
	
	function makeContactPropertyType($sLabel) {
		return preg_replace("/[^a-z0-9]+/i", "_", strtolower(capiStrReplaceDiacritics($sLabel)));		
	}
	
	function storeContactProperty($sLabel, $iIdContactType, $sIdAuthor) {
        global $cfg;
        
		$sType = $this->makeContactPropertyType($sLabel);
				
		$sql = "INSERT INTO
					".$cfg['tab']['contact_properties']."
				SET
					idcontactproperty='" . ($this->oDBI->nextid($cfg['tab']['contact_properties'])) . "', 
					idcontacttype='".$iIdContactType."',
					type='".$sType."',
					label='".urlencode($sLabel)."',
					ordernum='".($this->getCountContactProperties($iIdContactType)+1)."',
					created=NOW(),
					createdby='".$sIdAuthor."',
					modified=NOW(),
					modifiedby='".$sIdAuthor."'";
		$this->oDBI->query($sql);
		
		return true;
	}

	function updateAttr($iIdContactProperty, $sAttrName, $sAttrValue) {
        global $cfg;
        
		$sql = "UPDATE
					".$cfg['tab']['contact_properties']."
				SET
					".$sAttrName."='".urlencode($sAttrValue)."'
				WHERE
					idcontactproperty='".$iIdContactProperty."'";
		$this->oDBI->query($sql);
		
		return true;
	} 
	
	function rewriteFailedOrder($iIdContactType, $iOrderNum) {
        global $cfg;
        
		$sql = "UPDATE
					".$cfg['tab']['contact_properties']."
				SET
					ordernum=ordernum-1
				WHERE
					idcontacttype='".$iIdContactType."' AND
					ordernum>" . $iOrderNum;
		$this->oDBI->query($sql);
		
		return true;
	}
	
	function deleteContactProperty($iIdContactProperty) {
        global $cfg;
        
		$sql = "DELETE FROM
					".$cfg['tab']['contact_properties']."
				WHERE
					idcontactproperty='".$iIdContactProperty."'";
		$this->oDBI->query($sql);
		
		return true;
	}

	function deleteContactPropertyByType($iIdContactType) {
        global $cfg;
        
		$sql = "DELETE FROM
					".$cfg['tab']['contact_properties']."
				WHERE
					idcontacttype='".$iIdContactType."'";
		$this->oDBI->query($sql);
		
		return true;
	}
	
	
}
?>