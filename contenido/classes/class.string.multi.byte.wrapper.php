<?php

/**
 * This file contains the multibyte wrapper class for strings.
 *
 * @package    Core
 * @subpackage Util
 * @author     Frederic Schneider <frederic.schneider@4fb.de>
 * @author     Marcus Gnaß <marcus.gnass@4fb.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Wrapper class for mbstring functions to be used with strings.
 *
 * Each method is a wrapper for a mbstring function that implements a fallback
 * to the regular string function if the mbstring function does not exist
 * or the mbstring extension itself is not available.
 *
 * @package    Core
 * @subpackage Util
 * @todo add mb_chr(), mb_ord()
 */
class cStringMultiByteWrapper
{

    /**
     * Checks if a given mbstring function exists.
     *
     * Caches information about existing mbstring functions for better performance.
     */
    protected static function _functionExists(string $functionName): bool
    {
        static $cache;
        if (!isset($cache)) {
            $cache = [];
            foreach ([
                 'mb_strtolower', 'mb_strtoupper', 'mb_strlen', 'mb_substr', 'mb_substr_count', 'mb_send_mail',
                 'mb_strpos', 'mb_strrpos', 'mb_stripos', 'mb_strripos', 'mb_stristr', 'mb_strrchr', 'mb_ucfirst'
            ] as $function) {
                $cache[$function] = function_exists($function);
            }
        }
        return $cache[$functionName] ?? false;
    }

    /**
     * Determines multibyte encoding to be used for various mbstring functions.
     *
     * If NULL is given the encoding for the current language is used
     * which tends to be awfully slow as it requires a database lookup!
     *
     * If none could be determined the current set encoding is used.
     *
     * @param ?string $encoding
     *         - give a string to use a specific encoding
     *         - give null to use the encoding of the current language
     * @return bool|string
     */
    protected static function _getEncoding(?string $encoding = null)
    {
        if (!is_string($encoding)) {
            $encoding = mb_internal_encoding();
        }
        return $encoding;
    }

    /**
     * Make a string lowercase
     *
     * @param string $string The string being lowercased
     * @param ?string $encoding Encoding parameter, standard: {@see cRegistry::getEncoding()}
     * @return string With all alphabetic characters converted to lowercases
     * @link https://www.php.net/manual/en/function.mb-strtolower.php
     */
    public static function toLowerCase(string $string, ?string $encoding = null): string
    {
        if (self::_functionExists('mb_strtolower')) {
            $result = mb_strtolower($string, self::_getEncoding($encoding));
        } else {
            $result = strtolower($string);
        }
        return $result;
    }

    /**
     * Make a string uppercase
     *
     * @param string $string The string being uppercased
     * @param ?string $encoding Encoding parameter, standard: {@see cRegistry::getEncoding()}
     * @return string With all alphabetic characters converted to uppercases
     * @link https://www.php.net/manual/en/function.mb-strtoupper.php
     */
    public static function toUpperCase(string $string, ?string $encoding = null): string
    {
        if (self::_functionExists('mb_strtoupper')) {
            $result = mb_strtoupper($string, self::_getEncoding($encoding));
        } else {
            $result = strtoupper($string);
        }
        return $result;
    }

    /**
     * Get string length
     *
     * @param string $string The string being checked for length
     * @param ?string $encoding Encoding parameter, standard: {@see cRegistry::getEncoding()}
     * @return int Returns the number of characters
     * @link https://www.php.net/manual/en/function.mb-strlen.php
     */
    public static function getStringLength(string $string, ?string $encoding = null): int
    {
        if (self::_functionExists('mb_strlen')) {
            $result = mb_strlen($string, self::_getEncoding($encoding));
        } else {
            $result = strlen($string);
        }
        return $result;
    }

    /**
     * Get string length
     *
     * @param string $string The string to extract the substring form
     * @param int $start
     * @param ?int $length Maximum number of characters to use from $string, standard is NULL
     * @param ?string $encoding Encoding parameter, standard: {@see cRegistry::getEncoding()}
     * @return string Returns the number of characters
     * @link https://www.php.net/manual/en/function.mb-substr.php
     */
    public static function getPartOfString(
        string $string,
        int $start,
        ?int $length = null,
        ?string $encoding = null
    ): string {
        if (self::_functionExists('mb_substr')) {
            $result = mb_substr($string, $start, $length, self::_getEncoding($encoding));
        } else {
            $result = substr($string, $start, $length);
        }
        return $result;
    }

    /**
     * Count the number of substring occurrences
     *
     * @param string $haystack The string being checked
     * @param string $needle The string being found
     * @param ?string $encoding Encoding parameter, standard: {@see cRegistry::getEncoding()}
     * @return int The number of times the needle substring occurs in the haystack string.
     * @link https://www.php.net/manual/en/function.mb-substr-count.php
     */
    public static function countSubstring(string $haystack, string $needle, ?string $encoding = null): int
    {
        if (self::_functionExists('mb_substr_count')) {
            $result = mb_substr_count($haystack, $needle, self::_getEncoding($encoding));
        } else {
            $result = substr_count($haystack, $needle);
        }
        return $result;
    }

    /**
     * Send encoded mail
     *
     * @param string $to The mail addresses being sent to (multiple recipients comma separated)
     * @param string $subject The subject of the mail
     * @param string $message The message of the mail
     * @param string|string[] $additional_headers [Optional]
     * @param ?string $additional_parameter [Optional]
     * @return bool true or false
     * @link https://www.php.net/manual/en/function.mb-send-mail.php
     */
    public static function mail(
        string $to,
        string $subject,
        string $message,
        $additional_headers = null,
        ?string $additional_parameter = null
    ): bool {
        if (self::_functionExists('mb_send_mail')) {
            $result = mb_send_mail($to, $subject, $message, $additional_headers, $additional_parameter);
        } else {
            $result = mail($to, $subject, $message, $additional_headers, $additional_parameter);
        }
        return $result;
    }

    /**
     * Find the position of first occurrence of string in a string
     *
     * @param ?string $encoding Encoding parameter, standard: {@see cRegistry::getEncoding()}
     * @return int|false Returns the numeric position of the first occurrence of needle in the haystack string
     * @link https://www.php.net/manual/en/function.mb-strpos.php
     */
    public static function findFirstPos(string $haystack, string $needle, int $offset = 0, ?string $encoding = null)
    {
        if (self::_functionExists('mb_strpos')) {
            $result = mb_strpos($haystack, $needle, $offset, self::_getEncoding($encoding));
        } else {
            $result = strpos($haystack, $needle, $offset);
        }
        return $result;
    }

    /**
     * Find the position of last occurrence of string in a string
     *
     * @param ?string $encoding Encoding parameter, standard: {@see cRegistry::getEncoding()}
     * @return int|false Returns the numeric position of the last occurrence of needle in the haystack string
     * @link https://www.php.net/manual/en/function.mb-strrpos.php
     */
    public static function findLastPos(string $haystack, string $needle, int $offset = 0, ?string $encoding = null)
    {
        if (self::_functionExists('mb_strrpos')) {
            $result = mb_strrpos($haystack, $needle, $offset, self::_getEncoding($encoding));
        } else {
            $result = strrpos($haystack, $needle, $offset);
        }
        return $result;
    }

    /**
     * Finds position of first occurrence of a string within another, case-insensitive
     *
     * @param ?string $encoding Encoding parameter, standard: {@see cRegistry::getEncoding()}
     * @return int|false Returns the numeric position of the first occurrence of needle in the haystack string
     * @link https://www.php.net/manual/en/function.mb-stripos.php
     */
    public static function findFirstPosCI(string $haystack, string $needle, int $offset = 0, ?string $encoding = null)
    {
        if (self::_functionExists('mb_stripos')) {
            $result = mb_stripos($haystack, $needle, $offset, self::_getEncoding($encoding));
        } else {
            $result = stripos($haystack, $needle, $offset);
        }
        return $result;
    }

    /**
     * Finds position of last occurrence of a string within another, case-insensitive
     *
     * @param ?string $encoding Encoding parameter, standard: {@see cRegistry::getEncoding()}
     * @return int|false Returns the numeric position of the last occurrence of needle in the haystack string
     * @link https://www.php.net/manual/en/function.mb-strripos.php
     */
    public static function findLastPosCI(string $haystack, string $needle, int $offset = 0, ?string $encoding = null)
    {
        if (self::_functionExists('mb_strripos')) {
            $result = mb_strripos($haystack, $needle, $offset, self::_getEncoding($encoding));
        } else {
            $result = strripos($haystack, $needle, $offset);
        }
        return $result;
    }

    /**
     * Finds first occurrence of a string within another, case-insensitive
     *
     * @param ?string $encoding Encoding parameter, standard: {@see cRegistry::getEncoding()}
     * @return string|false Returns the portion of haystack, or FALSE if needle is not found.
     * @link https://www.php.net/manual/en/function.mb-stristr.php
     */
    public static function findFirstOccurrenceCI(
        string $haystack,
        string $needle,
        bool $before_needle = false,
        ?string $encoding = null
    ) {
        if (self::_functionExists('mb_stristr')) {
            $result = mb_stristr($haystack, $needle, $before_needle, self::_getEncoding($encoding));
        } else {
            $result = stristr($haystack, $needle, $before_needle);
        }
        return $result;
    }

    /**
     * Finds first occurrence of a string within another, case-insensitive
     *
     * @param ?string $encoding Encoding parameter, standard: {@see cRegistry::getEncoding()}
     * @return string|false|null Returns the portion of haystack, or FALSE if needle is not found.
     * @link https://www.php.net/manual/en/function.mb-strrchr.php
     */
    public static function findLastOccurrence(
        string $haystack,
        string $needle,
        bool $part = false,
        ?string $encoding = null
    ) {
        if (self::_functionExists('mb_strrchr')) {
            $result = mb_strrchr($haystack, $needle, $part, self::_getEncoding($encoding));
        } elseif (!$part) {
            $result = strrchr($haystack, $needle);
        } else {
            // TODO strrchr can't handle $part = true
            $result = null;
        }
        return $result;
    }

    /**
     * Make a string's first character uppercase.
     *
     * @param string $string The input string.
     * @param string|null $encoding The string encoding.
     * @return string
     * @link https://www.php.net/manual/en/function.mb-ucfirst.php
     * @since CONTENIDO 4.10.2
     */
    public static function ucfirst(string $string, ?string $encoding = null): string
    {
        if (self::_functionExists('mb_ucfirst')) {
            return mb_ucfirst($string, self::_getEncoding($encoding));
        } else {
            return ucfirst($string);
        }
    }

    /**
     * Regular expression match
     *
     * @param string[] $regs
     * @link https://www.php.net/manual/en/function.mb-ereg.php
     */
    public static function ereg(string $pattern, string $string, array &$regs = []): bool
    {
        // TODO provide fallback multibyte extension is missing
        return mb_ereg($pattern, $string, $regs);
    }

    /**
     * Regular expression match ignoring case
     *
     * @param string[] $regs
     * @return int Returns the byte length of the matched string if a match for pattern was found in string
     * @link https://www.php.net/manual/en/function.mb-eregi.php
     */
    public static function eregi(string $pattern, string $string, array &$regs = []): int
    {
        // TODO provide fallback multibyte extension is missing
        return mb_eregi($pattern, $string, $regs);
    }

    /**
     * Replace regular expression
     *
     * @return false|null|string Returns the byte length of the matched string if a match for pattern was found in string
     * @link https://www.php.net/manual/en/function.mb-ereg-replace.php
     */
    public static function ereg_replace(string $pattern, string $replacement, string $string, ?string $option = 'msr')
    {
        // TODO provide fallback multibyte extension is missing
        return mb_ereg_replace($pattern, $replacement, $string, $option);
    }

    /**
     * Replace regular expression ignoring case
     *
     * @return false|null|string Returns the byte length of the matched string if a match for pattern was found in string
     * @link https://www.php.net/manual/en/function.mb-eregi-replace.php
     */
    public static function eregi_replace(string $pattern, string $replacement, string $string, ?string $option = 'msr')
    {
        // TODO provide fallback multibyte extension is missing
        return mb_eregi_replace($pattern, $replacement, $string, $option);
    }

    /**
     * Split string using regular expression
     *
     * @return string[] The result as an array
     * @link https://www.php.net/manual/en/function.mb-split.php
     */
    public static function split(string $pattern, string $string, int $limit = -1): array
    {
        // TODO provide fallback multibyte extension is missing
        return mb_split($pattern, $string, $limit);
    }

    /**
     * Convert character encoding.
     *
     * @return string|false
     * @link https://www.php.net/manual/en/function.mb-convert-encoding.php
     * @since CONTENIDO 4.10.2
     */
    public static function convertEncoding(
        string $string,
        ?string $toEncoding = null,
        ?string $fromEncoding = null
    ) {
        if (empty($string)) {
            return $string;
        }
        if (!$toEncoding) {
            $toEncoding = cRegistry::getEncoding();
        }

        return mb_convert_encoding($string, self::_getEncoding($toEncoding), $fromEncoding);
    }
}
