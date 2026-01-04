<?php

/**
 * This file contains the ISO3166 collection and item class.
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
 * ISO 3166 country codes collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiIso3166>
 */
class cApiIso3166Collection extends ItemCollection
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param string|false $select [optional] Where clause to use for selection {@see ItemCollection::select()}
     * @throws cDbException|cInvalidArgumentException
     */
    public function __construct($select = false)
    {
        parent::__construct(cDb::getTableName('iso_3166'), 'iso');
        $this->_setItemClass('cApiIso3166');

        if ($select !== false) {
            $this->select($select);
        }
    }
}

/**
 * ISO 3166 country code item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiIso3166 extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id Specifies the ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('iso_3166'), 'iso');
        $this->setFilters();
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }
}
