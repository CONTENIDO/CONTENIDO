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

$sCatList = cSecurity::toString($_REQUEST['wholelist'] ?? '');
if ($sCatList != '') {
    $aCatList = explode(',', $sCatList);
} else {
    $aCatList = [];
}

$aConExpandedList = unserialize($currentuser->getUserProperty('system', 'con_cat_expandstate'));
if (!is_array($aConExpandedList))  {
    $aConExpandedList = [];
}

if (!isset($aConExpandedList[$client]) || !is_array($aConExpandedList[$client])) {
    $aConExpandedList[$client] = [];
}

if ($bDebug) {
    print_r($aConExpandedList[$client]);
    print_r($aCatList);
}

if ($action === 'toggle') {
    $sKey = array_search($idcat, $aConExpandedList[$client]);
    if ($sKey !== false) {
        unset($aConExpandedList[$client][$sKey]);
    } elseif (in_array($idcat, $aCatList)) {
        $aConExpandedList[$client][] = $idcat;
    }
} elseif ($action === 'expand') {
    if (!in_array($idcat, $aConExpandedList[$client]) && in_array($idcat, $aCatList)) {
        $aConExpandedList[$client][] = $idcat;
    }
} elseif ($action === 'collapse') {
    $sKey = array_search($idcat, $aConExpandedList[$client]);
    if ($sKey !== false) {
        unset($aConExpandedList[$client][$sKey]);
    }
} elseif ($action === 'collapseall') {
    if (count($aConExpandedList[$client])) {
        $aConExpandedList[$client] = [];
    }
} elseif ($action === 'expandall') {
    $aConExpandedList[$client] = $aCatList;
}

$currentuser->setUserProperty('system', 'con_cat_expandstate', serialize($aConExpandedList));
