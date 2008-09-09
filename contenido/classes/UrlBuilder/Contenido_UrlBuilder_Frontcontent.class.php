<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Implementation of IContenido_Frontend_Navigation_UrlBuilder to build front_content.php URL
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend classes
 * @version    1.0.1
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2008-02-19
 *   modified 2008-09-09 Fix of parameter checking in method buildUrl()
 *   @todo: add switch for & vs. &amp;
 * 
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

include_once('Contenido_UrlBuilder.class.php');

class Contenido_UrlBuilder_Frontcontent extends Contenido_UrlBuilder {
    static private $_instance; // object instance
    private $_sAmp = '&amp;';
    
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
            self::$_instance = new Contenido_UrlBuilder_Frontcontent();
        }
        return self::$_instance;
    }
    
    /**
     * Builds a URL in front_content.php style.
     * Depending on which array keys of $aParams are set, the URL is built differently.
     * Valid array keys are: idcat, idart and idcatart.
     * Internally, the method first tries to create URLs in this order:
     * front_content.php?idcat=1&idart=1
     * front_content.php?idcat=1
     * front_content.php?idart=1
     * front_content.php?idcatart=1
     *
     * @param array $aParams
     * @param boolean $bUseAbsolutePath
     * @return void
     * @throws InvalidArgumentException
     * @throws Exception
     * @author Rudi Bieller
     */
    public function buildUrl(array $aParams, $bUseAbsolutePath = false) {
        $bIdcatSet = isset($aParams['idcat']);
        $bIdartSet = isset($aParams['idart']);
        $bIdcatArtSet = isset($aParams['idcatart']);
        if ($bIdcatSet === false || $bIdartSet === false || $bIdcatArtSet === false) {
            throw new InvalidArgumentException('$aParams must have at least one of the following values set: $aParams[idcat], $aParams[idart] or $aParams[idcatart]!');
        }
        $sHttpBasePath = $bUseAbsolutePath === true ? $this->sHttpBasePath : '';
        if ($bIdcatSet === true) {
            if ($bIdartSet === true) {
                $this->sUrl = $sHttpBasePath . 'front_content.php?idcat='.strval($aParams['idcat']).$this->_sAmp.'idart='.strval($aParams['idcat']);
            } else {
                $this->sUrl = $sHttpBasePath . 'front_content.php?idcat='.strval($aParams['idcat']);
            }
        } else {
            if ($bIdartSet === true) {
                $this->sUrl = $sHttpBasePath . 'front_content.php?idart='.strval($aParams['idart']);
            } else {
                if ($bIdcatArtSet === true) {
                    $this->sUrl = $sHttpBasePath . 'front_content.php?idcatart='.strval($aParams['idcatart']);
                } else {
                    throw new Exception('Cannot build URL because of missing parameters!');
                }
            }
        }
    }
}
?>