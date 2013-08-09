<?php
/**
 * This file contains CONTENIDO String API functions.
 *
 * If you are planning to add a function, please make sure that:
 * 1.) The function is in the correct place
 * 2.) The function is documented
 * 3.) The function makes sense and is generically usable
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Timo Hummel
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Trims a string to a given length and makes sure that all words up to $maxlen
 * are preserved, without exceeding $maxlen.
 *
 * Warning: Currently, this function uses a regular ASCII-Whitespace to do the
 * seperation test. If you are using '&nbsp' to create spaces, this function will fail.
 *
 * Example:
 * $string = "This is a simple test";
 * echo cApiStrTrimAfterWord ($string, 15);
 *
 * This would output "This is a", since this function respects word boundaries
 * and doesn't operate beyond the limit given by $maxlen.
 *
 * @param   string  $string  The string to operate on
 * @param   int     $maxlen  The maximum number of characters
 * @return  string  The resulting string
 */
function cApiStrTrimAfterWord($string, $maxlen) {
    // If the string is smaller than the maximum lenght, it makes no sense to
    // process it any further. Return it.
    if (strlen($string) < $maxlen) {
        return $string;
    }

    // If the character after the $maxlen position is a space, we can return
    // the string until $maxlen.
    if (substr($string, $maxlen, 1) == ' ') {
        return substr($string, 0, $maxlen);
    }

    // Cut the string up to $maxlen so we can use strrpos (reverse str position)
    $cutted_string = substr($string, 0, $maxlen);

    // Extract the end of the last word
    $last_word_position = strrpos($cutted_string, ' ');

    return (substr($cutted_string, 0, $last_word_position));
}

/**
 * Trims a string to a specific length. If the string is longer than $maxlen,
 * dots are inserted ("...") right before $maxlen.
 *
 * Example:
 * $string = "This is a simple test";
 * echo cApiStrTrimHard ($string, 15);
 *
 * This would output "This is a si...", since the string is longer than $maxlen
 * and the resulting string matches 15 characters including the dots.
 *
 * @param   string  $string  The string to operate on
 * @param   int     $maxlen  The maximum number of characters
 * @return  string  The resulting string
 */
function cApiStrTrimHard($string, $maxlen, $fillup = '...') {
    // If the string is smaller than the maximum lenght, it makes no sense to
    // process it any further. Return it.
    if (strlen($string) < $maxlen) {
        return $string;
    }

    // Calculate the maximum text length
    $maximum_text_length = $maxlen - strlen($fillup);

    // Cut it
	if (preg_match('/(*UTF8)^.{0,'.$maximum_text_length.'}/', $string ,$result_array)) {
		$cutted_string = $result_array[0];
	} else if (preg_match('/^.{0,'.$maximum_text_length.'}/u', $string ,$result_array)) {
		$cutted_string = $result_array[0];
	} else {
		 $cutted_string = substr($string, 0, $maximum_text_length);
	}

    // Append the fillup string
    $cutted_string .= $fillup;

    return ($cutted_string);
}

/**
 * Trims a string to a approximate length. Sentence boundaries are preserved.
 *
 * The algorythm inside calculates the sentence length to the previous and next
 * sentences. The distance to the next sentence which is smaller will be taken to
 * trim the string to match the approximate length parameter.
 *
 * Example:
 *
 * $string  = "This contains two sentences. ";
 * $string .= "Lets play around with them. ";
 *
 * echo cApiStrTrimSentence($string, 40);
 * echo cApiStrTrimSentence($string, 50);
 *
 * The first example would only output the first sentence, the second example both
 * sentences.
 *
 * Explanation:
 *
 * To match the given max length closely, the function calculates the distance to
 * the next and previous sentences. Using the maxlength of 40 characters, the
 * distance to the previous sentence would be 8 characters, and to the next sentence
 * it would be 19 characters. Therefore, only the previous sentence is displayed.
 *
 * The second example displays the second sentence also, since the distance to the
 * next sentence is only 9 characters, but to the previous it is 18 characters.
 *
 * If you specify the boolean flag "$hard", the limit parameter creates a hard limit
 * instead of calculating the distance.
 *
 * This function ensures that at least one sentence is returned.
 *
 * @param  string  $string     The string to operate on
 * @param  int     $approxlen  The approximate number of characters
 * @param  bool    $hard       If true, use a hard limit for the number of characters
 * @return string  The resulting string
 */
function cApiStrTrimSentence($string, $approxlen, $hard = false) {
    // If the string is smaller than the maximum lenght, it makes no sense to
    // process it any further. Return it.
    if (strlen($string) < $approxlen) {
        return $string;
    }

    // Find out the start of the next sentence
    $next_sentence_start = strpos($string, '.', $approxlen);

    // If there's no next sentence (somebody forgot the dot?), set it to the end
    // of the string.
    if ($next_sentence_start === false) {
        $next_sentence_start = strlen($string);
    }

    // Cut the previous sentence so we can use strrpos
    $previous_sentence_cutted = substr($string, 0, $approxlen);

    // Get out the previous sentence start
    $previous_sentence_start = strrpos($previous_sentence_cutted, '.');

    // If the sentence doesn't contain a dot, use the text start.
    if ($previous_sentence_start === false) {
        $previous_sentence_start = 0;
    }

    // If we have a hard limit, we only want to process everything before $approxlen
    if (($hard == true) && ($next_sentence_start > $approxlen)) {
        return (substr($string, 0, $previous_sentence_start + 1));
    }

    // Calculate next and previous sentence distances
    $distance_previous_sentence = $approxlen - $previous_sentence_start;
    $distance_next_sentence = $next_sentence_start - $approxlen;

    // Sanity: Return at least one sentence.
    $sanity = substr($string, 0, $previous_sentence_start + 1);

    if (strpos($sanity, '.') === false) {
        return (substr($string, 0, $next_sentence_start + 1));
    }

    // Decide wether the next or previous sentence is nearer
    if ($distance_previous_sentence > $distance_next_sentence) {
        return (substr($string, 0, $next_sentence_start + 1));
    } else {
        return (substr($string, 0, $previous_sentence_start + 1));
    }
}

/**
 * cApiStrReplaceDiacritics: Converts diactritics to english characters whenever possible.
 *
 * For german umlauts, this function converts the umlauts to their ASCII
 * equalients (e.g. � => ae).
 *
 * For more information about diacritics, refer to
 * http://en.wikipedia.org/wiki/Diacritic
 *
 * For other languages, the diacritic marks are removed, if possible.
 *
 * @param  string  $sString         The string to operate on
 * @param  string  $sourceEncoding  The source encoding (default: ISO-8859-1)
 * @param  string  $targetEncoding  The target encoding (default: ISO-8859-1)
 * @return string  The resulting string
 */
function cApiStrReplaceDiacritics($sString, $sourceEncoding = 'ISO-8859-1', $targetEncoding = 'ISO-8859-1') {
    if ($sourceEncoding != 'UTF-8') {
        $sString = utf8_decode($sString);
    }

    // replace regular german umlauts and other common characters with diacritics
    static $aSearch, $aReplace;
    if (!isset($aSearch)) {
        $aSearch = array('Ä', 'Ö', 'Ü', 'ä', 'ö', 'ü', 'ß', 'Á', 'À', 'Â', 'á', 'à', 'â', 'É', 'È', 'Ê', 'é', 'è', 'ê', 'Í', 'Ì', 'Î', 'í', 'ì', 'î', 'Ó', 'Ò', 'Ô', 'ó', 'ò', 'ô', 'Ú', 'Ù', 'Û', 'ú', 'ù', 'û');
        $aReplace = array('Ae', 'Oe', 'Ue', 'ae', 'oe', 'ue', 'ss', 'A', 'A', 'A', 'a', 'a', 'a', 'E', 'E', 'E', 'e', 'e', 'e', 'I', 'I', 'I', 'i', 'i', 'i', 'O', 'O', 'O', 'o', 'o', 'o', 'U', 'U', 'U', 'u', 'u', 'u');
    }
    $sString = str_replace($aSearch, $aReplace, $sString);

    // TODO: Additional converting

    return $sString;
}

/**
 * Converts a string to another encoding. This function tries to detect which function
 * to use (either recode or iconv).
 *
 * If $sourceEncoding and $targetEncoding are the same, this function returns immediately.
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
 * @todo Check if the charset names are the same for both converters
 * @todo Implement a converter and charset checker to ensure compilance.
 *
 * @param   string  $sString         The string to operate on
 * @param   string  $sourceEncoding  The source encoding (default: ISO-8859-1)
 * @param   string  $targetEncoding  The target encoding (if false, use source encoding)
 * @return  string  The resulting string
 */
function cApiStrRecodeString($sString, $sourceEncoding, $targetEncoding) {
    // If sourceEncoding and targetEncoding are the same, return
    if ($sourceEncoding == $targetEncoding) {
        return $sString;
    }

    // Check for the "recode" support
    if (function_exists('recode')) {
        $sResult = recode_string("$sourceEncoding..$targetEncoding", $sString);
        return $sResult;
    }

    // Check for the "iconv" support
    if (function_exists('iconv')) {
        $sResult = iconv($sourceEncoding, $targetEncoding, $sString);
        return $sResult;
    }

    // No charset converters found; return with warning
    cWarning(__FILE__, __LINE__, 'cApiStrRecodeString could not find either recode or iconv to do charset conversion.');
    return $sString;
}

/**
 * Removes or converts all "evil" URL characters. This function removes or converts
 * all characters which can make an URL invalid.
 *
 * Clean characters include:
 * - All characters between 32 and 126 which are not alphanumeric and
 *   aren't one of the following: _-.
 *
 * @param   string  $sString   The string to operate on
 * @param   bool    $bReplace  If true, all "unclean" characters are replaced
 * @return  string  The resulting string
 */
function cApiStrCleanURLCharacters($sString, $bReplace = false) {
    $sString = cApiStrReplaceDiacritics($sString);
    $sString = str_replace(' ', '-', $sString);
    $sString = str_replace('/', '-', $sString);
    $sString = str_replace('&', '-', $sString);
    $sString = str_replace('+', '-', $sString);

    $iStrLen = strlen($sString);

    $sResultString = '';

    for ($i = 0; $i < $iStrLen; $i++) {
        $sChar = substr($sString, $i, 1);

        if (preg_match('/^[a-z0-9]*$/i', $sChar) || $sChar == '-' || $sChar == '_' || $sChar == '.') {
            $sResultString .= $sChar;
        } else {
            if ($bReplace == true) {
                $sResultString .= '_';
            }
        }
    }

    return $sResultString;
}

/**
 * Normalizes line endings in passed string.
 * @param  string  $sString
 * @param  string  $sLineEnding  Feasible values are "\n", "\r" or "\r\n"
 * @return string
 */
function cApiStrNormalizeLineEndings($sString, $sLineEnding = "\n") {
    if ($sLineEnding !== "\n" && $sLineEnding !== "\r" && $sLineEnding !== "\r\n") {
        $sLineEnding = "\n";
    }

    $sString = str_replace("\r\n", "\n", $sString);
    $sString = str_replace("\r", "\n", $sString);
    if ($sLineEnding !== "\n") {
        $sString = str_replace("\n", $sLineEnding, $sString);
    }

    return $sString;
}
