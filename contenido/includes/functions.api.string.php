<?php

/**
 * This file contains CONTENIDO String API functions.
 *
 * If you are planning to add a function, please make sure that:
 * 1.) The function is in the correct place
 * 2.) The function is documented
 * 3.) The function makes sense and is generally usable
 *
 * @package    Core
 * @subpackage Backend
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @deprecated [2015-05-21] use {@see cString::trimAfterWord()} instaed
 */
function cApiStrTrimAfterWord($string, $maxlen)
{
    cDeprecated('This method is deprecated and is not needed any longer');
    return cString::trimAfterWord($string, $maxlen);
}

/**
 * @deprecated [2015-05-21] use {@see String::trimHard()} instead
 */
function cApiStrTrimHard($string, $maxlen, $fillup = '...')
{
    cDeprecated('This method is deprecated and is not needed any longer');
    return cString::trimHard($string, $maxlen, $fillup);
}

/**
 * @deprecated [2015-05-21] use {@see cString::trimSentence()} instaed
 */
function cApiStrTrimSentence($string, $approxlen, $hard = false)
{
    cDeprecated('This method is deprecated and is not needed any longer');
    return cString::trimSentence($string, $approxlen, $hard);
}

/**
 * @deprecated [2015-05-21] use {@see cString::replaceDiacritics()} instaed
 */
function cApiStrReplaceDiacritics($sString, $sourceEncoding = 'UTF-8', $targetEncoding = 'UTF-8')
{
    cDeprecated('This method is deprecated and is not needed any longer');
    return cString::replaceDiacritics($sString, $sourceEncoding, $targetEncoding);
}

/**
 * @deprecated [2015-05-21] use {@see cString::recodeString()} instaed
 */
function cApiStrRecodeString($sString, $sourceEncoding, $targetEncoding)
{
    cDeprecated('This method is deprecated and is not needed any longer');
    return cString::recodeString($sString, $sourceEncoding, $targetEncoding);
}

/**
 * @deprecated [2015-05-21] use {@see cString::cleanURLCharacters()} instaed
 */
function cApiStrCleanURLCharacters($sString, $bReplace = false)
{
    cDeprecated('This method is deprecated and is not needed any longer');
    return cString::cleanURLCharacters($sString, $bReplace);
}

/**
 * @deprecated [2015-05-21] use {@see cString::normalizeLineEndings()} instaed
 */
function cApiStrNormalizeLineEndings($sString, $sLineEnding = "\n")
{
    cDeprecated('This method is deprecated and is not needed any longer');
    return cString::normalizeLineEndings($sString, $sLineEnding);
}
