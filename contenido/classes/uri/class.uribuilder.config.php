<?php

/**
 * This file contains the uri builder configuration class.
 *
 * @package    Core
 * @subpackage Frontend_URI
 * @version    SVN Revision $Rev:$
 *
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if (!class_exists('NotInitializedException')) {
    /**
     */
    class NotInitializedException extends Exception {
    }
}

/**
 * Configure cUriBuilder URL style. Per default, configures for style
 * index-a-1.html.
 * If you need another style, extend this class to your needs and pass it to
 * desired cUriBuilder.
 *
 * The cUriBuilderConfig::setConfig() must be called at least once to
 * initialize the desired
 * UriBuilder.
 *
 * Usage:
 * ------
 * <code>
 * // Example for default front_content cUriBuilder
 * $myCfg['name'] = 'front_content';
 * $myCfg['config'] = array();
 * cUriBuilderConfig::setConfig($myCfg);
 *
 * // Example for CustomPath cUriBuilder
 * $myCfg['name'] = 'custom_path';
 * $myCfg['config'] = array('prefix' => 'rocknroll', 'suffix' => '.4fb',
 * 'separator' => ',');
 * cUriBuilderConfig::setConfig($myCfg);
 * </code>
 *
 * @package    Core
 * @subpackage Frontend_URI
 */
class cUriBuilderConfig {

    /**
     * UriBuilder configuration array
     *
     * @var array
     */
    private static $_aUriBuilderCfg = array(
        'config' => array(
            'prefix' => 'index',
            'suffix' => '.html',
            'separator' => '-'
        )
    );

    /**
     * Set cUriBuilder configuration
     *
     * @param array $cfg
     *         Assoziative configuration array as follows:
     *         - $cfg['name'] = Name of UriBuilder class to use
     *         - $cfg['config'] = UriBuilder configuration
     * @throws cInvalidArgumentException
     *         If $cfg ist empty, $cfg['name'] is missing
     *         or $cfg['config'] exists but is not a array
     */
    public static function setConfig(array $cfg) {
        if (count($cfg) == 0) {
            throw new cInvalidArgumentException('cUriBuilderConfig: Empty configuration');
        } elseif (!isset($cfg['name']) || (string) $cfg['name'] === '') {
            throw new cInvalidArgumentException('cUriBuilderConfig: Missing UriBuilder name');
        } elseif (isset($cfg['config']) && !is_array($cfg['config'])) {
            throw new cInvalidArgumentException('cUriBuilderConfig: Invalid UriBuilder configuration');
        }

        self::$_aUriBuilderCfg = $cfg;
    }

    /**
     * Returns cUriBuilder name
     *
     * @throws cException
     *         If cUriBuilder configuration wasn't initialized before
     * @return string
     *         cUriBuilder name
     */
    public static function getUriBuilderName() {
        if (!is_array(self::$_aUriBuilderCfg) || !isset(self::$_aUriBuilderCfg['name'])) {
            throw new cException('cUriBuilderConfig: Configuration is not set');
        }

        return self::$_aUriBuilderCfg['name'];
    }

    /**
     * Returns cUriBuilder configuration
     *
     * @throws cException
     *         If cUriBuilder configuration wasn't initialized before
     * @return array
     *         cUriBuilder configuration
     */
    public static function getConfig() {
        if (!is_array(self::$_aUriBuilderCfg)) {
            throw new cException('cUriBuilderConfig: Configuration is not set');
        }

        return self::$_aUriBuilderCfg['config'];
    }

}
