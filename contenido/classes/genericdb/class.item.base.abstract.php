<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Generic database abstract base item.
 *
 * NOTE:
 * Because of required downwards compatibilitiy all protected/private member
 * variables or methods don't have an leading underscore.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Classes
 * @version    0.2
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9
 *
 * {@internal
 *   created  2011-03-16
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


global $cfg;

// Try to load GenericDB database driver
$driver_filename = cRegistry::getBackendPath() . $cfg['path']['classes'] . 'drivers/' . $cfg['sql']['gdb_driver'] . '/class.gdb.' . $cfg['sql']['gdb_driver'] . '.php';

if (cFileHandler::exists($driver_filename)) {
    include_once($driver_filename);
}

/**
 * Class cItemException.
 * @author     Murat Purc <murat@purc.de>
 * @version    0.1
 * @copyright  four for business AG <www.4fb.de>
 * @deprecated 2012-09-04 Use one of the cException subclasses instead
 */
class cItemException extends cException {

    /**
     * @deprecated 2012-09-04 Use one of the cException subclasses instead
     */
    public function __construct($message, $code, $previous) {
        cDeprecated('Use one of the cException subclasses instead');
        parent::__construct($message, $code, $previous);
    }

}

/**
 * Class cItemBaseAbstract.
 * Base class with common features for database based items and item collections.
 */
abstract class cItemBaseAbstract extends cGenericDb {

    /**
     * Database instance, contains the database object
     * @var  cDb
     */
    protected $db;

    /**
     * Second DB instance, is required for some additional queries without
     * losing an current existing query result.
     * @var  cDb
     */
    protected $secondDb;

    /**
     * Property collection instance
     * @var  cApiPropertyCollection
     */
    protected $properties;

    /**
     * Item cache instance
     * @var  cItemCache
     */
    protected $_oCache;

    /**
     * GenericDB settings, see $cfg['sql']
     * @var  array
     */
    protected $_settings;

    /**
     * Storage of the source table to use for the information
     * @var  string
     */
    protected $table;

    /**
     * Storage of the primary key
     * @var  string
     * @todo remove access from public
     */
    public $primaryKey;

    /**
     * Checks for the virginity of created objects. If true, the object
     * is virgin and no operations on it except load-Functions are allowed.
     * @todo remove access from public
     * @var  bool
     */
    public $virgin;

    /**
     * Storage of the last occured error
     * @var  string
     */
    protected $lasterror = '';

    /**
     * Classname of current instance
     * @var  string
     */
    protected $_className;

    /**
     * Sets some common properties
     *
     * @param  string  $sTable       Name of table
     * @param  string  $sPrimaryKey  Primary key of table
     * @param  string  $sClassName   Name of parent class
     * @throws cInvalidArgumentException If table name or primary key is not set
     * @return void
     */
    protected function __construct($sTable, $sPrimaryKey, $sClassName) {
        global $cfg;

        $this->db = cRegistry::getDb();

        if ($sTable == '') {
            $sMsg = "$sClassName: No table specified. Inherited classes *need* to set a table";
            throw new cInvalidArgumentException($sMsg);
        } elseif ($sPrimaryKey == '') {
            $sMsg = "No primary key specified. Inherited classes *need* to set a primary key";
            throw new cInvalidArgumentException($sMsg);
        }

        $this->_settings = $cfg['sql'];

        // instantiate caching
        $aCacheOpt = (isset($this->_settings['cache'])) ? $this->_settings['cache'] : array();
        $this->_oCache = cItemCache::getInstance($sTable, $aCacheOpt);

        $this->table = $sTable;
        $this->primaryKey = $sPrimaryKey;
        $this->virgin = true;
        $this->_className = $sClassName;
    }

    /**
     * Escape string for using in SQL-Statement.
     *
     * @param   string  $sString  The string to escape
     * @return  string  Escaped string
     */
    public function escape($sString) {
        return $this->db->escape($sString);
    }

    /**
     * Returns the second database instance, usable to run additional statements
     * without losing current query results.
     *
     * @return  cDb
     */
    protected function _getSecondDBInstance() {
        if (!isset($this->secondDb) || !($this->secondDb instanceof cDb)) {
            $this->secondDb = cRegistry::getDb();
        }
        return $this->secondDb;
    }

    /**
     * Returns properties instance, instantiates it if not done before.
     * NOTE: This funtion changes always the client variable of property collection instance.
     *
     * @param   int  $idclient  Id of client to use in property collection. If not passed
     *                          it uses global variable
     * @return  cApiPropertyCollection
     */
    protected function _getPropertiesCollectionInstance($idclient = 0) {
        global $client;

        if ((int) $idclient <= 0) {
            $idclient = $client;
        }

        // Runtime on-demand allocation of the properties object
        if (!isset($this->properties) || !($this->properties instanceof cApiPropertyCollection)) {
            $this->properties = new cApiPropertyCollection();
        }

        if ((int) $idclient > 0) {
            $this->properties->changeClient($idclient);
        }

        return $this->properties;
    }

}

?>