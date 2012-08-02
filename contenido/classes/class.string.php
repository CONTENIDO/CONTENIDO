<?php
/**
 * Contains CONTENIDO string class file
 *
 * @package Core
 * @subpackage String
 * @version SVN Revision $Rev:$
 * @version SVN Id $Id$
 *
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * String helper class.
 *
 * @package Core
 * @subpackage String
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
        $start = str_rpos(strtolower($subject), strtolower($find));

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
                //Needle not in string at all
                return false;
            } else {
                //No more occurances found
                return $start - strlen($needle);
            }
        } else {
            //Find the next occurance
            return str_rpos($haystack, $needle, $tempPos + strlen($needle));
        }
    }

}

