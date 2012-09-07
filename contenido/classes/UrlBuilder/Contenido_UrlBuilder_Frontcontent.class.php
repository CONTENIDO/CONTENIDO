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
 * @version    1.0.3
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * 
 * {@internal 
 *   created 2008-02-19
 *   modified 2008-09-09 Fix of parameter checking in method buildUrl()
 *   modified 2008-09-29, Murat Purc, fix parameter check and third argument for buildUrl()
 *   modified 2008-12-26, Murat Purc, added handling of additional parameter to buildUrl()
 *   modified 2009-01-19 Rudi Bieller Bugfix in buildUrl() for idart (had idcat as param name...)
 *   @todo: add switch for & vs. &amp;
 * 
 *   $Id: Contenido_UrlBuilder_Frontcontent.class.php 976 2009-01-19 14:10:23Z rudi.bieller $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

include_once('Contenido_UrlBuilder.class.php');

class Contenido_UrlBuilder_Frontcontent extends Contenido_UrlBuilder {

    /**
     * Self instance
     * @var  Contenido_UrlBuilder_Frontcontent
     */
    static private $_instance;

    /**
     * XHTML compliant parameter composition delemiter
     * @var  string
     */
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
     * Additional array keys will also be added to the generated url.
     * Internally, the method first tries to create URLs in this order:
     * front_content.php?idcat=1&idart=1
     * front_content.php?idcat=1
     * front_content.php?idart=1
     * front_content.php?idcatart=1
     *
     * @param array $aParams
     * @param boolean $bUseAbsolutePath
     * @param array  $aConfig Is not used at the moment 
     * @return void
     * @throws InvalidArgumentException
     * @throws Exception
     * @author Rudi Bieller
     */
    public function buildUrl(array $aParams, $bUseAbsolutePath = false, array $aConfig = array()) {
        $bIdcatSet = isset($aParams['idcat']);
        $bIdartSet = isset($aParams['idart']);
        $bIdcatArtSet = isset($aParams['idcatart']);
        if ($bIdcatSet === false && $bIdartSet === false && $bIdcatArtSet === false) {
            throw new InvalidArgumentException('$aParams must have at least one of the following values set: $aParams[idcat], $aParams[idart] or $aParams[idcatart]!');
        }
        $sHttpBasePath = $bUseAbsolutePath === true ? $this->sHttpBasePath : '';
        if ($bIdcatSet === true) {
            if ($bIdartSet === true) {
                $this->sUrl = $sHttpBasePath . 'front_content.php?idcat='.strval($aParams['idcat']).$this->_sAmp.'idart='.strval($aParams['idart']);
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

        // now add additional params
        foreach ($aParams as $param => $value) {
            if ($param == 'idcat' || $param == 'idart' || $param == 'idcatart') {
                continue;
            }
            $this->sUrl .= $this->_sAmp . $param .'=' . urlencode(urldecode((string) $value));
        }

    }
}
?>