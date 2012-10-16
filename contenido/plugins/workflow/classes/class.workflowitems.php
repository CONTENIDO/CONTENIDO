<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Workflow items
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
 *   created 2003-07-18
 *   
 *   $Id: class.workflowitems.php,v 1.3 2006/01/13 15:54:41 timo.hummel Exp $
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


/**
 * Class WorkflowItems
 * Class for workflow item management
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.2
 * @copyright four for business 2003
 */
class WorkflowItems extends ItemCollection {
	
	/**
     * Constructor Function
     * @param string $table The table to use as information source
     */
	function WorkflowItems()
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["workflow_items"], "idworkflowitem");
	}
	
	function loadItem ($itemID)
	{
		$item = new WorkflowItem();
		$item->loadByPrimaryKey($itemID);
		return ($item);
	}

	function delete ($id)
	{
        global $cfg;
		$item = new WorkflowItem;
		$item->loadByPrimaryKey($id);
		$pos = $item->get("position");
		$idworkflow = $item->get("idworkflow");
		$oDb = new DB_contenido();
		
		$this->select("position > $pos AND idworkflow = '".Contenido_Security::escapeDB($idworkflow, $oDb)."'");
		while ($obj = $this->next())
		{
			$obj->setPosition($obj->get("position")-1);
			$obj->store();
		}
        
        $aUserSequencesDelete = array();
        $sSql = 'SELECT idusersequence FROM '.$cfg["tab"]["workflow_user_sequences"].' WHERE idworkflowitem = '.$id.';';
        $oDb->query($sSql);
        while ($oDb->next_record()) {
            array_push($aUserSequencesDelete, Contenido_Security::escapeDB($oDb->f('idusersequence'), $oDb));
        }

        $sSql = 'DELETE FROM '.$cfg["tab"]["workflow_actions"].' WHERE idworkflowitem = '.Contenido_Security::escapeDB($id, $oDb).';';
        $oDb->query($sSql);

        $this->updateArtAllocation($id, 1);
        
        if (count($aUserSequencesDelete) > 0) {
            $sSql = 'DELETE FROM '.$cfg["tab"]["workflow_user_sequences"].' WHERE idusersequence in ('.implode(',', $aUserSequencesDelete).');';
            $oDb->query($sSql);
        }
	}
    
    function updateArtAllocation ($idworkflowitem, $delete = false) {
        global $idworkflow, $cfg;
        $oDb = new DB_contenido();
        
        $aUserSequences = array();
        $sSql = 'SELECT idusersequence FROM '.$cfg["tab"]["workflow_user_sequences"].' WHERE idworkflowitem = '.Contenido_Security::escapeDB($idworkflowitem, $oDb).';';

        $oDb->query($sSql);
        while ($oDb->next_record()) {
            array_push($aUserSequences, Contenido_Security::escapeDB($oDb->f('idusersequence'), $oDb));
        }
        
        $aIdArtLang = array();
        if (count($aUserSequences) > 0) {
            $sSql = 'SELECT idartlang FROM '.$cfg["tab"]["workflow_art_allocation"].' WHERE idusersequence in ('.implode(',', $aUserSequences).');';
            $oDb->query($sSql);
            while ($oDb->next_record()) {
                array_push($aIdArtLang, $oDb->f('idartlang'));
            }
            $sSql = 'DELETE FROM '.$cfg["tab"]["workflow_art_allocation"].' WHERE idusersequence in ('.implode(',', $aUserSequences).');';
            $oDb->query($sSql);
        }
        
        if ($delete) {
            parent::delete($idworkflowitem);
        }
        
        foreach ($aIdArtLang as $iIdArtLang) {
            setUserSequence($iIdArtLang, $idworkflow);
        } 
    }
	
	
	function swap ($idworkflow, $pos1, $pos2)
	{
		$this->select("idworkflow = '$idworkflow' AND position = '$pos1'");
		if (($item = $this->next()) === false)
		{
			$this->lasterror = i18n("Swapping items failed: Item doesn't exist", "workflow");
			return false;
		}
		
		$pos1ID = $item->getField("idworkflowitem");

		$this->select("idworkflow = '$idworkflow' AND position = '$pos2'");
		if (($item = $this->next()) === false)
		{
			$this->lasterror = i18n("Swapping items failed: Item doesn't exist", "workflow");
			return false;
		}
		
		$pos2ID = $item->getField("idworkflowitem");
				
		$item = new WorkflowItem();
		$item->loadByPrimaryKey($pos1ID);
		$item->setPosition($pos2);
		$item->store();
		$item->loadByPrimaryKey($pos2ID);
		$item->setPosition($pos1);
		$item->store();
        
        $this->updateArtAllocation($pos1ID);
        $this->updateArtAllocation($pos2ID);
		return (true);
	}
	
	function create ($idworkflow)
	{
		$workflows = new Workflows;
		
		$workflows->select("idworkflow = '$idworkflow'");

		if ($workflows->next() === false)
		{
			$this->lasterror = i18n("Can't add item to workflow: Workflow doesn't exist", "workflow");
			return false;
		}
				
		$this->select("idworkflow = '$idworkflow'","","position DESC","1");
		
		$item = $this->next();
		
		if ($item === false)
		{
			$lastPos = 1;
		} else {
			$lastPos = $item->getField("position") + 1;
		}
		
		$newItem = parent::create();
		if ($newItem->init($idworkflow, $lastPos) === false)
		{
			$this->delete($newItem->getField("idworkflowitem"));
			$this->lasterror = $newItem->lasterror;
			return false;
		}
        
        if ($item === false) {
            $this->updateArtAllocation(0);
        }
		
		return ($newItem);
	}
}

/**
 * Class WorkflowItem
 * Class for a single workflow item
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright four for business 2003
 */
class WorkflowItem extends Item {
	
	/**
     * Constructor Function
     * @param string $table The table to use as information source
     */
	function WorkflowItem()
	{
		global $cfg;
		
		parent::Item($cfg["tab"]["workflow_items"], "idworkflowitem");
	}

	function getStepRights ()
	{
		$idwfi = $this->values["idworkflowitem"];
		$workflowActions = new WorkflowActions;
		
		$actions = WorkflowActions::getAvailableWorkflowActions();
		
		foreach ($actions as $key => $value)
		{
			$rights[$key] = $workflowActions->get($idwfi, $key);
		}
		
		return $rights;
	}

	/**
     * Overridden setField function.
     * @param string $field Void field since we override the usual setField function
     * @param string $value Void field since we override the usual setField function
     */	
	function setField($field, $value)
	{
		if ($this->virgin == true)
		{
			$this->lasterror = i18n("No item loaded", "workflow");
			return false;
		}
		
		if ($field == "idsequence")
		{
			die("You can't set the idsequence field using this method. Use 'create' in the WorkflowItems class.");
		}
		
		if ($field == "idworkflow")
		{
			die("You can't set the workflow ID using this method. Use 'create' in the WorkflowItems class!");
		}
		
		if ($field == "position")
		{
			die("You can't set the position ID using this method. Use 'create' or 'swap' to create or move items!");
		}
		
		if ($field == "idtask" && $value != 0)
		{
			$taskCollection = new WorkflowTasks;
			$taskCollection->select("idtask = '$value'");
			if ($taskCollection->next() === false)
			{
				$this->lasterror = i18n("Requested task doesn't exist, can't assign", "workflow");
				return false;
			}
		}

		parent::setField($field, $value);
	}
	
	/**
     * init initializes a new wf_items entry. Should
	 * only be called by the create function.
     * @param int $idworkflow The workflow to set the item to
     */	
	function init ($idworkflow, $idposition)
	{
		global $cfg;
		
		$workflows = new Workflows;
		
		$workflows->select("idworkflow = '$idworkflow'");
		
		if ($workflows->next() === false)
		{
			$this->lasterror = i18n("Workflow doesn't exist", "workflow");
			return false;
		}
		
		$workflowItems = new WorkflowItems;
		$workflowItems->select("position = '$idposition' AND idworkflow = '$idworkflow'");
		if ($workflowItems->next())
		{
			$this->lasterror = i18n("Position in this workflow already exists.", "workflow");
			return false;
		}
		
		parent::setField("idworkflow", $idworkflow);
		parent::setField("position", $idposition);
		parent::store();
		return true;
	}	

	/**
     * setPosition Sets the position for an item. Should only be 
	 * called by the "swap" function
     * @param int $idposition The new position ID
     */	
	function setPosition ($idposition)
	{
		parent::setField("position", $idposition);
		parent::store();
		return true;
	}
}
?>