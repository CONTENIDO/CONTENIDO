<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Converts characters between their normalized and diacritic representation.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package CONTENIDO Backend Classes
 * @version 1.0.0
 * @author unknown
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since file available since CONTENIDO release <= 4.6
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * cCharacterConverter
 *
 * @deprecated [2013-02-06] This class is deprecated.
 *
 *             A diacritic mark or diacritic is a mark added to a letter to
 *             alter a
 *             word's pronunciation or to distungish between similar words.
 *             However,
 *             users of foreign languages are unable to type diacritics (either
 *             because
 *             the keyboard mapping doesn't support them, or they are looking to
 *             similar
 *             to other characters). Examples for conversions:
 *
 *             German diacritic char ï¿½ maps to u and ue.
 *
 *             Developers can use the diacritic search implemented in the
 *             GenericDB to
 *             automatically handle diacritic search conversion.
 */
class cCharacterConverter {

    protected $_oDB;

    protected $_aAliasCache = array();

    protected $_aCharCache = array();

    public function __construct() {
        cDeprecated('This class is deprecated');
        return null;
    }

    /**
     *
     * @deprecated 2012-08-24 Use __construct()
     */
    function cCharacterConverter() {
        cDeprecated('Use __construct()');
        $this->__construct();
    }

    public function fetchDiacriticCharactersForNormalizedChar($sEncoding, $cNormalizedChar) {
        return null;
    }

    /**
     *
     * @param unknown_type $sEncoding
     * @param unknown_type $cCharacter
     * @throws cInvalidArgumentException if the given character is longer than
     *         one char
     * @return multitype:NULL
     */
    public function fetchNormalizedCharsForDiacriticCharacter($sEncoding, $cCharacter) {
        return null;
    }

    protected function _correctEncoding($sEncoding) {
        return null;
    }

}
