<?php

/**
 * This file contains the uri builder mod rewrite class.
 *
 * @package    Plugin
 * @subpackage ModRewrite
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Class to build frontend urls for the advanced mod rewrite plugin.
 *
 * Extends the abstract `cUriBuilder` class and implements the
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
 * @package    Plugin
 * @subpackage ModRewrite
 */
class cUriBuilderMR extends cUriBuilder
{

    /**
     * @var cUriBuilderMR Self-instance
     */
    private static $instance;

    /**
     * Cached rootdir.
     *
     * The rootdir can differ from the configured one if an alternate
     * frontendpath is configured as the client setting. To determine the
     * current rootdir only once this is cached in a static class member.
     *
     * @var string
     */
    private static $cachedRootDir;

    /**
     * @var string Ampersand used for composing several parameter value pairs
     */
    private $ampersand = '&amp;';

    /**
     * @var bool Is XHTML output?
     */
    private $isXHTML = false;

    /**
     * @var bool Is mod rewrite enabled?
     */
    private $isMrEnabled = false;

    /**
     * @var array Mod Rewrite configuration
     */
    private $mrCfg = NULL;

    /**
     * Constructor to create an instance of this class.
     *
     * Tries to set some member variables.
     */
    private function __construct()
    {
        $this->sHttpBasePath = '';
        if (PiModRewrite::isEnabled()) {
            $this->mrCfg = PiModRewrite::getConfig();
            $this->isMrEnabled = true;
            $this->isXHTML = !(getEffectiveSetting('generator', 'xhtml', 'false') == 'false');
            $this->ampersand = ($this->isXHTML) ? '&amp;' : '&';
        }
    }

    /**
     * Returns an instance of cUriBuilderMR.
     */
    public static function getInstance(): self
    {
        if (self::$instance == NULL) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Builds a URL based on defined mod rewrite settings.
     *
     * @param array $params The parameter array, provides only the following parameters:
     *      <code>
     *      $params[0] = 'front_content.php?idart=123...'
     *      </code>
     * @param bool $bUseAbsolutePath Flag to use the absolute path (not used at the moment)
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function buildUrl(array $params, $bUseAbsolutePath = false)
    {
        PiModRewriteDebugger::add($params, __METHOD__ . ' $params');

        $urlDebug = [];
        $urlDebug['in'] = $params;

        $url = self::buildMrUrl($params);

        $urlPrefix = '';
        if ($bUseAbsolutePath) {
            $hmlPath = cRegistry::getFrontendUrl();
            $aComp = parse_url($hmlPath);
            $urlPrefix = $aComp['scheme'] . '://' . $aComp['host'];
            if (PiModRewriteUtil::arrayValue($aComp, 'port', '') !== '') {
                $urlPrefix .= ':' . $aComp['port'];
            }
        }

        $this->sUrl = $urlPrefix . $url;

        $urlDebug['out'] = $this->sUrl;
        PiModRewriteDebugger::add($urlDebug, __METHOD__ . ' in -> out');
    }

    /**
     * Builds the SEO-URL by analyzing passed arguments (parameter value pairs).
     *
     * @param array $params Parameter array
     * @return string New build pretty url
     * @throws cDbException|cException|cInvalidArgumentException
     */
    private function buildMrUrl(array $params): string
    {
        // language should change, set lang parameter
        if (isset($params['changelang'])) {
            $params['lang'] = $params['changelang'];
        }

        // build the query
        $query = http_build_query($params);

        // get pretty url parts
        $oMRUrlStack = PiModRewriteUrlStackService::getInstance();
        $prettyUrlDto = $oMRUrlStack->getPrettyUrlDto('front_content.php?' . $query);

        // get all non CONTENIDO related query parameter
        $query = $this->createUrlQueryPart($params);

        // some presetting of variables
        $aParts = [];

        // add client id/name if desired
        $param = $this->getClientParameter($params);
        if ($param) {
            $aParts[] = $param;
        }

        // add language id/name if desired
        $param = $this->getLanguageParameter($params);
        if ($param) {
            $aParts[] = $param;
        }

        // get the path part of the url
        $sPath = $this->getPath($prettyUrlDto);
        if ($sPath !== '') {
            $aParts[] = $sPath;
        }
        $sPath = implode('/', $aParts) . '/';

        // get pagename part of the url
        $sArticle = $this->getArticleName($prettyUrlDto, $params);

        if ($sArticle !== '') {
            $sFileExt = $this->mrCfg['file_extension'];
        } else {
            $sFileExt = '';
        }

        $sPathAndArticle = $sPath . $sArticle . $sFileExt;

        // use lowercase url
        if ($this->mrCfg['use_lowercase_uri'] == 1) {
            $sPathAndArticle = cString::toLowerCase($sPathAndArticle);
        }

        // $sUrl = $this->mrCfg['rootdir'] . $sPathAndArticle . $query;
        $sUrl = $sPathAndArticle . $query;

        // remove double or more join parameter
        $sUrl = PiModRewriteUtil::removeMultipleChars('/', $sUrl);
        if (cString::getPartOfString($sUrl, -2) == '?=') {
            $sUrl = substr_replace($sUrl, '', -2);
        }

        // now convert CONTENIDO url to an AMR url
        $sUrl = PiModRewriteUrlUtil::getInstance()->toModRewriteUrl($sUrl);

        // prepend rootdir as defined in config
        // $sUrl = $this->mrCfg['rootdir'] . $sUrl;
        // this version allows for multiple domains of a client
        $sUrl = self::getMultiClientRootDir($this->mrCfg['rootdir'] ?? '') . $sUrl;

        // remove double slashes
        $sUrl = PiModRewriteUtil::removeMultipleChars('/', $sUrl);

        return $sUrl;
    }

    /**
     * Returns the defined rootdir.
     * Allows for root dir being alternatively defined as a path of setting client/%frontend_path%.
     *
     * @param string $configuredRootDir Defined rootdir
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public static function getMultiClientRootDir(string $configuredRootDir): string
    {
        // return cached rootdir if set
        if (isset(self::$cachedRootDir)) {
            return self::$cachedRootDir;
        }

        // get props of the current client
        $clientProperties = cRegistry::getClient()->getProperties();

        // return rootdir as defined in AMR if the client has no props
        if (!is_array($clientProperties)) {
            self::$cachedRootDir = $configuredRootDir;
            return $configuredRootDir;
        }

        foreach ($clientProperties as $clientProperty) {
            // skip props that are not of type 'client'
            if ($clientProperty['type'] != 'client') {
                continue;
            }

            // skip props whose name does not contain 'frontend_path'
            if (false === strstr($clientProperty['name'], 'frontend_path')) {
                continue;
            }

            // current host & path (HTTP_HOST & REQUEST_URI)
            $httpHost = $_SERVER['HTTP_HOST'] ?? '';
            $httpPath = $_SERVER['REQUEST_URI'] ?? '';

            // host and path of configured alternative URL
            $propHost = parse_url($clientProperty['value'], PHP_URL_HOST);
            $propPath = parse_url($clientProperty['value'], PHP_URL_PATH);

            // skip if http host does not equal configured host (allowing for
            // optional www)
            if ($propHost != $httpHost && ('www.' . $propHost) != $httpHost && $propHost != 'www.' . $httpHost) {
                continue;
            }

            // skip if the http path does not start with the configured path
            if (0 !== cString::findFirstPos($httpPath, $propPath)) {
                continue;
            }

            // return path as specified in client settings
            self::$cachedRootDir = $propPath;
            return $propPath;
        }

        // return rootdir as defined in AMR
        self::$cachedRootDir = $configuredRootDir;
        return $configuredRootDir;
    }

    /**
     * Loops through the given parameter array and creates the query part of the URL.
     * All non-CONTENIDO-related parameters will be excluded from composition.
     *
     * @param array $arguments Associative parameter array
     * @return string Composed query part for the URL like '?foo=bar&amp;param=value'
     */
    private function createUrlQueryPart(array $arguments): string
    {
        // set list of parameter which are to ignore while setting additional parameter
        $aIgnoredParams = [
            'idcat',
            'idart',
            'lang',
            'client',
            'idcatart',
            'idartlang',
        ];
        if ($this->mrCfg['use_language'] == 1) {
            $aIgnoredParams[] = 'changelang';
        }
        if ($this->mrCfg['use_client'] == 1) {
            $aIgnoredParams[] = 'changeclient';
        }

        // collect additional non-CONTENIDO related parameters
        $query = '';
        foreach ($arguments as $p => $v) {
            if (!in_array($p, $aIgnoredParams)) {
                // $query .= urlencode(urldecode($p)) . '=' .
                // urlencode(urldecode($v)) . $this->ampersand;
                $p = urlencode(urldecode($p));
                if (is_array($v)) {
                    // handle query parameter like foobar[0}=a&foobar[1]=b...
                    foreach ($v as $p2 => $v2) {
                        $p2 = urlencode(urldecode($p2));
                        $v2 = urlencode(urldecode($v2));
                        $query .= $p . '[' . $p2 . ']=' . $v2 . $this->ampersand;
                    }
                } else {
                    $v = urlencode(urldecode($v));
                    $query .= $p . '=' . $v . $this->ampersand;
                }
            }
        }
        if (cString::getStringLength($query) > 0) {
            $query = '?' . cString::getPartOfString($query, 0, -cString::getStringLength($this->ampersand));
        }
        return $query;
    }

    /**
     * Returns client id or name depending on settings.
     *
     * @param array $arguments Additional arguments
     * @return string|int|null Client id, client name or NULL
     * @throws cDbException
     */
    private function getClientParameter(array $arguments)
    {
        // set the client if desired
        if ($this->mrCfg['use_client'] == 1) {
            $changeClientId = cSecurity::toInteger($arguments['changeclient'] ?? 0);
            $clientId = $changeClientId > 0 ? $changeClientId : cRegistry::getClientId();
            if ($this->mrCfg['use_client_name'] == 1) {
                return urlencode(PiModRewrite::getClientName($clientId));
            } else {
                return $clientId;
            }
        }
        return null;
    }

    /**
     * Returns language id or name depending on settings.
     *
     * @param array $arguments Additional arguments
     * @return string|int|null Language id, language name or NULL
     * @throws cDbException
     */
    private function getLanguageParameter(array $arguments)
    {
        // set language if desired
        if ($this->mrCfg['use_language'] == 1) {
            $changeLanguageId = isset($arguments['changelang']) ? cSecurity::toInteger($arguments['changelang']) : 0;
            $languageId = $changeLanguageId > 0 ? $changeLanguageId : cRegistry::getLanguageId();
            if ($this->mrCfg['use_language_name'] == 1) {
                return urlencode(PiModRewrite::getLanguageName($languageId));
            } else {
                return $languageId;
            }
        }
        return NULL;
    }

    /**
     * Returns the article name depending on the current setting.
     *
     * @param array $arguments Additional arguments
     * @return string Article name
     */
    private function getArticleName(PiModRewritePrettyUrlDto $prettyUrlDto, array $arguments): string
    {
        $articleName = $prettyUrlDto->getUrlName();
        $iIdCat = intval($arguments['idcat'] ?? 0);
        $iIdCatLang = intval($arguments['idcatlang'] ?? 0);
        $iIdCatArt = intval($arguments['idcatart'] ?? 0);
        $iIdArt = intval($arguments['idart'] ?? 0);
        $iIdArtLang = intval($arguments['idartlang'] ?? 0);

        // category id was passed but not article id
        if (($iIdCat > 0 || $iIdCatLang > 0) && $iIdCatArt == 0 && $iIdArt == 0 && $iIdArtLang == 0) {
            $articleName = '';
            if ($this->mrCfg['add_startart_name_to_url']) {
                if ($this->mrCfg['default_startart_name'] !== '') {
                    // use the default start article name
                    $articleName = $this->mrCfg['default_startart_name'];
                } else {
                    $articleName =$prettyUrlDto->getUrlName();
                }
            }
        }

        return $articleName;
    }

    /**
     * Returns the composed path of url (normally the category structure).
     *
     * @return string Path
     */
    private function getPath(PiModRewritePrettyUrlDto $prettyUrlDto): string
    {
        $path = $prettyUrlDto->getUrlPath();

        // check start directory settings
        if ($this->mrCfg['startfromroot'] == 0 && (cString::getStringLength($path) > 0)) {
            // splitt string in array
            $aCategories = explode('/', $path);

            // remove the first category
            array_shift($aCategories);

            $path = implode('/', $aCategories);
        }

        return $path;
    }
}
