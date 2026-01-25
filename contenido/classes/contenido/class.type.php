<?php

/**
 * This file contains the type collection and item class.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Type collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiType>
 */
class cApiTypeCollection extends ItemCollection
{
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('type'), 'idtype');
        $this->_setItemClass('cApiType');
    }

    /**
     * Creates a type entry.
     *
     * @param string $type
     * @param string $description
     * @param string $code [optional]
     * @param int $status [optional]
     * @param string $author [optional]
     * @param string $created [optional]
     * @param string $lastmodified [optional]
     * @return cApiType
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($type, $description, $code = '', $status = 0, $author = '', $created = '', $lastmodified = '')
    {
        if (empty($author)) {
            $author = cRegistry::getAuth()->getUsername();
        }
        if (empty($created)) {
            $created = date('Y-m-d H:i:s');
        }
        if (empty($lastmodified)) {
            $lastmodified = date('Y-m-d H:i:s');
        }

        $item = $this->createNewItem();

        $item->set('type', $type);
        $item->set('description', $description);
        $item->set('code', $code);
        $item->set('status', $status);
        $item->set('author', $author);
        $item->set('created', $created);
        $item->set('lastmodified', $lastmodified);
        $item->store();

        return $item;
    }

}

/**
 * Type item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiType extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id The ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('type'), 'idtype');
        $this->setFilters();
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Loads a type entry by its type.
     *
     * @param string $type e.g. CMS_HTML, CMS_TEXT, etc.
     * @throws cException
     */
    public function loadByType(string $type): bool
    {
        $recordSet = $this->_oCache->getItemByProperties(['type' => $type]);
        if ($recordSet) {
            // entry in cache found, load entry from cache
            $this->loadByRecordSet($recordSet);
            return true;
        } else {
            $where = $this->db->prepare("`type` = '%s'", $type);
            return $this->_loadByWhereClause($where);
        }
    }

    /**
     * User-defined setter for item fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        if ('status' === $name) {
            $value = cSecurity::toInteger($value);
        }

        return parent::setField($name, $value, $safe);
    }

}
