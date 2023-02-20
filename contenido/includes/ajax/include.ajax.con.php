<?php

/**
 * This file contains some AJAX function of the backend area "con".
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Ingo van Peeren
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * @var cApiUser $currentuser
 * @var int $idcat
 * @var int $client
 */

$bDebug = $bDebug ?? false;
$action = $action ?? '';

if (!$idcat) {
    $idcat = cSecurity::toInteger($_REQUEST['idcat'] ?? '0');
}

$sCatlist = cSecurity::toString($_REQUEST['wholelist'] ?? '');
if ($sCatlist != '') {
    $aCatlist = explode(',', $sCatlist);
} else {
    $aCatlist = [];
}

$aConexpandedList = unserialize($currentuser->getUserProperty("system", "con_cat_expandstate"));
if (!is_array($aConexpandedList))  {
    $aConexpandedList = [];
}

if (!is_array($aConexpandedList[$client])) {
    $aConexpandedList[$client] = [];
}

if ($bDebug) {
    print_r($aConexpandedList[$client]);
    print_r($aCatlist);
}

if ($action == 'toggle') {
    $sKey = array_search($idcat, $aConexpandedList[$client]);
    if ($sKey !== false) {
        unset($aConexpandedList[$client][$sKey]);
    } elseif (in_array($idcat, $aCatlist)) {
        $aConexpandedList[$client][] = $idcat;
    }
} elseif ($action == 'expand') {
    if (!in_array($idcat, $aConexpandedList[$client]) && in_array($idcat, $aCatlist)) {
        $aConexpandedList[$client][] = $idcat;
    }
} elseif ($action == 'collapse') {
    $sKey = array_search($idcat, $aConexpandedList[$client]);
    if ($sKey !== false) {
        unset($aConexpandedList[$client][$sKey]);
    }
} elseif ($action == 'collapseall') {
    if (count($aConexpandedList[$client])) {
        $aConexpandedList[$client] = [];
    }
} elseif ($action == 'expandall') {
    $aConexpandedList[$client] = $aCatlist;
}

$currentuser->setUserProperty("system", "con_cat_expandstate", serialize($aConexpandedList));

?>