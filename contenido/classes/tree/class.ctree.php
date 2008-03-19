<?php
/*****************************************
* File      :   $RCSfile: class.ctree.php,v $
* Project   :   Contenido
* Descr     :   logical cTree
* Modified  :   $Date: 2004/08/04 07:56:19 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.ctree.php,v 1.2 2004/08/04 07:56:19 timo.hummel Exp $
******************************************/

cInclude("classes", "tree/class.ctreeitem.php");

/**
 * class cTree
 * 
 */
class cTree extends cTreeItem
{
	
	var $_treeIcon;
	
	function cTree ($name = "")
	{
		/* The root item currently has to be a "0".
		 * This is a bug, feel free to fix it. */
		  
		cTreeItem::cTreeItem(0,$name);	
	}
	
	/**
	 * sets a new name for the tree.
	 *
	 * @param string name Name of the tree
	 * @return void
	 * @access public
	 */
	function setTreeName( $name )
	{
		$this->setName($name);	
	} // end of member function setTreeName

	function setIcon ( $path )
	{
		$this->_treeIcon = $path;	
	}


} // end of cTree
?>
