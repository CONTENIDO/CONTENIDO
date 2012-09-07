<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Configure UrlBuilder URL style. Per default, configures for style index-a-1.html.
 * If you need another style, extend this class to your needs and pass it to desired UrlBuilder.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 * @package    Contenido Backend classes
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
 *   $Id: Contenido_UrlBuilderConfig.class.php 885 2008-11-19 23:25:36Z xmurrix $:
 * }}
 *
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


if (!class_exists('NotInitializedException')) {
    class NotInitializedException extends Exception {}
}


/**
 * Class to manage UrlBuilder configuration.
 *
 * The Contenido_UrlBuilderConfig::setConfig() must be called at least once to initialize the desired
 * UrlBuilder.
 *
 * Usage:
 * ------
 * <code>
 * // Example for default front_content UrlBuilder
 * $myCfg['name']   = 'front_content';
 * $myCfg['config'] = array();
 * Contenido_UrlBuilderConfig::setConfig($myCfg);
 *
 * // Example for CustomPath UrlBuilder
 * $myCfg['name']   = 'custom_path';
 * $myCfg['config'] = array('prefix' => 'rocknroll', 'suffix' => '.4fb', 'separator' => ',');
 * Contenido_UrlBuilderConfig::setConfig($myCfg);
 * </code>
 *
 */
class Contenido_UrlBuilderConfig {

    /**
     * UrlBuilder configuration array
     * @var array
     */
    private static $_aUrlBuilderCfg = array(
        'config' => array('prefix' => 'index', 'suffix' => '.html', 'separator' => '-')
    );


    /**
     * Set UlrBuilder configuration
     * 
     * @param  array  $cfg  Assoziative configuration array as follows:
     *                      - $cfg['name']   = Name of UrlBuilder class to use
     *                      - $cfg['config'] = UrlBuilder configuration
     * @throws  InvalidArgumentException  If $cfg ist empty, $cfg['name'] is missing or $cfg['config']
     *                                    exists but is not a array
     */
    public static function setConfig(array $cfg) {
        if (count($cfg) == 0) {
            throw new InvalidArgumentException('Contenido_UrlBuilderConfig: Empty configuration');
        } elseif (!isset($cfg['name']) || (string) $cfg['name'] === '') {
            throw new InvalidArgumentException('Contenido_UrlBuilderConfig: Missing UrlBuilder name');
        } elseif (isset($cfg['config']) && !is_array($cfg['config'])) {
            throw new InvalidArgumentException('Contenido_UrlBuilderConfig: Invalid UrlBuilder configuration');
        }

        self::$_aUrlBuilderCfg = $cfg;
    }


    /**
     * Returns UrlBuilder name
     *
     * @return  string  UrlBuilder name
     * @throws  NotInitializedException If UrlBuilder configuration wasn't initialized before
     */
    public static function getUrlBuilderName() {
        if (!is_array(self::$_aUrlBuilderCfg) || !isset(self::$_aUrlBuilderCfg['name'])) {
            throw new NotInitializedException('Contenido_UrlBuilderConfig: Configuration is not set');
        }

        return self::$_aUrlBuilderCfg['name'];
    }


    /**
     * Returns UrlBuilder configuration
     *
     * @return  array  UrlBuilder configuration
     * @throws  NotInitializedException If UrlBuilder configuration wasn't initialized before
     */
    public static function getConfig() {
        if (!is_array(self::$_aUrlBuilderCfg)) {
            throw new NotInitializedException('Contenido_UrlBuilderConfig: Configuration is not set');
        }

        return self::$_aUrlBuilderCfg['config'];
    }

}
