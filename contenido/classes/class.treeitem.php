<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido Tree Item Class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.1.4
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-05-20
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *
 *   $Id: class.treeitem.php 387 2008-06-30 10:01:05Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

/**
 * Class TreeItem
 * Class to create tree-based items
 *
 * The treeitem class allows you to logically store
 * tree-based structures.
 *
 * Example:
 *
 * Let's have a tree with 3 nodes. It's important that
 * we always have a "root" key.
 *
 * $root = new TreeItem("root", 1);
 * $item1 = new TreeItem("node1",2);
 * $item2 = new TreeItem("node2",3);
 * $item3 = new TreeItem("node3",4);
 *
 * $root->addItem($item1);
 * $root->addItem($item2);
 * $root->addItem($item3);
 *
 * This represents the tree we described above.
 *
 * If you know the ID of the item you want to add
 * to, there's no need to have a specific item handy,
 * but rather you can use the "addItemToID" function.
 */
class TreeItem
{
	 
	/**
     * Sub Items for this tree item
     * @var array
     */
	var $subitems;
	
	/**
     * Determinates if this tree item is collapsed
     * @var boolean
     */
	var $collapsed;
	
	/**
     * ID for this item
     * @var string
     */
	var $id;
	
	/**
     * Name for this item
     * @var string
     */
	var $name;
	
	/**
     * Icon for the collapsed item
     * @var string
     */
	var $collapsed_icon;

	/**
     * Icon for the expanded item
     * @var string
     */
	var $expanded_icon;
	
	/**
     * Icon for last node in a branch
     * @var string
     */
	var $lastnode_icon;

	/**
     * Contains the level of this item
     * @var integer
     */
	var $level;
	
	/**
     * Contains custom entries
     * @var array
     */
	var $custom;
	
	/**
     * Contains the parent of this item
     * @var array
     */
	var $parent;

	/**
   * Constructor Function
	 * Creates a new, independant tree item.
   * @param string $name The name of that item
	 * @param string $id   The unique ID of that item
	 * @param boolean $collapsed Is this item collapsed by default
     */		
	function TreeItem($name ="", $id="", $collapsed = false)
	{
		$this->name = $name;
		$this->id = $id;
		$this->collapsed = $collapsed;
		$this->subitems = array();
		$this->collapsed_icon = 'images/but_plus.gif';
		$this->expanded_icon = 'images/but_minus.gif';
		$this->lastnode_icon = 'images/but_lastnode.gif';
		$this->parent = -1;
	}

	
	/**
     * addItem
	 * Adds a new subitem to this item.
     * @param object $item the item to add
     */	
	function addItem(&$item)
	{
		$this->subitems[count($this->subitems)] = &$item;
		$item->parent = $this->id;
	}

	/**
     * addItemToID
	 * Adds a new subitem to a specific item with an ID.
	 * Traverses all subitems to find the correct item.
     * @param object $item the item to add
	 * @param string $id the ID to add the item to
     */	
	function addItemToID($item, $id)
	{
		if ($this->id == $id)
		{
			$this->subitems[count($this->subitems)] = &$item;
			$item->parent = $this->id;
		} else {
			foreach (array_keys($this->subitems) as $key)
			{
				$this->subitems[$key]->addItemToID($item, $id);
			}
		}
	}

	/**
   * getItemByID
	 * Retrieves a specific item by its ID. Note that this
	 * function traverses all subitems to find the correct item.
	 * @param string $id the ID to find
   * @return object The item, or false if nothing was found
   */	
	function &getItemByID($id)
	{
		if ($this->id == $id)
		{
			return ($this);
		} else {
			foreach (array_keys($this->subitems) as $key)
			{
				$retObj = &$this->subitems[$key]->getItemByID($id);
				if ($retObj->id == $id)
				{
					return ($retObj);
				}
			}
		}
		
		return false;
	}


	/**
     * removeItem
	 * Removes an item with a specific ID.
	 * @param string $id the ID to find
     */		
	function removeItem ($id)
	{
		foreach (array_keys($this->subitems) as $key)
		{
			if ($this->subitems[$key]->id  == $id)
			{
				unset($this->subitems[$key]);
			}
		}	
	}

	/**
     * isCustomAttributeSet
	 * checks if a specific custom attribute is set
	 * @param string $item the attribute name to find
     */	
	function isCustomAttributeSet ($item)
	{
		if (array_key_exists($item, $this->custom))
		{
			return true;
		} else {
			foreach (array_keys($this->subitems) as $key)
			{
				if ($this->subitems[$key]->isCustomAttributeSet($item))
				{
					return true;
				}	
			}	
		}	
		
		return false;
	}
	


	/**
   * markExpanded
	 * Marks an item as expanded. Traverses all subitems
	 * to find the ID. Note that only the item with $id is
	 * expanded, but not its childs.
	 * @param string $id the ID to expand, or an array with all id's
     */	
	function markExpanded ($id)
	{
		if (is_array($id))
		{
			if (in_array($this->id, $id))
			{
				$this->collapsed = false;
			}
			
			foreach (array_keys($this->subitems) as $key)
			{
				$this->subitems[$key]->markExpanded($id);
			}				
		} else {
    		if ($this->id == $id)
    		{
    			$this->collapsed = false;
    			return true;
    		} else {
    			foreach (array_keys($this->subitems) as $key)
    			{
    				$this->subitems[$key]->markExpanded($id);
    			}
    		}
		}
	}

	/**
     * expandAll
	 * Expands all items, starting from the $start item.
	 * @param string $start the ID to start expanding from
     */		
	function expandAll ($start = -2)
	{
		if ($start != $this->id)
		{
			$this->collapsed = false;
		}
		
		foreach (array_keys($this->subitems) as $key)
		{
			$this->subitems[$key]->expandAll();
		}
	}

	/**
     * collapseAll
	 * Collapses all items, starting from the $start item.
	 * @param string $start the ID to start collapsing from
     */		
	function collapseAll ($start = -2)
	{
		if ($start != $this->id)
		{
			$this->collapsed = true;
		}
		
		foreach (array_keys($this->subitems) as $key)
		{
			$this->subitems[$key]->collapseAll();
		}
	}
	
	/**
     * markCollapsed
	 * Marks an item as collpased. Traverses all subitems
	 * to find the ID. Note that only the item with $id is
	 * collapsed, but not its childs.
	 * @param string $id the ID to collapse
     */		
	function markCollapsed($id)
	{
		if ($this->id == $id)
		{
			$this->collapsed = true;
		
		} else {
			foreach (array_keys($this->subitems) as $key)
			{
				$this->subitems[$key]->markCollapsed($id);
			}
		}
	}
	
	/**
   * traverse
	 * traverses the tree starting from this item, and returning
	 * all objects as $objects.
	 * @param object $objects all found objects
	 * @param integer $level Level to start on
     */		
	function traverse (&$objects, $level = 0)
	{
		$objects[count($objects)] = &$this;
		$this->level = $level;
		
		if ($this->collapsed == false)
		{
			foreach (array_keys($this->subitems) as $key)
			{
				$this->subitems[$key]->traverse($objects, $level + 1);
			}
		}
	}		

	/**
	 * Starts iterating at root node and flattens the tree into an array 
	 */
	function getFlatTree($item, &$flat_tree)
	{
		foreach ($item->subitems as $curItem)
		{
		$curItem->custom['vertline']=array();
		$flat_tree[] = $curItem;
		$this->getFlatTree($curItem, $flat_tree);
		}
	}		
	
	function hasCollapsedNode($item_id)
	{
		$parentNodeList=array();
		$this->getTreeParentNodes($parentNodeList, $item_id);
		$collapsedList=array();
		$this->getRealCollapsedList($collapsedList);
		
		if(sizeof(array_intersect($parentNodeList, $collapsedList)) > 0)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Returns a list of the id of all parent nodes of the given node
	 **/
	function getTreeParentNodes(&$parentNodes, $id)
	{
		$curItem = $this->getItemByID($id);
		$parentId = $curItem->parent;
		
		if($parentId && $parentId != -1)
		{
			$parentNodes[] = $parentId;
			$this->getTreeParentNodes($parentNodes, $parentId);
		}
	}
	
	/**
	 * Returns a list of the id of all parent nodes of the given node
	 * Not using the nodes of hierarchical tree, but flat tree !!
	 **/
	function getParentNodes(&$parentNodes, $stop_id)
	{
		$flat_tree = array();
		$this->getFlatTree($this, $flat_tree);
		
		foreach($flat_tree as $key => $value)
		{
			if($value->id != $stop_id)
			{
				$parentNodes[] = $value->id;
			}
			else
			{
				break;
			}
		}
	}
	
	/**
	 * getCollapsedList thinks if a node has no subnodes it is collapsed
	 * I don't think so
	 **/
	function getRealCollapsedList(&$list)
	{
		$this->getCollapsedList($list);
		$cleared_list=array();
		
		// remove all nodes that have no subnodes
		foreach($list as $key)
		{
			$item=$this->getItemByID($key);
			if(sizeof($item->subitems) > 0)
			{
				$cleared_list[]=$key;	
			}
		}
	}
	
	/**
     * getCollapsedList
	 * Returns all items (as ID array) which are collapsed.
	 * @param array $list Contains the list with all collapsed items
     */		
	function getCollapsedList(&$list)
	{
		if ($this->collapsed == true)
		{
			$list[] = $this->id;
		}
		
		foreach (array_keys($this->subitems) as $key)
		{
			$this->subitems[$key]->getCollapsedList($list);
		}
	}

	/**
   * getExpandedList
	 * Returns all items (as ID array) which are expanded.
	 * @param array $list Contains the list with all expanded items
     */	
	function getExpandedList(&$list)
	{
		if ($this->collapsed == false && !in_array($this->id, $list))
		{
			$list[] = $this->id;
		}
		
		foreach (array_keys($this->subitems) as $key)
		{
			$this->subitems[$key]->getExpandedList($list);
		}
	}
	
}

?>