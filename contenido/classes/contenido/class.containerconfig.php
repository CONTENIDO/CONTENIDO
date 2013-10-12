<?php
/**
 * This file contains the container configuration collection and item class.
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
 * Container configuration collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiContainerConfigurationCollection extends ItemCollection {

    /**
     * Create a new collection of items.
     *
     * @param string $select where clause to use for selection (see
     *            ItemCollection::select())
     */
    public function __construct($select = false) {
        global $cfg;
        parent::__construct($cfg['tab']['container_conf'], 'idcontainerc');
        $this->_setItemClass('cApiContainerConfiguration');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiTemplateConfigurationCollection');

        if ($select !== false) {
            $this->select($select);
        }
    }

    /**
     *
     * @param int $idtplcfg
     * @param int $number
     * @param string $container
     */
    public function create($idtplcfg, $number, $container) {
        $item = parent::createNewItem();
        $item->set('idtplcfg', $idtplcfg);
        $item->set('number', $number);
        $item->set('container', $container);
        $item->store();
    }
}

/**
 * Container configuration item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiContainerConfiguration extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['container_conf'], 'idcontainerc');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Userdefined setter for container config fields.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $bSafe Flag to run defined inFilter on passed value
     * @todo should return return value of overloaded method
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'idtplcfg':
            case 'number':
                $value = (int) $value;
                break;
        }

        parent::setField($name, $value, $bSafe);
    }
}
