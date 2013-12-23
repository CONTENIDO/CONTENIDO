<?php
/**
 * This file contains the uri builder custom path class.
 *
 * @package    Core
 * @subpackage Frontend_URI
 * @version    SVN Revision $Rev:$
 *
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.pathresolver.php');

/**
 * Custom path uri builder class.
 * Implementation to build URL in style index-a-1.html
 * with category path (/category/subcategory/index-a-1.html).
 *
 * @package    Core
 * @subpackage Frontend_URI
 */
class cUriBuilderCustomPath extends cUriBuilder {

    /**
     * Self instance
     * @var  cUriBuilderCustomPath
     */
    static private $_instance;

    /**
     * Configuration
     * @var array
     */
    private $aConfig;

    /**
     * Constructor
     */
    private function __construct() {
        $this->sHttpBasePath = '';
    }

    /**
     * Get instance of self
     * @return obj cUriBuilderFrontcontent
     */
    public static function getInstance() {
        if (self::$_instance == NULL) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Builds a URL in index-a-1.html style.
     * Index keys of $aParams will be used as "a", corresponding values as "1" in this sample.
     * For creating the location string $aParams needs to have keys idcat, level, lang and at least one custom key.
     * If level is not set, level 0 will be used as default.
     *
     * @param  array  $aParams  Required keys are: idcat, level, lang and at least one custom key.
     * @param  bool  $bUseAbsolutePath
     * @param  array  $aConfig  If not set, will use UriBuilderConfig::getConfig()
     * @throws cInvalidArgumentException
     * @todo Somehow get around using prCreateURLNameLocationString()
     */
    public function buildUrl(array $aParams, $bUseAbsolutePath = false, array $aConfig = array()) {
        if (!isset($aParams['idcat'])) {
            throw new cInvalidArgumentException('$aParams[idcat] must be set!');
        }
        if (!isset($aParams['level'])) {
            //throw new cInvalidArgumentException('$aParams[level] must be set! Setting it to 0 will create complete path.');
            $aParams['level'] = '1';
        }
        if (!isset($aParams['lang'])) {
            throw new cInvalidArgumentException('$aParams[lang] must be set!');
        }
        if (sizeof($aParams) <= 3) {
            throw new cInvalidArgumentException('$aParams must have at least one custom entry!');
        }
        // if no config passed or not all parameters available, use default config
        if (sizeof($aConfig) == 0 || !isset($aConfig['prefix']) || !isset($aConfig['suffix']) || !isset($aConfig['separator'])) {
            include_once('class.uribuilder.config.php');
            $aConfig = cUriBuilderConfig::getConfig();
        }
        $this->aConfig = $aConfig;

        $sCategoryString = '';
        prCreateURLNameLocationString(intval($aParams['idcat']), "/", $sCategoryString, false, "", $aParams['level'], $aParams['lang'], true, false);
        if (strlen($sCategoryString) > 0 && substr($sCategoryString, -1) != '/') {
            $sCategoryString .= '/';
        }
        $this->sUrl = $bUseAbsolutePath === true ? $this->sHttpBasePath : '';
        $this->sUrl .= $sCategoryString;
        $this->sUrl .= $this->aConfig['prefix'];
        foreach ($aParams as $sKey => $mVal) {
            if ($sKey != 'idcat' && $sKey != 'lang' && $sKey != 'level') {
                $sVal = $mVal; // assuming mVal is a string and thus a single value
                if (is_array($mVal)) { // mVal has more than one value, e.g. index-b-1-2.html
                    $sVal = implode($this->aConfig['separator'], $mVal);
                }
                $this->sUrl .= $this->aConfig['separator'] . strval($sKey) . $this->aConfig['separator'] . strval($sVal);
            }
        }
        $this->sUrl .= $this->aConfig['suffix'];
    }

}
