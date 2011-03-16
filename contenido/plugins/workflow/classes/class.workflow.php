<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Workflow management class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.6
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2003-07-18
 *   
 *   $Id: class.workflow.php,v 1.6 2003/08/18 11:59:22 timo.hummel Exp $
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


$cfg["tab"]["workflow"] = $cfg['sql']['sqlprefix']."_piwf_workflow";
$cfg["tab"]["workflow_allocation"] = $cfg['sql']['sqlprefix']."_piwf_allocation";
$cfg["tab"]["workflow_art_allocation"] = $cfg['sql']['sqlprefix']."_piwf_art_allocation";
$cfg["tab"]["workflow_items"] = $cfg['sql']['sqlprefix']."_piwf_items";
$cfg["tab"]["workflow_tasks"] = $cfg['sql']['sqlprefix']."_piwf_tasks";
$cfg["tab"]["workflow_user_sequences"] = $cfg['sql']['sqlprefix']."_piwf_user_sequences";
$cfg["tab"]["workflow_actions"] = $cfg['sql']['sqlprefix']."_piwf_actions";

plugin_include('workflow', 'classes/class.workflowactions.php');
plugin_include('workflow', 'classes/class.workflowallocation.php');
plugin_include('workflow', 'classes/class.workflowartallocation.php');
plugin_include('workflow', 'classes/class.workflowitems.php');
plugin_include('workflow', 'classes/class.workflowusersequence.php');

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
	function __construct()
	{
		global $cfg;
		parent::__construct($cfg["tab"]["workflow"], "idworkflow");
        $this->_setItemClass("Workflow");
	}
	
    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    function Workflows()
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct();
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
        $sSql = 'SELECT idworkflowitem FROM '.$cfg["tab"]["workflow_items"].' WHERE idworkflow = '. Contenido_Security::toInteger($idWorkflow) .';';
        $oDb->query($sSql);
        while ($oDb->next_record()) {
            array_push($aItemIdsDelete, Contenido_Security::escapeDB($oDb->f('idworkflowitem'), $oDb));
        }
        
        $aUserSequencesDelete = array();
        $sSql = 'SELECT idusersequence FROM '.$cfg["tab"]["workflow_user_sequences"].' WHERE idworkflowitem in ('.implode(',', $aItemIdsDelete).');';
        $oDb->query($sSql);
        while ($oDb->next_record()) {
            array_push($aUserSequencesDelete, Contenido_Security::escapeDB($oDb->f('idusersequence'), $oDb));
        }
        
        $sSql = 'DELETE FROM '.$cfg["tab"]["workflow_user_sequences"].' WHERE idworkflowitem in ('.implode(',', $aItemIdsDelete).');';
        $oDb->query($sSql);
        
        $sSql = 'DELETE FROM '.$cfg["tab"]["workflow_actions"].' WHERE idworkflowitem in ('.implode(',', $aItemIdsDelete).');';
        $oDb->query($sSql);
        
        $sSql = 'DELETE FROM '.$cfg["tab"]["workflow_items"].' WHERE idworkflow = '.Contenido_Security::toInteger($idWorkflow).';';
        $oDb->query($sSql);
        
        $sSql = 'DELETE FROM '.$cfg["tab"]["workflow_allocation"].' WHERE idworkflow = '.Contenido_Security::toInteger($idWorkflow).';';
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
	function __construct()
	{
		global $cfg;
		
		parent::__construct($cfg["tab"]["workflow"], "idworkflow");
	}

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    function Workflow()
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct();
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
	$db = new DB_Contenido;
	
	/* Get the idcatlang */
	$sql = "SELECT idcatlang FROM "
			.$cfg["tab"]["cat_lang"].
		   " WHERE idlang = '". Contenido_Security::escapeDB($idlang, $db)."' AND
             idcat = '".Contenido_Security::escapeDB($idcat, $db)."'";
   
   $db->query($sql);
   
   if ($db->next_record())
   {
       return ($db->f("idcatlang"));
   }
}


?>