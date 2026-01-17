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
class PimPluginViewDependencies
{

    /**
     * @var string Path to the folder containing all plugins.
     */
    private static $pluginsFoldername = '';

    /**
     * @var SimpleXMLElement
     */
    private static $tempXml;

    /**
     * Construct function
     */
    public function __construct()
    {
        $this->setPluginsFoldername(PimPluginHelper::getPluginsFolderPath());
    }

    /**
     * Setter method for pluginFoldername
     */
    private function setPluginsFoldername(string $pluginsFoldername)
    {
        self::$pluginsFoldername = $pluginsFoldername;
    }

    /**
     * Getter method for pluginFoldername
     */
    private function getPluginsFoldername(): string
    {
        return self::$pluginsFoldername;
    }

    /**
     * Get dependencies
     * @throws cException
     */
    private function getPluginDependencies(): string
    {
        $tempXml = self::$tempXml;

        // Initializing dependencies string
        $dependencies = '';

        $dependenciesCount = count($tempXml->dependencies);
        for ($i = 0; $i < $dependenciesCount; $i++) {
            $dependencies .= sprintf(
                i18n('This plugin has a dependency to plugin &quot;%s&quot;<br />', 'pim'),
                $tempXml->dependencies->depend[$i]
            );
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
     * @return string Plugin dependency
     * @throws cException
     */
    public function getPluginDependenciesExtracted(SimpleXMLElement $tempXml): string
    {
        // Write plugin.xml content into tempXml variable
        self::$tempXml = $tempXml;

        // Call plugin dependencies
        return $this->getPluginDependencies();
    }

    /**
     * Get dependencies from installed plugins
     *
     * @param int $pluginId Id of defined plugin
     * @return string|false Plugin dependencies
     * @throws cException|cInvalidArgumentException
     */
    public function getPluginDependenciesInstalled(int $pluginId = 0)
    {
        // Return false if no idplugin variable is defined
        if ($pluginId == 0) {
            return false;
        }

        // Get folder name from defined plugin
        $pimPluginColl = new PimPluginCollection();
        $pimPluginColl->setWhere('idplugin', $pluginId);
        $pimPluginColl->query();
        $pimPluginSql = $pimPluginColl->next();
        $folderName = $pimPluginSql->get('folder');

        // Reset the query so we can use PimPluginCollection later again...
        $pimPluginColl->resetQuery();

        // Skip the plugin if it has no plugin.xml file
        if (!cFileHandler::exists(PimPluginHelper::getPluginConfigFile($folderName))) {
            return false;
        }

        // Read plugin.xml files from existing plugins at contenido/plugins dir
        $tempXmlContent = cFileHandler::read(PimPluginHelper::getPluginConfigFile($folderName));

        // Write plugin.xml content into tempXml variable
        self::$tempXml = simplexml_load_string($tempXmlContent);

        // Call plugin dependencies
        return $this->getPluginDependencies();
    }
}
