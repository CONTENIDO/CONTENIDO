<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Article specification class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.0
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *
 *   $Id: class.artspec.php 528 2008-07-02 13:29:28Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

class ArtSpecCollection extends ItemCollection {
	/**
     * Constructor Function
     * @param none
     */
	function ArtSpecCollection()
	{
		global $cfg;
		parent::ItemCollection($cfg['tab']['art_spec'], "idartspec");
	}

	/**
     * Loads an item by its ID (primary key)
     * @param $itemID integer Specifies the item ID to load
     */	
	function loadItem ($itemID)
	{
		$item = new ArtSpecItem();
		$item->loadByPrimaryKey($itemID);
		return ($item);
	}
	
	function delete($id)
	{
		/* Local new db instance since we don't want to kill our
           probably existing result set */
		$db = new DB_Contenido;
		
		if (!$this->exists($id))
		{
			return false;
		} else
		{
			$obj = $this->loadItem($id);
		}

		$sql  = "DELETE FROM " .$this->table ." WHERE ";
		$sql .= $this->primaryKey . " = '". $id ."'";
		
		$db->query($sql);
		
		return $obj;
	}
}

/**
 * Article specification Item
 */
class ArtSpecItem extends Item {
	
	/**
     * Constructor Function
     * @param string $table The table to use as information source
     */
	function ArtSpecItem()
	{
		global $cfg;
		
		parent::Item($cfg['tab']['art_spec'], "idartspec");
	}

}
?>