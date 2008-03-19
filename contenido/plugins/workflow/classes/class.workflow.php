<?php

/*****************************************
* File      :   $RCSfile: class.workflow.php,v $
* Project   :   Contenido Workflow
* Descr     :   Workflow management class
*
* Author    :   $Author: timo.hummel $
*               
* Created   :   18.07.2003
* Modified  :   $Date: 2003/08/18 11:59:22 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.workflow.php,v 1.6 2003/08/18 11:59:22 timo.hummel Exp $
******************************************/

$cfg["tab"]["workflow"] = "piwf_workflow";
$cfg["tab"]["workflow_allocation"] = "piwf_allocation";
$cfg["tab"]["workflow_art_allocation"] = "piwf_art_allocation";
$cfg["tab"]["workflow_items"] = "piwf_items";
$cfg["tab"]["workflow_tasks"] = "piwf_tasks";
$cfg["tab"]["workflow_user_sequences"] = "piwf_user_sequences";
$cfg["tab"]["workflow_actions"] = "piwf_actions";

$workflowPath = $cfg["path"]['contenido'] . $cfg["path"]["plugins"] . "workflow/classes/";
require_once($cfg["path"]["contenido"] . $cfg["path"]["classes"]. "class.genericdb.php");
require_once ($cfg["path"]["contenido"] . $cfg["path"]["classes"] . 'class.ui.php');
require_once ($workflowPath . "class.workflowactions.php");
require_once ($workflowPath . "class.workflowallocation.php");
require_once ($workflowPath . "class.workflowartallocation.php");
require_once ($workflowPath . "class.workflowitems.php");
require_once ($workflowPath . "class.workflowusersequence.php");

/**
 * Class Workflows
 * Class for workflow management
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.2
 * @copyright four for business 2003
 */
class Workflows extends ItemCollection {
	
	/**
     * Constructor Function
     * @param none
     */
	function Workflows()
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["workflow"], "idworkflow");
	}
	
	function loadItem ($itemID)
	{
		$item = new Workflow();
		$item->loadByPrimaryKey($itemID);
		return ($item);
	}
	
	function create ()
	{
		global $auth, $client, $lang;
		$newitem = parent::create();
		$newitem->setField("created", date("Y-m-d H-i-s"));
		$newitem->setField("idauthor", $auth->auth["uid"]);
		$newitem->setField("idclient", $client);
		$newitem->setField("idlang", $lang);
		$newitem->store();
		
		return ($newitem);
	}
	
    /**
     * Deletes all corresponding informations to this workflow and delegate call to parent
     * @param integer $idWorkflow - id of workflow to delete
     */
    function delete($idWorkflow) {
        global $cfg;
        $oDb = new DB_contenido();
        
        $aItemIdsDelete = array();
        $sSql = 'SELECT idworkflowitem FROM '.$cfg["tab"]["workflow_items"].' WHERE idworkflow = '.$idWorkflow.';';
        $oDb->query($sSql);
        while ($oDb->next_record()) {
            array_push($aItemIdsDelete, $oDb->f('idworkflowitem'));
        }
        
        $aUserSequencesDelete = array();
        $sSql = 'SELECT idusersequence FROM '.$cfg["tab"]["workflow_user_sequences"].' WHERE idworkflowitem in ('.implode(',', $aItemIdsDelete).');';
        $oDb->query($sSql);
        while ($oDb->next_record()) {
            array_push($aUserSequencesDelete, $oDb->f('idusersequence'));
        }
        
        $sSql = 'DELETE FROM '.$cfg["tab"]["workflow_user_sequences"].' WHERE idworkflowitem in ('.implode(',', $aItemIdsDelete).');';
        $oDb->query($sSql);
        
        $sSql = 'DELETE FROM '.$cfg["tab"]["workflow_actions"].' WHERE idworkflowitem in ('.implode(',', $aItemIdsDelete).');';
        $oDb->query($sSql);
        
        $sSql = 'DELETE FROM '.$cfg["tab"]["workflow_items"].' WHERE idworkflow = '.$idWorkflow.';';
        $oDb->query($sSql);
        
        $sSql = 'DELETE FROM '.$cfg["tab"]["workflow_allocation"].' WHERE idworkflow = '.$idWorkflow.';';
        $oDb->query($sSql);
        
        $sSql = 'DELETE FROM '.$cfg["tab"]["workflow_art_allocation"].' WHERE idusersequence in ('.implode(',', $aUserSequencesDelete).');';
        $oDb->query($sSql);

        parent::delete($idWorkflow);
    }
}

/**
 * Class Workflow
 * Class for a single workflow item
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright four for business 2003
 */
class Workflow extends Item {
	
	/**
     * Constructor Function
     * @param string $table The table to use as information source
     */
	function Workflow()
	{
		global $cfg;
		
		parent::Item($cfg["tab"]["workflow"], "idworkflow");
	}
	
}


/* Helper functions */

function getWorkflowForCat ($idcat)
{
	global $lang, $cfg;
	
	$idcatlang = getCatLang($idcat, $lang);
	$workflows = new WorkflowAllocations;
    $workflows->select("idcatlang = '$idcatlang'");
    if ($obj = $workflows->next())
    {
    	/* Sanity: Check if the workflow still exists */
    	$workflow = new Workflow;
    	
    	$res = $workflow->loadByPrimaryKey($obj->get("idworkflow"));
    	
    	if ($res == false)
    	{
    		return 0;
            
    	} else {
	    	return $obj->get("idworkflow");
    	}
    }
}

function getCatLang ($idcat, $idlang)
{
	global $lang, $cfg;
	/* Get the idcatlang */
	$sql = "SELECT idcatlang FROM "
			.$cfg["tab"]["cat_lang"].
		   " WHERE idlang = '$idlang' AND
             idcat = '$idcat'";
   $db = new DB_Contenido;
   $db->query($sql);
   
   if ($db->next_record())
   {
       return ($db->f("idcatlang"));
   }
}


?>