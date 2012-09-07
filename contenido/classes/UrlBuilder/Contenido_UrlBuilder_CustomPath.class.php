<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Implementation of IContenido_Frontend_Navigation_UrlBuilder to build URL in style index-a-1.html 
 * with category path (/category/subcategory/index-a-1.html).
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.0
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2008-02-19
 *   modified 2008-02-28 Changed to using Config for URL style
 *   @todo Somehow get around using prCreateURLNameLocationString()
 *   
 *   $Id: Contenido_UrlBuilder_CustomPath.class.php 738 2008-08-27 10:21:19Z timo.trautmann $: 
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


include_once('Contenido_UrlBuilder.class.php');
cInclude('includes', 'functions.pathresolver.php');

class Contenido_UrlBuilder_CustomPath extends Contenido_UrlBuilder {
    static private $_instance; // object instance
    private $aConfig;
    
    /**
     * Constructor
     * @access private
     * @return void
     * @author Rudi Bieller
     */
    private function __construct() {
        $this->sHttpBasePath = '';
    }
    
    /**
     * Get instance of Contenido_UrlBuilder_Frontcontent
     * @access public
     * @return obj Contenido_UrlBuilder_Frontcontent
     * @author Rudi Bieller
     */
    public static function getInstance() {
        if (self::$_instance == null) {
            self::$_instance = new Contenido_UrlBuilder_CustomPath();
        }
        return self::$_instance;
    }
    
    /**
     * Builds a URL in index-a-1.html style.
     * Index keys of $aParams will be used as "a", corresponding values as "1" in this sample.
     * For creating the location string $aParams needs to have keys idcat, level, lang and at least one custom key.
     * If level is not set, level 0 will be used as default.
     *
     * @param array $aParams Required keys are: idcat, level, lang and at least one custom key.
     * @param boolean $bUseAbsolutePath
     * @param array $aConfig If not set, will use UrlBuilderConfig::getConfig()
     * @return void
     * @throws InvalidArgumentException
     * @throws Exception
     * @author Rudi Bieller
     * @todo Somehow get around using prCreateURLNameLocationString()
     */
    public function buildUrl(array $aParams, $bUseAbsolutePath = false, array $aConfig = array()) {
        if (!isset($aParams['idcat'])) {
            throw new InvalidArgumentException('$aParams[idcat] must be set!');
        }
        if (!isset($aParams['level'])) {
            //throw new InvalidArgumentException('$aParams[level] must be set! Setting it to 0 will create complete path.');
            $aParams['level'] = '1';
        }
        if (!isset($aParams['lang'])) {
            throw new InvalidArgumentException('$aParams[lang] must be set!');
        }
        if (sizeof($aParams) <= 3) {
            throw new InvalidArgumentException('$aParams must have at least one custom entry!');
        }
        // if no config passed or not all parameters available, use default config
        if (sizeof($aConfig) == 0 || !isset($aConfig['prefix']) || !isset($aConfig['suffix']) || !isset($aConfig['separator'])) {
            include_once('Contenido_UrlBuilderConfig.class.php');
            $aConfig = Contenido_UrlBuilderConfig::getConfig();
        }
        $this->aConfig = $aConfig;
        
        $sCategoryString = '';
		prCreateURLNameLocationString(intval($aParams['idcat']), 
		                                "/", 
		                                $sCategoryString, 
		                                false, 
		                                "", 
		                                $aParams['level'], 
		                                $aParams['lang'], 
		                                true, 
		                                false);
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
?>