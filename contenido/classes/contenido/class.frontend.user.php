<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Frontend user classes
 *
 * Code is taken over from file contenido/classes/class.frontend.users.php in favor of
 * normalizing API.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO API
 * @version    0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9.0
 *
 * {@internal
 *   created  2011-10-07
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Frontend user collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiFrontendUserCollection extends ItemCollection
{
    /**
     * Constructor function
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg['tab']['frontendusers'], 'idfrontenduser');
        $this->_setItemClass('cApiFrontendUser');
    }

    /**
     * Checks if a specific user already exists
     *
     * @param   string  $sUsername  specifies the username to search for
     * @return  bool
     */
    public function userExists($sUsername)
    {
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
     * @param  string  $username  Specifies the username
     * @param  string  $password  Specifies the password (optional)
     * @return  cApiFrontendUser
     */
    public function create($username, $password = '')
    {
        global $client, $auth;

        // Check if the username already exists
        $this->select("idclient=" . (int) $client . " AND username='" . urlencode($username) . "'");

        if ($this->next()) {
            return $this->create($username . '_' . substr(md5(rand()), 0, 10), $password);
        }

        $item = parent::create();
        $item->set('idclient', $client);
        $item->set('username', $username);
        $item->set('password', $password);
        $item->set('created', date('Y-m-d H:i:s'), false);
        $item->set('author', $auth->auth['uid']);
        $item->set('active', 0);

        $item->store();

        // Put this user into the default groups
        $feGroups = new cApiFrontendGroupCollection();
        $feGroups->select("idclient=" . (int) $client . " AND defaultgroup=1");

        $feGroupMembers = new cApiFrontendGroupMemberCollection();

        $iduser = $item->get('idfrontenduser');

        while ($feGroup = $feGroups->next()) {
            $idgroup = $feGroup->get('idfrontendgroup');
            $feGroupMembers->create($idgroup, $iduser);
        }

        return $item;
    }

    /**
     * Overridden delete method to remove user from groupmember table
     * before deleting user.
     *
     * @param   int  $itemId  specifies the frontend user
     * @return  bool
     */
    public function delete($itemId)
    {
        // delete group memberships
        $feGroupMembers = new cApiFrontendGroupMemberCollection();
        $feGroupMembers->select('idfrontenduser=' . (int) $itemId);
        while ($item = $feGroupMembers->next()) {
            $feGroupMembers->delete($item->get('idfrontendgroupmember'));
        }

        // delete user
        return parent::delete($itemId);
    }
}


/**
 * Frontend user item
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiFrontendUser extends Item
{
    /**
     * Constructor function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
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
     * @param  string  $field  Specifies the field to set
     * @param  string  $value  Specifies the value to set
     * @param  bool    $safe  Flag to use defined inFilter
     * @return  bool
     */
    public function setField($field, $value, $safe = true)
    {
        if ($field == 'password') {
            return parent::setField($field, md5($value), $safe);
        } else {
            return parent::setField($field, $value, $safe);
        }
    }

    /**
     * Sets the password to a raw value without md5 encoding.
     *
     * @param  string $password Raw password
     * @return  bool
     */
    public function setRawPassword($password)
    {
        return parent::setField('password', $password);
    }

    /**
     * Checks if the given password matches the password in the database
     *
     * @param   string  $password  Password to check
     * @return  bool  True if the password is correct, false otherwise
     */
    public function checkPassword($password)
    {
        return (md5($password) == $this->get('password'));
    }

    /**
     * Saves modified user entry
     *
     * @return  bool
     */
    public function store()
    {
        global $auth;

        $this->set('modified', date('Y-m-d H:i:s'), false);
        $this->set('modifiedby', $auth->auth['uid']);
        return parent::store();
    }

    /**
     * Returns list of all groups belonging to current user
     *
     * @return  array  List of frontend group ids
     */
    public function getGroupsForUser()
    {
        $feGroupMembers = new cApiFrontendGroupMemberCollection();
        $feGroupMembers->setWhere('idfrontenduser', $this->get('idfrontenduser'));
        $feGroupMembers->query();

        $groups = array();
        while ($feGroupMember = $feGroupMembers->next()) {
            $groups[] = $feGroupMember->get('idfrontendgroup');
        }
        return $groups;
    }
}


################################################################################
# Old versions of frontend user item collection and frontend user item classes
#
# NOTE: Class implemetations below are deprecated and the will be removed in
#       future versions of contenido.
#       Don't use them, they are still available due to downwards compatibility.


/**
 * Frontend user collection
 * @deprecated  [2011-10-07] Use cApiFrontendUserCollection instead of this class.
 */
class FrontendUserCollection extends cApiFrontendUserCollection
{
    public function __construct()
    {
        cDeprecated("Use class cApiFrontendUserCollection instead");
        parent::__construct();
    }
    public function FrontendUserCollection()
    {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }
}


/**
 * Single frontend user item
 * @deprecated  [2011-10-07] Use cApiFrontendUser instead of this class.
 */
class FrontendUser extends cApiFrontendUser
{
    public function __construct($mId = false)
    {
        cDeprecated("Use class cApiFrontendUser instead");
        parent::__construct($mId);
    }
    public function FrontendUser($mId = false)
    {
        cDeprecated("Use __construct() instead");
        $this->__construct($mId);
    }
}

?>