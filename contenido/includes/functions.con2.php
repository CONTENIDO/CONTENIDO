<?php

/*****************************************
* File      :   $RCSfile: functions.con2.php,v $
* Project   :   Contenido
* Descr     :   Contenido Content Functions
*				NOTE: Please add only stuff which is relevant for
*					  the frontend AND the backend. This file should
*					  NOT contain any backend editing functions to
*					  improve frontend performance.
*
* Author    :   Timo A. Hummel
*               
* Created   :   15.12.2003
* Modified  :   $Date: 2007/01/30 20:14:25 $
*
* © four for business AG, www.4fb.de
*
* $Id: functions.con2.php,v 1.35 2007/01/30 20:14:25 bjoern.behrens Exp $
******************************************/


/**
 * Generates the code for one
 * article
 *
 * @param int $idcat Id of category
 * @param int $idart Id of article
 * @param int $lang Id of language
 * @param int $client Id of client
 * @param int $layout Layout-ID of alternate Layout (if false, use associated layout)
 *
 * @author Jan Lengowski <jan.lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
function conGenerateCode($idcat, $idart, $lang, $client, $layout = false)
{
	global $frontend_debug, $_cecRegistry;

	$debug = 0;

	if ($debug)
		echo "conGenerateCode($idcat, $idart, $lang, $client, $layout);<br>";

	global $db, $db2, $sess, $cfg, $code, $cfgClient, $client, $lang, $encoding;

	if (!is_object($db2))
		$db2 = new DB_Contenido;

	/* extract IDCATART */
	$sql = "SELECT
	                    idcatart
	                FROM
	                    ".$cfg["tab"]["cat_art"]."
	                WHERE
	                    idcat = '".$idcat."' AND
	                    idart = '".$idart."'";

	$db->query($sql);
	$db->next_record();

	$idcatart = $db->f("idcatart");

	/* If neither the
	   article or the category is
	   configured, no code will be
	   created and an error occurs. */
	$sql = "SELECT
	                    a.idtplcfg AS idtplcfg
	                FROM
	                    ".$cfg["tab"]["art_lang"]." AS a,
	                    ".$cfg["tab"]["art"]." AS b
	                WHERE
	                    a.idart     = '".$idart."' AND
	                    a.idlang    = '".$lang."' AND
	                    b.idart     = a.idart AND
	                    b.idclient  = '".$client."'";

	$db->query($sql);
	$db->next_record();

	if ($db->f("idtplcfg") != 0)
	{

		/* Article is configured */
		$idtplcfg = $db->f("idtplcfg");

		if ($debug)
			echo "configuration for article found: $idtplcfg<br><br>";

		$a_c = array ();

		$sql2 = "SELECT
		                        *
		                     FROM
		                        ".$cfg["tab"]["container_conf"]."
		                     WHERE
		                        idtplcfg = '".$idtplcfg."'
		                     ORDER BY
		                        number ASC";

		$db2->query($sql2);

		while ($db2->next_record())
		{
			$a_c[$db2->f("number")] = $db2->f("container");

		}

	} else
	{

		/* Check whether category is
		 configured. */
		$sql = "SELECT
		                        a.idtplcfg AS idtplcfg
		                    FROM
		                        ".$cfg["tab"]["cat_lang"]." AS a,
		                        ".$cfg["tab"]["cat"]." AS b
		                    WHERE
		                        a.idcat     = '".$idcat."' AND
		                        a.idlang    = '".$lang."' AND
		                        b.idcat     = a.idcat AND
		                        b.idclient  = '".$client."'";

		$db->query($sql);
		$db->next_record();

		if ($db->f("idtplcfg") != 0)
		{

			/* Category is configured,
			   extract varstring */
			$idtplcfg = $db->f("idtplcfg");

			if ($debug)
				echo "configuration for category found: $idtplcfg<br><br>";

			$a_c = array ();

			$sql2 = "SELECT
			                            *
			                         FROM
			                            ".$cfg["tab"]["container_conf"]."
			                         WHERE
			                            idtplcfg = '".$idtplcfg."'
			                         ORDER BY
			                            number ASC";

			$db2->query($sql2);

			while ($db2->next_record())
			{
				$a_c[$db2->f("number")] = $db2->f("container");

			}

		} else
		{

			/* Article nor Category
			   is configured. Creation of
			   Code is not possible. Write
			   Errormsg to DB. */

			if ($debug)
				echo "Neither CAT or ART are configured!<br><br>";

			$code = '<html><body>No code was created for this art in this category.</body><html>';

			$sql = "SELECT * FROM ".$cfg["tab"]["code"]." WHERE idcatart='$idcatart' AND idlang='$lang'";

			$db->query($sql);

			if ($db->next_record())
			{
				$sql = "UPDATE ".$cfg["tab"]["code"]." SET code='$code', idlang='$lang', idclient='$client' WHERE idcatart='$idcatart' AND idlang='$lang'";
				$db->query($sql);
			} else
			{
				$sql = "INSERT INTO ".$cfg["tab"]["code"]." (idcode, idcatart, code, idlang, idclient) VALUES ('".$db->nextid($cfg["tab"]["code"])."', '$idcatart', '$code', '$lang', '$client')";
				$db->query($sql);
			}

			return "0601";

		}

	}

	/* Get IDLAY and IDMOD array */
	$sql = "SELECT
	                    a.idlay AS idlay,
	                    a.idtpl AS idtpl
	                FROM
	                    ".$cfg["tab"]["tpl"]." AS a,
	                    ".$cfg["tab"]["tpl_conf"]." AS b
	                WHERE
	                    b.idtplcfg  = '".$idtplcfg."' AND
	                    b.idtpl     = a.idtpl";

	$db->query($sql);
	$db->next_record();

	$idlay = $db->f("idlay");

	if ($layout != false)
	{
		$idlay = $layout;
	}

	$idtpl = $db->f("idtpl");

	if ($debug)
		echo "Usging Layout: $idlay and Template: $idtpl for generation of code.<br><br>";

	/* List of used modules */
	$sql = "SELECT
	                    number,
	                    idmod
	                FROM
	                    ".$cfg["tab"]["container"]."
	                WHERE
	                    idtpl = '".$idtpl."'
	                ORDER BY
	                    number ASC";

	$db->query($sql);

	while ($db->next_record())
	{
		$a_d[$db->f("number")] = $db->f("idmod");
	}

	/* Get code from Layout */
	$sql = "SELECT * FROM ".$cfg["tab"]["lay"]." WHERE idlay = '".$idlay."'";

	$db->query($sql);
	$db->next_record();

	$code = $db->f("code");
	$code = AddSlashes($code);

	/* Create code for all containers */
	if ($idlay)
	{
		cInclude("includes", "functions.tpl.php");
		tplPreparseLayout($idlay);
		$tmp_returnstring = tplBrowseLayoutForContainers($idlay);
		$a_container = explode("&", $tmp_returnstring);

		foreach ($a_container as $key => $value)
		{

			$sql = "SELECT * FROM ".$cfg["tab"]["mod"]." WHERE idmod='".$a_d[$value]."'";

			$db->query($sql);
			$db->next_record();

			if (is_numeric($a_d[$value]))
			{
				$thisModule = '<?php $cCurrentModule = '. ((int) $a_d[$value]).'; ?>';
				$thisContainer = '<?php $cCurrentContainer = '. ((int) $value).'; ?>';
			}

			$output = $thisModule.$thisContainer.$db->f("output");
			$output = AddSlashes($output)."\n";

			$template = $db->f("template");

			$a_c[$value] = preg_replace("/(&\$)/", "", $a_c[$value]);

			$tmp1 = preg_split("/&/", $a_c[$value]);

			$varstring = array ();

			foreach ($tmp1 as $key1 => $value1)
			{

				$tmp2 = explode("=", $value1);
				foreach ($tmp2 as $key2 => $value2)
				{
					$varstring["$tmp2[0]"] = $tmp2[1];
				}
			}

			$CiCMS_Var = '$C'.$value.'CMS_VALUE';
			$CiCMS_VALUE = '';

			foreach ($varstring as $key3 => $value3)
			{
				$tmp = urldecode($value3);
				$tmp = str_replace("\'", "'", $tmp);
				$CiCMS_VALUE .= $CiCMS_Var.'['.$key3.']="'.$tmp.'"; ';
				$output = str_replace("\$CMS_VALUE[$key3]", $tmp, $output);
				$output = str_replace("CMS_VALUE[$key3]", $tmp, $output);
			}

			$output = str_replace("CMS_VALUE", $CiCMS_Var, $output);
			$output = str_replace("\$".$CiCMS_Var, $CiCMS_Var, $output);

			$output = eregi_replace("(CMS_VALUE\[)([0-9]*)(\])", "", $output);

			if ($frontend_debug["container_display"] == true)
			{
				$fedebug .= "Container: CMS_CONTAINER[$value]".'\\\\n';
			}
			if ($frontend_debug["module_display"] == true)
			{
				$fedebug .= "Modul: ".$db->f("name").'\\\\n';
			}
			if ($frontend_debug["module_timing_summary"] == true || $frontend_debug["module_timing"] == true)
			{
				$fedebug .= 'Eval-Time: $modtime'.$value.'\\\\n';
				$output = '<?php $modstart'.$value.' = getmicrotime(); ?'.'>'.$output.'<?php $modend'.$value.' = getmicrotime()+0.001; $modtime'.$value.' = $modend'.$value.' - $modstart'.$value.'; ?'.'>';
			}

			if ($fedebug != "")
			{
				$output = addslashes('<?php echo \'<img onclick="javascript:showmod'.$value.'();" src="'.$cfg['path']['contenido_fullhtml'].'images/but_preview.gif">\'; ?'.'>'."<br>").$output;
				$output = $output.addslashes('<?php echo \'<script language="javascript">function showmod'.$value.' () { window.alert(\\\'\'. "'.addslashes($fedebug).'".\'\\\');} </script>\'; ?'.'>');
			}

			if ($frontend_debug["module_timing_summary"] == true)
			{
				$output .= addslashes(' <?php $cModuleTimes["'.$value.'"] = $modtime'.$value.'; ?>');
				$output .= addslashes(' <?php $cModuleNames["'.$value.'"] = "'.addslashes($db->f("name")).'"; ?>');
			}
			/* Replace new containers */
			$code = preg_replace("/<container( +)id=\\\\\"$value\\\\\"(.*)>(.*)<\/container>/i", "CMS_CONTAINER[$value]", $code);

			$code = preg_replace("/<container( +)id=\\\\\"$value\\\\\"(.*)\/>/i", "CMS_CONTAINER[$value]", $code);

			$code = str_ireplace("CMS_CONTAINER[$value]", "<?php $CiCMS_VALUE ?>\r\n".$output, $code);

			$fedebug = "";

		}
	}

	/* Find out what kind of CMS_... Vars are in use */
	$sql = "SELECT
	                    *
	                FROM
	                    ".$cfg["tab"]["content"]." AS A,
	                    ".$cfg["tab"]["art_lang"]." AS B,
	                    ".$cfg["tab"]["type"]." AS C
	                WHERE
	                    A.idtype    = C.idtype AND
	                    A.idartlang = B.idartlang AND
	                    B.idart     = '".$idart."' AND
	                    B.idlang    = '".$lang."'";

	$db->query($sql);

	while ($db->next_record())
	{
		$a_content[$db->f("type")][$db->f("typeid")] = $db->f("value");
	}

	$sql = "SELECT idartlang, pagetitle FROM ".$cfg["tab"]["art_lang"]." WHERE idart='".$idart."' AND idlang='".$lang."'";

	$db->query($sql);
	$db->next_record();

	$idartlang = $db->f("idartlang");
	$pagetitle = stripslashes($db->f("pagetitle"));

	/* replace all CMS_TAGS[] */
	$sql = "SELECT type, code FROM ".$cfg["tab"]["type"];

	$db->query($sql);

	$match = array ();
	while ($db->next_record())
	{

		$tmp = preg_match_all("/(".$db->f("type")."\[+\d+\])/i", $code, $match);
		$a_[strtolower($db->f("type"))] = $match[0];

		$success = array_walk($a_[strtolower($db->f("type"))], 'extractNumber');

		$search = array ();
		$replacements = array ();

		foreach ($a_[strtolower($db->f("type"))] as $val)
		{
			eval ($db->f("code"));

			$search[$val] = $db->f("type")."[$val]";
			$replacements[$val] = $tmp;
			$keycode[$db->f("type")][$val] = $tmp;
		}

		$code = str_ireplace($search, $replacements, $code);

	}

	if (is_array($keycode))
	{
		saveKeywordsForArt($keycode, $idart, "auto", $lang);
	}

	/* add/replace title */
	if ($pagetitle != "")
	{
		$code = preg_replace("/<title>.*?<\/title>/i", "{TITLE}", $code);

		if (strstr($code, "{TITLE}"))
		{
			$code = str_ireplace("{TITLE}", addslashes("<title>$pagetitle</title>"), $code);
		} else
		{
			$code = str_ireplace_once("</head>", addslashes("<title>".$pagetitle."</title>\n</head>"), $code);
		}
	} else
	{
		$code = str_replace('<title></title>', '', $code);
	}

	$availableTags = conGetAvailableMetaTagTypes();

	$metatags = array ();
	foreach ($availableTags as $key => $value)
	{
		$metavalue = conGetMetaValue($idartlang, $key);

		if (strlen($metavalue) > 0)
		{
			//$metatags[$value["name"]] = array(array("attribute" => $value["fieldname"], "value" => $metavalue), ...);
			$metatags[] = array ($value["fieldname"] => $value["name"], 'content' => $metavalue);
		}

	}

	/* contenido */
	$metatags[] = array ('name' => 'generator', 'content' => 'CMS Contenido '.$cfg['version']);
	if (getEffectiveSetting('generator', 'xhtml', "false") == "true")
	{
		$metatags[] = array ('http-equiv' => 'Content-Type', 'content' => 'application/xhtml+xml; charset='.$encoding[$lang]);
	} else {
		$metatags[] = array ('http-equiv' => 'Content-Type', 'content' => 'text/html; charset='.$encoding[$lang]);
	}	

	$_cecIterator = $_cecRegistry->getIterator("Contenido.Content.CreateMetatags");

	if ($_cecIterator->count() > 0)
	{
		$tmpMetatags = array ();
		while ($chainEntry = $_cecIterator->next())
		{
			$tmpMetatags = $chainEntry->execute($metatags);

			if (is_array($tmpMetatags))
			{
				$metatags = $tmpMetatags;
			}
		}
	}

	$sMetatags = '';
	
	cInclude("classes", "class.htmlelements.php");
	
	foreach ($metatags as $value)
	{
		// build up metatag string
		$oMetaTagGen = new cHTML;
		$oMetaTagGen->_tag = 'meta';
		$oMetaTagGen->updateAttributes($value);

		/* HTML does not allow ID for meta tags */
		$oMetaTagGen->removeAttribute("id");
		$sMetatags .= $oMetaTagGen->render()."\n";
	}

	/* Add meta tags */
	$code = str_ireplace_once("</head>", $sMetatags."</head>", $code);

	/* write code into the database */
	$date = date("Y-m-d H:i:s");

	if ($layout == false)
	{
		$sql = "SELECT * FROM ".$cfg["tab"]["code"]." WHERE idcatart = '".$idcatart."' AND idlang = '".$lang."'";

		$db->query($sql);

		if ($db->next_record())
		{
			if ($debug)
				echo "UPDATED code for lang:$lang, client:$client, idcatart:$idcatart";
			$sql = "UPDATE ".$cfg["tab"]["code"]." SET code='".addslashes($code)."', idlang='".$lang."', idclient='".$client."' WHERE idcatart='".$idcatart."' AND idlang='".$lang."'";
			$db->query($sql);
		} else
		{
			if ($debug)
				echo "INSERTED code for lang:$lang, client:$client, idcatart:$idcatart";
			$sql = "INSERT INTO ".$cfg["tab"]["code"]." (idcode, idcatart, code, idlang, idclient) VALUES ('".$db->nextid($cfg["tab"]["code"])."', '".$idcatart."', '".addslashes($code)."', '".$lang."', '".$client."')";
			$db->query($sql);
		}

		$sql = "UPDATE ".$cfg["tab"]["cat_art"]." SET createcode = '0' WHERE idcatart='".$idcatart."'";
		$db->query($sql);
	}

	return $code;
}

/**
 * Returns the idartlang for a given article and language
 *
 * @param $idart ID of the article
 * @param $idlang ID of the language
 * @return mixed idartlang of the article or false if nothing was found
 *
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @copyright four for business AG 2003
 */
function getArtLang($idart, $idlang)
{
	global $cfg;

	$db = new DB_Contenido;
	$sql = "SELECT idartlang FROM ".$cfg["tab"]["art_lang"]." WHERE "."idart = '$idart' AND idlang = '$idlang'";

	$db->query($sql);
	if ($db->next_record())
	{
		return $db->f("idartlang");
	} else
	{
		return false;
	}
}

/**
 * Returns all available meta tag types
 *
 * @param none
 *
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @copyright four for business AG 2003
 */
function conGetAvailableMetaTagTypes()
{
	global $cfg;

	$db = new DB_Contenido;

	$sql = "SELECT idmetatype, metatype, fieldtype, maxlength, fieldname
					FROM ".$cfg["tab"]["meta_type"];

	$db->query($sql);

	$metatag = array ();

	while ($db->next_record())
	{
		$newentry["name"] = $db->f("metatype");
		$newentry["fieldtype"] = $db->f("fieldtype");
		$newentry["maxlength"] = $db->f("maxlength");
		$newentry["fieldname"] = $db->f("fieldname");
		$metatag[$db->f("idmetatype")] = $newentry;
	}

	return $metatag;

}

/**
 * Get the meta tag value for a specific article
 *
 * @param $idartlang ID of the article
 * @param $idmetatype Metatype-ID
 *
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @copyright four for business AG 2003
 */
function conGetMetaValue($idartlang, $idmetatype)
{
	global $cfg;

	if ($idartlang == 0)
	{
		return;
	}

	$db = new DB_Contenido;

	$sql = "SELECT metavalue
					FROM ".$cfg["tab"]["meta_tag"]." WHERE idartlang = '$idartlang'
						 AND idmetatype = '$idmetatype'";

	$db->query($sql);

	if ($db->next_record())
	{
		return stripslashes($db->f("metavalue"));
	} else
	{
		return "";
	}

}

/**
 * Set the meta tag value for a specific article
 *
 * @param $idartlang ID of the article
 * @param $idmetatype Metatype-ID
 * @param $value Value of the meta tag
 *
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @copyright four for business AG 2003
 */
function conSetMetaValue($idartlang, $idmetatype, $value)
{
	global $cfg;

	$db = new DB_Contenido;
	$sql = "DELETE FROM ".$cfg["tab"]["meta_tag"]."
				WHERE idartlang = '$idartlang'
						 AND idmetatype = '$idmetatype'";

	$db->query($sql);

	$nextid = $db->nextid($cfg["tab"]["meta_tag"]);

	$sql = "INSERT INTO ".$cfg["tab"]["meta_tag"]." SET idartlang = '$idartlang',
						   idmetatype = '$idmetatype',
						   idmetatag = '$nextid',
	                       metavalue = '".addslashes($value)."'";

	$db->query($sql);

}

/** 
 * (re)generate keywords for all articles of a given client (with specified language) 
 * @param $client Client
 * @param $lang Language of a client 
 * @return void
 *
 * @author Willi Man
 * Created   :   12.05.2004
 * Modified  :   13.05.2004
 * @copyright four for business AG 2003
 */
function conGenerateKeywords($client, $lang)
{
	global $cfg;
	$db_art = new DB_Contenido;

	$options = array ("img", "link", "linktarget", "swf"); // cms types to be excluded from indexing

	$sql = "SELECT
	    			a.idart, b.idartlang
	    		FROM
	    			".$cfg["tab"]["art"]." AS a,
	    			".$cfg["tab"]["art_lang"]." AS b
	    		WHERE
	    			a.idart    = b.idart AND
	    			a.idclient = $client AND
	    			b.idlang = $lang";

	$db_art->query($sql);

	$articles = array ();
	while ($db_art->next_record())
	{
		$articles[$db_art->f("idart")] = $db_art->f("idartlang");
	}

	if (count($articles) > 0)
	{
		cInclude('classes', 'class.search.php');

		foreach ($articles as $artid => $article_lang)
		{
			$article_content = array ();
			$article_content = conGetContentFromArticle($article_lang);

			if (count($article_content) > 0)
			{
				$art_index = new Index($db_art);
				$art_index->lang = $lang;
				$art_index->start($artid, $article_content, 'auto', $options);
			}

		}
	}

}

/** 
 * get content from article 
 * @param $article_lang ArticleLanguageId of an article (idartlang) 
 * @return array Array with content of an article indexed by content-types
 *
 * @author Willi Man
 * Created   :   12.05.2004
 * Modified  :   13.05.2004
 * @copyright four for business AG 2003
 */
function conGetContentFromArticle($article_lang)
{

	global $cfg;
	$db_con = new DB_Contenido;

	$sql = "SELECT
					*
				FROM
					".$cfg["tab"]["content"]." AS A,
					".$cfg["tab"]["art_lang"]." AS B,
					".$cfg["tab"]["type"]." AS C
				WHERE
					A.idtype    = C.idtype AND
					A.idartlang = B.idartlang AND
					A.idartlang     = '".$article_lang."' ";

	$db_con->query($sql);

	while ($db_con->next_record())
	{
		$a_content[$db_con->f("type")][$db_con->f("typeid")] = urldecode($db_con->f("value"));
	}

	return $a_content;

}
?>
