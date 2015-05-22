<?php

/**
 * This file contains CONTENIDO String API functions.
 *
 * If you are planning to add a function, please make sure that:
 * 1.) The function is in the correct place
 * 2.) The function is documented
 * 3.) The function makes sense and is generically usable
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *
 * @author Timo Hummel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Trims a string to a given length and makes sure that all words up to $maxlen
 * are preserved, without exceeding $maxlen.
 *
 * Warning: Currently, this function uses a regular ASCII-Whitespace to do the
 * separation test. If you are using '&nbsp' to create spaces, this function
 * will fail.
 *
 * Example:
 * $string = "This is a simple test";
 * echo cApiStrTrimAfterWord ($string, 15);
 *
 * This would output "This is a", since this function respects word boundaries
 * and doesn't operate beyond the limit given by $maxlen.
 *
 * @deprecated [2015-05-21]
 *         use cString::trimAfterWord
 * @param string $string
 *         The string to operate on
 * @param int $maxlen
 *         The maximum number of characters
 * @return string
 *         The resulting string
 */
function cApiStrTrimAfterWord($string, $maxlen) {
    cDeprecated('This method is deprecated and is not needed any longer');
    return cString::trimAfterWord($string, $maxlen);
}

/**
 * Trims a string to a specific length.
 * If the string is longer than $maxlen,
 * dots are inserted ("...") right before $maxlen.
 *
 * Example:
 * $string = "This is a simple test";
 * echo cApiStrTrimHard ($string, 15);
 *
 * This would output "This is a si...", since the string is longer than $maxlen
 * and the resulting string matches 15 characters including the dots.
 *
 * @deprecated [2015-05-21]
 *         use cString::trimHard() instead
 * @param string $string
 *         The string to operate on
 * @param int $maxlen
 *         The maximum number of characters
 * @param string $fillup
 * @return string
 *         The resulting string
 */
function cApiStrTrimHard($string, $maxlen, $fillup = '...') {
    cDeprecated('This method is deprecated and is not needed any longer');
    return cString::trimHard($string, $maxlen, fillup);
}

/**
 * Trims a string to a approximate length.
 * Sentence boundaries are preserved.
 *
 * The algorythm inside calculates the sentence length to the previous and next
 * sentences. The distance to the next sentence which is smaller will be taken
 * to
 * trim the string to match the approximate length parameter.
 *
 * Example:
 *
 * $string = "This contains two sentences. ";
 * $string .= "Lets play around with them. ";
 *
 * echo cApiStrTrimSentence($string, 40);
 * echo cApiStrTrimSentence($string, 50);
 *
 * The first example would only output the first sentence, the second example
 * both
 * sentences.
 *
 * Explanation:
 *
 * To match the given max length closely, the function calculates the distance
 * to
 * the next and previous sentences. Using the maxlength of 40 characters, the
 * distance to the previous sentence would be 8 characters, and to the next
 * sentence
 * it would be 19 characters. Therefore, only the previous sentence is
 * displayed.
 *
 * The second example displays the second sentence also, since the distance to
 * the
 * next sentence is only 9 characters, but to the previous it is 18 characters.
 *
 * If you specify the boolean flag "$hard", the limit parameter creates a hard
 * limit
 * instead of calculating the distance.
 *
 * This function ensures that at least one sentence is returned.
 *
 * @deprecated [2015-05-21]
 *         use cString::trimSentence
 * @param string $string
 *         The string to operate on
 * @param int $approxlen
 *         The approximate number of characters
 * @param bool $hard
 *         If true, use a hard limit for the number of characters
 * @return string
 *         The resulting string
 */
function cApiStrTrimSentence($string, $approxlen, $hard = false) {
    cDeprecated('This method is deprecated and is not needed any longer');
    return cString::trimSentence($string, $approxlen, $hard);
}

/**
 * Converts diactritics to english characters whenever possible.
 *
 * For german umlauts, this function converts the umlauts to their ASCII
 * equalients (e.g. ä => ae).
 *
 * For more information about diacritics, refer to
 * http://en.wikipedia.org/wiki/Diacritic
 *
 * For other languages, the diacritic marks are removed, if possible.
 *
 * @deprecated [2015-05-21]
 *         use cString::replaceDiacritics
 * @param string $sString
 *         The string to operate on
 * @param string $sourceEncoding
 *         The source encoding (default: UTF-8)
 * @param string $targetEncoding
 *         The target encoding (default: UTF-8)
 * @return string
 *         The resulting string
 */
function cApiStrReplaceDiacritics($sString, $sourceEncoding = 'UTF-8', $targetEncoding = 'UTF-8') {
    cDeprecated('This method is deprecated and is not needed any longer');
    return cString::replaceDiacritics($sString, $sourceEncoding, $targetEncoding);
}

/**
 * Converts a string to another encoding.
 * This function tries to detect which function
 * to use (either recode or iconv).
 *
 * If $sourceEncoding and $targetEncoding are the same, this function returns
 * immediately.
 *
 * For more information about encodings, refer to
 * http://en.wikipedia.org/wiki/Character_encoding
 *
 * For more information about the supported encodings in recode, refer to
 * http://www.delorie.com/gnu/docs/recode/recode_toc.html
 *
 * Note: depending on whether recode or iconv is used, the supported charsets
 * differ. The following ones are commonly used and are most likely supported by
 * both converters:
 *
 * - ISO-8859-1 to ISO-8859-15
 * - ASCII
 * - UTF-8
 *
 * @deprecated [2015-05-21]
 *         use cString::recodeString
 * @todo Check if the charset names are the same for both converters
 * @todo Implement a converter and charset checker to ensure compilance.
 * @param string $sString
 *         The string to operate on
 * @param string $sourceEncoding
 *         The source encoding (default: ISO-8859-1)
 * @param string $targetEncoding
 *         The target encoding (if false, use source encoding)
 * @return string
 *         The resulting string
 */
function cApiStrRecodeString($sString, $sourceEncoding, $targetEncoding) {
    cDeprecated('This method is deprecated and is not needed any longer');
    return cString::recodeString($sString, $sourceEncoding, $targetEncoding);
}

/**
 * Removes or converts all "evil" URL characters.
 * This function removes or converts
 * all characters which can make an URL invalid.
 *
 * Clean characters include:
 * - All characters between 32 and 126 which are not alphanumeric and
 * aren't one of the following: _-.
 *
 * @deprecated [2015-05-21]
 *         use cString::cleanURLCharacters
 * @param string $sString
 *         The string to operate on
 * @param bool $bReplace
 *         If true, all "unclean" characters are replaced
 * @return string
 *         The resulting string
 */
function cApiStrCleanURLCharacters($sString, $bReplace = false) {
    cDeprecated('This method is deprecated and is not needed any longer');
    return cString::cleanURLCharacters($sString, $bReplace);
}

/**
 * Normalizes line endings in passed string.
 *
 * @deprecated [2015-05-21]
 *         use cString::normalizeLineEndings
 * @param string $sString
 * @param string $sLineEnding
 *         Feasible values are "\n", "\r" or "\r\n"
 * @return string
 */
function cApiStrNormalizeLineEndings($sString, $sLineEnding = "\n") {
    cDeprecated('This method is deprecated and is not needed any longer');
    return cString::normalizeLineEndings($sString, $sLineEnding);
}
