<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Class for language management and information
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.6
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
 *   $Id: class.lang.php 531 2008-07-02 13:30:54Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "class.genericdb.php");

/**
 * Class Language
 * Class for language collections
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright four for business 2003
 */
class Languages extends ItemCollection {

    /**
     * Constructor
     * @param none
     */
    function Languages()
    {
    	global $cfg;
    	
    	/* Call the parent constructor with the table name
           and primary key to use. */
    	parent::ItemCollection($cfg["tab"]["lang"],"idlang");
    	
    	$this->_setItemClass("Language");
    } 

	function nextAccessible()
	{
		global $perm, $client, $cfg, $lang;
		
		$item = parent::next();
		
		$db = new DB_Contenido;
		$lang 	= Contenido_Security::toInteger($lang);
		$client = Contenido_Security::toInteger($client);
		
		$sql = "SELECT idclient FROM ".$cfg["tab"]["clients_lang"]." WHERE idlang = '".$lang."'";
		$db->query($sql);
		
		if ($db->next_record())
		{
			if ($client != $db->f("idclient"))
			{
				$item = $this->nextAccessible();
			}	
		}
		
		if ($item)
		{
			if ($perm->have_perm_client("lang[".$item->get("idlang")."]") ||
                $perm->have_perm_client("admin[".$client."]") ||
                $perm->have_perm_client())
            {
            	/* Do nothing for now */
            } else {
            	$item = $this->nextAccessible();
            }
            
            return $item;
		} else {
			return false;
		}
	}
	
	
} // end class

/**
 * Class Language
 * Class for a single language item
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright four for business 2003
 */
class Language extends Item {
	
	/**
     * Constructor Function
     * @param none
     */
	function Language()
	{
		global $cfg;
		
		parent::Item($cfg["tab"]["lang"], "idlang");
	}
	
}
?>
