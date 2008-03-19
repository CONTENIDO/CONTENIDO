<?php

class cContactData {
	var $oDBI;
	var $aGetByProperties = array();
	
	function cContactData($oDBI) {
		$this->oDBI = $oDBI;
	}

	function resetGetByProperties() {
		$this->aGetByProperties = array();
	}
	
	function getNextIdContactDataGroup() {
		return $this->oDBI->nextid($cfg['tab']['contact_data']);
	}

	function storeContactData($iIdContactDataGroup, $iIdContactProperty, $sContent) {
        global $cfg;
        
		$sql = "INSERT INTO
					".$cfg['tab']['contact_data']."
				SET
					idcontactdata='".($this->oDBI->nextid($cfg['tab']['contact_data']))."',
					idcontactdatagroup='".$iIdContactDataGroup."',
					idcontactproperty='".$iIdContactProperty."',
					content='".urlencode($sContent)."',
					created=NOW()";
		$this->oDBI->query($sql);
		
		return true;
	}	
	
	function getContactData($iIdContactType, $sOrderBy = "c.idcontactdatagroup DESC, b.ordernum ASC") {
        global $cfg;
        
		$sql = "SELECT
					c.idcontactdatagroup,
					c.idcontactproperty,
					c.content
				FROM
					".$cfg['tab']['contact_types']." as a,
					".$cfg['tab']['contact_properties']." as b,
					".$cfg['tab']['contact_data']." as c
				WHERE
					a.idcontacttype='".$iIdContactType."' AND
					b.idcontacttype=a.idcontacttype AND
					c.idcontactproperty=b.idcontactproperty
				ORDER BY " . $sOrderBy;
		$this->oDBI->query($sql);
		
		$aResults = array();
		
		if($this->oDBI->num_rows() > 0) {
			while($this->oDBI->next_record()) {
				$aResults[$this->oDBI->f("idcontactdatagroup")][$this->oDBI->f("idcontactproperty")] = urldecode($this->oDBI->f("content")); 
			}
		}
		
		return $aResults;
	}
	
	function deleteContactDataGroup($iIdContactDataGroup) {
        global $cfg;
        
		$sql = "DELETE FROM
					".$cfg['tab']['contact_data']."
				WHERE
					idcontactdatagroup='".$iIdContactDataGroup."'";
		$this->oDBI->query($sql);
		
		return true;
	}
	
	function deleteContactDataByProperty($iIdContactProperty) {
        global $cfg;
        
		$sql = "DELETE FROM
					".$cfg['tab']['contact_data']."
				WHERE
					 idcontactproperty='".$iIdContactProperty."'";
		$this->oDBI->query($sql);
		
		return true;
	}

}
?>