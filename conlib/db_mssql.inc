<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * 
 * Requirements: 
 * @con_php_req 5
 * @con_notice 
 * This is using the MSSQL class
 * 
 *
 * @package    Contenido Backend <Area>
 * @version    1.1
 * @author     Cameron Taggart
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 * 
 * {@internal 
 *   created  1998-01-01
 *   modified unknown, Guarneri carmelo
 *   modified unknown, Cameron Just
 *   modified 2008-07-03, bilal arslan, added security fix
 *
 *   $Id: db_mssql.inc 740 2008-08-27 10:45:04Z timo.trautmann $:
 * }}
 * 
 */

 if(!defined('CON_FRAMEWORK')) {
                die('Illegal call');
}


class DB_Sql {
  var $Host     = "";
  var $Database = "";
  var $User     = "";
  var $Password = "";

  var $Link_ID  = 0;
  var $Query_ID = 0;
  var $Record   = array();
  var $Row      = 0;
  
  var $Errno    = 0;
  var $Error    = "";

  var $Auto_Free = 0;     ## set this to 1 to automatically free results
  
  
  /* public: constructor */
  function DB_Sql($query = "") {
      $this->query($query);
  }

  function connect() {
    if ( 0 == $this->Link_ID ) {
      $this->Link_ID=mssql_pconnect($this->Host, $this->User, $this->Password);
      if (!$this->Link_ID)
        $this->halt("Link-ID == false, mssql_pconnect failed");
      else
      	mssql_select_db($this->Database, $this->Link_ID);
    }
  }
  function free_result(){
	  mssql_free_result($this->Query_ID);
  	$this->Query_ID = 0;
  }
  
  function query($Query_String) 
  {
    
    /* No empty queries, please, since PHP4 chokes on them. */
    if ($Query_String == "")
      /* The empty query string is passed on from the constructor,
       * when calling the class without a query, e.g. in situations
       * like these: '$db = new DB_Sql_Subclass;'
       */
      return 0;

  	if (!$this->Link_ID)
    	$this->connect();
    
#   printf("<br>Debug: query = %s<br>\n", $Query_String);
    
    $this->Query_ID = mssql_query($Query_String, $this->Link_ID);
    $this->Row = 0;
    if (!$this->Query_ID) {
      $this->Errno = 1;
      $this->Error = "General Error (The MSSQL interface cannot return detailed error messages).";
      $this->halt("Invalid SQL: ".$Query_String);
    }
    return $this->Query_ID;
  }
  
  function next_record() {
  	
    if ($this->Record = mssql_fetch_row($this->Query_ID)) {
      // add to Record[<key>]
      $count = mssql_num_fields($this->Query_ID);
      for ($i=0; $i<$count; $i++){
      	$fieldinfo = mssql_fetch_field($this->Query_ID,$i);
        $this->Record[strtolower($fieldinfo->name)] = $this->Record[$i];
      }
      $this->Row += 1;
      $stat = 1;
    } else {
      if ($this->Auto_Free) {
	    	$this->free_result();
	  	}
      $stat = 0;
    }
    return $stat;
  }
  
  function seek($pos) {
		mssql_data_seek($this->Query_ID,$pos);
  	$this->Row = $pos;
  }

  function metadata($table) {
    $count = 0;
    $id    = 0;
    $res   = array();

    $this->connect();
    $id = mssql_query("select * from $table", $this->Link_ID);
    if (!$id) {
      $this->Errno = 1;
      $this->Error = "General Error (The MSSQL interface cannot return detailed error messages).";
      $this->halt("Metadata query failed.");
    }
    $count = mssql_num_fields($id);
    
    for ($i=0; $i<$count; $i++) {
    	$info = mssql_fetch_field($id, $i);
      $res[$i]["table"] = $table;
      $res[$i]["name"]  = $info["name"];
      $res[$i]["len"]   = $info["max_length"];
      $res[$i]["flags"] = $info["numeric"];
    }
    $this->free_result();
    return $res;
  }
  
  function affected_rows() {
// Not a supported function in PHP3/4.  Chris Johnson, 16May2001.
//    return mssql_affected_rows($this->Query_ID);
    $rsRows = mssql_query("Select @@rowcount as rows", $this->Link_ID);
    if ($rsRows) {       
       return mssql_result($rsRows, 0, "rows");
    }
  }
  
    /* public: Escape string for using in SQL-Statement */
  function escape ($sString) {
      //ToDo implement specific database behaviour
      return addslashes ($sString);
  } 
  
  function num_rows() {
    return mssql_num_rows($this->Query_ID);
  }
  
  function num_fields() {
    return mssql_num_fields($this->Query_ID);
  }

  function nf() {
    return $this->num_rows();
  }
  
  function np() {
    print $this->num_rows();
  }
  
  function f($Field_Name) {
    return $this->Record[strtolower($Field_Name)];
  }
  
  function p($Field_Name) {
    print $this->f($Field_Name);
  }
  
  function halt($msg) {
    printf("</td></tr></table><b>Database error:</b> %s<br>\n", $msg);
    printf("<b>MSSQL Error</b>: %s (%s)<br>\n",
      $this->Errno,
      $this->Error);
    die("Session halted.");
  }
}
?>
