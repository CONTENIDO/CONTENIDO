<?php

/**
 * This file contains the client collection and item class.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Client collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @method cApiClient createNewItem
 * @method cApiClient|bool next
 */
class cApiClientCollection extends ItemCollection {
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        parent::__construct(cRegistry::getDbTableName('clients'), 'idclient');
        $this->_setItemClass('cApiClient');
    }

    /**
     * Creates a new client entry
     *
     * @param string  $name
     * @param int     $errsite_cat  [optional]
     * @param int     $errsite_art  [optional]
     * @param string  $author       [optional]
     * @param string  $created      [optional]
     * @param string  $lastmodified [optional]
     *
     * @return cApiClient
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function create($name, $errsite_cat = 0, $errsite_art = 0, $author = '', $created = '', $lastmodified = '') {
        if (empty($author)) {
            $auth = cRegistry::getAuth();
            $author = $auth->auth['uname'];
        }
        if (empty($created)) {
            $created = date('Y-m-d H:i:s');
        }
        if (empty($lastmodified)) {
            $lastmodified = date('Y-m-d H:i:s');
        }

        $item = $this->createNewItem();
        $item->set('name', $name);
        $item->set('errsite_cat', $errsite_cat);
        $item->set('errsite_art', $errsite_art);
        $item->set('author', $author);
        $item->set('created', $created);
        $item->set('lastmodified', $lastmodified);
        $item->store();

        return $item;
    }

    /**
     * Returns all clients available in the system
     *
     * @return array
     *         Array with id and name entries
     * @throws cDbException
     * @throws cException
     */
    public function getAvailableClients() {
        $clients = [];

        $this->select();

        while (($item = $this->next()) !== false) {
            $clients[$item->get('idclient')] = [
                'name' => $item->get('name'),
            ];
        }

        return $clients;
    }

    /**
     * Returns all clients available in the system
     *
     * @return array
     *         Array with id and name entries
     * @throws cDbException
     * @throws cException
     */
    public function getAccessibleClients() {
        $perm = cRegistry::getPerm();
        $clients = [];
        $this->select();
        while (($item = $this->next()) !== false) {
            $idClient = $item->get('idclient');
            if ($perm->have_perm_client("client[" . $idClient . "]")
                || $perm->have_perm_client("admin[" . $idClient . "]")
                || $perm->have_perm_client()) {
                $clients[$idClient] = [
                    'name' => $item->get('name'),
                ];
            }
        }
        return $clients;
    }

    /**
     * Returns first client available in the system
     *
     * @return cApiClient|NULL
     * @throws cDbException
     * @throws cException
     */
    public function getFirstAccessibleClient() {
        $perm = cRegistry::getPerm();
        $this->select();
        while (($item = $this->next()) !== false) {
            $idClient = $item->get('idclient');
            if ($perm->have_perm_client("client[" . $idClient . "]")
                || $perm->have_perm_client("admin[" . $idClient . "]")) {
                return $item;
            }
        }
        return NULL;
    }

    /**
     * Returns the client name of the given clientid
     *
     * @param int $idClient
     * @return string
     *         Client name if found, or empty string if not.
     * @throws cDbException
     * @throws cException
     */
    public function getClientname($idClient) {
        $this->select("idclient='" . (int) $idClient . "'");
        if (($item = $this->next()) !== false) {
            return $item->get('name');
        } else {
            return i18n("No client");
        }
    }

    /**
     * Returns if the given client has a language
     *
     * @param int $idClient
     * @return bool
     *         true if the client has a language
     * @throws cException
     */
    public function hasLanguageAssigned($idClient) {
        $client = new cApiClient($idClient);

        return $client->hasLanguages();
    }

    /**
     * Checks if the current authenticated user can access the client.
     *
     * @since CONTENIDO 4.10.2
     * @param int $clientId Id of client to check permissions for.
     * @return bool
     */
    public static function isClientAccessible(int $clientId): bool
    {
        $perm = cRegistry::getPerm();
        if ($perm->have_perm_client('client[' . $clientId . ']')
            || $perm->have_perm_client('admin[' . $clientId . ']')) {
            return true;
        }

        return false;
    }

}

/**
 * Class cApiClient, client item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Marco Jahn
 */
class cApiClient extends Item {

    /**
     * Setting of client ID (deprecated)
     *
     * @deprecated [2014-12-03]
     *         Class variable idclient is deprecated
     * @var int
     */
     private $idclient;

    /**
     * Property collection instance
     *
     * @var cApiPropertyCollection
     */
    protected $_oPropertyCollection;

    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id [optional]
     *                  Specifies the ID of item to load
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($id = false) {
        parent::__construct(cRegistry::getDbTableName('clients'), 'idclient');
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Magic getter method for deprecated idclient variable.
     *
     * @param string $name
     *         only works for "idclient"
     * @return mixed
     */
    public function __get($name) {
        if ($name === 'idclient') {
            return $this->get('idclient');
        } else {
            return parent::__get($name);
        }
    }

    /**
     * Magic setter method for deprecated idclient variable
     *
     * @param string $name
     *         only works for "idclient"
     * @param mixed $value
     *         Value to set
     */
    public function __set($name, $value) {
        if ($name === 'idclient') {
            $this->set('idclient', cSecurity::toInteger($value));
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * Static accessor to the singleton instance.
     *
     * @deprecated [2015-05-21]
     *         This method is no longer supported (no replacement)
     * @todo There is no need since caching is available at GenericDB level
     * @param int $client [optional]
     * @return cApiClient
     *         Reference to the singleton instance.
     */
    public static function getInstance($client = false) {
        static $currentInstance = [];

        cDeprecated('This method is deprecated and is not needed any longer');

        if (!$client) {
            // Use global $client
            $client = cSecurity::toInteger(cRegistry::getClientId());
        }

        if (!isset($currentInstance[$client])) {
            $currentInstance[$client] = new cApiClient($client);
        }

        return $currentInstance[$client];
    }

    /**
     * Load dataset by primary key
     *
     * @param int $idKey
     *
     * @return bool
     */
    public function loadByPrimaryKey($idKey) {
        if (parent::loadByPrimaryKey($idKey)) {
            $this->set('idclient', $idKey);
            return true;
        }
        return false;
    }

    /**
     * Set client property
     *
     * @todo should return return value as overwritten method
     *
     * @param mixed $type
     *                          Type of the data to store (arbitrary data)
     * @param mixed $name
     *                          Entry name
     * @param mixed $value
     *                          Value
     * @param mixed $idproperty [optional]
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function setProperty($type, $name, $value, $idproperty = 0) {
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        $oPropertyColl->setValue('clientsetting', $this->get('idclient'), $type, $name, $value, $idproperty);
    }

    /**
     * Get client property
     *
     * @param mixed $type
     *                      Type of the data to get
     * @param mixed $name
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
    public function getProperty($type, $name, $client = 0) {
        $propertyColl = $this->_getPropertiesCollectionInstance();
        return $propertyColl->getValue('clientsetting', $this->get('idclient'), $type, $name);
    }

    /**
     * Delete client property
     *
     * @param int    $idProp
     *                       Id of property
     * @param string $p2     [optional]
     *                       Not used, is here to prevent PHP Strict warnings
     * @param int    $client [optional]
     *                       Client id (not used, it's declared because of PHP strict warnings)
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function deleteProperty($idProp, $p2 = "", $client = 0) {
        $propertyColl = $this->_getPropertiesCollectionInstance();
        $propertyColl->delete($idProp);
    }

    /**
     * Get client properties by type
     *
     * @param mixed $type
     *         Type of the data to get
     *
     * @return array
     *         Associative array
     *
     * @throws cDbException
     * @throws cException
     */
    public function getPropertiesByType($type) {
        $propertyColl = $this->_getPropertiesCollectionInstance();
        return $propertyColl->getValuesByType('clientsetting', $this->get('idclient'), $type);
    }

    /**
     * Get all client properties
     *
     * @todo return value should be the same as getPropertiesByType(),
     *         e.g. an empty array instead of false
     * @return array|false
     *         array
     * @throws cDbException
     * @throws cException
     */
    public function getProperties() {
        $propertyColl = $this->_getPropertiesCollectionInstance();
        $whereString = "itemid='" . $this->get('idclient') . "' AND itemtype='clientsetting'";
        $propertyColl->select($whereString, "", "type, name, value ASC");

        if ($propertyColl->count() > 0) {
            $array = [];

            while (($item = $propertyColl->next()) !== false) {
                $array[$item->get('idproperty')]['type'] = $item->get('type');
                $array[$item->get('idproperty')]['name'] = $item->get('name');
                $array[$item->get('idproperty')]['value'] = $item->get('value');
            }

            return $array;
        } else {
            return false;
        }
    }

    /**
     * Check if client has at least one language
     *
     * @return bool
     * @throws cException
     */
    public function hasLanguages() {
        $clientLanguageCollection = new cApiClientLanguageCollection();
        $clientLanguageCollection->setWhere("idclient", $this->get("idclient"));
        $clientLanguageCollection->query();

        if ($clientLanguageCollection->next()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * User-defined setter for client fields.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $bSafe [optional]
     *         Flag to run defined inFilter on passed value
     *
     * @return bool
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'errsite_cat':
            case 'errsite_art':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $bSafe);
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
            $this->_oPropertyCollection->changeClient($this->get('idclient'));
        }
        return $this->_oPropertyCollection;
    }

}
