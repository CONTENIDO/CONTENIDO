<?php
/**
 * This file contains the frontend user collection and item class.
 *
 * @package          Core
 * @subpackage       GenericDB_Model
 * @version          SVN Revision $Rev:$
 *
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Frontend user collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiFrontendUserCollection extends ItemCollection {

    /**
     * Constructor function
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['frontendusers'], 'idfrontenduser');
        $this->_setItemClass('cApiFrontendUser');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiClientCollection');
    }

    /**
     * Checks if a specific user already exists
     *
     * @param string $sUsername specifies the username to search for
     * @return bool
     */
    public function userExists($sUsername) {
        global $client;

        $feUsers = new cApiFrontendUserCollection();
        $feUsers->setWhere('idclient', $client);
        $feUsers->setWhere('username', strtolower($sUsername));
        $feUsers->query();

        return ($feUsers->next()) ? true : false;
    }

    /**
     * Creates a new user
     *
     * @param string $username Specifies the username
     * @param string $password Specifies the password (optional)
     * @return cApiFrontendUser
     */
    public function create($username, $password = '') {
        global $client, $auth;

        // Check if the username already exists
        $this->select("idclient = " . (int) $client . " AND username = '" . $this->escape($username) . "'");

        if ($this->next()) {
            return $this->create($username . '_' . substr(md5(rand()), 0, 10), $password);
        }

        $item = $this->createNewItem();
        $item->set('idclient', $client);
        $item->set('username', $username);
        $item->set('salt', md5($username . rand(1000, 9999) . rand(1000, 9999) . rand(1000, 9999)));
        $item->set('password', $password);
        $item->set('created', date('Y-m-d H:i:s'), false);
        $item->set('author', $auth->auth['uid']);
        $item->set('active', 0);

        $item->store();

        // Put this user into the default groups
        $feGroups = new cApiFrontendGroupCollection();
        $feGroups->select("idclient = " . (int) $client . " AND defaultgroup = 1");

        $feGroupMembers = new cApiFrontendGroupMemberCollection();

        $iduser = $item->get('idfrontenduser');

        while (($feGroup = $feGroups->next()) !== false) {
            $idgroup = $feGroup->get('idfrontendgroup');
            $feGroupMembers->create($idgroup, $iduser);
        }

        return $item;
    }

    /**
     * Overridden delete method to remove user from groupmember table
     * before deleting user.
     *
     * @param int $itemId specifies the frontend user
     * @return bool
     */
    public function delete($itemId) {
        // delete group memberships
        $feGroupMembers = new cApiFrontendGroupMemberCollection();
        $feGroupMembers->select('idfrontenduser = ' . (int) $itemId);
        while (($item = $feGroupMembers->next()) !== false) {
            $feGroupMembers->delete($item->get('idfrontendgroupmember'));
        }

        // delete user
        return parent::delete($itemId);
    }

}

/**
 * Frontend user item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiFrontendUser extends Item {

    /**
     * Constructor function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['frontendusers'], 'idfrontenduser');
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Overridden setField method to md5 the password.
     * Sets the value of a specific field.
     *
     * @param string $field Specifies the field to set
     * @param string $value Specifies the value to set
     * @param bool $safe Flag to use defined inFilter
     * @return bool
     */
    public function setField($field, $value, $safe = true) {
        if ($field == 'password') {
            return parent::setField($field, hash('sha256', md5($value) . $this->get('salt')), $safe);
        } else {
            return parent::setField($field, $value, $safe);
        }
    }

    /**
     * Sets the password to a raw value without md5 encoding.
     *
     * @param string $password Raw password
     * @return bool
     */
    public function setRawPassword($password) {
        return $this->setField('password', $password);
    }

    /**
     * Checks if the given password matches the password in the database
     *
     * @param string $password Password to check
     * @return bool True if the password is correct, false otherwise
     */
    public function checkPassword($password) {
        if ($this->isLoaded() === false) {
            return false;
        }

        $pass = $this->get('password');
        $salt = $this->get('salt');

        return (hash('sha256', md5($password) . $salt) == $pass);
    }

    /**
     * Saves modified user entry
     *
     * @return bool
     */
    public function store() {
        global $auth;

        $this->set('modified', date('Y-m-d H:i:s'), false);
        $this->set('modifiedby', $auth->auth['uid']);
        return parent::store();
    }

    /**
     * Returns list of all groups belonging to current user
     *
     * @return array List of frontend group ids
     */
    public function getGroupsForUser() {
        $feGroupMembers = new cApiFrontendGroupMemberCollection();
        $feGroupMembers->setWhere('idfrontenduser', $this->get('idfrontenduser'));
        $feGroupMembers->query();

        $groups = array();
        while (($feGroupMember = $feGroupMembers->next()) !== false) {
            $groups[] = $feGroupMember->get('idfrontendgroup');
        }
        return $groups;
    }

}
