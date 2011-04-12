<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Includes Mod Rewrite url utility class.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend plugins
 * @version    0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since Contenido release 4.8.15
 *
 * {@internal
 *   created  2008-05-xx
 *
 *   $Id$:
 * }}
 *
 */


defined('CON_FRAMEWORK') or die('Illegal call');


/**
 * Mod Rewrite url utility class. Handles convertion of Urls from contenido core
 * based url composition pattern to AMR (Advanced Mod Rewrite) url composition
 * pattern and vice versa.
 *
 * @author      Murat Purc <murat@purc.de>
 * @package     Contenido Backend plugins
 * @subpackage  ModRewrite
 */
class ModRewriteUrlUtil extends ModRewriteBase
{

    /**
     * Self instance (singleton implementation)
     * @var  ModRewriteUrlUtil
     */
    private static $_instance;

    /**
     * Contenido category word separator
     * @var  string
     */
    private $_catWordSep = '-';

    /**
     * AMR category word separator
     * @var  string
     */
    private $_mrCatWordSep;

    /**
     * Contenido category separator
     * @var  string
     */
    private $_catSep = '/';

    /**
     * AMR category separator
     * @var  string
     */
    private $_mrCatSep;

    /**
     * Contenido article separator
     * @var  string
     */
    private $_artSep = '/';

    /**
     * AMR article separator
     * @var  string
     */
    private $_mrArtSep;

    /**
     * Contenido article word separator
     * @var  string
     */
    private $_artWordSep = '-';

    /**
     * AMR article word separator
     * @var  string
     */
    private $_mrArtWordSep;

    /**
     * AMR extension used for articlenames (e. g. .html)
     * @var  string
     */
    private $_mrExt;


    /**
     * Constructor, sets some AMR configuration related properties
     */
    private function __construct()
    {
        $aCfg = parent::getConfig();
        $this->_mrCatWordSep = $aCfg['category_word_seperator'];
        $this->_mrCatSep     = $aCfg['category_seperator'];
        $this->_mrArtSep     = $aCfg['article_seperator'];
        $this->_mrArtWordSep = $aCfg['article_word_seperator'];
        $this->_mrExt        = $aCfg['file_extension'];
    }

    private function __clone()
    {
    }

    /**
     * Returns self instance (singleton pattern)
     * @return  ModRewriteUrlUtil
     */
    public static function getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new ModRewriteUrlUtil();
        }
        return self::$_instance;
    }


    /**
     * Converts passed AMR url path to Contenido url path.
     *
     * @param   string  $urlPath  AMR url path
     * @return  string  Contenido url path
     */
    public function toContenidoUrlPath($urlPath)
    {
        $newUrlPath = $this->_toUrlPath(
            $urlPath, $this->_mrCatSep, $this->_catSep, $this->_mrCatWordSep, $this->_catWordSep,
            $this->_mrArtSep, $this->_artSep
        );
        return $newUrlPath;
    }

    /**
     * Converts passed Contenido url path to AMR url path.
     *
     * @param   string  $urlPath  Contenido url path
     * @return  string  AMR url path
     */
    public function toModRewriteUrlPath($urlPath)
    {
        $newUrlPath = $this->_toUrlPath(
            $urlPath, $this->_catSep, $this->_mrCatSep, $this->_catWordSep, $this->_mrCatWordSep,
            $this->_artSep, $this->_mrArtSep
        );
        return $newUrlPath;
    }


    /**
     * Converts passed url path to a another url path (Contenido to AMR and vice versa).
     *
     * @param   string  $urlPath         Source url path
     * @param   string  $fromCatSep      Source category seperator
     * @param   string  $toCatSep        Destination category seperator
     * @param   string  $fromCatWordSep  Source category word seperator
     * @param   string  $toCatWordSep    Destination category word seperator
     * @param   string  $fromArtSep      Source article seperator
     * @param   string  $toArtSep        Destination article seperator
     * @return  string  Destination url path
     */
    private function _toUrlPath($urlPath, $fromCatSep, $toCatSep, $fromCatWordSep, $toCatWordSep,
                                $fromArtSep, $toArtSep)
    {
        if ((string) $urlPath == '') {
            return $urlPath;
        }

        if (substr($urlPath, -1) == $fromArtSep) {
            $urlPath = substr($urlPath, 0, -1) . '{TAS}';
        }

        // pre replace category word seperator and category seperator
        $urlPath = str_replace($fromCatWordSep, '{CWS}', $urlPath);
        $urlPath = str_replace($fromCatSep, '{CS}', $urlPath);

        // replace category word seperator
        $urlPath = str_replace('{CWS}', $toCatWordSep, $urlPath);
        $urlPath = str_replace('{CS}', $toCatSep, $urlPath);

        $urlPath = str_replace('{TAS}', $toArtSep, $urlPath);

        return $urlPath;
    }


    /**
     * Converts passed AMR url name to Contenido url name.
     *
     * @param   string  $urlName  AMR url name
     * @return  string  Contenido url name
     */
    public function toContenidoUrlName($urlName)
    {
        $newUrlName = $this->_toUrlName($urlName, $this->_mrArtWordSep, $this->_artWordSep);
        return $newUrlName;
    }


    /**
     * Converts passed Contenido url name to AMR url name.
     *
     * @param   string  $urlName  Contenido url name
     * @return  string  AMR url name
     */
    public function toModRewriteUrlName($urlName)
    {
        $newUrlName = $this->_toUrlName($urlName, $this->_artWordSep, $this->_mrArtWordSep);
        return $newUrlName;
    }


    /**
     * Converts passed url name to a another url name (Contenido to AMR and vice versa).
     *
     * @param   string  $urlName         Source url name
     * @param   string  $fromArtWordSep  Source article word seperator
     * @param   string  $toArtWordSep    Destination article word seperator
     * @return  string  Destination url name
     */
    private function _toUrlName($urlName, $fromArtWordSep, $toArtWordSep)
    {
        if ((string) $urlName == '') {
            return $urlName;
        }

        $urlName = str_replace($this->_mrExt, '{EXT}', $urlName);

        // replace article word seperator
        $urlName = str_replace($fromArtWordSep, $toArtWordSep, $urlName);

        $urlName = str_replace('{EXT}', $this->_mrExt, $urlName);

        return $urlName;
    }


    /**
     * Converts passed AMR url to Contenido url.
     *
     * @param   string  $url  AMR url
     * @return  string  Contenido url
     */
    public function toContenidoUrl($url)
    {
        if (strpos($url, $this->_mrExt) === false) {
            $newUrl = $this->toContenidoUrlPath($url);
        } else {
            // replace category word and article word seperator
            $path   = substr($url, 0, strrpos($url, $this->_mrArtSep) + 1);
            $name   = substr($url, strrpos($url, $this->_mrArtSep) + 1);
            $newUrl = $this->toContenidoUrlPath($path) . $this->toContenidoUrlName($name);
        }
        return $newUrl;
    }


    /**
     * Converts passed AMR url to Contenido url.
     *
     * @param   string  $url  AMR url
     * @return  string  Contenido url
     */
    public function toModRewriteUrl($url)
    {
        if (strpos($url, $this->_mrExt) === false) {
            $newUrl = $this->toModRewriteUrlPath($url);
        } else {
            // replace category word and article word seperator
            $path   = substr($url, 0, strrpos($url, $this->_artSep) + 1);
            $name   = substr($url, strrpos($url, $this->_artSep) + 1);
            $newUrl = $this->toModRewriteUrlPath($path) . $this->toModRewriteUrlName($name);
        }
        return $newUrl;
    }


    /**
     * Converts passed url to a another url (Contenido to AMR and vice versa).
     *
     * @param   string  $urlPath         Source url path
     * @param   string  $fromCatSep      Source category seperator
     * @param   string  $toCatSep        Destination category seperator
     * @param   string  $fromCatWordSep  Source category word seperator
     * @param   string  $toCatWordSep    Destination category word seperator
     * @param   string  $fromArtSep      Source article seperator
     * @param   string  $toArtSep        Destination article seperator
     * @param   string  $fromArtWordSep  Source article word seperator
     * @param   string  $toArtWordSep    Destination article word seperator
     * @return  string  Destination url
     *
     * @deprecated  No more used, is to delete
     */
    private function _toUrl($url, $fromCatSep, $toCatSep, $fromCatWordSep, $toCatWordSep,
                            $fromArtSep, $toArtSep, $fromArtWordSep, $toArtWordSep)
    {
        if ((string) $url == '') {
            return $url;
        }

        $url = str_replace($this->_mrExt, '{EXT}', $url);

        // replace category seperator
        $url = str_replace($fromCatSep, $toCatSep, $url);

        // replace article seperator
        $url = str_replace($fromArtSep, $toArtSep, $url);

        $url = str_replace('{EXT}', $this->_mrExt, $url);

        if (strpos($url, $this->_mrExt) === false) {
            // no articlename, replace category word seperator
            $url = str_replace($fromCatWordSep, $toCatWordSep, $url);
        } else {
            // replace category word and article word seperator
            $path = str_replace($fromCatWordSep, $toCatWordSep, substr($url, 0, strrpos($url, $toArtSep) + 1));
            $file = str_replace($fromArtWordSep, $toArtWordSep, substr($url, strrpos($url, $toArtSep) + 1));
            $url = $path . $file;
        }

        return $url;
    }

}