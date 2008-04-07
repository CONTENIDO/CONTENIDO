<?php

/**
 * Class Users
 * Container class for all system users
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 1.0
 * @copyright four for business AG 2003
 */
class Users {
	
	/**
	 * Storage of the source table to use for the user informations
     * @var string Contains the source table
     * @access private
	 */
	var $table;

	/**
	 * DB_Contenido instance
     * @var object Contains the database object
     * @access private
	 */
	var $db;	
	
	/**
     * Constructor Function
     * @param string $table The table to use as information source
     */
	function Users($table = "")
	{
		if ($table == "")
		{
			global $cfg;
			$this->table = $cfg["tab"]["phplib_auth_user_md5"];
		} else {
			$this->table = $table;
		}
		
		$this->db = new DB_Contenido;
	}


    /**
     * create ($username)
     * creates a new user by specifying its username
	 * @param string $username Specifies the username
	 * @return int userid of created user
     */	
	function create ($username)
	{
		$newuserid = md5($username);
		$sql = "SELECT user_id FROM ".$this->table." WHERE user_id = '$newuserid'";
		$this->db->query($sql);
		if ($this->db->next_record())
		{
			return false;
		}
		
		$sql = "INSERT INTO ".$this->table." SET user_id = '$newuserid', username = '$username'";
		$this->db->query($sql);

		return $newuserid;
	}

	
    /**
     * deleteUserByID ($userid)
     * Removes the specified user from the database
	 * @param string $userid Specifies the user ID
	 * @return bool True if the delete was successful
     */	
	function deleteUserByID($userid)
	{
		$sql = "DELETE FROM "
				.$this->table.
				" WHERE user_id = '".$userid."'";
	    
	    $this->db->query($sql);
	    if ($this->db->affected_rows() == 0)
	    {
	    	return false;
	    } else {
	    	return true;
	    }
	}

    /**
     * deleteUserByUsername ($username)
     * Removes the specified user from the database
	 * @param string $userid Specifies the username
	 * @return bool True if the delete was successful
     */	
	function deleteUserByUsername($username)
	{
		$sql = "DELETE FROM "
				.$this->table.
				" WHERE username = '".$username."'";
	    
	    $this->db->query($sql);
	    if ($this->db->affected_rows() == 0)
	    {
	    	return false;
	    } else {
	    	return true;
	    }
	}	
	
	/**
     * getAccessibleUsers
     * Returns all users which are accessible by the current user
     * @return array Array of user objects
     */
    function getAccessibleUsers($perms, $includeAdmins = false) {

		global $cfg;
		
		$clientclass = new Client;
		/*if (!in_array("sysadmin", $perms))
		{
			$limit[] = 'perms NOT LIKE "%sysadmin%"';
		}*/
		
		$allClients = $clientclass->getAvailableClients();

	    foreach ($allClients as $key => $value)
    	{
        	if (in_array("client[".$key."]", $perms) || in_array("admin[".$key."]", $perms))
        	{
        		$limit[] = 'perms LIKE "%client['.$key.']%"';

				if ($includeAdmins) {
        			$limit[] = 'perms LIKE "%admin['.$key.']%"';
				}
        	}
        	
        	if (in_array("admin[".$key."]", $perms))
        	{
        		$limit[] = 'perms LIKE "%admin['.$key.']%"';
        	}
    	} 

		if ($includeAdmins) {
        	$limit[] = 'perms LIKE "%sysadmin%"';
        }

        $db = new DB_Contenido;

		if (count($limit) > 0)
		{
			$limitSQL = implode(" OR ", $limit);
		}
		
		if (in_array("sysadmin", $perms))
		{
			$limitSQL = "1";
		}
		
        $sql = "SELECT
                    user_id,
                    username,
                    realname
                FROM
                ". $cfg["tab"]["phplib_auth_user_md5"]. " WHERE 1 AND ".$limitSQL." ORDER BY realname, username";
                
        $db->query($sql);

        $users = array();
        
        while ($db->next_record())
        {
            
            $newentry["username"] = $db->f("username");
            $newentry["realname"] = $db->f("realname");

            $users[$db->f("user_id")] = $newentry;

        }

        return ($users);
    } // end function


	
}

/**
 * Class User
 * Class for user information and management
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 1.0
 * @copyright four for business 2003
 */
class User {

	/**
	 * Storage of the source table to use for the user informations
     * @var string Contains the source table
     * @access private
	 */
	var $table;

	/**
	 * DB_Contenido instance
     * @var object Contains the database object
     * @access private
	 */
	var $db;	
	
	/**
	 * Storage of the source table to use for the user informations
     * @var array Contains the source table
     * @access private
	 */
	var $values;	
	
	/**
	 * Storage of the fields which were modified
     * @var array Contains the field names which where modified
     * @access private
	 */
	var $modifiedValues;	
	
    /**
     * Constructor Function
     * @param string $table The table to use as information source
     */
    function User($table = "") {
    	
    	if ($table == "")
		{
			global $cfg;
			$this->table = $cfg["tab"]["phplib_auth_user_md5"];
		} else {
			$this->table = $table;
		}

        $this->db = new DB_Contenido;
    } // end function

    /**
     * loadUserByUsername($username)
     * Loads a user from the database by its username
	 * @param string $username Specifies the username
	 * @return bool True if the load was successful
     */
	function loadUserByUsername ($username)
	{
		/* SQL-Statement to select by username */
		$sql = "SELECT * FROM ".
				$this->table
				." WHERE username = '" .$username."'";
		
		/* Query the database */
		$this->db->query($sql);
		
		/* Advance to the next record, return false if nothing found */
		if (!$this->db->next_record())
		{
			return false;
		}
		
		$this->values = $this->db->copyResultToArray();
		
		return true;
	}


    /**
     * loadUserByUserID($userID)
     * Loads a user from the database by its userID
	 * @param string $userid Specifies the userID
	 * @return bool True if the load was successful
     */
	function loadUserByUserID ($userID)
	{
		/* SQL-Statement to select by userID */
		$sql = "SELECT * FROM ".
				$this->table
				." WHERE user_id = '" .$userID."'";
		
		/* Query the database */
		$this->db->query($sql);
		
		/* Advance to the next record, return false if nothing found */
		if (!$this->db->next_record())
		{
			return false;
		}
		
		$this->values = $this->db->copyResultToArray();
		
		return true;
	}
	
	/**
     * getField($field)
     * Gets the value of a specific field
	 * @param string $field Specifies the field to retrieve
	 * @return mixed Value of the field
     */
	function getField ($field)
	{
		return ($this->values[$field]);
	}
	
	/**
     * setField($field, $value)
     * Sets the value of a specific field
	 * @param string $field Specifies the field to set
	 * @param string $value Specifies the value to set
     */
	function setField ($field, $value)
	{
		$this->modifiedValues[$field] = true;
		$this->values[$field] = $value;
	}
	
	/**
     * store()
     * Stores the modified user object to the database
     */
	function store ()
	{
		
		$sql = "UPDATE " .$this->table ." SET ";
		$first = true;
		
		foreach ($this->modifiedValues as $key => $value)
		{
			if ($first == true)
			{
				$sql .= "$key = '" . $this->values[$key] ."'";
				$first = false;
			} else {
				$sql .= ", $key = '" . $this->values[$key] ."'";
			}
		}
		
		$sql .= " WHERE user_id = '" . $this->values['user_id']."'";
		
		$this->db->query($sql);
		
		if ($this->db->affected_rows() < 1)
		{
			return false;
		} else {
			return true;
		}
	}
		
	/**
     * getUserProperty($type, $name)
     * Stores the modified user object to the database
	 * @param string type Specifies the type (class, category etc) for the property to retrieve
	 * @param string name Specifies the name of the property to retrieve
	 * @param boolean group Specifies if this function should recursively search in groups
	 * @return string The value of the retrieved property
     */
    function getUserProperty ($type, $name, $group = false)
	{
		global $cfg, $perm;
		
		if (!is_object($perm))
		{
			$perm = new Contenido_Perm;
		}
		
		$result = false;
		
		if ($group == true)
		{
			$groups = $perm->getGroupsForUser($this->values['user_id']);
			
			if (is_array($groups))
			{
    			foreach ($groups as $value)
    			{
    				$sql = "SELECT value FROM " .$cfg["tab"]["group_prop"]."
    				WHERE group_id = '".$value."'
    			      AND type = '$type'
    				  AND name = '$name'";
    				$this->db->query($sql);		
    				
    				if ($this->db->next_record())
    				{
    					$result = $this->db->f("value");
    				}			
    			}
			}
		}
		
		$sql = "SELECT value FROM " .$cfg["tab"]["user_prop"]."
				WHERE user_id = '".$this->values['user_id']."'
			      AND type = '$type'
				  AND name = '$name'";
		$this->db->query($sql);
		 
		if ($this->db->next_record())
		{
			$result = $this->db->f("value");
		}
		
        if ($result !== false)
        {
            return urldecode($result);
        } else {
            return false;
        }
	}	

	/**
	 * getUserPropertiesByType($type)
	 * Stores the modified user object to the database
	 * 
	 * @param string 	sType 	Specifies the type (class, category etc) for the property to retrieve
	 * @param boolean 	bGroup 	Specifies if this function should recursively search in groups
	 * @return array 			The value of the retrieved property
	 **/
	 function getUserPropertiesByType ($sType, $bGroup = false)
	 {
	 	global $cfg, $perm;
	 	
	 	if (!is_object($perm))
	 	{
	 		$perm = new Contenido_Perm;
	 	}
	 	
	 	$aResult = array();
	 	
	 	if ($bGroup == true)
	 	{
	 		$aGroups = $perm->getGroupsForUser($this->values['user_id']);
	 		
	 		if (is_array($aGroups))
	 		{
	 			foreach ($aGroups as $iID)
	 			{
	 				$sSQL = "SELECT name, value FROM " .$cfg["tab"]["group_prop"]." 
                			 WHERE group_id = '".$iID."' 
                   			 AND type = '".$sType."'";
					$this->db->query($sSQL);
					
					while ($this->db->next_record())
					{
						$aResult[$this->db->f("name")] = urldecode($this->db->f("value"));
					}
				}
	 		}
	 	}
	 	
	 	$sSQL = "SELECT name, value FROM " .$cfg["tab"]["user_prop"]." 
            	 WHERE user_id = '".$this->values['user_id']."' 
                 AND type = '$sType'";
		$this->db->query($sSQL);
		
		while ($this->db->next_record())
		{
			$aResult[$this->db->f("name")] = urldecode($this->db->f("value"));
		}
		
		return $aResult;
	}

	/**
     * getUserProperties()
     * Retrieves all available properties of the user
     * @param none
     */
    function getUserProperties ()
	{
		global $cfg;
		
		$sql = "SELECT type, name FROM " .$cfg["tab"]["user_prop"]."
				WHERE user_id = '".$this->values['user_id']."'";
		$this->db->query($sql);

		if ($this->db->num_rows() == 0)
		{
			return false;
		}
		
		while ($this->db->next_record())
		{
			$props[] = array("name" => $this->db->f("name"),
						     "type" => $this->db->f("type"));	
		}		 
		
		return $props;

	}	
	
	/**
     * setUserProperty($type, $name, $value)
     * Stores a property to the database
	 * @param string type Specifies the type (class, category etc) for the property to retrieve
	 * @param string name Specifies the name of the property to retrieve
	 * @param string value Specifies the value to insert
     */
    function setUserProperty ($type, $name, $value)
	{
		global $cfg;
		
		$value = urlencode($value);
		
		/* Check if such an entry already exists */
		if ($this->getUserProperty($type, $name) !== false)
		{
	
			$sql = "UPDATE ".$cfg["tab"]["user_prop"]."
					SET value = '$value'
					WHERE user_id = '".$this->values['user_id']."'
			      	AND type = '$type'
				  	AND name = '$name'";
			$this->db->query($sql);
		} else {
			$sql = "INSERT INTO  ".$cfg["tab"]["user_prop"]."
					SET value = '$value',
						user_id = '".$this->values['user_id']."',
			      		type = '$type',
				  		name = '$name',
                        iduserprop = '" .$this->db->nextid($cfg["tab"]["user_prop"])."'";
			$this->db->query($sql);
		}
	}
	
	/**
     * deleteUserProperty($type, $name)
     * Deletes a user property from the table
	 * @param string type Specifies the type (class, category etc) for the property to retrieve
	 * @param string name Specifies the name of the property to retrieve
     */
    function deleteUserProperty ($type, $name)
	{
		global $cfg;
		
		/* Check if such an entry already exists */
		$sql = "DELETE FROM  ".$cfg["tab"]["user_prop"]."
					WHERE user_id = '".$this->values['user_id']."' AND
			      		type = '$type' AND
				  		name = '$name'";
		$this->db->query($sql);
	}
    /**
     * getAvailableUsers()
     * Returns all users available in the system
     * @return array   Array with id and name entries
     */
    function getAvailableUsers($sort = "ORDER BY realname ASC") {
        global $cfg;

        $db = new DB_Contenido;

        $sql = "SELECT
                    user_id,
                    username,
                    realname
                FROM
                ". $cfg["tab"]["phplib_auth_user_md5"]. " " . $sort;

        $db->query($sql);

        $users = array();
        
        while ($db->next_record())
        {
            
            $newentry["username"] = $db->f("username");
            $newentry["realname"] = $db->f("realname");

            $users[$db->f("user_id")] = $newentry;

        }

        return ($users);
    } // end function

    /**
     * getSystemAdmins()
     * Returns all system admins available in the system
     * @return array   Array with id and name entries
     */
    function getSystemAdmins() {
        global $cfg;

        $db = new DB_Contenido;

        $sql = "SELECT
                    user_id,
                    username,
                    realname,
                    email
                FROM
                ". $cfg["tab"]["phplib_auth_user_md5"] ."
                WHERE
                    perms LIKE \"%sysadmin%\"";


        $db->query($sql);

        $users = array();
        
        while ($db->next_record())
        {
            
            $newentry["username"] = $db->f("username");
            $newentry["realname"] = $db->f("realname");
            $newentry["email"]    = $db->f("email");

            $users[$db->f("user_id")] = $newentry;

        }

        return ($users);
    } // end function
   
    /**
     * getClientAdmins()
     * Returns all system admins available in the system
     * @return array   Array with id and name entries
     */
    function getClientAdmins($client) {
        global $cfg;

        $db = new DB_Contenido;

        $sql = "SELECT
                    user_id,
                    username,
                    realname,
                    email
                FROM
                ". $cfg["tab"]["phplib_auth_user_md5"] ."
                WHERE
                    perms LIKE \"%admin[".$client."]%\"";


        $db->query($sql);

        $users = array();
        
        while ($db->next_record())
        {
            
            $newentry["username"] = $db->f("username");
            $newentry["realname"] = $db->f("realname");
            $newentry["email"]    = $db->f("email");

            $users[$db->f("user_id")] = $newentry;

        }

        return ($users);
    } // end function

    
    /**
     * getUsername()
     * Returns the username of the given userid
     * @return string  Username if found, or emptry string if not.
     */
    function getUsername ($userid)
    {
        global $cfg;

        $db = new DB_Contenido;

        $sql = "SELECT
                    username
                FROM
                ". $cfg["tab"]["phplib_auth_user_md5"]."
                WHERE
                    user_id = \"".$userid."\"";

        $db->query($sql);
        $db->next_record();
        return ($db->f("username"));

    } // end function

    /**
     * getRealname()
     * Returns the realname of the given userid
     * @return string  Realname if found, or emptry string if not.
     */
    function getRealname ($userid)
    {
        global $cfg;

        $db = new DB_Contenido;

        $sql = "SELECT
                    realname
                FROM
                ". $cfg["tab"]["phplib_auth_user_md5"]."
                WHERE
                    user_id = \"".$userid."\"";

        $db->query($sql);
        $db->next_record();

        return ($db->f("realname"));

    } // end function 

    /**
     * getRealnameByUserName()
     * Returns the realname of the given username
     * @return string  Realname if found, or emptry string if not.
     */
    function getRealnameByUserName ($username)
    {
        global $cfg;

        $db = new DB_Contenido;

        $sql = "SELECT
                    realname
                FROM
                ". $cfg["tab"]["phplib_auth_user_md5"]."
                WHERE
                    username = \"".$username."\"";

        $db->query($sql);
        $db->next_record();

        return ($db->f("realname"));

    } // end function 

    /**
     * getGroupsByUserID()
     * Returns the groups a user is in
     * @return array Real names og groups
     */
	function getGroupsByUserID ($userid) {

        global $cfg;

        $db = new DB_Contenido;

        $sql = "SELECT
                    a.group_id
                FROM
                	".$cfg["tab"]["groups"]." AS a,
                	".$cfg["tab"]["groupmembers"]." AS b
				WHERE
					(a.group_id  = b.group_id)
					AND 
					(b.user_id = '".$userid."')
				";

        $db->query($sql);
        
		$arrGroups = array();
		
		$oGroup = new Group(); 
		
		while ($db->next_record()) {
			$oGroup->loadGroupByGroupID($db->f('group_id'));
			$sTemp = $oGroup->getField('groupname');
			$sTemp = substr($sTemp, 4, strlen($sTemp) - 4);
			
			$sDescription = trim($oGroup->getField('description'));
			
			if ($sDescription != '') {
				$sTemp.=' ('.$sDescription.')';
			}
			
			$arrGroups[] = $sTemp;
		}
        return $arrGroups;
		
		
	
	}
} // end class

?>
