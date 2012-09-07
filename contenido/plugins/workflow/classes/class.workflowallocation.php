<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 *  Workflow allocation class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.5
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2003-07-18
 *   
 *   $Id: class.workflowallocation.php,v 1.5 2006/01/13 15:54:41 timo.hummel Exp $
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "class.security.php");

/**
 * Class WorkflowAllocations
 * Class for workflow allocation management
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.2
 * @copyright four for business 2003
 */
class WorkflowAllocations extends ItemCollection {
	
	/**
     * Constructor Function
     * @param string $table The table to use as information source
     */
	function WorkflowAllocations()
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["workflow_allocation"], "idallocation");
	}
	
	function loadItem ($itemID)
	{
		$item = new WorkflowAllocation();
		$item->loadByPrimaryKey($itemID);
		return ($item);
	}
	
	function delete ($idallocation)
	{
		global $cfg, $lang;

		$obj = new WorkflowAllocation;
		$obj->loadByPrimaryKey($idallocation);
		
		$idcatlang = $obj->get("idcatlang");

		$db = new DB_Contenido;
		$sql = "SELECT idcat FROM ".$cfg["tab"]["cat_lang"]." WHERE idcatlang = '".Contenido_Security::toInteger($idcatlang)."'";
		$db->query($sql);
		$db->next_record();
		$idcat = $db->f("idcat");
		
		$sql = "SELECT idart FROM ".$cfg["tab"]["cat_art"]." WHERE idcat = '".Contenido_Security::toInteger($idcat)."'";
		$db->query($sql);
		
		while ($db->next_record())
		{
			$idarts[] = $db->f("idart");
		}
		
		$idartlangs = array();
		
		if (is_array($idarts))
		{
			foreach ($idarts as $idart)
			{
				$sql = "SELECT idartlang FROM ".$cfg["tab"]["art_lang"]." WHERE idart = '".Contenido_Security::toInteger($idart)."' and idlang = '".Contenido_Security::toInteger($lang)."'";
				$db->query($sql);
				if ($db->next_record())
				{
					$idartlangs[] = $db->f("idartlang");
				}
			}
		}
		
		$workflowArtAllocation = new WorkflowArtAllocation;
		$workflowArtAllocations = new WorkflowArtAllocations;
		
		foreach ($idartlangs as $idartlang)
		{
			$workflowArtAllocation->loadBy("idartlang", $idartlang);
			$workflowArtAllocations->delete($workflowArtAllocation->get("idartallocation"));
		}

		parent::delete($idallocation);		
	}
	
	function create ($idworkflow, $idcatlang)
	{
		$this->select("idcatlang = '$idcatlang'");
		
		if ($this->next() !== false)
		{
			$this->lasterror = i18n("Category already has a workflow assigned", "workflow");
			return false;
		}
		
		$workflows = new Workflows;
		$workflows->select("idworkflow = '$idworkflow'");
		
		if ($workflows->next() === false)
		{
			$this->lasterror = i18n("Workflow doesn't exist", "workflow");
			return false;
		} 
		$newitem = parent::create();
		if (!$newitem->setWorkflow($idworkflow))
		{
			$this->lasterror = $newitem->lasterror;
			$workflows->delete($newitem->getField("idallocation"));
			return false;
		}
		
		if (!$newitem->setCatLang($idcatlang))
		{
			$this->lasterror = $newitem->lasterror;
			$workflows->delete($newitem->getField("idallocation"));
			return false;
		}
		
		$newitem->store();
		
		return ($newitem);
	}
}

/**
 * Class WorkflowAllocation
 * Class for a single workflow allocation item
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright four for business 2003
 */
class WorkflowAllocation extends Item {
	
	/**
     * Constructor Function
     * @param string $table The table to use as information source
     */
	function WorkflowAllocation()
	{
		global $cfg;
		
		parent::Item($cfg["tab"]["workflow_allocation"], "idallocation");
	}

	/**
     * Overridden setField function. Users should only use setWorkflow.
     * @param string $field Void field since we override the usual setField function
     * @param string $value Void field since we override the usual setField function
     */	
	function setField($field, $value)
	{
		die("Don't use setField for WorkflowAllocation items! Use setWorkflow instead!");
	}
	
	/**
     * setWorkflow sets the workflow for the current item.
     * @param int $idworkflow Workflow-ID to set the item to
     */	
	function setWorkflow ($idworkflow)
	{
		
		
		
		$workflows = new Workflows;

		$workflows->select("idworkflow = '$idworkflow'");
		
		if ($workflows->next() === false)
		{
			$this->lasterror = i18n("Workflow doesn't exist", "workflow");
			return false;
		} 
		
		parent::setField("idworkflow", $idworkflow);
		parent::store();
		return true;
	}

	/**
     * setCatLang sets the idcatlang for the current item. Should
	 * only be called by the create function.
     * @param int $idcatlang idcatlang to set.
     */	
	function setCatLang ($idcatlang)
	{
		global $cfg;
		
		$allocations = new WorkflowAllocations;
		
		$allocations->select("idcatlang = '$idcatlang'");
		
		if ($allocations->next() !== false)
		{
			$this->lasterror = i18n("Category already has a workflow assigned", "workflow");
			return false;
		}
		
		$db = new DB_Contenido;
		$sql = "SELECT idcatlang FROM ".$cfg["tab"]["cat_lang"]." WHERE idcatlang = '".Contenido_Security::toInteger($idcatlang)."'";
		$db->query($sql);
		
		if (!$db->next_record())
		{
			$this->lasterror = i18n("Category doesn't exist, assignment failed", "workflow");
			return false;
		}

		parent::setField("idcatlang", $idcatlang);
		parent::store();
		return true;
	}	
}
?>