<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Workflow management class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    CONTENIDO Plugins
 * @subpackage Workflow
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
 *   $Id$
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


/**
 * Class WorkflowUserSequences
 * Class for workflow user sequence management
 * @package    CONTENIDO Plugins
 * @subpackage Workflow
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.2
 * @copyright four for business 2003
 */
class WorkflowUserSequences extends ItemCollection {
	
	/**
     * Constructor Function
     * @param string $table The table to use as information source
     */
	function __construct()
	{
		global $cfg;
		parent::__construct($cfg["tab"]["workflow_user_sequences"], "idusersequence");
        $this->_setItemClass("WorkflowUserSequence");
	}
	
    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    function WorkflowUserSequences()
    {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }

	function delete ($id)
	{
        global $cfg, $idworkflow;
        
		$item = new WorkflowUserSequence;
		$item->loadByPrimaryKey($id);
        
		$pos = $item->get("position");
		$idworkflowitem = $item->get("idworkflowitem");
		$this->select("position > $pos AND idworkflowitem = '".Contenido_Security::escapeDB($idworkflowitem, NULL)."'");
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
        $oDb = new DB_Contenido();
        
        $aIdArtLang = array();
		$sSql = 'SELECT idartlang FROM '.$cfg["tab"]["workflow_art_allocation"].' WHERE idusersequence = '.Contenido_Security::escapeDB($idusersequence, $oDb).';';
        $oDb->query($sSql);
        while ($oDb->next_record()) {
            array_push($aIdArtLang, $oDb->f('idartlang'));
        }
        
        $sSql = 'DELETE FROM '.$cfg["tab"]["workflow_art_allocation"].' WHERE idusersequence = '.Contenido_Security::escapeDB($idusersequence, $oDb).';';
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
		
		$this->select("idworkflowitem = '".Contenido_Security::escapeDB($idworkflowitem, NULL)."'","","position DESC","1");
		
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
		$this->select("idworkflowitem = '$idworkflowitem' AND position = '".Contenido_Security::escapeDB($pos1, NULL)."'");
		if (($item = $this->next()) === false)
		{
			$this->lasterror = i18n("Swapping items failed: Item doesn't exist", "workflow");
			return false;
		}
		
		$pos1ID = $item->getField("idusersequence");

		$this->select("idworkflowitem = '$idworkflowitem' AND position = '".Contenido_Security::escapeDB($pos2, NULL)."'");
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
 * @package    CONTENIDO Plugins
 * @subpackage Workflow
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright four for business 2003
 */
class WorkflowUserSequence extends Item {
	
	/**
     * Constructor Function
     * @param string $table The table to use as information source
     */
	function __construct()
	{
		global $cfg;
		parent::__construct($cfg["tab"]["workflow_user_sequences"], "idusersequence");
	}
	
    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    function WorkflowUserSequence()
    {
        cDeprecated("Use __construct() instead");
        $this->__construct();
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
        			       " WHERE user_id = '".Contenido_Security::escapeDB($value, $db)."'";
        			$db->query($sql);
        			
        			if (!$db->next_record())
        			{
        				$sql = "SELECT group_id FROM " . $cfg["tab"]["groups"] .
        				       " WHERE group_id = '".Contenido_Security::escapeDB($value, $db)."'";
        				       
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