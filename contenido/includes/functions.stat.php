<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Define the "stat" related functions
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package Contenido Backend includes
 * @version 1.0.3
 * @author Olaf Niemann
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since file available since contenido release <= 4.6
 *
 *        {@internal
 *        created 2002-03-02
 *        modified 2008-06-26, Frederic Schneider, add security fix
 *        modified 2008-07-22, Ingo van Peeren, fixed SQL syntax error due to
 *        security fix
 *        modified 2009-10-23, Murat Purc, removed deprecated function (PHP 5.3
 *        ready) and commented code
 *
 *        $Id: functions.stat.php 1085 2009-10-24 02:01:34Z xmurrix $:
 *        }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude("includes", "functions.database.php");

/**
 * Displays statistic information layer (a div Tag)
 *
 * @param int $id Either article or directory id
 * @param string $type The type
 * @param int $x Style top position
 * @param int $y Style left position
 * @param int $w Style width
 * @param int $h Style height
 * @return string Composed info layer
 */
function statsDisplayInfo($id, $type, $x, $y, $w, $h) {
    if (strcmp($type, "article" == 0)) {
        $text = i18n("Info about article") . " " . $id;
    } else {
        $text = i18n("Info about directory") . " " . $id;
    }

    $div = '<DIV ID="idElement14" class="text_medium" style="background: #E8E8EE;
             border: 1px; border-style: solid; border-color: #B3B3B3; position:absolute;
             top:' . $x . 'px; left:' . $y . '.px; width:' . $w . 'px; height:' . $h . 'px;">' . $text . '</DIV>';

    return $div;
}

/**
 * Archives the current statistics
 *
 * @param $yearmonth String with the desired archive date (YYYYMM)
 *
 * @return none
 *
 */
function statsArchive($yearmonth) {
    global $cfg;

    $yearmonth = preg_replace('/\s/', '0', $yearmonth);

    $db = new DB_Contenido();
    $db2 = new DB_Contenido();

    $sql = "SELECT
                idcatart, idlang, idclient, visited, visitdate
            FROM
                " . $cfg["tab"]["stat"];

    $db->query($sql);

    while ($db->next_record()) {
        $insertSQL = "INSERT INTO
                          " . $cfg["tab"]["stat_archive"] . "
                          ( idstatarch, archived, idcatart, idlang, idclient, visited, visitdate)
                      VALUES
                          (" . Contenido_Security::toInteger($db2->nextid($cfg["tab"]["stat_archive"])) . ",
                           " . $yearmonth . ",
                           " . Contenido_Security::toInteger($db->f(0)) . ",
                           " . Contenido_Security::toInteger($db->f(1)) . ",
                           " . Contenido_Security::toInteger($db->f(2)) . ",
                           " . Contenido_Security::toInteger($db->f(3)) . ",
                           '" . Contenido_Security::escapeDB($db->f(4), $db2) . "')";

        $db2->query($insertSQL);
    }

    $sql = "DELETE FROM " . $cfg["tab"]["stat"];
    $db->query($sql);

    // Recreate empty stats
    $sql = "SELECT
                A.idcatart,
                B.idclient,
                C.idlang
            FROM
                " . $cfg["tab"]["cat_art"] . " AS A INNER JOIN
                " . $cfg["tab"]["cat"] . " AS B ON A.idcat = B.idcat INNER JOIN
                " . $cfg["tab"]["cat_lang"] . " AS C ON A.idcat = C.idcat ";

    $db->query($sql);

    while ($db->next_record()) {
        $insertSQL = "INSERT INTO
                          " . $cfg["tab"]["stat"] . "
                          ( idstat, idcatart, idlang, idclient, visited )
                      VALUES (
                          " . Contenido_Security::toInteger($db2->nextid($cfg["tab"]["stat"])) . ",
                          " . Contenido_Security::toInteger($db->f(0)) . ",
                          " . Contenido_Security::toInteger($db->f(2)) . ",
                          " . Contenido_Security::toInteger($db->f(1)) . ",
                          '0000-00-00 00:00:00')";

        $db2->query($insertSQL);
    }
}

/**
 * Generates a statistics page
 *
 * @param $yearmonth Specifies the year and month from which to retrieve the
 *        statistics, specify "current" to retrieve the current
 *        entries
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 *         @modified Timo A. Hummel <Timo.Hummel@4fb.de>
 * @copyright four for business AG <http://www.4fb.de>
 *
 * @return none
 *
 */
function statsOverviewAll($yearmonth) {
    global $cfg, $db, $tpl, $client, $lang;

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
                    A.idcat=B.idcat
                AND
                    B.idcat=C.idcat
                AND
                    C.idlang='" . Contenido_Security::toInteger($lang) . "'
                AND
                    B.idclient='" . Contenido_Security::toInteger($client) . "'
                ORDER BY
                    idtree";

    $db->query($sql);

    $currentRow = 2;

    $aRowname = array();
    $iLevel = 0;

    $tpl->set('s', 'IMG_EXPAND', $cfg["path"]["contenido_fullhtml"] . $cfg['path']['images'] . 'open_all.gif');
    $tpl->set('s', 'IMG_COLLAPSE', $cfg["path"]["contenido_fullhtml"] . $cfg['path']['images'] . 'close_all.gif');

    while ($db->next_record()) {
        if ($db->f("level") == 0 && $db->f("preid") != 0) {
            $bgcolor = '#FFFFFF';
            $tpl->set('d', 'BGCOLOR', $bgcolor);
            $tpl->set('d', 'PADDING_LEFT', '10');
            $tpl->set('d', 'TEXT', '&nbsp;');
            $tpl->set('d', 'NUMBEROFARTICLES', '');
            $tpl->set('d', 'TOTAL', '');
            $tpl->set('d', 'ICON', '');
            $tpl->set('d', 'STATUS', '');
            $tpl->set('d', 'ONCLICK', '');
            $tpl->set('d', 'ROWNAME', '');
            $tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
            $tpl->set('d', 'INTHISLANGUAGE', '');
            $tpl->set('d', 'EXPAND', '');
            $tpl->set('d', 'DISPLAY_ROW', $sDisplay);

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

        $db2 = new DB_Contenido();
        // ************** number of arts **************
        $sql = "SELECT COUNT(*) FROM " . $cfg["tab"]["cat_art"] . " WHERE idcat='" . Contenido_Security::toInteger($idcat) . "'";
        $db2->query($sql);
        $db2->next_record();

        $numberOfArticles = $db2->f(0);
        $sumNumberOfArticles += $numberOfArticles;
        // ************** hits of category total**************
        if (strcmp($yearmonth, "current") == 0) {
            $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat"] . " AS B WHERE A.idcatart=B.idcatart AND A.idcat='" . Contenido_Security::toInteger($idcat) . "' AND B.idclient='" . Contenido_Security::toInteger($client) . "'";
        } else {
            if (!$bUseHeapTable) {
                $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat_archive"] . " AS B WHERE A.idcatart=B.idcatart AND A.idcat='" . Contenido_Security::toInteger($idcat) . "'
                        AND B.idclient='" . Contenido_Security::toInteger($client) . "' AND B.archived = '" . Contenido_Security::escapeDB($yearmonth, $db2) . "'";
            } else {
                $sql = "SELECT SUM(visited) FROM " . Contenido_Security::escapeDB($sHeapTable, $db2) . " WHERE idcat='" . Contenido_Security::toInteger($idcat) . "'
                        AND idclient='" . Contenido_Security::toInteger($client) . "' AND archived = '" . Contenido_Security::escapeDB($yearmonth, $db2) . "'";
            }
        }
        $db2->query($sql);
        $db2->next_record();

        $total = $db2->f(0);

        // ************** hits of category in this language ***************
        if (strcmp($yearmonth, "current") == 0) {
            $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat"] . " AS B WHERE A.idcatart=B.idcatart AND A.idcat='" . Contenido_Security::toInteger($idcat) . "'
                    AND B.idlang='" . Contenido_Security::toInteger($lang) . "' AND B.idclient='" . Contenido_Security::toInteger($client) . "'";
        } else {
            if (!$bUseHeapTable) {
                $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat_archive"] . " AS B WHERE A.idcatart=B.idcatart AND A.idcat='" . Contenido_Security::toInteger($idcat) . "'
                        AND B.idlang='" . Contenido_Security::toInteger($lang) . "' AND B.idclient='" . Contenido_Security::toInteger($client) . "' AND B.archived = '" . Contenido_Security::escapeDB($yearmonth, $db2) . "'";
            } else {
                $sql = "SELECT SUM(visited) FROM " . Contenido_Security::escapeDB($sHeapTable, $db2) . " WHERE idcat='" . Contenido_Security::toInteger($idcat) . "' AND idlang='" . Contenido_Security::toInteger($lang) . "'
                        AND idclient='" . Contenido_Security::toInteger($client) . "' AND archived = '" . Contenido_Security::escapeDB($yearmonth, $db2) . "'";
            }
        }

        $db2->query($sql);
        $db2->next_record();

        $inThisLanguage = $db2->f(0);

        $icon = '<img src="' . $cfg['path']['images'] . 'folder.gif" style="vertical-align:top;">';

        // ************ art ********************************
        $sql = "SELECT * FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["art"] . " AS B, " . $cfg["tab"]["art_lang"] . " AS C WHERE A.idcat='" . Contenido_Security::toInteger($idcat) . "'
                AND A.idart=B.idart AND B.idart=C.idart AND C.idlang='" . Contenido_Security::toInteger($lang) . "' ORDER BY B.idart";
        $db2->query($sql);

        $numrows = $db2->num_rows();
        $onclick = "";

        $online = $db->f("visible");
        if ($bCatVisible == 1) {
            $offonline = '<img src="' . $cfg['path']['images'] . 'online_off.gif" alt="' . i18n("Category is online") . '" title="' . i18n("Category is online") . '">';
        } else {
            $offonline = '<img src="' . $cfg['path']['images'] . 'offline_off.gif" alt="' . i18n("Category is offline") . '" title="' . i18n("Category is offline") . '">';
        }

        // ************check if there are subcategories ******************
        $iSumSubCategories = 0;
        $sSql = "SELECT count(*) as cat_count from " . $cfg["tab"]["cat"] . " WHERE parentid = '" . Contenido_Security::toInteger($idcat) . "';";
        $db3 = new DB_contenido();
        $db3->query($sSql);
        if ($db3->next_record()) {
            $iSumSubCategories = $db3->f('cat_count');
        }
        $db3->free();

        $bgcolor = $cfg["color"]["table_dark"];
        $tpl->set('d', 'BGCOLOR', $bgcolor);
        $tpl->set('d', 'PADDING_LEFT', $padding_left);
        $tpl->set('d', 'TEXT', $text);
        $tpl->set('d', 'ONCLICK', $onclick);
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
                                               style="vertical-align:top; margin-top:6px;">
                                      </a>');
        } else {
            $tpl->set('d', 'EXPAND', '<img src="' . $cfg['path']['images'] . 'spacer.gif" width="7">');
        }
        $tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
        $tpl->set('d', 'INTHISLANGUAGE', $inThisLanguage);
        if ($db->f("level") != 0) {
            $tpl->set('d', 'DISPLAY_ROW', 'none');
        } else {
            $tpl->set('d', 'DISPLAY_ROW', $sDisplay);
        }

        $tpl->next();
        $currentRow++;

        $onclick = "";

        $text = "";
        $numberOfArticles = "";
        $total = "";
        $inThisLanguage = "";

        while ($db2->next_record()) {
            $idart = $db2->f("idart");

            array_push($aRowname, $idart);

            $text = "";
            $numberOfArticles = "";
            $total = "";
            $inThisLanguage = "";

            $padding_left = 10 + (15 * ($db->f("level") + 1));

            $text = $db2->f("title");
            $online = $db2->f("online");

            // ************** number of arts **************
            $db3 = new DB_contenido();

            // ************** hits of art total **************
            if (strcmp($yearmonth, "current") == 0) {
                $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat"] . " AS B WHERE A.idcatart=B.idcatart AND A.idcat='" . Contenido_Security::toInteger($idcat) . "'
                     AND A.idart='" . Contenido_Security::toInteger($idart) . "' AND B.idclient='" . Contenido_Security::toInteger($client) . "'";
            } else {
                if (!$bUseHeapTable) {
                    $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat_archive"] . " AS B WHERE A.idcatart=B.idcatart AND A.idcat='" . Contenido_Security::toInteger($idcat) . "'
                            AND A.idart='" . Contenido_Security::toInteger($idart) . "' AND B.idclient='" . Contenido_Security::toInteger($client) . "' and B.archived = '" . Contenido_Security::escapeDB($yearmonth, $db3) . "'";
                } else {
                    $sql = "SELECT SUM(visited) FROM " . Contenido_Security::escapeDB($sHeapTable, $db3) . " WHERE idcat='" . Contenido_Security::toInteger($idcat) . "' AND idart='" . Contenido_Security::toInteger($idart) . "'
                            AND idclient='" . Contenido_Security::toInteger($client) . "' AND archived = '" . Contenido_Security::escapeDB($yearmonth, $db3) . "'";
                }
            }

            $db3->query($sql);
            $db3->next_record();

            $total = $db3->f(0);

            // ************** hits of art in this language ***************
            if (strcmp($yearmonth, "current") == 0) {
                $sql = "SELECT visited FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat"] . " AS B WHERE A.idcatart=B.idcatart AND A.idcat='" . Contenido_Security::toInteger($idcat) . "'
                        AND A.idart='" . Contenido_Security::toInteger($idart) . "' AND B.idlang='" . Contenido_Security::toInteger($lang) . "' AND B.idclient='" . Contenido_Security::toInteger($client) . "'";
            } else {
                if (!$bUseHeapTable) {
                    $sql = "SELECT visited FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat_archive"] . " AS B WHERE A.idcatart=B.idcatart AND A.idcat='" . Contenido_Security::toInteger($idcat) . "'
                            AND A.idart='" . Contenido_Security::toInteger($idart) . "' AND B.idlang='" . Contenido_Security::toInteger($lang) . "' AND B.idclient='" . Contenido_Security::toInteger($client) . "'
                            AND B.archived = '" . Contenido_Security::escapeDB($yearmonth, $db3) . "'";
                } else {
                    $sql = "SELECT visited FROM " . Contenido_Security::escapeDB($sHeapTable, $db3) . " WHERE idcat='" . Contenido_Security::toInteger($idcat) . "' AND idart='" . Contenido_Security::toInteger($idart) . "'
                            AND idlang='" . Contenido_Security::toInteger($lang) . "' AND idclient='" . Contenido_Security::toInteger($client) . "' AND archived = '" . Contenido_Security::escapeDB($yearmonth, $db3) . "'";
                }
            }

            $db3->query($sql);
            $db3->next_record();

            $inThisLanguage = $db3->f(0);

            if ($online == 0) {
                $offonline = '<img src="' . $cfg['path']['images'] . 'offline_off.gif" alt="Artikel ist offline" title="Artikel ist offline">';
            } else {
                $offonline = '<img src="' . $cfg['path']['images'] . 'online_off.gif" alt="Artikel ist online" title="Artikel ist online">';
            }

            $icon = '<img src="' . $cfg['path']['images'] . 'article.gif" style="vertical-align:top;">';
            $bgcolor = $cfg["color"]["table_light"];
            $tpl->set('d', 'BGCOLOR', $bgcolor);
            $tpl->set('d', 'PADDING_LEFT', $padding_left);
            $tpl->set('d', 'TEXT', $text);
            $tpl->set('d', 'ONCLICK', "");
            $tpl->set('d', 'ICON', $icon);
            $tpl->set('d', 'STATUS', $offonline);
            $tpl->set('d', 'ROWNAME', implode('_', $aRowname));
            // $tpl->set('d', 'ROWNAME', "HIDE".($db->f("level")+1));
            $tpl->set('d', 'NUMBEROFARTICLES', $numberOfArticles);
            $tpl->set('d', 'TOTAL', $total);
            $tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
            $tpl->set('d', 'INTHISLANGUAGE', $inThisLanguage);
            $tpl->set('d', 'EXPAND', '<img src="' . $cfg['path']['images'] . 'spacer.gif" width="7">');
            $tpl->set('d', 'DISPLAY_ROW', 'none');
            $tpl->next();
            $currentRow++;

            array_pop($aRowname);
        }
    }

    // ************** hits total**************
    if (strcmp($yearmonth, "current") == 0) {
        $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat"] . " AS B WHERE A.idcatart=B.idcatart AND B.idclient='" . Contenido_Security::toInteger($client) . "'";
    } else {
        if (!$bUseHeapTable) {
            $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat_archive"] . " AS B WHERE A.idcatart=B.idcatart AND B.idclient='" . Contenido_Security::toInteger($client) . "'
                    AND B.archived = '" . Contenido_Security::escapeDB($yearmonth, $db) . "'";
        } else {
            $sql = "SELECT SUM(visited) FROM " . Contenido_Security::escapeDB($sHeapTable, $db) . " WHERE idclient='" . Contenido_Security::toInteger($client) . "' AND archived = '" . Contenido_Security::escapeDB($yearmonth, $db) . "'";
        }
    }

    $db->query($sql);
    $db->next_record();

    $total = $db->f(0);

    // ************** hits total on this language ***************
    if (strcmp($yearmonth, "current") == 0) {
        $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat"] . " AS B WHERE A.idcatart=B.idcatart AND B.idlang='" . Contenido_Security::toInteger($lang) . "'
                AND B.idclient='" . Contenido_Security::toInteger($client) . "'";
    } else {
        if (!$bUseHeapTable) {
            $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat_archive"] . " AS B WHERE A.idcatart=B.idcatart AND B.idlang='" . Contenido_Security::toInteger($lang) . "'
                    AND B.idclient='" . Contenido_Security::toInteger($client) . "' AND B.archived = '" . Contenido_Security::escapeDB($yearmonth, $db) . "'";
        } else {
            $sql = "SELECT SUM(visited) FROM " . Contenido_Security::escapeDB($sHeapTable, $db) . " WHERE idlang='" . Contenido_Security::toInteger($lang) . "' AND idclient='" . Contenido_Security::toInteger($client) . "'
                    AND archived = '" . Contenido_Security::escapeDB($yearmonth, $db) . "'";
        }
    }

    $db->query($sql);
    $db->next_record();

    $inThisLanguage = $db->f(0);

    $bgcolor = '#FFFFFF';
    $tpl->set('d', 'BGCOLOR', $bgcolor);
    $tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
    $tpl->set('d', 'TEXT', '&nbsp;');
    $tpl->set('d', 'ICON', '');
    $tpl->set('d', 'STATUS', '');
    $tpl->set('d', 'PADDING_LEFT', '10');
    $tpl->set('d', 'NUMBEROFARTICLES', '');
    $tpl->set('d', 'TOTAL', '');
    $tpl->set('d', 'INTHISLANGUAGE', '');
    $tpl->set('d', 'EXPAND', '');
    $tpl->set('d', 'ROWNAME', '');
    $tpl->set('d', 'ONCLICK', '');
    $tpl->set('d', 'DISPLAY_ROW', $sDisplay);

    $bgcolor = $cfg["color"]["table_dark"];
    $tpl->set('s', 'SUMBGCOLOR', $cfg["color"]["table_dark"]);
    $tpl->set('s', 'SUMTEXT', i18n("Sum"));
    $tpl->set('s', 'SUMNUMBEROFARTICLES', $sumNumberOfArticles);
    $tpl->set('s', 'SUMTOTAL', $total);
    $tpl->set('s', 'SUMINTHISLANGUAGE', $inThisLanguage);
    $tpl->next();
}

/**
 * Generates a statistics page for a given year
 *
 * @param $year Specifies the year to retrieve the
 *        statistics for
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 *         @modified Timo A. Hummel <Timo.Hummel@4fb.de>
 * @copyright four for business AG <http://www.4fb.de>
 *
 * @return none
 *
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
                    A.idcat=B.idcat
                AND
                    B.idcat=C.idcat
                AND
                    C.idlang='" . Contenido_Security::toInteger($lang) . "'
                AND
                    B.idclient='" . Contenido_Security::toInteger($client) . "'
                ORDER BY
                    idtree";

    $db->query($sql);

    $currentRow = 2;

    $aRowname = array();
    $iLevel = 0;

    $tpl->set('s', 'IMG_EXPAND', $cfg["path"]["contenido_fullhtml"] . $cfg['path']['images'] . 'open_all.gif');
    $tpl->set('s', 'IMG_COLLAPSE', $cfg["path"]["contenido_fullhtml"] . $cfg['path']['images'] . 'close_all.gif');

    while ($db->next_record()) {
        if ($db->f("level") == 0 && $db->f("preid") != 0) {
            $bgcolor = '#FFFFFF';
            $tpl->set('d', 'BGCOLOR', $bgcolor);
            $tpl->set('d', 'PADDING_LEFT', '10');
            $tpl->set('d', 'TEXT', '&nbsp;');
            $tpl->set('d', 'NUMBEROFARTICLES', '');
            $tpl->set('d', 'TOTAL', '');
            $tpl->set('d', 'STATUS', '');
            $tpl->set('d', 'ONCLICK', '');
            $tpl->set('d', 'ICON', '');
            $tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
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

        $db2 = new DB_Contenido();
        // ************** number of arts **************
        $sql = "SELECT COUNT(*) FROM " . $cfg["tab"]["cat_art"] . " WHERE idcat='" . Contenido_Security::toInteger($idcat) . "'";
        $db2->query($sql);
        $db2->next_record();

        $numberOfArticles = $db2->f(0);
        $sumNumberOfArticles += $numberOfArticles;
        $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat_archive"] . " AS B WHERE A.idcatart=B.idcatart AND A.idcat='" . Contenido_Security::toInteger($idcat) . "'
                AND B.idclient='" . Contenido_Security::toInteger($client) . "' AND SUBSTRING(B.archived,1,4) = " . Contenido_Security::toInteger($year, $db2) . " GROUP BY SUBSTRING(B.archived,1,4)";

        $db2->query($sql);
        $db2->next_record();

        $total = $db2->f(0);

        // ************** hits of category in this language ***************
        $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat_archive"] . " AS B WHERE A.idcatart=B.idcatart AND A.idcat='" . Contenido_Security::toInteger($idcat) . "'
                AND B.idlang='" . Contenido_Security::toInteger($lang) . "' AND B.idclient='" . Contenido_Security::toInteger($client) . "' AND SUBSTRING(B.archived,1,4) = " . Contenido_Security::escapeDB($year, $db2) . "
                GROUP BY SUBSTRING(B.archived,1,4)";

        $db2->query($sql);
        $db2->next_record();

        $inThisLanguage = $db2->f(0);

        $icon = '<img src="' . $cfg['path']['images'] . 'folder.gif" style="vertical-align:top;">';

        // ************ art ********************************
        $sql = "SELECT * FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["art"] . " AS B, " . $cfg["tab"]["art_lang"] . " AS C WHERE A.idcat='" . Contenido_Security::toInteger($idcat) . "' AND A.idart=B.idart AND B.idart=C.idart
                AND C.idlang='" . Contenido_Security::toInteger($lang) . "' ORDER BY B.idart";
        $db2->query($sql);

        $numrows = $db2->num_rows();
        $onclick = "";

        if ($bCatVisible == 0) {
            $offonline = '<img src="' . $cfg['path']['images'] . 'offline_off.gif" alt="Kategorie ist offline" title="Kategorie ist unsichtbar">';
        } else {
            $offonline = '<img src="' . $cfg['path']['images'] . 'online_off.gif" alt="Kategorie ist online" title="Kategorie ist sichtbar">';
        }

        // ************check if there are subcategories ******************
        $iSumSubCategories = 0;
        $sSql = "SELECT count(*) as cat_count from " . $cfg["tab"]["cat"] . " WHERE parentid = '" . Contenido_Security::toInteger($idcat) . "';";
        $db3 = new DB_contenido();
        $db3->query($sSql);
        if ($db3->next_record()) {
            $iSumSubCategories = $db3->f('cat_count');
        }
        $db3->free();

        $bgcolor = $cfg["color"]["table_dark"];
        $tpl->set('d', 'BGCOLOR', $bgcolor);
        $tpl->set('d', 'PADDING_LEFT', $padding_left);
        $tpl->set('d', 'TEXT', $text);
        $tpl->set('d', 'ONCLICK', $onclick);
        $tpl->set('d', 'ICON', $icon);
        $tpl->set('d', 'STATUS', $offonline);
        $tpl->set('d', 'NUMBEROFARTICLES', $numberOfArticles);
        $tpl->set('d', 'TOTAL', $total);
        $tpl->set('d', 'ROWNAME', implode('_', $aRowname));
        $tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
        $tpl->set('d', 'INTHISLANGUAGE', $inThisLanguage);

        if ($numrows > 0 || $iSumSubCategories > 0) {
            $tpl->set('d', 'EXPAND', '<a href="javascript:changeVisibility(\'' . implode('_', $aRowname) . '\', ' . $db->f("level") . ', ' . $idcat . ')">
                                          <img src="' . $cfg['path']['images'] . 'open_all.gif"
                                               alt="' . i18n("Open category") . '"
                                               title="' . i18n("Open category") . '"
                                               id="' . implode('_', $aRowname) . '_img"
                                               style="vertical-align:top; margin-top:6px;">
                                      </a>');
        } else {
            $tpl->set('d', 'EXPAND', '<img src="' . $cfg['path']['images'] . 'spacer.gif" width="7">');
        }

        if ($db->f("level") != 0) {
            $tpl->set('d', 'DISPLAY_ROW', 'none');
        } else {
            $tpl->set('d', 'DISPLAY_ROW', $sDisplay);
        }

        $tpl->next();
        $currentRow++;

        $onclick = "";

        $text = "";
        $numberOfArticles = "";
        $total = "";
        $inThisLanguage = "";

        while ($db2->next_record()) {
            $idart = $db2->f("idart");

            array_push($aRowname, $idart);

            $text = "";
            $numberOfArticles = "";
            $total = "";
            $inThisLanguage = "";

            $padding_left = 10 + (15 * ($db->f("level") + 1));

            $text = $db2->f("title");
            $online = $db2->f("online");

            // ************** number of arts **************
            $db3 = new DB_contenido();

            // ************** hits of art total **************
            $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat_archive"] . " AS B WHERE A.idcatart=B.idcatart AND A.idcat='" . Contenido_Security::toInteger($idcat) . "'
                    AND A.idart='" . Contenido_Security::toInteger($idart) . "' AND B.idclient='" . Contenido_Security::toInteger($client) . "' AND SUBSTRING(B.archived,1,4) = " . Contenido_Security::escapeDB($year, $db3) . "
                    GROUP BY SUBSTRING(B.archived,1,4)";

            $db3->query($sql);
            $db3->next_record();

            $total = $db3->f(0);

            // ************** hits of art in this language ***************
            $sql = "SELECT visited FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat_archive"] . " AS B WHERE A.idcatart=B.idcatart AND A.idcat='" . Contenido_Security::toInteger($idcat) . "'
                    AND A.idart='" . Contenido_Security::toInteger($idart) . "' AND B.idlang='" . Contenido_Security::toInteger($lang) . "' AND B.idclient='" . Contenido_Security::toInteger($client) . "'
                    AND SUBSTRING(B.archived,1,4) = " . Contenido_Security::escapeDB($year, $db3) . " GROUP BY SUBSTRING(B.archived,1,4)";

            $db3->query($sql);
            $db3->next_record();

            $inThisLanguage = $db3->f(0);

            if ($online == 0) {
                $offonline = '<img src="' . $cfg['path']['images'] . 'offline_off.gif" alt="Artikel ist offline" title="Artikel ist offline">';
            } else {
                $offonline = '<img src="' . $cfg['path']['images'] . 'online_off.gif" alt="Artikel ist online" title="Artikel ist online">';
            }

            $icon = '<img src="' . $cfg['path']['images'] . 'article.gif" style="vertical-align:top;">';
            $bgcolor = $cfg["color"]["table_light"];
            $tpl->set('d', 'BGCOLOR', $bgcolor);
            $tpl->set('d', 'PADDING_LEFT', $padding_left);
            $tpl->set('d', 'TEXT', $text);
            $tpl->set('d', 'ONCLICK', "");
            $tpl->set('d', 'ICON', $icon);
            $tpl->set('d', 'STATUS', $offonline);
            $tpl->set('d', 'ROWNAME', implode('_', $aRowname));
            $tpl->set('d', 'NUMBEROFARTICLES', $numberOfArticles);
            $tpl->set('d', 'TOTAL', $total);
            $tpl->set('d', 'ROWNAME', implode('_', $aRowname));
            $tpl->set('d', 'EXPAND', '<img src="' . $cfg['path']['images'] . 'spacer.gif" width="7">');
            $tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
            $tpl->set('d', 'INTHISLANGUAGE', $inThisLanguage);
            $tpl->set('d', 'DISPLAY_ROW', 'none');
            $tpl->next();
            $currentRow++;

            array_pop($aRowname);
        }
    }

    // ************** hits total**************
    $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat_archive"] . " AS B WHERE A.idcatart=B.idcatart AND B.idclient='" . Contenido_Security::toInteger($client) . "'
            AND SUBSTRING(B.archived,1,4) = '" . Contenido_Security::escapeDB($year, $db) . "' GROUP BY SUBSTRING(B.archived,1,4)";

    $db->query($sql);
    $db->next_record();

    $total = $db->f(0);

    // ************** hits total on this language ***************
    $sql = "SELECT SUM(visited) FROM " . $cfg["tab"]["cat_art"] . " AS A, " . $cfg["tab"]["stat_archive"] . " AS B WHERE A.idcatart=B.idcatart AND B.idlang='" . Contenido_Security::toInteger($lang) . "'
            AND B.idclient='" . Contenido_Security::toInteger($client) . "' AND SUBSTRING(B.archived,1,4) = '" . Contenido_Security::escapeDB($year, $db) . "' GROUP BY SUBSTRING(B.archived,1,4)";

    $db->query($sql);
    $db->next_record();

    $inThisLanguage = $db->f(0);

    $bgcolor = '#FFFFFF';
    $tpl->set('d', 'BGCOLOR', $bgcolor);
    $tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
    $tpl->set('d', 'TEXT', '&nbsp;');
    $tpl->set('d', 'ICON', '');
    $tpl->set('d', 'STATUS', '');
    $tpl->set('d', 'PADDING_LEFT', '10');
    $tpl->set('d', 'NUMBEROFARTICLES', '');
    $tpl->set('d', 'TOTAL', '');
    $tpl->set('d', 'ONCLICK', '');
    $tpl->set('d', 'EXPAND', '');
    $tpl->set('d', 'ROWNAME', '');
    $tpl->set('d', 'INTHISLANGUAGE', '');
    $tpl->set('d', 'DISPLAY_ROW', $sDisplay);
    $bgcolor = $cfg["color"]["table_dark"];
    $tpl->set('s', 'SUMBGCOLOR', $cfg["color"]["table_dark"]);
    $tpl->set('s', 'SUMTEXT', "Summe");
    $tpl->set('s', 'SUMNUMBEROFARTICLES', $sumNumberOfArticles);
    $tpl->set('s', 'SUMTOTAL', $total);
    $tpl->set('s', 'SUMINTHISLANGUAGE', $inThisLanguage);
    $tpl->next();
}

/**
 * Generates a top<n> statistics page
 *
 * @param $yearmonth Specifies the year and month from which to retrieve the
 *        statistics, specify "current" to retrieve the current
 *        entries
 * @param $top Specifies the amount of pages to display
 *
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @copyright four for business AG <http://www.4fb.de>
 *
 * @return none
 *
 */
function statsOverviewTop($yearmonth, $top) {
    global $cfg, $db, $tpl, $client, $lang;

    if (strcmp($yearmonth, "current") == 0) {
        $sql = "SELECT
                    C.title, A.visited
                FROM " . $cfg["tab"]["stat"] . " AS A,
                     " . $cfg["tab"]["cat_art"] . " AS B,
                     " . $cfg["tab"]["art_lang"] . " AS C
                WHERE
                    C.idart = B.idart
                AND
                    C.idlang = A.idlang
                AND
                    B.idcatart = A.idcatart
                AND
                    A.idclient = '" . Contenido_Security::toInteger($client) . "'
                AND
                    A.idlang = '" . Contenido_Security::toInteger($lang) . "'
                ORDER BY
                    A.visited DESC

                LIMIT
                    " . Contenido_Security::escapeDB($top, $db);
    } else {
        $sql = "SELECT
                    C.title, A.visited, B.idcat
                FROM " . $cfg["tab"]["stat_archive"] . " AS A,
                     " . $cfg["tab"]["cat_art"] . " AS B,
                     " . $cfg["tab"]["art_lang"] . " AS C
                WHERE
                    C.idart = B.idart
                AND
                    C.idlang = A.idlang
                AND
                    B.idcatart = A.idcatart
                AND
                    A.idclient = '" . Contenido_Security::toInteger($client) . "'
                AND
                    A.archived = '" . Contenido_Security::escapeDB($yearmonth, $db) . "'
                AND
                    A.idlang = '" . Contenido_Security::toInteger($lang) . "'
                ORDER BY
                    A.visited DESC
                LIMIT
                    " . Contenido_Security::escapeDB($top, $db);
    }

    $db->query($sql);
    global $client, $cfgClient;

    while ($db->next_record()) {
        $cat_name = "";
        statCreateLocationString($db->f(2), "&nbsp;/&nbsp;", $cat_name);
        $bgcolor = $cfg["color"]["table_light"];
        $tpl->set('d', 'BGCOLOR', $bgcolor);
        $tpl->set('d', 'PADDING_LEFT', '5');
        $tpl->set('d', 'PATH', "Pfad:&nbsp;/&nbsp;" . $cat_name);
        $tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
        $tpl->set('d', 'TEXT', $db->f(0));
        $tpl->set('d', 'TOTAL', $db->f(1));
        $tpl->set('d', 'ULR_TO_PAGE', $cfgClient[$client]['path']['htmlpath'] . 'front_content.php?idart=' . $db->f('idart'));
        $tpl->next();
    }
}

/**
 * Returns the canonical month.
 *
 * Wrapper for function getCanonicalMonth()
 *
 * @param int $month The digit representation of a month
 * @return string Textual representation of a month
 */
function statReturnCanonicalMonth($month) {
    return getCanonicalMonth($month);
}

/**
 * Generates the location string for passed category id.
 *
 * Performs a recursive call, if parent category doesn't matches to 0
 *
 * @param int $idcat The category id
 * @param string $seperator Separator for location string
 * @param string $cat_str The location string variable (reference)
 * @return void
 */
function statCreateLocationString($idcat, $seperator, &$cat_str) {
    global $cfg, $db, $client, $lang;

    $sql = "SELECT
                a.name AS name,
                a.idcat AS idcat,
                b.parentid AS parentid
            FROM
                " . $cfg["tab"]["cat_lang"] . " AS a,
                " . $cfg["tab"]["cat"] . " AS b
            WHERE
                a.idlang    = '" . Contenido_Security::toInteger($lang) . "' AND
                b.idclient  = '" . Contenido_Security::toInteger($client) . "' AND
                b.idcat     = '" . Contenido_Security::toInteger($idcat) . "' AND
                a.idcat     = b.idcat";

    $db4 = new DB_Contenido();
    $db4->query($sql);
    $db4->next_record();

    $name = $db4->f("name");
    $parentid = $db4->f("parentid");

    $tmp_cat_str = $name . $seperator . $cat_str;
    $cat_str = $tmp_cat_str;

    if ($parentid != 0) {
        statCreateLocationString($parentid, $seperator, $cat_str);
    } else {
        $sep_length = strlen($seperator);
        $str_length = strlen($cat_str);
        $tmp_length = $str_length - $sep_length;
        $cat_str = substr($cat_str, 0, $tmp_length);
    }
}

/**
 * Generates a top<n> statistics page
 *
 * @param $year Specifies the year from which to retrieve the
 *        statistics
 * @param $top Specifies the amount of pages to display
 *
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @copyright four for business AG <http://www.4fb.de>
 *
 * @return none
 *
 */
function statsOverviewTopYear($year, $top) {
    global $cfg, $db, $tpl, $client, $lang;

    $sql = "SELECT
                C.title, SUM(A.visited) as visited
            FROM " . $cfg["tab"]["stat_archive"] . " AS A,
                 " . $cfg["tab"]["cat_art"] . " AS B,
                 " . $cfg["tab"]["art_lang"] . " AS C
            WHERE
                C.idart = B.idart
            AND
                C.idlang = A.idlang
            AND
                B.idcatart = A.idcatart
            AND
                A.idclient = '" . Contenido_Security::toInteger($client) . "'
            AND
                A.archived LIKE '" . Contenido_Security::escapeDB($year, $db) . "%'
            AND
                A.idlang = '" . Contenido_Security::toInteger($lang) . "'
            GROUP BY A.idcatart
            ORDER BY
                visited DESC

            LIMIT
                " . Contenido_Security::escapeDB($top, $db);

    $db->query($sql);
    global $cfgClient, $client;

    while ($db->next_record()) {
        $bgcolor = $cfg["color"]["table_light"];
        $tpl->set('d', 'BGCOLOR', $bgcolor);
        $tpl->set('d', 'PADDING_LEFT', '0');
        $tpl->set('d', 'BORDERCOLOR', $cfg["color"]["table_border"]);
        $tpl->set('d', 'TEXT', $db->f(0));
        $tpl->set('d', 'TOTAL', $db->f(1));
        $tpl->set('d', 'PATH', "Pfad:&nbsp;/&nbsp;" . $cat_name);
        $tpl->set('d', 'ULR_TO_PAGE', $cfgClient[$client]['path']['htmlpath'] . 'front_content.php?idart=' . $db->f('idart'));
        $tpl->next();
    }
}

/**
 * Returns a drop down to choose the stats to display
 *
 * @param none
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @copyright four for business AG <http://www.4fb.de>
 *
 * @return string Returns a drop down string
 *
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

    return ("<form name=\"name\">" . "  <select class=\"text_medium\" onchange=\"top10Action(this)\">" . "    <option value=\"top10\" $defaultTop10>" . i18n("Top 10") . "</option>" . "    <option value=\"top20\" $defaultTop20>" . i18n("Top 20") . "</option>" . "    <option value=\"top30\" $defaultTop30>" . i18n("Top 30") . "</option>" . "    <option value=\"all\" $defaultAll>" . i18n("All") . "</option>" . "  </select>" . "</form>");
}

/**
 * Returns a drop down to choose the stats to display for yearly summary pages
 *
 * @param none
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @copyright four for business AG <http://www.4fb.de>
 *
 * @return string Returns a drop down string
 *
 */
function statDisplayYearlyTopChooser($default) {
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

    return ("<form name=\"name\">" . "  <select class=\"text_medium\" onchange=\"top10ActionYearly(this)\">" . "    <option value=\"top10\" $defaultTop10>" . i18n("Top 10") . "</option>" . "    <option value=\"top20\" $defaultTop20>" . i18n("Top 20") . "</option>" . "    <option value=\"top30\" $defaultTop30>" . i18n("Top 30") . "</option>" . "    <option value=\"all\" $defaultAll>" . i18n("All") . "</option>" . "  </select>" . "</form>");
}

/**
 * Return an array with all years which are available as stat files
 *
 * @param mixed many
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @copyright four for business AG <http://www.4fb.de>
 *
 * @return array Array of strings with years.
 */
function statGetAvailableYears($client, $lang) {
    global $cfg, $db;

    $availableYears = array();

    $sql = "SELECT SUBSTRING(`archived`,1,4)
            FROM
                " . $cfg["tab"]["stat_archive"] . "
            WHERE
                idlang = '" . Contenido_Security::toInteger($lang) . "' AND
                idclient = '" . Contenido_Security::toInteger($client) . "'
            GROUP BY
                SUBSTRING(`archived`,1,4)
            ORDER BY
                SUBSTRING(`archived`,1,4) DESC";

    $db->Query($sql);
    while ($db->next_record()) {
        array_push($availableYears, $db->f(0));
    }

    return ($availableYears);
}

/**
 * Return an array with all months for a specific year which are available
 * as stat files
 *
 * @param mixed many
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @copyright four for business AG <http://www.4fb.de>
 *
 * @return array Array of strings with months.
 */
function statGetAvailableMonths($year, $client, $lang) {
    global $cfg, $db;

    $availableYears = array();

    $sql = "SELECT SUBSTRING(`archived`,5,2)
            FROM
                " . $cfg["tab"]["stat_archive"] . "
            WHERE
                idlang = '" . Contenido_Security::toInteger($lang) . "' AND
                idclient = '" . Contenido_Security::toInteger($client) . "' AND
                SUBSTRING(`archived`,1,4) = '" . Contenido_Security::escapeDB($year, $db) . "'
            GROUP BY
                SUBSTRING(`archived`,5,2)
            ORDER BY SUBSTRING(`archived`,5,2) DESC";

    $db->query($sql);
    while ($db->next_record()) {
        array_push($availableYears, $db->f(0));
    }

    return ($availableYears);
}

/**
 * Resets the statistic for passed client
 *
 * @param int $client Id of client
 * @return void
 */
function statResetStatistic($client) {
    global $db;
    global $cfg;
    $sql = "UPDATE " . $cfg["tab"]["stat"] . " SET visited=0 WHERE idclient='" . Contenido_Security::toInteger($client) . "'";
    $db->query($sql);
}

/**
 * Deletes existing heap table (table in memory) and creates it.
 *
 * @param string $sHeapTable Table name
 * @param DB_Contenido $db Database object
 * @return void
 */
function buildHeapTable($sHeapTable, $db) {
    global $cfg;

    $sql = "DROP TABLE IF EXISTS " . Contenido_Security::escapeDB($sHeapTable, $db) . ";";
    $db->query($sql);

    $sql = "CREATE TABLE " . Contenido_Security::escapeDB($sHeapTable, $db) . " TYPE=HEAP
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

    $sql = "ALTER TABLE `" . Contenido_Security::escapeDB($sHeapTable, $db) . "` ADD PRIMARY KEY (`idcatart`,`idcat` ,`idart`,`idstatarch` ,`archived`,`idlang`,`idclient` ,`visited`);";
    $db->query($sql);
}
?>