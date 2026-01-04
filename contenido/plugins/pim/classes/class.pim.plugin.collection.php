<?php

/**
 * This file contains Plugin Manager class.
 *
 * @package    Plugin
 * @subpackage PluginManager
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Plugin Manager recipient class.
 *
 * @package    Plugin
 * @subpackage PluginManager
 * @author     Frederic Schneider
 * @extends ItemCollection<PimPlugin>
 */
class PimPluginCollection extends ItemCollection
{

    use cItemCollectionIdsByClientIdTrait;

    /**
     * @var string Client id foreign key field name
     * @since CONTENIDO 4.10.2
     */
    private $fkClientIdName = 'idclient';

    /**
     * Constructor Function
     *
     * @throws cInvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(cDb::getTableName('plugins'), 'idplugin');
        $this->_setItemClass('PimPlugin');
    }

    /**
     * Create a new plugin
     *
     * @param string $name
     * @param string $description
     * @param string $author
     * @param string $copyright
     * @param string $mail
     * @param string $website
     * @param string $version
     * @param string $foldername
     * @param string $uuId
     * @param string $active
     * @param int $execOrder
     * @return PimPlugin
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create($name, $description, $author, $copyright, $mail, $website, $version, $foldername, $uuId, $active, $execOrder = 0)
    {
        $client = cRegistry::getClientId();

        $nextId = $this->_getNextId();

        // create a new entry
        $item = $this->createNewItem($nextId);
        $item->set('idclient', $client);
        $item->set('name', $name);
        $item->set('description', $description);
        $item->set('author', $author);
        $item->set('copyright', $copyright);
        $item->set('mail', $mail);
        $item->set('website', $website);
        $item->set('version', $version);
        $item->set('folder', $foldername);
        $item->set('uuid', $uuId);
        $item->set('installed', date('Y-m-d H:i:s'), false);
        $item->set('active', $active);

        // set execution order to the last of the list or to what was specified in create
        if ($execOrder == 0) {
            $this->select();
            $execOrder = $this->count();
        }
        $item->set('executionorder', $execOrder);

        $item->store();

        return $item;
    }

    /**
     * Get the next id in table *_plugins
     *
     * @throws cDbException
     */
    protected function _getNextId(): int
    {
        $this->db->query(
            'SELECT MAX(`idplugin`) AS `id` FROM `%s`',
            cDb::getTableName('plugins')
        );
        $maxId = $this->db->nextRecord() ? cSecurity::toInteger($this->db->f('id')) : 0;

        // id must be over 10.000
        if ($maxId < 10000) {
            $maxId = 10000;
        }

        // Add ten
        $maxId = $maxId + 10;

        // Replace the last char (number) against '0', last number is always 0!
        $maxId = substr(cSecurity::toString($maxId), 0, -1) . '0';

        return cSecurity::toInteger($maxId);
    }
}

/**
 * Single Plugin Manager Item
 */
class PimPlugin extends Item
{

    /**
     * @var string Error storage
     */
    protected $_error;

    /**
     * Constructor Function
     *
     * @param mixed $id Specifies the id of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('plugins'), 'idplugin');
        $this->_error = '';
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * User-defined setter for pim fields.
     *
     * @inheritDoc
     */
    public function setField($name, $value, $safe = true)
    {
        switch ($name) {
            case 'active':
            case 'idclient':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }

    /**
     * Check dependencies
     * Adapted from PimPLuginSetup class
     *
     * @param int $newOrder New execution order value
     *
     * @return bool
     *
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function checkDependedFromOtherPlugins($newOrder)
    {
        $cfg = cRegistry::getConfig();
        $pluginsDir = cRegistry::getBackendPath() . $cfg['path']['plugins'];

        // Get uuid from selected plugin
        $pimPluginColl = new PimPluginCollection();
        $pimPluginColl->setWhere('idplugin', $this->get('idplugin'));
        $pimPluginColl->query();
        $pimPluginSql = $pimPluginColl->next();
        $uuidBase = $pimPluginSql->get('uuid');

        // Reset query, so we can use PimPluginCollection later again...
        $pimPluginColl->resetQuery();

        // Read all dirs
        $dirs = cDirHandler::read($pluginsDir);
        foreach ($dirs as $dirname) {

            // Skip plugin if it has no plugin.xml file
            if (!cFileHandler::exists($pluginsDir . $dirname . DIRECTORY_SEPARATOR . "plugin.xml")) {
                continue;
            }

            // Read plugin.xml files from existing plugins at contenido/plugins dir
            $tempXmlContent = cFileHandler::read($pluginsDir . $dirname . DIRECTORY_SEPARATOR . "plugin.xml");

            // Write plugin.xml content into temporary variable
            $tempXml = simplexml_load_string($tempXmlContent);

            $dependenciesCount = count($tempXml->dependencies);
            for ($i = 0; $i < $dependenciesCount; $i++) {

                // Security check
                $depend = cSecurity::escapeString($tempXml->dependencies->depend[$i]);

                // If is no dependencie name defined please go to next dependencie
                if ($depend == '') {
                    continue;
                }

                // Build uuid variable from attributes
                foreach ($tempXml->dependencies->depend[$i]->attributes() as $key => $value) {

                    // We use only uuid attribute and can ignore other attributes
                    if ($key == "uuid") {

                        $uuidTemp = cSecurity::escapeString($value);

                        if ($uuidBase === $uuidTemp) {

                            // Prüfe, ob das Kindplugin aktiv ist
                            $pimPluginColl->setWhere('uuid', $tempXml->general->uuid);
                            $pimPluginColl->setWhere('active', '1');
                            $pimPluginColl->query();

                            if ($pimPluginColl->count() == 0) {
                                continue;
                            }

                            $result = $pimPluginColl->next();

                            if ($newOrder == $result->get('executionorder')) {
                                return false;
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Check dependencies
     * Adapted from PimPLuginSetup class
     *
     * @param int $newOrder New executionorder value
     *
     * @return bool
     *
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function checkDependenciesToOtherPlugins($newOrder)
    {
        $cfg = cRegistry::getConfig();
        $pluginsDir = cRegistry::getBackendPath() . $cfg['path']['plugins'];

        // Get uuid from selected plugin
        $pimPluginColl = new PimPluginCollection();
        $pimPluginColl->setWhere('idplugin', $this->get('idplugin'));
        $pimPluginColl->query();
        $pimPluginSql = $pimPluginColl->next();
        $folderBase = $pimPluginSql->get('folder');
        $uuidBase = $pimPluginSql->get('uuid');

        // Reset query so we can use PimPluginCollection later again...
        $pimPluginColl->resetQuery();

        // Skip plugin if it has no plugin.xml file
        if (!cFileHandler::exists($pluginsDir . $folderBase . DIRECTORY_SEPARATOR . "plugin.xml")) {
            return true;
        }

        // Read plugin.xml files from existing plugins at contenido/plugins dir
        $tempXmlContent = cFileHandler::read($pluginsDir . $folderBase . DIRECTORY_SEPARATOR . "plugin.xml");

        // Write plugin.xml content into temporary variable
        $tempXml = simplexml_load_string($tempXmlContent);

        // Initializing dependencies array
        $dependenciesBase = [];

        $dependenciesCount = count($tempXml->dependencies);
        for ($i = 0; $i < $dependenciesCount; $i++) {

            foreach ($tempXml->dependencies->depend[$i]->attributes() as $key => $value) {
                $dependenciesBase[] = cSecurity::escapeString($value);
            }

        }

        // Read all dirs
        $dirs = cDirHandler::read($pluginsDir);
        foreach ($dirs as $dirname) {

            // Skip plugin if it has no plugin.xml file
            if (!cFileHandler::exists($pluginsDir . $dirname . DIRECTORY_SEPARATOR . "plugin.xml")) {
                continue;
            }

            // Read plugin.xml files from existing plugins at contenido/plugins dir
            $tempXmlContent = cFileHandler::read($pluginsDir . $dirname . DIRECTORY_SEPARATOR . "plugin.xml");

            // Write plugin.xml content into temporary variable
            $tempXml = simplexml_load_string($tempXmlContent);

            if (in_array($tempXml->general->uuid, $dependenciesBase) === true) {

                $pimPluginColl->setWhere('uuid', $tempXml->general->uuid);
                $pimPluginColl->query();
                $result = $pimPluginColl->next();

                if ($newOrder == $result->get('executionorder')) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Change the execution order of this plugin and update the order for every other plugin
     *
     * @param int $newOrder New execution order for this plugin
     * @return bool
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function updateExecOrder($newOrder)
    {
        $dependendFromOtherPlugins = $this->checkDependedFromOtherPlugins($newOrder);
        $dependenciesToOtherPlugins = $this->checkDependenciesToOtherPlugins($newOrder);

        if ($dependendFromOtherPlugins === false || $dependenciesToOtherPlugins === false) {
            return false;
        }

        $oldOrder = $this->get('executionorder'); // get the old value
        $idplugin = $this->get('idplugin');

        $this->set('executionorder', $newOrder); // update this plugin to the new value
        $this->store();

        // move the other plugins up or down
        $pluginColl = new PimPluginCollection();
        $pluginColl->select('executionorder >= "' . min($newOrder, $oldOrder) . '" AND executionorder <= "' . max($newOrder, $oldOrder) . '" AND idplugin != "' . $idplugin . '"', NULL, 'executionorder'); // select every plugin that needs to be updated

        while ($plugin = $pluginColl->next()) {
            if ($newOrder < $oldOrder) {
                $plugin->set('executionorder', $plugin->get('executionorder') + 1); // increment the execution order after we moved the plugin up
                $plugin->store();
            } elseif ($oldOrder < $newOrder) {
                $plugin->set('executionorder', $plugin->get('executionorder') - 1); // decrement the execution value after we moved the plugin down
                $plugin->store();
            }
        }

        return true;
    }

    /**
     * Check if plugin exists and is active
     *
     * @param string $pluginname
     * @return bool true iv available, false if it is not available
     * @throws cDbException|cException
     */
    public function isPluginAvailable($pluginname)
    {
        return $this->loadByMany([
            'idclient' => cRegistry::getClientId(),
            'name' => $pluginname,
            'active' => 1
        ]);
    }
}
