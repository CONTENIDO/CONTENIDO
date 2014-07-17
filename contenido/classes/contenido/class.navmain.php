<?php
/**
 * This file contains the nav main collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @version SVN Revision $Rev:$
 *
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
     * Constructor
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['nav_main'], 'idnavm');
        $this->_setItemClass('cApiNavMain');
    }

    /**
     * Create new item with given values.
     *
     * @param string $location
     * @return cApiNavMain
     */
    public function create($location) {
        $item = $this->createNewItem();
        $item->set('location', cSecurity::escapeString($location));
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
class cApiNavMain extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
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
