<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Session Management for PHP3
 * 
 * Requirements: 
 * @con_php_req 5
 *
 * @package    Contenido Backend <Area>
 * @version    0.1
 * @author     Boris Erdmann, Kristian Koehntopp
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 * 
 * {@internal 
 *   created  2002-08-16
 *   modified 2008-07-04, bilal arslan, added security fix
 *   modified 2009-10-29, Murat Purc, replaced deprecated functions (PHP 5.3 ready) and some formatting
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class Contenido_Perm
{

	var $classname = "Contenido_Perm";
	var $areacache = array ();
	var $actioncache = array ();
	var $db;
	var $db_perm;

	function getGroupsForUser($user)
	{
		global $cfg;
		
		if (!is_object($this->db)) {
			$this->db = new DB_Contenido;
		}

		$sql = "SELECT group_id FROM
		      	  		".$cfg["tab"]["groupmembers"]."
		    			WHERE user_id = '". Contenido_Security::escapeDB($user, $this->db)."'";
		$this->db->query($sql);

		$groups = array ();

		while ($this->db->next_record())
		{
			$groups[] = $this->db->f("group_id");
		}

		return $groups;
	}

	function getIDForArea($area)
	{
		global $cfg;

		if (!is_object($this->db_perm)) {
			$this->db_perm = new DB_Contenido;
		}

		if (!is_numeric($area))
		{
			if (!@ array_key_exists($area, $this->areacache))
			{
				$sql = "SELECT idarea
				    	                    FROM
				    		   ".$cfg["tab"]["area"]."
				    		     WHERE
				    		        name = \"". Contenido_Security::escapeDB($area, $this->db_perm )."\"";
				$this->db_perm->query($sql);

				$this->db_perm->next_record();
				$this->areacache[$area] = $this->db_perm->f("idarea");
				$area = $this->db_perm->f(0);
			} else
			{
				$area = $this->areacache[$area];
			}

			return ($area);
		}

		return $area;

	}

	function getIDForAction($action)
	{

		global $cfg;

		if (!is_object($this->db_perm)) {
			$this->db_perm = new DB_Contenido;
		}

		if (!is_numeric($action))
		{
			if (!@ array_key_exists($action, $this->actioncache))
			{
				$sql = "SELECT idaction
				    	                    FROM
				                  ".$cfg["tab"]["actions"]."
				     		        WHERE
				    		       name = \"".Contenido_Security::escapeDB($action, $this->db_perm )."\"";

				$this->db_perm->query($sql);
				$this->db_perm->next_record();

				$this->actioncache[$action] = $this->db_perm->f("idaction");
				$action = $this->db_perm->f(0);
			} else
			{
				$action = $this->actioncache[$action];
			}
			return ($action);

		}

		return $action;

	}

	function load_permissions($force = false)
	{

		global $sess, $area_rights, $item_rights, $db, $client, $lang, $auth, $cfg, $changelang, $changeclient;

		$return = "1";
		//if not admin or sysadmin
		if (!$this->have_perm())
		{
			$return = isset ($area_rights);

			if (!isset ($area_rights) || !isset ($item_rights) || isset ($changeclient) || isset ($changelang) || $force)
			{
				$return = "3";
				//register variables
				$sess->register("area_rights");
				$sess->register("item_rights");
				$item_rights = array ();

				$groups = $this->getGroupsForUser($auth->auth["uid"]);

				if (is_array($groups))
				{
					foreach ($groups as $group)
					{
						$this->load_permissions_for_user($group);
					}
				}

				$this->load_permissions_for_user($auth->auth["uid"]);

			}

		}

		return $return;
	}

	function load_permissions_for_user($user)
	{
		global $db, $client, $lang, $cfg;
		global $area_rights, $item_rights;
		$sql = "SELECT *
		                          FROM ".$cfg["tab"]["rights"]."
		                          WHERE user_id='".Contenido_Security::escapeDB($user, $db)."' AND idcat='0' AND idclient='".Contenido_Security::toInteger($client)."' AND idlang='".Contenido_Security::toInteger($lang)."'";
		$db->query($sql);

		if (!is_array($area_rights))
		{
			$area_rights = array ();
		}
		while ($db->next_record())
		{
			$area_rights[$db->f("idarea")][$db->f("idaction")] = true;
		}

		// Select Rights for Article and Sructure    Atention    Hard code Areas
		$sql = "SELECT
			                 idarea
			             FROM
			               ".$cfg["tab"]["area"];
		$db->query($sql);

		$tmp_area = array ();
		while ($db->next_record())
		{
			array_push($tmp_area, $db->f("idarea"));
		}
		
		$tmp_area_string = implode("','", array_keys($tmp_area));
		$sql = "SELECT
			     	idarea, idaction, idcat
		                          FROM ".$cfg["tab"]["rights"]."
		                          WHERE user_id='".Contenido_Security::escapeDB($user, $db)."' AND idclient='".Contenido_Security::toInteger($client)."' AND idlang='".Contenido_Security::toInteger($lang)."' AND idarea IN ('$tmp_area_string') AND idcat != '0'";
		$db->query($sql);

		while ($db->next_record())
		{
			$item_rights[$db->f(0)][$db->f(1)][$db->f(2)] = $db->f(2);

		}
	}

	function have_perm_area_action_anyitem($area, $action = 0)
	{

		if ($this->have_perm_area_action($area, $action))
		{
			return true;
		}

		$area = $this->getIDForArea($area);
		$action = $this->getIDForAction($action);

		global $item_rights;

		if (isset ($item_rights[$area][$action]))
		{
			return true;
		} else
		{
			return false;
		}

	}

	function have_perm_area_action_item($area, $action, $itemid)
	{
		global $item_rights, $auth, $client, $lang, $cfg;
		
		if (!is_object($this->db)) {
			$this->db = new DB_Contenido;
		}

		$area = $this->getIDForArea($area);
		$action = $this->getIDForAction($action);

		if ($this->have_perm())
		{
			return true;
		} else
		{
			//if the user has a right on this action in this area   check for the items
			if ($this->have_perm_area_action($area, $action))
			{
				return true;
			}
			//check rights for the action in this area at this item
			if (isset ($item_rights[$area][$action][$itemid]))
			{
				//if have action for area + action +item  check right for client and lang
				return true;
			} elseif ($item_rights[$area] != "noright")
			{

				$groupsForUser = $this->getGroupsForUser($auth->auth[uid]);
				$groupsForUser[] = $auth->auth[uid];

				$tmp_userstring = implode("','", $groupsForUser);

				$sql = "SELECT *
				                          FROM ".$cfg["tab"]["rights"]."
				                          WHERE user_id IN ('".$tmp_userstring."') AND idclient = '".Contenido_Security::toInteger($client)."' AND idlang = '".Contenido_Security::toInteger($lang)."' AND idarea = '$area'  AND idcat != '0'";
				$this->db->query($sql);

				//if there are no rights for this area set the flag norights
				if ($this->db->nf() == 0)
				{
					$item_rights[$area] = "noright";
					return false;
				}

				while ($this->db->next_record())
				{
					$item_rights[$this->db->f("idarea")][$this->db->f("idaction")][$this->db->f("idcat")] = $this->db->f("idcat");
				}

				//check
				if (isset ($item_rights[$area][$action][$itemid]))
				{
					//if have action for area + action +item  check right for client and lang
					return true;

				}

			}
			return false;
		}
	}

	function getParentAreaId($area)
	{

		global $client, $lang, $cfg, $sess;

		if (!is_object($this->db)) {
			$this->db = new DB_Contenido;
		}

		if (is_numeric($area))
		{
			$sql = "SELECT
			                    b.name
			                FROM
			                    ".$cfg["tab"]["area"]." AS a,
			                    ".$cfg["tab"]["area"]." AS b
			                WHERE
			                    a.idarea = '". Contenido_Security::toInteger($area)."' AND
			                    b.name = a.parent_id";
		} else
		{
			$sql = "SELECT
			                    b.name
			                FROM
			                    ".$cfg["tab"]["area"]." AS a,
			                    ".$cfg["tab"]["area"]." AS b
			                WHERE
			                    a.name = '". Contenido_Security::escapeDB($area, $this->db)."' AND
			                    b.name = a.parent_id";

		}
		$this->db->query($sql);

		if ($this->db->next_record())
		{
			return $this->db->f(0);
		} else
		{
			return $area;

		}

	}

	function have_perm_area_action($area, $action = 0)
	{
		global $area_rights, $client, $lang, $cfg;
		//if not admin or sysadmin
		if (!is_object($this->db_perm)) {
			$this->db_perm = new DB_Contenido;
		}

		$area = $this->getIDForArea($area);
		$action = $this->getIDForAction($action);

		if ($action == 0)
		{
			$area = $this->getParentAreaId($area);
		}

		$area = $this->getIDForArea($area);

		if (!$this->have_perm())
		{

			if ($action == 0 && $area_rights[$area])
			{
				//if have action for area + action   check right for client and lang
				return ($this->have_perm_client_lang($client, $lang));
			}

			//check rights for the action in this area
			if ($area_rights[$area][$action])
			{
				//if have action for area + action   check right for client and lang
				return $this->have_perm_client_lang($client, $lang);
			}

			return false;
		}
		return true;

	}

	function have_perm_client_lang($client, $lang)
	{
		// Changed back to a full featured function, as have_perm
		// needs $client as global variable - not provided by this
		// function
		//return ($this->have_perm("client[$client],lang[$lang]"));
		
		global $auth;

		if (!isset($auth->auth['perm']))
		{
			$auth->auth['perm'] = '';
		}

		// Split the permissions of the user
		$userperm = explode(',', $auth->auth['perm']);

		if (in_array('sysadmin', $userperm)) {
			return true; // User is sysadmin
		} elseif (in_array("admin[$client]", $userperm)) {
			return true; // User is admin
		} else {
			// Check rights for the client and the language
			$pageperm = explode(',', "client[$client],lang[$lang]");
			foreach ($pageperm as $value) {
				if (!in_array($value, $userperm)) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * hasClientPermission
	 * Checks if a user has access rights for a specific client.
	 * 
	 * @param integer $iClient	idclient to check, or false for the current client
	 * @param object  $oUser	User object to check against, or false for the current user	
	 */
	function hasClientPermission ($iClient = false, $oUser = false)
	{
		global $auth;

		if ($iClient === false)
		{
			global $client;
			$iClient = $client;	
		}
		
		$oUser = $this->_checkUserObject($oUser);
		
		if ($this->isSysadmin($oUser) || $this->isClientAdmin($iClient, $oUser) || $this->isClientUser($iClient, $oUser))
		{
			return true;	
		} else {
			return false;
		}
		/* Commented out Timo Trautmann, because here only client access is checked, possibility for admin or sysadmin access was ignored 
		   functions isSysadmin isClientAdmin isClientUser also handles permission for groups
			#Check clients' rights of users' group(s)
			$aGroups = $this->getGroupsForUser($auth->auth["uid"]);
			if (is_array($aGroups))
			{
				foreach ($aGroups as $group)
				{
					$oGroup = new Group;
					$oGroup->loadGroupByGroupID ($group);
					
					if ($this->isClientGroup($iClient, $oGroup))
					{
						return true;
					}	
				}
			}
			
			return false;	
		}*/
	}
	

	/**
	 * isClientUser
	 * Checks if the given user has access permission for a client
	 * 
	 * @param integer	$iClient	idclient to check
	 * @param object 	$oUser		User object to check against
	 */
	function isClientUser ($iClient, $oUser)
	{
		$oUser = $this->_checkUserObject($oUser);
		
		$aPermissions = explode(",", $oUser->getEffectiveUserPerms());
		
		if (in_array("client[$iClient]", $aPermissions))
		{
			return true;	
		}
		
		return false;
	}
	
	/**
	 * isClientGroup
	 * Checks if the given group has access permission for a client
	 * 
	 * @param integer	$iClient	idclient to check
	 * @param object 	$oGroup		Group object to check against
	 */
	function isClientGroup ($iClient, $oGroup)
	{
		$aPermissions = explode(",", $oGroup->getField("perms"));
		
		if (in_array("client[$iClient]", $aPermissions))
		{
			return true;	
		}
		
		return false;
	}

	/**
	 * isClientAdmin
	 * Checks if the given user has an admin permission
	 * 
	 * @param integer	$iClient	idclient to check
	 * @param object 	$oUser		User object to check against
	 */
	function isClientAdmin ($iClient, $oUser)
	{
		$oUser = $this->_checkUserObject($oUser);
		
		$aPermissions = explode(",", $oUser->getEffectiveUserPerms());
		
		if (in_array("admin[$iClient]", $aPermissions))
		{
			return true;	
		}
		
		return false;
	}
	
	/**
	 * isSysadmin
	 * Checks if the given user has sysadmin permission
	 * 
	 * @param object $oUser	User object to check against
	 */
	function isSysadmin ($oUser)
	{
		$oUser = $this->_checkUserObject($oUser);
		
		$aPermissions = explode(",", $oUser->getEffectiveUserPerms());
		
		if (in_array("sysadmin", $aPermissions))
		{
			return true;	
		}
		
		return false;
	}
	
	/**
	 * _checkUserObject
	 * 
	 * Checks if the given object is a user object. 
	 * 
	 * If oUser is false, initialize the object from the currently logged in user. If oUser is not
	 * a object of the class User, issue a warning.
	 * 
	 * @parameter object oUser User object
	 * @access private
	 */
	function _checkUserObject ($oUser)
	{
		if ($oUser === false)
		{
			global $currentuser;
			$oUser = $currentuser;	
		}
		
		if (!is_object($oUser))
		{
			global $auth;
			$oUser = new User;
			$oUser->loadUserByUserID($auth->auth["uid"]);
		}
		
		if (strtolower(get_class($oUser)) != "user")
		{
			cWarning(__FILE__, __LINE__, "oUser parameter is not of type User");	
		}
		
		return $oUser;	
	}
	/**
	 * @deprecated
	 */
	function have_perm_client($p = 'x')
	{
		global $auth, $client;

		if (!isset ($auth->auth['perm'])) {
			$auth->auth['perm'] = '';
		}

		//split the permissions of the user
		$userperm = explode(',', $auth->auth['perm']);

		//if User is sysadmin or admin at this client return true
		if (in_array('sysadmin', $userperm)) {
			return true;
		}

		//if there are more permissions to ask split them
		$pageperm = explode(',', $p);
		foreach ($pageperm as $value) {
			if (!in_array($value, $userperm)) {
				return false;
			}
		}
		return true;

	}

	function have_perm($p = 'x')
	{
		global $auth, $client;

		if (!isset ($auth->auth['perm'])) {
			$auth->auth['perm'] = '';
		}

		//split the permissions of the user
		$userperm = explode(',', $auth->auth['perm']);

		//if User is sysadmin or admin at this client return true
		if (in_array('sysadmin', $userperm)) {
			return true;
		} elseif (in_array("admin[$client]", $userperm)) {
			return true;
			//else check rights for the client and the language
		} else {
			//if there are more permissions to ask split them
			$pageperm = explode(',', $p);
			foreach ($pageperm as $value) {
				if (!in_array($value, $userperm)) {
					return false;
				}
			}
		}
		return true;
	}

	//checks if an item have any perms
	function have_perm_item($mainarea, $itemid)
	{

		global $cfg, $item_rights, $cfg, $client, $lang, $auth, $area_tree, $sess;

		$mainarea = $this->getIDForArea($mainarea);

		//if is not admin or sysadmin
		if (!$this->have_perm())
		{

			if (!is_object($this->db)) {
				$this->db = new DB_Contenido;
			}

			$this->showareas($mainarea);

			$flg = false;
			//check if there are any rights for this areas
			foreach ($area_tree[$mainarea] as $value)
			{
				// if the flag noright is set there are no rights in this area
				if ($item_rights[$value] == "noright")
				{
					continue;

				}
				elseif (is_array($item_rights[$value]))
				{
					//if there are any rights
					foreach ($item_rights[$value] as $value2)
					{
						if (in_array($itemid, $value2))
						{
							return true;
						}
					}

				}
				elseif ($item_rights[$value] != "noright")
				{

					$groupsForUser = $this->getGroupsForUser($auth->auth[uid]);
					$groupsForUser[] = $auth->auth[uid];

					//else search for rights for this user in this area
					$sql = "SELECT
					                        *
					                     FROM
					                        ".$cfg["tab"]["rights"]."
					                     WHERE
					                        user_id IN ('".implode("','", $groupsForUser)."') AND
					                        idclient = '". Contenido_Security::toInteger($client)."' AND
					                        idlang = '". Contenido_Security::toInteger($lang)."' AND
					                        idarea = '$value' AND
					                        idcat != '0'";
					$this->db->query($sql);

					//if there are no rights for this area set the flag norights
					if ($this->db->affected_rows() == 0)
					{
						$item_rights[$value] = "noright";
					}

					//set the rights
					while ($this->db->next_record())
					{
						if ($this->db->f("idcat") == $itemid)
						{
							$flg = true;
						}
						$item_rights[$this->db->f("idarea")][$this->db->f("idaction")][$this->db->f("idcat")] = $this->db->f("idcat");
					}

				} //end if

			} //end for
			return $flg;
		} //end if
		return true;

	}

	function showareas($mainarea)
	{

		global $area_tree, $sess, $perm, $cfg;

		if (!is_object($this->db)) {
			$this->db = new DB_Contenido;
		}

		$mainarea = $this->getIDForArea($mainarea);

		//if $area_tree for this area is not register
		if (!isset ($area_tree[$mainarea]))
		{
			$sess->register("area_tree");

			// parent_id uses the name not the idarea
			$sql = "SELECT
			                  name
			               FROM
			                  ".$cfg["tab"]["area"]."
			               WHERE
			                  idarea='$mainarea'";

			$this->db->query($sql);
			$this->db->next_record();
			$name = $this->db->f("name");

			//check which subareas are there and write them in the array
			$sql = "SELECT
			                  idarea
			               FROM
			                  ".$cfg["tab"]["area"]."
			               WHERE
			                  parent_id='$name' OR
			                  idarea='$mainarea'";

			$this->db->query($sql);
			$area_tree[$mainarea] = array ();
			while ($this->db->next_record())
			{
				$area_tree[$mainarea][] = $this->db->f("idarea");
			}

		}
		return $mainarea;
	}

}
