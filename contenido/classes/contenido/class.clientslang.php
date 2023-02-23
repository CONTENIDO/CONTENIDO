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
class cApiClientLanguageCollection extends ItemCollection {
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct() {
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
     *
     * @return cApiClientLanguage|Item
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function create($iClient, $iLang) {
        $oItem = $this->createNewItem();
        $oItem->set('idclient', $iClient, false);
        $oItem->set('idlang', $iLang, false);
        $oItem->store();
        return $oItem;
    }

    /**
     * Checks if a language is associated with a given list of clients.
     *
     * @param int   $iLang
     *         Language id which should be checked
     * @param array $aClientIds
     *
     * @return bool
     * @throws cDbException
     */
    public function hasLanguageInClients($iLang, array $aClientIds) {
        $iLang = cSecurity::toInteger($iLang);
        $aClientIds = array_map('intval', $aClientIds);
        $sWhere = ' `idlang` = ' . $iLang . ' AND `idclient` IN (' . implode(',', $aClientIds) . ')';
        return $this->flexSelect('', '', $sWhere);
    }

    /**
     * Returns list of languages (language ids) by passed client.
     *
     * @param int $client
     * @return array
     * @throws cDbException
     */
    public function getLanguagesByClient($client) {
        $list = [];
        $sql = "SELECT `idlang` FROM `%s` WHERE `idclient` = %d";
        $this->db->query($sql, $this->table, $client);
        while ($this->db->nextRecord()) {
            $list[] = $this->db->f("idlang");
        }
        return $list;
    }

    /**
     * Returns all languages (language ids and names) of an client
     *
     * @param int $client
     * @return array
     *         List of languages where the key is the language id and value the
     *         language name
     * @throws cDbException
     */
    public function getLanguageNamesByClient($client) {
        $list = [];
        $sql = "SELECT l.idlang AS idlang, l.name AS name
                FROM `%s` AS cl, `%s` AS l
                WHERE idclient = %d AND cl.idlang = l.idlang
                ORDER BY idlang ASC";

        $this->db->query($sql, $this->table, cRegistry::getDbTableName('lang'), $client);
        while ($this->db->nextRecord()) {
            $list[$this->db->f('idlang')] = $this->db->f('name');
        }

        return $list;
    }

    /**
     * Returns all languages of a client. Merges the values from language and client language
     * table and returns them back.
     *
     * @param int $client
     * @return array
     *         List of languages where the key is the language id and value an
     *         associative array merged by fields from language and client
     *         language table
     * @throws cDbException
     */
    public function getAllLanguagesByClient($client) {
        $list = [];
        $sql = "SELECT *
                FROM `%s` AS cl, `%s` AS l
                WHERE cl.idclient = %d AND cl.idlang = l.idlang
                ORDER BY l.idlang ASC";

        $this->db->query($sql, $this->table, cRegistry::getDbTableName('lang'), $client);
        while ($this->db->nextRecord()) {
            $list[$this->db->f('idlang')] = $this->db->toArray();
        }

        return $list;
    }

    /**
     * Returns the id of first language for a specific client.
     *
     * @param int $client
     * @return int|NULL
     * @throws cDbException
     */
    public function getFirstLanguageIdByClient($client) {
        $sql = "SELECT l.idlang FROM `%s` AS cl, `%s` AS l "
            . "WHERE cl.idclient = %d AND cl.idlang = l.idlang LIMIT 0,1";

        $this->db->query($sql, $this->table, cRegistry::getDbTableName('lang'), $client);

        return ($this->db->nextRecord()) ? cSecurity::toInteger($this->db->f('idlang')) : NULL;
    }

    /**
     * Returns ids of all languages for a specific client.
     *
     * @since CONTENIDO 4.10.2
     * @param int $client
     *
     * @return int[]
     * @throws cDbException|cInvalidArgumentException
     */
    public function getAllLanguageIdsByClient(int $client): array
    {
        if ($client <= 0) {
            return [];
        }
        $list = [];
        $sql = "SELECT l.idlang FROM `%s` AS cl, `%s` AS l "
            . "WHERE cl.idclient = %d AND cl.idlang = l.idlang ORDER BY l.idlang ASC";

        $this->db->query($sql, $this->table, cRegistry::getDbTableName('lang'), $client);
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
class cApiClientLanguage extends Item {

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
     * @param bool $iIdClientsLang [optional]
     *                             If specified, load item
     * @param bool $iIdClient      [optional]
     *                             If idclient and idlang specified, load item;
     *                             ignored, if idclientslang specified
     * @param bool $iIdLang        [optional]
     *                             If idclient and idlang specified, load item;
     *                             ignored, if idclientslang specified
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($iIdClientsLang = false, $iIdClient = false, $iIdLang = false) {
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
     * Load dataset by primary key
     *
     * @param int $iIdClientsLang
     * @return bool
     *
     * @throws cDbException
     * @throws cException
     */
    public function loadByPrimaryKey($iIdClientsLang) {
        if (parent::loadByPrimaryKey($iIdClientsLang)) {
            $this->idclient = $this->get('idclient');
            return true;
        }
        return false;
    }

    /**
     * Set client property
     *
     * @todo Use parents method
     * @todo should return return value as overwritten method
     * @see  Item::setProperty()
     *
     * @param mixed $mType
     *                      Type of the data to store (arbitrary data)
     * @param mixed $mName
     *                      Entry name
     * @param mixed $mValue
     *                      Value
     * @param int   $client [optional]
     *                      Client id
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function setProperty($mType, $mName, $mValue, $client = 0) {
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        $oPropertyColl->setValue($this->getPrimaryKeyName(), $this->get($this->getPrimaryKeyName()), $mType, $mName, $mValue, $client);
    }

    /**
     * Get client property
     *
     * @todo Use parents method @see Item::getProperty()
     *
     * @param mixed $mType
     *                      Type of the data to get
     * @param mixed $mName
     *                      Entry name
     * @param int   $client [optional]
     *                      Client id (not used, it's declared because of PHP strict warnings)
     *
     * @return mixed
     *                      Value
     *
     * @throws cDbException
     * @throws cException
     */
    public function getProperty($mType, $mName, $client = 0) {
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        return $oPropertyColl->getValue($this->getPrimaryKeyName(), $this->get($this->getPrimaryKeyName()), $mType, $mName);
    }

    /**
     * Delete client property
     *
     * @todo Use parents method @see Item::deleteProperty(), but be carefull,
     *       different parameter!
     *
     * @param int $idprop
     *                    Id of property
     * @param int $p2
     *                    Not used, is here to prevent PHP Strict warnings
     * @param int $client [optional]
     *                    Client id (not used, it's declared because of PHP strict warnings)
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function deleteProperty($idprop, $p2 = NULL, $client = 0) {
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        $oPropertyColl->delete($idprop);
    }

    /**
     * Get client properties by type
     *
     * @param mixed $mType
     *         Type of the data to get
     *
     * @return array
     *         Associative array
     *
     * @throws cDbException
     * @throws cException
     */
    public function getPropertiesByType($mType) {
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        return $oPropertyColl->getValuesByType($this->getPrimaryKeyName(), $this->idclient, $mType);
    }

    /**
     * Get all client properties
     *
     * @todo return value should be the same as getPropertiesByType(), e.g. an
     *       empty array instead of false
     * @return array|false
     *         array
     * @throws cDbException
     * @throws cException
     */
    public function getProperties() {
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
     * @param int $client [optional]
     *         Client id (not used, it's declared because of PHP strict warnings)
     * @return cApiPropertyCollection
     */
    protected function _getPropertiesCollectionInstance($client = 0) {
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
     * @param string $name
     * @param mixed $value
     * @param bool $bSafe [optional]
     *         Flag to run defined inFilter on passed value
     * @return bool
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'idlang':
            case 'idclient':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $bSafe);
    }

}
