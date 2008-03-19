<?php
/*****************************************
* File      :   $RCSfile: class.communications.php,v $
* Project   :   Contenido
* Descr     :   Communication/Messaging system
* Modified  :   $Date: 2004/05/19 09:02:52 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.communications.php,v 1.5 2004/05/19 09:02:52 timo.hummel Exp $
******************************************/

/**
 * Communications class
 */
 
class CommunicationCollection extends ItemCollection {
	
	/**
     * Constructor Function
     * @param none
     */
	function CommunicationCollection()
	{
		global $cfg;
		
		parent::ItemCollection($cfg["tab"]["communications"], "idcommunication");
		$this->_setItemClass("CommunicationItem");
	}

	/**
     * Creates a new communication item
     */		
	function create ()
	{
		global $auth, $client;
		$item = parent::create();
		
		$item->set("idclient", $client);
		$item->set("author", $auth->auth["uid"]);
		$item->set("created", date("Y-m-d H:i:s"),false);
		
		return $item;
	}
	
}

/**
 * Single CommunicationItem Item
 */
class CommunicationItem extends Item {
	
	/**
     * Constructor Function
     * @param string $table The table to use as information source
     */
	function CommunicationItem()
	{
		global $cfg;
		
		parent::Item($cfg["tab"]["communications"], "idcommunication");
	}
	
	function store ()
	{
		global $auth;
		$this->set("modifiedby", $auth->auth["uid"]);
		$this->set("modified", date("Y-m-d H:i:s"),false);
		
		parent::store();	
	}

	
}
?>