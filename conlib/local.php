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
 *
 * @package    Contenido Backend <Area>
 * @version    1.51
 * @author     Boris Erdmann, Kristian Koehntopp
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 *
 * {@internal
 *   created  2000-01-01
 *   modified 2008-07-04, bilal arslan, added security fix
 *   modified 2010-02-02, Ingo van Peeren, added local method connect() in order
 *                                         to allow only one database connection, see [CON-300]
 *   modified 2010-02-17, Ingo van Peeren, only one connection for mysqli too
 *   modified 2011-02-26, Ortwin Pinke, added temporary pw request behaviour, so user may login with old and/or requested pw
 *
 *   $Id: local.php 1309 2011-02-26 14:32:42Z oldperl $:
 * }}
 *
 */

if(!defined('CON_FRAMEWORK')) {
   die('Illegal call');
}

class DB_Contenido extends DB_Sql {

  var $Host;
  var $Database;
  var $User;
  var $Password;

  var $Halt_On_Error = "report";

  //Konstruktor
  function DB_Contenido($Host = "", $Database = "", $User = "", $Password = "")
  {
      global $cachemeta, $contenido_host, $contenido_database, $contenido_user, $contenido_password;

	  if ($Database)
	  {
	  		$this->Database = $Database;
	  } else {
	  		$this->Database = $contenido_database;
	  }

	  if ($Host)
	  {
	  		$this->Host = $Host;
	  } else {
	  		$this->Host = $contenido_host;
	  }

	  if ($User)
	  {
	  		$this->User = $User;
	  } else {
	  		$this->User = $contenido_user;
	  }

	  if ($Password)
	  {
	  		$this->Password = $Password;
	  } else {
			$this->Password = $contenido_password;
	  }

      if (!is_array($cachemeta))
      {
      	$cachemeta = array();
      }

      // TODO check this out
      // HerrB: Checked and disabled. Kills umlauts, if tables are latin1_general.

      // try to use the new connection and get the needed encryption
      //$this->query("SET NAMES 'utf8'");
  }

  // Wrapper for parent connect methods in order to allow only 1 database connection
  function connect($Database = "", $Host = "", $User = "", $Password = "") {
      global $db_link;
      
      if ((0 == $db_link || !is_resource($db_link)) && !is_object($db_link)) {
          $db_link = parent::connect($Database, $Host, $User, $Password);
          global $contenido_charset;
          if (!empty($contenido_charset)) {
            $this->query('SET NAMES "' . $this->escape($contenido_charset) . '"');
          }
      }

      $this->Link_ID = $db_link;

      return $this->Link_ID;
  }

  function haltmsg($msg) {
    error_log($msg);
  }

  function copyResultToArray ($table = "")
  {
  		global $cachemeta;

  		$values = array();

  		if ($table != "")
  		{
  			if (array_key_exists($table, $cachemeta))
  			{
  				$metadata = $cachemeta[$table];
  			} else {
  				$cachemeta[$table] = $this->metadata();
  				$metadata = $cachemeta[$table];
  			}
  		} else {
  			$metadata = $this->metadata();
  		}

		if (!is_array($metadata))
		{
			return false;
		}

		foreach ($metadata as $entry)
		{
			$values[$entry['name']] = $this->f($entry['name']);
		}

		return $values;
  }
}

class Contenido_CT_Sql extends CT_Sql {

  var $database_class = "DB_Contenido";          ## Which database to connect...
  var $database_table = ""; ## and find our session data in this table.

  function Contenido_CT_Sql ()
  {
  	global $cfg;
  	$this->database_table = $cfg["tab"]["phplib_active_sessions"];
  }
}

/**
 * Implements the interface class for storing session data
 * to disk using file session container of phplib.
 */
class Contenido_CT_File extends CT_File {

	/**
	 * The maximum length for one line
	 * in session file.
	 *
	 * @var int
	 */
	var $iLineLength = 999999;

	/**
	 * Overrides standard constructor
	 * for setting up file path to
	 * the one which is configured
	 * in php.ini
	 *
	 * @return Contenido_CT_File
	 *
	 * @author Holger Librenz <holger.librenz@4fb.de>
	 */
    function Contenido_CT_File () {
    	global $cfg;

    	if (isset($cfg['session_line_length']) &&
    	      !empty($cfg['session_line_length'])) {
    	   	$this->iLineLength = (int) $cfg['session_line_length'];
    	}

        // get php.ini value for session path
        $this->file_path = session_save_path() . '/';
    }

    /**
     * Overrides get method, because standard
     * byte count is not really senseful for
     * contenido!
     *
     * @param string $id
     * @param string $name
     * @return mixed
     */
    function ac_get_value($id, $name) {
        if(file_exists($this->file_path."$id$name"))
        {
            $f=fopen($this->file_path."$id$name",'r');
            if($f<0)
                return '';

            $s=fgets($f,$this->iLineLength);
            fclose($f);

            return urldecode($s);
        }
        else
            return '';
    }
}

class Contenido_CT_Shm extends CT_Shm {
    function Contenido_CT_Shm ()  {
        $this->ac_start();
    }
}


class Contenido_Session extends Session {

  var $classname		= "Contenido_Session";

  var $cookiename     	= "contenido";        ## defaults to classname
  var $magic          	= "123Hocuspocus";    ## ID seed
  var $mode           	= "get";              ## We propagate session IDs with cookies
  var $fallback_mode  	= "cookie";
  var $lifetime       	= 0;                  ## 0 = do session cookies, else minutes
  var $that_class     	= "Contenido_CT_Sql"; ## name of data storage container

  function Contenido_Session () {
      global $cfg;

      $this->gc_time = $cfg['session']['backend']['lifetime'];
      $this->gc_probability = $cfg['session']['backend']['gc_probability'];
	  $this->lifetime = $cfg['session']['backend']['lifetime'];

      $sFallback = 'sql';
      $sClassPrefix = 'Contenido_CT_';

      $sStorageContainer = strtolower($cfg['session_container']);

      if (class_exists ($sClassPrefix . ucfirst($sStorageContainer))) {
          $sClass = $sClassPrefix . ucfirst($sStorageContainer);
      } else {
          $sClass = $sClassPrefix . ucfirst($sFallback);
      }

      $this->that_class = $sClass;

  }

  function delete ()
  {
  	$col = new InUseCollection;
	$col->removeSessionMarks($this->id);

	parent::delete();
  }
}

class Contenido_Frontend_Session extends Session {

  var $classname = "Contenido_Frontend_Session";

  var $cookiename     = "sid";              ## defaults to classname
  var $magic          = "Phillipip";        ## ID seed
  var $mode           = "cookie";           ## We propagate session IDs with cookies
  var $fallback_mode  = "cookie";
  var $that_class     = "Contenido_CT_Sql"; ## name of data storage container

  function Contenido_Frontend_Session () {
	global $load_lang, $load_client, $cfg;

    $this->gc_time = $cfg['session']['frontend']['lifetime'];
	$this->gc_probability = $cfg['session']['frontend']['gc_probability'];
	$this->lifetime = $cfg['session']['frontend']['lifetime'];

  	$this->cookiename = "sid_".$load_client."_".$load_lang;

  	$this->setExpires(time()+3600);

  	/*
  	 * added 2007-10-11, H. Librenz	- bugfix (found by dodger77): we need alternative session containers
  	 * 									also in frontend ;)
  	 */
    $sFallback = 'sql';
    $sClassPrefix = 'Contenido_CT_';

    $sStorageContainer = strtolower($cfg['session_container']);

    if (class_exists ($sClassPrefix . ucfirst($sStorageContainer))) {
       $sClass = $sClassPrefix . ucfirst($sStorageContainer);
    } else {
      $sClass = $sClassPrefix . ucfirst($sFallback);
    }

    $this->that_class = $sClass;
  }
}

class Contenido_Auth extends Auth {
  var $classname      = "Contenido_Auth";

  var $lifetime       =  15;

  var $database_class = "DB_Contenido";
  var $database_table = "con_phplib_auth_user";

  function auth_loginform() {
    global $sess;
    global $_PHPLIB;

    include($_PHPLIB["libdir"] . "loginform.ihtml");
  }

function auth_validatelogin() {
    global $username, $password;

    if ($password == "")
    {
    	return false;
    }

    if(isset($username)) {
        $this->auth["uname"]=$username;     ## This provides access for "loginform.ihtml"
    }else if ($this->nobody){                      ##  provides for "default login cancel"
        $uid = $this->auth["uname"] = $this->auth["uid"] = "nobody";
        return $uid;
    }
    $uid = false;


    $this->db->query(sprintf("select user_id, perms from %s ".
                             "where username = '%s' and password = '%s'",
                          $this->database_table,
                          addslashes($username),
                          addslashes($password)));

    while($this->db->next_record()) {
      $uid = $this->db->f("user_id");
      $this->auth["perm"] = $this->db->f("perms");
    }
    return $uid;
  }
}

class Contenido_Default_Auth extends Contenido_Auth {

  var $classname = "Contenido_Default_Auth";
  var $lifetime       =  1;

  function auth_loginform() {

    global $sess;
    global $_PHPLIB;

    include($_PHPLIB["libdir"] . "defloginform.ihtml");
  }

  var $nobody    = true;
}

class Contenido_Challenge_Auth extends Auth {
  var $classname      = "Contenido_Challenge_Auth";

  var $lifetime       =  1;

  var $magic          = "Simsalabim";  ## Challenge seed
  var $database_class = "DB_Contenido";
  var $database_table = "con_phplib_auth_user";

  function auth_loginform() {
    global $sess;
    global $challenge;
    global $_PHPLIB;

    $challenge = md5(uniqid($this->magic));
    $sess->register("challenge");

    include($_PHPLIB["libdir"] . "crloginform.ihtml");
  }

  function auth_validatelogin() {
    global $username, $password, $challenge, $response, $timestamp;

    if ($password == "")
    {
    	return false;
    }

    if(isset($username)) {
      $this->auth["uname"]=$username;        ## This provides access for "loginform.ihtml"
    }

    # Sanity check: If the user presses "reload", don't allow a login with the data
    # again. Instead, prompt again.
    if ($timestamp < (time() - 60*15))
    {
        return false;
    }
    $this->db->query(sprintf("select user_id,perms,password ".
                "from %s where username = '%s'",
                          $this->database_table,
                          addslashes($username)));

    while($this->db->next_record()) {
      $uid   = $this->db->f("user_id");
      $perm  = $this->db->f("perms");
      $pass  = $this->db->f("password");
    }
    $exspected_response = md5("$username:$pass:$challenge");

    ## True when JS is disabled
    if ($response == "") {
      if ($password != $pass) {
        return false;
      } else {
        $this->auth["perm"] = $perm;
        return $uid;
      }
    }

    ## Response is set, JS is enabled
    if ($exspected_response != $response) {
      return false;
    } else {
      $this->auth["perm"] = $perm;
      return $uid;
    }
  }
}

##
## Contenido_Challenge_Crypt_Auth: Keep passwords in md5 hashes rather
##                           than cleartext in database
## Author: Jim Zajkowski <jim@jimz.com>

class Contenido_Challenge_Crypt_Auth extends Auth {

  var $classname      = "Contenido_Challenge_Crypt_Auth";
  var $lifetime       =  15;
  var $magic          = "Frrobo123xxica";  ## Challenge seed
  var $database_class = "DB_Contenido";
  var $database_table = "";
  var $group_table = "";
  var $member_table = "";

  function Contenido_Challenge_Crypt_Auth ()
  {
		global $cfg;
	 	$this->database_table = $cfg["tab"]["phplib_auth_user_md5"];
		$this->group_table = $cfg["tab"]["groups"];
		$this->member_table = $cfg["tab"]["groupmembers"];
		$this->lifetime = $cfg["backend"]["timeout"];

		if ($this->lifetime == 0)
		{
			$this->lifetime = 15;
		}
  }

  function auth_loginform() {

    global $sess;
    global $challenge;
    global $_PHPLIB;
    global $cfg;

    $challenge = md5(uniqid($this->magic));
    $sess->register("challenge");

    include ($cfg["path"]["contenido"] . 'main.loginform.php');

  }

  function auth_loglogin($uid)
  {
        global $cfg, $client, $lang, $auth, $sess, $saveLoginTime;

        $perm = new Contenido_Perm;

        $timestamp	= date("Y-m-d H:i:s");
        $idcatart	= "0";

    	/* Find the first accessible client and language for the user */
		// All the needed information should be available in clients_lang - but the previous code was designed with a
		// reference to the clients table. Maybe fail-safe technology, who knows...
    	$sql = "SELECT tblClientsLang.idclient, tblClientsLang.idlang FROM ".
    		   $cfg["tab"]["clients"]." AS tblClients, ".$cfg["tab"]["clients_lang"]." AS tblClientsLang ".
        	   "WHERE tblClients.idclient = tblClientsLang.idclient ORDER BY idclient ASC, idlang ASC";
		$this->db->query($sql);

    	$bFound = false;
    	while ($this->db->next_record() && !$bFound)
		{
			$iTmpClient	= $this->db->f("idclient");
			$iTmpLang	= $this->db->f("idlang");

			if ($perm->have_perm_client_lang($iTmpClient, $iTmpLang))
			{
				$client	= $iTmpClient;
				$lang	= $iTmpLang;
				$bFound = true;
			}
		}

        if (isset($idcat) && isset($idart))
        {

//            SECURITY FIX
            $sql = "SELECT idcatart
                    FROM
                       ". $cfg["tab"]["cat_art"] ."
                    WHERE
                        idcat = '".Contenido_Security::toInteger($idcat)."' AND
                        idart = '".Contenido_Security::toInteger($idart)."'";

            $this->db->query($sql);

            $this->db->next_record();
            $idcatart = $this->db->f("idcatart");
        }

        if (!is_numeric($client)) { return; }
        if (!is_numeric($lang)) { return;  }

		$idaction	= $perm->getIDForAction("login");
		$lastentry	= $this->db->nextid($cfg["tab"]["actionlog"]);

        $sql = "INSERT INTO
                    ". $cfg["tab"]["actionlog"]."
                SET
                    idlog = $lastentry,
                    user_id = '" . $uid . "',
                    idclient = '".Contenido_Security::toInteger($client)."',
                    idlang = '".Contenido_Security::toInteger($lang)."',
                    idaction = $idaction,
                    idcatart = $idcatart,
                    logtimestamp = '$timestamp'";

        $this->db->query($sql);

        $sess->register("saveLoginTime");

        $saveLoginTime = true;
	}

  function auth_validatelogin() {

    global $username, $password, $challenge, $response, $formtimestamp, $auth_handlers;

    $gperm = array();

    if ($password == "")
    {
    	return false;
    }

    if (($formtimestamp + (60*15)) < time())
    {
    	return false;
    }

    if(isset($username)) {
        $this->auth["uname"]=$username;     ## This provides access for "loginform.ihtml"
    }else if ($this->nobody){                      ##  provides for "default login cancel"
        $uid = $this->auth["uname"] = $this->auth["uid"] = "nobody";
        return $uid;
    }

    $uid  = false;
	$perm = false;
	$pass = false;

    $sDate = date('Y-m-d');

    $this->db->query(sprintf("select user_id,perms,password,tmp_pw_request from %s where username = '%s' AND
                              (valid_from <= '".$sDate."' OR valid_from = '0000-00-00' OR valid_from is NULL) AND
                              (valid_to >= '".$sDate."' OR valid_to = '0000-00-00' OR valid_to is NULL)",
							 $this->database_table,
							  Contenido_Security::escapeDB($username,  $this->db)));

    // check if requested new password is used, if set equal password
    $sRequestPassword = $this->db->f("tmp_pw_request");
    $this->auth['pwr'] = false;
    if(md5($password) == $sRequestPassword) {
        $sQuery =   "UPDATE ".$this->database_table." SET password = '".$sRequestPassword."',
                         tmp_pw_request = NULL,
                         using_pw_request = 1,
                         WHERE username = '".Contenido_Security::escapeDB($username,  $this->db)."'";
        $this->db->query($sQuery);
        $pass = $sRequestPassword;
        $this->auth['pwr'] = true;
    }


    $sMaintenanceMode = getSystemProperty('maintenance', 'mode');
    while($this->db->next_record()) {
		$uid   = $this->db->f("user_id");
		$perm  = $this->db->f("perms");
		$pass  = $this->db->f("password");   ## Password is stored as a md5 hash

		$bInMaintenance = false;
        if ($sMaintenanceMode == 'enabled') {
            #sysadmins are allowed to login every time
            if (!preg_match('/sysadmin/', $perm)) {
                $bInMaintenance = true;
            }
        }

        if ($bInMaintenance) {
            unset($uid);
            unset($perm);
            unset($pass);
        }

		if (is_array($auth_handlers) && !$bInMaintenance)
		{
    		if (array_key_exists($pass, $auth_handlers))
    		{
    			$success = call_user_func($auth_handlers[$pass], $username, $password);

    			if ($success)
    			{
    				$uid = md5($username);
        			$pass = md5($password);
    			}
    		}
		}
	}

    if ($uid == false)
    {
    	## No user found, sleep and exit
    	sleep(5);
    	return false;
    } else {
    	$this->db->query(sprintf("select A.group_id as group_id, A.perms as perms ".
								 "from %s AS A, %s AS B where A.group_id = B.group_id AND B.user_id = '%s'",
								 $this->group_table,
								 $this->member_table,
								 $uid));

		if ($perm != "")
		{
			$gperm[] = $perm;
		}

    	while ($this->db->next_record())
    	{
    		$gperm[] = $this->db->f("perms");
    	}

    	if (is_array($gperm))
    	{
    		$perm = implode(",",$gperm);
    	}

    	if ($response == "")					## True when JS is disabled
    	{
      		if (md5($password) != $pass)		## md5 hash for non-JavaScript browsers
      		{
				sleep(5);
        		return false;
      		} else {
        		$this->auth["perm"] = $perm;
        		$this->auth_loglogin($uid);
        		return $uid;
      		}
    	}

    	$expected_response = md5("$username:$pass:$challenge");

    	if ($expected_response != $response)	## Response is set, JS is enabled
    	{
			sleep(5);
			return false;
    	} else {
      		$this->auth["perm"] = $perm;
      		$this->auth_loglogin($uid);
      		return $uid;
    	}
    }
  }
}

class Contenido_Frontend_Challenge_Crypt_Auth extends Auth {
  var $classname      = "Contenido_Frontend_Challenge_Crypt_Auth";
  var $lifetime       =  15;
  var $magic          = "Frrobo123xxica";  ## Challenge seed
  var $database_class = "DB_Contenido";
  var $database_table = "";
  var $fe_database_table = "";
  var $group_table    = "";
  var $member_table   = "";
  var $nobody         = true;

  function Contenido_Frontend_Challenge_Crypt_Auth ()
  {
  	global $cfg;
  	$this->database_table = $cfg["tab"]["phplib_auth_user_md5"];
  	$this->fe_database_table = $cfg["tab"]["frontendusers"];
	$this->group_table = $cfg["tab"]["groups"];
	$this->member_table = $cfg["tab"]["groupmembers"];
  }

  function auth_preauth()
  {
    global $password;

	if ($password == "")
	{
		/* Stay as nobody when an empty password is passed */
		$uid = $this->auth["uname"] = $this->auth["uid"] = "nobody";
		return false;
    }

    return $this->auth_validatelogin();
  }

  function auth_loginform()
  {
    global $sess;
    global $challenge;
    global $_PHPLIB;
    global $client;
    global $cfgClient;

    $challenge = md5(uniqid($this->magic));
    $sess->register("challenge");

    include($cfgClient[$client]["path"]["frontend"]."front_crcloginform.inc.php");
  }

  function auth_validatelogin()
  {
	global $username, $password, $challenge, $response, $auth_handlers, $client;



    $client = (int)$client;

    if(isset($username))
    {
        $this->auth["uname"] = $username;     ## This provides access for "loginform.ihtml"
    } else if ($this->nobody) {                      ##  provides for "default login cancel"
        $uid = $this->auth["uname"] = $this->auth["uid"] = "nobody";
        return $uid;
    }

    $uid = false;

    /* Authentification via frontend users */
    $this->db->query(sprintf("SELECT idfrontenduser, password FROM %s WHERE username = '%s' AND idclient='$client' AND active='1'",
    						 $this->fe_database_table,
    						 Contenido_Security::escapeDB(urlencode($username), $this->db )));

	if ($this->db->next_record())
	{
		$uid  = $this->db->f("idfrontenduser");
		$perm = "frontend";
		$pass = $this->db->f("password");
	}

	if ($uid == false)
	{
		/* Authentification via backend users */
    	$this->db->query(sprintf("select user_id, perms, password from %s where username = '%s'",
                          		 $this->database_table,
								  Contenido_Security::escapeDB($username, $this->db) ));

        while($this->db->next_record())
        {
			$uid   = $this->db->f("user_id");
			$perm  = $this->db->f("perms");
			$pass  = $this->db->f("password");   ## Password is stored as a md5 hash

			if (is_array($auth_handlers))
			{
    			if (array_key_exists($pass, $auth_handlers))
    			{
    				$success = call_user_func($auth_handlers[$pass], $username, $password);

    				if ($success)
    				{
    					$uid  = md5($username);
        				$pass = md5($password);
    				}
    			}
			}
		}

		if ($uid !== false) {
	    	$this->db->query(sprintf("select A.group_id as group_id, A.perms as perms ".
    	            				 "from %s AS A, %s AS B where A.group_id = B.group_id AND ".
    	            				 "B.user_id = '%s'",
        	                 		 $this->group_table,
            	             		 $this->member_table,
                	         		 $uid));

			/* Deactivated: Backend user would be sysadmin when logged on as frontend user
	     	*  (and perms would be checked), see http://www.contenido.org/forum/viewtopic.php?p=85666#85666
			$perm = "sysadmin"; */
			if ($perm != "")
			{
				$gperm[] = $perm;
			}

	    	while ($this->db->next_record())
    		{
    			$gperm[] = $this->db->f("perms");
    		}

	    	if (is_array($gperm))
    		{
    			$perm = implode(",",$gperm);
    		}
		}
	}

	if ($uid == false)
	{
		## User not found, sleep and exit
		sleep(5);
		return false;
	} else {
		if ($response == "")					## True when JS is disabled
		{
			if (md5($password) != $pass)		## md5 hash for non-JavaScript browsers
			{
				sleep(5);
				return false;
			} else {
				$this->auth["perm"] = $perm;
				return $uid;
			}
		}

		$expected_response = md5("$username:$pass:$challenge");
    	if ($expected_response != $response)	## Response is set, JS is enabled
		{
			sleep(5);
			return false;
		} else {
			$this->auth["perm"] = $perm;
			return $uid;
		}
	}
  }
}

/**
 * Registers an external auth handler
 */
function register_auth_handler($aHandlers)
{
	global $auth_handlers;

	if (!is_array($auth_handlers))
	{
		$auth_handlers = array();
	}

	if (!is_array($aHandlers))
	{
		$aHandlers = Array($aHandlers);
	}

	foreach ($aHandlers as $sHandler)
	{
		if (!in_array($sHandler, $auth_handlers))
		{
			$auth_handlers[md5($sHandler)] = $sHandler;
		}
	}
}

?>