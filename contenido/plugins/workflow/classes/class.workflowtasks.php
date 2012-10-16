<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Simple wrapper for workflow tasks
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.2
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2003-07-18
 *   
 *   $Id: class.workflowtasks.php,v 1.2 2003/08/14 07:54:03 timo.hummel Exp $
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


/**
 * Class WorkflowTasks
 * Class for workflow task collections
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.2
 * @copyright four for business 2003
 */
class WorkflowTasks extends ItemCollection {
	
	/**
     * Constructor Function
     * @param string $table The table to use as information source
     */
	function WorkflowTasks()
	{
		global $cfg;
		
		parent::ItemCollection($cfg["tab"]["tasks"], "idtask");
	}
	
	function loadItem ($itemID)
	{
		$item = new WorkflowTask();
		$item->loadByPrimaryKey($itemID);
		return ($item);
	}
	
	function create ()
	{
		$newitem = parent::create();
		return ($newitem);
	}
	
	function select ($where = "", $group_by = "", $order_by = "", $limit = "")
	{
		global $client;
		
		if ($where != "")
		{
			$where = $where . " AND idclient = '".Contenido_Security::escapeDB($client, NULL)."'";
		}
		return parent::select($where, $group_by, $order_by, $limit);	
	}
}

/**
 * Class WorkflowTask
 * Class for a single workflow task item
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright four for business 2003
 */
class WorkflowTask extends Item {
	
	/**
     * Constructor Function
     * @param string $table The table to use as information source
     */
	function WorkflowTask()
	{
		global $cfg;
		
		parent::Item($cfg["tab"]["tasks"], "idtask");
	}
	
}
?>