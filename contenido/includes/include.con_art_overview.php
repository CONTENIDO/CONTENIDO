
<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * Displays all articles of a category
 * 
 * Requirements: 
 * @con_php_req 5.0
 * 
 *
 * @package    Contenido Backend includes
 * @version    1.0.1
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-01-26
 *   modified 2005-06-23, Andreas Lindner
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *
 *   $Id: include.con_art_overview.php 344 2008-06-27 10:23:17Z frederic.schneider $:
 * }}
 * 
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

cInclude("includes","functions.tpl.php");
cInclude("includes","functions.str.php");
cInclude("classes", "class.todo.php");
cInclude("includes", "functions.pathresolver.php");
$firstMark = false;
$db2 = new DB_Contenido;

$idcat	= ( isset($_GET['idcat']) && is_numeric($_GET['idcat'])) ? $_GET['idcat'] : -1;
$next	= ( isset($_GET['next']) && is_numeric($_GET['next']) && $_GET['next'] > 0) ? $_GET['next'] : 0;

$dateformat = getEffectiveSetting("backend", "timeformat_date", "Y-m-d");
$debug = false;
$templateDescription = '';

if (!isset($syncfrom))
{
	$syncfrom = -1;
}

$syncoptions = $syncfrom;

if ($action == "con_duplicate")
{
	$newidartlang = conCopyArticle($duplicate, $idcat);
}

if ($action == "con_syncarticle")
{
	/* Verify that the category is available in this language */
	$sql = "SELECT idcatlang FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat='".Contenido_Security::toInteger($idcat)."' AND idlang='".Contenido_Security::toInteger($lang)."'";
	$db->query($sql);
	if ($db->next_record())
	{
		conSyncArticle($syncarticle, $sourcelanguage, $lang);
	} else {
		strSyncCategory($idcat, $sourcelanguage, $lang);
		conSyncArticle($syncarticle, $sourcelanguage, $lang);
	}
}

/* Which columns to display? */
$listColumns = array(	"start" => i18n("Article"),
						"title" => i18n("Title"),
						"changeddate" => i18n("Changed"),
						"publisheddate" => i18n("Published"),
						"sortorder" => i18n("Sort order"),
						"template" => i18n("Template"),
						"actions" => i18n("Actions"));

/* Which actions to display? */
$actionList = array(	"online",
						"duplicate",
						"locked",
						"todo",
						"delete",
						"usetime");


/* Call chains to process the columns and the action list */
$_cecIterator = $_cecRegistry->getIterator("Contenido.ArticleList.Columns");

if ($_cecIterator->count() > 0)
{
	while ($chainEntry = $_cecIterator->next())
	{
		$newColumnList = $chainEntry->execute($listColumns);
	  
		if (is_array($newColumnList))
		{
			$listColumns = $newColumnList;
		}
	}
}

$_cecIterator = $_cecRegistry->getIterator("Contenido.ArticleList.Actions");

if ($_cecIterator->count() > 0)
{
	while ($chainEntry = $_cecIterator->next())
	{
		$newActionList = $chainEntry->execute($actionList);
	  
		if (is_array($newActionList))
		{
			$actionList = $newActionList;
		}
	}
}

$cat_idtpl = 0;

if ( is_numeric($idcat) && ($idcat >= 0)) {
	// Saving sort and elements per page user settings (if specified)
	// Should be changed to User->setProperty... someday
	if (isset($sort))
	{
		$currentuser->setUserProperty("system","sortorder-idlang-$lang-idcat-$idcat",$sort);
	}

	if (isset($elemperpage) && is_numeric($elemperpage))
	{
		$currentuser->setUserProperty("system","elemperpage-idlang-$lang-idcat-$idcat", $elemperpage);

	} else {
		$elemperpage = $currentuser->getUserProperty("system","elemperpage-idlang-$lang-idcat-$idcat");

		if (!is_numeric($elemperpage))
		{
			$elemperpage = 25;
		}
	}

	$col = new InUseCollection;

	if (((  $idcat == 0 ||
	$perm->have_perm_area_action("con")) && $perm->have_perm_item("str", $idcat))   ||
	$perm->have_perm_area_action("con", "con_makestart")                    ||
	$perm->have_perm_area_action("con", "con_makeonline")                   ||
	$perm->have_perm_area_action("con", "con_deleteart")                    ||
	$perm->have_perm_area_action("con", "con_tplcfg_edit")                  ||
	$perm->have_perm_area_action("con", "con_lock") 	                    ||
	$perm->have_perm_area_action("con", "con_makecatonline")                ||
	$perm->have_perm_area_action("con", "con_changetemplate")               ||
	$perm->have_perm_area_action("con_editcontent", "con_editart")          ||
	$perm->have_perm_area_action("con_editart", "con_edit")                 ||
	$perm->have_perm_area_action("con_editart", "con_newart")               ||
	$perm->have_perm_area_action("con_editart", "con_saveart")				||
	$perm->have_perm_area_action("con_tplcfg", "con_tplcfg_edit")           ||
	$perm->have_perm_area_action_item("con", "con_makestart", $idcat)       ||
	$perm->have_perm_area_action_item("con", "con_makeonline", $idcat)      ||
	$perm->have_perm_area_action_item("con", "con_deleteart", $idcat)       ||
	$perm->have_perm_area_action_item("con", "con_tplcfg_edit", $idcat)     ||
	$perm->have_perm_area_action_item("con", "con_lock", $idcat)            ||
	$perm->have_perm_area_action_item("con", "con_makecatonline", $idcat)   ||
	$perm->have_perm_area_action_item("con", "con_changetemplate", $idcat)  ||
	$perm->have_perm_area_action_item("con_editcontent", "con_editart", $idcat)       ||
	$perm->have_perm_area_action_item("con_editart", "con_edit", $idcat)    ||
	$perm->have_perm_area_action_item("con_editart", "con_newart", $idcat)  ||
	$perm->have_perm_area_action_item("con_tplcfg", "con_tplcfg_edit",$idcat) ||
	$perm->have_perm_area_action_item("con_editart", "con_saveart", $idcat)) {

		$sort = $currentuser->getUserProperty("system","sortorder-idlang-$lang-idcat-$idcat");

		$sql  = "SELECT
					a.idart AS idart,
					a.idlang AS idlang,
					a.idartlang AS idartlang,
					a.title AS title,
					c.idcat AS idcat,
					{ISSTART}
					c.idcatart AS idcatart,
					a.idtplcfg AS idtplcfg,
					a.published AS published,
					a.online AS online,
					a.created AS created,
					a.lastmodified AS lastmodified,
					a.timemgmt AS timemgmt,
					a.datestart AS datestart,
					a.dateend AS dateend,
					a.artsort AS artsort,
					a.redirect AS redirect,
					a.locked AS locked
				 FROM
					".$cfg["tab"]["art_lang"]." AS a,
					".$cfg["tab"]["art"]." AS b,
					".$cfg["tab"]["cat_art"]." AS c
				 WHERE
					(a.idlang   = '".$lang."' {SYNCOPTIONS}) AND
					a.idart     = b.idart AND
					b.idclient  = '".$client."' AND
					b.idart     = c.idart AND
					c.idcat     = '".$idcat."'";
			
		// Simple SQL statement to get the number of articles
		$sql_count =
				"SELECT 
					COUNT(*) AS article_count
				 FROM
					".$cfg["tab"]["art_lang"]." AS a,
					".$cfg["tab"]["art"]." AS b,
					".$cfg["tab"]["cat_art"]." AS c
				 WHERE
					(a.idlang   = '".Contenido_Security::toInteger($lang)."' {SYNCOPTIONS}) AND
					a.idart     = b.idart AND
					b.idclient  = '".Contenido_Security::toInteger($client)."' AND
					b.idart     = c.idart AND
					c.idcat     = '".Contenido_Security::toInteger($idcat)."'";

		if ($cfg["is_start_compatible"] == true)
		{
			$sql = str_replace("{ISSTART}", "c.is_start AS is_start,", $sql);
		} else {
			$sql = str_replace("{ISSTART}", "", $sql);
		}

		if ($syncoptions == -1)
		{
			$sql 		= str_replace("{SYNCOPTIONS}", "", $sql);
			$sql_count	= str_replace("{SYNCOPTIONS}", "", $sql_count);
		} else {
			$sql 		= str_replace("{SYNCOPTIONS}", "OR a.idlang = '".$syncoptions."'", $sql);
			$sql_count	= str_replace("{SYNCOPTIONS}", "OR a.idlang = '".$syncoptions."'", $sql_count);
		}

		# Article sort
		switch ($sort)
		{
			case 2:
				$sql .= " ORDER BY a.lastmodified DESC";
				break;
			case 3:
        		$sql .= " ORDER BY a.published DESC, a.lastmodified DESC";
				break;
			case 4:
				$sql .= " ORDER BY a.artsort ASC";
				break;
			default:
				// Default sort order
				$sql .= " ORDER BY a.title ASC";
				$sort = 1;
		}


		# Getting article count, if necessary
		if ($elemperpage > 0)
		{
			$db->query($sql_count);
			$db->next_record();
			$iArticleCount = $db->f("article_count");
			 
			# If not beyond scope, limit
			if ($iArticleCount == 0)
			{
				$next = 0;
			} else if ($next >= $iArticleCount) {
				$next = (ceil($iArticleCount / $elemperpage) - 1) * $elemperpage;
			}
			$sql .= " LIMIT $next, $elemperpage";
		} else {
			$iArticleCount = 0; // Will be used to "hide" the browsing area
		}

		# Debug info
		if ( $debug ) {
			echo "<pre>";
			echo $sql;
			echo "</pre>";
		}

		# Getting data
		$db->query($sql);

		# Reset Template
		$tpl->reset();

		# No article
		$no_article = true;

        $aArticles = Array();

        while ($db->next_record() ) {
        	$sItem = "k" . $db->f("idart");
        	
			if ($db->f("idlang") == $lang || !array_key_exists($sItem, $aArticles)) {
				$aArticles[$sItem]["idart"]	= $db->f("idart");
				$aArticles[$sItem]["idlang"]	= $db->f("idlang");
				$aArticles[$sItem]["idartlang"]	= $db->f("idartlang");
				$aArticles[$sItem]["title"]	= $db->f("title");
				if ($cfg["is_start_compatible"]	== true) {
					$aArticles[$sItem]["is_start"] = $db->f("is_start");
				} else {
					$aArticles[$sItem]["is_start"] = isStartArticle($db->f("idartlang"), $idcat, $lang);
				}
	
				$aArticles[$sItem]["idcatart"]	= $db->f("idcatart");
				$aArticles[$sItem]["idtplcfg"]	= $db->f("idtplcfg");
				$aArticles[$sItem]["published"]	= $db->f("published");
				$aArticles[$sItem]["online"]	= $db->f("online");
				$aArticles[$sItem]["created"]	= $db->f("created");
				$aArticles[$sItem]["idcat"]		= $db->f("idcat");
				$aArticles[$sItem]["lastmodified"] = $db->f("lastmodified");
				$aArticles[$sItem]["timemgmt"]	= $db->f("timemgmt");
				$aArticles[$sItem]["datestart"]	= $db->f("datestart");
				$aArticles[$sItem]["dateend"]	= $db->f("dateend");
				$aArticles[$sItem]["artsort"]	= $db->f("artsort");
				$aArticles[$sItem]["locked"]	= $db->f("locked");
				$aArticles[$sItem]["redirect"]	= $db->f("redirect");
			}
        }

        $artlist = array();
        	
		foreach ($aArticles as $sart) {
			$dyn_cnt++;
			$idart      = $sart["idart"];
			$idlang		= $sart["idlang"];
			 
			$idtplcfg   = $sart["idtplcfg"];
			$idartlang  = $sart["idartlang"];
			$lidcat      = $sart["idcat"];
			$idcatlang  = 0;
			$idart      = $sart["idart"];
			$published    = $sart["published"];
			$online     = $sart["online"];

			$is_start   = $sart["is_start"];
			 
			$idcatart   = $sart["idcatart"];
			$created    = $sart["created"];
			$modified   = $sart["lastmodified"];
			$title      = htmlspecialchars($sart["title"]);
			$timemgmt   = $sart["timemgmt"];
			$datestart  = $sart["datestart"];
			$dateend    = $sart["dateend"];
			$sortkey    = $sart["artsort"];
			$locked     = $sart["locked"];
                $redirect   = $sart["redirect"];

			$published = ($published != '0000-00-00 00:00:00') ? date($dateformat,strtotime($published)) : i18n("not yet published");
			$created = date($dateformat,strtotime($created));
			$modified = date($dateformat,strtotime($modified));
			$alttitle = "idart".'&#58; '.$idart.' '."idcatart".'&#58; '.$idcatart.' '."idartlang".'&#58; '.$idartlang;

			if (($obj = $col->checkMark("article", $idartlang)) === false)
			{
				$inUse = false;
			} else {
				$vuser = new User;
				$vuser->loadUserByUserID($obj->get("userid"));
				$inUseUser = $vuser->getField("username");
				$inUseUserRealName = $vuser->getField("realname");
					
				$inUse = true;
				$title = $title . " (" . i18n("Article is in use").")";
				$alttitle = sprintf(i18n("Article in use by %s (%s)"), $inUseUser, $inUseUserRealName). " ". $alttitle;
			}

			$bgcolor = $cfg["color"]["table_light"];
			if ($idlang != $lang)
			{
				$bgcolor = ( is_int($dyn_cnt / 2) ) ? $cfg["color"]["table_light_sync"] : $cfg["color"]["table_dark_sync"];
			}

			/* Id of the row,
			 stores informations about
			 the article and category */
			$tmp_rowid  = $idart."-".$idartlang."-".$lidcat."-".$idcatlang."-".$idcatart."-".$idlang;
			$tpl->set('d', 'ROWID', $tmp_rowid);

			$colitem[$tmp_rowid] = $bgcolor;
			# Backgroundcolor of the table row
			$tpl->set('d', 'BGCOLOR', $bgcolor);

			# Article Title
			if ($perm->have_perm_area_action( "con_editcontent", "con_editart" ) ||
			$perm->have_perm_area_action_item( "con_editcontent", "con_editart" ,$idcat) )
			{
				if ($idlang != $lang)
				{
					$tmp_alink = $sess->url("main.php?area=con_editcontent&action=con_editart&changeview=prev&idartlang=$idartlang&idart=$idart&idcat=$idcat&frame=$frame&tmpchangelang=$idlang");
					$titlelink = '<a href="'.$tmp_alink.'" title="'.$alttitle.'">'.$title.'</a>';
				} else {
					$tmp_alink = $sess->url("main.php?area=con_editcontent&action=con_editart&changeview=edit&idartlang=$idartlang&idart=$idart&idcat=$idcat&frame=$frame");
					$titlelink = '<a href="'.$tmp_alink.'" title="'.$alttitle.'">'.$title.'</a>';
				}
			} else {
				$tmp_alink = "";
				$titlelink = $title;
			}

			if ($timemgmt == "1")
			{
				$sql = "SELECT NOW() AS TIME";
				 
				$db3 = new DB_Contenido;
				 
				$db3->query($sql);
				$db3->next_record();

				$starttimestamp = strtotime($datestart);
				$endtimestamp = strtotime($dateend);
				$nowtimestamp = strtotime($db3->f("TIME"));
				 
				if (($nowtimestamp < $endtimestamp) && ($nowtimestamp > $starttimestamp))
				{
					$usetime = '<img src="images/but_time_2.gif" alt="Artikel mit Zeitsteuerung online" title="Artikel mit Zeitsteuerung online" style="margin-left:3px;">';
				} else {
					$usetime = '<img src="images/but_time_1.gif" alt="Artikel mit Zeitsteuerung offline" title="Artikel mit Zeitsteuerung offline" style="margin-left:3px;">';
				}
			} else {
				$usetime = "";
			}

			# Article Title
			if (($perm->have_perm_area_action( "con", "con_lock" ) ||
			$perm->have_perm_area_action_item( "con", "con_lock" ,$idcat)) && $inUse == false )
			{
				if ($locked == 1)
				{
					$lockimg = 'images/article_locked.gif';
					$lockalt = i18n("Unfreeze article");
				} else {
					$lockimg = 'images/article_unlocked.gif';
					$lockalt = i18n("Freeze article");
				}
				$tmp_lock = '<a href="'.$sess->url("main.php?area=con&idcat=$idcat&action=con_lock&frame=4&idart=$idart&next=$next").'" title="'.$lockalt.'"><img src="'.$lockimg.'" title="'.$lockalt.'" alt="'.$lockalt.'" border="0"></a>';
			} else {
				if ($locked == 1)
				{
					$lockimg = 'images/article_locked.gif';
					$lockalt = i18n("Article is frozen");
				} else {
					$lockimg = 'images/article_unlocked.gif';
					$lockalt = i18n("Article is not frozen");
				}
				$tmp_lock = '<img src="'.$lockimg.'" title="'.$lockalt.'" alt="'.$lockalt.'" border="0">';
			}

			if ($idlang != $lang)
			{
				$lockedlink = "";
			} else {
				$lockedlink = $tmp_lock;
			}

			if ($sortkey == "")
			{
				$sortkey = "&nbsp;";
			}

			$tmp_articletitle = $titlelink;

            # Article conf button
            if ($perm->have_perm_area_action("con_editart","con_edit") ||
                $perm->have_perm_area_action_item("con_editart","con_edit",$idcat))
            {
                $tmp_artconf = '<a href="'.$sess->url("main.php?area=con_editart&action=con_edit&frame=4&idart=$idart&idcat=$idcat").'" title="'.i18n("Article properties").'"><img src="'.$cfg["path"]["images"].'but_art_conf2.gif" alt="'.i18n("Article properties").'" title="'.i18n("Article properties").'" border="0"></a>';
            } else {
                $tmp_artconf="";
            }

            $tmp_sync = '';
            if ($idlang != $lang)
            {   
                
                $sql = "SELECT idcatlang FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat='".Contenido_Security::toInteger($idcat)."' AND idlang='".Contenido_Security::toInteger($lang)."'";
                
                $db->query($sql);
                if ($db->next_record())
                {
                    $tmp_sync = '<a href="'.$sess->url("main.php?area=con&action=con_syncarticle&syncarticle=$idart&sourcelanguage=$idlang&frame=4&idcat=$idcat&next=$next").'" title="'.i18n("Copy article to the current language").'"><img src="'.$cfg["path"]["images"].'but_sync_art.gif" alt="'.i18n("Copy article to the current language").'" title="'.i18n("Copy article to the current language").'" border="0" style="margin-left:3px;"></a>';
                    
                } else {
                    $tmp_sync = "";	
                }             
            }
            
			# Article Template
			if ( !is_object($db2) )
			{
				$db2 = new DB_Contenido;
			}

			$sql2 = 
				"SELECT
	      	b.name AS tplname,
					b.idtpl AS idtpl,
					b.description AS description
        FROM
	        ".$cfg["tab"]["tpl_conf"]." AS a,
	        ".$cfg["tab"]["tpl"]." AS b
        WHERE
	        a.idtplcfg = '".Contenido_Security::toInteger($idtplcfg)."' AND
	        a.idtpl = b.idtpl";

			$db2->query($sql2);
			$db2->next_record();

			$a_tplname = $db2->f("tplname");
			$a_idtpl = $db2->f("idtpl");
			
			$templateDescription = $db2->f("description");
			
			
			# Uses Category Template
			if ( 0 == $idtplcfg )
			{
				$a_tplname = "--- ".i18n("None")." ---";
			}

			# Make Startarticle button
			$imgsrc = "isstart";
				 
			if ($is_start == false) {
				$imgsrc.='0';
				} else {
				$imgsrc.='1';
				}
				 
			if (isArtInMultipleUse($idart)) {
				$imgsrc.='m';
			}

			if ((int)$redirect == 1) {
				$imgsrc.='r';
			}
			
			$imgsrc.='.gif';

			if ( ($perm->have_perm_area_action("con","con_makestart") || $perm->have_perm_area_action_item("con","con_makestart",$idcat)) && $idcat != 0) {
				if ( $is_start == false) {
					$tmp_link = '<a href="'.$sess->url("main.php?area=con&amp;idcat=$idcat&amp;action=con_makestart&amp;idcatart=$idcatart&amp;frame=4&is_start=1&amp;next=$next").'" title="'.i18n("Flag as start article").'"><img src="images/'.$imgsrc.'" border="0" title="'.i18n("Flag as start article").'" alt="'.i18n("Flag as start article").'" style="margin-left:3px;"></a>';
				} else {
					$tmp_link = '<a href="'.$sess->url("main.php?area=con&amp;idcat=$idcat&amp;action=con_makestart&amp;idcatart=$idcatart&amp;frame=4&amp;is_start=0&amp;next=$next").'" title="'.i18n("Flag as normal article").'"><img src="images/'.$imgsrc.'" border="0" title="'.i18n("Flag as normal article").'" alt="'.i18n("Flag as normal article").'" style="margin-left:3px;"></a>';
				}
			} else {
				if ($is_start == true) {
					$sTitle = i18n("Start article"); 
			} else {
					$sTitle = i18n("Normal article");
				}
				
				$tmp_img = '<img src="images/'.$imgsrc.'" border="0" title="'.$sTitle.'" alt="'.$sTitle.'" style="margin-left:3px;">'; 
				
				$tmp_link = $tmp_img;
			}

			$tmp_start = $tmp_link;

			# Make copy button
			if ( ($perm->have_perm_area_action("con","con_duplicate") || $perm->have_perm_area_action_item("con","con_duplicate",$idcat)) && $idcat != 0) {
				 
				$imgsrc = "but_copy.gif";
				$tmp_link = '<a href="'.$sess->url("main.php?area=con&idcat=$idcat&action=con_duplicate&duplicate=$idart&frame=4&next=$next").'" title="'.i18n("Duplicate article").'"><img src="images/'.$imgsrc.'" border="0" title="'.i18n("Duplicate article").'" alt="'.i18n("Duplicate article").'" style="margin-left:3px;"></a>';
			} else {
				$tmp_link = "";
			}

			if ($idlang != $lang)
			{
				$duplicatelink = "";
			} else {
				$duplicatelink = $tmp_link;
			}
			 
			$subject = urlencode(sprintf(i18n("Reminder for Article '%s'"),$title));
			$mycatname = "";
			conCreateLocationString($idcat, "&nbsp;/&nbsp;", $mycatname);
			$message = urlencode(sprintf(i18n("Reminder for Article '%s'\nCategory: %s"),$title,$mycatname));

			$todolink = new TODOLink("idart", $idart, $subject, $message);

			# Make On-/Offline button
			if ( $online ) {
				if (($perm->have_perm_area_action("con","con_makeonline") ||
				$perm->have_perm_area_action_item("con","con_makeonline",$idcat)) && ($idcat != 0))
				{
					$tmp_online = '<a href="'.$sess->url("main.php?area=con&idcat=$idcat&action=con_makeonline&frame=4&idart=$idart&next=$next").'" title="'.i18n("Make offline").'"><img src="images/online.gif" title="'.i18n("Make offline").'" alt="'.i18n("Make offline").'" border="0" style="margin-left:3px;"></a>';
				} else {
					$tmp_online = '<img src="images/online.gif" title="'.i18n("Article is online").'" alt="'.i18n("Article is online").'" border="0" style="margin-left:3px;">';
				}
			} else {
				if (($perm->have_perm_area_action("con","con_makeonline") ||
				$perm->have_perm_area_action_item("con","con_makeonline",$idcat)) && ($idcat != 0))
				{
					$tmp_online = '<a href="'.$sess->url("main.php?area=con&idcat=$idcat&action=con_makeonline&frame=4&idart=$idart&next=$next").'" title="'.i18n("Make online").'"><img src="images/offline.gif" title="'.i18n("Make online").'" alt="'.i18n("Make online").'" border="0" style="margin-left:3px;"></a>';
				} else {
					$tmp_online = '<img src="images/offline.gif" title="'.i18n("Article is offline").'" alt="'.i18n("Article is offline").'" border="0" style="margin-left:3px;">';
				}
			}

			if ($idlang != $lang)
			{
				$onlinelink = "";
			} else {
				$onlinelink = $tmp_online;
			}

			# Delete button
			if (($perm->have_perm_area_action("con","con_deleteart") ||
			$perm->have_perm_area_action_item("con","con_deleteart",$idcat)) && $inUse == false)
			{
				$tmp_title = $title;

				if (strlen($tmp_title) > 30)
				{
					$tmp_title = substr($tmp_title, 0, 27) . "...";
				}
				 
				$confirmString = sprintf(i18n("Are you sure to delete the following article:<br><br><b>%s</b>"),htmlspecialchars($tmp_title));
				$tmp_del = '<a href="javascript://" onclick="box.confirm(&quot;'.i18n("Delete article").'&quot;, &quot;'.addslashes($confirmString).'&quot;, &quot;deleteArticle('.$idart.','.$idcat.','.$next.')&quot;)" title="'.i18n("Delete article").'"><img src="images/delete.gif" title="'.i18n("Delete article").'" alt="'.i18n("Delete article").'" border="0" style="margin-left:3px;"></a>';
				 
			} else {
				$tmp_del = "";
			}

			if ($idlang != $lang)
			{
				$deletelink = "";
			} else {
				$deletelink = $tmp_del;
			}

			// DIRECTION
			cInclude('includes', 'functions.lang.php');
			$tpl->set('d', 'DIRECTION', 'dir="' . langGetTextDirection($lang) . '"');

			# Next iteration

			# Articles found
			$no_article = false;
			foreach ($listColumns as $listColumn => $ctitle)
			{
				switch ($listColumn)
				{
					case "start":
						$value = $tmp_start;
						break;
					case "title":
						$value = $tmp_articletitle;
						break;
					case "changeddate":
						$value = $modified;
						break;
					case "publisheddate":
						$value = $published;
						break;
					case "sortorder":
						$value = $sortkey;
						break;
					case "template":
						$value = $a_tplname;
						break;
					case "actions":
						$actions = array();
						foreach ($actionList as $actionItem)
						{
							switch ($actionItem)
							{
								case "todo":
									$actionValue = $todolink->render();
									break;
                                case "artconf":
                                    $actionValue = $tmp_artconf;
                                    break; 
								case "online":
									$actionValue = $onlinelink;
									break;
								case "locked":
									$actionValue = $lockedlink;
									break;
								case "duplicate":
									$actionValue = $duplicatelink;
									break;
								case "delete":
									$actionValue = $deletelink;
									break;
								case "usetime":
									$actionValue = $usetime;
									break;
								default:
									/* Ask chain about the entry */
									$_cecIterator = $_cecRegistry->getIterator("Contenido.ArticleList.RenderAction");
                                    $contents = array();
									if ($_cecIterator->count() > 0)
									{
										while ($chainEntry = $_cecIterator->next())
										{
											$contents[]  = $chainEntry->execute($idcat, $idart, $idartlang, $actionItem);
										}
									}
									$actionValue = implode("", $contents);
									break;
							}

							$actions[] = $actionValue;
						}
                        
                        if ($tmp_sync != '') {
                            $actions[] = $tmp_sync;
                        }
						
						$value = implode("\n", $actions);
						break;
					default:
						$contents = array();
						/* Call chain to retrieve value */
						$_cecIterator = $_cecRegistry->getIterator("Contenido.ArticleList.RenderColumn");
										
						if ($_cecIterator->count() > 0)
						{
							$contents = array();
							while ($chainEntry = $_cecIterator->next())
							{
								$contents[]  = $chainEntry->execute($idcat, $idart, $idartlang, $listColumn);
							}
						}
						$value = implode("", $contents);
				}
				$artlist[$tmp_rowid][$listColumn] = $value;
				$artlist[$tmp_rowid]['templateDescription'] = $templateDescription;
			}
		}


		$headers = array();

		foreach ($listColumns as $key => $listColumn)
		{
			/* Dirty hack to force column widths */
			if ($key == "title" || $listColumn == i18n("Title"))
			{
				$headers[] = '<td width="100%" class="headerbordercell" nowrap="nowrap">'.$listColumn.'</td>';
			} else {
				$headers[] = '<td width="1%" class="headerbordercell" nowrap="nowrap">'.$listColumn.'</td>';
			}
		}

		$tpl->set('s', 'HEADERS', implode("\n", $headers));

		if ($elemperpage > 0 && $iArticleCount > 0)
		{
			for ($i = 1; $i <= ceil($iArticleCount / $elemperpage); $i++)
			{
				$iNext = ($i - 1) * $elemperpage;
				if ($sBrowseLinks !== "") {
					$sBrowseLinks .= "&nbsp;";
				}
				if ($next == $iNext)
				{
					$sBrowseLinks .= $i."\n"; // I'm on the current page, no link
				} else {
					$tmp_alink = $sess->url("main.php?area=con&frame=$frame&idcat=$idcat&next=$iNext");
					$sBrowseLinks .= '<a href="'.$tmp_alink.'">'.$i.'</a>'."\n";
				}
			}
			$tpl->set('s', 'NEXT', $next);
			$tpl->set('s', 'BROWSE', sprintf(i18n("Go to page: %s"), $sBrowseLinks));
		} else {
			$tpl->set('s', 'NEXT', "0");
			$tpl->set('s', 'BROWSE', sprintf(i18n("Go to page: %s"), "1"));
		}
        $tpl->set('s', 'CLICK_ROW_NOTIFICATION', i18n("with click select line for further treatment"));
        

		if (count($artlist) > 0)
		{
			foreach ($artlist as $key2 => $artitem)
			{
				if ($firstMark == false) {
					$script = 'function initTheOne() {
                                   var theOne = document.getElementById("'.$key2.'");
                                   artRow.reset();
		                           artRow.over( theOne );
		                           artRow.click( theOne )
                               }
                               initTheOne()';
					$firstMark = true;
					$tpl->set('s', 'ROWMARKSCRIPT', $script);
				}

				$cells = array();

				foreach ($listColumns as $key => $listColumn)
				{
					// Description for hover effect
					if($key == 'template')
					{
						$templateDescription = $artitem['templateDescription'];
						$descString = '<b>'.$artitem[$key].'</b>';
						
                        $sTemplatename = capiStrTrimHard($artitem[$key], 20);
                        if (strlen($artitem[$key]) > 20) {
                            $cells[] = '<td nowrap="nowrap" class="bordercell" onmouseover="Tip(\''.$descString.'\', BALLOON, true, ABOVE, true);">'.$sTemplatename.'</td>';
                        } else {
                            $cells[] = '<td nowrap="nowrap" class="bordercell">'.$artitem[$key].'</td>';
                        }
                    }
					else
					{
						$cells[] = '<td nowrap="nowrap" class="bordercell">'.$artitem[$key].'</td>';
					}
				}
				$tpl->set('d', 'CELLS', implode("\n", $cells));
				$tpl->set('d', 'BGCOLOR', $colitem[$key2]);
                
                if ($colitem[$key2] == $cfg["color"]["table_dark_sync"] || $colitem[$key2] == $cfg["color"]["table_light_sync"]) {
                    $tpl->set('d', 'CSS_CLASS', 'class="con_sync"');
                } else {
                    $tpl->set('d', 'CSS_CLASS', '');
                }
                
				$tpl->set('d', 'ROWID', $key2);
				$tpl->next();
			}
		}else
		{
			$emptyCell = '<td nowrap="nowrap" class="bordercell" colspan="'.count($listColumns).'">'.i18n("No articles found").'</td>';
			$tpl->set('d', 'CELLS', $emptyCell);
			$tpl->set('s', 'ROWMARKSCRIPT', '');
		}

		# Sortierungs select
		$s_types = array(1 => i18n("Alphabetical"),
		2 => i18n("Last change"),
		3 => i18n("Published date"),
		4 => i18n("Sort key"));

		$tpl2 = new Template;
		$tpl2->set('s', 'NAME', 'sort');
		$tpl2->set('s', 'CLASS', 'text_medium');
		$tpl2->set('s', 'OPTIONS', 'onchange="artSort(this)"');

		foreach ($s_types as $key => $value) {

			$selected = ( $sort == $key ) ? 'selected="selected"' : '';

			$tpl2->set('d', 'VALUE',    $key);
			$tpl2->set('d', 'CAPTION',  $value);
			$tpl2->set('d', 'SELECTED', $selected);
			$tpl2->next();

		}

		$select     = ( !$no_article ) ? $tpl2->generate($cfg["path"]["templates"] . $cfg['templates']['generic_select'], true) : '&nbsp;';
		$caption    = ( !$no_article ) ? i18n("Sort articles:") : '&nbsp;';

		$tpl->set('s', 'ARTSORTCAPTION', $caption);
		$tpl->set('s', 'ARTSORT', $select);

		# Elements per Page select
		$aElemPerPage =   array(0 	=> i18n("All"),
		25 	=> "25",
		50 	=> "50",
		75 	=> "75",
		100	=> "100");

		$tpl2 = new Template;
		$tpl2->set('s', 'NAME', 'sort');
		$tpl2->set('s', 'CLASS', 'text_medium');
		$tpl2->set('s', 'OPTIONS', 'onchange="changeElemPerPage(this)"');

		foreach ($aElemPerPage as $key => $value) {
			$selected = ( $elemperpage == $key ) ? 'selected="selected"' : '';

			$tpl2->set('d', 'VALUE',    $key);
			$tpl2->set('d', 'CAPTION',  $value);
			$tpl2->set('d', 'SELECTED', $selected);
			$tpl2->next();
		}

		$select     = ( !$no_article ) ? $tpl2->generate($cfg["path"]["templates"] . $cfg['templates']['generic_select'], true) : '&nbsp;';
		$caption    = ( !$no_article ) ? i18n("Items per page:") : '&nbsp;';

		$tpl->set('s', 'ELEMPERPAGECAPTION', $caption);
		$tpl->set('s', 'ELEMPERPAGE', $select);

		
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
                    a.idclient = '".Contenido_Security::toInteger($client)."' AND
                    a.idcat    = '".Contenido_Security::toInteger($idcat)."' AND
                    b.idlang   = '".Contenido_Security::toInteger($lang)."' AND
                    b.idcat    = a.idcat AND
                    c.idtplcfg = b.idtplcfg";

		$db->query($sql);

		if ($db->next_record())
		{
			//$foreignlang = false;
			//conCreateLocationString($idcat, "&nbsp;/&nbsp;", $cat_name);
		} 

		// Show path of selected category to user
		prCreateURLNameLocationString($idcat, '/', $cat_name_tmp);
		
		if ($cat_name_tmp != '') {
			$cat_name = '<div class="categorypath">';
			$cat_name .= $cat_name_tmp.'/'.htmlspecialchars($sFistArticleName);
			$cat_name .= "</div>";
		} else {
			$cat_name = '';
		}

		$cat_idtpl = $db->f("idtpl");

		# Hinweis wenn kein Artikel gefunden wurde
		if ( $no_article ) {

			$tpl->set("d", "START",         "&nbsp;");
			$tpl->set("d", "ARTICLE",       i18n("No articles found"));
			$tpl->set("d", "PUBLISHED",     "&nbsp;");
			$tpl->set("d", "LASTMODIFIED",  "&nbsp;");
			$tpl->set("d", "ARTCONF",       "&nbsp;");
			$tpl->set("d", "TPLNAME",       "&nbsp;");
			$tpl->set("d", "LOCKED",       "&nbsp;");
			$tpl->set("d", "DUPLICATE",     "&nbsp;");
			$tpl->set("d", "TPLCONF",       "&nbsp;");
			$tpl->set("d", "ONLINE",        "&nbsp;");
			$tpl->set("d", "DELETE",        "&nbsp;");
			$tpl->set("d", "USETIME",       "&nbsp;");
			$tpl->set("d", "TODO",       "&nbsp;");
			$tpl->set("d", "SORTKEY",       "&nbsp;");

			$tpl->next();
		}

		# Kategorie anzeigen und Konfigurieren button
		/* JL 23.06.03 Check right from "Content" instead of "Category"
		if ($perm->have_perm_area_action("str_tplcfg","str_tplcfg") ||
		$perm->have_perm_area_action_item("str_tplcfg","str_tplcfg",$lidcat)) */

		if (($perm->have_perm_area_action_item( "con", "con_tplcfg_edit", $idcat ) ||
		$perm->have_perm_area_action( "con", "con_tplcfg_edit" )) && $foreignlang == false) {

			if ( 0 != $idcat ) {

				$tpl->set('s', 'CATEGORY', $cat_name);
				$tpl->set('s', 'CATEGORY_CONF', $tmp_img);
				$tpl->set('s', 'CATEGORY_LINK', $tmp_link);
			} else {
				$tpl->set('s', 'CATEGORY', $cat_name);
				$tpl->set('s', 'CATEGORY_CONF', '&nbsp;');
				$tpl->set('s', 'CATEGORY_LINK', '&nbsp;');
			}
		} else {
			$tpl->set('s', 'CATEGORY', $cat_name);
			$tpl->set('s', 'CATEGORY_CONF', '&nbsp;');
			$tpl->set('s', 'CATEGORY_LINK', '&nbsp;');
		}

		# SELF_URL (Variable für das javascript);
		$tpl->set('s', 'SELF_URL', $sess->url("main.php?area=con&frame=4&idcat=$idcat"));

		# New Article link
		if (($perm->have_perm_area_action("con_editart", "con_newart") ||
		$perm->have_perm_area_action_item("con_editart", "con_newart", $idcat)) && $foreignlang == false)
		{
			if ( $idcat != 0 && $cat_idtpl != 0)
			{
				$tpl->set('s', 'NEWARTICLE_TEXT', '<a href="'.$sess->url("main.php?area=con_editart&frame=$frame&action=con_newart&idcat=$idcat").'">'.i18n("Create new article").'</a>');
				$tpl->set('s', 'NEWARTICLE_IMG', '<a href="'.$sess->url("main.php?area=con_editart&frame=$frame&action=con_newart&idcat=$idcat").'" title="'.i18n("Create new article").'"><img src="images/but_art_new.gif" border="0" alt="'.i18n("Create new article").'"></a>');
			}
			else
			{
				$tpl->set('s', 'NEWARTICLE_TEXT', '&nbsp;');
				$tpl->set('s', 'NEWARTICLE_IMG', '&nbsp;');
			}
		}
		else
		{
			$tpl->set('s', 'NEWARTICLE_TEXT', '&nbsp;');
			$tpl->set('s', 'NEWARTICLE_IMG', '&nbsp;');
		}

		$str = "";

		/* Session ID */
		$tpl->set('s', 'SID', $sess->id);

		$tpl->set('s', 'NOTIFICATION', $str);

		# Generate template
		$tpl->generate($cfg['path']['templates'] . $cfg['templates']['con_art_overview']);
	} else {
		$notification->displayNotification("error", i18n("Permission denied"));
	}
} else {
	$tpl->reset();
	$tpl->set('s', 'CONTENTS', '');
	$tpl->generate($cfg['path']['templates'] . $cfg['templates']['blank']);
}

?>