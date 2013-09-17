<?php
/**
 * This file contains the container collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @version SVN Revision $Rev:$
 *
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Container collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiContainerCollection extends ItemCollection {

    /**
     * Create a new collection of items.
     *
     * @param string $select where clause to use for selection (see
     *        ItemCollection::select())
     */
    public function __construct($select = false) {
        global $cfg;
        parent::__construct($cfg['tab']['container'], 'idcontainer');
        $this->_setItemClass('cApiContainer');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiTemplateCollection');

        if ($select !== false) {
            $this->select($select);
        }
    }

    /**
     *
     * @param int $idtpl
     */
    public function clearAssignments($idtpl) {
        $this->select('idtpl = ' . (int) $idtpl);
        while (($item = $this->next()) !== false) {
            $this->delete($item->get('idcontainer'));
        }
    }

    /**
     *
     * @param int $idtpl
     * @param int $number
     * @param int $idmod
     */
    public function assignModul($idtpl, $number, $idmod) {
        $this->select('idtpl = ' . (int) $idtpl . ' AND number = ' . (int) $number);
        if (($item = $this->next()) !== false) {
            $item->set('idmod', (int) $idmod);
            $item->store();
        } else {
            $this->create($idtpl, $number, $idmod);
        }
    }

    /**
     *
     * @param int $idtpl
     * @param int $number
     * @param int $idmod
     */
    public function create($idtpl, $number, $idmod) {
        $item = parent::createNewItem();
        $item->set('idtpl', (int) $idtpl);
        $item->set('number', (int) $number);
        $item->set('idmod', (int) $idmod);
        $item->store();
    }
}

/**
 * Container item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiContainer extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['container'], 'idcontainer');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }
}
