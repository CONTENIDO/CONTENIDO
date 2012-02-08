<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO Group Management Modue
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.6.3
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created  2003-05-20
 *   modified 2008-07-02, Frederic Schneider, add security fix
 *   modified 2011-02-05, Murat Purc, Cleanup/formatting, documentation, standardize
 *                                    getGroupProperties() and new function insert()
 *   modified 2011-11-08, Murat Purc, Usage of cApiGroupProperty classes
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Class Groups
 * Container class for all system groups
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 1.0
 * @copyright four for business AG 2003
 */
class Groups
{

    /**
     * Storage of the source table to use for the group informations
     * @var string Contains the source table
     * @access private
     */
    var $table;

    /**
     * DB_Contenido instance
     * @var  DB_Contenido  Contains the database object
     * @access private
     */
    var $db;

    /**
     * Constructor Function
     *
     * @param string $table The table to use as information source
     */
    function Groups($table = '')
    {
        if ($table == '') {
            global $cfg;
            $this->table = $cfg['tab']['groups'];
        } else {
            $this->table = $table;
        }

        $this->db = new DB_Contenido();
    }


    /**
     * Removes the specified group from the database
     *
     * @param   string  $groupid  Specifies the group ID
     * @return  bool    True if the delete was successful
     */
    function deleteGroupByID($groupid)
    {
        $sql = "DELETE FROM "
                .$this->table.
                " WHERE group_id = '".Contenido_Security::escapeDB($groupid, $this->db)."'";

        $this->db->query($sql);
        return ($this->db->affected_rows() == 0) ? false : true;
    }


    /**
     * Removes the specified group from the database.
     *
     * @param   string  $groupid  Specifies the groupname
     * @return  bool    True if the delete was successful
     */
    function deleteGroupByGroupname($groupname)
    {
        $sql = "DELETE FROM "
                .$this->table.
                " WHERE groupname = '".Contenido_Security::escapeDB($groupname, $this->db)."'";

        $this->db->query($sql);
        return ($this->db->affected_rows() == 0) ? false : true;
    }


    /**
     * Returns all groups which are accessible by the current group.
     *
     * @param   array  $perms
     * @return  array  Array of group objects
     */
    function getAccessibleGroups($perms)
    {
        global $cfg;

        $clientclass = new Client();

        $allClients = $clientclass->getAvailableClients();

        $db = new DB_Contenido();

        foreach ($allClients as $key => $value) {
            if (in_array("client[".$key."]", $perms) || in_array("admin[".$key."]", $perms)) {
                $limit[] = 'perms LIKE "%client['.Contenido_Security::escapeDB($key, $db).']%"';
            }

            if (in_array("admin[".$key."]", $perms)) {
                $limit[] = 'perms LIKE "%admin['.Contenido_Security::escapeDB($key, $db).']%"';
            }
        }

        if (count($limit) > 0) {
            $limitSQL = implode(' OR ', $limit);
        }

        if (in_array('sysadmin', $perms)) {
            $limitSQL = '1';
        }

        $sql = "SELECT
                    group_id, groupname, description
                FROM
                ". $cfg['tab']['groups']
                . " WHERE 1 AND " .$limitSQL;

        $db->query($sql);

        $groups = array();

        while ($db->next_record()) {
            $groups[$db->f('group_id')] = array(
                'groupname'   => substr($db->f('groupname'), 4),
                'description' => $db->f('description'),
            );
        }

        return ($groups);
    }

}

/**
 * Class Group
 * Class for group information and management
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 1.0
 * @copyright four for business 2003
 */
class Group
{

    /**
     * Storage of the source table to use for the group informations
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
     * Storage of the source table to use for the group informations
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
     * Constructor Function.
     *
     * @param  string  $table  The table to use as information source
     */
    function Group($table = '')
    {
        if ($table == '') {
            global $cfg;
            $this->table = $cfg['tab']['groups'];
        } else {
            $this->table = $table;
        }

        $this->db = new DB_Contenido();
    }


    /**
     * Loads a group from the database by its groupname.
     *
     * @param   string  $groupname  Specifies the groupname
     * @return  bool    True if the load was successful
     */
    function loadGroupByGroupname($groupname)
    {
        // SQL-Statement to select by groupname
        $sql = "SELECT * FROM ".
                $this->table
                ." WHERE groupname = '" .Contenido_Security::escapeDB($groupname, $this->db)."'";

        // Query the database
        $this->db->query($sql);

        // Advance to the next record, return false if nothing found
        if (!$this->db->next_record()) {
            return false;
        }

        $this->values = $this->db->toArray();
    }


    /**
     * Loads a group from the database by its groupID.
     *
     * @param   string  $groupid  Specifies the groupID
     * @return  bool    True if the load was successful
     */
    function loadGroupByGroupID($groupID)
    {
        // SQL-Statement to select by groupID
        $sql = "SELECT * FROM ".
                $this->table
                ." WHERE group_id = '" .Contenido_Security::escapeDB($groupID, $this->db)."'";

        // Query the database
        $this->db->query($sql);

        // Advance to the next record, return false if nothing found
        if (!$this->db->next_record()) {
            return false;
        }

        $this->values = $this->db->toArray();
    }


    /**
     * Gets the value of a specific field.
     *
     * @param   string  $field  Specifies the field to retrieve
     * @return  mixed   Value of the field
     */
    function getField($field)
    {
        return ($this->values[$field]);
    }


    /**
     * Sets the value of a specific field.
     *
     * @param  string  $field  Specifies the field to set
     * @param  string  $value  Specifies the value to set
     */
    function setField($field, $value)
    {
        $this->modifiedValues[$field] = true;
        $this->values[$field] = $value;
    }


    /**
     * Stores the modified group object to the database.
     *
     * @return  bool
     */
    function store()
    {
        $sql = "UPDATE " . $this->table ." SET ";
        $first = true;

        foreach ($this->modifiedValues as $key => $value) {
            if ($first == true) {
                $sql .= "$key = '" . $this->values[$key] ."'";
            } else {
                $sql .= ", $key = '" . $this->values[$key] ."'";
            }
            $first = false;
        }

        $sql .= " WHERE group_id = '" .Contenido_Security::escapeDB($this->values['group_id'], $this->db)."'";

        $this->db->query($sql);

        return ($this->db->affected_rows() < 1) ? false : true;
    }

    /**
     * Inserts new group object to the database
     *
     * @return  bool
     */
    function insert()
    {
        $sql = "INSERT INTO " . $this->table ." SET ";
        $first = true;

        foreach ($this->modifiedValues as $key => $value) {
            if ($first == true) {
                $sql .= "$key = '" . $this->values[$key] ."'";
            } else {
                $sql .= ", $key = '" . $this->values[$key] ."'";
            }
            $first = false;
        }

        return ($this->db->query($sql));
    }


    /**
     * Returns the group property
     *
     * @param   string  $type  Type (class, category etc) for the property to retrieve
     * @param   string  $name  Name of the property to retrieve
     * @return  string|bool  Value of the retrieved property or false
     */
    function getGroupProperty($type, $name)
    {
        $groupPropColl = new cApiGroupPropertyCollection($this->values['group_id']);
        $groupProp = $groupPropColl->fetchByGroupIdTypeName($type, $name);
        return ($groupProp) ? $groupProp->get('value') : false;
    }


    /**
     * Retrieves all available properties of the group.
     *
     * @return  array  Returns assoziative properties array as follows:
     *                 - $arr[idgroupprop][name]
     *                 - $arr[idgroupprop][type]
     *                 - $arr[idgroupprop][value]
     */
    function getGroupProperties()
    {
        $props = array();

        $groupPropColl = new cApiGroupPropertyCollection($this->values['group_id']);
        $groupProps = $groupPropColl->fetchByGroupId();
        foreach($groupProps as $groupProp) {
            $props[$groupProp->get('idgroupprop')] = array(
                'name'  => $groupProp->get('name'),
                'type'  => $groupProp->get('type'),
                'value' => $groupProp->get('value'),
            );
        }

        return $props;
    }


    /**
     * Stores a property to the database.
     *
     * @param  string  $type   Type (class, category etc) for the property to retrieve
     * @param  string  $name   Name of the property to retrieve
     * @param  string  $value  Value to insert
     * @return cApiGroupProperty
     */
    function setGroupProperty($type, $name, $value)
    {
        $groupPropColl = new cApiGroupPropertyCollection($this->values['group_id']);
        return $groupPropColl->setValueByTypeName($type, $name, $value);
    }


    /**
     * Deletes a group property from the table.
     *
     * @param  string  $type  Type (class, category etc) for the property to delete
     * @param  string  $name  Name of the property to delete
     */
    function deleteGroupProperty($type, $name)
    {
        $groupPropColl = new cApiGroupPropertyCollection($this->values['group_id']);
        return $groupPropColl->deleteByGroupIdTypeName($type, $name);
    }

}

?>