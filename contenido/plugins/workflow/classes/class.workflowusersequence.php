<?php

/*****************************************
* File      :   $RCSfile: class.workflowusersequence.php,v $
* Project   :   Contenido Workflow
* Descr     :   Workflow management class
*
* Author    :   $Author: timo.hummel $
*               
* Created   :   18.07.2003
* Modified  :   $Date: 2006/01/13 15:54:41 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.workflowusersequence.php,v 1.3 2006/01/13 15:54:41 timo.hummel Exp $
******************************************/


/**
 * Class WorkflowUserSequences
 * Class for workflow user sequence management
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.2
 * @copyright four for business 2003
 */
class WorkflowUserSequences extends ItemCollection {
	
	/**
     * Constructor Function
     * @param string $table The table to use as information source
     */
	function WorkflowUserSequences()
	{
		global $cfg;
		
		parent::ItemCollection($cfg["tab"]["workflow_user_sequences"], "idusersequence");
	}
	
	function loadItem ($itemID)
	{
		$item = new WorkflowUserSequence();
		$item->loadByPrimaryKey($itemID);
		return ($item);
	}
	
	function delete ($id)
	{
        global $cfg, $idworkflow;
        
		$item = new WorkflowUserSequence;
		$item->loadByPrimaryKey($id);
        
		$pos = $item->get("position");
		$idworkflowitem = $item->get("idworkflowitem");
		$this->select("position > $pos AND idworkflowitem = '$idworkflowitem'");
		while ($obj = $this->next())
		{
			$pos = $obj->get("position") -1;
			$obj->setPosition($pos);
			$obj->store();
		}

        parent::delete($id);
        
        $this->updateArtAllocation($id);
	}
	
    function updateArtAllocation ($idusersequence) {
        global $idworkflow, $cfg;
        $oDb = new DB_contenido();
        
        $aIdArtLang = array();
		$sSql = 'SELECT idartlang FROM '.$cfg["tab"]["workflow_art_allocation"].' WHERE idusersequence = '.$idusersequence.';';
        $oDb->query($sSql);
        while ($oDb->next_record()) {
            array_push($aIdArtLang, $oDb->f('idartlang'));
        }
        
        $sSql = 'DELETE FROM '.$cfg["tab"]["workflow_art_allocation"].' WHERE idusersequence = '.$idusersequence.';';
        $oDb->query($sSql);
        
        
        foreach ($aIdArtLang as $iIdArtLang) {
            setUserSequence($iIdArtLang, $idworkflow);
        } 
    }
    
	function create ($idworkflowitem)
	{
		global $auth, $client, $idworkflow;
		$newitem = parent::create();
		
		$workflowitems = new WorkflowItems;
		if (!$workflowitems->exists($idworkflowitem))
		{
			$this->delete($newitem->getField("idusersequence"));
			$this->lasterror = i18n("Workflow item doesn't exist. Can't create entry.", "workflow");
			return false;
		}
		
		$this->select("idworkflowitem = '$idworkflowitem'","","position DESC","1");
		
		$item = $this->next();
		
		if ($item === false)
		{
			$lastPos = 1;
		} else {
			$lastPos = $item->getField("position") + 1;
		}
		
		$newitem->setWorkflowItem($idworkflowitem);
		$newitem->setPosition($lastPos);
		$newitem->store();
        
		return ($newitem);
	}
	
	function swap ($idworkflowitem, $pos1, $pos2)
	{
		$this->select("idworkflowitem = '$idworkflowitem' AND position = '$pos1'");
		if (($item = $this->next()) === false)
		{
			$this->lasterror = i18n("Swapping items failed: Item doesn't exist", "workflow");
			return false;
		}
		
		$pos1ID = $item->getField("idusersequence");

		$this->select("idworkflowitem = '$idworkflowitem' AND position = '$pos2'");
		if (($item = $this->next()) === false)
		{
			$this->lasterror(i18n("Swapping items failed: Item doesn't exist", "workflow"));
			return false;
		}
		
		$pos2ID = $item->getField("idusersequence");
				
		$item = new WorkflowUserSequence();
		$item->loadByPrimaryKey($pos1ID);
		$item->setPosition($pos2);
		$item->store();
		$item->loadByPrimaryKey($pos2ID);
		$item->setPosition($pos1);
		$item->store();
        
        $this->updateArtAllocation($pos2ID);
        $this->updateArtAllocation($pos1ID);
        
		return (true);
	}	
}

/**
 * Class WorkflowUserSequence
 * Class for a single workflow item
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright four for business 2003
 */
class WorkflowUserSequence extends Item {
	
	/**
     * Constructor Function
     * @param string $table The table to use as information source
     */
	function WorkflowUserSequence()
	{
		global $cfg;
		
		parent::Item($cfg["tab"]["workflow_user_sequences"], "idusersequence");
	}
	

	/**
     * Override setField Function to prevent that somebody modifies
	 * idsequence.
     * @param string $field Field to set
     * @param string $valie Value to set
     */
	function setField($field, $value)
	{
		global $cfg;
		
		switch ($field)
		{
			case "idworkflowitem":
				die("Please use create to modify idsequence. Direct modifications are not allowed");
			case "idusersequence":
				die("Please use create to modify idsequence. Direct modifications are not allowed");
			case "position":
				die("Please use create and swap to set the position. Direct modifications are not allowed");
			case "iduser":
				if ($value != 0)
				{
    				$db = new DB_Contenido;
        			$sql = "SELECT user_id FROM " . $cfg["tab"]["phplib_auth_user_md5"] .
        			       " WHERE user_id = '$value'";
        			$db->query($sql);
        			
        			if (!$db->next_record())
        			{
        				$sql = "SELECT group_id FROM " . $cfg["tab"]["groups"] .
        				       " WHERE group_id = '$value'";
        				       
        				$db->query($sql);
        				if (!$db->next_record())
        				{
        					$this->lasterror = i18n("Can't set user_id: User or group doesn't exist", "workflow");
        					return false;
        				}
        			}
                    $idusersquence = parent::getField('idusersequence');
				}

		}
		
		parent::setField($field, $value);	
        if ($idusersquence) {
            WorkflowUserSequences::updateArtAllocation(0);
        }
	}	

	/**
     * Returns the associated workflowItem for this user sequence
     * @param none
     */
	function getWorkflowItem ()
	{
		if (!$this->virgin)
		{
			$workflowItem = new WorkflowItem;
			$workflowItem->loadByPrimaryKey($this->values["idworkflowitem"]);
			return ($workflowItem);
		} else {
			return false;
		}
	}
		
	/**
     * Interface to set idworkflowitem. Should only be called by "create".
     * @param string $value The value to set
     */
	function setWorkflowItem($value)
	{
		parent::setField("idworkflowitem", $value);
	}	

	/**
     * Interface to set idworkflowitem. Should only be called by "create".
     * @param string $value The value to set
     */
	function setPosition($value)
	{
		parent::setField("position", $value);
	}		
}
?>