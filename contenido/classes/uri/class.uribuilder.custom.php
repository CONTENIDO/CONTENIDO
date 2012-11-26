<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Implementation of IContenido_Frontend_Navigation_UriBuilder to build URL in
 * style index-a-1.html without category path.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package CONTENIDO Backend Classes
 * @version 1.0.1
 * @author Rudi Bieller
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

class cUriBuilderCustom extends cUriBuilder {

    /**
     * Self instance
     *
     * @var cUriBuilderCustom
     */
    private static $_instance;

    /**
     * Configuration
     *
     * @var array
     */
    private $aConfig;

    /**
     * Constructor
     *
     * @return void
     */
    private function __construct() {
        $this->sHttpBasePath = '';
    }

    /**
     * Get instance of self
     *
     * @return cUriBuilderCustom
     */
    public static function getInstance() {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Builds a URL in index-a-1.html style.
     * Index keys of $aParams will be used as "a", corresponding values as "1"
     * in this sample.
     *
     * @param array $aParams
     * @param bool $bUseAbsolutePath
     * @param array $aConfig If not set, will use UriBuilderConfig::getConfig()
     * @return void
     * @throws cInvalidArgumentException
     */
    public function buildUrl(array $aParams, $bUseAbsolutePath = false, array $aConfig = array()) {
        if (sizeof($aParams) == 0) {
            throw new cInvalidArgumentException('$aParams must have at least one entry!');
        }
        // if no config passed or not all parameters available, use default
        // config
        if (sizeof($aConfig) == 0 || !isset($aConfig['prefix']) || !isset($aConfig['suffix']) || !isset($aConfig['separator'])) {
            include_once ('class.uribuilder.config.php');
            $aConfig = cUriBuilderConfig::getConfig();
        }
        $this->aConfig = $aConfig;

        $this->sUrl = $bUseAbsolutePath === true ? $this->sHttpBasePath : '';
        $this->sUrl .= $this->aConfig['prefix'];
        foreach ($aParams as $sKey => $mVal) {
            $sVal = $mVal; // assuming mVal is a string and thus a single value
            if (is_array($mVal)) { // mVal has more than one value, e.g.
                // index-b-1-2.html
                $sVal = implode($this->aConfig['separator'], $mVal);
            }
            $this->sUrl .= $this->aConfig['separator'] . strval($sKey) . $this->aConfig['separator'] . strval($sVal);
        }
        $this->sUrl .= $this->aConfig['suffix'];
    }

}
