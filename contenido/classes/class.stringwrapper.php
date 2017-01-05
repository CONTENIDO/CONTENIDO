<?php
/**
 * This file contains the string wrapper class.
 *
 * @package    Core
 * @subpackage Util
 * @author     Frederic Schneider <frederic.schneider@4fb.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * String wrapper class
 *
 * @package Core
 * @subpackage Util
 */
class cStringWrapper {

    protected static $isMbstringLoaded = null;
    protected static $mbstringFunction = array();

    /**
     * Check if mbstring extension is loaded     *
     */
    protected static function mbStringLoading() {

        if (extension_loaded('mbstring')) {
            self::$isMbstringLoaded = true;
        } else {
            self::$isMbstringLoaded = false;
        }

        self::mbStringFunctions();
    }

    /**
     * Checks if mbstring functions are exists
     */
    protected static function mbStringFunctions() {

        // Check for mb_strtolower
        if (function_exists('mb_strtolower')) {
            self::$mbstringFunction['mb_strtolower'] = true;
        }

        // Check for mb_strtoupper
        if (function_exists('mb_strtoupper')) {
            self::$mbstringFunction['mb_strtoupper'] = true;
        }

        // Check for mb_strlen
        if (function_exists('mb_strlen')) {
            self::$mbstringFunction['mb_strlen'] = true;
        }

        // Check for mb_sustr
        if (function_exists('mb_substr')) {
            self::$mbstringFunction['mb_substr'] = true;
        }

        // Check for mb_sustr_count
        if (function_exists('mb_substr_count')) {
            self::$mbstringFunction['mb_substr_count'] = true;
        }

        // Check for mb_strpos
        if (function_exists('mb_strpos')) {
            self::$mbstringFunction['mb_strpos'] = true;
        }

        // Check for mb_strrpos
        if (function_exists('mb_strrpos')) {
            self::$mbstringFunction['mb_strrpos'] = true;
        }

        // Check for mb_stripos
        if (function_exists('mb_stripos')) {
            self::$mbstringFunction['mb_stripos'] = true;
        }

        // Check for mb_strripos
        if (function_exists('mb_strripos')) {
            self::$mbstringFunction['mb_strripos'] = true;
        }

        // Check for mb_stristr
        if (function_exists('mb_stristr')) {
            self::$mbstringFunction['mb_stristr'] = true;
        }

        // Check for mb_strrchr
        if (function_exists('mb_strrchr')) {
            self::$mbstringFunction['mb_strrchr'] = true;
        }
    }

    /**
     * Make a string lowercase
     * See also PHP documentation for mb_strtolower()
     *
     * @param $string The string being lowercased
     * @param mixed $mb_encoding encoding parameter, standard: cRegistry::getEncoding()
     * @return string with all alphabetic characters converted to lowercases
     * @link http://php.net/manual/de/function.mb-strtolower.php
     */
    public static function toLowerCase($string, $mb_encoding = '') {

        // Fallback to the regular string functions if a mbstring function does not exist
        // or mbstring extension is not available
        if (self::$isMbstringLoaded === false || empty(self::$mbstringFunction['mb_strtolower'])) {
            return strtolower($string);
        } elseif (self::$isMbstringLoaded === null) {
            self::mbStringLoading();
         }

        if ($mb_encoding == '') {
            $mb_encoding = mb_internal_encoding();
        }

        return mb_strtolower($string, $mb_encoding);
    }

    /**
     * Make a string uppercase
     *
     * @param $string The string being uppercased
     * @param mixed $mb_encoding encoding parameter, standard: cRegistry::getEncoding()
     * @return string with all alphabetic characters converted to uppercases
     * @link http://php.net/manual/de/function.mb-strtoupper.php
     */
    public static function toUpperCase($string, $mb_encoding = '') {

        // Fallback to the regular string functions if a mbstring function does not exist
        // or mbstring extension is not available
        if (self::$isMbstringLoaded === false || empty(self::$mbstringFunction['mb_strtoupper'])) {
            return strtoupper($string);
        } elseif (self::$isMbstringLoaded === null) {
            self::mbStringLoading();
        }

        if ($mb_encoding == '') {
            $mb_encoding = cRegistry::getEncoding();
        }

        return mb_strtoupper($string, $mb_encoding);
    }

    /**
     * Get string length
     *
     * @param $string The string being checked for length
     * @param mixed $mb_encoding encoding parameter, standard: cRegistry::getEncoding()
     * @return string Returns the number of characters
     * @link http://php.net/manual/de/function.mb-strlen.php
     */
    public static function getStringLength($string, $mb_encoding = '') {

        // Fallback to the regular string functions if a mbstring function does not exist
        // or mbstring extension is not available
        if (self::$isMbstringLoaded === false || empty(self::$mbstringFunction['mb_strlen'])) {
            return strlen($string);
        } elseif (self::$isMbstringLoaded === null) {
            self::mbStringLoading();
        }

        if ($mb_encoding == '') {
            $mb_encoding = cRegistry::getEncoding();
        }

        return mb_strlen($string, $mb_encoding);
    }

    /**
     * Get string length
     *
     * @param $string The string to extract the substring form
     * @param int
     * @param string [Optional] Maximum number of characters to use from $string, standard is NULL
     * @param mixed $mb_encoding encoding parameter, standard: cRegistry::getEncoding()
     * @return string Returns the number of characters
     * @link http://php.net/manual/de/function.mb-substr.php
     */
    public static function getPartOfString($string, $start, $length = null, $mb_encoding = '') {

        // Fallback to the regular string functions if a mbstring function does not exist
        // or mbstring extension is not available
        if (self::$isMbstringLoaded === false || empty(self::$mbstringFunction['mb_substr'])) {
            return substr($string, $start, $length);
        } elseif (self::$isMbstringLoaded === null) {
            self::mbStringLoading();
        }

        if ($mb_encoding == '') {
            $mb_encoding = cRegistry::getEncoding();
        }

        return mb_substr($string, $start, $length, $mb_encoding);
    }

    /**
     * Count the number of substring occurences
     *
     * @param string $haystack The string being checked
     * @param string $needle The string being found
     * @param mixed $mb_encoding encoding parameter, standard: cRegistry::getEncoding()
     * @return string The number of times the needle substring occurs in the haystack string.
     * @link http://php.net/manual/de/function.mb-substr-count.php
     */
    public static function countSubstring($haystack, $needle, $mb_encoding = '') {

        // Fallback to the regular string functions if a mbstring function does not exist
        // or mbstring extension is not available
        if (self::$isMbstringLoaded === false || empty(self::$mbstringFunction['mb_substr_count'])) {
            return substr_count($haystack, $needle);
        } elseif (self::$isMbstringLoaded === null) {
            self::mbStringLoading();
        }

        if ($mb_encoding == '') {
            $mb_encoding = cRegistry::getEncoding();
        }

        return mb_substr_count($haystack, $needle, $mb_encoding);
    }

    /**
     * Send encoded mail
     *
     * @param string $to The mail addresses being sent to (multiple recipents comma separated)
     * @param string $subject The subject of the mail
     * @param string $message The message of the mail
     * @param string $additional_headers [Optional]
     * @param string $additional_parameter [Optional]
     * @return boolean true or false
     * @link http://php.net/manual/de/function.mb-send-mail.php
     */
    public static function mail($to, $subject, $message, $additional_headers = null, $aditional_parameter = null) {

        // Fallback to the regular string functions if a mbstring function does not exist
        // or mbstring extension is not available
        if (self::$isMbstringLoaded === false || empty(self::$mbstringFunction['mb_mail'])) {
            return mail($to, $subject, $message, $additional_headers, $aditional_parameter);
        } elseif (self::$isMbstringLoaded === null) {
            self::mbStringLoading();
        }

        return mb_send_mail($to, $subject, $message, $additional_headers, $aditional_parameter);
    }

    /**
     * Find the position of first occurence of string in a string
     *
     * @param string $haystack
     * @param string $needle
     * @param integer $offset [Optional]
     * @param mixed $mb_encoding encoding parameter, standard: cRegistry::getEncoding()
     * @return Returns the numeric position of the first occurrence of needle in the haystack string
     * @link http://php.net/manual/de/function.mb-strpos.php
     */
    public static function findFirstPos($haystack, $needle, $offset = 0, $mb_encoding = '') {

        // Fallback to the regular string functions if a mbstring function does not exist
        // or mbstring extension is not available
        if (self::$isMbstringLoaded === false || empty(self::$mbstringFunction['mb_strpos'])) {
            return strpos($haystack, $needle, $offset);
        } elseif (self::$isMbstringLoaded === null) {
            self::mbStringLoading();
        }

        if ($mb_encoding == '') {
            $mb_encoding = cRegistry::getEncoding();
        }

        return mb_strpos($haystack, $needle, $offset, $mb_encoding);
    }

    /**
     * Find the position of last occurence of string in a string
     *
     * @param string $haystack
     * @param string $needle
     * @param integer $offset [Optional]
     * @param mixed $mb_encoding encoding parameter, standard: cRegistry::getEncoding()
     * @return Returns the numeric position of the last occurrence of needle in the haystack string
     * @link http://php.net/manual/de/function.mb-strrpos.php
     */
    public static function findLastPos($haystack, $needle, $offset = 0, $mb_encoding = '') {

        // Fallback to the regular string functions if a mbstring function does not exist
        // or mbstring extension is not available
        if (self::$isMbstringLoaded === false || empty(self::$mbstringFunction['mb_strrpos'])) {
            return strrpos($haystack, $needle, $offset);
        } elseif (self::$isMbstringLoaded === null) {
            self::mbStringLoading();
        }

        if ($mb_encoding == '') {
            $mb_encoding = cRegistry::getEncoding();
        }

        return mb_strrpos($haystack, $needle, $offset, $mb_encoding);
    }

    /**
     * Finds position of first occurrence of a string within another, case insensitive
     *
     * @param string $haystack
     * @param string $needle
     * @param integer $offset [Optional]
     * @param mixed $mb_encoding encoding parameter, standard: cRegistry::getEncoding()
     * @return Returns the numeric position of the first occurrence of needle in the haystack string
     * @link http://php.net/manual/de/function.mb-stripos.php
     */
    public static function findFirstPosCI($haystack, $needle, $offset = 0, $mb_encoding = '') {

        // Fallback to the regular string functions if a mbstring function does not exist
        // or mbstring extension is not available
        if (self::$isMbstringLoaded === false || empty(self::$mbstringFunction['mb_stripos'])) {
            return stripos($haystack, $needle, $offset);
        } elseif (self::$isMbstringLoaded === null) {
            self::mbStringLoading();
        }

        if ($mb_encoding == '') {
            $mb_encoding = cRegistry::getEncoding();
        }

        return mb_stripos($haystack, $needle, $offset, $mb_encoding);
    }

    /**
     * Finds position of last occurrence of a string within another, case insensitive
     *
     * @param string $haystack
     * @param string $needle
     * @param integer $offset [Optional]
     * @param mixed $mb_encoding encoding parameter, standard: cRegistry::getEncoding()
     * @return Returns the numeric position of the last occurrence of needle in the haystack string
     * @link http://php.net/manual/de/function.mb-strripos.php
     */
    public static function findLastPosCI($haystack, $needle, $offset = 0, $mb_encoding = '') {

        // Fallback to the regular string functions if a mbstring function does not exist
        // or mbstring extension is not available
        if (self::$isMbstringLoaded === false || empty(self::$mbstringFunction['mb_strripos'])) {
            return strripos($haystack, $needle, $offset);
        } elseif (self::$isMbstringLoaded === null) {
            self::mbStringLoading();
        }

        if ($mb_encoding == '') {
            $mb_encoding = cRegistry::getEncoding();
        }

        return mb_strripos($haystack, $needle, $offset, $mb_encoding);
    }

    /**
     * Finds first occurrence of a string within another, case insensitive
     *
     * @param string $haystack
     * @param string $needle
     * @param boolean $before_needle [Optional]
     * @param mixed $mb_encoding encoding parameter, standard: cRegistry::getEncoding()
     * @return Returns the numeric position of the last occurrence of needle in the haystack string
     * @link http://php.net/manual/de/function.mb-stristr.php
     */
    public static function findFirstOccurrenceCI($haystack, $needle, $before_needle = false, $mb_encoding = '') {

        // Fallback to the regular string functions if a mbstring function does not exist
        // or mbstring extension is not available
        if (self::$isMbstringLoaded === false || empty(self::$mbstringFunction['mb_stristr'])) {
            return stristr($haystack, $needle, $before_needle);
        } elseif (self::$isMbstringLoaded === null) {
            self::mbStringLoading();
        }

        if ($mb_encoding == '') {
            $mb_encoding = cRegistry::getEncoding();
        }

        return mb_stristr($haystack, $needle, $before_needle, $mb_encoding);
    }

    /**
     * Finds first occurrence of a string within another, case insensitive
     *
     * @param string $haystack
     * @param string $needle
     * @param boolean $part [Optional]
     * @param mixed $mb_encoding encoding parameter, standard: cRegistry::getEncoding()
     * @return Returns the numeric position of the last occurrence of needle in the haystack string
     * @link http://php.net/manual/de/function.mb-strrchr.php
     */
    public static function findLastOccurrence($haystack, $needle, $part = false, $mb_encoding = '') {

        // Fallback to the regular string functions if a mbstring function does not exist
        // or mbstring extension is not available
        if (self::$isMbstringLoaded === false || empty(self::$mbstringFunction['mb_stristr'])) {
            return strrchr($haystack, $needle, $part);
        } elseif (self::$isMbstringLoaded === null) {
            self::mbStringLoading();
        }

        if ($mb_encoding == '') {
            $mb_encoding = cRegistry::getEncoding();
        }

        return mb_strrchr($haystack, $needle, part, $mb_encoding);
    }

    /**
     * Regular expression match
     *
     * @param string $pattern
     * @param string $string
     * @param array $regs [Optional]
     * @return Returns Returns the byte length of the matched string if a match for pattern was found in string
     * @link http://php.net/manual/de/function.mb-ereg.php
     */
    public static function ereg($pattern, $string, &$regs = array()) {
        return mb_ereg($pattern, $string, $regs);
    }

    /**
     * Regular expression match ignoring case
     *
     * @param string $pattern
     * @param string $string
     * @param array $regs [Optional]
     * @return Returns the byte length of the matched string if a match for pattern was found in string
     * @link http://php.net/manual/de/function.mb-ereg.php
     */
    public static function eregi($pattern, $string, &$regs = array()) {
        return mb_eregi($pattern, $string, $regs);
    }

    /**
     * Replace regular expression
     *
     * @param string $pattern
     * @param string $replacement
     * @param string $string
     * @param string $option [Optional]
     * @return Returns the byte length of the matched string if a match for pattern was found in string
     * @link http://php.net/manual/de/function.mb-ereg-replace.php
     */
    public static function ereg_replace($pattern, $replacement, $string, $option = 'msr') {
        return mb_ereg_replace($pattern, $replacement, $string, $option);
    }

    /**
     * Replace regular expression ignoring case
     *
     * @param string $pattern
     * @param string $replacement
     * @param string $string
     * @param string $option [Optional]
     * @return Returns the byte length of the matched string if a match for pattern was found in string
     * @link http://php.net/manual/de/function.mb-eregi-replace.php
     */
    public static function eregi_replace($pattern, $replacement, $string, $option = 'msr') {
        return mb_eregi_replace($pattern, $replacement, $string, $option);
    }

    /**
     * Split string using regular expression
     *
     * @param string $pattern
     * @param string $string
     * @param integer $limit [Optional]
     * @return The result as an array
     * @link http://php.net/manual/de/function.mb-split.php
     */
    public static function split($pattern, $string, $limit = -1) {
        return mb_split($pattern, $string, $limit);
    }
}