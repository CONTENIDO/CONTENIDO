<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Template access class
 *
 * @package CONTENIDO API
 * @version 1.4
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Container collection
 *
 * @package CONTENIDO API
 * @subpackage Model
 */
class cApiContainerCollection extends ItemCollection {

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

    public function clearAssignments($idtpl) {
        $this->select('idtpl = ' . (int) $idtpl);
        while (($item = $this->next()) !== false) {
            $this->delete($item->get('idcontainer'));
        }
    }

    public function assignModul($idtpl, $number, $idmod) {
        $this->select('idtpl = ' . (int) $idtpl . ' AND number = ' . (int) $number);
        if (($item = $this->next()) !== false) {
            $item->set('idmod', (int) $idmod);
            $item->store();
        } else {
            $this->create($idtpl, $number, $idmod);
        }
    }

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
 * @package CONTENIDO API
 * @subpackage Model
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
