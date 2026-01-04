<?php

/**
 * This file contains the frontend user collection and item class.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Frontend user collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiFrontendUser>
 */
class cApiFrontendUserCollection extends ItemCollection
{

    use cItemCollectionIdsByClientIdTrait;

    /**
     * @var string Client id foreign key field name
     * @since CONTENIDO 4.10.2
     */
    private $fkClientIdName = 'idclient';

    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('frontendusers'), 'idfrontenduser');
        $this->_setItemClass('cApiFrontendUser');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiClientCollection');
    }

    /**
     * Checks if a specific user already exists
     *
     * @param string $sUsername Specifies the username to search for
     * @throws cException
     */
    public function userExists(string $sUsername): bool
    {
        $feUsers = new cApiFrontendUserCollection();
        $feUsers->setWhere('idclient', cRegistry::getClientId());
        $feUsers->setWhere('username', cString::toLowerCase($sUsername));
        $feUsers->query();

        return (bool)$feUsers->next();
    }

    /**
     * Creates a new user
     *
     * @param string $username Specifies the username
     * @param string $password [optional] Specifies the password (optional)
     * @return cApiFrontendUser
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($username, $password = '')
    {
        $client = cRegistry::getClientId();
        $auth = cRegistry::getAuth();

        // Check if the username already exists
        $this->select(sprintf("`idclient` = %d AND `username` = '%s'", $client, $this->escape($username)));
        if ($this->next()) {
            return $this->create($username . '_' . cString::getPartOfString(md5(rand()), 0, 10), $password);
        }

        $item = $this->createNewItem();
        $item->set('idclient', $client);
        $item->set('username', $username);
        $item->set('salt', md5($username . rand(1000, 9999) . rand(1000, 9999) . rand(1000, 9999)));
        $item->set('password', $password);
        $item->set('created', date('Y-m-d H:i:s'), false);
        $item->set('author', $auth->getUserId());
        $item->set('active', 0);

        $item->store();

        // Put this user into the default groups
        $feGroups = new cApiFrontendGroupCollection();
        $feGroups->select(sprintf("`idclient` = %d AND `defaultgroup` = 1", $client));

        $feGroupMembers = new cApiFrontendGroupMemberCollection();

        $iduser = $item->get('idfrontenduser');

        while ($feGroup = $feGroups->next()) {
            $idgroup = $feGroup->get('idfrontendgroup');
            $feGroupMembers->create($idgroup, $iduser);
        }

        return $item;
    }

    /**
     * Overridden delete method to remove user from groupmember table before deleting user.
     *
     * @inheritDoc
     * @param int $id The frontend user id
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function delete($id)
    {
        // delete group memberships
        $feGroupMembers = new cApiFrontendGroupMemberCollection();
        $feGroupMembers->select('`idfrontenduser` = ' . cSecurity::toInteger($id));
        while ($item = $feGroupMembers->next()) {
            $feGroupMembers->delete($item->get('idfrontendgroupmember'));
        }

        // delete user
        return parent::delete($id);
    }

}

/**
 * Frontend user item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiFrontendUser extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id Specifies the ID of item to load
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('frontendusers'), 'idfrontenduser');
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Overridden setField method to md5 the password.
     * Sets the value of a specific field.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        if ($name == 'password') {
            return parent::setField($name, hash('sha256', md5($value) . $this->get('salt')), $safe);
        } else {
            return parent::setField($name, $value, $safe);
        }
    }

    /**
     * Sets the password to a raw value without md5 encoding.
     *
     * @param string $password Raw password
     */
    public function setRawPassword($password): bool
    {
        return $this->setField('password', $password);
    }

    /**
     * Checks if the given password matches the password in the database
     *
     * @param string $password Password to check
     * @return bool True if the password is correct, false otherwise
     */
    public function checkPassword($password): bool
    {
        if ($this->isLoaded() === false) {
            return false;
        }

        $pass = $this->get('password');
        $salt = $this->get('salt');

        return hash('sha256', md5($password) . $salt) == $pass;
    }

    /**
     * Saves modified user entry
     *
     * @inheritDoc
     */
    public function store()
    {
        $auth = cRegistry::getAuth();

        $this->set('modified', date('Y-m-d H:i:s'), false);
        $this->set('modifiedby', $auth->getUserId());
        return parent::store();
    }

    /**
     * Returns list of all groups belonging to current user
     *
     * @return array List of frontend group ids
     * @throws cException
     */
    public function getGroupsForUser(): array
    {
        $feGroupMembers = new cApiFrontendGroupMemberCollection();
        $feGroupMembers->setWhere('idfrontenduser', $this->get('idfrontenduser'));
        $feGroupMembers->query();

        $groups = [];
        while ($feGroupMember = $feGroupMembers->next()) {
            $groups[] = $feGroupMember->get('idfrontendgroup');
        }
        return $groups;
    }

}
