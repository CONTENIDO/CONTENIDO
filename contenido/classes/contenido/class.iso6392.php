<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Isocodes access class
 *
 * @package CONTENIDO API
 * @version 1.0
 * @author Alexander Scheider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since Contenido 4.9
 */
if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Isocode 639-2 collection
 *
 * @package CONTENIDO API
 * @subpackage Model
 */
class cApiIso6392Collection extends ItemCollection {

    public function __construct($select = false) {
        parent::__construct(cRegistry::getDbTableName('iso_639_2'), 'iso');
        $this->_setItemClass('cApiIso6392');

        if ($select !== false) {
            $this->select($select);
        }
    }

}

/**
 * Iso 639-2 item
 *
 * @package CONTENIDO API
 * @subpackage Model
 */
class cApiIso6392 extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        parent::__construct(cRegistry::getDbTableName('iso_639_2'), 'iso');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

}
