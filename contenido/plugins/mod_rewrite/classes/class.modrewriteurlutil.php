<?php
/**
 * AMR url utility class
 *
 * @package     Plugin
 * @subpackage  ModRewrite
 * @id          $Id$:
 * @author      Murat Purc <murat@purc.de>
 * @copyright   four for business AG <www.4fb.de>
 * @license     https://www.contenido.org/license/LIZENZ.txt
 * @link        https://www.4fb.de
 * @link        https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Mod Rewrite url utility class. Handles conversion of Urls from CONTENIDO core
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
     * AMR extension used for article-names (e.g. .html)
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
        return $this->_toUrlPath(
                $urlPath, $this->_mrCatSep, $this->_catSep, $this->_mrCatWordSep, $this->_catWordSep, $this->_mrArtSep, $this->_artSep
        );
    }

    /**
     * Converts passed CONTENIDO url path to AMR url path.
     *
     * @param   string  $urlPath  CONTENIDO url path
     * @return  string  AMR url path
     */
    public function toModRewriteUrlPath($urlPath) {
        return $this->_toUrlPath(
                $urlPath, $this->_catSep, $this->_mrCatSep, $this->_catWordSep, $this->_mrCatWordSep, $this->_artSep, $this->_mrArtSep
        );
    }

    /**
     * Converts passed url path to a another url path (CONTENIDO to AMR and vice versa).
     *
     * @param   string  $urlPath         Source url path
     * @param   string  $fromCatSep      Source category separator
     * @param   string  $toCatSep        Destination category separator
     * @param   string  $fromCatWordSep  Source category word separator
     * @param   string  $toCatWordSep    Destination category word separator
     * @param   string  $fromArtSep      Source article separator
     * @param   string  $toArtSep        Destination article separator
     * @return  string  Destination url path
     */
    private function _toUrlPath($urlPath, $fromCatSep, $toCatSep, $fromCatWordSep, $toCatWordSep, $fromArtSep, $toArtSep) {
        if ((string) $urlPath == '') {
            return $urlPath;
        }

        if (cString::getPartOfString($urlPath, -1) == $fromArtSep) {
            $urlPath = cString::getPartOfString($urlPath, 0, -1) . '{TAS}';
        }

        // pre replace category word separator and category separator
        $urlPath = str_replace($fromCatWordSep, '{CWS}', $urlPath);
        $urlPath = str_replace($fromCatSep, '{CS}', $urlPath);

        // replace category word separator
        $urlPath = str_replace('{CWS}', $toCatWordSep, $urlPath);
        $urlPath = str_replace('{CS}', $toCatSep, $urlPath);

        return str_replace('{TAS}', $toArtSep, $urlPath);
    }

    /**
     * Converts passed AMR url name to CONTENIDO url name.
     *
     * @param   string  $urlName  AMR url name
     * @return  string  CONTENIDO url name
     */
    public function toContenidoUrlName($urlName) {
        return $this->_toUrlName($urlName, $this->_mrArtWordSep, $this->_artWordSep);
    }

    /**
     * Converts passed CONTENIDO url name to AMR url name.
     *
     * @param   string  $urlName  CONTENIDO url name
     * @return  string  AMR url name
     */
    public function toModRewriteUrlName($urlName) {
        return $this->_toUrlName($urlName, $this->_artWordSep, $this->_mrArtWordSep);
    }

    /**
     * Converts passed url name to a another url name (CONTENIDO to AMR and vice versa).
     *
     * @param   string  $urlName         Source url name
     * @param   string  $fromArtWordSep  Source article word separator
     * @param   string  $toArtWordSep    Destination article word separator
     * @return  string  Destination url name
     */
    private function _toUrlName($urlName, $fromArtWordSep, $toArtWordSep) {
        if ((string) $urlName == '') {
            return $urlName;
        }

        $urlName = str_replace($this->_mrExt, '{EXT}', $urlName);

        // replace article word separator
        $urlName = str_replace($fromArtWordSep, $toArtWordSep, $urlName);

        return str_replace('{EXT}', $this->_mrExt, $urlName);
    }

    /**
     * Converts passed AMR url to CONTENIDO url.
     *
     * @param   string  $url  AMR url
     * @return  string  CONTENIDO url
     */
    public function toContenidoUrl($url) {
        if (cString::findFirstPos($url, $this->_mrExt) === false) {
            $newUrl = $this->toContenidoUrlPath($url);
        } else {
            // replace category word and article word separator
            $path = cString::getPartOfString($url, 0, cString::findLastPos($url, $this->_mrArtSep) + 1);
            $name = cString::getPartOfString($url, cString::findLastPos($url, $this->_mrArtSep) + 1);
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
        if (cString::findFirstPos($url, $this->_mrExt) === false) {
            $newUrl = $this->toModRewriteUrlPath($url);
        } else {
            // replace category word and article word separator
            $path = cString::getPartOfString($url, 0, cString::findLastPos($url, $this->_artSep) + 1);
            $name = cString::getPartOfString($url, cString::findLastPos($url, $this->_artSep) + 1);
            $newUrl = $this->toModRewriteUrlPath($path) . $this->toModRewriteUrlName($name);
        }
        return $newUrl;
    }
}