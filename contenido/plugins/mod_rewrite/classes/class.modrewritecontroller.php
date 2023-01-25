<?php
/**
 * AMR controller class
 *
 * @package     Plugin
 * @subpackage  ModRewrite
 * @id          $Id$:
 * @author      Murat Purc <murat@purc.de>
 * @copyright   four for business AG <www.4fb.de>
 * @license     http://www.contenido.org/license/LIZENZ.txt
 * @link        http://www.4fb.de
 * @link        http://www.contenido.org
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
 * @author      Murat Purc <murat@purc.de>
 * @package     Plugin
 * @subpackage  ModRewrite
 */
class ModRewriteController extends ModRewriteBase {
    // Error constants

    const ERROR_CLIENT = 1;
    const ERROR_LANGUAGE = 2;
    const ERROR_CATEGORY = 3;
    const ERROR_ARTICLE = 4;
    const ERROR_POST_VALIDATION = 5;
    const FRONT_CONTENT = 'front_content.php';

    /**
     * Extracted request uri path parts by path separator '/'
     *
     * @var array
     */
    private $_aParts;

    /**
     * Extracted article name from request uri
     *
     * @var string
     */
    private $_sArtName;

    /**
     * Remaining path for path resolver (see $GLOBALS['path'])
     *
     * @var string
     */
    private $_sPath;

    /**
     * Incoming URL
     *
     * @var string
     */
    private $_sIncomingUrl;

    /**
     * Resolved URL
     *
     * @var string
     */
    private $_sResolvedUrl;

    /**
     * Client id used by this class
     *
     * @var int
     */
    private $_iClientMR;

    /**
     * Language id used by this class
     *
     * @var int
     */
    private $_iLangMR;

    /**
     * Flag about occurred errors
     *
     * @var bool
     */
    private $_bError = false;

    /**
     * One of ERROR_* constants or 0
     *
     * @var int
     */
    private $_iError = 0;

    /**
     * Flag about found routing definition
     *
     * @var bool
     */
    private $_bRoutingFound = false;

    /**
     * Constructor, sets several properties.
     *
     * @param  string  $incomingUrl  Incoming URL
     */
    public function __construct($incomingUrl) {

        // CON-1266 make incoming URL lowercase if option "URLS to
        // lowercase" is set
        if (1 == $this->getConfig('use_lowercase_uri')) {
            $incomingUrl = cString::toLowerCase($incomingUrl);
        }

        $this->_sIncomingUrl = $incomingUrl;
        $this->_aParts = [];
        $this->_sArtName = '';
    }

    /**
     * Getter for overwritten client id ({@see cRegistry::getClientId()})
     *
     * @return  int  Client id
     */
    public function getClient() {
        return cRegistry::getClientId();
    }

    /**
     * Getter for overwritten change client id (see $GLOBALS['changeclient'])
     *
     * @return  int  Change client id
     */
    public function getChangeClient() {
        return $GLOBALS['changeclient'];
    }

    /**
     * Getter for article id ({@see cRegistry::getArticleId()})
     *
     * @return  int  Article id
     */
    public function getIdArt() {
        return cRegistry::getArticleId();
    }

    /**
     * Getter for category id ({@see cRegistry::getCategoryId()})
     *
     * @return  int  Category id
     */
    public function getIdCat() {
        return cRegistry::getCategoryId();
    }

    /**
     * Getter for language id ({@see cRegistry::getLanguageId()})
     *
     * @return  int  Language id
     */
    public function getLang() {
        return cRegistry::getLanguageId();
    }

    /**
     * Getter for change language id ({@see cRegistry::getLanguageId()})
     *
     * @return  int  Change language id
     */
    public function getChangeLang() {
        return (int) cRegistry::getChangeLang();
    }

    /**
     * Getter for path (see $GLOBALS['path'])
     *
     * @return  string  Path, used by path resolver
     */
    public function getPath() {
        return $this->_sPath;
    }

    /**
     * Getter for resolved url
     *
     * @return  string  Resolved url
     */
    public function getResolvedUrl() {
        return $this->_sResolvedUrl;
    }

    /**
     * Returns a flag about found routing definition
     *
     * return  bool  Flag about found routing
     */
    public function getRoutingFoundState() {
        return $this->_bRoutingFound;
    }

    /**
     * Getter for occurred error state
     *
     * @return  bool  Flag for occurred error
     */
    public function errorOccured() {
        return $this->_bError;
    }

    /**
     * Getter for occurred error state
     *
     * @return  int  Numeric error code
     */
    public function getError() {
        return $this->_iError;
    }

    /**
     * Main function to call for mod rewrite related preprocessing jobs.
     *
     * Executes some private functions to extract request URI and to set needed member variables
     * (client, language, article id, category id, etc.)
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function execute() {
        if (!parent::isEnabled()) {
            return;
        }

        $this->_extractRequestUri();

        $this->_initializeClientId();

        $this->_setClientId();

        mr_loadConfiguration($this->_iClientMR);

        $this->_setLanguageId();

        // second call after setting client and language
        $this->_extractRequestUri(true);

        $this->_setPathresolverSetting();

        $this->_setIdart();

        ModRewriteDebugger::add($this->_aParts, 'ModRewriteController::execute() _setIdart');

        $this->_postValidation();
    }

    /**
     * Extracts request URI and sets member variables $this->_sArtName and $this->_aParts
     *
     * @param  bool $secondCall  Flag about second call of this function, is needed
     *                           to re extract url if a routing definition was found
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    private function _extractRequestUri($secondCall = false) {
        $client = cRegistry::getClientId();

        // get REQUEST_URI
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        // CON-1266 make request URL lowercase if option "URLS to
        // lowercase" is set
        if (1 == $this->getConfig('use_lowercase_uri')) {
            $requestUri = cString::toLowerCase($requestUri);
        }

        // check for defined rootdir
        // allows for root dir being alternativly defined as path of setting client/%frontend_path%
        $rootdir = cUriBuilderMR::getMultiClientRootDir(parent::getConfig('rootdir'));
        if ('/' !==  $rootdir && 0 === cString::findFirstPos($requestUri, $this->_sIncomingUrl)) {
            $this->_sIncomingUrl = str_replace($rootdir, '/', $this->_sIncomingUrl);
        }

        $aUrlComponents = $this->_parseUrl($this->_sIncomingUrl);
        if (isset($aUrlComponents['path'])) {
            if (parent::getConfig('rootdir') !== '/' && cString::findFirstPos($aUrlComponents['path'], parent::getConfig('rootdir')) === 0) {
                $aUrlComponents['path'] = str_replace(parent::getConfig('rootdir'), '/', $aUrlComponents['path']);
            }

            if ($secondCall) {

                // @todo: implement real redirect of old front_content.php style urls
                // check for routing definition
                $routings = parent::getConfig('routing');
                if (is_array($routings) && isset($routings[$aUrlComponents['path']])) {
                    $aUrlComponents['path'] = $routings[$aUrlComponents['path']];
                    if (cString::findFirstPos($aUrlComponents['path'], self::FRONT_CONTENT) !== false) {
                        // routing destination contains front_content.php

                        $this->_bRoutingFound = true;

                        // set client language, if not set before
                        mr_setClientLanguageId($client);

                        //rebuild URL
                        $url = mr_buildNewUrl($aUrlComponents['path']);

                        $aUrlComponents = $this->_parseUrl($url);

                        // add query parameter to superglobal _GET
                        if (isset($aUrlComponents['query'])) {
                            $vars = NULL;
                            parse_str($aUrlComponents['query'], $vars);
                            $_GET = array_merge($_GET, $vars);
                        }

                        $this->_aParts = [];
                    }
                } else {
                    return;
                }
            }

            $aPaths = explode('/', $aUrlComponents['path']);
            foreach ($aPaths as $p => $item) {
                if (!empty($item)) {
                    // pathinfo would also work
                    $arr = explode('.', $item);
                    $count = count($arr);
                    if ($count > 0 && '.' . cString::toLowerCase($arr[$count - 1]) == parent::getConfig('file_extension')) {
                        array_pop($arr);
                        $this->_sArtName = trim(implode('.', $arr));
                    } else {
                        $this->_aParts[] = $item;
                    }
                }
            }

            if ($secondCall) {
                // reprocess extracting client and language
                $this->_setClientId();
                mr_loadConfiguration($this->_iClientMR);
                $this->_setLanguageId();
            }
        }
        ModRewriteDebugger::add($this->_aParts, 'ModRewriteController::_extractRequestUri() $this->_aParts');

        // loop parts array and remove existing 'front_content.php'
        if ($this->_hasPartArrayItems()) {
            foreach ($this->_aParts as $p => $item) {
                if ($item == self::FRONT_CONTENT) {
                    unset($this->_aParts[$p]);
                }
            }
        }
    }

    /**
     * Tries to initialize the client id.
     * This is required to load the proper plugin configuration for current client.
     */
    private function _initializeClientId() {
        global $client, $changeclient, $load_client;

        $iClient = (isset($client) && (int) $client > 0) ? $client : 0;
        $iChangeClient = (isset($changeclient) && (int) $changeclient > 0) ? $changeclient : 0;
        $iLoadClient = (isset($load_client) && (int) $load_client > 0) ? $load_client : 0;

        if ($iClient > 0 && $iChangeClient == 0) {
            $this->_iClientMR = $iClient;
        } elseif ($iChangeClient > 0) {
            $this->_iClientMR = $iChangeClient;
        } else {
            $this->_iClientMR = $iLoadClient;
        }

        if ((int) $this->_iClientMR > 0) {
            // set global client variable
            $client = (int) $this->_iClientMR;
        }
    }

    /**
     * Tries to initialize the language id.
     */
    private function _initializeLanguageId() {
        global $lang, $changelang, $load_lang;

        $iLang = (isset($lang) && (int) $lang > 0) ? $lang : 0;
        $iChangeLang = (isset($changelang) && (int) $changelang > 0) ? $changelang : 0;
        $iLoadLang = (isset($load_lang) && (int) $load_lang > 0) ? $load_lang : 0;

        if ($iLang > 0 && $iChangeLang == 0) {
            $this->_iLangMR = $iLang;
        } elseif ($iChangeLang > 0) {
            $this->_iLangMR = $iChangeLang;
        } else {
            $this->_iLangMR = $iLoadLang;
        }

        if ((int) $this->_iLangMR > 0) {
            // set global lang variable
            $lang = (int) $this->_iLangMR;
        }
    }

    /**
     * Detects client id from given url
     *
     * @throws cDbException
     */
    private function _setClientId() {
        global $client;

        if ($this->_bError) {
            return;
        } elseif ($this->_isRootRequest()) {
            // request to root
            return;
        } elseif (parent::getConfig('use_client') !== 1) {
            return;
        }

        if (parent::getConfig('use_client_name') == 1) {
            $detectedClientId = (int) ModRewrite::getClientId(array_shift($this->_aParts));
        } else {
            $detectedClientId = (int) array_shift($this->_aParts);
            if ($detectedClientId > 0 && !ModRewrite::languageIdExists($detectedClientId)) {
                $detectedClientId = 0;
            }
        }

        if ($detectedClientId > 0) {
            // overwrite existing client variables
            $this->_iClientMR = $detectedClientId;
            $client = $detectedClientId;
        } else {
            $this->_setError(self::ERROR_CLIENT);
        }
    }

    /**
     * Sets language id
     *
     * @throws cDbException
     */
    private function _setLanguageId() {
        global $lang;

        if ($this->_bError) {
            return;
        } elseif ($this->_isRootRequest()) {
            // request to root
            return;
        } elseif (parent::getConfig('use_language') !== 1) {
            return;
        }

        if (parent::getConfig('use_language_name') == 1) {
            // thanks to Nicolas Dickinson for multi Client/Language BugFix
            $languageName = array_shift($this->_aParts);
            $detectedLanguageId = (int) ModRewrite::getLanguageId($languageName, $this->_iClientMR);
        } else {
            $detectedLanguageId = (int) array_shift($this->_aParts);
            if ($detectedLanguageId > 0 && !ModRewrite::clientIdExists($detectedLanguageId)) {
                $detectedLanguageId = 0;
            }
        }

        if ($detectedLanguageId > 0) {
            // overwrite existing language variables
            $this->_iLangMR = $detectedLanguageId;
            $lang = $detectedLanguageId;
        } else {
            $this->_setError(self::ERROR_LANGUAGE);
        }
    }

    /**
     * Sets path resolver and category id
     *
     * @throws cException
     */
    private function _setPathresolverSetting() {
        global $client, $lang, $load_lang, $idcat;

        if ($this->_bError) {
            return;
        } elseif (!$this->_hasPartArrayItems()) {
            return;
        }

        $this->_sPath = '/' . implode('/', $this->_aParts) . '/';

        if (!isset($lang) || (int) $lang <= 0) {
            if ((int) $load_lang > 0) {
                // load_client is set in __FRONTEND_PATH__/data/config/config.php
                $lang = (int) $load_lang;
            } else {
                // get client id from table
                $clCol = new cApiClientLanguageCollection();
                $clCol->setWhere('idclient', $client);
                $clCol->query();
                if (false !== $clItem = $clCol->next()) {
                    $lang = $clItem->get('idlang');
                }
            }
        }

        $idcat = (int) ModRewrite::getCatIdByUrlPath($this->_sPath);

        if ($idcat == 0) {
            // category couldn't resolved
            $this->_setError(self::ERROR_CATEGORY);
            $idcat = NULL;
        } else {
            // unset $this->_sPath if $idcat could set, otherwise it would be resolved again.
            $this->_sPath = '';
        }

        ModRewriteDebugger::add($idcat, 'ModRewriteController->_setPathresolverSetting $idcat');
        ModRewriteDebugger::add($this->_sPath, 'ModRewriteController->_setPathresolverSetting $this->_sPath');
    }

    /**
     * Sets article id
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    private function _setIdart() {
        global $idcat, $idart, $lang;

        if ($this->_bError) {
            return;
        } elseif ($this->_isRootRequest()) {
            return;
        }

        $iIdCat = (isset($idcat) && (int) $idcat > 0) ? $idcat : 0;
        $iIdArt = (isset($idart) && (int) $idart > 0) ? $idart : 0;
        $detectedIdart = 0;
        $defaultStartArtName = parent::getConfig('default_startart_name');
        $currArtName = $this->_sArtName;

        // startarticle name in url
        if (parent::getConfig('add_startart_name_to_url') && !empty($currArtName)) {
            if ($currArtName == $defaultStartArtName) {
                // stored articlename is the default one, remove it ModRewrite::getArtIdByWebsafeName()
                // will find the real article name
                $currArtName = '';
            }
        }

        // Last check, before detecting article id
        if ($iIdCat == 0 && $iIdArt == 0 && empty($currArtName)) {
            // no idcat, idart and article name
            // must be a request to root or with language name and/or client name part!
            return;
        }

        if ($iIdCat > 0 && $iIdArt == 0 && !empty($currArtName)) {
            // existing idcat with no idart and with article name
            $detectedIdart = (int) ModRewrite::getArtIdByWebsafeName($currArtName, $iIdCat, $lang);
        } elseif ($iIdCat > 0 && $iIdArt == 0 && empty($currArtName)) {
            if (parent::getConfig('add_startart_name_to_url') && ($currArtName == '' || $defaultStartArtName == '' || $defaultStartArtName == $this->_sArtName)) {
                // existing idcat without idart and without article name or with default start article name
                $catLangColl = new cApiCategoryLanguageCollection();
                $detectedIdart = (int) $catLangColl->getStartIdartByIdcatAndIdlang($iIdCat, $lang);
            }
        } elseif ($iIdCat == 0 && $iIdArt == 0 && !empty($currArtName)) {
            // no idcat and idart but article name
            $detectedIdart = (int) ModRewrite::getArtIdByWebsafeName($currArtName, $iIdCat, $lang);
        }

        if ($detectedIdart > 0) {
            $idart = $detectedIdart;
        } elseif (!empty($currArtName)) {
            $this->_setError(self::ERROR_ARTICLE);
        }

        ModRewriteDebugger::add($detectedIdart, 'ModRewriteController->_setIdart $detectedIdart');
    }

    /**
     * Does post validation of the extracted data.
     *
     * One main goal of this function is to prevent duplicated content, which could happen, if
     * the configuration 'startfromroot' is activated.
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    private function _postValidation() {
        global $idcat, $idart, $client;

        if ($this->_bError || $this->_bRoutingFound || !$this->_hasPartArrayItems()) {
            return;
        }

        if (parent::getConfig('startfromroot') == 1 && parent::getConfig('prevent_duplicated_content') == 1) {

            // prevention of duplicated content if '/firstcat/' is directly requested!

            $idcat = (isset($idcat) && (int) $idcat > 0) ? $idcat : NULL;
            $idart = (isset($idart) && (int) $idart > 0) ? $idart : NULL;

            // compose new parameter
            $param = '';
            if ($idcat) {
                $param .= 'idcat=' . (int) $idcat;
            }
            if ($idart) {
                $param .= ($param !== '') ? '&idart=' . (int) $idart : 'idart=' . (int) $idart;
            }

            if ($param == '') {
                return;
            }

            // set client language, if not set before
            mr_setClientLanguageId($client);

            //rebuild url
            $url = mr_buildNewUrl(self::FRONT_CONTENT . '?' . $param);

            $aUrlComponents = @parse_url($this->_sIncomingUrl);
            $incomingUrl = (isset($aUrlComponents['path'])) ? $aUrlComponents['path'] : '';

            ModRewriteDebugger::add($url, 'ModRewriteController->_postValidation validate url');
            ModRewriteDebugger::add($incomingUrl, 'ModRewriteController->_postValidation incomingUrl');

            // now the new generated uri should be identical with the request uri
            if ($incomingUrl !== $url) {
                $this->_setError(self::ERROR_POST_VALIDATION);
                $idcat = NULL;
            }
        }
    }

    /**
     * Parses the url using defined separators
     *
     * @param   string  $url  Incoming url
     * @return  array|bool  Parsed url
     */
    private function _parseUrl($url) {
        $this->_sResolvedUrl = $url;

        $oMrUrlUtil = ModRewriteUrlUtil::getInstance();
        $url = $oMrUrlUtil->toContenidoUrl($url);

        return @parse_url($url);
    }

    /**
     * Returns state of parts property.
     *
     * @return  bool  True if $this->_aParts property contains items
     */
    private function _hasPartArrayItems() {
        return (!empty($this->_aParts));
    }

    /**
     * Checks if current request was a root request.
     *
     * @return  bool
     */
    private function _isRootRequest() {
        return ($this->_sIncomingUrl == '/' || $this->_sIncomingUrl == '');
    }

    /**
     * Sets error code and error flag (everything greater than 0 is an error)
     * @param  int  $errCode
     */
    private function _setError($errCode) {
        $this->_iError = (int) $errCode;
        $this->_bError = ((int) $errCode > 0);
    }

}