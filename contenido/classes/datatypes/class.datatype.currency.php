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

class cDatatypeCurrency extends cDatatypeNumber {

    protected $_cCurrencyLocation;

    protected $_sCurrencySymbol;

    const LEFT = 1;

    const RIGHT = 2;

    public function __construct() {
        parent::__construct();

        $this->setCurrencySymbolLocation(self::RIGHT);
        $this->setCurrencySymbol("�");
    }

    public function setCurrencySymbol($sSymbol) {
        $this->_sCurrencySymbol = $sSymbol;
    }

    public function getCurrencySymbol() {
        return ($this->_sCurrencySymbol);
    }

    /**
     * @throws cInvalidArgumentException if the given location is not one of the constants cDatatypeCurrency::LEFT and cDatatypeCurrency::RIGHT
     */
    public function setCurrencySymbolLocation($cLocation) {
        switch ($cLocation) {
            case self::LEFT:
            case self::RIGHT:
                $this->_cCurrencyLocation = $cLocation;
                break;
            default:
                throw new cInvalidArgumentException('Warning: No valid cDatatypeCurrency::* Constant given. Available values: cDatatypeCurrency::LEFT, cDatatypeCurrency::RIGHT');
        }
    }

    public function render() {
        $value = parent::render();

        switch ($this->_cCurrencyLocation) {
            case self::LEFT:
                return sprintf("%s %s", $this->_sCurrencySymbol, $value);
                break;
            case self::RIGHT:
                return sprintf("%s %s", $value, $this->_sCurrencySymbol);
                break;
        }
    }

}

?>