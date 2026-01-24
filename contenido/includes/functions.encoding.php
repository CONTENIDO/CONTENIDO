<?php

/**
 * This file contains some little function to retrieving current encoding.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Holger Librenz
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @deprecated [2015-05-21] use {@see cRegistry::getEncoding()} instead
 */
function getEncodingByLanguage($db, $lang)
{
    cDeprecated('This method is deprecated and is not needed any longer');
    return cRegistry::getEncoding();
}

/**
 * Special version of htmlentites for iso-8859-2
 * Returns transformed string
 */
function htmlentities_iso88592(string $input = ''): string
{
    $arrEntities_pl = [
        '&ecirc;',
        '&oacute;',
        '&plusmn;',
        '&para;',
        '&sup3;',
        '&iquest;',
        '&frac14;',
        '&aelig;',
        '&ntilde;',
        '&Ecirc;',
        '&Oacute;',
        '&iexcl;',
        '&brvbar;',
        '&pound;',
        '&not;',
        '&macr;',
        '&AElig;',
        '&Ntilde;',
    ];
    $arrEntities = conGetHtmlTranslationTable(HTML_ENTITIES);
    $arrEntities = array_diff($arrEntities, $arrEntities_pl);

    return strtr($input, $arrEntities);
}
