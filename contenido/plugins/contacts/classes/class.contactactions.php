<?

class cContactActions {
	var $oDBI;
	var $cfg;
	var $iIdActionStart = 100100;
	var $iIdActionEnd = 101000;
	var $sActionPrefix = "contact_plugin_";
	
	function cContactActions($oDBI, $cfg) {
		$this->oDBI = $oDBI;
		$this->cfg = $cfg;
	}
	
	function getNextIdAction() {
		$sql = "SELECT
					MAX(idaction)+1 as nextidaction
				FROM
					".$this->cfg['sql']['sqlprefix']."_actions
				WHERE
					idaction>='".$this->iIdActionStart."' AND
					idaction<'".$this->iIdActionEnd."'";
		$this->oDBI->query($sql);
		
		$this->oDBI->next_record();
		
		$iIdActionNext = $this->oDBI->f("nextidaction"); 
		
		return $iIdActionNext ? $iIdActionNext : $this->iIdActionStart;
	}
	
	function storeAction($area, $sName) {
		$iIdActionNext = $this->getNextIdAction();
		
		$sql = "INSERT INTO
					".$this->cfg['sql']['sqlprefix']."_actions
				SET
					idaction='".$iIdActionNext."',
					idarea='".$area."',
					name='".$sName."',
					relevant='1'";
		$this->oDBI->query($sql);
		
		return $iIdActionNext;		
	} 
	
	function deleteActionByName($sName) {
		$sql = "DELETE FROM
					".$this->cfg['sql']['sqlprefix']."_actions
				WHERE
					name='".$sName."'";
		$this->oDBI->query($sql);
		
		return true;
	}
	
	function getAvalibleActions() {
		$sql = "SELECT
					name
				FROM
					".$this->cfg['sql']['sqlprefix']."_actions
				WHERE
					idaction>=".$this->iIdActionStart." AND
					idaction<".$this->iIdActionEnd;
		$this->oDBI->query($sql);
		
		$aResults = array();
		
		if($this->oDBI->num_rows() > 0) {
			while($this->oDBI->next_record()) {
				$aResults[] = $this->oDBI->f("name");
			}
		}
		
		return $aResults;
	}
}

?>