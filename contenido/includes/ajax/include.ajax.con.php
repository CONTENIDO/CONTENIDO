<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Some AJAX functions of area con
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.1
 * @author     Ingo van Peeren
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release 4.8.9
 * 
 * {@internal 
 *   created 2008-09-08
 *   modified 2009-06-25, Ingo van Peeren, added some array checks and initializing
 *   $Id$:
 * }}
 * 
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

if (!$idcat) {
	$idcat = Contenido_Security::toInteger($_REQUEST['idcat']);
}

$sCatlist = Contenido_Security::toString($_REQUEST['wholelist']);
if ($sCatlist != '') {
	$aCatlist = explode(',', $sCatlist);
} else {
	$aCatlist = array();
}

$aConexpandedList = unserialize($currentuser->getUserProperty("system", "con_cat_expandstate"));
if (!is_array($aConexpandedList))  {
	$aConexpandedList = array();
}

if ($bDebug) {
	print_r($aConexpandedList);
	print_r($aCatlist);
}

if ($action == 'toggle') {
	$sKey = array_search($idcat, $aConexpandedList);
	if ($sKey !== false) {
		unset($aConexpandedList[$sKey]);
	} elseif (in_array($idcat, $aCatlist)) {
		$aConexpandedList[] = $idcat;
	}
} elseif ($action == 'expand') {
	if (!in_array($idcat, $aConexpandedList) && in_array($idcat, $aCatlist)) {
		$aConexpandedList[] = $idcat;
	}
} elseif ($action == 'collapse') {
	$sKey = array_search($idcat, $aConexpandedList);
	if ($sKey !== false) {
		unset($aConexpandedList[$sKey]);
	}
} elseif ($action == 'collapseall') {
	if (count($aConexpandedList)) {
		$aConexpandedList = array();
	}
} elseif ($action == 'expandall') {
	$aConexpandedList = $aCatlist;
}

$currentuser->setUserProperty("system", "con_cat_expandstate", serialize($aConexpandedList));
?>