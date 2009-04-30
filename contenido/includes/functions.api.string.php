<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Contenido Strign API functions
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.6.0
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-08-08
 *   modified 2008-06-25, Frederic Schneider, add security fix
 *   modified 2008-09-15, Murat Purc, add replacement of characters with diacritics
 *   modified 2009-04-30, Ortwin Pinke, CON-252 
 *
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

/**
 * Info:
 * This file contains Contenido String API functions.
 *
 * If you are planning to add a function, please make sure that:
 * 1.) The function is in the correct place
 * 2.) The function is documented
 * 3.) The function makes sense and is generically usable
 *
 */


/**
 * capiStrTrimAfterWord: Trims a string to a given
 * length and makes sure that all words up to
 * $maxlen are preserved, without exceeding $maxlen.
 *
 * Warning: Currently, this function uses a regular
 * ASCII-Whitespace to do the seperation test. If
 * you are using '&nbsp' to create spaces, this
 * function will fail.
 *
 * Example:
 * $string = "This is a simple test";
 * echo capiStrTrimAfterWord ($string, 15);
 *
 * This would output "This is a", since 
 * this function respects word boundaries
 * and doesn't operate beyond the limit given
 * by $maxlen.
 *
 * @param $string string The string to operate on
 * @param $maxlen int The maximum number of characters 
 *
 * @return string The resulting string
 */
function capiStrTrimAfterWord ($string, $maxlen)
{
	/* If the string is smaller than the maximum
       lenght, it makes no sense to process it any
       further. Return it. */
	if (strlen($string) < $maxlen)
	{
		return $string;
	}

	/* If the character after the $maxlen
       position is a space, we can return
       the string until $maxlen */	
	if (substr($string, $maxlen,1) == ' ')
	{
		return substr($string, 0, $maxlen);
	}
	
	/* Cut the string up to $maxlen so we can
       use strrpos (reverse str position) */
	$cutted_string = substr($string, 0, $maxlen);
	
	/* Extract the end of the last word */
	$last_word_position = strrpos($cutted_string, ' ');
	
	return (substr($cutted_string, 0, $last_word_position));
}

/**
 * capiStrTrimHard: Trims a string to a specific
 * length. If the string is longer than $maxlen,
 * dots are inserted ("...") right before $maxlen.
 *
 * Example:
 * $string = "This is a simple test";
 * echo capiStrTrimHard ($string, 15);
 *
 * This would output "This is a si...", since 
 * the string is longer than $maxlen and the
 * resulting string matches 15 characters including
 * the dots.
 *
 * @param $string string The string to operate on
 * @param $maxlen int The maximum number of characters 
 *
 * @return string The resulting string
 */
function capiStrTrimHard ($string, $maxlen, $fillup = "...")
{
	/* Our fillup string */
	$fillup = "...";

	/* If the string is smaller than the maximum
       lenght, it makes no sense to process it any
       further. Return it. */	
	if (strlen($string) < $maxlen)
	{
		return $string;
	}
	
	/* Calculate the maximum text length */
	$maximum_text_length = $maxlen - strlen($fillup);
	
	/* Cut it */
	$cutted_string = substr($string, 0, $maximum_text_length);
	
	/* Append the fillup string */
	$cutted_string .= $fillup;
	 
	return ($cutted_string);
}

/**
 * capiStrTrimSentence: Trims a string to a 
 * approximate length. Sentence boundaries are
 * preserved.
 *
 * The algorythm inside calculates the sentence
 * length to the previous and next sentences.
 * The distance to the next sentence which is
 * smaller will be taken to trim the string
 * to match the approximate length parameter.
 *
 * Example:
 *
 * $string  = "This contains two sentences. ";
 * $string .= "Lets play around with them. ";
 *
 * echo capiStrTrimSentence($string, 40);
 * echo capiStrTrimSentence($string, 50);
 *
 * The first example would only output the first sentence,
 * the second example both sentences.
 *
 * Explanation:
 *
 * To match the given max length closely, 
 * the function calculates the distance to
 * the next and previous sentences. Using
 * the maxlength of 40 characters, the
 * distance to the previous sentence would
 * be 8 characters, and to the next sentence
 * it would be 19 characters. Therefore,
 * only the previous sentence is displayed. 
 *
 * The second example displays the second
 * sentence also, since the distance to the
 * next sentence is only 9 characters, but
 * to the previous it is 18 characters.
 *
 * If you specify the boolean flag "$hard",
 * the limit parameter creates a hard limit
 * instead of calculating the distance.
 *
 * This function ensures that at least one
 * sentence is returned.
 *
 * @param $string string The string to operate on
 * @param $approxlen int The approximate number of characters 
 * @param $hard boolean If true, use a hard limit for the number of characters (default: false)
 * @return string The resulting string
 */
function capiStrTrimSentence ($string, $approxlen, $hard = false)
{

	/* If the string is smaller than the maximum
       lenght, it makes no sense to process it any
       further. Return it. */		
	if (strlen($string) < $approxlen)
	{
		return $string;
	}
	
	/* Find out the start of the next sentence */
	$next_sentence_start = strpos($string, '.', $approxlen);

	/* If there's no next sentence (somebody forgot the dot?),
       set it to the end of the string. */	
	if ($next_sentence_start === false)
	{
		$next_sentence_start = strlen($string);	
	} 
	
	/* Cut the previous sentence so we can use strrpos */
	$previous_sentence_cutted = substr($string, 0, $approxlen);
	
	/* Get out the previous sentence start */
	$previous_sentence_start = strrpos($previous_sentence_cutted, '.');
	
	/* If the sentence doesn't contain a dot, use the text start. */
	if ($previous_sentence_start === false)
	{
		$previous_sentence_start = 0;
	}
	
	/* If we have a hard limit, we only want to process
       everything before $approxlen */
	if (($hard == true) && ($next_sentence_start > $approxlen))
	{
		return (substr($string, 0, $previous_sentence_start+1));
	}  
	
	/* Calculate next and previous sentence distances */
	$distance_previous_sentence = $approxlen - $previous_sentence_start;
	$distance_next_sentence = $next_sentence_start - $approxlen;

	/* Sanity: Return at least one sentence. */
	$sanity = substr($string, 0, $previous_sentence_start + 1);
	
	if (strpos($sanity,'.') === false)
	{
		return (substr($string, 0, $next_sentence_start + 1));
	}
	
	/* Decide wether the next or previous sentence is nearer */	
	if ($distance_previous_sentence > $distance_next_sentence)
	{
		return (substr($string, 0, $next_sentence_start+1));
	} else {
		return (substr($string, 0, $previous_sentence_start+1));
	}
}

/**
 * capiStrReplaceDiacritics: Converts diactritics
 * to english characters whenever possible.
 *
 * For german umlauts, this function converts the 
 * umlauts to their ASCII equalients (e.g. ä => ae).
 *
 * For more information about diacritics, refer to
 * http://en.wikipedia.org/wiki/Diacritic
 * 
 * For other languages, the diacritic marks are removed,
 * if possible.
 *
 * @param $sString 			string 	The string to operate on
 * @param $sourceEncoding	string	The source encoding (default: ISO-8859-1)
 * @return string The resulting string
 *
 * @author Timo A. Hummel
 * @copyright four for business AG, http://www.4fb.de
 */
function capiStrReplaceDiacritics ($sString, $sourceEncoding = "ISO-8859-1", $targetEncoding = false)
{
	/* If the target encoding isn't set, use source encoding */
	if ($targetEncoding == false)
	{
		$targetEncoding = $sourceEncoding;
	}
	
	// replace regular german umlauts and other common characters with diacritics
    static $aSearch, $aReplace;
    if (!isset($aSearch)) {
        $aSearch  = array('Ä',  'Ö',  'Ü',  'ä',  'ö', 'ü',  'ß',  'Á', 'À', 'Â', 'á', 'à', 'â', 'É', 'È', 'Ê', 'é', 'è', 'ê', 'Í', 'Ì', 'Î', 'í', 'ì', 'î', 'Ó', 'Ò', 'Ô', 'ó', 'ò', 'ô', 'Ú', 'Ù', 'Û', 'ú', 'ù', 'û');
        $aReplace = array('Ae', 'Oe', 'Ue', 'ae', 'oe', 'ue', 'ss', 'A', 'A', 'A', 'a', 'a', 'a', 'E', 'E', 'E', 'e', 'e', 'e', 'I', 'I', 'I', 'i', 'i', 'i', 'O', 'O', 'O', 'o', 'o', 'o', 'U', 'U', 'U', 'u', 'u', 'u');
    }
    $sString = str_replace($aSearch, $aReplace, $sString);
	
	/* TODO: Additional converting */
	
	return ($sString);
}


/**
 * capiStrRecodeString: Converts a string to another encoding.
 * This function tries to detect which function to use (either
 * recode or iconv).
 *
 * If $sourceEncoding and $targetEncoding are the same, this
 * function returns immediately.
 *
 * For more information about encodings, refer to
 * http://en.wikipedia.org/wiki/Character_encoding
 *
 * For more information about the supported encodings in recode,
 * refer to
 * http://www.delorie.com/gnu/docs/recode/recode_toc.html
 *
 * Note: depending on whether recode or iconv is used, the
 * supported charsets differ. The following ones are commonly used
 * and are most likely supported by both converters:
 *
 * - ISO-8859-1 to ISO-8859-15
 * - ASCII
 * - UTF-8
 * 
 * @todo Check if the charset names are the same for both converters  
 * @todo Implement a converter and charset checker to ensure compilance.
 *
 * @param $sString 			string 	The string to operate on
 * @param $targetEncoding	string	The target encoding (if false, use source encoding)
 * @param $sourceEncoding	string	The source encoding (default: ISO-8859-1)
 * @return 					string	The resulting string
 *
 * @author Timo A. Hummel
 * @copyright four for business AG, http://www.4fb.de
 */
function capiStrRecodeString ($sString, $sourceEncoding, $targetEncoding)
{
	/* If sourceEncoding and targetEncoding are the same, return */
	if ($sourceEncoding == $targetEncoding)
	{
		return $sString;	
	}
	
	/* Check for the "recode" support */
	if (function_exists("recode"))
	{
		$sResult = recode_string("$sourceEncoding..$targetEncoding", $sString);
		
		return ($sResult);	
	}
	
	/* Check for the "iconv" support */ 
	if (function_exists("iconv"))
	{
		$sResult = iconv($sourceEncoding, $targetEncoding, $sString);
		
		return ($sResult);	
	}

	/* No charset converters found; return with warning */	
	cWarning(__FILE__, __LINE__, "capiStrRecodeString could not find either recode or iconv to do charset conversion.");
	return ($sString);
}

/**
 * capiStrCleanURLCharacters: Removes or converts all "evil" 
 * URL characters.
 *
 * This function removes or converts all characters which can
 * make an URL invalid.
 *
 * Clean characters include:
 * - All characters between 32 and 126 which are not alphanumeric and
 *   aren't one of the following: _-.
 *
 * @param $sString 			string 	The string to operate on
 * @param $bReplace			string	If true, all "unclean" characters are replaced
 * @return 					string	The resulting string
 *
 * @author Timo A. Hummel
 * @copyright four for business AG, http://www.4fb.de
 */
function capiStrCleanURLCharacters ($sString, $bReplace = false)
{
	$sString = capiStrReplaceDiacritics($sString);
	$sString = str_replace(" ", "-", $sString);
	$sString = str_replace("/", "-", $sString);	
	$sString = str_replace("&", "-", $sString);	
	$sString = str_replace("+", "-", $sString);		
		
	$iStrLen = strlen($sString);
	
	for ($i=0; $i < $iStrLen; $i++)
	{
		$sChar = substr($sString, $i, 1);
		
		if (preg_match('/^[a-z0-9]*$/i', $sChar) || $sChar ==  "-" || $sChar == "_" || $sChar == ".")
		{
			$sResultString .= $sChar;	
		} else {
			if ($bReplace == true)
			{
				$sResultString .= "_";	
			}
		}
		
	}		

	return ($sResultString);
}
?>
