<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Defines the "str" related functions
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend includes
 * @version    1.3.6
 * @author     Olaf Niemann
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 *
 * {@internal
 *   created 2002-03-02
 *   modified 2008-06-26, Frederic Schneider, add security fix
 *   modified 2008-08-29, Murat Purc, add new chain execution
 *   modified 2008-09-03, Hotfix recursive call more than 200 times exit script on hosteurope Timo.Trautmann (strRemakeTreeTableFindNext)
 *   modified 2008-10-29, delete from cat_tree only for one Cliente OliverL (strRemakeTreeTable)
 *   modified 2008-11-03, Add cat_tree only for one Cliente OliverL (strRemakeTreeTable)
 *   modified 2009-05-05, Timo Trautmann - optional use for copy label on copy proccess
 *   modified 2009-10-14, Dominik Ziegler - changed functionality of strMoveSubtree and strMoveCatTargetallowed to prevent crashing tree on moving
 *   modified 2009-10-23, Murat Purc, removed deprecated function (PHP 5.3 ready), commenting code and some formatting
 *   modified 2009-10-27, Murat Purc, fixed/modified CEC_Hook, see [#CON-256]
 *
 *   $Id$:
 * }}
 *
 */

if(!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/*********************************************************************************
Functions
strNewTree($catname)
strNewCategory($tmp_parentid, $catname)
strOrderedPostTreeList ($idcat, $poststring)
strRemakeTreeTable()
strNextDeeper($tmp_idcat)
strNextPost($tmp_idcat)
strNextBackwards($tmp_idcat)
strRemakeTreeTableFindNext($tmp_idcat,$tmp_level)
strShowTreeTable()
strRenameCategory ($idcat, $lang, $newcategoryname)
strMakeVisible ($idcat, $lang, $visible)
strMakePublic ($idcat, $lang, $public)
strDeleteCategory ($idcat)
strMoveUpCategory ($idcat)
strMoveDownCategory ($idcat)
strMoveSubtree ($idcat, $parentid_new)
strMoveCatTargetallowed($idcat, $source)
********************************************************************************/

cInclude("classes", "contenido/class.category.php");
cInclude("classes", "contenido/class.categorylanguage.php");
cInclude("classes", "contenido/class.template.php");
cInclude("classes", "contenido/class.templateconfig.php");
cInclude("classes", "contenido/class.containerconfig.php");
cInclude("classes", "contenido/class.container.php");
cInclude("includes", "functions.con.php");

global $db_str;
global $db_str2;

if (class_exists("DB_Contenido")) {
    $db_str = new DB_Contenido();
    $db_str2 = new DB_Contenido();
}


/**
 * Creates a new category tree (root category item).
 *
 * @param   string  $catname     The category name
 * @param   string  $catalias    Alias of category
 * @param   bool    $bVisible    Flag about visible status
 * @param   bool    $bPublic     Flag about public status
 * @param   int     $iIdtplcfg   Id of template configuration
 * @return  (int|void)  Id of new generated category or nothing on failure
 */
function strNewTree($catname, $catalias = '', $bVisible = 0, $bPublic = 1, $iIdtplcfg = 0) {
    global $db;
    global $client;
    global $lang;
    global $cfg;
    global $area_tree;
    global $sess;
    global $perm;
    global $area_rights;
    global $item_rights;
    global $_SESSION;
    // Flag to rebuild the category table
    global $remakeCatTable;
    global $remakeStrTable;
    global $auth;

    $remakeCatTable = true;
    $remakeStrTable = true;

    $db2= new DB_Contenido;

    if (trim($catname) == "") {
        return;
    }

    $catalias = trim($catalias);
    if ($catalias == "") {
        $catalias = trim($catname);
    }

    $tmp_newid = $db->nextid($cfg["tab"]["cat"]);
    if ($tmp_newid == 0) {
        return;
    }

    if ($perm->have_perm_area_action("str_tplcfg", "str_tplcfg")) {
        $iIdtplcfg = (int) $iIdtplcfg;
    } else  {
        $iIdtplcfg = 0;
    }

    $bVisible = (int) $bVisible;
    if (! (($bVisible == 0 || $bVisible == 1) && $perm->have_perm_area_action('str', "str_makevisible")) ) {
        $bVisible = 0;
    }

    $bPublic = (int) $bPublic;
    if (! (($bPublic == 0 || $bPublic == 1) && $perm->have_perm_area_action('str', "str_makepublic")) ) {
        $bPublic = 1;
    }

    $sql = "SELECT idcat FROM ".$cfg["tab"]["cat"]." WHERE parentid='0' AND postid='0' AND idclient='".Contenido_Security::toInteger($client)."'";
    $db->query($sql);
    $db->next_record();
    $tmp_id = $db->f("idcat");

    $a_languages[] = $lang;

    if (is_array($a_languages)) {

        if (!$tmp_id) {
            //********** Entry in 'cat'-table ************
            $sql = "INSERT INTO ".$cfg["tab"]["cat"]." (idcat, preid, postid, idclient, author, created, lastmodified) VALUES('".Contenido_Security::toInteger($tmp_newid)."', '0', '0',
                    '".Contenido_Security::toInteger($client)."', '".Contenido_Security::escapeDB($auth->auth['uname'], $db)."', '".date("Y-m-d H:i:s")."', '".date("Y-m-d H:i:s")."')";
            $db->query($sql);

            //********* enter name of cat in 'cat_lang'-table ******
            foreach ($a_languages as $tmp_lang) {
                if ($tmp_lang == $lang) {
                    $sql = "INSERT INTO ".$cfg["tab"]["cat_lang"]." (idcatlang, idcat, idlang, name, visible, public, idtplcfg, urlname, author, created, lastmodified)
                            VALUES('".Contenido_Security::toInteger($db->nextid($cfg["tab"]["cat_lang"]))."', '".Contenido_Security::toInteger($tmp_newid)."', '".Contenido_Security::toInteger($tmp_lang)."',
                            '".htmlspecialchars($catname, ENT_QUOTES)."', '".Contenido_Security::toInteger($bVisible)."', '".Contenido_Security::toInteger($bPublic)."', '0',
                            '".htmlspecialchars(capiStrCleanURLCharacters($catalias), ENT_QUOTES)."', '".Contenido_Security::escapeDB($auth->auth['uname'], $db)."', '".date("Y-m-d H:i:s")."', '".date("Y-m-d H:i:s")."')";
                    $db->query($sql);
                } else {
                    $sql = "INSERT INTO ".$cfg["tab"]["cat_lang"]." (idcatlang, idcat, idlang, name, visible, public, idtplcfg, urlname, author, created, lastmodified)
                            VALUES('".Contenido_Security::toInteger($db->nextid($cfg["tab"]["cat_lang"]))."', '".Contenido_Security::toInteger($tmp_newid)."', '".Contenido_Security::toInteger($tmp_lang)."',
                            '".htmlspecialchars($catname, ENT_QUOTES)."', '".Contenido_Security::toInteger($bVisible)."', '".Contenido_Security::toInteger($bPublic)."', '0',
                           '".htmlspecialchars(capiStrCleanURLCharacters($catalias), ENT_QUOTES)."', '".Contenido_Security::escapeDB($auth->auth['uname'], $db)."', '".date("Y-m-d H:i:s")."', '".date("Y-m-d H:i:s")."')";
                    $db->query($sql);
                }
            }
        } else {
            //********** Entry in 'cat'-table ************
            $sql = "UPDATE ".$cfg["tab"]["cat"]." SET postid='$tmp_newid' WHERE idcat='".Contenido_Security::toInteger($tmp_id)."'";
            $db->query($sql);

            //********** Entry in 'cat'-table ************
            $sql = "INSERT INTO ".$cfg["tab"]["cat"]." (idcat, preid, postid, idclient, author, created, lastmodified) VALUES('".Contenido_Security::toInteger($tmp_newid)."', '".Contenido_Security::toInteger($tmp_id)."',
                    '0', '".Contenido_Security::toInteger($client)."', '".Contenido_Security::escapeDB($auth->auth['uname'], $db)."', '".date("Y-m-d H:i:s")."', '".date("Y-m-d H:i:s")."')";
            $db->query($sql);

            //********* enter name of cat in 'cat_lang'-table ******
            foreach ($a_languages as $tmp_lang) {
                if ($tmp_lang == $lang) {
                    $sql = "INSERT INTO ".$cfg["tab"]["cat_lang"]." (idcatlang, idcat, idlang, name, visible, public, idtplcfg, urlname, author, created, lastmodified)
                            VALUES('".Contenido_Security::toInteger($db->nextid($cfg["tab"]["cat_lang"]))."', '".Contenido_Security::toInteger($tmp_newid)."', '".Contenido_Security::toInteger($tmp_lang)."',
                            '".htmlspecialchars($catname, ENT_QUOTES)."', '".Contenido_Security::toInteger($bVisible)."', '".Contenido_Security::toInteger($bPublic)."', '0',
                            '".htmlspecialchars(capiStrCleanURLCharacters($catalias), ENT_QUOTES)."', '".Contenido_Security::escapeDB($auth->auth['uname'], $db)."', '".date("Y-m-d H:i:s")."', '".date("Y-m-d H:i:s")."')";
                    $db->query($sql);
                } else {
                    $sql = "INSERT INTO ".$cfg["tab"]["cat_lang"]." (idcatlang, idcat, idlang, name, visible, public, idtplcfg, urlname, author, created, lastmodified)
                            VALUES('".Contenido_Security::toInteger($db->nextid($cfg["tab"]["cat_lang"]))."', '".Contenido_Security::toInteger($tmp_newid)."', '".Contenido_Security::toInteger($tmp_lang)."',
                            '".htmlspecialchars($catname, ENT_QUOTES)."', '".Contenido_Security::toInteger($bVisible)."', '".Contenido_Security::toInteger($bPublic)."', '0',
                            '".htmlspecialchars(capiStrCleanURLCharacters($catalias), ENT_QUOTES)."', '".Contenido_Security::escapeDB($auth->auth['uname'], $db)."', '".date("Y-m-d H:i:s")."', '".date("Y-m-d H:i:s")."')";
                    $db->query($sql);
                }
            }
        }

        // set correct rights for element
        cInclude ("includes", "functions.rights.php");
        foreach ($a_languages as $tmp_lang) {
            createRightsForElement("str", $tmp_newid, $tmp_lang);
            createRightsForElement("con", $tmp_newid, $tmp_lang);
        }

    }

    /* Search for default template */
    $templateCollection = new cApiTemplateCollection("defaulttemplate = '1' AND idclient = '".Contenido_Security::toInteger($client)."'");

    if ($template = $templateCollection->next()) {
        $idtpl = $template->get("idtpl");
        if ($iIdtplcfg > 0) {
            $idtpl = $iIdtplcfg;
        }

        /* Assign template, if default template exists */
        $catCollection = new cApiCategoryLanguageCollection("idcat = '".Contenido_Security::toInteger($tmp_newid)."'");

        while ($cat = $catCollection->next()) {
            $cat->assignTemplate($idtpl);
        }

    } else {
      //2008-06-25 timo.trautmann also set default template if it is selcted by user and there is no default template
      if ($iIdtplcfg > 0) {
          $idtpl = $iIdtplcfg;

          $catCollection = new cApiCategoryLanguageCollection("idcat = '$tmp_newid'");

          while ($cat = $catCollection->next()) {
              $cat->assignTemplate($idtpl);
          }
      }
    }

    return ($tmp_newid);
}


/**
 * Creates a new category.
 *
 * @param   int     $tmp_parentid  Id of parent category
 * @param   string  $catname       The category name
 * @param   bool    $remakeTree    Flag to rebuild category tree structure
 * @param   string  $catalias      Alias of category
 * @param   bool    $bVisible      Flag about visible status
 * @param   bool    $bPublic       Flag about public status
 * @param   int     $iIdtplcfg     Id of template configuration
 * @return  (int|void)  Id of new generated category or nothing on failure
 */
function strNewCategory($tmp_parentid, $catname, $remakeTree = true, $catalias = '', $bVisible = 0, $bPublic = 1, $iIdtplcfg = 0) {
    global $db;
    global $client;
    global $lang;
    global $cfg;
    global $area_tree;
    global $perm;
    // Flag to rebuild the category table
    global $remakeCatTable;
    global $remakeStrTable;
    global $auth;
    global $tmp_area;

    $db2= new DB_Contenido;

    if (trim($catname) == "") {
        return;
    }

    $catalias = trim($catalias);
    if ($catalias == "") {
        $catalias = trim($catname);
    }

    if ($perm->have_perm_area_action("str_tplcfg", "str_tplcfg")) {
        $iIdtplcfg = (int) $iIdtplcfg;
    } else  {
        $iIdtplcfg = 0;
    }

    $bVisible = (int) $bVisible;
    if (! (($bVisible == 0 || $bVisible == 1) && $perm->have_perm_area_action('str', "str_makevisible")) ) {
        $bVisible = 0;
    }

    $bPublic = (int) $bPublic;
    if (! (($bPublic == 0 || $bPublic == 1) && $perm->have_perm_area_action('str', "str_makepublic")) ) {
        $bPublic = 1;
    }

    $tmp_newid = $db->nextid($cfg["tab"]["cat"]);
    if ($tmp_newid == 0) {
        return;
    }

    $remakeCatTable = true;
    $remakeStrTable = true;

    $sql = "SELECT idcat FROM ".$cfg["tab"]["cat"]." WHERE parentid='".Contenido_Security::toInteger($tmp_parentid)."' AND postid=0";
    $db->query($sql);
    $db->next_record();
    $tmp_id = $db->f("idcat");

    if (!$tmp_id) {
        //********** Entry in 'cat'-table ************
        $sql = "INSERT INTO ".$cfg["tab"]["cat"]." (idcat, parentid, preid, postid, idclient, author, created, lastmodified) VALUES('".Contenido_Security::toInteger($tmp_newid)."',
                '".Contenido_Security::toInteger($tmp_parentid)."', '0', '0', '".Contenido_Security::toInteger($client)."', '".Contenido_Security::escapeDB($auth->auth['uname'], $db)."',
                '".date("Y-m-d H:i:s")."', '".date("Y-m-d H:i:s")."')";
        $db->query($sql);

        //********* enter name of cat in 'cat_lang'-table ******
        $a_languages[] = $lang;

        foreach ($a_languages as $tmp_lang) {
            if ($tmp_lang == $lang) {
                $sql = "INSERT INTO ".$cfg["tab"]["cat_lang"]." (idcatlang, idcat, idlang, name, visible, public, idtplcfg, urlname, author, created, lastmodified)
                        VALUES('".Contenido_Security::toInteger($db->nextid($cfg["tab"]["cat_lang"]))."', '".Contenido_Security::toInteger($tmp_newid)."', '".Contenido_Security::toInteger($tmp_lang)."',
                        '".htmlspecialchars($catname, ENT_QUOTES)."', '".Contenido_Security::toInteger($bVisible)."', '".Contenido_Security::toInteger($bPublic)."', '0',
                        '".htmlspecialchars(capiStrCleanURLCharacters($catalias), ENT_QUOTES)."', '".Contenido_Security::escapeDB($auth->auth['uname'], $db)."', '".date("Y-m-d H:i:s")."', '".date("Y-m-d H:i:s")."')";
                $db->query($sql);
            } else {
                $sql = "INSERT INTO ".$cfg["tab"]["cat_lang"]." (idcatlang, idcat, idlang, name, visible, public, idtplcfg, urlname, author, created, lastmodified)
                        VALUES('".Contenido_Security::toInteger($db->nextid($cfg["tab"]["cat_lang"]))."', '".Contenido_Security::toInteger($tmp_newid)."', '".Contenido_Security::toInteger($tmp_lang)."',
                        '".htmlspecialchars($catname, ENT_QUOTES)."', '".Contenido_Security::toInteger($bVisible)."', '".Contenido_Security::toInteger($bPublic)."', '0',
                        '".htmlspecialchars(capiStrCleanURLCharacters($catalias), ENT_QUOTES)."', '".Contenido_Security::escapeDB($auth->auth['uname'], $db)."', '".date("Y-m-d H:i:s")."', '".date("Y-m-d H:i:s")."')";
                $db->query($sql);
            }
        }
    } else {
        //********** Entry in 'cat'-table ************
        $sql = "UPDATE ".$cfg["tab"]["cat"]." SET postid='$tmp_newid', lastmodified = '".date("Y-m-d H:i:s")."' WHERE idcat='".Contenido_Security::toInteger($tmp_id)."'";
        $db->query($sql);

        //********** Entry in 'cat'-table ************
        $sql = "INSERT INTO ".$cfg["tab"]["cat"]." (idcat, parentid, preid, postid, idclient, author, created, lastmodified) VALUES('".Contenido_Security::toInteger($tmp_newid)."',
                '".Contenido_Security::toInteger($tmp_parentid)."', '".Contenido_Security::toInteger($tmp_id)."', '0', '".Contenido_Security::toInteger($client)."',
                '".Contenido_Security::escapeDB($auth->auth['uname'], $db)."', '".date("Y-m-d H:i:s")."', '".date("Y-m-d H:i:s")."')";
        $db->query($sql);

        //********* enter name of cat in 'cat_lang'-table ******
        $a_languages[] = $lang;
        foreach ($a_languages as $tmp_lang) {
            if ($tmp_lang == $lang) {
                $sql = "INSERT INTO ".$cfg["tab"]["cat_lang"]." (idcatlang, idcat, idlang, name, visible, public, idtplcfg, urlname, author, created, lastmodified)
                        VALUES('".Contenido_Security::toInteger($db->nextid($cfg["tab"]["cat_lang"]))."', '".Contenido_Security::toInteger($tmp_newid)."', '".Contenido_Security::toInteger($tmp_lang)."',
                        '".htmlspecialchars($catname, ENT_QUOTES)."', '".Contenido_Security::toInteger($bVisible)."', '".Contenido_Security::toInteger($bPublic)."', '0',
                        '".htmlspecialchars(capiStrCleanURLCharacters($catalias), ENT_QUOTES)."', '".Contenido_Security::escapeDB($auth->auth['uname'], $db)."', '".date("Y-m-d H:i:s")."', '".date("Y-m-d H:i:s")."')";
                $db->query($sql);
            } else {
                $sql = "INSERT INTO ".$cfg["tab"]["cat_lang"]." (idcatlang, idcat, idlang, name, visible, public, idtplcfg, urlname, author, created, lastmodified)
                        VALUES('".Contenido_Security::toInteger($db->nextid($cfg["tab"]["cat_lang"]))."', '".Contenido_Security::toInteger($tmp_newid)."', '".Contenido_Security::toInteger($tmp_lang)."',
                        '".htmlspecialchars($catname, ENT_QUOTES)."', '".Contenido_Security::toInteger($bVisible)."', '".Contenido_Security::toInteger($bPublic)."', '0',
                        '".htmlspecialchars(capiStrCleanURLCharacters($catalias), ENT_QUOTES)."', '".Contenido_Security::escapeDB($auth->auth['uname'], $db)."', '".date("Y-m-d H:i:s")."', '".date("Y-m-d H:i:s")."')";
                $db->query($sql);
            }
        }

    }

    // set correct rights for element
    cInclude ("includes", "functions.rights.php");
    foreach ($a_languages as $tmp_lang) {
        copyRightsForElement("str", $tmp_parentid, $tmp_newid, $tmp_lang);
        copyRightsForElement("con", $tmp_parentid, $tmp_newid, $tmp_lang);
    }

    if ($remakeTree == true) {
        strRemakeTreeTable();
    }

    /* Search for default template */
    $templateCollection = new cApiTemplateCollection("defaulttemplate = '1' AND idclient = '$client'");

    if ($template = $templateCollection->next()) {
        $idtpl = $template->get("idtpl");
        if ($iIdtplcfg > 0) {
            $idtpl = $iIdtplcfg;
        }

        /* Assign template, if default template exists */
        $catCollection = new cApiCategoryLanguageCollection("idcat = '".Contenido_Security::toInteger($tmp_newid)."'");

        while ($cat = $catCollection->next()) {
            $cat->assignTemplate($idtpl);
        }
    } else {
      //2008-06-25 timo.trautmann also set default template if it is selcted by user and there is no default template
      if ($iIdtplcfg > 0) {
          $idtpl = $iIdtplcfg;

          $catCollection = new cApiCategoryLanguageCollection("idcat = '".Contenido_Security::toInteger($tmp_newid)."'");

          while ($cat = $catCollection->next()) {
                $cat->assignTemplate($idtpl);
          }
      }
    }

    return($tmp_newid);
}


function strOrderedPostTreeList($idcat, $poststring) {
    global $db;
    global $client;
    global $lang;
    global $cfg;

    $sql = "SELECT idcat FROM ".$cfg["tab"]["cat"]." WHERE parentid=0 AND preid='".Contenido_Security::toInteger($idcat)."' AND idcat!=0";

    $db->query($sql);
    if ($db->next_record()) {
        $tmp_idcat = $db->f("idcat");
        $poststring = $poststring.",".$tmp_idcat;
        $poststring = strOrderedPostTreeList($tmp_idcat, $poststring);
    }

    return $poststring;
}


/**
 * Remakes the category tree structure in category tree table.
 *
 * @return  void
 */
function strRemakeTreeTable() {
    global $db;
    global $client;
    global $lang;
    global $cfg;
    // Flag to rebuild the category table
    global $remakeCatTable;
    global $remakeStrTable;
    $remakeCatTable = true;
    $remakeStrTable = true;

    $poststring = "";
	$sql = "SELECT idcat FROM ".$cfg["tab"]["cat"]." WHERE idclient = '".$client."'";
    $db->query($sql);
    $idcats = array();
    while ($db->next_record()) {
        $idcats[] = $db->f("idcat");
    }
    
    $sql = "DELETE FROM ".$cfg["tab"]["cat_tree"]." WHERE idcat IN ('".implode("', '",$idcats)."')"; // empty 'cat_tree'-table
    $db->query($sql);

    $sql = "DELETE FROM ".$cfg["tab"]["cat"]." WHERE idcat='0'";
    $db->query($sql);

    $sql = "DELETE FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat='0'";
    $db->query($sql);

    $sql = "SELECT idcat, parentid, preid, postid FROM ".$cfg["tab"]["cat"]." WHERE idclient = '".$client."' ORDER BY parentid ASC, preid ASC, postid ASC";
    
    $db->query($sql);
        
    $iNextTreeId = $db->nextid($cfg["tab"]["cat_tree"]);
    
	// build cat_tree
    $aCategories = array();
    $aLevels = array();
    while($db->next_record()) {
        
        if ($db->f('parentid') == 0) {
			$aCategories[0][$db->f('idcat')] = array(
				'idcat' => $db->f('idcat'),
		    	'parentid' => $db->f('parentid'),
				'level' => 0
			);
            $aLevels[$db->f('idcat')] = 0;											     
	    } else {
			$iLevel = $aLevels[$db->f('parentid')] + 1;
			$aLevels[$db->f('idcat')] = $iLevel;
			$aCategories[$db->f('parentid')][$db->f('idcat')] = array(
				'idcat' => $db->f('idcat'),
			    'parentid' => $db->f('parentid'),
				'level' => $iLevel
			);
		}			                                      
    }
    
    // build INSERT statement
    $sInsertQuery = "INSERT INTO ".$cfg["tab"]["cat_tree"]." (idtree, idcat, level) VALUES ";        
    $sInsertQuery = recCats($aCategories[0], $sInsertQuery, $iNextTreeId, $aCategories);
    $sInsertQuery = rtrim($sInsertQuery, " ,");
    
	// lock db table and execute INSERT query    
    $db->lock($cfg["tab"]["cat_tree"]);
    $db->query($sInsertQuery);
    $db->unlock($cfg["tab"]["cat_tree"]);
        
}

function recCats ($aCats, $sInsertQuery, &$iNextTreeId, &$aAllCats) {
	foreach ($aCats as $iCat => $aCat) {
		$sInsertQuery .= "(" . (int) $iNextTreeId . ", ".(int) $iCat.", ". (int) $aCat['level']."), ";
		$iNextTreeId++;
		if (is_array($aAllCats[$iCat])) {
			$sInsertQuery = recCats($aAllCats[$iCat], $sInsertQuery, $iNextTreeId, $aAllCats);
		}
	}
	return $sInsertQuery;
}


function strNextDeeper($tmp_idcat, $ignore_lang = false) {
    global $cfg, $db_str, $lang;

    $sql = "SELECT idcat FROM ".$cfg["tab"]["cat"]." WHERE parentid='".Contenido_Security::toInteger($tmp_idcat)."' AND preid='0'";
    $db_str->query($sql);
    if ($db_str->next_record()) {
        $midcat = $db_str->f("idcat");
        if ($ignore_lang == true) {
            return $midcat;
        }

        //******deeper element exists
        /* Check for language dependent part */

        $sql = "SELECT idcatlang FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat='".Contenido_Security::toInteger($midcat)."' AND idlang='".Contenido_Security::toInteger($lang)."'";
        $db_str->query($sql);
        if ($db_str->next_record()) {
            return $midcat;
        } else {
            return 0;
        }
    } else {
        //******deeper element does not exist
        return 0;
    }
}


/**
 * Checks, if passed category cotains any articles
 *
 * @param   int  $tmp_idcat  ID of category
 * @return  bool
 */
function strHasArticles($tmp_idcat) {
    global $cfg, $db_str;
    global $lang;

    $sql = "SELECT b.idartlang AS idartlang FROM
                ".$cfg["tab"]["cat_art"]." AS a,
                ".$cfg["tab"]["art_lang"]." AS b
                WHERE a.idcat='".Contenido_Security::toInteger($tmp_idcat)."' AND
                a.idart = b.idart AND b.idlang = '".Contenido_Security::toInteger($lang)."'";

    $db_str->query($sql);

    if ($db_str->next_record()) { //******post element exists
        return true;
    } else { //******post element does not exist
        return false;
    }
}


function strNextPost($tmp_idcat) {
    global $db;
    global $cfg;

    $sql = "SELECT idcat FROM ".$cfg["tab"]["cat"]." WHERE preid='".Contenido_Security::toInteger($tmp_idcat)."'";
    $db->query($sql);
    if ($db->next_record()) { //******post element exists
        $tmp_idcat = $db->f("idcat");
        $sql = "SELECT parentid FROM ".$cfg["tab"]["cat"]." WHERE idcat='".Contenido_Security::toInteger($tmp_idcat)."'";
        $db->query($sql);
        if ($db->next_record()) { //******parent from post must not be 0
            $tmp_parentid = $db->f("parentid");
            if ($tmp_parentid != 0) {
                return $tmp_idcat;
            } else {
                return 0;
            }
        } else {
            return 99;
        }
    } else { //******post element does not exist
        return 0;
    }
}

function strNextBackwards($tmp_idcat) {
    global $db;
    global $cfg;

    $sql = "SELECT parentid FROM ".$cfg["tab"]["cat"]." WHERE idcat='".Contenido_Security::toInteger($tmp_idcat)."'";
    $db->query($sql);
    if ($db->next_record()) { //******parent exists
        $tmp_idcat = $db->f("parentid");
        if ($tmp_idcat != 0) {
            $sql = "SELECT idcat FROM ".$cfg["tab"]["cat"]." WHERE preid='".Contenido_Security::toInteger($tmp_idcat)."'";
            $db->query($sql);
            if ($db->next_record()) { //******parent has post
                $tmp_idcat = $db->f("idcat");
                $sql = "SELECT parentid FROM ".$cfg["tab"]["cat"]." WHERE idcat='".Contenido_Security::toInteger($tmp_idcat)."'";
                $db->query($sql);
                if ($db->next_record()) { //******parent from post must not be 0
                    $tmp_parentid = $db->f("parentid");
                    if ($tmp_parentid != 0) {
                        return $tmp_idcat;
                    } else {
                        return 0;
                    }
                } else {
                    return 99;
                }
            } else { //******parent has no post
                return strNextBackwards($tmp_idcat);
            }
        } else {
            return 0;
        }
    } else { //******no parent
        return 0;
    }
}

/**
    Hotfix recursive call more than 200 times exit script on hosteurope Timo.Trautmann (strRemakeTreeTableFindNext)
**/
function strRemakeTreeTableFindNext($tmp_idcat,$tmp_level) {
    global $db;
    global $cfg;

    //************* Insert Element in 'cat_tree'-table **************
    $sql = "INSERT INTO ".$cfg["tab"]["cat_tree"]." (idtree, idcat, level) VALUES ('".$db->nextid($cfg["tab"]["cat_tree"])."', '".Contenido_Security::toInteger($tmp_idcat)."', '".Contenido_Security::toInteger($tmp_level)."')";
    $db->query($sql);

    //************* dig deeper, if possible ******
    $tmp = strNextDeeperAll($tmp_idcat, true);

    foreach ($tmp as $iCurIdCat) {
        if (count(strNextDeeperAll($iCurIdCat, true)) > 0 ) {
            strRemakeTreeTableFindNext($iCurIdCat, ($tmp_level+1));
        } else {
            $sql = "INSERT INTO ".$cfg["tab"]["cat_tree"]." (idtree, idcat, level) VALUES ('".$db->nextid($cfg["tab"]["cat_tree"])."', '".Contenido_Security::toInteger($iCurIdCat)."', '".Contenido_Security::toInteger($tmp_level+1)."')";
            $db->query($sql);
        }
    }
}



/**
    Hotfix recursive call more than 200 times exit script on hosteurope Timo.Trautmann
**/
function strNextDeeperAll($tmp_idcat, $ignore_lang = false) {
    global $cfg, $db_str, $db_str2, $lang;
    $aCats = array();
    $bLoop = true;
    $sql = "SELECT idcat FROM ".$cfg["tab"]["cat"]." WHERE parentid='".Contenido_Security::toInteger($tmp_idcat)."' and preid = 0";

    #echo $sql.'<br>';
    $db_str->query($sql);
    if ($db_str->next_record()) {
        while ($bLoop) {
            $midcat = $db_str->f("idcat");

            if ($ignore_lang == true) {
                array_push($aCats, $midcat);
            } else {
                //******deeper element exists
                /* Check for language dependent part */
                $sql = "SELECT idcatlang FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat='".Contenido_Security::toInteger($midcat)."' AND idlang='".Contenido_Security::toInteger($lang)."'";
                $db_str2->query($sql);

                if ($db_str2->next_record()) {
                    array_push($aCats, $midcat);
                }
            }

            $sql = "SELECT preid, postid, idcat FROM ".$cfg["tab"]["cat"]." WHERE parentid='".Contenido_Security::toInteger($tmp_idcat)."' and preid = ".Contenido_Security::toInteger($midcat)."";
            $db_str->query($sql);
            if (!$db_str->next_record()) {
                $bLoop = false;
            }
        }
    }
    return $aCats;
}


/**
 * Renders the category tree a HTML table
 *
 * @return  void
 */
function strShowTreeTable() {
    global $db;
    global $sess;
    global $client;
    global $lang;
    global $idcat;
    global $cfg;
    global $lngStr;

    echo "<br><table cellpadding=$cellpadding cellspacing=$cellspacing border=$border >";
    $sql = "SELECT * FROM ".$cfg["tab"]["cat_tree"]." AS A, ".$cfg["tab"]["cat"]." AS B, ".$cfg["tab"]["cat_lang"]." AS C WHERE A.idcat=B.idcat AND B.idcat=C.idcat AND C.idlang='".Contenido_Security::toInteger($lang)."'
            AND B.idclient='".Contenido_Security::toInteger($client)."' ORDER BY A.idtree";
    $db->query($sql);
    while ($db->next_record()) {
        $tmp_id    = $db->f("idcat");
        $tmp_name  = $db->f("name");
        $tmp_level = $db->f("level");

        echo "<tr><td>".$tmp_id." | ".$tmp_name." | ".$tmp_level."</td>";
        echo "<td><a class=action href=\"".$sess->url("main.php?action=20&idcat=$tmp_id")."\">".$lngStr["actions"]["20"]."</a></td>";
        echo "<td><a class=action href=\"".$sess->url("main.php?action=30&idcat=$tmp_id")."\">".$lngStr["actions"]["30"]."</a></td>";
        echo "</td></tr>";
    }
    echo "</table>";
}


/**
 * Renames a category
 *
 * @param   int     $idcat             Category id
 * @param   int     $lang              Language id
 * @param   string  $newcategoryname   New category name
 * @param   string  $newcategoryalias  New category alias
 * @return  void
 */
function strRenameCategory($idcat, $lang, $newcategoryname, $newcategoryalias) {
    global $db;
    global $cfg;
    global $cfgClient;
    global $client;

    // Flag to rebuild the category table
    global $remakeCatTable;
    global $remakeStrTable;
    $remakeCatTable = true;
    $remakeStrTable = true;

    if (trim($newcategoryname) != "") {
        $sUrlname = htmlspecialchars(capiStrCleanURLCharacters($newcategoryname), ENT_QUOTES);
        $sName = htmlspecialchars($newcategoryname, ENT_QUOTES);

        if (trim($newcategoryalias) != "") {
            $sql = "SELECT urlname, name FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat='".Contenido_Security::toInteger($idcat)."' AND idlang='".Contenido_Security::toInteger($lang)."'";
            $db->query($sql);
            $sUrlnameNew = htmlspecialchars(capiStrCleanURLCharacters($newcategoryalias), ENT_QUOTES);
            if ($db->next_record()) {
                $sOldAlias = $db->f('urlname');
                $sOldName = $db->f('name');
            }
            if ($sOldAlias != $sUrlnameNew) {
                $sUrlname = $sUrlnameNew;
            }

            @unlink($cfgClient[$client]["path"]["frontend"]."cache/locationstring-url-cache-$lang.txt");
        }

        $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET urlname='".Contenido_Security::escapeDB($sUrlname, $db)."', name='".Contenido_Security::escapeDB($sName, $db)."', lastmodified = '".date("Y-m-d H:i:s")."'
                WHERE idcat='".Contenido_Security::toInteger($idcat)."' AND idlang='".Contenido_Security::toInteger($lang)."'";
        $db->query($sql);
    }
}


/**
 * Sets the visible status of the category and its childs
 *
 * @param   int  $idcat    Category id
 * @param   int  $lang     Language id
 * @param   int  $visible  Visible status
 * @return  void
 */
function strMakeVisible ($idcat, $lang, $visible) {
    global $db;
    global $cfg;
    // Flag to rebuild the category table
    global $remakeCatTable;
    global $remakeStrTable;
    $remakeCatTable = true;
    $remakeStrTable = true;

    $a_catstring = strDeeperCategoriesArray($idcat);
    foreach ($a_catstring as $value) {
        $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET visible='".Contenido_Security::toInteger($visible)."', lastmodified ='".date("Y-m-d H:i:s")."'
                WHERE idcat='".Contenido_Security::toInteger($value)."' AND idlang='".Contenido_Security::toInteger($lang)."' ";
        $db->query($sql);
    }

    if ($cfg["pathresolve_heapcache"] == true && $visible = 0) {
        $pathresolve_tablename = $cfg["sql"]["sqlprefix"]."_pathresolve_cache";
        $sql = "DELETE FROM %s WHERE idlang = '%s' AND idcat = '%s'";
        $db->query(sprintf($sql, Contenido_Security::escapeDB($pathresolve_tablename, $db), Contenido_Security::toInteger($lang), $idcat));
    }
}


/**
 * Sets the public status of the category and its childs
 *
 * @param   int  $idcat   Category id
 * @param   int  $lang    Language id
 * @param   int  $public  Public status
 * @return  void
 */
function strMakePublic($idcat, $lang, $public) {
    global $db;
    global $cfg;
    // Flag to rebuild the category table
    global $remakeCatTable;
    global $remakeStrTable;
    $remakeCatTable = true;
    $remakeStrTable = true;

    $a_catstring = strDeeperCategoriesArray($idcat);
    foreach ($a_catstring as $value) {
        $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET public='$public', lastmodified = '".date("Y-m-d H:i:s")."'
                WHERE idcat='".Contenido_Security::toInteger($value)."' AND idlang='".Contenido_Security::toInteger($lang)."' ";
        $db->query($sql);
    }
}


function strDeeperCategoriesArray($idcat_start) {
    global $db;
    global $client;
    global $cfg;

    $sql = "SELECT * FROM ".$cfg["tab"]["cat_tree"]." AS A, ".$cfg["tab"]["cat"]." AS B WHERE A.idcat=B.idcat AND idclient='".Contenido_Security::toInteger($client)."' ORDER BY idtree";
    $db->query($sql);
    $i = 0;
    while ($db->next_record()) {
        if ($db->f("parentid") < $idcat_start) {        // ending part of tree
            $i = 0;
        }
        if ($db->f("idcat") == $idcat_start) {        // starting part of tree
            $i = 1;
        }
        if ($i == 1) {
            $catstring[] = $db->f("idcat");
        }
    }

    return $catstring;
}


/**
 * Deletes the category and its template configurations.
 *
 * Only categories having no child categories and having no articles will be deleted!
 *
 * @param   int   $idcat  Id of category to delete
 * @return  void
 */
function strDeleteCategory($idcat) {
    global $db;
    global $lang;
    global $client;
    global $lang;
    global $cfg;

    // Flag to rebuild the category table
    global $remakeCatTable;
    global $remakeStrTable;
    $remakeCatTable = true;
    $remakeStrTable = true;

    $db2 = new DB_Contenido;

    if (strNextDeeper($idcat)) {
        return "0201";        // category has subcategories
    } else {

        if (strHasArticles($idcat)) {
            return "0202";        // category has arts
        } else {
            $sql = "SELECT idtplcfg FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat='".Contenido_Security::toInteger($idcat)."' AND idlang='".Contenido_Security::toInteger($lang)."'";
            $db->query($sql);

            while ($db->next_record()) {
                ////// delete entry in 'tpl_conf'-table
                $sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg='".Contenido_Security::toInteger($db->f("idtplcfg"))."'";
                $db2->query($sql);

                $sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = '".Contenido_Security::toInteger($db->f("idtplcfg"))."'";
                $db2->query($sql);
            }

            /* Delete language dependend part */
            $sql = "DELETE FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat='".Contenido_Security::toInteger($idcat)."' AND idlang='".Contenido_Security::toInteger($lang)."'";
            $db->query($sql);

            /* Are there any additional languages? */
            $sql = "SELECT idcatlang FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat='".Contenido_Security::toInteger($idcat)."'";
            $db->query($sql);

            if ($db->num_rows() > 0) {
                // more languages found...
                // delete rights for element
                cInclude ("includes", "functions.rights.php");
                deleteRightsForElement("str", $idcat, $lang);
                deleteRightsForElement("con", $idcat, $lang);

                return;
            }

            $sql = "SELECT * FROM ".$cfg["tab"]["cat"]." WHERE idcat='".Contenido_Security::toInteger($idcat)."'";
            $db->query($sql);
            $db->next_record();
            $tmp_preid  = $db->f("preid");
            $tmp_postid = $db->f("postid");

            ////// update pre cat set new postid
            if ($tmp_preid != 0) {
                $sql = "UPDATE ".$cfg["tab"]["cat"]." SET postid='".Contenido_Security::toInteger($tmp_postid)."' WHERE idcat='".Contenido_Security::toInteger($tmp_preid)."'";
                $db->query($sql);
            }

            ////// update post cat set new preid
            if ($tmp_postid != 0) {
                $sql = "UPDATE ".$cfg["tab"]["cat"]." SET preid='".Contenido_Security::toInteger($tmp_preid)."' WHERE idcat='".Contenido_Security::toInteger($tmp_postid)."'";
                $db->query($sql);
            }

            ////// delete entry in 'cat'-table
            $sql = "DELETE FROM ".$cfg["tab"]["cat"]." WHERE idcat='".Contenido_Security::toInteger($idcat)."'";
            $db->query($sql);

            $sql = "SELECT idtplcfg FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat='".Contenido_Security::toInteger($idcat)."'";
            $db->query($sql);
            while ($db->next_record()) {
                ////// delete entry in 'tpl_conf'-table
                $sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg='".Contenido_Security::toInteger($db->f("idtplcfg"))."'";
                $db2->query($sql);

                $sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = '".Contenido_Security::toInteger($db->f("idtplcfg"))."'";
                echo $sql;
                $db2->query($sql);
            }

            ////// delete entry in 'cat_lang'-table
            $sql = "DELETE FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat='".Contenido_Security::toInteger($idcat)."'";
            $db->query($sql);

            ////// delete entry in 'cat_tree'-table
            $sql = "DELETE FROM ".$cfg["tab"]["cat_tree"]." WHERE idcat='".Contenido_Security::toInteger($idcat)."'";
            $db->query($sql);
        }

        // delete rights for element
        cInclude ("includes", "functions.rights.php");
        deleteRightsForElement("str", $idcat);
        deleteRightsForElement("con", $idcat);
    }
}


/**
 * Moves a category upwards.
 *
 * @param   int  $idcat  Id of category to move upwards
 * @return  void
 */
function strMoveUpCategory($idcat) {
    global $db;
    global $sess;
    global $cfg;

    // Flag to rebuild the category table
    global $remakeCatTable;
    global $remakeStrTable;
    $remakeCatTable = true;
    $remakeStrTable = true;

    $sql = "SELECT idcat, preid, postid FROM ".$cfg["tab"]["cat"]." WHERE idcat='".Contenido_Security::toInteger($idcat)."'";
    $db->query($sql);
    $db->next_record();
    $tmp_idcat  = $db->f("idcat");
    $tmp_preid  = $db->f("preid");
    $tmp_postid = $db->f("postid");

    if ($tmp_preid != 0) {
        $sql = "SELECT idcat, preid, postid FROM ".$cfg["tab"]["cat"]." WHERE idcat='".Contenido_Security::toInteger($tmp_preid)."'";
        $db->query($sql);
        $db->next_record();
        $tmp_idcat_pre  = $db->f("idcat");
        $tmp_preid_pre  = $db->f("preid");
        $tmp_postid_pre = $db->f("postid");

        $sql = "SELECT idcat, preid, postid FROM ".$cfg["tab"]["cat"]." WHERE idcat='".Contenido_Security::toInteger($tmp_preid_pre)."'";
        $db->query($sql);
        $db->next_record();
        $tmp_idcat_pre_pre  = $db->f("idcat");
        $tmp_preid_pre_pre  = $db->f("preid");
        $tmp_postid_pre_pre = $db->f("postid");

        $sql = "SELECT idcat, preid, postid FROM ".$cfg["tab"]["cat"]." WHERE idcat='".Contenido_Security::toInteger($tmp_postid)."'";
        $db->query($sql);
        $db->next_record();
        $tmp_idcat_post  = $db->f("idcat");
        $tmp_preid_post  = $db->f("preid");
        $tmp_postid_post = $db->f("postid");

        $sql = "UPDATE ".$cfg["tab"]["cat"]." SET  postid='".Contenido_Security::toInteger($tmp_idcat)."' WHERE idcat='".Contenido_Security::toInteger($tmp_preid_pre)."'";
        $db->query($sql);

        $sql = "UPDATE ".$cfg["tab"]["cat"]." SET  preid='".Contenido_Security::toInteger($tmp_idcat)."', postid='".Contenido_Security::toInteger($tmp_postid)."' WHERE idcat='".Contenido_Security::toInteger($tmp_preid)."'";
        $db->query($sql);

        $sql = "UPDATE ".$cfg["tab"]["cat"]." SET  preid='".Contenido_Security::toInteger($tmp_preid_pre)."', postid='".Contenido_Security::toInteger($tmp_preid)."' WHERE idcat='$tmp_idcat'";
        $db->query($sql);

        $sql = "UPDATE ".$cfg["tab"]["cat"]." SET  preid='".Contenido_Security::toInteger($tmp_idcat_pre)."' WHERE idcat='$tmp_postid'";
        $db->query($sql);
    }
}


/**
 * Moves a category downwards.
 *
 * @param   int  $idcat  Id of category to move downwards
 * @return  void
 */
function strMoveDownCategory($idcat) {
    global $db;
    global $sess;
    global $cfg;

    // Flag to rebuild the category table
    global $remakeCatTable;
    global $remakeStrTable;
    $remakeCatTable = true;
    $remakeStrTable = true;

    $arrLinks = array();

    $sql = "SELECT idcat, preid, postid FROM ".$cfg["tab"]["cat"]." WHERE idcat='".Contenido_Security::toInteger($idcat)."'";
    $db->query($sql);
    $db->next_record();
    $arrLinks['cur']['idcat'] = $db->f("idcat");
    $arrLinks['cur']['pre'] = $db->f("preid");
    $arrLinks['cur']['post'] = $db->f("postid");

    $sql = "SELECT idcat, preid, postid FROM ".$cfg["tab"]["cat"]." WHERE idcat='".Contenido_Security::toInteger($arrLinks['cur']['pre'])."'";
    $db->query($sql);
    if ($db->next_record()) {
        $arrLinks['pre']['idcat'] = $db->f("idcat");
        $arrLinks['pre']['pre'] = $db->f("preid");
        $arrLinks['pre']['post'] = $db->f("postid");
    } else {
        $arrLinks['pre']['idcat'] = 0;
        $arrLinks['pre']['pre'] = 0;
        $arrLinks['pre']['post'] = 0;
    }

    $sql = "SELECT idcat, preid, postid FROM ".$cfg["tab"]["cat"]." WHERE idcat='".Contenido_Security::toInteger($arrLinks['cur']['post'])."'";
    $db->query($sql);
    if ($db->next_record()) {
        $arrLinks['post']['idcat'] = $db->f("idcat");
        $arrLinks['post']['pre'] = $db->f("preid");
        $arrLinks['post']['post'] = $db->f("postid");
    } else {
        $arrLinks['post']['idcat'] = 0;
        $arrLinks['post']['pre'] = 0;
        $arrLinks['post']['post'] = 0;
    }

    if ($arrLinks['cur']['post'] != 0) {
        if ($arrLinks['pre']['idcat'] != 0) {
            $sql = "UPDATE ".$cfg["tab"]["cat"]." SET postid='".Contenido_Security::toInteger($arrLinks['post']['idcat'])."' WHERE idcat='".Contenido_Security::toInteger($arrLinks['pre']['idcat'])."'";
            $db->query($sql);
        } else {
            $sql = "UPDATE ".$cfg["tab"]["cat"]." SET preid='".Contenido_Security::toInteger($arrLinks['pre']['idcat'])."' WHERE idcat='".Contenido_Security::toInteger($arrLinks['post']['idcat'])."'";
            $db->query($sql);
        }

        $sql = "UPDATE ".$cfg["tab"]["cat"]." SET preid='".Contenido_Security::toInteger($arrLinks['cur']['post'])."', postid='".Contenido_Security::toInteger($arrLinks['post']['post'])."'
                WHERE idcat='".Contenido_Security::toInteger($arrLinks['cur']['idcat'])."'";
        $db->query($sql);

        $sql = "UPDATE ".$cfg["tab"]["cat"]." SET preid='".Contenido_Security::toInteger($arrLinks['pre']['idcat'])."', postid='".Contenido_Security::toInteger($arrLinks['cur']['idcat'])."'
                WHERE idcat='".Contenido_Security::toInteger($arrLinks['post']['idcat'])."'";
        $db->query($sql);
    }

    if ($arrLinks['post']['post'] != 0) {
        $sql = "UPDATE ".$cfg["tab"]["cat"]." SET preid='".Contenido_Security::toInteger($arrLinks['cur']['idcat'])."' WHERE idcat='".Contenido_Security::toInteger($arrLinks['post']['post'])."'";
        $db->query($sql);
    }
}


/**
 * Moves a subtree to another destination.
 *
 * @param   int  $idcat  Id of category
 * @param   int  $parentid_new  Id of destination parent category
 * @return  void
 */
function strMoveSubtree($idcat, $parentid_new) {
    global $db, $cfg, $remakeCatTable, $remakeStrTable, $movesubtreeidcat, $sess;
    $remakeCatTable = true;
    $remakeStrTable = true;

    $idcat 			= Contenido_Security::toInteger( $idcat );
    $iNewParentId 	= Contenido_Security::toInteger( $parentid_new );

    // Check if iNewParentId is 0 and the unescaped value is not null
    if ( $iNewParentId == 0 && !is_null( $parentid_new ) ) {
        $movesubtreeidcat = 0;
    } else if ( $iNewParentId != 0 ) {
        $sql = "SELECT idcat, preid, postid FROM ".$cfg["tab"]["cat"]." WHERE idcat='" . $idcat . "'";
        $db->query($sql);
        $db->next_record();
        $tmp_idcat  = $db->f("idcat");
        $tmp_preid  = $db->f("preid");
        $tmp_postid = $db->f("postid");

        //****************** update predecessor (pre)**********************
        if ($tmp_preid != 0) {
            $sql = "UPDATE ".$cfg["tab"]["cat"]." SET postid='" . $tmp_postid . "' WHERE idcat='" . $tmp_preid . "'";
            $db->query($sql);
        }

        //****************** update follower (post)**********************
        if ($tmp_postid != 0) {
            $sql = "UPDATE ".$cfg["tab"]["cat"]." SET preid='" . $tmp_preid . "' WHERE idcat='" . $tmp_postid . "'";
            $db->query($sql);
        }

        //****************** find new pre ********************
        $sql = "SELECT idcat, preid FROM ".$cfg["tab"]["cat"]." WHERE parentid='" . $iNewParentId . "' AND postid='0'";
        $db->query($sql);
        if ($db->next_record()) {
            $tmp_new_preid = $db->f("idcat");
            $tmp_preid_2   = $db->f("preid");
            if ($tmp_new_preid != $idcat) {
                //******************** update new pre: set post **********************
                $sql = "UPDATE ".$cfg["tab"]["cat"]." SET postid='" . $idcat . "' WHERE idcat='" . $tmp_new_preid . "'";
                $db->query($sql);
            } else {
                $sql = "SELECT idcat FROM ".$cfg["tab"]["cat"]." WHERE idcat='" . $tmp_preid_2 . "'";
                $db->query($sql);
                if ($db->next_record()) {
                    $tmp_new_preid = $db->f("idcat");
                    //******************** update new pre: set post **********************
                    $sql = "UPDATE ".$cfg["tab"]["cat"]." SET postid='" . $idcat . "' WHERE idcat='" . $tmp_new_preid . "'";
                    $db->query($sql);
                } else {
                    $tmp_new_preid = 0;
                }
            }
        } else {
            $tmp_new_preid = 0;
        }

        //*************** update idcat ********************
        $sql = "UPDATE ".$cfg["tab"]["cat"]." SET parentid='" . $iNewParentId . "', preid='" . $tmp_new_preid . "', postid='0' WHERE idcat='" . $idcat . "'";
        $db->query($sql);

        $movesubtreeidcat = 0;
    } else {
        // We recoded this function to prevent crashing the cat tree
        // when a user copies a tree and forget to set the target category

        // Copy transaction now is only performed by setting the target
        $movesubtreeidcat = $idcat;
    }

    $sess->register('movesubtreeidcat');
    $sess->freeze();
}


/**
 * Checks if category is movable.
 *
 * @param   int  $idcat   Id of category to move
 * @param   int  $source  Id of source category
 * @return  bool
 */
function strMoveCatTargetallowed($idcat, $source) {
    if ($idcat == $source) {
        return 0;
    } else {
        return 1;
    }
}


/**
 * Synchronizes a category from one language to another language.
 *
 * @param   int   $idcatParam  Id of category to synchronize
 * @param   int   $sourcelang  Id of source language
 * @param   int   $targetlang  Id of target language
 * @param   bool  $bMultiple   Flag to synchronize child languages
 * @return  void
 */
function strSyncCategory($idcatParam, $sourcelang, $targetlang, $bMultiple = false) {
    global $cfg;

    $tmpdb = new DB_Contenido;
    $bMultiple = (bool) $bMultiple;

    $aCatArray = array();
    if ($bMultiple == true) {
        $aCatArray = strDeeperCategoriesArray($idcatParam);
    } else {
        array_push($aCatArray, $idcatParam);
    }

    foreach ($aCatArray as $idcat) {
        /* Check if category already exists */
        $sql = "SELECT  idcat, idlang, idtplcfg, name,
                visible, public, status, author,
                created, lastmodified
            FROM
                ".$cfg["tab"]["cat_lang"]."
            WHERE
                idcat = '".Contenido_Security::toInteger($idcat)."' AND idlang = '".Contenido_Security::toInteger($targetlang)."'";

        $tmpdb->query($sql);

        if ($tmpdb->next_record()) {
            return false;
        }

        $sql = "SELECT  idcat, idlang, idtplcfg, name,
                visible, public, status, author,
                created, lastmodified, urlname
            FROM
                ".$cfg["tab"]["cat_lang"]."
            WHERE
                idcat = '".Contenido_Security::toInteger($idcat)."' AND idlang = '".Contenido_Security::toInteger($sourcelang)."'";

        $tmpdb->query($sql);

        if ($tmpdb->next_record()) {
            if ($tmpdb->f("idtplcfg") != 0) {
                /* Copy the template configuration */
                $newidtplcfg = tplcfgDuplicate($tmpdb->f("idtplcfg"));
            } else {
                $newidtplcfg = 0;
            }
            $newidcatlang = $tmpdb->nextid($cfg["tab"]["cat_lang"]);

            $idcat = $tmpdb->f("idcat");
            $idlang = $targetlang;
            $idtplcfg = $newidtplcfg;
            $name = $tmpdb->f("name");
            $visible = 0;
            $public = $tmpdb->f("public");
            $urlname = $tmpdb->f("urlname");
            $status = $tmpdb->f("status");
            $author = $tmpdb->f("author");
            $created = $tmpdb->f("created");
            $lastmodified = $tmpdb->f("lastmodified");

            $sql = "INSERT INTO
                ".$cfg["tab"]["cat_lang"]."
                (idcatlang, idcat, idlang, idtplcfg, name,
                 visible, public, status, author, created,
                 lastmodified, urlname)
                VALUES
                ('".Contenido_Security::toInteger($newidcatlang)."',
                '".Contenido_Security::toInteger($idcat)."',
                '".Contenido_Security::toInteger($idlang)."',
                '".Contenido_Security::toInteger($idtplcfg)."',
                '".Contenido_Security::escapeDB($name, $tmpdb)."',
                '".Contenido_Security::toInteger($visible)."',
                '".Contenido_Security::toInteger($public)."',
                '".Contenido_Security::toInteger($status)."',
                '".Contenido_Security::escapeDB($author, $tmpdb)."',
                '".Contenido_Security::escapeDB($created, $tmpdb)."',
                '".Contenido_Security::escapeDB($lastmodified, $tmpdb)."',
                '".Contenido_Security::escapeDB($urlname, $tmpdb)."')";
            $tmpdb->query($sql);

            // execute CEC hook
            $param = $tmpdb->Record;
            $param['idlang']   = $idlang;
            $param['idtplcfg'] = $idtplcfg;
            $param['visible']  = $visible;
            CEC_Hook::execute('Contenido.Category.strSyncCategory_Loop', $param);

            // set correct rights for element
            cInclude ("includes", "functions.rights.php");
            createRightsForElement("str", $idcat, $targetlang);
            createRightsForElement("con", $idcat, $targetlang);
        }
    }
}


/**
 * Checks if category has a start article
 *
 * @param   int   $idcat   Id of category
 * @param   int   $idlang  The language id
 * @return  bool
 */
function strHasStartArticle($idcat, $idlang) {
    global $cfg, $db_str;

    if ($cfg["is_start_compatible"] == false) {
        $sql = "SELECT startidartlang FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat = '".Contenido_Security::toInteger($idcat)."' AND idlang='".Contenido_Security::toInteger($idlang)."' AND startidartlang != 0";
    } else {
        $sql = "SELECT is_start FROM ".$cfg["tab"]["cat_art"]." WHERE idcat = '".Contenido_Security::toInteger($idcat)."' AND is_start = 1";
    }
    $db_str->query($sql);
    if ($db_str->next_record()) {
        return true;
    }
    return false;
}


/**
 * Copies the category and it's existing articles into another category.
 *
 * @param   int   $idcat          Id of category to copy
 * @param   int   $destidcat      Id of destination category
 * @param   bool  $remakeTree     Flag to rebuild category tree
 * @param   bool  $bUseCopyLabel  Flag to add copy label to the new categories
 * @return  void
 */
function strCopyCategory($idcat, $destidcat, $remakeTree = true, $bUseCopyLabel = true) {
    global $cfg, $client, $lang;

    $newidcat = strNewCategory($destidcat, "a", $remakeTree);

    /* Selectors */
    $_oldcatlang = new cApiCategoryLanguageCollection;
    $_newcatlang = new cApiCategoryLanguageCollection;

    $_oldcatlang->select("idcat = '$idcat' AND idlang = '$lang'");
    $oldcatlang = $_oldcatlang->next();

    if (!is_object($oldcatlang)) {
        return;
    }

    $_newcatlang->select("idcat = '$newidcat' AND idlang = '$lang'");
    $newcatlang = $_newcatlang->next();

    if (!is_object($newcatlang)) {
        return;
    }

    /* Worker objects */
    $newcat = new cApiCategory($newidcat);
    $oldcat = new cApiCategory($idcat);

    /* Copy properties */
    if ($bUseCopyLabel == true) {
        $newcatlang->set("name", sprintf(i18n("%s (Copy)"), $oldcatlang->get("name")));
    } else {
        $newcatlang->set("name", $oldcatlang->get("name"));
    }

    $newcatlang->set("public", $oldcatlang->get("public"));
    $newcatlang->set("visible", 0);
    $newcatlang->store();

    // execute cec hook
    CEC_Hook::execute('Contenido.Category.strCopyCategory', array(
        'oldcat'     => $oldcat,
        'newcat'     => $newcat,
        'newcatlang' => $newcatlang
    ));

    /* Copy template configuration */
    if ($oldcatlang->get("idtplcfg") != 0) {
        /* Create new template configuration */
        $newcatlang->assignTemplate($oldcatlang->getTemplate());

        /* Copy the container configuration */
        $c_cconf = new cApiContainerConfigurationCollection;
        $m_cconf = new cApiContainerConfigurationCollection;
        $c_cconf->select("idtplcfg = '".$oldcatlang->get("idtplcfg")."'");

        while ($i_cconf = $c_cconf->next()) {
            $m_cconf->create($newcatlang->get("idtplcfg"), $i_cconf->get("number"), $i_cconf->get("container"));
        }
    }

    $db = new DB_Contenido;
    $db2 = new DB_Contenido;

    /* Copy all articles */
    $sql = "SELECT A.idart, B.idartlang FROM ".$cfg["tab"]["cat_art"]." AS A, ".$cfg["tab"]["art_lang"]." AS B WHERE A.idcat = '".Contenido_Security::toInteger($idcat)."' AND B.idart = A.idart AND B.idlang = '".Contenido_Security::toInteger($lang)."'";
    $db->query($sql);

    while ($db->next_record()) {
        $newidart = conCopyArticle($db->f("idart"), $newidcat, "", false);
        if ($db->f("idartlang") == $oldcatlang->get("startidartlang")) {
            $sql = "SELECT idcatart FROM ".$cfg["tab"]["cat_art"]." WHERE idcat = '".Contenido_Security::toInteger($newidcat)."' AND idart = '".Contenido_Security::toInteger($newidart)."'";
            $db2->query($sql);
            if ($db2->next_record()) {
                conMakeStart($db2->f("idcatart"), 1);
            }
        }
    }

    return ($newidcat);
}


/**
 * Copies the categorytree (category and its childs) to an another category.
 *
 * @param   int   $idcat          Id of category to copy
 * @param   int   $destcat        Id of destination category
 * @param   bool  $remakeTree     Flag to rebuild category tree
 * @param   bool  $bUseCopyLabel  Flag to add copy label to the new categories
 * @return  void
 */
function strCopyTree($idcat, $destcat, $remakeTree = true, $bUseCopyLabel = true) {
    global $cfg;

    $newidcat = strCopyCategory($idcat, $destcat, false, $bUseCopyLabel);

    $db = new DB_Contenido;
    $db->query("SELECT idcat FROM ".$cfg["tab"]["cat"]." WHERE parentid = '".Contenido_Security::toInteger($idcat)."'");
    while ($db->next_record()) {
        strCopyTree($db->f("idcat"), $newidcat, false, false);
    }

    if ($remakeTree == true) {
        strRemakeTreeTable();
    }
}

?>