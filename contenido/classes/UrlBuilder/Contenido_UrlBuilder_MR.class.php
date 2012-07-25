<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Implementation of Contenido_UrlBuilder to build AMR (Advanced Mod Rewrite) Frontend-URLs
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Plugins
 * @subpackage ModRewrite
 * @version    0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9.0
 *
 * {@internal
 *   created  2008-05-xx
 *
 *   $Id$:
 * }}
 *
 */


defined('CON_FRAMEWORK') or die('Illegal call');


cInclude('classes', 'UrlBuilder/Contenido_UrlBuilder.class.php');
cInclude('classes', 'UrlBuilder/Contenido_UrlBuilderFactory.class.php');


/**
 * Class to build frontend urls for advandced mod rewrite plugin.
 * Extends abstract Contenido_UrlBuilder class and implements singleton pattern.
 *
 * Usage:
 * <pre>
 * cInclude('classes', 'UrlBuilder/Contenido_UrlBuilder_MR.class.php');
 * $url = 'front_content.php?idart=123';
 * $mrUrlBuilder = Contenido_UrlBuilder_MR::getInstance();
 * $mrUrlBuilder->buildUrl(array($url));
 * $newUrl = $mrUrlBuilder->getUrl();
 * </pre>
 *
 * @todo  Add handling of absolute paths, standardize handling of fragments
 *
 *
 * @author      Murat Purc <murat@purc.de>
 * @package     CONTENIDO Plugins
 * @subpackage  ModRewrite
 */
class Contenido_UrlBuilder_MR extends Contenido_UrlBuilder
{

    /**
     * Self instance
     *
     * @var  Contenido_UrlBuilder_MR
     */
    static private $_instance;

    /**
     * Ampersant used for composing several parameter value pairs
     *
     * @var  string
     */
    private $_sAmp = '&amp;';

    /**
     * Is XHTML output?
     *
     * @var  bool
     */
    private $_bIsXHTML = false;

    /**
     * Is mod rewrite enabled?
     *
     * @var  bool
     */
    private $_bMREnabled = false;

    /**
     * Mod Rewrite configuration
     *
     * @var  array
     */
    private $_aMrCfg = null;


    /**
     * Constructor, tries to set some member variables.
     */
    private function __construct()
    {
        $this->sHttpBasePath = '';
        if (ModRewrite::isEnabled()) {
            $this->_aMrCfg     = ModRewrite::getConfig();
            $this->_bMREnabled = true;
            $this->_bIsXHTML   = (getEffectiveSetting('generator', 'xhtml', 'false') == 'false') ? false : true;
            $this->_sAmp       = ($this->_bIsXHTML) ? '&amp;' : '&';
        }
    }


    /**
     * Returns a instance of Contenido_UrlBuilder_MR
     *
     * @return  Contenido_UrlBuilder_MR
     */
    public static function getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    /**
     * Builds a URL based on defined mod rewrite settings.
     *
     * @param   array    $params  Parameter array, provides only following parameters:
     * <code>
     * $params[0] = 'front_content.php?idart=123...'
     * </code>
     * @param   boolean  $bUseAbsolutePath  Flag to use absolute path (not used at the moment)
     * @return  string   New build url
     */
    public function buildUrl(array $params, $bUseAbsolutePath = false)
    {
        global $cfgClient, $client;

        ModRewriteDebugger::add($params, 'Contenido_UrlBuilder_MR::buildUrl() $params');
        $urlDebug = array();
        $urlDebug['in'] = $params;

        $url = self::_buildUrl($params);

        $urlPrefix = '';
        if ($bUseAbsolutePath) {
            $hmlPath = $cfgClient[$client]['path']['htmlpath'];
            $aComp   = parse_url($hmlPath);
            $urlPrefix = $aComp['scheme'] . '://' . $aComp['host'];
            if (mr_arrayValue($aComp, 'port', '') !== '') {
                $urlPrefix .= ':' . $aComp['port'];
            }
        }

        $this->sUrl = $urlPrefix . $url;

        $urlDebug['out'] = $this->sUrl;
        ModRewriteDebugger::add($urlDebug, 'Contenido_UrlBuilder_MR::buildUrl() in -> out');
    }


    /**
     * Builds the SEO-URL by analyzing passed arguments (parameter value pairs)
     *
     * @param   array   $aParams  Parameter array
     * @return  string  New build pretty url
     */
    private function _buildUrl(array $aParams)
    {
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
        $aParts = array();

        // add client id/name if desired
        if ($param = $this->_getClientParameter($aParams)) {
            $aParts[] = $param;
        }

        // add language id/name if desired
        if ($param = $this->_getLanguageParameter($aParams)) {
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
        if ($this->_aMrCfg['use_lowercase_uri'] == 1) {
            // use lowercase url
            $sPathAndArticle = strtolower($sPathAndArticle);
        }

//        $sUrl = $this->_aMrCfg['rootdir'] . $sPathAndArticle . $sQuery;
        $sUrl = $sPathAndArticle . $sQuery;

        // remove double or more join parameter
        $sUrl = mr_removeMultipleChars('/', $sUrl);
        if (substr($sUrl, -2) == '?=') {
            $sUrl = substr_replace($sUrl, '', -2);
        }

        // now convert CONTENIDO url to an AMR url
        $sUrl = ModRewriteUrlUtil::getInstance()->toModRewriteUrl($sUrl);

        return mr_removeMultipleChars('/', $this->_aMrCfg['rootdir'] . $sUrl);
    }


    /**
     * Loops thru passed parameter array and creates the query part of the URL.
     * All non CONTENIDO related parameter will be excluded from composition.
     *
     * @param   array  $aArgs  Assoziative parameter array
     * @return  string  Composed query part for the URL like '?foo=bar&amp;param=value'
     */
    private function _createUrlQueryPart(array $aArgs)
    {
        // set list of parameter which are to ignore while setting additional parameter
        $aIgnoredParams = array(
            'idcat', 'idart', 'lang', 'client', 'idcatart', 'idartlang'
        );
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
#                $sQuery .= urlencode(urldecode($p)) . '=' . urlencode(urldecode($v)) . $this->_sAmp;
                $p = urlencode(urldecode($p));
                if (is_array($v)) {
                    // handle query parameter like foobar[0}=a&foobar[1]=b...
                    foreach ($v as $p2 => $v2){
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
        if (strlen($sQuery) > 0) {
            $sQuery = '?' . substr($sQuery, 0, - strlen($this->_sAmp));
        }
        return $sQuery;
    }


    /**
     * Returns client id or name depending on settings.
     *
     * @param   array   $aArgs     Additional arguments
     * @return  mixed   Client id, client name or null
     */
    private function _getClientParameter(array $aArgs)
    {
        global $client;

        // set client if desired
        if ($this->_aMrCfg['use_client'] == 1) {
            $iChangeClient = (isset($aArgs['changeclient'])) ? (int) $aArgs['changeclient'] : 0;
            $idclient      = ($iChangeClient > 0) ? $iChangeClient : $client;
            if ($this->_aMrCfg['use_client_name'] == 1) {
                return urlencode(ModRewrite::getClientName($idclient));
            } else {
                return $idclient;
            }
        }
        return null;
    }


    /**
     * Returns language id or name depending on settings.
     *
     * @param   array   $aArgs     Additional arguments
     * @return  mixed   Language id, language name or null
     */
    private function _getLanguageParameter(array $aArgs)
    {
        global $lang;

        // set language if desired
        if ($this->_aMrCfg['use_language'] == 1) {
            $iChangeLang = (isset($aArgs['changelang'])) ? (int) $aArgs['changelang'] : 0;
            $idlang      = ($iChangeLang > 0) ? $iChangeLang : $lang;
            if ($this->_aMrCfg['use_language_name'] == 1) {
                return urlencode(ModRewrite::getLanguageName($idlang));
            } else {
                return $idlang;
            }
        }
        return null;
    }


    /**
     * Returns composed path of url (normally the category structure)
     *
     * @param   array   $aPretty   Pretty url array
     * @return  string  Path
     */
    private function _getPath(array $aPretty)
    {
        $sPath = (isset($aPretty['urlpath'])) ? $aPretty['urlpath'] : '';

        // check start directory settings
        if ($this->_aMrCfg['startfromroot'] == 0 && (strlen($sPath) > 0)) {
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
     * Returns articlename depending on current setting
     *
     * @param   array   $aPretty   Pretty url array
     * @param   array   $aArgs     Additional arguments
     * @return  string  Articlename
     */
    private function _getArticleName(array $aPretty, array $aArgs)
    {
        $sArticle   = (isset($aPretty['urlname'])) ? $aPretty['urlname'] : '';

        $iIdCat     = (isset($aArgs['idcat'])) ? (int) $aArgs['idcat'] : 0;
        $iIdCatLang = (isset($aArgs['idcatlang'])) ? (int) $aArgs['idcatlang'] : 0;
        $iIdCatArt  = (isset($aArgs['idcatart'])) ? (int) $aArgs['idcatart'] : 0;
        $iIdArt     = (isset($aArgs['idart'])) ? (int) $aArgs['idart'] : 0;
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
