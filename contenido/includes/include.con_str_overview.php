<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Displays the structure in the left frame
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.1.0
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created 2002-03-02
 *   modified 2007-04-24, Holger Librenz
 *   modified 2008-02-13, Andreas Lindner
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2008-09-08, Ingo van Peeren, optimized HTML, added AJAX an javascript to prevent
 *                                         reloading of navigation tree, small sql performance
 *                                         improvement
 *   modified 2009-12-18, Murat Purc, fixed usage of wrong db instance, see [#CON-282]
 *   modified 2010-01-30, Ingo van Peeren, optimized amount of db queries, removed unused variables
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude("includes","functions.str.php");
cInclude("includes","functions.tpl.php");
cInclude('includes', 'functions.lang.php');

function showTree($iIdcat, &$aWholelist) {
global $check_global_rights, $sess, $cfg, $perm, $db, $db2, $db3, $area, $client, $lang, $navigationTree;

    $tpl = new Template;
    $tpl->reset();

    $iIdcat = (int) $iIdcat;

    foreach ($navigationTree[$iIdcat] as $sKey => $aValue) {

        $cfgdata = '';
        $aCssClasses = array();

        #Check rights per cat
        if (!$check_global_rights) {
            $check_rights = false;
        } else {
            $check_rights = true;
        }

        if (!$check_rights) {
            $check_rights = ($aValue['forcedisplay'] == 1) ? true : false;
        }

          $idcat = (int)$aValue['idcat'];
      $level = $aValue['level'] - 1;
      $name  = $aValue['name'];

        if ($check_rights) {

        $idtpl = ( $aValue['idtpl'] != '' ) ? $aValue['idtpl'] : 0;

        if (($aValue["idlang"] != $lang) || ($aValue['articles'] == true)) {
            #$aCssClasses[] = 'con_sync';
        }

        $check_rights = $perm->have_perm_area_action_item("con", "con_changetemplate",$aValue['idcat']);
        if (!$check_rights)
        {
            $check_rights = $perm->have_perm_area_action("con", "con_changetemplate");
        }

        if ($check_rights)
        {
            $changetemplate = 1;

          } else {
              $changetemplate = 0;

          }

        $check_rights = $perm->have_perm_area_action_item("con", "con_makecatonline",$aValue['idcat']);
        if (!$check_rights)
        {
            $check_rights = $perm->have_perm_area_action("con", "con_makecatonline");
          }

           if ($check_rights)
           {
               $onoffline = 1;
           } else {
               $onoffline = 0;
           }


        $check_rights = $perm->have_perm_area_action_item("con", "con_makepublic",$aValue['idcat']);
        if (!$check_rights)
        {
            $check_rights = $perm->have_perm_area_action("con", "con_makepublic");
        }

        if ($check_rights)
        {
            $makepublic = 1;
        } else {
            $makepublic = 0;
        }

        $check_rights = $perm->have_perm_area_action_item("con", "con_tplcfg_edit", $aValue['idcat']);
        if (!$check_rights)
        {
            $check_rights = $perm->have_perm_area_action("con", "con_tplcfg_edit");
        }

        if ($check_rights)
        {
            $templateconfig = 1;
        } else {
            $templateconfig = 0;
        }

           if ($aValue["idlang"] == $lang)
           {
               # Build cfgdata string
            $cfgdata = $idcat."-".$idtpl."-".$aValue['online']."-".$aValue['public']."-".
                       $changetemplate ."-".
                       $onoffline ."-".
                       $makepublic."-".$templateconfig;
           } else {
               $cfgdata = "";
           }

        # Select the appropriate folder-
        # image depending on the structure
        # properties

        if ( $aValue['online'] == 1 ) {
                # Category is online

                if ( $aValue['public'] == 0 ) {
                    # Category is locked
                    if ( $aValue['no_start'] || $aValue['no_online'] ) {
                        # Error found
                        $aAnchorClass = 'on_error_locked';

                    } else {
                        # No error found
                        $aAnchorClass = 'on_locked';

                    }

                } else {
                    # Category is public
                    if ( $aValue['no_start'] || $aValue['no_online'] ) {
                        # Error found
                        $aAnchorClass = 'on_error';

                    } else {
                        # No error found
                        $aAnchorClass = 'on';

                    }
                }

            } else {
                # Category is offline

                if ( $aValue['public'] == 0 ) {
                    # Category is locked
                    if ( $aValue['no_start'] || $aValue['no_online'] ) {
                        # Error found
                        $aAnchorClass = 'off_error_locked';

                    } else {
                        # No error found
                        $aAnchorClass = 'off_locked';

                    }

                } else {
                    # Category is public
                    if ( $aValue['no_start'] || $aValue['no_online'] ) {
                        # Error found
                        $aAnchorClass = 'off_error';

                    } else {
                        # No error found
                        $aAnchorClass = 'off';

                    }
                }
            }

        if ( $aValue['islast'] == 1 ) {
            $aCssClasses[] = 'last';
        }

        if ( $aValue['collapsed'] == 1 && is_array($navigationTree[$idcat]) ) {
            $aCssClasses[] = 'collapsed';
        }

        if ( $aValue['active'] ) {
            $aCssClasses[] = 'active';
        }

        $bIsSyncable = false;
        if ($aValue["idlang"] != $lang)
           {
               # Fetch parent id and check if it is syncronized
               $sql = "SELECT parentid FROM %s WHERE idcat = '%s'";
               $db->query(sprintf($sql, $cfg["tab"]["cat"], $idcat));
               if ($db->next_record())
               {
                   if ($db->f("parentid") != 0)
                   {
                       $parentid = $db->f("parentid");
                       $sql = "SELECT idcatlang FROM %s WHERE idcat = '%s' AND idlang = '%s'";
                       $db->query(sprintf($sql, $cfg["tab"]["cat_lang"], Contenido_Security::toInteger($parentid), Contenido_Security::toInteger($lang)));

                       if ($db->next_record())
                       {
                                $aCssClasses[] = 'con_sync';
                                $bIsSyncable = true;
                    }

                   } else {
                    $aCssClasses[] = 'con_sync';
                    $bIsSyncable = true;
                }
               }
           }

        //Last param defines if cat is syncable or not, all other rights are disabled at this point
        if ($bIsSyncable) {
            if ($cfgdata != '') {
                $cfgdata .= '-1';
            } else {
                $cfgdata = $idcat."-".$idtpl."-".$aValue['online']."-".$aValue['public'].
                       "-0-0-0-0-1";
            }
        } else {
            if ($cfgdata != '') {
                $cfgdata .= '-0';
            } else {
                $cfgdata = $idcat."-".$idtpl."-".$aValue['online']."-".$aValue['public'].
                       "-0-0-0-0-0";
            }
        }

        $mstr = '<a class="'.$aAnchorClass.'" href="#" title="idcat'.'&#58; '.$idcat.'">' . $name . '</a>';

        # Build Tree
        $tpl->set('d', 'CFGDATA',   $cfgdata);
        if (is_array($navigationTree[$idcat])) {
            $tpl->set('d', 'SUBCATS', showTree($idcat, $aWholelist));
            $tpl->set('d', 'COLLAPSE', '<a href="#"> </a>');
            $aWholelist[] = $idcat;
        } else {
            $tpl->set('d', 'SUBCATS', '');
            $tpl->set('d', 'COLLAPSE',  '<span> </span>');
        }
        $tpl->set('d', 'CAT',       $mstr);
        $tpl->set('d', 'CSS_CLASS',    ' class="'.implode(' ', $aCssClasses).'"');

        $tpl->next();

    } // end if have_perm
    else {
        if (is_array($navigationTree[(int)$aValue['idcat']])) {
            $sTpl = showTree((int)$aValue['idcat'], $aWholelist);
            if (!preg_match('/^<ul>\s*<\/ul>$/', $sTpl)) {
                $tpl->set('d', 'CFGDATA',   '0-0-0-0-0-0-0-0-0');
                $tpl->set('d', 'SUBCATS', $sTpl);
                $tpl->set('d', 'COLLAPSE', '<a href="#"></a>');
                $tpl->set('d', 'CAT',       '<a class="off_disabled" href="#">' . $name . '</a>');
                $tpl->set('d', 'CSS_CLASS',    ' class="active"');
                $tpl->next();
            }
            $aWholelist[] = $aValue['idcat'];
        }
    }

    }
    return $tpl->generate($cfg['path']['templates'] . 'template.con_str_overview.list.html', 1);
}

$db2 = new DB_Contenido;
$db3 = new DB_Contenido;

//Refresh or reset right frames, when a synclang is changed or a category is synchronized
$tpl->reset();

if ($action == "con_synccat" || isset($_GET['refresh_syncoptions']) && $_GET['refresh_syncoptions'] == 'true') {
    $tpl->set('s', 'RELOAD_RIGHT', 'reloadRightFrame();');
} else {
    $tpl->set('s', 'RELOAD_RIGHT', '');
}

if ($action == "con_synccat")
{
    strSyncCategory($syncidcat, $syncfromlang, $lang, $multiple);
    $remakeStrTable = true;
}

if ( !is_object($db2) ) $db2 = new DB_Contenido;

if (!isset($remakeStrTable))
{
    $remakeStrTable = false;
}

if (!isset($remakeCatTable))
{
    $remakeCatTable = false;
}

$sess->register("remakeCatTable");
$sess->register("CatTableClient");
$sess->register("CatTableLang");
$sess->register("remakeStrTable");

if (isset($syncoptions))
{
    $syncfrom = $syncoptions;
    $remakeCatTable = true;
}

if (!isset($syncfrom))
{
    $syncfrom = 0;
}

$sess->register("syncfrom");

$syncoptions = $syncfrom;

if (!isset($CatTableClient))
{
    $CatTableClient = 0;
}

if ($CatTableClient != $client)
{
    $remakeCatTable = true;
}

if (!isset($CatTableLang))
{
    $CatTableLang = 0;
}

if ($CatTableLang != $lang)
{
    $remakeCatTable = true;
}

$CatTableClient = $client;
$CatTableLang = $lang;

if ($syncoptions == -1)
{
    $sql = "SELECT
                a.preid AS preid,
                a.postid AS postid,
                a.parentid AS parentid,
                c.idcat AS idcat,
                c.level AS level,
                b.name AS name,
                b.public AS public,
                b.visible AS online,
                d.idtpl AS idtpl,
                b.idlang AS idlang
            FROM
                (".$cfg["tab"]["cat"]." AS a,
                ".$cfg["tab"]["cat_lang"]." AS b,
                ".$cfg["tab"]["cat_tree"]." AS c)
            LEFT JOIN
                ".$cfg["tab"]["tpl_conf"]." AS d
                ON d.idtplcfg = b.idtplcfg
            WHERE
                a.idclient  = '".Contenido_Security::toInteger($client)."' AND
                b.idlang    = '".Contenido_Security::toInteger($lang)."' AND
                c.idcat     = b.idcat AND
                b.idcat     = a.idcat
            ORDER BY
                c.idtree ASC";
} else {
    $sql = "SELECT
                a.preid AS preid,
                a.postid AS postid,
                a.parentid AS parentid,
                c.idcat AS idcat,
                c.level AS level,
                b.name AS name,
                b.public AS public,
                b.visible AS online,
                d.idtpl AS idtpl,
                b.idlang AS idlang
            FROM
                (".$cfg["tab"]["cat"]." AS a,
                ".$cfg["tab"]["cat_lang"]." AS b,
                ".$cfg["tab"]["cat_tree"]." AS c)
            LEFT JOIN
                ".$cfg["tab"]["tpl_conf"]." AS d
                ON d.idtplcfg = b.idtplcfg
            WHERE
                a.idclient  = '".Contenido_Security::toInteger($client)."' AND
                (b.idlang    = '".Contenido_Security::toInteger($lang)."' OR
                 b.idlang     = '".Contenido_Security::toInteger($syncoptions)."') AND
                c.idcat     = b.idcat AND
                b.idcat     = a.idcat
            ORDER BY
                c.idtree ASC";

}

$db->query($sql);

if (isset($syncoptions))
{
    $remakeCatTable = true;
}

if (isset($online))
{
    $remakeCatTable = true;
}

if (isset($public))
{
    $remakeCatTable = true;
}

if (isset($idtpl))
{
    $remakeCatTable = true;
}

if (isset($force))
{
    $remakeCatTable = true;
}

$arrIn = array();
while ($db->next_record()) {
    $arrIn[] = $db->f('idcat');
}

$arrArtCache = array();

if (count($arrIn) > 0) {
    $sIn = implode(',',$arrIn);

    $sql2 = "SELECT b.idcat, a.idart, idlang FROM ".$cfg["tab"]["art_lang"]." AS a,
                                  ".$cfg["tab"]["cat_art"]." AS b
            WHERE b.idcat IN (".Contenido_Security::escapeDB($sIn, $db).") AND (a.idlang = '".Contenido_Security::toInteger($syncoptions)."' OR a.idlang = '".Contenido_Security::toInteger($lang)."')
            AND b.idart = a.idart";
    $db->query($sql2);

    while ($db->next_record()) {
        $arrArtCache[$db->f('idcat')][$db->f('idart')][$db->f('idlang')] = 'x';
    }
}

$db->query($sql);

while ($db->next_record()) {
    $entry = array();

    $entry['articles'] = false;

    if ($db->f("idlang") == $lang) {

        $arts = Array();

          if (isset($arrArtCache[$db->f("idcat")])) {
              foreach ($arrArtCache[$db->f("idcat")] as $key => $value) {
                  foreach ($value as $key2 => $value2) {
                      $arts[$key][$key2] = 1;
                  }
              }
          }

          foreach ($arts as $idart => $entry) {
              if (is_array($entry))
              {
                  if (!array_key_exists($lang,$entry))
                  {
                      //$entry['articles'] = true;
                    $aIsArticles[$db->f("idcat")] = true;
                      break;
                  }
              }
          }
    }
}

if ($syncoptions == -1) {
    $sql2 = "SELECT
                    c.idcat AS idcat,
                    SUM(a.online) AS online,
                    d.startidartlang
                FROM
                    ".$cfg["tab"]["art_lang"]." AS a,
                    ".$cfg["tab"]["art"]." AS b,
                    ".$cfg["tab"]["cat_art"]." AS c,
                    ".$cfg["tab"]["cat_lang"]." AS d
                WHERE
                    a.idlang = ".Contenido_Security::toInteger($lang)." AND
                    a.idart = b.idart AND
                    b.idclient = '".Contenido_Security::toInteger($client)."' AND
                    b.idart = c.idart AND
                    c.idcat = d.idcat
                GROUP BY c.idcat
                        ";
} else {
    $sql2 = "SELECT
                    c.idcat AS idcat,
                    SUM(a.online) AS online,
                    d.startidartlang
                FROM
                    ".$cfg["tab"]["art_lang"]." AS a,
                    ".$cfg["tab"]["art"]." AS b,
                    ".$cfg["tab"]["cat_art"]." AS c,
                    ".$cfg["tab"]["cat_lang"]." AS d
                WHERE
                    a.idart = b.idart AND
                    b.idclient = '".Contenido_Security::toInteger($client)."' AND
                    b.idart = c.idart AND
                    c.idcat = d.idcat
                GROUP BY c.idcat";
}

$db->query($sql2);
$aStartOnlineArticles = array();
while ($db->next_record()) {
    if ($db->f('startidartlang') > 0) {
        $aStartOnlineArticles[$db->f('idcat')]['is_start'] = true;
    } else {
        $aStartOnlineArticles[$db->f('idcat')]['is_start'] = false;
    }
    if ($db->f('online') > 0) {
        $aStartOnlineArticles[$db->f('idcat')]['is_online'] = true;
    } else {
        $aStartOnlineArticles[$db->f('idcat')]['is_online'] = false;
    }
}

$_cecIterator = $_cecRegistry->getIterator("Contenido.ArticleCategoryList.ListItems");

if ($_cecIterator->count() > 0)
{
    while ($chainEntry = $_cecIterator->next())
    {
        $listItem = $chainEntry->execute();

        if (is_array($listItem))
        {
            if (!array_key_exists("expandcollapseimage", $listItem) || $listItem["expandcollapseimage"] == "")
            {
                $collapseImage = '<img src="images/spacer.gif" width="11" height="11">';
            } else {
                $collapseImage = $listItem["expandcollapseimage"];
            }

            if (!array_key_exists("image", $listItem) || $listItem["image"] == "")
            {
                $image = '<img src="images/spacer.gif">';
            } else {
                $image = $listItem["image"];
            }

            if (!array_key_exists("id", $listItem) || $listItem["id"] == "")
            {
                $id = rand();
            } else {
                $id = $listItem["id"];
            }

            if (array_key_exists("markable", $listItem))
            {
                if ($listItem["markable"] == true)
                {
                    $mmark = $markscript;
                } else {
                    $mmark = "";
                }
            } else {
                $mmark = "";
            }
        }

    }
}

$languages = getLanguageNamesByClient($client);

/******************************/
/* Expand all / Collapse all */
/******************************/
$selflink = "main.php";
$expandlink = $sess->url($selflink . "?area=$area&frame=$frame&expand=all&syncoptions=$syncoptions");
$collapselink = $sess->url($selflink . "?area=$area&frame=$frame&collapse=all&syncoptions=$syncoptions");
$collapseimg = '<a href="'.$collapselink.'" alt="'.i18n("Close all categories").'" title="'.i18n("Close all categories").'"><img src="images/but_minus.gif" border="0"></a>';
$expandimg = '<a href="'.$expandlink.'" alt="'.i18n("Open all categories").'" title="'.i18n("Open all categories").'"><img src="images/but_plus.gif" border="0"></a>';
$allLinks = $expandimg .'<img src="images/spacer.gif" width="3">'.$collapseimg;
$text_direction = langGetTextDirection($lang);

#Check global rights
$check_global_rights = $perm->have_perm_area_action("con", "con_makestart");
if (!$check_global_rights) {$check_global_rights = $perm->have_perm_area_action("con_editart", "con_edit");}
if (!$check_global_rights) {$check_global_rights = $perm->have_perm_area_action("con_editart", "con_saveart");}
if (!$check_global_rights) {$check_global_rights = $perm->have_perm_area_action("con_editcontent", "con_editart");}
if (!$check_global_rights) {$check_global_rights = $perm->have_perm_area_action("con_editart", "con_newart");}
if (!$check_global_rights) {$check_global_rights = $perm->have_perm_area_action("con", "con_deleteart");}
if (!$check_global_rights) {$check_global_rights = $perm->have_perm_area_action("con", "con_makeonline");}
if (!$check_global_rights) {$check_global_rights = $perm->have_perm_area_action("con", "con_tplcfg_edit");}
if (!$check_global_rights) {$check_global_rights = $perm->have_perm_area_action("con", "con_makecatonline");}
if (!$check_global_rights) {$check_global_rights = $perm->have_perm_area_action("con", "con_changetemplate");}

if ($lang > $syncoptions) {
    $sOrder = 'DESC';
} else {
    $sOrder = 'ASC';
}

$client = (int) $client;
$sql = "SELECT DISTINCT " .
      "a.idcat, " .
      "a.parentid, " .
      "a.preid, " .
      "a.postid, " .
      "a.parentid, " .
      "b.name, " .
      "b.idlang, " .
      "b.visible, " .
      "b.public, " .
      "c.level, " .
      "d.idtpl " .
      "FROM {$cfg['tab']['cat']} AS a " .
      "LEFT JOIN {$cfg['tab']['cat_lang']} AS b ON a.idcat = b.idcat " .
      "LEFT JOIN {$cfg['tab']['cat_tree']} AS c ON (a.idcat = c.idcat AND b.idcat = c.idcat) " .
      "LEFT JOIN {$cfg["tab"]["tpl_conf"]} AS d ON b.idtplcfg = d.idtplcfg " .
      "WHERE " .
      "   a.idclient = {$client} " .
      "ORDER BY b.idlang {$sOrder}, c.idtree ASC ";
$db->query($sql);
if ($client == 0) {
    $client = '';
}


$sExpandList = $currentuser->getUserProperty("system","con_cat_expandstate");
if ($sExpandList != '') {
    $conexpandedList = unserialize($currentuser->getUserProperty("system","con_cat_expandstate"));
}

if (!is_array($conexpandedList)) {
    $conexpandedList = array();
}

$navigationTree = array();
$aWholelist = array();

while ($db->next_record()) {
    if (!isset ($navigationTree[$db->f('parentid')][$db->f('idcat')]) && ($db->f('idlang') == $lang || $db->f('idlang') == $syncoptions)) {
        if (in_array($db->f('idcat'), $conexpandedList)) {
            $collapsed = false;
        } else {
            $collapsed = true;
        }
        if ($perm->have_perm_item("con", $db->f('idcat'))) {
            $forcedisplay = 1;
        } else {
            $forcedisplay = 0;
        }
        if ($idcat == $db->f('idcat')) {
            $active = true;
        } else {
            $active = false;
        }
        $navigationTree[$db->f('parentid')][$db->f('idcat')] = array (
           'idcat' => $db->f('idcat'),
           'preid' => $db->f('preid'),
           'postid' => $db->f('postid'),
           'visible' => $db->f('visible'),
           'online' => $db->f('visible'),
           'public' => $db->f('public'),
           'name' => $db->f('name'),
           'idlang' => $db->f('idlang'),
           'idtpl' => $db->f('idtpl'),
           'collapsed' => $collapsed,
           'forcedisplay' => $forcedisplay,
           'active' => $active,
           'islast' => false,
           'articles' => $aIsArticles[$db->f("idcat")],
           'level' => $db->f('level')
        );
        if ($aStartOnlineArticles[$db->f('idcat')]['is_start']) {
            $navigationTree[$db->f('parentid')][$db->f('idcat')]['no_start'] = false;
        } else {
            $navigationTree[$db->f('parentid')][$db->f('idcat')]['no_start'] = true;
        }
        if ($aStartOnlineArticles[$db->f('idcat')]['is_online']) {
            $navigationTree[$db->f('parentid')][$db->f('idcat')]['no_online'] = false;
        } else {
            $navigationTree[$db->f('parentid')][$db->f('idcat')]['no_online'] = true;
        }

    }
}

cDebug(print_r($navigationTree, true));

if (count($navigationTree[0])) {
    $sCategories = showTree(0, $aWholelist);
}

$tpl->set('s', 'SID', $sess->id);
$tpl->set('s', 'CATS', $sCategories);
$tpl->set('s', 'AREA', $area);
$tpl->set('s', 'SESSION', $contenido);
$tpl->set('s', 'DIRECTION', 'dir="' . langGetTextDirection($lang) . '"');
$tpl->set('s', 'SYNCOPTIONS', $syncoptions);

$tpl->set('s', 'AJAXURL', $cfg['path']['contenido_fullhtml'].'ajaxmain.php');
$tpl->set('s', 'WHOLELIST', implode(', ', $aWholelist));
$tpl->set('s', 'EXPANDEDLIST', implode(', ', $conexpandedList));

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['con_str_overview']);
?>