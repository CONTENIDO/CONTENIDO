<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Management of per-workflowitem actions
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
 *   $Id: class.workflowactions.php,v 1.3 2006/01/13 15:54:41 timo.hummel Exp $
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

/**
 * Class WorkflowActions
 * Class for workflow action collections
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.2
 * @copyright four for business 2003
 */
class WorkflowActions extends ItemCollection {
	
	/**
     * Constructor Function
     * @param string $table The table to use as information source
     */
	function WorkflowActions()
	{
		global $cfg;
		
		parent::ItemCollection($cfg["tab"]["workflow_actions"], "idworkflowaction");
	}
	
	function loadItem ($itemID)
	{
		$item = new WorkflowAction();
		$item->loadByPrimaryKey($itemID);
		return ($item);
	}
	
	function get ($idworkflowitem, $action)
	{
		$this->select("idworkflowitem = '".Contenido_Security::escapeDB($idworkflowitem, NULL)."' AND action = '".Contenido_Security::escapeDB($action, NULL)."'");
		if ($this->next())
		{
			return true;
		} else {
			return false;
		}
	}
	
	function getAvailableWorkflowActions ()
	{
		$availableWorkflowActions = array (
		"publish" => i18n("Publish article", "workflow"),
		"lock" => i18n("Lock article", "workflow"),
		"last" => i18n("Move back to last editor", "workflow"),
		"reject" => i18n("Reject article", "workflow"),
		"articleedit" => i18n("Edit article content", "workflow"),
		"propertyedit" => i18n("Edit article properties", "workflow"),
		"templateedit" => i18n("Edit template", "workflow"),
		"revise" => i18n("Revise article", "workflow"));
		
		return($availableWorkflowActions);
	}
	function set ($idworkflowitem, $action)
	{
		$this->select("idworkflowitem = '".Contenido_Security::escapeDB($idworkflowitem, NULL)."' AND action = '".Contenido_Security::escapeDB($action, NULL)."'");
		if (!$this->next())
		{
			$newitem = parent::create();
			$newitem->setField("idworkflowitem",$idworkflowitem);
			$newitem->setField("action",$action);
			$newitem->store();
		}
	}
	
	function remove ($idworkflowitem, $action)
	{
		$this->select("idworkflowitem = '$idworkflowitem' AND action = '$action'");
		if ($item = $this->next())
		{
			$this->delete($item->getField("idworkflowaction"));
		}	
	}	
	
	function select ($where = "", $group_by = "", $order_by = "", $limit = "")
	{
		global $client;
		
		return parent::select($where, $group_by, $order_by, $limit);	
	}
}

/**
 * Class WorkflowAction
 * Class for a single workflow action
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright four for business 2003
 */
class WorkflowAction extends Item {
	
	/**
     * Constructor Function
     * @param string $table The table to use as information source
     */
	function WorkflowAction()
	{
		global $cfg;
		
		parent::Item($cfg["tab"]["workflow_actions"], "idworkflowaction");
	}
	
}
?>