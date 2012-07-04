<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Displays all last edited articles of a category
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend Includes
 * @version    1.3.1
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created 2003-08-05
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

die();

cInclude("includes", "functions.con.php");

$sql = "SELECT
            logtimestamp
        FROM
            ".$cfg["tab"]["actionlog"]."
        WHERE
           user_id = '".$db->escape($auth->auth["uid"]). "'
        ORDER BY
            logtimestamp DESC
        LIMIT 2";

$db->query($sql);
$db->next_record();

$lastlogin = $db->f("logtimestamp");

$idaction = $perm->getIDForAction("con_editart");

$sql = "SELECT
            a.idart AS idart,
            a.idartlang AS idartlang,
            a.title AS title,
            c.idcat AS idcat,
            a.idlang AS idlang,
            c.idcatart AS idcatart,
            a.idtplcfg AS idtplcfg,
            a.online AS online,
            a.created AS created,
            a.lastmodified AS lastmodified
        FROM
            ".$cfg["tab"]["art_lang"]." AS a,
            ".$cfg["tab"]["art"]." AS b,
            ".$cfg["tab"]["cat_art"]." AS c,
            ".$cfg["tab"]["actionlog"]." AS d
        WHERE
            a.idlang    = '".cSecurity::toInteger($lang)."' AND
            a.idart     = b.idart AND
            b.idclient  = '".cSecurity::toInteger($client)."' AND
            b.idart     = c.idart AND
            d.idaction  = '".cSecurity::toInteger($idaction)."' AND
            d.user_id    = '".$db->escape($auth->auth["uid"])."' AND
            d.idcatart  = c.idcatart
        GROUP BY
                c.idcatart
        ORDER BY
                logtimestamp DESC
        LIMIT 5";

# Debug info
cDebug($sql);

$db->query($sql);

# Reset Template
$tpl->reset();

# No article
$no_article = true;

$tpl->set('s', 'LASTARTICLES', i18n("Recently edited articles").":".markSubMenuItem(1));

while ( $db->next_record() ) {
    $idtplcfg   = $db->f("idtplcfg");
    $idartlang  = $db->f("idartlang");
    $idlang     = $db->f("idlang");
    $idcat      = $db->f("idcat");
    $idart      = $db->f("idart");
    $online     = $db->f("online");

    $is_start = isStartArticle($idartlang, $idcat, $idlang);

    $idcatart   = $db->f("idcatart");
    $created    = $db->f("created");
    $modified   = $db->f("lastmodified");
    $category = "";
    conCreateLocationString($idcat, "&nbsp;/&nbsp;", $category);
    if ($category == "") {
        $category = "&nbsp;";
    }

    # Article Title
    $tmp_alink = $sess->url("frameset.php?area=con&override_area4=con_editcontent&override_area3=con&action=con_editart&idartlang=$idartlang&idart=$idart&idcat=$idcat&idartlang=$idartlang");
    $tpl->set('d', 'ARTICLE', $db->f('title'));

    # Created
    $tpl->set('d', 'CREATED', $created);

    # Lastmodified
    $tpl->set('d', 'LASTMODIFIED', $modified);

    # Category
    $tpl->set('d', 'CATEGORY', $category);

    # Article Template
    if (0 == $idtplcfg) { # Uses Category Template
        $a_tplname = "--- ".i18n("None")." ---";
        $a_idtpl = 0;

    } else { # Has own Template

        if (!isset($db2) || !is_object($db2)) {
            $db2 = cRegistry::getDb();
        }

        $sql2 = "SELECT
                    b.name AS tplname,
                    b.idtpl AS idtpl
                 FROM
                    ".$cfg["tab"]["tpl_conf"]." AS a,
                    ".$cfg["tab"]["tpl"]." AS b
                 WHERE
                    a.idtplcfg = '".cSecurity::toInteger($idtplcfg)."' AND
                    a.idtpl = b.idtpl";

        $db2->query($sql2);
        $db2->next_record();

        $a_tplname = $db2->f("tplname");
        $a_idtpl = $db2->f("idtpl");
    }

    if ($a_tplname == "") {
        $a_tplname = "&nbsp;";
    }

    $tpl->set('d', 'TPLNAME', $a_tplname);

    # Make Startarticle button
    $tmp_img = (1 == $is_start) ? '<img src="images/isstart1.gif" border="0">' : '<img src="images/isstart0.gif" border="0">';
    $tpl->set('d', 'START', $tmp_img);

    if ($online) {
        $tmp_online = '<img src="images/online.gif" title="'.i18n("Article is online").'" alt="'.i18n("Article is online").'" border="0">';
    } else {
        $tmp_online = '<img src="images/offline.gif" title="'.i18n("Article is offline").'" alt="'.i18n("Article is offline").'" border="0"></a>';
    }

    $tpl->set('d', 'ONLINE', $tmp_online);

    # Next iteration
    $tpl->next();

    # Articles found
    $no_article = false;
}

# Sort select
$s_types = array(
    1 => i18n("Alphabetical"),
    2 => i18n("Last change"),
    3 => i18n("Creation date")
);

$tpl2 = new Template();
$tpl2->set('s', 'NAME', 'sort');
$tpl2->set('s', 'CLASS', 'text_medium');
$tpl2->set('s', 'OPTIONS', 'onchange="artSort(this)"');

foreach ($s_types as $key => $value) {
    $selected = (isset($_GET['sort']) && $_GET['sort'] == $key) ? 'selected="selected"' : '';
    $tpl2->set('d', 'VALUE',    $key);
    $tpl2->set('d', 'CAPTION',  $value);
    $tpl2->set('d', 'SELECTED', $selected);
    $tpl2->next();
}

$select  = (!$no_article) ? $tpl2->generate($cfg["path"]["templates"] . $cfg['templates']['generic_select'], true) : '';
$caption = (!$no_article) ? 'Artikel sortieren' : '';

$tpl->set('s', 'ARTSORTCAPTION', $caption);
$tpl->set('s', 'ARTSORT', $select);

# Extract Category and Catcfg
$sql = "SELECT
            b.name AS name,
            d.idtpl AS idtpl
        FROM
            (".$cfg["tab"]["cat"]." AS a,
            ".$cfg["tab"]["cat_lang"]." AS b,
            ".$cfg["tab"]["tpl_conf"]." AS c)
        LEFT JOIN
            ".$cfg["tab"]["tpl"]." AS d
        ON
            d.idtpl = c.idtpl
        WHERE
            a.idclient = '".cSecurity::toInteger($client)."' AND
            a.idcat = '".cSecurity::toInteger($idcat)."' AND
            b.idlang = '".cSecurity::toInteger($lang)."' AND
            b.idcat = a.idcat AND
            c.idtplcfg = b.idtplcfg";

$db->query($sql);
$db->next_record();

$cat_idtpl = $db->f("idtpl");

# Notify if no article was found
if ($no_article) {
    $tpl->set("d", "START", "&nbsp;");
    $tpl->set("d", "ARTICLE", i18n("No article found"));
    $tpl->set("d", "CREATED", "&nbsp;");
    $tpl->set("d", "LASTMODIFIED", "&nbsp;");
    $tpl->set("d", "ARTCONF", "&nbsp;");
    $tpl->set("d", "TPLNAME", "&nbsp;");
    $tpl->set("d", "TPLCONF", "&nbsp;");
    $tpl->set("d", "ONLINE", "&nbsp;");
    $tpl->set('d', 'CATEGORY', '&nbsp');
    $tpl->set("d", "DELETE", "&nbsp;");

    $tpl->next();
}

$cat_name = "";

# SELF_URL (Variable für das javascript);
$tpl->set('s', 'SELF_URL', $sess->url("main.php?area=con&frame=4&idcat=$idcat"));

# New article link
$tpl->set('s', 'NEWARTICLE', '<a href="'.$sess->url("main.php?area=con_editart&frame=$frame&action=con_newart&idcat=$idcat").'">' . i18n("Create article") . '</a>');

$tpl->set('s', 'HELP', "");

# Generate template
$tpl->generate($cfg['path']['templates'] . $cfg['templates']['mycontenido_lastarticles']);

?>