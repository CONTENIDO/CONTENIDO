<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Frontend logic base class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.2.1
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-30, Frederic Schneider, add security fix
 *
 *   $Id: class.frontend.logic.php 425 2008-06-30 14:53:17Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

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