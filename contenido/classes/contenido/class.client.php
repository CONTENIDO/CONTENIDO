<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Client management class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO API
 * @version    1.2.1
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2007-06-24
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Client collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiClientCollection extends ItemCollection {

    /**
     * Constructor
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['clients'], 'idclient');
        $this->_setItemClass('cApiClient');
        $this->_setJoinPartner('cApiClientLanguageCollection');
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiClientCollection() {
        cDeprecated('Use __construct() instead');
        $this->__construct();
    }

    /**
     * Creates a new client entry
     * @global  object  $auth
     * @param  string  $name
     * @param  int  $errsite_cat
     * @param  int  $errsite_art
     * @param  string  $author
     * @param  string  $created
     * @param  string  $lastmodified
     * @return  cApiClient
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
        return ($item);
    }

    /**
     * Returns all clients available in the system
     *
     * @return  array   Array with id and name entries
     */
    public function getAvailableClients() {
        $aClients = array();

        $this->select();

        while ($oItem = $this->next()) {
            $aClients[$oItem->get('idclient')] = array('name' => $oItem->get('name'));
        }

        return ($aClients);
    }

    /**
     * Returns all clients available in the system
     *
     * @return  array   Array with id and name entries
     */
    public function getAccessibleClients() {
        global $perm;
        $aClients = array();
        $this->select();
        while ($oItem = $this->next()) {
            if ($perm->have_perm_client("client[" . $oItem->get('idclient') . "]") ||
                    $perm->have_perm_client("admin[" . $oItem->get('idclient') . "]") ||
                    $perm->have_perm_client()) {
                $aClients[$oItem->get('idclient')] = array('name' => $oItem->get('name'));
            }
        }
        return ($aClients);
    }

    /**
     * Returns first client available in the system
     *
     * @return  cApiClient|null
     */
    public function getFirstAccessibleClient() {
        global $perm;
        $this->select();
        while ($oItem = $this->next()) {
            if ($perm->have_perm_client("client[" . $oItem->get('idclient') . "]") ||
                    $perm->have_perm_client("admin[" . $oItem->get('idclient') . "]")) {
                return $oItem;
            }
        }
        return null;
    }

    /**
     * Returns the clientname of the given clientid
     *
     * @param   int   $iIdClient
     * @return  string  Clientname if found, or empty string if not.
     */
    public function getClientname($iIdClient) {
        $this->select("idclient='" . (int) $iIdClient . "'");
        if ($oItem = $this->next()) {
            return $oItem->get('name');
        } else {
            return i18n("No client");
        }
    }

    /**
     * Returns if the given client has a language
     *
     * @param   int   $iIdClient
     * @return  bool  true if the client has a language
     */
    public function hasLanguageAssigned($iIdClient) {
        $oClient = new cApiClient($iIdClient);
        return $oClient->hasLanguages();
    }

}

/**
 * Class cApiClient, client item
 * @package    CONTENIDO API
 * @subpackage Model
 * @author     Marco Jahn <Marco.Jahn@4fb.de>
 * @version    1.01
 * @copyright  four for business 2004
 */
class cApiClient extends Item {

    public $idclient;

    /**
     * Property collection instance
     * @var cApiPropertyCollection
     */
    protected $_oPropertyCollection;

    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['clients'], 'idclient');
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiClient($mId = false) {
        cDeprecated("Use __construct() instead");
        $this->__construct($mId);
    }

    /**
     * Static accessor to the singleton instance.
     *
     * @todo  There is no need since caching is available at GenericDB level
     * @param   int  $iClient
     * @return  cApiClient  Reference to the singleton instance.
     */
    public static function getInstance($iClient = false) {
        static $oCurrentInstance = array();

        if (!$iClient) {
            // Use global $client
            $iClient = $GLOBALS['client'];
        }

        if (!isset($oCurrentInstance[$iClient])) {
            $oCurrentInstance[$iClient] = new cApiClient($iClient);
        }

        return $oCurrentInstance[$iClient];
    }

    /**
     * Load dataset by primary key
     *
     * @param   int  $iIdKey
     * @return  bool
     */
    public function loadByPrimaryKey($iIdKey) {
        if (parent::loadByPrimaryKey($iIdKey) == true) {
            $this->idclient = $iIdKey;
            return true;
        }
        return false;
    }

    /**
     * Set client property
     *
     * @param  mixed  $mType   Type of the data to store (arbitary data)
     * @param  mixed  $mName   Entry name
     * @param  mixed  $mValue  Value
     * @param  mixed  $mIdproperty
     */
    public function setProperty($mType, $mName, $mValue, $mIdproperty = 0) {
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        $oPropertyColl->setValue('clientsetting', $this->idclient, $mType, $mName, $mValue, $mIdproperty);
    }

    /**
     * Get client property
     *
     * @param   mixed  $mType   Type of the data to get
     * @param   mixed  $mName   Entry name
     * @return  mixed  Value
     */
    public function getProperty($mType, $mName) {
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        return $oPropertyColl->getValue('clientsetting', $this->idclient, $mType, $mName);
    }

    /**
     * Delete client property
     *
     * @param   int  $iIdProp  Id of property
     * @param   string  $p2  Not used, is here to prevent PHP Strict warnings
     * @return  void
     */
    public function deleteProperty($iIdProp, $p2 = "") {
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        $oPropertyColl->delete($iIdProp);
    }

    /**
     * Get client properties by type
     *
     * @param   mixed  $mType   Type of the data to get
     * @return  array  Assoziative array
     */
    public function getPropertiesByType($mType) {
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        return $oPropertyColl->getValuesByType('clientsetting', $this->idclient, $mType);
    }

    /**
     * Get all client properties
     *
     * @param   mixed  $mType   Type of the data to get
     * @return  array|false  Assoziative array
     * @todo    return value should be the same as getPropertiesByType(), e. g. an empty array instead false
     */
    public function getProperties() {
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        $oPropertyColl->select("itemid='" . $this->idclient . "' AND itemtype='clientsetting'", "", "type, name, value ASC");

        if ($oPropertyColl->count() > 0) {
            $aArray = array();

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
     * Check if client has at least one language
     *
     * @return  bool
     */
    public function hasLanguages() {
        $cApiClientLanguageCollection = new cApiClientLanguageCollection();
        $cApiClientLanguageCollection->setWhere("idclient", $this->get("idclient"));
        $cApiClientLanguageCollection->query();

        if ($cApiClientLanguageCollection->next()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Userdefined setter for client fields.
     * @param  string  $name
     * @param  mixed   $value
     * @param  bool    $bSafe   Flag to run defined inFilter on passed value
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'errsite_cat':
            case 'errsite_art':
                $value = (int) $value;
                break;
        }

        if (is_string($value)) {
            $value = $this->escape($value);
        }

        parent::setField($name, $value, $bSafe);
    }

    /**
     * Lazy instantiation and return of properties object
     *
     * @return cApiPropertyCollection
     */
    protected function _getPropertiesCollectionInstance() {
        // Runtime on-demand allocation of the properties object
        if (!is_object($this->_oPropertyCollection)) {
            $this->_oPropertyCollection = new cApiPropertyCollection();
            $this->_oPropertyCollection->changeClient($this->idclient);
        }
        return $this->_oPropertyCollection;
    }

}

################################################################################
# NOTE: Class implemetations below are deprecated and the will be removed in
#       future versions of contenido.
#       Don't use them, they are still available due to downwards compatibility.

/**
 * Client class
 * @deprecated  [2012-02-09] Use cApiClientCollection instead of this class.
 */
class Client extends cApiClientCollection {

    public function __construct() {
        cDeprecated("Use class cApiClientCollection instead");
        parent::__construct();
    }

    public function Client() {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }

}

?>