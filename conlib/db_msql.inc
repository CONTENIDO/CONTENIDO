<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Session Management for PHP3
 * 
 * Requirements: 
 * @con_php_req 5
 * @con_template <Templatefiles>
 * @con_notice <Notice>
 * 
 *
 * @package    Contenido Backend <Area>
 * @version    <version>
 * @author     Sascha Schumann <sascha@schumann.cx>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 * 
 * {@internal 
 *   created <date>
 *   modified 2008-07-03, bilal arslan, added security fix
 *
 *   $Id: db_msql.inc 740 2008-08-27 10:45:04Z timo.trautmann $:
 * }}
 * 
 */
 
 if(!defined('CON_FRAMEWORK')) {
                die('Illegal call');
}


/*
 * Session Management for PHP3
 *
 * Copyright (c) 1998-2000 NetUSE AG
 *                    Boris Erdmann, Kristian Koehntopp
 *
 * Derived from db_mysql.inc by Sascha Schumann <sascha@schumann.cx>
 *
 * $Id: db_msql.inc 740 2008-08-27 10:45:04Z timo.trautmann $
 *
 */ 

class DB_Sql {
  var $Host     = "";
  var $Database = "";

  var $Link_ID  = 0;
  var $Query_ID = 0;
  var $Record   = array();
  var $Row;

  var $Error    = "";
  
  var $Auto_Free = 0;     ## Set this to 1 for automatic msql_free_result()

  /* public: constructor */
  function DB_Sql($query = "") {
      $this->query($query);
  }

  function connect() {
    // Not connected? Then connect?
    if ( 0 == $this->Link_ID ) {
      // Check for local connect
      $this->Link_ID = empty($this->Host)? 
                         $this->Link_ID=msql_pconnect():
                         $this->Link_ID=msql_pconnect($this->Host);
    }
    
    // Still not connected? Raise error.
    if ( 0 == $this->Link_ID ) {
      $this->halt("Link-ID == false, pconnect failed");
    }

    // Select current database
	  if (!msql_select_db($this->Database, $this->Link_ID)) {
      $this->halt("cannot use database ".$this->Database);
    }
  }
  
  function query($Query_String) {
    
    /* No empty queries, please, since PHP4 chokes on them. */
    if ($Query_String == "")
      /* The empty query string is passed on from the constructor,
       * when calling the class without a query, e.g. in situations
       * like these: '$db = new DB_Sql_Subclass;'
       */
      return 0;

    $this->connect();

#   printf("Debug: query = %s<br>\n", $Query_String);
	$oDB = new DB_Contenido; 
    $this->Query_ID = msql_query($Query_String,$this->Link_ID);
    $this->Row   = 0;
    $this->Error = msql_error();
    if (!$this->Query_ID) {
      $this->halt("Invalid SQL: ".$Query_String);
    }

    return $this->Query_ID;
  }

  function next_record() {
    $this->Record = msql_fetch_array($this->Query_ID);
    $this->Row   += 1;
    $this->Error = msql_error();

    $stat = is_array($this->Record);
    if (!$stat && $this->Auto_Free) {
      msql_free_result($this->Query_ID);
      $this->Query_ID = 0;
    }
    return $stat;
  }

  function seek($pos) {
    $status = msql_data_seek($this->Query_ID, $pos);
    if ($status)
      $this->Row = $pos;
    return;
  }

  function metadata($table) {
    $count = 0;
    $id    = 0;
    $res   = array();

    $this->connect();
    $id = @msql_list_fields($this->Database,  $table);
    if ($id < 0) {
      $this->Error = msql_error();
      $this->halt("Metadata query failed.");
    }
    $count = msql_num_fields($id);
    
    for ($i=0; $i<$count; $i++) {
      $res[$i]["table"] = msql_fieldtable ($id, $i);
      $res[$i]["name"]  = msql_fieldname  ($id, $i);
      $res[$i]["type"]  = msql_fieldtype  ($id, $i);
      $res[$i]["len"]   = msql_fieldlen   ($id, $i);
      $res[$i]["flags"] = msql_fieldflags ($id, $i);
      $res["meta"][$res[$i]["name"]] = $i;
      $res["num_fields"]= $count;
    }
    
    msql_free_result($id);
    return $res;
  }

  function affected_rows() {
	  return msql_affected_rows($this->Query_ID);
  }

  function num_rows() {
    return msql_num_rows($this->Query_ID);
  }

  function num_fields() {
    return msql_num_fields($this->Query_ID);
  }

  function nf() {
    return $this->num_rows();
  }

  function np() {
    print $this->num_rows();
  }

  function f($Name) {
    return $this->Record[$Name];
  }

  function p($Name) {
    print $this->Record[$Name];
  }
  
  function halt($msg) {
    printf("</td></tr></table><b>Database error:</b> %s<br>\n", $msg);
    printf("<b>MSQL Error</b>: %s<br>\n", $this->Error);
    die("Session halted.");
  }
  
  /* public: Escape string for using in SQL-Statement */
  function escape ($sString) {
      //ToDo implement specific database behaviour
      return addslashes ($sString);
  } 
}
?>
