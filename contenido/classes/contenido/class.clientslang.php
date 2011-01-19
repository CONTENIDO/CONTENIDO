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
 * @version    1.41
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2007-05-25
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class cApiClientLanguageCollection extends ItemCollection
{
    /**
     * Constructor
     */
    function cApiClientLanguageCollection()
    {
        global $cfg;
        parent::ItemCollection($cfg["tab"]["clients_lang"], "idclientslang");
        $this->_setItemClass("cApiClientLanguage");
    }
}

class cApiClientLanguage extends Item
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
     * @param  int  $iIdClientsLang  If specified, load item
     * @param  int  $iIdClient       If idclient and idlang specified, load item; ignored, if idclientslang specified
     * @param  int  $iIdLang         If idclient and idlang specified, load item; ignored, if idclientslang specified
     */
    function cApiClientLanguage ($iIdClientsLang = false, $iIdClient = false, $iIdLang = false)
    {
        global $cfg;
        parent::Item($cfg["tab"]["clients_lang"], "idclientslang");

        if ($iIdClientsLang !== false) {
            $this->loadByPrimaryKey($iIdClientsLang);
        } else if ($iIdClient !== false && $iIdLang !== false) {
            /* One way, but the other should be faster
            $oCollection = new cApiClientLanguageCollection;
            $oCollection->setWhere("idclient", $iIdClient);
            $oCollection->setWhere("idlang", $iIdLang);
            $oCollection->query();

            if ($oItem = $oCollection->next()) {
                $this->loadByPrimaryKey($oItem->get($oItem->primaryKey));
            } */

            $sSQL = "SELECT ".$this->primaryKey." FROM ".$this->table.
                    " WHERE idclient = '" . Contenido_Security::escapeDB($iIdClient, $this->db) . "' AND idlang = '" . Contenido_Security::escapeDB($iIdLang, $this->db) . "'";

            /* Query the database */
            $this->db->query($sSQL);

            if ($this->db->next_record()) {
                $this->loadByPrimaryKey($this->db->f($this->primaryKey));
            }
        }
    }

    /**
     * Load dataset by primary key
     *
     * @param   int  $iIdClientsLang
     * @return  bool
     */
    function loadByPrimaryKey($iIdClientsLang)
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
     * @param  mixed  $mType   Type of the data to store (arbitary data)
     * @param  mixed  $mName   Entry name
     * @param  mixed  $mValue  Value
     */
    function setProperty($mType, $mName, $mValue)
    {
        $oPropertyColl = $this->_getPropertyCollection();
        $oPropertyColl->setValue($this->primaryKey, $this->get($this->primaryKey), $mType, $mName, $mValue);
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
        return $oPropertyColl->getValue($this->primaryKey, $this->get($this->primaryKey), $mType, $mName);
    }

    /**
     * Delete client property
     *
     * @param   int  $idprop   Id of property
     * @return  void
     */
    function deleteProperty($idprop)
    {
        $oPropertyColl = $this->_getPropertyCollection();
        $oPropertyColl->delete($idprop);
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
        return $oPropertyColl->getValuesByType($this->primaryKey, $this->idclient, $mType);
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
        $itemtype = Contenido_Security::escapeDB($this->primaryKey, $this->db);
        $itemid   = Contenido_Security::escapeDB($this->get($this->primaryKey), $this->db);
        $oPropertyColl = $this->_getPropertyCollection();
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