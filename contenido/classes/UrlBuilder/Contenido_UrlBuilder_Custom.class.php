<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Implementation of IContenido_Frontend_Navigation_UrlBuilder to build URL in style index-a-1.html without category path.
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
 *   created 2008-02-20
 *   modified 2008-02-28 Changed to using Config for URL style
 * 
 *   $Id: Contenido_UrlBuilder_Custom.class.php 738 2008-08-27 10:21:19Z timo.trautmann $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


include_once('Contenido_UrlBuilder.class.php');

class Contenido_UrlBuilder_Custom extends Contenido_UrlBuilder {
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
            self::$_instance = new Contenido_UrlBuilder_Custom();
        }
        return self::$_instance;
    }
    
    /**
     * Builds a URL in index-a-1.html style.
     * Index keys of $aParams will be used as "a", corresponding values as "1" in this sample.
     *
     * @param array $aParams
     * @param boolean $bUseAbsolutePath
     * @param array $aConfig If not set, will use UrlBuilderConfig::getConfig()
     * @return void
     * @throws InvalidArgumentException
     * @author Rudi Bieller
     */
    public function buildUrl(array $aParams, $bUseAbsolutePath = false, array $aConfig = array()) {
        if (sizeof($aParams) == 0) {
            throw new InvalidArgumentException('$aParams must have at least one entry!');
        }
        // if no config passed or not all parameters available, use default config
        if (sizeof($aConfig) == 0 || !isset($aConfig['prefix']) || !isset($aConfig['suffix']) || !isset($aConfig['separator'])) {
            include_once('Contenido_UrlBuilderConfig.class.php');
            $aConfig = Contenido_UrlBuilderConfig::getConfig();
        }
        $this->aConfig = $aConfig;
        
        $this->sUrl = $bUseAbsolutePath === true ? $this->sHttpBasePath : '';
        $this->sUrl .= $this->aConfig['prefix'];
        foreach ($aParams as $sKey => $mVal) {
			$sVal = $mVal; // assuming mVal is a string and thus a single value
			if (is_array($mVal)) { // mVal has more than one value, e.g. index-b-1-2.html
			    $sVal = implode($this->aConfig['separator'], $mVal);
			}
            $this->sUrl .= $this->aConfig['separator'] . strval($sKey) . $this->aConfig['separator'] . strval($sVal);
        }
        $this->sUrl .= $this->aConfig['suffix'];
    }
}
?>