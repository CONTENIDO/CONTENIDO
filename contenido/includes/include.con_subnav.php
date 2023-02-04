<?php

/**
 * This file contains the sub navigation frame backend page for content area.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Jan Lengowski
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $tpl;

$db = cRegistry::getDb();
$cfg = cRegistry::getConfig();
$client = cRegistry::getClientId();
$lang = cRegistry::getLanguageId();
$action = cRegistry::getAction();

// Get sync options
if (isset($syncoptions)) {
    $syncfrom = $syncoptions;
    $remakeCatTable = true;
} else {
    $syncoptions = -1;
}

if (!isset($syncfrom)) {
    $syncfrom = 0;
}
if (!isset($idcat) || $idcat == '') {
    $idcat = 0;
}
if (!isset($sql)) {
    $sql = '';
}
if (!isset($bNoArticle)) {
    $bNoArticle = 'false';
}

$area = $_GET['area'];

if (isset($_GET['display_menu']) && $_GET['display_menu'] == 1) {

    $anchorTpl = '<a class="white%s" style="%s" onclick="%s">%s</a>';

    $nav = new cGuiNavigation();

    // Simple SQL statement to get the number of articles
    $sql_count =
        "SELECT
                    COUNT(*) AS article_count
                 FROM
                    " . $cfg["tab"]["art_lang"] . " AS a,
                    " . $cfg["tab"]["art"] . " AS b,
                    " . $cfg["tab"]["cat_art"] . " AS c
                 WHERE
                    (a.idlang   = " . cSecurity::toInteger($lang) . " {SYNCOPTIONS}) AND
                    a.idart     = b.idart AND
                    b.idclient  = " . cSecurity::toInteger($client) . " AND
                    b.idart     = c.idart AND
                    c.idcat     = " . cSecurity::toInteger($idcat);

    $sql = str_replace("{ISSTART}", '', $sql);

    if ($syncoptions == -1) {
        $sql = str_replace("{SYNCOPTIONS}", '', $sql);
        $sql_count = str_replace("{SYNCOPTIONS}", '', $sql_count);
    } else {
        $sql = str_replace("{SYNCOPTIONS}", "OR a.idlang = '" . $syncoptions . "'", $sql);
        $sql_count = str_replace("{SYNCOPTIONS}", "OR a.idlang = '" . $syncoptions . "'", $sql_count);
    }

    $iArticleCount = 0;
    $db->query($sql_count);
    while ($db->nextRecord()) {
        $iArticleCount = $db->f('article_count');
    }

    $num = 0;

    // Get all sub navigation items
    $navSubColl = new cApiNavSubCollection();
    $areasNavSubs = $navSubColl->getSubnavigationsByAreaName($area);

    foreach ($areasNavSubs as $areasNavSub) {
        /*
         * Tab display "logic"
         * Show all tabs
         * - if category ID is empty (lost and found)
         * - if category has articles
         *
         * Show first tab
         * - if category has no articles
         *
         * Show first tab only
         * - if article was deleted
         *
         * Show second tab
         * - if article is created or saved
         */
        if (cSecurity::toInteger($idcat) == 0 || $iArticleCount > 0 || ($iArticleCount <= 0 && $tpl->dyn_cnt == 0) ||
            ($iArticleCount <= 0 && $tpl->dyn_cnt == 1 && $bNoArticle == 'true') ||
            ($bNoArticle == 'true' && $action == 'saveart') ||
            ($iArticleCount <= 0 && $tpl->dyn_cnt == 0 && $action == 'deleteArt')) {
            $style = '';
        } else {
            $style = 'display:none;';
        }

        // Tab select "logic"
        if (($iArticleCount <= 0 && $tpl->dyn_cnt == 1 && $bNoArticle == 'true') ||
            ($tpl->dyn_cnt == 1 && $bNoArticle == 'true' && $action == 'saveart')) {
            $num = $tpl->dyn_cnt;
        }

        $caption = $areasNavSub['caption'];
        $areaName = $areasNavSub['name'];

        // CSS Class
        $sClass = ($areaName == $area) ? ' current' : '';

        // Link
        if ($cfg['help'] == true) {
            $sLink = getJsHelpContext(i18n("Article") . "/$caption") . 'artObj.doAction(\'' . $areaName . '\');';
        } else {
            $sLink = 'artObj.doAction(\'' . $areaName . '\');';
        }

        // Set template data
        $tpl->set('d', 'ID', 'c_' . $tpl->dyn_cnt);
        $tpl->set('d', 'DATA_NAME', $areaName);
        $tpl->set('d', 'CLASS', '');
        $tpl->set('d', 'OPTIONS', '');
        $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $sClass, $style, $sLink, $caption));
#        if ($cfg['help'] == true) {
#            $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $style, getJsHelpContext(i18n("Article") . "/$caption") . 'artObj.doAction(\'' . $areaName . '\');' , $caption));
#        } else {
#            $tpl->set('d', 'CAPTION', sprintf($anchorTpl, $style, 'artObj.doAction(\'' . $areaName . '\');' , $caption));
#        }

        $tpl->next();
    }

    $tpl->set('s', 'iID', 'c_' . $num);
    $tpl->set('s', 'COLSPAN', ($tpl->dyn_cnt * 2) + 2);
    $tpl->set('s', 'IDCAT', $idcat);
    $tpl->set('s', 'CLIENT', $client);
    $tpl->set('s', 'LANG', $lang);

    // Generate the third navigation layer
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['con_subnav']);
} else {
    $tpl->reset();
    $tpl->generate($cfg['path']['templates'] . $cfg['templates']['right_top_blank']);
}
