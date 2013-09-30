<?php
/**
 * AMR url utility class
 *
 * @package     Plugin
 * @subpackage  ModRewrite
 * @version     SVN Revision $Rev:$
 * @id          $Id$:
 * @author      Murat Purc <murat@purc.de>
 * @copyright   four for business AG <www.4fb.de>
 * @license     http://www.contenido.org/license/LIZENZ.txt
 * @link        http://www.4fb.de
 * @link        http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Mod Rewrite url utility class. Handles convertion of Urls from CONTENIDO core
 * based url composition pattern to AMR (Advanced Mod Rewrite) url composition
 * pattern and vice versa.
 *
 * @author      Murat Purc <murat@purc.de>
 * @package     Plugin
 * @subpackage  ModRewrite
 */
class ModRewriteUrlUtil extends ModRewriteBase {

    /**
     * Self instance (singleton implementation)
     * @var  ModRewriteUrlUtil
     */
    private static $_instance;

    /**
     * CONTENIDO category word separator
     * @var  string
     */
    private $_catWordSep = '-';

    /**
     * AMR category word separator
     * @var  string
     */
    private $_mrCatWordSep;

    /**
     * CONTENIDO category separator
     * @var  string
     */
    private $_catSep = '/';

    /**
     * AMR category separator
     * @var  string
     */
    private $_mrCatSep;

    /**
     * CONTENIDO article separator
     * @var  string
     */
    private $_artSep = '/';

    /**
     * AMR article separator
     * @var  string
     */
    private $_mrArtSep;

    /**
     * CONTENIDO article word separator
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
    private function __construct() {
        $aCfg = parent::getConfig();
        $this->_mrCatWordSep = $aCfg['category_word_seperator'];
        $this->_mrCatSep = $aCfg['category_seperator'];
        $this->_mrArtSep = $aCfg['article_seperator'];
        $this->_mrArtWordSep = $aCfg['article_word_seperator'];
        $this->_mrExt = $aCfg['file_extension'];
    }

    /**
     * Prevent cloning
     */
    private function __clone() {

    }

    /**
     * Returns self instance (singleton pattern)
     * @return  ModRewriteUrlUtil
     */
    public static function getInstance() {
        if (self::$_instance == NULL) {
            self::$_instance = new ModRewriteUrlUtil();
        }
        return self::$_instance;
    }

    /**
     * Converts passed AMR url path to CONTENIDO url path.
     *
     * @param   string  $urlPath  AMR url path
     * @return  string  CONTENIDO url path
     */
    public function toContenidoUrlPath($urlPath) {
        $newUrlPath = $this->_toUrlPath(
                $urlPath, $this->_mrCatSep, $this->_catSep, $this->_mrCatWordSep, $this->_catWordSep, $this->_mrArtSep, $this->_artSep
        );
        return $newUrlPath;
    }

    /**
     * Converts passed CONTENIDO url path to AMR url path.
     *
     * @param   string  $urlPath  CONTENIDO url path
     * @return  string  AMR url path
     */
    public function toModRewriteUrlPath($urlPath) {
        $newUrlPath = $this->_toUrlPath(
                $urlPath, $this->_catSep, $this->_mrCatSep, $this->_catWordSep, $this->_mrCatWordSep, $this->_artSep, $this->_mrArtSep
        );
        return $newUrlPath;
    }

    /**
     * Converts passed url path to a another url path (CONTENIDO to AMR and vice versa).
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
    private function _toUrlPath($urlPath, $fromCatSep, $toCatSep, $fromCatWordSep, $toCatWordSep, $fromArtSep, $toArtSep) {
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
     * Converts passed AMR url name to CONTENIDO url name.
     *
     * @param   string  $urlName  AMR url name
     * @return  string  CONTENIDO url name
     */
    public function toContenidoUrlName($urlName) {
        $newUrlName = $this->_toUrlName($urlName, $this->_mrArtWordSep, $this->_artWordSep);
        return $newUrlName;
    }

    /**
     * Converts passed CONTENIDO url name to AMR url name.
     *
     * @param   string  $urlName  CONTENIDO url name
     * @return  string  AMR url name
     */
    public function toModRewriteUrlName($urlName) {
        $newUrlName = $this->_toUrlName($urlName, $this->_artWordSep, $this->_mrArtWordSep);
        return $newUrlName;
    }

    /**
     * Converts passed url name to a another url name (CONTENIDO to AMR and vice versa).
     *
     * @param   string  $urlName         Source url name
     * @param   string  $fromArtWordSep  Source article word seperator
     * @param   string  $toArtWordSep    Destination article word seperator
     * @return  string  Destination url name
     */
    private function _toUrlName($urlName, $fromArtWordSep, $toArtWordSep) {
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
     * Converts passed AMR url to CONTENIDO url.
     *
     * @param   string  $url  AMR url
     * @return  string  CONTENIDO url
     */
    public function toContenidoUrl($url) {
        if (strpos($url, $this->_mrExt) === false) {
            $newUrl = $this->toContenidoUrlPath($url);
        } else {
            // replace category word and article word seperator
            $path = substr($url, 0, strrpos($url, $this->_mrArtSep) + 1);
            $name = substr($url, strrpos($url, $this->_mrArtSep) + 1);
            $newUrl = $this->toContenidoUrlPath($path) . $this->toContenidoUrlName($name);
        }
        return $newUrl;
    }

    /**
     * Converts passed AMR url to CONTENIDO url.
     *
     * @param   string  $url  AMR url
     * @return  string  CONTENIDO url
     */
    public function toModRewriteUrl($url) {
        if (strpos($url, $this->_mrExt) === false) {
            $newUrl = $this->toModRewriteUrlPath($url);
        } else {
            // replace category word and article word seperator
            $path = substr($url, 0, strrpos($url, $this->_artSep) + 1);
            $name = substr($url, strrpos($url, $this->_artSep) + 1);
            $newUrl = $this->toModRewriteUrlPath($path) . $this->toModRewriteUrlName($name);
        }
        return $newUrl;
    }
}