<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Generic database abstraction functions.
 *
 * NOTE:
 * Because of required downwards compatibilitiy all protected/private member
 * variables or methods don't have an leading underscore.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.2.2
 * @author     Timo A. Hummel
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created  2003-07-18
 *   modified 2008-07-02, Frederic Schneider, add security fix
 *   modified 2010-05-20, Murat Purc, Removed check of $_REQUEST['cfg'] during processing ticket [#CON-307]
 *   modified 2011-03-10, Murat Purc, Refactoring of Item and ItemCollection, partly port to PHP 5,
 *                        new Contenido_ItemException and Contenido_ItemBaseAbstract, documentation and formatting.
 *   modified 2011-03-13  Murat Purc, added Contenido_ItemCache() to enable caching of result sets.
 *   modified 2011-05-20  Murat Purc, fixed wrong caching behavior in Contenido_ItemCache.
 *   modified 2011-06-28  Murat Purc, added function escape().
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

global $cfg;

// Try to load GenericDB database driver
$driver_filename = $cfg['path']['contenido'].$cfg['path']['classes'].'drivers/'.$cfg['sql']['gdb_driver'].'/class.gdb.'.$cfg['sql']['gdb_driver'].'.php';

if (file_exists($driver_filename)) {
    include_once($driver_filename);
}


/**
 * Class Contenido_ItemException.
 * @author     Murat Purc <murat@purc.de>
 * @version    0.1
 * @copyright  four for business AG <www.4fb.de>
 */
class Contenido_ItemException extends Exception {}


/**
 * Class Contenido_ItemCache.
 *
 * Implements features to cache entries, usually result sets of Item classes.
 * Contains a list of self instances, where each instance contains cached Items
 * fore one specific table.
 *
 * @author     Murat Purc <murat@purc.de>
 * @version    0.1.2
 * @copyright  four for business AG <www.4fb.de>
 */
class Contenido_ItemCache
{
    /**
     * List of self instances (Contenido_ItemCache)
     * @var  array
     */
    protected static $_oInstances = array();

    /**
     * Assoziative cache array
     * @var  array
     */
    protected $_aItemsCache = array();

    /**
     * Table name for current instance
     * @var  string
     */
    protected $_sTable = '';

    /**
     * Max number of items to cache
     * @var  int
     */
    protected $_iMaxItemsToCache = 10;

    /**
     * Enable caching
     * @var  bool
     */
    protected $_bEnable = false;

    /**
     * Contructor of Contenido_ItemCache
     * @param  string  $sTable   Table name
     * @param  array   $aOptions Options array as follows:
     *                 - $aOptions['max_items_to_cache'] = (int) Number of items to cache
     *                 - $aOptions['enable'] = (bool) Flag to enable caching
     */
    protected function __construct($sTable, array $aOptions = array())
    {
        $this->_sTable = $sTable;
        if (isset($aOptions['max_items_to_cache']) && (int) $aOptions['max_items_to_cache'] > 0) {
            $this->_iMaxItemsToCache = (int) $aOptions['max_items_to_cache'];
        }
        if (isset($aOptions['enable']) && is_bool($aOptions['enable'])) {
            $this->_bEnable = (bool) $aOptions['enable'];
        }
    }

    /**
     * Prevent cloning
     */
    protected function __clone()
    {
    }

    /**
     * Returns item cache instance, creates it, if not done before.
     * Works as a singleton for one specific table.
     *
     * @param  string  $sTable   Table name
     * @param  array   $aOptions Options array as follows:
     *                 - $aOptions['max_items_to_cache'] = (int) Number of items to cache
     *                 - $aOptions['enable'] = (bool) Flag to enable caching
     */
    public static function getInstance($sTable, array $aOptions = array())
    {
        if (!isset(self::$_oInstances[$sTable])) {
            self::$_oInstances[$sTable] = new self($sTable, $aOptions);
        }
        return self::$_oInstances[$sTable];
    }

    /**
     * Returns items cache list.
     *
     * @return  array
     */
    public function getItemsCache()
    {
        return $this->_aItemsCache;
    }

    /**
     * Returns existing entry from cache by it's id.
     *
     * @param   mixed  $mId
     * @return  array|null
     */
    public function getItem($mId)
    {
        if (!$this->_bEnable) {
            return null;
        }

        if (isset($this->_aItemsCache[$mId])) {
            return $this->_aItemsCache[$mId];
        } else {
            return null;
        }
    }

    /**
     * Returns existing entry from cache by matching propery value.
     *
     * @param   mixed  $mProperty
     * @param   mixed  $mValue
     * @return  array|null
     */
    public function getItemByProperty($mProperty, $mValue)
    {
        if (!$this->_bEnable) {
            return null;
        }

        // loop thru all cached entries and try to find a entry by it's property
        foreach ($this->_aItemsCache as $id => $aEntry) {
            if (isset($aEntry[$mProperty]) && $aEntry[$mProperty] == $mValue) {
                return $aEntry;
            }
        }
        return null;
    }

    /**
     * Adds passed item data to internal cache
     *
     * @param   mixed  $mId
     * @param   array  $aData  Usually the recordset
     * @return  void
     */
    public function addItem($mId, array $aData)
    {
        if (!$this->_bEnable) {
            return null;
        }

        if ($this->_iMaxItemsToCache == count($this->_aItemsCache)) {
            // we have reached the maximum number of cached items, remove first entry
            $firstEntryKey = array_shift(array_keys($this->_aItemsCache));
            unset($this->_aItemsCache[$firstEntryKey]);
        }

        // add entry
        $this->_aItemsCache[$mId] = $aData;
    }

    /**
     * Removes existing cache entry by it's key
     *
     * @param   mixed  $mId
     * @return  void
     */
    public function removeItem($mId)
    {
        if (!$this->_bEnable) {
            return null;
        }

        // remove entry
        if (!isset($this->_aItemsCache[$mId])){
            unset($this->_aItemsCache[$mId]);
        }
    }
}


/**
 * Class Contenido_ItemBaseAbstract.
 * Base class with common features for database based items and item collections.
 *
 * @author     Murat Purc <murat@purc.de>
 * @version    0.2
 * @copyright  four for business AG <www.4fb.de>
 */
abstract class Contenido_ItemBaseAbstract
{
    /**
     * Database instance, contains the database object
     * @var  DB_Contenido
     */
    protected $db;

    /**
     * Second DB instance, is required for some additional queries without
     * losing an current existing query result.
     * @var  DB_Contenido
     */
    protected $secondDb;

    /**
     * Property collection instance
     * @var  cApiPropertyCollection
     */
    protected $properties;

    /**
     * Item cache instance
     * @var  Contenido_ItemCache
     */
    protected static $_oCache;

    /**
     * GenericDB settings, see $cfg['sql']
     * @var  array
     */
    protected $_settings;

    /**
     * Storage of the source table to use for the information
     * @var  string
     */
    protected $table;

    /**
     * Storage of the primary key
     * @var  string
     * @todo remove access from public
     */
    public $primaryKey;

    /**
     * Checks for the virginity of created objects. If true, the object
     * is virgin and no operations on it except load-Functions are allowed.
     * @todo remove access from public
     * @var  bool
     */
    public $virgin;

    /**
     * Lifetime of results/created objects?
     * FIXME  Not used at the moment!
     * @var  int
     */
    protected $lifetime;

    /**
     * Storage of the last occured error
     * @var  string
     */
    protected $lasterror = '';

    /**
     * Cache the result items
     * FIXME  seems to not used, remove it!
     * @var  array
     */
    protected $cache;

    /**
     * Classname of current instance
     * @var  string
     */
    protected $_className;


    /**
     * Sets some common properties
     *
     * @param  string  $sTable       Name of table
     * @param  string  $sPrimaryKey  Primary key of table
     * @param  string  $sClassName   Name of parent class
     * @param  int     $iLifetime    Lifetime of the object in seconds (NOT USED!)
     * @throws  Contenido_ItemException  If table name or primary key is not set
     */
    protected function __construct($sTable, $sPrimaryKey, $sClassName, $iLifetime = 10)
    {
        global $cfg;

        $this->db = new DB_Contenido();

        if ($sTable == '') {
            $sMsg = "$sClassName: No table specified. Inherited classes *need* to set a table";
            throw new Contenido_ItemException($sMsg);
        } elseif ($sPrimaryKey == '') {
            $sMsg = "No primary key specified. Inherited classes *need* to set a primary key";
            throw new Contenido_ItemException($sMsg);
        }

        $this->_settings = $cfg['sql'];

        // instanciate caching
        $aCacheOpt = (isset($this->_settings['cache'])) ? $this->_settings['cache'] : array();
        $this->_oCache = Contenido_ItemCache::getInstance($sTable, $aCacheOpt);

        $this->table      = $sTable;
        $this->primaryKey = $sPrimaryKey;
        $this->virgin     = true;
        $this->lifetime   = $iLifetime;
        $this->_className = $sClassName;
    }


    /**
     * Escape string for using in SQL-Statement.
     *
     * @param   string  $sString  The string to escape
     * @return  string  Escaped string
     */
    public function escape($sString)
    {
        return $this->db->escape($sString);
    }

    /**
     * Returns the second database instance, usable to run additional statements
     * without losing current query results.
     *
     * @return  DB_Contenido
     */
    protected function _getSecondDBInstance()
    {
        if (!isset($this->secondDb) || !($this->secondDb instanceof DB_Contenido)) {
            $this->secondDb = new DB_Contenido();
        }
        return $this->secondDb;
    }

    /**
     * Returns properties instance, instantiates it if not done before.
     *
     * @return  cApiPropertyCollection
     */
    protected function _getPropertiesCollectionInstance()
    {
        // Runtime on-demand allocation of the properties object
        if (!isset($this->properties) || !($this->properties instanceof cApiPropertyCollection)) {
            $this->properties = new cApiPropertyCollection();
        }
        return $this->properties;
    }
}


/**
 * Class ItemCollection
 * Abstract class for database based item collections.
 *
 * @author     Timo A. Hummel <Timo.Hummel@4fb.de>
 * @author     Murat Purc <murat@purc.de>
 * @version    0.2
 * @copyright  four for business 2003
 */
abstract class ItemCollection extends Contenido_ItemBaseAbstract
{
    /**
     * Storage of all result items
     * @var string Contains all result items
     */
    protected $objects;

    /**
     * GenericDB driver object
     * @var  gdbDriver
     */
    protected $_driver;


    /**
     * List of instances of ItemCollection implementations
     * @var  array
     */
    protected $_collectionCache = array();

    /**
     * @var string Single item class
     */
    protected $_itemClass;

    /**
     * @var object Iterator object for the next() method
     */
    protected $_iteratorItem;

    /**
     * @var array Reverse join partners for this data object
     */
    protected $_JoinPartners;

    /**
     * @var array Forward join partners for this data object
     */
    protected $_forwardJoinPartners;

    /**
     * @var array Where restrictions for the query
     */
    protected $_whereRestriction;

    /**
     * @var array Inner group conditions
     */
    protected $_innerGroupConditions = array();

    /**
     * @var array Group conditions
     */
    protected $_groupConditions;

    /**
     * @var array Result fields for the query
     */
    protected $_resultFields = array();

    /**
     * @var string Encoding
     */
    protected $_encoding;

    /**
     * Stores all operators which are supported by GenericDB
     * Unsupported operators are passed trough as-is.
     * @var  array
     */
    protected $_aOperators;

    /**
     * Flag to select all fields in a query. Reduces the number of queries send
     * to the database.
     * @var  bool
     */
    protected $_bAllMode = false;


    /**
     * Constructor Function
     *
     * @param  string  $sTable       The table to use as information source
     * @param  string  $sPrimaryKey  The primary key to use
     * @param  int     $iLifetime
     */
    public function __construct($sTable, $sPrimaryKey, $iLifetime = 10)
    {
        parent::__construct($sTable, $sPrimaryKey, get_parent_class($this), $iLifetime);

        $this->resetQuery();

        // Try to load driver
        $this->_initializeDriver();

        // Try to find out the current encoding
        if (isset($GLOBALS['lang']) && isset($GLOBALS['aLanguageEncodings'])) {
            $this->setEncoding($GLOBALS['aLanguageEncodings'][$GLOBALS['lang']]);
        }

        $this->_aOperators = array(
            '=', '!=', '<>', '<', '>', '<=', '>=', 'LIKE', 'DIACRITICS'
        );
    }

    /**
     * Constructor function for downwards compatibility
     */
    public function ItemCollection($sTable, $sPrimaryKey, $iLifetime = 10)
    {
        $this->__construct($sTable, $sPrimaryKey, $iLifetime);
    }


    /**
     * Defines the reverse links for this table.
     *
     * Important: The class specified by $sForeignCollectionClass needs to be a
     *            collection class and has to exist.
     *            Define all links in the constructor of your object.
     *
     * @param   string  $sForeignCollectionClass  Specifies the foreign class to use
     * @return  void
     */
    protected function _setJoinPartner($sForeignCollectionClass)
    {
        if (class_exists($sForeignCollectionClass)) {
            // Add class
            if (!in_array($sForeignCollectionClass, $this->_JoinPartners)) {
                $this->_JoinPartners[] = strtolower($sForeignCollectionClass);
            }
        } else {
            $sMsg = "Could not instanciate class [$sForeignCollectionClass] for use "
                  . "with _setJoinPartner in class " . get_class($this);
            cWarning(__FILE__, __LINE__, $sMsg);
        }
    }

    /**
     * Method to set the accompanying item object.
     *
     * @param   string  $sClassName  Specifies the classname of item
     * @return  void
     */
    protected function _setItemClass($sClassName)
    {
        if (class_exists($sClassName)) {
            $this->_itemClass = $sClassName;
            $this->_itemClassInstance = new $sClassName;

            // Initialize driver in case the developer does a setItemClass-Call
            // before calling the parent constructor
            $this->_initializeDriver();
            $this->_driver->setItemClassInstance($this->_itemClassInstance);
        } else {
            $sMsg = "Could not instanciate class [$sClassName] for use with "
                  . "_setItemClass in class " . get_class($this);
            cWarning(__FILE__, __LINE__, $sMsg);
        }
    }

    /**
     * Initializes the driver to use with GenericDB.
     *
     * @param $bForceInit boolean If true, forces the driver to initialize, even if it already exists.
     */
    protected function _initializeDriver($bForceInit = false)
    {
        if (!is_object($this->_driver) || $bForceInit == true) {
            $this->_driver = new gdbMySQL();
        }
    }

    /**
     * Sets the encoding.
     * @param  string  $sEncoding
     */
    public function setEncoding($sEncoding)
    {
        $this->_encoding = $sEncoding;
        $this->_driver->setEncoding($sEncoding);
    }

    /**
     * Sets the query to use foreign tables in the resultset
     * @param  string  $sForeignClass  The class of foreign table to use
     */
    public function link($sForeignClass)
    {
        if (class_exists($sForeignClass)) {
            $this->_links[$sForeignClass] = new $sForeignClass;
        } else {
            $sMsg = "Could not find class [$sForeignClass] for use with link in class "
                  . get_class($this);
            cWarning(__FILE__, __LINE__, $sMsg);
        }
    }

    /**
     * Sets the limit for results
     * @param  int  $iRowStart
     * @param  int  $iRowCount
     */
    public function setLimit($iRowStart, $iRowCount)
    {
        $this->_limitStart = $iRowStart;
        $this->_limitCount = $iRowCount;
    }

    /**
     * Restricts a query with a where clause
     * @param  string  $sField
     * @param  mixed   $mRestriction
     * @param  string  $sOperator
     */
    public function setWhere($sField, $mRestriction, $sOperator = '=')
    {
        $sField = strtolower($sField);
        $this->_where['global'][$sField]['operator']    = $sOperator;
        $this->_where['global'][$sField]['restriction'] = $mRestriction;
    }

    /**
     * Removes a previous set where clause (@see ItemCollection::setWhere).
     * @param  string  $sField
     * @param  mixed   $mRestriction
     * @param  string  $sOperator
     */
    public function deleteWhere($sField, $mRestriction, $sOperator = '=')
    {
        $sField = strtolower($sField);
        if (isset($this->_where['global'][$sField]) && is_array($this->_where['global'][$sField])) {
            if ($this->_where['global'][$sField]['operator'] == $sOperator &&
                $this->_where['global'][$sField]['restriction'] == $mRestriction) {
                unset($this->_where['global'][$sField]);
            }
        }
    }

    /**
     * Restricts a query with a where clause, groupable
     * @param  string  $sGroup
     * @param  string  $sField
     * @param  mixed   $mRestriction
     * @param  string  $sOperator
     */
    public function setWhereGroup($sGroup, $sField, $mRestriction, $sOperator = '=')
    {
        $sField = strtolower($sField);
        $this->_where['groups'][$sGroup][$sField]['operator'] = $sOperator;
        $this->_where['groups'][$sGroup][$sField]['restriction'] = $mRestriction;
    }

    /**
     * Removes a previous set groupable where clause (@see ItemCollection::setWhereGroup).
     * @param  string  $sGroup
     * @param  string  $sField
     * @param  mixed   $mRestriction
     * @param  string  $sOperator
     */
    public function deleteWhereGroup($sGroup, $sField, $mRestriction, $sOperator = '=')
    {
        $sField = strtolower($sField);
        if (is_array($this->_where['groups'][$sGroup]) &&
            isset($this->_where['groups'][$sGroup][$sField]) &&
            is_array($this->_where['groups'][$sGroup][$sField])) {
            if ($this->_where['groups'][$sGroup][$sField]['operator'] == $sOperator &&
                $this->_where['groups'][$sGroup][$sField]['restriction'] == $mRestriction) {
                unset($this->_where['groups'][$sGroup][$sField]);
            }
        }
    }

    /**
     * Defines how relations in one group are linked each together
     * @param  string  $sGroup
     * @param  string  $sCondition
     */
    public function setInnerGroupCondition($sGroup, $sCondition = 'AND')
    {
        $this->_innerGroupConditions[$sGroup] = $sCondition;
    }

    /**
     * Defines how groups are linked to each other
     * @param  string  $sGroup1
     * @param  string  $sGroup2
     * @param  string  $sCondition
     */
    public function setGroupCondition($sGroup1, $sGroup2, $sCondition = 'AND')
    {
        $this->_groupConditions[$sGroup1][$sGroup2] = $sCondition;
    }

    /**
     * Builds a where statement out of the setGroupWhere calls
     *
     * @return  array  With all where statements
     */
    protected function _buildGroupWhereStatements()
    {
        $aWheres = array();
        $aGroupWhere = array();

        $mLastGroup = false;
        $sGroupWhereStatement = '';

        // Find out if there are any defined groups
        if (count($this->_where['groups']) > 0) {
            // Step trough all groups
            foreach ($this->_where['groups'] as $groupname => $group) {
                $aWheres = array();

                // Fetch restriction, fields and operators and build single group
                // where statements
                foreach ($group as $field => $item) {
                    $aWheres[] = $this->_driver->buildOperator($field, $item['operator'], $item['restriction']);
                }

                // Add completed substatements
                $sOperator = 'AND';
                if (isset($this->_innerGroupConditions[$groupname])) {
                    $sOperator = $this->_innerGroupConditions[$groupname];
                }

                $aGroupWhere[$groupname] = implode(' '.$sOperator.' ', $aWheres);
            }
        }

        // Combine groups
        foreach ($aGroupWhere as $groupname => $group) {
            if ($mLastGroup != false) {
                $sOperator = 'AND';
                // Check if there's a group condition
                if (isset($this->_groupConditions[$groupname])) {
                    if (isset($this->_groupConditions[$groupname][$mLastGroup])) {
                        $sOperator = $this->_groupConditions[$groupname][$mLastGroup];
                    }
                }

                // Reverse check
                if (isset($this->_groupConditions[$mLastGroup])) {
                    if (isset($this->_groupConditions[$mLastGroup][$groupname])) {
                        $sOperator = $this->_groupConditions[$mLastGroup][$groupname];
                    }
                }

                $sGroupWhereStatement .= ' '.$sOperator.' ('.$group.')';
            } else {
                $sGroupWhereStatement .= '('.$group.')';
            }

            $mLastGroup = $groupname;
        }

        return $sGroupWhereStatement;
    }

    /**
     * Builds a where statement out of the setWhere calls
     *
     * @return  array  With all where statements
     */
    protected function _buildWhereStatements()
    {
        $aWheres = array();

        // Build global where condition
        foreach ($this->_where['global'] as $field => $item) {
            $aWheres[] = $this->_driver->buildOperator($field, $item['operator'], $item['restriction']);
        }

        return (implode(' AND ', $aWheres));
    }

    /**
     * Fetches all tables which will be joined later on.
     *
     * The returned array has the following format:
     * <pre>
     * array(
     *     array(fields),
     *     array(tables),
     *     array(joins),
     *     array(wheres)
     * );
     * </pre>
     *
     * Notes:
     * The table is the table name which needs to be added to the FROM clause
     * The join statement which is inserted after the master table
     * The where statement is combined with all other where statements
     * The fields to select from
     *
     * @todo  Reduce complexity of this function, to much code...
     *
     * @param   ???    $ignoreRoot
     * @return  array  Array structure, see above
     */
    protected function _fetchJoinTables($ignoreRoot)
    {
        $aParameters = array();
        $aFields = array();
        $aTables = array();
        $aJoins = array();
        $aWheres = array();

        // Fetch linked tables
        foreach ($this->_links as $link => $object) {
            $matches = $this->_findReverseJoinPartner(strtolower(get_class($this)), $link);

            if ($matches !== false) {
                if (isset($matches['desttable'])) {
                    // Driver function: Build query parts
                    $aParameters[] = $this->_driver->buildJoinQuery(
                        $matches['desttable'],
                        strtolower($matches['destclass']),
                        $matches['key'],
                        strtolower($matches['sourceclass']),
                        $matches['key']
                    );
                } else {
                    foreach ($matches as $match) {
                        $aParameters[] = $this->_driver->buildJoinQuery(
                            $match['desttable'],
                            strtolower($match['destclass']),
                            $match['key'],
                            strtolower($match['sourceclass']),
                            $match['key']
                        );
                    }
                }
            } else {
                // Try forward search
                $mobject = new $link;

                $matches = $mobject->_findReverseJoinPartner($link, strtolower(get_class($this)));

                if ($matches !== false) {
                    if (isset($matches['desttable'])) {
                        $i = $this->_driver->buildJoinQuery(
                            $mobject->table,
                            strtolower($link),
                            $mobject->primaryKey,
                            strtolower($matches['destclass']),
                            $matches['key']
                        );

                        if ($i['field'] == ($link.'.'.$mobject->primaryKey) && $link == $ignoreRoot) {
                            unset($i['join']);
                        }
                        $aParameters[] = $i;
                    } else {
                        foreach ($matches as $match) {
                            $xobject = new $match['sourceclass'];

                            $i = $this->_driver->buildJoinQuery(
                                $xobject->table,
                                strtolower($match['sourceclass']),
                                $xobject->primaryKey,
                                strtolower($match['destclass']),
                                $match['key']
                            );

                            if ($i['field'] == ($match['sourceclass'] . '.' . $xobject->primaryKey) &&
                                $match['sourceclass'] == $ignoreRoot) {
                                unset($i['join']);
                            }
                            array_unshift($aParameters, $i);
                        }
                    }
                } else {
                    $bDualSearch = true;
                    // Check first if we are a instance of another class
                    foreach ($mobject->_JoinPartners as $sJoinPartner) {
                        if (class_exists($sJoinPartner)) {
                            if (is_subclass_of($this, $sJoinPartner)) {
                                $matches = $mobject->_findReverseJoinPartner($link, strtolower($sJoinPartner));

                                if ($matches !== false) {
                                    if ($matches['destclass'] == strtolower($sJoinPartner)) {
                                        $matches['destclass'] = get_class($this);

                                        if (isset($matches['desttable'])) {
                                            $i = $this->_driver->buildJoinQuery(
                                                $mobject->table,
                                                strtolower($link),
                                                $mobject->primaryKey,
                                                strtolower($matches['destclass']),
                                                $matches['key']
                                            );

                                            if ($i['field'] == ($link . '.' . $mobject->primaryKey) &&
                                                $link == $ignoreRoot) {
                                                unset($i['join']);
                                            }
                                            $aParameters[] = $i;
                                        } else {
                                            foreach ($matches as $match) {
                                                $xobject = new $match['sourceclass'];

                                                $i = $this->_driver->buildJoinQuery(
                                                    $xobject->table,
                                                    strtolower($match['sourceclass']),
                                                    $xobject->primaryKey,
                                                    strtolower($match['destclass']),
                                                    $match['key']
                                                );

                                                if ($i['field'] == ($match['sourceclass'] . '.' . $xobject->primaryKey) &&
                                                    $match['sourceclass'] == $ignoreRoot) {
                                                    unset($i['join']);
                                                }
                                                array_unshift($aParameters, $i);
                                            }
                                        }
                                        $bDualSearch = false;
                                    }
                                }
                            }
                        }
                    }

                    if ($bDualSearch) {
                        // Try dual-side search
                        $forward = $this->_resolveLinks();
                        $reverse = $mobject->_resolveLinks();

                        $result = array_intersect($forward, $reverse);

                        if (count($result) > 0) {
                            // Found an intersection, build references to it
                            foreach ($result as $value) {
                                $oIntersect = new $value;
                                $oIntersect->link(strtolower(get_class($this)));
                                $oIntersect->link(strtolower(get_class($mobject)));

                                $aIntersectParameters = $oIntersect->_fetchJoinTables($ignoreRoot);

                                $aFields = array_merge($aIntersectParameters['fields'], $aFields);
                                $aTables = array_merge($aIntersectParameters['tables'], $aTables);
                                $aJoins = array_merge($aIntersectParameters['joins'], $aJoins);
                                $aWheres = array_merge($aIntersectParameters['wheres'], $aWheres);
                            }
                        } else {
                            $sMsg = "Could not find join partner for class [$link] in class "
                                  . get_class($this)." in neither forward nor reverse direction.";
                            cWarning(__FILE__, __LINE__, $sMsg);
                        }
                    }
                }
            }
        }

        // Add this class
        $aFields[] = strtolower(strtolower(get_class($this))).'.'.$this->primaryKey;

        // Make the parameters unique
        foreach ($aParameters as $parameter) {
            array_unshift($aFields, $parameter['field']);
            array_unshift($aTables, $parameter['table']);
            array_unshift($aJoins, $parameter['join']);
            array_unshift($aWheres, $parameter['where']);
        }

        $aFields = array_filter(array_unique($aFields));
        $aTables = array_filter(array_unique($aTables));
        $aJoins = array_filter(array_unique($aJoins));
        $aWheres = array_filter(array_unique($aWheres));

        return array(
            'fields' => $aFields, 'tables' => $aTables, 'joins' => $aJoins, 'wheres' => $aWheres
        );
    }

    /**
     * Resolves links (class names of joined partners)
     *
     * @return  array
     */
    protected function _resolveLinks()
    {
        $aResolvedLinks = array();
        $aResolvedLinks[] = strtolower(get_class($this));

        foreach ($this->_JoinPartners as $link) {
            $class = new $link;
            $aResolvedLinks = array_merge($class->_resolveLinks(), $aResolvedLinks);
        }
        return $aResolvedLinks;
    }

    /**
     * Resets the properties
     */
    public function resetQuery()
    {
        $this->setLimit(0, 0);
        $this->_JoinPartners = array();
        $this->_forwardJoinPartners = array();
        $this->_links = array();
        $this->_where['global'] = array();
        $this->_where['groups'] = array();
        $this->_groupConditions = array();
        $this->_resultFields = array();
    }

    /**
     * Builds and runs the query
     *
     * @return  bool
     */
    public function query()
    {
        if (!isset($this->_itemClassInstance)) {
            $sMsg = "GenericDB can't use query() if no item class is set via setItemClass";
            cWarning(__FILE__, __LINE__, $sMsg);
            return false;
        }

        $aGroupWhereStatements = $this->_buildGroupWhereStatements();
        $sWhereStatements = $this->_buildWhereStatements();
        $aParameters = $this->_fetchJoinTables(strtolower(get_class($this)));

        $aStatement = array(
            'SELECT',
            implode(', ', (array_merge($aParameters['fields'], $this->_resultFields))),
            'FROM',
            '`' . $this->table . '` AS ' . strtolower(get_class($this))
        );

        if (count($aParameters['tables']) > 0) {
            $aStatement[] = implode(', ', $aParameters['tables']);
        }

        if (count($aParameters['joins']) > 0) {
            $aStatement[] = implode(' ', $aParameters['joins']);
        }

        $aWheres = array();

        if (count($aParameters['wheres']) > 0) {
            $aWheres[] = implode(', ', $aParameters['wheres']);
        }

        if ($aGroupWhereStatements != '') {
            $aWheres[] = $aGroupWhereStatements;
        }

        if ($sWhereStatements != '') {
            $aWheres[] = $sWhereStatements;
        }

        if (count($aWheres) > 0) {
            $aStatement[] = 'WHERE '.implode(' AND ', $aWheres);
        }

        if ($this->_order != '') {
            $aStatement[] = 'ORDER BY '.$this->_order;
        }

        if ($this->_limitStart > 0 || $this->_limitCount > 0) {
            $iRowStart = intval($this->_limitStart);
            $iRowCount = intval($this->_limitCount);
            $aStatement[] = "LIMIT $iRowStart, $iRowCount";
        }

        $sql = implode(' ', $aStatement);

        $result = $this->db->query($sql);
        $this->_lastSQL = $sql;
        // @todo  disable all mode in this method for the moment. It has to be verified,
        //        if enabling will result in negative side effects.
        $this->_bAllMode = false;
        return ($result) ? true : false;
    }

    /**
     * Sets the result order part of the query
     * (e. g. "fieldname", "fieldname DESC", "fieldname DESC, field2name ASC")
     * @param string $order
     */
    public function setOrder($order)
    {
        $this->_order = strtolower($order);
    }

    /**
     * Adds a result field
     * @param  string  $sField
     */
    public function addResultField($sField)
    {
        $sField = strtolower($sField);
        if (!in_array($sField, $this->_resultFields)) {
            $this->_resultFields[] = $sField;
        }
    }

    /**
     * Removes existing result field
     * @param  string  $sField
     */
    public function removeResultField($sField)
    {
        $sField = strtolower($sField);
        $key = array_search($sField, $this->_resultFields);
        if ($key !== false) {
            unset($this->_resultFields[$key]);
        }
    }

    /**
     * Returns reverse join partner.
     *
     * @param  string   $sParentClass
     * @param  string   $sClassName
     * @param  array|bool
     */
    protected function _findReverseJoinPartner($sParentClass, $sClassName)
    {
        // Make the parameters lowercase, as get_class is buggy
        $sClassName   = strtolower($sClassName);
        $sParentClass = strtolower($sParentClass);

        // Check if we found a direct link
        if (in_array($sClassName, $this->_JoinPartners)) {
            $obj = new $sClassName;
            return array(
                'desttable' => $obj->table, 'destclass' => $sClassName,
                'sourceclass' => $sParentClass, 'key' => $obj->primaryKey
            );
        } else {
            // Recurse all items
            foreach ($this->_JoinPartners as $join => $tmpClassname) {
                $obj = new $tmpClassname;
                $status = $obj->_findReverseJoinPartner($tmpClassname, $sClassName);

                if (is_array($status)) {
                    $returns = array();

                    if (!isset($status['desttable'])) {
                        foreach ($status as $subitem) {
                            $returns[] = $subitem;
                        }
                    } else {
                        $returns[] = $status;
                    }

                    $obj = new $tmpClassname;

                    $returns[] = array(
                        'desttable' => $obj->table, 'destclass' => $tmpClassname,
                        'sourceclass' => $sParentClass, 'key' => $obj->primaryKey
                    );
                    return ($returns);
                }
            }
        }

        return false;
    }

    /**
     * Selects all entries from the database. Objects are loaded using their primary key.
     *
     * @param   string  $sWhere    Specifies the where clause.
     * @param   string  $sGroupBy  Specifies the group by clause.
     * @param   string  $sOrderBy  Specifies the order by clause.
     * @param   string  $sLimit    Specifies the limit by clause.
     * @return  bool   True on success, otherwhise false
     */
    public function select($sWhere = '', $sGroupBy = '', $sOrderBy = '', $sLimit = '')
    {
        unset($this->objects);

        if ($sWhere == '') {
            $sWhere = '';
        } else {
            $sWhere = ' WHERE '.$sWhere;
        }

        if ($sGroupBy != '') {
            $sGroupBy = ' GROUP BY '.$sGroupBy;
        }

        if ($sOrderBy != '') {
            $sOrderBy = ' ORDER BY '.$sOrderBy;
        }

        if ($sLimit != '') {
            $sLimit = ' LIMIT '.$sLimit;
        }

        $sFields = ($this->_settings['select_all_mode']) ? '*' : $this->primaryKey;
        $sql = 'SELECT ' . $sFields . ' FROM `' . $this->table . '`' . $sWhere
             . $sGroupBy . $sOrderBy . $sLimit;
        $this->db->query($sql);
        $this->_lastSQL = $sql;
        $this->_bAllMode = $this->_settings['select_all_mode'];

        if ($this->db->num_rows() == 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Selects all entries from the database. Objects are loaded using their primary key.
     *
     * @param   string  $sDistinct  Specifies if distinct will be added to the SQL
     *                              statement ($sDistinct !== '' -> DISTINCT)
     * @param   string  $sFrom      Specifies the additional from clause (e.g.
     *                              'con_news_groups AS groups, con_news_groupmembers AS groupmembers').
     * @param   string  $sWhere     Specifies the where clause.
     * @param   string  $sGroupBy   Specifies the group by clause.
     * @param   string  $sOrderBy   Specifies the order by clause.
     * @param   string  $sLimit     Specifies the limit by clause.
     * @return  bool   True on success, otherwhise false
     * @author HerrB
     */
    public function flexSelect($sDistinct = '', $sFrom = '', $sWhere = '', $sGroupBy = '', $sOrderBy = '', $sLimit = '')
    {
        unset($this->objects);

        if ($sDistinct != '') {
            $sDistinct = 'DISTINCT ';
        }

        if ($sFrom != '') {
            $sFrom = ', '.$sFrom;
        }

        if ($sWhere != '') {
            $sWhere = ' WHERE '.$sWhere;
        }

        if ($sGroupBy != '') {
            $sGroupBy = ' GROUP BY '.$sGroupBy;
        }

        if ($sOrderBy != '') {
            $sOrderBy = ' ORDER BY '.$sOrderBy;
        }

        if ($sLimit != '') {
            $sLimit = ' LIMIT '.$sLimit;
        }

        $sql = 'SELECT ' . $sDistinct . strtolower(get_class($this)) . '.' . $this->primaryKey
             . ' AS ' . $this->primaryKey . ' FROM `' . $this->table . '` AS ' . strtolower(get_class($this))
             . $sFrom . $sWhere . $sGroupBy . $sOrderBy . $sLimit;

        $this->db->query($sql);
        $this->_lastSQL = $sql;
        // @todo  disable all mode in this method
        $this->_bAllMode = false;

        if ($this->db->num_rows() == 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Checks if a specific entry exists.
     *
     * @param   mixed  $mId  The id to check for (could be numeric or string)
     * @return  bool  True if object exists, false if not
     */
    public function exists($mId)
    {
        $oDb = $this->_getSecondDBInstance();
        $sql = "SELECT `%s` FROM %s WHERE %s='%s'";
        $oDb->query($sql, $this->primaryKey, $this->table, $this->primaryKey, $mId);
        return ($oDb->next_record()) ? true : false;
    }

    /**
     * Advances to the next item in the database.
     *
     * @return Item|bool  The next object, or false if no more objects
     */
    public function next()
    {
        if ($this->db->next_record()) {
            if ($this->_bAllMode) {
                $aRs = $this->db->toArray(DB_Contenido::FETCH_BOTH);
                return $this->loadItem($aRs);
            } else {
                return $this->loadItem($this->db->f($this->primaryKey));
            }
        } else {
            return false;
        }
    }

    /**
     * Fetches the resultset related to current loaded primary key as a object
     *
     * @param  Item
     */
    public function fetchObject($sClassName)
    {
        $sKey = strtolower($sClassName);

        if (is_object($this->_collectionCache[$sKey])) {
            $this->_collectionCache[$sKey] = new $sClassName;
        }
        $obj = $this->_collectionCache[$sKey];
        return $obj->loadItem($this->db->f($obj[$sKey]->primaryKey));
    }

    /* Prelimary documentation

       $aFields = array with the fields to fetch. Notes:
       If the array contains keys, the key will be used as alias for the field. Example:
       array('id' => 'idcat') will put 'idcat' into field 'id'

       $aObjects = array with the objects to fetch. Notes:
       If the array contains keys, the key will be used as alias for the object. If you specify
       more than one object with the same key, the array will be multi-dimensional.
    */
    public function fetchTable(array $aFields = array(), array $aObjects = array())
    {
        $row = 1;
        $aTable = array();

        $this->db->seek(0);

        while ($this->db->next_record()) {
            foreach ($aFields as $alias => $field) {
                if ($alias != '') {
                    $aTable[$row][$alias] = $this->db->f($field);
                } else {
                    $aTable[$row][$field] = $this->db->f($field);
                }
            }

            // Fetch objects
            foreach ($aObjects as $alias => $object) {
                if ($alias != '') {
                    if (isset($aTable[$row][$alias])) {
                        // Is set, check for array. If no array, create one
                        if (is_array($aTable[$row][$alias])) {
                            $aTable[$row][$alias][] = $this->fetchObject($object);
                        } else {
                            // $tmpObj = $aTable[$row][$alias];
                            $aTable[$row][$alias] = array();
                            $aTable[$row][$alias][] = $this->fetchObject($object);
                        }
                    } else {
                        $aTable[$row][$alias] = $this->fetchObject($object);
                    }
                } else {
                    $aTable[$row][$object] = $this->fetchObject($object);
                }
            }
            $row ++;
        }

        $this->db->seek(0);

        return $aTable;
    }

    /**
     * Returns an array of arrays
     * @param   array   $aObjects  With the correct order of the objects
     * @return  array   Result
     */
    public function queryAndFetchStructured(array $aObjects)
    {
        $aOrder = array();
        $aFetchObjects = array();
        $aResult = array();

        foreach ($aObjects as $object) {
            $x = new $object;
            $object = strtolower($object);
            $aOrder[] = $object.'.'.$x->primaryKey.' ASC';
            $aFetchObjects[] = $x;
        }

        $this->setOrder(implode(', ', $aOrder));
        $this->query();

        $this->db->seek(0);

        while ($this->db->next_record()) {
            $aResult = $this->_recursiveStructuredFetch($aFetchObjects, $aResult);
        }

        return $aResult;
    }

    protected function _recursiveStructuredFetch(array $aObjects, array $aResult)
    {
        $i = array_shift($aObjects);

        $value = $this->db->f($i->primaryKey);

        if (!is_null($value)) {
            $aResult[$value]['class'] = strtolower(get_class($i));
            $aResult[$value]['object'] = $i->loadItem($value);

            if (count($aObjects) > 0) {
                $aResult[$value]['items'] = $this->_recursiveStructuredFetch($aObjects, $aResult[$value]['items']);
            }
        }

        return $aResult;
    }

    /**
     * Returns the amount of returned items
     * @return  int  Number of rows
     */
    public function count()
    {
        return ($this->db->num_rows());
    }

    /**
     * Loads a single entry by it's id.
     *
     * @param   string|int   $id   The primary key of the item to load.
     * @return  Item  The loaded item
     */
    public function fetchById($id)
    {
        if (is_numeric($id)) {
            $id = (int) $id;
        } elseif (is_string($id)) {
            $id = $this->escape($id);
        }
        return $this->loadItem($id);
    }

    /**
     * Loads a single object from the database.
     *
     * @param   mixed   $mItem  The primary key of the item to load or a recordset
     *                          with itemdata (array) to inject to the item object.
     * @return  Item  The newly created object
     * @throws  Contenido_ItemException  If item class is not set
     */
    public function loadItem($mItem)
    {
        if (empty($this->_itemClass)) {
            $sMsg = "ItemClass has to be set in the constructor of class "
                   . get_class($this) . ")";
            throw new Contenido_ItemException($sMsg);
        }

        if (!is_object($this->_iteratorItem)) {
            $this->_iteratorItem = new $this->_itemClass();
        }

        if (is_array($mItem)) {
            $this->_iteratorItem->loadByRecordSet($mItem);
        } else {
            $this->_iteratorItem->loadByPrimaryKey($mItem);
        }

        return $this->_iteratorItem;
    }

    /**
     * Creates a new item in the table and loads it afterwards.
     *
     * @param  string  $primaryKeyValue  Optional parameter for direct input of primary key value
     * @return  Item  The newly created object
     */
    public function create($primaryKeyValue = null)
    {
        $oDb = $this->_getSecondDBInstance();

        $sql = 'INSERT INTO `%s` (%s) VALUES ("%s")';
        $oDb->query($sql, $this->table, $this->primaryKey, $primaryKeyValue);

        if ($primaryKeyValue === null) {
            $primaryKeyValue = $oDb->getLastInsertedId($this->table);
        }

        return $this->loadItem($primaryKeyValue);
    }

    /**
     * Deletes an item in the table.
     * Deletes also cached e entry and any existing properties.
     *
     * @param   mixed  $mId  Id of entry to delete
     * @return  bool
     */
    public function delete($mId)
    {
        $result = $this->_delete($mId);

        return $result;
    }

    /**
     * Deletes all found items in the table matching the rules in the passed where clause.
     * Deletes also cached e entries and any existing properties.
     *
     * @param   string  $sWhere  The where clause of the SQL statement
     * @return  int  Number of deleted entries
     */
    public function deleteByWhereClause($sWhere)
    {
        $oDb = $this->_getSecondDBInstance();

        $aIds = array();
        $numDeleted = 0;

        // get all ids
        $sql = 'SELECT ' . $this->primaryKey . ' AS pk FROM `' . $this->table . '` WHERE ' . $sWhere;
        $oDb->query($sql);
        while ($oDb->next_record()) {
            $aIds[] = $oDb->f('pk');
        }

        // delete entries by their ids
        foreach ($aIds as $id) {
            if ($this->_delete($id)) {
                $numDeleted++;
            }
        }

        return $numDeleted;
    }

    /**
     * Deletes an item in the table, deletes also existing cache entries and
     * properties of the item.
     *
     * @param   mixed  $mId  Id of entry to delete
     * @return  bool
     */
    protected function _delete($mId)
    {
        $oDb = $this->_getSecondDBInstance();

        // delete db entry
        $sql = "DELETE FROM `%s` WHERE %s = '%s'";
        $oDb->query($sql, $this->table, $this->primaryKey, $mId);

        // delete cache entry
        $this->_oCache->removeItem($mId);

        // delete the property values
        $oProperties = $this->_getPropertiesCollectionInstance();
        $oProperties->deleteProperties($this->primaryKey, $mId);

        return ($oDb->affected_rows() == 0) ? false : true;
    }

    /**
     * Fetches an array of fields from the database.
     *
     * Example:
     * $i = $object->fetchArray('idartlang', array('idlang', 'name'));
     *
     * could result in:
     * $i[5] = array('idlang' => 5, 'name' => 'My Article');
     *
     * Important: If you don't pass an array for fields, the function
     *            doesn't create an array.
     * @param   string  $sKey     Name of the field to use for the key
     * @param   mixed   $mFields  String or array
     * @return  array   Resulting array
     */
    public function fetchArray($sKey, $mFields)
    {
        $aResult = array();

        while ($item = $this->next()) {
            if (is_array($mFields)) {
                foreach ($mFields as $value) {
                    $aResult[$item->get($sKey)][$value] = $item->get($value);
                }
            } else {
                $aResult[$item->get($sKey)] = $item->get($mFields);
            }
        }

        return $aResult;
    }

}

/**
 * Class Item
 * Abstract class for database based items.
 *
 * @author     Timo A. Hummel <Timo.Hummel@4fb.de>
 * @author     Murat Purc <murat@purc.de>
 * @version    0.3
 * @copyright  four for business 2003
 */
abstract class Item extends Contenido_ItemBaseAbstract
{
    /**
     * Storage of the source table to use for the user informations
     * @var  array
     */
    public $values;

    /**
     * Storage of the fields which were modified, where the keys are the
     * fieldnames and the values just simple booleans.
     * @var  array
     */
    protected $modifiedValues;

    /**
     * Stores the old primary key, just in case somebody wants to change it
     * @var  string
     */
    protected $oldPrimaryKey;

    /**
     * List of funcion names of the filters used when data is stored to the db.
     * @var  array
     */
    protected $_arrInFilters = array('urlencode', 'htmlspecialchars', 'addslashes');

    /**
     * List of funcion names of the filtersused when data is retrieved from the db
     * @var  array
     */
    protected $_arrOutFilters = array('stripslashes', 'htmldecode', 'urldecode');

    /**
     * Class name of meta object
     * @var  string
     */
    protected $_metaObject;

    /**
     * Constructor function
     *
     * @param  string  $sTable       The table to use as information source
     * @param  string  $sPrimaryKey  The primary key to use
     * @param  int     $iLifetime
     */
    public function __construct($sTable = '', $sPrimaryKey = '', $iLifetime = 10)
    {
        parent::__construct($sTable, $sPrimaryKey, get_parent_class($this), $iLifetime);
    }

    /**
     * Constructor function for downwards compatibility
     */
    public function Item($sTable, $sPrimaryKey, $iLifetime = 10)
    {
        $this->__construct($sTable, $sPrimaryKey, $iLifetime);
    }

    /**
     * Loads an item by colum/field from the database.
     *
     * @param   string  $sField  Specifies the field
     * @param   mixed   $mValue  Specifies the value
     * @param   bool    $bSafe   Use inFilter or not
     * @return  bool    True if the load was successful
     */
    public function loadBy($sField, $mValue, $bSafe = true)
    {
        if ($bSafe) {
            $mValue = $this->_inFilter($mValue);
        }

        // check, if cache contains a matching entry
        $aRecordSet = null;
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
        $sql = "SELECT * FROM %s WHERE %s = '%s'";

        // Query the database
        $this->db->query($sql, $this->table, $sField, $mValue);

        $this->_lastSQL = $sql;

        if ($this->db->num_rows() > 1) {
            $sMsg = "Tried to load a single line with field $sField and value $mValue from "
                  . $this->table . " but found more than one row";
            cWarning(__FILE__, __LINE__, $sMsg);
        }

        // Advance to the next record, return false if nothing found
        if (!$this->db->next_record()) {
            return false;
        }

        $this->loadByRecordSet($this->db->toArray());
        return true;
    }

    /**
     * Loads an item by ID from the database.
     *
     * @param   string  $mValue  Specifies the primary key value
     * @return  bool    True if the load was successful
     */
    public function loadByPrimaryKey($mValue)
    {
        $bSuccess = $this->loadBy($this->primaryKey, $mValue);

        if (($bSuccess == true) && method_exists($this, '_onLoad')) {
            $this->_onLoad();
        }
        return $bSuccess;
    }

    /**
     * Loads an item by it's recordset.
     *
     * @param   array  $aRecordSet  The recordset of the item
     */
    public function loadByRecordSet(array $aRecordSet)
    {
        $this->values        = $aRecordSet;
        $this->oldPrimaryKey = $this->values[$this->primaryKey];
        $this->virgin        = false;
        $this->_oCache->addItem($this->oldPrimaryKey, $this->values);
    }

    /**
     * Function which is called whenever an item is loaded.
     * Inherited classes should override this function if desired.
     *
     * @return  void
     */
    protected function _onLoad()
    {
    }

    /**
     * Gets the value of a specific field.
     *
     * @param   string  $sField  Specifies the field to retrieve
     * @return  mixed   Value of the field
     */
    public function getField($sField)
    {
        if ($this->virgin == true) {
            $this->lasterror = 'No item loaded';
            return false;
        }
        return $this->_outFilter($this->values[$sField]);
    }

    /**
     * Wrapper for getField (less to type).
     *
     * @param   string  $sField  Specifies the field to retrieve
     * @return  mixed   Value of the field
     */
    public function get($sField)
    {
        return $this->getField($sField);
    }

    /**
     * Sets the value of a specific field.
     *
     * @param  string  $sField  Field name
     * @param  string  $mValue  Value to set
     * @param  bool    $bSafe   Flag to run defined inFilter on passed value
     */
    public function setField($sField, $mValue, $bSafe = true)
    {
        if ($this->virgin == true) {
            $this->lasterror = 'No item loaded';
            return false;
        }

        $this->modifiedValues[$sField] = true;

        if ($sField == $this->primaryKey) {
            $this->oldPrimaryKey = $this->values[$sField];
        }

        if ($bSafe == true) {
            $this->values[$sField] = $this->_inFilter($mValue);
        } else {
            $this->values[$sField] = $mValue;
        }
        return true;
    }

    /**
     * Shortcut to setField.
     *
     * @param  string  $sField  Field name
     * @param  string  $mValue  Value to set
     * @param  bool    $bSafe   Flag to run defined inFilter on passed value
     */
    public function set($sField, $mValue, $bSafe = true)
    {
        return $this->setField($sField, $mValue, $bSafe);
    }

    /**
     * Stores the loaded and modified item to the database.
     *
     * @return  bool
     */
    public function store()
    {
        if ($this->virgin == true) {
            $this->lasterror = 'No item loaded';
            return false;
        }

        $sql = 'UPDATE `' . $this->table . '` SET ';
        $first = true;

        if (!is_array($this->modifiedValues)) {
            return true;
        }

        foreach ($this->modifiedValues as $key => $bValue) {
            if ($first == true) {
                $sql .= "`$key` = '" . $this->values[$key] . "'";
                $first = false;
            } else {
                $sql .= ", `$key` = '" . $this->values[$key] . "'";
            }
        }

        $sql .= " WHERE " . $this->primaryKey . " = '" . $this->oldPrimaryKey . "'";

        $this->db->query($sql);

        $this->_lastSQL = $sql;

        if ($this->db->affected_rows() > 0) {
            $this->_oCache->addItem($this->oldPrimaryKey, $this->values);
        }

        return ($this->db->affected_rows() < 1) ? false : true;
    }

    /**
     * Returns current item data as an assoziative array.
     *
     * @return array|false
     */
    public function toArray()
    {
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
    public function toObject()
    {
        $return = $this->toArray();
        return (false !== $return) ? (object) $return : $return;
    }

    /**
     * Sets a custom property.
     *
     * @param   string  $sType   Specifies the type
     * @param   string  $sName   Specifies the name
     * @param   mixed   $mValue  Specifies the value
     * @return  bool
     */
    public function setProperty($sType, $sName, $mValue)
    {
        // If this object wasn't loaded before, return false
        if ($this->virgin == true) {
            $this->lasterror = 'No item loaded';
            return false;
        }

        // Set the value
        $oProperties = $this->_getPropertiesCollectionInstance();
        $bResult = $oProperties->setValue(
            $this->primaryKey, $this->get($this->primaryKey), $sType, $sName, $mValue
        );
        return $bResult;
    }

    /**
     * Returns a custom property.
     *
     * @param   string  $sType  Specifies the type
     * @param   string  $sName  Specifies the name
     * @return  mixed   Value of the given property or false
     */
    public function getProperty($sType, $sName)
    {
        // If this object wasn't loaded before, return false
        if ($this->virgin == true) {
            $this->lasterror = 'No item loaded';
            return false;
        }

        // Return the value
        $oProperties = $this->_getPropertiesCollectionInstance();
        $mValue = $oProperties->getValue(
            $this->primaryKey, $this->get($this->primaryKey), $sType, $sName
        );
        return $mValue;
    }

   /**
    * Deletes a custom property.
    *
    * @param   string  $sType   Specifies the type
    * @param   string  $sName   Specifies the name
    * @return  bool
    */
    public function deleteProperty($sType, $sName)
    {
        // If this object wasn't loaded before, return false
        if ($this->virgin == true) {
            $this->lasterror = 'No item loaded';
            return false;
        }

        // Delete the value
        $oProperties = $this->_getPropertiesCollectionInstance();
        $bResult = $oProperties->deleteValue(
            $this->primaryKey, $this->get($this->primaryKey), $sType, $sName
        );
        return $bResult;
    }

   /**
    * Deletes a custom property by its id.
    *
    * @param   int  $idprop   Id of property
    * @return  bool
    */
    public function deletePropertyById($idprop)
    {
        $oProperties = $this->_getPropertiesCollectionInstance();
        return $oProperties->delete($idprop);
    }

    /**
     * Deletes the current item
     *
     * @return void
     */
    // Method doesn't work, remove in future versions
    // function delete()
    // {
    //    $this->_collectionInstance->delete($item->get($this->primaryKey));
    //}

    /**
     * Define the filter functions used when data is being stored or retrieved
     * from the database.
     *
     * Examples:
     * <pre>
     * $obj->setFilters(array('addslashes'), array('stripslashes'));
     * $obj->setFilters(array('htmlencode', 'addslashes'), array('stripslashes', 'htmlencode'));
     * </pre>
     *
     * @param array  $aInFilters   Array with function names
     * @param array  $aOutFilters  Array with function names
     *
     * @return void
     */
    public function setFilters($aInFilters = array(), $aOutFilters = array())
    {
        $this->_arrInFilters  = $aInFilters;
        $this->_arrOutFilters = $aOutFilters;
    }

    /**
     * Filters the passed data using the functions defines in the _arrInFilters array.
     *
     * @see setFilters
     *
     * @todo  This method is used from public scope, but it should be protected
     *
     * @param   mixed  $mData  Data to filter
     * @return  mixed  Filtered data
     */
    public function _inFilter($mData)
    {
        foreach ($this->_arrInFilters as $_function) {
            if (function_exists($_function)) {
                $mData = $_function($mData);
            }
        }
        return $mData;
    }

    /**
     * Filters the passed data using the functions defines in the _arrOutFilters array.
     *
     * @see setFilters
     *
     * @param   mixed  $mData  Data to filter
     * @return  mixed  Filtered data
     */
    protected function _outFilter($mData)
    {
        foreach ($this->_arrOutFilters as $_function) {
            if (function_exists($_function)) {
                $mData = $_function($mData);
            }
        }
        return $mData;
    }

    protected function _setMetaObject($sObjectName)
    {
        $this->_metaObject = $sObjectName;
    }

    public function getMetaObject()
    {
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
            $_metaObjectCache[$qclassname] = new $sClassName ($this);
            return $_metaObjectCache[$qclassname];
        }
    }

}

?>