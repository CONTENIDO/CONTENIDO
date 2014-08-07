<?php
/**
 * This file contains the client language collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @version SVN Revision $Rev:$
 *
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Client language collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiClientLanguageCollection extends ItemCollection {

    /**
     * Constructor
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['clients_lang'], 'idclientslang');
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
     * @return cApiClientLanguage
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
     * @param int $iLang Language id which should be checked
     * @param array $aClients List of clients to check
     * @return bool
     */
    public function hasLanguageInClients($iLang, array $aClientIds) {
        $iLang = (int) $iLang;
        $aClientIds = array_map('intval', $aClientIds);
        $sWhere = 'idlang=' . $iLang . ' AND idclient IN (' . implode(',', $aClientIds) . ')';
        return $this->flexSelect('', '', $sWhere);
    }

    /**
     * Returns list of languages (language ids) by passed client.
     *
     * @param int $client
     * @return array
     */
    public function getLanguagesByClient($client) {
        $list = array();
        $sql = "SELECT idlang FROM `%s` WHERE idclient=%d";
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
     * @return array List of languages where the key is the language id and
     *         value the language name
     */
    public function getLanguageNamesByClient($client) {
        global $cfg;

        $list = array();
        $sql = "SELECT l.idlang AS idlang, l.name AS name
                FROM `%s` AS cl, `%s` AS l
                WHERE idclient=%d AND cl.idlang = l.idlang
                ORDER BY idlang ASC";

        $this->db->query($sql, $this->table, $cfg['tab']['lang'], $client);
        while ($this->db->nextRecord()) {
            $list[$this->db->f('idlang')] = $this->db->f('name');
        }

        return $list;
    }

    /**
     * Returns all languages of an client. Merges the values from language and client language
     * table and returns them back.
     *
     * @param int $client
     * @return array List of languages where the key is the language id and
     *         value an assoziative array merged by fields from language and client language table
     */
    public function getAllLanguagesByClient($client) {
        global $cfg;

        $list = array();
        $sql = "SELECT *
                FROM `%s` AS cl, `%s` AS l
                WHERE cl.idclient=%d AND cl.idlang = l.idlang
                ORDER BY l.idlang ASC";

        $this->db->query($sql, $this->table, $cfg['tab']['lang'], $client);
        while ($this->db->nextRecord()) {
            $list[$this->db->f('idlang')] = $this->db->toArray();
        }

        return $list;
    }

    /**
     * Returns the id of first language for a specific client.
     *
     * @param int $client
     * @return int NULL
     */
    public function getFirstLanguageIdByClient($client) {
        global $cfg;

        $sql = "SELECT l.idlang FROM `%s` AS cl, `%s` AS l " . "WHERE cl.idclient = %d AND cl.idlang = l.idlang LIMIT 0,1";

        $this->db->query($sql, $this->table, $cfg['tab']['lang'], $client);

        return ($this->db->nextRecord()) ? (int) $this->db->f('idlang') : NULL;
    }
}

/**
 * Client item
 *
 * @package Core
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
     * Constructor
     *
     * @param int $iIdClientsLang If specified, load item
     * @param int $iIdClient If idclient and idlang specified, load item;
     *        ignored, if idclientslang specified
     * @param int $iIdLang If idclient and idlang specified, load item; ignored,
     *        if idclientslang specified
     */
    public function __construct($iIdClientsLang = false, $iIdClient = false, $iIdLang = false) {
        global $cfg;
        parent::__construct($cfg['tab']['clients_lang'], 'idclientslang');

        if ($iIdClientsLang !== false) {
            $this->loadByPrimaryKey($iIdClientsLang);
        } elseif ($iIdClient !== false && $iIdLang !== false) {
            /*
             * One way, but the other should be faster $oCollection = new
             * cApiClientLanguageCollection; $oCollection->setWhere('idclient',
             * $iIdClient); $oCollection->setWhere('idlang', $iIdLang);
             * $oCollection->query(); if ($oItem = $oCollection->next()) {
             * $this->loadByPrimaryKey($oItem->get($oItem->primaryKey)); }
             */

            // Query the database
            $sSQL = "SELECT %s FROM %s WHERE idclient = '%d' AND idlang = '%d'";
            $this->db->query($sSQL, $this->primaryKey, $this->table, $iIdClient, $iIdLang);
            if ($this->db->nextRecord()) {
                $this->loadByPrimaryKey($this->db->f($this->primaryKey));
            }
        }
    }

    /**
     * Load dataset by primary key
     *
     * @param int $iIdClientsLang
     * @return bool
     */
    public function loadByPrimaryKey($iIdClientsLang) {
        if (parent::loadByPrimaryKey($iIdClientsLang) == true) {
            $this->idclient = $this->get('idclient');
            return true;
        }
        return false;
    }

    /**
     * Set client property
     *
     * @param mixed $mType Type of the data to store (arbitary data)
     * @param mixed $mName Entry name
     * @param mixed $mValue Value
     * @param int $client Client id
     * @todo Use parents method
     * @see Item::setProperty()
     * @todo should return return value as overwritten method
     */
    public function setProperty($mType, $mName, $mValue, $client = 0) {
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        $oPropertyColl->setValue($this->primaryKey, $this->get($this->primaryKey), $mType, $mName, $mValue, $client);
    }

    /**
     * Get client property
     *
     * @todo Use parents method @see Item::getProperty()
     *
     * @param mixed $mType Type of the data to get
     * @param mixed $mName Entry name
     * @param int $client Client id (not used, it's declared because of PHP
     *        strict warnings)
     * @return mixed Value
     */
    public function getProperty($mType, $mName, $client = 0) {
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        return $oPropertyColl->getValue($this->primaryKey, $this->get($this->primaryKey), $mType, $mName);
    }

    /**
     * Delete client property
     *
     * @todo Use parents method @see Item::deleteProperty(), but be carefull,
     *       different parameter!
     *
     * @param int $idprop Id of property
     * @param int $p2 Not used, is here to prevent PHP Strict warnings
     * @param int $client Client id (not used, it's declared because of PHP
     *        strict warnings)
     */
    public function deleteProperty($idprop, $p2 = NULL, $client = 0) {
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        $oPropertyColl->delete($idprop);
    }

    /**
     * Get client properties by type
     *
     * @param mixed $mType Type of the data to get
     * @return array Assoziative array
     */
    public function getPropertiesByType($mType) {
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        return $oPropertyColl->getValuesByType($this->primaryKey, $this->idclient, $mType);
    }

    /**
     * Get all client properties
     *
     * @return array false array
     * @todo return value should be the same as getPropertiesByType(), e.g. an
     *       empty array instead of false
     */
    public function getProperties() {
        $itemtype = $this->db->escape($this->primaryKey);
        $itemid = $this->db->escape($this->get($this->primaryKey));
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        $oPropertyColl->select("itemtype='" . $itemtype . "' AND itemid='" . $itemid . "'", '', 'type, value ASC');

        if ($oPropertyColl->count() > 0) {
            $aArray = array();

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
     * @param int $client Client id (not used, it's declared because of PHP
     *        strict warnings)
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
     * Userdefined setter for clients lang fields.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $bSafe Flag to run defined inFilter on passed value
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'idclient':
                $value = (int) $value;
                break;
			case 'idlang':
                $value = (int) $value;
                break;
        }

        return parent::setField($name, $value, $bSafe);
    }

}
