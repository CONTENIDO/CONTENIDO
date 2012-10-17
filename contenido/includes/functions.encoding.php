<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Some little function to retrieving current encoding.
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package CONTENIDO Backend Includes
 * @version 1.3.1
 * @author Holger Librenz
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since file available since CONTENIDO release <= 4.6
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Returns encoding for language with ID $iLang (global $lang in CONTENIDO
 * style).
 * The parameter $db has to be an instance of cDb (global $db in con)
 * and
 * $cfg is the equivalent to global $cfg array in CONTENIDO.
 * If no encoding is found or any parameter is not valid, the function will
 * return
 * false, otherwise the encoding as string like it is stored in database.
 * modified 18.03.2008 - Removed special mySQl behaviour (using db object
 * instead) Timo Trautmann
 *
 * @param cDb $db not used any more!
 * @param int $lang
 * @return string
 */
function getEncodingByLanguage($db, $lang) {
    // check parameters and use cRegistry values if they are invalid
    if (!is_numeric($lang)) {
        $lang = cRegistry::getLanguageId();
    }

    $lang = cSecurity::toInteger($lang);
    if ($lang > 0) {
        // load the language object with the given ID and return the encoding
        $apiLanguage = new cApiLanguage($lang);
        if ($apiLanguage->isLoaded()) {
            return trim($apiLanguage->get('encoding'));
        }
    }

    return false;
}

/**
 * Special version of htmlentites for iso-8859-2
 * Returns transformed string
 *
 * @param string $input
 * @return string
 */
function htmlentities_iso88592($input = '') {
    $arrEntities_pl = array(
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
        '&Ntilde;'
    );
    $arrEntities = conGetHtmlTranslationTable(HTML_ENTITIES);
    $arrEntities = array_diff($arrEntities, $arrEntities_pl);

    return strtr($input, $arrEntities);
}
