<?php

/*
 * Session Management for PHP3
 *
 * Copyright (c) 1998-2000 NetUSE AG
 *                    Boris Erdmann, Kristian Koehntopp
 *
 * $Id$
 *
 */
 
$mysqli_type = array();
$mysqli_type[0] = "decimal";
$mysqli_type[1] = "tinyint";
$mysqli_type[2] = "smallint";
$mysqli_type[3] = "int";
$mysqli_type[4] = "float";
$mysqli_type[5] = "double";
$mysqli_type[7] = "timestamp";
$mysqli_type[8] = "bigint";
$mysqli_type[9] = "mediumint";
$mysqli_type[10] = "date";
$mysqli_type[11] = "time";
$mysqli_type[12] = "datetime";
$mysqli_type[13] = "year";
$mysqli_type[252] = "blob"; // text, blob, tinyblob,mediumblob, etc...
$mysqli_type[253] = "string"; // varchar and char
$mysqli_type[254] = "enum";

class DB_Sql
{

	/* public: connection parameters */
	var $Host = "";
	var $Database = "";
	var $User = "";
	var $Password = "";

	/* public: configuration parameters */
	var $Auto_Free = 0; ## Set to 1 for automatic mysqli_free_result()
	var $Debug = 0; ## Set to 1 for debugging messages.
	var $Halt_On_Error = "yes"; ## "yes" (halt with message), "no" (ignore errors quietly), "report" (ignore errror, but spit a warning)
	var $Seq_Table = "";

	/* public: result array and current row number */
	var $Record = array ();
	var $Row;

	/* public: current error number and error text */
	var $Errno = 0;
	var $Error = "";

	/* public: this is an api revision, not a CVS revision. */
	var $type = "mysqli";
	var $revision = "1.2";

	/* private: link and query handles */
	var $Link_ID = 0;
	var $Query_ID = 0;

	/* public: constructor */
	function DB_Sql($query = "")
	{
		global $cfg;
		$this->Seq_Table = $cfg["tab"]["sequence"];
		$this->query($query);

	}

	/* public: some trivial reporting */
	function link_id()
	{
		return $this->Link_ID;
	}

	function query_id()
	{
		return $this->Query_ID;
	}

	/* public: connection management */
	function connect($Database = "", $Host = "", $User = "", $Password = "")
	{
		/* Handle defaults */
		if ("" == $Database)
			$Database = $this->Database;
		if ("" == $Host)
			$Host = $this->Host;
		if ("" == $User)
			$User = $this->User;
		if ("" == $Password)
			$Password = $this->Password;

		/* establish connection, select database */
		if (0 == $this->Link_ID)
		{
			if (($iPos = strpos($Host, ":")) !== false)
			{
				list($sHost, $sPort) = explode(":", $Host);
				
				$this->Link_ID = @mysqli_connect($sHost, $User, $Password, "", $sPort);
			} else {
				$this->Link_ID = @mysqli_connect($Host, $User, $Password);	
			}
			

if (!$this->Link_ID)
         {
            $this->halt("connect($Host, $User, \$Password) failed: ".mysqli_connect_error());
            return 0;
         }

			if ($this->Database != "")
			{
				if (!mysqli_select_db($this->Link_ID, $Database))
				{
					$this->halt("cannot use database ".$Database);
					return 0;
				}
			}
		}

		return $this->Link_ID;
	}

	/* public: discard the query result */
	function free()
	{
		if (is_object($this->Query_ID))
		{
			mysqli_free_result($this->Query_ID);
		}
		$this->Query_ID = 0;
	}

	/* public: perform a query */
	function query($Query_String)
	{
		/* No empty queries, please, since PHP4 chokes on them. */
		if ($Query_String == "")
			/* The empty query string is passed on from the constructor,
			 * when calling the class without a query, e.g. in situations
			 * like these: '$db = new DB_Sql_Subclass;'
			 */
			return 0;

		if (!$this->connect())
		{
			return 0; /* we already complained in connect() about that. */
		};

		# New query, discard previous result.
		if ($this->Query_ID)
		{
			$this->free();
		}

		if ($this->Debug)
			printf("Debug: query = %s<br>\n", $Query_String);

		$this->Query_ID = mysqli_query($this->Link_ID, $Query_String);

		$this->Row = 0;
		$this->Errno = mysqli_errno($this->Link_ID);
		$this->Error = mysqli_error($this->Link_ID);
		if (!$this->Query_ID)
		{
			$this->halt(implode("", array ("MySQL error ", $this->Errno, ": ", $this->Error, "\n", $Query_String)));
		}

		# Will return nada if it fails. That's fine.
		return $this->Query_ID;
	}

	/* public: walk result set */
	function next_record()
	{
		global $cCurrentModule;
		$this->NoRecord = false;
		if (!$this->Query_ID)
		{
			$this->NoRecord = true;
			if ($cCurrentModule > 0)
			{
				$this->halt("next_record called with no query pending in Module ID $cCurrentModule.");
			} else
			{
				$this->halt("next_record called with no query pending.");
			}
			return 0;
		}

		$this->Record = mysqli_fetch_array($this->Query_ID, MYSQLI_BOTH);
		$this->Row += 1;
		$this->Errno = mysqli_errno($this->Link_ID);
		$this->Error = mysqli_error($this->Link_ID);

		$stat = is_array($this->Record);
		if (!$stat && $this->Auto_Free)
		{
			$this->free();
		}
		return $stat;
	}

	/* public: position in result set */
	function seek($pos = 0)
	{
		$status = mysqli_data_seek($this->Query_ID, $pos);
		if ($status)
			$this->Row = $pos;
		else
		{
			$this->halt("seek($pos) failed: result has ".$this->num_rows()." rows.");

			/* half assed attempt to save the day, 
			 * but do not consider this documented or even
			 * desireable behaviour.
			 */
			mysqli_data_seek($this->Query_ID, $this->num_rows());
			$this->Row = $this->num_rows();
			return 0;
		}

		return 1;
	}

	/* public: table locking */
	function lock($table, $mode = "write")
	{
		global $cfg;
		if ($cfg["nolock"] == true)
		{
			return true;
		}
		$query = "lock tables ";
		if (is_array($table))
		{
			while (list ($key, $value) = each($table))
			{
				if (!is_int($key))
				{
					// texts key are "read", "read local", "write", "low priority write"
					$query .= "$value $key, ";
				} else
				{
					$query .= "$value $mode, ";
				}
			}
			$query = substr($query, 0, -2);
		} else
		{
			$query .= "$table $mode";
		}
		$res = $this->query($query);
		if (!$res)
		{
			$this->halt("lock() failed.");
			return 0;
		}
		return $res;
	}

	function unlock()
	{
		global $cfg;
		if ($cfg["nolock"] == true)
		{
			return true;
		}

		$res = $this->query("unlock tables");
		if (!$res)
		{
			$this->halt("unlock() failed.");
		}
		return $res;
	}

	/* public: evaluate the result (size, width) */
	function affected_rows()
	{
		return mysqli_affected_rows($this->Link_ID);
	}

	function num_rows()
	{
		return mysqli_num_rows($this->Query_ID);
	}

	function num_fields()
	{
		return mysqli_num_fields($this->Query_ID);
	}

	/* public: shorthand notation */
	function nf()
	{
		return $this->num_rows();
	}

	function np()
	{
		print $this->num_rows();
	}

	function f($Name)
	{
		if (isset ($this->Record[$Name]))
		{
			return $this->Record[$Name];
		}
	}

	function p($Name)
	{
		if (isset ($this->Record[$Name]))
		{
			print $this->Record[$Name];
		}
	}

	/* public: sequence numbers */
	function nextid($seq_name)
	{
		global $cfg;

		$this->connect();
		$this->Seq_Table = $cfg["tab"]["sequence"];

		if ($this->lock($this->Seq_Table))
		{
			/* get sequence number (locked) and increment */
			$q = sprintf("select nextid from %s where seq_name = '%s'", $this->Seq_Table, $seq_name);
			$id = mysqli_query($this->Link_ID, $q);
			$res = mysqli_fetch_array($id, MYSQLI_BOTH);

			/* No current value, make one */
			if (!is_array($res))
			{
				$currentid = 0;
				$q = sprintf("insert into %s values('%s', %s)", $this->Seq_Table, $seq_name, $currentid);
				$id = mysqli_query($this->Link_ID, $q);
			} else
			{
				$currentid = $res["nextid"];
			}
			$nextid = $currentid +1;
			$q = sprintf("update %s set nextid = '%s' where seq_name = '%s'", $this->Seq_Table, $nextid, $seq_name);
			$id = mysqli_query($this->Link_ID, $q);
			$this->unlock();
		} else
		{
			$this->halt("cannot lock ".$this->Seq_Table." - has it been created?");
			return 0;
		}
		return $nextid;
	}

	/* public: return table metadata */
	function metadata($table = "", $full = false)
	{
		global $mysqli_type;
		
		$count = 0;
		$id = 0;
		$res = array ();

		/*
		 * Due to compatibility problems with Table we changed the behavior
		 * of metadata();
		 * depending on $full, metadata returns the following values:
		 *
		 * - full is false (default):
		 * $result[]:
		 *   [0]["table"]  table name
		 *   [0]["name"]   field name
		 *   [0]["type"]   field type
		 *   [0]["len"]    field length
		 *   [0]["flags"]  field flags
		 *
		 * - full is true
		 * $result[]:
		 *   ["num_fields"] number of metadata records
		 *   [0]["table"]  table name
		 *   [0]["name"]   field name
		 *   [0]["type"]   field type
		 *   [0]["len"]    field length
		 *   [0]["flags"]  field flags
		 *   ["meta"][field name]  index of field named "field name"
		 *   This last one could be used if you have a field name, but no index.
		 *   Test:  if (isset($result['meta']['myfield'])) { ...
		 */

		// if no $table specified, assume that we are working with a query
		// result
		if ($table)
		{
			$this->connect();
			$id = mysqli_query($this->Link_ID, sprintf("SELECT * FROM %s LIMIT 1", $table));
			if (!$id)
			{
				$this->halt("Metadata query failed.");
				return false;
			}
		} else
		{
			$id = $this->Query_ID;
			if (!$id)
			{
				$this->halt("No query specified.");
				return false;
			}
		}

		$count = mysqli_num_fields($id);

		// made this IF due to performance (one if is faster than $count if's)
		if (!$full)
		{
			for ($i = 0; $i < $count; $i ++)
			{
				$finfo = mysqli_fetch_field($id);
				$res[$i]["table"] = $finfo->table;
				$res[$i]["name"] = $finfo->name;
				$res[$i]["type"] = $mysqli_type[$finfo->type];
				$res[$i]["len"] = $finfo->max_length;
				$res[$i]["flags"] = $finfo->flags;
			}
		} else
		{ // full
			$res["num_fields"] = $count;

			for ($i = 0; $i < $count; $i ++)
			{
				$finfo = mysqli_fetch_field($id);
				$res[$i]["table"] = $finfo->table;
				$res[$i]["name"] = $finfo->name;
				$res[$i]["type"] = $finfo->type;
				$res[$i]["len"] = $finfo->max_length;
				$res[$i]["flags"] = $finfo->flags;
				$res["meta"][$res[$i]["name"]] = $i;
			}
		}

		// free the result only if we were called on a table
		if ($table)
		{
			mysqli_free_result($id);
		}
		return $res;
	}

	/* public: find available table names */
	function table_names()
	{
		$this->connect();
		$h = @ mysqli_query($this->Link_ID, "show tables");
		$i = 0;
		while ($info = mysqli_fetch_row($h))
		{
			$return[$i]["table_name"] = $info[0];
			$return[$i]["tablespace_name"] = $this->Database;
			$return[$i]["database"] = $this->Database;
			$i ++;
		}

		mysqli_free_result($h);
		return $return;
	}

	/* private: error handling */
	function halt($msg)
	{
if ($this->Link_ID) {
         $this->Error = mysqli_error($this->Link_ID);
         $this->Errno = mysqli_errno($this->Link_ID);
      }
		if ($this->Halt_On_Error == "no")
			return;

		$this->haltmsg($_SERVER["REQUEST_URI"]." ".$msg);

		if ($this->Halt_On_Error != "report")
			die("Session halted.");
	}

	function haltmsg($msg)
	{
		printf("</td></tr></table><b>Database error:</b> %s<br>\n", $msg);
		printf("<b>MySQL Error</b>: %s (%s)<br>\n", $this->Errno, $this->Error);
	}
	
    function close()
    {
       if ($this->Link_ID) {
           return mysqli_close($this->Link_ID);
       } else {
           return false;
       }
    }
   
    function server_info()
    {
        $arr['description'] = mysqli_get_server_info($this->Link_ID); 
        return $arr;
    }
}
?>