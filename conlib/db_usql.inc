<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * PHP Base Library
 * general utilities for db_sql
 * 
 * Requirements: 
 * @con_php_req 5
 * @con_template <Templatefiles>
 * @con_notice <Notice>
 * 
 *
 * @package    Contenido Backend <Area>
 * @version    1.1.1.2
 * @author     Carmelo Guarneri
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 * 
 * {@internal 
 *   created  2002-07-21
 *   modified 2008-07-04, bilal arslan, added security fix
 *   modified 2009-11-06, Murat Purc, replaced deprecated functions (PHP 5.3 ready)
 *
 *   $Id$:
 * }}
 * 
 */
 if(!defined('CON_FRAMEWORK')) {
   die('Illegal call');
}

class DB_USql extends DB_Sql {

//--------------------------------------------------------------
//  this function can be used to export all the columns of
//  a record into global variables.
//  It should be used after a call to next_record().
//--------------------------------------------------------------
  function import_record_vars() {
    while (list($key, $val) = each($this->Record))
    if (preg_match("/[A-Za-z][A-Za-z0-9_]*/", $key)) {
      $field_name = strtoupper($key); 
	  global $$field_name;
	  $$field_name=$val;
    }; 
  }

//--------------------------------------------------------------
//  this function can be used to export all the records of
//  a table on the output in the form of a call to the db_sql 
//  query function with an insert statement.
//--------------------------------------------------------------
  function dump_table($tablename, $filter="") {
    $this->query(sprintf("select * from %s", $tablename));
    while ($this->next_record()) {
	  $this->dump_record($tablename, $filter);
	};
  }

//--------------------------------------------------------------
//  this function can be used to export all the records of
//  a query on the output in the form of a call to the db_sql 
//  query function with an insert statement.
//--------------------------------------------------------------
  function dump_query($tablename, $filter="") {
    //$this->query(sprintf("select * from %s", $tablename));
    while ($this->next_record()) {
	  $this->dump_record($tablename, $filter);
	};
  }

  function dump_record($tablename, $filter="") {
    $fields="";
	$values="";
    while (list($key, $val) = each($this->Record))
    if (preg_match("/[A-Za-z][A-Za-z0-9_]*/", $key)) {
      $field_name = strtoupper($key);
	  if (!empty($val)) 
	  if (strstr( $filter, $field_name )=="") {
	    $fields.="$field_name ,";
		$val = preg_replace("/'/", "''", $val);
		$val = preg_replace("/\"/", "\\\"", $val);
		//addslashes($val);
	    $values.="'$val' ,";
	  };
    }
    $fields=substr($fields, 0, strlen($fields)-1); 
    $values=substr($values, 0, strlen($values)-1);
	$insert=sprintf("insert into %s(%s) values(%s)", $tablename, $fields, $values);
	echo "\$db->query(\"$insert\");\n";
  }
  };

?>
