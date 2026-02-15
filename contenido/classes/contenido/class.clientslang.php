<?php

/**
 * This file contains the client language collection and item class.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Client language collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiClientLanguage>
 */
class cApiClientLanguageCollection extends ItemCollection
{

    use cItemCollectionIdsByClientIdAndLanguageIdTrait;

    /**
     * @var string Client id foreign key field name
     * @since CONTENIDO 4.10.2
     */
    private $fkClientIdName = 'idclient';

    /**
     * @var string Language id foreign key field name
     * @since CONTENIDO 4.10.2
     */
    private $fkLanguageIdName = 'idlang';

    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('clients_lang'), 'idclientslang');
        $this->_setItemClass('cApiClientLanguage');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiClientCollection');
        $this->_setJoinPartner('cApiLanguageCollection');
    }

    /**
     * Creates a client language entry.
     *
     * @param int $clientId
     * @param int $languageId
     * @return cApiClientLanguage
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($clientId, $languageId)
    {
        $oItem = $this->createNewItem();
        $oItem->set('idclient', $clientId, false);
        $oItem->set('idlang', $languageId, false);
        $oItem->store();
        return $oItem;
    }

    /**
     * Checks if a language is associated with a given list of clients.
     *
     * @param int $languageId Language id which should be checked
     * @throws cDbException
     */
    public function hasLanguageInClients($languageId, array $clientIds): bool
    {
        $languageId = cSecurity::toInteger($languageId);
        $clientIds = array_map('intval', $clientIds);
        $where = ' `idlang` = ' . $languageId . ' AND `idclient` IN (' . implode(',', $clientIds) . ')';
        return $this->flexSelect('', '', $where);
    }

    /**
     * Returns list of languages (language ids) by passed client.
     *
     * @throws cDbException
     * @return int[] List of language ids.
     */
    public function getLanguagesByClient($clientId): array
    {
        $list = [];
        $sql = "SELECT `idlang` FROM `%s` WHERE `idclient` = %d";
        $this->db->query($sql, $this->table, $clientId);
        while ($this->db->nextRecord()) {
            $list[] = cSecurity::toInteger( $this->db->f('idlang'));
        }
        return $list;
    }

    /**
     * Returns all languages (language ids and names) of a client
     *
     * @param int $clientId
     * @return array<int, string> List of languages where the key is the language id and value the language name
     * @throws cDbException
     */
    public function getLanguageNamesByClient($clientId): array
    {
        $list = [];
        $sql = "SELECT l.idlang AS idlang, l.name AS name
                FROM `%s` AS cl, `%s` AS l
                WHERE idclient = %d AND cl.idlang = l.idlang
                ORDER BY idlang ASC";

        $this->db->query($sql, $this->table, cDb::getTableName('lang'), $clientId);
        while ($this->db->nextRecord()) {
            $list[cSecurity::toInteger($this->db->f('idlang'))] = $this->db->f('name');
        }

        return $list;
    }

    /**
     * Returns all languages of a client. Merges the values from language and client language
     * table and returns them back.
     *
     * @param int $clientId
     * @return array<int, array> List of languages where the key is the language id and value an
     *      associative array merged by fields from language and client language table
     * @throws cDbException
     */
    public function getAllLanguagesByClient($clientId): array
    {
        $list = [];
        $sql = "SELECT *
                FROM `%s` AS cl, `%s` AS l
                WHERE cl.idclient = %d AND cl.idlang = l.idlang
                ORDER BY l.idlang ASC";

        $this->db->query($sql, $this->table, cDb::getTableName('lang'), $clientId);
        while ($this->db->nextRecord()) {
            $list[cSecurity::toInteger($this->db->f('idlang'))] = $this->db->toArray();
        }

        return $list;
    }

    /**
     * Returns the id of first language for a specific client.
     *
     * @param int $clientId
     * @throws cDbException
     */
    public function getFirstLanguageIdByClient($clientId): ?int
    {
        $sql = "SELECT l.idlang FROM `%s` AS cl, `%s` AS l "
            . "WHERE cl.idclient = %d AND cl.idlang = l.idlang LIMIT 0,1";

        $this->db->query($sql, $this->table, cDb::getTableName('lang'), $clientId);

        return $this->db->nextRecord() ? cSecurity::toInteger($this->db->f('idlang')) : NULL;
    }

    /**
     * Returns ids of all languages for a specific client.
     *
     * @param bool $onlyActive Flag to get only ids of active languages.
     * @return int[]
     * @throws cDbException
     * @since CONTENIDO 4.10.2
     */
    public function getAllLanguageIdsByClient(int $clientId, bool $onlyActive = false): array
    {
        if ($clientId <= 0) {
            return [];
        }

        $sql = "SELECT l.idlang FROM `%s` AS cl, `%s` AS l WHERE cl.idclient = %d AND cl.idlang = l.idlang";
        if ($onlyActive) {
            $sql .= " AND l.active = 1";
        }
        $sql .= " ORDER BY l.idlang ASC";

        $this->db->query($sql, $this->table, cDb::getTableName('lang'), $clientId);
        $list = [];
        while ($this->db->nextRecord()) {
            $list[] = cSecurity::toInteger($this->db->f('idlang'));
        }

        return $list;
    }
}

/**
 * Client item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiClientLanguage extends Item
{

    /**
     * @var int Id of client
     * @deprecated [2026-02-14] Since CONTENIDO 4.10.2, use `$item->get('idclient')` instead!
     */
    public $idclient;

    /**
     * Property collection instance
     *
     * @var cApiPropertyCollection
     */
    protected $_oPropertyCollection;

    /**
     * Constructor to create an instance of this class.
     *
     * @param string|int|false $clientLanguageId [optional] If specified, load item
     * @param string|int|false $clientId [optional] If idclient and idlang specified, load item;
     *      ignored, if idclientslang specified
     * @param string|int|false $languageId [optional] If idclient and idlang specified, load item;
     *      ignored, if idclientslang specified
     * @throws cDbException|cException
     */
    public function __construct($clientLanguageId = false, $clientId = false, $languageId = false)
    {
        parent::__construct(cDb::getTableName('clients_lang'), 'idclientslang');

        if ($clientLanguageId !== false) {
            $this->loadByPrimaryKey($clientLanguageId);
        } elseif ($clientId !== false && $languageId !== false) {
            $this->loadByClientIdAndLanguageId(cSecurity::toInteger($clientId), cSecurity::toInteger($languageId));
        }
    }

    /**
     * Load a client language item by client and language id.
     *
     * @throws cDbException
     * @since CONTENIDO 4.10.2
     */
    public function loadByClientIdAndLanguageId(int $clientId, int $languageId): bool
    {
        $this->db->query(
            "SELECT * FROM `%s` WHERE `idclient` = '%d' AND `idlang` = '%d'",
            $this->table,
            $clientId,
            $languageId
        );
        if ($this->db->nextRecord()) {
            $this->loadByRecordSet($this->db->toArray());
            $this->idclient = $this->get('idclient');
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     * @todo Remove this method once the property `$this->idclient` has been removed!
     */
    public function loadByPrimaryKey($value)
    {
        if (parent::loadByPrimaryKey($value)) {
            $this->idclient = $this->get('idclient');
            return true;
        }
        return false;
    }

    /**
     * Set client property
     *
     * @param mixed $type Type of the data to store (arbitrary data)
     * @param mixed $name Entry name
     * @param mixed $value Value
     * @param int $clientId Client id
     * @throws cDbException|cException|cInvalidArgumentException
     * @todo Use parents method
     * @todo should return return value as overwritten method
     * @see Item::setProperty()
     */
    public function setProperty($type, $name, $value, $clientId = 0)
    {
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        $oPropertyColl->setValue($this->getPrimaryKeyName(), $this->get($this->getPrimaryKeyName()), $type, $name, $value, $clientId);
    }

    /**
     * Get client property
     *
     * @param mixed $type Type of the data to get
     * @param mixed $name Entry name
     * @param int $clientId Client id (not used, it's declared because of PHP strict warnings)
     * @return mixed Value
     * @throws cDbException|cException
     * @todo Use parents method @see Item::getProperty()
     */
    public function getProperty($type, $name, $clientId = 0)
    {
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        return $oPropertyColl->getValue($this->getPrimaryKeyName(), $this->get($this->getPrimaryKeyName()), $type, $name);
    }

    /**
     * Delete client property
     *
     * @param int $propertyId Id of property
     * @param int $p2 Not used, is here to prevent PHP Strict warnings
     * @param int $clientId Client id (not used, it's declared because of PHP strict warnings)
     * @throws cDbException|cInvalidArgumentException
     * @todo Use parents method @see Item::deleteProperty(), but be carefully, different parameter!
     */
    public function deleteProperty($propertyId, $p2 = null, $clientId = 0)
    {
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        $oPropertyColl->delete($propertyId);
    }

    /**
     * Get client properties by type
     *
     * @param mixed $type Type of the data to get
     * @return array Associative array
     * @throws cDbException|cException
     */
    public function getPropertiesByType($type)
    {
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        return $oPropertyColl->getValuesByType($this->getPrimaryKeyName(), $this->get('idclient'), $type);
    }

    /**
     * Get all client properties
     *
     * @return array|false
     * @throws cDbException|cException
     * @todo return value should be the same as getPropertiesByType(), e.g. an empty array instead of false
     */
    public function getProperties()
    {
        $itemtype = $this->db->escape($this->getPrimaryKeyName());
        $itemid = $this->db->escape($this->get($this->getPrimaryKeyName()));
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        $oPropertyColl->select(sprintf(
            "`itemtype` = '%s' AND `itemid` = '%s'",
            $itemtype,
            $itemid
        ), '', '`type`, `value` ASC');

        if ($oPropertyColl->count() > 0) {
            $aArray = [];

            while ($oItem = $oPropertyColl->next()) {
                $aArray[$oItem->get('idproperty')]['type'] = $oItem->get('type');
                $aArray[$oItem->get('idproperty')]['name'] = $oItem->get('name');
                $aArray[$oItem->get('idproperty')]['value'] = $oItem->get('value');
            }

            return $aArray;
        } else {
            return false;
        }
    }

    /**
     * Lazy instantiation and return of properties object
     *
     * @param int $clientId Client id (not used, it's declared because of PHP strict warnings)
     */
    protected function _getPropertiesCollectionInstance(int $clientId = 0): cApiPropertyCollection
    {
        // Runtime on-demand allocation of the properties object
        if (!is_object($this->_oPropertyCollection)) {
            $this->_oPropertyCollection = new cApiPropertyCollection();
            $this->_oPropertyCollection->changeClient($this->get('idclient'));
        }
        return $this->_oPropertyCollection;
    }

    /**
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'idclientslang':
            case 'idclient':
            case 'idlang':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }

    /**
     * @inheritDoc
     */
    public function getField($name, $safe = true)
    {
        $value = parent::getField($name, $safe);

        switch ($name) {
            case 'idclientslang':
            case 'idclient':
            case 'idlang':
                $value = cSecurity::toInteger($value);
                break;
        }

        return $value;
    }

}
