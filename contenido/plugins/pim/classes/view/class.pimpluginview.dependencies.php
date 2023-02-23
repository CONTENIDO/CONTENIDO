<?php

/**
 * This file contains abstract class for view plugin dependencies
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
 * View plugin dependencies
 * TODO: Later implement into new PIM view design
 *
 * @package    Plugin
 * @subpackage PluginManager
 * @author     frederic.schneider
 */
class PimPluginViewDependencies {

	// Filename of Xml configuration file for plugins
	const PLUGIN_CONFIG_FILENAME = "plugin.xml";

    /**
     * @var string
     */
	private static $pluginFoldername;

    /**
     * @var SimpleXMLElement
     */
	private static $tempXml;

	/**
	 * Construct function
	 */
	public function __construct() {
		$this->_setPluginFoldername();
	}

	/**
	 * Get method for pluginFoldername
	 *
	 * @return string $pluginFoldername
	 */
	private function _setPluginFoldername() {
		$cfg = cRegistry::getConfig();
		return self::$pluginFoldername = $cfg['path']['contenido'] . $cfg['path']['plugins'];
	}

	/**
	 * Get method for pluginFoldername
	 *
	 * @return string $pluginFoldername
	 */
	private function _getPluginFoldername() {
		return self::$pluginFoldername;
	}

    /**
     * Get dependencies
     *
     * @return bool|string
     */
	private function _getPluginDependencies() {

		$tempXml = self::$tempXml;

		// Initializing dependencies string
		$dependencies = '';

		$dependenciesCount = count($tempXml->dependencies);
		for ($i = 0; $i < $dependenciesCount; $i++) {
			$dependencies .= sprintf(i18n('This plugin has a dependency to plugin &quot;%s&quot;<br />', 'pim'), $tempXml->dependencies->depend[$i]);
		}

		if ($dependencies == '') {
			return i18n('This plugin has no dependencies to other plugins', 'pim');
		} else {
			return $dependencies;
		}

	}

    /**
     * Get dependencies from extracted plugins
     *
     * @param SimpleXMLElement $tempXml
     *
     * @return string Plugin dependency
     */
	public function getPluginDependenciesExtracted($tempXml) {
		// Write plugin.xml content into tempXml variable
    	self::$tempXml = $tempXml;

    	// Call plugin dependencies
    	return $this->_getPluginDependencies();
	}

    /**
     * Get dependencies from installed plugins
     *
     * @param int $idplugin Id of defined plugin
     *
     * @return string Plugin dependencies
     *
     * @throws cException
     * @throws cInvalidArgumentException
     */
	public function getPluginDependenciesInstalled($idplugin = 0) {

		// Return false if no idplugin variable is defined
		if ($idplugin == 0) {
			return false;
		}

		// Get foldername from defined plugin
    	$pimPluginColl = new PimPluginCollection();
    	$pimPluginColl->setWhere('idplugin', $idplugin);
    	$pimPluginColl->query();
    	$pimPluginSql = $pimPluginColl->next();
    	$folderBase = $pimPluginSql->get('folder');

    	// Reset query so we can use PimPluginCollection later again...
    	$pimPluginColl->resetQuery();

    	// Skip plugin if it has no plugin.xml file
    	if (!cFileHandler::exists($this->_getPluginFoldername() . $folderBase . DIRECTORY_SEPARATOR . self::PLUGIN_CONFIG_FILENAME)) {
    		return false;
    	}

    	// Read plugin.xml files from existing plugins at contenido/plugins dir
    	$tempXmlContent = cFileHandler::read($this->_getPluginFoldername() . $folderBase . DIRECTORY_SEPARATOR . self::PLUGIN_CONFIG_FILENAME);

    	// Write plugin.xml content into tempXml variable
    	self::$tempXml = simplexml_load_string($tempXmlContent);

    	// Call plugin dependencies
    	return $this->_getPluginDependencies();
	}
}
