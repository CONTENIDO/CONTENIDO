<?php
/*
 * PHP Base Library
 *
 * general utilities for db_sql
 *
 * (c) 1999-2000 Carmelo Guarneri
 *
 * $Id: db_usql.inc,v 1.1.1.1 2002/07/21 08:35:41 root Exp $
 *
 */ 


class DB_USql extends DB_Sql {

//--------------------------------------------------------------
//  this function can be used to export all the columns of
//  a record into global variables.
//  It should be used after a call to next_record().
//--------------------------------------------------------------
  function import_record_vars() {
    while (list($key, $val) = each($this->Record))
    if (ereg("[A-Za-z][A-Za-z0-9_]*", $key)) {
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
    if (ereg("[A-Za-z][A-Za-z0-9_]*", $key)) {
      $field_name = strtoupper($key);
	  if (!empty($val)) 
	  if (strstr( $filter, $field_name )=="") {
	    $fields.="$field_name ,";
		$val=ereg_replace("'","''",$val);
		$val=ereg_replace("\"","\\\"",$val);
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
