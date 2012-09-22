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
 * Registers an external auth handler
 */
function register_auth_handler($aHandlers) {
    cDeprecated("Registering auth handlers is not supported any longer.");
}

?>