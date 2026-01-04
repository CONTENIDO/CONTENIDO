<?php

/**
 * This file contains the language collection and item class.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Language collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @extends ItemCollection<cApiLanguage>
 */
class cApiLanguageCollection extends ItemCollection
{
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('lang'), 'idlang');
        $this->_setItemClass('cApiLanguage');
    }

    /**
     * Creates a language entry.
     *
     * @param string $name
     * @param int $active
     * @param string $encoding
     * @param string $direction
     * @return cApiLanguage
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($name, $active, $encoding, $direction)
    {
        $auth = cRegistry::getAuth();

        $item = $this->createNewItem();

        $item->set('name', $name, false);
        $item->set('active', $active, false);
        $item->set('encoding', $encoding, false);
        $item->set('direction', $direction, false);
        $item->set('author', $auth->getUserId(), false);
        $item->set('created', date('Y-m-d H:i:s'), false);
        $item->set('lastmodified', '0000-00-00 00:00:00', false);
        $item->store();

        return $item;
    }

    /**
     * Returns next accessible language for current client and current logged-in user.
     *
     * @return ?cApiLanguage
     * @throws cDbException|cException
     */
    public function nextAccessible()
    {
        $item = $this->next();

        if ($item === false) {
            return NULL;
        }

        $client = cRegistry::getClientId();

        $clientsLanguageColl = new cApiClientLanguageCollection();
        $clientsLanguageColl->select('idlang = ' . $item->get('idlang'));
        if (($clientsLang = $clientsLanguageColl->next()) !== false) {
            if ($client != $clientsLang->get('idclient')) {
                $item = $this->nextAccessible();
            }
        }

        if ($item) {
            $perm = cRegistry::getPerm();
            if ($perm->have_perm_client('lang[' . $item->get('idlang') . ']') || $perm->have_perm_client('admin[' . $client . ']') || $perm->have_perm_client()) {
                // Do nothing for now
            } else {
                $item = $this->nextAccessible();
            }

            return $item;
        } else {
            return NULL;
        }
    }

    /**
     * Returns the language name of the language with the given ID.
     *
     * @param int $idlang The ID of the language
     * @return string The name of the language
     * @throws cDbException|cException
     */
    public function getLanguageName($idlang): string
    {
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
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiLanguage extends Item
{
    /**
     * @var array
     */
    protected static $_propertiesCache = [];

    /**
     * @var array
     */
    protected static $_propertiesCacheLoaded = [];

    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $id Specifies the ID of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('lang'), 'idlang');
        $this->setFilters();
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Stores made changes.
     *
     * @inheritDoc
     */
    public function store()
    {
        $this->set('lastmodified', date('Y-m-d H:i:s'), false);
        return parent::store();
    }

    /**
     * User-defined setter for lang fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'active':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }

    /**
     * Loads all languagesettings into a static array.
     *
     * @param int $clientId Id of client to load properties from
     * @throws cDbException|cException
     */
    protected function _loadProperties($clientId = 0)
    {
        $clientId = cSecurity::toInteger($clientId);
        if (!isset(self::$_propertiesCacheLoaded[$clientId])) {
            self::$_propertiesCache[$clientId] = [];

            $itemtype = $this->db->escape($this->getPrimaryKeyName());
            $itemid = $this->db->escape($this->get($this->getPrimaryKeyName()));

            $propColl = $this->_getPropertiesCollectionInstance($clientId);
            $propColl->select("itemtype='$itemtype' AND itemid='$itemid'", '', 'type, value ASC');

            if (0 < $propColl->count()) {
                while ($item = $propColl->next()) {
                    $type = $item->get('type');
                    if (!isset(self::$_propertiesCache[$clientId][$type])) {
                        self::$_propertiesCache[$clientId][$type] = [];
                    }

                    $name = $item->get('name');
                    $value = $item->get('value');
                    self::$_propertiesCache[$clientId][$type][$name] = $value;
                }
            }
        }

        self::$_propertiesCacheLoaded[$clientId] = true;
    }

    /**
     * Returns a custom property.
     *
     * @param string $type Specifies the type
     * @param string $name Specifies the name
     * @param int $clientId Id of client to set property for
     * @return mixed Value of the given property or false if item hasn't been loaded
     * @throws cDbException|cException
     */
    public function getProperty($type, $name, $clientId = 0)
    {
        // skip & return false if item hasn't been loaded
        if (true !== $this->isLoaded()) {
            $this->lasterror = 'No item loaded';
            return false;
        }

        $this->_loadProperties($clientId);

        if (isset(
            self::$_propertiesCache[$clientId],
            self::$_propertiesCache[$clientId][$type],
            self::$_propertiesCache[$clientId][$type][$name]
        )) {
            return self::$_propertiesCache[$clientId][$type][$name];
        } else {
            return false;
        }
    }

}
