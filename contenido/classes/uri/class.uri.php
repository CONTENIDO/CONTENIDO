<?php

/**
 * This file contains the uri class.
 *
 * @package    Core
 * @subpackage Frontend_URI
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
     *
     * @var cUri
     */
    static private $_instance;

    /**
     * UriBuilder instance.
     *
     * @var cUriBuilder
     */
    private $_oUriBuilder;

    /**
     * UriBuilder name.
     *
     * @var string
     */
    private $_sUriBuilderName;

    /**
     * Constructor to create an instance of this class.
     *
     * Is not callable from outside.
     *
     * Gets the UriBuilder configuration and creates an UriBuilder
     * instance.
     *
     * @throws cException
     */
    private function __construct() {
        $this->_sUriBuilderName = cUriBuilderConfig::getUriBuilderName();
        $this->_oUriBuilder = cUriBuilderFactory::getUriBuilder($this->_sUriBuilderName);
    }

    /**
     * Returns self instance.
     *
     * @return cUri
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
     * @param mixed $param
     *         Either url or associative array containing parameter:
     *         - url: front_content.php?idcat=12&lang=1
     *         - params: ['idcat' => 12, 'lang' => 1]
     *         Required values depend on used UriBuilder, but a must have is 'lang'.
     * @param bool $bUseAbsolutePath [optional]
     *         Flag to create absolute Urls
     * @param array $aConfig [optional]
     *         If not set, cUriBuilderConfig::getConfig() will be used by the UriBuilder
     * @return string
     *         The Url build by cUriBuilder
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function build($param, $bUseAbsolutePath = false, array $aConfig = []) {
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
        $aHookParams = [
            'param' => $param, 'bUseAbsolutePath' => $bUseAbsolutePath, 'aConfig' => $aConfig
        ];
        $aResult = cApiCecHook::executeAndReturn('Contenido.Frontend.PreprocessUrlBuilding', $aHookParams);
        if ($aResult) {
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

        // CON-2712
        if ($bUseAbsolutePath === true) {
            $this->_oUriBuilder->setHttpBasePath(cRegistry::getFrontendUrl());
        }

        $this->_oUriBuilder->buildUrl($param, $bUseAbsolutePath, $aConfig);

        $url = $this->_oUriBuilder->getUrl();

        // execute postprocess hook
        $result = cApiCecHook::executeAndReturn('Contenido.Frontend.PostprocessUrlBuilding', $url);
        if ($result) {
            $url = (string) $result;
        }

        return $url;
    }

    /**
     * Creates a URL used to redirect to frontend page.
     *
     * @param mixed $param
     *                       Either url or associative array containing parameter:
     *                       - url: front_content.php?idcat=12&lang=1
     *                       - params: ['idcat' => 12, 'lang' => 1]
     *                       Required values depend on used UriBuilder, but a must have is 'lang'.
     * @param array $aConfig [optional]
     *                       If not set, cUriBuilderConfig::getConfig() will be used by the UriBuilder.
     *
     * @return string
     *         The redirect Url build by cUriBuilder.
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function buildRedirect($param, array $aConfig = []) {
        $url = $this->build($param, true, $aConfig);
        return str_replace('&amp;', '&', $url);
    }

    /**
     * Splits passed url into its components.
     *
     * @param string $sUrl
     *         The Url to strip down.
     * @return array
     *         Associative array created by using parse_url()
     *         having the key 'params' which includes the parameter value pairs.
     */
    public function parse($sUrl) {
        $aUrl = @parse_url($sUrl);

        if (isset($aUrl['query'])) {
            $aUrl['query'] = str_replace('&amp;', '&', $aUrl['query']);
            parse_str($aUrl['query'], $aUrl['params']);
        }

        if (!isset($aUrl['params'])) {
            $aUrl['params'] = [];
        }

        return $aUrl;
    }

    /**
     * Composes a url using passed components array.
     *
     * @param array $aComponents
     *         Associative array created by parse_url()
     * @return string
     *         The composed Url
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
     * Checks, if passed url is an external url while performing
     * hostname check.
     *
     * @param string $sUrl
     *         Url to check.
     * @return bool
     *         True if url is a external url, otherwise false.
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

        return cString::toLowerCase($aComponents['host']) !== cString::toLowerCase($aComponents2['host']);
    }

    /**
     * Checks, if passed url is an identifiable internal url.
     *
     * Following urls will be identified as a internal url:
     *
     * - "/", "/?idart=123", "/?idcat=123", ...
     * - "front_content.php", "front_content.php?idart=123", "front_content.php?idcat=123", ...
     * - "/front_content.php", "/front_content.php?idart=123", "/front_content.php?idcat=123", ...
     * - The path component of an client HTML base path: e.g. "/cms/", "/cms/?idart=123", "/cms/?idcat=123"
     * - Also possible: "/cms/front_content.php", "/cms/front_content.php?idart=123", "/cms/front_content.php?idcat=123"
     *
     * All of them prefixed with protocol and client host (e.g. http://host/) will also be identified
     * as a internal Url.
     *
     * Other Urls, even internal Urls like /unknown/path/to/some/page.html
     * will not be identified as internal url event if they are real
     * working clean URLs.
     *
     * @param string $sUrl
     *         Url to check.
     * @return bool
     *         True if url is identifiable internal url, otherwise false.
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
        $pathInfo = pathinfo($aComponents['path']);
        $baseName = $pathInfo['basename'];
        $path = $pathInfo['dirname'];
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
            // If url is e.g. "/cms/"
            return true;
        } else {
            return false;
        }
    }

    /**
     * Appends additional query parameters to a URI.
     *
     * @since CONTENIDO 4.10.2
     * @param string $uri - The URI to append parameters to
     * @param array $parameters - Parameter to append
     * @param array|null $reservedParameters - List of reserved parameters to skip from overwriting.
     *     If no list is given, then following reserved parameters will be defined as not overridable:
     *     'client', 'idart', 'idcat', 'idartlang', 'lang', 'error'
     * @param bool $overwrite - Flag to overwrite other already existing parameters in given url.
     *     Note, enabled flag won't invalidate the $reservedParameters value!
     * @return string - The modified URI
     */
    public function appendParameters($uri, array $parameters, $reservedParameters = null, $overwrite = false) {
        if (!is_array($reservedParameters)) {
            $reservedParameters = [
                'client', 'idart', 'idcat', 'idartlang', 'lang', 'error'
            ];
        }

        $urlParts = $this->parse($uri);
        $urlParameters = $urlParts['params'];

        // Filter parameter
        $filteredParameters = [];
        foreach ($parameters as $key => $value) {
            if (!$overwrite && isset($urlParameters[$key])) {
                // Do not add already existing parameters to url
                continue;
            }
            if (in_array($key, $reservedParameters)) {
                // CON-2231: Do not add reserved get parameters to redirect url
                continue;
            }
            $filteredParameters[$key] = $value;
        }

       // Clean parameter values recursive
        array_walk_recursive($filteredParameters, function (&$value) {
            if (!is_array($value)) {
                $value = htmlentities(cRequestValidator::cleanParameter($value));
            }
        });

        // Clean parameter keys
        $filteredParameters2 = [];
        foreach ($filteredParameters as $key => $value) {
            $filteredParameters2[htmlentities(cRequestValidator::cleanParameter($key))] = $value;
        }

        // Merge url parameters with filtered parameters
        $urlParts['query'] = http_build_query(array_merge($urlParameters, $filteredParameters2), '', '&');
        $uri = $this->composeByComponents($urlParts);

        return $uri;
    }

    /**
     * Returns UriBuilder instance.
     *
     * @return cUriBuilder
     */
    public function getUriBuilder() {
        return $this->_oUriBuilder;
    }

}
