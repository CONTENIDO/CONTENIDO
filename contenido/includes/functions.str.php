<?php
/******************************************************************************
Description : Defines the 'str' related functions
Copyright   : four for business AG
Author      : Olaf Niemann
Urls        : www.contenido.de
Create date : 2002-03-02

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


if (class_exists("DB_Contenido"))
{
	$db_str = new DB_Contenido;
}

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

		if (trim($catname) == "")
	    {
	        return;
	    }
        
        $catalias = trim($catalias);
        if ($catalias == "") {
            $catalias = trim($catname);
        }
	
	    $tmp_newid = $db->nextid($cfg["tab"]["cat"]);
	
	    if ($tmp_newid == 0)
	    {
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
            $bPublic = 0;
        }
        

        $sql = "SELECT idcat FROM ".$cfg["tab"]["cat"]." WHERE parentid='0' AND postid='0' AND idclient='$client'";
        $db->query($sql);
        $db->next_record();
        $tmp_id = $db->f("idcat");

        $a_languages[] = $lang;
        
        if (is_array($a_languages)) {

                if (!$tmp_id) {
                        //********** Entry in 'cat'-table ************
                        $sql = "INSERT INTO ".$cfg["tab"]["cat"]." (idcat, preid, postid, idclient, author, created, lastmodified) VALUES('$tmp_newid', '0', '0', '$client','".$auth->auth['uname']."','".date("Y-m-d H:i:s")."','".date("Y-m-d H:i:s")."')";
                        $db->query($sql);

                        //********* enter name of cat in 'cat_lang'-table ******
                        foreach ($a_languages as $tmp_lang) {
                                 if ($tmp_lang == $lang) {
                                         $sql = "INSERT INTO ".$cfg["tab"]["cat_lang"]." (idcatlang, idcat, idlang, name, visible, public, idtplcfg, urlname, author, created, lastmodified) VALUES('".$db->nextid($cfg["tab"]["cat_lang"])."','$tmp_newid','$tmp_lang','".htmlspecialchars($catname, ENT_QUOTES)."','$bVisible','$bPublic','0', '".htmlspecialchars(capiStrCleanURLCharacters($catalias), ENT_QUOTES)."','".$auth->auth['uname']."','".date("Y-m-d H:i:s")."','".date("Y-m-d H:i:s")."')";
                                        $db->query($sql);
                                } else {
                                        $sql = "INSERT INTO ".$cfg["tab"]["cat_lang"]." (idcatlang, idcat, idlang, name, visible, public, idtplcfg, urlname, author, created, lastmodified) VALUES('".$db->nextid($cfg["tab"]["cat_lang"])."','$tmp_newid','$tmp_lang','".htmlspecialchars($catname, ENT_QUOTES)."','$bVisible','$bPublic','0', '".htmlspecialchars(capiStrCleanURLCharacters($catalias), ENT_QUOTES)."','".$auth->auth['uname']."','".date("Y-m-d H:i:s")."','".date("Y-m-d H:i:s")."')";
                                        $db->query($sql);
                                }
                        }
                } else {
                        //********** Entry in 'cat'-table ************
                        $sql = "UPDATE ".$cfg["tab"]["cat"]." SET postid='$tmp_newid' WHERE idcat='$tmp_id'";
                        $db->query($sql);

                        //********** Entry in 'cat'-table ************
                        $sql = "INSERT INTO ".$cfg["tab"]["cat"]." (idcat, preid, postid, idclient, author, created, lastmodified) VALUES('$tmp_newid', '$tmp_id', '0', '$client','".$auth->auth['uname']."','".date("Y-m-d H:i:s")."','".date("Y-m-d H:i:s")."')";
                        $db->query($sql);

                        //********* enter name of cat in 'cat_lang'-table ******
                        foreach ($a_languages as $tmp_lang) {
                                 if ($tmp_lang == $lang) {
                                        $sql = "INSERT INTO ".$cfg["tab"]["cat_lang"]." (idcatlang, idcat, idlang, name, visible, public, idtplcfg, urlname, author, created, lastmodified) VALUES('".$db->nextid($cfg["tab"]["cat_lang"])."','$tmp_newid','$tmp_lang','".htmlspecialchars($catname, ENT_QUOTES)."','$bVisible','$bPublic','0', '".htmlspecialchars(capiStrCleanURLCharacters($catalias), ENT_QUOTES)."','".$auth->auth['uname']."','".date("Y-m-d H:i:s")."','".date("Y-m-d H:i:s")."')";
                                        $db->query($sql);
                                } else {
                                        $sql = "INSERT INTO ".$cfg["tab"]["cat_lang"]." (idcatlang, idcat, idlang, name, visible, public, idtplcfg, urlname, author, created, lastmodified) VALUES('".$db->nextid($cfg["tab"]["cat_lang"])."','$tmp_newid','$tmp_lang','".htmlspecialchars($catname, ENT_QUOTES)."','$bVisible','$bPublic','0', '".htmlspecialchars(capiStrCleanURLCharacters($catalias), ENT_QUOTES)."','".$auth->auth['uname']."','".date("Y-m-d H:i:s")."','".date("Y-m-d H:i:s")."')";
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
        $templateCollection = new cApiTemplateCollection("defaulttemplate = '1' AND idclient = '$client'");
        
        if ($template = $templateCollection->next())
        {
        	$idtpl = $template->get("idtpl");
            if ($iIdtplcfg > 0) {
                $idtpl = $iIdtplcfg;
            }
        		
            /* Assign template, if default template exists */
            
            
        } else {
          //2008-06-25 timo.trautmann also set default template if it is selcted by user and there is no default template
          if ($iIdtplcfg > 0) {
	          $idtpl = $iIdtplcfg;
	          
	          $catCollection = new cApiCategoryLanguageCollection("idcat = '$tmp_newid'");
	            
	          while ($cat = $catCollection->next())
	          {
	            	$cat->assignTemplate($idtpl);
	          }  
          }      	
        }

        
        return ($tmp_newid);
}

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

		if (trim($catname) == "")
	    {
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
            $bPublic = 0;
        }
	
	    $tmp_newid = $db->nextid($cfg["tab"]["cat"]);
	
	    if ($tmp_newid == 0)
	    {
	        return;
	    }


		$remakeCatTable = true;
		$remakeStrTable = true;

        $sql = "SELECT idcat FROM ".$cfg["tab"]["cat"]." WHERE parentid='$tmp_parentid' AND postid=0";
        $db->query($sql);
        $db->next_record();
        $tmp_id = $db->f("idcat");

        if (!$tmp_id) {
                //********** Entry in 'cat'-table ************
                $sql = "INSERT INTO ".$cfg["tab"]["cat"]." (idcat, parentid, preid, postid, idclient, author, created, lastmodified) VALUES('$tmp_newid', '$tmp_parentid', '0', '0', '$client','".$auth->auth['uname']."','".date("Y-m-d H:i:s")."','".date("Y-m-d H:i:s")."')";
                $db->query($sql);


                //********* enter name of cat in 'cat_lang'-table ******
                $a_languages[] = $lang;
                
                foreach ($a_languages as $tmp_lang) {
                         if ($tmp_lang == $lang) {
                                 $sql = "INSERT INTO ".$cfg["tab"]["cat_lang"]." (idcatlang, idcat, idlang, name, visible, public, idtplcfg, urlname, author, created, lastmodified) VALUES('".$db->nextid($cfg["tab"]["cat_lang"])."','$tmp_newid','$tmp_lang','".htmlspecialchars($catname, ENT_QUOTES)."','$bVisible','$bPublic','0', '".htmlspecialchars(capiStrCleanURLCharacters($catalias), ENT_QUOTES)."','".$auth->auth['uname']."','".date("Y-m-d H:i:s")."','".date("Y-m-d H:i:s")."')";
                                $db->query($sql);
                        } else {
                                $sql = "INSERT INTO ".$cfg["tab"]["cat_lang"]." (idcatlang, idcat, idlang, name, visible, public, idtplcfg, urlname, author, created, lastmodified) VALUES('".$db->nextid($cfg["tab"]["cat_lang"])."','$tmp_newid','$tmp_lang','".htmlspecialchars($catname, ENT_QUOTES)."','$bVisible','$bPublic','0', '".htmlspecialchars(capiStrCleanURLCharacters($catalias), ENT_QUOTES)."','".$auth->auth['uname']."','".date("Y-m-d H:i:s")."','".date("Y-m-d H:i:s")."')";
                                $db->query($sql);
                        }
                }
        } else {
                //********** Entry in 'cat'-table ************
                $sql = "UPDATE ".$cfg["tab"]["cat"]." SET postid='$tmp_newid', lastmodified = '".date("Y-m-d H:i:s")."' WHERE idcat='$tmp_id'";
                $db->query($sql);

                //********** Entry in 'cat'-table ************
                $sql = "INSERT INTO ".$cfg["tab"]["cat"]." (idcat, parentid, preid, postid, idclient, author, created, lastmodified) VALUES('$tmp_newid', '$tmp_parentid', '$tmp_id', '0', '$client','".$auth->auth['uname']."','".date("Y-m-d H:i:s")."','".date("Y-m-d H:i:s")."')";
                $db->query($sql);

                //********* enter name of cat in 'cat_lang'-table ******
                $a_languages[] = $lang;
                foreach ($a_languages as $tmp_lang) {
                         if ($tmp_lang == $lang) {
                                $sql = "INSERT INTO ".$cfg["tab"]["cat_lang"]." (idcatlang, idcat, idlang, name, visible, public, idtplcfg, urlname, author, created, lastmodified) VALUES('".$db->nextid($cfg["tab"]["cat_lang"])."','$tmp_newid','$tmp_lang','".htmlspecialchars($catname, ENT_QUOTES)."','$bVisible','$bPublic','0', '".htmlspecialchars(capiStrCleanURLCharacters($catalias), ENT_QUOTES)."','".$auth->auth['uname']."','".date("Y-m-d H:i:s")."','".date("Y-m-d H:i:s")."')";
                                $db->query($sql);
                        } else {
                                $sql = "INSERT INTO ".$cfg["tab"]["cat_lang"]." (idcatlang, idcat, idlang, name, visible, public, idtplcfg, urlname, author, created, lastmodified) VALUES('".$db->nextid($cfg["tab"]["cat_lang"])."','$tmp_newid','$tmp_lang','".htmlspecialchars($catname, ENT_QUOTES)."','$bVisible','$bPublic','0', '".htmlspecialchars(capiStrCleanURLCharacters($catalias), ENT_QUOTES)."','".$auth->auth['uname']."','".date("Y-m-d H:i:s")."','".date("Y-m-d H:i:s")."')";
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

        if ($remakeTree == true)
        {
        	strRemakeTreeTable();
        }
        
        /* Search for default template */
        $templateCollection = new cApiTemplateCollection("defaulttemplate = '1' AND idclient = '$client'");

        if ($template = $templateCollection->next())
        {
        	$idtpl = $template->get("idtpl");
            if ($iIdtplcfg > 0) {
                $idtpl = $iIdtplcfg;
            }
        		
            /* Assign template, if default template exists */
            
            $catCollection = new cApiCategoryLanguageCollection("idcat = '$tmp_newid'");

            while ($cat = $catCollection->next())
            {
            	$cat->assignTemplate($idtpl);
            }        	
        } else {
          //2008-06-25 timo.trautmann also set default template if it is selcted by user and there is no default template
          if ($iIdtplcfg > 0) {
	          $idtpl = $iIdtplcfg;
	          
	          $catCollection = new cApiCategoryLanguageCollection("idcat = '$tmp_newid'");
	            
	          while ($cat = $catCollection->next())
	          {
	            	$cat->assignTemplate($idtpl);
	          }  
          }      	
        }      

        return($tmp_newid);

}

function strOrderedPostTreeList ($idcat, $poststring) {
        global $db;
        global $client;
        global $lang;
        global $cfg;

        $sql = "SELECT idcat FROM ".$cfg["tab"]["cat"]." WHERE parentid=0 AND preid='$idcat' AND idcat!=0";

        $db->query($sql);
        if ( $db->next_record() ) {
                $tmp_idcat = $db->f("idcat");
                $poststring = $poststring.",".$tmp_idcat;
                $poststring = strOrderedPostTreeList($tmp_idcat, $poststring);
        }

        return $poststring;

} 

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

        $sql = "DELETE FROM ".$cfg["tab"]["cat_tree"];                    // empty 'cat_tree'-table
        $db->query($sql);

        $sql = "DELETE FROM ".$cfg["tab"]["cat"]." WHERE idcat='0'";
        $db->query($sql);

        $sql = "DELETE FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat='0'";
        $db->query($sql);

        $sql = "SELECT idcat FROM ".$cfg["tab"]["cat"]." WHERE parentid='0' AND preid='0' AND idcat!='0'";

        $db->query($sql);
        while($db->next_record())
        {
                $idcats[] = $db->f("idcat");
        }

        if (is_array($idcats)) {
                foreach ($idcats as $value) {
                        $poststring = $poststring.$value.strOrderedPostTreeList ($value, "").",";
                }
        }
        $poststring=ereg_replace(",$","", $poststring);
        $a_maincats = explode(",", $poststring);
        if (is_array($a_maincats)){
                foreach ($a_maincats as $tmp_idcat) {
                        strRemakeTreeTableFindNext($tmp_idcat,0);
                }
        }
}

function strNextDeeper($tmp_idcat, $ignore_lang = false) {
        global $cfg, $db_str, $lang;

        $sql = "SELECT idcat FROM ".$cfg["tab"]["cat"]." WHERE parentid='$tmp_idcat' AND preid='0'";
        $db_str->query($sql);
        if ($db_str->next_record()) {     
        		$midcat = $db_str->f("idcat");
        		if ($ignore_lang == true)
        		{
        			return $midcat;
        		}

				//******deeper element exists
				/* Check for language dependent part */
				
				$sql = "SELECT idcatlang FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat='$midcat' AND idlang='$lang'";
				$db_str->query($sql);
				
				if ($db_str->next_record())
				{
                	return $midcat;
				} else {
					return 0;
				}
        } else {                                        //******deeper element does not exist
                return 0;
        }
}

function strHasArticles($tmp_idcat) {
        global $cfg, $db_str;
        global $lang;

        $sql = "SELECT b.idartlang AS idartlang FROM 
					".$cfg["tab"]["cat_art"]." AS a,
					".$cfg["tab"]["art_lang"]." AS b
					WHERE a.idcat='$tmp_idcat' AND
					a.idart = b.idart AND b.idlang = '$lang'";

        $db_str->query($sql);
        
        if ($db_str->next_record()) {                         //******post element exists
                return true;
        } else {                                        //******post element does not exist
                return false;
        }
}
function strNextPost($tmp_idcat) {
        global $db;
        global $cfg;

        $sql = "SELECT idcat FROM ".$cfg["tab"]["cat"]." WHERE preid='$tmp_idcat'";
        $db->query($sql);
        if ($db->next_record()) {                         //******post element exists
                $tmp_idcat = $db->f("idcat");
                $sql = "SELECT parentid FROM ".$cfg["tab"]["cat"]." WHERE idcat='$tmp_idcat'";
                $db->query($sql);
                if ($db->next_record()) {                         //******parent from post must not be 0
                        $tmp_parentid = $db->f("parentid");
                        if ($tmp_parentid != 0) {
                                return $tmp_idcat;
                        } else {
                                return 0;
                        }
                } else {
                        return 99;
                }
        } else {                                        //******post element does not exist
                return 0;
        }
}

function strNextBackwards($tmp_idcat) {
        global $db;
        global $cfg;

        $sql = "SELECT parentid FROM ".$cfg["tab"]["cat"]." WHERE idcat='$tmp_idcat'";
        $db->query($sql);
        if ($db->next_record()) {                         //******parent exists
                $tmp_idcat = $db->f("parentid");
                if ($tmp_idcat != 0) {
                        $sql = "SELECT idcat FROM ".$cfg["tab"]["cat"]." WHERE preid='$tmp_idcat'";
                        $db->query($sql);
                        if ($db->next_record()) {                         //******parent has post
                                $tmp_idcat = $db->f("idcat");
                                $sql = "SELECT parentid FROM ".$cfg["tab"]["cat"]." WHERE idcat='$tmp_idcat'";
                                $db->query($sql);
                                if ($db->next_record()) {                         //******parent from post must not be 0
                                        $tmp_parentid = $db->f("parentid");
                                        if ($tmp_parentid != 0) {
                                                return $tmp_idcat;
                                        } else {
                                                return 0;
                                        }
                                } else {
                                        return 99;
                                }
                        } else {                                        //******parent has no post
                                return strNextBackwards($tmp_idcat);
                        }
                } else {
                        return 0;
                }
        } else {                                        //******no parent
                return 0;
        }

}

function strRemakeTreeTableFindNext($tmp_idcat,$tmp_level) {
        global $db;
        global $cfg;

        //************* Insert Element in 'cat_tree'-table **************
        $sql = "INSERT INTO ".$cfg["tab"]["cat_tree"]." (idtree, idcat, level) VALUES ('".$db->nextid($cfg["tab"]["cat_tree"])."', '$tmp_idcat', '$tmp_level')";
        $db->query($sql);

        //************* dig deeper, if possible ******
        $tmp = strNextDeeper($tmp_idcat, true);
        if ($tmp != 0) {
                $tmp_idcat = $tmp;
                $tmp_level++;

                strRemakeTreeTableFindNext($tmp_idcat,$tmp_level);

        } else {
                $tmp = strNextPost($tmp_idcat);
        //************ if not get post element ********
                if ($tmp != 0) {
                        $tmp_idcat = $tmp;

                        strRemakeTreeTableFindNext($tmp_idcat,$tmp_level);

        //************ if that's not possible either go backwards *********
                } else {
                        $tmp = strNextBackwards($tmp_idcat);
                        if ($tmp != 0) {
                                $tmp_idcat = $tmp;
                                $sql = "SELECT A.level FROM ".$cfg["tab"]["cat_tree"]." AS A, ".$cfg["tab"]["cat"]." AS B WHERE A.idcat=B.idcat AND B.postid='$tmp_idcat'";
                                $db->query($sql);
                                if ($db->next_record()) {
                                        $tmp_level = $db->f("level");
                                } else {
                                        $level = 0;
                                }
                                if ($tmp_level != 0) {
                                        strRemakeTreeTableFindNext($tmp_idcat,$tmp_level);
                                }
                        }
                }

        }
}

function strShowTreeTable() {
        global $db;
        global $sess;
        global $client;
        global $lang;
        global $idcat;
        global $cfg;
        global $lngStr;

        echo "<br><table cellpadding=$cellpadding cellspacing=$cellspacing border=$border >";
        $sql = "SELECT * FROM ".$cfg["tab"]["cat_tree"]." AS A, ".$cfg["tab"]["cat"]." AS B, ".$cfg["tab"]["cat_lang"]." AS C WHERE A.idcat=B.idcat AND B.idcat=C.idcat AND C.idlang='$lang' AND B.idclient='$client' ORDER BY A.idtree";
        $db->query($sql);
        while($db->next_record())
        {
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

function strRenameCategory ($idcat, $lang, $newcategoryname, $newcategoryalias) {
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
                    $sql = "SELECT urlname, name FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat='$idcat' AND idlang='$lang' ";
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
                
                $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET urlname='".$sUrlname."', name='".$sName."', lastmodified = '".date("Y-m-d H:i:s")."' WHERE idcat='$idcat' AND idlang='$lang' ";
                $db->query($sql);                
        } else {

                //echo ("Fehlermeldung aufrufen: strrenamecategory");
//                Header("Location: str_main.php?error=2");    // ohne Namen wird nicht umbenannt.

        }

}


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
                $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET visible='$visible',lastmodified ='".date("Y-m-d H:i:s")."' WHERE idcat='$value' AND idlang='$lang' ";
                $db->query($sql);
        }

		if ($cfg["pathresolve_heapcache"] == true && $visible = 0)
		{
			$pathresolve_tablename = $cfg["sql"]["sqlprefix"]."_pathresolve_cache";
			$sql = "DELETE FROM %s WHERE idlang = '%s' AND idcat = '%s'";
			$db->query(sprintf($sql, $pathresolve_tablename, $lang, $idcat));
		}        
}

function strMakePublic ($idcat, $lang, $public) {
        global $db;
        global $cfg;
		// Flag to rebuild the category table
		global $remakeCatTable;
		global $remakeStrTable;
		$remakeCatTable = true;
		$remakeStrTable = true;


                $a_catstring = strDeeperCategoriesArray($idcat);
        foreach ($a_catstring as $value) {
                $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET public='$public', lastmodified = '".date("Y-m-d H:i:s")."' WHERE idcat='$value' AND idlang='$lang' ";
                $db->query($sql);
        }
}

function strDeeperCategoriesArray($idcat_start) {
        global $db;
        global $client;
        global $cfg;

        $sql = "SELECT * FROM ".$cfg["tab"]["cat_tree"]." AS A, ".$cfg["tab"]["cat"]." AS B WHERE A.idcat=B.idcat AND idclient='$client' ORDER BY idtree";
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


function strDeleteCategory ($idcat) {
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
            $sql = "SELECT idtplcfg FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat='$idcat' AND idlang='$lang'";
            $db->query($sql);
            
            while ($db->next_record()) {
                ////// delete entry in 'tpl_conf'-table
                $sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg='".$db->f("idtplcfg")."'";
                $db2->query($sql);

                $sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = '".$db->f("idtplcfg")."'";
                $db2->query($sql);
            }
            
            /* Delete language dependend part */
            $sql = "DELETE FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat='$idcat' AND idlang='$lang'";
            $db->query($sql);

            /* Are there any additional languages? */
            $sql = "SELECT idcatlang FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat='$idcat'";
            $db->query($sql);

            if ($db->num_rows() > 0)
            {
              // more languages found...
              // delete rights for element
              cInclude ("includes", "functions.rights.php");
              deleteRightsForElement("str", $idcat, $lang);
              deleteRightsForElement("con", $idcat, $lang);

                return;
            }

            $sql = "SELECT * FROM ".$cfg["tab"]["cat"]." WHERE idcat='$idcat'";
            $db->query($sql);
            $db->next_record();
            $tmp_preid  = $db->f("preid");
            $tmp_postid = $db->f("postid");

            ////// update pre cat set new postid
            if ($tmp_preid != 0) {
                $sql = "UPDATE ".$cfg["tab"]["cat"]." SET postid='$tmp_postid' WHERE idcat='$tmp_preid'";
                $db->query($sql);
            }

            ////// update post cat set new preid
            if ($tmp_postid != 0) {
                $sql = "UPDATE ".$cfg["tab"]["cat"]." SET preid='$tmp_preid' WHERE idcat='$tmp_postid'";
                $db->query($sql);
            }

            ////// delete entry in 'cat'-table
            $sql = "DELETE FROM ".$cfg["tab"]["cat"]." WHERE idcat='$idcat' ";
            $db->query($sql);

            $sql = "SELECT idtplcfg FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat='$idcat'";
            $db->query($sql);
            while ($db->next_record()) {
                ////// delete entry in 'tpl_conf'-table
                $sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg='".$db->f("idtplcfg")."'";
                $db2->query($sql);

                $sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = '".$db->f("idtplcfg")."'";
                echo $sql;
                $db2->query($sql);
            }

            ////// delete entry in 'cat_lang'-table
            $sql = "DELETE FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat='$idcat'";
            $db->query($sql);

            ////// delete entry in 'cat_tree'-table
            $sql = "DELETE FROM ".$cfg["tab"]["cat_tree"]." WHERE idcat='$idcat'";
            $db->query($sql);

        }

        // delete rights for element
        cInclude ("includes", "functions.rights.php");
        deleteRightsForElement("str", $idcat);
        deleteRightsForElement("con", $idcat);

    }
}

function strMoveUpCategory ($idcat) {
        global $db;
        global $sess;
        global $cfg;

		// Flag to rebuild the category table
		global $remakeCatTable;
		global $remakeStrTable;
		$remakeCatTable = true;
		$remakeStrTable = true;


        $sql = "SELECT idcat, preid, postid FROM ".$cfg["tab"]["cat"]." WHERE idcat='$idcat'";
        $db->query($sql);
        $db->next_record();
        $tmp_idcat  = $db->f("idcat");
        $tmp_preid  = $db->f("preid");
        $tmp_postid = $db->f("postid");

        if ($tmp_preid != 0) {
                $sql = "SELECT idcat, preid, postid FROM ".$cfg["tab"]["cat"]." WHERE idcat='$tmp_preid'";
                $db->query($sql);
                $db->next_record();
                $tmp_idcat_pre  = $db->f("idcat");
                $tmp_preid_pre  = $db->f("preid");
                $tmp_postid_pre = $db->f("postid");

                $sql = "SELECT idcat, preid, postid FROM ".$cfg["tab"]["cat"]." WHERE idcat='$tmp_preid_pre'";
                $db->query($sql);
                $db->next_record();
                $tmp_idcat_pre_pre  = $db->f("idcat");
                $tmp_preid_pre_pre  = $db->f("preid");
                $tmp_postid_pre_pre = $db->f("postid");

                $sql = "SELECT idcat, preid, postid FROM ".$cfg["tab"]["cat"]." WHERE idcat='$tmp_postid'";
                $db->query($sql);
                $db->next_record();
                $tmp_idcat_post  = $db->f("idcat");
                $tmp_preid_post  = $db->f("preid");
                $tmp_postid_post = $db->f("postid");

                $sql = "UPDATE ".$cfg["tab"]["cat"]." SET  postid='$tmp_idcat' WHERE idcat='$tmp_preid_pre'";
                $db->query($sql);

                $sql = "UPDATE ".$cfg["tab"]["cat"]." SET  preid='$tmp_idcat', postid='$tmp_postid' WHERE idcat='$tmp_preid'";
                $db->query($sql);

                $sql = "UPDATE ".$cfg["tab"]["cat"]." SET  preid='$tmp_preid_pre', postid='$tmp_preid' WHERE idcat='$tmp_idcat'";
                $db->query($sql);

                $sql = "UPDATE ".$cfg["tab"]["cat"]." SET  preid='$tmp_idcat_pre' WHERE idcat='$tmp_postid'";
                $db->query($sql);

        }
}

function strMoveDownCategory ($idcat) {
        global $db;
        global $sess;
        global $cfg;

		// Flag to rebuild the category table
		global $remakeCatTable;
		global $remakeStrTable;
		$remakeCatTable = true;
		$remakeStrTable = true;

		$arrLinks = array();

        $sql = "SELECT idcat, preid, postid FROM ".$cfg["tab"]["cat"]." WHERE idcat='$idcat'";
        $db->query($sql);
        $db->next_record();
		$arrLinks['cur']['idcat'] = $db->f("idcat");
		$arrLinks['cur']['pre'] = $db->f("preid");
		$arrLinks['cur']['post'] = $db->f("postid");
		
        $sql = "SELECT idcat, preid, postid FROM ".$cfg["tab"]["cat"]." WHERE idcat='".$arrLinks['cur']['pre']."'";
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
		
        $sql = "SELECT idcat, preid, postid FROM ".$cfg["tab"]["cat"]." WHERE idcat='".$arrLinks['cur']['post']."'";
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
	            $sql = "UPDATE ".$cfg["tab"]["cat"]." SET  postid='".$arrLinks['post']['idcat']."' WHERE idcat='".$arrLinks['pre']['idcat']."'";
	            $db->query($sql);
			} else {
	            $sql = "UPDATE ".$cfg["tab"]["cat"]." SET  preid='".$arrLinks['pre']['idcat']."' WHERE idcat='".$arrLinks['post']['idcat']."'";
	            $db->query($sql);
			}
			
            $sql = "UPDATE ".$cfg["tab"]["cat"]." SET preid='".$arrLinks['cur']['post']."', postid='".$arrLinks['post']['post']."' WHERE idcat='".$arrLinks['cur']['idcat']."'";
            $db->query($sql);

            $sql = "UPDATE ".$cfg["tab"]["cat"]." SET  preid='".$arrLinks['pre']['idcat']."', postid='".$arrLinks['cur']['idcat']."' WHERE idcat='".$arrLinks['post']['idcat']."'";
            $db->query($sql);
		}

        if ($arrLinks['post']['post'] != 0) {
            $sql = "UPDATE ".$cfg["tab"]["cat"]." SET  preid='".$arrLinks['cur']['idcat']."' WHERE idcat='".$arrLinks['post']['post']."'";
            $db->query($sql);
        }
}

function strMoveSubtree ($idcat, $parentid_new) {
        global $db;
        global $cfg;
		// Flag to rebuild the category table
		global $remakeCatTable;
		global $remakeStrTable;
		$remakeCatTable = true;
		$remakeStrTable = true;


        $sql = "SELECT idcat, preid, postid FROM ".$cfg["tab"]["cat"]." WHERE idcat='$idcat'";
        $db->query($sql);
        $db->next_record();
        $tmp_idcat  = $db->f("idcat");
        $tmp_preid  = $db->f("preid");
        $tmp_postid = $db->f("postid");

        //****************** update predecessor (pre)**********************
        if ($tmp_preid != 0) {
                $sql = "UPDATE ".$cfg["tab"]["cat"]." SET postid='$tmp_postid' WHERE idcat='$tmp_preid'";
                $db->query($sql);
        }

        //****************** update follower (post)**********************
        if ($tmp_postid != 0) {
                $sql = "UPDATE ".$cfg["tab"]["cat"]." SET preid='$tmp_preid' WHERE idcat='$tmp_postid'";
                $db->query($sql);
        }

        //****************** find new pre ********************
        $sql = "SELECT idcat, preid FROM ".$cfg["tab"]["cat"]." WHERE parentid='$parentid_new' AND postid='0'";
        $db->query($sql);
        if ($db->next_record()) {
                $tmp_new_preid = $db->f("idcat");
                $tmp_preid_2   = $db->f("preid");
                if ($tmp_new_preid != $idcat) {
                        //******************** update new pre: set post **********************
                        $sql = "UPDATE ".$cfg["tab"]["cat"]." SET postid='$idcat' WHERE idcat='$tmp_new_preid'";
                        $db->query($sql);
                } else {
                        $sql = "SELECT idcat FROM ".$cfg["tab"]["cat"]." WHERE idcat='$tmp_preid_2'";
                        $db->query($sql);
                        if ($db->next_record()) {
                                $tmp_new_preid = $db->f("idcat");
                                //******************** update new pre: set post **********************
                                $sql = "UPDATE ".$cfg["tab"]["cat"]." SET postid='$idcat' WHERE idcat='$tmp_new_preid'";
                                $db->query($sql);
                        } else {
                                $tmp_new_preid = 0;
                        }
                }
        } else {
                $tmp_new_preid = 0;
        }

        //*************** update idcat ********************
        $sql = "UPDATE ".$cfg["tab"]["cat"]." SET parentid='$parentid_new', preid='$tmp_new_preid', postid='0' WHERE idcat='$idcat'";
        $db->query($sql);
}

function strMoveCatTargetallowed($idcat, $source) {
        global $cfg;

        $tmpdb = new DB_Contenido;
        $sql = "SELECT parentid FROM ".$cfg["tab"]["cat"]." WHERE idcat='$idcat'";
        $tmpdb->query($sql);
        $tmpdb->next_record();
        $p = $tmpdb->f("parentid");

        if ($p == $source) {
                return 0;
        } elseif ($p == 0) {
                return 1;
        } else {
                return strMoveCatTargetallowed($p, $source);
        }
}

function strSyncCategory($idcatParam, $sourcelang, $targetlang, $bMultiple = false)
{
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
                idcat = '$idcat' AND idlang = '$targetlang'";
                
        $tmpdb->query($sql);
        
        if ($tmpdb->next_record())
        {
        	return false;
        }

    	
        $sql = "SELECT  idcat, idlang, idtplcfg, name,
                visible, public, status, author,
                created, lastmodified, urlname
            FROM
                ".$cfg["tab"]["cat_lang"]."
            WHERE
                idcat = '$idcat' AND idlang = '$sourcelang'";

        $tmpdb->query($sql);

        if ($tmpdb->next_record())
        {
        if ($tmpdb->f("idtplcfg") != 0)
        {
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
            ('$newidcatlang',
            '$idcat',
            '$idlang',
            '$idtplcfg',
            '$name',
            '$visible',
            '$public',
            '$status',
            '$author',
            '$created',
            '$lastmodified',
            '$urlname')";
        $tmpdb->query($sql);

        // set correct rights for element
        cInclude ("includes", "functions.rights.php");
        createRightsForElement("str", $idcat, $targetlang);
        createRightsForElement("con", $idcat, $targetlang);

        }
    }
}

function strHasStartArticle ($idcat, $idlang)
{
	global $cfg, $db_str;
	

	if ($cfg["is_start_compatible"] == false)
	{
		$sql = "SELECT startidartlang FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat = '$idcat' AND idlang='$idlang' AND startidartlang != 0";
		$db_str->query($sql);
		
		if ($db_str->next_record())
		{
			return true;
		}
	} else {
		$sql = "SELECT is_start FROM ".$cfg["tab"]["cat_art"]." WHERE idcat = '$idcat' AND is_start = 1";
		$db_str->query($sql);
		
		if ($db_str->next_record())
		{
			return true;	
		}	
	}
	
	return false;
}

function strCopyCategory ($idcat, $destidcat, $remakeTree = true)
{
   global $cfg, $client, $lang;

   $newidcat = strNewCategory($destidcat, "a", $remakeTree);

   /* Selectors */
   $_oldcatlang = new cApiCategoryLanguageCollection;
   $_newcatlang = new cApiCategoryLanguageCollection;

   $_oldcatlang->select("idcat = '$idcat' AND idlang = '$lang'");
   $oldcatlang = $_oldcatlang->next();
   
   if (!is_object($oldcatlang))
   {
   		return;
   }
   
   $_newcatlang->select("idcat = '$newidcat' AND idlang = '$lang'");
   $newcatlang = $_newcatlang->next();
   
   if (!is_object($newcatlang))
   {
   		return;
   }   


   /* Worker objects */
   $newcat = new cApiCategory($newidcat);
   $oldcat = new cApiCategory($idcat);

   /* Copy properties */
   $newcatlang->set("name", sprintf(i18n("%s (Copy)"), $oldcatlang->get("name")));
   $newcatlang->set("public", $oldcatlang->get("public"));
   $newcatlang->set("visible", 0);
   $newcatlang->store();

   /* Copy template configuration */
   if ($oldcatlang->get("idtplcfg") != 0)
   {
      /* Create new template configuration */
      $newcatlang->assignTemplate($oldcatlang->getTemplate());

      /* Copy the container configuration */
      $c_cconf = new cApiContainerConfigurationCollection;
      $m_cconf = new cApiContainerConfigurationCollection;
      $c_cconf->select("idtplcfg = '".$oldcatlang->get("idtplcfg")."'");

      while ($i_cconf = $c_cconf->next())
      {
         $m_cconf->create($newcatlang->get("idtplcfg"), $i_cconf->get("number"), $i_cconf->get("container"));
      }
   }

   $db = new DB_Contenido;
   $db2 = new DB_Contenido;

   /* Copy all articles */
   $sql = "SELECT A.idart, B.idartlang FROM ".$cfg["tab"]["cat_art"]." AS A, ".$cfg["tab"]["art_lang"]." AS B WHERE A.idcat = '$idcat' AND B.idart = A.idart AND B.idlang = '$lang'";
   $db->query($sql);

   while ($db->next_record())
   {
      $newidart = conCopyArticle($db->f("idart"), $newidcat);
      if ($db->f("idartlang") == $oldcatlang->get("startidartlang"))
      {
         $sql = "SELECT idcatart FROM ".$cfg["tab"]["cat_art"]." WHERE idcat = '$newidcat' AND idart = '$newidart'";
         $db2->query($sql);
         if ($db2->next_record())
         {
            conMakeStart($db2->f("idcatart"), 1);
         }
      }

   }

   return ($newidcat);
}

function strCopyTree ($idcat, $destcat, $remakeTree = true)
{
	global $cfg;

	$newidcat = strCopyCategory($idcat, $destcat, false);
	
	$db = new DB_Contenido;
	$db->query("SELECT idcat FROM ".$cfg["tab"]["cat"]." WHERE parentid = '$idcat'");
	
	while ($db->next_record())
	{
		strCopyTree($db->f("idcat"), $newidcat, false);	
	}
	
	if ($remakeTree == true)
	{
		strRemakeTreeTable();
	}
}

?>
