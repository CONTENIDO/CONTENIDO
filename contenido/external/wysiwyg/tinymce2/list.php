<?php
// ================================================
// TINYMCE 1.45rc1 PHP WYSIWYG interface
// ================================================
// Generates file/link list for editor
// ================================================
//								  www.dayside.net
// ================================================
// Author: Martin Horwath, horwath@dayside.net
// TINYMCE 1.45rc1 Fileversion , 2005-06-10 v0.0.3
// ================================================

// include editor config/combat file
@include (dirname(__FILE__).DIRECTORY_SEPARATOR."config.php"); // CONTENIDO

$db2 = new DB_Contenido();

$arg_seperator = "&amp;";

switch($_REQUEST['mode']) {

	case "link":
		$sql = "SELECT
					*
				FROM
					".$cfg["tab"]["cat_tree"]." AS a,
					".$cfg["tab"]["cat_lang"]." AS b,
					".$cfg["tab"]["cat"]." AS c
				WHERE
					a.idcat = b.idcat AND
					c.idcat = a.idcat AND
					c.idclient = '".$client."' AND
					b.idlang = '".$lang."'
				ORDER BY
					a.idtree";

		$db->query($sql);

		echo "var tinyMCELinkList = new Array(";

		$loop = false;
				
		while ( $db->next_record() ) {
			$tmp_catname  = $db->f("name");
			$spaces = "";

			for ($i = 0; $i < $db->f("level"); $i++) {
				$spaces .= "&nbsp;&nbsp;";
			}

			if ($loop) {
				echo ",";
			} else {
				$loop = true;
			}
			
			if ($db->f("visible") == 0) {
				$tmp_catname = "[" . $tmp_catname . "]";
			}

			echo "\n\t".'["'.$spaces.$tmp_catname.'", "'."front_content.php?idcat=".$db->f("idcat").'"]';

			if ($cfg["is_start_compatible"] == true)
			{
				$sql2 = "SELECT
							 *
						 FROM
							 ".$cfg["tab"]["cat_art"]." AS a,
							 ".$cfg["tab"]["art"]." AS b,
							 ".$cfg["tab"]["art_lang"]." AS c
						 WHERE
							 a.idcat = '".$db->f("idcat")."' AND
							 b.idart = a.idart AND
							 c.idart = a.idart AND
							 c.idlang = '".$lang."' AND
							 b.idclient = '".$client."'
						 ORDER BY
							 a.is_start DESC,
							 c.title ASC";
			} else {
				$sql2 = "SELECT
							 *
						 FROM
							 ".$cfg["tab"]["cat_art"]." AS a,
							 ".$cfg["tab"]["art"]." AS b,
							 ".$cfg["tab"]["art_lang"]." AS c
						 WHERE
							 a.idcat = '".$db->f("idcat")."' AND
							 b.idart = a.idart AND
							 c.idart = a.idart AND
							 c.idlang = '".$lang."' AND
							 b.idclient = '".$client."'
						 ORDER BY
							 c.title ASC";
			}

			$db2->query($sql2);

			while ($db2->next_record()) {

				$tmp_title = $db2->f("title");

				if ( strlen($tmp_title) > 32 ) {
					$tmp_title = substr($tmp_title, 0, 32);
				}

				if ($cfg["is_start_compatible"] == true)
				{
					$is_start = $db2->f("is_start");
				} else {
					$is_start = isStartArticle($db2->f("idartlang"), $db2->f("idcat"), $lang);
					if ($is_start == true)
					{
						$is_start = 1;
					} else {
						$is_start = 0;
					}
				}
				if ($is_start == 1) {
					$tmp_title .= "*";
				}
				if ($db2->f("online") == 0) {
					$tmp_title = "[" . $tmp_title . "]";
				}
				echo ",\n\t".'["&nbsp;&nbsp;'.$spaces.'|&nbsp;&nbsp;'.$tmp_title.'", "'."front_content.php?idart=".$db2->f("idart").'"]';
			}
		}

		echo "\n);";

		break;

	case "image":
		$sql = "SELECT * FROM ".$cfg["tab"]["upl"]." WHERE idclient='$client' AND filetype IN ('gif', 'jpg', 'jpeg', 'png') ORDER BY dirname, filename ASC";
		$db->query($sql);

		echo "var tinyMCEImageList = new Array(";

		$loop = false;

		while ( $db->next_record() ) {
			if ($loop) {
				echo ",";
			} else {
				$loop = true;
			}

			echo "\n\t".'["'.$db->f("dirname").$db->f("filename").'", "'.$cfgClient[$client]["upload"].$db->f("dirname").$db->f("filename").'"]';
		}

		echo "\n);";
		break;

	case "flash":
		$sql = "SELECT * FROM ".$cfg["tab"]["upl"]." WHERE idclient='$client' AND filetype IN ('swf') ORDER BY dirname,filename ASC";
		$db->query($sql);

		echo "var tinyMCEFlashList = new Array(";

		$loop = false;

		while ( $db->next_record() ) {
			if ($loop) {
				echo ",";
			} else {
				$loop = true;
			}

			echo "\n\t".'["'.$db->f("dirname").$db->f("filename").'", "'.$cfgClient[$client]["upload"].$db->f("dirname").$db->f("filename").'"]';
		}

		echo "\n);";
		break;

	case "media":
		$sql = "SELECT * FROM ".$cfg["tab"]["upl"]." WHERE idclient='$client' AND filetype IN ('swf','dcr','mov','qt','mpg','mpg3','mpg4','mpeg','avi','wmv','wm','asf','asx','wmx','wvx','rm','ra','ram') ORDER BY dirname, filename ASC";
		$db->query($sql);

		echo "var tinyMCEMediaList = new Array(";

		$loop = false;

		while ( $db->next_record() ) {
			if ($loop) {
				echo ",";
			} else {
				$loop = true;
			}

			echo "\n\t".'["'.$db->f("dirname").$db->f("filename").'", "'.$cfgClient[$client]["upload"].$db->f("dirname").$db->f("filename").'"]';
		}

		echo "\n);";
		break;

	default:
}
?>