<?php
/**
 * Encoding Helper Function
 * 
 * @file $RCSfile: functions.encoding.php,v $
 *  
 * @description Some little function to retrieving current encoding.
 * 
 * @version 1.0.0
 * @author Holger Librenz
 * @copyright four for business AG <www.4fb.de>
 * 
 * @modified $Date: 2007/10/11 15:45:33 $
 * @modifiedby $Author: holger.librenz $
 * 
 * $Id: functions.encoding.php,v 1.1 2007/10/11 15:45:33 holger.librenz Exp $
 */

/**
 * Returns encoding for language with ID $iLang (global $lang in contenido style).
 * The parameter $oDb has to be an instance of DB_Contenido (global $db in con) and
 * $cfg is the equivalent to global $cfg array in contenido.
 * If no encoding is found or any parameter is not valid, the function will return
 * false, otherwise the encoding as string like it is stored in database.
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
			idlang = " . $iLang;

		if ($oDb->query($sQuery)) {
			if ($oResult = mysql_fetch_object($oDb->Query_ID)) {
				$sResult = trim($oResult->encoding);
			}
		}
	}

	return $sResult;
}
?>