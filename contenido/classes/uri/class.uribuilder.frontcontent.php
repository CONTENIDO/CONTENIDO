<?php

/**
 * This file contains the uri builder front content class.
 *
 * @package    Core
 * @subpackage Frontend_URI
 * @author     Rudi Bieller
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Implementation to build front_content.php URL.
 *
 * @package    Core
 * @subpackage Frontend_URI
 */
class cUriBuilderFrontcontent extends cUriBuilder {

    /**
     * Self instance
     *
     * @var cUriBuilderFrontcontent
     */
    private static $_instance;

    /**
     * XHTML compliant parameter composition delimiter
     *
     * @var string
     */
    private $_sAmp = '&';

    /**
     * Constructor to create an instance of this class.
     */
    private function __construct() {
        $this->sHttpBasePath = '';
    }

    /**
     * Get instance of self.
     *
     * @return cUriBuilderFrontcontent
     */
    public static function getInstance() {
        if (self::$_instance == NULL) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Builds a URL in front_content.php style.
     *
     * Depending on which array keys of $aParams are set, the URL is
     * built differently.
     *
     * Valid array keys are: idcat, idart and idcatart.
     *
     * Additional array keys will also be added to the generated url.
     *
     * Internally, the method first tries to create URLs in this order:
     * - front_content.php?idcat=1&idart=1
     * - front_content.php?idcat=1
     * - front_content.php?idart=1
     * - front_content.php?idcatart=1
     *
     * @param array $aParams
     * @param bool $bUseAbsolutePath [optional]
     * @param array $aConfig [optional]
     *         Is not used at the moment
     * @throws cInvalidArgumentException
     * @throws cException
     */
    public function buildUrl(array $aParams, $bUseAbsolutePath = false, array $aConfig = array()) {
        $bIdcatSet = isset($aParams['idcat']);
        $bIdartSet = isset($aParams['idart']);
        $bIdcatArtSet = isset($aParams['idcatart']);
        if ($bIdcatSet === false && $bIdartSet === false && $bIdcatArtSet === false) {
            throw new cInvalidArgumentException('$aParams must have at least one of the following values set: $aParams[idcat], $aParams[idart] or $aParams[idcatart]!');
        }
        $sHttpBasePath = $bUseAbsolutePath === true ? $this->sHttpBasePath : '';
        if ($bIdcatSet === true) {
            if ($bIdartSet === true) {
                $this->sUrl = $sHttpBasePath . 'front_content.php?idcat=' . strval($aParams['idcat']) . $this->_sAmp . 'idart=' . strval($aParams['idart']);
            } else {
                $this->sUrl = $sHttpBasePath . 'front_content.php?idcat=' . strval($aParams['idcat']);
            }
        } else {
            if ($bIdartSet === true) {
                $this->sUrl = $sHttpBasePath . 'front_content.php?idart=' . strval($aParams['idart']);
            } else {
                if ($bIdcatArtSet === true) {
                    $this->sUrl = $sHttpBasePath . 'front_content.php?idcatart=' . strval($aParams['idcatart']);
                } else {
                    throw new cException('Cannot build URL because of missing parameters!');
                }
            }
        }

        // now add additional params
        foreach ($aParams as $param => $value) {
            if ($param == 'idcat' || $param == 'idart' || $param == 'idcatart') {
                continue;
            }
            $this->sUrl .= $this->_sAmp . $param . '=' . urlencode(urldecode((string) $value));
        }
    }

}
