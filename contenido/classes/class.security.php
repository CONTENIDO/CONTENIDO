<?php

/**
 * This file contains the security class.
 *
 * @package    Core
 * @subpackage Security
 * @author     Frederic Schneider
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This object makes CONTENIDO more secure.
 *
 * @package    Core
 * @subpackage Security
 */
class cSecurity
{
    /**
     * Checks some CONTENIDO core-related request parameters against XSS.
     *
     * @return bool True on success otherwise nothing.
     * @throws cFileNotFoundException|cInvalidArgumentException
     */
    public static function checkRequests(): bool
    {
        return cRequestValidator::getInstance()->checkParams();
    }

    /**
     * Escapes string using CONTENIDO urlencoding method and escapes string for inserting.
     *
     * @param mixed $value Input value, e.g. a string
     * @param cDb $db CONTENIDO database object
     * @return string Filtered string
     */
    public static function filter($value, cDb $db): string
    {
        $value = self::toString($value);
        if (defined('CON_STRIPSLASHES')) {
            $value = stripslashes($value);
        }
        return self::escapeDB(conHtmlSpecialChars($value), $db, false);
    }

    /**
     * Reverts effect of method filter().
     *
     * @param mixed $value Input value, e.g. a string
     * @return string Unfiltered string
     */
    public static function unFilter($value): string
    {
        return htmldecode(self::unescapeDB(self::toString($value)));
    }

    /**
     * Check: Is the value of type boolean?
     *
     * @param mixed $value Input value
     * @return bool Check state
     */
    public static function isBoolean($value): bool
    {
        return $value === self::toBoolean($value);
    }

    /**
     * Check: Is the value of type integer?
     *
     * @param mixed $value Input value
     * @param bool $strict Strict check
     *      - true: Check if the value is of type integer.
     *      - false: Check if the value is numeric.
     * @return bool Check state
     */
    public static function isInteger($value, bool $strict = false): bool
    {
        return $strict ? is_int($value) : preg_match('/^[0-9]+$/', self::toString($value));
    }

    /**
     * Check: Is the value a numeric string or an integer and is positive?
     * Everything above zero is interpreted as a positive integer.
     *
     * @param mixed $value The value to check
     * @return bool Check state
     */
    public static function isPositiveInteger($value): bool
    {
        return is_numeric($value) && self::toInteger($value) == $value
            && self::toInteger($value) > 0;
    }

    /**
     * Check: Is the value of type string?
     *
     * @param mixed $value Input value, e.g. a string
     * @return bool Check state
     */
    public static function isString($value): bool
    {
        return is_string($value);
    }

    /**
     * Convert a string to a bool.
     *
     * @param mixed $value Input value, e.g. a string
     * @return bool Type casted input string
     */
    public static function toBoolean($value): bool
    {
        return is_bool($value) ? $value : (bool) $value;
    }

    /**
     * Convert a string to an integer.
     *
     * @param mixed $value Input value, e.g. a string
     * @return int Type casted input string
     */
    public static function toInteger($value): int
    {
        return is_int($value) ? $value : (int) $value;
    }

    /**
     * Convert a value to a string.
     *
     * @param mixed $value Input value, e.g. a string
     * @param bool $isHtml [optional] If true check with strip_tags and stripslashes
     * @param null|string|string[] $allowedTags [optional] Allowable tags if $bHTML is true
     * @return string Converted string
     */
    public static function toString($value, bool $isHtml = false, $allowedTags = ''): string
    {
        $value = is_string($value) ? $value : (string) $value;
        if ($isHtml) {
            $value = strip_tags(stripslashes($value), $allowedTags);
        }
        return $value;
    }

    /**
     * Escaped a query-string with mysql_real_escape_string.
     *
     * @param mixed $value Input value, e.g. a string
     * @param ?cDb $db CONTENIDO database object
     * @param bool $undoAddSlashes [optional; default: true] Flag for undo addslashes
     * @return string Converted string
     */
    public static function escapeDB($value, ?cDb $db = null, bool $undoAddSlashes = true): string
    {
        if (!is_object($db)) {
            return self::escapeString($value);
        } else {
            if (defined('CON_STRIPSLASHES') && $undoAddSlashes) {
                $value = stripslashes($value);
            }
            return $db->escape($value);
        }
    }

    /**
     * Escaped an query-string with addslashes.
     *
     * @param mixed $value Input value, e.g. a string
     * @return string Converted string
     */
    public static function escapeString($value): string
    {
        $value = self::toString($value);
        if (defined('CON_STRIPSLASHES')) {
            $value = stripslashes($value);
        }
        return addslashes($value);
    }

    /**
     * Un-quote string quoted with escapeDB().
     *
     * @param string $value Input value, e.g. a string
     * @return string Converted string
     */
    public static function unescapeDB($value): string
    {
        return stripslashes(self::toString($value));
    }

}
