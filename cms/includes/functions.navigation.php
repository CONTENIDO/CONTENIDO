<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * <Description>
 *
 * Requirements:
 * @con_php_req 5
 *
 *
 * @package    CONTENIDO Frontend
 * @subpackage Navigation
 * @version    0.1
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 *
 * {@internal
 *   created   unknown
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


// create Navigation array for one level
function createNavigationArray($start_id, $db)
{
    global $user, $cfg, $client, $lang, $auth;

    $navigation = array();
    $frontendPermissionCollection = new cApiFrontendPermissionCollection();

//    SECURITY-FIX
    $sql = "SELECT
                A.idcat,
                C.name,
                C.public,
                C.idcatlang
            FROM
                ".$cfg["tab"]["cat_tree"]." AS A,
                ".$cfg["tab"]["cat"]." AS B,
                ".$cfg["tab"]["cat_lang"]." AS C
            WHERE
                A.idcat    = B.idcat   AND
                B.idcat    = C.idcat   AND
                B.idclient = '".Contenido_Security::escapeDB($client, $db)."' AND
                C.idlang   = '".Contenido_Security::escapeDB($lang, $db)."' AND
                C.visible  = '1'       AND
                B.parentid = '".Contenido_Security::escapeDB($start_id, $db)."'
            ORDER BY
                A.idtree";
    $db->query($sql);

    while ($db->next_record()) {
        $cat_id = $db->f("idcat");
        $cat_idlang = $db->f("idcatlang");
        $visible = false;
        if ($db->f("public")!=0) {
            $visible = true;
        } elseif (($auth->auth['uid']!='')&&($auth->auth['uid']!='nobody')) {
            $FrontendGroupMemberCollection = new cApiFrontendGroupMemberCollection();

            $FrontendGroupMemberCollection->setWhere("idfrontenduser",$auth->auth['uid']);
            $FrontendGroupMemberCollection->query();
            $groups = array();
            while ($member = $FrontendGroupMemberCollection->next()) {
                   $groups[] = $member->get("idfrontendgroup");
            }
        }
        if (count($groups)>0) {
            for ($i=0;$i<count($groups);$i++) {
                if ($frontendPermissionCollection->checkPerm($groups[$i],'category','access',$cat_idlang, true)) {
                    $visible=true;
                }
            }
        }
        if ($visible) {
            $navigation[$cat_id] = array("idcat"  => $cat_id,
                                         "name"   => $db->f("name"),
                                         "target" => '_self',
                                         "public" => $db->f("public"));
        }
    } // end while

    $db->free();

    return  $navigation;
}

/**
 * Return true if $parentid is parent of $catid
 */
function isParent($parentid, $catid, $db)
{
    global $cfg, $client, $lang;
    //    SECURITY-FIX
    $sql = "SELECT
            a.parentid
            FROM
                ".$cfg["tab"]["cat"]." AS a,
                ".$cfg["tab"]["cat_lang"]." AS b
            WHERE
                a.idclient = '". Contenido_Security::escapeDB($client, $db)."' AND
                b.idlang   = '".Contenido_Security::escapeDB($lang, $db)."' AND
                a.idcat    = b.idcat AND
                a.idcat    = '".Contenido_Security::escapeDB($catid, $db)."'";

    $db->query($sql);
    $db->next_record();

    $pre = $db->f("parentid");

    if ($parentid == $pre) {
        return true;
    } else {
        return false;
    }
}


function getParent($preid, &$db)
{
    global $cfg, $client, $lang;

//    SECURITY-FIX
    $sql = "SELECT
            a.parentid
            FROM
                ".$cfg["tab"]["cat"]." AS a,
                ".$cfg["tab"]["cat_lang"]." AS b
            WHERE
                a.idclient = '".Contenido_Security::escapeDB($client, $db)."' AND
                b.idlang   = '".Contenido_Security::escapeDB($lang, $db)."' AND
                a.idcat    = b.idcat AND
                a.idcat    = '".Contenido_Security::escapeDB($preid, $db)."'";

    $db->query($sql);

    if ($db->next_record()) {
        return $db->f("parentid");
    } else {
        return false;
    }
}


function getLevel($catid, &$db)
{
    global $cfg, $client, $lang;

//    SECURITY-FIX
    $sql = "SELECT
                level
            FROM
                ".$cfg["tab"]["cat_tree"]."
            WHERE
                idcat = '". Contenido_Security::escapeDB($catid, $db)."' ";

    $db->query($sql);

    if ($db->next_record()) {
        return $db->f("level");
    } else {
        return false;
    }
}


/**
 * Return path of a given category up to a certain level
 */
function getCategoryPath($cat_id, $level, $reverse = true, &$db) {
    $root_path = array();

    array_push($root_path, $cat_id);

    $parent_id = $cat_id;

    while (getLevel($parent_id, $db) != false AND getLevel($parent_id, $db) > $level AND getLevel($parent_id, $db) >= 0) {
        $parent_id = getParent($parent_id, $db);
        if ($parent_id != false) {
            array_push($root_path, $parent_id);
        }
    }

    if ($reverse == true) {
        $root_path = array_reverse($root_path);
    }

    return $root_path;
}


/**
 * Return location string of a given category
 */
function getLocationString($iStartCat, $level, $seperator, $sLinkStyleClass, $sTextStyleClass, $fullweblink = false, $reverse = true, $mod_rewrite = true, $db)
{
    global $sess, $cfgClient, $client;

    $aCatPath = getCategoryPath($iStartCat, $level, $reverse, $db);

    if (is_array($aCatPath) AND count($aCatPath) > 0) {
        $aLocation = array();
        foreach($aCatPath as $value) {
            if (!$fullweblink) {
                if ($mod_rewrite == true) {
                    $linkUrl = $sess->url("index-a-$value.html");
                } else {
                    $linkUrl = $sess->url("front_content.php?idcat=$value");
                }
            } else {
                if ($mod_rewrite == true) {
                    $linkUrl = $sess->url($cfgClient[$client]["path"]["htmlpath"] . "index-a-$value.html");
                } else {
                    $linkUrl = $sess->url($cfgClient[$client]["path"]["htmlpath"] . "front_content.php?idcat=$value");
                }
            }
            $name = getCategoryName($value, $db);
            $aLocation[] = '<a href="'.$linkUrl.'" class="'.$sLinkStyleClass.'"><nobr>'.$name.'</nobr></a>';
        }
    }

    $sLocation = implode($seperator, $aLocation);
    $sLocation = '<span class="'.$sTextStyleClass.'">'.$sLocation.'</span>';

    return $sLocation;
}


/**
 * get subtree by a given id
 *
 * @param int $idcat Id of category
 * @return array Array with all deeper categories
 *
 * @copyright four for business AG <www.4fb.de>
 */
function getSubTree($idcat_start, $db)
{
    global $client, $cfg;

    //    SECURITY-FIX
    $sql = "SELECT
                B.idcat, A.level
            FROM
                ".$cfg["tab"]["cat_tree"]." AS A,
                ".$cfg["tab"]["cat"]." AS B
            WHERE
                A.idcat  = B.idcat AND
                idclient = '". Contenido_Security::escapeDB($client, $db)."'
            ORDER BY
                idtree";

    $db->query($sql);

    $subCats  = false;
    $curLevel = 0;
    while ($db->next_record()) {
        if ($db->f("idcat") == $idcat_start) {
            $curLevel = $db->f("level");
            $subCats = true;
        } elseif ($db->f("level") <= $curLevel) {   // ending part of tree
            $subCats = false;
        }

        if ($subCats == true) { //echo "true"; echo $db->f("idcat"); echo "<br>";
            $deeper_cats[] = $db->f("idcat");
        }
    }
    return $deeper_cats;
}


function getTeaserDeeperCategories($iIdcat, $db)
{
    global $client, $cfg, $lang;

    //    SECURITY-FIX
    $sql = "SELECT
               B.parentid, B.idcat
            FROM
                ".$cfg["tab"]["cat_tree"]." AS A,
                ".$cfg["tab"]["cat"]." AS B,
                ".$cfg["tab"]["cat_lang"]." AS C
            WHERE
                A.idcat  = B.idcat AND
                B.idcat  = C.idcat AND
                C.idlang = '". Contenido_Security::escapeDB($lang, $db)."' AND
                C.visible = '1' AND
                B.idclient = '". Contenido_Security::escapeDB($client, $db) ."'
            ORDER BY
                idtree";
    $db->query($sql);

    $subCats  = false;
    $curLevel = 0;
    while ($db->next_record()) {
        if ($db->f("idcat") == $iIdcat) {
            $curLevel = $db->f("level");
            $subCats = true;
        } elseif ($curLevel == $db->f("level")) {    // ending part of tree
            $subCats = false;
        }

        if ($subCats == true) {
            $deeper_cats[] = $db->f("idcat");
        }
    }
    return $deeper_cats;
}


/**
 * get subtree by a given id, without protected and invisible categories
 *
 * @param int $idcat Id of category
 * @return array Array with all deeper categories
 *
 * @copyright four for business AG <www.4fb.de>
 */
function getProtectedSubTree($idcat_start, $db)
{
    global $client, $cfg, $lang;

    //    SECURITY-FIX
    $sql = "SELECT
                B.parentid, B.idcat
            FROM
                ".$cfg["tab"]["cat_tree"]." AS A,
                ".$cfg["tab"]["cat"]." AS B,
                ".$cfg["tab"]["cat_lang"]." AS C
            WHERE
                A.idcat  = B.idcat AND
                B.idcat  = C.idcat AND
                C.idlang = '".Contenido_Security::escapeDB($lang, $db)."' AND
                C.visible = '1' AND
                C.public = '1' AND
                B.idclient = '".Contenido_Security::escapeDB($client, $db)."'
            ORDER BY
                idtree";

    $db->query($sql);

    $subCats  = false;
    $curLevel = 0;
    while ($db->next_record()) {
        if ($db->f("idcat") == $idcat_start) {
            $curLevel = $db->f("level");
            $subCats = true;
        } elseif ($curLevel == $db->f("level")) {    // ending part of tree
            $subCats = false;
        }

        if ($subCats == true) { //echo "true"; echo $db->f("idcat"); echo "<br>";
            $deeper_cats[] = $db->f("idcat");
        }
    }
    return $deeper_cats;
}


/**
 * Return category name
 */
function getCategoryName($cat_id, &$db)
{
    global $cfg, $client, $lang;

        //    SECURITY-FIX
    $sql = "SELECT
                *
            FROM
                ".$cfg["tab"]["cat"]." AS A,
                ".$cfg["tab"]["cat_lang"]." AS B
            WHERE
                A.idcat     = B.idcat   AND
                A.idcat     = '". Contenido_Security::escapeDB($cat_id, $db)."' AND
                A.idclient  = '".Contenido_Security::escapeDB($client, $db)."' AND
                B.idlang    = '".Contenido_Security::escapeDB($lang, $db)."'
            ";

    $db->query($sql);

    if ($db->next_record()) {
        $cat_name = $db->f("name");
        return $cat_name;
    } else {
        return '';
    }
}


// get direct subcategories of a given category
function getSubCategories($parent_id, $db)
{
    $subcategories = array();

    global $cfg, $client, $lang;

//    SECURITY-FIX
    $sql = "SELECT
                A.idcat
            FROM
                ".$cfg["tab"]["cat_tree"]." AS A,
                ".$cfg["tab"]["cat"]." AS B,
                ".$cfg["tab"]["cat_lang"]." AS C
            WHERE
                A.idcat     = B.idcat   AND
                B.idcat     = C.idcat   AND
                B.idclient  = '". Contenido_Security::escapeDB($client, $db)."' AND
                C.idlang    = '".Contenido_Security::escapeDB($lang, $db)."'   AND
                C.visible   = '1'       AND
                C.public    = '1'       AND
                B.parentid  = '".Contenido_Security::escapeDB($parent_id, $db)."'
            ORDER BY
                A.idtree";

    $db->query($sql);

    while ($db->next_record()) {
        $subcategories[] = $db->f("idcat");
    }

    return  $subcategories;
}


// get direct subcategories with protected categories
function getProtectedSubCategories($parent_id, $db)
{
    $subcategories = array();
    unset($subcategories);

    global $cfg, $client, $lang;

//    SECURITY-FIX
    $sql = "SELECT
                A.idcat
            FROM
                ".$cfg["tab"]["cat_tree"]." AS A,
                ".$cfg["tab"]["cat"]." AS B,
                ".$cfg["tab"]["cat_lang"]." AS C
            WHERE
                A.idcat     = B.idcat   AND
                B.idcat     = C.idcat   AND
                B.idclient  = '".Contenido_Security::escapeDB($client, $db)."' AND
                C.idlang    = '".Contenido_Security::escapeDB($lang, $db)."'   AND
                B.parentid  = '".Contenido_Security::escapeDB($parent_id, $db)."'
            ORDER BY
                A.idtree";

    $db->query($sql);

    while ($db->next_record()) {
        $subcategories[] = $db->f("idcat");
    }

    return  $subcategories;
}


function checkCatPermission($idcatlang, $public)
{
    #Check if current user has permissions to access cat

    global $auth;

    $oDB = new DB_Contenido();

    $frontendPermissionCollection = new cApiFrontendPermissionCollection();
    $visible=false;

    if ($public!=0) {
        $visible = true;
    } elseif (($auth->auth['uid']!='')&&($auth->auth['uid']!='nobody')) {
        $FrontendGroupMemberCollection = new cApiFrontendGroupMemberCollection();
        $FrontendGroupMemberCollection->setWhere("idfrontenduser",$auth->auth['uid']);
        $FrontendGroupMemberCollection->query();
        $groups = array();
        while ($member = $FrontendGroupMemberCollection->next()) {
            $groups[] = $member->get("idfrontendgroup");
        }
    }
    if (count($groups)>0) {
        for($i=0;$i<count($groups);$i++) {
            if ($frontendPermissionCollection->checkPerm($groups[$i],'category','access',$idcatlang, true)) {
                $visible=true;
            }
        }
    }

    return $visible;
}

?>