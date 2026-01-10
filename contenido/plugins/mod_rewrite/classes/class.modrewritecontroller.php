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
 * Mod Rewrite controller class. Extracts url parts and sets some necessary globals like:
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
class ModRewriteController extends ModRewriteBase
{
    // Error constants

    public const ERROR_CLIENT = 1;
    public const ERROR_LANGUAGE = 2;
    public const ERROR_CATEGORY = 3;
    public const ERROR_ARTICLE = 4;
    public const ERROR_POST_VALIDATION = 5;
    public const FRONT_CONTENT = 'front_content.php';

    /**
     * @var array Extracted request uri path parts by path separator '/'
     */
    private $urlComponents = [];

    /**
     * @var string Extracted article name from request uri
     */
    private $articleName = '';

    /**
     * @var string Remaining path for path resolver (see $GLOBALS['path'])
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
     * Constructor, sets several properties.
     *
     * @param string $incomingUrl Incoming URL via request, e.g. `$_SERVER['REQUEST_URI']`.
     */
    public function __construct(string $incomingUrl)
    {
        // CON-1266 make incoming URL lowercase if option "URLS to
        // lowercase" is set
        if (1 == $this->getConfig('use_lowercase_uri')) {
            $incomingUrl = cString::toLowerCase($incomingUrl);
        }

        $this->incomingUrl = $incomingUrl;
    }

    /**
     * Getter for overwritten client id ({@see cRegistry::getClientId()})
     */
    public function getClient(): int
    {
        return cRegistry::getClientId();
    }

    /**
     * Getter for overwritten change client id (see $GLOBALS['changeclient'])
     *
     * @return ?int Change client id
     */
    public function getChangeClient(): ?int
    {
        return isset($GLOBALS['changeclient']) ? cSecurity::toInteger($GLOBALS['changeclient']) : null;
    }

    /**
     * Getter for article id ({@see cRegistry::getArticleId()})
     */
    public function getIdArt(): int
    {
        return cRegistry::getArticleId();
    }

    /**
     * Getter for category id ({@see cRegistry::getCategoryId()})
     */
    public function getIdCat(): int
    {
        return cRegistry::getCategoryId();
    }

    /**
     * Getter for language id ({@see cRegistry::getLanguageId()})
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
     * Getter for path (see $GLOBALS['path'])
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
     * @deprecated Use {@see ModRewriteController::isRoutingFound()}.
     * @since 2.0.1
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
     * @deprecated Use {@see ModRewriteController::isError()}.
     * @since 2.0.1
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
     * Getter for occurred error code, see ModRewriteController::ERROR_* constants.
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * Main function to call for mod rewrite related preprocessing jobs.
     *
     * Executes some private functions to extract request URI and to set needed member variables
     * (client, language, article id, category id, etc.)
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function execute()
    {
        if (!parent::isEnabled()) {
            return;
        }

        $this->_extractRequestUri();

        $this->initializeClientId();

        $this->detectClientId();

        mr_loadConfiguration($this->mrClientId);

        $this->detectLanguageId();

        // second call after setting client and language
        $this->_extractRequestUri(true);

        $this->detectPathResolverSetting();

        $this->_setIdart();

        ModRewriteDebugger::add($this->urlComponents, 'ModRewriteController::execute() _setIdart');

        $this->postValidation();
    }

    /**
     * Extracts request URI and sets member variables $this->articleName and $this->urlComponents
     *
     * @param bool $secondCall Flag about second call of this function, is needed to re-extract
     *      url if a routing definition was found
     * @throws cDbException|cException|cInvalidArgumentException
     */
    private function _extractRequestUri(bool $secondCall = false)
    {
        $client = cRegistry::getClientId();

        // get REQUEST_URI
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        // CON-1266 make request URL lowercase if option "URLS to
        // lowercase" is set
        if (1 == $this->getConfig('use_lowercase_uri')) {
            $requestUri = cString::toLowerCase($requestUri);
        }

        $rootDir = parent::getConfig('rootdir');

        // check for defined rootdir
        // allows for root dir being alternatively defined as path of setting client/%frontendpath%
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
                        mr_setClientLanguageId($client);

                        //rebuild URL
                        $url = mr_buildNewUrl($urlComponents['path']);

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
                mr_loadConfiguration($this->mrClientId);
                $this->detectLanguageId();
            }
        }
        ModRewriteDebugger::add(
            $this->urlComponents,
            'ModRewriteController::_extractRequestUri() $this->urlComponents'
        );

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
     * This is required to load the proper plugin configuration for current client.
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

        $actLanguageId = cSecurity::isInteger($lang ?? '0');

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
            $detectedClientId = ModRewrite::getClientId(array_shift($this->urlComponents));
        } else {
            $detectedClientId = cSecurity::toInteger(array_shift($this->urlComponents));
            if ($detectedClientId > 0 && !ModRewrite::languageIdExists($detectedClientId)) {
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
            $detectedLanguageId = ModRewrite::getLanguageId($languageName, $this->mrClientId);
        } else {
            $detectedLanguageId = cSecurity::toInteger(array_shift($this->urlComponents));
            if ($detectedLanguageId > 0 && !ModRewrite::clientIdExists($detectedLanguageId)) {
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
        global $client, $lang, $idcat;

        if ($this->isError()) {
            return;
        } elseif (!$this->hasUrlComponents()) {
            return;
        }

        $this->path = '/' . implode('/', $this->urlComponents) . '/';

        if (!isset($lang) || cSecurity::isInteger($lang) <= 0) {
            if (cRegistry::getLoadLanguageId()) {
                // load_client is set in __FRONTEND_PATH__/data/config/config.php
                $lang = cSecurity::isInteger(cRegistry::getLoadLanguageId());
            } else {
                // get client id from table
                $clientLanguageCollection = new cApiClientLanguageCollection();
                $clientLanguageCollection->setWhere('idclient', $client);
                $clientLanguageCollection->query();
                if (($item = $clientLanguageCollection->next()) !== false) {
                    $lang = cSecurity::isInteger($item->get('idlang'));
                }
            }
        }

        $idcat = ModRewrite::getCatIdByUrlPath($this->path);

        if ($idcat == 0) {
            // category couldn't resolve
            $this->setError(self::ERROR_CATEGORY);
            $idcat = NULL;
        } else {
            // unset $this->path if $idcat could set, otherwise it would be resolved again.
            $this->path = '';
        }

        ModRewriteDebugger::add($idcat, 'ModRewriteController->detectPathResolverSetting $idcat');
        ModRewriteDebugger::add($this->path, 'ModRewriteController->detectPathResolverSetting $this->path');
    }

    /**
     * Sets article id
     *
     * @throws cDbException|cInvalidArgumentException
     */
    private function _setIdart()
    {
        // NOTE: Use globals here!
        global $idcat, $idart, $lang;

        if ($this->isError()) {
            return;
        } elseif ($this->isRootRequest()) {
            return;
        }

        $actCategoryId = isset($idcat) && cSecurity::isInteger($idcat) > 0 ? cSecurity::isInteger($idcat) : NULL;
        $actArticleId = isset($idart) && cSecurity::isInteger($idart) > 0 ? cSecurity::isInteger($idart) : NULL;
        $detectedIdart = 0;
        $defaultStartArtName = parent::getConfig('default_startart_name');
        $currArtName = $this->articleName;

        // start article name in url
        if (parent::getConfig('add_startart_name_to_url') && !empty($currArtName)) {
            if ($currArtName == $defaultStartArtName) {
                // stored article name is the default one, remove it ModRewrite::getArtIdByWebsafeName()
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
            $detectedIdart = cSecurity::isInteger(
                ModRewrite::getArtIdByWebsafeName($currArtName, $actCategoryId, $lang)
            );
        } elseif ($actCategoryId > 0 && $actArticleId == 0 && empty($currArtName)) {
            if (
                parent::getConfig('add_startart_name_to_url')
                && ($currArtName == '' || $defaultStartArtName == '' || $defaultStartArtName == $this->articleName)
            ) {
                // existing idcat without idart and without article name or with default start article name
                $catLangColl = new cApiCategoryLanguageCollection();
                $detectedIdart = $catLangColl->getStartIdartByIdcatAndIdlang($actCategoryId, $lang);
            }
        } elseif ($actCategoryId == 0 && $actArticleId == 0 && !empty($currArtName)) {
            // no idcat and idart but article name
            $detectedIdart = cSecurity::isInteger(
                ModRewrite::getArtIdByWebsafeName($currArtName, $actCategoryId, $lang)
            );
        }

        if ($detectedIdart > 0) {
            $idart = $detectedIdart;
        } elseif (!empty($currArtName)) {
            $this->setError(self::ERROR_ARTICLE);
        }

        ModRewriteDebugger::add($detectedIdart, 'ModRewriteController->_setIdart $detectedIdart');
    }

    /**
     * Does post validation of the extracted data.
     *
     * One main goal of this function is to prevent duplicated content, which could happen, if
     * the configuration 'startfromroot' is activated.
     *
     * @throws cDbException|cInvalidArgumentException|cException
     */
    private function postValidation()
    {
        // NOTE: Use globals here!
        global $idcat, $idart, $client;

        if ($this->isError()|| $this->isRoutingFound() || !$this->hasUrlComponents()) {
            return;
        }

        if (parent::getConfig('startfromroot') == 1 && parent::getConfig('prevent_duplicated_content') == 1) {
            // prevention of duplicated content if '/firstcat/' is directly requested!

            $idcat = isset($idcat) && cSecurity::isInteger($idcat) > 0 ? cSecurity::isInteger($idcat) : NULL;
            $idart = isset($idart) && cSecurity::isInteger($idart) > 0 ? cSecurity::isInteger($idart) : NULL;

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
            mr_setClientLanguageId(cSecurity::toInteger($client));

            //rebuild url
            $url = mr_buildNewUrl(self::FRONT_CONTENT . '?' . http_build_query($params));

            $urlComponents = @parse_url($this->incomingUrl);
            $incomingUrl = $urlComponents['path'] ?? '';

            ModRewriteDebugger::add($url, 'ModRewriteController->postValidation validate url');
            ModRewriteDebugger::add($incomingUrl, 'ModRewriteController->postValidation incomingUrl');

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
        $url = ModRewriteUrlUtil::getInstance()->toContenidoUrl($url);

        return @parse_url(ModRewriteUrlUtil::getInstance()->toContenidoUrl($url));
    }

    /**
     * Returns state of parts property.
     */
    private function hasUrlComponents(): bool
    {
        return !empty($this->urlComponents);
    }

    /**
     * Checks if current request was a root request.
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
