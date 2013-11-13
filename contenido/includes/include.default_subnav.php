<?php
/**
 * This file contains the default sub navigation frame backend page.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Oliver Lohkemper
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// In some cases dont print menue
if ($dont_print_subnav == 1) {
    $tpl->reset();
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['right_top_blank']);
    return;
}

$aExectime = array();
$aExectime['fullstart'] = getmicrotime();

// Requires all query parameter passed by frame
$aBasicParams = array('area', 'frame', 'contenido', 'appendparameters');

// Flag to check is file is loading from Main-Frame
$bVirgin = false;

$db = cRegistry::getDb();

// Basic-Url-Params with Key: like 'id%' or '%id' and Value: are integer or strlen=32 (for md5)
$sUrlParams = ''; // URL-Parameter as string '&...' + '&...'
$iCountBasicVal = 0; // Count of basic Parameter in URL

foreach ($_GET as $sTempKey => $sTempValue) {
    if (in_array($sTempKey, $aBasicParams)) {
        // Basic parameters attached
        $iCountBasicVal++;
    } else if ((substr($sTempKey, 0, 2) == 'id' || substr($sTempKey, -2, 2) == 'id')
        && ((int) $sTempValue == $sTempValue                      // check integer
        || preg_match('/^[0-9a-f]{32}$/', $sTempValue)) // check md5
        )
    {
        // Complement the selected data
        $sUrlParams .= '&' . $sTempKey . '=' . $sTempValue;
    }
}

// Is loading from main.php
// dann ist die Anzahl aller gueltigen Variablen mit den in GET identisch
if ($iCountBasicVal == count($_GET)) {
    $bVirgin = true;
}

/*
// Area-Url-Params for special params
switch ($area) {
    case 'style':
    case 'js':
    case 'htmltpl':
        if (array_key_exists('file', $_GET)) {
            $sUrlParams .= '&file=' . $_GET['file'];
        }
        break;
    default:
        echo '';
}
*/

// Debug
cDebug::out('Url-Params: ' . $sUrlParams);

$anchorTpl = '<a class="white%s" target="right_bottom" href="%s">%s</a>';

// Get all sub navigation items
$navSubColl = new cApiNavSubCollection();
$areasNavSubs = $navSubColl->getSubnavigationsByAreaName($area);

foreach ($areasNavSubs as $areasNavSub) {
    // Name
    $areaName = $areasNavSub['name'];
//##echo "<pre>" . print_r($areasNavSub, true) . "</pre>\n";

    // for Main-Area
    if ($areaName == $area) {
        // Menueless
        $bMenuless = $areasNavSub['menuless'] ? true : false;

        if ($bVirgin && !$bMenuless && $areasNavSub['name'] == $area) {
            // Is loading from main, main-area and menuless -> stop this 'while'
            break;
        }
    }

    // CSS Class
    $sClass = ($areaName == $area) ? ' current' : '';

    // Link
    $sLink = $sess->url('main.php?area=' . $areaName . '&frame=4' . ($appendparameters ? '&appendparameters=' . $appendparameters : '') . $sUrlParams);

    // Fill template
    $tpl->set('d', 'ID', 'c_' . $tpl->dyn_cnt);
    $tpl->set('d', 'DATA_NAME', $areaName);
    $tpl->set('d', 'CLASS', 'item ' . $areaName);
    $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sClass, $sLink, $areasNavSub['caption']));
    $tpl->next();
}

// Is there a menu (left frame)?
if ($db->numRows() == 0) {
    $sql = $db->prepare("SELECT menuless FROM `%s` WHERE name = '%s' AND parent_id = 0", $cfg['tab']['area'], $area);
    $db->query($sql);
    while ($db->nextRecord()) {
        $bMenuless = $db->f('menuless') ? true : false;
    }
}

if (!$bVirgin || $bMenuless) {
    $tpl->set('s', 'CLASS', $bMenuless ? 'menuless' : '');

    $sTpl = $tpl->generate($cfg['path']['templates'] . $cfg['templates']['subnav'], true);

    cDebug::out('sExectime: ' . substr($sExectime, 0, 7) . ' sec');
    echo $sTpl;
} else {
    // Is loading from main.php
    $tpl->reset();
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['right_top_blank']);
}
