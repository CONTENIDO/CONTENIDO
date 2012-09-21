<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO daabase, session and authentication classes
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    CONTENIDO Core
 * @version    1.7.1
 * @author     Boris Erdmann, Kristian Koehntopp
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 *
 * {@internal
 *   created  2000-01-01
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * @package    CONTENIDO Core
 * @subpackage Database
 */

class DB_Contenido extends DB_Sql {

    /**
     * Constructor of database class.
     *
     * @param array  $options  Optional assoziative options. The value depends
     *                          on used DBMS, but is generally as follows:
     *                          - $options['connection']['host']  (string) Hostname  or ip
     *                          - $options['connection']['database']  (string) Database name
     *                          - $options['connection']['user']  (string) User name
     *                          - $options['connection']['password']  (string)  User password
     *                          - $options['haltBehavior']  (string)  Optional, halt behavior on occured errors
     *                          - $options['haltMsgPrefix']  (string)  Optional, Text to prepend to the halt message
     *                          - $options['enableProfiling']  (bool)  Optional, flag to enable profiling
     * @throws cException if the database connection could not be established
     * @return void
     */
    public function __construct(array $options = array()) {
        global $cachemeta;
        global $cfg;

        parent::__construct($options);

        if (!is_array($cachemeta)) {
            $cachemeta = array();
        }

        if ($this->Errno == 1) {
            $backendUrl = cRegistry::getBackendUrl();
            $contenidoPath = (empty($backendUrl))? $cfg['path']['frontend'] : $backendUrl;

            $errormessage = i18n('The MySQL Database for the installation %s is not reachable. Please check if this is a temporary problem or if it is a real fault.');
            $errormessage = sprintf($errormessage, $contenidoPath);

            throw new cException($errormessage);
        }
    }

    /**
     * Fetches the next recordset from result set
     *
     * @param  bool
     */
    public function next_record() {
        global $cCurrentModule;

        if (!$this->Query_ID) {
            if ($cCurrentModule > 0) {
                $this->halt('next_record called with no query pending in Module ID ' . $cCurrentModule . '.');
            } else {
                $this->halt('next_record called with no query pending.');
            }
            return false;
        }

        return parent::next_record();
    }

    /**
     *
     * Get last inserted id of given tablename
     *
     * @param string $tableName
     * @return int|null last id of table
     */
    public function getLastInsertedId($tableName = '') {
        $lastId = null;
        if (strlen($tableName) > 0) {
            $sqlGetLastInsertedId = 'SELECT LAST_INSERT_ID() as last_id FROM ' . $tableName;
            $this->query($sqlGetLastInsertedId);
            if ($this->next_record()) {
                $lastId = $this->f('last_id');
            }
        }

        return $lastId;
    }

    /**
     * Returns the metada of passed table
     *
     * @param   string  $sTable  The tablename of empty string to retrieve metadata of all tables!
     * @return  array|bool   Assoziative metadata array (result depends on used db driver)
     *                       or false in case of an error
     * @deprecated [2011-03-03] Use db drivers toArray() method instead
     */
    public function copyResultToArray($sTable = '') {
        global $cachemeta;
        cDeprecated('Use db drivers toArray() method instead');

        $aValues = array();

        if ($sTable != '') {
            if (array_key_exists($sTable, $cachemeta)) {
                $aMetadata = $cachemeta[$sTable];
            } else {
                $cachemeta[$sTable] = $this->metadata($sTable);
                $aMetadata = $cachemeta[$sTable];
            }
        } else {
            $aMetadata = $this->metadata($sTable);
        }

        if (!is_array($aMetadata) || count($aMetadata) == 0) {
            return false;
        }

        foreach ($aMetadata as $entry) {
            $aValues[$entry['name']] = $this->f($entry['name']);
        }

        return $aValues;
    }

}

/**
 * @deprecated 2012-09-06 This class is not supported any longer.
 */
class Contenido_CT_Sql {
    public function __construct() {
        cDeprecated("This class is not supported any longer.");
    }
}

/**
 * @deprecated 2012-09-06 This class is not supported any longer.
 */
class Contenido_CT_File {
    public function __construct() {
        cDeprecated("This class is not supported any longer.");
    }
}

/**
 * @deprecated 2012-09-06 This class is not supported any longer.
 */
class Contenido_CT_Session {
    public function __construct() {
        cDeprecated("This class is not supported any longer.");
    }
}

/**
 * @package    CONTENIDO Core
 * @subpackage Session
 * @deprecated This class was replaced by cSession. Please use that instead
 */
class Contenido_Session extends Session {

    public $classname = 'Contenido_Session';
    public $cookiename = 'contenido';        ## defaults to classname
    public $magic = '123Hocuspocus';    ## ID seed
    public $mode = 'get';              ## We propagate session IDs with cookies
    public $fallback_mode = 'cookie';
    public $lifetime = 0;                  ## 0 = do session cookies, else minutes
    public $that_class = 'Contenido_CT_Sql'; ## name of data storage container
    public $gc_probability = 5;

    public function __construct() {
        global $cfg;

        cDeprecated('This class was replaced by cSession. Please use it instead.');

        $sFallback = 'sql';
        $sClassPrefix = 'Contenido_CT_';

        $sStorageContainer = strtolower($cfg['session_container']);

        if (class_exists($sClassPrefix . ucfirst($sStorageContainer))) {
            $sClass = $sClassPrefix . ucfirst($sStorageContainer);
        } else {
            $sClass = $sClassPrefix . ucfirst($sFallback);
        }

        $this->that_class = $sClass;
    }

    public function delete() {
        $oCol = new cApiInUseCollection();
        $oCol->removeSessionMarks($this->id);
        parent::delete();
    }

}

/**
 * @package    CONTENIDO Core
 * @subpackage Session
 * @deprecated This class was replaced by cFrontendSession. Please use that instead
 */
class Contenido_Frontend_Session extends Session {

    public $classname = 'Contenido_Frontend_Session';
    public $cookiename = 'sid';              ## defaults to classname
    public $magic = 'Phillipip';        ## ID seed
    public $mode = 'cookie';           ## We propagate session IDs with cookies
    public $fallback_mode = 'cookie';
    public $lifetime = 0;                  ## 0 = do session cookies, else minutes
    public $that_class = 'Contenido_CT_Sql'; ## name of data storage container
    public $gc_probability = 5;

    public function __construct() {
        global $load_lang, $load_client, $cfg;

        cDeprecated('This class was replaced by cFrontendSession. Please use it instead.');

        $this->cookiename = 'sid_' . $load_client . '_' . $load_lang;

        $this->setExpires(time() + 3600);

        // added 2007-10-11, H. Librenz
        // bugfix (found by dodger77): we need alternative session containers
        //                             also in frontend
        $sFallback = 'sql';
        $sClassPrefix = 'Contenido_CT_';

        $sStorageContainer = strtolower($cfg['session_container']);

        if (class_exists($sClassPrefix . ucfirst($sStorageContainer))) {
            $sClass = $sClassPrefix . ucfirst($sStorageContainer);
        } else {
            $sClass = $sClassPrefix . ucfirst($sFallback);
        }

        $this->that_class = $sClass;
    }

}

/**
 * Base CONTENIDO authentication class
 * @package    CONTENIDO Core
 * @subpackage Authentication
 */
class Contenido_Auth extends Auth {
}

/**
 * Contenido_Challenge_Crypt_Auth: Keep passwords in md5 hashes rather than cleartext in database
 * @package    CONTENIDO Core
 * @subpackage Authentication
 * @author     Jim Zajkowski <jim@jimz.com>
 */
class Contenido_Challenge_Crypt_Auth extends Auth {

    public $classname = 'Contenido_Challenge_Crypt_Auth';
    public $lifetime = 15;
    public $magic = 'Frrobo123xxica';  ## Challenge seed
    public $database_table = '';
    public $group_table = '';
    public $member_table = '';

    public function __construct() {
        global $cfg;
        $this->database_table = $cfg['tab']['phplib_auth_user_md5'];
        $this->group_table = $cfg['tab']['groups'];
        $this->member_table = $cfg['tab']['groupmembers'];
        $this->lifetime = $cfg['backend']['timeout'];

        if ($this->lifetime == 0) {
            $this->lifetime = 15;
        }
    }

    public function auth_loginform() {
        global $sess, $challenge, $_PHPLIB;

        $challenge = md5(uniqid($this->magic));
        $sess->register('challenge');

        include(cRegistry::getBackendPath() . 'main.loginform.php');
    }

    public function auth_loglogin($uid) {
        global $cfg, $client, $lang, $sess, $saveLoginTime;

        $perm = new cPermission();
        $idcatart = 0;

        /* Find the first accessible client and language for the user */
        // All the needed information should be available in clients_lang - but the previous code was designed with a
        // reference to the clients table. Maybe fail-safe technology, who knows...
        // @todo Replace following query against usage of a model
        $sql = 'SELECT cl.idclient, cl.idlang FROM %s AS c, %s AS cl ' .
                'WHERE c.idclient = cl.idclient ORDER BY idclient ASC, idlang ASC';
        $this->db->query($sql, $cfg['tab']['clients'], $cfg['tab']['clients_lang']);

        $bFound = false;
        while ($this->db->next_record() && !$bFound) {
            $iTmpClient = $this->db->f('idclient');
            $iTmpLang = $this->db->f('idlang');

            if ($perm->have_perm_client_lang($iTmpClient, $iTmpLang)) {
                $client = $iTmpClient;
                $lang = $iTmpLang;
                $bFound = true;
            }
        }

        if (!is_numeric($client) || !is_numeric($lang)) {
            return;
        }

        if (isset($idcat) && isset($idart)) {
            $catArtColl = new cApiCategoryArticleCollection();
            if ($rs = $catArtColl->fetchByCategoryIdAndArticleId($idcat, $idart)) {
                $idcatart = $rs->get('idcatart');
            }
        }

        $idaction = $perm->getIDForAction('login');

        // create a actionlog entry
        $actionLogCol = new cApiActionlogCollection();
        $actionLogCol->create($uid, $client, $lang, $idaction, $idcatart);

        $sess->register('saveLoginTime');
        $saveLoginTime = true;
    }

    public function auth_validatelogin() {
        global $username, $password, $challenge, $response, $formtimestamp, $auth_handlers;

        $gperm = array();

        if ($password == '') {
            return false;
        }

        if (($formtimestamp + (60 * 15)) < time()) {
            return false;
        }

        if (isset($username)) {
            // This provides access for 'loginform.ihtml'
            $this->auth['uname'] = $username;
        } elseif ($this->nobody) {
            //  provides for 'default login cancel'
            $uid = $this->auth['uname'] = $this->auth['uid'] = self::AUTH_UID_NOBODY;
            return $uid;
        }

        $uid = false;
        $perm = false;
        $pass = false;

        $sDate = date('Y-m-d');

        $sql = "SELECT user_id, perms, password FROM %s WHERE username = '%s' AND
            (valid_from <= '%s' OR valid_from = '0000-00-00' OR valid_from is NULL) AND
            (valid_to >= '%s' OR valid_to = '0000-00-00' OR valid_to is NULL)";
        $this->db->query($sql, $this->database_table, $username, $sDate, $sDate);

        $sMaintenanceMode = getSystemProperty('maintenance', 'mode');
        while ($this->db->next_record()) {
            $uid = $this->db->f('user_id');
            $perm = $this->db->f('perms');
            $pass = $this->db->f('password'); // Password is stored as a md5 hash

            $bInMaintenance = false;
            if ($sMaintenanceMode == 'enabled') {
                // sysadmins are allowed to login every time
                if (!preg_match('/sysadmin/', $perm)) {
                    $bInMaintenance = true;
                }
            }

            if ($bInMaintenance) {
                unset($uid, $perm, $pass);
            }

            if (is_array($auth_handlers) && !$bInMaintenance) {
                if (array_key_exists($pass, $auth_handlers)) {
                    $success = call_user_func($auth_handlers[$pass], $username, $password);

                    if ($success) {
                        $uid = md5($username);
                        $pass = md5($password);
                    }
                }
            }
        }

        if ($uid == false) {
            // No user found, sleep and exit
            sleep(5);
            return false;
        } else {
            $sql = 'SELECT a.group_id AS group_id, a.perms AS perms '
                    . "FROM %s AS a, %s AS b WHERE a.group_id = b.group_id AND b.user_id = '%s'";
            $this->db->query($sql, $this->group_table, $this->member_table, $uid);

            if ($perm != '') {
                $gperm[] = $perm;
            }

            while ($this->db->next_record()) {
                $gperm[] = $this->db->f('perms');
            }

            if (is_array($gperm)) {
                $perm = implode(',', $gperm);
            }

            if ($response == '') {                   ## True when JS is disabled
                if (md5($password) != $pass) {       ## md5 hash for non-JavaScript browsers
                    sleep(5);
                    return false;
                } else {
                    $this->auth['perm'] = $perm;
                    $this->auth_loglogin($uid);
                    return $uid;
                }
            }

            $expected_response = md5("$username:$pass:$challenge");

            if ($expected_response != $response) {   ## Response is set, JS is enabled
                sleep(5);
                return false;
            } else {
                $this->auth['perm'] = $perm;
                $this->auth_loglogin($uid);
                return $uid;
            }
        }
    }

}

/**
 * @package    CONTENIDO Core
 * @subpackage Authentication
 */
class Contenido_Frontend_Challenge_Crypt_Auth extends Auth {

    public $classname = 'Contenido_Frontend_Challenge_Crypt_Auth';
    public $lifetime = 15;
    public $magic = 'Frrobo123xxica';  ## Challenge seed
    public $database_table = '';
    public $fe_database_table = '';
    public $group_table = '';
    public $member_table = '';
    public $nobody = true;

    public function __construct() {
        global $cfg;
        $this->database_table = $cfg['tab']['phplib_auth_user_md5'];
        $this->fe_database_table = $cfg['tab']['frontendusers'];
        $this->group_table = $cfg['tab']['groups'];
        $this->member_table = $cfg['tab']['groupmembers'];
    }

    public function auth_preauth() {
        global $password;

        if ($password == '') {
            // Stay as nobody when an empty password is passed
            $uid = $this->auth['uname'] = $this->auth['uid'] = self::AUTH_UID_NOBODY;
            return false;
        }

        return $this->auth_validatelogin();
    }

    public function auth_loginform() {
        global $sess, $challenge, $_PHPLIB, $client;

        $challenge = md5(uniqid($this->magic));
        $sess->register('challenge');

        include(cRegistry::getFrontendPath() . 'front_crcloginform.inc.php');
    }

    public function auth_validatelogin() {
        global $username, $password, $challenge, $response, $auth_handlers, $client;

        $client = (int) $client;

        if (isset($username)) {
            // This provides access for 'loginform.ihtml'
            $this->auth['uname'] = $username;
        } else if ($this->nobody) {
            // provides for 'default login cancel'
            $uid = $this->auth['uname'] = $this->auth['uid'] = self::AUTH_UID_NOBODY;
            return $uid;
        }

        $uid = false;

        // Authentification via frontend users
        $sql = "SELECT idfrontenduser, password FROM %s WHERE username = '%s' AND idclient=%d AND active=1";
        $this->db->query($sql, $this->fe_database_table, $username, $client);

        if ($this->db->next_record()) {
            $uid = $this->db->f('idfrontenduser');
            $perm = 'frontend';
            $pass = $this->db->f('password');
        }

        if ($uid == false) {
            // Authentification via backend users
            $sql = "SELECT user_id, perms, password FROM %s WHERE username = '%s'";
            $this->db->query($sql, $this->database_table, $username);

            while ($this->db->next_record()) {
                $uid = $this->db->f('user_id');
                $perm = $this->db->f('perms');
                $pass = $this->db->f('password');   ## Password is stored as a md5 hash

                if (is_array($auth_handlers)) {
                    if (array_key_exists($pass, $auth_handlers)) {
                        $success = call_user_func($auth_handlers[$pass], $username, $password);
                        if ($success) {
                            $uid = md5($username);
                            $pass = md5($password);
                        }
                    }
                }
            }

            if ($uid !== false) {
                $sql = "SELECT a.group_id AS group_id, a.perms AS perms "
                        . "FROM %s AS a, %s AS b WHERE a.group_id = b.group_id AND b.user_id = '%s'";
                $this->db->query($sql, $this->group_table, $this->member_table, $uid);

                /* Deactivated: Backend user would be sysadmin when logged on as frontend user
                 *  (and perms would be checked), see http://www.contenido.org/forum/viewtopic.php?p=85666#85666
                  $perm = 'sysadmin'; */
                if ($perm != '') {
                    $gperm[] = $perm;
                }

                while ($this->db->next_record()) {
                    $gperm[] = $this->db->f('perms');
                }

                if (is_array($gperm)) {
                    $perm = implode(',', $gperm);
                }
            }
        }

        if ($uid == false) {
            // User not found, sleep and exit
            sleep(5);
            return false;
        } else {
            if ($response == '') {                   ## True when JS is disabled
                if (md5($password) != $pass) {       ## md5 hash for non-JavaScript browsers
                    sleep(5);
                    return false;
                } else {
                    $this->auth['perm'] = $perm;
                    return $uid;
                }
            }

            $expected_response = md5("$username:$pass:$challenge");
            if ($expected_response != $response) {   ## Response is set, JS is enabled
                sleep(5);
                return false;
            } else {
                $this->auth['perm'] = $perm;
                return $uid;
            }
        }
    }

}

/**
 * Registers an external auth handler
 */
function register_auth_handler($aHandlers) {
    global $auth_handlers;

    if (!is_array($auth_handlers)) {
        $auth_handlers = array();
    }

    if (!is_array($aHandlers)) {
        $aHandlers = Array($aHandlers);
    }

    foreach ($aHandlers as $sHandler) {
        if (!in_array($sHandler, $auth_handlers)) {
            $auth_handlers[md5($sHandler)] = $sHandler;
        }
    }
}

?>