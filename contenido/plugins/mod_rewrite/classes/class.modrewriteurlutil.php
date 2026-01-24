<?php

/**
 * AMR url utility class
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
 * Mod Rewrite url utility class. Handles conversion of Urls from CONTENIDO core based url composition
 * pattern to AMR (Advanced Mod Rewrite) url composition pattern and vice versa.
 *
 * @author     Murat Purc <murat@purc.de>
 * @package    Plugin
 * @subpackage ModRewrite
 */
class ModRewriteUrlUtil extends ModRewriteBase
{

    /**
     * @var ModRewriteUrlUtil Self instance (singleton implementation)
     */
    private static $instance;

    /**
     * @var string CONTENIDO category word separator
     */
    private $catWordSep = '-';

    /**
     * @var string AMR category word separator
     */
    private $mrCatWordSep;

    /**
     * @var string CONTENIDO category separator
     */
    private $catSeparator = '/';

    /**
     * @var string AMR category separator
     */
    private $mrCatSep;

    /**
     * @var string CONTENIDO article separator
     */
    private $artSeparator = '/';

    /**
     * @var string AMR article separator
     */
    private $mrArtSep;

    /**
     * @var string CONTENIDO article word separator
     */
    private $artWordSep = '-';

    /**
     * @var string AMR article word separator
     */
    private $mrArtWordSep;

    /**
     * @var string AMR extension used for article-names (e.g. '.html')
     */
    private $mrExt;

    /**
     * Constructor, sets some AMR configuration related properties
     */
    private function __construct()
    {
        $config = parent::getConfig();
        $this->mrCatWordSep = cSecurity::toString($config['category_word_seperator']);
        $this->mrCatSep = cSecurity::toString($config['category_seperator']);
        $this->mrArtSep = cSecurity::toString($config['article_seperator']);
        $this->mrArtWordSep = cSecurity::toString($config['article_word_seperator']);
        $this->mrExt = cSecurity::toString($config['file_extension']);
    }

    /**
     * Prevent cloning
     */
    private function __clone()
    {
    }

    /**
     * Returns self instance (singleton pattern)
     */
    public static function getInstance(): self
    {
        if (self::$instance == NULL) {
            self::$instance = new ModRewriteUrlUtil();
        }
        return self::$instance;
    }

    /**
     * Converts passed AMR url path to CONTENIDO url path.
     *
     * @param string $urlPath AMR url path
     * @return string CONTENIDO url path
     */
    public function toContenidoUrlPath(string $urlPath): string
    {
        return $this->_toUrlPath(
            $urlPath, $this->mrCatSep,
            $this->catSeparator,
            $this->mrCatWordSep,
            $this->catWordSep,
            $this->mrArtSep,
            $this->artSeparator
        );
    }

    /**
     * Converts passed CONTENIDO url path to AMR url path.
     *
     * @param string $urlPath CONTENIDO url path
     * @return string AMR url path
     */
    public function toModRewriteUrlPath(string $urlPath): string
    {
        return $this->_toUrlPath(
            $urlPath,
            $this->catSeparator,
            $this->mrCatSep,
            $this->catWordSep,
            $this->mrCatWordSep,
            $this->artSeparator,
            $this->mrArtSep
        );
    }

    /**
     * Converts passed url path to another url path (CONTENIDO to AMR and vice versa).
     *
     * @param string $urlPath Source url path
     * @param string $fromCatSep Source category separator
     * @param string $toCatSep Destination category separator
     * @param string $fromCatWordSep Source category word separator
     * @param string $toCatWordSep Destination category word separator
     * @param string $fromArtSep Source article separator
     * @param string $toArtSep Destination article separator
     * @return string Destination url path
     */
    private function _toUrlPath(
        string $urlPath,
        string $fromCatSep,
        string $toCatSep,
        string $fromCatWordSep,
        string $toCatWordSep,
        string $fromArtSep,
        string $toArtSep
    ): string {
        if ($urlPath == '') {
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
     * @param string $urlName AMR url name
     * @return string CONTENIDO url name
     */
    public function toContenidoUrlName(string $urlName): string
    {
        return $this->_toUrlName($urlName, $this->mrArtWordSep, $this->artWordSep);
    }

    /**
     * Converts passed CONTENIDO url name to AMR url name.
     *
     * @param string $urlName CONTENIDO url name
     * @return string AMR url name
     */
    public function toModRewriteUrlName(string $urlName): string
    {
        return $this->_toUrlName($urlName, $this->artWordSep, $this->mrArtWordSep);
    }

    /**
     * Converts passed url name to another url name (CONTENIDO to AMR and vice versa).
     *
     * @param string $urlName Source url name
     * @param string $fromArtWordSep Source article word separator
     * @param string $toArtWordSep Destination article word separator
     * @return string Destination url name
     */
    private function _toUrlName(string $urlName, string $fromArtWordSep, string $toArtWordSep): string
    {
        if ($urlName == '') {
            return $urlName;
        }

        $urlName = str_replace($this->mrExt, '{EXT}', $urlName);

        // replace article word separator
        $urlName = str_replace($fromArtWordSep, $toArtWordSep, $urlName);

        return str_replace('{EXT}', $this->mrExt, $urlName);
    }

    /**
     * Converts passed AMR url to CONTENIDO url.
     *
     * @param string $url AMR url
     * @return string CONTENIDO url
     */
    public function toContenidoUrl(string $url): string
    {
        if (cString::findFirstPos($url, $this->mrExt) === false) {
            $newUrl = $this->toContenidoUrlPath($url);
        } else {
            // replace category word and article word separator
            $path = cString::getPartOfString($url, 0, cString::findLastPos($url, $this->mrArtSep) + 1);
            $name = cString::getPartOfString($url, cString::findLastPos($url, $this->mrArtSep) + 1);
            $newUrl = $this->toContenidoUrlPath($path) . $this->toContenidoUrlName($name);
        }
        return $newUrl;
    }

    /**
     * Converts passed AMR url to CONTENIDO url.
     *
     * @param string $url AMR url
     * @return string CONTENIDO url
     */
    public function toModRewriteUrl(string $url): string
    {
        if (cString::findFirstPos($url, $this->mrExt) === false) {
            $newUrl = $this->toModRewriteUrlPath($url);
        } else {
            // replace category word and article word separator
            $path = cString::getPartOfString($url, 0, cString::findLastPos($url, $this->artSeparator) + 1);
            $name = cString::getPartOfString($url, cString::findLastPos($url, $this->artSeparator) + 1);
            $newUrl = $this->toModRewriteUrlPath($path) . $this->toModRewriteUrlName($name);
        }
        return $newUrl;
    }
}
