<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Metatype management class
 *
 * @package CONTENIDO API
 * @version 0.1
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Metatype collection
 *
 * @package CONTENIDO API
 * @subpackage Model
 */
class cApiMetaTypeCollection extends ItemCollection {

    /**
     * Constructor
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['meta_type'], 'idmetatype');
        $this->_setItemClass('cApiMetaType');
    }

    /**
     * Creates a meta type entry.
     *
     * @param string $metatype
     * @param string $fieldtype
     * @param int $maxlength
     * @param string $fieldname
     * @return cApiMetaType
     */
    public function create($metatype, $fieldtype, $maxlength, $fieldname) {
        $oItem = parent::createNewItem();

        $oItem->set('metatype', $metatype);
        $oItem->set('fieldtype', $fieldtype);
        $oItem->set('maxlength', $maxlength);
        $oItem->set('fieldname', $fieldname);
        $oItem->store();

        return $oItem;
    }

}

/**
 * Metatype item
 *
 * @package CONTENIDO API
 * @subpackage Model
 */
class cApiMetaType extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['meta_type'], 'idmetatype');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Userdefined setter for article language fields.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $bSafe Flag to run defined inFilter on passed value
     */
    public function setField($name, $value, $bSafe = true) {
        if ('maxlength' == $name) {
            $value = (int) $value;
        }

        if (is_string($value)) {
            $value = $this->escape($value);
        }

        parent::setField($name, $value, $bSafe);
    }

}
