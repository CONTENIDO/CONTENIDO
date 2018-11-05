<?php

/**
 * This file contains the nav main collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * File collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiNavMainCollection extends ItemCollection {
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['nav_main'], 'idnavm');
        $this->_setItemClass('cApiNavMain');
    }

    /**
     * Create new item with given values.
     *
     * @param string $name
     * @param string $location
     * @param null   $id
     *
     * @return cApiNavMain
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function create($name, $location, $id = null) {
        $item = $this->createNewItem();

        if ($id != null) {
            $item->set('idnavm', $id);
        }

        $item->set('name', $name);
        $item->set('location', $location);
        $item->store();
        return $item;
    }
}

/**
 * NavMain item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiNavMain extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $mId [optional]
     *                   Specifies the ID of item to load
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['nav_main'], 'idnavm');
        $this->setFilters(array(
            'addslashes'
        ), array(
            'stripslashes'
        ));
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }
}
