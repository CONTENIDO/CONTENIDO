<?php
/**
 * This file contains the uri class.
 *
 * @package    Core
 * @subpackage Frontend_URI
 * @version    SVN Revision $Rev:$
 *
 * @author     Murat Purc
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Frontend URL creation. Works as a wrapper of an UriBuilder instance.
 *
 * @package    Core
 * @subpackage Frontend_URI
 */
class cUri {

    /**
     * Self instance.
     * @var  cUri
     */
    static private $_instance;

    /**
     * UriBuilder instance.
     * @var  cUriBuilder
     */
    private $_oUriBuilder;

    /**
     * UriBuilder name.
     * @var  string
     */
    private $_sUriBuilderName;

    /**
     * Constructor of cUri. Is not callable from outside.
     * Gets the UriBuilder configuration and creates an UriBuilder instance.
     */
    private function __construct() {
        $this->_sUriBuilderName = cUriBuilderConfig::getUriBuilderName();
        $this->_oUriBuilder = cUriBuilderFactory::getUriBuilder(
                        $this->_sUriBuilderName
        );
    }

    /**
     * Returns self instance
     * @return  cUri
     */
    public static function getInstance() {
        if (self::$_instance == NULL) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Creates a URL to frontend page.
     *
     * @param   mixed    $param   Either url or assoziative array containing parameter:
     *                            - url: front_content.php?idcat=12&lang=1
     *                            - params: array('idcat' => 12, 'lang' => 1)
     *                            Required values depend on used UriBuilder, but a must have is 'lang'.
     * @param   boolean  $bUseAbsolutePath  Flag to create absolute Urls
     * @param   array    $aConfig  If not set, cUriBuilderConfig::getConfig() will be used by the UriBuilder
     * @throws cInvalidArgumentException if the given params do not contain the lang
     * @return  string   The Url build by cUriBuilder
     */
    public function build($param, $bUseAbsolutePath = false, array $aConfig = array()) {
        if (!is_array($param)) {
            $arr = $this->parse($param);
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
        if ($aResult = cApiCecHook::executeAndReturn('Contenido.Frontend.PreprocessUrlBuilding', $aHookParams)) {
            $param = (isset($aResult['param'])) ? $aResult['param'] : '';
            if (isset($aResult['bUseAbsolutePath'])) {
                $bUseAbsolutePath = (bool) $aResult['bUseAbsolutePath'];
            }
            if (isset($aResult['aConfig']) && is_array($aResult['aConfig'])) {
                $aConfig = $aResult['aConfig'];
            }
        }

        if ($this->_sUriBuilderName == 'custom_path' && !isset($aParams['level'])) {
            // downwards compatibility to cUriBuilderCustomPath
            $aParams['level'] = '1';
        }

        if (!isset($param['lang'])) {
            // another downwards compatibility to cUriBuilderCustomPath
            throw new cInvalidArgumentException('$param[lang] must be set!');
        }

        if ($this->_sUriBuilderName == 'custom_path' && count($param) <= 3) {
            // third downwards compatibility
            $param['_c_p_'] = '1';
        }

        $this->_oUriBuilder->buildUrl($param, $bUseAbsolutePath, $aConfig);

        $url = $this->_oUriBuilder->getUrl();

        // execute postprocess hook
        if ($result = cApiCecHook::executeAndReturn('Contenido.Frontend.PostprocessUrlBuilding', $url)) {
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
     *                            Required values depend on used UriBuilder, but a must have is 'lang'.
     * @param   array    $aConfig  If not set, cUriBuilderConfig::getConfig() will be used by the UriBuilder
     * @return  string   The redirect Url build by cUriBuilder
     */
    public function buildRedirect($param, array $aConfig = array()) {
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
    public function parse($sUrl) {
        $aUrl = @parse_url($sUrl);
        if (isset($aUrl['query'])) {
            $aUrl['query'] = str_replace('&amp;', '&', $aUrl['query']);
            parse_str($aUrl['query'], $aUrl['params']);
        }
        if (!isset($aUrl['params']) || !is_array($aUrl['params'])) {
            $aUrl['params'] = array();
        }
        return $aUrl;
    }

    /**
     * Composes a url using passed components array
     *
     * @param   array   %aComponents Assoziative array created by parse_url()
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
        if (!$path = $this->_oUriBuilder->getHttpBasePath()) {
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
     * - "/front_content.php", "/front_content.php?idart=123", "/front_content.php?idcat=123", ...
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
    public function isIdentifiableFrontContentUrl($sUrl) {
        if ($this->isExternalUrl($sUrl)) {
            // detect a external url, return false
            return false;
        }

        $aComponents = $this->parse($sUrl);
        if (!isset($aComponents['path']) || $aComponents['path'] == '') {
            return false;
        }

        $clientPath = '';
        if ($httpBasePath = $this->_oUriBuilder->getHttpBasePath()) {
            $aComponents2 = $this->parse($httpBasePath);
            if (isset($aComponents2['path'])) {
                $clientPath = $aComponents2['path'];
            }
        }

        // Use pathinfo to get the path part (dirname) of the url
        $pathinfo = pathinfo($aComponents['path']);
        $baseName = $pathinfo['basename'];
        $path = $pathinfo['dirname'];
        $path = str_replace('\\', '/', $path);
        if ($path == '.') {
            $path = '';
        }

        // Remove leading/ending slashes
        $path = trim($path, '/');
        $clientPath = trim($clientPath, '/');

        if (($path == '' && ($baseName == 'front_content.php' || $baseName == ''))) {
            return true;
        } elseif (($path == $clientPath && ($baseName == 'front_content.php' || $baseName == ''))) {
            return true;
        } elseif ($path == '' && $baseName !== 'front_content.php' && $baseName == $clientPath) {
            // If url is e. g. "/cms/"
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns UriBuilder instance.
     *
     * @return  cUriBuilder
     */
    public function getUriBuilder() {
        return $this->_oUriBuilder;
    }

}
