<?php

/**
 * This file contains the frontend group memeber collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Frontend group member collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiFrontendGroupMemberCollection extends ItemCollection {

    /**
     * Constructor Function
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
     * @todo Should return null in case of failure
     * @param int $idfrontendgroup
     *         specifies the frontend group
     * @param int $idfrontenduser
     *         specifies the frontend user
     * @return cApiFrontendGroupMember|false
     */
    public function create($idfrontendgroup, $idfrontenduser) {
        $this->select('idfrontendgroup = ' . (int) $idfrontendgroup . ' AND idfrontenduser = ' . (int) $idfrontenduser);

        if ($this->next()) {
            return false;
        }

        $item = $this->createNewItem();

        $item->set('idfrontenduser', $idfrontenduser);
        $item->set('idfrontendgroup', $idfrontendgroup);
        $item->store();

        return $item;
    }

    /**
     * Removes an association
     *
     * @param int $idfrontendgroup
     *         Specifies the frontend group
     * @param int $idfrontenduser
     *         Specifies the frontend user
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
     * @param int $idfrontendgroup
     *         specifies the frontend group
     * @param bool $asObjects [optional]
     *         Specifies if the function should return objects
     * @return array
     *         List of frontend user ids or cApiFrontendUser items
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
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiFrontendGroupMember extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId [optional]
     *         Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['frontendgroupmembers'], 'idfrontendgroupmember');
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }
}
