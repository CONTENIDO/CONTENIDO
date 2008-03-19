<?php
/*****************************************
* File      :   $RCSfile: class.frontend.logic.php,v $
* Project   :   Contenido
* Descr     :   Frontend logic base class
* Modified  :   $Date: 2005/05/20 12:38:42 $
*
* © four for business AG, www.4fb.de
*
* $Id: class.frontend.logic.php,v 1.2 2005/05/20 12:38:42 timo.hummel Exp $
******************************************/

/**
 * FrontendLogic: This is the base class for all frontend related logic.
 * 
 * Basically, the class FrontendLogic is the base class for all your objects in
 * the frontend. Your child classes define how your objects are named, which
 * actions and items they contain and which item type they've got.
 * 
 * A word on actions: Each single object of a FrontendLogic subclass has the
 * same amount of actions. You can't have a different set of actions for 
 * different objects of the same type.
 */
class FrontendLogic
{
	/**
	 * getFriendlyName: Returns the friendly (e.g. display) name of your
	 * objects.
	 * 
	 * @return string Name of the object
	 */
	function getFriendlyName ()
	{
		return "Inherited class *must* override getFriendlyName";	
	}
	
	/**
	 * listActions: Lists all actions
	 * 
	 * The returned array has the format $actionname => $actiondescription
	 * 
	 * @return array Array of all actions  
	 */
	function listActions ()
	{
		return array("Inherited class *must* override listActions");	
	}
	
	/**
	 * listItems: Lists all available items
	 * 
	 * The returned array has the format $itemid => $itemname
	 * 
	 * @return array Array of items
	 */
	function listItems ()
	{
		return array("Inherited class *must* override listItems");	
	}
	
}

?>