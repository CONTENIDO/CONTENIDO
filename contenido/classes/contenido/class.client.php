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
 * @package    CONTENIDO Backend classes
 * @version    1.2.1
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2007-06-24
 *   modified 2011-03-15, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *   modified 2011-05-20, Murat Purc, renamed _getPropertyCollection() to _getPropertiesCollectionInstance()
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class cApiClientCollection extends ItemCollection
{
    /**
     * Constructor
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg['tab']['clients'], 'idclient');
        $this->_setItemClass("cApiClient");
        $this->_setJoinPartner("cApiClientLanguageCollection");
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiClientCollection()
    {
        cWarning(__FILE__, __LINE__, 'Deprecated method call, use __construct()');
        $this->__construct();
    }

    /**
     * Returns all clients available in the system
     *
     * @return  array   Array with id and name entries
     */
    public function getAvailableClients()
    {
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
    public function getAccessibleClients()
    {
        global $perm;
        $aClients = array();
        $this->select();
        while ($oItem = $this->next()) {
            if ($perm->have_perm_client("client[".$oItem->get('idclient')."]") ||
                $perm->have_perm_client("admin[".$oItem->get('idclient')."]") ||
                $perm->have_perm_client()) {
                $aClients[$oItem->get('idclient')] = array('name' => $oItem->get('name'));
            }
        }
        return ($aClients);
    }

    /**
     * Returns the clientname of the given clientid
     *
     * @param   int   $iIdClient
     * @return  string  Clientname if found, or empty string if not.
     */
    public function getClientname($iIdClient)
    {
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
    public function hasLanguageAssigned($iIdClient)
    {
        global $cfg;
        $db = new DB_Contenido();
        $sql = 'SELECT idlang FROM ' . $cfg['tab']['clients_lang'] . ' WHERE idclient = "' . (int) $iIdClient . '"';
        $db->query($sql);
        if ($db->next_record()) {
            return true;
        } else {
            return false;
        }
    }
}


/**
 * Class cApiClient
 * @author Marco Jahn <Marco.Jahn@4fb.de>
 * @version 1.01
 * @copyright four for business 2004
 */
class cApiClient extends Item
{
    public $idclient;

    /**
     * Property collection instance
     * @var PropertyCollection
     */
    protected $_oPropertyCollection;

    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['clients'], 'idclient');
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiClient($mId = false)
    {
        cWarning(__FILE__, __LINE__, 'Deprecated method call, use __construct()');
        $this->__construct($mId);
    }

    /**
     * Static accessor to the singleton instance.
     *
     * @todo  There is no need since caching is available at GenericDB level
     * @param   int  $iClient
     * @return  cApiClient  Reference to the singleton instance.
     */
    public static function getInstance($iClient = false)
    {
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
    public function loadByPrimaryKey($iIdKey)
    {
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
    public function setProperty($mType, $mName, $mValue, $mIdproperty = 0)
    {
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
    public function getProperty($mType, $mName)
    {
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        return $oPropertyColl->getValue('clientsetting', $this->idclient, $mType, $mName);
    }

    /**
     * Delete client property
     *
     * @param   int  $iIdProp  Id of property
     * @return  void
     */
    public function deleteProperty($iIdProp)
    {
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        $oPropertyColl->delete($iIdProp);
    }

    /**
     * Get client properties by type
     *
     * @param   mixed  $mType   Type of the data to get
     * @return  array  Assoziative array
     */
    public function getPropertiesByType($mType)
    {
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
    public function getProperties()
    {
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        $oPropertyColl->select("itemid='".$this->idclient."' AND itemtype='clientsetting'", "", "type, name, value ASC");

        if ($oPropertyColl->count() > 0) {
            $aArray = array();

            while ($oItem = $oPropertyColl->next()) {
                $aArray[$oItem->get('idproperty')]['type']  = $oItem->get('type');
                $aArray[$oItem->get('idproperty')]['name']  = $oItem->get('name');
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
    public function hasLanguages()
    {
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
     * Lazy instantiation and return of properties object
     *
     * @return PropertyCollection
     */
    protected function _getPropertiesCollectionInstance()
    {
        // Runtime on-demand allocation of the properties object
        if (!is_object($this->_oPropertyCollection)) {
            $this->_oPropertyCollection = new PropertyCollection();
            $this->_oPropertyCollection->changeClient($this->idclient);
        }
        return $this->_oPropertyCollection;
    }
}

?>