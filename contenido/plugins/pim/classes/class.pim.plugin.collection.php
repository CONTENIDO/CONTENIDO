<?php
/**
 * This file contains Plugin Manager class.
 *
 * @package Plugin
 * @subpackage PluginManager
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Plugin Manager recipient class.
 *
 * @package     Plugin
 * @subpackage  PluginManager
 * @author Frederic Schneider
 * @method PimPlugin createNewItem
 * @method PimPlugin next
 */
class PimPluginCollection extends ItemCollection {
    /**
     * Constructor Function
     *
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['plugins'], 'idplugin');
        $this->_setItemClass('PimPlugin');
    }

    /**
     * Create a new plugin
     *
     * @param unknown_type $name
     * @param unknown_type $description
     * @param unknown_type $author
     * @param unknown_type $copyright
     * @param unknown_type $mail
     * @param unknown_type $website
     * @param unknown_type $version
     * @param unknown_type $foldername
     * @param unknown_type $uuId
     * @param unknown_type $active
     * @param int          $execOrder
     *
     * @return Item
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function create($name, $description, $author, $copyright, $mail, $website, $version, $foldername, $uuId, $active, $execOrder = 0) {
        global $client;

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
        $item->set('installed', date("Y-m-d H:i:s"), false);
        $item->set('active', $active);

        // set execution order to the last of the list or to what was specified in create
        if ($execOrder == 0) {
            $this->select();
            $execOrder = $this->count();
        }
        $item->set("executionorder", $execOrder);

        $item->store();

        return $item;
    }

    /**
     * Get the next id in table *_plugins
     *
     * @return int
     *
     * @throws cDbException
     */
    protected function _getNextId() {
        $cfg = cRegistry::getConfig();

        $sql = 'SELECT MAX(idplugin) AS id FROM ' . $cfg['tab']['plugins'];
        $this->db->query($sql);

        if ($this->db->nextRecord()) {

            $result = $this->db->f('id');

            // id must be over 10.000
            if ($result < 10000) {
                $result = 10000;
            }

            // add ten
            $result = $result + 10;

            // removed the last number
            $result = cString::getPartOfString($result, 0, cString::getStringLength($result) - 1);

            // last number is always zero
            return cSecurity::toInteger($result . 0);
        }
    }
}

/**
 * Single Plugin Manager Item
 */
class PimPlugin extends Item {

    /**
     * @var string Error storage
     */
    protected $_error;

    /**
     * Constructor Function
     *
     * @param  mixed $id Specifies the id of item to load
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($id = false) {
        $cfg = cRegistry::getConfig();
        parent::__construct($cfg['tab']['plugins'], 'idplugin');
        $this->_error = '';
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

    /**
     * Userdefined setter for pim fields.
     *
     * @param string $name
     * @param mixed  $value
     * @param bool   $bSafe Flag to run defined inFilter on passed value
     *
     * @return bool
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'idclient':
                $value = (int) $value;
                break;
			case 'active':
                $value = (int) $value;
                break;
        }

        return parent::setField($name, $value, $bSafe);
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
    public function checkDependedFromOtherPlugins($newOrder) {
    	$cfg = cRegistry::getConfig();
    	$pluginsDir = $cfg['path']['contenido'] . $cfg['path']['plugins'];

    	// Get uuid from selected plugin
    	$pimPluginColl = new PimPluginCollection();
    	$pimPluginColl->setWhere('idplugin', $this->get("idplugin"));
    	$pimPluginColl->query();
    	$pimPluginSql = $pimPluginColl->next();
    	$uuidBase = $pimPluginSql->get('uuid');

    	// Reset query so we can use PimPluginCollection later again...
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
    			if ($depend == "") {
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
    public function checkDependenciesToOtherPlugins($newOrder) {
    	$cfg = cRegistry::getConfig();
    	$pluginsDir = $cfg['path']['contenido'] . $cfg['path']['plugins'];

    	// Get uuid from selected plugin
    	$pimPluginColl = new PimPluginCollection();
    	$pimPluginColl->setWhere('idplugin', $this->get("idplugin"));
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
    	$dependenciesBase = array();

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
     *
     * @return bool
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function updateExecOrder($newOrder) {

    	$dependendFromOtherPlugins = $this->checkDependedFromOtherPlugins($newOrder);
		$dependenciesToOtherPlugins = $this->checkDependenciesToOtherPlugins($newOrder);

    	if ($dependendFromOtherPlugins === false || $dependenciesToOtherPlugins === false) {
    		return false;
    	}

    	$oldOrder = $this->get('executionorder'); // get the old value
        $idplugin = $this->get("idplugin");

        $this->set('executionorder', $newOrder); // update this plugin to the new value
        $this->store();

        // move the other plugins up or down
        $pluginColl = new PimPluginCollection();
        $pluginColl->select('executionorder >= "' . min($newOrder, $oldOrder) . '" AND executionorder <= "' . max($newOrder, $oldOrder) . '" AND idplugin != "' . $idplugin . '"', NULL, 'executionorder'); // select every plugin that needs to be updated

        while ($plugin = $pluginColl->next()) {
            if ($newOrder < $oldOrder) {
                $plugin->set("executionorder", $plugin->get("executionorder") + 1); // increment the execution order after we moved the plugin up
                $plugin->store();
            } elseif ($oldOrder < $newOrder) {
                $plugin->set("executionorder", $plugin->get("executionorder") - 1); // decrement the execution value after we moved the plugin down
                $plugin->store();
            }
        }

        return true;
    }

    /**
     * Check if plugin exists and is active
     *
     * @param string $pluginname
     *
     * @return bool true iv available, false if it is not available
     *
     * @throws cDbException
     * @throws cException
     */
    public function isPluginAvailable($pluginname) {
        return $this->loadByMany(array(
            'idclient' => cRegistry::getClientId(),
            'name' => $pluginname,
            'active' => 1
        ));
    }
}
