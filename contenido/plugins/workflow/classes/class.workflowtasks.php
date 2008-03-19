<?php

/*****************************************
* File      :   $RCSfile: class.workflowtasks.php,v $
* Project   :   Contenido Workflow
* Descr     :   Simple wrapper for workflow tasks
*
* Author    :   $Author: timo.hummel $
*               
* Created   :   18.07.2003
* Modified  :   $Date: 2003/08/14 07:54:03 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.workflowtasks.php,v 1.2 2003/08/14 07:54:03 timo.hummel Exp $
******************************************/


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
			$where = $where . " AND idclient = '$client'";
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