<?php
/**
 * This file contains the generic db item collection class.
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
 * Class ItemCollection
 * Abstract class for database based item collections.
 *
 * @package Core
 * @subpackage GenericDB
 */
abstract class ItemCollection extends cItemBaseAbstract {

    /**
     * Storage of all result items.
     * Contains all result items.
     *
     * @var string
     */
    protected $objects;

    /**
     * GenericDB driver object
     *
     * @var cGenericDbDriver
     */
    protected $_driver;

    /**
     * List of instances of ItemCollection implementations
     *
     * @var array
     */
    protected $_collectionCache = array();

    /**
     * Single item class
     *
     * @var string
     */
    protected $_itemClass;

    /**
     * Iterator object for the next() method
     *
     * @var object
     */
    protected $_iteratorItem;

    /**
     * Reverse join partners for this data object
     *
     * @var array
     */
    protected $_JoinPartners = array();

    /**
     * Forward join partners for this data object
     *
     * @var array
     */
    protected $_forwardJoinPartners;

    /**
     * Where restrictions for the query
     *
     * @var array
     */
    protected $_whereRestriction;

    /**
     * Inner group conditions
     *
     * @var array
     */
    protected $_innerGroupConditions = array();

    /**
     * Group conditions
     *
     * @var array
     */
    protected $_groupConditions;

    /**
     * Result fields for the query
     *
     * @var array
     */
    protected $_resultFields = array();

    /**
     * Encoding
     *
     * @var string
     */
    protected $_encoding;

    /**
     * Item class instance
     *
     * @var object
     */
    protected $_itemClassInstance;

    /**
     * Stores all operators which are supported by GenericDB
     * Unsupported operators are passed trough as-is.
     *
     * @var array
     */
    protected $_aOperators;

    /**
     * Flag to select all fields in a query.
     * Reduces the number of queries send to the database.
     *
     * @var bool
     */
    protected $_bAllMode = false;

    /**
     * Array with where conditions
     *
     * @var array
     */
    protected $_where;

    /**
     * Order mode with direction
     *
     * @var string
     */
    protected $_order;

    /**
     * Starting limit
     *
     * @var int
     */
    protected $_limitStart;

    /**
     * Amount of items for limit
     *
     * @var int
     */
    protected $_limitCount;

    /**
     * Last SQL statement
     *
     * @var string
     */
    protected $_lastSQL;

    /**
     * Array with linked tables
     *
     * @var array
     */
    protected $_links;

    /**
     * Constructor Function
     *
     * @param string $sTable
     *         The table to use as information source
     * @param string $sPrimaryKey
     *         The primary key to use
     */
    public function __construct($sTable, $sPrimaryKey) {
        parent::__construct($sTable, $sPrimaryKey, get_parent_class($this));

        $this->resetQuery();

        // Try to load driver
        $this->_initializeDriver();

        // Try to find out the current encoding
        if (isset($GLOBALS['lang']) && isset($GLOBALS['aLanguageEncodings'])) {
            $this->setEncoding($GLOBALS['aLanguageEncodings'][$GLOBALS['lang']]);
        }

        $this->_aOperators = array(
            '=',
            '!=',
            '<>',
            '<',
            '>',
            '<=',
            '>=',
            'LIKE',
            'DIACRITICS'
        );
    }

    /**
     * Defines the reverse links for this table.
     *
     * Important: The class specified by $sForeignCollectionClass needs to be a
     * collection class and has to exist.
     * Define all links in the constructor of your object.
     *
     * @param string $sForeignCollectionClass
     *         Specifies the foreign class to use
     * @throws cInvalidArgumentException
     *         if the given foreign class can not be instantiated
     */
    protected function _setJoinPartner($sForeignCollectionClass) {
        if (class_exists($sForeignCollectionClass)) {
            // Add class
            if (!in_array($sForeignCollectionClass, $this->_JoinPartners)) {
                $this->_JoinPartners[] = strtolower($sForeignCollectionClass);
            }
        } else {
            $msg = "Could not instantiate class [$sForeignCollectionClass] for use " . "with _setJoinPartner in class " . get_class($this);
            throw new cInvalidArgumentException($msg);
        }
    }

    /**
     * Method to set the accompanying item object.
     *
     * @param string $sClassName
     *         Specifies the classname of item
     * @throws cInvalidArgumentException
     *         if the given class can not be instantiated
     */
    protected function _setItemClass($sClassName) {
        if (class_exists($sClassName)) {
            $this->_itemClass = $sClassName;
            $this->_itemClassInstance = new $sClassName();

            // Initialize driver in case the developer does a setItemClass-Call
            // before calling the parent constructor
            $this->_initializeDriver();
            $this->_driver->setItemClassInstance($this->_itemClassInstance);
        } else {
            $msg = "Could not instantiate class [$sClassName] for use with " . "_setItemClass in class " . get_class($this);
            throw new cInvalidArgumentException($msg);
        }
    }

    /**
     * Initializes the driver to use with GenericDB.
     *
     * @param boolean $bForceInit
     *         If true, forces the driver to initialize, even if it already exists.
     */
    protected function _initializeDriver($bForceInit = false) {
        if (!is_object($this->_driver) || $bForceInit == true) {
            $this->_driver = new cGenericDbDriverMysql();
        }
    }

    /**
     * Sets the encoding.
     *
     * @param string $sEncoding
     */
    public function setEncoding($sEncoding) {
        $this->_encoding = $sEncoding;
        $this->_driver->setEncoding($sEncoding);
    }

    /**
     * Sets the query to use foreign tables in the resultset
     *
     * @param string $sForeignClass
     *         The class of foreign table to use
     * @throws cInvalidArgumentException
     *         if the given foreign class does not exist
     */
    public function link($sForeignClass) {
        if (class_exists($sForeignClass)) {
            $this->_links[$sForeignClass] = new $sForeignClass();
        } else {
            $msg = "Could not find class [$sForeignClass] for use with link in class " . get_class($this);
            throw new cInvalidArgumentException($msg);
        }
    }

    /**
     * Sets the limit for results
     *
     * @param int $iRowStart
     * @param int $iRowCount
     */
    public function setLimit($iRowStart, $iRowCount) {
        $this->_limitStart = $iRowStart;
        $this->_limitCount = $iRowCount;
    }

    /**
     * Restricts a query with a where clause
     *
     * @param string $sField
     * @param mixed $mRestriction
     * @param string $sOperator
     */
    public function setWhere($sField, $mRestriction, $sOperator = '=') {
        $sField = strtolower($sField);
        $this->_where['global'][$sField]['operator'] = $sOperator;
        $this->_where['global'][$sField]['restriction'] = $mRestriction;
    }

    /**
     * Removes a previous set where clause (@see ItemCollection::setWhere).
     *
     * @param string $sField
     * @param mixed $mRestriction
     * @param string $sOperator
     */
    public function deleteWhere($sField, $mRestriction, $sOperator = '=') {
        $sField = strtolower($sField);
        if (isset($this->_where['global'][$sField]) && is_array($this->_where['global'][$sField])) {
            if ($this->_where['global'][$sField]['operator'] == $sOperator && $this->_where['global'][$sField]['restriction'] == $mRestriction) {
                unset($this->_where['global'][$sField]);
            }
        }
    }

    /**
     * Restricts a query with a where clause, groupable
     *
     * @param string $sGroup
     * @param string $sField
     * @param mixed $mRestriction
     * @param string $sOperator
     */
    public function setWhereGroup($sGroup, $sField, $mRestriction, $sOperator = '=') {
        $sField = strtolower($sField);
        $this->_where['groups'][$sGroup][$sField]['operator'] = $sOperator;
        $this->_where['groups'][$sGroup][$sField]['restriction'] = $mRestriction;
    }

    /**
     * Removes a previous set groupable where clause (@see
     * ItemCollection::setWhereGroup).
     *
     * @param string $sGroup
     * @param string $sField
     * @param mixed $mRestriction
     * @param string $sOperator
     */
    public function deleteWhereGroup($sGroup, $sField, $mRestriction, $sOperator = '=') {
        $sField = strtolower($sField);
        if (is_array($this->_where['groups'][$sGroup]) && isset($this->_where['groups'][$sGroup][$sField]) && is_array($this->_where['groups'][$sGroup][$sField])) {
            if ($this->_where['groups'][$sGroup][$sField]['operator'] == $sOperator && $this->_where['groups'][$sGroup][$sField]['restriction'] == $mRestriction) {
                unset($this->_where['groups'][$sGroup][$sField]);
            }
        }
    }

    /**
     * Defines how relations in one group are linked each together
     *
     * @param string $sGroup
     * @param string $sCondition
     */
    public function setInnerGroupCondition($sGroup, $sCondition = 'AND') {
        $this->_innerGroupConditions[$sGroup] = $sCondition;
    }

    /**
     * Defines how groups are linked to each other
     *
     * @param string $sGroup1
     * @param string $sGroup2
     * @param string $sCondition
     */
    public function setGroupCondition($sGroup1, $sGroup2, $sCondition = 'AND') {
        $this->_groupConditions[$sGroup1][$sGroup2] = $sCondition;
    }

    /**
     * Builds a where statement out of the setGroupWhere calls
     *
     * @return array
     *         With all where statements
     */
    protected function _buildGroupWhereStatements() {
        $aWheres = array();
        $aGroupWhere = array();

        $mLastGroup = false;
        $sGroupWhereStatement = '';

        // Find out if there are any defined groups
        if (count($this->_where['groups']) > 0) {
            // Step trough all groups
            foreach ($this->_where['groups'] as $groupname => $group) {
                $aWheres = array();

                // Fetch restriction, fields and operators and build single
                // group
                // where statements
                foreach ($group as $field => $item) {
                    $aWheres[] = $this->_driver->buildOperator($field, $item['operator'], $item['restriction']);
                }

                // Add completed substatements
                $sOperator = 'AND';
                if (isset($this->_innerGroupConditions[$groupname])) {
                    $sOperator = $this->_innerGroupConditions[$groupname];
                }

                $aGroupWhere[$groupname] = implode(' ' . $sOperator . ' ', $aWheres);
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

                $sGroupWhereStatement .= ' ' . $sOperator . ' (' . $group . ')';
            } else {
                $sGroupWhereStatement .= '(' . $group . ')';
            }

            $mLastGroup = $groupname;
        }

        return $sGroupWhereStatement;
    }

    /**
     * Builds a where statement out of the setWhere calls
     *
     * @return array
     *         With all where statements
     */
    protected function _buildWhereStatements() {
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
     * array(fields),
     * array(tables),
     * array(joins),
     * array(wheres)
     * );
     * </pre>
     *
     * Notes:
     * The table is the table name which needs to be added to the FROM clause
     * The join statement which is inserted after the master table
     * The where statement is combined with all other where statements
     * The fields to select from
     *
     * @throws cException
     *         if no join partner could be found
     * @return array
     *         Array structure, see above
     */
    protected function _fetchJoinTables() {
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
                    $aParameters[] = $this->_driver->buildJoinQuery($matches['desttable'], strtolower($matches['destclass']), $matches['key'], strtolower($matches['sourceclass']), $matches['key']);
                } else {
                    foreach ($matches as $match) {
                        $aParameters[] = $this->_driver->buildJoinQuery($match['desttable'], strtolower($match['destclass']), $match['key'], strtolower($match['sourceclass']), $match['key']);
                    }
                }
            } else {
                throw new cUnexpectedValueException("The join partner '" . get_class($this) . "' is not registered and can not be used with link().");
            }
        }

        // Add this class
        $aFields[] = strtolower(strtolower(get_class($this))) . '.' . $this->primaryKey;

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
            'fields' => $aFields,
            'tables' => $aTables,
            'joins' => $aJoins,
            'wheres' => $aWheres
        );
    }

    /**
     * Resolves links (class names of joined partners)
     *
     * @return array
     */
    protected function _resolveLinks() {
        $aResolvedLinks = array();
        $aResolvedLinks[] = strtolower(get_class($this));

        foreach ($this->_JoinPartners as $link) {
            $class = new $link();
            $aResolvedLinks = array_merge($class->_resolveLinks(), $aResolvedLinks);
        }
        return $aResolvedLinks;
    }

    /**
     * Resets the properties
     */
    public function resetQuery() {
        $this->setLimit(0, 0);
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
     * @throws cException
     *         if no item class has been set
     * @return bool
     */
    public function query() {
        if (!isset($this->_itemClassInstance)) {
            throw new cException('GenericDB can\'t use query() if no item class is set via setItemClass');
        }

        $aGroupWhereStatements = $this->_buildGroupWhereStatements();
        $sWhereStatements = $this->_buildWhereStatements();
        $aParameters = $this->_fetchJoinTables();

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
            $aStatement[] = 'WHERE ' . implode(' AND ', $aWheres);
        }

        if ($this->_order != '') {
            $aStatement[] = 'ORDER BY ' . $this->_order;
        }

        if ($this->_limitStart > 0 || $this->_limitCount > 0) {
            $iRowStart = intval($this->_limitStart);
            $iRowCount = intval($this->_limitCount);
            $aStatement[] = "LIMIT $iRowStart, $iRowCount";
        }

        $sql = implode(' ', $aStatement);

        $result = $this->db->query($sql);
        $this->_lastSQL = $sql;
        // @todo disable all mode in this method for the moment. It has to be
        // verified,
        // if enabling will result in negative side effects.
        $this->_bAllMode = false;
        return ($result) ? true : false;
    }

    /**
     * Sets the result order part of the query
     * (e.
     * g. "fieldname", "fieldname DESC", "fieldname DESC, field2name ASC")
     *
     * @param string $order
     */
    public function setOrder($order) {
        $this->_order = strtolower($order);
    }

    /**
     * Adds a result field
     *
     * @param string $sField
     */
    public function addResultField($sField) {
        $sField = strtolower($sField);
        if (!in_array($sField, $this->_resultFields)) {
            $this->_resultFields[] = $sField;
        }
    }

    /**
     * Removes existing result field
     *
     * @param string $sField
     */
    public function removeResultField($sField) {
        $sField = strtolower($sField);
        $key = array_search($sField, $this->_resultFields);
        if ($key !== false) {
            unset($this->_resultFields[$key]);
        }
    }

    /**
     * Returns reverse join partner.
     *
     * @param string $sParentClass
     * @param string $sClassName
     * @return array|bool
     */
    protected function _findReverseJoinPartner($sParentClass, $sClassName) {
        // Make the parameters lowercase, as get_class is buggy
        $sClassName = strtolower($sClassName);
        $sParentClass = strtolower($sParentClass);

        // Check if we found a direct link
        if (in_array($sClassName, $this->_JoinPartners)) {
            $obj = new $sClassName();
            return array(
                'desttable' => $obj->table,
                'destclass' => $sClassName,
                'sourceclass' => $sParentClass,
                'key' => $obj->primaryKey
            );
        } else {
            // Recurse all items
            foreach ($this->_JoinPartners as $join => $tmpClassname) {
                $obj = new $tmpClassname();
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

                    $returns[] = array(
                        'desttable' => $obj->table,
                        'destclass' => $tmpClassname,
                        'sourceclass' => $sParentClass,
                        'key' => $obj->primaryKey
                    );
                    return ($returns);
                }
            }
        }
        return false;
    }

    /**
     * Selects all entries from the database.
     * Objects are loaded using their primary key.
     *
     * @param string $sWhere
     *         Specifies the where clause.
     * @param string $sGroupBy
     *         Specifies the group by clause.
     * @param string $sOrderBy
     *         Specifies the order by clause.
     * @param string $sLimit
     *         Specifies the limit by clause.
     * @return bool
     *         True on success, otherwhise false
     */
    public function select($sWhere = '', $sGroupBy = '', $sOrderBy = '', $sLimit = '') {
        unset($this->objects);

        if ($sWhere == '') {
            $sWhere = '';
        } else {
            $sWhere = ' WHERE ' . $sWhere;
        }

        if ($sGroupBy != '') {
            $sGroupBy = ' GROUP BY ' . $sGroupBy;
        }

        if ($sOrderBy != '') {
            $sOrderBy = ' ORDER BY ' . $sOrderBy;
        }

        if ($sLimit != '') {
            $sLimit = ' LIMIT ' . $sLimit;
        }

        $sFields = ($this->_settings['select_all_mode']) ? '*' : $this->primaryKey;
        $sql = 'SELECT ' . $sFields . ' FROM `' . $this->table . '`' . $sWhere . $sGroupBy . $sOrderBy . $sLimit;
        $this->db->query($sql);
        $this->_lastSQL = $sql;
        $this->_bAllMode = $this->_settings['select_all_mode'];

        if ($this->db->numRows() == 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Selects all entries from the database.
     * Objects are loaded using their primary key.
     *
     * @param string $sDistinct
     *         Specifies if distinct will be added to the SQL statement
     *         ($sDistinct !== '' -> DISTINCT)
     * @param string $sFrom
     *         Specifies the additional from clause (e.g.
     *         'con_news_groups AS groups, con_news_groupmembers AS groupmembers').
     * @param string $sWhere
     *         Specifies the where clause.
     * @param string $sGroupBy
     *         Specifies the group by clause.
     * @param string $sOrderBy
     *         Specifies the order by clause.
     * @param string $sLimit
     *         Specifies the limit by clause.
     * @return bool
     *         True on success, otherwhise false
     */
    public function flexSelect($sDistinct = '', $sFrom = '', $sWhere = '', $sGroupBy = '', $sOrderBy = '', $sLimit = '') {
        unset($this->objects);

        if ($sDistinct != '') {
            $sDistinct = 'DISTINCT ';
        }

        if ($sFrom != '') {
            $sFrom = ', ' . $sFrom;
        }

        if ($sWhere != '') {
            $sWhere = ' WHERE ' . $sWhere;
        }

        if ($sGroupBy != '') {
            $sGroupBy = ' GROUP BY ' . $sGroupBy;
        }

        if ($sOrderBy != '') {
            $sOrderBy = ' ORDER BY ' . $sOrderBy;
        }

        if ($sLimit != '') {
            $sLimit = ' LIMIT ' . $sLimit;
        }

        $sql = 'SELECT ' . $sDistinct . strtolower(get_class($this)) . '.' . $this->primaryKey . ' AS ' . $this->primaryKey . ' FROM `' . $this->table . '` AS ' . strtolower(get_class($this)) . $sFrom . $sWhere . $sGroupBy . $sOrderBy . $sLimit;

        $this->db->query($sql);
        $this->_lastSQL = $sql;
        // @todo disable all mode in this method
        $this->_bAllMode = false;

        if ($this->db->numRows() == 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Checks if a specific entry exists.
     *
     * @param mixed $mId
     *         The id to check for (could be numeric or string)
     * @return bool
     *         True if object exists, false if not
     */
    public function exists($mId) {
        $oDb = $this->_getSecondDBInstance();
        $sql = "SELECT `%s` FROM %s WHERE %s='%s'";
        $oDb->query($sql, $this->primaryKey, $this->table, $this->primaryKey, $mId);
        return ($oDb->nextRecord()) ? true : false;
    }

    /**
     * Advances to the next item in the database.
     *
     * @return Item|bool
     *         next object, or false if no more objects
     */
    public function next() {
        $ret = false;
        while ($this->db->nextRecord()) {
            if ($this->_bAllMode) {
                $aRs = $this->db->toArray(cDb::FETCH_BOTH);
                $ret = $this->loadItem($aRs);
            } else {
                $ret = $this->loadItem($this->db->f($this->primaryKey));
            }

            if ($ret->get($this->primaryKey) == "") {
                continue;
            } else {
                break;
            }
        }
        return $ret;
    }

    /**
     * Fetches the resultset related to current loaded primary key as an object.
     *
     * @param string $sClassName
     * @return Item
     */
    public function fetchObject($sClassName) {
        $sKey = strtolower($sClassName);

        if (!is_object($this->_collectionCache[$sKey])) {
            $this->_collectionCache[$sKey] = new $sClassName();
        }
        /* @var $obj ItemCollection */
        $obj = $this->_collectionCache[$sKey];
        return $obj->loadItem($this->db->f($obj->primaryKey));
    }

    /**
     * Notes:
     * If the array contains keys, the key will be used as alias for the field.
     * Example: array('id' => 'idcat') will put 'idcat' into field 'id'
     * $aObjects = array with the objects to fetch. Notes: If the array contains
     * keys, the key will be used as alias for the object. If you specify more
     * than one object with the same key, the array will be multi-dimensional.
     *
     * @param array $aFields
     *         array with the fields to fetch
     * @param array $aObjects
     * @return array
     */
    public function fetchTable(array $aFields = array(), array $aObjects = array()) {
        $row = 1;
        $aTable = array();

        if ($this->count() > 0) {

            $this->db->seek(0);

            while ($this->db->nextRecord()) {
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
                $row++;
            }

            $this->db->seek(0);

        }

        return $aTable;
    }

    /**
     * Returns an array of arrays
     *
     * @param array $aObjects
     *         With the correct order of the objects
     * @return array Result
     */
    public function queryAndFetchStructured(array $aObjects) {
        $aOrder = array();
        $aFetchObjects = array();
        $aResult = array();

        foreach ($aObjects as $object) {
            $x = new $object();
            $object = strtolower($object);
            $aOrder[] = $object . '.' . $x->primaryKey . ' ASC';
            $aFetchObjects[] = $x;
        }

        $this->setOrder(implode(', ', $aOrder));
        $this->query();

        $this->db->seek(0);

        while ($this->db->nextRecord()) {
            $aResult = $this->_recursiveStructuredFetch($aFetchObjects, $aResult);
        }

        return $aResult;
    }

    /**
     *
     * @param array $aObjects
     * @param array $aResult
     * @return array
     */
    protected function _recursiveStructuredFetch(array $aObjects, array $aResult) {
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
     *
     * @return int
     *         Number of rows
     */
    public function count() {
        return ($this->db->numRows());
    }

    /**
     * Loads a single entry by it's id.
     *
     * @param string|int $id
     *         The primary key of the item to load.
     * @return Item
     *         The loaded item
     */
    public function fetchById($id) {
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
     * @param mixed $mItem
     *         The primary key of the item to load or a recordset with itemdata
     *         (array) to inject to the item object.
     * @throws cException
     *         If item class is not set
     * @return Item
     *         The newly created object
     */
    public function loadItem($mItem) {
        if (empty($this->_itemClass)) {
            $sMsg = "ItemClass has to be set in the constructor of class " . get_class($this) . ")";
            throw new cException($sMsg);
        }

        if (!is_object($this->_iteratorItem)) {
            $this->_iteratorItem = new $this->_itemClass();
        }
        $obj = clone $this->_iteratorItem;

        if (is_array($mItem)) {
            $obj->loadByRecordSet($mItem);
        } else {
            $obj->loadByPrimaryKey($mItem);
        }

        return $obj;
    }

    /**
     * Creates a new item in the table and loads it afterwards.
     *
     * @param string|array $data
     *         optional parameter for direct input of primary key value
     *         (string) or multiple column name - value pairs
     * @return Item
     *         The newly created object
     */
    public function createNewItem($data = NULL) {
        $this->_executeCallbacks(self::CREATE_BEFORE, get_class($this), array());

        $db = $this->_getSecondDBInstance();

        $primaryKeyValue = NULL;
        // prepare the primary key value and the data depending on the type of
        // $data
        if (is_array($data)) {
            if (array_key_exists($this->primaryKey, $data)) {
                $primaryKeyValue = $data[$this->primaryKey];
            }
        } else {
            // data is the primary key
            $primaryKeyValue = $data;
            $data = array(
                $this->primaryKey => $data
            );
        }

        // build the insert statement and execute it
        $sql = $db->buildInsert($this->table, $data);
        $db->query($sql);

        if ($primaryKeyValue === NULL) {
            $primaryKeyValue = $db->getLastInsertedId();
        }

        if ($db->affectedRows() == 0) {
            $this->_executeCallbacks(self::CREATE_FAILURE, $this->_itemClass, array());
        } else {
            $this->_executeCallbacks(self::CREATE_SUCCESS, $this->_itemClass, array(
                $primaryKeyValue
            ));
        }

        return $this->loadItem($primaryKeyValue);
    }

    /**
     * Inserts a new item entry by using a existing item entry.
     *
     * @param object $srcItem
     *         Source Item instance to copy
     * @param array $fieldsToOverwrite
     *         Assoziative list of fields to overwrite.
     * @throws cInvalidArgumentException
     *         If Item class doesn't match the defined _itemClass property
     *         or passed Item instance has no loaded recordset
     * @return Item|NULL
     */
    public function copyItem($srcItem, array $fieldsToOverwrite = array()) {
        if (get_class($srcItem) !== $this->_itemClass) {
            throw new cInvalidArgumentException("Item class doesn't match");
        } elseif (!$srcItem->isLoaded()) {
            throw new cInvalidArgumentException("Item instance has no loaded recordset");
        }

        $destItem = self::createNewItem();
        if (!is_object($destItem)) {
            return NULL;
        }

        $rs = $srcItem->toArray();

        foreach ($rs as $field => $value) {
            if (is_numeric($field)) {
                // Skip index based field
                continue;
            } elseif ($field == $this->primaryKey) {
                // Skip primary key
                continue;
            }

            if (isset($fieldsToOverwrite[$field])) {
                $value = $fieldsToOverwrite[$field];
            }

            $destItem->set($field, $value);
        }

        $destItem->store();
        return $destItem;
    }

    /**
     * Returns all ids of recordsets in the table matching the rules in the
     * passed where clause.
     *
     * @param string $sWhere
     *         The where clause of the SQL statement
     * @return array
     *         List of ids
     */
    public function getIdsByWhereClause($sWhere) {
        $oDb = $this->_getSecondDBInstance();

        $aIds = array();

        // Get all ids
        $sql = 'SELECT ' . $this->primaryKey . ' AS pk FROM `' . $this->table . '` WHERE ' . $sWhere;
        $oDb->query($sql);
        while ($oDb->nextRecord()) {
            $aIds[] = $oDb->f('pk');
        }

        return $aIds;
    }

    /**
     * Returns all specified fields of recordsets in the table matching the
     * rules in the passed where clause.
     *
     * @param array $aFields
     *         List of fields to get
     * @param string $sWhere
     *         The where clause of the SQL statement
     * @return array
     *         List of entries with specified fields
     */
    public function getFieldsByWhereClause(array $aFields, $sWhere) {
        $oDb = $this->_getSecondDBInstance();

        $aEntries = array();

        if (count($aFields) == 0) {
            return $aEntries;
        }

        // Delete multiple db entries at once
        $aEscapedFields = array_map(array(
            $oDb,
            'escape'
        ), $aFields);

        $fields = implode(', ', $aEscapedFields);

        // Get all fields
        $sql = 'SELECT ' . $fields . ' FROM `' . $this->table . '` WHERE ' . $sWhere;
        $oDb->query($sql);
        while ($oDb->nextRecord()) {
            $data = array();
            foreach ($aFields as $field) {
                $data[$field] = $oDb->f($field);
            }
            $aEntries[] = $data;
        }

        return $aEntries;
    }

    /**
     * Returns all ids of recordsets in the table.
     *
     * @return array
     *         List of ids
     */
    public function getAllIds() {
        $oDb = $this->_getSecondDBInstance();

        $aIds = array();

        // Get all ids
        $sql = 'SELECT ' . $this->primaryKey . ' AS pk FROM `' . $this->table . '`';
        $oDb->query($sql);
        while ($oDb->nextRecord()) {
            $aIds[] = $oDb->f('pk');
        }

        return $aIds;
    }

    /**
     * Deletes an item in the table.
     * Deletes also cached e entry and any existing properties.
     *
     * @param mixed $mId
     *         Id of entry to delete
     * @return bool
     */
    public function delete($mId) {
        $result = $this->_delete($mId);
        return $result;
    }

    /**
     * Deletes all found items in the table matching the rules in the passed
     * where clause.
     * Deletes also cached e entries and any existing properties.
     *
     * @param string $sWhere
     *         The where clause of the SQL statement
     * @return int
     *         Number of deleted entries
     */
    public function deleteByWhereClause($sWhere) {
        // Get all ids and delete related entries
        $aIds = $this->getIdsByWhereClause($sWhere);

        if (!is_array($aIds) || 0 >= count($aIds)) {
            return 0;
        }

        $numDeleted = $this->_deleteMultiple($aIds);
        return $numDeleted;
    }

    /**
     * Deletes all found items in the table matching the passed field and it's
     * value.
     * Deletes also cached e entries and any existing properties.
     *
     * @param string $sField
     *         The field name
     * @param mixed $mValue
     *         The value of the field
     * @return int
     *         Number of deleted entries
     */
    public function deleteBy($sField, $mValue) {
        $where = (is_string($mValue)) ? "`%s` = '%s'" : "`%s` = %d";
        $where = $this->db->prepare($where, $sField, $mValue);

        return $this->deleteByWhereClause($where);
    }

    // TODO
    // /**
    //  * Deletes all found items in the table matching the passed field and it's value.
    //  * Deletes also cached e entries and any existing properties.
    //  *
    //  * @param mixed $mValue
    //  *         The value of the field
    //  * @return bool
    //  */
    // public function deleteByMany($values) {
    // }

    /**
     * Deletes an item in the table, deletes also existing cache entries and
     * properties of the item.
     *
     * @param mixed $mId
     *         Id of entry to delete
     * @return bool
     */
    protected function _delete($mId) {
        $this->_executeCallbacks(self::DELETE_BEFORE, $this->_itemClass, array(
            $mId
        ));

        $oDb = $this->_getSecondDBInstance();

        // delete db entry
        $sql = "DELETE FROM `%s` WHERE %s = '%s'";
        $oDb->query($sql, $this->table, $this->primaryKey, $mId);
        $success = $oDb->affectedRows();

        // delete cache entry
        $this->_oCache->removeItem($mId);

        // delete the property values
        $oProperties = $this->_getPropertiesCollectionInstance();
        $oProperties->deleteProperties($this->primaryKey, $mId);

        if ($success == 0) {
            $this->_executeCallbacks(self::DELETE_FAILURE, $this->_itemClass, array(
                $mId
            ));
            return false;
        } else {
            $this->_executeCallbacks(self::DELETE_SUCCESS, $this->_itemClass, array(
                $mId
            ));
            return true;
        }
    }

    /**
     * Deletes all items in the table, deletes also existing cache entries and
     * properties of the item.
     *
     * @param array $aIds
     *         Id of entries to delete (has to be called w/ an array!)
     * @return int
     *         Number of affected records
     */
    protected function _deleteMultiple(array $aIds) {
        foreach ($aIds as $mId) {
            $this->_executeCallbacks(self::DELETE_BEFORE, $this->_itemClass, array(
                $mId
            ));
        }

        $oDb = $this->_getSecondDBInstance();

        // Delete multiple db entries at once
        $aEscapedIds = array_map(array(
            $oDb,
            'escape'
        ), $aIds);
        $in = "'" . implode("', '", $aEscapedIds) . "'";
        $sql = "DELETE FROM `%s` WHERE %s IN (" . $in . ")";
        $oDb->query($sql, $this->table, $this->primaryKey);
        $numAffected = $oDb->affectedRows();

        // Delete cache entries
        $this->_oCache->removeItems($aIds);

        // Delete the property values
        $oProperties = $this->_getPropertiesCollectionInstance();
        $oProperties->deletePropertiesMultiple($this->primaryKey, $aIds);

        // NOTE: Deleteing multiple entries at once has a drawback. There is no
        // way to detect faulty ids, if one or more entries couldn't deleted.
        if ($numAffected == 0) {
            foreach ($aIds as $mId) {
                $this->_executeCallbacks(self::DELETE_FAILURE, $this->_itemClass, array(
                    $mId
                ));
            }
        } else {
            foreach ($aIds as $mId) {
                $this->_executeCallbacks(self::DELETE_SUCCESS, $this->_itemClass, array(
                    $mId
                ));
            }
        }
        return $numAffected;
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
     * doesn't create an array.
     *
     * @param string $sKey
     *         Name of the field to use for the key
     * @param mixed $mFields
     *         String or array
     * @return array
     *         Resulting array
     */
    public function fetchArray($sKey, $mFields) {
        $aResult = array();

        while (($item = $this->next()) !== false) {
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
