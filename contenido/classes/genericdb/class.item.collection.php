<?php

/**
 * This file contains the generic db item collection class.
 *
 * @package Core
 * @subpackage GenericDB
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
 * @subpackage GenericDB@
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
     * @param string $sTable
     *         The table to use as information source
     * @param string $sPrimaryKey
     *         The primary key to use
     *
     * @throws cInvalidArgumentException
     */
    public function __construct($sTable, $sPrimaryKey)
    {
        parent::__construct($sTable, $sPrimaryKey, get_parent_class($this));

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
     * The class specified by $sForeignCollectionClass needs to be a
     * collection class and has to exist.
     * Define all links in the constructor of your object.
     *
     * @param string $sForeignCollectionClass
     *         Specifies the foreign class to use
     *
     * @throws cInvalidArgumentException
     *         if the given foreign class can not be instantiated
     */
    protected function _setJoinPartner($sForeignCollectionClass)
    {
        if (class_exists($sForeignCollectionClass)) {
            // Add class
            if (!in_array($sForeignCollectionClass, $this->_JoinPartners)) {
                $this->_JoinPartners[] = cString::toLowerCase($sForeignCollectionClass);
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
     *         Specifies the class name of item which extends from {@see Item}
     *
     * @throws cInvalidArgumentException
     *         if the given class can not be instantiated
     */
    protected function _setItemClass(string $sClassName)
    {
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
     * @param bool $bForceInit [optional]
     *         If true, forces the driver to initialize, even if it already exists.
     */
    protected function _initializeDriver($bForceInit = false)
    {
        if (!is_object($this->_driver) || $bForceInit) {
            $this->_driver = new cGenericDbDriverMysql();
        }
    }

    /**
     * Sets the encoding.
     *
     * @param string $sEncoding
     */
    public function setEncoding($sEncoding)
    {
        $this->_encoding = $sEncoding;
        $this->_driver->setEncoding($sEncoding);
    }

    /**
     * Sets the foreign tables to use in the result set for the query.
     *
     * @param string $sForeignClass
     *         The class of foreign table to use
     * @param string $sLinkField
     *         The link field to use instead of the primary keys
     * @throws cInvalidArgumentException
     *         if the given foreign class does not exist
     */
    public function link($sForeignClass, $sLinkField = '')
    {
        if (class_exists($sForeignClass)) {
            $this->_links[$sForeignClass] = new $sForeignClass();
            if (!empty($sLinkField)) {
                $this->_linkFields[$sForeignClass] = $sLinkField;
            }
            $this->_setJoinPartner($sForeignClass);
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
    public function setLimit($iRowStart, $iRowCount)
    {
        $this->_limitStart = cSecurity::toInteger($iRowStart);
        $this->_limitCount = cSecurity::toInteger($iRowCount);
    }

    /**
     * Restricts a query with a WHERE clause
     *
     * @param string $sField Name of field
     * @param mixed $mRestriction The value to use for the condition, values
     *      of type string will be escaped automatically.
     * @param string $sOperator The operator for the condition, e.g. '=', '>', '<', etc.
     */
    public function setWhere($sField, $mRestriction, $sOperator = '=')
    {
        $sField = cString::toLowerCase($sField);
        $this->_where['global'][$sField]['operator'] = $sOperator;
        $this->_where['global'][$sField]['restriction'] = $mRestriction;
    }

    /**
     * Removes a previous set WHERE clause, see
     * {@see ItemCollection::setWhere}.
     *
     * @param string $sField
     * @param mixed $mRestriction
     * @param string $sOperator [optional]
     */
    public function deleteWhere($sField, $mRestriction, $sOperator = '=')
    {
        $sField = cString::toLowerCase($sField);
        if (isset($this->_where['global'][$sField]) && is_array($this->_where['global'][$sField])) {
            if ($this->_where['global'][$sField]['operator'] == $sOperator && $this->_where['global'][$sField]['restriction'] == $mRestriction) {
                unset($this->_where['global'][$sField]);
            }
        }
    }

    /**
     * Restricts a query with a groupable WHERE clause.
     *
     * @param string $sGroup The WHERE group name
     * @param string $sField Name of field
     * @param mixed $mRestriction The value to use for the condition, values
     *      of type string will be escaped automatically.
     * @param string $sOperator The operator for the condition, e.g. '=', '>', '<', etc.
     */
    public function setWhereGroup($sGroup, $sField, $mRestriction, $sOperator = '=')
    {
        $sField = cString::toLowerCase($sField);
        $this->_where['groups'][$sGroup][$sField]['operator'] = $sOperator;
        $this->_where['groups'][$sGroup][$sField]['restriction'] = $mRestriction;
    }

    /**
     * Removes a previous set groupable WHERE clause, see
     * {@see ItemCollection::setWhereGroup}.
     *
     * @param string $sGroup
     * @param string $sField
     * @param mixed $mRestriction
     * @param string $sOperator [optional]
     */
    public function deleteWhereGroup($sGroup, $sField, $mRestriction, $sOperator = '=')
    {
        $sField = cString::toLowerCase($sField);
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
     * @param string $sCondition [optional]
     */
    public function setInnerGroupCondition($sGroup, $sCondition = 'AND')
    {
        $this->_innerGroupConditions[$sGroup] = $sCondition;
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
     * @return string
     *         With all where statements
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
                $sOperator = 'AND';
                if (isset($this->_innerGroupConditions[$groupName])) {
                    $sOperator = $this->_innerGroupConditions[$groupName];
                }

                $aGroupWhere[$groupName] = implode(' ' . $sOperator . ' ', $aWheres);
            }
        }

        // Combine groups
        $sGroupWhereStatement = '';
        $mLastGroup = false;
        foreach ($aGroupWhere as $groupName => $group) {
            if ($mLastGroup !== false) {
                $sOperator = 'AND';
                // Check if there's a group condition
                if (isset($this->_groupConditions[$groupName])) {
                    if (isset($this->_groupConditions[$groupName][$mLastGroup])) {
                        $sOperator = $this->_groupConditions[$groupName][$mLastGroup];
                    }
                }

                // Reverse check
                if (isset($this->_groupConditions[$mLastGroup])) {
                    if (isset($this->_groupConditions[$mLastGroup][$groupName])) {
                        $sOperator = $this->_groupConditions[$mLastGroup][$groupName];
                    }
                }

                $sGroupWhereStatement .= ' ' . $sOperator . ' (' . $group . ')';
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
     * @return string
     *         With all where statements
     */
    protected function _buildWhereStatements()
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
     * @return array
     *         Array structure, see above
     * @throws cException
     *         if no join partner could be found
     */
    protected function _fetchJoinTables()
    {
        $aParameters = [];
        $aFields = [];
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
                throw new cUnexpectedValueException("The join partner '" . get_class($this) . "' is not registered and can not be used with link().");
            }
        }

        // Add this class
        $aFields[] = cString::toLowerCase(cString::toLowerCase(get_class($this))) . '.' . $this->getPrimaryKeyName();

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

        return [
            'fields' => $aFields,
            'tables' => $aTables,
            'joins' => $aJoins,
            'wheres' => $aWheres
        ];
    }

    /**
     * Resolves links (class names of joined partners)
     *
     * @return array
     */
    protected function _resolveLinks()
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
     * @return bool
     * @throws cException
     *         if no item class has been set
     */
    public function query()
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
            $iRowStart = $this->_limitStart;
            $iRowCount = $this->_limitCount;
            $aStatement[] = "LIMIT $iRowStart, $iRowCount";
        }

        $sql = implode(' ', $aStatement);

        $result = $this->db->query($sql);
        $this->_lastSQL = $sql;
        // @todo disable all mode in this method for the moment. It has to be
        // verified, if enabling will result in negative side effects.
        $this->_bAllMode = false;

        return (bool) $result;
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
     *
     * @param string $sField
     */
    public function addResultField($sField)
    {
        $sField = cString::toLowerCase($sField);
        if (!in_array($sField, $this->_resultFields)) {
            $this->_resultFields[] = $sField;
        }
    }

    /**
     * Adds multiple result fields
     *
     * @since CONTENIDO 4.10.2
     * @param string[] $aFields
     */
    public function addResultFields(array $aFields)
    {
        foreach ($aFields as $field) {
            $this->addResultField($field);
        }
    }

    /**
     * Removes existing result field
     *
     * @param string $sField
     */
    public function removeResultField(string $sField)
    {
        $sField = cString::toLowerCase($sField);
        $key = array_search($sField, $this->_resultFields);
        if ($key !== false) {
            unset($this->_resultFields[$key]);
        }
    }

    /**
     * Removes multiple result fields
     *
     * @since CONTENIDO 4.10.2
     * @param string[] $aFields
     */
    public function removeResultFields(array $aFields)
    {
        foreach ($aFields as $field) {
            $this->removeResultField($field);
        }
    }

    /**
     * Returns reverse join partner.
     *
     * @param string $sParentClass
     * @param string $sClassName
     * @return array|bool  List of join partner structures or false.
     */
    protected function _findReverseJoinPartner($sParentClass, $sClassName)
    {
        // Check if we found a direct link
        if (in_array(cString::toLowerCase($sClassName), $this->_JoinPartners)) {
            $obj = new $sClassName();

            return [
                'desttable' => $obj->table,
                'destclass' => cString::toLowerCase($sClassName),
                'sourceclass' => cString::toLowerCase($sParentClass),
                'key' => $this->_getReverseJoinPartnerKey($obj, $sClassName)
            ];
        } else {
            // Recurse all items
            foreach ($this->_JoinPartners as $join => $tmpClassname) {
                $obj = new $tmpClassname();
                $status = $obj->_findReverseJoinPartner($tmpClassname, $sClassName);

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
                        'sourceclass' => cString::toLowerCase($sParentClass),
                        'key' => $this->_getReverseJoinPartnerKey($obj, $sClassName)
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
     * @param  Item|object  $joinPartnerObj  Join partner instance
     * @param  string  $sClassName  Join partner class name
     *
     * @return  string  Join partner key (table field)
     */
    protected function _getReverseJoinPartnerKey($joinPartnerObj, $sClassName)
    {
        if (!empty($this->_linkFields[$sClassName])) {
            return $this->_linkFields[$sClassName];
        } else {
            return $joinPartnerObj->getPrimaryKeyName();
        }
    }

    /**
     * Selects all entries from the database.
     * Objects are loaded using their primary key.
     *
     * @param string $sWhere   [optional]
     *                         Specifies the WHERE clause.
     * @param string $sGroupBy [optional]
     *                         Specifies the GROUP BY clause.
     * @param string $sOrderBy [optional]
     *                         Specifies the ORDER BY clause.
     * @param string $sLimit   [optional]
     *                         Specifies the LIMIT clause.
     *
     * @return bool
     *         True on success, otherwise false
     * @throws cDbException
     */
    public function select($sWhere = '', $sGroupBy = '', $sOrderBy = '', $sLimit = '')
    {
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

        $sFields = ($this->_settings['select_all_mode']) ? '*' : $this->getPrimaryKeyName();
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
     * @param string $sDistinct [optional]
     *                          Specifies if distinct will be added to the SQL statement
     *                          ($sDistinct !== '' -> DISTINCT)
     * @param string $sFrom     [optional]
     *                          Specifies the additional FROM clause (e.g.
     *                          'con_news_groups AS groups, con_news_groupmembers AS groupmembers').
     * @param string $sWhere    [optional]
     *                          Specifies the WHERE clause.
     * @param string $sGroupBy  [optional]
     *                          Specifies the GROUP BY clause.
     * @param string $sOrderBy  [optional]
     *                          Specifies the ORDER BY clause.
     * @param string $sLimit    [optional]
     *                          Specifies the LIMIT clause.
     * @return bool
     *                          True on success, otherwise false
     * @throws cDbException
     */
    public function flexSelect(
        $sDistinct = '', $sFrom = '', $sWhere = '', $sGroupBy = '', $sOrderBy = '', $sLimit = ''
    )
    {
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

        $tableNameAlias = cString::toLowerCase(get_class($this));
        $primaryKey = $this->getPrimaryKeyName();

        $sql = 'SELECT ' . $sDistinct . $tableNameAlias . '.' . $primaryKey . ' AS ' . $primaryKey
            . ' FROM `' . $this->table . '` AS ' . $tableNameAlias . $sFrom . $sWhere . $sGroupBy . $sOrderBy . $sLimit;

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
     * Checks if a specific record exists.
     *
     * @param mixed $mId
     *         The id to check for (could be numeric or string)
     *
     * @return bool
     *         True if object exists, false if not
     * @throws cDbException|cInvalidArgumentException
     */
    public function exists($mId)
    {
        $oDb = $this->_getSecondDBInstance();
        $sql = "SELECT `%s` FROM `%s` WHERE `%s` = '%s'";
        $oDb->query($sql, $this->getPrimaryKeyName(), $this->table, $this->getPrimaryKeyName(), $mId);
        return $oDb->nextRecord();
    }

    /**
     * Advances to the next item in the database.
     *
     * @return Item|object|bool
     *         next object, or false if no more objects
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
     * Fetches the result set related to current loaded primary key as an object.
     *
     * @param string $sClassName
     * @return Item|object
     * @throws cException
     */
    public function fetchObject($sClassName)
    {
        $sKey = cString::toLowerCase($sClassName);

        if (empty($this->_collectionCache[$sKey]) || !is_object($this->_collectionCache[$sKey])) {
            $this->_collectionCache[$sKey] = new $sClassName();
        }
        /* @var $obj ItemCollection */
        $obj = $this->_collectionCache[$sKey];
        return $obj->loadItem($this->db->f($obj->getPrimaryKeyName()));
    }

    /**
     * Fetches the result of a previous run query (e.g. `$obj->query()`) into a
     * desired result list.
     *
     * @param array $aFields  [optional] Array of fields to fetch from the result.
     *     If it is an indexed array, the value will be used for the field, and
     *     the result entries will be also an indexed array.
     *     <pre>
     *     // Parameter `$aFields` as indexed array
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
     *     // Parameter `$aFields` as associative array
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
     * @param array $aClassNames [optional] Array of class names, which extends
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
    public function fetchTable(array $aFields = [], array $aClassNames = [])
    {
        if ($this->count() <= 0) {
            return [];
        }

        $this->db->seek(0);

        $aTable = [];
        $row = 1;
        while ($this->db->nextRecord()) {
            foreach ($aFields as $alias => $field) {
                if ($alias != '') {
                    $aTable[$row][$alias] = $this->db->f($field);
                } else {
                    $aTable[$row][$field] = $this->db->f($field);
                }
            }

            // Fetch objects
            foreach ($aClassNames as $alias => $object) {
                if ($alias != '') {
                    if (isset($aTable[$row][$alias])) {
                        // Is set, check for array. If no array, create one
                        if (!is_array($aTable[$row][$alias])) {
                            $aTable[$row][$alias] = [];
                        }
                        $aTable[$row][$alias][] = $this->fetchObject($object);
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

        return $aTable;
    }

    /**
     * Returns an array of arrays
     *
     * @param array $aClassNames
     *         With the correct order of the class names which extend from
     *         {@see Item}
     * @return array
     *         Result
     * @throws cDbException|cException
     */
    public function queryAndFetchStructured(array $aClassNames)
    {
        $aOrder = [];
        $aFetchObjects = [];

        foreach ($aClassNames as $object) {
            $x = new $object();
            $object = cString::toLowerCase($object);
            $aOrder[] = $object . '.' . $x->getPrimaryKeyName() . ' ASC';
            $aFetchObjects[] = $x;
        }

        $this->setOrder(implode(', ', $aOrder));
        $this->query();

        $this->db->seek(0);

        $aResult = [];
        while ($this->db->nextRecord()) {
            $aResult = $this->_recursiveStructuredFetch($aFetchObjects, $aResult);
        }

        return $aResult;
    }

    /**
     * Loops through the current result of the last run query, and collects the
     * result recursively by instantiating the proper Item object for each entry
     * in `$aObjects` parameter.
     *
     * @param Item[]|object[] $aObjects List of objects which extend from {@see Item}.
     * @param array $aResult Array of results where the key is the primary key
     *    of the object and the value is an associative structure, e.g.
     *    <pre>
     *    $aResult = [
     *        '123' => [
     *            'class' => (string) Class name in lower-case
     *            'object' => (Item|object) The object instance
     *            'items' => (Item[]|object[]|null) Recursive structure
     *        ],
     *        ...
     *    ];
     *    </pre>
     *
     * @return array The passed array being updated within the function.
     */
    protected function _recursiveStructuredFetch(array $aObjects, array $aResult)
    {
        $i = array_shift($aObjects);

        $value = $this->db->f($i->getPrimaryKeyName());

        if (!is_null($value)) {
            $aResult[$value]['class'] = cString::toLowerCase(get_class($i));
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
    public function count()
    {
        return $this->db->numRows();
    }

    /**
     * Loads a single record by its id.
     *
     * @param string|int $id
     *         The primary key of the item to load.
     * @return Item|object
     *         The loaded item
     * @throws cException
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
     * @param mixed $mItem
     *         The primary key of the item to load or a recordset with item data
     *         (array) to inject to the item object.
     * @return Item|object
     *         The newly created object
     * @throws cException
     *         If item class is not set
     */
    public function loadItem($mItem)
    {
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
     * @param string|array $data [optional]
     *                           optional parameter for direct input of primary key value
     *                           (string) or multiple column name - value pairs
     *
     * @return Item|object
     *                           The newly created object
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
     * @param Item|object $srcItem
     *                                  Source Item instance to copy
     * @param array  $fieldsToOverwrite [optional]
     *                                  Associative list of fields to overwrite.
     * @return Item|object|NULL
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException If Item class doesn't match the defined _itemClass property
     *                                  or passed Item instance has no loaded recordset
     */
    public function copyItem($srcItem, array $fieldsToOverwrite = [])
    {
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
     * Returns all ids of the records in the table that match the criteria
     * in the passed WHERE clause.
     *
     * @param string $sWhere
     *         The WHERE clause of the SQL statement
     *
     * @return array
     *         List of ids
     * @throws cDbException|cInvalidArgumentException
     */
    public function getIdsByWhereClause($sWhere)
    {
        $oDb = $this->_getSecondDBInstance();

        $aIds = [];

        // Get all ids
        $sql = 'SELECT `' . $this->getPrimaryKeyName() . '` AS `pk` FROM `' . $this->table . '` WHERE ' . $sWhere;
        $oDb->query($sql);
        while ($oDb->nextRecord()) {
            $aIds[] = $oDb->f('pk');
        }

        return $aIds;
    }

    /**
     * Returns all ids of the records in the table that match the criteria
     * in the passed WHERE clause ($field $operator $value).
     *
     * @since CONTENIDO 4.10.2
     * @param string $field
     *         The table field name
     * @param string|int|null|mixed $value
     *         The value
     * @param string $operator
     *         The operator to use (e.g. '=', '>', '<', 'IN', etc.)
     * @return int[]|string[]
     *         List of ids
     * @throws cDbException|cInvalidArgumentException
     */
    public function getIdsWhere(string $field, $value, string $operator = '='): array
    {
        // Build WHERE clause
        $sWhere = $this->_driver->buildOperator($field, $operator, $value);

        // Return the data
        return $this->getIdsByWhereClause($sWhere);
    }

    /**
     * Returns all specified fields of the records in the table that match
     * the criteria in the passed WHERE clause.
     *
     * @param array  $aFields
     *         List of fields to get
     * @param string $sWhere
     *         The WHERE clause of the SQL statement
     *
     * @return array
     *         List of entries with specified fields
     * @throws cDbException|cInvalidArgumentException
     */
    public function getFieldsByWhereClause(array $aFields, $sWhere)
    {
        $oDb = $this->_getSecondDBInstance();

        $aEntries = [];

        if (count($aFields) == 0) {
            return $aEntries;
        }

        // Escape fields
        $aEscapedFields = array_map([
            $oDb,
            'escape'
        ], $aFields);
        $fields = '`' . implode('`, `', $aEscapedFields) . '`';

        // Get all fields
        $sql = 'SELECT ' . $fields . ' FROM `' . $this->table . '` WHERE ' . $sWhere;
        $oDb->query($sql);
        while ($oDb->nextRecord()) {
            $data = [];
            foreach ($aFields as $field) {
                $data[$field] = $oDb->f($field);
            }
            $aEntries[] = $data;
        }

        return $aEntries;
    }

    /**
     * Returns all specified fields of the records in the table that match
     * the criteria in the passed WHERE clause ($field $operator $value).
     *
     * @since CONTENIDO 4.10.2
     * @param array  $aFields
     *         List of fields to get
     * @param string $field
     *         The table field name to query
     * @param string|int|null|mixed $value
     *         The value to query
     * @param string $operator
     *         The operator to use (e.g. '=', '>', '<', 'IN', etc.)
     *
     * @return int[]|string[]
     *         List of ids
     * @throws cDbException|cInvalidArgumentException
     */
    public function getFieldsWhere(
        array $aFields, string $field, $value, string $operator = '='
    ): array
    {
        // Build WHERE clause
        $sWhere = $this->_driver->buildOperator($field, $operator, $value);

        // Return the data
        return $this->getFieldsByWhereClause($aFields, $sWhere);
    }

    /**
     * Returns all ids of records in the table.
     *
     * @return array
     *         List of ids
     * @throws cDbException|cInvalidArgumentException
     */
    public function getAllIds() {
        $oDb = $this->_getSecondDBInstance();

        $aIds = [];

        // Get all ids
        $sql = 'SELECT `' . $this->getPrimaryKeyName() . '` AS `pk` FROM `' . $this->table . '`';
        $oDb->query($sql);
        while ($oDb->nextRecord()) {
            $aIds[] = $oDb->f('pk');
        }

        return $aIds;
    }

    /**
     * Deletes the record with id from the table.
     * Deletes also the cached record and any existing properties.
     *
     * @param mixed $mId
     *         Id of record to delete
     *
     * @return bool
     *
     * @throws cDbException|cInvalidArgumentException
     */
    public function delete($mId)
    {
        return $this->_delete($mId);
    }

    /**
     * Deletes all records in the table that match the criteria in the
     * passed WHERE clause.
     * Deletes also the cached records and any existing properties.
     *
     * @param string $sWhere
     *         The WHERE clause of the SQL statement
     *
     * @return int
     *         Number of deleted records
     * @throws cDbException|cInvalidArgumentException
     */
    public function deleteByWhereClause($sWhere)
    {
        // Get all ids and delete related entries
        $aIds = $this->getIdsByWhereClause($sWhere);

        if (!is_array($aIds) || 0 >= count($aIds)) {
            return 0;
        }

        return $this->_deleteMultiple($aIds);
    }

    /**
     * Deletes all records in the table that match the criteria field,
     * and its value (field = value).
     * Deletes also the cached records and any existing properties.
     *
     * @param string $sField
     *         The field name
     * @param mixed  $mValue
     *         The value of the field
     *
     * @return int
     *         Number of deleted records
     *
     * @throws cDbException|cInvalidArgumentException
     */
    public function deleteBy($sField, $mValue)
    {
        $where = (is_string($mValue)) ? "`%s` = '%s'" : "`%s` = %d";
        $where = $this->db->prepare($where, $sField, $mValue);

        return $this->deleteByWhereClause($where);
    }

    /**
     * Deletes a record from the table, deletes also the cached record
     * and any of its existing properties.
     *
     * @param mixed $mId
     *         Id of record to delete
     * @return bool
     *
     * @throws cDbException|cInvalidArgumentException
     */
    protected function _delete($mId)
    {
        $this->_executeCallbacks(self::DELETE_BEFORE, $this->_itemClass, [
            $mId
        ]);

        $oDb = $this->_getSecondDBInstance();

        // Delete the database record
        $sql = "DELETE FROM `%s` WHERE `%s` = '%s'";
        $oDb->query($sql, $this->table, $this->getPrimaryKeyName(), $mId);
        $success = $oDb->affectedRows();

        // Delete the cached record
        $this->_oCache->removeItem($mId);

        // Delete any existing property values
        $oProperties = $this->_getPropertiesCollectionInstance();
        $oProperties->deleteProperties($this->getPrimaryKeyName(), $mId);

        if ($success == 0) {
            $this->_executeCallbacks(self::DELETE_FAILURE, $this->_itemClass, [
                $mId
            ]);
            return false;
        } else {
            $this->_executeCallbacks(self::DELETE_SUCCESS, $this->_itemClass, [
                $mId
            ]);
            return true;
        }
    }

    /**
     * Deletes all records with the passed ids from the table, deletes also
     * the cached records and any of their existing properties.
     *
     * @param int[]|string[] $aIds
     *         Id of records to delete
     *
     * @return int
     *         Number of affected records
     * @throws cDbException|cInvalidArgumentException
     */
    protected function _deleteMultiple(array $aIds)
    {
        foreach ($aIds as $mId) {
            $this->_executeCallbacks(self::DELETE_BEFORE, $this->_itemClass, [
                $mId
            ]);
        }

        $oDb = $this->_getSecondDBInstance();

        // Delete multiple database records at once
        $aEscapedIds = array_map([
            $oDb,
            'escape'
        ], $aIds);
        $in = "'" . implode("', '", $aEscapedIds) . "'";
        $sql = "DELETE FROM `%s` WHERE `%s` IN (" . $in . ")";
        $oDb->query($sql, $this->table, $this->getPrimaryKeyName());
        $numAffected = $oDb->affectedRows();

        // Delete the cached records
        $this->_oCache->removeItems($aIds);

        // Delete any existing property values of the records
        $oProperties = $this->_getPropertiesCollectionInstance();
        $oProperties->deletePropertiesMultiple($this->getPrimaryKeyName(), $aIds);

        // NOTE: Deleting multiple entries at once has a drawback. There is no
        // way to detect faulty ids, if one or more entries couldn't be deleted.
        if ($numAffected == 0) {
            foreach ($aIds as $mId) {
                $this->_executeCallbacks(self::DELETE_FAILURE, $this->_itemClass, [
                    $mId
                ]);
            }
        } else {
            foreach ($aIds as $mId) {
                $this->_executeCallbacks(self::DELETE_SUCCESS, $this->_itemClass, [
                    $mId
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
     * @param string $sKey
     *         Name of the field to use for the key
     * @param string|string[]  $mFields
     *         String or array
     * @return array
     *         Resulting array
     * @throws cDbException|cException
     */
    public function fetchArray($sKey, $mFields)
    {
        $aResult = [];

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

    /**
     * Tries to detect the global encoding for current language and returns it.
     * Stores the detected encoding in cache property, tp prevent further
     * detection trials in future usages.
     *
     * @return string|null
     */
    private static function _getGlobalEncoding()
    {
        $lang = cSecurity::toInteger(cRegistry::getLanguageId());
        if (!isset(self::$_globalEncoding[$lang])) {
            $encodings = $GLOBALS['aLanguageEncodings'] ?? [];
            if ($lang > 0 && is_array($encodings) && isset($encodings[$lang])) {
                self::$_globalEncoding[$lang] = cSecurity::toString($encodings[$lang]);
            }
        }

        return self::$_globalEncoding[$lang] ?? null;
    }

}
