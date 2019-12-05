<?php

/**
 * This file contains the CONTENIDO statistic functions.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Olaf Niemann
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude("includes", "functions.database.php");

/**
 * Displays statistic information layer (a div Tag)
 *
 * @deprecated [2015-05-21]
 *         This method is no longer supported (no replacement)
 *
 * @param int    $id
 *         Either article or directory id
 * @param string $type
 *         The type
 * @param int    $x
 *         Style top position
 * @param int    $y
 *         Style left position
 * @param int    $w
 *         Style width
 * @param int    $h
 *         Style height
 *
 * @return string
 *         Composed info layer
 *
 * @throws cException
 * @throws cInvalidArgumentException
 */
function statsDisplayInfo($id, $type, $x, $y, $w, $h) {
    cDeprecated('This method is deprecated and is not needed any longer');

    if (strcmp($type, "article" == 0)) {
        $text = i18n("Info about article") . " " . $id;
    } else {
        $text = i18n("Info about directory") . " " . $id;
    }

    $div = new cHTMLDiv($text, "text_medium", "idElement14");
    $div->appendStyleDefinition("border", "1px solid #e8e8ee");
    $div->appendStyleDefinition("position", "absolute");
    $div->appendStyleDefinition("top", $x . "px");
    $div->appendStyleDefinition("left", $y . "px");
    $div->appendStyleDefinition("width", $w . "px");
    $div->appendStyleDefinition("height", $h . "px");

    return $div->toHtml();
}

/**
 * Archives the current statistics
 *
 * @param string $yearmonth
 *         String with the desired archive date (YYYYMM)
 *
 * @throws cDbException
 */
function statsArchive($yearmonth) {
    global $cfg;

    $yearmonth = preg_replace('/\s/', '0', $yearmonth);

    $db = cRegistry::getDb();
    $db2 = cRegistry::getDb();

    $sql = "SELECT idcatart, idlang, idclient, visited, visitdate FROM " . $cfg["tab"]["stat"];

    $db->query($sql);

    while ($db->nextRecord()) {
        $insertSQL = "INSERT INTO
                          " . $cfg["tab"]["stat_archive"] . "
                          (archived, idcatart, idlang, idclient, visited, visitdate)
                      VALUES
                          (
                           " . $yearmonth . ",
                           " . cSecurity::toInteger($db->f(0)) . ",
                           " . cSecurity::toInteger($db->f(1)) . ",
                           " . cSecurity::toInteger($db->f(2)) . ",
                           " . cSecurity::toInteger($db->f(3)) . ",
                           '" . $db2->escape($db->f(4)) . "')";

        $db2->query($insertSQL);
    }

    $sql = "TRUNCATE TABLE " . $cfg["tab"]["stat"];
    $db->query($sql);

    // Recreate empty stats
    $sql = "SELECT
                A.idcatart, B.idclient, C.idlang
            FROM
                " . $cfg["tab"]["cat_art"] . " AS A INNER JOIN
                " . $cfg["tab"]["cat"] . " AS B ON A.idcat = B.idcat INNER JOIN
                " . $cfg["tab"]["cat_lang"] . " AS C ON A.idcat = C.idcat ";

    $db->query($sql);

    while ($db->nextRecord()) {
        $insertSQL = "INSERT INTO
                          " . $cfg["tab"]["stat"] . "
                          (idcatart, idlang, idclient, visited)
                      VALUES (
                          " . cSecurity::toInteger($db->f(0)) . ",
                          " . cSecurity::toInteger($db->f(2)) . ",
                          " . cSecurity::toInteger($db->f(1)) . ",
                          0)";

        $db2->query($insertSQL);
    }
}

/**
 * Generates a statistics page
 *
 * @param string $yearmonth
 *         Specifies the year and month from which to retrieve the statistics,
 *         specify "current" to retrieve the current entries.
 *
 * @throws cDbException
 * @throws cException
 */
function statsOverviewAll($yearmonth) {
    global $cfg, $db, $tpl, $client, $lang, $cfgClient;

    $sDisplay = 'table-row';

    $bUseHeapTable = $cfg["statistics_heap_table"];

    $sHeapTable = $cfg['tab']['stat_heap_table'];

    if ($bUseHeapTable) {
        if (!dbTableExists($db, $sHeapTable)) {
            buildHeapTable($sHeapTable, $db);
        }
    }

    if (preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])) {
        $sDisplay = 'block';
    }

    $sql = "SELECT
                    idtree, A.idcat, level, preid, C.name, visible
                FROM
                    " . $cfg["tab"]["cat_tree"] . " AS A,
                    " . $cfg["tab"]["cat"] . " AS B,
                    " . $cfg["tab"]["cat_lang"] . " AS C
                WHERE
                    A.idcat=B.idcat AND
                    B.idcat=C.idcat AND
                    C.idlang=" . cSecurity::toInteger($lang) . " AND
                    B.idclient=" . cSecurity::toInteger($client) . "
                ORDER BY idtree";

    $db->query($sql);

    $currentRow = 2;

    $aRowname = array();
    $iLevel = 0;
    $backendUrl = cRegistry::getBackendUrl();
    $tpl->set('s', 'IMG_EXPAND', $backendUrl . $cfg['path']['images'] . 'open_all.gif');
    $tpl->set('s', 'IMG_COLLAPSE', $backendUrl . $cfg['path']['images'] . 'close_all.gif');

    $sumNumberOfArticles = 0;

    while ($db->nextRecord()) {
        if ($db->f("level") == 0 && $db->f("preid") != 0) {
            $tpl->set('d', 'PADDING_LEFT', '10');
            $tpl->set('d', 'TEXT', '&nbsp;');
            $tpl->set('d', 'NUMBEROFARTICLES', '');
            $tpl->set('d', 'TOTAL', '');
            $tpl->set('d', 'ICON', '');
            $tpl->set('d', 'STATUS', '');
            $tpl->set('d', 'ROWNAME', '');
            $tpl->set('d', 'INTHISLANGUAGE', '');
            $tpl->set('d', 'EXPAND', '');
            $tpl->set('d', 'DISPLAY_ROW', $sDisplay);
            $tpl->set('d', 'PATH', '');
            $tpl->set('d', 'ULR_TO_PAGE', '');

            $tpl->next();
            $currentRow++;
        }

        $padding_left = 10 + (15 * $db->f("level"));
        $text = $db->f(4);
        $idcat = $db->f("idcat");
        $bCatVisible = $db->f("visible");

        if ($db->f("level") < $iLevel) {
            $iDistance = $iLevel - $db->f("level");

            for ($i = 0; $i < $iDistance; $i++) {
                array_pop($aRowname);
            }
            $iLevel = $db->f("level");
        }

        if ($db->f("level") >= $iLevel) {
            if ($db->f("level") == $iLevel) {
                array_pop($aRowname);
            } else {
                $iLevel = $db->f("level");
            }
            array_push($aRowname, $idcat);
        }

        /*
         * number of arts
         */
        $sql = "SELECT COUNT(*) FROM " . $cfg["tab"]["cat_art"] . " WHERE idcat=" . cSecurity::toInteger($idcat);
        $db2 = cRegistry::getDb();
        $db2->query($sql);
        $db2->nextRecord();
        $numberOfArticles = $db2->f(0);
        $sumNumberOfArticles += $numberOfArticles;

        /*
         * hits of category total
         */
        if (strcmp($yearmonth, "current") == 0) {
            $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat"] . " AS B WHERE A.idcatart=B.idcatart AND A.idcat=" . cSecurity::toInteger($idcat) . " AND B.idclient=" . cSecurity::toInteger($client);
        } else {
            if (!$bUseHeapTable) {
                $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat_archive"] . " AS B WHERE A.idcatart=B.idcatart AND A.idcat=" . cSecurity::toInteger($idcat) . "
                        AND B.idclient=" . cSecurity::toInteger($client) . " AND B.archived='" . $db2->escape($yearmonth) . "'";
            } else {
                $sql = "SELECT SUM(visited) FROM " . $db2->escape($sHeapTable) . " WHERE idcat=" . cSecurity::toInteger($idcat) . "
                        AND idclient=" . cSecurity::toInteger($client) . " AND archived='" . $db2->escape($yearmonth) . "'";
            }
        }
        $db2->query($sql);
        $db2->nextRecord();
        $total = $db2->f(0);

        /*
         * hits of category in this language
         */
        if (strcmp($yearmonth, "current") == 0) {
            $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat"] . " AS B WHERE A.idcatart=B.idcatart AND A.idcat=" . cSecurity::toInteger($idcat) . "
                    AND B.idlang=" . cSecurity::toInteger($lang) . " AND B.idclient=" . cSecurity::toInteger($client);
        } else {
            if (!$bUseHeapTable) {
                $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat_archive"] . " AS B WHERE A.idcatart=B.idcatart AND A.idcat=" . cSecurity::toInteger($idcat) . "
                        AND B.idlang=" . cSecurity::toInteger($lang) . " AND B.idclient=" . cSecurity::toInteger($client) . " AND B.archived='" . $db2->escape($yearmonth) . "'";
            } else {
                $sql = "SELECT SUM(visited) FROM " . $db2->escape($sHeapTable) . " WHERE idcat=" . cSecurity::toInteger($idcat) . " AND idlang=" . cSecurity::toInteger($lang) . "
                        AND idclient=" . cSecurity::toInteger($client) . " AND archived='" . $db2->escape($yearmonth) . "'";
            }
        }
        $db2->query($sql);
        $db2->nextRecord();
        $inThisLanguage = $db2->f(0);

        $icon = '<img alt="" src="' . $cfg['path']['images'] . 'folder.gif" class="vAlignMiddle">';

        /*
         * art
         */
        $sql = "SELECT * FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["art"] . " AS B, " . $cfg["tab"]["art_lang"] . " AS C WHERE A.idcat=" . cSecurity::toInteger($idcat) . "
                AND A.idart=B.idart AND B.idart=C.idart AND C.idlang=" . cSecurity::toInteger($lang) . " ORDER BY B.idart";
        $db2->query($sql);

        $numrows = $db2->numRows();

        $online = $db->f("visible");
        if ($bCatVisible == 1) {
            $offonline = '<img src="' . $cfg['path']['images'] . 'online_off.gif" alt="' . i18n("Category is online") . '" title="' . i18n("Category is online") . '">';
        } else {
            $offonline = '<img src="' . $cfg['path']['images'] . 'offline_off.gif" alt="' . i18n("Category is offline") . '" title="' . i18n("Category is offline") . '">';
        }

        /*
         * check if there are subcategories
         */
        $iSumSubCategories = 0;
        $sSql = "SELECT COUNT(*) AS cat_count FROM " . $cfg["tab"]["cat"] . " WHERE parentid=" . cSecurity::toInteger($idcat) . ";";
        $db3 = cRegistry::getDb();
        $db3->query($sSql);
        if ($db3->nextRecord()) {
            $iSumSubCategories = $db3->f('cat_count');
        }
        $db3->free();

        $tpl->set('d', 'PADDING_LEFT', $padding_left);
        $tpl->set('d', 'TEXT', conHtmlSpecialChars($text) . ' (idcat: ' . cSecurity::toInteger($db->f('idcat')) . ')');
        $tpl->set('d', 'ICON', $icon);
        $tpl->set('d', 'STATUS', $offonline);
        $tpl->set('d', 'NUMBEROFARTICLES', $numberOfArticles);
        $tpl->set('d', 'TOTAL', $total);
        $tpl->set('d', 'ROWNAME', implode('_', $aRowname));
        if ($numrows > 0 || $iSumSubCategories > 0) {
            $tpl->set('d', 'EXPAND', '<a href="javascript:changeVisibility(\'' . implode('_', $aRowname) . '\', ' . $db->f("level") . ', ' . $idcat . ')">
                                          <img src="' . $cfg['path']['images'] . 'open_all.gif"
                                               alt="' . i18n("Open category") . '"
                                               title="' . i18n("Open category") . '"
                                               id="' . implode('_', $aRowname) . '_img"
                                               class="vAlignMiddle">
                                      </a>');
        } else {
            $tpl->set('d', 'EXPAND', '<img alt="" src="' . $cfg['path']['images'] . 'spacer.gif" width="7">');
        }
        $tpl->set('d', 'INTHISLANGUAGE', $inThisLanguage);
        if ($db->f("level") != 0) {
            $tpl->set('d', 'DISPLAY_ROW', 'none');
        } else {
            $tpl->set('d', 'DISPLAY_ROW', $sDisplay);
        }
        $frontendURL = cRegistry::getFrontendUrl();
        $cat_name = "";
        statCreateLocationString($db->f('idcat'), "&nbsp;/&nbsp;", $cat_name);
        $tpl->set('d', 'PATH', i18n("Path") . ":&nbsp;/&nbsp;" . $cat_name);
        $tpl->set('d', 'ULR_TO_PAGE', $frontendURL . 'front_content.php?idcat=' . $db->f('idcat'));

        $tpl->next();
        $currentRow++;

        $text = "";
        $numberOfArticles = "";
        $total = "";
        $inThisLanguage = "";

        while ($db2->nextRecord()) {
            $idart = $db2->f("idart");

            array_push($aRowname, $idart);

            $text = "";
            $numberOfArticles = "";
            $total = "";
            $inThisLanguage = "";

            $padding_left = 10 + (15 * ($db->f("level") + 1));

            $text = $db2->f("title");
            $online = $db2->f("online");

            /*
             * number of arts
             */
            $db3 = cRegistry::getDb();

            /*
             * hits of art total
             */
            if (strcmp($yearmonth, "current") == 0) {
                $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat"] . " AS B WHERE A.idcatart=B.idcatart AND A.idcat=" . cSecurity::toInteger($idcat) . "
                     AND A.idart=" . cSecurity::toInteger($idart) . " AND B.idclient=" . cSecurity::toInteger($client);
            } else {
                if (!$bUseHeapTable) {
                    $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat_archive"] . " AS B WHERE A.idcatart=B.idcatart AND A.idcat=" . cSecurity::toInteger($idcat) . "
                            AND A.idart=" . cSecurity::toInteger($idart) . " AND B.idclient=" . cSecurity::toInteger($client) . " AND B.archived='" . $db3->escape($yearmonth) . "'";
                } else {
                    $sql = "SELECT SUM(visited) FROM " . $db3->escape($sHeapTable) . " WHERE idcat=" . cSecurity::toInteger($idcat) . " AND idart=" . cSecurity::toInteger($idart) . "
                            AND idclient=" . cSecurity::toInteger($client) . " AND archived='" . $db3->escape($yearmonth) . "'";
                }
            }

            $db3->query($sql);
            $db3->nextRecord();

            $total = $db3->f(0);

            /*
             * hits of art in this language
             */
            if (strcmp($yearmonth, "current") == 0) {
                $sql = "SELECT visited, idart FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat"] . " AS B WHERE A.idcatart=B.idcatart AND A.idcat=" . cSecurity::toInteger($idcat) . "
                        AND A.idart=" . cSecurity::toInteger($idart) . " AND B.idlang=" . cSecurity::toInteger($lang) . " AND B.idclient=" . cSecurity::toInteger($client);
            } else {
                if (!$bUseHeapTable) {
                    $sql = "SELECT visited, idart FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat_archive"] . " AS B WHERE A.idcatart=B.idcatart AND A.idcat=" . cSecurity::toInteger($idcat) . "
                            AND A.idart=" . cSecurity::toInteger($idart) . " AND B.idlang=" . cSecurity::toInteger($lang) . " AND B.idclient=" . cSecurity::toInteger($client) . "
                            AND B.archived='" . $db3->escape($yearmonth) . "'";
                } else {
                    $sql = "SELECT visited, idart FROM " . $db3->escape($sHeapTable) . " WHERE idcat=" . cSecurity::toInteger($idcat) . " AND idart=" . cSecurity::toInteger($idart) . "
                            AND idlang=" . cSecurity::toInteger($lang) . " AND idclient=" . cSecurity::toInteger($client) . " AND archived='" . $db3->escape($yearmonth) . "'";
                }
            }

            $db3->query($sql);
            $db3->nextRecord();

            $inThisLanguage = $db3->f(0);

            if ($online == 0) {
                $offonline = '<img src="' . $cfg['path']['images'] . 'offline_off.gif" alt="' . i18n("Article is offline") . '" title="' . i18n("Article is offline") . '">';
            } else {
                $offonline = '<img src="' . $cfg['path']['images'] . 'online_off.gif" alt="' . i18n("Article is online") . '" title="' . i18n("Article is online") . '">';
            }

            $icon = '<img alt="" src="' . $cfg['path']['images'] . 'article.gif"  class="vAlignMiddle">';
            $tpl->set('d', 'PADDING_LEFT', $padding_left);
            $tpl->set('d', 'TEXT', conHtmlSpecialChars($text) . ' (idart: ' . cSecurity::toInteger($db3->f('idart')) . ')');
            $tpl->set('d', 'ICON', $icon);
            $tpl->set('d', 'STATUS', $offonline);
            $tpl->set('d', 'ROWNAME', implode('_', $aRowname));
            //$tpl->set('d', 'ROWNAME', "HIDE".($db->f("level")+1));
            $tpl->set('d', 'NUMBEROFARTICLES', $numberOfArticles);
            $tpl->set('d', 'TOTAL', $total);
            $tpl->set('d', 'INTHISLANGUAGE', $inThisLanguage);
            $tpl->set('d', 'EXPAND', '<img alt="" src="' . $cfg['path']['images'] . 'spacer.gif" width="7">');
            $tpl->set('d', 'DISPLAY_ROW', 'none');
            $cat_name = "";
            statCreateLocationString($db3->f('idart'), "&nbsp;/&nbsp;", $cat_name);
            $tpl->set('d', 'PATH', i18n("Path") . ":&nbsp;/&nbsp;" . $cat_name);
            $tpl->set('d', 'ULR_TO_PAGE', $frontendURL . 'front_content.php?idart=' . $db3->f('idart'));
            $tpl->next();
            $currentRow++;

            array_pop($aRowname);
        }
    }

    /*
     * hits total
     */
    if (strcmp($yearmonth, "current") == 0) {
        $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat"] . " AS B WHERE A.idcatart=B.idcatart AND B.idclient=" . cSecurity::toInteger($client);
    } else {
        if (!$bUseHeapTable) {
            $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat_archive"] . " AS B WHERE A.idcatart=B.idcatart AND B.idclient=" . cSecurity::toInteger($client) . "
                    AND B.archived='" . $db->escape($yearmonth) . "'";
        } else {
            $sql = "SELECT SUM(visited) FROM " . $db->escape($sHeapTable) . " WHERE idclient=" . cSecurity::toInteger($client) . " AND archived='" . $db->escape($yearmonth) . "'";
        }
    }

    $db->query($sql);
    $db->nextRecord();

    $total = $db->f(0);

    /*
     * hits total on this language
     */
    if (strcmp($yearmonth, "current") == 0) {
        $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat"] . " AS B WHERE A.idcatart=B.idcatart AND B.idlang=" . cSecurity::toInteger($lang) . "
                AND B.idclient=" . cSecurity::toInteger($client);
    } else {
        if (!$bUseHeapTable) {
            $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat_archive"] . " AS B WHERE A.idcatart=B.idcatart AND B.idlang=" . cSecurity::toInteger($lang) . "
                    AND B.idclient=" . cSecurity::toInteger($client) . " AND B.archived='" . $db->escape($yearmonth) . "'";
        } else {
            $sql = "SELECT SUM(visited) FROM " . $db->escape($sHeapTable) . " WHERE idlang=" . cSecurity::toInteger($lang) . " AND idclient=" . cSecurity::toInteger($client) . "
                    AND archived='" . $db->escape($yearmonth) . "'";
        }
    }

    $db->query($sql);
    $db->nextRecord();

    $inThisLanguage = $db->f(0);

    $tpl->set('d', 'TEXT', '&nbsp;');
    $tpl->set('d', 'ICON', '');
    $tpl->set('d', 'STATUS', '');
    $tpl->set('d', 'PADDING_LEFT', '10');
    $tpl->set('d', 'NUMBEROFARTICLES', '');
    $tpl->set('d', 'TOTAL', '');
    $tpl->set('d', 'INTHISLANGUAGE', '');
    $tpl->set('d', 'EXPAND', '');
    $tpl->set('d', 'ROWNAME', '');
    $tpl->set('d', 'DISPLAY_ROW', $sDisplay);

    $tpl->set('s', 'SUMTEXT', i18n("Sum"));
    $tpl->set('s', 'SUMNUMBEROFARTICLES', $sumNumberOfArticles);
    $tpl->set('s', 'SUMTOTAL', $total);
    $tpl->set('s', 'SUMINTHISLANGUAGE', $inThisLanguage);
    $tpl->next();
}

/**
 * Generates a statistics page for a given year
 *
 * @param string $year
 *         Specifies the year to retrieve the statistics for
 *
 * @throws cDbException
 * @throws cException
 */
function statsOverviewYear($year) {
    global $cfg, $db, $tpl, $client, $lang;

    $sDisplay = 'table-row';

    if (preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])) {
        $sDisplay = 'block';
    }

    $sql = "SELECT
                idtree, A.idcat, level, preid, C.name, visible
            FROM
                " . $cfg["tab"]["cat_tree"] . " AS A,
                " . $cfg["tab"]["cat"] . " AS B,
                " . $cfg["tab"]["cat_lang"] . " AS C
            WHERE
                A.idcat=B.idcat AND
                B.idcat=C.idcat AND
                C.idlang=" . cSecurity::toInteger($lang) . " AND
                B.idclient=" . cSecurity::toInteger($client) . "
            ORDER BY idtree";

    $db->query($sql);

    $currentRow = 2;

    $aRowname = array();
    $iLevel = 0;
    $backendUrl = cRegistry::getBackendUrl();
    $tpl->set('s', 'IMG_EXPAND', $backendUrl . $cfg['path']['images'] . 'open_all.gif');
    $tpl->set('s', 'IMG_COLLAPSE', $backendUrl . $cfg['path']['images'] . 'close_all.gif');

    $sumNumberOfArticles = 0;

    while ($db->nextRecord()) {
        if ($db->f("level") == 0 && $db->f("preid") != 0) {
            $tpl->set('d', 'PADDING_LEFT', '10');
            $tpl->set('d', 'TEXT', '&nbsp;');
            $tpl->set('d', 'NUMBEROFARTICLES', '');
            $tpl->set('d', 'TOTAL', '');
            $tpl->set('d', 'STATUS', '');
            $tpl->set('d', 'ICON', '');
            $tpl->set('d', 'INTHISLANGUAGE', '');
            $tpl->set('d', 'EXPAND', '');
            $tpl->set('d', 'DISPLAY_ROW', $sDisplay);
            $tpl->set('d', 'ROWNAME', '');
            $tpl->next();
            $currentRow++;
        }

        $padding_left = 10 + (15 * $db->f("level"));
        $text = $db->f(4);
        $idcat = $db->f("idcat");
        $bCatVisible = $db->f("visible");

        if ($db->f("level") < $iLevel) {
            $iDistance = $iLevel - $db->f("level");

            for ($i = 0; $i < $iDistance; $i++) {
                array_pop($aRowname);
            }
            $iLevel = $db->f("level");
        }

        if ($db->f("level") >= $iLevel) {
            if ($db->f("level") == $iLevel) {
                array_pop($aRowname);
            } else {
                $iLevel = $db->f("level");
            }
            array_push($aRowname, $idcat);
        }

        $db2 = cRegistry::getDb();
        /*
         * number of arts
         */
        $sql = "SELECT COUNT(*) FROM " . $cfg["tab"]["cat_art"] . " WHERE idcat=" . cSecurity::toInteger($idcat);
        $db2->query($sql);
        $db2->nextRecord();

        $numberOfArticles = $db2->f(0);
        $sumNumberOfArticles += $numberOfArticles;
        $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat_archive"] . " AS B WHERE A.idcatart=B.idcatart AND A.idcat=" . cSecurity::toInteger($idcat) . "
                AND B.idclient=" . cSecurity::toInteger($client) . " AND SUBSTRING(B.archived,1,4)=" . cSecurity::toInteger($year) . " GROUP BY SUBSTRING(B.archived,1,4)";
        $db2->query($sql);
        $db2->nextRecord();

        $total = $db2->f(0);

        /*
         * hits of category in this language
         */
        $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat_archive"] . " AS B WHERE A.idcatart=B.idcatart AND A.idcat=" . cSecurity::toInteger($idcat) . "
                AND B.idlang=" . cSecurity::toInteger($lang) . " AND B.idclient=" . cSecurity::toInteger($client) . " AND SUBSTRING(B.archived,1,4)=" . $db2->escape($year) . "
                GROUP BY SUBSTRING(B.archived,1,4)";
        $db2->query($sql);
        $db2->nextRecord();

        $inThisLanguage = $db2->f(0);

        $icon = '<img alt="" src="' . $cfg['path']['images'] . 'folder.gif" class="vAlignMiddle">';

        /*
         * art
         */
        $sql = "SELECT * FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["art"] . " AS B, " . $cfg["tab"]["art_lang"] . " AS C WHERE A.idcat=" . cSecurity::toInteger($idcat) . " AND A.idart=B.idart AND B.idart=C.idart
                AND C.idlang=" . cSecurity::toInteger($lang) . " ORDER BY B.idart";
        $db2->query($sql);

        $numrows = $db2->numRows();

        if ($bCatVisible == 0) {
            $offonline = '<img src="' . $cfg['path']['images'] . 'offline_off.gif" alt="' . i18n("Category is offline") . '" title="' . i18n("Category is offline") . '">';
        } else {
            $offonline = '<img src="' . $cfg['path']['images'] . 'online_off.gif" alt="' . i18n("Category is online") . '" title="' . i18n("Category is online") . '">';
        }

        /*
         * check if there are subcategories
         */
        $iSumSubCategories = 0;
        $sSql = "SELECT count(*) as cat_count from " . $cfg["tab"]["cat"] . " WHERE parentid=" . cSecurity::toInteger($idcat) . ";";
        $db3 = cRegistry::getDb();
        $db3->query($sSql);
        if ($db3->nextRecord()) {
            $iSumSubCategories = $db3->f('cat_count');
        }
        $db3->free();

        $tpl->set('d', 'PADDING_LEFT', $padding_left);
        $tpl->set('d', 'TEXT', conHtmlSpecialChars($text) . ' (idcat: ' . cSecurity::toInteger($db->f('idcat')) . ')');
        $tpl->set('d', 'ICON', $icon);
        $tpl->set('d', 'STATUS', $offonline);
        $tpl->set('d', 'NUMBEROFARTICLES', $numberOfArticles);
        $tpl->set('d', 'TOTAL', $total);
        $tpl->set('d', 'ROWNAME', implode('_', $aRowname));
        $tpl->set('d', 'INTHISLANGUAGE', $inThisLanguage);

        if ($numrows > 0 || $iSumSubCategories > 0) {
            $tpl->set('d', 'EXPAND', '<a href="javascript:changeVisibility(\'' . implode('_', $aRowname) . '\', ' . $db->f("level") . ', ' . $idcat . ')">
                                          <img src="' . $cfg['path']['images'] . 'open_all.gif"
                                               alt="' . i18n("Open category") . '"
                                               title="' . i18n("Open category") . '"
                                               id="' . implode('_', $aRowname) . '_img"
                                               class="vAlignMiddle">
                                      </a>');
        } else {
            $tpl->set('d', 'EXPAND', '<img alt="" src="' . $cfg['path']['images'] . 'spacer.gif" width="7">');
        }

        if ($db->f("level") != 0) {
            $tpl->set('d', 'DISPLAY_ROW', 'none');
        } else {
            $tpl->set('d', 'DISPLAY_ROW', $sDisplay);
        }
        $frontendURL = cRegistry::getFrontendUrl();
        $cat_name = "";
        statCreateLocationString($db->f('idcat'), "&nbsp;/&nbsp;", $cat_name);
        $tpl->set('d', 'PATH', i18n("Path") . ":&nbsp;/&nbsp;" . $cat_name);
        $tpl->set('d', 'ULR_TO_PAGE', $frontendURL . 'front_content.php?idcat=' . $db->f('idcat'));

        $tpl->next();
        $currentRow++;

        $text = "";
        $numberOfArticles = "";
        $total = "";
        $inThisLanguage = "";

        while ($db2->nextRecord()) {
            $idart = $db2->f("idart");

            array_push($aRowname, $idart);

            $text = "";
            $numberOfArticles = "";
            $total = "";
            $inThisLanguage = "";

            $padding_left = 10 + (15 * ($db->f("level") + 1));

            $text = $db2->f("title");
            $online = $db2->f("online");

            /*
             * number of arts
             */
            $db3 = cRegistry::getDb();

            /*
             * hits of art total
             */
            $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat_archive"] . " AS B WHERE A.idcatart=B.idcatart AND A.idcat=" . cSecurity::toInteger($idcat) . "
                    AND A.idart=" . cSecurity::toInteger($idart) . " AND B.idclient=" . cSecurity::toInteger($client) . " AND SUBSTRING(B.archived,1,4)=" . $db3->escape($year) . "
                    GROUP BY SUBSTRING(B.archived,1,4)";
            $db3->query($sql);
            $db3->nextRecord();

            $total = $db3->f(0);

            /*
             * hits of art in this language
             */
            $sql = "SELECT visited FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat_archive"] . " AS B WHERE A.idcatart=B.idcatart AND A.idcat=" . cSecurity::toInteger($idcat) . "
                    AND A.idart=" . cSecurity::toInteger($idart) . " AND B.idlang=" . cSecurity::toInteger($lang) . " AND B.idclient=" . cSecurity::toInteger($client) . "
                    AND SUBSTRING(B.archived,1,4)=" . $db3->escape($year) . " GROUP BY SUBSTRING(B.archived,1,4)";
            $db3->query($sql);
            $db3->nextRecord();

            $inThisLanguage = $db3->f(0);

            if ($online == 0) {
                $offonline = '<img src="' . $cfg['path']['images'] . 'offline_off.gif" alt="' . i18n("Article is offline") . '" title="' . i18n("Article is offline") . '">';
            } else {
                $offonline = '<img src="' . $cfg['path']['images'] . 'online_off.gif" alt="' . i18n("Category is online") . '" title="' . i18n("Category is online") . '">';
            }

            $icon = '<img alt="" src="' . $cfg['path']['images'] . 'article.gif" class="vAlignMiddle">';
            $tpl->set('d', 'PADDING_LEFT', $padding_left);
            $tpl->set('d', 'TEXT', conHtmlSpecialChars($text) . ' (idart: ' . cSecurity::toInteger($idart) . ')');
            $tpl->set('d', 'ICON', $icon);
            $tpl->set('d', 'STATUS', $offonline);
            $tpl->set('d', 'ROWNAME', implode('_', $aRowname));
            $tpl->set('d', 'NUMBEROFARTICLES', $numberOfArticles);
            $tpl->set('d', 'TOTAL', $total);
            $tpl->set('d', 'ROWNAME', implode('_', $aRowname));
            $tpl->set('d', 'EXPAND', '<img alt="" src="' . $cfg['path']['images'] . 'spacer.gif" width="7">');
            $tpl->set('d', 'INTHISLANGUAGE', $inThisLanguage);
            $tpl->set('d', 'DISPLAY_ROW', 'none');
            $cat_name = "";
            statCreateLocationString($idart, "&nbsp;/&nbsp;", $cat_name);
            $tpl->set('d', 'PATH', i18n("Path") . ":&nbsp;/&nbsp;" . $cat_name);
            $tpl->set('d', 'ULR_TO_PAGE', $frontendURL . 'front_content.php?idart=' . $idart);

            $tpl->next();
            $currentRow++;

            array_pop($aRowname);
        }
    }

    /*
     * hits total
     */
    $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat_archive"] . " AS B WHERE A.idcatart=B.idcatart AND B.idclient=" . cSecurity::toInteger($client) . "
            AND SUBSTRING(B.archived,1,4)='" . $db->escape($year) . "' GROUP BY SUBSTRING(B.archived,1,4)";
    $db->query($sql);
    $db->nextRecord();

    $total = $db->f(0);

    /*
     * hits total on this language
     */
    $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat_archive"] . " AS B WHERE A.idcatart=B.idcatart AND B.idlang=" . cSecurity::toInteger($lang) . "
            AND B.idclient=" . cSecurity::toInteger($client) . " AND SUBSTRING(B.archived,1,4)='" . $db->escape($year) . "' GROUP BY SUBSTRING(B.archived,1,4)";
    $db->query($sql);
    $db->nextRecord();

    $inThisLanguage = $db->f(0);

    $tpl->set('d', 'TEXT', '&nbsp;');
    $tpl->set('d', 'ICON', '');
    $tpl->set('d', 'STATUS', '');
    $tpl->set('d', 'PADDING_LEFT', '10');
    $tpl->set('d', 'NUMBEROFARTICLES', '');
    $tpl->set('d', 'TOTAL', '');
    $tpl->set('d', 'EXPAND', '');
    $tpl->set('d', 'ROWNAME', '');
    $tpl->set('d', 'INTHISLANGUAGE', '');
    $tpl->set('d', 'DISPLAY_ROW', $sDisplay);
    $tpl->set('s', 'SUMTEXT', "Summe");
    $tpl->set('s', 'SUMNUMBEROFARTICLES', $sumNumberOfArticles);
    $tpl->set('s', 'SUMTOTAL', $total);
    $tpl->set('s', 'SUMINTHISLANGUAGE', $inThisLanguage);
    $tpl->next();
}

/**
 * Generates a top<n> statistics page
 *
 * @param string $yearmonth
 *         Specifies the year and month from which to retrieve the statistics,
 *         specify "current" to retrieve the current entries.
 * @param int    $top
 *         Specifies the amount of pages to display
 *
 * @throws cDbException
 * @throws cException
 */
function statsOverviewTop($yearmonth, $top) {
    global $cfg, $db, $tpl, $client, $cfgClient, $lang;

    if (strcmp($yearmonth, "current") == 0) {
        $sql = "SELECT DISTINCT
                    C.title, A.visited, C.idart
                FROM
                    " . $cfg["tab"]["stat"] . " AS A,
                    " . $cfg["tab"]["cat_art"] . " AS B,
                    " . $cfg["tab"]["art_lang"] . " AS C
                WHERE
                    C.idart = B.idart AND
                    C.idlang = A.idlang AND
                    B.idcatart = A.idcatart AND
                    A.idclient = " . cSecurity::toInteger($client) . " AND
                    A.idlang = " . cSecurity::toInteger($lang) . "
                ORDER BY A.visited DESC
                LIMIT " . $db->escape($top);
    } else {
        $sql = "SELECT DISTINCT
                    C.title, A.visited, B.idcat, C.idart
                FROM
                    " . $cfg["tab"]["stat_archive"] . " AS A,
                    " . $cfg["tab"]["cat_art"] . " AS B,
                    " . $cfg["tab"]["art_lang"] . " AS C
                WHERE
                    C.idart = B.idart AND
                    C.idlang = A.idlang AND
                    B.idcatart = A.idcatart AND
                    A.idclient = " . cSecurity::toInteger($client) . " AND
                    A.archived = '" . $db->escape($yearmonth) . "' AND
                    A.idlang = " . cSecurity::toInteger($lang) . " ORDER BY
                    A.visited DESC
                LIMIT " . $db->escape($top);
    }

    $db->query($sql);

    $frontendURL = cRegistry::getFrontendUrl();
    while ($db->nextRecord()) {
        $cat_name = "";
        statCreateLocationString($db->f(2), "&nbsp;/&nbsp;", $cat_name);
        $tpl->set('d', 'PADDING_LEFT', '5');
        $tpl->set('d', 'PATH', i18n("Path") . ":&nbsp;/&nbsp;" . $cat_name);
        $tpl->set('d', 'TEXT', conHtmlSpecialChars($db->f(0)) . ' (idart: ' . cSecurity::toInteger($db->f('idart')) . ')');
        $tpl->set('d', 'TOTAL', $db->f(1));
        $tpl->set('d', 'ULR_TO_PAGE', $frontendURL . 'front_content.php?idart=' . $db->f('idart'));
        $tpl->next();
    }
}

/**
 * Generates the location string for passed category id.
 *
 * Performs a recursive call, if parent category doesn't matches to 0
 *
 * @param int    $idcat
 *         The category id
 * @param string $seperator
 *         Separator for location string
 * @param string $cat_str
 *         The location string variable (reference)
 *
 * @throws cException
 * @throws cInvalidArgumentException
 */
function statCreateLocationString($idcat, $seperator, &$cat_str) {
    $cats = array();

    // get category path
    $helper = cCategoryHelper::getInstance();
    foreach ($helper->getCategoryPath($idcat) as $categoryLang) {
        $cats[] = $categoryLang->getField('name');
    }

    $cat_str = implode($seperator, $cats);
}

/**
 * Generates a top<n> statistics page
 *
 * @param int $year
 *         Specifies the year from which to retrieve the statistics
 * @param int $top
 *         Specifies the amount of pages to display
 *
 * @throws cDbException
 * @throws cException
 * @throws cInvalidArgumentException
 */
function statsOverviewTopYear($year, $top) {
    global $cfg, $db, $tpl, $client, $lang, $cfgClient;

    $sql = "SELECT
                C.title, SUM(A.visited) as visited, B.idcat AS idcat, C.idart AS idart
            FROM
                " . $cfg["tab"]["stat_archive"] . " AS A,
                " . $cfg["tab"]["cat_art"] . " AS B,
                " . $cfg["tab"]["art_lang"] . " AS C
            WHERE
                C.idart = B.idart AND
                C.idlang = A.idlang AND
                B.idcatart = A.idcatart AND
                A.idclient = " . cSecurity::toInteger($client) . " AND
                A.archived LIKE '" . $db->escape($year) . "%' AND
                A.idlang = " . cSecurity::toInteger($lang) . "
            GROUP BY A.idcatart
            ORDER BY visited DESC
            LIMIT " . $db->escape($top);

    $db->query($sql);
    $frontendURL = cRegistry::getFrontendUrl();
    while ($db->nextRecord()) {
        $cat_name = '';
        statCreateLocationString($db->f('idcat'), "&nbsp;/&nbsp;", $cat_name);

        $tpl->set('d', 'PADDING_LEFT', '0');
        $tpl->set('d', 'PATH', i18n("Path") . ":&nbsp;/&nbsp;" . $cat_name);
        $tpl->set('d', 'TEXT', conHtmlSpecialChars($db->f(0)) . ' (idart: ' . cSecurity::toInteger($db->f('idart')) . ')');
        $tpl->set('d', 'TOTAL', $db->f(1));
        $tpl->set('d', 'ULR_TO_PAGE', $frontendURL . 'front_content.php?idart=' . $db->f('idart'));
        $tpl->next();
    }
}

/**
 * Returns a drop down to choose the stats to display
 *
 * @param string $default
 *
 * @return string
 *         Returns a drop down string
 *
 * @throws cException
 */
function statDisplayTopChooser($default) {
    if ($default == "top10") {
        $defaultTop10 = "selected";
    }
    if ($default == "top20") {
        $defaultTop20 = "selected";
    }
    if ($default == "top30") {
        $defaultTop30 = "selected";
    }
    if ($default == "all") {
        $defaultAll = "selected";
    }

    return ("<form name=\"name\">" .
            "  <select class=\"text_medium\" onchange=\"top10Action(this)\">" .
            "    <option value=\"top10\" $defaultTop10>" . i18n("Top 10") . "</option>" .
            "    <option value=\"top20\" $defaultTop20>" . i18n("Top 20") . "</option>" .
            "    <option value=\"top30\" $defaultTop30>" . i18n("Top 30") . "</option>" .
            "    <option value=\"all\" $defaultAll>" . i18n("All") . "</option>" .
            "  </select>" .
            "</form>");
}

/**
 * Returns a drop down to choose the stats to display for yearly summary pages
 *
 * @param string $default
 *
 * @return string
 *         Returns a drop down string
 *
 * @throws cException
 */
function statDisplayYearlyTopChooser($default) {
    $defaultAll = $defaultTop10 = $defaultTop20 = $defaultTop30 = '';
    
    if ($default == "top10") {
        $defaultTop10 = "selected";
    }
    if ($default == "top20") {
        $defaultTop20 = "selected";
    }
    if ($default == "top30") {
        $defaultTop30 = "selected";
    }
    if ($default == "all") {
        $defaultAll = "selected";
    }

    return ("<form name=\"name\">" .
            "  <select class=\"text_medium\" onchange=\"top10ActionYearly(this)\">" .
            "    <option value=\"top10\" $defaultTop10>" . i18n("Top 10") . "</option>" .
            "    <option value=\"top20\" $defaultTop20>" . i18n("Top 20") . "</option>" .
            "    <option value=\"top30\" $defaultTop30>" . i18n("Top 30") . "</option>" .
            "    <option value=\"all\" $defaultAll>" . i18n("All") . "</option>" .
            "  </select>" .
            "</form>");
}

/**
 * Return an array with all years which are available as stat files.
 *
 * @param int $client
 * @param int $lang
 *
 * @return array
 *         Array of strings with years.
 *
 * @throws cDbException
 */
function statGetAvailableYears($client, $lang) {
    global $cfg, $db;

    $sql = "SELECT
                SUBSTRING(`archived`, 1, 4)
            FROM
                " . $cfg["tab"]["stat_archive"] . "
            WHERE
                idlang = " . cSecurity::toInteger($lang) . " AND
                idclient = " . cSecurity::toInteger($client) . "
            GROUP BY
                SUBSTRING(`archived`, 1, 4)
            ORDER BY
                SUBSTRING(`archived`, 1, 4) DESC";
    $db->query($sql);

    $availableYears = array();
    while ($db->nextRecord()) {
        $availableYears[] = $db->f(0);
    }

    return $availableYears;
}

/**
 * Return an array with all months for a specific year which are available
 * as stat files.
 *
 * @param string $year
 * @param int    $client
 * @param int    $lang
 *
 * @return array
 *         Array of strings with months.
 *
 * @throws cDbException
 */
function statGetAvailableMonths($year, $client, $lang) {
    global $cfg, $db;

    $availableYears = array();

    $sql = "SELECT
                SUBSTRING(`archived`, 5, 2)
            FROM
                " . $cfg["tab"]["stat_archive"] . "
            WHERE
                idlang = " . cSecurity::toInteger($lang) . " AND
                idclient = " . cSecurity::toInteger($client) . " AND
                SUBSTRING(`archived`, 1, 4) = '" . $db->escape($year) . "'
            GROUP BY
                SUBSTRING(`archived`, 5, 2)
            ORDER BY
                SUBSTRING(`archived`, 5, 2) DESC";

    $db->query($sql);
    while ($db->nextRecord()) {
        $availableYears[] = $db->f(0);
    }

    return $availableYears;
}

/**
 * Resets the statistic for passed client
 *
 * @param int $client
 *         Id of client
 *
 * @throws cDbException
 */
function statResetStatistic($client) {
    global $db, $cfg;
    $sql = "UPDATE " . $cfg["tab"]["stat"] . " SET visited=0 WHERE idclient=" . cSecurity::toInteger($client);
    $db->query($sql);
}

/**
 * Deletes existing heap table (table in memory) and creates it.
 *
 * @param string $sHeapTable
 *         Table name
 * @param cDb    $db
 *         Database object
 *
 * @throws cDbException
 */
function buildHeapTable($sHeapTable, $db) {
    global $cfg;

    $sql = "DROP TABLE IF EXISTS " . $db->escape($sHeapTable) . ";";
    $db->query($sql);

    $sql = "CREATE TABLE " . $db->escape($sHeapTable) . " TYPE=HEAP
                SELECT
                    A.idcatart,
                    A.idcat,
                    A.idart,
                    B.idstatarch,
                    B.archived,
                    B.idlang,
                    B.idclient,
                    B.visited
                FROM
                    " . $cfg['tab']['cat_art'] . " AS A, " . $cfg['tab']['stat_archive'] . " AS B
                WHERE
                    A.idcatart = B.idcatart;";
    $db->query($sql);

    $sql = "ALTER TABLE `" . $db->escape($sHeapTable) . "` ADD PRIMARY KEY (`idcatart`,`idcat` ,`idart`,`idstatarch` ,`archived`,`idlang`,`idclient` ,`visited`);";
    $db->query($sql);
}
