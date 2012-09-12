<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Generic database abstraction functions
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.2
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 3003-07-18
 *   modified 2008-07-02, Frederic Schneider, add security fix
 *   modified 2010-05-20, Murat Purc, Removed check of $_REQUEST['cfg'] during processing ticket [#CON-307]
 *
 *   $Id: class.genericdb.php 1157 2010-05-20 14:10:43Z xmurrix $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("classes", "class.properties.php");

/* Try to load GenericDB database driver */
$driver_filename = $cfg["path"]["contenido"].$cfg["path"]["classes"]."drivers/".$cfg['sql']['gdb_driver']."/class.gdb.".$cfg['sql']['gdb_driver'].".php";

if (file_exists($driver_filename))
{
	include_once ($driver_filename);
}

/**
 * Class ItemCollection
 * Class for database based item collections
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright four for business 2003
 */
class ItemCollection
{
	/**
	 * Storage of the source table to use for the information
	 * @var string Contains the source table
	 * @access private
	 */
	var $table;

	/**
	 * Storage of the primary key
	 * @var string Contains the primary key of the source table
	 * @access private
	 */
	var $primaryKey;

	/**
	 * DB_Contenido instance
	 * @var object Contains the database object
	 * @access private
	 */
	var $db;

	/**
	 * Storage of the last error
	 * @var string Contains the error string of the last error occured
	 * @access private
	 */
	var $lasterror;

	/**
	 * Storage of all result items
	 * @var string Contains all result items
	 * @access private
	 */
	var $objects;

	/**
	 * Cache the result items
	 * @var array Contains all cache items
	 * @access private
	 */
	var $cache;

	/**
	 * @var int Lifetime in seconds
	 * @access private
	 */
	var $lifetime;

	/**
	 * @var string Single item class
	 * @access private
	 */
	var $_itemClass;

	/**
	 * @var object Iterator object for the next() method
	 * @access private
	 */
	var $_iteratorItem;

	/**
	 * @var array Reverse join partners for this data object
	 * @access private
	 */
	var $_JoinPartners;

	/**
	 * @var array Forward join partners for this data object
	 * @access private
	 */
	var $_forwardJoinPartners;

	/**
	 * @var array Where restrictions for the query
	 * @access private
	 */
	var $_whereRestriction;

	/**
	 * @var array Inner group conditions
	 * @access private
	 */
	var $_innerGroupConditions = array ();

	/**
	 * @var array Group conditions
	 * @access private
	 */
	var $_groupConditions;

	/**
	 * @var array Result fields for the query
	 * @access private
	 */
	var $_resultFields = array ();

	/**
	 * @var array Property collection
	 * @access private
	 */
	var $properties;

	/**
	 * @var array Is entry virgin?
	 * @access private
	 */
	var $virgin;
	
	/**
	 * @var string Encoding
	 * @access private
	 */
	var $_encoding;	

	/**
	 * @var _aOperators
	 * @access private
	 * 
	 * Stores all operators which are supported by GenericDB
	 * Unsupported operators are passed trough as-is.
	 */
	var $_aOperators;
	
	
	/**
	 * Constructor Function
	 * Note: Default lifetime is 10 seconds.
	 * @param string $table The table to use as information source
	 */
	function ItemCollection($table, $primaryKey, $lifetime = 10)
	{
		global $cfg, $contenido;

		$this->db = new DB_Contenido;

		if ($table == "")
		{
			$classname = get_parent_class($this);
			die("Class $classname: No table specified. Inherited classes *need* to set a table");

		}

		if ($primaryKey == "")
		{
			die("No primary key specified. Inherited classes *need* to set a primary key");
		}

		$this->table = $table;
		$this->primaryKey = $primaryKey;
		$this->virgin = true;
		$this->lifetime = $lifetime;

		$this->resetQuery();

		/* Try to load driver */

		$this->_initializeDriver();
		
		/* Try to find out the current encoding */
		if (isset($GLOBALS["lang"]) && isset($GLOBALS["aLanguageEncodings"]))
		{
			$this->setEncoding($GLOBALS["aLanguageEncodings"][$GLOBALS["lang"]]);
		}
		
		$this->_aOperators = array(	"=",
									"!=",
									"<>",
									"<",
									">",
									"<=",
									">=",
									"LIKE",
									"DIACRITICS");
	}

	/**
	 * _setJoinPartner: Defines the reverse links for this table.
	 * 
	 * Important: The class specified by $foreignCollectionClass needs to be a collection class and has to exist
	 *            Define all links in the constructor of your object
	 *
	 * @param string $foreignCollectionClass Specifies the foreign class to use
	 * @return none
	 */
	function _setJoinPartner($foreignCollectionClass)
	{
		if (class_exists($foreignCollectionClass))
		{
			/* Add class */
			$this->_JoinPartners[] = strtolower($foreignCollectionClass);

			/* Remove duplicates */
			$this->_JoinPartners = array_unique($this->_JoinPartners);
		} else
		{
			cWarning(__FILE__, __LINE__, "Could not instanciate class [$foreignCollectionClass] for use with _setJoinPartner in class ".get_class($this));
		}
	}

	/**
	 * _setItemClass: private method to set the accompanying item object.
	 * 
	 * @param string $classname specifies the classname
	 * @return none
	 */
	function _setItemClass($classname)
	{
		if (class_exists($classname))
		{
			$this->_itemClass = $classname;
			$this->_itemClassInstance = new $classname;
			
			/* Initialize driver in case the developer does a setItemClass-Call before calling the parent constructor */
			$this->_initializeDriver();
			$this->_driver->setItemClassInstance($this->_itemClassInstance);
		} else
		{
			cWarning(__FILE__, __LINE__, "Could not instanciate class [$classname] for use with _setItemClass in class ".get_class($this));
		}
	}
	
	/**
	 * _initializeDriver: Initializes the driver to use with GenericDB.
	 * 
	 * @param $bForceInit boolean If true, forces the driver to initialize, even if it already exists.
	 */
	function _initializeDriver ($bForceInit = false)
	{
		if (!is_object($this->_driver) || $bForceInit == true)
		{
			$this->_driver = new gdbMySQL();	
		}
	}

	function setEncoding ($sEncoding)
	{
		$this->_encoding = $sEncoding;
		$this->_driver->setEncoding($sEncoding);	
	}
	
	/**
	 * sets the query to use foreign tables in the resultset
	 * 
	 */
	function link($foreignClass)
	{
		if (class_exists($foreignClass))
		{
			$this->_links[$foreignClass] = new $foreignClass;
		} else
		{
			cWarning(__FILE__, __LINE__, "Could not find class [$foreignClass] for use with link in class ".get_class($this));
		}
	}

	function setLimit($iRowStart, $iRowCount)
	{
		$this->_limitStart = $iRowStart;
		$this->_limitCount = $iRowCount;
	}

	/**
	 * setWhere ($field, $restriction, $operator)
	 *
	 * Restricts a query with a where clause 
	 */
	function setWhere($field, $restriction, $operator = "=")
	{
		$field = strtolower($field);

		$this->_where["global"][$field]["operator"] = $operator;
		$this->_where["global"][$field]["restriction"] = $restriction;
	}

	/**
	 * deleteWhere ($field, $restriction, $operator)
	 *
	 * Restricts a query with a where clause 
	 */
	function deleteWhere($field, $restriction, $operator = "=")
	{
		$field = strtolower($field);

		if (is_array($this->_where["global"]) && array_key_exists($field, $this->_where["global"]) && is_array($this->_where["global"][$field]))
		{
			if ($this->_where["global"][$field]["operator"] == $operator && $this->_where["global"][$field]["restriction"] == $restriction)
			{
				unset ($this->_where["global"][$field]);
			}
		}
	}

	/**
	 * setWhereGroup ($group, $field, $restriction, $operator)
	 *
	 * Restricts a query with a where clause, groupable
	 */
	function setWhereGroup($group, $field, $restriction, $operator = "=")
	{
		$field = strtolower($field);

		$this->_where["groups"][$group][$field]["operator"] = $operator;
		$this->_where["groups"][$group][$field]["restriction"] = $restriction;
	}

	/**
	 * deleteWhereGroup ($group, $field, $restriction, $operator)
	 *
	 * Restricts a query with a where clause, groupable
	 */
	function deleteWhereGroup($group, $field, $restriction, $operator = "=")
	{
		$field = strtolower($field);

		if (is_array($this->_where["groups"][$group]) && array_key_exists($field, $this->_where["groups"][$group]) && is_array($this->_where["groups"][$group][$field]))
		{
			if ($this->_where["groups"][$group][$field]["operator"] == $operator && $this->_where["groups"][$group][$field]["restriction"] == $restriction)
			{
				unset ($this->_where["groups"][$group][$field]);
			}
		}
	}

	/**
	 * setInnerGroupCondition ($group, $condition)
	 *
	 * Defines how relations in one group are linked each together
	 */
	function setInnerGroupCondition($group, $condition = "AND")
	{
		$this->_innerGroupConditions[$group] = $condition;
	}

	/**
	 * setGroupCondition ($group1, $group2, $condition)
	 *
	 * Defines how groups are linked to each other
	 */
	function setGroupCondition($group1, $group2, $condition = "AND")
	{
		$this->_groupConditions[$group1][$group2] = $condition;
	}

	/**
	 * _buildGroupWhereStatements ()
	 *
	 * Builds a where statement out of the setGroupWhere calls
	 *
	 * @param none
	 * @return array with all where statements
	 */
	function _buildGroupWhereStatements()
	{
		$wheres = array ();
		$groupwhere = array ();

		$lastgroup = false;
		$groupwherestatement = "";

		/* Find out if there are any defined groups */
		if (count($this->_where["groups"]) > 0)
		{
			/* Step trough all groups */
			foreach ($this->_where["groups"] as $groupname => $group)
			{
				$wheres = array ();

				/* Fetch restriction, fields and operators and build single group where statements */
				foreach ($group as $field => $item)
				{
					$wheres[] = $this->_driver->buildOperator($field, $item["operator"], $item["restriction"]);
				}

				/* Add completed substatements */
				$operator = 'AND';
				if (array_key_exists($groupname, $this->_innerGroupConditions))
				{
					$operator = $this->_innerGroupConditions[$groupname];
				}

				$groupwhere[$groupname] = implode(" ".$operator." ", $wheres);
			}

		}

		/* Combine groups */
		foreach ($groupwhere as $groupname => $group)
		{
			if ($lastgroup != false)
			{
				$operator = "AND";
				/* Check if there's a group condition */
				if (array_key_exists($groupname, $this->_groupConditions))
				{
					if (array_key_exists($lastgroup, $this->_groupConditions[$groupname]))
					{
						$operator = $this->_groupConditions[$groupname][$lastgroup];
					}
				}

				/* Reverse check */
				if (array_key_exists($lastgroup, $this->_groupConditions))
				{
					if (array_key_exists($groupname, $this->_groupConditions[$lastgroup]))
					{
						$operator = $this->_groupConditions[$lastgroup][$groupname];
					}
				}

				$groupwherestatement .= " ".$operator." (".$group.")";
			} else
			{
				$groupwherestatement .= "(".$group.")";
			}

			$lastgroup = $groupname;
		}

		return ($groupwherestatement);

	}

	/**
	 * _buildWhereStatements ()
	 *
	 * Builds a where statement out of the setWhere calls
	 *
	 * @param none
	 * @return array with all where statements
	 */
	function _buildWhereStatements()
	{
		$wheres = array ();

		/* Build global where condition */
		foreach ($this->_where["global"] as $field => $item)
		{
			$wheres[] = $this->_driver->buildOperator($field, $item["operator"], $item["restriction"]);
		}

		return (implode(" AND ", $wheres));
	}

	/**
	 * _fetchJoinTables ()
	 *
	 * Fetches all tables which will be joined later on.
	 *
	 * The returned array has the following format:
	 * 
	 * array(
	 *       array(fields),
	 *       array(tables),
	 *		 array(joins),
	 *       array(wheres)
	 *      );
	 *
	 * Notes:
	 * The table is the table name which needs to be added to the FROM clause
	 * The join statement which is inserted after the master table
	 * The where statement is combined with all other where statements
	 * The fields to select from
	 *
	 * @param none
	 * @return array see above
	 */
	function _fetchJoinTables($ignore_root)
	{
		$parameters = array ();
		$fields = array ();
		$tables = array ();
		$joins = array ();
		$wheres = array ();

		/* Fetch linked tables */
		foreach ($this->_links as $link => $object)
		{
			$matches = $this->_findReverseJoinPartner(strtolower(get_class($this)), $link);

			if ($matches !== false)
			{
				if (array_key_exists("desttable", $matches))
				{
					/* Driver function: Build query parts */
					$parameters[] = $this->_driver->buildJoinQuery($matches["desttable"], strtolower($matches["destclass"]), $matches["key"], strtolower($matches["sourceclass"]), $matches["key"]);

				} else
				{
					foreach ($matches as $match)
					{
						$parameters[] = $this->_driver->buildJoinQuery($match["desttable"], strtolower($match["destclass"]), $match["key"], strtolower($match["sourceclass"]), $match["key"]);
					}
				}
			} else
			{
				/* Try forward search */
				$mobject = new $link;
                
				$matches = $mobject->_findReverseJoinPartner($link, strtolower(get_class($this)));

				if ($matches !== false)
				{
					if (array_key_exists("desttable", $matches))
					{
						$i = $this->_driver->buildJoinQuery($mobject->table, strtolower($link), $mobject->primaryKey, strtolower($matches["destclass"]), $matches["key"]);

						if ($i["field"] == ($link.".".$mobject->primaryKey) && $link == $ignore_root)
						{
							unset ($i["join"]);
						}
						$parameters[] = $i;
					} else
					{
						foreach ($matches as $match)
						{

							$xobject = new $match["sourceclass"];

							$i = $this->_driver->buildJoinQuery($xobject->table, strtolower($match["sourceclass"]), $xobject->primaryKey, strtolower($match["destclass"]), $match["key"]);

							if ($i["field"] == ($match["sourceclass"].".".$xobject->primaryKey) && $match["sourceclass"] == $ignore_root)
							{
								unset ($i["join"]);
							}
							array_unshift($parameters, $i);
						}
					}

				} else
				{
                    $bDualSearch = true;
                    /* Check first if we are a instance of another class */
                    foreach ($mobject->_JoinPartners as $sJoinPartner)
                    {
                        if (class_exists($sJoinPartner))
                        {
                            if (is_subclass_of($this, $sJoinPartner))
                            {
                                $matches = $mobject->_findReverseJoinPartner($link, strtolower($sJoinPartner));
                                

                                if ($matches !== false)
                                {
                                    if ($matches["destclass"] == strtolower($sJoinPartner))
                                    {
                                        $matches["destclass"] = get_class($this);
                                
                                        if (array_key_exists("desttable", $matches))
                                        {
                                            $i = $this->_driver->buildJoinQuery($mobject->table, strtolower($link), $mobject->primaryKey, strtolower($matches["destclass"]), $matches["key"]);
                    
                                            if ($i["field"] == ($link.".".$mobject->primaryKey) && $link == $ignore_root)
                                            {
                                                unset ($i["join"]);
                                            }
                                            $parameters[] = $i;
                                        } else
                                        {
                                            foreach ($matches as $match)
                                            {
                    
                                                $xobject = new $match["sourceclass"];
                    
                                                $i = $this->_driver->buildJoinQuery($xobject->table, strtolower($match["sourceclass"]), $xobject->primaryKey, strtolower($match["destclass"]), $match["key"]);
                    
                                                if ($i["field"] == ($match["sourceclass"].".".$xobject->primaryKey) && $match["sourceclass"] == $ignore_root)
                                                {
                                                    unset ($i["join"]);
                                                }
                                                array_unshift($parameters, $i);
                                            }
                                        }
                                        $bDualSearch = false;
                                    }
                                }
                                
                                
                            }
                        }
                    }
                    
                    if ($bDualSearch)
                    {
                        /* Try dual-side search */
                        $forward = $this->_resolveLinks();
                        $reverse = $mobject->_resolveLinks();
    
                        $result = array_intersect($forward, $reverse);
    
                        if (count($result) > 0)
                        {
                            /* Found an intersection, build references to it */
                            foreach ($result as $value)
                            {
    
                                $intersect_class = new $value;
                                $intersect_class->link(strtolower(get_class($this)));
                                $intersect_class->link(strtolower(get_class($mobject)));
    
                                $intersect_parameters = $intersect_class->_fetchJoinTables($ignore_root);
    
                                $fields = array_merge($intersect_parameters["fields"], $fields);
                                $tables = array_merge($intersect_parameters["tables"], $tables);
                                $joins = array_merge($intersect_parameters["joins"], $joins);
                                $wheres = array_merge($intersect_parameters["wheres"], $wheres);
    
                            }
                        } else
                        {
                            cWarning(__FILE__, __LINE__, "Could not find join partner for class [$link] in class ".get_class($this)." in neither forward nor reverse direction.");
                        }
                    }
				}
			}
		}

		/* Add this class */
		$fields[] = strtolower(strtolower(get_class($this))).".".$this->primaryKey;

		/* Make the parameters unique */
		foreach ($parameters as $parameter)
		{
			array_unshift($fields, $parameter["field"]);
			array_unshift($tables, $parameter["table"]);
			array_unshift($joins, $parameter["join"]);
			array_unshift($wheres, $parameter["where"]);
		}

		$fields = array_filter(array_unique($fields));
		$tables = array_filter(array_unique($tables));
		$joins = array_filter(array_unique($joins));
		$wheres = array_filter(array_unique($wheres));

		return (array ("fields" => $fields, "tables" => $tables, "joins" => $joins, "wheres" => $wheres));

	}

	function _resolveLinks()
	{
		$resolvedLinks[] = strtolower(get_class($this));

		foreach ($this->_JoinPartners as $link)
		{
			$class = new $link;
			$resolvedLinks = array_merge($class->_resolveLinks(), $resolvedLinks);
		}
		return ($resolvedLinks);
	}

	function resetQuery()
	{
		$this->setLimit(0, 0);

		$this->_JoinPartners = array ();
		$this->_forwardJoinPartners = array ();

		$this->_links = array ();
		$this->_where["global"] = array ();
		$this->_where["groups"] = array ();
		$this->_groupConditions = array ();

	}

	function query()
	{
		if (!isset ($this->_itemClassInstance))
		{
			cWarning(__FILE__, __LINE__, "GenericDB can't use query() if no item class is set via setItemClass");
		}

		$groupWhereStatements = $this->_buildGroupWhereStatements();
		$whereStatements = $this->_buildWhereStatements();
		$parameters = $this->_fetchJoinTables(strtolower(get_class($this)));

		$statement = array ("SELECT", implode(", ", (array_merge($parameters["fields"], $this->_resultFields))), "FROM", $this->table." AS ".strtolower(get_class($this)));

		if (count($parameters["tables"]) > 0)
		{
			$statement[] = implode(", ", $parameters["tables"]);
		}

		if (count($parameters["joins"]) > 0)
		{
			$statement[] = implode(" ", $parameters["joins"]);
		}

		$wheres = array ();

		if (count($parameters["wheres"]) > 0)
		{
			$wheres[] = implode(", ", $parameters["wheres"]);
		}

		if ($groupWhereStatements != "")
		{
			$wheres[] = $groupWhereStatements;
		}

		if ($whereStatements != "")
		{
			$wheres[] = $whereStatements;
		}

		if (count($wheres) > 0)
		{
			$statement[] = "WHERE ".implode(" AND ", $wheres);
		}

		if ($this->_order != "")
		{
			$statement[] = "ORDER BY ".$this->_order;
		}

		if ($this->_limitStart > 0 || $this->_limitCount > 0)
		{
			$iRowStart = intval($this->_limitStart);
			$iRowCount = intval($this->_limitCount);
			$statement[] = "LIMIT $iRowStart, $iRowCount";

		}

		$sql = implode(" ", $statement);

		$this->db->query($sql);
		$this->_lastSQL = $sql;
		$this->_mode = "automatic";

	}

	function setOrder($order)
	{
		$this->_order = strtolower($order);
	}

	function addResultField($field)
	{
		$this->_resultFields[] = strtolower($field);
		$this->_resultFields = array_unique($this->_resultFields);
	}

	function removeResultField($field)
	{
		$key = array_search($this->_resultFields);

		if ($key !== false)
		{
			unset ($this->_resultFields[$key]);
		}
	}

	function _findReverseJoinPartner($parentclass, $classname)
	{
		/* Make the parameters lowercase, as get_class is buggy */
		$classname = strtolower($classname);
		$parentclass = strtolower($parentclass);

		/* Check if we found a direct link */
		if (in_array($classname, $this->_JoinPartners))
		{
			$object = new $classname;
			return array ("desttable" => $object->table, "destclass" => $classname, "sourceclass" => $parentclass, "key" => $object->primaryKey);

		} else
		{
			/* Recurse all items */

			foreach ($this->_JoinPartners as $join => $tmp_classname)
			{
				$object = new $tmp_classname;
				$status = $object->_findReverseJoinPartner($tmp_classname, $classname);

				if (is_array($status))
				{
					$returns = array ();

					if (!array_key_exists("desttable", $status))
					{
						foreach ($status as $subitem)
						{
							$returns[] = $subitem;
						}
					} else
					{

						$returns[] = $status;
					}

					$object = new $tmp_classname;

					$returns[] = array ("desttable" => $object->table, "destclass" => $tmp_classname, "sourceclass" => $parentclass, "key" => $object->primaryKey);
					return ($returns);
				}
			}
		}

		return false;
	}

	/**
	 * select ($where = "", $group_by = "", $order_by = "", $limit = "")
	 * Selects all entries from the database and returns them as DBObject-objects
	 * to the user. Objects are loaded using their primary key.
	 * @param string $where Specifies the where clause.
	 * @param string $group_by Specifies the group by clause.
	 * @param string $order_by Specifies the order by clause.
	 * @param string $limit Specifies the limit by clause.
	 * @return array Array of DBObject-Objects
	 */
	function select($where = "", $group_by = "", $order_by = "", $limit = "")
	{
		unset ($this->objects);

		if ($where == "")
		{
			$where = "";
		} else
		{
			$where = " WHERE ".$where;
		}

		if ($group_by != "")
		{
			$group_by = " GROUP BY ".$group_by;
		}

		if ($order_by != "")
		{
			$order_by = " ORDER BY ".$order_by;
		}

		if ($limit != "")
		{
			$limit = " LIMIT ".$limit;
		}

		$sql = "SELECT ".$this->primaryKey." FROM ".$this->table.$where.$group_by.$order_by.$limit;
		$this->db->query($sql);
		$this->_lastSQL = $sql;
		$this->_mode = "manual";

		if ($this->db->num_rows() == 0)
		{
			return false;
		} else
		{

			/* Everything ok, do nothing for now */
		}
	}

	/**
		 *  flexSelect ($distinct = "", $from = "", $where = "", $group_by = "", $order_by = "", $limit = "")
	 *  Selects all entries from the database and returns them as DBObject-objects
	 * to the user. Objects are loaded using their primary key.
	 * @param string $distinct Specifies if distinct will be added to the SQL statement ($distinct !== "" -> DISTINCT)
	 * @param string $from     Specifies the additional from clause (e.g. "con_news_groups AS groups, con_news_groupmembers AS groupmembers").
	 * @param string $where    Specifies the where clause.
	 * @param string $group_by Specifies the group by clause.
	 * @param string $order_by Specifies the order by clause.
	 * @param string $limit    Specifies the limit by clause.
	 * @return array Array of DBObject-Objects
	 *
	 * @author HerrB
	 */
	function flexSelect($distinct = "", $from = "", $where = "", $group_by = "", $order_by = "", $limit = "")
	{
		unset ($this->objects);

		if ($distinct != "")
		{
			$distinct = "DISTINCT ";
		}

		if ($from != "")
		{
			$from = ", ".$from;
		}

		if ($where != "")
		{
			$where = " WHERE ".$where;
		}

		if ($group_by != "")
		{
			$group_by = " GROUP BY ".$group_by;
		}

		if ($order_by != "")
		{
			$order_by = " ORDER BY ".$order_by;
		}

		if ($limit != "")
		{
			$limit = " LIMIT ".$limit;
		}

		$sql = "SELECT ".$distinct.strtolower(get_class($this)).".".$this->primaryKey." AS ".$this->primaryKey." FROM ".$this->table." AS ".strtolower(get_class($this)).$from.$where.$group_by.$order_by.$limit;

		$this->db->query($sql);
		$this->_lastSQL = $sql;
		$this->_mode = "manual";

		if ($this->db->num_rows() == 0)
		{
			return false;
		} else
		{
			/* Everything ok, do nothing for now */
		}
	}

	/**
	 * exists ($id)
	 * Checks if a specific entry exists 
	 * @param integer $id The id to check for
	 * @return boolean true if object exists, false if not
	 */
	function exists($id)
	{
		$db = new DB_Contenido;

		$sql = "SELECT ".$this->primaryKey." FROM ".$this->table." WHERE ".$this->primaryKey." ='".$id."'";

		$db->query($sql);

		if ($db->next_record())
		{
			return true;
		} else
		{
			return false;
		}
	}

	/**
	 * next ()
	 * Advances to the next item in the database. 
	 * @param none
	 * @return object The next object, or false if no more objects
	 */
	function next()
	{
		if ($this->_mode == "manual")
		{
			if ($this->db->next_record())
			{
				return $this->loadItem($this->db->f($this->primaryKey));
			} else
			{
				return false;
			}
		} else
		{
			if ($this->db->next_record())
			{
				return $this->loadItem($this->db->f($this->primaryKey));
			} else
			{
				return false;
			}
		}
	}

	function fetchObject($class)
	{
		$class = strtolower($class);

		if (is_object($this->_collectionCache[$class]))
		{
			if (strtolower(get_class($this->_collectionCache[$class])) == $class)
			{
				return ($this->_collectionCache[$class]->loadItem($this->db->f($this->_collectionCache[$class]->primaryKey)));
			}
		} else
		{
			$this->_collectionCache[$class] = new $class;

			return ($this->_collectionCache[$class]->loadItem($this->db->f($this->_collectionCache[$class]->primaryKey)));
		}
	}

	/* Prelimary documentation
	
	   $fields = array with the fields to fetch. Notes:
	   If the array contains keys, the key will be used as alias for the field. Example:
	   array("id" => "idcat") will put "idcat" into field "id"  
	
	   $objects = array with the objects to fetch. Notes:
	   If the array contains keys, the key will be used as alias for the object. If you specify
	   more than one object with the same key, the array will be multi-dimensional.
	*/

	function fetchTable($fields = array (), $objects = array ())
	{
		$row = 1;
		$table = array ();

		$this->db->seek(0);

		while ($this->db->next_record())
		{
			foreach ($fields as $alias => $field)
			{
				if ($alias != "")
				{
					$table[$row][$alias] = $this->db->f($field);
				} else
				{
					$table[$row][$field] = $this->db->f($field);
				}
			}

			/* Fetch objects */
			foreach ($objects as $alias => $object)
			{
				if ($alias != "")
				{
					if (isset ($table[$row][$alias]))
					{
						/* Is set, check for array. If no array, create one */
						if (is_array($table[$row][$alias]))
						{
							$table[$row][$alias][] = $this->fetchObject($object);
						} else
						{
							$tmp_obj = $table[$row][$alias];
							$table[$row][$alias] = array ();
							$table[$row][$alias][] = $this->fetchObject($object);
						}
					} else
					{
						$table[$row][$alias] = $this->fetchObject($object);
					}
				} else
				{
					$table[$row][$object] = $this->fetchObject($object);
				}

			}
			$row ++;
		}

		$this->db->seek(0);

		return ($table);
	}

	/**
	 * fetchStructured
	 * Returns an array of arrays
	 * @param objects array with the correct order of the objects
	 * @return array result
	 */
	function queryAndFetchStructured($objects)
	{
		$order = array ();

		foreach ($objects as $object)
		{
			$x = new $object;

			$object = strtolower($object);
			$order[] = $object.".".$x->primaryKey." ASC";
			$fetchobjects[] = $x;
		}

		$this->setOrder(implode(", ", $order));
		$this->query();

		$this->db->seek(0);

		while ($this->db->next_record())
		{
			$array = $this->_recursiveStructuredFetch($fetchobjects, $array);
		}

		return ($array);
	}

	function _recursiveStructuredFetch($objects, $array)
	{
		$i = array_shift($objects);

		$value = $this->db->f($i->primaryKey);

		if (!is_null($value))
		{
			$array[$value]["class"] = strtolower(get_class($i));
			$array[$value]["object"] = $i->loadItem($value);

			if (count($objects) > 0)
			{
				$array[$value]["items"] = $this->_recursiveStructuredFetch($objects, $array[$value]["items"]);
			}
		}

		return $array;
	}

	/**
	 * count ()
	 * Returns the amount of returned items
	 * @param none
	 * @return integer Number of rows
	 */
	function count()
	{
		return ($this->db->num_rows());
	}

	/**
	 * loadItem ($item)
	 * Loads a single object from the database.
	 * Needs to be overridden by the extension class.
	 * @param variant $item Specifies the primary key of the item to load
	 * @return object The newly created object
	 */
	function loadItem($vitem)
	{
		if (empty ($this->_itemClass))
		{
			die("loadItem MUST be overridden by the extension class (in class ".get_class($this).")");
		}

                if (!is_object($this->_iteratorItem))
		{
			$this->_iteratorItem = new $this->_itemClass();
		}

                $obj = clone $this->_iteratorItem;
		$obj->loadByPrimaryKey($vitem);
		return $obj;
	}

	/**
	 * create()
	 * Creates a new item in the table and loads it afterwards.
	 */
	function create()
	{
		/* Local new db instance since we don't want to kill our
		   probably existing result set */
		$db = new DB_Contenido;

		$nextid = $db->nextid($this->table);
		$sql = "INSERT INTO ".$this->table." (";
		$sql .= $this->primaryKey.") VALUES (".$nextid.")";

		$db->query($sql);
		return $this->loaditem($nextid);
	}

	/**
	 * delete()
	 * Deletes an item in the table.
	 */
	function delete($id)
	{
		/* Local new db instance since we don't want to kill our
		   probably existing result set */
		$db = new DB_Contenido;

		$sql = "DELETE FROM ".$this->table." WHERE ";
		$sql .= $this->primaryKey." = '".$id."'";

		$db->query($sql);

		/* Runtime on-demand allocation of the properties object */
		if (!is_object($this->properties))
		{
			$this->properties = new PropertyCollection;
		}

		/* delete the property values */ 
		$this->properties->deleteProperties($this->primaryKey, $id);

		/* If this object wasn't loaded before, return false */
		if ($this->virgin == true)
		{
			$this->lasterror = "No item loaded";
			return false;
		}

		if ($db->affected_rows() == 0)
		{
			return false;
		} else
		{
			return true;
		}
	}

	/**
	 * fetchArray()
	 * Fetches an array of fields from the database.
	 *
	 * Example:
	 * $i = $object->fetchArray("idartlang", array("idlang", "name"));
	 *
	 * could result in:
	 * $i[5] = array("idlang" => 5, "name" => "My Article");
	 *
	 * Important: If you don't pass an array for fields, the function
	 *            doesn't create an array.
	 * @param $key    string Name of the field to use for the key
	 * @param $fields mixed  String or array
	 * @return array Resulting array
	 */
	function fetchArray($key, $fields)
	{
		$result = array ();

		while ($item = $this->next())
		{
			if (is_array($fields))
			{
				foreach ($fields as $value)
				{
					$result[$item->get($key)][$value] = $item->get($value);
				}
			} else
			{
				$result[$item->get($key)] = $item->get($fields);
			}
		}

		return ($result);
	}

}

/**
 * Class Item
 * Class for database based items
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright four for business 2003
 */
class Item
{
	/**
	 * Storage of the source table to use for the user informations
	 * @var string Contains the source table
	 * @access private
	 */
	var $table;

	/**
	 * DB_Contenido instance
	 * @var object Contains the database object
	 * @access private
	 */
	var $db;

	/**
	 * Primary key of the table
	 * @var object Contains the database object
	 * @access private
	 */
	var $primaryKey;

	/**
	 * Storage of the source table to use for the user informations
	 * @var array Contains the source table
	 * @access private
	 */
	var $values;

	/**
	 * Storage of the fields which were modified
	 * @var array Contains the field names which where modified
	 * @access private
	 */
	var $modifiedValues;

	/**
	 * Storage of the last error
	 * @var string Contains the error string of the last error occured
	 * @access private
	 */
	var $lasterror;

	/**
	 * Checks for the virginity of this object. If true, the object
	 * is virgin and no operations on it except load-Functions are allowed.
	 * @var boolean Contains the virginity of this object.
	 * @access private
	 */
	var $virgin;

	/**
	 * Cache the result items
	 * @var array Contains all cache items
	 * @access private
	 */
	var $cache;

	/**
	 * @var int Lifetime in seconds
	 * @access private
	 */
	var $lifetime;

	/**
	 * @var object PropertyCollection object
	 * @access private
	 */
	var $properties;

	/**
	 * stores the old primary key, just in case somebody wants to change it
	 * @var string oldPrimaryKey
	 * @access private
	 */
	var $oldPrimaryKey;

	/**
	 * Array storing the funcion names of the filters
	 * used when data is stored to the db
	 * @var array 
	 * @access private
	 */
	var $_arrInFilters = array ('urlencode', 'htmlspecialchars', 'addslashes');

	/**
	 * Array storing the funcion names of the filters
	 * used when data is retrieved from the db
	 * @var array 
	 * @access private
	 */
	var $_arrOutFilters = array ('stripslashes', 'htmldecode', 'urldecode');

	var $_metaObject;

	/**
	 * Constructor Function
	 * @param string $table The table to use as information source
	 * @param string $primaryKey The primary key to use
	 */
	function Item($table = "", $primaryKey = "", $lifetime = 10)
	{
		$this->db = new DB_Contenido;

		if ($table == "")
		{
			$classname = get_parent_class($this);
			die("$classname: No table specified. Inherited classes *need* to set a table");
		}

		if ($primaryKey == "")
		{
			die("No primary key specified. Inherited classes *need* to set a primary key");
		}

		$this->table = $table;
		$this->primaryKey = $primaryKey;
		$this->virgin = true;
		$this->lifetime = $lifetime;

	} // end function

	/**
	 * loadBy ($field, $value)
	 * Loads an item by ID from the database
	 * @param string $field Specifies the field
	 * @param string $value Specifies the value
	 * @param bool $bSafe use inFilter or not
	 * @return bool True if the load was successful
	 */
	function loadBy($field, $value, $bSafe = true)
	{
		if ($bSafe) {
            $value = $this->_inFilter($value);
        }

		/* SQL-Statement to select by field */
		$sql = "SELECT * FROM ".$this->table." WHERE ".$field." = '".$value."'";

		/* Query the database */
		$this->db->query($sql);

		$this->_lastSQL = $sql;

		if ($this->db->num_rows() > 1)
		{
			cWarning(__FILE__, __LINE__, "Tried to load a single line with field $field and value $value from ".$this->table." but found more than one row");
		}

		/* Advance to the next record, return false if nothing found */
		if (!$this->db->next_record())
		{
			return false;
		}

		$this->values = $this->db->copyResultToArray($this->table);
		$this->oldPrimaryKey = $this->values[$this->primaryKey];
		$this->virgin = false;
		return true;
	}

	/**
	 * loadByPrimaryKey ($value)
	 * Loads an item by ID from the database
	 * @param string $value Specifies the primary key value
	 * @return bool True if the load was successful
	 */
	function loadByPrimaryKey($value)
	{
		$success = $this->loadBy($this->primaryKey, $value);

		if (($success == true) && method_exists($this, "_onLoad"))
		{
			$this->_onLoad();
		}

		return ($success);
	}

	/**
	 * _onLoad ()
	 *
	 * Function which is called whenever an item is loaded.
	 * Inherited classes should override this function if desired.
	 *
	 * @param none
	 * @return none
	 */
	function _onLoad()
	{
	}

	/**
	 * getField($field)
	 * Gets the value of a specific field
	 * @param string $field Specifies the field to retrieve
	 * @return mixed Value of the field
	 */
	function getField($field)
	{
		if ($this->virgin == true)
		{
			$this->lasterror = "No item loaded";
			return false;
		}

		return $this->_outFilter($this->values[$field]);
	}

	/**
	 * get($field)
	 * Wrapper for getField (less to type)
	 * @param string $field Specifies the field to retrieve
	 * @return mixed Value of the field
	 */
	function get($field)
	{
		return $this->getField($field);
	}

	/**
	 * setField($field, $value)
	 * Sets the value of a specific field
	 * @param string $field Specifies the field to set
	 * @param string $value Specifies the value to set
	 * @param boolean $safe Speficies if we should translate characters
	 */
	function setField($field, $value, $safe = true)
	{
		if ($this->virgin == true)
		{
			$this->lasterror = "No item loaded";
			return false;
		}

		$this->modifiedValues[$field] = true;

		if ($field == $this->primaryKey)
		{
			$this->oldPrimaryKey = $this->values[$field];
		}

		if ($safe == true)
		{
			$this->values[$field] = $this->_inFilter($value);

		} else
		{
			$this->values[$field] = $value;
		}
	}

	/**
	 * set($field, $value)
	 * Shortcut to setField
	 * @param string $field Specifies the field to set
	 * @param string $value Specifies the value to set
	 */
	function set($field, $value, $safe = true)
	{
		return ($this->setField($field, $value, $safe));
	}

	/**
	 * store()
	 * Stores the modified user object to the database
	 */
	function store()
	{

		if ($this->virgin == true)
		{
			$this->lasterror = "No item loaded";
			return false;
		}

		$sql = "UPDATE ".$this->table." SET ";
		$first = true;

		if (!is_array($this->modifiedValues))
		{
			return true;
		}

		foreach ($this->modifiedValues as $key => $value)
		{
			if ($first == true)
			{
				$sql .= "`$key` = '".$this->values[$key]."'";
				$first = false;
			} else
			{
				$sql .= ", `$key` = '".$this->values[$key]."'";
			}
		}

		$sql .= " WHERE ".$this->primaryKey." = '".$this->oldPrimaryKey."'";

		$this->db->query($sql);

		$this->_lastSQL = $sql;

		if ($this->db->affected_rows() < 1)
		{
			return false;
		} else
		{
			return true;
		}
	}

	/**
	 * setProperty ($type, $name, $value)
	 * Sets a custom property
	 * @param string $type  Specifies the type
	 * @param string $name  Specifies the name
	 * @param string $value Specifies the value
	 */
	function setProperty($type, $name, $value)
	{
		/* Runtime on-demand allocation of the properties object */
		if (!is_object($this->properties))
		{
			$this->properties = new PropertyCollection;
		}

		/* If this object wasn't loaded before, return false */
		if ($this->virgin == true)
		{
			$this->lasterror = "No item loaded";
			return false;
		}

		/* Set the value */
		return ($this->properties->setValue($this->primaryKey, $this->get($this->primaryKey), $type, $name, $value));
	}

	/**
	 * getProperty ($type, $name)
	 * Sets a custom property
	 * @param string $type  Specifies the type
	 * @param string $name  Specifies the name
	 * @return boolean Value of the given property
	 */
	function getProperty($type, $name)
	{
		/* Runtime on-demand allocation of the properties object */
		if (!is_object($this->properties))
		{
			$this->properties = new PropertyCollection;
		}

		/* If this object wasn't loaded before, return false */
		if ($this->virgin == true)
		{
			$this->lasterror = "No item loaded";
			return false;
		}

		/* Return the value */
		return ($this->properties->getValue($this->primaryKey, $this->get($this->primaryKey), $type, $name));
	}
	
	   /** 
    * deleteProperty ($type, $name) 
    * Deletes a custom property 
    * @param string $type   Specifies the type 
    * @param string $name   Specifies the name 
    */ 
	function deleteProperty($type, $name) 
	{ 
		/* Runtime on-demand allocation of the properties object */ 
		if (!is_object($this->properties)) 
		{ 
			$this->properties = new PropertyCollection; 
		} 

		/* If this object wasn't loaded before, return false */ 
		if ($this->virgin == true) 
		{ 
			$this->lasterror = "No item loaded"; 
			return false; 
		} 

		/* Delete the value */ 
		return ($this->properties->deleteValue($this->primaryKey, $this->get($this->primaryKey), $type, $name)); 
	}

	/**
	 * Deletes the current item
	 * 
	 * @return void
	 */
	// Method doesn't work, remove in future versions
	// function delete()
	// {
	//	$this->_collectionInstance->delete($item->get($this->primaryKey));
	//}

	/**
	 * Define the filter functions used when 
		 * data is being stored or retrieved from 
	 * the database.
	 *
	 * Examples: 
	 *
	 * $obj->setFilters(array('addslashes'), array('stripslashes'));
	 * $obj->setFilters(array('htmlencode', 'addslashes'), array('stripslashes', 'htmlencode'));
	 * 
	 * @param array inFilters array with function names
	 * @param array outFilters array with function names
	 * 
	 * @return void
	 */
	function setFilters($arrInFilters = array (), $arrOutFilters = array ())
	{
		$this->_arrInFilters = $arrInFilters;
		$this->_arrOutFilters = $arrOutFilters;
	}

	/**
	 * Filters the passed data using the functions  
		 * defines in the _arrInFilters array.
	 * 
	 * @see setFilters
	 * 
	 * @param mixed Data to filter	 
	 * 
	 * @access private
	 * @return mixed Filtered data
	 */
	function _inFilter($data)
	{
		foreach ($this->_arrInFilters as $_function)
		{
			if (function_exists($_function))
			{
				$data = $_function ($data);
			}
		}

		return $data;
	}

	/**
	 * Filters the passed data using the functions  
		 * defines in the _arrOutFilters array.
	 * 
	 * @see setFilters
	 * 
	 * @param mixed Data to filter	 
	 * 
	 * @access private
	 * @return mixed Filtered data
	 */
	function _outFilter($data)
	{
		foreach ($this->_arrOutFilters as $_function)
		{
			if (function_exists($_function))
			{
				$data = $_function ($data);
			}
		}

		return $data;
	}

	function _setMetaObject($objectname)
	{
		$this->_metaObject = $objectname;
	}

	function & getMetaObject()
	{
		global $_metaObjectCache;

		if (!is_array($_metaObjectCache))
		{
			$_metaObjectCache = array ();
		}

		$classname = $this->_metaObject;
		$qclassname = strtolower($classname);

		if (array_key_exists($qclassname, $_metaObjectCache))
		{
			if (is_object($_metaObjectCache[$qclassname]))
			{
				if (strtolower(get_class($_metaObjectCache[$qclassname])) == $qclassname)
				{
					$_metaObjectCache[$qclassname]->setPayloadObject($this);
					return $_metaObjectCache[$qclassname];
				}
			}
		}

		if (class_exists($classname))
		{
			$_metaObjectCache[$qclassname] = new $classname ($this);
			return $_metaObjectCache[$qclassname];
		}

	}
} // end class
?>