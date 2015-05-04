<?php
/**
 * This file contains the language collection and item class.
 *
 * @package          Core
 * @subpackage       GenericDB_Model
 * @version          SVN Revision $Rev:$
 *
 * @author           Bjoern Behrens
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Language collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiLanguageCollection extends ItemCollection {

    /**
     * Constructor
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['lang'], 'idlang');
        $this->_setItemClass('cApiLanguage');
    }

    /**
     * Creates a language entry.
     *
     * @global object $auth
     * @param string $name
     * @param int $active
     * @param string $encoding
     * @param string $direction
     * @return cApiLanguage
     */
    public function create($name, $active, $encoding, $direction) {
        global $auth;

        $item = $this->createNewItem();

        $item->set('name', $name, false);
        $item->set('active', $active, false);
        $item->set('encoding', $encoding, false);
        $item->set('direction', $direction, false);
        $item->set('author', $auth->auth['uid'], false);
        $item->set('created', date('Y-m-d H:i:s'), false);
        $item->set('lastmodified', '0000-00-00 00:00:00', false);
        $item->store();

        return $item;
    }

    /**
     * Returns next accessible language for current client and current logged in
     * user.
     *
     * @global object $perm
     * @global array $cfg
     * @global int $client
     * @global int $lang
     *
     * @return cApiLanguage|NULL
     */
    public function nextAccessible() {
        global $perm, $client, $lang;

        $item = $this->next();

        $lang = (int) $lang;
        $client = (int) $client;

        if ($item === false) {
            return false;
        }

        $clientsLanguageColl = new cApiClientLanguageCollection();
        $clientsLanguageColl->select('idlang = ' . $item->get("idlang"));
        if (($clientsLang = $clientsLanguageColl->next()) !== false) {
            if ($client != $clientsLang->get('idclient')) {
                $item = $this->nextAccessible();
            }
        }

        if ($item) {
            if ($perm->have_perm_client('lang[' . $item->get('idlang') . ']') || $perm->have_perm_client('admin[' . $client . ']') || $perm->have_perm_client()) {
                // Do nothing for now
            } else {
                $item = $this->nextAccessible();
            }

            return $item;
        } else {
            return false;
        }
    }

    /**
     * Returns the language name of the language with the given ID.
     *
     * @param int $idlang
     *         the ID of the language
     * @return string
     *         the name of the language
     */
    public function getLanguageName($idlang) {
        $item = new cApiLanguage($idlang);
        if ($item->isLoaded()) {
            return $item->get('name');
        } else {
            return i18n('No language');
        }
    }

}

/**
 * Language item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiLanguage extends Item {
    /**
     *
     * @var array
     */
    protected static $_propertiesCache = array();

    /**
     *
     * @var array
     */
    protected static $_propertiesCacheLoaded = array();

    /**
     * Constructor Function
     *
     * @param mixed $mId
     *         Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['lang'], 'idlang');
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Stores made changes.
     *
     * @return bool
     */
    public function store() {
        $this->set('lastmodified', date('Y-m-d H:i:s'), false);
        return parent::store();
    }

    /**
     * Userdefined setter for lang fields.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $bSafe
     *         Flag to run defined inFilter on passed value
     * @return bool
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'active':
                $value = (int) $value;
                break;
        }

        return parent::setField($name, $value, $bSafe);
    }

    /**
     * Loads all languagesettings into an static array.
     *
     * @param int $idclient
     *         Id of client to load properties from
     */
    protected function _loadProperties($idclient = 0) {

        if (!isset(self::$_propertiesCacheLoaded[$idclient])) {

            self::$_propertiesCache[$idclient] = array();

            $itemtype = $this->db->escape($this->getPrimaryKeyName());
            $itemid = $this->db->escape($this->get($this->getPrimaryKeyName()));

            $propColl = $this->_getPropertiesCollectionInstance($idclient);
            $propColl->select("itemtype='$itemtype' AND itemid='$itemid'", '', 'type, value ASC');

            if (0 < $propColl->count()) {

                while (false !== $item = $propColl->next()) {

                    $type = $item->get('type');
                    if (!isset(self::$_propertiesCache[$idclient][$type])) {
                        self::$_propertiesCache[$idclient][$type] = array();
                    }

                    $name = $item->get('name');
                    $value = $item->get('value');
                    self::$_propertiesCache[$idclient][$type][$name] = $value;
                }
            }
        }

        self::$_propertiesCacheLoaded[$idclient] = true;
    }

    /**
     * Returns a custom property.
     *
     * @param string $type
     *         Specifies the type
     * @param string $name
     *         Specifies the name
     * @param int $idclient
     *         Id of client to set property for
     * @return mixed
     *         Value of the given property or false if item hasn't been loaded
     */
    public function getProperty($type, $name, $idclient = 0) {

        // skip & return false if item hasn't been loaded
        if ($this->virgin == true) {
            $this->lasterror = 'No item loaded';
            return false;
        }

        $this->_loadProperties($idclient);

        if (isset(
            self::$_propertiesCache[$idclient],
            self::$_propertiesCache[$idclient][$type],
            self::$_propertiesCache[$idclient][$type][$name]
        )) {
            return self::$_propertiesCache[$idclient][$type][$name];
        } else {
            return false;
        }
    }

}