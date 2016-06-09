<?php

/**
 * This file contains the frontend group collection and item class.
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
 * Frontend group collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiFrontendGroupCollection extends ItemCollection {

    /**
     * Constructor to create an instance of this class.
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['frontendgroups'], 'idfrontendgroup');
        $this->_setItemClass('cApiFrontendGroup');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiClientCollection');
    }

    /**
     * Creates a new group
     *
     * @param string $groupname
     *         Specifies the groupname
     * @return cApiFrontendGroup
     */
    public function create($groupname) {
        global $client;

        $group = new cApiFrontendGroup();

        // _arrInFilters = array('urlencode', 'htmlspecialchars', 'addslashes');

        $mangledGroupName = $group->_inFilter($groupname);
        $this->select("idclient = " . cSecurity::toInteger($client) . " AND groupname = '" . $mangledGroupName . "'");

        if (($obj = $this->next()) !== false) {
            $groupname = $groupname . md5(rand());
        }

        $item = $this->createNewItem();
        $item->set('idclient', $client);
        $item->set('groupname', $groupname);
        $item->store();

        return $item;
    }

    /**
     * Overridden delete method to remove groups from groupmember table
     * before deleting group
     *
     * @todo should return return value of overloaded method
     * @param int $itemID
     *         specifies the frontend user group
     */
    public function delete($itemID) {
        $associations = new cApiFrontendGroupMemberCollection();
        $associations->select('idfrontendgroup = ' . (int) $itemID);

        while (($item = $associations->next()) !== false) {
            $associations->delete($item->get('idfrontendgroupmember'));
        }
        parent::delete($itemID);
    }
}

/**
 * Frontend group item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiFrontendGroup extends Item {

    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $mId [optional]
     *         Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['frontendgroups'], 'idfrontendgroup');
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }
}
