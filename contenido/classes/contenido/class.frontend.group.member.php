<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Frontend group member classes
 *
 * Code is taken over from file contenido/classes/class.frontend.groups.php in
 * favor of
 * normalizing API.
 *
 * @package CONTENIDO API
 * @version 0.1
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since file available since CONTENIDO release 4.9.0
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Frontend group member collection
 *
 * @package CONTENIDO API
 * @subpackage Model
 */
class cApiFrontendGroupMemberCollection extends ItemCollection {

    /**
     * Constructor Function
     *
     * @param none
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['frontendgroupmembers'], 'idfrontendgroupmember');
        $this->_setItemClass('cApiFrontendGroupMember');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiFrontendGroupCollection');
        $this->_setJoinPartner('cApiFrontendUserCollection');
    }

    /**
     * Creates a new association
     *
     * @param $idfrontendgroup int specifies the frontend group
     * @param $idfrontenduser int specifies the frontend user
     * @return cApiFrontendGroupMember bool
     */
    public function create($idfrontendgroup, $idfrontenduser) {
        $this->select('idfrontendgroup = ' . (int) $idfrontendgroup . ' AND idfrontenduser = ' . (int) $idfrontenduser);

        if ($this->next()) {
            return false;
        }

        $item = parent::createNewItem();

        $item->set('idfrontenduser', $idfrontenduser);
        $item->set('idfrontendgroup', $idfrontendgroup);
        $item->store();

        return $item;
    }

    /**
     * Removes an association
     *
     * @param int $idfrontendgroup Specifies the frontend group
     * @param int $idfrontenduser Specifies the frontend user
     */
    public function remove($idfrontendgroup, $idfrontenduser) {
        $this->select('idfrontendgroup = ' . (int) $idfrontendgroup . ' AND idfrontenduser = ' . (int) $idfrontenduser);

        if (($item = $this->next()) !== false) {
            $this->delete($item->get('idfrontendgroupmember'));
        }
    }

    /**
     * Returns all users in a single group
     *
     * @param int $idfrontendgroup specifies the frontend group
     * @param bool $asObjects Specifies if the function should return objects
     * @return array List of frontend user ids or cApiFrontendUser items
     */
    public function getUsersInGroup($idfrontendgroup, $asObjects = true) {
        $this->select('idfrontendgroup = ' . (int) $idfrontendgroup);

        $objects = array();

        while (($item = $this->next()) !== false) {
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
 * Frontend group member item
 *
 * @package CONTENIDO API
 * @subpackage Model
 */
class cApiFrontendGroupMember extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['frontendgroupmembers'], 'idfrontendgroupmember');
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

}
