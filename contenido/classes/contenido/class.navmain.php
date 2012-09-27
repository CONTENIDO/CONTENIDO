<?php
/**
 * NavMain Management System
 *
 * @package CONTENIDO API
 * @subpackage Model
 * @version SVN Revision $Rev:$
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * File collection
 *
 * @package CONTENIDO API
 * @subpackage Model
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

    public function create($location) {
        $item = parent::createNewItem();

        $location = cSecurity::escapeString($location);
        $item->set('location', $location);

        $item->store();

        return ($item);
    }

}

/**
 * NavMain item
 *
 * @package CONTENIDO API
 * @subpackage Model
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
