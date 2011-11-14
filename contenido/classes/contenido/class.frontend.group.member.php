<?php
/**
 * Project: 
 * CONTENIDO Content Management System
 * 
 * Description: 
 * Frontend group member classes
 *
 * Code is taken over from file contenido/classes/class.frontend.groups.php in favor of
 * normalizing API.
 *
 * Requirements: 
 * @con_php_req 5.0
 *
 * @package    CONTENIDO Backend Classes
 * @version    0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9.0
 * 
 * {@internal
 *   created  2011-09-20
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * Frontend group member collection
 */
class cApiFrontendGroupMemberCollection extends ItemCollection
{
    /**
     * Constructor Function
     * @param none
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg['tab']['frontendgroupmembers'], 'idfrontendgroupmember');
        $this->_setJoinPartner('cApiFrontendGroupCollection');
        $this->_setJoinPartner('cApiFrontendUserCollection');
        $this->_setItemClass('cApiFrontendGroupMember');
    }

    /**
     * Creates a new association
     * @param $idfrontendgroup int specifies the frontend group
     * @param $idfrontenduser  int specifies the frontend user
     * @return  cApiFrontendGroupMember|bool
     */
    public function create($idfrontendgroup, $idfrontenduser)
    {
        $this->select('idfrontendgroup = ' . (int) $idfrontendgroup . ' AND idfrontenduser = ' . (int) $idfrontenduser);

        if ($this->next()) {
            return false;
        }

        $item = parent::create();

        $item->set('idfrontenduser', $idfrontenduser);
        $item->set('idfrontendgroup', $idfrontendgroup);
        $item->store();

        return $item;
    }

    /**
     * Removes an association
     * @param  int  $idfrontendgroup  Specifies the frontend group
     * @param  int  $idfrontenduser  Specifies the frontend user
     */
    public function remove($idfrontendgroup, $idfrontenduser)
    {
        $this->select('idfrontendgroup = ' . (int) $idfrontendgroup . ' AND idfrontenduser = ' . (int) $idfrontenduser);

        if ($item = $this->next()) {
            $this->delete($item->get('idfrontendgroupmember'));
        }
    }

    /**
     * Returns all users in a single group
     * @param  int  $idfrontendgroup  specifies the frontend group
     * @param  bool  $asObjects  Specifies if the function should return objects
     * @return array List of frontend user ids or cApiFrontendUser items 
     */
    public function getUsersInGroup($idfrontendgroup, $asObjects = true)
    {
        $this->select('idfrontendgroup = ' . (int) $idfrontendgroup);

        $objects = array();

        while ($item = $this->next()) {
            if ($asObjects) {
                $user = new cApiFrontendUser();
                $user->loadByPrimaryKey($item->get('idfrontenduser'));
                $objects[] = $user;
            } else {
                $objects[] = $item->get('idfrontenduser');
            }
        }

        return ($objects);
    }
}


/**
 * Single cApiFrontendGroupMember item
 */
class cApiFrontendGroupMember extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['frontendgroupmembers'], 'idfrontendgroupmember');
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }
}


################################################################################
# Old versions of frontend group member item collection and frontend group member
# item classes
#
# NOTE: Class implemetations below are deprecated and the will be removed in 
#       future versions of contenido.
#       Don't use them, they are still available due to downwards compatibility.


/**
 * Frontend group member collection
 * @deprecated  [2011-09-20] Use cApiFrontendGroupMemberCollection instead of this class.
 */
class FrontendGroupMemberCollection extends cApiFrontendGroupMemberCollection
{
    public function __construct()
    {
        cWarning(__FILE__, __LINE__, 'Deprecated class ' . __CLASS__ . ' use ' . get_parent_class($this));
        parent::__construct();
    }
    public function FrontendGroupMemberCollection()
    {
        cWarning(__FILE__, __LINE__, 'Deprecated method call, use __construct()');
        $this->__construct();
    }
}


/**
 * Single frontend group member item
 * @deprecated  [2011-09-20] Use cApiFrontendGroupMember instead of this class.
 */
class FrontendGroupMember extends cApiFrontendGroupMember
{
    public function __construct($mId = false)
    {
        cWarning(__FILE__, __LINE__, 'Deprecated class ' . __CLASS__ . ' use ' . get_parent_class($this));
        parent::__construct($mId);
    }
    public function FrontendGroupMember($mId = false)
    {
        cWarning(__FILE__, __LINE__, 'Deprecated method call, use __construct()');
        $this->__construct($mId);
    }
}

?>