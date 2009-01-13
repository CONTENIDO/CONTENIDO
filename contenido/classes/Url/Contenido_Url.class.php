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
 *   created  2009-09-29
 *   modified 2008-12-23, Murat Purc, added functions buildRedirect(), composeByComponents() and
 *                                    isExternalUrl() and exended flexibility of build()
 *   modified 2008-12-26, Murat Purc, added execution of chains 'Contenido.Frontend.PreprocessUrlBuilding'
 *                                    and 'Contenido.Frontend.PostprocessUrlBuilding' to build()
 *   modified 2009-01-13, Murat Purc, added new function isIdentifiableFrontContentUrl() for better 
 *                                    identification of internal urls
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
     * @param   mixed    $param   Either url or assoziative array containing parameter:
     *                            - url: front_content.php?idcat=12&lang=1
     *                            - params: array('idcat' => 12, 'lang' => 1)
     *                            Required values depend on used UrlBuilder, but a must have is 'lang'.
     * @param   boolean  $bUseAbsolutePath  Flag to create absolute Urls
     * @param   array    $aConfig  If not set, UrlBuilderConfig::getConfig() will be used by the URLBuilder
     * @return  string   The Url build by UrlBuilder
     */
    public function build($param, $bUseAbsolutePath=false, array $aConfig=array()) {

        if (!is_array($param)) {
            $arr   = $this->parse($param);
            $param = $arr['params'];
        }

        // fallback for urls to homepage (/ or front_content.php)
        if (count($param) == 0 || (!isset($param['idart']) && !isset($param['idartlang']) &&
            !isset($param['idcat']) && !isset($param['idcatlang']) && !isset($param['idcatart']))) {
            $param['idcat'] = getEffectiveSetting('navigation', 'idcat-home', 1);
        }

        // execute preprocess hook
        $aHookParams = array(
            'param' => $param, 'bUseAbsolutePath' => $bUseAbsolutePath, 'aConfig' => $aConfig
        );
        if ($aResult = CEC_Hook::execute('Contenido.Frontend.PreprocessUrlBuilding', $aHookParams)) {
            $param = (isset($aResult['param'])) ? $aResult['param'] : '';
            if (isset($aResult['bUseAbsolutePath'])) {
                $bUseAbsolutePath = (bool) $aResult['bUseAbsolutePath'];
            }
            if (isset($aResult['aConfig']) && is_array($aResult['aConfig'])) {
                $aConfig = $aResult['aConfig'];
            }
        }

        if ($this->_sUrlBuilderName == 'custom_path' && !isset($aParams['level'])) {
            // downwards compatibility to Contenido_UrlBuilder_CustomPath
            $aParams['level'] = '1';
        }

        if (!isset($param['lang'])) {
            // another downwards compatibility to Contenido_UrlBuilder_CustomPath
            throw new InvalidArgumentException('$param[lang] must be set!');
        }

        if ($this->_sUrlBuilderName == 'custom_path' && count($param) <= 3) {
            // third downwards compatibility
            $param['_c_p_'] = '1';
        }

        $this->_oUrlBuilder->buildUrl($param, $bUseAbsolutePath, $aConfig);

        $url = $this->_oUrlBuilder->getUrl();

        // execute postprocess hook
        if ($result = CEC_Hook::execute('Contenido.Frontend.PostprocessUrlBuilding', $url)) {
            $url = (string) $result;
        }

        return $url;
    }


    /**
     * Creates a URL used to redirect to frontend page.
     *
     * @param   mixed    $param   Either url or assoziative array containing parameter:
     *                            - url: front_content.php?idcat=12&lang=1
     *                            - params: array('idcat' => 12, 'lang' => 1)
     *                            Required values depend on used UrlBuilder, but a must have is 'lang'.
     * @param   array    $aConfig  If not set, UrlBuilderConfig::getConfig() will be used by the URLBuilder
     * @return  string   The redirect Url build by UrlBuilder
     */
    public function buildRedirect($param, array $aConfig=array()) {
        $url = $this->build($param, true, $aConfig);
        return str_replace('&amp;', '&', $url);
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
     * Composes a url using passed components array
     *
     * @param   array   Assoziative array created by parse_url()
     * @return  string  $sUrl  The composed Url
     */
    public function composeByComponents(array $aComponents) {
        $sUrl = (isset($aComponents['scheme']) ? $aComponents['scheme'] . '://' : '') .
                (isset($aComponents['user']) ? $aComponents['user'] . ':' : '') .
                (isset($aComponents['pass']) ? $aComponents['pass'] . '@' : '') .
                (isset($aComponents['host']) ? $aComponents['host'] : '') .
                (isset($aComponents['port']) ? ':' . $aComponents['port'] : '') .
                (isset($aComponents['path']) ? $aComponents['path'] : '') .
                (isset($aComponents['query']) ? '?' . $aComponents['query'] : '') .
                (isset($aComponents['fragment']) ? '#' . $aComponents['fragment'] : '');
        return $sUrl;
    }


    /**
     * Checks, if passed url is an external url while performing hostname check
     *
     * @param   string  $sUrl  Url to check
     * @return  bool  True if url is a external url, otherwhise false
     */
    public function isExternalUrl($sUrl) {
        $aComponents = $this->parse($sUrl);
        if (!isset($aComponents['host'])) {
            return false;
        }
        if (!$path = $this->_oUrlBuilder->getHttpBasePath()) {
            return false;
        }

        $aComponents2 = $this->parse($path);
        if (!isset($aComponents2['host'])) {
            return false;
        }

        return (strtolower($aComponents['host']) !== strtolower($aComponents2['host']));
    }


    /**
     * Checks, if passed url is an identifiable internal url.
     *
     * Following urls will be identified as a internal url:
     * - "/", "/?idart=123", "/?idcat=123", ...
     * - "front_content.php", "front_content.php?idart=123", "front_content.php?idcat=123", ...
     * - The path component of an client HTML base path: e. g. "/cms/", "/cms/?idart=123", "/cms/?idcat=123"
     * - Also possible: "/cms/front_content.php", "/cms/front_content.php?idart=123", "/cms/front_content.php?idcat=123"
     * All of them prefixed with protocol and client host (e. g. http://host/) will also be identified 
     * as a internal Url.
     *
     * Other Urls, even internal Urls like /unknown/path/to/some/page.html will not be identified as 
     * internal url event if they are real working clean URLs.
     *
     * @param   string  $sUrl  Url to check
     * @return  bool  True if url is identifiable internal url, otherwhise false
     */
    public function isIdentifiableFrontContentUrl($sUrl){
        if ($this->isExternalUrl($sUrl)) {
            // detect a external url, return false
            return false;
        }

        $aComponents = $this->parse($sUrl);
        if (!isset($aComponents['path']) || $aComponents['path'] == '') {
            return false;
        }

        $clientPath = '';
        if ($httpBasePath = $this->_oUrlBuilder->getHttpBasePath()) {
            $aComponents2 = $this->parse($httpBasePath);
            if (isset($aComponents2['path'])) {
                $clientPath = $aComponents2['path'];
            }
        }

        $path = $aComponents['path'];
        if ($path == '/' || strpos($path, 'front_content.php') === 0 || 
            strpos($path, '/front_content.php') > 0 || ($clientPath !== '' && $clientPath == $path)) {
            return true;
        } else {
            return false;
        }
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

