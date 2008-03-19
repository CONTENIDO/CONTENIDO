<?php
/**
 * article specification class
 */

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