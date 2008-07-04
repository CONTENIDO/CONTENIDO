<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Query generation for PHP3
 * 
 * Requirements: 
 * @con_php_req 5
 * @con_notice 
 * Credits: Gerard Hickey <Gerard.Hickey@nsc.com>
 * I took many ideas from his SQL.inc, thanks! :-)
 * The idea is of this class is based in November 1997,
 * it was a collection of functions for PHP/FI and mSQL. 
 * 
 *
 * @package    Contenido Backend <Area>
 * @version    1.1.1.1
 * @author     Alex Aulbach
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 * 
 * {@internal 
 *   created  1998-01-01
 *   modified 2008-07-04, bilal arslan, added security fix
 *
 *   $Id$:
 * }}
 * 
 */
 if(!defined('CON_FRAMEWORK')) {
   die('Illegal call');
}

/*
The Query-class is an enhancement to the db_*-classes. Currently It supports
mySQL an Oracle but it is easy expandable. See down.

It always needs the class DB_Sql!

Query is fully upward compatible with DB_Sql. Example:

OLD:                                NEW:

require("db_mysql.inc");            require("db_mysql.inc");
class hugobla extends DB_sql {}     require("query_sql.inc");
$db = new hugobla;                  class hugobla extends Query {}
                                    $db = new hugobla;

It just provides a number of new functions.

The Query-class is inteded to help you with creating selects, inserts,
updates, where-clauses etc. Not just *simple* selects, but longer ones. It
is indeed a great help for tables with more than 10-20 columns. But it can
also be used for simple and small selects. The inbuilt checks help you
programming saver.

Here is an example:

file: insert.php3
------------------
<?
## gets data from my form and inserts it into db

require("prepend.inc"); ## here the classes are loaded and configured
$db = new hugobla;
$db->query($db->insert_plain_Clause("mytable",$db->capture_vars("mytable"),ARRAY()));
echo "Values inserted";

?>

file: insert2.php3
-------------------
<?
## gets data from my form and inserts it into db with a new INDEX
## myindex is defined as INT AUTO_INCREMENT PRIMARY KEY
## (this is mysql, in oracle you have to define a trigger)
## mytime is defined as DATETIME (DATE in oracle)

require("prepend.inc"); ## here the classes are loaded and configured
$db = new hugobla;
$mytime="SYSDATE()";
$db->query($db->insert_plain_Clause("mytable",$db->capture_vars("mytable"),
     ARRAY(myindex=>'NULL',mytime='func')));
echo "Values inserted: "$db->last_insert_id();

?>

This example is nice, cause you see how easy it can be used. :-)

The best thing is, that you don't have to care, if a field is a string or a
number. The values are automatically converted into the right form. The type
of the vars are read from the table. Stringvalues are encapsulated with '
(configurable) and escaped (the code for this is currently not good - we are
assuming, that PHP is configured not to encapsulate strings), int-
and real-values are casted. It can handle "NULL"-values, function-statements
or other values for insertion.

You will make less errors.

mySQL and most other DB's accept a a short form of insert-clause (INSERT
INTO bla VALUES (...)). The Query-class will always make the longer form
(INSERT INTO BLA (...) VALUES (...)). This makes it possible to use ALTER
TABLE-commands without changing the program! E.g. changing a field in a
table from NUMBER to VARCHAR(10) is fully encapsulated with this class.

The class supports currently only mysql and oracle. I think the differences
between the DBs are encapsulated enough in the db_* classes, so it is
possible to handle the remaining small differences inside this class (this
affects mainly the function sql2phptype() ) and it could be easiely extended
(asuming, that the metadata()-function of the db_*-class works correctly).
In this case it is important, that the $type-variable in the db_*.inc-class
is correctly set.


TODO-list:
- A method to create querys like the LIMIT-clause in mySQL. For Oracle
  this works:

  select linenum, foo, bar
  from (select rownum as linenum, foo, bar from
           (select foo,bar from chaos order by bar) )
  where linenum between 11 and 20;

- cleaner escaping, handling of \ and NUL (current code is bullshit)
  Some ideas?

- Little Alta-Vista: add functions to create a where clause from a search
  string with rules to handle searching for more than one word.
  half automatic generating search patterns into a where-clause
  simple search engine support, simple support for semi full-text-search

- automatic configurable manipulation of values, eg. 
  triming of strings (delete whitespace at begin and end)
  also : TOUPPER, TOLOWER etc

- SELECT-Clause (GROUP BY, HAVING, JOIN...)

- make new functions insert_Clause() etc. which inserts only the
  fields they got from your call (the current will do "plain" insert)

- where_Clause() - creating WHERE for select, update, exists etc.

- serv all queries directly into db, return just the handle
  (hm, how to deal with the DB-handle?)

- Return a 2-dimensional (Table-compatible) field from select (not so important)

- The sql2phptype() can be used to help creating automatic input forms
  for a table

DEPENDING:
- db_mysql: new function metatabledata(), which returns the table-info from
  current selected table (will return multiple table-columns with a join)
- db_mysql: perhaps the function sql2phptype() should be placed there?


For developers of new db_*.inc: the function metadata() is very important
for the correct work of this class. T

*/

class Query extends DB_Sql {

	## DONT FORGET to set the variables from DB_Sql! See there!

	## For debugging: if set to TRUE the Query is printed out,
	## before executing or returning 
	var $Q_Debug=false;

	## set this to another value, if you want to hide it
	## in your HTML-code
	## example: var $Q_Debug_print="\n<!-- QDebug: %s -->\n";
	var $Q_Debug_print="<BR><B>QDebug:</B>\n%s\n<BR>\n";

	## Set this to TRUE if you only want to test, which query
	## will be created (ideal in combination with $Q_Debug)
	## This depends only functions which will make changes
	var $No_Write=false;

	## currently unused, this var is just an idea for later use.
	var $Backslash_N_is_NULL = false;

	## Metacache: see funtcion metadata_buffered()
	var $meta_cache_off = false;

	## This is the char, which will be replaced by \char.
	## PHP3 seems to be NOT binary-safe, so not quoting
	## for \0  (some ideas?)
	## we use ereg_replace to do the replacements.
	## with PHP3.0.6 you should replace this with str_replace()!
	##   Quoting = 1 -> normal quoting using AddSlashes
	##             2 -> Replace \' to '' - needed eg. for sybase or oracle
	var $Quoting=1;
	var $Quotechar="'";
	
	var $StrLengTrunc = false;
	var $StrLengWarn = false;

	###########################
	## _QDebug
	function _QDebug ($str) {
		if ($this->Q_Debug) {
			printf($this->Q_Debug_print,$str);
		}
	}

	###########################
	## Set DB-Classname
	## This is only a 3rd posibility for setting the classname
	##
	function set_db_class ($db_class) {
		$this->Database=$db_class;
	}


	###########################
	## This function gets a datatype from the DB and returns an
	## equivalent datatype for PHP
	##
	## It returns also a subtype for a string
	##
	function sql2phptype ($type,$format='') {
		## $this->type is currently either "mysql" or "oracle"
		switch ($this->type) { 
			case "mysql":
				switch ($type) {
					case "var string":
					case "string" :
					case "char" :
						return(Array("string",""));
						break;
					case "timestamp" :
					case "datetime" :
					case "date" :
					case "time" :
						return(Array("string","date"));
						break;
					case "blob" :
						return(Array("string","blob"));
						break;
					case "real" :
						return(Array("double",""));
						break;
					case "long" :
					default :
						return(Array("int",""));
						break;
				}
				break;
			case "oracle":
				switch ($type) {
					case "VARCHAR2" :
					case "VARCHAR" :
					case "CHAR" :
						return(Array("string",""));
						break;
					case "DATE" :
						return(Array("string","date"));
						break;
					case "BLOB" :
					case "CLOB" :
					case "BFILE" :
					case "RAW" :
					case "LONG" :
					case "LONG RAW" :
						return(Array("string","blob"));
						break;
					case "NUMBER" :
						if ($format) {
							return(Array("double",""));
						} else {
							return(Array("int",""));
						}
						break;
					default :
						$this->halt("sql2phptype(): Type is not a valid value: '$type'");
						break;
				}
				break;
			default:
				$this->halt("sql2phptype(): DB-type is not a valid value: ".$this->type);
				break;
		}
	}


	#######################################
	## This function returns a PHP-variable depending
	## on type. E.g. a string is returned as 'string'
	##
	## The parameters mean
	## $val - the value
	##        There is a special case: If value is "NULL" and
	##        the type is not "string" or subtype is empty, then
	##        a value "NULL" is inserted. This let you just spare
	##        a little bit work with $special
	##
	## $meta - the meta information for this field (that's what
	##         is returned by metadata() from DB_sql-class, but just one
	##         single row, e.g. $meta[2], not hole $meta).
	##
	## $special - Overwrites the type of the var if set. Some special
	##            meanings:
	##            "NULL" means, that this value must be set to "NULL"
	##            "func" means, that $val should be untouched -
	##            e.g. to insert the value of a SQL-function
	##            [ INSERT INTO bla VALUES ( time=NOW() ) ]
	##

	function convert ($val,$meta,$special="") {
		list($type,$subtype)=$this->sql2phptype($meta["type"],$meta["format"]);
		if (($val == "NULL" &&
		    ($type != "string" || $type[1] != "")) ||
		    $special == "NULL") {
			$type="NULL";
		} else {
			if ($special) {
				$type=$special;
				if ($type!="func") {
					$val=$type;
					$type="func";
				}
			}
		}
		switch ($type) {
			case "string" :
				$val=(string)$val;
				if ($this->Quoting) {
					$val=AddSlashes($val);
				}
				if ($this->Quoting==2) {
					$val=str_replace("\\'","''",$val);
				}
				if ($subtype!='date' &&
				    ( $this->StrLengTrunc || $this->StrLengWarn ) ) {
					if ( strlen($val) > $meta[len] ) {
						if ($this->StrLengWarn) {
							echo "<BR>STRING TOO LONG: '$meta[name]'";
							if ($this->StrLengTrunc) {
								echo ", TRUNCATING!";
							}
						}
						if ($this->StrLengTrunc) {
							$val=substr($val,0,$meta[len]);
						}
					}
				}
				$val=$this->Quotechar . $val . $this->Quotechar;
				break;
			case "int" :
				$val=(int)$val;
				break;
			case "double" :
				$val=(double)$val;
				break;
			case "NULL" :
				$val="NULL";
				break;
			case "func" :
				$val=(string)$val;
				break;
			default :
				echo "UNKNOWN TYPE: $type<BR>";
		}
		$this->_QDebug("Val: $meta[name] => $val<BR>");
		return(Array($val,$meta["name"]));
	}


	##
	## Function to generate a plain INSERT-Clause
	## ("plain" means, that every field in the table will
	##  be set to a value, default is '' or 0 if nothing said
	##  in $special)
	##
	## $fields  is an assoc. Array consisting out of
	##          table_name => value-pairs
	## $special is an assoc. field which will commit special
	##          handling to convert() (See there)
	## $check   could be "strong" or "soft".
	##          "soft" won't tell you if there were to less
	##          or too much fields (good for debuging)
	##
	## returns the insert clause. It's on you to modify it
	## and send it to your DB
	##
	function insert_plain_Clause ($table,$fields,$special,$check="soft") {
		$meta=$this->metadata_buffered($table);

		for ($i=0; $i < $meta["num_fields"]; $i++) {
			$j=$meta[$i]["name"];
			## NOT IMPLEMENTED: SEARCHING FOR $fields[$i]
			list($val["val"][$i],$val["name"][$i])=
			     $this->convert($fields[$j],$meta[$i],$special[$j]);
		}
		if (Count($fields)!=Count($val["name"]) && $check=="strong") {
			echo "WARNING: insert_plain_clause(): There are not the same number of".
			     " fields as in table for INSERT<BR>";
		}
		$q=sprintf("INSERT INTO %s (%s) VALUES (%s)",
		   $table,join($val["name"],","),
		          join($val["val"],","));
		$this->_QDebug($q);
		return($q);
	}

	# Replace, a special mySQL-function, same as INSERT
	function replace_plain_Clause ($table,$fields,$special,$check="soft") {
		$meta=$this->metadata_buffered($table);

		for ($i=0; $i < $meta["num_fields"]; $i++) {
			$j=$meta[$i]["name"];
			## NOT IMPLEMENTED: SEARCHING FOR $fields[$i]
			list($val["val"][$i],$val["name"][$i])=
			     $this->convert($fields[$j],$meta[$i],$special[$j]);
		}
		if (Count($fields)!=Count($val["name"]) && $check=="strong") {
			echo "WARNING: replace_plain_Clause(): There are not the same number of".
			     " fields as in table for INSERT<BR>";
		}
		$q=sprintf("REPLACE %s (%s) VALUES (%s)",
		   $table,join($val["name"],","),
		          join($val["val"],","));
		$this->_QDebug($q);
		return($q);
	}

	##
	## This function is nearly the same, as insert_plain_Clause,
	## The where parameter is new and should be generated by yourself
	## The check parameter knows 3 values: strong, soft and weak
	## weak enables you to sent a query without $where (enables you
	## to update the hole table)
	##
	function update_plain_Clause ($table,$fields,$special,$where,$check="soft") {
		$meta=$this->metadata_buffered($table);
		if (!$where && $check!="weak") {
			echo "ERROR: update_plain_Clause(): Parameter \$where is empty!<BR>";
			return(false);
		}

		for ($i=0; $i < $meta["num_fields"]; $i++) {
			$j=$meta[$i]["name"];
			## NOT IMPLEMENTED: SEARCHING FOR $fields[$i]
			list($val["val"][$i],$val["name"][$i])=
			     $this->convert($fields[$j],$meta[$i],$special[$j]);
#echo "V: ".$val["name"][$i]." : ". $val["val"][$i]." - ".$fields[$j]."<BR>";
		}
		if (Count($fields)!=Count($val["name"]) && $check=="strong") {
			echo "WARNING: update_plain_Clause(): There are not the same number of".
			     " fields for INSERT<BR>";
		}
		for ($i=0 ; $i < Count ($val["name"]); $i++ ) {
			$s[]=$val["name"][$i]."=".$val["val"][$i];
		}
		$q=sprintf("UPDATE %s SET %s",$table,join($s,","));
		if ($where) {
			if (!eregi("^[[:space:]]*WHERE",$where)) {
				## insert "WHERE" if not set
				$where="WHERE $where";
			}
			$q.=" $where";
		}
		$this->_QDebug($q);
		return($q);
	}


	##
	## This function is nearly the same, as insert_Clause,
	## The where parameter is new and should be generated by yourself
	## The check parameter knows 3 values: strong, soft and weak
	## weak enables you to sent a query without $where (enables you
	## to update the hole table)
	##
	function update_Clause ($table,$fields,$special,$where,$check="soft") {
		$meta=$this->metadata_buffered($table);
		if (!$where && $check!="weak") {
			echo "ERROR: update_Clause(): Parameter \$where is empty!<BR>";
			return(false);
		}

		$i=0;
		for (reset($fields); list($key,$val)=each($fields); $i++) {
			if ( isset($meta[meta][$key]) ) {
				$j=$meta[meta][$key];
				list($v["val"][$i],$v["name"][$i])=
				     $this->convert($val,$meta[$j],$special[$key]);
			}
		}
		for ($i=0 ; $i < Count ($v["name"]); $i++ ) {
			$s[]=$v["name"][$i]."=".$v["val"][$i];
		}
		if (Count($s)) {
			$q=sprintf("UPDATE %s SET %s",$table,join($s,","));
			if ($where) {
				if (!eregi("^[[:space:]]*WHERE",$where)) {
					## insert "WHERE" if not set
					$where="WHERE $where";
				}
				$q.=" $where";
			}
		}
		$this->_QDebug($q);
		return($q);
	}



	##
	## DELETE
	## deletes the selected Table
	## $check can be "soft" and "weak". Weak let's you delete the
	## hole table, if you want
	##
	function delete_Clause ($table,$where,$check="soft") {
		if (!$where && $check!="weak") {
			echo "ERROR: delete_Clause(): Parameter \$where is empty!<BR>";
			return(false);
		}

		$q=sprintf("DELETE FROM %s",$table);
		if ($where) {
			if (!eregi("^[[:space:]]*WHERE",$where)) {
				## insert "WHERE" if not set
				$where="WHERE $where";
			}
			$q.=" $where";
		}
		$this->_QDebug($q);
		return($q);
	}


	##
	## This function checks wether in table $table a
	## field $name is set with value $val
	##
	## it returns the number of found matches or zero
	##
	function exists ($table,$name,$val) {
		$meta=$this->metadata_buffered($table);
		$j=$meta["meta"][$name];
		list($k)=$this->convert($val,$meta[$j]);
		$q=sprintf("SELECT COUNT(%s) as c FROM %s WHERE %s=%s",
		   $name,$table,$name,$k);
		$this->_QDebugs($q);
		$this->query($q);
		$this->next_record();
		return($this->f("c"));
	}

	##
	## This function creates a query like exists, but returns
	## an assoc array of the first found row, or false if nothing found
	## field $name is set with value $val
	##
	function getrow ($table,$name,$val) {
		$meta=$this->metadata_buffered($table);
		$j=$meta[meta][$name];
		list($k)=$this->convert($val,$meta[$j]);
		$q=sprintf("SELECT * FROM %s WHERE %s=%s",
		   $table,$name,$k);
		$this->_QDebug($q);
		$this->query($q);
		if ($this->next_record()) {
			return($this->Record);
		} else {
			echo "<BR><B>WARNING:</B> getrow(): KEY: $name VAL: $val not found<BR>";
			return(false);
		}
	}



	##
	## WHERE-PLAIN-CLAUSE
	## Let you generate a WHERE-Clause with a Loop.
	##
	## Returns a where-clause beginning with " WHERE "
	##
	## This function generates a where-clause
	## $mywhere   An array of simple expressions, eg. "firstname='Alex'"
	## $andor     This string is printed bewtween the where-Array
	##            default is 'AND'. It will handle an existing
	##            $oldwhere correctly. You can set this to '', but then
	##            the correct operator must be set by you in the where
	## $where     an existing WHERE-clause. Default is empty.
	## $check     if 'strong', it will stop, if an empty where-clause
	##            will be returned, to avoid "full" selects. Default is soft
	##
	function where_plain_Clause ($mywhere,$andor='AND',$where='',$check="soft") {
		$meta=$this->metadata_buffered($table);
		$q='';

		for ($i=0; $i<Count($mywhere); $i++ ) {
			$q.=" $andor ".$mywhere[$i];
		}
		if ($where) {
			$where=eregi_Replace("^[[:space:]]*WHERE","",$where);
			$q.=" $andor $where";
		}
		if (!$q && $check=='full') {
			echo "WARNING: where_plain_Clause(): WHERE-clause is empty!<BR>";
		}
		$q=ereg_Replace("^ $andor "," WHERE ",$q);
		$this->_QDebug("where_plain_Clause(): $q");
		return($q);
	}

	##
	## ANOTHER-WHERE-CLAUSE
	##
	## This function generates a where-clause beginning with " WHERE "
	## Different form where_plain_Clause() this function is fully automated
	## It can handle NULL-Values (IS NULL) in a special manner:
	## if a value of $fields is 'NULL', we are looking, if the
	## operator is '='. In this case the operator is changed into "IS"
	## in any other case it is changed into "IS NOT".
	##
	## $tables    table
	## $fields    Assoc name=>value-fields
	## $op        Assoc name=>operator. If empty, '=' is taken. it is printed
	##            *between* the name/value pairs.
	##            if $op is 'func' the name is taken as function name,
	##            inside the brakets is the value.
	## $special   Affects the calculation of value.
	##            See INSERT_CLAUSE() for more about this.
	## $andor     This string is printed bewtween the name/value-pairs,
	##            default is 'AND'. If $where is set, it prints
	##            it directly at the end before concatenating
	## $where     an existing WHERE-clause. Default is empty.
	## $check     if 'strong', it will stop, if an empty where-clause
	##            will be returned, to avoid "full" selects. Default is soft
	##
	## Returns a where-clause beginning with " WHERE "
	##
	function where_Clause ($table,$fields,$op='',$special='',
	                        $andor='AND',$where='',$check="soft") {
		$meta=$this->metadata_buffered($table);
		$q='';

		if (!is_Array($op)) $op=ARRAY();
		if (!is_Array($special)) $op=ARRAY();
		if (!$andor) $andor='AND';

		$i=0;
		for (reset($fields); list($key,$val)=each($fields); $i++) {
			list($k[val],$k[name])=
			    $this->convert($fields[$key],$meta[$meta[meta][$key]],$special[$key]);
			if (!$op[$key]) $o='='; else $o=$op[$key];
			if ('NULL'==strval($k[val])) {
				if ($o=='=' || strtoupper($o)=='IS') $o = 'IS';
				else         $o = 'IS NOT';
			}
			$q.=" $andor $k[name] $o $k[val]";
		}
		if ($where) {
			$where=eregi_Replace("^[[:space:]]*WHERE","",$where);
			$q.=" $andor $where";
		}
		if (!$q && $check=='full') {
			echo "WARNING: where_Clause(): WHERE-clause is empty!<BR>";
		}
		$q=ereg_Replace("^ $andor "," WHERE ",$q);
		$this->_QDebug("where_Clause(): $q");
		return($q);
	}


	##
	## capture-vars
	##
	## This function returns an assoc. Array consisting out of
	## name=>value-pairs needed by all the other functions. It reads
	## the name of the vars from the fields in $table and the values
	## from the $GLOBALS-var-field.
	## This has the sense, that you can name the variables in your
	## Input-Form exactly like the names in your table. This again
	## let make you less errors and less side effects.
	##
	## $table     The name of the table
	##
	function capture_vars ($table) {
		$meta=$this->metadata_buffered($table);
		$r=Array();
		for ($i=0; $i < $meta["num_fields"]; $i++) {
			$j=$meta[$i]["name"];
			if (isset($GLOBALS[$j])) {
			    $r[$j] = $GLOBALS[$j];
				$this->_QDebug("Found $j: $r[$j]");
			}
		}
		return($r);
	}

	##
	## all_changed_vars
	##
	## This function returns an assoc. Array consisting out of
	## name=>value-pairs which have a different value from the value
	## currently existing in your table. This is needed by
	## update_Clause(), cause with this, the update-query can be shortened
	## to the maximum needed max. Can also be used for much other things,
	## e.g. checking if something in your form has been changed (in this
	## case it returns an empty array)
	##
	## $table     The name of the table
	## $fields    Your assoc value field, which you want to check for
	## $where     The where-clause, which matches your row.
	##            This functions writes warnings, if your where-clause
	##            returns more than one row or nothing
	##
	function all_changed_vars ($table,$fields,$where,$check='soft') {
		$meta=$this->metadata_buffered($table);

		$q1="SELECT * FROM $table $where";
		$this->query($q1);
		$r=Array();
		if ($this->next_record()) {
			for ($i=0; $i < $meta["num_fields"]; $i++) {
				$j=$meta[$i]["name"];
				if ($this->Record[$j]!=$fields[$j]) {
					$r[$j]=$fields[$j];
					$this->_QDebug("Changed $j:  ".$fields[$j]." -> ".$this->Record[$j]);
				}
			}
			if ($this->next_record()) {
				echo "ERROR: all_changed_vars(): Found more than one row!<BR>";
			}
		} elseif ($check!='soft') {
			echo "<BR><B>WARNING:</B> all_changed_vars(): No row found!<BR>";
		}
		$this->_QDebug("WHERE: $where");
		return($r);
	}

	##
	## metadata_buffered (internal)
	##
	## This function calls metadata() if it won't find the buffer,
	## this speeds the Query-class strongly up, cause it is needed in nearly
	## every function
	##
	## $table    the name of the table
	## 
	## Returns the metadata-field
	##
	function metadata_buffered($table) {
		if ( !is_Array($this->meta_buf[$table]) || $this->meta_cache_off) {
			return ($this->meta_buf[$table]=$this->metadata($table,true));
		} else {
			return ($this->meta_buf[$table]);
		}
	}


}

?>
