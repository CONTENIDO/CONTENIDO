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
     * @return PimPlugin
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function create(
        string $name,
        string $description,
        string $author,
        string $copyright,
        string $mail,
        string $website,
        string $version,
        string $foldername,
        string $uuId,
        int $active,
        int $execOrder = 0
    ) {
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
        $item->set('active', $active === 1 ? 1 : 0);

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
    protected $error;

    /**
     * Constructor Function
     *
     * @param mixed $id Specifies the id of item to load
     * @throws cDbException|cException
     */
    public function __construct($id = false)
    {
        parent::__construct(cDb::getTableName('plugins'), 'idplugin');
        $this->error = '';
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
            case 'executionorder':
            case 'idclient':
            case 'idplugin':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $safe);
    }

    /**
     * @inheritDoc
     */
    public function getField($name, $safe = true)
    {
        $value = parent::getField($name, $safe);

        switch ($name) {
            case 'active':
            case 'executionorder':
            case 'idclient':
            case 'idplugin':
                $value = cSecurity::toInteger($value);
                break;
        }

        return $value;
    }

    /**
     * Check dependencies
     * Adapted from PimPluginSetup class
     *
     * @param int $newOrder New execution order value
     * @throws cException|cInvalidArgumentException
     */
    public function checkDependedFromOtherPlugins(int $newOrder): bool
    {
        $pluginsDir = PimPluginHelper::getPluginsFolderPath();

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
        foreach ($dirs as $folderName) {
            // Skip plugin if it has no plugin.xml file
            if (!cFileHandler::exists(PimPluginHelper::getPluginConfigFile($folderName))) {
                continue;
            }

            // Read plugin.xml files from existing plugins at contenido/plugins dir
            $tempXmlContent = cFileHandler::read(PimPluginHelper::getPluginConfigFile($folderName));

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
     * Adapted from PimPluginSetup class
     *
     * @param int $newOrder New executionorder value
     * @throws cException|cInvalidArgumentException
     */
    public function checkDependenciesToOtherPlugins(int $newOrder): bool
    {
        $pluginsDir = PimPluginHelper::getPluginsFolderPath();

        // Get uuid from the selected plugin
        $pimPluginColl = new PimPluginCollection();
        $pimPluginColl->setWhere('idplugin', $this->get('idplugin'));
        $pimPluginColl->query();
        $pimPluginSql = $pimPluginColl->next();
        $folderName = $pimPluginSql->get('folder');
        $uuidBase = $pimPluginSql->get('uuid');

        // Reset query so we can use PimPluginCollection later again...
        $pimPluginColl->resetQuery();

        // Skip plugin if it has no plugin.xml file
        if (!cFileHandler::exists(PimPluginHelper::getPluginConfigFile($folderName))) {
            return true;
        }

        // Read plugin.xml files from existing plugins at contenido/plugins dir
        $tempXmlContent = cFileHandler::read(PimPluginHelper::getPluginConfigFile($folderName));

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
        foreach ($dirs as $folderName) {
            // Skip the plugin if it has no plugin.xml file
            if (!cFileHandler::exists(PimPluginHelper::getPluginConfigFile($folderName))) {
                continue;
            }

            // Read plugin.xml files from existing plugins at contenido/plugins dir
            $tempXmlContent = cFileHandler::read(PimPluginHelper::getPluginConfigFile($folderName));

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
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function updateExecOrder(int $newOrder): bool
    {
        $dependedFromOtherPlugins = $this->checkDependedFromOtherPlugins($newOrder);
        $dependenciesToOtherPlugins = $this->checkDependenciesToOtherPlugins($newOrder);

        if ($dependedFromOtherPlugins === false || $dependenciesToOtherPlugins === false) {
            return false;
        }

        $oldOrder = $this->get('executionorder'); // get the old value
        $idplugin = $this->get('idplugin');

        $this->set('executionorder', $newOrder); // update this plugin to the new value
        $this->store();

        // move the other plugins up or down
        $pluginColl = new PimPluginCollection();
        $pluginColl->select(
            sprintf(
                '`executionorder` >= %d AND `executionorder` <= %d AND `idplugin` != %d',
                min($newOrder, $oldOrder),
                max($newOrder, $oldOrder),
                $idplugin
            ),
            NULL,
            'executionorder'
        );

        while ($plugin = $pluginColl->next()) {
            if ($newOrder < $oldOrder) {
                // increment the execution order after we moved the plugin up
                $plugin->set('executionorder', $plugin->get('executionorder') + 1);
                $plugin->store();
            } elseif ($oldOrder < $newOrder) {
                // decrement the execution value after we moved the plugin down
                $plugin->set('executionorder', $plugin->get('executionorder') - 1);
                $plugin->store();
            }
        }

        return true;
    }

    /**
     * Check if the plugin exists and is active.
     *
     * @param string $pluginName
     * @return bool true iv available, false if it is not available
     * @throws cDbException|cException
     */
    public function isPluginAvailable(string $pluginName): bool
    {
        return $this->loadByMany([
            'idclient' => cRegistry::getClientId(),
            'name' => $pluginName,
            'active' => 1
        ]);
    }
}
