<?php
/**
 * This file contains the container configuration collection and item class.
 *
 * @package          Core
 * @subpackage       GenericDB_Model
 * @version          SVN Revision $Rev:$
 *
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Container configuration collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiContainerConfigurationCollection extends ItemCollection {

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

    public function create($idtplcfg, $number, $container) {
        $item = parent::createNewItem();
        $item->set('idtplcfg', (int) $idtplcfg);
        $item->set('number', (int) $number);
        $item->set('container', $this->escape($container));
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
}
