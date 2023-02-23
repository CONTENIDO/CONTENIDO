<?php

/**
 * This file contains the uri builder mod rewrite class.
 *
 * @package Plugin
 * @subpackage ModRewrite
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class to build frontend urls for advandced mod rewrite plugin.
 *
 * Extends abstract Contenido_UriBuilder class and implements the
 * singleton pattern.
 *
 * Usage:
 * <pre>
 * cInclude('classes', 'uri/class.uriBuilder.MR.php');
 * $url = 'front_content.php?idart=123';
 * $mrUriBuilder = cUriBuilderMR::getInstance();
 * $mrUriBuilder->buildUrl(array($url));
 * $newUrl = $mrUriBuilder->getUrl();
 * </pre>
 *
 * @todo add handling of absolute paths
 * @todo standardize handling of fragments
 * @package Plugin
 * @subpackage ModRewrite
 */
class cUriBuilderMR extends cUriBuilder {

    /**
     * Self instance
     *
     * @var cUriBuilderMR
     */
    private static $_instance;

    /**
     * Cached rootdir.
     *
     * The rootdir can differ from the configured one if an alternate
     * frontendpath is configured as client setting. In order to determine the
     * current rootdir only once this is cached as static class member.
     *
     * @var string
     */
    private static $_cachedRootDir;

    /**
     * Ampersand used for composing several parameter value pairs
     *
     * @var string
     */
    private $_sAmp = '&amp;';

    /**
     * Is XHTML output?
     *
     * @var bool
     */
    private $_bIsXHTML = false;

    /**
     * Is mod rewrite enabled?
     *
     * @var bool
     */
    private $_bMREnabled = false;

    /**
     * Mod Rewrite configuration
     *
     * @var array
     */
    private $_aMrCfg = NULL;

    /**
     * Constructor to create an instance of this class.
     *
     * Tries to set some member variables.
     */
    private function __construct() {
        $this->sHttpBasePath = '';
        if (ModRewrite::isEnabled()) {
            $this->_aMrCfg = ModRewrite::getConfig();
            $this->_bMREnabled = true;
            $this->_bIsXHTML = (getEffectiveSetting('generator', 'xhtml', 'false') == 'false') ? false : true;
            $this->_sAmp = ($this->_bIsXHTML) ? '&amp;' : '&';
        }
    }

    /**
     * Returns a instance of cUriBuilderMR.
     *
     * @return cUriBuilderMR
     */
    public static function getInstance() {
        if (self::$_instance == NULL) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Builds a URL based on defined mod rewrite settings.
     *
     * @param array $params
     *                                Parameter array, provides only following parameters:
     *                                <code>
     *                                $params[0] = 'front_content.php?idart=123...'
     *                                </code>
     * @param bool  $bUseAbsolutePath [optional]
     *                                Flag to use absolute path (not used at the moment)
     *
     * @return string
     *         New build url
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function buildUrl(array $params, $bUseAbsolutePath = false) {
        ModRewriteDebugger::add($params, 'cUriBuilderMR::buildUrl() $params');

        $urlDebug       = [];
        $urlDebug['in'] = $params;

        $url = self::_buildUrl($params);

        $urlPrefix = '';
        if ($bUseAbsolutePath) {
            $hmlPath = cRegistry::getFrontendUrl();
            $aComp = parse_url($hmlPath);
            $urlPrefix = $aComp['scheme'] . '://' . $aComp['host'];
            if (mr_arrayValue($aComp, 'port', '') !== '') {
                $urlPrefix .= ':' . $aComp['port'];
            }
        }

        $this->sUrl = $urlPrefix . $url;

        $urlDebug['out'] = $this->sUrl;
        ModRewriteDebugger::add($urlDebug, 'cUriBuilderMR::buildUrl() in -> out');
    }

    /**
     * Builds the SEO-URL by analyzing passed arguments
     * (parameter value pairs).
     *
     * @param array $aParams
     *         Parameter array
     *
     * @return string
     *         New build pretty url
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    private function _buildUrl(array $aParams) {
        // language should changed, set lang parameter
        if (isset($aParams['changelang'])) {
            $aParams['lang'] = $aParams['changelang'];
        }

        // build the query
        $sQuery = http_build_query($aParams);

        // get pretty url parts
        $oMRUrlStack = ModRewriteUrlStack::getInstance();
        $aPretty = $oMRUrlStack->getPrettyUrlParts('front_content.php?' . $sQuery);

        // get all non CONTENIDO related query parameter
        $sQuery = $this->_createUrlQueryPart($aParams);

        // some presettings of variables
        $aParts = [];

        // add client id/name if desired
        $param = $this->_getClientParameter($aParams);
        if ($param) {
            $aParts[] = $param;
        }

        // add language id/name if desired
        $param = $this->_getLanguageParameter($aParams);
        if ($param) {
            $aParts[] = $param;
        }

        // get path part of the url
        $sPath = $this->_getPath($aPretty);
        if ($sPath !== '') {
            $aParts[] = $sPath;
        }
        $sPath = implode('/', $aParts) . '/';

        // get pagename part of the url
        $sArticle = $this->_getArticleName($aPretty, $aParams);

        if ($sArticle !== '') {
            $sFileExt = $this->_aMrCfg['file_extension'];
        } else {
            $sFileExt = '';
        }

        $sPathAndArticle = $sPath . $sArticle . $sFileExt;

        // use lowercase url
        if ($this->_aMrCfg['use_lowercase_uri'] == 1) {
            $sPathAndArticle = cString::toLowerCase($sPathAndArticle);
        }

        // $sUrl = $this->_aMrCfg['rootdir'] . $sPathAndArticle . $sQuery;
        $sUrl = $sPathAndArticle . $sQuery;

        // remove double or more join parameter
        $sUrl = mr_removeMultipleChars('/', $sUrl);
        if (cString::getPartOfString($sUrl, -2) == '?=') {
            $sUrl = substr_replace($sUrl, '', -2);
        }

        // now convert CONTENIDO url to an AMR url
        $sUrl = ModRewriteUrlUtil::getInstance()->toModRewriteUrl($sUrl);

        // prepend rootdir as defined in config
        // $sUrl = $this->_aMrCfg['rootdir'] . $sUrl;
        // this version allows for multiple domains of a client
        $sUrl = self::getMultiClientRootDir($this->_aMrCfg['rootdir']) . $sUrl;

        // remove double slashes
        $sUrl = mr_removeMultipleChars('/', $sUrl);

        return $sUrl;
    }

    /**
     * Returns the defined rootdir.
     *
     * Allows for root dir being alternativly defined as path of setting
     * client/%frontend_path%.
     *
     * @param string $configuredRootDir
     *         defined rootdir
     *
     * @return string
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public static function getMultiClientRootDir($configuredRootDir) {

        // return cached rootdir if set
        if (isset(self::$_cachedRootDir)) {
            return self::$_cachedRootDir;
        }

        // get props of current client
        $props = cRegistry::getClient()->getProperties();
        // $props = cRegistry::getClient()->getPropertiesByType('client');

        // return rootdir as defined in AMR if client has no props
        if (!is_array($props)) {
            self::$_cachedRootDir = $configuredRootDir;
            return $configuredRootDir;
        }

        foreach ($props as $prop) {
            // skip props that are not of type 'client'
            if ($prop['type'] != 'client') {
                continue;
            }

            // skip props whose name does not contain 'frontend_path'
            if (false === strstr($prop['name'], 'frontend_path')) {
                continue;
            }

            // current host & path (HTTP_HOST & REQUEST_URI)
            $httpHost = $_SERVER['HTTP_HOST'];
            $httpPath = $_SERVER['REQUEST_URI'];

            // host & path of configured alternative URL
            $propHost = parse_url($prop['value'], PHP_URL_HOST);
            $propPath = parse_url($prop['value'], PHP_URL_PATH);

            // skip if http host does not equal configured host (allowing for
            // optional www)
            if ($propHost != $httpHost && ('www.' . $propHost) != $httpHost && $propHost != 'www.' . $httpHost) {
                continue;
            }

            // skip if http path does not start with configured path
            if (0 !== cString::findFirstPos($httpPath, $propPath)) {
                continue;
            }

            // return path as specified in client settings
            self::$_cachedRootDir = $propPath;
            return $propPath;
        }

        // return rootdir as defined in AMR
        self::$_cachedRootDir = $configuredRootDir;
        return $configuredRootDir;
    }

    /**
     * Loops through given parameter array and creates the query part of
     * the URL.
     *
     * All non CONTENIDO related parameters will be excluded from
     * composition.
     *
     * @param array $aArgs
     *         associative parameter array
     * @return string
     *         composed query part for the URL
     *         like '?foo=bar&amp;param=value'
     */
    private function _createUrlQueryPart(array $aArgs) {
        // set list of parameter which are to ignore while setting additional parameter
        $aIgnoredParams = [
            'idcat',
            'idart',
            'lang',
            'client',
            'idcatart',
            'idartlang',
        ];
        if ($this->_aMrCfg['use_language'] == 1) {
            $aIgnoredParams[] = 'changelang';
        }
        if ($this->_aMrCfg['use_client'] == 1) {
            $aIgnoredParams[] = 'changeclient';
        }

        // collect additional non CONTENIDO related parameters
        $sQuery = '';
        foreach ($aArgs as $p => $v) {
            if (!in_array($p, $aIgnoredParams)) {
                // $sQuery .= urlencode(urldecode($p)) . '=' .
                // urlencode(urldecode($v)) . $this->_sAmp;
                $p = urlencode(urldecode($p));
                if (is_array($v)) {
                    // handle query parameter like foobar[0}=a&foobar[1]=b...
                    foreach ($v as $p2 => $v2) {
                        $p2 = urlencode(urldecode($p2));
                        $v2 = urlencode(urldecode($v2));
                        $sQuery .= $p . '[' . $p2 . ']=' . $v2 . $this->_sAmp;
                    }
                } else {
                    $v = urlencode(urldecode($v));
                    $sQuery .= $p . '=' . $v . $this->_sAmp;
                }
            }
        }
        if (cString::getStringLength($sQuery) > 0) {
            $sQuery = '?' . cString::getPartOfString($sQuery, 0, -cString::getStringLength($this->_sAmp));
        }
        return $sQuery;
    }

    /**
     * Returns client id or name depending on settings.
     *
     * @param array $aArgs
     *         Additional arguments
     * @return mixed
     *         Client id, client name or NULL
     */
    private function _getClientParameter(array $aArgs) {
        global $client;

        // set client if desired
        if ($this->_aMrCfg['use_client'] == 1) {
            $iChangeClient = (isset($aArgs['changeclient'])) ? (int) $aArgs['changeclient'] : 0;
            $idclient = ($iChangeClient > 0) ? $iChangeClient : $client;
            if ($this->_aMrCfg['use_client_name'] == 1) {
                return urlencode(ModRewrite::getClientName($idclient));
            } else {
                return $idclient;
            }
        }
        return NULL;
    }

    /**
     * Returns language id or name depending on settings.
     *
     * @param array $aArgs
     *         Additional arguments
     * @return mixed
     *         Language id, language name or NULL
     */
    private function _getLanguageParameter(array $aArgs) {
        global $lang;

        // set language if desired
        if ($this->_aMrCfg['use_language'] == 1) {
            $iChangeLang = (isset($aArgs['changelang'])) ? (int) $aArgs['changelang'] : 0;
            $idlang = ($iChangeLang > 0) ? $iChangeLang : $lang;
            if ($this->_aMrCfg['use_language_name'] == 1) {
                return urlencode(ModRewrite::getLanguageName($idlang));
            } else {
                return $idlang;
            }
        }
        return NULL;
    }

    /**
     * Returns composed path of url (normally the category structure).
     *
     * @param array $aPretty
     *         Pretty url array
     * @return string
     *         Path
     */
    private function _getPath(array $aPretty) {
        $sPath = (isset($aPretty['urlpath'])) ? $aPretty['urlpath'] : '';

        // check start directory settings
        if ($this->_aMrCfg['startfromroot'] == 0 && (cString::getStringLength($sPath) > 0)) {
            // splitt string in array
            $aCategories = explode('/', $sPath);

            // remove first category
            array_shift($aCategories);

            // implode array with categories to new string
            $sPath = implode('/', $aCategories);
        }

        return $sPath;
    }

    /**
     * Returns articlename depending on current setting.
     *
     * @param array $aPretty
     *         Pretty url array
     * @param array $aArgs
     *         Additional arguments
     * @return string
     *         Articlename
     */
    private function _getArticleName(array $aPretty, array $aArgs) {
        $sArticle = (isset($aPretty['urlname'])) ? $aPretty['urlname'] : '';

        $iIdCat = (isset($aArgs['idcat'])) ? (int) $aArgs['idcat'] : 0;
        $iIdCatLang = (isset($aArgs['idcatlang'])) ? (int) $aArgs['idcatlang'] : 0;
        $iIdCatArt = (isset($aArgs['idcatart'])) ? (int) $aArgs['idcatart'] : 0;
        $iIdArt = (isset($aArgs['idart'])) ? (int) $aArgs['idart'] : 0;
        $iIdArtLang = (isset($aArgs['idartlang'])) ? (int) $aArgs['idartlang'] : 0;

        // category id was passed but not article id
        if (($iIdCat > 0 || $iIdCatLang > 0) && $iIdCatArt == 0 && $iIdArt == 0 && $iIdArtLang == 0) {
            $sArticle = '';
            if ($this->_aMrCfg['add_startart_name_to_url']) {
                if ($this->_aMrCfg['default_startart_name'] !== '') {
                    // use default start article name
                    $sArticle = $this->_aMrCfg['default_startart_name'];
                } else {
                    $sArticle = (isset($aPretty['urlname'])) ? $aPretty['urlname'] : '';
                }
            } else {
                // url is to create without article name
                $sArticle = '';
            }
        }

        return $sArticle;
    }
}
