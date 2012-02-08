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
 * @package    CONTENIDO API
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


/**
 * User collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
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


/**
 * User item
 * @package    CONTENIDO API
 * @subpackage Model
 */
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
     * Retrieves the effective user property.
     * @param  string  $type   Type (class, category etc) for the property to retrieve
     * @param  string  $name   Name of the property to retrieve
     * @param  bool    $group  Flag to search in groups
     * @return string|bool  The value of the retrieved property or false
     */
    public function getUserProperty($type, $name, $group = false)
    {
        global $perm;

        if (!is_object($perm)) {
            $perm = new Contenido_Perm();
        }

        $result = false;

        if ($group == true) {
            // first get property by existing groups, if desired
            $groups = $perm->getGroupsForUser($this->values['user_id']);
            foreach ($groups as $groupid) {
                $groupPropColl = new cApiGroupPropertyCollection($groupid);
                $groupProp = $groupPropColl->fetchByGroupIdTypeName($type, $name);
                if ($groupProp) {
                    $result = $groupProp->get('value');
                }
            }
        }

        // get property of user
        $userPropColl = new cApiUserPropertyCollection($this->values['user_id']);
        $userProp = $userPropColl->fetchByUserIdTypeName($type, $name);
        if ($userProp) {
            $result = $userProp->get('value');
        }

        return ($result !== false) ? urldecode($result) : false;
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
     *                  - $arr[name] = value
     */
    public function getUserPropertiesByType($type, $group = false)
    {
        global $perm;

        if (!is_object($perm)) {
            $perm = new Contenido_Perm();
        }

        $props = array();

        if ($group == true) {
            // first get properties by existing groups, if desired
            $groups = $perm->getGroupsForUser($this->values['user_id']);
            foreach ($groups as $groupid) {
                $groupPropColl = new cApiGroupPropertyCollection($groupid);
                $groupProps = $groupPropColl->fetchByGroupIdType($type);
                foreach ($groupProps as $groupProp) {
                    $props[$groupProp->get('name')] = urldecode($groupProp->get('value'));
                }
            }
        }

        // get properties of user
        $userPropColl = new cApiUserPropertyCollection($this->values['user_id']);
        $userProps = $userPropColl->fetchByUserIdType($type);
        foreach ($userProps as $userProp) {
            $props[$userProp->get('name')] = urldecode($userProp->get('value'));
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
        $userPropColl = new cApiUserPropertyCollection($this->values['user_id']);
        $userProps = $userPropColl->fetchByUserId();

        $props = array();

        if (true === $beDownwardsCompatible) {
            // @deprecated  [2011-11-03]
            if (count($userProps) == 0) {
                return false;
            }

            foreach ($userProps as $userProp) {
                $props[] = array(
                    'name' => $userProp->get('name'),
                    'type' => $userProp->get('type')
                );
            }
        } else {
            foreach ($userProps as $userProp) {
                $props[$userProp->get('iduserprop')] = array(
                    'name'  => $userProp->get('name'),
                    'type'  => $userProp->get('type'),
                    'value' => $userProp->get('value'),
                );
            }
        }

        return $props;
    }

    /**
     * Stores a property to the database
     * @param  string  $type  Type (class, category etc) for the property to retrieve
     * @param  string  $name  Name of the property to retrieve
     * @param  string  $value Value to insert
     * @return cApiUserProperty
     */
    public function setUserProperty($type, $name, $value)
    {
        $userPropColl = new cApiUserPropertyCollection($this->values['user_id']);
        $userProps = $userPropColl->setValueByTypeName($type, $name, $value);
    }

    /**
     * Deletes a user property from the table.
     * @param   string  $type  Type (class, category etc) of property to retrieve
     * @param   string  $name  Name of property to retrieve
     * @return  bool
     */
    public function deleteUserProperty($type, $name)
    {
        $userPropColl = new cApiUserPropertyCollection($this->values['user_id']);
        return $userPropColl->deleteByUserIdTypeName($type, $name);
    }
}

?>