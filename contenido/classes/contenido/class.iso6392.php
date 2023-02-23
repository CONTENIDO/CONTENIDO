<?php

/**
 * This file contains the ISO6392 collection and item class.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Alexander Scheider
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * ISO 639-2 codes collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @method cApiIso6392 createNewItem
 * @method cApiIso6392|bool next
 */
class cApiIso6392Collection extends ItemCollection {
    /**
     * Constructor to create an instance of this class.
     *
     * @param bool $select [optional]
     *                     where clause to use for selection (see ItemCollection::select())
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
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
 * ISO 639-2 code item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiIso6392 extends Item {
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
        parent::__construct(cRegistry::getDbTableName('iso_639_2'), 'iso');
        $this->setFilters([], []);
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }
}
