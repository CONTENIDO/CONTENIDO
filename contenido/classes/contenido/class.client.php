<?php
/**
 * This file contains the client collection and item class.
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @version SVN Revision $Rev:$
 *
 * @author Bjoern Behrens
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Client collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiClientCollection extends ItemCollection {

    /**
     * Constructor
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['clients'], 'idclient');
        $this->_setItemClass('cApiClient');
    }

    /**
     * Creates a new client entry
     *
     * @global object $auth
     * @param string $name
     * @param int $errsite_cat
     * @param int $errsite_art
     * @param string $author
     * @param string $created
     * @param string $lastmodified
     * @return cApiClient
     */
    public function create($name, $errsite_cat = 0, $errsite_art = 0, $author = '', $created = '', $lastmodified = '') {
        global $auth;

        if (empty($author)) {
            $author = $auth->auth['uname'];
        }
        if (empty($created)) {
            $created = date('Y-m-d H:i:s');
        }
        if (empty($lastmodified)) {
            $lastmodified = date('Y-m-d H:i:s');
        }

        $item = parent::createNewItem();
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
     * @return array Array with id and name entries
     */
    public function getAvailableClients() {
        $clients = array();

        $this->select();

        while (($item = $this->next()) !== false) {
            $clients[$item->get('idclient')] = array(
                'name' => $item->get('name')
            );
        }

        return $clients;
    }

    /**
     * Returns all clients available in the system
     *
     * @return array Array with id and name entries
     */
    public function getAccessibleClients() {
        global $perm;
        $clients = array();
        $this->select();
        while (($item = $this->next()) !== false) {
            if ($perm->have_perm_client("client[" . $item->get('idclient') . "]") || $perm->have_perm_client("admin[" . $item->get('idclient') . "]") || $perm->have_perm_client()) {
                $clients[$item->get('idclient')] = array(
                    'name' => $item->get('name')
                );
            }
        }
        return $clients;
    }

    /**
     * Returns first client available in the system
     *
     * @return cApiClient null
     */
    public function getFirstAccessibleClient() {
        global $perm;
        $this->select();
        while (($item = $this->next()) !== false) {
            if ($perm->have_perm_client("client[" . $item->get('idclient') . "]") || $perm->have_perm_client("admin[" . $item->get('idclient') . "]")) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Returns the clientname of the given clientid
     *
     * @param int $idClient
     * @return string Clientname if found, or empty string if not.
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
     * @return bool true if the client has a language
     */
    public function hasLanguageAssigned($idClient) {
        $client = new cApiClient($idClient);

        return $client->hasLanguages();
    }
}

/**
 * Class cApiClient, client item
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @author Marco Jahn
 */
class cApiClient extends Item {

    /**
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
     * Constructor Function
     *
     * @param mixed $id Specifies the ID of item to load
     */
    public function __construct($id = false) {
        global $cfg;
        parent::__construct($cfg['tab']['clients'], 'idclient');
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Static accessor to the singleton instance.
     *
     * @todo There is no need since caching is available at GenericDB level
     * @param int $client
     * @return cApiClient Reference to the singleton instance.
     */
    public static function getInstance($client = false) {
        static $currentInstance = array();

        if (!$client) {
            // Use global $client
            $client = cRegistry::getClientId();
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
     * @return bool
     */
    public function loadByPrimaryKey($idKey) {
        if (parent::loadByPrimaryKey($idKey) == true) {
            $this->idclient = $idKey;
            return true;
        }
        return false;
    }

    /**
     * Set client property
     *
     * @param mixed $type Type of the data to store (arbitary data)
     * @param mixed $name Entry name
     * @param mixed $value Value
     * @param mixed $idproperty
     * @todo should return return value as overwritten method
     */
    public function setProperty($type, $name, $value, $idproperty = 0) {
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        $oPropertyColl->setValue('clientsetting', $this->idclient, $type, $name, $value, $idproperty);
    }

    /**
     * Get client property
     *
     * @param mixed $type Type of the data to get
     * @param mixed $name Entry name
     * @param int $client Client id (not used, it's declared because of PHP
     *            strict warnings)
     * @return mixed Value
     */
    public function getProperty($type, $name, $client = 0) {
        $propertyColl = $this->_getPropertiesCollectionInstance();
        return $propertyColl->getValue('clientsetting', $this->idclient, $type, $name);
    }

    /**
     * Delete client property
     *
     * @param int $idProp Id of property
     * @param string $p2 Not used, is here to prevent PHP Strict warnings
     * @param int $client Client id (not used, it's declared because of PHP
     *            strict warnings)
     * @return void
     */
    public function deleteProperty($idProp, $p2 = "", $client = 0) {
        $propertyColl = $this->_getPropertiesCollectionInstance();
        $propertyColl->delete($idProp);
    }

    /**
     * Get client properties by type
     *
     * @param mixed $type Type of the data to get
     * @return array Assoziative array
     */
    public function getPropertiesByType($type) {
        $propertyColl = $this->_getPropertiesCollectionInstance();
        return $propertyColl->getValuesByType('clientsetting', $this->idclient, $type);
    }

    /**
     * Get all client properties
     *
     * @return array false array
     * @todo return value should be the same as getPropertiesByType(), e.g. an
     *       empty array instead of false
     */
    public function getProperties() {
        $propertyColl = $this->_getPropertiesCollectionInstance();
        $propertyColl->select("itemid='" . $this->idclient . "' AND itemtype='clientsetting'", "", "type, name, value ASC");

        if ($propertyColl->count() > 0) {
            $array = array();

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
     * Userdefined setter for client fields.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $bSafe Flag to run defined inFilter on passed value
     * @todo should return return value of overloaded method
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'errsite_cat':
            case 'errsite_art':
                $value = (int) $value;
                break;
        }

        parent::setField($name, $value, $bSafe);
    }

    /**
     * Lazy instantiation and return of properties object
     *
     * @param int $client Client id (not used, it's declared because of PHP
     *            strict warnings)
     *
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
}
