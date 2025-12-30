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
 * @method cApiClientLanguage createNewItem
 * @method cApiClientLanguage|bool next
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
        parent::__construct(cRegistry::getDbTableName('clients_lang'), 'idclientslang');
        $this->_setItemClass('cApiClientLanguage');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiClientCollection');
        $this->_setJoinPartner('cApiLanguageCollection');
    }

    /**
     * Creates a client language entry.
     *
     * @param int $iClient
     * @param int $iLang
     * @return cApiClientLanguage|Item
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($iClient, $iLang)
    {
        $oItem = $this->createNewItem();
        $oItem->set('idclient', $iClient, false);
        $oItem->set('idlang', $iLang, false);
        $oItem->store();
        return $oItem;
    }

    /**
     * Checks if a language is associated with a given list of clients.
     *
     * @param int $iLang Language id which should be checked
     * @throws cDbException
     */
    public function hasLanguageInClients($iLang, array $aClientIds): bool
    {
        $iLang = cSecurity::toInteger($iLang);
        $aClientIds = array_map('intval', $aClientIds);
        $sWhere = ' `idlang` = ' . $iLang . ' AND `idclient` IN (' . implode(',', $aClientIds) . ')';
        return $this->flexSelect('', '', $sWhere);
    }

    /**
     * Returns list of languages (language ids) by passed client.
     *
     * @throws cDbException
     */
    public function getLanguagesByClient($clientId): array
    {
        $list = [];
        $sql = "SELECT `idlang` FROM `%s` WHERE `idclient` = %d";
        $this->db->query($sql, $this->table, $clientId);
        while ($this->db->nextRecord()) {
            $list[] = $this->db->f("idlang");
        }
        return $list;
    }

    /**
     * Returns all languages (language ids and names) of a client
     *
     * @param int $clientId
     * @return array List of languages where the key is the language id and value the language name
     * @throws cDbException
     */
    public function getLanguageNamesByClient($clientId): array
    {
        $list = [];
        $sql = "SELECT l.idlang AS idlang, l.name AS name
                FROM `%s` AS cl, `%s` AS l
                WHERE idclient = %d AND cl.idlang = l.idlang
                ORDER BY idlang ASC";

        $this->db->query($sql, $this->table, cRegistry::getDbTableName('lang'), $clientId);
        while ($this->db->nextRecord()) {
            $list[$this->db->f('idlang')] = $this->db->f('name');
        }

        return $list;
    }

    /**
     * Returns all languages of a client. Merges the values from language and client language
     * table and returns them back.
     *
     * @param int $clientId
     * @return array List of languages where the key is the language id and value an associative array
     *      merged by fields from language and client language table
     * @throws cDbException
     */
    public function getAllLanguagesByClient($clientId): array
    {
        $list = [];
        $sql = "SELECT *
                FROM `%s` AS cl, `%s` AS l
                WHERE cl.idclient = %d AND cl.idlang = l.idlang
                ORDER BY l.idlang ASC";

        $this->db->query($sql, $this->table, cRegistry::getDbTableName('lang'), $clientId);
        while ($this->db->nextRecord()) {
            $list[$this->db->f('idlang')] = $this->db->toArray();
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

        $this->db->query($sql, $this->table, cRegistry::getDbTableName('lang'), $clientId);

        return ($this->db->nextRecord()) ? cSecurity::toInteger($this->db->f('idlang')) : NULL;
    }

    /**
     * Returns ids of all languages for a specific client.
     *
     * @param int $clientId
     * @param bool $onlyActive Flag to get only ids of active languages.
     * @return int[]
     * @throws cDbException|cInvalidArgumentException
     * @since CONTENIDO 4.10.2
     */
    public function getAllLanguageIdsByClient(int $clientId, bool $onlyActive = false): array
    {
        if ($clientId <= 0) {
            return [];
        }

        $sql = "SELECT l.idlang FROM `%s` AS cl, `%s` AS l "
            . "WHERE cl.idclient = %d AND cl.idlang = l.idlang";
        if ($onlyActive) {
            $sql .= " AND l.active = 1";
        }
        $sql .= " ORDER BY l.idlang ASC";

        $this->db->query($sql, $this->table, cRegistry::getDbTableName('lang'), $clientId);
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
     * Id of client
     *
     * @var int
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
     * @param string|int|false $iIdClientsLang [optional] If specified, load item
     * @param string|int|false $iIdClient [optional] If idclient and idlang specified, load item;
     *      ignored, if idclientslang specified
     * @param string|int|false $iIdLang [optional] If idclient and idlang specified, load item;
     *      ignored, if idclientslang specified
     * @throws cDbException|cException
     */
    public function __construct($iIdClientsLang = false, $iIdClient = false, $iIdLang = false)
    {
        parent::__construct(cRegistry::getDbTableName('clients_lang'), 'idclientslang');

        if ($iIdClientsLang !== false) {
            $this->loadByPrimaryKey($iIdClientsLang);
        } elseif ($iIdClient !== false && $iIdLang !== false) {
            /*
             * One way, but the other should be faster $oCollection = new
             * cApiClientLanguageCollection; $oCollection->setWhere('idclient',
             * $iIdClient); $oCollection->setWhere('idlang', $iIdLang);
             * $oCollection->query(); if ($oItem = $oCollection->next()) {
             * $this->loadByPrimaryKey($oItem->get($oItem->getPrimaryKeyName())); }
             */

            // Query the database
            $sSQL = "SELECT %s FROM %s WHERE idclient = '%d' AND idlang = '%d'";
            $this->db->query($sSQL, $this->getPrimaryKeyName(), $this->table, $iIdClient, $iIdLang);
            if ($this->db->nextRecord()) {
                $this->loadByPrimaryKey($this->db->f($this->getPrimaryKeyName()));
            }
        }
    }

    /**
     * @inheritDoc
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
     * @see  Item::setProperty()
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
     * @param int $idprop Id of property
     * @param int $p2 Not used, is here to prevent PHP Strict warnings
     * @param int $clientId Client id (not used, it's declared because of PHP strict warnings)
     * @throws cDbException|cInvalidArgumentException
     * @todo Use parents method @see Item::deleteProperty(), but be carefully, different parameter!
     */
    public function deleteProperty($idprop, $p2 = NULL, $clientId = 0)
    {
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        $oPropertyColl->delete($idprop);
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
        return $oPropertyColl->getValuesByType($this->getPrimaryKeyName(), $this->idclient, $type);
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
        $oPropertyColl->select("itemtype='" . $itemtype . "' AND itemid='" . $itemid . "'", '', 'type, value ASC');

        if ($oPropertyColl->count() > 0) {
            $aArray = [];

            while (($oItem = $oPropertyColl->next()) !== false) {
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
            $this->_oPropertyCollection->changeClient($this->idclient);
        }
        return $this->_oPropertyCollection;
    }

    /**
     * User-defined setter for clients lang fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'idlang':
            case 'idclient':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }

}
