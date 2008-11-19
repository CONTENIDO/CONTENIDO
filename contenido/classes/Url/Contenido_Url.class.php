<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description:
 * Frontend URL creation. Works as a wrapper of an UrlBuilder instance.
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.0
 * @author     Murat Purc
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal
 *   created 2009-09-29
 *   
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}



final class Contenido_Url {

    /**
     * Self instance.
     *
     * @var  Contenido_Url
     */
    static private $_instance;

    /**
     * UrlBuilder instance.
     *
     * @var  Contenido_UrlBuilder
     */
    private $_oUrlBuilder;

    /**
     * UrlBuilder name.
     *
     * @var  string
     */
    private $_sUrlBuilderName;


    /**
     * Constructor of Contenido_Url. Is not callable from outside.
     *
     * Gets the UrlBuilder configuration and creates an UrlBuilder instance.
     */
    private function __construct() {
        cInclude('classes', 'UrlBuilder/Contenido_UrlBuilderFactory.class.php');
        $this->_sUrlBuilderName = Contenido_UrlBuilderConfig::getUrlBuilderName();
        $this->_oUrlBuilder     = Contenido_UrlBuilderFactory::getUrlBuilder(
            $this->_sUrlBuilderName
        );
    }


    /**
     * Returns self instance
     *
     * @return  Contenido_Url
     */
    public static function getInstance() {
        if (self::$_instance == null) {
            self::$_instance = new Contenido_Url();
        }
        return self::$_instance;
    }
    
    
    /**
     * Creates a URL to frontend page.
     *
     * @param  array    $aParams  Required keys depend on used UrlBuilder, but a must have is 'lang'.
     * @param  boolean  $bUseAbsolutePath  Flag to create absolute Urls
     * @param  array    $aConfig  If not set, UrlBuilderConfig::getConfig() will be used by the URLBuilder 
     */
    public function build(array $aParams, $bUseAbsolutePath=false, array $aConfig=array()) {

        if (!isset($aParams['level'])) {
            // downwards compatibility to Contenido_UrlBuilder_CustomPath
            $aParams['level'] = '1';
        }

        if (!isset($aParams['lang'])) {
            // another downwards compatibility to Contenido_UrlBuilder_CustomPath
            throw new InvalidArgumentException('$aParams[lang] must be set!');
        }
        
        if ($this->_sUrlBuilderName == 'custom_path' && count($aParams) <= 3) {
            // third downwards compatibility
            $aParams['_c_p_'] = '1';
        }

        $this->_oUrlBuilder->buildUrl($aParams, $bUseAbsolutePath, $aConfig);
        return $this->_oUrlBuilder->getUrl();
    }


    /**
     * Splits passed url into its components
     *
     * @param   string  $sUrl  The Url to strip down
     * @return  array   Assoziative array created by using parse_url() having the key 'params' which
     *                  includes the parameter value pairs.
     */
    public function parse($sUrl){
        $aUrl = @parse_url($sUrl);
        if (isset($aUrl['query'])) {
            $aUrl['query'] = str_replace('&amp;', '&', $aUrl['query']);
            parse_str($aUrl['query'], $aUrl['params']);
        }
        if (!isset($aUrl['params']) && !is_array($aUrl['params'])) {
            $aUrl['params'] = array();
        }
        return $aUrl;
    }


    /**
     * Returns UrlBuilder instance.
     *
     * @return  Contenido_UrlBuilder
     */
    public function getUrlBuilder() {
        return $this->_oUrlBuilder;
    }

}

