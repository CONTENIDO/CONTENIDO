<?php

/**
 * This file contains the ISO6392 collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @author Alexander Scheider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Isocode 639-2 collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiIso6392Collection extends ItemCollection {

    /**
     * Create a new collection of items.
     *
     * @param string $select [optional]
     *         where clause to use for selection (see ItemCollection::select())
     */
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
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiIso6392 extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId [optional]
     *         Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        parent::__construct(cRegistry::getDbTableName('iso_639_2'), 'iso');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }
}
