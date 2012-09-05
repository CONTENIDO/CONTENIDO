<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Configure UriBuilder URL style. Per default, configures for style index-a-1.html.
 * If you need another style, extend this class to your needs and pass it to desired UriBuilder.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 * @package    CONTENIDO Backend Classes
 * @version    1.1.0
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2008-02-28
 *   modified 2008-09-29, Murat Purc, added features to set and get configuration
 *   $Id: cUriBuilderConfig.class.php 2755 2012-07-25 20:10:28Z xmurrix $:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

if (!class_exists('NotInitializedException')) {
    class NotInitializedException extends Exception {}
}


/**
 * Class to manage UriBuilder configuration.
 *
 * The cUriBuilderrConfig::setConfig() must be called at least once to initialize the desired
 * UriBuilder.
 *
 * Usage:
 * ------
 * <code>
 * // Example for default front_content UriBuilder
 * $myCfg['name']   = 'front_content';
 * $myCfg['config'] = array();
 * cUriBuilderConfig::setConfig($myCfg);
 *
 * // Example for CustomPath UriBuilder
 * $myCfg['name']   = 'custom_path';
 * $myCfg['config'] = array('prefix' => 'rocknroll', 'suffix' => '.4fb', 'separator' => ',');
 * cUriBuilderConfig::setConfig($myCfg);
 * </code>
 *
 */
class cUriBuilderConfig
{

    /**
     * UriBuilder configuration array
     * @var array
     */
    private static $_aUriBuilderCfg = array(
        'config' => array('prefix' => 'index', 'suffix' => '.html', 'separator' => '-')
    );

    /**
     * Set UlrBuilder configuration
     *
     * @param  array  $cfg  Assoziative configuration array as follows:
     *                      - $cfg['name']   = Name of UriBuilder class to use
     *                      - $cfg['config'] = UriBuilder configuration
     * @throws  InvalidArgumentException  If $cfg ist empty, $cfg['name'] is missing or $cfg['config']
     *                                    exists but is not a array
     */
    public static function setConfig(array $cfg)
    {
        if (count($cfg) == 0) {
            throw new InvalidArgumentException('cUriBuilderConfig: Empty configuration');
        } elseif (!isset($cfg['name']) || (string) $cfg['name'] === '') {
            throw new InvalidArgumentException('cUriBuilderConfig: Missing UriBuilder name');
        } elseif (isset($cfg['config']) && !is_array($cfg['config'])) {
            throw new InvalidArgumentException('cUriBuilderConfig: Invalid UriBuilder configuration');
        }

        self::$_aUriBuilderCfg = $cfg;
    }

    /**
     * Returns UriBuilder name
     *
     * @return  string  UriBuilder name
     * @throws  NotInitializedException If UriBuilder configuration wasn't initialized before
     */
    public static function getUriBuilderName()
    {
        if (!is_array(self::$_aUriBuilderCfg) || !isset(self::$_aUriBuilderCfg['name'])) {
            throw new NotInitializedException('cUriBuilderConfig: Configuration is not set');
        }

        return self::$_aUriBuilderCfg['name'];
    }

    /**
     * Returns UriBuilder configuration
     *
     * @return  array  UriBuilder configuration
     * @throws  NotInitializedException If UriBuilder configuration wasn't initialized before
     */
    public static function getConfig()
    {
        if (!is_array(self::$_aUriBuilderCfg)) {
            throw new NotInitializedException('cUriBuilderrConfig: Configuration is not set');
        }

        return self::$_aUriBuilderCfg['config'];
    }

}
