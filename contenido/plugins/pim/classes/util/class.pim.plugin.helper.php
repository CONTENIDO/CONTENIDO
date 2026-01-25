<?php

declare(strict_types=1);

/**
 * This file contains the Plugin Helper class.
 *
 * @package    Plugin
 * @subpackage PluginManager
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 * @since      Plugin Manager 2.1.1
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Plugin Helper class.
 *
 * @package    Plugin
 * @subpackage PluginManager
 */
class PimPluginHelper
{
    /**
     * File name of the XML configuration file for plugins.
     */
    public const PLUGIN_CONFIG_FILENAME = 'plugin.xml';

    /**
     * File name of the SQL file for the installation of a plugin.
     */
    public const PLUGIN_INSTALL_FILENAME = 'plugin_install.sql';

    /**
     * File name of the SQL file for the uninstallation of a plugin.
     */
    public const PLUGIN_UNINSTALL_FILENAME = 'plugin_uninstall.sql';

    /**
     * File name of the SQL file for the update of a plugin.
     * Contains placeholders for the old and new version numbers.
     */
    public const PLUGIN_UPDATE_FILENAME = 'plugin_update_%s_to_%s.sql';

    /**
     * File name of the XML language file for plugin.
     * Contains placeholders for the locale code.
     */
    public const PLUGIN_LANGUAGE_FILENAME = 'lang_%s.xml';

    /**
     * Retrieves the file system path to the plugins folder with the ending slash.
     */
    public static function getPluginsFolderPath(): string
    {
        $cfg = cRegistry::getConfig();

        return cRegistry::getBackendPath() . $cfg['path']['plugins'];
    }

    /**
     * Retrieves the file system path to the plugin folder of a specific plugin the ending slash.
     */
    public static function getPluginFolderPath(string $pluginFolderName): string
    {
        return self::getPluginsFolderPath() . $pluginFolderName . '/';
    }

    /**
     * Retrieves the file system path to the plugin language folder of a specific plugin the ending slash.
     */
    public static function getPluginLanguageFolderPath(string $pluginFolderName): string
    {
        return self::getPluginFolderPath($pluginFolderName) . 'xml/';
    }

    /**
     * Retrieves the file system path to the configuration file of a specific plugin.
     */
    public static function getPluginConfigFile(string $pluginFolderName): string
    {
        return self::getPluginFolderPath($pluginFolderName) . self::PLUGIN_CONFIG_FILENAME;
    }

    /**
     * Retrieves the file system path to the installation SQL file of a specific plugin.
     */
    public static function getPluginInstallFile(string $pluginFolderName): string
    {
        return self::getPluginFolderPath($pluginFolderName) . self::PLUGIN_INSTALL_FILENAME;
    }

    /**
     * Retrieves the file system path to the uninstallation SQL file of a specific plugin.
     */
    public static function getPluginUninstallFile(string $pluginFolderName): string
    {
        return self::getPluginFolderPath($pluginFolderName) . self::PLUGIN_UNINSTALL_FILENAME;
    }

    /**
     * Generates the file name for a plugin update based on the old and new version numbers.
     */
    public static function getPluginUpdateFileName(string $oldVersion, string $newVersion): string
    {
        return sprintf(self::PLUGIN_UPDATE_FILENAME, $oldVersion, $newVersion);
    }

    /**
     * Retrieves the full file path for a plugin update file based on the plugin folder name,
     * old version, and new version numbers.
     */
    public static function getPluginUpdateFile(string $pluginFolderName, string $oldVersion, string $newVersion): string
    {
        $fileName = self::getPluginUpdateFileName($oldVersion, $newVersion);

        return self::getPluginFolderPath($pluginFolderName) . $fileName;
    }

    /**
     * Retrieves the file path for a plugin's language file based on the plugin folder name and locale code.
     */
    public static function getPluginLanguageFile(string $pluginFolderName, string $localeCode): string
    {
        $fileName = sprintf(self::PLUGIN_LANGUAGE_FILENAME, $localeCode);

        return self::getPluginLanguageFolderPath($pluginFolderName) . $fileName;
    }

}
