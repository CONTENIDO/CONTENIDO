<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Some little function to retrieving current encoding.
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.3.1
 * @author     Holger Librenz
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-06-26, Frederic Schneider, add security fix
 *   modified 2008-11-12, Andreas Lindner, add function htmlentities_iso88592    
 *
 *   $Id: functions.encoding.php 873 2008-11-12 09:18:50Z andreas.lindner $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

/**
 * Returns encoding for language with ID $iLang (global $lang in contenido style).
 * The parameter $oDb has to be an instance of DB_Contenido (global $db in con) and
 * $cfg is the equivalent to global $cfg array in contenido.
 * If no encoding is found or any parameter is not valid, the function will return
 * false, otherwise the encoding as string like it is stored in database.
 * modified 18.03.2008 - Removed special mySQl behaviour (using db object instead) Timo Trautmann
 * 
 * @param DB_Contenido $oDb
 * @param int $iLang
 * @param array $cfg
 * @return string
 */
function getEncodingByLanguage (&$oDb, $iLang, $cfg) {
	$sResult = false;

	if (!is_object($oDb)) {
		$oDb = new DB_Contenido();
	}

	$iLang = (int) $iLang;
	if ($iLang > 0 && is_array($cfg) && is_array($cfg['tab'])) {
		// prepare query
		$sQuery = "
		SELECT 
			encoding 
		FROM 
			" .  $cfg["tab"]["lang"] . "
		WHERE 
			idlang = " . Contenido_Security::toInteger($iLang);

		if ($oDb->query($sQuery)) {
			if ($oDb->next_record()) {
				$sResult = trim($oDb->f('encoding'));
			}
		}
	}

	return $sResult;
}

/**
 * Special version of htmlentites for iso-8859-2
 * Returns transformed string  
 * 
 * @param string $sInput
 * @return string
 */

function htmlentities_iso88592 ($sInput = '') {
    
	$arrEntities_pl = array('&ecirc;', '&oacute;', '&plusmn;', '&para;', '&sup3;', '&iquest;', '&frac14;', '&aelig;', '&ntilde;', '&Ecirc;', '&Oacute;', '&iexcl;', '&brvbar;', '&pound;', '&not;', '&macr;', '&AElig;', '&Ntilde;');   
    $arrEntities = get_html_translation_table(HTML_ENTITIES);
    $arrEntities = array_diff($arrEntities, $arrEntities_pl);

    return strtr($sInput, $arrEntities);
    
}

?>
