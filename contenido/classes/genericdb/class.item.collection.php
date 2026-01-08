<?php

/**
 * This file contains the generic db item collection class.
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
 * Class ItemCollection
 * Abstract class for database based item collections.
 *
 * @package    Core
 * @subpackage GenericDB@
 * @template Item
 */
abstract class ItemCollection extends cItemBaseAbstract
{

    use cItemCollectionChunkTrait;

    /**
     * Storage of all result items.
     * Contains all result items.
     * @TODO Is the property still used anywhere?
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
     * @var ItemCollection[]|object[]
     */
    protected $_collectionCache = [];

    /**
     * Single item class
     *
     * @var string
     */
    protected $_itemClass;

    /**
     * Iterator object for the next() method
     *
     * @var Item|object
     */
    protected $_iteratorItem;

    /**
     * Reverse join partners for this data object
     *
     * @var string[]
     */
    protected $_JoinPartners = [];

    /**
     * Forward join partners for this data object
     *
     * @var string[]
     */
    protected $_forwardJoinPartners;

    /**
     * Where restrictions for the query
     * @TODO Is the property still used anywhere?
     *
     * @var array
     */
    protected $_whereRestriction;

    /**
     * Inner group conditions
     *
     * @var string[]
     */
    protected $_innerGroupConditions = [];

    /**
     * Group conditions
     *
     * @var array
     */
    protected $_groupConditions = [];

    /**
     * Result fields for the query
     *
     * @var string[]
     */
    protected $_resultFields = [];

    /**
     * Encoding
     *
     * @var string
     */
    protected $_encoding;

    /**
     * @var array The detected global encodings for languages
     */
    private static $_globalEncoding = [];

    /**
     * Item class instance
     *
     * @var Item|object
     */
    protected $_itemClassInstance;

    /**
     * Stores all operators which are supported by GenericDB
     * Unsupported operators are passed through as-is.
     *
     * @var string[]
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
    protected $_where = [];

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
     * Associative array with linked tables, where the key is one of the
     * available ItemCollection classname and the value the ItemCollection
     * instance.
     *
     * @var array
     */
    protected $_links;

    /**
     * Associative array with fields of linked tables, where the key is one
     * of the available ItemCollection classname and the value the field
     * (usually the primary key) for the link.
     * Linked tables are linked by using the primary keys by default, but
     * any defined link fields will be used instead of the primary keys.
     *
     * @var array
     */
    protected $_linkFields;

    /**
     * Constructor to create an instance of this class.
     *
     * @param string $table The table to use as information source
     * @param string $primaryKey The primary key to use
     * @throws cInvalidArgumentException
     */
    public function __construct($table, $primaryKey)
    {
        parent::__construct($table, $primaryKey, get_parent_class($this));

        $this->resetQuery();

        // Try to load driver
        $this->_initializeDriver();

        // Try to find out the current encoding
        $encoding = self::_getGlobalEncoding();
        if (!empty($encoding)) {
            $this->setEncoding($encoding);
        }

        $this->_aOperators = [
            '=',
            '!=',
            '<>',
            '<',
            '>',
            '<=',
            '>=',
            'LIKE',
            'DIACRITICS'
        ];
    }

    /**
     * Defines the reverse links for this table.
     *
     * Important:
     * The class specified by $foreignCollectionClass needs to be a collection class and has to exist.
     * Define all links in the constructor of your object.
     *
     * @param string $foreignCollectionClass Specifies the foreign class to use
     * @throws cInvalidArgumentException If the given foreign class can not be instantiated
     */
    protected function _setJoinPartner(string $foreignCollectionClass)
    {
        if (class_exists($foreignCollectionClass)) {
            // Add class
            if (!in_array($foreignCollectionClass, $this->_JoinPartners)) {
                $this->_JoinPartners[] = cString::toLowerCase($foreignCollectionClass);
            }
        } else {
            throw new cInvalidArgumentException(sprintf(
                'Could not instantiate class [%s] for use with _setJoinPartner in class %s',
                $foreignCollectionClass,
                get_class($this)
            ));
        }
    }

    /**
     * Method to set the accompanying item object.
     *
     * @param string $className Specifies the class name of item which extends from {@see Item}
     * @throws cInvalidArgumentException if the given class can not be instantiated
     */
    protected function _setItemClass(string $className)
    {
        if (class_exists($className)) {
            $this->_itemClass = $className;
            $this->_itemClassInstance = new $className();

            // Initialize driver in case the developer does a setItemClass-Call
            // before calling the parent constructor
            $this->_initializeDriver();
            $this->_driver->setItemClassInstance($this->_itemClassInstance);
        } else {
            throw new cInvalidArgumentException(sprintf(
                'Could not instantiate class [%s] for use with _setItemClass in class %s',
                $className,
                get_class($this)
            ));
        }
    }

    /**
     * Initializes the driver to use with GenericDB.
     *
     * @param bool $forceInit [optional] If true, forces the driver to initialize, even if it already exists.
     */
    protected function _initializeDriver(bool $forceInit = false)
    {
        if (!is_object($this->_driver) || $forceInit) {
            $this->_driver = new cGenericDbDriverMysql();
        }
    }

    /**
     * Sets the encoding, e.g. 'UTF-8'
     */
    public function setEncoding(string $encoding)
    {
        $this->_encoding = $encoding;
        $this->_driver->setEncoding($encoding);
    }

    /**
     * Sets the foreign tables to use in the result set for the query.
     *
     * @param string $foreignClassName The class of foreign table to use
     * @param string $linkField The link field to use instead of the primary keys
     * @throws cInvalidArgumentException If the given foreign class does not exist
     */
    public function link(string $foreignClassName, string $linkField = '')
    {
        if (class_exists($foreignClassName)) {
            $this->_links[$foreignClassName] = new $foreignClassName();
            if (!empty($linkField)) {
                $this->_linkFields[$foreignClassName] = $linkField;
            }
            $this->_setJoinPartner($foreignClassName);
        } else {
            throw new cInvalidArgumentException(sprintf(
                'Could not find class [%s] for use with link in class %s',
                $foreignClassName,
                get_class($this)
            ));
        }
    }

    /**
     * Sets the limit for results
     *
     * @param int $limitStart
     * @param int $limitCount
     */
    public function setLimit($limitStart, $limitCount)
    {
        $this->_limitStart = cSecurity::toInteger($limitStart);
        $this->_limitCount = cSecurity::toInteger($limitCount);
    }

    /**
     * Restricts a query with a WHERE clause
     *
     * @param string $field Name of field
     * @param mixed $restriction The value to use for the condition, values
     *      of type string will be escaped automatically.
     * @param string $operator The operator for the condition, e.g. '=', '>', '<', etc.
     */
    public function setWhere($field, $restriction, $operator = '=')
    {
        $field = cString::toLowerCase($field);
        $this->_where['global'][$field]['operator'] = $operator;
        $this->_where['global'][$field]['restriction'] = $restriction;
    }

    /**
     * Removes a previous set WHERE clause, see {@see ItemCollection::setWhere}.
     *
     * @param string $field
     * @param mixed $restriction
     * @param string $operator [optional]
     */
    public function deleteWhere($field, $restriction, $operator = '=')
    {
        $field = cString::toLowerCase($field);
        if (isset($this->_where['global'][$field]) && is_array($this->_where['global'][$field])) {
            if (
                $this->_where['global'][$field]['operator'] == $operator
                && $this->_where['global'][$field]['restriction'] == $restriction
            ) {
                unset($this->_where['global'][$field]);
            }
        }
    }

    /**
     * Restricts a query with a groupable WHERE clause.
     *
     * @param string $group The WHERE group name
     * @param string $field Name of field
     * @param mixed $restriction The value to use for the condition, values
     *      of type string will be escaped automatically.
     * @param string $operator The operator for the condition, e.g. '=', '>', '<', etc.
     */
    public function setWhereGroup($group, $field, $restriction, $operator = '=')
    {
        $field = cString::toLowerCase($field);
        $this->_where['groups'][$group][$field]['operator'] = $operator;
        $this->_where['groups'][$group][$field]['restriction'] = $restriction;
    }

    /**
     * Removes a previous set groupable WHERE clause, see
     * {@see ItemCollection::setWhereGroup}.
     *
     * @param string $group
     * @param string $field
     * @param mixed $restriction
     * @param string $operator [optional]
     */
    public function deleteWhereGroup($group, $field, $restriction, $operator = '=')
    {
        $field = cString::toLowerCase($field);
        if (isset($this->_where['groups'][$group][$field]) && is_array($this->_where['groups'][$group][$field])) {
            if (
                $this->_where['groups'][$group][$field]['operator'] == $operator
                && $this->_where['groups'][$group][$field]['restriction'] == $restriction
            ) {
                unset($this->_where['groups'][$group][$field]);
            }
        }
    }

    /**
     * Defines how relations in one group are linked each together
     *
     * @param string $group
     * @param string $sCondition [optional]
     */
    public function setInnerGroupCondition($group, $sCondition = 'AND')
    {
        $this->_innerGroupConditions[$group] = $sCondition;
    }

    /**
     * Defines how groups are linked to each other
     *
     * @param string $sGroup1
     * @param string $sGroup2
     * @param string $sCondition [optional]
     */
    public function setGroupCondition($sGroup1, $sGroup2, $sCondition = 'AND')
    {
        $this->_groupConditions[$sGroup1][$sGroup2] = $sCondition;
    }

    /**
     * Builds a where statement out of the setGroupWhere calls
     *
     * @return string With all where statements
     */
    protected function _buildGroupWhereStatements()
    {
        // Find out if there are any defined groups
        $aGroupWhere = [];
        if (count($this->_where['groups']) > 0) {
            // Step through all groups
            foreach ($this->_where['groups'] as $groupName => $group) {
                $aWheres = [];

                // Fetch restriction, fields and operators and build single
                // group where statements
                foreach ($group as $field => $item) {
                    $aWheres[] = $this->_driver->buildOperator($field, $item['operator'], $item['restriction']);
                }

                // Add completed sub-statements
                $operator = 'AND';
                if (isset($this->_innerGroupConditions[$groupName])) {
                    $operator = $this->_innerGroupConditions[$groupName];
                }

                $aGroupWhere[$groupName] = implode(' ' . $operator . ' ', $aWheres);
            }
        }

        // Combine groups
        $sGroupWhereStatement = '';
        $mLastGroup = false;
        foreach ($aGroupWhere as $groupName => $group) {
            if ($mLastGroup !== false) {
                $operator = 'AND';
                // Check if there's a group condition
                if (isset($this->_groupConditions[$groupName])) {
                    if (isset($this->_groupConditions[$groupName][$mLastGroup])) {
                        $operator = $this->_groupConditions[$groupName][$mLastGroup];
                    }
                }

                // Reverse check
                if (isset($this->_groupConditions[$mLastGroup])) {
                    if (isset($this->_groupConditions[$mLastGroup][$groupName])) {
                        $operator = $this->_groupConditions[$mLastGroup][$groupName];
                    }
                }

                $sGroupWhereStatement .= ' ' . $operator . ' (' . $group . ')';
            } else {
                $sGroupWhereStatement .= '(' . $group . ')';
            }

            $mLastGroup = $groupName;
        }

        return $sGroupWhereStatement;
    }

    /**
     * Builds a where statement out of the setWhere calls
     *
     * @return string With all where statements
     */
    protected function _buildWhereStatements(): string
    {
        $aWheres = [];

        // Build global where condition
        foreach ($this->_where['global'] as $field => $item) {
            $aWheres[] = $this->_driver->buildOperator($field, $item['operator'], $item['restriction']);
        }

        return implode(' AND ', $aWheres);
    }

    /**
     * Fetches all tables which will be joined later on.
     *
     * The returned array has the following format:
     * <pre>
     * [
     *     [fields],
     *     [tables],
     *     [joins],
     *     [wheres]
     * ];
     * </pre>
     *
     * Notes:
     * The table is the table name which needs to be added to the FROM clause
     * The join statement which is inserted after the master table
     * The where statement is combined with all other where statements
     * The fields to select from
     *
     * @return array Array structure, see above
     * @throws cException if no join partner could be found
     */
    protected function _fetchJoinTables(): array
    {
        $aParameters = [];
        $fields = [];
        $aTables = [];
        $aJoins = [];
        $aWheres = [];

        // Fetch linked tables
        foreach ($this->_links as $link => $object) {
            $matches = $this->_findReverseJoinPartner(get_class($this), $link);
            if ($matches !== false) {
                if (isset($matches['desttable'])) {
                    // Driver function: Build query parts
                    $aParameters[] = $this->_driver->buildJoinQuery(
                        $matches['desttable'],
                        cString::toLowerCase($matches['destclass']),
                        $matches['key'],
                        cString::toLowerCase($matches['sourceclass']),
                        $matches['key']
                    );
                } else {
                    foreach ($matches as $match) {
                        $aParameters[] = $this->_driver->buildJoinQuery(
                            $match['desttable'],
                            cString::toLowerCase($match['destclass']),
                            $match['key'],
                            cString::toLowerCase($match['sourceclass']),
                            $match['key']
                        );
                    }
                }
            } else {
                throw new cUnexpectedValueException(sprintf(
                    "The join partner '%s' is not registered and can not be used with link().",
                    get_class($this)
                ));
            }
        }

        // Add this class
        $fields[] = cString::toLowerCase(cString::toLowerCase(get_class($this))) . '.' . $this->getPrimaryKeyName();

        // Make the parameters unique
        foreach ($aParameters as $parameter) {
            array_unshift($fields, $parameter['field']);
            array_unshift($aTables, $parameter['table']);
            array_unshift($aJoins, $parameter['join']);
            array_unshift($aWheres, $parameter['where']);
        }

        $fields = array_filter(array_unique($fields));
        $aTables = array_filter(array_unique($aTables));
        $aJoins = array_filter(array_unique($aJoins));
        $aWheres = array_filter(array_unique($aWheres));

        return [
            'fields' => $fields,
            'tables' => $aTables,
            'joins' => $aJoins,
            'wheres' => $aWheres
        ];
    }

    /**
     * Resolves links (class names of joined partners)
     */
    protected function _resolveLinks(): array
    {
        $aResolvedLinks = [];
        $aResolvedLinks[] = cString::toLowerCase(get_class($this));

        foreach ($this->_JoinPartners as $link) {
            $class = new $link();
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
        $this->_forwardJoinPartners = [];
        $this->_links = [];
        $this->_linkFields = [];
        $this->_where['global'] = [];
        $this->_where['groups'] = [];
        $this->_groupConditions = [];
        $this->_resultFields = [];
    }

    /**
     * Builds and runs the query
     *
     * @throws cException If no item class has been set
     */
    public function query(): bool
    {
        if (!isset($this->_itemClassInstance)) {
            throw new cException('GenericDB can\'t use query() if no item class is set via setItemClass');
        }

        $sGroupWhereStatements = $this->_buildGroupWhereStatements();
        $sWhereStatements = $this->_buildWhereStatements();
        $aParameters = $this->_fetchJoinTables();

        $aStatement = [
            'SELECT',
            implode(', ', (array_merge($aParameters['fields'], $this->_resultFields))),
            'FROM',
            '`' . $this->table . '` AS ' . cString::toLowerCase(get_class($this))
        ];

        if (count($aParameters['tables']) > 0) {
            $aStatement[] = implode(', ', $aParameters['tables']);
        }

        if (count($aParameters['joins']) > 0) {
            $aStatement[] = implode(' ', $aParameters['joins']);
        }

        $aWheres = [];

        if (count($aParameters['wheres']) > 0) {
            $aWheres[] = implode(', ', $aParameters['wheres']);
        }

        if ($sGroupWhereStatements != '') {
            $aWheres[] = $sGroupWhereStatements;
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
            $limitStart = $this->_limitStart;
            $limitCount = $this->_limitCount;
            $aStatement[] = "LIMIT $limitStart, $limitCount";
        }

        $sql = implode(' ', $aStatement);

        $result = $this->db->query($sql);
        $this->_lastSQL = $sql;
        // @todo disable all mode in this method for the moment. It has to be
        // verified, if enabling will result in negative side effects.
        $this->_bAllMode = false;

        return (bool)$result;
    }

    /**
     * Sets the result order part of the query
     * (e.g. 'fieldname', 'fieldname DESC', 'fieldname DESC, field2name ASC')
     *
     * @param string $order
     */
    public function setOrder($order)
    {
        $this->_order = cString::toLowerCase($order);
    }

    /**
     * Adds a result field
     */
    public function addResultField(string $field)
    {
        $field = cString::toLowerCase($field);
        if (!in_array($field, $this->_resultFields)) {
            $this->_resultFields[] = $field;
        }
    }

    /**
     * Adds multiple result fields
     *
     * @param string[] $fields
     * @since CONTENIDO 4.10.2
     */
    public function addResultFields(array $fields)
    {
        foreach ($fields as $field) {
            $this->addResultField($field);
        }
    }

    /**
     * Removes existing result field
     */
    public function removeResultField(string $field)
    {
        $field = cString::toLowerCase($field);
        $key = array_search($field, $this->_resultFields);
        if ($key !== false) {
            unset($this->_resultFields[$key]);
        }
    }

    /**
     * Removes multiple result fields
     *
     * @param string[] $fields
     * @since CONTENIDO 4.10.2
     */
    public function removeResultFields(array $fields)
    {
        foreach ($fields as $field) {
            $this->removeResultField($field);
        }
    }

    /**
     * Returns reverse join partner.
     *
     * @param string $parentClass
     * @param string $className
     * @return array|bool  List of join partner structures or false.
     */
    protected function _findReverseJoinPartner($parentClass, $className)
    {
        // Check if we found a direct link
        if (in_array(cString::toLowerCase($className), $this->_JoinPartners)) {
            $obj = new $className();

            return [
                'desttable' => $obj->table,
                'destclass' => cString::toLowerCase($className),
                'sourceclass' => cString::toLowerCase($parentClass),
                'key' => $this->_getReverseJoinPartnerKey($obj, $className)
            ];
        } else {
            // Recurse all items
            foreach ($this->_JoinPartners as $join => $tmpClassname) {
                $obj = new $tmpClassname();
                $status = $obj->_findReverseJoinPartner($tmpClassname, $className);

                if (is_array($status)) {
                    $returns = [];

                    if (!isset($status['desttable'])) {
                        foreach ($status as $subItem) {
                            $returns[] = $subItem;
                        }
                    } else {
                        $returns[] = $status;
                    }

                    $returns[] = [
                        'desttable' => $obj->table,
                        'destclass' => $tmpClassname,
                        'sourceclass' => cString::toLowerCase($parentClass),
                        'key' => $this->_getReverseJoinPartnerKey($obj, $className)
                    ];

                    return $returns;
                }
            }
        }
        return false;
    }

    /**
     * Returns the key (table field to use for JOIN clause) for the
     * reverse join partner.
     *
     * @param Item|object $joinPartnerObj Join partner instance
     * @param string $className Join partner class name
     * @return  string  Join partner key (table field)
     */
    protected function _getReverseJoinPartnerKey($joinPartnerObj, $className)
    {
        if (!empty($this->_linkFields[$className])) {
            return $this->_linkFields[$className];
        } else {
            return $joinPartnerObj->getPrimaryKeyName();
        }
    }

    /**
     * Selects all entries from the database.
     * Objects are loaded using their primary key.
     *
     * @param string $where [optional] Specifies the WHERE clause.
     * @param string $groupBy [optional] Specifies the GROUP BY clause.
     * @param string $orderBy [optional] Specifies the ORDER BY clause.
     * @param string $limit [optional] Specifies the LIMIT clause.
     * @return bool True on success, otherwise false
     * @throws cDbException
     */
    public function select($where = '', $groupBy = '', $orderBy = '', $limit = '')
    {
        unset($this->objects);

        $where = empty($where) ? '' : 'WHERE ' . $where;
        $groupBy = empty($groupBy) ? '' : ' GROUP BY ' . $groupBy;
        $orderBy = empty($orderBy) ? '' : ' ORDER BY ' . $orderBy;
        $limit = empty($limit) ? '' : ' LIMIT ' . $limit;

        $fields = $this->_settings['select_all_mode'] ? '*' : $this->getPrimaryKeyName();
        $sql = 'SELECT ' . $fields . ' FROM `' . $this->table . '`' . $where . $groupBy . $orderBy . $limit;
        $this->db->query($sql);
        $this->_lastSQL = $sql;
        $this->_bAllMode = $this->_settings['select_all_mode'];

        return $this->db->numRows() > 0;
    }

    /**
     * Selects all entries from the database.
     * Objects are loaded using their primary key.
     *
     * @param string $distinct [optional] Specifies if distinct will be added to the SQL statement
     *      ($distinct !== '' -> DISTINCT)
     * @param string $from [optional] Specifies the additional FROM clause
     *      (e.g. 'con_news_groups AS groups, con_news_groupmembers AS groupmembers').
     * @param string $where [optional] Specifies the WHERE clause.
     * @param string $groupBy [optional] Specifies the GROUP BY clause.
     * @param string $orderBy [optional] Specifies the ORDER BY clause.
     * @param string $limit [optional] Specifies the LIMIT clause.
     * @return bool True on success, otherwise false
     * @throws cDbException
     */
    public function flexSelect(
        $distinct = '', $from = '', $where = '', $groupBy = '', $orderBy = '', $limit = ''
    ) {
        unset($this->objects);

        if ($distinct != '') {
            $distinct = 'DISTINCT ';
        }

        if ($from != '') {
            $from = ', ' . $from;
        }

        if ($where != '') {
            $where = ' WHERE ' . $where;
        }

        if ($groupBy != '') {
            $groupBy = ' GROUP BY ' . $groupBy;
        }

        if ($orderBy != '') {
            $orderBy = ' ORDER BY ' . $orderBy;
        }

        if ($limit != '') {
            $limit = ' LIMIT ' . $limit;
        }

        $tableNameAlias = cString::toLowerCase(get_class($this));
        $primaryKey = $this->getPrimaryKeyName();

        $sql = 'SELECT ' . $distinct . $tableNameAlias . '.' . $primaryKey . ' AS ' . $primaryKey
            . ' FROM `' . $this->table . '` AS ' . $tableNameAlias . $from . $where . $groupBy . $orderBy . $limit;

        $this->db->query($sql);
        $this->_lastSQL = $sql;
        // @todo disable all mode in this method
        $this->_bAllMode = false;

        return $this->db->numRows() > 0;
    }

    /**
     * Checks if a specific record exists.
     *
     * @param mixed $id The id to check for (could be numeric or string)
     * @return bool True if object exists, false if not
     * @throws cDbException
     */
    public function exists($id): bool
    {
        $db = $this->_getSecondDBInstance();
        $sql = "SELECT `%s` FROM `%s` WHERE `%s` = '%s'";
        $db->query($sql, $this->getPrimaryKeyName(), $this->table, $this->getPrimaryKeyName(), $id);
        return $db->nextRecord();
    }

    /**
     * Advances to the next item in the database.
     *
     * @return Item|object|false Next object, or false if no more objects
     * @throws cDbException|cException
     */
    public function next()
    {
        $ret = false;
        while ($this->db->nextRecord()) {
            if ($this->_bAllMode) {
                $aRs = $this->db->toArray(cDbDriverHandler::FETCH_BOTH);
                $ret = $this->loadItem($aRs);
            } else {
                $ret = $this->loadItem($this->db->f($this->getPrimaryKeyName()));
            }

            if ($ret->get($this->getPrimaryKeyName()) == '') {
                continue;
            } else {
                break;
            }
        }
        return $ret;
    }

    /**
     * Generator function to use in foreach construct, saves memory by using generator syntax `yield`.
     *
     * Alternative way for `while ($item = $itemCollection->next()) {...}`.
     *
     * Example:
     * <code>
     * $myItemCollection = new cApiMyItemCollection();
     * $myItemCollection->select();
     * foreach ($myItemCollection->getItems() as $myItem) {
     *     // Do something with $myItem...
     * }
     * </code>
     * @return \Generator|Item[]
     * @throws cDbException|cException
     * @since CONTENIDO 4.10.2
     */
    public function getItems(): Generator
    {
        while ($entry = $this->next()) {
            yield $entry;
        }
    }

    /**
     * Fetches the result set related to current loaded primary key as an object.
     *
     * @return Item|object
     * @throws cException
     */
    public function fetchObject(string $className)
    {
        $key = cString::toLowerCase($className);

        if (empty($this->_collectionCache[$key]) || !is_object($this->_collectionCache[$key])) {
            $this->_collectionCache[$key] = new $className();
        }
        /* @var $obj ItemCollection */
        $obj = $this->_collectionCache[$key];
        return $obj->loadItem($this->db->f($obj->getPrimaryKeyName()));
    }

    /**
     * Fetches the result of a previous run query (e.g. `$obj->query()`) into a
     * desired result list.
     *
     * @param string[] $fields [optional] Array of fields to fetch from the result.
     *     If it is an indexed array, the value will be used for the field, and
     *     the result entries will be also an indexed array.
     *     <pre>
     *     // Parameter `$fields` as indexed array
     *     [
     *          'idclient',
     *          'name'
     *     ]
     *     // Will return a result where the entries are an indexed array like
     *     [
     *          1,
     *          'Example Client'
     *     ]
     *     </pre>
     *     If the array contains keys, the key will be used as alias for the field,
     *     and the result entries will be also an associative array.
     *     // Parameter `$fields` as associative array
     *     <pre>
     *      [
     *          'clientId' => 'idclient',
     *          'clientName' => 'name'
     *     ]
     *     // Will return a result where the entries are an associative array like
     *     [
     *          'clientId' => 1,
     *          'clientName' => 'Example Client'
     *     ]
     *     </pre>
     * @param string[] $classNames [optional] Array of class names, which extends
     *     the {@see ItemCollection}, to fetch from the result.
     *     If it is an indexed array, the value will be used for the class name.
     *     <pre>
     *      [
     *          'cApiClientCollection'
     *     ]
     *     </pre>
     *     If the array contains keys, the key will be used as alias for the
     *     class name.
     *     <pre>
     *      [
     *          'client' => 'cApiClientCollection'
     *     ]
     *     </pre>
     *     If you specify more than one class name with the same key, the array
     *     will be multidimensional.
     *
     * @return array
     * @throws cDbException|cException
     */
    public function fetchTable(array $fields = [], array $classNames = []): array
    {
        if ($this->count() <= 0) {
            return [];
        }

        $this->db->seek(0);

        $aTable = [];
        $row = 1;
        while ($this->db->nextRecord()) {
            foreach ($fields as $alias => $field) {
                if ($alias != '') {
                    $aTable[$row][$alias] = $this->db->f($field);
                } else {
                    $aTable[$row][$field] = $this->db->f($field);
                }
            }

            // Fetch objects
            foreach ($classNames as $alias => $className) {
                if ($alias != '') {
                    if (isset($aTable[$row][$alias])) {
                        // Is set, check for array. If no array, create one
                        if (!is_array($aTable[$row][$alias])) {
                            $aTable[$row][$alias] = [];
                        }
                        $aTable[$row][$alias][] = $this->fetchObject($className);
                    } else {
                        $aTable[$row][$alias] = $this->fetchObject($className);
                    }
                } else {
                    $aTable[$row][$className] = $this->fetchObject($className);
                }
            }
            $row++;
        }

        $this->db->seek(0);

        return $aTable;
    }

    /**
     * Returns an array of arrays
     *
     * @param string[] $classNames With the correct order of the class names which extend from {@see Item}
     * @return array Result
     * @throws cDbException|cException
     */
    public function queryAndFetchStructured(array $classNames): array
    {
        $aOrder = [];
        $aFetchObjects = [];

        foreach ($classNames as $className) {
            $obj = new $className();
            $className = cString::toLowerCase($className);
            $aOrder[] = $className . '.' . $obj->getPrimaryKeyName() . ' ASC';
            $aFetchObjects[] = $obj;
        }

        $this->setOrder(implode(', ', $aOrder));
        $this->query();

        $this->db->seek(0);

        $result = [];
        while ($this->db->nextRecord()) {
            $result = $this->_recursiveStructuredFetch($aFetchObjects, $result);
        }

        return $result;
    }

    /**
     * Loops through the current result of the last run query, and collects the
     * result recursively by instantiating the proper Item object for each entry
     * in `$objects` parameter.
     *
     * @param Item[]|object[] $objects List of objects which extend from {@see Item}.
     * @param array $results Array of results where the key is the primary key
     *    of the object and the value is an associative structure, e.g.
     *    <pre>
     *    $results = [
     *        '123' => [
     *            'class' => (string) Class name in lower-case
     *            'object' => (Item|object) The object instance
     *            'items' => (Item[]|object[]|null) Recursive structure
     *        ],
     *        ...
     *    ];
     *    </pre>
     *
     * @return array the provided array being updated within the function.
     */
    protected function _recursiveStructuredFetch(array $objects, array $results): array
    {
        $i = array_shift($objects);

        $value = $this->db->f($i->getPrimaryKeyName());

        if (!is_null($value)) {
            $results[$value]['class'] = cString::toLowerCase(get_class($i));
            $results[$value]['object'] = $i->loadItem($value);

            if (count($objects) > 0) {
                $results[$value]['items'] = $this->_recursiveStructuredFetch($objects, $results[$value]['items']);
            }
        }

        return $results;
    }

    /**
     * Returns the amount of returned items
     *
     * @return int Number of rows
     */
    public function count(): int
    {
        return $this->db->numRows();
    }

    /**
     * Loads a single record by its id.
     *
     * @param string|int $id The primary key of the item to load.
     * @return Item|object The loaded item
     * @throws cException
     */
    public function fetchById($id)
    {
        if (is_numeric($id)) {
            $id = (int)$id;
        } elseif (is_string($id)) {
            $id = $this->escape($id);
        }
        return $this->loadItem($id);
    }

    /**
     * Loads a single object from the database.
     *
     * @param mixed $mItem The primary key of the item to load or a recordset with item data
     *         (array) to inject to the item object.
     * @return Item|object The newly created object
     * @throws cException If item class is not set
     */
    public function loadItem($mItem)
    {
        if (empty($this->_itemClass)) {
            throw new cException(sprintf(
                'ItemClass has to be set in the constructor of class %s)',
                get_class($this)
            ));
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
     * Creates a new item in the table and loads it afterward.
     *
     * @param string|array $data [optional] Parameter for direct input of primary key value
     *      (string) or multiple column name - value pairs
     * @return Item|object The newly created object
     * @throws cInvalidArgumentException|cDbException|cException
     */
    public function createNewItem($data = NULL)
    {
        $this->_executeCallbacks(self::CREATE_BEFORE, get_class($this), []);

        $db = $this->_getSecondDBInstance();

        $primaryKeyValue = NULL;
        // prepare the primary key value and the data depending on the type of
        // $data
        if (is_array($data)) {
            if (array_key_exists($this->getPrimaryKeyName(), $data)) {
                $primaryKeyValue = $data[$this->getPrimaryKeyName()];
            }
        } else {
            // data is the primary key
            $primaryKeyValue = $data;
            $data = [
                $this->getPrimaryKeyName() => $data
            ];
        }

        // build the insert statement and execute it
        $sql = $db->buildInsert($this->table, $data);

        $db->query($sql);

        if ($primaryKeyValue === NULL) {
            $primaryKeyValue = $db->getLastInsertedId();
        }

        if ($db->affectedRows() == 0) {
            $this->_executeCallbacks(self::CREATE_FAILURE, $this->_itemClass, []);
        } else {
            $this->_executeCallbacks(self::CREATE_SUCCESS, $this->_itemClass, [
                $primaryKeyValue
            ]);
        }

        return $this->loadItem($primaryKeyValue);
    }

    /**
     * Inserts a new item entry by using an existing item entry.
     *
     * @param Item|object $srcItem Source Item instance to copy
     * @param array $fieldsToOverwrite Associative list of fields to overwrite.
     * @return Item|object|NULL
     * @throws cDbException|cException
     * @throws cInvalidArgumentException If Item class doesn't match the defined _itemClass property
     *      or passed Item instance has no loaded recordset
     */
    public function copyItem($srcItem, array $fieldsToOverwrite = [])
    {
        if (get_class($srcItem) !== $this->_itemClass) {
            throw new cInvalidArgumentException('Item class does not match');
        } elseif (!$srcItem->isLoaded()) {
            throw new cInvalidArgumentException('Item instance has no loaded recordset');
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
            } elseif ($field == $this->getPrimaryKeyName()) {
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
     * Returns all ids of the records in the table that match the criteria in the provided WHERE clause.
     *
     * @param string $where The WHERE clause of the SQL statement
     * @param string $groupBy The GROUP BY clause. @since CONTENIDO 4.10.2
     * @param string $orderBy The ORDER BY clause. @since CONTENIDO 4.10.2
     * @param string $limit The LIMIT clause. @since CONTENIDO 4.10.2
     * @return int[]|string[] List of ids
     * @throws cDbException
     */
    public function getIdsByWhereClause(
        string $where,
        string $groupBy = '',
        string $orderBy = '',
        string $limit = ''
    ): array {
        $db = $this->_getSecondDBInstance();

        $pkField = $this->getPrimaryKeyName();
        $where = empty($where) ? '' : 'WHERE ' . $where;
        $groupBy = empty($groupBy) ? '' : ' GROUP BY ' . $groupBy;
        $orderBy = empty($orderBy) ? '' : ' ORDER BY ' . $orderBy;
        $limit = empty($limit) ? '' : ' LIMIT ' . $limit;

        // Get all ids
        $sql = 'SELECT `' . $this->getPrimaryKeyName() . '` FROM `' . $this->table . '`' . $where . $groupBy . $orderBy . $limit;
        $db->query($sql);

        $ids = [];
        while ($db->nextRecord()) {
            $ids[] = $db->f($pkField);
        }

        return $ids;
    }

    /**
     * Returns all ids of the records in the table that match the criteria
     * in the provided WHERE clause ($field $operator $value).
     *
     * @param string $field The table field name
     * @param string|int|null|mixed $value The value
     * @param string $operator The operator to use (e.g. '=', '>', '<', 'IN', etc.)
     * @return int[]|string[] List of ids
     * @throws cDbException
     * @since CONTENIDO 4.10.2
     */
    public function getIdsWhere(string $field, $value, string $operator = '='): array
    {
        // Build WHERE clause
        $where = $this->_driver->buildOperator($field, $operator, $value);

        // Return the data
        return $this->getIdsByWhereClause($where);
    }

     /**
     * Returns all specified fields of the records in the table that match
     * the criteria in the provided WHERE clause.
     *
     * @param array $fields List of fields to get
     * @param string $where The WHERE clause of the SQL statement
     * @param string $groupBy The GROUP BY clause. @since CONTENIDO 4.10.2
     * @param string $orderBy The ORDER BY clause. @since CONTENIDO 4.10.2
     * @param string $limit The LIMIT clause. @since CONTENIDO 4.10.2
     * @return array List of entries with specified fields
     * @throws cDbException
     */
    public function getFieldsByWhereClause(
        array $fields,
        string $where,
        string $groupBy = '',
        string $orderBy = '',
        string $limit = ''
    ): array {
        if (!count($fields)) {
            return [];
        }

        $db = $this->_getSecondDBInstance();

        if (in_array('*', $fields)) {
            // Asterisk ("*") to get all field found
            $fieldsSql = '*';
        } else {
            // Escape fields
            $escapedFields = array_map([$db, 'escape'], $fields);
            $fieldsSql = '`' . implode('`, `', $escapedFields) . '`';
        }

        $where = empty($where) ? '' : 'WHERE ' . $where;
        $groupBy = empty($groupBy) ? '' : ' GROUP BY ' . $groupBy;
        $orderBy = empty($orderBy) ? '' : ' ORDER BY ' . $orderBy;
        $limit = empty($limit) ? '' : ' LIMIT ' . $limit;

        // Get all fields
        $entries = [];
        $sql = 'SELECT ' . $fieldsSql . ' FROM `' . $this->table . '`' . $where . $groupBy . $orderBy . $limit;
        $db->query($sql);
        while ($db->nextRecord()) {
            $data = [];
            foreach ($fields as $field) {
                $data[$field] = $db->f($field);
            }
            $entries[] = $data;
        }

        return $entries;
    }

    /**
     * Returns all specified fields of the records in the table that match
     * the criteria in the provided WHERE clause ($field $operator $value).
     *
     * @param array $fields List of fields to get
     * @param string $field The table field name to query
     * @param string|int|null|mixed $value The value to query
     * @param string $operator The operator to use (e.g. '=', '>', '<', 'IN', etc.)
     * @param string $groupBy The GROUP BY clause.
     * @param string $orderBy The ORDER BY clause.
     * @param string $limit The LIMIT clause.
     * @return int[]|string[] List of ids
     * @throws cDbException
     * @since CONTENIDO 4.10.2
     */
    public function getFieldsWhere(
        array $fields,
        string $field,
        $value,
        string $operator = '=',
        string $groupBy = '',
        string $orderBy = '',
        string $limit = ''
    ): array {
        // Build WHERE clause
        $where = $this->_driver->buildOperator($field, $operator, $value);

        // Return the data
        return $this->getFieldsByWhereClause($fields, $where, $groupBy, $orderBy, $limit);
    }

    /**
     * Returns all ids of records in the table.
     *
     * @return int[]|string[] List of ids
     * @throws cDbException
     */
    public function getAllIds(): array
    {
        $db = $this->_getSecondDBInstance();

        $ids = [];

        // Get all ids
        $db->query('SELECT `%s` AS `pk` FROM `%s`', $this->getPrimaryKeyName(), $this->table);
        while ($db->nextRecord()) {
            $ids[] = $db->f('pk');
        }

        return $ids;
    }

    /**
     * Deletes the record with id from the table.
     * Deletes also the cached record and any existing properties.
     *
     * @param int|string|mixed $id Id of record to delete
     * @return bool
     * @throws cDbException|cInvalidArgumentException
     */
    public function delete($id)
    {
        return $this->_delete($id);
    }

    /**
     * Deletes all records in the table that match the criteria in the provided WHERE clause.
     * Deletes also the cached records and any existing properties.
     *
     * @param string $where The WHERE clause of the SQL statement
     * @return int Number of deleted records
     * @throws cDbException|cInvalidArgumentException
     */
    public function deleteByWhereClause($where): int
    {
        // Get all ids and delete related entries
        $ids = $this->getIdsByWhereClause($where);

        if (!count($ids)) {
            return 0;
        }

        return $this->_deleteMultiple($ids);
    }

    /**
     * Deletes all records in the table that match the criteria field, and its value (field = value).
     * Deletes also the cached records and any existing properties.
     *
     * @param string $field The field name
     * @param mixed $mValue The value of the field
     * @return int Number of deleted records
     * @throws cDbException|cInvalidArgumentException
     */
    public function deleteBy($field, $mValue)
    {
        $where = (is_string($mValue)) ? "`%s` = '%s'" : "`%s` = %d";
        $where = $this->db->prepare($where, $field, $mValue);

        return $this->deleteByWhereClause($where);
    }

    /**
     * Deletes a record from the table, deletes also the cached record
     * and any of its existing properties.
     *
     * @param mixed $id
     *         Id of record to delete
     * @return bool
     *
     * @throws cDbException|cInvalidArgumentException
     */
    protected function _delete($id)
    {
        $this->_executeCallbacks(self::DELETE_BEFORE, $this->_itemClass, [
            $id
        ]);

        $db = $this->_getSecondDBInstance();

        // Delete the database record
        $sql = "DELETE FROM `%s` WHERE `%s` = '%s'";
        $db->query($sql, $this->table, $this->getPrimaryKeyName(), $id);
        $success = $db->affectedRows();

        // Delete the cached record
        $this->_oCache->removeItem($id);

        // Delete any existing property values
        $oProperties = $this->_getPropertiesCollectionInstance();
        $oProperties->deleteProperties($this->getPrimaryKeyName(), $id);

        if ($success == 0) {
            $this->_executeCallbacks(self::DELETE_FAILURE, $this->_itemClass, [
                $id
            ]);
            return false;
        } else {
            $this->_executeCallbacks(self::DELETE_SUCCESS, $this->_itemClass, [
                $id
            ]);
            return true;
        }
    }

    /**
     * Deletes all records with the provided ids from the table, deletes also
     * the cached records and any of their existing properties.
     *
     * @param int[]|string[] $ids Id of records to delete
     * @return int Number of affected records
     * @throws cDbException|cInvalidArgumentException
     */
    protected function _deleteMultiple(array $ids): int
    {
        foreach ($ids as $id) {
            $this->_executeCallbacks(self::DELETE_BEFORE, $this->_itemClass, [
                $id
            ]);
        }

        $db = $this->_getSecondDBInstance();

        // Delete multiple database records at once
        $aEscapedIds = array_map([
            $db,
            'escape'
        ], $ids);
        $in = "'" . implode("', '", $aEscapedIds) . "'";
        $sql = "DELETE FROM `%s` WHERE `%s` IN (" . $in . ")";
        $db->query($sql, $this->table, $this->getPrimaryKeyName());
        $numAffected = $db->affectedRows();

        // Delete the cached records
        $this->_oCache->removeItems($ids);

        // Delete any existing property values of the records
        $oProperties = $this->_getPropertiesCollectionInstance();
        $oProperties->deletePropertiesMultiple($this->getPrimaryKeyName(), $ids);

        // NOTE: Deleting multiple entries at once has a drawback. There is no
        // way to detect faulty ids, if one or more entries couldn't be deleted.
        if ($numAffected == 0) {
            foreach ($ids as $id) {
                $this->_executeCallbacks(self::DELETE_FAILURE, $this->_itemClass, [
                    $id
                ]);
            }
        } else {
            foreach ($ids as $id) {
                $this->_executeCallbacks(self::DELETE_SUCCESS, $this->_itemClass, [
                    $id
                ]);
            }
        }
        return $numAffected;
    }

    /**
     * Fetches an array of fields from the database.
     *
     * Example:
     * $i = $object->fetchArray('idartlang', ['idlang', 'name']);
     *
     * could result in:
     * $i[5] = ['idlang' => 5, 'name' => 'My Article'];
     *
     * Important: If you don't pass an array for fields, the function
     * doesn't create an array.
     *
     * @param string $key Name of the field to use for the key.
     *         If omitted the primary key name will be used.
     * @param string|string[]|null $fields Name of the field or list of fields to return.
     *         If omitted all fields will be returned.
     * @return array Resulting array
     * @throws cDbException|cException
     */
    public function fetchArray(string $key = '', $fields = null): array
    {
        $result = [];

        if (empty($key)) {
            $key = $this->getPrimaryKeyName();
        }
        if (!is_array($fields) && !is_null($fields)) {
            $fields = cSecurity::toString($fields);
        }

        while ($item = $this->next()) {
            $_key = $item->get($key);
            if (is_array($fields)) {
                foreach ($fields as $value) {
                    $result[$_key][$value] = $item->get($value);
                }
            } elseif (is_null($fields)) {
                $result[$_key] = $item->toArray();
            } else {
                $result[$_key] = $item->get($fields);
            }
        }

        return $result;
    }

    /**
     * Tries to detect the global encoding for current language and returns it.
     * Stores the detected encoding in cache property, tp prevent further
     * detection trials in future usages.
     */
    private static function _getGlobalEncoding(): ?string
    {
        $lang = cRegistry::getLanguageId();
        if (!isset(self::$_globalEncoding[$lang])) {
            $encodings = $GLOBALS['aLanguageEncodings'] ?? [];
            if ($lang > 0 && is_array($encodings) && isset($encodings[$lang])) {
                self::$_globalEncoding[$lang] = cSecurity::toString($encodings[$lang]);
            }
        }

        return self::$_globalEncoding[$lang] ?? null;
    }

}
