<?php

/**
 * This file contains the string utility class.
 *
 * @package    Core
 * @subpackage Util
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * String helper class.
 *
 * @package    Core
 * @subpackage Util
 */
class cString extends cStringMultiByteWrapper
{

    /**
     * Replaces a string only once.
     *
     * Caution: This function only takes strings as parameters, not arrays!
     *
     * @param string $find String to find
     * @param string $replace String to replace
     * @param string $subject String to process
     */
    public static function iReplaceOnce(string $find, string $replace, string $subject): string
    {
        $start = parent::findFirstPos(parent::toLowerCase($subject), parent::toLowerCase($find));
        if ($start === false) {
            return $subject;
        }

        $end = $start + parent::getStringLength($find);
        $first = parent::getPartOfString($subject, 0, $start);
        $last = parent::getPartOfString($subject, $end, parent::getStringLength($subject) - $end);

        return $first . $replace . $last;
    }

    /**
     * Replaces a string only once, in reverse direction.
     *
     * Caution: This function only takes strings as parameters, not arrays!
     *
     * @param string $find String to find
     * @param string $replace String to replace
     * @param string $subject String to process
     */
    public static function iReplaceOnceReverse(string $find, string $replace, string $subject): string
    {
        $start = self::posReverse(parent::toLowerCase($subject), parent::toLowerCase($find));
        if ($start === false) {
            return $subject;
        }

        $end = $start + parent::getStringLength($find);
        $first = parent::getPartOfString($subject, 0, $start);
        $last = parent::getPartOfString($subject, $end, parent::getStringLength($subject) - $end);

        return $first . $replace . $last;
    }

    /**
     * Finds a string position in reverse direction.
     *
     * NOTE: The original cString::findLastPos-function of PHP4 only finds a single character
     * as needle.
     *
     * @param string $haystack String to search in
     * @param string $needle String to search for
     * @param int $start Offset
     * @return int|false String position or false
     */
    public static function posReverse(string $haystack, string $needle, int $start = 0)
    {
        $tempPos = parent::findFirstPos($haystack, $needle, $start);

        if ($tempPos === false) {
            if ($start == 0) {
                // Needle not in string at all
                return false;
            } else {
                // No more occurrences found
                return $start - parent::getStringLength($needle);
            }
        } else {
            // Find the next occurrence
            return self::posReverse($haystack, $needle, $tempPos + parent::getStringLength($needle));
        }
    }

    /**
     * Adds slashes to passed variable or array.
     *
     * @param string|array $value
     *         Either a string or a multidimensional array of values
     * @return string|array
     */
    public static function addSlashes($value)
    {
        return is_array($value)
            ? array_map(function($item) { return cString::addSlashes($item); }, $value)
            : addslashes($value);
    }

    /**
     * Removes slashes from passed variable or array.
     *
     * @param string|array $value
     *         Either a string or a multidimensional array of values
     * @return string|array
     */
    public static function stripSlashes($value)
    {
        return is_array($value)
            ? array_map(function($item) { return cString::stripSlashes($item); }, $value)
            : stripslashes($value);
    }

    /**
     * Checks if the string haystack ends with needle.
     *
     * @param string $haystack The string to check
     * @param string $needle The string with which it should end
     */
    public static function endsWith(string $haystack, string $needle): bool
    {
        $length = parent::getStringLength($needle);
        if ($length == 0) {
            return true;
        }

        return parent::getPartOfString($haystack, -$length) === $needle;
    }

    /**
     * Returns true if needle can be found in haystack.
     *
     * @param string $haystack String to be searched
     * @param string $needle String to search for
     */
    public static function contains(string $haystack, string $needle): bool
    {
        return !(parent::findFirstPos($haystack, $needle) === false);
    }

    /**
     * Implementation of PHP 5.3's strstr with beforeNeedle.
     *
     * @param string $haystack String to be searched
     * @param string $needle String to search for
     * @param bool $beforeNeedle If true, return everything BEFORE needle
     * @return string|false
     * @link https://php.net/manual/de/function.mb-strstr.php
     * @link https://php.net/manual/de/function.strstr.php
     */
    public static function strstr(string $haystack, string $needle, bool $beforeNeedle = false)
    {
        if (!$beforeNeedle) {
            if (self::_functionExists('mb_strstr')) {
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
     * @param string $format Format according to date function specification
     */
    public static function validateDateFormat(string $format): bool
    {
        // try to create a DateTime instance based on php's date function format specification
        // return true if date is valid (no wrong format)
        return false !== DateTime::createFromFormat($format, date($format, time()));
    }

    /**
     * Extract a number from a string.
     *
     * @param string $string String var by reference
     */
    public static function extractNumber(string $string): string
    {
        return preg_replace('/[^0-9]/', '', $string);
    }

    /**
     * Returns whether a string is UTF-8 encoded or not.
     */
    public static function isUtf8(string $input): bool
    {
        $len = parent::getStringLength($input);

        for ($i = 0; $i < $len; $i++) {
            $char = ord($input[$i]);

            if ($char < 0x80) {
                // ASCII char
                continue;
            } elseif (($char & 0xE0) === 0xC0 && $char > 0xC1) {
                // 2 byte long char
                $n = 1;
            } elseif (($char & 0xF0) === 0xE0) {
                // 3 byte long char
                $n = 2;
            } elseif (($char & 0xF8) === 0xF0 && $char < 0xF5) {
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
     * @param bool $umlauts Flag to use german umlauts
     */
    public static function isAlphanumeric(string $test, bool $umlauts = true): bool
    {
        if ($umlauts) {
            $match = "/^[a-z0-9ÄäÖöÜüß ]+$/i";
        } else {
            $match = "/^[a-z0-9 ]+$/i";
        }

        return preg_match($match, $test);
    }

    /**
     * Trims a string to a given length and makes sure that all words up to
     * $maxLength are preserved, without exceeding $maxLength.
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
     * boundaries and doesn't operate beyond the limit given by $maxLength.
     *
     * @param string $string The string to operate on
     * @param int $maxLength The maximum number of characters
     */
    public static function trimAfterWord(string $string, int $maxLength): string
    {
        // If the string is smaller than the maximum length, it makes no sense to
        // process it any further. Return it.
        if (parent::getStringLength($string) < $maxLength) {
            return $string;
        }

        // If the character after the $maxLength position is a space, we can return
        // the string until $maxLength.
        if (parent::getPartOfString($string, $maxLength, 1) == ' ') {
            return parent::getPartOfString($string, 0, $maxLength);
        }

        // Cut the string up to $maxLength so we can use cString::findLastPos (reverse str position)
        $truncatedString = parent::getPartOfString($string, 0, $maxLength);

        // Extract the end of the last word
        $lastPos = cString::findLastPos($truncatedString, ' ');

        return parent::getPartOfString($truncatedString, 0, $lastPos);
    }

    /**
     * Trims a string to a specific length.
     *
     * If the string is longer than $maxLength, dots are inserted ("...") right
     * before $maxLength.
     *
     * Example:
     * $string = "This is a simple test";
     * echo cString::trimHard ($string, 15);
     *
     * This would output "This is a si...", since the string is longer than
     * $maxLength and the resulting string matches 15 characters including the dots.
     *
     * @param string $string The string to operate on
     * @param int $maxLength The maximum number of characters
     */
    public static function trimHard(string $string, int $maxLength, string $fillup = '...'): string
    {
        // If the string is smaller than the maximum length, it makes no sense to
        // process it any further. Return it.
        if (parent::getStringLength($string) < $maxLength) {
            return $string;
        }

        // Calculate the maximum text length
        $maxTextLength = $maxLength - parent::getStringLength($fillup);

        // If text length is over zero cut it
        if ($maxTextLength > 0) {
            if (preg_match('/^.{0,' . $maxTextLength . '}/u', $string, $matches)) {
                $truncatedString = $matches[0];
            } else {
                $truncatedString = parent::getPartOfString($string, 0, $maxTextLength);
            }
        } else {
            $truncatedString = $string;
        }

        // Append the fillup string
        return $truncatedString . $fillup;
    }

    /**
     * Trims a string to an approximate length preserving sentence boundaries.
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
     * @param string $string The string to operate on
     * @param int $approxLength The approximate number of characters
     * @param bool $hard If true, use a hard limit for the number of characters
     */
    public static function trimSentence(string $string, int $approxLength, bool $hard = false): string
    {
        // If the string is smaller than the maximum length, it makes no sense to
        // process it any further. Return it.
        if (parent::getStringLength($string) < $approxLength) {
            return $string;
        }

        // Find out the start of the next sentence
        $nextSentenceStart = parent::findFirstPos($string, '.', $approxLength);

        // If there's no next sentence (somebody forgot the dot?), set it to the end
        // of the string.
        if ($nextSentenceStart === false) {
            $nextSentenceStart = parent::getStringLength($string);
        }

        // Cut the previous sentence so we can use cString::findLastPos
        $previousSentenceTruncated = parent::getPartOfString($string, 0, $approxLength);

        // Get out the previous sentence start
        $previousSentenceStart = cString::findLastPos($previousSentenceTruncated, '.');

        // If the sentence doesn't contain a dot, use the text start.
        if ($previousSentenceStart === false) {
            $previousSentenceStart = 0;
        }

        // If we have a hard limit, we only want to process everything before
        // $approxLength
        if ($hard && $nextSentenceStart > $approxLength) {
            return parent::getPartOfString($string, 0, $previousSentenceStart + 1);
        }

        // Calculate next and previous sentence distances
        $previousSentenceDistance = $approxLength - $previousSentenceStart;
        $nextSentenceDistance = $nextSentenceStart - $approxLength;

        // Sanity: Return at least one sentence.
        $sanity = parent::getPartOfString($string, 0, $previousSentenceStart + 1);

        if (parent::findFirstPos($sanity, '.') === false) {
            return parent::getPartOfString($string, 0, $nextSentenceStart + 1);
        }

        // Decide whether the next or previous sentence is nearer
        if ($previousSentenceDistance > $nextSentenceDistance) {
            return parent::getPartOfString($string, 0, $nextSentenceStart + 1);
        } else {
            return parent::getPartOfString($string, 0, $previousSentenceStart + 1);
        }
    }

    /**
     * Converts diacritics to english characters whenever possible.
     *
     * For german umlauts, this function converts the umlauts to their ASCII equivalents (e.g. ä => ae).
     *
     * For more information about diacritics, refer to
     * https://en.wikipedia.org/wiki/Diacritic
     *
     * For other languages, the diacritic marks are removed, if possible.
     *
     * @param string $string The string to operate on
     * @param string $sourceEncoding The source encoding
     * @param string $targetEncoding The target encoding
     */
    public static function replaceDiacritics(
        string $string,
        string $sourceEncoding = 'UTF-8',
        string $targetEncoding = 'UTF-8'
    ) : string {
        if ($sourceEncoding != 'UTF-8') {
            $string = self::recodeString($string, $sourceEncoding, 'UTF-8');
        }

        // TODO The line below from the `intl` extension would do a better job than `iconv()`.
        //      It deals also with proper replacement of german umlauts.
        //$string = transliterator_transliterate('Any-Latin; Latin-ASCII', $string);

        // First, replace regular german umlauts to their ascii counterparts, iconv can't handle this
        $charMap = [
            'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue',
            'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue',
            'ß' => 'ss',
        ];
        $string = strtr($string, $charMap);

        // Then, do the rest with `ìconv`
        $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);

        return self::recodeString($string, 'UTF-8', $targetEncoding);
    }

    /**
     * Removes or converts all "evil" URL characters.
     *
     * This function removes or converts all characters which can make a URL invalid.
     *
     * Clean characters include:
     * - All characters between 32 and 126 which are not alphanumeric and
     * aren't one of the following: _-.
     *
     * @param string $string The string to operate on
     * @param bool $replace If true, all "unclean" characters are replaced
     */
    public static function cleanURLCharacters(string $string, bool $replace = false): string
    {
        $string = self::replaceDiacritics($string);
        $string = str_replace(['"', "'"], '', $string);
        $string = str_replace([' ', '/', '&', '*'], '-', $string);

        $string = preg_replace('/[^\p{L}\p{N}\-_.]+/u', $replace ? '' : '-', $string);
        $string = preg_replace('/-{2,}/', '-', $string);

        return trim($string, '-');
    }

    /**
     * Converts a string to another encoding.
     *
     * This function tries to detect which function to use (either recode or iconv).
     *
     * If $sourceEncoding and $targetEncoding are the same, this function returns immediately.
     *
     * For more information about encodings, refer to
     * https://en.wikipedia.org/wiki/Character_encoding
     *
     * For more information about the supported encodings in recode, refer to
     * https://www.delorie.com/gnu/docs/recode/recode_toc.html
     *
     * Note: depending on whether recode or iconv is used, the supported
     * charsets differ. The following ones are commonly used and are most likely
     * supported by both converters:
     *
     * - ISO-8859-1 to ISO-8859-15
     * - ASCII
     * - UTF-8
     *
     * @param string $string The string to operate on
     * @param string $sourceEncoding The source encoding
     * @param string $targetEncoding The target encoding (if false, use source encoding)
     * @todo Implement a converter and charset checker to ensure compliance.
     * @todo Check if the charset names are the same for both converters
     */
    public static function recodeString(string $string, string $sourceEncoding, string $targetEncoding): string
    {
        // If sourceEncoding and targetEncoding are the same, return
        if (parent::toLowerCase($sourceEncoding) == parent::toLowerCase($targetEncoding)) {
            return $string;
        }

        // Check for the "recode" support
        if (function_exists('recode_string')) {
            return recode_string("$sourceEncoding..$targetEncoding", $string);
        }

        // NOTE: No need to check for iconv, this is done in cSystemtest
        return iconv($sourceEncoding, $targetEncoding, $string);
    }

    /**
     * Normalizes line endings in passed string.
     *
     * @param string $lineEnding Feasible values are "\r\n", "\n", "\r"
     */
    public static function normalizeLineEndings(string $string, string $lineEnding = "\n"): string
    {
        $possibleLineEndings = ["\r\n", "\r"]; // The order is critical here.
        if (!in_array($lineEnding, ["\r\n", "\n", "\r"])) {
            $lineEnding = "\n";
        }

        $string = str_replace($possibleLineEndings, "\n", $string);

        if ($lineEnding !== "\n") {
            $string = str_replace("\n", $lineEnding, $string);
        }

        return $string;
    }
}