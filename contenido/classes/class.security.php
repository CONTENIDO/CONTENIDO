<?php
/**
 * This file contains the the security class.
 *
 * @package    Core
 * @subpackage Security
 * @version    SVN Revision $Rev:$
 *
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This object makes CONTENIDO more secure
 *
 * @package    Core
 * @subpackage Security
 */
class cSecurity {

    /**
     * Checks some CONTENIDO core related request parameters against XSS
     *
     * @return bool
     *         True on success otherwhise nothing.
     */
    public static function checkRequests() {
        $requestValidator = cRequestValidator::getInstance();

        return $requestValidator->checkParams();
    }

    /**
     * Escapes string using CONTENIDO urlencoding method and escapes string for inserting
     *
     * @param string $sString
     *         Input string
     * @param cDb $oDb
     *         CONTENIDO database object
     * @return string
     *         Filtered string
     */
    public static function filter($sString, $oDb) {
        $sString = self::toString($sString);
        if (defined('CON_STRIPSLASHES')) {
            $sString = stripslashes($sString);
        }
        return self::escapeDB(conHtmlSpecialChars($sString), $oDb, false);
    }

    /**
     * Reverts effect of method filter()
     *
     * @param string $sString
     *         Input string
     * @return string
     *         Unfiltered string
     */
    public static function unFilter($sString) {
        $sString = self::toString($sString);
        return htmldecode(self::unEscapeDB($sString));
    }

    /**
     * Check: Has the variable an bool value?
     *
     * @param string $sVar
     *         Input string
     * @return bool
     *         Check state
     */
    public static function isBoolean($sVar) {
        $sTempVar = $sVar;
        $sTemp2Var = self::toBoolean($sVar);
        return $sTempVar === $sTemp2Var;
    }

    /**
     * Check: Is the variable an integer?
     *
     * @param string $sVar
     *         Input string
     * @return bool
     *         Check state
     */
    public static function isInteger($sVar) {
        return preg_match('/^[0-9]+$/', $sVar);
    }

    /**
     * Check: Is the variable an string?
     *
     * @param string $sVar
     *         Input string
     * @return bool
     *         Check state
     */
    public static function isString($sVar) {
        return is_string($sVar);
    }

    /**
     * Convert an string to an bool
     *
     * @param string $sString
     *         Input string
     * @return bool
     *         Type casted input string
     */
    public static function toBoolean($sString) {
        return (bool) $sString;
    }

    /**
     * Convert an string to an integer
     *
     * @param string $sString
     *         Input string
     * @return int
     *         Type casted input string
     */
    public static function toInteger($sString) {
        return (int) $sString;
    }

    /**
     * Convert an string
     *
     * @param string $sString
     *         Input string
     * @param bool $bHTML [optional]
     *         If true check with strip_tags and stripslashes
     * @param string $sAllowableTags [optional]
     *         Allowable tags if $bHTML is true
     * @return string
     *         Converted string
     */
    public static function toString($sString, $bHTML = false, $sAllowableTags = '') {
        $sString = (string) $sString;
        if ($bHTML == true) {
            $sString = strip_tags(stripslashes($sString), $sAllowableTags);
        }
        return $sString;
    }

    /**
     * Escaped an query-string with mysql_real_escape_string
     *
     * @param string $sString
     *         Input string
     * @param cDb $oDB
     *         CONTENIDO database object
     * @param bool $bUndoAddSlashes [optional]
     *         Flag for undo addslashes (optional, default: true)
     * @return string
     *         Converted string
     */
    public static function escapeDB($sString, $oDB, $bUndoAddSlashes = true) {
        if (!is_object($oDB)) {
            return self::escapeString($sString);
        } else {
            if (defined('CON_STRIPSLASHES') && $bUndoAddSlashes == true) {
                $sString = stripslashes($sString);
            }
            return $oDB->escape($sString);
        }
    }

    /**
     * Escaped an query-string with addslashes
     *
     * @param string $sString
     *         Input string
     * @return string
     *         Converted string
     */
    public static function escapeString($sString) {
        $sString = (string) $sString;
        if (defined('CON_STRIPSLASHES')) {
            $sString = stripslashes($sString);
        }
        return addslashes($sString);
    }

    /**
     * Un-quote string quoted with escapeDB()
     *
     * @param string $sString
     *         Input string
     * @return string
     *         Converted string
     */
    public static function unescapeDB($sString) {
        return stripslashes($sString);
    }

}
