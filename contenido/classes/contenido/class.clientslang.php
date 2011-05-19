<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Language to client mapping class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend classes
 * @version    1.5.1
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2007-05-25
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


class cApiClientLanguageCollection extends ItemCollection
{
    /**
     * Constructor
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg["tab"]["clients_lang"], "idclientslang");
        $this->_setItemClass("cApiClientLanguage");
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiClientLanguageCollection()
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct();
    }
}


class cApiClientLanguage extends Item
{
    /**
     * Id of client
     * @var int
     */
    public $idclient;

    /**
     * Property collection instance
     * @var PropertyCollection
     */
    protected $_oPropertyCollection;

    /**
     * Constructor
     *
     * @param  int  $iIdClientsLang  If specified, load item
     * @param  int  $iIdClient       If idclient and idlang specified, load item; ignored, if idclientslang specified
     * @param  int  $iIdLang         If idclient and idlang specified, load item; ignored, if idclientslang specified
     */
    public function __construct($iIdClientsLang = false, $iIdClient = false, $iIdLang = false)
    {
        global $cfg;
        parent::__construct($cfg["tab"]["clients_lang"], "idclientslang");

        if ($iIdClientsLang !== false) {
            $this->loadByPrimaryKey($iIdClientsLang);
        } elseif ($iIdClient !== false && $iIdLang !== false) {
            /*
            One way, but the other should be faster
            $oCollection = new cApiClientLanguageCollection;
            $oCollection->setWhere("idclient", $iIdClient);
            $oCollection->setWhere("idlang", $iIdLang);
            $oCollection->query();
            if ($oItem = $oCollection->next()) {
                $this->loadByPrimaryKey($oItem->get($oItem->primaryKey));
            }
            */

            // Query the database
            $sSQL = "SELECT %s FROM %s WHERE idclient = '%d' AND idlang = '%d'";
            $this->db->query($sSQL, $this->primaryKey, $this->table, $iIdClient, $iIdLang);
            if ($this->db->next_record()) {
                $this->loadByPrimaryKey($this->db->f($this->primaryKey));
            }
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiClientLanguage($iIdClientsLang = false, $iIdClient = false, $iIdLang = false)
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct($iIdClientsLang, $iIdClient, $iIdLang);
    }

    /**
     * Load dataset by primary key
     *
     * @param   int  $iIdClientsLang
     * @return  bool
     */
    public function loadByPrimaryKey($iIdClientsLang)
    {
        if (parent::loadByPrimaryKey($iIdClientsLang) == true) {
            $this->idclient = $this->get("idclient");
            return true;
        }
        return false;
    }

    /**
     * Set client property
     *
     * @todo  Use parents method @see Item::setProperty()
     *
     * @param  mixed  $mType   Type of the data to store (arbitary data)
     * @param  mixed  $mName   Entry name
     * @param  mixed  $mValue  Value
     */
    public function setProperty($mType, $mName, $mValue)
    {
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        $oPropertyColl->setValue($this->primaryKey, $this->get($this->primaryKey), $mType, $mName, $mValue);
    }

    /**
     * Get client property
     *
     * @todo  Use parents method @see Item::getProperty()
     *
     * @param   mixed  $mType   Type of the data to get
     * @param   mixed  $mName   Entry name
     * @return  mixed  Value
     */
    public function getProperty($mType, $mName)
    {
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        return $oPropertyColl->getValue($this->primaryKey, $this->get($this->primaryKey), $mType, $mName);
    }

    /**
     * Delete client property
     *
     * @todo  Use parents method @see Item::deleteProperty(), but be carefull, different parameter!
     *
     * @param   int  $idprop   Id of property
     * @return  void
     */
    public function deleteProperty($idprop)
    {
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        $oPropertyColl->delete($idprop);
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
        return $oPropertyColl->getValuesByType($this->primaryKey, $this->idclient, $mType);
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
        $itemtype = Contenido_Security::escapeDB($this->primaryKey, $this->db);
        $itemid   = Contenido_Security::escapeDB($this->get($this->primaryKey), $this->db);
        $oPropertyColl = $this->_getPropertiesCollectionInstance();
        $oPropertyColl->select("itemtype='".$itemtype."' AND itemid='".$itemid."'", "", "type, value ASC");

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