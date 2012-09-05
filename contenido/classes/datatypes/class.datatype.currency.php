<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package CONTENIDO Backend Classes
 * @version 1.0
 * @author
 *
 *
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 *
 *       {@internal
 *
 *       $Id$:
 *       }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

define("cDatatypeCurrency_Left", 1);
define("cDatatypeCurrency_Right", 2);
class cDatatypeCurrency extends cDatatypeNumber {

    protected $_cCurrencyLocation;

    protected $_sCurrencySymbol;

    public function __construct() {
        parent::__construct();

        $this->setCurrencySymbolLocation(cDatatypeCurrency_Right);
        $this->setCurrencySymbol("�");
    }

    /**
     *
     * @deprecated [2012-01-19] use __construct instead
     */
    public function cDatatypeCurrency() {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }

    public function setCurrencySymbol($sSymbol) {
        $this->_sCurrencySymbol = $sSymbol;
    }

    public function getCurrencySymbol() {
        return ($this->_sCurrencySymbol);
    }

    /**
     * @throws cInvalidArgumentException if the given location is not one of the constants cDatatypeCurrency_Left and cDatatypeCurrency_Right
     */
    public function setCurrencySymbolLocation($cLocation) {
        switch ($cLocation) {
            case cDatatypeCurrency_Left:
            case cDatatypeCurrency_Right:
                $this->_cCurrencyLocation = $cLocation;
                break;
            default:
                throw new cInvalidArgumentException('Warning: No valid cDatatypeCurrency_* Constant given. Available values: cDatatypeCurrency_Left, cDatatypeCurrency_Right');
        }
    }

    public function render() {
        $value = parent::render();

        switch ($this->_cCurrencyLocation) {
            case cDatatypeCurrency_Left:
                return sprintf("%s %s", $this->_sCurrencySymbol, $value);
                break;
            case cDatatypeCurrency_Right:
                return sprintf("%s %s", $value, $this->_sCurrencySymbol);
                break;
        }
    }

}

?>