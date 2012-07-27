<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Area management class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO API
 * @version    1.3
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2004-08-04
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * File collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiFileCollection extends ItemCollection {

    /**
     * Constructor
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['files'], 'idfile');
        $this->_setItemClass('cApiFile');
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiFileCollection() {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }

    public function create($area, $filename, $filetype = 'main') {
        $item = parent::createNewItem();

        if (is_string($area)) {
            $c = new cApiArea();
            $c->loadBy('name', $area);

            if ($c->virgin) {
                $area = 0;
                cWarning(__FILE__, __LINE__, "Could not resolve area [$area] passed to method [create], assuming 0");
            } else {
                $area = $c->get('idarea');
            }
        }

        $item->set('idarea', $area);
        $item->set('filename', $filename);

        if ($filetype != 'main') {
            $item->set('filetype', 'inc');
        } else {
            $item->set('filetype', 'main');
        }

        $item->store();

        return ($item);
    }

}

/**
 * File item
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiFile extends Item {

    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['files'], 'idfile');
        $this->setFilters(array('addslashes'), array('stripslashes'));
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiFile($mId = false) {
        cDeprecated("Use __construct() instead");
        $this->__construct($mId);
    }

}

?>