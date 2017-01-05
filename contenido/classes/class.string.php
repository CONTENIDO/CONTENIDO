<?php
/**
 * This file contains the string utility class.
 *
 * @package    Core
 * @subpackage Util
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
class cString extends cStringWrapper {

    /**
     * Replaces a string only once.
     *
     * Caution: This function only takes strings as parameters, not arrays!
     *
     * @param string $find
     *         String to find
     * @param string $replace
     *         String to replace
     * @param string $subject
     *         String to process
     * @return string
     *         Processed string
     */
    public static function iReplaceOnce($find, $replace, $subject) {
        $start = parent::findFirstPos(parent::toLowerCase($subject), parent::toLowerCase($find));

        if ($start === false) {
            return $subject;
        }

        $end = $start + parent::getStringLength($find);
        $first = parent::getPartOfString($subject, 0, $start);
        $last = parent::getPartOfString($subject, $end, parent::getStringLength($subject) - $end);

        $result = $first . $replace . $last;

        return $result;
    }

    /**
     * Replaces a string only once, in reverse direction.
     *
     * Caution: This function only takes strings as parameters, not arrays!
     *
     * @param string $find
     *         String to find
     * @param string $replace
     *         String to replace
     * @param string $subject
     *         String to process
     * @return string
     *         Processed string
     */
    public static function iReplaceOnceReverse($find, $replace, $subject) {
        $start = self::posReverse(parent::toLowerCase($subject), parent::toLowerCase($find));

        if ($start === false) {
            return $subject;
        }

        $end = $start + parent::getStringLength($find);

        $first = parent::getPartOfString($subject, 0, $start);
        $last = parent::getPartOfString($subject, $end, parent::getStringLength($subject) - $end);

        $result = $first . $replace . $last;

        return $result;
    }

    /**
     * Finds a string position in reverse direction.
     *
     * NOTE: The original cString::findLastPos-function of PHP4 only finds a single character
     * as needle.
     *
     * @param string $haystack
     *         String to search in
     * @param string $needle
     *         String to search for
     * @param int $start [optional]
     *         Offset
     * @return int
     *         String position
     */
    public static function posReverse($haystack, $needle, $start = 0) {
        $tempPos = parent::findFirstPos($haystack, $needle, $start);

        if ($tempPos === false) {
            if ($start == 0) {
                // Needle not in string at all
                return false;
            } else {
                // No more occurances found
                return $start - parent::getStringLength($needle);
            }
        } else {
            // Find the next occurance
            return self::posReverse($haystack, $needle, $tempPos + parent::getStringLength($needle));
        }
    }

    /**
     * Adds slashes to passed variable or array.
     *
     * @param string|array $value
     *         Either a string or a multi-dimensional array of values
     * @return string|array
     */
    public static function addSlashes($value) {
        $value = is_array($value) ? array_map(array('cString', 'addSlashes'), $value) : addslashes($value);
        return $value;
    }

    /**
     * Removes slashes from passed variable or array.
     *
     * @param string|array $value
     *         Either a string or a multi-dimensional array of values
     * @return string|array
     */
    public static function stripSlashes($value) {
        $value = is_array($value) ? array_map(array('cString', 'stripSlashes'), $value) : stripslashes($value);
        return $value;
    }

    /**
     * Checks if the string haystack ends with needle.
     *
     * @param string $haystack
     *         The string to check
     * @param string $needle
     *         The string with which it should end
     * @return bool
     */
    public static function endsWith($haystack, $needle) {
        $length = parent::getStringLength($needle);
        if ($length == 0) {
            return true;
        }

        return parent::getPartOfString($haystack, -$length) === $needle;
    }

    /**
     * Returns true if needle can be found in haystack.
     *
     * @param string $haystack
     *         String to be searched
     * @param string $needle
     *         String to search for
     * @return bool
     */
    public static function contains($haystack, $needle) {
        return !(parent::findFirstPos($haystack, $needle) === false);
    }

    /**
     * Implementation of PHP 5.3's strstr with beforeNeedle.
     *
     * @param string $haystack
     *         String to be searched
     * @param string $needle
     *         String to search for
     * @param string $beforeNeedle [optional]
     *         If true, return everything BEFORE needle
     * @return string
     * @link http://php.net/manual/de/function.mb-strstr.php
     * @link http://php.net/manual/de/function.strstr.php
     */
    public static function strstr($haystack, $needle, $beforeNeedle = false) {

        if (!$beforeNeedle) {
            if (parent::$isMbstringLoaded === true && isset(parent::$mbstringFunction['mb_strstr'])) {
                return mb_strstr($haystack, $needle);
            } else {
                return strstr($haystack, $needle);
            }
        } else {
            return strtok($haystack, $needle);
        }
    }

    /**
     * This function checks if a given format is accepted by php's date function.
     *
     * @param string $format
     *         format according to date function specification
     * @return bool
     *         true if format is correct, false otherwise
     */
    public static function validateDateFormat($format) {
        // try to create a DateTime instance based on php's date function format specification
        // return true if date is valid (no wrong format)
        return false !== DateTime::createFromFormat($format, date($format, time()));
    }

    /**
     * Extract a number from a string.
     *
     * @param string $string
     *         String var by reference
     * @return string
     */
    public static function extractNumber(&$string) {
        $string = preg_replace('/[^0-9]/', '', $string);
        return $string;
    }


    /**
     * Returns whether a string is UTF-8 encoded or not.
     *
     * @param string $input
     * @return bool
     */
    public static function isUtf8($input) {
        $len = parent::getStringLength($input);

        for ($i = 0; $i < $len; $i++) {
            $char = ord($input[$i]);
            $n = 0;

            if ($char < 0x80) {
                // ASCII char
                continue;
            } else if (($char & 0xE0) === 0xC0 && $char > 0xC1) {
                // 2 byte long char
                $n = 1;
            } else if (($char & 0xF0) === 0xE0) {
                // 3 byte long char
                $n = 2;
            } else if (($char & 0xF8) === 0xF0 && $char < 0xF5) {
                // 4 byte long char
                $n = 3;
            } else {
                return false;
            }

            for ($j = 0; $j < $n; $j++) {
                $i++;

                if ($i == $len || (ord($input[$i]) & 0xC0) !== 0x80) {
                    return false;
                }
            }
        }
        return true;
    }


    /**
     * Checks if a value is alphanumeric.
     *
     * @param mixed $test
     *         Value to test
     * @param bool $umlauts [optional]
     *         Use german umlauts
     * @return bool
     *         Value is alphanumeric
     */
    public static function isAlphanumeric($test, $umlauts = true) {
        if ($umlauts == true) {
            $match = "/^[a-z0-9ÄäÖöÜüß ]+$/i";
        } else {
            $match = "/^[a-z0-9 ]+$/i";
        }

        return preg_match($match, $test);
    }

    /**
     * Trims a string to a given length and makes sure that all words up to
     * $maxlen are preserved, without exceeding $maxlen.
     *
     * Warning: Currently, this function uses a regular ASCII-Whitespace to do
     * the separation test. If you are using '&nbsp' to create spaces, this
     * function will fail.
     *
     * Example:
     * $string = "This is a simple test";
     * echo cString::trimAfterWord($string, 15);
     *
     * This would output "This is a", since this function respects word
     * boundaries and doesn't operate beyond the limit given by $maxlen.
     *
     * @param string $string
     *         The string to operate on
     * @param int $maxlen
     *         The maximum number of characters
     * @return string
     *         The resulting string
     */
    public static function trimAfterWord($string, $maxlen) {
        // If the string is smaller than the maximum lenght, it makes no sense to
        // process it any further. Return it.
        if (parent::getStringLength($string) < $maxlen) {
            return $string;
        }

        // If the character after the $maxlen position is a space, we can return
        // the string until $maxlen.
        if (parent::getPartOfString($string, $maxlen, 1) == ' ') {
            return parent::getPartOfString($string, 0, $maxlen);
        }

        // Cut the string up to $maxlen so we can use cString::findLastPos (reverse str position)
        $cutted_string = parent::getPartOfString($string, 0, $maxlen);

        // Extract the end of the last word
        $last_word_position = cString::findLastPos($cutted_string, ' ');

        return parent::getPartOfString($cutted_string, 0, $last_word_position);
    }

    /**
     * Trims a string to a specific length.
     *
     * If the string is longer than $maxlen, dots are inserted ("...") right
     * before $maxlen.
     *
     * Example:
     * $string = "This is a simple test";
     * echo cString::trimHard ($string, 15);
     *
     * This would output "This is a si...", since the string is longer than
     * $maxlen and the resulting string matches 15 characters including the dots.
     *
     * @param string $string
     *         The string to operate on
     * @param int $maxlen
     *         The maximum number of characters
     * @param string $fillup [optional]
     * @return string
     *         The resulting string
     */
    public static function trimHard($string, $maxlen, $fillup = '...') {
        // If the string is smaller than the maximum lenght, it makes no sense to
        // process it any further. Return it.
        if (parent::getStringLength($string) < $maxlen) {
            return $string;
        }

        // Calculate the maximum text length
        $maximum_text_length = $maxlen - parent::getStringLength($fillup);

        // If text length is over zero cut it
        if ($maximum_text_length > 0) {
            if (preg_match('/(*UTF8)^.{0,' . $maximum_text_length . '}/', $string, $result_array)) {
                $cutted_string = $result_array[0];
            } else if (preg_match('/^.{0,' . $maximum_text_length . '}/u', $string, $result_array)) {
                $cutted_string = $result_array[0];
            } else {
                $cutted_string = parent::getPartOfString($string, 0, $maximum_text_length);
            }
        } else {
            $cutted_string = $string;
        }

        // Append the fillup string
        $cutted_string .= $fillup;

        return $cutted_string;
    }

    /**
     * Trims a string to a approximate length preserving sentence boundaries.
     *
     * The algorithm inside calculates the sentence length to the previous and
     * next sentences. The distance to the next sentence which is smaller will
     * be taken to trim the string to match the approximate length parameter.
     *
     * Example:
     *
     * $string = "This contains two sentences. ";
     * $string .= "Lets play around with them. ";
     *
     * echo cString::trimSentence($string, 40);
     * echo cString::trimSentence($string, 50);
     *
     * The first example would only output the first sentence, the second
     * example both sentences.
     *
     * Explanation:
     *
     * To match the given max length closely, the function calculates the
     * distance to the next and previous sentences. Using the maxlength of 40
     * characters, the distance to the previous sentence would be 8 characters,
     * and to the next sentence it would be 19 characters. Therefore, only the
     * previous sentence is displayed.
     *
     * The second example displays the second sentence also, since the distance
     * to the next sentence is only 9 characters, but to the previous it is 18
     * characters.
     *
     * If you specify the boolean flag "$hard", the limit parameter creates a
     * hard limit instead of calculating the distance.
     *
     * This function ensures that at least one sentence is returned.
     *
     * @param string $string
     *         The string to operate on
     * @param int $approxlen
     *         The approximate number of characters
     * @param bool $hard [optional]
     *         If true, use a hard limit for the number of characters
     * @return string
     *         The resulting string
     */
    public static function trimSentence($string, $approxlen, $hard = false) {
        // If the string is smaller than the maximum lenght, it makes no sense to
        // process it any further. Return it.
        if (parent::getStringLength($string) < $approxlen) {
            return $string;
        }

        // Find out the start of the next sentence
        $next_sentence_start = parent::findFirstPos($string, '.', $approxlen);

        // If there's no next sentence (somebody forgot the dot?), set it to the end
        // of the string.
        if ($next_sentence_start === false) {
            $next_sentence_start = parent::getStringLength($string);
        }

        // Cut the previous sentence so we can use cString::findLastPos
        $previous_sentence_cutted = parent::getPartOfString($string, 0, $approxlen);

        // Get out the previous sentence start
        $previous_sentence_start = cString::findLastPos($previous_sentence_cutted, '.');

        // If the sentence doesn't contain a dot, use the text start.
        if ($previous_sentence_start === false) {
            $previous_sentence_start = 0;
        }

        // If we have a hard limit, we only want to process everything before
        // $approxlen
        if (($hard == true) && ($next_sentence_start > $approxlen)) {
            return parent::getPartOfString($string, 0, $previous_sentence_start + 1);
        }

        // Calculate next and previous sentence distances
        $distance_previous_sentence = $approxlen - $previous_sentence_start;
        $distance_next_sentence = $next_sentence_start - $approxlen;

        // Sanity: Return at least one sentence.
        $sanity = parent::getPartOfString($string, 0, $previous_sentence_start + 1);

        if (parent::findFirstPos($sanity, '.') === false) {
            return parent::getPartOfString($string, 0, $next_sentence_start + 1);
        }

        // Decide wether the next or previous sentence is nearer
        if ($distance_previous_sentence > $distance_next_sentence) {
            return parent::getPartOfString($string, 0, $next_sentence_start + 1);
        } else {
            return parent::getPartOfString($string, 0, $previous_sentence_start + 1);
        }
    }

    /**
     * Converts diactritics to english characters whenever possible.
     *
     * For german umlauts, this function converts the umlauts to their ASCII
     * equivalents (e.g. ä => ae).
     *
     * For more information about diacritics, refer to
     * http://en.wikipedia.org/wiki/Diacritic
     *
     * For other languages, the diacritic marks are removed, if possible.
     *
     * @param string $string
     *         The string to operate on
     * @param string $sourceEncoding [optional; default: UTF-8]
     *         The source encoding
     * @param string $targetEncoding [optional; default: UTF-8]
     *         The target encoding
     * @return string
     *         The resulting string
     */
    public static function replaceDiacritics($string, $sourceEncoding = 'UTF-8', $targetEncoding = 'UTF-8') {
        if ($sourceEncoding != 'UTF-8') {
            $string = self::recodeString($string, $sourceEncoding, "UTF-8");
        }

        // replace regular german umlauts and other common characters with
        // diacritics
        static $search, $replace;
        if (!isset($search)) {
            $search = array(
                'Ä',
                'Ö',
                'Ü',
                'ä',
                'ö',
                'ü',
                'ß',
                'Á',
                'À',
                'Â',
                'á',
                'à',
                'â',
                'É',
                'È',
                'Ê',
                'é',
                'è',
                'ê',
                'Í',
                'Ì',
                'Î',
                'í',
                'ì',
                'î',
                'Ó',
                'Ò',
                'Ô',
                'ó',
                'ò',
                'ô',
                'Ú',
                'Ù',
                'Û',
                'ú',
                'ù',
                'û'
            );
            $replace = array(
                'Ae',
                'Oe',
                'Ue',
                'ae',
                'oe',
                'ue',
                'ss',
                'A',
                'A',
                'A',
                'a',
                'a',
                'a',
                'E',
                'E',
                'E',
                'e',
                'e',
                'e',
                'I',
                'I',
                'I',
                'i',
                'i',
                'i',
                'O',
                'O',
                'O',
                'o',
                'o',
                'o',
                'U',
                'U',
                'U',
                'u',
                'u',
                'u'
            );
        }
        $string = str_replace($search, $replace, $string);

        // TODO: Additional converting

        return self::recodeString($string, "UTF-8", $targetEncoding);
    }

    /**
     * Converts a string to another encoding.
     *
     * This function tries to detect which function to use (either recode or
     * iconv).
     *
     * If $sourceEncoding and $targetEncoding are the same, this function
     * returns immediately.
     *
     * For more information about encodings, refer to
     * http://en.wikipedia.org/wiki/Character_encoding
     *
     * For more information about the supported encodings in recode, refer to
     * http://www.delorie.com/gnu/docs/recode/recode_toc.html
     *
     * Note: depending on whether recode or iconv is used, the supported
     * charsets differ. The following ones are commonly used and are most likely
     * supported by both converters:
     *
     * - ISO-8859-1 to ISO-8859-15
     * - ASCII
     * - UTF-8
     *
     * @todo Check if the charset names are the same for both converters
     * @todo Implement a converter and charset checker to ensure compilance.
     * @param string $string
     *         The string to operate on
     * @param string $sourceEncoding
     *         The source encoding
     * @param string $targetEncoding
     *         The target encoding (if false, use source encoding)
     * @return string
     *         The resulting string
     */
    public static function recodeString($string, $sourceEncoding, $targetEncoding) {
        // If sourceEncoding and targetEncoding are the same, return
        if (parent::toLowerCase($sourceEncoding) == parent::toLowerCase($targetEncoding)) {
            return $string;
        }

        // Check for the "recode" support
        if (function_exists('recode')) {
            $sResult = recode_string("$sourceEncoding..$targetEncoding", $string);
            return $sResult;
        }

        // Check for the "iconv" support
        if (function_exists('iconv')) {
            $sResult = iconv($sourceEncoding, $targetEncoding, $string);
            return $sResult;
        }

        // No charset converters found; return with warning
        cWarning(__FILE__, __LINE__, 'cString::recodeString could not find either recode or iconv to do charset conversion.');
        return $string;
    }

    /**
     * Removes or converts all "evil" URL characters.
     *
     * This function removes or converts all characters which can make an URL
     * invalid.
     *
     * Clean characters include:
     * - All characters between 32 and 126 which are not alphanumeric and
     * aren't one of the following: _-.
     *
     * @param string $string
     *         The string to operate on
     * @param bool $replace [optional]
     *         If true, all "unclean" characters are replaced
     * @return string
     *         The resulting string
     */
    public static function cleanURLCharacters($string, $replace = false) {
        $string = self::replaceDiacritics($string);
        $string = str_replace(' ', '-', $string);
        $string = str_replace('/', '-', $string);
        $string = str_replace('&', '-', $string);
        $string = str_replace('+', '-', $string);

        $iStrLen = parent::getStringLength($string);

        $sResultString = '';

        for ($i = 0; $i < $iStrLen; $i++) {
            $sChar = parent::getPartOfString($string, $i, 1);

            if (preg_match('/^[a-z0-9]*$/i', $sChar) || $sChar == '-' || $sChar == '_' || $sChar == '.') {
                $sResultString .= $sChar;
            } else {
                if ($replace == true) {
                    $sResultString .= '_';
                }
            }
        }

        return $sResultString;
    }

    /**
     * Normalizes line endings in passed string.
     *
     * @param string $string
     * @param string $lineEnding [optional]
     *         Feasible values are "\n", "\r" or "\r\n"
     * @return string
     */
    public static function normalizeLineEndings($string, $lineEnding = "\n") {
        if ($lineEnding !== "\n" && $lineEnding !== "\r" && $lineEnding !== "\r\n") {
            $lineEnding = "\n";
        }

        $string = str_replace("\r\n", "\n", $string);
        $string = str_replace("\r", "\n", $string);
        if ($lineEnding !== "\n") {
            $string = str_replace("\n", $lineEnding, $string);
        }

        return $string;
    }
}