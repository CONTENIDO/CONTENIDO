<?php

/**
 * This file contains the multi byte wrapper class for strings.
 *
 * @package    Core
 * @subpackage Util
 * @author     Frederic Schneider <frederic.schneider@4fb.de>
 * @author     Marcus Gnaß <marcus.gnass@4fb.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Wrapper class for mbstring functions to be used with strings.
 *
 * Each method is a wrapper for a mbstring function that implements a fallback
 * to the regular string function if the mbstring function does not exist
 * or the mbstring extension itself is not available.
 *
 * @package Core
 * @subpackage Util
 * @todo add mb_chr(), mb_ord()
 */
class cStringMultiByteWrapper {

    /**
     * Checks if a given mbstring function exists.
     *
     * Caches informations about existing mbstring functions for better
     * performance.
     *
     * @param string $functionName
     * @return bool
     */
    protected static function _functionExists($functionName) {
        static $cache;
        if (!isset($cache)) {
            $cache = array();
            foreach (array(
                'mb_strtolower', 'mb_strtoupper', 'mb_strlen', 'mb_substr',
                'mb_substr_count', 'mb_send_mail', 'mb_strpos', 'mb_strrpos', 'mb_stripos',
                'mb_strripos', 'mb_stristr', 'mb_strrchr'
            ) as $function) {
                $cache[$function] = function_exists($function);
            }
        }
        return isset($cache[$functionName]) ? $cache[$functionName] : false;
    }

    /**
     * Determines multi byte encoding to be used for various mbstring functions.
     *
     * If NULL is given the encoding for the current language is used
     * which tends to be awfully slow as it requires a database lookup!
     *
     * If none could be determined the current set encoding is used.
     *
     * @param string|null $encoding
     *         - give a string to use a specific encoding
     *         - give null to use the encoding of the current language
     * @return string
     */
    protected static function _getEncoding($encoding = null) {
        if (!is_string($encoding)) {
            $encoding = mb_internal_encoding();
        }
        return $encoding;
    }

    /**
     * Make a string lowercase
     *
     * @param string $string
     *         The string being lowercased
     * @param string|null $encoding
     *         encoding parameter, standard: cRegistry::getEncoding()
     * @return string
     *         with all alphabetic characters converted to lowercases
     * @link http://php.net/manual/de/function.mb-strtolower.php
     */
    public static function toLowerCase($string, $encoding = null) {
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
     * @param string $string
     *         The string being uppercased
     * @param string|null $encoding
     *         encoding parameter, standard: cRegistry::getEncoding()
     * @return string
     *         with all alphabetic characters converted to uppercases
     * @link http://php.net/manual/de/function.mb-strtoupper.php
     */
    public static function toUpperCase($string, $encoding = null) {
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
     * @param string $string
     *         The string being checked for length
     * @param string|null $encoding
     *         encoding parameter, standard: cRegistry::getEncoding()
     * @return int
     *         Returns the number of characters
     * @link http://php.net/manual/de/function.mb-strlen.php
     */
    public static function getStringLength($string, $encoding = null) {
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
     * @param string $string
     *         The string to extract the substring form
     * @param int $start
     * @param int $length [Optional]
     *         Maximum number of characters to use from $string, standard is NULL
     * @param string|null $encoding
     *         encoding parameter, standard: cRegistry::getEncoding()
     * @return string
     *         Returns the number of characters
     * @link http://php.net/manual/de/function.mb-substr.php
     */
    public static function getPartOfString($string, $start, $length = null, $encoding = null) {
        if (self::_functionExists('mb_substr')) {
            $result = mb_substr($string, $start, $length, self::_getEncoding($encoding));
        } else {
            $result = substr($string, $start, $length);
        }
        return $result;
    }

    /**
     * Count the number of substring occurences
     *
     * @param string $haystack
     *         The string being checked
     * @param string $needle
     *         The string being found
     * @param string|null $encoding
     *         encoding parameter, standard: cRegistry::getEncoding()
     * @return int
     *         The number of times the needle substring occurs in the haystack string.
     * @link http://php.net/manual/de/function.mb-substr-count.php
     */
    public static function countSubstring($haystack, $needle, $encoding = null) {
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
     * @param string $to
     *         The mail addresses being sent to (multiple recipents comma separated)
     * @param string $subject
     *         The subject of the mail
     * @param string $message
     *         The message of the mail
     * @param string $additional_headers [Optional]
     * @param string $additional_parameter [Optional]
     * @return boolean
     *         true or false
     * @link http://php.net/manual/de/function.mb-send-mail.php
     */
    public static function mail($to, $subject, $message, $additional_headers = null, $additional_parameter = null) {
        if (self::_functionExists('mb_send_mail')) {
            $result = mb_send_mail($to, $subject, $message, $additional_headers, $additional_parameter);
        } else {
            $result = mail($to, $subject, $message, $additional_headers, $additional_parameter);
        }
        return $result;
    }

    /**
     * Find the position of first occurence of string in a string
     *
     * @param string $haystack
     * @param string $needle
     * @param integer $offset [Optional]
     * @param string|null $encoding
     *         encoding parameter, standard: cRegistry::getEncoding()
     * @return int|false
     *         Returns the numeric position of the first occurrence of needle in the haystack string
     * @link http://php.net/manual/de/function.mb-strpos.php
     */
    public static function findFirstPos($haystack, $needle, $offset = 0, $encoding = null) {
        if (self::_functionExists('mb_strpos')) {
            $result = mb_strpos($haystack, $needle, $offset, self::_getEncoding($encoding));
        } else {
            $result = strpos($haystack, $needle, $offset);
        }
        return $result;
    }

    /**
     * Find the position of last occurence of string in a string
     *
     * @param string $haystack
     * @param string $needle
     * @param integer $offset [Optional]
     * @param string|null $encoding
     *         encoding parameter, standard: cRegistry::getEncoding()
     * @return int
     *         Returns the numeric position of the last occurrence of needle in the haystack string
     * @link http://php.net/manual/de/function.mb-strrpos.php
     */
    public static function findLastPos($haystack, $needle, $offset = 0, $encoding = null) {
        if (self::_functionExists('mb_strrpos')) {
            $result = mb_strrpos($haystack, $needle, $offset, self::_getEncoding($encoding));
        } else {
            $result = strrpos($haystack, $needle, $offset);
        }
        return $result;
    }

    /**
     * Finds position of first occurrence of a string within another, case insensitive
     *
     * @param string $haystack
     * @param string $needle
     * @param integer $offset [Optional]
     * @param string|null $encoding
     *         encoding parameter, standard: cRegistry::getEncoding()
     * @return int
     *         Returns the numeric position of the first occurrence of needle in the haystack string
     * @link http://php.net/manual/de/function.mb-stripos.php
     */
    public static function findFirstPosCI($haystack, $needle, $offset = 0, $encoding = null) {
        if (self::_functionExists('mb_stripos')) {
            $result = mb_stripos($haystack, $needle, $offset, self::_getEncoding($encoding));
        } else {
            $result = stripos($haystack, $needle, $offset);
        }
        return $result;
    }

    /**
     * Finds position of last occurrence of a string within another, case insensitive
     *
     * @param string $haystack
     * @param string $needle
     * @param integer $offset [Optional]
     * @param string|null $encoding
     *         encoding parameter, standard: cRegistry::getEncoding()
     * @return int
     *         Returns the numeric position of the last occurrence of needle in the haystack string
     * @link http://php.net/manual/de/function.mb-strripos.php
     */
    public static function findLastPosCI($haystack, $needle, $offset = 0, $encoding = null) {
        if (self::_functionExists('mb_strripos')) {
            $result = mb_strripos($haystack, $needle, $offset, self::_getEncoding($encoding));
        } else {
            $result = strripos($haystack, $needle, $offset);
        }
        return $result;
    }

    /**
     * Finds first occurrence of a string within another, case insensitive
     *
     * @param string $haystack
     * @param string $needle
     * @param boolean $before_needle [Optional]
     * @param string|null $encoding
     *         encoding parameter, standard: cRegistry::getEncoding()
     * @return string
     *         Returns the portion of haystack, or FALSE if needle is not found.
     * @link http://php.net/manual/de/function.mb-stristr.php
     */
    public static function findFirstOccurrenceCI($haystack, $needle, $before_needle = false, $encoding = null) {
        if (self::_functionExists('mb_stristr')) {
            $result = mb_stristr($haystack, $needle, $before_needle, self::_getEncoding($encoding));
        } else {
            $result = stristr($haystack, $needle, $before_needle);
        }
        return $result;
    }

    /**
     * Finds first occurrence of a string within another, case insensitive
     *
     * @param string $haystack
     * @param string $needle
     * @param boolean $part [Optional]
     * @param string|null $encoding
     *         encoding parameter, standard: cRegistry::getEncoding()
     * @return string
     *         Returns the portion of haystack, or FALSE if needle is not found.
     * @link http://php.net/manual/de/function.mb-strrchr.php
     */
    public static function findLastOccurrence($haystack, $needle, $part = false, $encoding = null) {
        if (self::_functionExists('mb_strrchr')) {
            $result = mb_strrchr($haystack, $needle, $part, self::_getEncoding($encoding));
        } elseif (!$part) {
            $result = strrchr($haystack, $needle);
        } else {
            // TODO strrchr canot handle $part = true
            $result = null;
        }
        return $result;
    }

    /**
     * Regular expression match
     *
     * @param string $pattern
     * @param string $string
     * @param array $regs [Optional]
     * @return int
     * @link http://php.net/manual/de/function.mb-ereg.php
     */
    public static function ereg($pattern, $string, &$regs = array()) {
        // TODO provide fallback multibyte extension is missing
        return mb_ereg($pattern, $string, $regs);
    }

    /**
     * Regular expression match ignoring case
     *
     * @param string $pattern
     * @param string $string
     * @param array $regs [Optional]
     * @return int Returns the byte length of the matched string if a match for pattern was found in string
     * @link http://php.net/manual/de/function.mb-eregi.php
     */
    public static function eregi($pattern, $string, &$regs = array()) {
        // TODO provide fallback multibyte extension is missing
        return mb_eregi($pattern, $string, $regs);
    }

    /**
     * Replace regular expression
     *
     * @param string $pattern
     * @param string $replacement
     * @param string $string
     * @param string $option [Optional]
     * @return false|string Returns the byte length of the matched string if a match for pattern was found in string
     * @link http://php.net/manual/de/function.mb-ereg-replace.php
     */
    public static function ereg_replace($pattern, $replacement, $string, $option = 'msr') {
        // TODO provide fallback multibyte extension is missing
        return mb_ereg_replace($pattern, $replacement, $string, $option);
    }

    /**
     * Replace regular expression ignoring case
     *
     * @param string $pattern
     * @param string $replacement
     * @param string $string
     * @param string $option [Optional]
     * @return false|string Returns the byte length of the matched string if a match for pattern was found in string
     * @link http://php.net/manual/de/function.mb-eregi-replace.php
     */
    public static function eregi_replace($pattern, $replacement, $string, $option = 'msr') {
        // TODO provide fallback multibyte extension is missing
        return mb_eregi_replace($pattern, $replacement, $string, $option);
    }

    /**
     * Split string using regular expression
     *
     * @param string $pattern
     * @param string $string
     * @param integer $limit [Optional]
     * @return string[] The result as an array
     * @link http://php.net/manual/de/function.mb-split.php
     */
    public static function split($pattern, $string, $limit = -1) {
        // TODO provide fallback multibyte extension is missing
        return mb_split($pattern, $string, $limit);
    }

}
