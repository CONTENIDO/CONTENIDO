<?php

declare(strict_types=1);

/**
 * AMR configuration service class.
 *
 * @package    Plugin
 * @subpackage ModRewrite
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 * @since      Advanced Mod Rewrite 2.1.0
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * AMR configuration service class.
 *
 * @package    Plugin
 * @subpackage ModRewrite
 */
class PiModRewriteConfigurationService
{
    /**
     * @var PiModRewriteConfigurationService Self-instance
     */
    private static $instance;

    /**
     * Constructor, sets some properties.
     */
    private function __construct()
    {
    }

    /**
     * Returns an instance of PiModRewriteConfigurationService (singleton implementation)
     */
    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Loads Advanced Mod Rewrite configuration for the passed client using the serialized
     * file containing the settings.
     *
     * File is placed in /contenido/mod_rewrite/includes/and is named like
     * config.mod_rewrite_{client_id}.php.
     *
     * @param int $clientId Id of client
     * @param bool $forceReload Flag to force to reload configuration, e.g., after done changes on it
     * @throws cInvalidArgumentException
     */
    public function loadConfiguration(int $clientId, bool $forceReload = false)
    {
        // NOTE: Use global here!
        global $cfg;

        $loadedConfigs = cRegistry::getAppVar('pluginModRewriteUtilGetLoadedConfigs', []);
        if (!isset($loadedConfigs)) {
            $loadedConfigs = [];
        } elseif (isset($loadedConfigs[$clientId]) && !$forceReload) {
            return;
        }

        $mrConfig = $this->getConfiguration($clientId);

        if (is_array($mrConfig)) {
            // merge mod rewrite configuration with the global cfg array
            $cfg = array_merge($cfg, $mrConfig);
        } else {
            // couldn't load configuration, set defaults
            $backendPath = cRegistry::getBackendPath();
            include_once($backendPath . $cfg['path']['plugins'] . 'mod_rewrite/includes/config.mod_rewrite_default.php');
        }

        $loadedConfigs[$clientId] = true;
        cRegistry::setAppVar('pluginModRewriteUtilGetLoadedConfigs', $loadedConfigs);
    }

    /**
     * Returns the path to the mod rewrite configuration file of a client.
     * File is placed within the client frontend path in the directory "data/config/{ENVIRONMENT}/"
     * and has the name "config.mod_rewrite.php"
     *
     * @param int $clientId Id of client
     * @return string File name and path
     */
    public function getConfigurationFilePath(int $clientId): string
    {
        $clientConfig = cRegistry::getClientConfig($clientId);

        return sprintf(
            '%s/data/config/%s/config.mod_rewrite.php',
            rtrim($clientConfig['path']['frontend'], '/'),
            CON_ENVIRONMENT
        );
    }

    /**
     * Returns the old configuration file path of a client, which was stored within the plugin folder.
     * The new configuration file is stored within the client frontend path.
     * This function is used to remove the old configuration file after saving the new one.
     */
    private function getOldConfigurationFilePath(int $clientId): string
    {
        $cfg = cRegistry::getConfig();

        return sprintf(
            '%s/mod_rewrite/includes/config.mod_rewrite_%d.php',
            rtrim(cRegistry::getBackendPath() . $cfg['path']['plugins'], '/'),
            $clientId
        );
    }

    /**
     * Returns the mod rewrite configuration array of a client.
     * File is placed in /contenido/mod_rewrite/includes/and is named like
     * config.mod_rewrite_{client_id}.php.
     *
     * @param int $clientId Id of client
     * @return ?array
     * @throws cInvalidArgumentException
     */
    public function getConfiguration(int $clientId): ?array
    {
        $file = $this->getConfigurationFilePath($clientId);
        if (!cFileHandler::isFile($file) || !is_readable($file)) {
            $file = $this->getOldConfigurationFilePath($clientId);
        }

        if (!cFileHandler::isFile($file) || !is_readable($file)) {
            return NULL;
        }
        if ($content = cFileHandler::read($file)) {
            return unserialize($content);
        } else {
            return NULL;
        }
    }

    /**
     * Saves the mod rewrite configuration array of a client.
     * File is placed in /contenido/mod_rewrite/includes/and is named like
     * config.mod_rewrite_{client_id}.php.
     *
     * @param int $clientId Id of client
     * @param array $config Configuration to save
     * @throws cInvalidArgumentException
     */
    public function setConfiguration(int $clientId, array $config): bool
    {
        $file = $this->getConfigurationFilePath($clientId);
        $result = cFileHandler::write($file, serialize($config));

        // Remove old configuration within plugin folder.
        $oldFile = $this->getOldConfigurationFilePath($clientId);
        if (cFileHandler::isFile($oldFile) && is_writeable($oldFile)) {
            cFileHandler::remove($oldFile);
        }

        return $result;
    }

}
