<?php
/**
 * This file contains the ISO3166 collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @version SVN Revision $Rev:$
 *
 * @author Alexander Scheider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Isocode 3166 collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiIso3166Collection extends ItemCollection {

    /**
     * Create a new collection of items.
     *
     * @param string $select where clause to use for selection (see
     *            ItemCollection::select())
     */
    public function __construct($select = false) {
        parent::__construct(cRegistry::getDbTableName('iso_3166'), 'iso');
        $this->_setItemClass('cApiIso3166');

        if ($select !== false) {
            $this->select($select);
        }
    }
}

/**
 * Iso 3166 item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiIso3166 extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        parent::__construct(cRegistry::getDbTableName('iso_3166'), 'iso');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }
}
