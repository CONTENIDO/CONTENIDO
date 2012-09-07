<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido In-Use classes
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.8
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-07-18
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *
 *   $Id: class.inuse.php 421 2008-06-30 13:39:59Z dominik.ziegler $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "class.genericdb.php");

/**
 * Class InUse
 * Class for In-Use management
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright four for business 2003
 */
class InUseCollection extends ItemCollection {
	
	/**
     * Constructor Function
     * @param none
     */
	function InUseCollection()
	{
		global $cfg;
		parent::ItemCollection($cfg["tab"]["inuse"], "idinuse");
		
		$this->_setItemClass("InUseItem");
	}

	/**
     * Marks a specific object as "in use". Note that
	 * items are released when the session is destroyed.
	 *
	 * Currently, the following types are defined and approved
	 * as internal Contenido standard:
	 * article
	 * module
	 * layout
     * template
 	 *
     * @param $type string Specifies the type to mark.
	 * @param $objectid mixed Specifies the object ID
	 * @param $session string Specifies the session for which the "in use" mark is valid
	 * @param $user string Specifies the user which requested the in-use flag
     */
   	function markInUse ($type, $objectid, $session, $user)
	{
		$type 		= Contenido_Security::escapeDB($type, null);
		$objectid 	= Contenido_Security::escapeDB($objectid, null);
		$session 	= Contenido_Security::escapeDB($session, null);
		$user		= Contenido_Security::escapeDB($user, null);
		
		$this->select("type = '".$type."' AND objectid = '".$objectid."'");
		
		if (!$this->next())
		{
			$newitem = parent::create();
			$newitem->set("type", $type);
			$newitem->set("objectid", $objectid);
			$newitem->set("session", $session);
			$newitem->set("userid", $user);
			$newitem->store();
		}
	}

	/**
     * Removes the "in use" mark from a specific object.
 	 *
     * @param $type string Specifies the type to de-mark.
	 * @param $objectid mixed Specifies the object ID
	 * @param $session string Specifies the session for which the "in use" mark is valid
     */	
	function removeMark ($type, $objectid, $session)
	{
		$type 		= Contenido_Security::escapeDB($type, null);
		$objectid 	= Contenido_Security::escapeDB($objectid, null);
		$session 	= Contenido_Security::escapeDB($session, null);
		
		$this->select("type = '".$type."' AND objectid = '".$objectid."' AND session = '".$session."'");
		
		if ($obj = $this->next())
		{
			/* Extract the ID */
			$id = $obj->get("idinuse");
			
			/* Let's save memory */
			unset($obj);
			
			/* Remove entry */
			$this->delete($id);
		}
	}

	/**
     * Removes all marks for a specific type and session
 	 *
     * @param $type string Specifies the type to de-mark.
	 * @param $session string Specifies the session for which the "in use" mark is valid
     */	
	function removeTypeMarks ($type, $session)
	{
		$type		= Contenido_Security::escapeDB($type, null);
		$session	= Contenido_Security::escapeDB($session, null);
		
		$this->select("type = '".$type."' AND session = '".$session."'");
		
		while ($obj = $this->next())
		{
			/* Extract the ID */
			$id = $obj->get("idinuse");
			
			/* Let's save memory */
			unset($obj);
			
			/* Remove entry */
			$this->delete($id);
		}
	}
	
	/**
     * Removes the mark for a specific item
 	 *
     * @param $type string Specifies the type to de-mark.
	 * @param $itemid string Specifies the item
     */	
	function removeItemMarks ($type, $itemid)
	{
		$type 	= Contenido_Security::escapeDB($type, null);
		$itemid = Contenido_Security::escapeDB($itemid, null);
		
		$this->select("type = '".$type."' AND objectid = '".$itemid."'");
		
		while ($obj = $this->next())
		{
			/* Extract the ID */
			$id = $obj->get("idinuse");
			
			/* Let's save memory */
			unset($obj);
			
			/* Remove entry */
			$this->delete($id);
		}
	}	

	/**
     * Removes all in-use marks for a specific session.
 	 *
	 * @param $session string Specifies the session for which the "in use" marks should be removed
     */	
	function removeSessionMarks ($session)
	{
		$session = Contenido_Security::escapeDB($session, null);
		$this->select("session = '".$session."'");
		
		while ($obj = $this->next())
		{
			/* Extract the ID */
			$id = $obj->get("idinuse");
			
			/* Let's save memory */
			unset($obj);
			
			/* Remove entry */
			$this->delete($id);
		}
	}
		
	/**
     * Checks if a specific item is marked
 	 *
     * @param $type string Specifies the type to de-mark.
	 * @param $objectid mixed Specifies the object ID
	 * @return int Returns false if it's not in use or returns the object if it is.
     */	
	function checkMark ($type, $objectid)
	{
		$type 		= Contenido_Security::escapeDB($type, null);
		$objectid 	= Contenido_Security::escapeDB($objectid, null);
		
		$this->select("type = '".$type."' AND objectid = '".$objectid."'");
		
		if ($obj = $this->next())
		{
			return ($obj);
		} else {
			return false;
		}
	}
	
	/**
     * Checks and marks if not marked.
	 *
	 * Example: Check for "idmod", also return a lock message:
	 * list($inUse, $message) = $col->checkAndMark("idmod", $idmod, true, i18n("Module is in use by %s (%s)"));
	 *
	 * Example 2: Check for "idmod", don't return a lock message
	 * $inUse = $col->checkAndMark("idmod", $idmod);
 	 *
     * @param $type string Specifies the type to de-mark.
	 * @param $objectid mixed Specifies the object ID
	 * @param $returnWarning boolean If true, also returns an error message if in use
	 * @param $warningTemplate string String to fill with the template
	 *		                          (%s as placeholder, first %s is the username, second is the real name)
	 * @param $allowOverride boolean True if the user can override the lock
	 * @param $location string Value to append to the override lock button
	 * @return mixed If returnWarning is false, returns a boolean value wether the object is locked. If
	 *				 returnWarning is true, returns a 2 item array (boolean inUse, string errormessage).
     */		
	function checkAndMark ($type, $objectid, $returnWarning = false, $warningTemplate = "", $allowOverride = false, $location = "")
	{
		global $sess, $auth, $notification, $area, $frame, $perm;
		
		if (($obj = $this->checkMark($type, $objectid)) === false)
        {
        	$this->markInUse($type, $objectid, $sess->id, $auth->auth["uid"]);
        	$inUse = false;
        	$disabled = "";	
        	$noti = "";					
        } else {
        	if ($returnWarning == true)
        	{
            	$vuser = new User;
            	$vuser->loadUserByUserID($obj->get("userid"));
            	$inUseUser = $vuser->getField("username");
            	$inUseUserRealName = $vuser->getField("realname");
            	
            	$message = sprintf($warningTemplate, $inUseUser, $inUseUserRealName);
            	
            	if ($allowOverride == true && ($auth->auth["uid"] == $obj->get("userid") || $perm->have_perm()))
            	{
            		$alt = i18n("Click here if you want to override the lock");
            	
            		$link = $sess->url($location."&overridetype=".$type."&overrideid=".$objectid);
            		
            		$warnmessage = i18n("Do you really want to override the lock?");
            		$script = "javascript:if (window.confirm('".$warnmessage."') == true) { window.location.href  = '".$link."';}";
            		$override = '<br><br><a alt="'.$alt.'" title="'.$alt.'" href="'.$script.'" class="standard">['.i18n("Override lock").']</a> <a href="javascript://" class="standard" onclick="elem = document.getElementById(\'contenido_notification\'); elem.style.display=\'none\'">['.i18n("Hide notification").']</a>';
            	} else {
            		$override = "";
            	}
            	
            	if (!is_object($notification))
            	{
            		$notification = new Contenido_Notification;
            	}
            	
            	$noti = $notification->messageBox("warning", $message.$override, 0);			
            	$inUse = true;
        	}
        }
        
        if ($returnWarning == true)
        {
        	return (array($inUse, $noti));
        } else {
        	return $inUse;
        }
	}	
}

/**
 * Class InUseItem
 * Class for a single in-use item
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright four for business 2003
 */
class InUseItem extends Item {
	
	/**
     * Constructor Function
     * @param string $table The table to use as information source
     */
	function InUseItem()
	{
		global $cfg;
		
		parent::Item($cfg["tab"]["inuse"], "idinuse");
	}
	
}

?>