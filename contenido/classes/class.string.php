<?php
/**
 * This file contains the string utility class.
 *
 * @package    Core
 * @subpackage Util
 * @version    SVN Revision $Rev:$
 *
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * String helper class.
 *
 * @package Core
 * @subpackage Util
 */
class cString {

    /**
     * Replaces a string only once
     *
     * Caution: This function only takes strings as parameters, not arrays!
     *
     * @param  string  $find  String to find
     * @param  string  $replace  String to replace
     * @param  string  $subject String to process
     * @return string Processed string
     */
    public static function iReplaceOnce($find, $replace, $subject) {
        $start = strpos(strtolower($subject), strtolower($find));

        if ($start === false) {
            return $subject;
        }

        $end = $start + strlen($find);
        $first = substr($subject, 0, $start);
        $last = substr($subject, $end, strlen($subject) - $end);

        $result = $first . $replace . $last;

        return $result;
    }

    /**
     * Replaces a string only once, in reverse direction
     *
     * Caution: This function only takes strings as parameters, not arrays!
     *
     * @param  string  $find  String to find
     * @param  string  $replace  String to replace
     * @param  string  $subject  String to process
     * @return string Processed string
     */
    public static function iReplaceOnceReverse($find, $replace, $subject) {
        $start = self::posReverse(strtolower($subject), strtolower($find));

        if ($start === false) {
            return $subject;
        }

        $end = $start + strlen($find);

        $first = substr($subject, 0, $start);
        $last = substr($subject, $end, strlen($subject) - $end);

        $result = $first . $replace . $last;

        return ($result);
    }

    /**
     * Finds a string position in reverse direction
     *
     * NOTE: The original strrpos-Function of PHP4 only finds a single character as needle.
     *
     * @param  string  $haystack   String to search in
     * @param  string  $needle     String to search for
     * @param  int     $start     Offset
     * @return string Processed string
     */
    public static function posReverse($haystack, $needle, $start = 0) {
        $tempPos = strpos($haystack, $needle, $start);

        if ($tempPos === false) {
            if ($start == 0) {
                // Needle not in string at all
                return false;
            } else {
                // No more occurances found
                return $start - strlen($needle);
            }
        } else {
            // Find the next occurance
            return self::posReverse($haystack, $needle, $tempPos + strlen($needle));
        }
    }

    /**
     * Adds slashes to passed variable or array.
     *
     * @param   string|array  $value  Either a string or a multi-dimensional array of values
     * @return  string|array
     */
    public static function addSlashes($value) {
        $value = is_array($value) ? array_map(array('cString', 'addSlashes'), $value) : addslashes($value);
        return $value;
    }

    /**
     * Removes slashes from passed variable or array.
     *
     * @param   string|array  $value  Either a string or a multi-dimensional array of values
     * @return  string|array
     */
    public static function stripSlashes($value) {
        $value = is_array($value) ? array_map(array('cString', 'stripSlashes'), $value) : stripslashes($value);
        return $value;
    }

    /**
     * Checks if the string haystack ends with needle
     *
     * @param   string  $haystack  The string to check
     * @param   string  $needle    The string with which it should end
     * @return  bool
     */
    public static function endsWith($haystack, $needle) {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    public function contains($haystack, $needle) {
        return !(strpos($haystack, $needle) === false);
    }
}
