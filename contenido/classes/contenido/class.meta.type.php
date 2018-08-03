<?php

/**
 * This file contains the meta type collection and item class.
 *
 * @package          Core
 * @subpackage       GenericDB_Model
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Metatype collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiMetaTypeCollection extends ItemCollection {

    /**
     * Constructor to create an instance of this class.
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
     * @param int    $maxlength
     * @param string $fieldname
     *
     * @return cApiMetaType
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function create($metatype, $fieldtype, $maxlength, $fieldname) {
        $oItem = $this->createNewItem();

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
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiMetaType extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $mId
     *         Specifies the ID of item to load
     *
     * @throws Exception
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
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
     * @param bool $bSafe [optional]
     *         Flag to run defined inFilter on passed value
     * @return bool
     */
    public function setField($name, $value, $bSafe = true) {
        if ('maxlength' == $name) {
            $value = (int) $value;
        }

        return parent::setField($name, $value, $bSafe);
    }

}
