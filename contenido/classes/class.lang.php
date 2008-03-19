<?php

/**********************************************************************************
* File      :   $RCSfile: class.lang.php,v $
* Project   :   Contenido
* Descr     :   Class for language management and information
*
* Author    :   Timo A. Hummel
*               
* Created   :   20.05.2003
* Modified  :   $Date: 2006/04/28 09:20:55 $
*
* © four for business AG, www.4fb.de
*
* This file is part of the Contenido Content Management System. 
*
* $Id: class.lang.php,v 1.6 2006/04/28 09:20:55 timo.hummel Exp $
***********************************************************************************/

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
		
		$sql = "SELECT idclient FROM ".$cfg["tab"]["clients_lang"]." WHERE idlang = '$lang'";
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
