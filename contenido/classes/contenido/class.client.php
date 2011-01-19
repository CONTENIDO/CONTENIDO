<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Client management class
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.13
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2007-06-24
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class cApiClientCollection extends ItemCollection
{
    /**
     * Constructor
     */
    function cApiClientCollection()
    {
        global $cfg;
        parent::ItemCollection($cfg['tab']['clients'], 'idclient');
        $this->_setItemClass("cApiClient");
        $this->_setJoinPartner("cApiClientLanguageCollection");
    }

    /**
     * Returns all clients available in the system
     *
     * @return  array   Array with id and name entries
     */
    function getAvailableClients()
    {
        $aClients = array();

        $this->select();

        while ($oItem = $this->next()) {
            $aNewEntry['name'] = $oItem->get('name');
            $aClients[$oItem->get('idclient')] = $aNewEntry;
        }

        return ($aClients);
    }

    /**
     * Returns all clients available in the system
     *
     * @return  array   Array with id and name entries
     */
    function getAccessibleClients()
    {
        global $perm;
        $aClients = array();
        $this->select();
        while ($oItem = $this->next()) {
            if ($perm->have_perm_client("client[".$oItem->get('idclient')."]") ||
                $perm->have_perm_client("admin[".$oItem->get('idclient')."]") ||
                $perm->have_perm_client()) {
                $aNewEntry['name'] = $oItem->get('name');
                $aClients[$oItem->get('idclient')] = $aNewEntry;
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
    function getClientname($iIdClient)
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
    function hasLanguageAssigned($iIdClient)
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
    var $idclient;

    /**
     * Property collection instance
     * @var PropertyCollection
     */
    var $_oPropertyCollection;

    /**
     * Constructor
     *
     * @param  int  client to load
     */
    function cApiClient($iIdClient = false)
    {
        global $cfg;
        parent::Item($cfg['tab']['clients'], 'idclient');
        if ($iIdClient !== false) {
            $this->loadByPrimaryKey($iIdClient);
        }
    }
    
    /**
     * Static accessor to the singleton instance.
     *
     * @param   int  $iClient
     * @return  cApiClient  Reference to the singleton instance.
     */
    public static function getInstance($iClient = false) {
        static $oCurrentInstance;

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
    function loadByPrimaryKey($iIdKey)
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
    function setProperty($mType, $mName, $mValue, $mIdproperty = 0)
    {
        $oPropertyColl = $this->_getPropertyCollection();
        $oPropertyColl->setValue('clientsetting', $this->idclient, $mType, $mName, $mValue, $mIdproperty);
    }

    /**
     * Get client property
     *
     * @param   mixed  $mType   Type of the data to get
     * @param   mixed  $mName   Entry name
     * @return  mixed  Value
     */
    function getProperty($mType, $mName)
    {
        $oPropertyColl = $this->_getPropertyCollection();
        return $oPropertyColl->getValue('clientsetting', $this->idclient, $mType, $mName);
    }

    /**
     * Delete client property
     *
     * @param   int  $iIdProp  Id of property
     * @return  void
     */
    function deleteProperty($iIdProp)
    {
        $oPropertyColl = $this->_getPropertyCollection();
        $oPropertyColl->delete($iIdProp);
    }

    /**
     * Get client properties by type
     *
     * @param   mixed  $mType   Type of the data to get
     * @return  array  Assoziative array
     */
    function getPropertiesByType($mType)
    {
        $oPropertyColl = $this->_getPropertyCollection();
        return $oPropertyColl->getValuesByType('clientsetting', $this->idclient, $mType);
    }

    /**
     * Get all client properties
     *
     * @param   mixed  $mType   Type of the data to get
     * @return  array|false  Assoziative array
     * @todo    return value should be the same as getPropertiesByType(), e. g. an empty array instead false
     */
    function getProperties()
    {
        $oPropertyColl = $this->_getPropertyCollection();
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
    function hasLanguages()
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
    function _getPropertyCollection()
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