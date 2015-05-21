<?php

/**
 * This file contains some little function to retrieving current encoding.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Holger Librenz
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @deprecated [2015-05-21] use cRegistry::getEncoding
 * Returns encoding for language with ID $iLang (global $lang in CONTENIDO
 * style).
 * The parameter $db has to be an instance of cDb (global $db in con)
 * and
 * $cfg is the equivalent to global $cfg array in CONTENIDO.
 * If no encoding is found or any parameter is not valid, the function will
 * return
 * false, otherwise the encoding as string like it is stored in database.
 *
 * @param cDb $db
 *         not used any more!
 * @param int $lang
 * @return string|boolean
 */
function getEncodingByLanguage($db, $lang) {
	cDeprecated('This method is deprecated and is not needed any longer');
	return cRegistry::getEncoding();
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
