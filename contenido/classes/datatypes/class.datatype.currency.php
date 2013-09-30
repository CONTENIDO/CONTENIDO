<?php
/**
 * This file contains the currency datatype class.
 *
 * @package Core
 * @subpackage Datatype
 * @version SVN Revision $Rev:$
 *
 * @author unknown
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Currency datatype class.
 *
 * @package Core
 * @subpackage Datatype
 */
class cDatatypeCurrency extends cDatatypeNumber {

    /**
     * Currency symbol is displayed left of value.
     *
     * @var int
     */
    const LEFT = 1;

    /**
     * Currency symbol is displayed right of value.
     *
     * @var int
     */
    const RIGHT = 2;

    /**
     * Position of currency symbol relative to its value.
     * Can be either cDatatypeCurrency::LEFT or cDatatypeCurrency::RIGHT.
     *
     * @var int
     */
    protected $_cCurrencyLocation;

    /**
     * Currency symbol to be displayed.
     *
     * @var string
     */
    protected $_sCurrencySymbol;

    /**
     * Create new instance.
     */
    public function __construct() {
        parent::__construct();

        $this->setCurrencySymbolLocation(self::RIGHT);
        $this->setCurrencySymbol("�");
    }

    /**
     * Return current currency symbol to display.
     *
     * @return string
     */
    public function getCurrencySymbol() {
        return ($this->_sCurrencySymbol);
    }

    /**
     * Sets current currency symbol to display.
     *
     * @param string $sSymbol
     */
    public function setCurrencySymbol($sSymbol) {
        $this->_sCurrencySymbol = $sSymbol;
    }

    /**
     * Sets current currency symbol location.
     * Can be either cDatatypeCurrency::LEFT or cDatatypeCurrency::RIGHT.
     *
     * @param int $cLocation
     * @throws cInvalidArgumentException if the given location is not one of the
     *         constants cDatatypeCurrency::LEFT and cDatatypeCurrency::RIGHT
     */
    public function setCurrencySymbolLocation($cCurrencyLocation) {
        switch ($cCurrencyLocation) {
            case self::LEFT:
            case self::RIGHT:
                $this->_cCurrencyLocation = $cCurrencyLocation;
                break;
            default:
                throw new cInvalidArgumentException('Warning: No valid cDatatypeCurrency::* Constant given. Available values: cDatatypeCurrency::LEFT, cDatatypeCurrency::RIGHT');
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see cDatatypeNumber::render()
     */
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