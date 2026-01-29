<?php

/**
 * AMR controller class
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
 * Advanced Mod Rewrite plugin controller class. Extracts url parts and sets some necessary globals like:
 * - $idart
 * - $idcat
 * - $client
 * - $changeclient
 * - $lang
 * - $changelang
 *
 * @author     Murat Purc <murat@purc.de>
 * @package    Plugin
 * @subpackage ModRewrite
 */
class PiModRewriteFrontContentService extends PiModRewriteBase
{
    // Error constants

    public const ERROR_CLIENT = 1;
    public const ERROR_LANGUAGE = 2;
    public const ERROR_CATEGORY = 3;
    public const ERROR_ARTICLE = 4;
    public const ERROR_POST_VALIDATION = 5;
    public const FRONT_CONTENT = 'front_content.php';

    /**
     * @var PiModRewriteConfigurationService
     */
    private $mrConfigurationService;

    /**
     * @var array Extracted request uri path parts by path separator '/'
     */
    private $urlComponents = [];

    /**
     * @var string Extracted article name from request uri
     */
    private $articleName = '';

    /**
     * @var string The remaining path for path resolver (see $GLOBALS['path'])
     */
    private $path = '';

    /**
     * @var string Incoming URL
     */
    private $incomingUrl;

    /**
     * @var string Resolved URL
     */
    private $resolvedUrl = '';

    /**
     * @var ?int Client id used by this class
     */
    private $mrClientId;

    /**
     * @var ?int Language id used by this class
     */
    private $mrLanguageId;

    /**
     * @var bool Flag about occurred errors
     */
    private $isError = false;

    /**
     * @var int One of ERROR_* constants or 0
     */
    private $error = 0;

    /**
     * @var bool Flag about found routing definition
     */
    private $isRoutingFound = false;

    /**
     * @param string $incomingUrl Incoming URL via request, e.g. `$_SERVER['REQUEST_URI']`.
     */
    public function __construct(string $incomingUrl)
    {
        // CON-1266 make incoming URL lowercase if option "URLS to
        // lowercase" is set
        if (1 == $this->getConfig('use_lowercase_uri')) {
            $incomingUrl = cString::toLowerCase($incomingUrl);
        }

        $this->mrConfigurationService = PiModRewriteConfigurationService::getInstance();
        $this->incomingUrl = $incomingUrl;
    }

    /**
     * Getter for the overwritten client id ({@see cRegistry::getClientId()})
     */
    public function getClient(): int
    {
        return cRegistry::getClientId();
    }

    /**
     * Getter for the overwritten change client id (see $GLOBALS['changeclient'])
     *
     * @return ?int Change client id
     */
    public function getChangeClient(): ?int
    {
        return isset($GLOBALS['changeclient']) ? cSecurity::toInteger($GLOBALS['changeclient']) : null;
    }

    /**
     * Getter for the article id ({@see cRegistry::getArticleId()})
     */
    public function getIdArt(): int
    {
        return cRegistry::getArticleId();
    }

    /**
     * Getter for the category id ({@see cRegistry::getCategoryId()})
     */
    public function getIdCat(): int
    {
        return cRegistry::getCategoryId();
    }

    /**
     * Getter for the language id ({@see cRegistry::getLanguageId()})
     */
    public function getLang(): int
    {
        return cRegistry::getLanguageId();
    }

    /**
     * Getter for change language id ({@see cRegistry::getLanguageId()})
     */
    public function getChangeLang(): int
    {
        return cRegistry::getChangeLang();
    }

    /**
     * Getter for the path (see $GLOBALS['path'])
     *
     * @return string Path, used by path resolver
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Getter for the resolved url.
     */
    public function getResolvedUrl(): string
    {
        return $this->resolvedUrl;
    }

    /**
     * @deprecated Use {@see PiModRewriteFrontContentService::isRoutingFound()}.
     * @since 2.1.0
     */
    public function getRoutingFoundState(): bool
    {
        return $this->isRoutingFound();
    }

    /**
     * Returns a flag about found routing definition.
     */
    public function isRoutingFound(): bool
    {
        return $this->isRoutingFound;
    }

    /**
     * @deprecated Use {@see PiModRewriteFrontContentService::isError()}.
     * @since 2.1.0
     */
    public function errorOccured(): bool
    {
        return $this->isError();
    }

    /**
     * Getter for occurred error.
     */
    public function isError(): bool
    {
        return $this->isError;
    }

    /**
     * Getter for occurred error code, see PiModRewriteFrontContentService::ERROR_* constants.
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * Main function to call for mod-rewrite-related preprocessing jobs.
     *
     * Executes some private functions to extract request URI and to set necessary member variables
     * (client, language, article id, category id, etc.)
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function execute()
    {
        if (!parent::isEnabled()) {
            return;
        }

        $this->extractRequestUri();

        $this->initializeClientId();

        $this->detectClientId();

        $this->mrConfigurationService->loadConfiguration($this->mrClientId);

        $this->detectLanguageId();

        // second call after setting client and language
        $this->extractRequestUri(true);

        $this->detectPathResolverSetting();

        $this->initializeArticleId();

        PiModRewriteDebugger::add($this->urlComponents, __METHOD__ . ' initializeArticleId');

        $this->postValidation();
    }

    /**
     * Extracts request URI and sets the member variables $this->articleName and $this->urlComponents
     *
     * @param bool $secondCall Flag about the second call of this function. This is needed to re-extract
     *      the URL if a routing definition was found.
     * @throws cDbException|cException|cInvalidArgumentException
     */
    private function extractRequestUri(bool $secondCall = false)
    {
        $clientId = cRegistry::getClientId();

        // get REQUEST_URI
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        // CON-1266 make request URL lowercase if option "URLS to
        // lowercase" is set
        if (1 == $this->getConfig('use_lowercase_uri')) {
            $requestUri = cString::toLowerCase($requestUri);
        }

        $rootDir = parent::getConfig('rootdir');

        // check for defined rootdir
        // allows for root dir being alternatively defined as the path of setting client/%frontendpath%
        $rootdir = cUriBuilderMR::getMultiClientRootDir($rootDir ?? '');
        if ('/' !== $rootdir && 0 === cString::findFirstPos($requestUri, $this->incomingUrl)) {
            $this->incomingUrl = str_replace($rootdir, '/', $this->incomingUrl);
        }

        $urlComponents = $this->parseUrl($this->incomingUrl);
        if (isset($urlComponents['path'])) {
            if ($rootDir !== '/' && cString::findFirstPos($urlComponents['path'], $rootDir) === 0) {
                $urlComponents['path'] = str_replace($rootDir, '/', $urlComponents['path']);
            }

            if ($secondCall) {
                // @todo: implement real redirect of old front_content.php style urls
                // check for routing definition
                $routings = parent::getConfig('routing');
                if (is_array($routings) && isset($routings[$urlComponents['path']])) {
                    $urlComponents['path'] = $routings[$urlComponents['path']];
                    if (cString::findFirstPos($urlComponents['path'], self::FRONT_CONTENT) !== false) {
                        // routing destination contains front_content.php

                        $this->isRoutingFound = true;

                        // set client language, if not set before
                        PiModRewriteUtil::setClientLanguageId($clientId);

                        //rebuild URL
                        $url = PiModRewriteUtil::buildNewUrl($urlComponents['path']);

                        $urlComponents = $this->parseUrl($url);

                        // add query parameter to superglobal _GET
                        if (isset($urlComponents['query'])) {
                            $vars = NULL;
                            parse_str($urlComponents['query'], $vars);
                            $_GET = array_merge($_GET, $vars);
                        }

                        $this->urlComponents = [];
                    }
                } else {
                    return;
                }
            }

            $pathParts = array_filter(explode('/', $urlComponents['path']));
            $extension = parent::getConfig('file_extension');
            foreach ($pathParts as $item) {
                // pathinfo would also work
                $arr = explode('.', $item);
                $count = count($arr);
                if ($count > 0 && '.' . cString::toLowerCase($arr[$count - 1]) == $extension) {
                    array_pop($arr);
                    $this->articleName = trim(implode('.', $arr));
                } else {
                    $this->urlComponents[] = $item;
                }
            }

            if ($secondCall) {
                // reprocess extracting client and language
                $this->detectClientId();
                $this->mrConfigurationService->loadConfiguration($this->mrClientId);
                $this->detectLanguageId();
            }
        }
        PiModRewriteDebugger::add($this->urlComponents, __METHOD__ . ' $this->urlComponents');

        // loop parts array and remove existing 'front_content.php'
        if ($this->hasUrlComponents()) {
            foreach ($this->urlComponents as $p => $item) {
                if ($item == self::FRONT_CONTENT) {
                    unset($this->urlComponents[$p]);
                }
            }
        }
    }

    /**
     * Tries to initialize the client id.
     * This is required to load the proper plugin configuration for the current client.
     */
    private function initializeClientId()
    {
        // Use global here, the variable will be updated!
        global $client;

        $actClientId = cSecurity::toInteger($client ?? '0');

        if ($actClientId > 0 && cRegistry::getChangeClientId() == 0) {
            $this->mrClientId = $actClientId;
        } elseif (cRegistry::getChangeClientId() > 0) {
            $this->mrClientId = cRegistry::getChangeClientId();
        } else {
            $this->mrClientId = cRegistry::getLoadClientId();
        }

        if ($this->mrClientId > 0) {
            // set global client variable
            $client = $this->mrClientId;
        }
    }

    /**
     * Tries to initialize the language id.
     */
    private function initializeLanguageId()
    {
        // Use global here, the variable will be updated!
        global $lang;

        $actLanguageId = cSecurity::toInteger($lang ?? '0');

        if ($actLanguageId > 0 && !cRegistry::getChangeLang() == 0) {
            $this->mrLanguageId = $actLanguageId;
        } elseif (cRegistry::getChangeLang() > 0) {
            $this->mrLanguageId = cRegistry::getChangeLang();
        } else {
            $this->mrLanguageId = cRegistry::getLoadLanguageId();
        }

        if ($this->mrLanguageId > 0) {
            // set global lang variable
            $lang = $this->mrLanguageId;
        }
    }

    /**
     * Detects client id from given url
     *
     * @throws cDbException
     */
    private function detectClientId()
    {
        // Use global here, the variables will be updated!
        global $client;

        if ($this->isError()) {
            return;
        } elseif ($this->isRootRequest()) {
            // request to root
            return;
        } elseif (parent::getConfig('use_client') !== 1) {
            return;
        }

        if (parent::getConfig('use_client_name') == 1) {
            $detectedClientId = PiModRewrite::getClientId(array_shift($this->urlComponents));
        } else {
            $detectedClientId = cSecurity::toInteger(array_shift($this->urlComponents));
            if ($detectedClientId > 0 && !PiModRewrite::languageIdExists($detectedClientId)) {
                $detectedClientId = 0;
            }
        }

        if ($detectedClientId > 0) {
            // overwrite existing client variables
            $this->mrClientId = $detectedClientId;
            $client = $detectedClientId;
        } else {
            $this->setError(self::ERROR_CLIENT);
        }
    }

    /**
     * Detects language id.
     *
     * @throws cDbException
     */
    private function detectLanguageId()
    {
        // NOTE: Use global here
        global $lang;

        if ($this->isError()) {
            return;
        } elseif ($this->isRootRequest()) {
            // request to root
            return;
        } elseif (parent::getConfig('use_language') !== 1) {
            return;
        }

        if (parent::getConfig('use_language_name') == 1) {
            // thanks to Nicolas Dickinson for multi Client/Language BugFix
            $languageName = cSecurity::toString(array_shift($this->urlComponents));
            $detectedLanguageId = PiModRewrite::getLanguageId($languageName, $this->mrClientId);
        } else {
            $detectedLanguageId = cSecurity::toInteger(array_shift($this->urlComponents));
            if ($detectedLanguageId > 0 && !PiModRewrite::clientIdExists($detectedLanguageId)) {
                $detectedLanguageId = 0;
            }
        }

        if ($detectedLanguageId > 0) {
            // overwrite existing language variables
            $this->mrLanguageId = $detectedLanguageId;
            $lang = $detectedLanguageId;
        } else {
            $this->setError(self::ERROR_LANGUAGE);
        }
    }

    /**
     * Sets path resolver and category id
     *
     * @throws cException
     */
    private function detectPathResolverSetting()
    {
        // NOTE: Use globals here!
        global $lang, $idcat;

        if ($this->isError()) {
            return;
        } elseif (!$this->hasUrlComponents()) {
            return;
        }

        $this->path = sprintf('/%s/', implode('/', $this->urlComponents));

        if (!isset($lang) || cSecurity::toInteger($lang) <= 0) {
            if (cRegistry::getLoadLanguageId()) {
                // load_client is set in __FRONTEND_PATH__/data/config/config.php
                $lang = cSecurity::toInteger(cRegistry::getLoadLanguageId());
            } else {
                // get client id from the table
                $clientLanguageCollection = new cApiClientLanguageCollection();
                $clientLanguageCollection->setWhere('idclient', cRegistry::getClientId());
                $clientLanguageCollection->query();
                if (($item = $clientLanguageCollection->next()) !== false) {
                    $lang = cSecurity::toInteger($item->get('idlang'));
                }
            }
        }

        $idcat = PiModRewrite::getCatIdByUrlPath($this->path);

        if ($idcat == 0) {
            // category couldn't resolve
            $this->setError(self::ERROR_CATEGORY);
            $idcat = NULL;
        } else {
            // unset $this->path if $idcat could set, otherwise it would be resolved again.
            $this->path = '';
        }

        PiModRewriteDebugger::add($idcat, __METHOD__ . ' $idcat');
        PiModRewriteDebugger::add($this->path, __METHOD__ . ' $this->path');
    }

    /**
     * Sets article id
     *
     * @throws cDbException|cInvalidArgumentException
     */
    private function initializeArticleId()
    {
        // NOTE: Use globals here!
        global $idcat, $idart;

        if ($this->isError()) {
            return;
        } elseif ($this->isRootRequest()) {
            return;
        }

        $actCategoryId = cSecurity::toInteger($idcat ?? 0) > 0 ? cSecurity::toInteger($idcat) : NULL;
        $actArticleId = cSecurity::toInteger($idart ?? 0) > 0 ? cSecurity::toInteger($idart) : NULL;
        $detectedIdart = 0;
        $defaultStartArtName = parent::getConfig('default_startart_name');
        $currArtName = $this->articleName;

        // start article the name in url
        if (parent::getConfig('add_startart_name_to_url') && !empty($currArtName)) {
            if ($currArtName == $defaultStartArtName) {
                // The stored article name is the default one, remove it PiModRewrite::getArtIdByWebsafeName()
                // will find the real article name
                $currArtName = '';
            }
        }

        // Last check, before detecting article id
        if ($actCategoryId == 0 && $actArticleId == 0 && empty($currArtName)) {
            // no idcat, idart and article name
            // must be a request to root or with language name and/or client name part!
            return;
        }

        if ($actCategoryId > 0 && $actArticleId == 0 && !empty($currArtName)) {
            // existing idcat with no idart and with article name
            $detectedIdart = cSecurity::toInteger(
                PiModRewrite::getArtIdByWebsafeName($currArtName, $actCategoryId, $this->getLang())
            );
        } elseif ($actCategoryId > 0 && $actArticleId == 0 && empty($currArtName)) {
            if (
                parent::getConfig('add_startart_name_to_url')
                && ($currArtName == '' || $defaultStartArtName == '' || $defaultStartArtName == $this->articleName)
            ) {
                // existing idcat without idart and without article name or with default start article name
                $catLangColl = new cApiCategoryLanguageCollection();
                $detectedIdart = $catLangColl->getStartIdartByIdcatAndIdlang($actCategoryId, $this->getLang());
            }
        } elseif ($actCategoryId == 0 && $actArticleId == 0 && !empty($currArtName)) {
            // no idcat and idart but article name
            $detectedIdart = cSecurity::toInteger(
                PiModRewrite::getArtIdByWebsafeName($currArtName, $actCategoryId, $this->getLang())
            );
        }

        if ($detectedIdart > 0) {
            $idart = $detectedIdart;
        } elseif (!empty($currArtName)) {
            $this->setError(self::ERROR_ARTICLE);
        }

        PiModRewriteDebugger::add($detectedIdart, __METHOD__ . ' $detectedIdart');
    }

    /**
     * Does post validation of the extracted data.
     *
     * One main goal of this function is to prevent duplicated content, which could happen if
     * the configuration 'startfromroot' is activated.
     *
     * @throws cDbException|cInvalidArgumentException|cException
     */
    private function postValidation()
    {
        // NOTE: Use globals here!
        global $idcat, $idart;

        if ($this->isError()|| $this->isRoutingFound() || !$this->hasUrlComponents()) {
            return;
        }

        if (parent::getConfig('startfromroot') == 1 && parent::getConfig('prevent_duplicated_content') == 1) {
            // prevention of duplicated content if '/firstcat/' is directly requested!

            $idcat = cSecurity::toInteger($idcat ?? 0) > 0 ? cSecurity::toInteger($idcat) : NULL;
            $idart = cSecurity::toInteger($idart ?? 0) > 0 ? cSecurity::toInteger($idart) : NULL;

            // compose new parameter
            $params = [];
            if ($idcat) {
                $params['idcat'] = $idcat;
            }
            if ($idart) {
                $params['idart'] = $idart;
            }

            if (empty($params)) {
                return;
            }

            // set client language, if not set before
            PiModRewriteUtil::setClientLanguageId(cRegistry::getClientId());

            //rebuild url
            $url = PiModRewriteUtil::buildNewUrl(self::FRONT_CONTENT . '?' . http_build_query($params));

            $urlComponents = @parse_url($this->incomingUrl);
            $incomingUrl = $urlComponents['path'] ?? '';

            PiModRewriteDebugger::add($url, __METHOD__ . ' validate url');
            PiModRewriteDebugger::add($incomingUrl, __METHOD__ . ' incomingUrl');

            // now the new generated uri should be identical with the request uri
            if ($incomingUrl !== $url) {
                $this->setError(self::ERROR_POST_VALIDATION);
                $idcat = NULL;
            }
        }
    }

    /**
     * Parses the url using defined separators
     *
     * @return array|bool  Parsed url of `false` on fail, see PHP function `parse_url()`.
     */
    private function parseUrl(string $url)
    {
        $this->resolvedUrl = $url;
        $url = PiModRewriteUrlUtil::getInstance()->toContenidoUrl($url);

        return @parse_url(PiModRewriteUrlUtil::getInstance()->toContenidoUrl($url));
    }

    /**
     * Returns state of parts property.
     */
    private function hasUrlComponents(): bool
    {
        return !empty($this->urlComponents);
    }

    /**
     * Checks if the current request was a root request.
     */
    private function isRootRequest(): bool
    {
        return $this->incomingUrl == '/' || $this->incomingUrl == '';
    }

    /**
     * Sets error code and error flag (everything greater than 0 is an error)
     */
    private function setError(int $errCode)
    {
        $this->error = $errCode;
        $this->isError = $errCode > 0;
    }

}

/**
 * @deprecated Since Advanced Mod Rewrite 2.1.0, use {@see PiModRewriteFrontContentService} instead.
 */
class ModRewriteController extends PiModRewriteFrontContentService
{}
