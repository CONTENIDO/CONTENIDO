<?php

/**
 * This file contains the generic db item class.
 *
 * @package Core
 * @subpackage GenericDB
 * @version SVN Revision $Rev:$
 *
 * @author Timo Hummel
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class Item
 * Abstract class for database based items.
 *
 * @package Core
 * @subpackage GenericDB
 */
abstract class Item extends cItemBaseAbstract {

    /**
     * Storage of the source table to use for the user informations
     *
     * @var array
     */
    public $values;

    /**
     * Storage of the fields which were modified, where the keys are the
     * fieldnames and the values just simple bools.
     *
     * @var array
     */
    protected $modifiedValues;

    /**
     * Stores the old primary key, just in case somebody wants to change it
     *
     * @var string
     */
    protected $oldPrimaryKey;

    /**
     * List of funcion names of the filters used when data is stored to the db.
     *
     * @var array
     */
    protected $_arrInFilters = array(
        'htmlspecialchars',
        'addslashes'
    );

    /**
     * List of funcion names of the filtersused when data is retrieved from the
     * db
     *
     * @var array
     */
    protected $_arrOutFilters = array(
        'stripslashes',
        'htmldecode'
    );

    /**
     * Class name of meta object
     *
     * @var string
     */
    protected $_metaObject;

    /**
     * Last executed SQL statement
     *
     * @var string
     */
    protected $_lastSQL;

    /**
     * Constructor function
     *
     * @param string $sTable
     *         The table to use as information source
     * @param string $sPrimaryKey
     *         The primary key to use
     */
    public function __construct($sTable, $sPrimaryKey) {
        parent::__construct($sTable, $sPrimaryKey, get_parent_class($this));
    }

    /**
     * Resets class variables back to default
     * This is handy in case a new item is tried to be loaded into this class instance.
     */
    protected function _resetItem() {
        parent::_resetItem();

        // make sure not to reset filters because then default filters would always be used for loading
        $this->values = null;
        $this->modifiedValues = null;
        $this->_metaObject = null;
        $this->_lastSQL = null;
    }

    /**
     * Loads an item by colum/field from the database.
     *
     * @param string $sField
     *         Specifies the field
     * @param mixed $mValue
     *         Specifies the value
     * @param bool $bSafe [optional]
     *         Use inFilter or not
     * @throws cException
     *         if more than one item has been found matching the given arguments
     * @return bool
     *         True if the load was successful
     */
    public function loadBy($sField, $mValue, $bSafe = true) {
        // reset class variables back to default before loading
        $this->_resetItem();

        if ($bSafe) {
            $mValue = $this->_inFilter($mValue);
        }

        // check, if cache contains a matching entry
        $aRecordSet = NULL;
        if ($sField === $this->primaryKey) {
            $aRecordSet = $this->_oCache->getItem($mValue);
        } else {
            $aRecordSet = $this->_oCache->getItemByProperty($sField, $mValue);
        }

        if ($aRecordSet) {
            // entry in cache found, load entry from cache
            $this->loadByRecordSet($aRecordSet);
            return true;
        }

        // SQL-Statement to select by field
        $sql = "SELECT * FROM `%s` WHERE %s = '%s'";
        $sql = $this->db->prepare($sql, $this->table, $sField, $mValue);

        // Query the database
        $this->db->query($sql);

        $this->_lastSQL = $sql;

        if ($this->db->numRows() > 1) {
            $msg = "Tried to load a single line with field $sField and value $mValue from " . $this->table . " but found more than one row";
            throw new cException($msg);
        }

        // Advance to the next record, return false if nothing found
        if (!$this->db->nextRecord()) {
            return false;
        }

        $this->loadByRecordSet($this->db->toArray());
        $this->virgin = false;
        return true;
    }

    /**
     * Loads an item by colums/fields from the database.
     *
     * @param array $aAttributes
     *         associative array with field / value pairs
     * @param bool $bSafe [optional]
     *         Use inFilter or not
     * @throws cException
     *         if more than one item could be found matching the given arguments
     * @return bool
     *         True if the load was successful
     */
    public function loadByMany(array $aAttributes, $bSafe = true) {
        // reset class variables back to default before loading
        $this->_resetItem();

        if ($bSafe) {
            $aAttributes = $this->_inFilter($aAttributes);
        }

        // check, if cache contains a matching entry
        $aRecordSet = NULL;
        if (count($aAttributes) == 1 && isset($aAttributes[$this->primaryKey])) {
            $aRecordSet = $this->_oCache->getItem($aAttributes[$this->primaryKey]);
        } else {
            $aRecordSet = $this->_oCache->getItemByProperties($aAttributes);
        }

        if ($aRecordSet) {
            // entry in cache found, load entry from cache
            $this->loadByRecordSet($aRecordSet);
            return true;
        }

        // SQL-Statement to select by fields
        $sql = 'SELECT * FROM `:mytab` WHERE';
        foreach (array_keys($aAttributes) as $sKey) {
            // add quotes if key is a string
            if (is_string($sKey)) {
                $sql .= " $sKey = ':$sKey' AND";
            } else {
                $sql .= " $sKey = :$sKey AND";
            }
        }
        // strip the last " AND" token
        $sql = substr($sql, 0, strlen($sql) - 4);
        $sql = $this->db->prepare($sql, array_merge(array(
            'mytab' => $this->table
        ), $aAttributes));

        // Query the database
        $this->db->query($sql);

        $this->_lastSQL = $sql;

        if ($this->db->numRows() > 1) {
            $msg = 'Tried to load a single line with fields ' . print_r(array_keys($aAttributes), true) . ' and values ' . print_r(array_values($aAttributes), true) . ' from ' . $this->table . ' but found more than one row';
            throw new cException($msg);
        }

        // Advance to the next record, return false if nothing found
        if (!$this->db->nextRecord()) {
            return false;
        }

        $this->loadByRecordSet($this->db->toArray());
        $this->virgin = false;
        return true;
    }

    /**
     * Loads an item by passed where clause from the database.
     * This function is expensive, since it executes allways a query to the
     * database
     * to retrieve the primary key, even if the record set is aleady cached.
     * NOTE: Passed value has to be escaped before. This will not be done by
     * this function.
     *
     * @param string $sWhere
     *         The where clause like 'idart = 123 AND idlang = 1'
     * @throws cException
     *         if more than one item could be found matching the given where clause
     * @return bool
     *         True if the load was successful
     */
    protected function _loadByWhereClause($sWhere) {
        // SQL-Statement to select by whee clause
        $sql = "SELECT %s AS pk FROM `%s` WHERE " . (string) $sWhere;
        $sql = $this->db->prepare($sql, $this->primaryKey, $this->table);

        // Query the database
        $this->db->query($sql);

        $this->_lastSQL = $sql;

        if ($this->db->numRows() > 1) {
            $msg = "Tried to load a single line with where clause '" . $sWhere . "' from " . $this->table . " but found more than one row";
            throw new cException($msg);
        }

        // Advance to the next record, return false if nothing found
        if (!$this->db->nextRecord()) {
            return false;
        }

        $id = $this->db->f('pk');
        return $this->loadByPrimaryKey($id);
    }

    /**
     * Loads an item by ID from the database.
     *
     * @param string $mValue
     *         Specifies the primary key value
     * @return bool
     *         True if the load was successful
     */
    public function loadByPrimaryKey($mValue) {
        $bSuccess = $this->loadBy($this->primaryKey, $mValue);

        if ($bSuccess == true && method_exists($this, '_onLoad')) {
            $this->_onLoad();
        }

        return $bSuccess;
    }

    /**
     * Loads an item by it's recordset.
     *
     * @param array $aRecordSet
     *         The recordset of the item
     */
    public function loadByRecordSet(array $aRecordSet) {
        $this->values = $aRecordSet;
        $this->oldPrimaryKey = $this->values[$this->primaryKey];
        $this->virgin = false;
        $this->_oCache->addItem($this->oldPrimaryKey, $this->values);

        if (method_exists($this, '_onLoad')) {
            $this->_onLoad();
        }
    }

    /**
     * Checks if a the item is already loaded.
     *
     * @return bool
     */
    public function isLoaded() {
        return !$this->virgin;
    }

    /**
     * Function which is called whenever an item is loaded.
     * Inherited classes should override this function if desired.
     */
    protected function _onLoad() {
    }

    /**
     * Gets the value of a specific field.
     *
     * @param string $sField
     *         Specifies the field to retrieve
     * @param bool $bSafe [optional]
     *         Flag to run defined outFilter on passed value
     * @return mixed
     *         Value of the field
     */
    public function getField($sField, $bSafe = true) {
        if ($this->virgin == true) {
            $this->lasterror = 'No item loaded';
            return false;
        }

        if (true == $bSafe) {
            return $this->outFilter($this->values[$sField]);
        } else {
            return $this->values[$sField];
        }
    }

    /**
     * Wrapper for getField (less to type).
     *
     * @param string $sField
     *         Specifies the field to retrieve
     * @param bool $bSafe [optional]
     *         Flag to run defined outFilter on passed value
     * @return mixed
     *         Value of the field
     */
    public function get($sField, $bSafe = true) {
        return $this->getField($sField, $bSafe);
    }

    /**
     * Sets the value of a specific field.
     *
     * @param string $sField
     *         Field name
     * @param string $mValue
     *         Value to set
     * @param bool $bSafe [optional]
     *         Flag to run defined inFilter on passed value
     * @return bool
     */
    public function setField($sField, $mValue, $bSafe = true) {
        if ($this->virgin == true) {
            $this->lasterror = 'No item loaded';
            return false;
        }

        if ($sField == $this->primaryKey) {
            $this->oldPrimaryKey = $this->values[$sField];
        }

        // apply filter on value
        if (true == $bSafe) {
            $mValue = $this->_inFilter($mValue);
        }

        // flag as modified
        if ($this->values[$sField] != $mValue || strlen($this->values[$sField]) != strlen($mValue)) {
            $this->modifiedValues[$sField] = true;
        }

        // set new value
        $this->values[$sField] = $mValue;

        return true;
    }

    /**
     * Shortcut to setField.
     *
     * @param string $sField
     *         Field name
     * @param mixed $mValue
     *         Value to set
     * @param bool $bSafe [optional]
     *         Flag to run defined inFilter on passed value
     * @return bool
     */
    public function set($sField, $mValue, $bSafe = true) {
        return $this->setField($sField, $mValue, $bSafe);
    }

    /**
     * Stores the loaded and modified item to the database.
     *
     * @return bool
     */
    public function store() {
        $this->_executeCallbacks(self::STORE_BEFORE, get_class($this), array(
            $this
        ));

        if ($this->virgin == true) {
            $this->lasterror = 'No item loaded';
            $this->_executeCallbacks(self::STORE_FAILURE, get_class($this), array(
                $this
            ));
            return false;
        }

        $sql = 'UPDATE `' . $this->table . '` SET ';
        $first = true;

        if (!is_array($this->modifiedValues)) {
            $this->_executeCallbacks(self::STORE_SUCCESS, get_class($this), array(
                $this
            ));
            return true;
        }

        foreach ($this->modifiedValues as $key => $bValue) {
            $value = $this->values[$key];
            if (is_string($value)) {
                $value = $this->db->escape($value);
            }

            if ($first == true) {
                $sql .= "`$key` = '" . $value . "'";
                $first = false;
            } else {
                $sql .= ", `$key` = '" . $value . "'";
            }
        }

        $sql .= " WHERE " . $this->primaryKey . " = '" . $this->oldPrimaryKey . "'";

        $this->db->query($sql);

        $this->_lastSQL = $sql;

        if ($this->db->affectedRows() > 0) {
            $this->_oCache->addItem($this->oldPrimaryKey, $this->values);
            $this->_executeCallbacks(self::STORE_SUCCESS, get_class($this), array(
                $this
            ));
            return true;
        }

        $this->_executeCallbacks(self::STORE_FAILURE, get_class($this), array(
            $this
        ));
        return false;
    }

    /**
     * Returns current item data as an assoziative array.
     *
     * @return array|false
     */
    public function toArray() {
        if ($this->virgin == true) {
            $this->lasterror = 'No item loaded';
            return false;
        }

        $aReturn = array();
        foreach ($this->values as $field => $value) {
            $aReturn[$field] = $this->getField($field);
        }
        return $aReturn;
    }

    /**
     * Returns current item data as an object.
     *
     * @return stdClass|false
     */
    public function toObject() {
        $return = $this->toArray();
        return (false !== $return) ? (object) $return : $return;
    }

    /**
     * Sets a custom property.
     *
     * @param string $sType
     *         Specifies the type
     * @param string $sName
     *         Specifies the name
     * @param mixed $mValue
     *         Specifies the value
     * @param int $iClient [optional]
     *         Id of client to set property for
     * @return bool
     */
    public function setProperty($sType, $sName, $mValue, $iClient = 0) {
        // If this object wasn't loaded before, return false
        if ($this->virgin == true) {
            $this->lasterror = 'No item loaded';
            return false;
        }

        // Set the value
        $oProperties = $this->_getPropertiesCollectionInstance($iClient);
        $bResult = $oProperties->setValue($this->primaryKey, $this->get($this->primaryKey), $sType, $sName, $mValue);
        return $bResult;
    }

    /**
     * Returns a custom property.
     *
     * @param string $sType
     *         Specifies the type
     * @param string $sName
     *         Specifies the name
     * @param int $iClient [optional]
     *         Id of client to set property for
     * @return mixed
     *         Value of the given property or false
     */
    public function getProperty($sType, $sName, $iClient = 0) {
        // If this object wasn't loaded before, return false
        if ($this->virgin == true) {
            $this->lasterror = 'No item loaded';
            return false;
        }

        // Return the value
        $oProperties = $this->_getPropertiesCollectionInstance($iClient);
        $mValue = $oProperties->getValue($this->primaryKey, $this->get($this->primaryKey), $sType, $sName);
        return $mValue;
    }

    /**
     * Deletes a custom property.
     *
     * @param string $sType
     *         Specifies the type
     * @param string $sName
     *         Specifies the name
     * @param int $iClient [optional]
     *         Id of client to delete properties
     * @return bool
     */
    public function deleteProperty($sType, $sName, $iClient = 0) {
        // If this object wasn't loaded before, return false
        if ($this->virgin == true) {
            $this->lasterror = 'No item loaded';
            return false;
        }

        // Delete the value
        $oProperties = $this->_getPropertiesCollectionInstance($iClient);
        $bResult = $oProperties->deleteValue($this->primaryKey, $this->get($this->primaryKey), $sType, $sName);
        return $bResult;
    }

    /**
     * Deletes a custom property by its id.
     *
     * @param int $idprop
     *         Id of property
     * @return bool
     */
    public function deletePropertyById($idprop) {
        $oProperties = $this->_getPropertiesCollectionInstance();
        return $oProperties->delete($idprop);
    }

    ///**
    // * Deletes the current item
    // * Method doesn't work, remove in future versions.
    // */
    // function delete() {
    // $this->_collectionInstance->delete($item->get($this->primaryKey));
    // }

    /**
     * Define the filter functions used when data is being stored or retrieved
     * from the database.
     *
     * Examples:
     * <pre>
     * $obj->setFilters(array('addslashes'), array('stripslashes'));
     * $obj->setFilters(array('htmlencode', 'addslashes'), array('stripslashes',
     * 'htmlencode'));
     * </pre>
     *
     * @param array $aInFilters [optional]
     *         Array with function names
     * @param array $aOutFilters [optional]
     *         Array with function names
     */
    public function setFilters($aInFilters = array(), $aOutFilters = array()) {
        $this->_arrInFilters = $aInFilters;
        $this->_arrOutFilters = $aOutFilters;
    }

    /**
     * Filters the passed data using the functions defines in the _arrInFilters
     * array.
     *
     * @todo This method is used from public scope, but it should be protected
     * @see Item::setFilters()
     * @param mixed $mData
     *         Data to filter
     * @return mixed
     *         Filtered data
     */
    public function _inFilter($mData) {
        foreach ($this->_arrInFilters as $_function) {
            if (function_exists($_function)) {
                if (is_array($mData)) {
                    foreach ($mData as $key => $value) {
                        $mData[$key] = $_function($value);
                    }
                } else {
                    $mData = $_function($mData);
                }
            }
        }
        return $mData;
    }

    /**
     * Filters the passed data using the functions defines in the _arrOutFilters
     * array.
     *
     * @see Item::setFilters()
     * @param mixed $mData
     *         Data to filter
     * @return mixed
     *         Filtered data
     */
    public function outFilter($mData) {
        foreach ($this->_arrOutFilters as $_function) {
            if (function_exists($_function)) {
                if (is_array($mData)) {
                    foreach ($mData as $key => $value) {
                        $mData[$key] = $_function($value);
                    }
                } else {
                    $mData = $_function($mData);
                }
            }
        }
        return $mData;
    }

    /**
     * Set meta object class name.
     *
     * @param string $metaObject
     */
    protected function _setMetaObject($metaObject) {
        $this->_metaObject = $metaObject;
    }

    /**
     * Return meta object instance.
     * This object might be retrieved from a global cache ($_metaObjectCache).
     *
     * @return object
     */
    public function getMetaObject() {
        global $_metaObjectCache;

        if (!is_array($_metaObjectCache)) {
            $_metaObjectCache = array();
        }

        $sClassName = $this->_metaObject;
        $qclassname = strtolower($sClassName);

        if (array_key_exists($qclassname, $_metaObjectCache)) {
            if (is_object($_metaObjectCache[$qclassname])) {
                if (strtolower(get_class($_metaObjectCache[$qclassname])) == $qclassname) {
                    $_metaObjectCache[$qclassname]->setPayloadObject($this);
                    return $_metaObjectCache[$qclassname];
                }
            }
        }

        if (class_exists($sClassName)) {
            $_metaObjectCache[$qclassname] = new $sClassName($this);
            return $_metaObjectCache[$qclassname];
        }
    }
}
