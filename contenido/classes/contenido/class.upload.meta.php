<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Upload meta class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO API
 * @version    1.0
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * Upload meta collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiUploadMetaCollection extends ItemCollection {
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['upl_meta'], 'id_uplmeta');
        $this->_setItemClass('cApiUploadMeta');
    }
}


/**
 * Upload meta item
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiUploadMeta extends Item {
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['upl_meta'], 'id_uplmeta');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }
}

?>