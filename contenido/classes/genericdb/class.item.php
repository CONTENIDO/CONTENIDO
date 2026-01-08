<?php

/**
 * This file contains the generic db item class.
 *
 * @package    Core
 * @subpackage GenericDB
 * @author     Timo Hummel
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class Item
 * Abstract class for database based items.
 *
 * @package    Core
 * @subpackage GenericDB
 */
abstract class Item extends cItemBaseAbstract
{

    /**
     * @var array Storage of the source table to use for the user information
     */
    public $values;

    /**
     * @var ?array Storage of the fields which were modified, where the keys are the
     *      field names and the values just simple booleans.
     */
    protected $modifiedValues;

    /**
     * @var string Stores the old primary key, just in case somebody wants to change it
     */
    protected $oldPrimaryKey;

    /**
     * @var array List of function names of the filters used when data is stored to the db.
     */
    protected $_arrInFilters = [
        'htmlspecialchars',
        'addslashes'
    ];

    /**
     * @var array List of function names of the filters used when data is retrieved from the db.
     */
    protected $_arrOutFilters = [
        'stripslashes',
        'htmldecode'
    ];

    /**
     * @var string Class name of meta object
     */
    protected $_metaObject;

    /**
     * @var string Last executed SQL statement
     */
    protected $_lastSQL;

    /**
     * Constructor to create an instance of this class.
     *
     * @param string $table The table to use as information source
     * @param int|string $primaryKey The primary key to use
     * @throws cInvalidArgumentException
     */
    public function __construct($table, $primaryKey)
    {
        parent::__construct($table, $primaryKey, get_parent_class($this));
    }

    /**
     * Resets class variables back to default. This is handy in case a new
     * item is tried to be loaded into this class instance.
     */
    protected function _resetItem()
    {
        parent::_resetItem();

        // Make sure not to reset filters because then default filters would
        // always be used for loading
        $this->values = null;
        $this->modifiedValues = null;
        $this->_metaObject = null;
        $this->_lastSQL = null;
    }

    /**
     * Loads an item by colum/field from the database.
     *
     * @param string $name Specifies the field
     * @param mixed $value Specifies the value
     * @param bool $safe Use inFilter or not
     * @param bool $allowOneResult Flag to allow only one result
     * @return bool True if the load was successful
     * @throws cDbException
     * @throws cException if more than one item has been found matching the given arguments
     */
    public function loadBy($name, $value, $safe = true, $allowOneResult = true)
    {
        // Reset class variables back to default before loading
        $this->_resetItem();

        if ($safe) {
            $value = $this->inFilter($value);
        }

        // Check, if cache contains a matching entry
        if ($name === $this->_primaryKeyName) {
            $recordSet = $this->_oCache->getItem($value);
        } else {
            $recordSet = $this->_oCache->getItemByProperty($name, $value);
        }

        if ($recordSet) {
            // Entry in cache found, load entry from cache
            $this->loadByRecordSet($recordSet);
            return true;
        }

        // SQL-Statement to select by field
        $sql = "SELECT * FROM `%s` WHERE %s = '%s'";
        $sql = $this->db->prepare($sql, $this->table, $name, $value);

        // Query the database
        $this->db->query($sql);

        $this->_lastSQL = $sql;

        if ($allowOneResult && $this->db->numRows() > 1) {
            $msg = "Tried to load a single line with field $name and value"
                . " $value from " . $this->table . " but found more than one row";
            throw new cException($msg);
        }

        // Advance to the next record, return false if nothing found
        if (!$this->db->nextRecord()) {
            return false;
        }

        $this->loadByRecordSet($this->db->toArray());
        $this->_setLoaded(true);
        return true;
    }

    /**
     * Loads an item by columns/fields from the database.
     *
     * @param array $attributes Associative array with field / value pairs
     * @param bool $safe Use inFilter or not
     * @param bool $allowOneResult  Flag to allow only one result
     * @return bool True if the load was successful
     * @throws cDbException
     * @throws cException if more than one item could be found matching the given arguments
     */
    public function loadByMany(array $attributes, $safe = true, $allowOneResult = true)
    {
        // Reset class variables back to default before loading
        $this->_resetItem();

        if ($safe) {
            $attributes = $this->inFilter($attributes);
        }

        // Check, if cache contains a matching entry
        if (count($attributes) == 1 && isset($attributes[$this->getPrimaryKeyName()])) {
            $recordSet = $this->_oCache->getItem($attributes[$this->getPrimaryKeyName()]);
        } else {
            $recordSet = $this->_oCache->getItemByProperties($attributes);
        }

        if ($recordSet) {
            // Entry in cache found, load entry from cache
            $this->loadByRecordSet($recordSet);
            return true;
        }

        // SQL-Statement to select by fields
        $sql = $this->_buildLoadByManyQuery($attributes);

        // Query the database
        $this->db->query($sql);

        $this->_lastSQL = $sql;

        if ($allowOneResult && $this->db->numRows() > 1) {
            $msg = 'Tried to load a single line with fields '
                . print_r(array_keys($attributes), true) . ' and values '
                . print_r(array_values($attributes), true) . ' from '
                . $this->table . ' but found more than one row';
            throw new cException($msg);
        }

        // Advance to the next record, return false if nothing found
        if (!$this->db->nextRecord()) {
            return false;
        }

        $this->loadByRecordSet($this->db->toArray());
        $this->_setLoaded(true);
        return true;
    }

    /**
     * Creates a select query by many fields.
     *
     * @param array $fields Associative fields and values list
     * @return string Build SQL statement
     * @throws cDbException
     */
    protected function _buildLoadByManyQuery(array $fields): string
    {
        // SQL-Statement to select by fields
        $fieldsSql = [];

        foreach ($fields as $key => $value) {
            if (is_int($value) || is_float($value) || is_bool($value)) {
                if (is_bool($value)) {
                    $fields[$key] = $value ? '1' : '0';
                }
                $fieldsSql[] = "`$key` = :$key";
            } elseif (is_null($value)) {
                $fieldsSql[] = "`$key` IS NULL";
            } else {
                // Treat everything else as a string
                $fieldsSql[] = "`$key` = ':$key'";
            }
        }
        $sql = 'SELECT * FROM `:mytab` WHERE ' . implode(' AND ', $fieldsSql);

        return $this->db->prepare($sql, array_merge([
            'mytab' => $this->table
        ], $fields));
    }

    /**
     * Loads an item by passed WHERE clause from the database.
     * This function is expensive, since it executes always a query to the
     * database to retrieve the primary key, even if the record set is already cached.
     * NOTE:
     * Passed value has to be escaped before. This will not be done by this function.
     *
     * @param string $where The WHERE clause like 'idart = 123 AND idlang = 1'
     * @return bool True if the load was successful
     * @throws cDbException
     * @throws cException if more than one item could be found matching the given WHERE clause
     */
    protected function _loadByWhereClause(string $where): bool
    {
        // SQL-Statement to select by WHERE clause
        $sql = "SELECT %s AS `pk` FROM `%s` WHERE " . cSecurity::toString($where);
        $sql = $this->db->prepare($sql, $this->getPrimaryKeyName(), $this->table);

        // Query the database
        $this->db->query($sql);

        $this->_lastSQL = $sql;

        if ($this->db->numRows() > 1) {
            $msg = "Tried to load a single line with WHERE clause '" . $where
                . "' from " . $this->table . " but found more than one row";
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
     * @param string|int $value Specifies the primary key value
     * @return bool True if the load was successful
     * @throws cDbException|cException
     */
    public function loadByPrimaryKey($value)
    {
        if (is_null($value) || (is_string($value) && empty($value))) {
            return false;
        }

        $bSuccess = $this->loadBy($this->_primaryKeyName, $value);
        if ($bSuccess && method_exists($this, '_onLoad')) {
            $this->_onLoad();
        }

        return $bSuccess;
    }

    /**
     * Loads an item by its recordset.
     *
     * @param array $recordSet The recordset of the item
     */
    public function loadByRecordSet(array $recordSet)
    {
        $this->values = $recordSet;
        $this->oldPrimaryKey = $this->values[$this->getPrimaryKeyName()];
        $this->_setLoaded(true);
        $this->_oCache->addItem($this->oldPrimaryKey, $this->values);

        if (method_exists($this, '_onLoad')) {
            $this->_onLoad();
        }
    }

    /**
     * Function which is called whenever an item is loaded.
     * Inherited classes should override this function if desired.
     */
    protected function _onLoad()
    {
    }

    /**
     * Returns the primary key value (id) of the item.
     *
     * @return int|string|bool|mixed
     *      Primary keys are usually numeric values, therefore the return type
     *      will be mostly an integer, but it can also be a string, or false,
     *      if the item wasn't or couldn't be loaded before.
     * @since CONTENIDO 4.10.2
     */
    public function getId()
    {
        return $this->getField($this->getPrimaryKeyName(), false);
    }

    /**
     * Gets the value of a specific field.
     *
     * @param string $name Specifies the field to retrieve
     * @param bool $safe Flag to run defined outFilter on passed value
     * @return mixed Value of the field
     */
    public function getField($name, $safe = true)
    {
        if (!$this->isLoaded()) {
            $this->lasterror = 'No item loaded';
            return false;
        }

        if ($safe) {
            return $this->outFilter($this->values[$name]);
        } else {
            return $this->values[$name];
        }
    }

    /**
     * Wrapper for getField (less to type).
     *
     * @param string $name Specifies the field to retrieve
     * @param bool $safe  Flag to run defined outFilter on passed value
     * @return mixed Value of the field
     */
    public function get($name, $safe = true)
    {
        return $this->getField($name, $safe);
    }

    /**
     * Sets the value of a specific field.
     *
     * @param string $name Field name
     * @param mixed $value Value to set
     * @param bool $safe Flag to run defined inFilter on passed value
     * @return bool
     */
    public function setField($name, $value, $safe = true)
    {
        if (!$this->isLoaded()) {
            $this->lasterror = 'No item loaded';
            return false;
        }

        if ($name == $this->getPrimaryKeyName()) {
            $this->oldPrimaryKey = $this->values[$name];
        }

        // Apply filter on value
        if ($safe) {
            $value = $this->inFilter($value);
        }

        // Flag as modified
        $modified = false;
        if (!isset($this->values[$name])) {
            $modified = true;
        } elseif ($this->values[$name] !== $value) {
            $modified = true;
        }
        if ($modified) {
            if (!is_array($this->modifiedValues)) {
                $this->modifiedValues = [];
            }
            $this->modifiedValues[$name] = true;
        }
#        if ($this->values[$name] != $value || cString::getStringLength($this->values[$name]) != cString::getStringLength($value)) {
#            $this->modifiedValues[$name] = true;
#        }

        // Set new value
        $this->values[$name] = $value;

        return true;
    }

    /**
     * Shortcut to setField.
     *
     * @param string $name Field name
     * @param mixed $value Value to set
     * @param bool $safe Flag to run defined inFilter on passed value
     * @return bool
     */
    public function set($name, $value, $safe = true)
    {
        return $this->setField($name, $value, $safe);
    }

    /**
     * Stores the loaded and modified item to the database.
     *
     * @return bool
     * @throws cDbException|cInvalidArgumentException
     */
    public function store()
    {
        $class = get_class($this);

        $this->_executeCallbacks(self::STORE_BEFORE, $class, [$this]);

        if (!$this->isLoaded()) {
            $this->lasterror = 'No item loaded';
            $this->_executeCallbacks(self::STORE_FAILURE, $class, [$this]);
            return false;
        }

        if (!is_array($this->modifiedValues)) {
            $this->_executeCallbacks(self::STORE_SUCCESS, $class, [$this]);
            return true;
        }

        $sql = $this->_buildStoreQuery($this->modifiedValues);
        $this->db->query($sql);

        $this->_lastSQL = $sql;

        if ($this->db->affectedRows() > 0) {
            $this->_oCache->addItem($this->oldPrimaryKey, $this->values);
            $this->_executeCallbacks(self::STORE_SUCCESS, $class, [$this]);
            $this->modifiedValues = null;
            return true;
        }

        $this->_executeCallbacks(self::STORE_FAILURE, $class, [$this]);
        return false;
    }

    /**
     * Checks whether the item has been modified or not.
     *
     * @return bool
     * @since CONTENIDO 4.10.2
     */
    public function isModified(): bool
    {
        return is_array($this->modifiedValues) && count($this->modifiedValues);
    }

    /**
     * Creates an update query by many fields.
     *
     * @param array $fields Associative fields and values list
     */
    protected function _buildStoreQuery(array $fields): string
    {
        $fieldsSql = [];

        foreach ($fields as $key => $mValue) {
            $value = $this->values[$key];
            if (is_int($value) || is_float($value) || is_bool($value)) {
                if (is_bool($value)) {
                    $value = $value ? '1' : '0';
                }
                $fieldsSql[] = "`$key` = " . $value;
            } elseif (is_null($value)) {
                $fieldsSql[] = "`$key` = NULL";
            } else {
                // Treat everything else as a string
                $fieldsSql[] = "`$key` = '" . $this->db->escape($value) . "'";
            }
        }

        $sql = 'UPDATE `' . $this->table . '` SET ' . implode(', ', $fieldsSql);
        $sql .= " WHERE `" . $this->getPrimaryKeyName() . "` = ";
        if (is_string($this->oldPrimaryKey)) {
            $sql .= "'" . $this->oldPrimaryKey . "'";
        } else {
            $sql .= $this->oldPrimaryKey;
        }

        return $sql;
    }

    /**
     * Returns current item data as an associative array.
     *
     * @return array|false
     */
    public function toArray()
    {
        if (!$this->isLoaded()) {
            $this->lasterror = 'No item loaded';
            return false;
        }

        $return = [];
        foreach ($this->values as $name => $value) {
            $return[$name] = $this->getField($name);
        }
        return $return;
    }

    /**
     * Returns current item data as an object.
     *
     * @return stdClass|false
     */
    public function toObject()
    {
        $return = $this->toArray();
        return $return ? (object)$return : false;
    }

    /**
     * Sets a custom property.
     *
     * @param string $type Specifies the type
     * @param string $name Specifies the name
     * @param mixed $value Specifies the value
     * @param int $clientId Id of client to set property for
     * @return bool
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function setProperty($type, $name, $value, $clientId = 0)
    {
        // If this object wasn't loaded before, return false
        if (!$this->isLoaded()) {
            $this->lasterror = 'No item loaded';
            return false;
        }

        // Set the value
        $oProperties = $this->_getPropertiesCollectionInstance(cSecurity::toInteger($clientId));
        return $oProperties->setValue(
            $this->getPrimaryKeyName(), $this->get($this->getPrimaryKeyName()), $type, $name, $value
        );
    }

    /**
     * Returns a custom property.
     *
     * @param string $type Specifies the type
     * @param string $name Specifies the name
     * @param int $clientId Id of client to set property for
     * @return mixed Value of the given property or false
     * @throws cDbException|cException
     */
    public function getProperty($type, $name, $clientId = 0)
    {
        // If this object wasn't loaded before, return false
        if (!$this->isLoaded()) {
            $this->lasterror = 'No item loaded';
            return false;
        }

        // Return the value
        $oProperties = $this->_getPropertiesCollectionInstance(cSecurity::toInteger($clientId));
        return $oProperties->getValue(
            $this->getPrimaryKeyName(), $this->get($this->getPrimaryKeyName()), $type, $name
        );
    }

    /**
     * Deletes a custom property.
     *
     * @param string $type Specifies the type
     * @param string $name Specifies the name
     * @param int $clientId Id of client to delete properties
     * @return bool
     * @throws cDbException|cInvalidArgumentException
     */
    public function deleteProperty($type, $name, $clientId = 0)
    {
        // If this object wasn't loaded before, return false
        if (!$this->isLoaded()) {
            $this->lasterror = 'No item loaded';
            return false;
        }

        // Delete the value
        $oProperties = $this->_getPropertiesCollectionInstance(cSecurity::toInteger($clientId));
        $numDeleted = $oProperties->deleteValue(
            $this->getPrimaryKeyName(), $this->get($this->getPrimaryKeyName()), $type, $name
        );
        return $numDeleted > 0;
    }

    /**
     * Deletes a custom property by its id.
     *
     * @param int $id Id of property
     * @throws cDbException|cInvalidArgumentException
     */
    public function deletePropertyById($id): bool
    {
        return $this->_getPropertiesCollectionInstance()->delete($id);
    }

    /**
     * Define the filter functions used when data is being stored or retrieved from the database.
     *
     * Examples:
     * <pre>
     * $obj->setFilters(['addslashes'], ['stripslashes']);
     * $obj->setFilters(
     *     ['htmlencode', 'addslashes'], ['stripslashes', 'htmlencode']
     * );
     * </pre>
     *
     * @param array $inFilters [optional] Array with function names
     * @param array $outFilters [optional] vArray with function names
     */
    public function setFilters(array $inFilters = [], array $outFilters = [])
    {
        $this->_arrInFilters = $inFilters;
        $this->_arrOutFilters = $outFilters;
    }

    /**
     * @deprecated [2023-02-11] Since CONTENIDO 4.10.2, use {@see Item::inFilter()} instead
     */
    public function _inFilter($data)
    {
        cDeprecated("The function _inFilter() is deprecated since CONTENIDO 4.10.2, use Item::inFilter() instead.");
        return $this->inFilter($data);
    }

    /**
     * Filters the provided data using the functions defines in the _arrInFilters array.
     *
     * @param mixed $data bData to filter
     * @return mixed Filtered data
     * @since CONTENIDO 4.10.2
     * @see Item::setFilters()
     */
    public function inFilter($data)
    {
        return $this->_filter($data, $this->_arrInFilters);
    }

    /**
     * Filters the provided data using the functions defines in the _arrOutFilters array.
     *
     * @param mixed $data Data to filter
     * @return mixed Filtered data
     * @see Item::setFilters()
     */
    public function outFilter($data)
    {
        return $this->_filter($data, $this->_arrOutFilters);
    }

    /**
     * Filters the provided data using the provided filter functions list.
     *
     * @param mixed $data Data to filter
     * @param array $filterFunctions List of functions
     * @return mixed Filtered data
     */
    protected function _filter($data, array $filterFunctions)
    {
        foreach ($filterFunctions as $_function) {
            if (function_exists($_function)) {
                // Check whether it is a string function and therefore
                // expects a value of type string
                $isStringFunction = in_array(
                    $_function, $this->_settings['string_filter_functions']
                );
                if (is_array($data)) {
                    foreach ($data as $key => $value) {
                        if ($isStringFunction) {
                            if (is_string($value)) {
                                $data[$key] = $_function($value);
                            }
                        } else {
                            $data[$key] = $_function($value);
                        }
                    }
                } else {
                    if ($isStringFunction) {
                        if (is_string($data)) {
                            $data = $_function($data);
                        }
                    } else {
                        $data = $_function($data);
                    }
                }
            }
        }
        return $data;
    }

    /**
     * Set a meta object class name.
     *
     * @param string $metaObject
     * @TODO Function doesn't seem to be used anywhere
     */
    protected function _setMetaObject($metaObject)
    {
        $this->_metaObject = $metaObject;
    }

    /**
     * Return a meta object instance.
     * This object might be retrieved from a global cache ($_metaObjectCache).
     *
     * @TODO Function seem to be a leftover from earlier times, it uses the property `$this->_metaObject` but no one seems to set this property, see also `_setMetaObject()` above.
     * @return object|void
     */
    public function getMetaObject()
    {
        global $_metaObjectCache;

        if (!is_array($_metaObjectCache)) {
            $_metaObjectCache = [];
        }

        if (empty($this->_metaObject)) {
            return null;
        }

        $sClassName = $this->_metaObject;
        $key = cString::toLowerCase($sClassName);

        if (array_key_exists($key, $_metaObjectCache)) {
            if (is_object($_metaObjectCache[$key])) {
                if (cString::toLowerCase(get_class($_metaObjectCache[$key])) == $key) {
                    // @TODO The function `setPayloadObject` exists only in `cTreeItem`.
                    /** See {@see cTreeItem::setPayloadObject()} */
                    $_metaObjectCache[$key]->setPayloadObject($this);
                    return $_metaObjectCache[$key];
                }
            }
        }

        if (class_exists($sClassName)) {
            $_metaObjectCache[$key] = new $sClassName($this);
            return $_metaObjectCache[$key];
        }

        return null;
    }

}
