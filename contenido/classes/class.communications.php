<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Communication/Messaging system
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.5
 * @author     Timo A. Hummel
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
 *   $Id: class.communications.php 528 2008-07-02 13:29:28Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

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
		
		$client = Contenido_Security::toInteger($client);
		
		$item->set("idclient", $client);
		$item->set("author", $auth->auth["uid"]);
		$item->set("created", date("Y-m-d H:i:s"), false);
		
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
		$this->set("modified", date("Y-m-d H:i:s"), false);
		
		parent::store();	
	}
}
?>