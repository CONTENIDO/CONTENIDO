<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * User access class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend classes
 * @version    1.8
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2007-06-24
 *   modified 2011-03-15, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *   modified 2011-02-05, Murat Purc, takeover roperty management from User class
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class cApiUserCollection extends ItemCollection
{
    public function __construct($select = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['phplib_auth_user_md5'], 'user_id');
        $this->_setItemClass('cApiUser');
        if ($select !== false) {
            $this->select($select);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiUserCollection($select = false)
    {
        cWarning(__FILE__, __LINE__, 'Deprecated method call, use __construct()');
        $this->__construct($select);
    }

    /**
     * Createa a user by user name.
     *
     * @param  string  $username
     * @return  cApiUser|false
     */
    public function create($username)
    {
        $md5user = md5($username);

        $this->resetQuery();
        $this->setWhere('user_id', $md5user);
        $this->query();

        if ($this->next()) {
            return false;
        } else {
            $item = parent::create();
            $item->set('user_id', $md5user);
            $item->set('username', $username);
            $item->store();

            return $item;
        }
    }
}


class cApiUser extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['phplib_auth_user_md5'], 'user_id');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiUser($mId = false)
    {
        cWarning(__FILE__, __LINE__, 'Deprecated method call, use __construct()');
        $this->__construct($mId);
    }

    /**
     * Stores the modified user object to the database
     * @param string type Specifies the type (class, category etc) for the property to retrieve
     * @param string name Specifies the name of the property to retrieve
     * @param boolean group Specifies if this function should recursively search in groups
     * @return string The value of the retrieved property
     */
    public function getUserProperty($type, $name, $group = false)
    {
        global $cfg, $perm;

        if (!is_object($perm)) {
            $perm = new Contenido_Perm();
        }

        $result = false;

        if ($group == true) {
            $groups = $perm->getGroupsForUser($this->values['user_id']);

            if (is_array($groups)) {
                foreach ($groups as $groupid) {
                    $sql = "SELECT value FROM " . $cfg['tab']['group_prop'] . "
                            WHERE group_id = '" . $this->db->escape($groupid) . "'
                              AND type = '" . $this->db->escape($type) . "'
                              AND name = '" . $this->db->escape($name) . "'";
                    $this->db->query($sql);

                    if ($this->db->next_record()) {
                        $result = $this->db->f('value');
                    }
                }
            }
        }

        $sql = "SELECT value FROM " .$cfg['tab']['user_prop']."
                WHERE user_id = '" . $this->db->escape($this->values['user_id']) . "'
                  AND type = '" . $this->db->escape($type) . "'
                  AND name = '" . $this->db->escape($name) . "'";
        $this->db->query($sql);

        if ($this->db->next_record()) {
            $result = $this->db->f('value');
        }

        if ($result !== false) {
            return urldecode($result);
        } else {
            return false;
        }
    }

    /**
     * Returns all user properties by type.
     *
     * @todo  return value should be similar to getUserProperties()
     *
     * @param   string  $type    Type (class, category etc) of the properties to retrieve
     * @param   bool    $group   Flag to retrieve in group properties. If enabled, group properties
     *                           will be merged with user properties where the user poperties will
     *                           overwrite group properties
     * @return  array   Assoziative properties array as follows:
     *                  - $arr[name][value]
     */
    public function getUserPropertiesByType($type, $group = false)
    {
        global $cfg, $perm;

        if (!is_object($perm)) {
            $perm = new Contenido_Perm();
        }

        $props = array();

        if ($group == true) {
            $groups = $perm->getGroupsForUser($this->values['user_id']);
            if (is_array($groups)) {
                foreach ($groups as $groupid) {
                    $sql = "SELECT name, value FROM " . $cfg['tab']['group_prop'] . "
                            WHERE group_id = '" . $this->db->escape($groupid) . "'
                            AND type = '".$this->db->escape($type) . "'";
                    $this->db->query($sql);

                    while ($this->db->next_record()) {
                        $props[$this->db->f('name')] = urldecode($this->db->f('value'));
                    }
                }
            }
        }

        $sql = "SELECT name, value FROM " . $cfg['tab']['user_prop'] . "
                WHERE user_id = '" . $this->db->escape($this->values['user_id']) . "'
                AND type = '" . $this->db->escape($type) . "'";
        $this->db->query($sql);

        while ($this->db->next_record()) {
            $props[$this->db->f('name')] = urldecode($this->db->f('value'));
        }

        return $props;
    }

    /**
     * Retrieves all available properties of the user.
     * Works with a downwards compatible mode.
     *
     * NOTE: Even if downwards compatible mode is enbabled by default, this mode is deprecated...
     *
     * @param  bool  $beDownwardsCompatible  Flag to return downwards compatible values
     * @return array|bool  Returns a array or false in downwards compatible mode, otherwhise a array.
     *                     Return value in new mode is:
     *                     - $arr[iduserprop][name]
     *                     - $arr[iduserprop][type]
     *                     - $arr[iduserprop][value]
     *                     Return value in downwards compatible mode is:
     *                     - $arr[pos][name]
     *                     - $arr[pos][type]
     */
    public function getUserProperties($beDownwardsCompatible = true)
    {
        global $cfg;

        $sql = "SELECT iduserprop, type, name, value FROM " . $cfg['tab']['user_prop'] . "
                WHERE user_id = '" . $this->db->escape($this->values['user_id']) . "'";
        $this->db->query($sql);

        $props = array();

        if (true === $beDownwardsCompatible) {
            // @deprecated  [2011-11-03]
            if ($this->db->num_rows() == 0) {
                return false;
            }

            while ($this->db->next_record()) {
                $props[] = array('name' => $this->db->f('name'),
                                 'type' => $this->db->f('type'));
            }

            return $props;
        } else {
            if ($this->db->num_rows() == 0) {
                return $props;
            }

            while ($this->db->next_record()) {
                $props[$this->db->f('iduserprop')] = array(
                    'name'  => $this->db->f('name'),
                    'type'  => $this->db->f('type'),
                    'value' => $this->db->f('value'),
                );
            }

            return $props;
        }
    }

    /**
     * Stores a property to the database
     * @param string type Specifies the type (class, category etc) for the property to retrieve
     * @param string name Specifies the name of the property to retrieve
     * @param string value Specifies the value to insert
     */
    public function setUserProperty($type, $name, $value)
    {
        global $cfg;

        $value = urlencode($value);

        // Check if such an entry already exists
        if ($this->getUserProperty($type, $name) !== false) {
            $sql = "UPDATE " . $cfg['tab']['user_prop'] . "
                    SET value = '" . $this->db->escape($value) . "'
                    WHERE user_id = '" . $this->db->escape($this->values['user_id']) . "'
                      AND type = '" . $this->db->escape($type) . "'
                      AND name = '" . $this->db->escape($name) . "'";
            $this->db->query($sql);
        } else {
            $sql = "INSERT INTO  " . $cfg['tab']['user_prop'] . "
                    SET value = '" . $this->db->escape($value) . "',
                        user_id = '" . $this->db->escape($this->values['user_id']) . "',
                        type = '" . $this->db->escape($type) . "',
                        name = '" . $this->db->escape($name) . "',
                        iduserprop = " .$this->db->nextid($cfg['tab']['user_prop']);
            $this->db->query($sql);
        }
    }

    /**
     * Deletes a user property from the table.
     * @param   string  $type  Type (class, category etc) of property to retrieve
     * @param   string  $name  Name of property to retrieve
     * @return  bool
     */
    public function deleteUserProperty($type, $name)
    {
        global $cfg;

        // Check if such an entry already exists
        $sql = "DELETE FROM  " . $cfg['tab']['user_prop'] . "
                    WHERE user_id = '" . $this->db->escape($this->values['user_id']) . "' AND
                          type = '" . $this->db->escape($type) . "' AND
                          name = '" . $this->db->escape($name) . "'";
        $this->db->query($sql);
        return ($this->db->affected_rows() === 0) ? false : true;
    }
}

?>