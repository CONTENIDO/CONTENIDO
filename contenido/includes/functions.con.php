<?php

/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Defines the 'con' related functions in Contenido
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.3
 * @author     Olaf Niemann, Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created unknown
 *   modified 2008-11-24, Mario Diaz, function conFlagOnOffline: Set publish date if article goes online
 *   modified 2008-06-25, Frederic Schneider, add security fix
 *   modified 2008-06-26, Timo.Trautmann, add security fix fix setting article online was not possible
 *   modified 2008-08-29, Murat Purc, add new chain execution, and handling og new field con_cat_lang.urlname
 *   modified 2008-09-07, Murat Purc, bugfix in conCopyArtLang at chain execution
 *   modified 2008-09-12, Oliver Lohkemper, bugfix in function conChangeTemplateForCat, add conGenerateCodeForAllartsInCategory()
 *   modified 2009-04-23, Andreas Lindner, also copy alias of article when syncing article to another language
 *   modified 2009-05-05, Timo Trautmann - optional use for copy label on copy proccess
 *   modified 2009-10-07, Murat Purc, bugfix in conMoveArticles (missing apostrophe)
 *   modified 2009-12-01, Dominik Ziegler, bugfix in conFlagOnOffline (article is still offline if enddate in time management is missing)
 *   modified 2009-10-27, Murat Purc, fixed/modified CEC_Hook, see [#CON-256]
 *  
 *   $Id$:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

/* Compatibility: Include new functions.con2.php */
cInclude("includes", "functions.con2.php");


/**
 * Create a new Article
 *
 * @param mixed many
 * @author Olaf Niemann <Olaf.Niemann@4fb.de>
 * @copyright four for business AG <http://www.4fb.de>
 *
 * @return int Id of the new article
 */
function conEditFirstTime($idcat, $idcatnew, $idart, $is_start, $idtpl,
                          $idartlang, $idlang, $title, $summary, $artspec, $created,
                          $lastmodified, $author, $online, $datestart, $dateend,
                          $artsort, $keyart=0)
{

        global $db;
        global $client;
        global $lang;
        global $cfg;
        global $auth;
        global $urlname;
        global $page_title;
        //Some stuff for the redirect
        global $redirect;
        global $redirect_url;
        global $external_redirect;
        global $time_move_cat; // Used to indicate "move to cat"
        global $time_target_cat; // Used to indicate the target category
        global $time_online_move; // Used to indicate if the moved article should be online
        global $timemgmt;
        global $_POST;

		$page_title = addslashes($page_title);

		$urlname            = (trim($urlname) == '') ? trim($title) : trim($urlname);
        $urlname            = htmlspecialchars(capiStrCleanURLCharacters($urlname), ENT_QUOTES);
        $usetimemgmt		= ($timemgmt == '1') 	? '1' : '0';
		$movetocat 			= ($time_move_cat == '1') ? '1' : '0';
		$onlineaftermove 	= ($time_online_move == '1') ? '1' : '0';
		$redirect  			= ($redirect == '1') 	? '1' : '0';
		$external_redirect  = ($external_redirect == '1')    ? '1' : '0';
        $redirect_url		= ($redirect_url == 'http://' || $redirect_url == '') ? '0' : $redirect_url;
        
		if ($is_start == 1)	{
			$usetimemgmt = "0";
		}
				
        $new_idart = $db->nextid($cfg["tab"]["art"]);

        # Set self defined Keywords
        if ( $keyart != "" ) {
            $keycode[1][1] = $keyart;
            SaveKeywordsforart($keycode,$new_idart,"self",$lang);
        }

        # Table 'cat_art'
        # Check if there are articles in this category.
        # If not make it a start article
        $sql = "SELECT * FROM ".$cfg["tab"]["cat_art"]." WHERE idcat='".Contenido_Security::toInteger($idcat)."'";
        $db->query($sql);
        
        if ( $db->next_record() ) {
        	
            if ($cfg["is_start_compatible"] == true)
            {
                $sql = "INSERT INTO ".$cfg["tab"]["cat_art"]." (idcatart, idcat, idart, is_start) VALUES ('".Contenido_Security::toInteger($db->nextid($cfg["tab"]["cat_art"]))."', '".Contenido_Security::toInteger($idcat)."',
                        '".Contenido_Security::toInteger($new_idart)."', '0')";
                $db->query($sql);
            } else {
                $autostart = false;
            }
        } else {
            if ($cfg["is_start_compatible"] == true)
            {
                $sql = "INSERT INTO ".$cfg["tab"]["cat_art"]." (idcatart, idcat, idart, is_start) VALUES ('".Contenido_Security::toInteger($db->nextid($cfg["tab"]["cat_art"]))."', '".Contenido_Security::toInteger($idcat) ."',
                        '".Contenido_Security::toInteger($new_idart)."', '1')";
                $db->query($sql);
            } else {
                $autostart = false;
            }
        }

        # Table 'con_art'
        $sql = "INSERT INTO ".$cfg["tab"]["art"]." (idart, idclient) VALUES ('".Contenido_Security::toInteger($new_idart)."', '".Contenido_Security::toInteger($client)."')";
        $db->query($sql);

        # Table 'con_stat'
        $sql = "SELECT idcatart FROM ".$cfg["tab"]["cat_art"]." WHERE idcat = '".Contenido_Security::toInteger($idcat)."' AND idart = '".Contenido_Security::toInteger($new_idart)."'";
        $db->query($sql);
        $db->next_record();
        $idcatart = $db->f("idcatart");

        $a_languages[] = $lang;
        foreach ($a_languages as $tmp_lang) {
            $sql = "INSERT INTO ".$cfg["tab"]["stat"]." (idstat, idcatart, idlang, idclient, visited) VALUES ('".Contenido_Security::toInteger($db->nextid($cfg["tab"]["stat"]))."',
                    '".Contenido_Security::toInteger($idcatart)."', '".Contenido_Security::toInteger($tmp_lang)."', '".Contenido_Security::toInteger($client)."', '0')";
            $db->query($sql);
        }

        # Table 'con_art_lang'
        # One entry for every language
        foreach ($a_languages as $tmp_lang) {

            $lastmodified = ( $lang == $tmp_lang ) ? $lastmodified : 0;

			$nextidartlang = $db->nextid($cfg["tab"]["art_lang"]);
				if(($online==1)){
					$published_value = date("Y-m-d H:i:s");
					$publishedby_value = $auth->auth["uname"];
				}else{
					$published_value = '';
					$publishedby_value = '';
				}
			
                $sql = "INSERT INTO
                        ".$cfg["tab"]["art_lang"]." (
                        idartlang,
                        idart,
                        idlang,
                        title,
                        urlname,
                        pagetitle,
						summary,
                        artspec,
                        created,
                        lastmodified,
                        author,
						published,
						publishedby,
                        online,
                        redirect,
                        redirect_url,
                        external_redirect,
                        artsort,
                        timemgmt,
                        datestart,
                        dateend,
                        status,
                        time_move_cat,
                        time_target_cat,
                        time_online_move
                        ) VALUES (
                        '".Contenido_Security::toInteger($nextidartlang)."',
                        '".Contenido_Security::toInteger($new_idart)."',
                        '".Contenido_Security::toInteger($tmp_lang)."',
                        '".Contenido_Security::escapeDB($title, $db)."',
                        '".Contenido_Security::escapeDB($urlname, $db)."',
                        '".Contenido_Security::escapeDB($page_title, $db)."',
						'".Contenido_Security::escapeDB($summary, $db)."',
						'".Contenido_Security::escapeDB($artspec, $db)."',
                        '".Contenido_Security::escapeDB($created, $db)."',
                        '".Contenido_Security::escapeDB($lastmodified, $db)."',
                        '".Contenido_Security::escapeDB($auth->auth["uname"], $db)."',
						'".Contenido_Security::escapeDB($published_value, $db)."',
						'".Contenido_Security::escapeDB($publishedby_value, $db)."',
                        '".Contenido_Security::escapeDB($online, $db)."',
                        '".Contenido_Security::escapeDB($redirect, $db)."',
                        '".Contenido_Security::escapeDB($redirect_url, $db)."',
                        '".Contenido_Security::escapeDB($external_redirect, $db)."',
                        '".Contenido_Security::escapeDB($artsort, $db)."',
                        '".Contenido_Security::escapeDB($usetimemgmt, $db)."',
                        '".Contenido_Security::escapeDB($datestart, $db)."',
                        '".Contenido_Security::escapeDB($dateend, $db)."',
                        '0',
                        '".Contenido_Security::escapeDB($movetocat, $db)."',
                        '".Contenido_Security::escapeDB($time_target_cat, $db)."',
                        '".Contenido_Security::escapeDB($onlineaftermove, $db)."')";
                        
                $db->query($sql);
                
                if ($cfg["is_start_compatible"] == false)
                {
                	if ($autostart == true)
                	{
                		conMakeStart($idcatart, 1);
                	} else {
                		conMakeStart($idcatart, 0);
                	}
                	
                }

			$availableTags = conGetAvailableMetaTagTypes();
	
			foreach ($availableTags as $key => $value)
			{
				conSetMetaValue($nextidartlang,
								$key,
								$_POST['META'.$value["name"]]);
        	}
        }

        # Set new idart
        $idart = $new_idart;

        # Table 'cat_art'
        $sql = "SELECT idcat FROM ".$cfg["tab"]["cat_art"]." WHERE idart='".Contenido_Security::toInteger($idart)."'"; // get all idcats that contain art
        $db->query($sql);

        while ($db->next_record()) {
            $tmp_idcat[] = $db->f("idcat");
        }

        if ( !is_array($idcatnew) )     { $idcatnew[0] = 0; }
        if ( !is_array($tmp_idcat) )    { $tmp_idcat[0] = 0; }
        
        foreach ($idcatnew as $value) {

            if ( !in_array($value, $tmp_idcat) ) {

                # INSERT -> Table 'cat_art'
                $sql = "INSERT INTO ".$cfg["tab"]["cat_art"]." (idcatart, idcat, idart) VALUES ('".Contenido_Security::toInteger($db->nextid($cfg["tab"]["cat_art"]))."', '".Contenido_Security::toInteger($value)."',
                        '".Contenido_Security::toInteger($idart)."')";
                $db->query($sql);

                # Entry in 'stat'-table for all languages
                $sql = "SELECT idcatart FROM ".$cfg["tab"]["cat_art"]." WHERE idcat='".Contenido_Security::toInteger($value)."' AND idart='".Contenido_Security::toInteger($idart)."'";
                $db->query($sql);
                
                $db->next_record();
                $tmp_idcatart = $db->f("idcatart");

                $a_languages = getLanguagesByClient($client);

                foreach ($a_languages as $tmp_lang) {

                    $sql = "INSERT INTO ".$cfg["tab"]["stat"]." (idstat, idcatart, idlang, idclient, visited) VALUES ('".Contenido_Security::toInteger($db->nextid($cfg["tab"]["stat"]))."',
                            '".Contenido_Security::toInteger($tmp_idcatart)."', '".Contenido_Security::toInteger($tmp_lang)."', '".Contenido_Security::toInteger($client)."', '0')";
                    $db->query($sql);
                }
            }
        }
        
        
        foreach ($tmp_idcat as $value) {

            if ( !in_array($value, $idcatnew) ) {

                $sql = "SELECT idcatart FROM ".$cfg["tab"]["cat_art"]." WHERE idcat='".Contenido_Security::toInteger($value)."' AND idart='".Contenido_Security::toInteger($idart)."'"; // get all idcatarts that will no longer exist
                $db->query($sql);

                //******** delete from 'code'-table ***************        // and delete corresponding code
                $sql = "DELETE FROM ".$cfg["tab"]["code"]." WHERE idcatart='".Contenido_Security::toInteger($db->f("idcatart"))."'";
                $db->query($sql);

                //******* delete from 'stat'-table ****************
                $sql = "SELECT * FROM ".$cfg["tab"]["cat_art"]." WHERE idcat='".Contenido_Security::toInteger($value)."' AND idart='".Contenido_Security::toInteger($idart)."' ";
                $db->query($sql);

                while ($db->next_record()) {
                    $a_idcatart[] = $db->f("idcatart");
                }
                
                if (is_array($a_idcatart)) {

                    foreach ($a_idcatart AS $value2) {
                        //****** delete from 'stat'-table ************
                        $sql = "DELETE FROM ".$cfg["tab"]["stat"]." WHERE idcatart='".Contenido_Security::toInteger($value2)."'";
                        $db->query($sql);
                    }
                }
                
                //******** delete from 'cat_art'-table ***************
                $sql = "DELETE FROM ".$cfg["tab"]["cat_art"]." WHERE idart='".Contenido_Security::toInteger($idart)."' AND idcat='".Contenido_Security::toInteger($value)."'";
                $db->query($sql);

                /* Remove startidartlang */
                if (isStartArticle($idartlang, $idcat, $lang))
                {
                	$sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET startidartlang='0' WHERE idcat='".Contenido_Security::toInteger($value)."' AND idlang='".Contenido_Security::toInteger($lang)."'";
                	$db->query($sql);
                }
                
                //******** delete from 'tpl_conf'-table ***************
                $sql = "SELECT idtplcfg FROM ".$cfg["tab"]["art_lang"]." WHERE idart = '".Contenido_Security::toInteger($idart)."' AND idlang = '".Contenido_Security::toInteger($lang)."'";
                $db->query($sql);
                $db->next_record();
                $tmp_idtplcfg = $db->f('idtplcfg');

                $sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg = '".Contenido_Security::toInteger($tmp_idtplcfgm)."'";
                $db->query($sql);

            } 
        }


        //********* update into 'art_lang'-table for all languages ******
        if ( !$title ) $title = "--- " . i18n("Default title"). " ---";

        $a_languages = getLanguagesByClient($client);

        foreach ($a_languages as $tmp_lang) {				
            $tmp_online 		= ( $lang == $tmp_lang ) ? $online : 0;
            $tmp_lastmodified	= ( $lang == $tmp_lang ) ? $lastmodified : 0;

            $sql = "UPDATE
                    ".$cfg["tab"]["art_lang"]."
                    SET
                    title           = '".Contenido_Security::escapeDB($title, $db)."',
                    urlname         = '".Contenido_Security::escapeDB($urlname, $db)."',
                    pagetitle       = '".Contenido_Security::escapeDB($page_title, $db)."',
					summary			= '".Contenido_Security::escapeDB($summary, $db)."',
					artspec 		= '".Contenido_Security::escapeDB($artspec, $db)."',
                    created         = '".Contenido_Security::escapeDB($created, $db)."',
                    lastmodified    = '".Contenido_Security::escapeDB($tmp_lastmodified, $db)."',
                    modifiedby      = '".Contenido_Security::escapeDB($author, $db)."',
                    online          = '".Contenido_Security::toInteger($tmp_online)."',
                    datestart       = '".Contenido_Security::escapeDB($datestart, $db)."',
                    dateend         = '".Contenido_Security::escapeDB($dateend, $db)."',
                    redirect        = '".Contenido_Security::escapeDB($redirect, $db)."',
                    external_redirect = '".Contenido_Security::escapeDB($external_redirect, $db)."',
                    redirect_url    = '".Contenido_Security::escapeDB($redirect_url, $db)."',
                    artsort         = '".Contenido_Security::escapeDB($artsort, $db)."'
                    WHERE
                    idart           = '".Contenido_Security::toInteger($new_idart)."' AND
                    idlang          = '".Contenido_Security::toInteger($tmp_lang)."'";

            $db->query($sql);
        }

        return $new_idart;
}




/**
 * Edit an existing article
 *
 * @param mixed many
 * @return void
 *
 * @author Olaf Niemann <olaf.niemann@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
function conEditArt($idcat, $idcatnew, $idart, $is_start, $idtpl, $idartlang,
                    $idlang, $title, $summary, $artspec, $created, $lastmodified, $author,
                    $online, $datestart, $dateend, $artsort, $keyart = 0)
{
        $args = func_get_args();
		
        global $db, $client, $lang, $cfg, $redirect, $redirect_url, $external_redirect, $perm;
        global $urlname;
        global $time_move_cat, $time_target_cat;
        global $time_online_move; // Used to indicate if the moved article should be online
        global $timemgmt;
        global $page_title;
        global $_POST;

        /* Add slashes because single quotes
           will crash the db */
        $page_title = addslashes($page_title);
        
		$urlname     = (trim($urlname) == '') ? trim($title) : trim($urlname);
        $urlname     = htmlspecialchars(capiStrCleanURLCharacters($urlname), ENT_QUOTES);
        $usetimemgmt = ($timemgmt == '1') ? '1': '0';        
		$onlineaftermove = ($time_online_move == '1') ? '1' : '0';
		$movetocat = ($time_move_cat == '1') ? '1' : '0';
        $redirect     = ('1' == $redirect ) ? 1 : 0;
        $redirect_url = ($redirect_url == 'http://' || $redirect_url == '') ? '0' : $redirect_url;
        $external_redirect = ($external_redirect == '1') ? 1 : 0;

		if ($is_start == 1)
		{
			$usetimemgmt = "0";
		}
		
        $sql = "SELECT idcat FROM ".$cfg["tab"]["cat_art"]." WHERE idart='".Contenido_Security::toInteger($idart)."'"; // get all idcats that contain art
        $db->query($sql);
        
        while ($db->next_record()) {
            $tmp_idcat[] = $db->f("idcat");
        }

        if ( !is_array($idcatnew) ) {
            $idcatnew[0] = 0;
        }

        if ( !is_array($tmp_idcat) ) {
            $tmp_idcat[0] = 0;
        }

        // if (is_array($idcatnew)) {
        foreach ($idcatnew as $value) {

            if (!in_array($value, $tmp_idcat) ) {

                # INSERT insert 'cat_art' table
                $sql = "INSERT INTO ".$cfg["tab"]["cat_art"]." (idcatart, idcat, idart) VALUES ('".Contenido_Security::toInteger($db->nextid($cfg["tab"]["cat_art"]))."', '".Contenido_Security::toInteger($value)."',
                        '".Contenido_Security::toInteger($idart)."')";
                $db->query($sql);

                # entry in 'stat'-table for all languages
                $sql = "SELECT idcatart FROM ".$cfg["tab"]["cat_art"]." WHERE idcat='".Contenido_Security::toInteger($value)."' AND idart='".Contenido_Security::toInteger($idart)."'";
                $db->query($sql);
                $db->next_record();
                
                $tmp_idcatart = $db->f("idcatart");

                $a_languages = getLanguagesByClient($client);

                foreach ($a_languages as $tmp_lang) {
                    $sql = "INSERT INTO ".$cfg["tab"]["stat"]." (idstat, idcatart, idlang, idclient, visited) VALUES ('".Contenido_Security::toInteger($db->nextid($cfg["tab"]["stat"]))."',
                            '".Contenido_Security::toInteger($tmp_idcatart)."', '".Contenido_Security::toInteger($tmp_lang)."', '".Contenido_Security::toInteger($client)."', '0')";
                    $db->query($sql);
                }
            }
        }
        

        foreach ($tmp_idcat as $value) {

            if (!in_array($value, $idcatnew)) {
                        
                $sql = "SELECT idcatart FROM ".$cfg["tab"]["cat_art"]." WHERE idcat='".Contenido_Security::toInteger($value)."' AND idart='".Contenido_Security::toInteger($idart)."'";  // get all idcatarts that will no longer exist
                $db->query($sql);

                //******** delete from 'code'-table ***************        // and delete corresponding code
                $sql = "DELETE FROM ".$cfg["tab"]["code"]." WHERE idcatart='".Contenido_Security::toInteger($db->f("idcatart"))."'";
                $db->query($sql);

                //******* delete from 'stat'-table ****************
                $sql = "SELECT * FROM ".$cfg["tab"]["cat_art"]." WHERE idcat='".Contenido_Security::toInteger($value)."' AND idart='".Contenido_Security::toInteger($idart)."'";
                $db->query($sql);
                
                while ($db->next_record()) {
                    $a_idcatart[] = $db->f("idcatart");
                }
                
                if (is_array($a_idcatart)) {
                    foreach ($a_idcatart as $value2) {
                        //****** delete from 'stat'-table ************
                        $sql = "DELETE FROM ".$cfg["tab"]["stat"]." WHERE idcatart='".Contenido_Security::toInteger($value2)."'";
                        $db->query($sql);
                    }
                }

                //******** delete from 'cat_art'-table ***************
                $sql = "DELETE FROM ".$cfg["tab"]["cat_art"]." WHERE idart='".Contenido_Security::toInteger($idart)."' AND idcat='".Contenido_Security::toInteger($value)."'";
                $db->query($sql);
                
                /* Remove startidartlang */
                if (isStartArticle($idartlang, $idcat, $lang))
                {
                    $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET startidartlang='0' WHERE idcat='".Contenido_Security::toInteger($value)."' AND idlang='".Contenido_Security::toInteger($lang)."'";
                    $db->query($sql);
                }

            }

        }

		// If the user has no right for makeonline, don't update it.
		if (!$perm->have_perm_area_action("con","con_makeonline") &&
		    !$perm->have_perm_area_action_item("con","con_makeonline", $idcat))
		{
		    $sqlonline = "";
		} else {
			$sqlonline = "online = '".Contenido_Security::escapeDB($online, $db)."',";
			if($online=='1'){
				//Check if online id is currently 0
				$sql = "SELECT online FROM ".$cfg["tab"]["art_lang"]." WHERE idartlang='".Contenido_Security::toInteger($idartlang)."'";
				$db->query($sql);
				if($db->next_record()){
					if($db->f("online")==0){
						//Only update if value changed from 0 to 1
						$sqlonline.="published = '".date("Y-m-d H:i:s")."', publishedby='".Contenido_Security::escapeDB($author, $db)."',";
					}
				}
			}
		}
		
		if ($title == "")
		{
			$title = "--- ".i18n("Default title")." ---";
		}
		
        //******** update 'art_lang'-table **********
        $sql = "UPDATE
                    ".$cfg["tab"]["art_lang"]."
                SET
                    title = '".Contenido_Security::escapeDB($title, $db)."',
                    urlname  = '".Contenido_Security::escapeDB($urlname, $db)."',
                    pagetitle = '".Contenido_Security::escapeDB($page_title, $db)."',
					summary = '".Contenido_Security::escapeDB($summary, $db)."',
					artspec = '".Contenido_Security::escapeDB($artspec, $db)."',
                    created = '".Contenido_Security::escapeDB($created, $db)."',
                    lastmodified = '".Contenido_Security::escapeDB($lastmodified, $db)."',
                    modifiedby = '".Contenido_Security::escapeDB($author, $db)."',
                    $sqlonline
                    timemgmt = '".Contenido_Security::escapeDB($usetimemgmt, $db)."',
                    redirect = '".Contenido_Security::escapeDB($redirect, $db)."',
                    external_redirect = '".Contenido_Security::escapeDB($external_redirect, $db)."',
                    redirect_url = '".Contenido_Security::escapeDB($redirect_url, $db)."',
                    artsort = '".Contenido_Security::toInteger($artsort)."'";

		if ($perm->have_perm_area_action("con", "con_makeonline") ||
        $perm->have_perm_area_action_item("con","con_makeonline", $idcat))
        {
        	        $sql .= ", datestart = '".Contenido_Security::escapeDB($datestart, $db)."',
                    dateend = '".Contenido_Security::escapeDB($dateend, $db)."',
                    time_move_cat = '".Contenido_Security::toInteger($movetocat)."',
                    time_target_cat = '".Contenido_Security::toInteger($time_target_cat)."',
                    time_online_move = '".Contenido_Security::toInteger($onlineaftermove)."'";
        }     
               

		$sql .= "WHERE idartlang='".Contenido_Security::toInteger($idartlang)."'";
        $db->query($sql);

        $availableTags = conGetAvailableMetaTagTypes();
	
		foreach ($availableTags as $key => $value)
		{
			conSetMetaValue($idartlang,
							$key,
							$_POST['META'.$value["name"]]);
		}

}

/**
 * Save a content element and generate index
 *
 * @param integer $idartlang idartlang of the article
 * @param string $type Type of content element
 * @param integer $typeid Serial number of the content element
 * @param string $value Content
 *
 * @return void
 *
 * @author Olaf Niemann <olaf.niemann@4fb.de>
 *         Jan Lengowski <jan.lengowski@4fb.de>
 *
 * @copyright four for business AG <www.4fb.de>
 *
 */
function conSaveContentEntry($idartlang, $type, $typeid, $value, $bForce = false)
{
    global $auth, $cfg, $cfgClient, $client, $lang, $_cecRegistry;

	if ($bForce == true) {
		$db = new DB_Contenido;
	} else {
		global $db;
	}
	
	cInclude("classes", "class.search.php");
	
    $date   = date("Y-m-d H:i:s");
    $author = $auth->auth["uname"];

    $cut_path  = $cfgClient[$client]["path"]["htmlpath"];

    $value = str_replace($cut_path, "", $value);
    $value = stripslashes($value);

	$iterator = $_cecRegistry->getIterator("Contenido.Content.SaveContentEntry");
	
	while ($chainEntry = $iterator->next())
	{
		$value =  $chainEntry->execute($idartlang, $type, $typeid, $value);
	}
    $value = urlencode($value);

    $sql = "SELECT * FROM ".$cfg["tab"]["type"]." WHERE type = '".Contenido_Security::escapeDB($type, $db)."'";
    $db->query($sql);	
    $db->next_record();
    $idtype=$db->f("idtype");

    $sql = "SELECT * FROM ".$cfg["tab"]["content"]." WHERE idartlang='".Contenido_Security::toInteger($idartlang)."' AND idtype='".Contenido_Security::toInteger($idtype)."' AND typeid='".Contenido_Security::toInteger($typeid)."'";
    $db->query($sql);

    if ($db->next_record()) {
            $sql = "UPDATE ".$cfg["tab"]["content"]." SET value='".Contenido_Security::escapeDB($value, $db)."', author='".Contenido_Security::escapeDB($author, $db)."', lastmodified='".Contenido_Security::escapeDB($date, $db)."'
                    WHERE idartlang='".Contenido_Security::toInteger($idartlang)."' AND idtype='".Contenido_Security::toInteger($idtype)."' AND typeid='".Contenido_Security::toInteger($typeid)."'";
            $db->query($sql);
    } else {

            $sql = "INSERT INTO ".$cfg["tab"]["content"]." (idcontent, idartlang, idtype, typeid, value, author, created, lastmodified) VALUES('".Contenido_Security::toInteger($db->nextid($cfg["tab"]["content"]))."',
                    '".Contenido_Security::toInteger($idartlang)."', '".Contenido_Security::toInteger($idtype)."', '".Contenido_Security::toInteger($typeid)."', '".Contenido_Security::escapeDB($value, $db)."',
                    '".Contenido_Security::escapeDB($author, $db)."', '".Contenido_Security::escapeDB($date, $db)."', '".Contenido_Security::escapeDB($date, $db)."')";
            $db->query($sql);
    }
        
    /* Touch the article to update last modified date */
    $lastmodified = date("Y-m-d H:i:s");
    
    $sql = "UPDATE
                    ".$cfg["tab"]["art_lang"]."
			SET
                    lastmodified = '".Contenido_Security::escapeDB($lastmodified, $db)."',
                    modifiedby = '".Contenido_Security::escapeDB($author, $db)."'
			WHERE
                    idartlang='".Contenido_Security::toInteger($idartlang)."'";
	$db->query($sql);                    
}

/** 
 * generate index of article content 
 * 
 * added by stese 
 * removed from function conSaveContentEntry  before 
 * Touch the article to update last modified date 
 * 
 * @see conSaveContentEntry 
 * @param integer $idart 
 */ 
function conMakeArticleIndex ( $idartlang, $idart ) {
	global $db, $auth, $cfg; 

	# generate index of article content	
	$oIndex 	= new Index($db); 
	$aOptions	= array("img", "link", "linktarget", "swf"); // cms types to be excluded from indexing 
	# indexing an article depends on the complete content with all content types, i.e it can not by differentiated by specific content types. 
	# Therefore one must fetch the complete content arrray. 
    
	$aContent = conGetContentFromArticle($idartlang);
	$oIndex->start($idart, $aContent, 'auto', $aOptions); 
}

/**
 * Toggle the online status
 * of an article
 *
 * @param int $idart Article Id
 * @param ing $lang Language Id
 *
 * @author Olaf Niemann <olaf.niemann@4fb-de>
 *         Jan Lengowski <jan.lengowski@4fb.de>
 *
 * @copyright four for business AG <www.4fb.de>
 */
function conMakeOnline($idart, $lang)
{
    global $db, $cfg, $auth;

    $sql = "SELECT online FROM ".$cfg["tab"]["art_lang"]." WHERE idart = '".Contenido_Security::toInteger($idart)."' AND idlang = '".Contenido_Security::toInteger($lang)."'";
    $db->query($sql);

    $db->next_record();

    $set = ( $db->f("online") == 0 ) ? 1 : 0;

	if($set==1){
		$publisher_info ="published = '".date("Y-m-d H:i:s")."', publishedby='".$auth->auth["uname"]."',";
	}else{
		$publisher_info = '';
	}
    $sql = "UPDATE ".$cfg["tab"]["art_lang"]."  SET ".$publisher_info." online = '".Contenido_Security::toInteger($set)."' WHERE idart = '".Contenido_Security::toInteger($idart)."'
            AND idlang = '".Contenido_Security::toInteger($lang)."'";
    $db->query($sql);
}

/**
 * Toggle the lock status
 * of an article
 *
 * @param int $idart Article Id
 * @param ing $lang Language Id
 *
 */
function conLock($idart, $lang)
{
    global $db, $cfg;

    $sql = "SELECT locked FROM ".$cfg["tab"]["art_lang"]." WHERE idart = '".Contenido_Security::toInteger($idart)."' AND idlang = '".Contenido_Security::toInteger($lang)."'";
    $db->query($sql);

    $db->next_record();

    $set = ( $db->f("locked") == 0 ) ? 1 : 0;

    $sql = "UPDATE ".$cfg["tab"]["art_lang"]." SET locked = '".Contenido_Security::toInteger($set)."' WHERE idart = '".Contenido_Security::toInteger($idart)."' AND idlang = '".Contenido_Security::toInteger($lang)."'";
    $db->query($sql);
}

/**
 * Toggle the online status of
 * a category
 *
 * @param int $idcat id of the category
 * @param int $lang id of the language
 * @param int $status status of the category
 *
 * @author Jan Lengowski <jan.lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
function conMakeCatOnline($idcat, $lang, $status)
{
    global $cfg, $db;
    
     $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET visible = '".Contenido_Security::toInteger($status)."',
				lastmodified = '".Contenido_Security::escapeDB(date("Y-m-d H:i:s"), $db)."' 
                WHERE idcat = '".Contenido_Security::toInteger($idcat)."' AND idlang = '".Contenido_Security::toInteger($lang)."'";
     $db->query($sql);

	if ($cfg["pathresolve_heapcache"] == true && !$status = 0)
	{
		$pathresolve_tablename = $cfg["sql"]["sqlprefix"]."_pathresolve_cache";
		$sql = "DELETE FROM %s WHERE idlang = '%s' AND idcat = '%s'";
		$db->query(sprintf(Contenido_Security::escapeDB($sql, $db), $pathresolve_tablename, $lang, $idcat));
	}
}

/**
 * Toggle the public status of a category
 * 
 * Almost the same function as strMakePublic in 
 * functions.str.php (conDeeperCategoriesArray instead of
 * strDeeperCategoriesArray)
 *	
 * @param int $idcat Article Id
 * @param int $idcat Language Id
 * @param bool $is_start Start status of the Article
 *
 * @author Olaf Niemann <olaf.niemann@4fb-de>
 *         Jan Lengowski <jan.lengowski@4fb.de>
 *
 * @copyright four for business AG <www.4fb.de>
 */
function conMakePublic($idcat, $lang, $public)
{
    global $db, $cfg;
    $public = (int) $public;
    if ($public != 1) {
        $public = 0;
    }
    
	$a_catstring = conDeeperCategoriesArray($idcat);
	foreach ($a_catstring as $value) {
		$sql = "UPDATE ".$cfg["tab"]["cat_lang"].
			   " SET public='".Contenido_Security::toInteger($public)."', lastmodified = '".Contenido_Security::escapeDB(date("Y-m-d H:i:s"), $db).
			   "' WHERE idcat='".Contenido_Security::toInteger($value)."' AND idlang='".Contenido_Security::toInteger($lang)."' ";
		$db->query($sql);
	}	
}

/**
 * Delete an Article
 *
 * @param int $idart Article Id
 *
 * @author Olaf Niemann <olaf.niemann@4fb-de>
 *         Jan Lengowski <jan.lengowski@4fb.de>
 *
 * @copyright four for business AG <www.4fb.de>
 */
function conDeleteart($idart)
{
    global $db, $cfg, $lang, $_cecRegistry;

    /* Delete current language */
    $sql = "SELECT idartlang, idtplcfg FROM ".$cfg["tab"]["art_lang"]." WHERE idart = '".Contenido_Security::toInteger($idart)."' AND idlang='".Contenido_Security::toInteger($lang)."'";
    $db->query($sql);
    $db->next_record();

    $idartlang = $db->f("idartlang");
    $idtplcfg = $db->f("idtplcfg");

    /* Fetch idcat */
    $sql = "SELECT idcat FROM ".$cfg["tab"]["cat_art"]." WHERE idart = '".Contenido_Security::toInteger($idart)."'";
    $db->query($sql);
    $db->next_record();

    $idcat = $db->f("idcat");

    /* Remove startidartlang */
    if (isStartArticle($idartlang, $idcat, $lang))
    {
        $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET startidartlang='0' WHERE idcat='".Contenido_Security::toInteger($idcat)."' AND idlang='".Contenido_Security::toInteger($lang)."'";
        $db->query($sql);
    }

    $sql = "DELETE FROM ".$cfg["tab"]["content"]." WHERE idartlang = '".Contenido_Security::toInteger($idartlang)."'";
    $db->query($sql);

    $sql = "DELETE FROM ".$cfg["tab"]["art_lang"]." WHERE idartlang = '".Contenido_Security::toInteger($idartlang)."'";
    $db->query($sql);

    if ($idtplcfg != "0") {

        $sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = '".Contenido_Security::toInteger($idtplcfg)."'";
        $db->query($sql);

        $sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg = '".Contenido_Security::toInteger($idtplcfg)."'";
        $db->query($sql);

    }

    /* Check if there are remaining languages */
    $sql = "SELECT idartlang FROM ".$cfg["tab"]["art_lang"]." WHERE idart = '".Contenido_Security::toInteger($idart)."'";
    $db->query($sql);

    if ($db->num_rows() > 0)
    {
        return;
    }

    $sql = "SELECT * FROM ".$cfg["tab"]["cat_art"]." WHERE idart = '".Contenido_Security::toInteger($idart)."'";
    $db->query($sql);

    while ( $db->next_record() ) {
        $idcatart[] = $db->f("idcatart");
    }

    ##################################################
    # set keywords
    $keycode[1][1]="";
    saveKeywordsForArt($keycode,$idart,"auto",$lang);
    saveKeywordsForArt($keycode,$idart,"self",$lang);

    if ( is_array($idcatart) ) {

        foreach ($idcatart AS $value) {

            //********* delete from code table **********
            $sql = "DELETE FROM ".$cfg["tab"]["code"]." WHERE idcatart = '".Contenido_Security::toInteger($value)."'";
            $db->query($sql);

            //****** delete from 'stat'-table ************
            $sql = "DELETE FROM ".$cfg["tab"]["stat"]." WHERE idcatart = '".Contenido_Security::toInteger($value)."'";
            $db->query($sql);

        }
    }

    $sql = "SELECT * FROM ".$cfg["tab"]["art_lang"]." WHERE idart = '".Contenido_Security::toInteger($idart)."'";

    $db->query($sql);

    while ( $db->next_record() ) {
        $idartlang[] = $db->f("idartlang");
    }

    if ( is_array($idartlang) ) {

        foreach ($idartlang AS $value) {

            $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET startidartlang='0' WHERE startidartlang ='".Contenido_Security::toInteger($value)."'";
            $db->query($sql);

            //********* delete from content table **********
            $sql = "DELETE FROM ".$cfg["tab"]["content"]." WHERE idartlang = '".Contenido_Security::toInteger($value)."'";
            $db->query($sql);
        }
    }

    $sql = "DELETE FROM ".$cfg["tab"]["cat_art"]." WHERE idart = '".Contenido_Security::toInteger($idart)."'";
    $db->query($sql);

    $sql = "DELETE FROM ".$cfg["tab"]["art"]." WHERE idart = '".Contenido_Security::toInteger($idart)."'";
    $db->query($sql);

    $sql = "DELETE FROM ".$cfg["tab"]["art_lang"]." WHERE idart = '".Contenido_Security::toInteger($idart)."'";
    $db->query($sql);

	# Contenido Extension Chain 
	# @see docs/techref/plugins/Contenido Extension Chainer.pdf 
	# 
	# Usage: 
	# One could define the file contenido/includes/config.local.php 
	# with following code. 
	# 
	# global $_cecRegistry; 
	# cInclude("plugins", "extension/extension.php"); 
	# $_cecRegistry->addChainFunction("Contenido.Content.DeleteArticle", "AdditionalFunction1"); 
	# 
	# If function "AdditionalFunction1" is defined in file extension.php, it would be called via 
	# $chainEntry->execute($idart); 

	$iterator = $_cecRegistry->getIterator("Contenido.Content.DeleteArticle"); 

	while ($chainEntry = $iterator->next()) 
	{ 
		$chainEntry->execute($idart); 
	}
}

/**
 * Extract a number from a string
 *
 * @param string $string String var by reference
 *
 * @author Olaf Niemann <olaf.niemann@4fb-de>
 *         Jan Lengowski <jan.lengowski@4fb.de>
 *
 * @copyright four for business AG <www.4fb.de>
 */
function extractNumber(&$string)
{
    $string = preg_replace("/[^0-9]/","",$string);
}



/**
 * Change the template of a category
 *
 * @param int $idcat Category Id 
 * @param int $idtpl Template Id
 *
 * @return void
 * @author Jan Lengowski <jan.lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
function conChangeTemplateForCat($idcat, $idtpl)
{
    /* Global vars */
    global $db, $db2, $cfg, $lang;
	
	/* DELETE old entries */
	$sql = "SELECT idtplcfg FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat = '".Contenido_Security::toInteger($idcat)."' AND idlang = '".Contenido_Security::toInteger($lang)."'";
	$db->query($sql);
	$db->next_record();	
	$old_idtplcfg = $db->f("idtplcfg");
	
	$sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg = '".Contenido_Security::toInteger($old_idtplcfg)."'";
	$db->query($sql);
	
	$sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = '".Contenido_Security::toInteger($old_idtplcfg)."'";
	$db->query($sql);	
	
    /* parameter $idtpl is 0,
       reset the template */
    if ( 0 == $idtpl ) {

        /* get $idtplcfg */
        $sql = "SELECT idtplcfg FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat = '".Contenido_Security::toInteger($idcat)."' AND idlang = '".Contenido_Security::toInteger($lang)."'";

        $db->query($sql);
        $db->next_record();
        
        $idtplcfg = $db->f("idtplcfg");
        
        /* DELETE 'template_conf' entry */
        $sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg = '".Contenido_Security::toInteger($idtplcfg)."'";
        $db->query($sql);
        
        /* DELETE 'container_conf entries' */
        $sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg = '".Contenido_Security::toInteger($idtplcfg)."'";
        $db->query($sql);
        
        /* UPDATE 'cat_lang' table */
        $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET idtplcfg = '0' WHERE idcat = '".Contenido_Security::toInteger($idcat)."' AND idlang = '".Contenido_Security::toInteger($lang)."'";
        $db->query($sql);

    } else {

        if ( !is_object($db2) ) $db2 = new DB_Contenido;

        /* check if a pre-configuration
           is assigned */
        $sql = "SELECT idtplcfg FROM ".$cfg["tab"]["tpl"]." WHERE idtpl = '".Contenido_Security::toInteger($idtpl)."'";

        $db->query($sql);
        $db->next_record();

        if ( 0 != $db->f("idtplcfg") ) {

            /* template is pre-configured,
               create new configuration and
               copy data from pre-cfg */

            /* get new id */
            $new_idtplcfg = $db2->nextid($cfg["tab"]["tpl_conf"]);

            /* create new configuration */
            $sql = "INSERT INTO ".$cfg["tab"]["tpl_conf"]." (idtplcfg, idtpl) VALUES ('".Contenido_Security::toInteger($new_idtplcfg)."', '".Contenido_Security::toInteger($idtpl)."')";
            $db->query($sql);

            /* extract pre-configuration data */
            $sql = "SELECT * FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = '".Contenido_Security::toInteger($db->f("idtplcfg"))."'";
            $db->query($sql);

            while ( $db->next_record() ) {

                /* get data */
                $nextid     = $db2->nextid($cfg["tab"]["container_conf"]);
                $number     = $db->f("number");
                $container  = $db->f("container");

                /* write new entry */
                $sql = "INSERT INTO
                            ".$cfg["tab"]["container_conf"]."
                            (idcontainerc, idtplcfg, number, container)
                        VALUES
                            ('".Contenido_Security::toInteger($nextid)."', '".Contenido_Security::toInteger($new_idtplcfg)."', '".Contenido_Security::toInteger($number)."', '".Contenido_Security::escapeDB($container, $db2)."')";

                $db2->query($sql);

            }
			
			/* extract old idtplcfg */
			$sql = "SELECT idtplcfg FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat = '".Contenido_Security::toInteger($idcat)."' AND idlang = '".Contenido_Security::toInteger($lang)."'";
			$db->query($sql);
			$db->next_record();
			$tmp_idtplcfg = $db->f("idtplcfg");
			
			if ( $tmp_idtplcfg != 0 ) {
				
				/* DELETE 'template_conf' entry */
            	$sql = "DELETE FROM ".$cfg["tab"]["tpl_conf"]." WHERE idtplcfg = '".Contenido_Security::toInteger($tmp_idtplcfg)."'";
                $db->query($sql);
                
                /* DELETE 'container_conf entries' */
                $sql = "DELETE FROM ".$cfg["tab"]["container_conf"]." WHERE idtplcfg = '".Contenido_Security::toInteger($tmp_idtplcfg)."'";
                $db->query($sql);					
				
			}
			
            /* update 'cat_lang' table */
            $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET idtplcfg = '".Contenido_Security::toInteger($new_idtplcfg)."' WHERE idcat = '".Contenido_Security::toInteger($idcat)."' AND idlang = '".Contenido_Security::toInteger($lang)."'";
            $db->query($sql);

        } else {

            /* template is not pre-configured,
               create a new configuration.  */
            $new_idtplcfg = $db->nextid($cfg["tab"]["tpl_conf"]);

            $sql = "INSERT INTO ".$cfg["tab"]["tpl_conf"]."
                    (idtplcfg, idtpl) VALUES
                    ('".Contenido_Security::toInteger($new_idtplcfg)."', '".Contenido_Security::toInteger($idtpl)."')";

            $db->query($sql);
            
            /* update 'cat_lang' table */
            $sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET idtplcfg = '".Contenido_Security::toInteger($new_idtplcfg)."' WHERE idcat = '".Contenido_Security::toInteger($idcat)."' AND idlang = '".Contenido_Security::toInteger($lang)."'";
            $db->query($sql);

        }
        
    }
    conGenerateCodeForAllartsInCategory($idcat);
} // end function


function conFetchCategoryTree ($client = false, $lang = false)
{
	global $db, $cfg;

	if ($client === false)
	{
		$client = $GLOBALS["client"];
	}
	
	if ($lang === false)
	{
		$lang = $GLOBALS["lang"];
	}	
	
    $sql = "SELECT
                *
            FROM
                ".$cfg["tab"]["cat_tree"]." AS A,
                ".$cfg["tab"]["cat"]." AS B,
                ".$cfg["tab"]["cat_lang"]." AS C
            WHERE
                A.idcat  = B.idcat AND
				B.idcat = C.idcat AND
				C.idlang = '".Contenido_Security::toInteger($lang)."' AND 
                idclient = '".Contenido_Security::toInteger($client)."'
            ORDER BY
                idtree";
     
    $catarray = array();
    
    $db->query($sql);
    
	while ($db->next_record())
	{
		$catarray[$db->f("idtree")] = array(
					"idcat" => $db->f("idcat"),
					"level" => $db->f("level"),
					"idtplcfg" => $db->f("idtplcfg"),
					"visible" => $db->f("visible"),
					"name" => $db->f("name"),
					"public" => $db->f("public"),
					"urlname" => $db->f("urlname"),
					"is_start" => $db->f("is_start")	
					);	
	}
	
	return ($catarray);
}

/**
 *
 * Fetch all deeper categories by a given id
 *
 * @param int $idcat Id of category
 * @return array Array with all deeper categories
 *
 * @author Olaf Niemann <olaf.niemann@4fb-de>
 *         Jan Lengowski <jan.lengowski@4fb.de>
 *
 * @copyright four for business AG <www.4fb.de>
 */
function conDeeperCategoriesArray($idcat_start)
{
    global $db, $client, $cfg;

    $sql = "SELECT
                *
            FROM
                ".$cfg["tab"]["cat_tree"]." AS A,
                ".$cfg["tab"]["cat"]." AS B
            WHERE
                A.idcat  = B.idcat AND
                idclient = '".Contenido_Security::toInteger($client)."'
            ORDER BY
                idtree";

    $db->query($sql);

    $found 		= false;
    $curLevel	= 0;

    while ( $db->next_record() ) {

        if ($found && $db->f("level") <= $curLevel) { // ending part of tree
            $found = false;
        }

        if ($db->f("idcat") == $idcat_start) { // starting part of tree
            $found = true;
            $curLevel = $db->f("level");
        }

        if ($found) {
            $catstring[] = $db->f("idcat");
        }
    }

    return $catstring;
}

/**
 * Recursive function to create an location string
 *
 * @param int $idcat ID of the starting category
 * @param string $seperator Seperation string
 * @param string $cat_str Category location string (by reference)
 * @param boolean $makeLink create location string with links
 * @param string $linkClass stylesheet class for the links
 * @param integer first navigation level location string should be printed out (first level = 0!!)
 *
 * @return string location string
 *
 * @author Jan Lengowski <jan.lengowski@4fb.de>
 * @author Marco Jahn <marco.jahn@4fb.de>
 *
 * @copyright four for business AG <www.4fb.de>
 */
function conCreateLocationString($idcat, $seperator, &$cat_str, $makeLink = false, $linkClass = "", $firstTreeElementToUse = 0, $uselang = 0, $final = true, $usecache = false)
{
    global $cfg, $client, $cfgClient, $lang, $sess, $_locationStringCache;
	
    if ($idcat == 0)
    {
        $cat_str = "Lost and Found";
        return;
    }
    
    if ($uselang == 0)
    {
    	$uselang = $lang;
    }

	if ($final == true && $usecache == true)
	{
		if (!is_array($_locationStringCache))
		{
		    if (file_exists($cfgClient[$client]["path"]["frontend"]."cache/locationstring-cache-$uselang.txt"))
		    {
		    	$_locationStringCache = unserialize(file_get_contents($cfgClient[$client]["path"]["frontend"]."cache/locationstring-cache-$uselang.txt"));
		    } else {
		    	$_locationStringCache = array();	
		    }
		}
			
		if (array_key_exists($idcat, $_locationStringCache))
		{
	    	if ($_locationStringCache[$idcat]["expires"] > time())
	    	{
	    			$cat_str = $_locationStringCache[$idcat]["name"];
	    			return;
    		}    			
		}		       		
	}    
    
    $db = new DB_Contenido;
    
    $sql = "SELECT
                a.name AS name,
                a.idcat AS idcat,
                b.parentid AS parentid,
				c.level as level
            FROM
                ".$cfg["tab"]["cat_lang"]." AS a,
                ".$cfg["tab"]["cat"]." AS b,
				".$cfg["tab"]["cat_tree"]." AS c
            WHERE
                a.idlang    = '".Contenido_Security::toInteger($uselang)."' AND
                b.idclient  = '".Contenido_Security::toInteger($client)."' AND
                b.idcat     = '".Contenido_Security::toInteger($idcat)."' AND
                a.idcat     = b.idcat AND
				c.idcat = b.idcat";
    
    $db->query($sql);
    $db->next_record();

	if ($db->f("level") >= $firstTreeElementToUse)
	{

		$name       = $db->f("name");
		$parentid   = $db->f("parentid");
	
		//create link
		if ($makeLink == true)
		{
			$linkUrl = $sess->url("front_content.php?idcat=$idcat");
			$name = '<a href="'.$linkUrl.'" class="'.$linkClass.'">'.$name.'</a>';	
		}

		$tmp_cat_str = $name . $seperator . $cat_str;
		$cat_str = $tmp_cat_str;

	}

    if ( $parentid != 0 ) {
        conCreateLocationString($parentid, $seperator, $cat_str, $makeLink, $linkClass, $firstTreeElementToUse ,$uselang, false);
        
    } else {
        $sep_length = strlen($seperator);
        $str_length = strlen($cat_str);
        $tmp_length = $str_length - $sep_length;
        $cat_str = substr($cat_str, 0, $tmp_length);
    }
    
    if ($final == true && $usecache == true)
    {
   		$_locationStringCache[$idcat]["name"] = $cat_str;
   		$_locationStringCache[$idcat]["expires"] = time() + 3600;
   		
   		if (is_writable($cfgClient[$client]["path"]["frontend"]."cache/"))
   		{
   			file_put_contents($cfgClient[$client]["path"]["frontend"]."cache/locationstring-cache-$uselang.txt", serialize($_locationStringCache));
   		}
    }
}

/**
 * Set a start-article
 *
 * @param int $idcatart Idcatart of the article
 *
 * @return void
 *
 * @author Olaf Niemann <olaf.niemann@4fb-de>
 *         Jan Lengowski <jan.lengowski@4fb.de>
 *
 * @copyright four for business AG <www.4fb.de>
 */
function conMakeStart($idcatart, $is_start)
{
    global $db, $cfg, $lang;

	if ($cfg["is_start_compatible"] == true)
	{
        $sql = "SELECT idcat FROM ".$cfg["tab"]["cat_art"]." WHERE idcatart = '".Contenido_Security::toInteger($idcatart)."'";

        $db->query($sql);
        $db->next_record();

        $tmp_idcat = $db->f("idcat");
    
    
        $sql = "SELECT tblCatArt.idcatart ".
                       "FROM ".$cfg["tab"]["cat_art"]." tblCatArt, ". $cfg["tab"]["art_lang"]." tblArtLang ".
                              "WHERE tblCatArt.idart = tblArtLang.idart AND tblCatArt.is_start = '1' AND ".
                                    "tblArtLang.idlang = '".Contenido_Security::toInteger($lang)."' AND tblCatArt.idcat = '".Contenido_Security::toInteger($tmp_idcat)."'";
        $db->query($sql);
       
        $aIDs = array();
        while ($db->next_record())
        {
            $aIDs[] = Contenido_Security::toInteger($db->f("idcatart"));
        }
      
        if (count($aIDs) > 0)
        {
            $sql = "UPDATE ".$cfg["tab"]["cat_art"]." SET is_start = 0 WHERE idcatart IN ('" . implode("','", $aIDs) . "')";
            $db->query($sql);
        }

    	$sql = "UPDATE ".$cfg["tab"]["cat_art"]." SET is_start='".Contenido_Security::toInteger($is_start)."' WHERE idcatart = '".Contenido_Security::toInteger($idcatart)."'";
    	$db->query($sql);
	} else {
		$sql = "SELECT idcat, idart FROM ".$cfg["tab"]["cat_art"]." WHERE idcatart='".Contenido_Security::toInteger($idcatart)."'";
		$db->query($sql);
		$db->next_record();
		
		$idart = $db->f("idart");
		$idcat = $db->f("idcat");
		
		$sql = "SELECT idartlang FROM ".$cfg["tab"]["art_lang"]." WHERE idart='".Contenido_Security::toInteger($idart)."' AND idlang='".Contenido_Security::toInteger($lang)."'";
		$db->query($sql);
		$db->next_record();
		
		$idartlang = $db->f("idartlang");
		
		if ($is_start == 1)
		{
			$sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET startidartlang='".Contenido_Security::toInteger($idartlang)."' WHERE idcat='".Contenido_Security::toInteger($idcat)."' AND idlang='".Contenido_Security::toInteger($lang)."'";
			$db->query($sql);
		} else {
			$sql = "UPDATE ".$cfg["tab"]["cat_lang"]." SET startidartlang='0' WHERE idcat='".Contenido_Security::toInteger($idcat)."' AND idlang='".Contenido_Security::toInteger($lang)."' AND startidartlang='".Contenido_Security::toInteger($idartlang)."'";
			$db->query($sql);
		}
	}
	
	if ( $is_start == 1 )
	{ 
		// Deactivate time management if article is a start article
		$sql = "SELECT idart FROM ".$cfg["tab"]["cat_art"]." WHERE idcatart = '".Contenido_Security::toInteger($idcatart)."'";
		
		$db->query($sql);
		$db->next_record(); 

		$idart = $db->f("idart");
      
		$sql = "UPDATE ".$cfg["tab"]["art_lang"]." SET timemgmt = 0 WHERE idart = '".Contenido_Security::toInteger($idart)."' AND idlang = '".Contenido_Security::toInteger($lang)."'"; 
		$db->query($sql);
	}
}

/**
 * Create code for one article in all categorys
 *
 * @param int $idart Article ID
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG 2003
 */
function conGenerateCodeForArtInAllCategories($idart)
{
    global $lang, $client, $cfg;
    $db = new DB_Contenido;

    $sql = "SELECT idcatart FROM ".$cfg["tab"]["cat_art"]." WHERE idart = '".Contenido_Security::toInteger($idart)."'";

    $db->query($sql);
    
    while ($db->next_record())
    {
        conSetCodeFlag($db->f("idcatart"));
    }    
}


/**
 * Generate code for all articles in a category
 *
 * @param int $idcat Category ID
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG 2003
 */
function conGenerateCodeForAllArtsInCategory($idcat)
{
    global $cfg;
    $db = new DB_Contenido;

    $sql = "SELECT idcatart FROM ".$cfg["tab"]["cat_art"]." WHERE idcat='".Contenido_Security::toInteger($idcat)."'";
    $db->query($sql);
    
    while ($db->next_record())
    {
        conSetCodeFlag($db->f("idcatart"));
    }
}

/**
 * Generate code for the active client
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG 2003
 */
function conGenerateCodeForClient() {

    global $client, $cfg;
    $db = new DB_Contenido;

    $sql = "SELECT A.idcatart
            FROM ".$cfg["tab"]["cat_art"]." as A, ".$cfg["tab"]["cat"]." as B
            WHERE B.idclient=''".Contenido_Security::toInteger($client)."' AND B.idcat=A.idcat";
    $db->query($sql);

    while ($db->next_record())
    {
        conSetCodeFlag($db->f("idcatart"));
    }
}

/**
 * Create code for all arts using the
 * same layout
 *
 * @param int $idlay Layout-ID
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG 2003
 */
function conGenerateCodeForAllartsUsingLayout($idlay)
{
    global $cfg;
    $db = new DB_Contenido;

    $sql = "SELECT idtpl FROM ".$cfg["tab"]["tpl"]." WHERE idlay='".Contenido_Security::toInteger($idlay)."'";
    $db->query($sql);
    while ($db->next_record())
    {
        conGenerateCodeForAllartsUsingTemplate($db->f("idtpl"));
    }
}

/**
 * Create code for all articles using
 * the same module
 *
 * @param int $idmod Module id
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG 2003
 */
function conGenerateCodeForAllartsUsingMod($idmod)
{
    global $cfg;
    $db = new DB_Contenido;

    $sql = "SELECT idtpl FROM ".$cfg["tab"]["container"]." WHERE idmod = '".Contenido_Security::toInteger($idmod)."'";
    $db->query($sql);

    while($db->next_record())
    {
        conGenerateCodeForAllArtsUsingTemplate($db->f("idtpl"));
    }    
}


/**
 * Generate code for all articles
 * using one template
 *
 * @param int $idtpl Template-Id
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG 2003
 */
function conGenerateCodeForAllArtsUsingTemplate($idtpl)
{
    global $cfg, $lang, $client;
	
	$db = new DB_Contenido;
	$db2 = new DB_Contenido;    
    
    /* Search all categories */
    $sql = "SELECT
                b.idcat
            FROM
                ".$cfg["tab"]["tpl_conf"]." AS a,
                ".$cfg["tab"]["cat_lang"]." AS b,
                ".$cfg["tab"]["cat"]." AS c
            WHERE
                a.idtpl     = '".Contenido_Security::toInteger($idtpl)."' AND
                b.idtplcfg  = a.idtplcfg AND
                c.idclient  = '".Contenido_Security::toInteger($client)."' AND
                b.idcat     = c.idcat";

    $db->query($sql);

    while ($db->next_record())
    {
        $sql = "SELECT idcatart FROM ".$cfg["tab"]["cat_art"]." WHERE idcat='".Contenido_Security::toInteger($db->f("idcat"))."'";
        $db2->query($sql);

        while ($db2->next_record())
        {
            conSetCodeFlag($db2->f("idcatart"));
        }
    }
    
    /* Search all articles */
    $sql = "SELECT
                b.idart
            FROM
                ".$cfg["tab"]["tpl_conf"]." AS a,
                ".$cfg["tab"]["art_lang"]." AS b,
                ".$cfg["tab"]["art"]." AS c
            WHERE
                a.idtpl     = '".Contenido_Security::toInteger($idtpl)."' AND
                b.idtplcfg  = a.idtplcfg AND
                c.idclient  = '".Contenido_Security::toInteger($client)."' AND
                b.idart     = c.idart";

    $db->query($sql);

    while ($db->next_record())
    {
        $sql = "SELECT idcatart FROM ".$cfg["tab"]["cat_art"]." WHERE idart='".Contenido_Security::toInteger($db->f("idart"))."'";
        $db2->query($sql);

        while ($db2->next_record())
        {
            conSetCodeFlag($db2->f("idcatart"));
        }
    }
}


/**
 * Create code for all articles
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG 2003
 */
function conGenerateCodeForAllArts()
{
    global $cfg;
    $db = new DB_Contenido;

    $sql = "SELECT idcatart FROM ".$cfg["tab"]["cat_art"];
    $db->query($sql);
    
    while ($db->next_record())
    {
        conSetCodeFlag($db->f("idcatart"));
    }
}

/**
 * Set code creation flag to true
 *
 * @param int $idcatart Contenido Category-Article-ID
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG 2003
 */
function conSetCodeFlag($idcatart)
{
    global $cfg;
    $db = new DB_Contenido;

    $sql = "UPDATE ".$cfg["tab"]["cat_art"]." SET createcode = '1' WHERE idcatart='".Contenido_Security::toInteger($idcatart)."'";
    $db->query($sql);
    
    /* Setting the createcode flag is not enough due to a bug in the
     * database structure. Remove all con_code entries for a specific
     * idcatart in the con_code table.
     */
     
     $sql = "DELETE FROM ".$cfg["tab"]["code"] ." WHERE idcatart='".Contenido_Security::toInteger($idcatart)."'";
     $db->query($sql);
}


/**
 * Set articles on/offline for the time management function
 *
 * @param none
 *
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @copyright four for business AG 2003
 */
function conFlagOnOffline() {
    global $cfg;
    $db = new DB_Contenido;
    $db2 = new DB_Contenido;

    /* Set all articles which are before our starttime to offline */
    $sql = "SELECT idartlang FROM ".$cfg["tab"]["art_lang"]." WHERE NOW() < datestart AND datestart != '0000-00-00 00:00:00' AND datestart IS NOT NULL AND timemgmt = 1";

    $db->query($sql);

    while ($db->next_record()) {
        $sql = "UPDATE ".$cfg["tab"]["art_lang"] ." SET online = 0 WHERE idartlang = '".Contenido_Security::toInteger($db->f("idartlang"))."'";
        $db2->query($sql);
    }

    /* Set all articles which are in between of our start/endtime to online */
    $sql = "SELECT idartlang FROM ".$cfg["tab"]["art_lang"]." WHERE NOW() > datestart AND (NOW() < dateend OR dateend = '0000-00-00 00:00:00') AND " .
    		"online = 0 AND timemgmt = 1";

    $db->query($sql);

    while ($db->next_record()) {
    	// modified 2007-11-14: Set publish date if article goes online
        $sql = "UPDATE " . $cfg["tab"]["art_lang"] . " SET online = 1, published = datestart " . 
        		"WHERE idartlang = " . Contenido_Security::toInteger($db->f("idartlang"));
        $db2->query($sql);
    }

    /* Set all articles after our endtime to offline */
    $sql = "SELECT idartlang FROM ".$cfg["tab"]["art_lang"]." WHERE NOW() > dateend AND dateend != '0000-00-00 00:00:00' AND timemgmt = 1";

    $db->query($sql);

    while ($db->next_record())
    {
        $sql = "UPDATE ".$cfg["tab"]["art_lang"]." SET online = 0 WHERE idartlang = '" . Contenido_Security::toInteger($db->f("idartlang")) . "'";
        $db2->query($sql);
    }

} 
/**
 * Move articles for the time management function
 * @param none
 *
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @copyright four for business AG 2003
 */
function conMoveArticles()
{
    global $cfg;
    $db = new DB_Contenido;
    $db2 = new DB_Contenido;

    /* Perform after-end updates */
    $sql = "SELECT idartlang, idart, time_move_cat, time_target_cat, time_online_move FROM ".$cfg["tab"]["art_lang"]." WHERE NOW() > dateend AND dateend != '0000-00-00 00:00:00' AND timemgmt = 1";

    $db->query($sql);

    while ($db->next_record())
    {    
        if ($db->f("time_move_cat") == "1")
        {
            $sql = "UPDATE ".$cfg["tab"]["art_lang"]." SET timemgmt = 0, online = 0 WHERE idartlang = '".Contenido_Security::toInteger($db->f("idartlang"))."'";
            $db2->query($sql);
            
            $sql = "UPDATE ".$cfg["tab"]["cat_art"]." SET idcat = '" . Contenido_Security::toInteger($db->f("time_target_cat")) . "', createcode = '1' WHERE idart = '" . Contenido_Security::toInteger($db->f("idart")) . "'";
            $db2->query($sql);

            if ($db->f("time_online_move") == "1")
            {
                $sql = "UPDATE ".$cfg["tab"]["art_lang"] ." SET online = 1 WHERE idart = '".Contenido_Security::toInteger($db->f("idart"))."'";
            } else {
                $sql = "UPDATE ".$cfg["tab"]["art_lang"] ." SET online = 0 WHERE idart = '".Contenido_Security::toInteger($db->f("idart"))."'";
            }
            $db2->query($sql);

            // execute CEC hook
            CEC_Hook::execute('Contenido.Article.conMoveArticles_Loop', $db->Record);
        }
    }

}


function conCopyTemplateConfiguration ($srcidtplcfg)
{
	global $cfg;
	
	$sql = "SELECT idtpl FROM ".$cfg["tab"]["tpl_conf"] ." WHERE idtplcfg = '".Contenido_Security::toInteger($srcidtplcfg)."'";
	$db = new DB_Contenido;
	$db->query($sql);
	
	if (!$db->next_record())
	{
		return false;	
	}
	
	$idtpl = $db->f("idtpl");
	
	$nextidtplcfg = $db->nextid($cfg["tab"]["tpl_conf"]);
	$created = date("Y-m-d H:i:s");
	
	$sql = "INSERT INTO ".$cfg["tab"]["tpl_conf"] . " (idtplcfg, idtpl, created) VALUES ('".Contenido_Security::toInteger($nextidtplcfg)."', '".Contenido_Security::toInteger($idtpl)."', '".Contenido_Security::escapeDB($created, $db)."')";
	$db->query($sql);
	
	return $nextidtplcfg;
}

function conCopyContainerConf ($srcidtplcfg, $dstidtplcfg)
{
	global $cfg;
	
	$db = new DB_Contenido;
	$sql = "SELECT number, container FROM ".$cfg["tab"]["container_conf"] . " WHERE idtplcfg = '".Contenido_Security::toInteger($srcidtplcfg)."'";
	
	$db->query($sql);
	
	while ($db->next_record())
	{
		$val[$db->f("number")] = $db->f("container");
	}
	
	if (!is_array($val))
	{
		return false;
	}
	
	foreach ($val as $key => $value)
	{
		$nextidcontainerc = $db->nextid($cfg["tab"]["container_conf"]);
		
		$sql = "INSERT INTO ".$cfg["tab"]["container_conf"]." (idcontainerc, idtplcfg, number, container) VALUES ('".Contenido_Security::toInteger($nextidcontainerc)."', '".Contenido_Security::toInteger($dstidtplcfg)."',
                '".Contenido_Security::toInteger($key)."', '".Contenido_Security::escapeDB($value, $db)."')";
		$db->query($sql);	
	}
	
	return true;
	
}

function conCopyContent ($srcidartlang, $dstidartlang)
{
	global $cfg;
	
	$db = new DB_Contenido;
	
	$sql = "SELECT idtype, typeid, value, version, author FROM ".$cfg["tab"]["content"]." WHERE idartlang = '".Contenido_Security::toInteger($srcidartlang)."'";
	
	$db->query($sql);
	
	$id = 0;
	
	while ($db->next_record())
	{
		$id++;
		$val[$id]["idtype"] = $db->f("idtype");
		$val[$id]["typeid"] = $db->f("typeid");
		$val[$id]["value"] = $db->f("value");
		$val[$id]["version"]  = $db->f("version");
		$val[$id]["author"] = $db->f("author");	
	} 	
	
	if (!is_array($val))
	{
		return false;
	}
	
	foreach ($val as $key => $value)
	{
		$nextid = $db->nextid($cfg["tab"]["content"]);
		$idtype = $value["idtype"];
		$typeid = $value["typeid"];
		$lvalue = $value["value"];
		$version = $value["version"];
		$author = $value["author"];
		$created = date("Y-m-d H:i:s");
		
		$sql = "INSERT INTO ".$cfg["tab"]["content"]
		      ." (idcontent, idartlang, idtype, typeid, value, version, author, created) ".
		      "VALUES ('".Contenido_Security::toInteger($nextid)."', '".Contenido_Security::toInteger($dstidartlang)."', '".Contenido_Security::toInteger($idtype)."', '".Contenido_Security::toInteger($typeid)."',
              '".Contenido_Security::escapeDB($lvalue, $db)."', '".Contenido_Security::escapeDB($version, $db)."', '".Contenido_Security::escapeDB($author, $db)."', '".Contenido_Security::escapeDB($created, $db)."')";
		      
		$db->query($sql);	
		
	}
}

function conCopyArtLang ($srcidart, $dstidart, $newtitle, $bUseCopyLabel = true)
{
	global $cfg, $lang;
	
	$db = new DB_Contenido;
	$db2 = new DB_Contenido;
	
	$sql = "SELECT idartlang, idlang, idtplcfg, title, pagetitle, summary, 
			author, online, redirect, redirect, redirect_url,
			artsort, timemgmt, datestart, dateend, status, free_use_01,
			free_use_02, free_use_03, time_move_cat, time_target_cat,
			time_online_move, external_redirect, locked FROM
			".$cfg["tab"]["art_lang"]." WHERE idart = '".Contenido_Security::toInteger($srcidart)."' AND idlang='".Contenido_Security::toInteger($lang)."'";
	$db->query($sql);
		
	while ($db->next_record())
	{
		
		$nextid = $db2->nextid($cfg["tab"]["art_lang"]);
		/* Copy the template configuration */
		if ($db->f("idtplcfg") != 0)
		{
			$newidtplcfg = conCopyTemplateConfiguration($db->f("idtplcfg"));
		 	conCopyContainerConf($db->f("idtplcfg"), $newidtplcfg);	
		}
		
		conCopyContent($db->f("idartlang"), $nextid);		
		
		$idartlang = $nextid;
		$idart = $dstidart;
		$idlang = $db->f("idlang");
		$idtplcfg = $newidtplcfg;
		
		if ($newtitle != "")
		{
			$title = sprintf($newtitle, addslashes($db->f("title")));
		} else {
			if ($bUseCopyLabel == true) {
				$title = sprintf(i18n("%s (Copy)"), addslashes($db->f("title"))); 
			} else {
				$title = addslashes($db->f("title")); 
			}
		}
		$pagetitle = addslashes($db->f("pagetitle"));
		$summary = addslashes($db->f("summary"));
		$created = date("Y-m-d H:i:s");
		$author = $db->f("author");
		$online = 0;
		$redirect = $db->f("redirect");
		$redirecturl = $db->f("redirect_url");
		$artsort = $db->f("artsort");
		$timemgmt = $db->f("timemgmt");
		$datestart = $db->f("datestart");
		$dateend = $db->f("dateend");
		$status = $db->f("status");
		$freeuse01 = $db->f("free_use_01");
		$freeuse02 = $db->f("free_use_02");
		$freeuse03 = $db->f("free_use_03");
		$timemovecat = $db->f("time_move_cat");
		$timetargetcat = $db->f("time_target_cat");
		$timeonlinemove = $db->f("time_online_move");
		$externalredirect = $db->f("external_redirect");
		$locked = $db->f("locked");
		
		$sql = "INSERT INTO ".$cfg["tab"]["art_lang"]."
				(idartlang, idart, idlang, idtplcfg, title,
				pagetitle, summary, created, lastmodified,
				author, online, redirect, redirect_url,
				artsort, timemgmt, datestart, dateend, 
				status, free_use_01, free_use_02, free_use_03,
				time_move_cat, time_target_cat, time_online_move,
				external_redirect, locked) VALUES ('".Contenido_Security::toInteger($idartlang)."',
				'".Contenido_Security::toInteger($idart)."',
				'".Contenido_Security::toInteger($idlang)."',
				'".Contenido_Security::toInteger($idtplcfg)."',
				'".Contenido_Security::escapeDB($title, $db2)."',
				'".Contenido_Security::escapeDB($pagetitle, $db2)."',
				'".Contenido_Security::escapeDB($summary, $db2)."',
				'".Contenido_Security::escapeDB($created, $db2)."',
				'".Contenido_Security::escapeDB($created, $d2b)."',                
				'".Contenido_Security::escapeDB($author, $db2)."',
				'".Contenido_Security::toInteger($online)."',
				'".Contenido_Security::escapeDB($redirect, $db2)."',
				'".Contenido_Security::escapeDB($redirecturl, $db2)."',
				'".Contenido_Security::toInteger($artsort)."',
				'".Contenido_Security::toInteger($timemgmt)."',
				'".Contenido_Security::escapeDB($datestart, $db2)."',
				'".Contenido_Security::escapeDB($dateend, $db2)."',
				'".Contenido_Security::toInteger($status)."',
				'".Contenido_Security::toInteger($freeuse01)."',
				'".Contenido_Security::toInteger($freeuse02)."',
				'".Contenido_Security::toInteger($freeuse03)."',
				'".Contenido_Security::toInteger($timemovecat)."',
				'".Contenido_Security::toInteger($timetargetcat)."',
				'".Contenido_Security::toInteger($timeonlinemove)."',
				'".Contenido_Security::escapeDB($externalredirect, $db)."',
				'".Contenido_Security::toInteger($locked)."')";

		$db2->query($sql);
		
        // execute CEC hook
        CEC_Hook::execute('Contenido.Article.conCopyArtLang_AfterInsert', array(
            'idartlang' => Contenido_Security::toInteger($idartlang), 
            'idart'     => Contenido_Security::toInteger($idart), 
            'idlang'    => Contenido_Security::toInteger($idlang),
            'idtplcfg'  => Contenido_Security::toInteger($idtplcfg), 
            'title'     => Contenido_Security::escapeDB($title, $db2)
        ));

		/* Copy meta tags */
		$sql = "SELECT idmetatype, metavalue FROM ".$cfg["tab"]["meta_tag"]." WHERE idartlang = '".Contenido_Security::toInteger($db->f("idartlang"))."'";
		$db->query($sql);
		
		while ($db->next_record())
		{
			$nextidmetatag = $db2->nextid($cfg["tab"]["meta_tag"]);
			$metatype = $db->f("idmetatype");
			$metavalue = $db->f("metavalue");
			$sql = "INSERT INTO ".$cfg["tab"]["meta_tag"]."
						(idmetatag, idartlang, idmetatype, metavalue)
						VALUES
						('".Contenido_Security::toInteger($nextidmetatag)."', '".Contenido_Security::toInteger($idartlang)."', '".Contenido_Security::toInteger($metatype)."', '".Contenido_Security::escapeDB($metavalue, $db2)."')";
			$db2->query($sql);
		}
		
		/* Update keyword list for new article */
		conMakeArticleIndex ($idartlang, $idart);
	}			
}

function conCopyArticle ($srcidart, $targetcat = 0, $newtitle = "", $bUseCopyLabel = true)
{
	global $cfg, $_cecRegistry;
	
	$db = new DB_Contenido;
	$db2 = new DB_Contenido;
	
	$sql = "SELECT idclient FROM ".$cfg["tab"]["art"] ." WHERE idart = '".Contenido_Security::toInteger($srcidart)."'";
	
	$db->query($sql); 

	if (!$db->next_record())
	{
		return false;
	}
	
	$idclient = $db->f("idclient");
	$dstidart = $db->nextid($cfg["tab"]["art"]);
	
	$sql = "INSERT INTO ".$cfg["tab"]["art"]." (idart, idclient) VALUES ('".Contenido_Security::toInteger($dstidart)."', '".Contenido_Security::toInteger($idclient)."')";
	$db->query($sql);
	
	conCopyArtLang($srcidart, $dstidart, $newtitle, $newtitle);
	
	// Update category relationship
	$sql = "SELECT idcat, status FROM ".$cfg["tab"]["cat_art"]." WHERE idart = '".Contenido_Security::toInteger($srcidart)."'";
	$db->query($sql);
	
	while ($db->next_record())
	{
		$nextid = $db2->nextid($cfg["tab"]["cat_art"]);
		
		// These are the insert values
		$aFields = Array("idcatart" => Contenido_Security::toInteger($nextid),
						 "idcat" => ($targetcat != 0) ? Contenido_Security::toInteger($targetcat) : Contenido_Security::toInteger($db->f("idcat")),
						 "idart" => Contenido_Security::toInteger($dstidart),
						 "is_start" => 0,
						 "status" => ($db->f("status") != '') ? Contenido_Security::toInteger($db->f("status")) : 0,
						 "createcode" => 1);
						 
		$sql = "INSERT INTO ".$cfg["tab"]["cat_art"]." (".implode(", ", array_keys($aFields)).") VALUES (".implode(", ", array_values($aFields)).");";
		$db2->query($sql);
		
		if ($targetcat != 0) { // If true, exit while routine, only one category entry is needed
			break;
		}
	}
   	
	# Contenido Extension Chain
	# @see docs/techref/plugins/Contenido Extension Chainer.pdf
	#
	# Usage:
	# One could define the file contenido/includes/config.local.php
	# with following code.
	#
	# global $_cecRegistry;
	# cInclude("plugins", "extension/extenison.php");
	# $_cecRegistry->addChainFunction("Contenido.Content.CopyArticle", "AdditionalFunction1");
	# 
	# If function "AdditionalFunction1" is defined in file extension.php, it would be called via
	# $chainEntry->execute($srcidart, $dstidart);
	 
	$iterator = $_cecRegistry->getIterator("Contenido.Content.CopyArticle");
	
	while ($chainEntry = $iterator->next())
	{
		$chainEntry->execute($srcidart, $dstidart);
	}
	
	return $dstidart;
	 
}

function conGetTopmostCat($idcat, $minLevel = 0)
{
    global $cfg, $client, $lang;

    $db = new DB_Contenido;
    
    $sql = "SELECT
                a.name AS name,
                a.idcat AS idcat,
                b.parentid AS parentid,
				c.level AS level
            FROM
                ".$cfg["tab"]["cat_lang"]." AS a,
                ".$cfg["tab"]["cat"]." AS b,
				".$cfg["tab"]["cat_tree"]." AS c
            WHERE
                a.idlang    = '".Contenido_Security::toInteger($lang)."' AND
                b.idclient  = '".Contenido_Security::toInteger($client)."' AND
                b.idcat     = '".Contenido_Security::toInteger($idcat)."' AND
				c.idcat		= b.idcat AND
                a.idcat     = b.idcat";
                
    $db->query($sql);
    $db->next_record();

    $name       = $db->f("name");
    $parentid   = $db->f("parentid");
	$thislevel = $db->f("level");
	
    if ( $parentid != 0 && $thislevel >= $minLevel) {
        return conGetTopmostCat($parentid, $minLevel);
    } else {
		return $idcat;
	}
}


function conSyncArticle ($idart, $srclang, $dstlang)
{
	global $cfg;
	
	$db = new DB_Contenido;
	$db2 = new DB_Contenido;
	
	#Check if article has already been synced to target language
	$sql = "SELECT * FROM ".$cfg['tab']['art_lang']." WHERE (idart = ".Contenido_Security::toInteger($idart).") AND (idlang= ".Contenido_Security::toInteger($dstlang).")";
	$db2->query($sql);

	$sql = "SELECT idartlang, idart, idlang, idtplcfg, title, urlname, pagetitle,
				   summary, created, lastmodified, redirect, redirect_url,
				   artsort, status, external_redirect
			FROM
				".$cfg["tab"]["art_lang"]."
			WHERE
				idart = '".Contenido_Security::toInteger($idart)."' AND idlang = '".Contenido_Security::toInteger($srclang)."'";
	$db->query($sql);
	
	if ($db->next_record() && ($db2->num_rows() == 0) ) {
		$newidartlang = $db2->nextid($cfg["tab"]["art_lang"]);
		
		if ($db->f("idtplcfg") != 0)
		{
			$newidtplcfg = tplcfgDuplicate($db->f("idtplcfg")); 	
		} else {
			$newidtplcfg = 0;	
		}
		
		
		$idartlang = $db->f("idartlang");
		$idart = $db->f("idart");
		$idlang = $db->f("idlang");
		$title = addslashes($db->f("title"));
		$urlname = addslashes($db->f("urlname"));
		$pagetitle = addslashes($db->f("pagetitle"));
		$summary = addslashes($db->f("summary"));
		$created = $db->f("created");
		$lastmodified = $db->f("lastmodified");
		$redirect = $db->f("redirect");
		$redirect_url = $db->f("redirect_url");
		$artsort = $db->f("artsort");
		$status = $db->f("status");
		$external_redirect = $db->f("external_redirect");
		
		$sql = "INSERT INTO
					".$cfg["tab"]["art_lang"]."
				(idartlang, idart, idlang, idtplcfg, title,urlname,
				 pagetitle, summary, created, lastmodified,
				 author, modifiedby, online, redirect, redirect_url,
				 artsort, status, external_redirect)
				VALUES
				('".Contenido_Security::toInteger($newidartlang)."', '".Contenido_Security::toInteger($idart)."',
				'".Contenido_Security::toInteger($dstlang)."', '".Contenido_Security::toInteger($newidtplcfg)."',
				'".Contenido_Security::escapeDB($title, $db2)."',
				'".Contenido_Security::escapeDB($urlname, $db2)."',
				'".Contenido_Security::escapeDB($pagetitle, $db2)."',
				'".Contenido_Security::escapeDB($summary, $db2)."',
				'".Contenido_Security::escapeDB($created, $db2)."',
				'".Contenido_Security::escapeDB($lastmodified, $db2)."',
				'".Contenido_Security::escapeDB($author, $db2)."',
				'".Contenido_Security::escapeDB($modifiedby, $db2)."',
				'".Contenido_Security::toInteger($online)."',
				'".Contenido_Security::escapeDB($redirect, $db2)."',
				'".Contenido_Security::escapeDB($redirect_url, $db2)."',
				'".Contenido_Security::toInteger($artsort)."',
				'".Contenido_Security::toInteger($status)."',
				'".Contenido_Security::escapeDB($external_redirect, $db2)."')";
		$db2->query($sql);

        // execute CEC hook
        $param['src_art_lang']  = $db->Record;
        $param['dest_art_lang'] = $db2->Record;
        $param['dest_art_lang']['idartlang'] = Contenido_Security::toInteger($newidartlang);
        $param['dest_art_lang']['idlang']    = Contenido_Security::toInteger($dstlang);
        $param['dest_art_lang']['idtplcfg']  = Contenido_Security::toInteger($newidtplcfg);
        CEC_Hook::execute('Contenido.Article.conSyncArticle_AfterInsert', $param);

		/* Copy content */
		$sql = "SELECT 
					idtype, typeid, value, version, author,
					created, lastmodified
				FROM
					".$cfg["tab"]["content"]."
				WHERE
					idartlang = '".Contenido_Security::toInteger($idartlang)."'";

		$db->query($sql);
		
		while ($db->next_record())
		{
			$newidcontent = $db2->nextid($cfg["tab"]["content"]);
			$idtype = $db->f("idtype");
			$typeid = $db->f("typeid");
			$value = $db->f("value");
			$version = $db->f("version");
			$author = $db->f("author");
			$created = $db->f("created");
			$lastmodified = $db->f("lastmodified");

			$sql = "INSERT INTO	
						".$cfg["tab"]["content"]."
					(idcontent, idartlang, idtype, typeid,
					 value, version, author, created, lastmodified)
					VALUES
					('".Contenido_Security::toInteger($newidcontent)."', '".Contenido_Security::toInteger($newidartlang)."',
					'".Contenido_Security::toInteger($idtype)."', '".Contenido_Security::toInteger($typeid)."',
					'".Contenido_Security::escapeDB($value, $db2)."',
					'".Contenido_Security::escapeDB($version, $db2)."',
					'".Contenido_Security::escapeDB($author, $db2)."',
					'".Contenido_Security::escapeDB($created, $db2)."',
					'".Contenido_Security::escapeDB($lastmodified, $db2)."')";

			$db2->query($sql);
		}
		
		/* Copy meta tags */
		$sql = "SELECT idmetatype, metavalue FROM ".$cfg["tab"]["meta_tag"]." WHERE idartlang = '$idartlang'";
		$db->query($sql);
		
		while ($db->next_record())
		{
			$nextidmetatag = $db2->nextid($cfg["tab"]["meta_tag"]);
			$metatype = $db->f("idmetatype");
			$metavalue = $db->f("metavalue");
			$sql = "INSERT INTO ".$cfg["tab"]["meta_tag"]."
						(idmetatag, idartlang, idmetatype, metavalue)
						VALUES
						('".Contenido_Security::toInteger($nextidmetatag)."', '".Contenido_Security::toInteger($newidartlang)."', '".Contenido_Security::toInteger($metatype)."', '".Contenido_Security::escapeDB($metavalue, $db2)."')";
			$db2->query($sql);
		}
				 
		
	}
}

function isStartArticle ($idartlang, $idcat, $idlang, $db = null)
{
	global $cfg;
	
	if (!is_object($db)) {
		$db = new DB_Contenido;
	}
	
	if ($cfg["is_start_compatible"] == true)
	{
		$sql = "SELECT idart FROM ".$cfg["tab"]["art_lang"]." WHERE idartlang='".Contenido_Security::toInteger($idartlang)."'";
		$db->query($sql);
		
		if (!$db->next_record())
		{
			return false;
		} else {
			$idart = $db->f("idart");
			$sql = "SELECT is_start FROM ".$cfg["tab"]["cat_art"]." WHERE is_start = '1' AND idcat='".Contenido_Security::toInteger($idcat)."' AND idart='".Contenido_Security::toInteger($idart)."'";
			$db->query($sql);
			
			if ($db->next_record())
			{
				return true;	
			} else {
				return false;
			}
		}
	} else {
    	$sql = "SELECT startidartlang FROM ".$cfg["tab"]["cat_lang"]."
    			WHERE startidartlang='".Contenido_Security::toInteger($idartlang)."' AND idcat='".Contenido_Security::toInteger($idcat)."' AND idlang='".Contenido_Security::toInteger($idlang)."'";
    			
    	$db->query($sql);
    	
    	if ($db->next_record())
    	{
    		return true;
    	} else {
    		return false;
    	}
	}		
}

/**
 * Returns all categories in which the given article is in. 
 *
 * @param idart int Article ID
 * @param db    object Optional; if specified, uses the given db object
 * @return array Flat array which contains all category id's
 */
function conGetCategoryAssignments ($idart, $db = false)
{
	global $cfg;
	
	if ($db === false)
	{
		$db = new DB_Contenido;	
	}
	
	$sql = "SELECT idcat FROM ".$cfg["tab"]["cat_art"]." WHERE idart = '".Contenido_Security::toInteger($idart)."'";
	$db->query($sql);
	
	$categories = array();
	
	while ($db->next_record())
	{
		$categories[] = $db->f("idcat");	
	}
	
	return ($categories);
}
?>
