<?php
/**
 * This file contains some AJAX function of the backend area "con".
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Ingo van Peeren
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

if (!$idcat) {
    $idcat = cSecurity::toInteger($_REQUEST['idcat']);
}

$sCatlist = cSecurity::toString($_REQUEST['wholelist']);
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