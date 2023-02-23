<?php

/**
 * This file contains the view utility class `cAsset`.
 *
 * @package    Core
 * @subpackage Util
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Asset helper class.
 * Adds a version parameter to passed asset files (css & js).
 * Helps to serve modified files, so the cached versions on
 * the clients will be updated.
 *
 * @package    Core
 * @subpackage Util
 */
class cAsset
{

    /**
     * Internal cache for already processed files.
     *
     * @var string[]
     */
    protected static $_files = [];

    /**
     * Returns versioned asset file path for the CONTENIDO backend.
     * The file must exist and the path must start relative from
     * CONTENIDO backend, which is usually '{installation_root}/contenido'.
     *
     * Use following CEC Hook to have a custom modified asset file path:
     * - Contenido.Backend.Asset
     *
     * @param string $file The relative path to the file from
     *     CONTENIDO backend.
     *     Examples:
     *     - scripts/contenido.js
     *     - styles/includes/con_editcontent.css
     *
     * @return string The modified path with the version parameter or
     *     the original value.
     *     Examples:
     *     - scripts/contenido.js?v=123456789
     *     - styles/includes/con_editcontent.css?v=123456789
     */
    public static function backend(string $file): string
    {
        if (!self::_isValidAsset($file)) {
            return $file;
        }

        if (isset(self::$_files[$file])) {
            return self::$_files[$file];
        }

        $filePathName = self::_getRealPath($file, cRegistry::getBackendPath());
        if (!$filePathName) {
            self::$_files[$file] = $file;
            return $file;
        }

        // Execute chain
        $changedFile = cApiCecHook::executeAndReturn('Contenido.Backend.Asset', $file);
        if ($changedFile === $file) {
            $file = self::_processFile($file, $filePathName);
        }

        self::$_files[$file] = $file;
        return self::$_files[$file];
    }

    /**
     * Returns versioned asset file path for the frontend.
     * The file must exist and the path must start relative from
     * clients frontend path, which is usually '{installation_root}/cms'.
     *
     * Use following CEC Hook to have a custom modified asset file path:
     * - Contenido.Frontend.Asset
     *
     * @param string $file The relative path to the file from
     *     CONTENIDO backend.
     *     Examples:
     *     - js/main.js
     *     - css/main.css
     * @param int $client  Client id
     * @return string The modified path with the version parameter or
     *     the original value.
     *     Examples:
     *     - js/main.js?v=123456789
     *     - css/main.css?v=123456789
     */
    public static function frontend(string $file, int $client = null): string
    {
        if (!self::_isValidAsset($file)) {
            return $file;
        }
        if (is_null($client)) {
            $client = cRegistry::getClientId();
        }

        $clientCfg = cRegistry::getClientConfig($client);
        if (!is_array($clientCfg)) {
            return $file;
        }

        $filePathName = self::_getRealPath($file, $clientCfg['path']['frontend']);
        if (!$filePathName) {
            self::$_files[$file] = $file;
            return $file;
        }

        // Execute chain
        $changedFile = cApiCecHook::executeAndReturn('Contenido.Frontend.Asset', $file, $client);
        if ($changedFile === $file) {
            $file = self::_processFile($file, $filePathName);
        }

        self::$_files[$file] = $file;

        return self::_processFile($file, $filePathName);
    }

    /**
     * Checks the syntax of the asset file path, the criteria are:
     * - Value can't be empty, and can't be a fully qualified url
     * - Value can't be schemeless (protocol-relative) url
     * - The asset file extension can be '.css' or '.js'
     *
     * @param string $file
     * @return bool
     */
    protected static function _isValidAsset(string $file): bool
    {
        if (empty($file) || substr( $file, 0, 2) === '//') {
            return false;
        }
        if (filter_var($file, FILTER_VALIDATE_URL) === true) {
            return false;
        }
        if (!preg_match( '/\.(css|js)$/i', $file)) {
            return false;
        }

        return true;
    }

    /**
     * Returns the real path to the file (absolute pathname).
     *
     * @param string $file  The relative asset file path
     * @param string $basePath The base path, e.g. path to CONTENIDO
     *     backend folder or to a client frontend folder.
     * @return string|null  The real path or null in case the path
     *     couldn't be resolved.
     */
    protected static function _getRealPath(string $file, string $basePath)
    {
        // Some checks & modifications on the file path
        if (substr($file, 0, 2) === '..') {
            $fileToUse = '/' . $file;
        } else if (in_array(substr($file, 0, 1), ['.', '/']) === false) {
            $fileToUse = '/' . $file;
        } else {
            $fileToUse = $file;
        }

        // Return the absolute pathname to the file
        $filePathName = realpath($basePath . $fileToUse);
        if ($filePathName === false) {
            return null;
        }

        return $filePathName;
    }

    /**
     * Reads the file modification time and adds it to the file
     * as a query parameter.
     *
     * @param string $file  The relative asset file path
     * @param string $filePathName The real path to the file.
     * @return string The modified asset file path, e.g. js/main.js?v=123456789
     */
    protected static function _processFile(string $file, string $filePathName): string
    {
        $parts = cUri::getInstance()->parse($file);
        if (!empty($parts['params']['v'])) {
            // File url has already a version parameter.
            return $file;
        }

        // Get file modification time and add as a version parameter (e.g. v=123456789)
        $parts['params']['v'] = filemtime($filePathName);
        $parts['query'] = http_build_query($parts['params']);
        return cUri::getInstance()->composeByComponents($parts);
    }

}