<?php
/**
 * This file contains the number datatype class.
 *
 * @package          Core
 * @subpackage       Datatype
 * @version          SVN Revision $Rev:$
 *
 * @author           unknown
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Number datatype class.
 *
 * @package          Core
 * @subpackage       Datatype
 */
class cDatatypeNumber extends cDatatype {

    /**
     *
     * @var int
     */
    protected $_iPrecision;

    /**
     *
     * @var string
     */
    protected $_sThousandSeparatorCharacter;

    /**
     *
     * @var string
     */
    protected $_sDecimalPointCharacter;

    /**
     *
     */
    public function __construct() {
        $language = cI18n::getLanguage();

        // Try to find out the current locale settings
        $aLocaleSettings = cLocaleConv($language);

        $this->setDecimalPointCharacter($aLocaleSettings["mon_decimal_point"]);
        $this->setThousandSeparatorCharacter($aLocaleSettings["mon_thousands_sep"]);

        parent::__construct();
    }

    /**
     * (non-PHPdoc)
     * @see cDatatype::set()
     */
    public function set($value) {
        $this->_mValue = floatval($value);
    }

    /**
     * (non-PHPdoc)
     * @see cDatatype::get()
     */
    public function get() {
        return $this->_mValue;
    }

    /**
     *
     * @param int $iPrecision
     */
    public function setPrecision($iPrecision) {
        $this->_iPrecision = $iPrecision;
    }

    /**
     *
     * @param string $sCharacter
     */
    public function setDecimalPointCharacter($sCharacter) {
        $this->_sDecimalPointCharacter = $sCharacter;
    }

    /**
     *
     * @return string
     */
    public function getDecimalPointCharacter() {
        return $this->_sDecimalPointCharacter;
    }

    /**
     *
     * @param string $sCharacter
     */
    public function setThousandSeparatorCharacter($sCharacter) {
        $this->_sThousandSeparatorCharacter = $sCharacter;
    }

    /**
     *
     * @return string
     */
    public function getThousandSeparatorCharacter() {
        return $this->_sThousandSeparatorCharacter;
    }

    /**
     *
     * @throws cException if the decimal separator character and the thousand
     *         separator character are equal
     */
    public function parse($value) {
        if ($this->_sDecimalPointCharacter == $this->_sThousandSeparatorCharacter) {
            throw new cException("Decimal point character cannot be the same as the thousand separator character. Current decimal point character is '{$this->_sDecimalPointCharacter}', current thousand separator character is '{$this->_sThousandSeparatorCharacter}'");
        }

        // Convert to standard english format
        $value = str_replace($this->_sThousandSeparatorCharacter, "", $value);
        $value = str_replace($this->_sDecimalPointCharacter, ".", $value);

        $this->_mValue = floatval($value);
    }

    /**
     * (non-PHPdoc)
     * @see cDatatype::render()
     */
    public function render() {
        return number_format($this->_mValue, $this->_iPrecision, $this->_sDecimalPointCharacter, $this->_sThousandSeparatorCharacter);
    }

}

?>