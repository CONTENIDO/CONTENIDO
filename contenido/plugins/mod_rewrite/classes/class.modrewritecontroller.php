<?php
/**
 * Includes Mod Rewrite controller class.
 *
 * @author      Murat Purc <murat@purc.de>
 * @copyright   © Murat Purc 2008
 * @package     Contenido
 * @subpackage  ModRewrite
 */


defined('CON_FRAMEWORK') or die('Illegal call');


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
 * @date        16.04.2008
 * @package     Contenido
 * @subpackage  ModRewrite
 */
class ModRewriteController extends ModRewriteBase
{

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
     * Incomming URL
     *
     * @var string
     */
    private $_sIncommingUrl;

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
     * Flag about occured errors
     *
     * @var bool
     */
    private $_bError = false;

    /**
     * Flag about found routing definition
     *
     * @var bool
     */
    private $_bRoutingFound = false;


    /**
     * Constructor, sets several properties.
     *
     * @param  string  $incommingUrl  Incomming URL
     */
    public function __construct($incommingUrl)
    {
        $this->_sIncommingUrl = $incommingUrl;
        $this->_aParts        = array();
    }


    /**
     * Getter for overwritten client id (see $GLOBALS['client'])
     *
     * @return  int  Client id
     */
    public function getClient()
    {
        return $GLOBALS['client'];
    }


    /**
     * Getter for overwritten change client id (see $GLOBALS['changeclient'])
     *
     * @return  int  Change client id
     */
    public function getChangeClient()
    {
        return $GLOBALS['changeclient'];
    }


    /**
     * Getter for article id (see $GLOBALS['idart'])
     *
     * @return  int  Article id
     */
    public function getIdArt()
    {
        return $GLOBALS['idart'];
    }


    /**
     * Getter for category id (see $GLOBALS['idcat'])
     *
     * @return  int  Category id
     */
    public function getIdCat()
    {
        return $GLOBALS['idcat'];
    }


    /**
     * Getter for language id (see $GLOBALS['lang'])
     *
     * @return  int  Language id
     */
    public function getLang()
    {
        return $GLOBALS['lang'];
    }


    /**
     * Getter for change language id (see $GLOBALS['change_lang'])
     *
     * @return  int  Change language id
     */
    public function getChangeLang()
    {
        return $GLOBALS['change_lang'];
    }


    /**
     * Getter for path (see $GLOBALS['path'])
     *
     * @return  string  Path, used by path resolver
     */
    public function getPath()
    {
        return $this->_sPath;
    }


    /**
     * Getter for resolved url
     *
     * @return  string  Resolved url
     */
    public function getResolvedUrl()
    {
        return $this->_sResolvedUrl;
    }


    /**
     * Returns a flag about found routing definition
     *
     * return  bool  Flag about found routing
     */
    public function getRoutingFoundState()
    {
        return $this->_bRoutingFound;
    }


    /**
     * Getter for occured error state
     *
     * @return  bool  Flag for occured error
     */
    public function errorOccured()
    {
        return $this->_bError;
    }


    /**
     * Main function to call for mod rewrite related preprocessing jobs.
     *
     * Executes some private functions to extract request URI and to set needed membervariables
     * (client, language, article id, category id, etc.)
     */
    public function execute()
    {
        if (parent::isEnabled() == false) {
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
     */
    private function _extractRequestUri($secondCall = false)
    {
        global $client;

        // check for defined rootdir
        if (parent::getConfig('rootdir') !== '/' && strpos($_SERVER['REQUEST_URI'], $this->_sIncommingUrl) === 0) {
            $this->_sIncommingUrl = str_replace(parent::getConfig('rootdir'), '/', $this->_sIncommingUrl);
        }

        $aUrlComponents = $this->_parseUrl($this->_sIncommingUrl);
        if (isset($aUrlComponents['path'])) {
##++##
            if (parent::getConfig('rootdir') !== '/' && strpos($aUrlComponents['path'], parent::getConfig('rootdir')) === 0) {
                $aUrlComponents['path'] = str_replace(parent::getConfig('rootdir'), '/', $aUrlComponents['path']);
            }
##++##

            if ($secondCall == true) {

#        ModRewriteDebugger::add($aUrlComponents, 'ModRewriteController::_extractRequestUri() 2. call $aUrlComponents');
                // @todo: implement real redirect of old front_content.php style urls

                // check for routing definition
                $routings = parent::getConfig('routing');
                if (is_array($routings) && isset($routings[$aUrlComponents['path']])) {
                    $aUrlComponents['path'] = $routings[$aUrlComponents['path']];
                    if (strpos($aUrlComponents['path'], 'front_content.php') !== false) {
                        // routing destination contains front_content.php

                        $this->_bRoutingFound = true;

                        // set client language, if not set before
                        mr_setClientLanguageId($client);

                        //rebuild URL
                        $url = mr_buildNewUrl($aUrlComponents['path']);

                        $aUrlComponents = $this->_parseUrl($url);

                        // add query parameter to superglobal _GET
                        if (isset($aUrlComponents['query'])) {
                           parse_str($aUrlComponents['query'], $vars);
                           $_GET = array_merge($_GET, $vars);
                        }

                        $this->_aParts = array();
                    }
                } else {
                    return;
                }
            }

            $aPaths = explode('/', $aUrlComponents['path']);
            foreach ($aPaths as $p => $item) {
                if (!empty($item)) {
                    // pathinfo would also work
                    $arr   = explode('.', $item);
                    $count = count($arr);
                    if ($count > 0 && '.' . strtolower($arr[$count-1]) == parent::getConfig('file_extension')) {
                        array_pop($arr);
                        $this->_sArtName = implode('.', $arr);
                    } else {
                        $this->_aParts[] = $item;
                    }
                }
            }

            if ($secondCall == true) {
                // reprocess extracting client and language
                $this->_setClientId();
                mr_loadConfiguration($this->_iClientMR);
                $this->_setLanguageId();
            }

        }
        ModRewriteDebugger::add($this->_aParts, 'ModRewriteController::_extractRequestUri() $this->_aParts');

        // loop parts array and remove existing 'front_content.php'
        if ($this->_hasPartArrayItems()) {
            foreach($this->_aParts as $p => $item) {
                if ($item == 'front_content.php') {
                    unset($this->_aParts[$p]);
                }
            }
        }

        // set parts property top null, if needed
        if ($this->_hasPartArrayItems() == false) {
            $this->_aParts = null;
        }

        // set artname to null if needed
        if (!isset($this->_sArtName) || empty($this->_sArtName) || strlen($this->_sArtName) == 0) {
            $this->_sArtName = null;
        }

    }


    /**
     * Tries to initialize the client id
     */
    private function _initializeClientId()
    {
        global $client, $changeclient, $load_client;

        $iClient       = (isset($client) && (int) $client > 0) ? $client : 0;
        $iChangeClient = (isset($changeclient) && (int) $changeclient > 0) ? $changeclient : 0;
        $iLoadClient   = (isset($load_client) && (int) $load_client > 0) ? $load_client : 0;

        $this->_iClientMR = 0;
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
     * Sets client id
     */
    private function _setClientId()
    {
        global $client, $changeclient, $load_client;

        if ($this->_hasPartArrayItems() == false || parent::getConfig('use_client') !== 1) {
            return;
        }

        $iClient       = (isset($client) && (int) $client > 0) ? $client : 0;
        $iLoadClient   = (isset($load_client) && (int) $load_client > 0) ? $load_client : 0;

        if (parent::getConfig('use_client_name') == 1) {
            $changeclient     = ModRewrite::getClientId(array_shift($this->_aParts));
            $this->_iClientMR = $changeclient;
        } else {
            $changeclient     = (int) array_shift($this->_aParts);
            $this->_iClientMR = $changeclient;
        }

        if (empty($changeclient) || (int) $changeclient <= 0) {
            $changeclient = $iLoadClient;
        }
        if ($iClient > 0 && $changeclient !== $iClient) {
            // overwrite existing client variable
            $this->_iClientMR = $changeclient;
            $client = $changeclient;
        }
    }


    /**
     * Sets language id
     */
    private function _setLanguageId()
    {
        global $lang, $changelang;

        if ($this->_hasPartArrayItems() == false || parent::getConfig('use_language') !== 1) {
            return;
        }

        if (parent::getConfig('use_language_name') == 1) {
            // thanks to Nicolas Dickinson for multi Client/Language BugFix
            $changelang = ModRewrite::getLanguageId(array_shift($this->_aParts) , $this->_iClientMR);
        } else {
            $changelang = (int) array_shift($this->_aParts);
        }

        if ((int) $changelang > 0) {
            $lang = $changelang;
            $changelang = $changelang;
        }
    }


    /**
     * Sets path resolver and category id
     */
    private function _setPathresolverSetting()
    {
        global $client, $lang, $load_lang, $idcat;

        if ($this->_hasPartArrayItems() == false) {
            return;
        }

        $this->_sPath = '/' . implode('/', $this->_aParts) . '/';

        if (!isset($lang) || (int) $lang <= 0) {
            if ((int) $load_lang > 0) {
                // load_client is set in frontend/config.php
                $lang = (int) $load_lang;
            } else {
                // get client id from table
                cInclude('classes', 'contenido/class.clientslang.php');
                $clCol = new cApiClientLanguageCollection();
                $clCol->setWhere('idclient', $client);
                $clCol->query();
                if ($clItem = $clCol->next()) {
                    $lang = $clItem->get('idlang');
                }
            }
        }

        $idcat = (int) ModRewrite::getCatIdByUrlPath($this->_sPath);

        if ($idcat == 0) {
            // category couldn't resolved
            $this->_bError = true;
            $idcat = null;
        } else {
            // unset $this->_sPath if $idcat could set, otherwhise it would be resolved again.
            unset($this->_sPath);
        }

        ModRewriteDebugger::add($idcat, 'ModRewriteController->_setPathresolverSetting $idcat');
        ModRewriteDebugger::add($this->_sPath, 'ModRewriteController->_setPathresolverSetting $this->_sPath');
    }


    /**
     * Sets article id
     */
    private function _setIdart()
    {
        global $idcat, $idart, $lang;

        // startarticle name in url
        if (parent::getConfig('add_startart_name_to_url') && isset($this->_sArtName)) {
            if ($this->_sArtName == parent::getConfig('default_startart_name')) {
                // stored articlename is the default one, remove it ModRewrite::getArtIdByWebsafeName()
                // will find the real article name
                $this->_sArtName = null;
            }
        }

        $idcat = (isset($idcat) && (int) $idcat > 0) ? $idcat : null;
        $idart = (isset($idart) && (int) $idart > 0) ? $idart : null;

        if ($idcat !== null && $this->_sArtName && $idart == null) {
            // existing idcat with article name and no idart
            $idart = ModRewrite::getArtIdByWebsafeName($this->_sArtName, $idcat, $lang);
        } elseif ($idcat > 0 && $this->_sArtName == null && $idart == null) {

            if (parent::getConfig('add_startart_name_to_url') && parent::getConfig('default_startart_name') == '') {

                // existing idcat without article name and idart
                cInclude('classes', 'class.article.php');
                $artColl = new ArticleCollection(array('idcat' => $idcat, 'start' => 1));
                if ($artItem = $artColl->startArticle()) {
                    $idart = $artItem->get('idart');
                }

            }

        } elseif ($idcat == null && $idart == null && isset($this->_sArtName)) {
            // no idcat and idart but article name
            $idart = ModRewrite::getArtIdByWebsafeName($this->_sArtName);
        }

        if ($idart !== null && (!$idart || (int) $idart == 0)) {
            if (parent::getConfig('redirect_invalid_article_to_errorsite') == 1) {
                $this->_bError = true;
                $idart = null;
            }
        }

        ModRewriteDebugger::add($idart, 'ModRewriteController->_setIdart $idart');
    }


    /**
     * Does post validation of the extracted data.
     *
     * One main goal of this function is to prevent duplicated content, which could happen, if
     * the configuration 'startfromroot' is activated.
     */
    private function _postValidation()
    {
        global $idcat, $idart, $client;

        if ($this->_bError || $this->_bRoutingFound || !$this->_hasPartArrayItems()) {
            return;
        }

        if (parent::getConfig('startfromroot') == 1 && parent::getConfig('prevent_duplicated_content') == 1) {

            // prevention of duplicated content if '/firstcat/' is directly requested!

            $idcat = (isset($idcat) && (int) $idcat > 0) ? $idcat : null;
            $idart = (isset($idart) && (int) $idart > 0) ? $idart : null;

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
            $url = mr_buildNewUrl('front_content.php?' . $param);

            $aUrlComponents = @parse_url($this->_sIncommingUrl);
            $incommingUrl   = (isset($aUrlComponents['path'])) ? $aUrlComponents['path'] : '';

            ModRewriteDebugger::add($url, 'ModRewriteController->_postValidation validate url');
            ModRewriteDebugger::add($incommingUrl, 'ModRewriteController->_postValidation incommingUrl');

            // now the new generated uri should be identical with the request uri
            if ($incommingUrl !== $url) {
                $this->_bError = true;
                $idcat = null;
            }
        }
    }


    /**
     * Parses the url using defined separators
     *
     * @param   string  $url  Incoming url
     * @return  string  Parsed url
     */
    private function _parseUrl($url)
    {
        $this->_sResolvedUrl = $url;

        $oMrUrlUtil = ModRewriteUrlUtil::getInstance();
        $url = $oMrUrlUtil->toContenidoUrl($url);

        return @parse_url($url);
    }


    /**
     * Returns state of parts property.
     *
     * @return  bool  True if $this->_aParts propery is an array and contains items
     * @access  private
     */
    function _hasPartArrayItems()
    {
        if (is_array($this->_aParts) && count($this->_aParts) > 0) {
            return true;
        } else {
            return false;
        }
    }

}