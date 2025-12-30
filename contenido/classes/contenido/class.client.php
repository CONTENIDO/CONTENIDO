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
class cApiClientCollection extends ItemCollection
{
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cRegistry::getDbTableName('clients'), 'idclient');
        $this->_setItemClass('cApiClient');
    }

    /**
     * Creates a new client entry
     *
     * @param string $name
     * @param int $errsite_cat [optional]
     * @param int $errsite_art [optional]
     * @param string $author [optional]
     * @param string $created [optional]
     * @param string $lastmodified [optional]
     * @return cApiClient
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($name, $errsite_cat = 0, $errsite_art = 0, $author = '', $created = '', $lastmodified = '')
    {
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
     * @return array<int, array{name: string}> Array with id and name entries
     * @throws cDbException|cException
     */
    public function getAvailableClients(): array
    {
        $clients = [];

        $this->select();
        while (($item = $this->next()) !== false) {
            $clients[(int) $item->get('idclient')] = [
                'name' => $item->get('name'),
            ];
        }

        return $clients;
    }

    /**
     * Returns all clients available in the system
     *
     * @return array<int, array{name: string}> Array with id and name entries
     * @throws cDbException|cException
     */
    public function getAccessibleClients(): array
    {
        $perm = cRegistry::getPerm();
        $clients = [];

        $this->select();
        while (($item = $this->next()) !== false) {
            $idClient = (int) $item->get('idclient');
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
     * @throws cDbException|cException
     */
    public function getFirstAccessibleClient(): ?cApiClient
    {
        $perm = cRegistry::getPerm();
        $this->select();
        while (($item = $this->next()) !== false) {
            $idClient = (int) $item->get('idclient');
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
     * @return string Client name if found, or empty string if not.
     * @throws cDbException|cException
     */
    public function getClientname($idClient): string
    {
        $this->select("idclient='" . (int)$idClient . "'");
        if (($item = $this->next()) !== false) {
            return $item->get('name');
        } else {
            return i18n("No client");
        }
    }

    /**
     * Returns if the given client has a language
     *
     * @throws cException
     */
    public function hasLanguageAssigned(int $clientId): bool
    {
        $client = new cApiClient($clientId);

        return $client->hasLanguages();
    }

    /**
     * Checks if the current authenticated user can access the client.
     *
     * @param int $clientId Id of client to check permissions for.
     * @since CONTENIDO 4.10.2
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
class cApiClient extends Item
{

    /**
     * @deprecated [2014-12-03] Class variable idclient is deprecated
     * @var int Setting of client ID (deprecated)
     */
    private $idclient;

    /**
     * @var cApiPropertyCollection Property collection instance
     */
    protected $_oPropertyCollection;

    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id Specifies the ID of item to load
     * @throws cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cRegistry::getDbTableName('clients'), 'idclient');
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Magic getter method for deprecated idclient variable.
     *
     * @param string $name Works only for "idclient"
     * @return mixed
     */
    public function __get(string $name)
    {
        if ($name === 'idclient') {
            return $this->get('idclient');
        } else {
            return parent::__get($name);
        }
    }

    /**
     * Magic setter method for deprecated idclient variable
     *
     * @param string $name Works only for "idclient"
     * @param mixed $value Value to set
     */
    public function __set(string $name, $value)
    {
        if ($name === 'idclient') {
            $this->set('idclient', cSecurity::toInteger($value));
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * @deprecated [2015-05-21] This method is no longer supported (no replacement)
     */
    public static function getInstance($clientId = false)
    {
        static $currentInstance = [];

        cDeprecated('This method is deprecated and is not needed any longer');

        if (!$clientId) {
            // Use global $clientId
            $clientId = cRegistry::getClientId();
        }

        if (!isset($currentInstance[$clientId])) {
            $currentInstance[$clientId] = new cApiClient($clientId);
        }

        return $currentInstance[$clientId];
    }

    /**
     * @inheritDoc
     */
    public function loadByPrimaryKey($value)
    {
        if (parent::loadByPrimaryKey($value)) {
            $this->set('idclient', $value);
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
     * @param mixed $idproperty [optional]
     * @throws cDbException|cException|cInvalidArgumentException
     * @todo should return return value as overwritten method
     */
    public function setProperty($type, $name, $value, $idproperty = 0)
    {
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        $oPropertyColl->setValue('clientsetting', $this->get('idclient'), $type, $name, $value, $idproperty);
    }

    /**
     * Get client property
     *
     * @param mixed $type Type of the data to get
     * @param mixed $name Entry name
     * @param int $clientId Client id (not used, it's declared because of PHP strict warnings)
     * @return mixed Value
     * @throws cDbException|cException
     */
    public function getProperty($type, $name, $clientId = 0)
    {
        $propertyColl = $this->_getPropertiesCollectionInstance();
        return $propertyColl->getValue('clientsetting', $this->get('idclient'), $type, $name);
    }

    /**
     * Delete client property
     *
     * @param int $idProp Id of property
     * @param string $p2 Not used, is here to prevent PHP Strict warnings
     * @param int $clientId Client id (not used, it's declared because of PHP strict warnings)
     * @throws cDbException|cInvalidArgumentException
     */
    public function deleteProperty($idProp, $p2 = "", $clientId = 0)
    {
        $propertyColl = $this->_getPropertiesCollectionInstance();
        $propertyColl->delete($idProp);
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
        $propertyColl = $this->_getPropertiesCollectionInstance();
        return $propertyColl->getValuesByType('clientsetting', $this->get('idclient'), $type);
    }

    /**
     * Get all client properties
     *
     * @return array|false
     * @throws cDbException|cException
     * @todo return value should be the same as getPropertiesByType(),
     *         e.g. an empty array instead of false
     */
    public function getProperties()
    {
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
     * @throws cException
     */
    public function hasLanguages(): bool
    {
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
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'errsite_cat':
            case 'errsite_art':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
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

}
