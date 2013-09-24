<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Defines the general contenido functions
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend includes
 * @version    1.3.4
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 *
 * {@internal
 *   created unknown
 *   modified 2008-06-26, Frederic Schneider, add security fix
 *   modified 2008-07-03, Dominik Ziegler, fixed bug CON-143
 *   modified 2009-02-15, Murat Purc, fixed bug CON-238
 *   modified 2010-09-29, Ortwin Pinke, fixed bug CON-349
 *   modified 2010-12-16, Dominik Ziegler, display error message on database connection failure [#CON-376]
 *
 *   $Id: functions.general.php 1246 2010-12-16 13:13:04Z dominik.ziegler $:
* }}
 *
 */

if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

/**
 * Extracts the available content-
 * types from the database
 *
 * Creates an array $a_content[type][number] = content string
 * f.e. $a_content['CMS_HTML'][1] = content string
 * Same for array $a_description
 *
 * @param int $idartlang Language specific ID of the arcticle
 * @return void
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG
 */
function getAvailableContentTypes($idartlang)
{
	global $db, $cfg, $a_content, $a_description;

	$sql = "SELECT
	                *
	            FROM
	                ".$cfg["tab"]["content"]." AS a,
	                ".$cfg["tab"]["art_lang"]." AS b,
	                ".$cfg["tab"]["type"]." AS c
	            WHERE
	                a.idtype    = c.idtype AND
	                a.idartlang = b.idartlang AND
	                b.idartlang = '".Contenido_Security::toInteger($idartlang)."'";

	$db->query($sql);

	while ($db->next_record())
	{
		$a_content[$db->f("type")][$db->f("typeid")] = urldecode($db->f("value"));
		$a_description[$db->f("type")][$db->f("typeid")] = i18n($db->f("description"));
	}

}

/**
 * Checks if an article is assigned
 * to multiple categories
 *
 * @param Int Article-Id ($idart)
 * @param Int Client-Id
 * @return Bool Article assigned to multiple categories
 * @access Public
 */
function isArtInMultipleUse($idart)
{
	global $cfg, $client;

	$db = new DB_Contenido;
	$sql = "SELECT idart FROM ".$cfg["tab"]["cat_art"]." WHERE idart = '".Contenido_Security::toInteger($idart)."'";
	$db->query($sql);

	return ($db->affected_rows() > 1);
}

/**
 * Checks if a value is alphanumeric
 *
 * @param Mixed Value to test
 * @param Bool [Use german Umlaute] Optional
 * @return Bool Value is alphanumeric
 */
function is_alphanumeric($test, $umlauts = true)
{

	if ($umlauts == true)
	{
		$match = "/^[a-z0-9������� ]+$/i";
	} else
	{
		$match = "/^[a-z0-9 ]+$/i";
	}

	return (preg_match($match, $test));
}

function getCanonicalMonth($month)
{
	switch ($month)
	{
		case 1 :
			return (i18n("January"));
			break;
		case 2 :
			return (i18n("February"));
			break;
		case 3 :
			return (i18n("March"));
			break;
		case 4 :
			return (i18n("April"));
			break;
		case 5 :
			return (i18n("May"));
			break;
		case 6 :
			return (i18n("June"));
			break;
		case 7 :
			return (i18n("July"));
			break;
		case 8 :
			return (i18n("August"));
			break;
		case 9 :
			return (i18n("September"));
			break;
		case 10 :
			return (i18n("October"));
			break;
		case 11 :
			return (i18n("November"));
			break;
		case 12 :
			return (i18n("December"));
			break;
	}
}

/**
 * Get multi-language day
 *
 * @param integer the day number of date(w)
 *
 * @return string  Dayname of current language
 */

function getCanonicalDay($iDay)
{
	switch ($iDay)
	{
		case 1 :
			return (i18n("Monday"));
			break;
		case 2 :
			return (i18n("Tuesday"));
			break;
		case 3 :
			return (i18n("Wednesday"));
			break;
		case 4 :
			return (i18n("Thursday"));
			break;
		case 5 :
			return (i18n("Friday"));
			break;
		case 6 :
			return (i18n("Saturday"));
			break;
		case 0 :
			return (i18n("Sunday"));
			break;
		default: break;
	}
}

function getIDForArea($area)
{
	global $client, $lang, $cfg, $sess;

	$db = new DB_Contenido;

	if (!is_numeric($area))
	{
		$sql = "SELECT
		                    idarea
		                FROM
		                    ".$cfg["tab"]["area"]."
		                WHERE
		                    name = '".Contenido_Security::escapeDB($area, $db)."'";

		$db->query($sql);
		if ($db->next_record())
		{
			$area = $db->f(0);
		}
	}

	return $area;

}

function getParentAreaId($area)
{

	global $client, $lang, $cfg, $sess;

	$db = new DB_Contenido;

	if (is_numeric($area))
	{
		$sql = "SELECT
		                    b.name
		                FROM
		                    ".$cfg["tab"]["area"]." AS a,
		                    ".$cfg["tab"]["area"]." AS b
		                WHERE
		                    a.idarea = '".Contenido_Security::toInteger($area)."' AND
		                    b.name = a.parent_id";
	} else
	{
		$sql = "SELECT
		                    b.name
		                FROM
		                    ".$cfg["tab"]["area"]." AS a,
		                    ".$cfg["tab"]["area"]." AS b
		                WHERE
		                    a.name = '".Contenido_Security::escapeDB($area, $db)."' AND
		                    b.name = a.parent_id";

	}
	$db->query($sql);

	if ($db->next_record())
	{
		return $db->f(0);
	} else
	{
		return $area;

	}

}

/**
 * Write JavaScript to mark
 *
 * @param int $menuitem Which menuitem to mark
 * @param bool $return Return or echo script
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
function markSubMenuItem($menuitem, $return = false)
{
    $str = '
    <script type="text/javascript">
    (function(id){
        var menuItem;

        try {
            // Check if we are in a dual-frame or a quad-frame
            if (parent.parent.frames[0].name == "header") {
                if (parent.frames["right_top"].document.getElementById(id)) {
                    menuItem = parent.frames["right_top"].document.getElementById(id).getElementsByTagName("a")[0];
                    parent.frames["right_top"].sub.clicked(menuItem);
                }
            } else {
                // Check if submenuItem is existing and mark it
                if (parent.parent.frames["right"].frames["right_top"].document.getElementById(id)) {
                    menuItem = parent.parent.frames["right"].frames["right_top"].document.getElementById(id).getElementsByTagName("a")[0];
                    parent.parent.frames["right"].frames["right_top"].sub.clicked(menuItem);
                }
            }
        } catch (e) {}
    })("c_' . $menuitem . '");
    </script>';

    if ($return) {
        return $str;
    } else {
        echo $str;
    }
}

/**
 * Redirect to main area
 *
 * @param bool $send Redirect Yes/No
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
function backToMainArea($send)
{

	if ($send)
	{

		/* Global vars */
		global $area, $cfg, $db, $sess, $idart, $idcat, $idartlang, $idcatart, $frame;

		/* Get main area */
		$sql = "SELECT
		                    a.name
		                FROM
		                    ".$cfg["tab"]["area"]." AS a,
		                    ".$cfg["tab"]["area"]." AS b
		                WHERE
		                    b.name      = '".Contenido_Security::escapeDB($area, $db)."' AND
		                    b.parent_id = a.name";

		$db->query($sql);
		$db->next_record();

		$parent = $db->f("name");

		/* Create url string */
		$url_str = 'main.php?'.'area='.$parent.'&'.'idcat='.$idcat.'&'.'idart='.$idart.'&'.'idartlang='.$idartlang.'&'.'idcatart='.$idcatart.'&'.'force=1&'.'frame='.$frame;

		$url = $sess->url($url_str);

		/* Redirect */
		header("location: $url");

	}

}

function showLocation($area)
{
	global $db;
	global $cfgPath, $lngArea;
	global $cfg;
	global $belang;

	//Create new xml Class and load the file

	$xml = new XML_doc;
	if ($xml->load($cfg['path']['xml'].$cfg['lang'][$belang]) == false)
	{
		if ($xml->load($cfg['path']['xml'].'lang_en_US.xml') == false)
		{
			die("Unable to load any XML language file");
		}
	}

	$sql = "SELECT location
	              FROM ".$cfg["tab"]["area"]." as A, ".$cfg["tab"]["nav_sub"]." as B
	              Where A.name='".Contenido_Security::escapeDB($area, $db)."' AND A.idarea=B.idarea AND A.online='1'";

	$db->query($sql);
	if ($db->next_record())
	{

		echo "<b>".$xml->valueOf($db->f("location"))."</b>";

	} else
	{

		$sql = "SELECT parent_id
		                    FROM ".$cfg["tab"]["area"]."
		                    WHERE name='".Contenido_Security::escapeDB($area, $db)."' AND online='1'";
		$db->query($sql);
		$db->next_record();
		$parent = $db->f("parent_id");

		$sql = "SELECT location
		                    FROM ".$cfg["tab"]["area"]." as A, ".$cfg["tab"]["nav_sub"]." as B
		                    Where A.name='".Contenido_Security::escapeDB($parent, $db)."' AND A.idarea = B.idarea AND A.online='1'";

		$db->query($sql);
		$db->next_record();
		echo "<b>".$xml->valueOf($db->f("location")).$lngArea[$area]."</b>";

	}

}

function showTable($tablename)
{
	global $db;

	$sql = "SELECT * FROM $tablename";
	$db->query($sql);
	while ($db->next_record())
	{
		while (list ($key, $value) = each($db->Record))
		{
			print (is_string($key) ? "<b>$key</b>: $value | " : "");
		}
		print ("<br>");
	}

}

function getLanguagesByClient($client)
{
	global $db;
	global $cfg;

	$sql = "SELECT idlang FROM ".$cfg["tab"]["clients_lang"]." WHERE idclient='".Contenido_Security::toInteger($client)."'";
	$db->query($sql);
	while ($db->next_record())
	{
		$list[] = $db->f("idlang");
	}

	return $list;
}

function getLanguageNamesByClient($client)
{
	global $db;
	global $cfg;

	$sql = "SELECT
	                    a.idlang AS idlang,
	                    b.name AS name
	                FROM
	                  ".$cfg["tab"]["clients_lang"]." AS a,
	                  ".$cfg["tab"]["lang"]." AS b
	                WHERE
	                    idclient='".Contenido_Security::toInteger($client)."' AND
	                    a.idlang = b.idlang
	                ORDER BY
	                    idlang ASC";

	$db->query($sql);
	while ($db->next_record())
	{
		$list[$db->f("idlang")] = $db->f("name");
	}

	return $list;
}

function set_magic_quotes_gpc(&$code) {
      global $cfg;

      if (!$cfg['simulate_magic_quotes']) {
            if (get_magic_quotes_gpc() == 0)
            {
            	$code = addslashes($code);
            }
      }
}

/**
 * @deprecated since 22.08.2005
 * This function is called everytime when the code of an article is generated.
 * In this case indexing can cause performance problems.
 * Indexing an article is treated now in function conSaveContentEntry.
 */
function SaveKeywordsforart($keycode, $idart, $place, $lang)
{
	; # do nothing
}

function fakeheader($time)
{
	global $con_time0;
	if (!isset ($con_time0))
	{
		$con_time0 = $time;
	}

	if ($time >= $con_time0 +1000)
	{
		$con_time0 = $time;
		header('X-pmaPing: Pong');
	} // end if
}

function recursive_copy($from_path, $to_path)
{
	mkdir($to_path, 0777);
	$old_path = getcwd();
	$this_path = getcwd();

	if (is_dir($from_path))
	{
		chdir($from_path);
		$myhandle = opendir('.');

		while (($myfile = readdir($myhandle)) !== false)
		{
			if (($myfile != ".") && ($myfile != ".."))
			{
				if (is_dir($myfile))
				{
					recursive_copy($from_path.$myfile."/", $to_path.$myfile."/");
					chdir($from_path);
				} else if (file_exists($myfile))
				{
					copy($from_path.$myfile, $to_path.$myfile);
				}
			}
		}
		closedir($myhandle);
	}

	chdir($old_path);
	return;
}

function getmicrotime()
{
	list ($usec, $sec) = explode(" ", microtime());
	return ((float) $usec + (float) $sec);
}

/* Small hack to clean up unused sessions.
   As we are probably soon rewriting the
   session management, this hack is OK. */
function cleanupSessions()
{
	global $cfg;

	$db = new DB_Contenido;
	$db2 = new DB_Contenido;

	$col = new InUseCollection;

	$temp = new Contenido_Challenge_Crypt_Auth;

	$maxdate = date("YmdHis", time() - ($temp->lifetime * 60));

	/* Expire old sessions */
	$sql = "SELECT changed, sid FROM ".$cfg["tab"]["phplib_active_sessions"];
	$db->query($sql);

	while ($db->next_record())
	{
		if ($db->f("changed") < $maxdate)
		{
			$sql = "DELETE FROM ".$cfg["tab"]["phplib_active_sessions"]." WHERE sid = '".Contenido_Security::escapeDB($db->f("sid"), $db2)."'";
			$db2->query($sql);
			$col->removeSessionMarks($db->f("sid"));
		}
	}

	/* Expire invalid InUse-Entries */
	$col->select();

	while ($c = $col->next())
	{
		$sql = "SELECT sid FROM ".$cfg["tab"]["phplib_active_sessions"]." WHERE sid = '".Contenido_Security::escapeDB($c->get("session"), $db2)."'";
		$db2->query($sql);
		if (!$db2->next_record())
		{
			$col->delete($c->get("idinuse"));
		}
	}
}

function isGroup($uid)
{
	$users = new User;

	if ($users->loadUserByUserID($uid) == false)
	{
		return true;
	} else
	{
		return false;
	}
}

function getGroupOrUserName($uid)
{
	$users = new User;

	if ($users->loadUserByUserID($uid) === false)
	{
		$groups = new Group;
		/* Yes, it's a group. Let's try to load the group members! */
		if ($groups->loadGroupByGroupID($uid) === false)
		{
			return false;
		} else
		{
			return substr($groups->getField("groupname"), 4);
		}
	} else
	{
		return $users->getField("realname");
	}
}

/**
 * getPhpModuleInfo - parses phpinfo() output
 *
 * parses phpinfo() output
 * (1) get informations for a specific module (parameter $modulname)
 * (2) get informations for all modules (no parameter for $modulname needed)
 *
 * if a specified extension doesn't exists or isn't activated an array will be returned:
 * Array
 *     (
 *          [error] => extension is not available
 *     )
 *
 *
 * to get specified information on one module use (1):
 * getPhpModuleInfo($moduleName = 'gd');
 *
 * to get all informations use (2):
 * getPhpModuleInfo($moduleName);
 *
 *
 * EXAMPLE OUTPUT (1):
 * Array
 * (
 *    [GD Support] => Array
 *        (
 *            [0] => enabled
 *         )
 * ...
 * )
 *
 *
 * EXAMPLE OUTPUT (2):
 * Array
 * (
 *     [yp] => Array
 *         (
 *              [YP Support] => Array
 *                  (
 *                      [0] => enabled
 *                   )
 *
 *         )
 * ...
 * }
 *
 * foreach ($moduleSettings as $setting => $value)
 * $setting contains the modul settings
 * $value contains the settings as an array ($value[0] => Local Value && $value[1] => Master Value)
 *
 * @param $modulName string specify modul name or if not get all settings
 *
 * @return array see above for example
 * @author Marco Jahn
 */
function getPhpModuleInfo($moduleName)
{
	$moduleSettings = array();
	ob_start();
	phpinfo(INFO_MODULES); // get information vor modules
	$string = ob_get_contents();
	ob_end_clean();

	$pieces = explode("<h2", $string); // get several modules

	foreach ($pieces as $val)
	{
		// perform a regular expression match on every module header
		preg_match("/<a name=\"module_([^<>]*)\">/", $val, $sub_key);

		// perform a regular expression match on tabs with 2 columns
		preg_match_all("/<tr[^>]*>
				<td[^>]*>(.*)<\/td>
				<td[^>]*>(.*)<\/td>/Ux", $val, $sub);

		// perform a regular expression match on tabs with 3 columns
		preg_match_all("/<tr[^>]*>
				<td[^>]*>(.*)<\/td>
				<td[^>]*>(.*)<\/td>
				<td[^>]*>(.*)<\/td>/Ux", $val, $sub_ext);

		if (isset ($moduleName)) // if $moduleName is specified
		{
			if (extension_loaded($moduleName)) //check if specified extension exists or is loaded
			{
				if ($sub_key[1] == $moduleName)
				{ //create array only for specified $moduleName
					foreach ($sub[0] as $key => $val)
					{
						$moduleSettings[strip_tags($sub[1][$key])] = array (strip_tags($sub[2][$key]));
					}
				}
			} else //specified extension is not loaded or doesn't exists
				{
				$moduleSettings['error'] = 'extension is not available';
			}
		} else // $moduleName isn't specified => get everything
			{
			foreach ($sub[0] as $key => $val)
			{
				$moduleSettings[$sub_key[1]][strip_tags($sub[1][$key])] = array (strip_tags($sub[2][$key]));
			}

			foreach ($sub_ext[0] as $key => $val)
			{
				$moduleSettings[$sub_key[1]][strip_tags($sub_ext[1][$key])] = array (strip_tags($sub_ext[2][$key]), strip_tags($sub_ext[3][$key]));
			}
		}
	}
	return $moduleSettings;
}

function isValidMail($sEMail, $bStrict = false)
{
	if ($bStrict)
	{
		// HerrB (14.02.2008), code posted by Calvini
		// See http://www.contenido.org/forum/viewtopic.php?p=106612#106612
		// Note, that IDNs are currently only supported if given as punycode

		// "Strict" just means "95% real-world match",
		// e.g. a.b@c.de, a-b@c.de, a_b@c.de and some special chars (not \n, ;)

		// See also http://www.php.net/manual/en/function.eregi.php#52458,
		// but note http://www.php.net/manual/en/function.eregi.php#55215
		// or just kill yourself, as being dumb to even try to validate an
		// email address: http://www.php.net/manual/en/function.preg-match.php#76615

		$sLocalChar		= '-a-z0-9_!#\\$&\'\\*\\+\\/=\\?\\^`\\{\\|\\}~';
		$sLocalRegEx	= '['.$sLocalChar.'](\\.*['.$sLocalChar.'])*';
		$sDomainChar	= 'a-z���';
		$sDomainRegEx	= $sDomainRegEx  = '((['.$sDomainChar.']|['.$sDomainChar.']['.$sDomainChar.'0-9-]{0,61}['.$sDomainChar.'0-9])\\.)+';
		$sTLDChar		= 'a-z';
		$sTLDRegEx		= '['.$sTLDChar.']{2,}';
		return preg_match('/^' . $sLocalRegEx . '@' . $sDomainRegEx . $sTLDRegEx . '$/i', $sEMail);
	} else {
		return preg_match("/^[0-9a-z]([-_.]*[0-9a-z]*)*@[a-z0-9-]+\.([a-z])/i", $sEMail);
	}
}

function htmldecode($string)
{
	$trans_tbl = conGetHtmlTranslationTable(HTML_ENTITIES);
	$trans_tbl = array_flip($trans_tbl);
	$ret = strtr($string, $trans_tbl);

	return $ret;
}

function rereadClients()
{
	global $cfgClient;
	global $errsite_idcat;
	global $errsite_idart;
	global $db;
	global $cfg;

	if (!is_object($db))
	{
		$db = new DB_Contenido;
	}

	$sql = "SELECT
	                idclient,
	                frontendpath,
	                htmlpath,
	                errsite_cat,
	                errsite_art
	            FROM
	            ".$cfg["tab"]["clients"];

	$db->query($sql);

	while ($db->next_record())
	{

		$cfgClient["set"] = "set";
		$cfgClient[$db->f("idclient")]["path"]["frontend"] = $db->f("frontendpath");
		$cfgClient[$db->f("idclient")]["path"]["htmlpath"] = $db->f("htmlpath");
		$errsite_idcat[$db->f("idclient")] = $db->f("errsite_cat");
		$errsite_idart[$db->f("idclient")] = $db->f("errsite_art");

		$cfgClient[$db->f("idclient")]["images"] = $db->f("htmlpath")."images/";
		$cfgClient[$db->f("idclient")]["upload"] = "upload/";

		$cfgClient[$db->f("idclient")]["htmlpath"]["frontend"] = $cfgClient[$db->f("idclient")]["path"]["htmlpath"];
		$cfgClient[$db->f("idclient")]["upl"]["path"] = $cfgClient[$db->f("idclient")]["path"]["frontend"]."upload/";
		$cfgClient[$db->f("idclient")]["upl"]["htmlpath"] = $cfgClient[$db->f("idclient")]["htmlpath"]["frontend"]."upload/";
		$cfgClient[$db->f("idclient")]["upl"]["frontendpath"] = "upload/";
		$cfgClient[$db->f("idclient")]["css"]["path"] = $cfgClient[$db->f("idclient")]["path"]["frontend"]."css/";
		$cfgClient[$db->f("idclient")]["js"]["path"] = $cfgClient[$db->f("idclient")]["path"]["frontend"]."js/";
		$cfgClient[$db->f("idclient")]["tpl"]["path"] = $cfgClient[$db->f("idclient")]["path"]["frontend"]."templates/";

	}

}

/**
 * Sets a system property entry
 *
 * @modified Timo Trautmann 22.02.2008 Support for editing name and type
 *
 * @param string $type The type of the item
 * @param string $name The name of the item
 * @param string $value The value of the item
 * @param int $idsystemprop The sysprop id, use optional. If set it allows to modify type name and value
 */
function setSystemProperty($type, $name, $value, $idsystemprop = 0)
{
	global $cfg;
	if ($type == "" || $name == "")
	{
		return false;
	}

    $idsystemprop = Contenido_Security::toInteger($idsystemprop);

	$db_systemprop = new DB_Contenido;

    if ($idsystemprop == 0) {
        $sql = "SELECT idsystemprop FROM ".$cfg["tab"]["system_prop"]." WHERE type='".Contenido_Security::escapeDB($type, $db_systemprop)."' AND name='".Contenido_Security::escapeDB($name, $db_systemprop)."'";
    } else {
        $sql = "SELECT idsystemprop FROM ".$cfg["tab"]["system_prop"]." WHERE idsystemprop='$idsystemprop'";
    }

	$db_systemprop->query($sql);

	if ($db_systemprop->num_rows() > 0)
	{
        if ($idsystemprop == 0) {
            $sql = "UPDATE ".$cfg["tab"]["system_prop"]." SET value='".Contenido_Security::filter($value, $db_systemprop)."' WHERE type='".Contenido_Security::escapeDB($type, $db_systemprop)."'
                    AND name='".Contenido_Security::escapeDB($name, $db_systemprop)."'";
        } else {
            $sql = "UPDATE ".$cfg["tab"]["system_prop"]." SET value='".Contenido_Security::filter($value, $db_systemprop)."', type='".Contenido_Security::escapeDB($type, $db_systemprop)."',
                    name='".Contenido_Security::escapeDB($name, $db_systemprop)."' WHERE idsystemprop='$idsystemprop'";
        }
	} else
	{
		$idsystemprop = $db_systemprop->nextid($cfg["tab"]["system_prop"]);
		$sql = "INSERT INTO ".$cfg["tab"]["system_prop"]." (idsystemprop, value, type, name) VALUES ('$idsystemprop', '".Contenido_Security::filter($value, $db_systemprop)."',
                '".Contenido_Security::escapeDB($type, $db_systemprop)."', '".Contenido_Security::escapeDB($name, $db_systemprop)."')";
	}

	$db_systemprop->query($sql);
}

/**
 * Remove a system property entry
 *
 * @param string $type The type of the item
 * @param string $name The name of the item
 */
function deleteSystemProperty($type, $name)
{
	global $cfg;

	$db_systemprop = new DB_Contenido;

	$sql = "DELETE FROM ".$cfg["tab"]["system_prop"]." WHERE type='".Contenido_Security::escapeDB($type, $db_systemprop)."' AND name='".Contenido_Security::escapeDB($name, $db_systemprop)."'";
	$db_systemprop->query($sql);
}

/**
 * Retrieves all available system properties.
 * Array format:
 *
 * $array[$type][$name] = $value;
 *
 * @modified Timo Trautmann 22.02.2008 Support for editing name and type editing by primaray key idsystemprop
 * if bGetPropId is set:
 * $array[$type][$name][value] = $value;
 * $array[$type][$name][idsystemprop] = $idsystemprop;
 *
 * @param boolean bGetPropId - if true special mode is activated which generates for each property a third array, which also contains idsystemprop value
 * @return array
 */
function getSystemProperties($bGetPropId = 0)
{
	global $cfg;

	$db_systemprop = new DB_Contenido;

	$sql = "SELECT idsystemprop, type, name, value FROM ".$cfg["tab"]["system_prop"]." ORDER BY type ASC, name ASC, value ASC";
	$db_systemprop->query($sql);
    $results = array();

    if ($bGetPropId) {
    	while ($db_systemprop->next_record())
    	{
    		$results[$db_systemprop->f("type")][$db_systemprop->f("name")]['value'] = urldecode($db_systemprop->f("value"));
            $results[$db_systemprop->f("type")][$db_systemprop->f("name")]['idsystemprop'] = urldecode($db_systemprop->f("idsystemprop"));
    	}
    } else {
        while ($db_systemprop->next_record())
    	{
    		$results[$db_systemprop->f("type")][$db_systemprop->f("name")] = urldecode($db_systemprop->f("value"));
    	}
    }

	return ($results);
}

/**
 * Gets a system property entry
 *
 * @param string $type The type of the item
 * @param string $name The name of the item
 * @return mixed boolean false if nothing was found, or
 */
function getSystemProperty($type, $name)
{
	global $cfg;

	$db_systemprop = new DB_Contenido;

	$sql = "SELECT value FROM ".$cfg["tab"]["system_prop"]." WHERE type='".Contenido_Security::escapeDB($type, $db_systemprop)."' AND name='".Contenido_Security::escapeDB($name, $db_systemprop)."'";
	$db_systemprop->query($sql);

	if ($db_systemprop->next_record())
	{
		return urldecode($db_systemprop->f("value"));
	} else
	{
		return false;
	}
}

/**
 * Gets system property entries
 *
 * @param string $type The type of the item
 * @return array Value
 */
function getSystemPropertiesByType($sType)
{
	global $cfg;

	$aResult = array();

	$db_systemprop = new DB_Contenido;

	$sSQL = "SELECT name, value FROM ".$cfg["tab"]["system_prop"]." WHERE type='".Contenido_Security::escapeDB($sType, $db_systemprop)."' ORDER BY name";
	$db_systemprop->query($sSQL);

	while ($db_systemprop->next_record())
	{
		$aResult[$db_systemprop->f("name")] = urldecode($db_systemprop->f("value"));
	}

	return $aResult;
}

/**
 * Returns the current effective setting for a property.
 *
 * The order is:
 * System => Client => Group => User
 *
 * System properties can be overridden by the group, and group
 * properties can be overridden by the user.
 *
 * @param string $type The type of the item
 * @param string $name The name of the item
 * @param string $default Optional default value
 * @return mixed boolean false if nothing was found
 */
function getEffectiveSetting($type, $name, $default = "")
{
	global $auth, $client, $lang;

	if ($auth->auth["uid"] != "nobody")
	{
		$user = new User;
		$user->loadUserByUserID($auth->auth["uid"]);

		$value = $user->getUserProperty($type, $name, true);
        unset($user);
	} else {
		$value = false;
	}

    if ($value == false) {
        $oClient = new cApiClientLanguage(false, $client, $lang);
        $value = $oClient->getProperty($type, $name);
        unset ($oClient);
    }

	if ($value == false)
	{
		$oClient = new cApiClient($client);
		$value = $oClient->getProperty($type, $name);
        unset ($oClient);
	}

	if ($value == false)
	{
		$value = getSystemProperty($type, $name);
	}

	if ($value === false)
	{
		return $default;
	} else {
		return $value;
	}
}

/**
 * Returns the current effective settings for a type of properties.
 *
 * The order is:
 * System => Client => Group => User
 *
 * System properties can be overridden by the group, and group
 * properties can be overridden by the user.
 *
 * @param string $type The type of the item
 * @return array Value
 */
function getEffectiveSettingsByType($sType)
{
	global $auth, $client, $cfg, $lang;

	$aResult = getSystemPropertiesByType($sType);

	$oClient = new cApiClient($client);
	$aResult = array_merge($aResult, $oClient->getPropertiesByType($sType));
    unset ($oClient);

    $oClient = new cApiClientLanguage(false, $client, $lang);
    $aResult = array_merge($aResult, $oClient->getPropertiesByType($sType));

	unset ($oClient);

	if ($auth->auth["uid"] != "nobody")
	{
		$oUser = new User;
		$oUser->loadUserByUserID($auth->auth["uid"]);

		$aResult = array_merge($aResult, $oUser->getUserPropertiesByType($sType, true));
	}

	return $aResult;
}

/**
* retrieve list of article specifications for current client and language
*
* @return array list of article specifications
*/
function getArtspec()
{
	global $db, $cfg, $lang, $client;
	$sql = "SELECT artspec, idartspec, online, artspecdefault FROM ".$cfg['tab']['art_spec']."
			WHERE client='".Contenido_Security::toInteger($client)."' AND lang='".Contenido_Security::toInteger($lang)."' ORDER BY artspec ASC";
	$db->query($sql);

	$artspec = array ();

	while ($db->next_record())
	{
		$artspec[$db->f("idartspec")]['artspec'] = $db->f("artspec");
		$artspec[$db->f("idartspec")]['online'] = $db->f("online");
		$artspec[$db->f("idartspec")]['default'] = $db->f("artspecdefault");
	}
	return $artspec;
}

/**
* add new article specification
*
* @param string article specification text
*
* @return void
*/
function addArtspec($artspectext, $online)
{
	global $db, $cfg, $lang, $client;

	if (isset ($_POST['idartspec']))
	{ //update
		$sql = "UPDATE ".$cfg['tab']['art_spec']." SET
						artspec='".Contenido_Security::escapeDB(urldecode($artspectext), $db)."',
						online='".Contenido_Security::toInteger($online)."'
					WHERE idartspec=".Contenido_Security::toInteger($_POST['idartspec'])."";
		$db->query($sql);
	} else
	{
		$sql = "INSERT INTO ".$cfg['tab']['art_spec']."
					(idartspec, client, lang, artspec, online, artspecdefault)
					VALUES
					(".Contenido_Security::toInteger($db->nextid($cfg['tab']['art_spec'])).", '".Contenido_Security::toInteger($client)."', '".Contenido_Security::toInteger($lang)."',
					'".Contenido_Security::escapeDB(urldecode($artspectext), $db)."', 0, 0)";
		$db->query($sql);
	}
}

/**
* delete specified article specification
*
* @param integer article specification id
*
* @return void
*/
function deleteArtspec($idartspec)
{
	global $db, $cfg;
	$sql = "DELETE FROM ".$cfg['tab']['art_spec']." WHERE idartspec = '".Contenido_Security::toInteger($idartspec)."'";
	$db->query($sql);

	$sql = "UPDATE ".$cfg["tab"]["art_lang"]." set artspec = '0' WHERE artspec = '".Contenido_Security::toInteger($idartspec)."'";
	$db->query($sql);
}

/**
* set article specifications online
*
* flag to switch if an article specification should be shown the frontend or not
*
* @param integer article specification id
* @param integer 0/1 switch the status between on an offline
*
* @return void
*/
function setArtspecOnline($idartspec, $online)
{
	global $db, $cfg;
	$sql = "UPDATE ".$cfg['tab']['art_spec']." SET online=".Contenido_Security::toInteger($online)." WHERE idartspec=".Contenido_Security::toInteger($idartspec)."";
	$db->query($sql);
}

/**
* set a default article specification
*
* while creating a new article this defined article specification will be default setting
*
* @param integer article specification id
*
* @return void
*/
function setArtspecDefault($idartspec)
{
	global $db, $cfg, $lang, $client;
	$sql = "UPDATE ".$cfg['tab']['art_spec']." SET artspecdefault=0 WHERE client='".Contenido_Security::toInteger($client)."' AND lang='".Contenido_Security::toInteger($lang)."'";
	$db->query($sql);

	$sql = "UPDATE ".$cfg['tab']['art_spec']." SET artspecdefault=1 WHERE idartspec='".Contenido_Security::toInteger($idartspec)."'";
	$db->query($sql);
}

/**
 * Build a Article select Box
 *
 * @param String Name of the SelectBox
 * @param String Value of the SelectBox
 * @return String HTML
 */
function buildArticleSelect($sName, $iIdCat, $sValue) {
	global $cfg, $client, $lang, $idcat;
	$db = new DB_Contenido;

	$html = '';
	$html .= '<select id="'.$sName.'" name="'.$sName.'">';
	$html .= '  <option value="">'.i18n("Please choose").'</option>';

	$sql = "SELECT b.title, b.idart FROM
	    	   ".$cfg["tab"]["art"]." AS a, ".$cfg["tab"]["art_lang"]." AS b, ".$cfg["tab"]["cat_art"]." AS c
			   WHERE c.idcat = '".Contenido_Security::toInteger($iIdCat)."'
	    	   AND b.idlang = '".Contenido_Security::toInteger($lang)."' AND b.idart = a.idart and b.idart = c.idart
	           ORDER BY b.title";

	$db->query($sql);

	while ($db->next_record())
	{
		if ($sValue != $db->f('idart'))
		{
			$html .= '<option value="'.$db->f('idart').'" style="background-color:#EFEFEF">'.$db->f('title').'</option>';
		} else{
			$html .= '<option value="'.$db->f('idart').'" style="background-color:#EFEFEF" selected="selected">'.$db->f('title').'</option>';
		}
	}

	$html .= '</select>';

	return $html;
}

/**
 * Build a Category / Article select Box
 *
 * @param String Name of the SelectBox
 * @param String Value of the SelectBox
 * @param Integer Value of highest level that should be shown
 * @param String Optional style informations for select
 * @return String HTML
 */
function buildCategorySelect($sName, $sValue, $sLevel = 0, $sStyle = "")
{
	global $cfg, $client, $lang, $idcat;

	$db = new DB_Contenido;
	$db2 = new DB_Contenido;

	$html = '';
	$html .= '<select id="'.$sName.'" style="'.$sStyle.'" name="'.$sName.'">';
	$html .= '  <option value="">'.i18n("Please choose").'</option>';

	if ($sLevel > 0)
	{
		$addString = "AND c.level<$sLevel";
	}

	$sql = "SELECT a.idcat AS idcat, b.name AS name, c.level FROM
	    	   ".$cfg["tab"]["cat"]." AS a, ".$cfg["tab"]["cat_lang"]." AS b,
	    	   ".$cfg["tab"]["cat_tree"]." AS c WHERE a.idclient = '".Contenido_Security::toInteger($client)."'
	    	   AND b.idlang = '".Contenido_Security::toInteger($lang)."' AND b.idcat = a.idcat AND c.idcat = a.idcat ".Contenido_Security::escapeDB($addString, $db)."
	           ORDER BY c.idtree";

	$db->query($sql);

	$categories = array ();

	while ($db->next_record())
	{
		$categories[$db->f("idcat")]["name"] = $db->f("name");

		$sql2 = "SELECT level FROM ".$cfg["tab"]["cat_tree"]." WHERE idcat = '".Contenido_Security::toInteger($db->f("idcat"))."'";
		$db2->query($sql2);

		if ($db2->next_record())
		{
			$categories[$db->f("idcat")]["level"] = $db2->f("level");
		}

		$sql2 = "SELECT a.title AS title, b.idcatart AS idcatart FROM
		                ".$cfg["tab"]["art_lang"]." AS a,  ".$cfg["tab"]["cat_art"]." AS b
		    			WHERE b.idcat = '".$db->f("idcat")."' AND a.idart = b.idart AND
		    			a.idlang = '".Contenido_Security::toInteger($lang)."'";

		$db2->query($sql2);

		while ($db2->next_record())
		{
			$categories[$db->f("idcat")]["articles"][$db2->f("idcatart")] = $db2->f("title");
		}

	}

	foreach ($categories as $tmpidcat => $props)
	{
		$spaces = "&nbsp;&nbsp;";

		for ($i = 0; $i < $props["level"]; $i ++)
		{
			$spaces .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
		}

		$tmp_val = $tmpidcat;

		if ($sValue != $tmp_val)
		{
			$html .= '<option value="'.$tmp_val.'" style="background-color:#EFEFEF">'.$spaces.">".$props["name"].'</option>';

		} else
		{

			$html .= '<option value="'.$tmp_val.'" style="background-color:#EFEFEF" selected="selected">'.$spaces.">".$props["name"].'</option>';
		}
	}

	$html .= '</select>';

	return $html;
}

/**
 * getFileExtension
 * Returns the file extension of a given file
 *
 * @param string $filename Name of the file
 * @return extension on success, false if no extension could be extracted.
 */
function getFileExtension($filename)
{
	$dotposition = strrpos($filename, ".");

	if ($dotposition !== false)
	{
		return (strtolower(substr($filename, $dotposition +1)));
	} else
	{
		return false;
	}
}

function human_readable_size($number)
{
	$base = 1024;
	$suffixes = array (" B", " KB", " MB", " GB", " TB", " PB", " EB");

	$usesuf = 0;
	$n = (float) $number; //Appears to be necessary to avoid rounding
	while ($n >= $base)
	{
		$n /= (float) $base;
		$usesuf ++;
	}

	$places = 2 - floor(log10($n));
	$places = max($places, 0);
	$retval = number_format($n, $places, ".", "").$suffixes[$usesuf];
	return $retval;
}

/* Define "is_a" if it doesn't exist */
if (!function_exists('is_a'))
{
	function is_a($object, $className)
	{
		return ((strtolower($className) == get_class($object)) or (is_subclass_of($object, $className)));
	}
}

/**
 * Trims an array
 *
 * @param array Array to trim
 * @return array Trimmed array
 */
function trim_array($array)
{
	if (!is_array($array))
	{
		return $array;
	}

	foreach ($array as $key => $value)
	{
		$array[$key] = trim($value);
	}

	return $array;
}

function array_csort()
{ //coded by Ichier2003
	$args = func_get_args();
	$marray = array_shift($args);
	$msortline = "return(array_multisort(";
	$i = 0;
	foreach ($args as $arg)
	{
		$i ++;
		if (is_string($arg))
		{
			foreach ($marray as $row)
			{
				$a = strtoupper($row[$arg]);
				$sortarr[$i][] = $a;
			}
		} else
		{
			$sortarr[$i] = $arg;
		}
		$msortline .= "\$sortarr[".$i."],";
	}
	$msortline .= "\$marray));";
	@ eval ($msortline);
	return $marray;
}

if (!function_exists("str_ireplace"))
{

	function str_ireplace($find, $replace, $string)
	{
		if (!is_array($find))
			$find = array ($find);
		if (!is_array($replace))
			$replace = array ($replace);
		foreach ($find as $fKey => $fItem)
		{
			$between = explode(strtolower($fItem), strtolower($string));
			$pos = 0;
			foreach ($between as $bKey => $bItem)
			{
				$between[$bKey] = substr($string, $pos, strlen($bItem));
				$pos += strlen($bItem) + strlen($fItem);
			}
			$string = implode($replace[$fKey], $between);
		}
		return ($string);
	}

}

/**
 * str_ireplace_once - Replaces a string only once
 *
 * Caution: This function only takes strings as parameters,
 *          not arrays!
 * @param $find string String to find
 * @param $replace string String to replace
 * @param $subject string String to process
 *
 * @return string Processed string
 */
function str_ireplace_once($find, $replace, $subject)
{
	$start = strpos(strtolower($subject), strtolower($find));

	if ($start === false)
	{
		return $subject;
	}

	$end = $start +strlen($find);

	$first = substr($subject, 0, $start);
	$last = substr($subject, $end, strlen($subject) - $end);

	$result = $first.$replace.$last;

	return ($result);
}

/**
 * str_ireplace_once_reverse - Replaces a string only once, in reverse direction
 *
 * Caution: This function only takes strings as parameters,
 *          not arrays!
 * @param $find string String to find
 * @param $replace string String to replace
 * @param $subject string String to process
 *
 * @return string Processed string
 */
function str_ireplace_once_reverse($find, $replace, $subject)
{
	$start = str_rpos(strtolower($subject), strtolower($find));

	if ($start === false)
	{
		return $subject;
	}

	$end = $start +strlen($find);

	$first = substr($subject, 0, $start);
	$last = substr($subject, $end, strlen($subject) - $end);

	$result = $first.$replace.$last;

	return ($result);
}

/**
 * str_rpos - Finds a string position in reverse direction
 *
 * NOTE: The original strrpos-Function of PHP4 only finds
 *		 a single character as needle.
 *
 * @param $haystack string  String to search in
 * @param $needle   string  String to search for
 * @param $start    integer Offset
 *
 * @return string Processed string
 */
function str_rpos($haystack, $needle, $start = 0)
{
	$tempPos = strpos($haystack, $needle, $start);

	if ($tempPos === false)
	{
		if ($start == 0)
		{
			//Needle not in string at all
			return false;
		} else
		{
			//No more occurances found
			return $start -strlen($needle);
		}
	} else
	{
		//Find the next occurance
		return str_rpos($haystack, $needle, $tempPos +strlen($needle));
	}
}

/**
 * isImageMagickAvailable - checks if ImageMagick is available
 *
 * @return boolean true if ImageMagick is available
 */
function isImageMagickAvailable()
{
    global $cfg;
	global $_imagemagickAvailable;

	if (is_bool($_imagemagickAvailable))
	{
		return $_imagemagickAvailable;
	}

    // check, if escapeshellarg or exec function is disabled, we need both
    if (isFunctionDisabled('escapeshellarg') || isFunctionDisabled('exec'))
    {
        $_imagemagickAvailable = false;
        return $_imagemagickAvailable;
    }

	$output = array();
	$retval = 0;
    $program = escapeshellarg($cfg['images']['image_magick']['path'] . 'convert');
	exec("'{$program}' -version", $output, $retval);

    if (!is_array($output) || count($output) == 0)
    {
		$_imagemagickAvailable = false;
    }
    elseif (strpos($output[0], "ImageMagick") !== false)
    {
        $_imagemagickAvailable = true;
    }
    else
    {
        $_imagemagickAvailable = false;
    }

    return $_imagemagickAvailable;
}

/**
 * isRunningFromWeb - checks if the script is being runned from the web
 *
 * @return boolean true if the script is running from the web
 */
function isRunningFromWeb()
{
	if ($_SERVER["PHP_SELF"] == "" || php_sapi_name() == "cgi" || php_sapi_name() == "cli")
	{
		return false;
	}

	return true;
}

/**
 * getClientName: Returns the client name for a given ID
 *
 * @return string client name
 */
function getClientName($idclient)
{
	global $cfg;

	$db = new DB_Contenido;

	$sql = "SELECT name FROM ".$cfg["tab"]["clients"]." WHERE idclient='".Contenido_Security::toInteger($idclient)."'";

	$db->query($sql);

	if ($db->next_record())
	{
		return $db->f("name");
	} else
	{
		return false;
	}
}

/* Compatibility with PHP < 4.3.0 */
if (!function_exists('file_get_contents'))
{
	function file_get_contents($filename, $use_include_path = 0)
	{
		$data = '';
		$file = @ fopen($filename, "rb", $use_include_path);
		if ($file)
		{
			while (!feof($file))
				$data .= fread($file, 1024);
			fclose($file);
		} else
		{
			/* There was a problem opening the file. */
			return FALSE;
		}
		return $data;
	}
}

if (!function_exists("file_put_contents"))
{
	function file_put_contents($filename, $content)
	{
		if (!$file = fopen($filename, "wb+"))
		{
			return false;
		}
		if ($file)
		{
			if (!fwrite($file, $content))
			{
				return false;
			}

			fclose($file);
		}
		return true;
	}
}

function scanDirectory($sDirectory, $bRecursive = false)
{
	if (substr($sDirectory, strlen($sDirectory) - 1, 1) == "/")
	{
		$sDirectory = substr($sDirectory, 0, strlen($sDirectory) - 1);
	}

	if (!is_dir($sDirectory))
	{
		return false;
	}

	$aFiles = array ();

	$hDirHandle = opendir($sDirectory);

	while ($sFile = readdir($hDirHandle))
	{
		$sFullpathFile = $sDirectory."/".$sFile;

		if (is_file($sFullpathFile) && is_readable($sFullpathFile))
		{
			$aFiles[] = $sFullpathFile;
		}

		if (is_dir($sFullpathFile) && $sFile != "." && $sFile != ".." && $bRecursive == true)
		{
			$aSubFiles = scanDirectory($sFullpathFile, $bRecursive);

			if (is_array($aSubFiles))
			{
				$aFiles = array_merge($aFiles, $aSubFiles);
			}
		}
	}

	return $aFiles;
}

/**
 * scanPlugins: Scans a given plugin directory and places the
 * 				found plugins into the array $cfg['plugins']
 *
 *
 * Example:
 * scanPlugins("frontendusers");
 *
 * Result:
 * $cfg['plugins']['frontendusers'] => array with all found plugins
 *
 * Note: Plugins are only "found" if the following directory structure
 *       if found:
 *
 * entity/
 *        plugin1/plugin1.php
 *        plugin2/plugin2.php
 *
 * The plugin's directory and file name have to be the
 * same, otherwise the function won't find them!
 *
 * @param $entity Name of the directory to scan
 * @return string client name
 */
function scanPlugins($entity)
{
	global $cfg;

	$pluginorder = getSystemProperty("plugin", $entity."-pluginorder");
	$lastscantime = getSystemProperty("plugin", $entity."-lastscantime");

	$plugins = array ();

	/* Fetch and trim the plugin order */
	if ($pluginorder != "")
	{
		$plugins = explode(",", $pluginorder);

		foreach ($plugins as $key => $plugin)
		{
			$plugins[$key] = trim($plugin);
		}
	}

	$basedir = $cfg["path"]["contenido"].$cfg["path"]["plugins"]."$entity/";

	/* Don't scan all the time, but each 60 seconds */
	if ($lastscantime +60 < time())
	{
		setSystemProperty("plugin", $entity."-lastscantime", time());

		$dh = opendir($basedir);

		while (($file = readdir($dh)) !== false)
		{

			if (is_dir($basedir.$file) && $file != "includes" && $file != "." && $file != "..")
			{
				if (!in_array($file, $plugins))
				{
					if (file_exists($basedir.$file."/".$file.".php"))
					{
						$plugins[] = $file;
					}
				}
			}
		}

		foreach ($plugins as $key => $value)
		{
			if (!is_dir($basedir.$value) || !file_exists($basedir.$value."/".$value.".php"))
			{
				unset ($plugins[$key]);
			}
		}

		$pluginorder = implode(",", $plugins);
		setSystemProperty("plugin", $entity."-pluginorder", $pluginorder);
	}

	foreach ($plugins as $key => $value)
	{
		if (!is_dir($basedir.$value) || !file_exists($basedir.$value."/".$value.".php"))
		{
			unset ($plugins[$key]);
		} else
		{
			i18nRegisterDomain($entity . "_" . $value, $basedir.$value."/locale/");
		}
	}

	$cfg['plugins'][$entity] = $plugins;

}

/**
 * includePlugins: Includes plugins for a given entity
 *
 * Example:
 * includePlugins("frontendusers");
 *
 * @param $entity Name of the directory to scan
 */
function includePlugins($entity)
{
	global $cfg;

	if (is_array($cfg['plugins'][$entity]))
	{
		foreach ($cfg['plugins'][$entity] as $plugin)
		{
			plugin_include($entity, $plugin."/".$plugin.".php");
		}
	}
}

/**
 * callPluginStore: Calls the plugin's store methods
 *
 * Example:
 * callPluginStore("frontendusers");
 *
 * @param $entity Name of the directory to scan
 */
function callPluginStore($entity)
{
	global $cfg;

	/* Check out if there are any plugins */
	if (is_array($cfg['plugins'][$entity]))
	{
		foreach ($cfg['plugins'][$entity] as $plugin)
		{
			if (function_exists($entity."_".$plugin."_wantedVariables") && function_exists($entity."_".$plugin."_store"))
			{
				$wantVariables = call_user_func($entity."_".$plugin."_wantedVariables");

				if (is_array($wantVariables))
				{
					$varArray = array ();

					foreach ($wantVariables as $value)
					{
						$varArray[$value] = stripslashes($GLOBALS[$value]);
					}
				}
				$store = call_user_func($entity."_".$plugin."_store", $varArray);
			}
		}
	}
}

function displayPlugin($entity, & $form)
{
	/* TODO: Function can't work, as $feuser is not defined (see $display =
	 * call_user_func($entity."_".$plugin."_display", $feuser);) and plugins need
	 * - if data has to be shown - global objects ...
	 */
	$pluginOrder = trim_array(explode(",", getSystemProperty("plugin", $entity."-pluginorder")));

	/* Check out if there are any plugins */
	if (is_array($pluginOrder))
	{
		foreach ($pluginOrder as $plugin)
		{
			if (function_exists($entity."_".$plugin."_getTitle") && function_exists($entity."_".$plugin."_display"))
			{
				$plugTitle = call_user_func($entity."_".$plugin."_getTitle");
				$display = call_user_func($entity."_".$plugin."_display", $feuser);

				if (is_array($plugTitle) && is_array($display))
				{
					foreach ($plugTitle as $key => $value)
					{
						$form->add($value, $display[$key]);
					}
				} else
				{
					if (is_array($plugTitle) || is_array($display))
					{
						$form->add("WARNING", "The plugin $plugin delivered an array for the displayed titles, but did not return an array for the contents.");
					} else
					{
						$form->add($plugTitle, $display);
					}
				}
			}
		}
	}
}

/**
 * createRandomName: Creates a random name (example: Passwords)
 *
 * Example:
 * echo createRandomName(8);
 *
 * @param $nameLength Length of the generated string
 * @return string random name
 */
function createRandomName($nameLength)
{
	$NameChars = 'abcdefghijklmnopqrstuvwxyz';
	$Vouel = 'aeiou';
	$Name = "";

	for ($index = 1; $index <= $nameLength; $index ++)
	{
		if ($index % 3 == 0)
		{
			$randomNumber = rand(1, strlen($Vouel));
			$Name .= substr($Vouel, $randomNumber -1, 1);
		} else
		{
			$randomNumber = rand(1, strlen($NameChars));
			$Name .= substr($NameChars, $randomNumber -1, 1);
		}
	}

	return $Name;
}

/**
 * sendPostRequest: Sents a HTTP POST request
 *
 * Example:
 * sendPostRequest("hostname", "serverpath/test.php", $data);
 *
 * @param $host 	Hostname or domain
 * @param $pathhost Path on the host or domain
 * @param $data		Data to send
 * @param $referer	Referer (optional)
 * @param $port		Port (default: 80)
 */
function sendPostRequest($host, $path, $data, $referer = "", $port = 80)
{
	$fp = fsockopen($host, $port);

	fputs($fp, "POST $path HTTP/1.1\n");
	fputs($fp, "Host: $host\n");
	fputs($fp, "Referer: $referer\n");
	fputs($fp, "Content-type: application/x-www-form-urlencoded\n");
	fputs($fp, "Content-length: ".strlen($data)."\n");
	fputs($fp, "Connection: close\n\n");
	fputs($fp, "$data\n");

	while (!feof($fp))
	{
		$res .= fgets($fp, 128);
	}

	fclose($fp);

	return $res;
}

/**
 * mime_content_type: Define if it doesn't exist
 *
 * @param $file	File to check
 * @return string mime-type
 */
if (!function_exists('mime_content_type'))
{
	function mime_content_type($file)
	{
        // Use PECL fileinfo if available
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME);
            return $finfo->file($file);
        }

		if (false === isFunctionDisabled('escapeshellarg') && false === isFunctionDisabled('exec')) {
            return exec('file -bi ' . escapeshellarg($file));
        }

        // Use fallback alternative, without Fileinfo which requires PECL fileinfo
		$ext = strtolower(end(explode('.', $filename)));
		$mimeTypes = array(
            'ai' =>'application/postscript',
			'aif' =>'audio/x-aiff',
			'aifc' =>'audio/x-aiff',
			'aiff' =>'audio/x-aiff',
			'asc' =>'text/plain',
			'atom' =>'application/atom+xml',
			'avi' =>'video/x-msvideo',
			'bcpio' =>'application/x-bcpio',
			'bmp' =>'image/bmp',
			'cdf' =>'application/x-netcdf',
			'cgm' =>'image/cgm',
			'cpio' =>'application/x-cpio',
			'cpt' =>'application/mac-compactpro',
			'crl' =>'application/x-pkcs7-crl',
			'crt' =>'application/x-x509-ca-cert',
			'csh' =>'application/x-csh',
			'css' =>'text/css',
			'dcr' =>'application/x-director',
			'dir' =>'application/x-director',
			'djv' =>'image/vnd.djvu',
			'djvu' =>'image/vnd.djvu',
			'doc' =>'application/msword',
			'dtd' =>'application/xml-dtd',
			'dvi' =>'application/x-dvi',
			'dxr' =>'application/x-director',
			'eps' =>'application/postscript',
			'etx' =>'text/x-setext',
			'ez' =>'application/andrew-inset',
			'gif' =>'image/gif',
			'gram' =>'application/srgs',
			'grxml' =>'application/srgs+xml',
			'gtar' =>'application/x-gtar',
			'hdf' =>'application/x-hdf',
			'hqx' =>'application/mac-binhex40',
			'html' =>'text/html',
			'html' =>'text/html',
			'ice' =>'x-conference/x-cooltalk',
			'ico' =>'image/x-icon',
			'ics' =>'text/calendar',
			'ief' =>'image/ief',
			'ifb' =>'text/calendar',
			'iges' =>'model/iges',
			'igs' =>'model/iges',
			'jpe' =>'image/jpeg',
			'jpeg' =>'image/jpeg',
			'jpg' =>'image/jpeg',
			'js' =>'application/x-javascript',
			'kar' =>'audio/midi',
			'latex' =>'application/x-latex',
			'm3u' =>'audio/x-mpegurl',
			'man' =>'application/x-troff-man',
			'mathml' =>'application/mathml+xml',
			'me' =>'application/x-troff-me',
			'mesh' =>'model/mesh',
			'mid' =>'audio/midi',
			'midi' =>'audio/midi',
			'mif' =>'application/vnd.mif',
			'mov' =>'video/quicktime',
			'movie' =>'video/x-sgi-movie',
			'mp2' =>'audio/mpeg',
			'mp3' =>'audio/mpeg',
			'mpe' =>'video/mpeg',
			'mpeg' =>'video/mpeg',
			'mpg' =>'video/mpeg',
			'mpga' =>'audio/mpeg',
			'ms' =>'application/x-troff-ms',
			'msh' =>'model/mesh',
			'mxu m4u' =>'video/vnd.mpegurl',
			'nc' =>'application/x-netcdf',
			'oda' =>'application/oda',
			'ogg' =>'application/ogg',
			'pbm' =>'image/x-portable-bitmap',
			'pdb' =>'chemical/x-pdb',
			'pdf' =>'application/pdf',
			'pgm' =>'image/x-portable-graymap',
			'pgn' =>'application/x-chess-pgn',
			'php' =>'application/x-httpd-php',
			'php4' =>'application/x-httpd-php',
			'php3' =>'application/x-httpd-php',
			'phtml' =>'application/x-httpd-php',
			'phps' =>'application/x-httpd-php-source',
			'png' =>'image/png',
			'pnm' =>'image/x-portable-anymap',
			'ppm' =>'image/x-portable-pixmap',
			'ppt' =>'application/vnd.ms-powerpoint',
			'ps' =>'application/postscript',
			'qt' =>'video/quicktime',
			'ra' =>'audio/x-pn-realaudio',
			'ram' =>'audio/x-pn-realaudio',
			'ras' =>'image/x-cmu-raster',
			'rdf' =>'application/rdf+xml',
			'rgb' =>'image/x-rgb',
			'rm' =>'application/vnd.rn-realmedia',
			'roff' =>'application/x-troff',
			'rtf' =>'text/rtf',
			'rtx' =>'text/richtext',
			'sgm' =>'text/sgml',
			'sgml' =>'text/sgml',
			'sh' =>'application/x-sh',
			'shar' =>'application/x-shar',
			'shtml' =>'text/html',
			'silo' =>'model/mesh',
			'sit' =>'application/x-stuffit',
			'skd' =>'application/x-koan',
			'skm' =>'application/x-koan',
			'skp' =>'application/x-koan',
			'skt' =>'application/x-koan',
			'smi' =>'application/smil',
			'smil' =>'application/smil',
			'snd' =>'audio/basic',
			'spl' =>'application/x-futuresplash',
			'src' =>'application/x-wais-source',
			'sv4cpio' =>'application/x-sv4cpio',
			'sv4crc' =>'application/x-sv4crc',
			'svg' =>'image/svg+xml',
			'swf' =>'application/x-shockwave-flash',
			't' =>'application/x-troff',
			'tar' =>'application/x-tar',
			'tcl' =>'application/x-tcl',
			'tex' =>'application/x-tex',
			'texi' =>'application/x-texinfo',
			'texinfo' =>'application/x-texinfo',
			'tgz' =>'application/x-tar',
			'tif' =>'image/tiff',
			'tiff' =>'image/tiff',
			'tr' =>'application/x-troff',
			'tsv' =>'text/tab-separated-values',
			'txt' =>'text/plain',
			'ustar' =>'application/x-ustar',
			'vcd' =>'application/x-cdlink',
			'vrml' =>'model/vrml',
			'vxml' =>'application/voicexml+xml',
			'wav' =>'audio/x-wav',
			'wbmp' =>'image/vnd.wap.wbmp',
			'wbxml' =>'application/vnd.wap.wbxml',
			'wml' =>'text/vnd.wap.wml',
			'wmlc' =>'application/vnd.wap.wmlc',
			'wmlc' =>'application/vnd.wap.wmlc',
			'wmls' =>'text/vnd.wap.wmlscript',
			'wmlsc' =>'application/vnd.wap.wmlscriptc',
			'wmlsc' =>'application/vnd.wap.wmlscriptc',
			'wrl' =>'model/vrml',
			'xbm' =>'image/x-xbitmap',
			'xht' =>'application/xhtml+xml',
			'xhtml' =>'application/xhtml+xml',
			'xls' =>'application/vnd.ms-excel',
			'xml xsl' =>'application/xml',
			'xpm' =>'image/x-xpixmap',
			'xslt' =>'application/xslt+xml',
			'xul' =>'application/vnd.mozilla.xul+xml',
			'xwd' =>'image/x-xwindowdump',
			'xyz' =>'chemical/x-xyz',
			'zip' =>'application/zip',
		);

		if (isset($mimeTypes[$ext])) {
			return $mimeTypes[$ext];
		} else {
			return 'application/octet-stream';
		}
	}
}

if (!function_exists('set_include_path'))
{
	function set_include_path($path)
	{
		ini_set('include_path', $path);
	}
}

if (!function_exists('get_include_path'))
{
	function get_include_path()
	{
		return (ini_get('include_path'));
	}
}

if (!function_exists('restore_include_path'))
{
	function restore_include_path()
	{
		return (ini_restore('include_path'));
	}
}

function is_dbfs($file)
{
	if (substr($file, 0, 5) == "dbfs:")
	{
		return true;
	}
}

function setHelpContext($area)
{
	global $cfg;

	if ($cfg['help'] == true)
	{
		$hc = "parent.parent.parent.frames[0].document.getElementById('help').setAttribute('data', '$area');";
	} else
	{
		$hc = "";
	}

	return $hc;
}

function define_if($constant, $value)
{
	if (!defined($constant))
	{
		define($constant, $value);
	}
}

/**
 * Specify platform specific newline character; PHP_EOL has been introduced in PHP 5.0.2
 * Note, that Mac seems to use \r, sorry guys
 */
if (!defined('PHP_EOL'))
{
	if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
	{
		define ('PHP_EOL', "\r\n"); // Windows
	} else {
		define ('PHP_EOL', "\n");   // *nix
	}
}

function locale_arsort($locale, $array)
{
	$oldlocale = setlocale(LC_COLLATE, 0);
	setlocale(LC_COLLATE, $locale);

	uasort($array, "strcoll");

	setlocale(LC_COLLATE, $oldlocale);

	return ($array);
}

/* TODO: Ask timo to document this. */
/* Note: If subarrays exists, this function currently returns the key of the array
   given by $array, and not from the subarrays (todo: add flag to allow this) */
function array_search_recursive($search, $array, $partial = false, $strict = false)
{
	foreach ($array as $key => $value)
	{
		if (is_array($value))
		{
			$val = array_search_recursive($search, $value, $partial, $strict);
			if ($val !== false)
			{
				return ($key);
			}
		} else
		{
			if ($partial == false)
			{
				if ($strict == true)
				{
					if ($value === $search)
					{
						return $key;
					}
				} else
				{
					if ($value == $search)
					{
						return $key;
					}
				}
			} else
			{
				if (strpos($value, $search) !== FALSE)
				{
					return $key;
				}
			}

		}
	}

	return false;
}

if (!function_exists('stripos'))
{
	function stripos($haystack, $needle, $offset = 0)
	{
		return (strpos(strtolower($haystack), strtolower($needle), $offset));
	}
}

if (!function_exists('array_combine'))
{

	function & array_combine($keys, $values)
	{
		if (count($keys) != count($values))
			return false;

		$keys = array_values($keys);
		$values = array_values($values);

		$newarray = array ();

		foreach ($keys as $index => $key)
			$newarray[$key] = $values[$index];

		return $newarray;
	}

}

/**
 * cDie: Contenido die-alternative
 *
 * @param $file	   File name   (use __FILE__)
 * @param $line    Line number (use __LINE__)
 * @param $message Message to display
 */
function cDie($file, $line, $message)
{
	cError("$file $line: $message");
	die("$file $line: $message");
}

/**
 * cWarning: Contenido warning
 *
 * @param $file	   File name   (use __FILE__)
 * @param $line    Line number (use __LINE__)
 * @param $message Message to display
 */
function cWarning($file, $line, $message)
{
	trigger_error("$file $line: $message", E_USER_WARNING);
}

/**
 * cError: Contenido error
 *
 * @param $file	   File name   (use __FILE__)
 * @param $line    Line number (use __LINE__)
 * @param $message Message to display
 */
function cError($file, $line, $message)
{
	trigger_error("$file $line: $message", E_USER_ERROR);
}

/**
 * getNamedFrame: Returns the name of the numeric frame given
 *
 * @param $frame   Frame number
 * @return string  Canonical name of the frame
 */
function getNamedFrame($frame)
{
	switch ($frame)
	{
		case 1 :
			return ("left_top");
			break;
		case 2 :
			return ("left_bottom");
			break;
		case 3 :
			return ("right_top");
			break;
		case 4 :
			return ("right_bottom");
			break;
		default :
			return ("");
			break;
	}
}

/**
 * startTiming: Starts the timing for a specific function
 *
 * @param function string Name of the function
 * @param parameters array All parameters for the function to measure
 *
 * @return int uuid for this measure process
 */
function startTiming($function, $parameters = array ())
{
	global $_timings, $cfg;

	if ($cfg["debug"]["functiontiming"] == false)
	{
		return;
	}

	/* Create (almost) unique ID */
	$uuid = md5(uniqid(rand(), true));

	if (!is_array($parameters))
	{
		cWarning(__FILE__, __LINE__, "Warning: startTiming's parameters parameter expects an array");
		$parameters = array ();
	}

	$_timings[$uuid]["parameters"] = $parameters;
	$_timings[$uuid]["function"] = $function;

	$_timings[$uuid]["start"] = getmicrotime();

	return $uuid;
}

/**
 * endAndLogTiming: Ends the timing process and logs it to the timings file
 *
 * @param uuid int UUID which has been used for timing
 */
function endAndLogTiming($uuid)
{
	global $_timings, $cfg;

	if ($cfg["debug"]["functiontiming"] == false)
	{
		return;
	}

	$_timings[$uuid]["end"] = getmicrotime();

	$timeSpent = $_timings[$uuid]["end"] - $_timings[$uuid]["start"];

	$myparams = array ();

	/* Build nice representation of the function */
	foreach ($_timings[$uuid]["parameters"] as $parameter)
	{

		switch (gettype($parameter))
		{
			case "string" :
				$myparams[] = '"'.$parameter.'"';
				break;

			case "boolean" :
				if ($parameter == true)
				{
					$myparams[] = "true";
				} else
				{
					$myparams[] = "false";
				}
				break;

			default :
				if ($parameter == "")
				{
					$myparams[] = '"'.$parameter.'"';
				} else
				{
					$myparams[] = $parameter;
				}
		}
	}

	$parameterString = implode(", ", $myparams);

	trigger_error("calling function ".$_timings[$uuid]["function"]."(".$parameterString.") took ".$timeSpent." seconds", E_USER_NOTICE);
}

// @TODO: it's better to create a instance of DB_Contenido class, the class constructor connects also to the database.
function checkMySQLConnectivity() {

	global $contenido_host, $contenido_database, $contenido_user, $contenido_password, $cfg;

	if ($cfg["database_extension"] == "mysqli") {
		if (($iPos = strpos($contenido_host, ":")) !== false) {
			list($sHost, $sPort) = explode(":", $contenido_host);
            if(is_numeric($sPort)) {
			     $res = @ mysqli_connect($sHost, $contenido_user, $contenido_password, "", $sPort);
            } else {
			     $res = @ mysqli_connect($sHost, $contenido_user, $contenido_password, "", 0, $sPort);
            }
		} else {
			$res = @ mysqli_connect($contenido_host, $contenido_user, $contenido_password);
		}
	} else {
		$res = @ mysql_connect($contenido_host, $contenido_user, $contenido_password);
	}

	$selectDb = false;
	if ( $res ) {
		if ($cfg["database_extension"] == "mysqli") {
			$selectDb = @ mysqli_select_db($res, $contenido_database);
		} else {
			$selectDb = @ mysql_select_db($contenido_database);
		}
	}

	if ( !$res || !$selectDb ) {

		$errortitle = i18n("MySQL Database not reachable for installation %s");
		$errortitle = sprintf($errortitle, $cfg["path"]["contenido_fullhtml"]);

		$errormessage = i18n("The MySQL Database for the installation %s is not reachable. Please check if this is a temporary problem or if it is a real fault.");
		$errormessage = sprintf($errormessage, $cfg["path"]["contenido_fullhtml"]);

		notifyOnError($errortitle, $errormessage);

		if ($cfg["contenido"]["errorpage"] != "")
		{
			header("Location: ".$cfg["contenido"]["errorpage"]);
		} else {
			die("Could not connect to the database server with this configuration!");
		}
		exit;
	} else
	{
		if ($cfg["database_extension"] == "mysqli")
		{
			mysqli_close($res);
		} else {
			mysql_close($res);
		}
	}
}

function notifyOnError($errortitle, $errormessage)
{
	global $cfg;

	if (file_exists($cfg["path"]["contenido"]."logs/notify.txt"))
	{
		$notifytimestamp = file_get_contents($cfg["path"]["contenido"]."logs/notify.txt");
	} else
	{
		$notifytimestamp = 0;
	}

	if ((time() - $notifytimestamp) > $cfg["contenido"]["notifyinterval"] * 60)
	{
		if ($cfg['contenido']['notifyonerror'] != "") {
			$sMailhost = getSystemProperty('system', 'mail_host');
			if ($sMailhost == '') {
				$sMailhost = 'localhost';
			}

			$oMail = new PHPMailer;
			$oMail->Host = $sMailhost;
			$oMail->IsHTML(0);
			$oMail->WordWrap = 1000;
			$oMail->IsMail();

			$oMail->AddAddress($cfg["contenido"]["notifyonerror"], "");
			$oMail->Subject = $errortitle;
			$oMail->Body = $errormessage;

			/* Notify configured email */
			$oMail->Send();
		}
		/* Write last notify log file */
		file_put_contents($cfg["path"]["contenido"]."logs/notify.txt", time());
	}
}

function cIDNAEncode($sourceEncoding, $string)
{
	if (extension_loaded("iconv"))
	{
		cInclude("pear", "Net/IDNA.php");

		$idn = Net_IDNA :: getInstance();

		$string = iconv("UTF-8", $sourceEncoding, $string);
		$string = $idn->encode($string);

		return ($string);
	}

	if (extension_loaded("recode"))
	{
		cInclude("pear", "Net/IDNA.php");

		$idn = Net_IDNA :: getInstance();

		$string = $idn->decode($string);
		$string = recode_string("UTF-8..".$sourceEncoding, $string);
		return $string;
	}

	return $string;

}

function cIDNADecode($targetEncoding, $string)
{
	if (extension_loaded("iconv"))
	{
		cInclude("pear", "Net/IDNA.php");

		$idn = Net_IDNA :: getInstance();

		$string = $idn->decode($string);
		$string = iconv($targetEncoding, "UTF-8", $string);

		return ($string);
	}

	if (extension_loaded("recode"))
	{
		cInclude("pear", "Net/IDNA.php");

		$idn = Net_IDNA :: getInstance();

		$string = recode_string($targetEncoding."..UTF-8", $string);
		$string = $idn->decode($string);

		return $string;
	}

	return $string;

}

function cInitializeArrayKey (&$aArray, $sKey, $mDefault = "")
{
	if (!is_array($aArray))
	{
		if (isset($aArray))
		{
			return false;
		}

		$aArray = array();
	}

	if (!array_key_exists($sKey, $aArray))
	{
		$aArray[$sKey] = $mDefault;
	}
}

/**
 * Function checks current language
 * and client settings by HTTP-Params
 * and DB settings. Based on this
 * informations it will send an HTTP
 * header for right encoding.
 *
 * @param DB_Contenido $db
 * @param array $cfg global cfg-array
 * @param int $lang global language id
 *
 * @since 4.6.18
 *
 * @version 1.0.0
 * @author Holger Librenz
 */
function sendEncodingHeader ($db, $cfg, $lang) {
    if (array_key_exists("use_encoding", $_GET))
    {
        $use_encoding = trim(strip_tags($_GET["use_encoding"]));
    }

    if (array_key_exists("use_encoding", $_POST))
    {
        $use_encoding = trim(strip_tags($_POST["use_encoding"]));
    }

    if (!isset($use_encoding))
    {
        $use_encoding = true;
    }

    if (is_string($use_encoding))
    {
        if ($use_encoding == "false")
        {
            $use_encoding = false;
        } else {
            $use_encoding = true;
        }
    }

    if ($use_encoding != false)
    {
        $sql = "SELECT idlang, encoding FROM ".$cfg["tab"]["lang"];
        $db->query($sql);

        $aLanguageEncodings = array();

        while ($db->next_record())
        {
            $aLanguageEncodings[$db->f("idlang")] = $db->f("encoding");
        }

        if (array_key_exists($lang, $aLanguageEncodings))
        {
            if (!in_array($aLanguageEncodings[$lang], $cfg['AvailableCharsets']))
            {
                header("Content-Type: text/html; charset=ISO-8859-1");
            } else {
                header("Content-Type: text/html; charset={$aLanguageEncodings[$lang]}");
            }
        } else {
            header("Content-Type: text/html; charset=ISO-8859-1");
        }

    }
}

/**
 * Checks, if a function is disabled or not ('disable_functions' setting in php.ini)
 * @param  string  $functionName  Name of the function to check
 * @return bool
 */
function isFunctionDisabled($functionName) {
    static $disabledFunctions;

    if (empty($functionName)) {
        return true;
    }

    if (!isset($disabledFunctions)) {
        $disabledFunctions = array_map('trim', explode(',', ini_get('disable_functions')));
    }

    return (in_array($functionName, $disabledFunctions));
}

?>
