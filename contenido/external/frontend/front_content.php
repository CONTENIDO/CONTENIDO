<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * This file handles the view of an article.
 *
 * To handle the page we use the Database Abstraction Layer, the Session, Authentication and Permissions Handler of the
 * PHPLIB application development toolkit.
 *
 * The Client Id and the Language Id of an article will be determined depending on file __FRONTEND_PATH__/config.php where
 * $load_lang and $load_client are defined.
 * Depending on http globals via e.g. front_content.php?idcat=41&idart=34
 * the most important Contenido globals $idcat (Category Id), $idart (Article Id), $idcatart, $idartlang will be determined.
 *
 * The article can be displayed and edited in the Backend or the Frontend.
 * The attributes of an article will be considered (an article can be online, offline or protected ...).
 *
 * It is possible to customize the behavior by including the file __FRONTEND_PATH__/config.local.php or
 * the file __FRONTEND_PATH__/config.after.php
 *
 * If you use 'Frontend User' for protected areas, the category access permission will by handled via the
 * Contenido Extension Chainer.
 *
 * Finally the 'code' of an article will by evaluated and displayed.
 *
 * Requirements: 
 * @con_php_req 5.0
 * @con_note If you edit this file you must synchronise the files
 * ./contenido/external/frontend/front_content.php
 * and
 * ./contenido/external/backendedit/front_content.php
 * 
 *
 * @package    Contenido Backend external
 * @version    1.8.6
 * @author     Olaf Niemann, Jan Lengowski, Timo A. Hummel et al.
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * 
 * {@internal 
 *   created 2003-01-21
 *   modified 2008-07-02, Frederic Schneider, add security fix
 *   modified 2008-08-29, Murat Purc, synchronised with /cms/front_content.php
 *   modified 2008-09-07, Murat Purc, new chain 'Contenido.Frontend.AfterLoadPlugins'
 *
 *   $Id$:
 * }}
 * 
 */

if (!defined("CON_FRAMEWORK")) {
    define("CON_FRAMEWORK", true);
}

$contenido_path = '';
# include the config file of the frontend to init the Client and Language Id
include_once ("config.php");

// include security class and check request variables
include_once ($contenido_path . 'classes/class.security.php');
Contenido_Security::checkRequests();

if (isset($_REQUEST['belang'])) {
	$aValid = array('de_DE', 'en_US', 'fr_FR', 'it_IT', 'nl_NL');
	if (!in_array(strval($_REQUEST['belang']), $aValid)) {
		die('Please use a valid language!');
	}
}

# Contenido startup process
include_once ($contenido_path."includes/startup.php");

// check HTTP parameters, if requested
if ($cfg['http_params_check']['enabled'] === true) {
	cInclude('classes', 'class.httpinputvalidator.php');
	$oHttpInputValidator =
		new HttpInputValidator($cfg["path"]["contenido"] . $cfg["path"]["includes"] . '/config.http_check.php');
}

cInclude("includes", "functions.con.php");
cInclude("includes", "functions.con2.php");
cInclude("includes", "functions.api.php");
cInclude("includes", "functions.pathresolver.php");

if ($cfg["use_pseudocron"] == true)
{
	/* Include cronjob-Emulator */
	$oldpwd = getcwd();
	chdir($cfg["path"]["contenido"].$cfg["path"]["cronjobs"]);
	cInclude("includes", "pseudo-cron.inc.php");
	chdir($oldpwd);
}

/*
 * Initialize the Database Abstraction Layer, the Session, Authentication and Permissions Handler of the
 * PHPLIB application development toolkit
 * @see http://sourceforge.net/projects/phplib
 */
if ($contenido)
{
	//Backend
	page_open(array ('sess' => 'Contenido_Session', 'auth' => 'Contenido_Challenge_Crypt_Auth', 'perm' => 'Contenido_Perm'));
	i18nInit($cfg["path"]["contenido"].$cfg["path"]["locale"], $belang);
}
else
{
	//Frontend
	page_open(array ('sess' => 'Contenido_Frontend_Session', 'auth' => 'Contenido_Frontend_Challenge_Crypt_Auth', 'perm' => 'Contenido_Perm'));
}

/**
 * Bugfix
 * @see http://contenido.org/forum/viewtopic.php?t=18291
 *
 * added by H. Librenz (2007-12-07)
 */
//includePluginConf();
/**
 * fixed bugfix - using functions brokes variable scopes!
 *
 * added by H. Librenz (2007-12-21) based on an idea of A. Lindner
 */
require_once $cfg['path']['contenido'] . $cfg['path']['includes'] . 'functions.includePluginConf.php';

// Call hook after plugins are loaded, added by Murat Purc, 2008-09-07
CEC_Hook::execute('Contenido.Frontend.AfterLoadPlugins');

$db = new DB_Contenido;

$sess->register("cfgClient");
$sess->register("errsite_idcat");
$sess->register("errsite_idart");
$sess->register("encoding");

if ($cfgClient["set"] != "set")
{
	rereadClients();
}

$sql = "SELECT idlang, encoding FROM ".$cfg["tab"]["lang"];
$db->query($sql);
// get encodings of all languages
while ($db->next_record())
{
	$encoding[$db->f("idlang")] = $db->f("encoding");
}

if (is_numeric($tmpchangelang) && $tmpchangelang > 0)
{
	$savedlang = $lang;
	$lang = $tmpchangelang;
}

// Checking basic data input
if (isset($changeclient) && !is_numeric($changeclient)) {
	unset ($changeclient);
}

if (isset($client) && !is_numeric($client)) {
	unset ($client);
}

if (isset($changelang) && !is_numeric($changelang)) {
	unset ($changelang);
}

if (isset($lang) && !is_numeric($lang)) {
	unset ($lang);
}

// Change client
if (isset($changeclient)){
    $client = $changeclient;
    unset($lang);
    unset($load_lang);
}

// Change language
if (isset($changelang)) $lang = $changelang;

// Initialize client
if (!isset($client)) {
    //load_client defined in frontend/config.php
    $client = $load_client;
}

// Initialize language
if (!isset($lang)) {

    // if there is an entry load_lang in frontend/config.php use it, else use the first language of this client
    if(isset($load_lang)){
        // load_client is set in frontend/config.php
        $lang = $load_lang;
    }else{

        $sql = "SELECT
                    B.idlang
                FROM
                    ".$cfg["tab"]["clients_lang"]." AS A,
                    ".$cfg["tab"]["lang"]." AS B
                WHERE
                    A.idclient='".Contenido_Security::toInteger($client)."' AND
                    A.idlang = B.idlang
                LIMIT
                    0,1";

        $db->query($sql);
        $db->next_record();

        $lang = $db->f("idlang");
    }
}

if (!$sess->is_registered("lang") ) $sess->register("lang");
if (!$sess->is_registered("client") ) $sess->register("client");

if (isset ($username))
{
	$auth->login_if(true);
}

/*
 * Send HTTP header with encoding
 */
header("Content-Type: text/html; charset={$encoding[$lang]}");

/*
 * if http global logout is set e.g. front_content.php?logout=true
 * log out the current user.
 */
if (isset ($logout))
{
	$auth->logout(true);
	$auth->unauth(true);
	$auth->auth["uname"] = "nobody";
}

/*
 * local configuration
 */
if (file_exists("config.local.php"))
{
	@ include ("config.local.php");
}

/*
 * If the path variable was passed, try to resolve it to a Category Id
 * e.g. front_content.php?path=/company/products/
 */
if (isset($path) && strlen($path) > 1)
{
	/* Which resolve method is configured? */
	if ($cfg["urlpathresolve"] == true)
	{
		
		$iLangCheck = 0;	
		$idcat = prResolvePathViaURLNames($path, $iLangCheck);

	}
	else
	{
		$iLangCheck = 0;	
		
		$idcat = prResolvePathViaCategoryNames($path, $iLangCheck);
		if($lang != iLangCheck){
			$lang = $iLangCheck;
		}
		
	}
}


// error page
$errsite = "Location: front_content.php?client=$client&idcat=".$errsite_idcat[$client]."&idart=".$errsite_idart[$client]."&lang=$lang&error=1";

/*
 * Try to initialize variables $idcat, $idart, $idcatart, $idartlang
 * Note: These variables can be set via http globals e.g. front_content.php?idcat=41&idart=34&idcatart=35&idartlang=42
 * If not the values will be computed.
 */
if ($idart && !$idcat && !$idcatart)
{
	/* Try to fetch the first idcat */
	$sql = "SELECT idcat FROM ".$cfg["tab"]["cat_art"]." WHERE idart = '".Contenido_Security::toInteger($idart)."'";
	$db->query($sql);

	if ($db->next_record())
	{
		$idcat = $db->f("idcat");
	}
}

unset ($code);
unset ($markscript);

if (!$idcatart)
{
	if (!$idart)
	{
		if (!$idcat)
		{
			# Note: In earlier Contenido versions the information if an article is startarticle of a category has been stored
			# in relation con_cat_art.
			if ($cfg["is_start_compatible"] == true)
			{
				$sql = "SELECT
                            idart,
                            B.idcat
                        FROM
                            ".$cfg["tab"]["cat_art"]." AS A,
                            ".$cfg["tab"]["cat_tree"]." AS B,
                            ".$cfg["tab"]["cat"]." AS C
                        WHERE
                            A.idcat=B.idcat AND
                            B.idcat=C.idcat AND
                            is_start='1' AND
                            idclient='".Contenido_Security::toInteger($client)."'
                        ORDER BY
                            idtree ASC";
			}
			else
			{
				# Note: Now the information if an article is startarticle of a category is stored in relation con_cat_lang.
				$sql = "SELECT
                            A.idart,
                            B.idcat
                        FROM
                            ".$cfg["tab"]["cat_art"]." AS A,
                            ".$cfg["tab"]["cat_tree"]." AS B,
                            ".$cfg["tab"]["cat"]." AS C,
							".$cfg["tab"]["cat_lang"]." AS D,
							".$cfg["tab"]["art_lang"]." AS E
                        WHERE
                            A.idcat=B.idcat AND
                            B.idcat=C.idcat AND
							D.startidartlang = E.idartlang AND
							D.idlang='".Contenido_Security::toInteger($lang)."' AND
							E.idart=A.idart AND
							E.idlang='".Contenido_Security::toInteger($lang)."' AND
                            idclient='".Contenido_Security::toInteger($client)."'
                        ORDER BY
                            idtree ASC";
			}

			$db->query($sql);

			if ($db->next_record())
			{
				$idart = $db->f("idart");
				$idcat = $db->f("idcat");
			}
			else
			{
				if ($contenido)
				{
					cInclude("includes", "functions.i18n.php");
					die(i18n("No start article for this category"));
				}
				else
				{
					if ($error == 1)
					{
						echo "Fatal error: Could not display error page. Error to display was: 'No start article in this category'";
					}
					else
					{
						header($errsite);
					}
				}
			}
		}
		else
		{
			$idart = -1;
			if ($cfg["is_start_compatible"] == true)
			{
				$sql = "SELECT idart FROM ".$cfg["tab"]["cat_art"]." WHERE idcat='".Contenido_Security::toInteger($idcat)."' AND is_start='1'";
				$db->query($sql);

				if ($db->next_record())
				{
					$idart = $db->f("idart");
				}
			}
			else
			{
				$sql = "SELECT startidartlang FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat='".Contenido_Security::toInteger($idcat)."' AND idlang='".Contenido_Security::toInteger($lang)."'";
				$db->query($sql);

				if ($db->next_record())
				{
					if ($db->f("startidartlang") != 0)
					{
						$sql = "SELECT idart FROM ".$cfg["tab"]["art_lang"]." WHERE idartlang='".Contenido_Security::toInteger($db->f("startidartlang"))."'";
						$db->query($sql);
						$db->next_record();
						$idart = $db->f("idart");
					}
				}
			}

			if ($idart != -1)
			{
			}
			else
			{
				// error message in backend
				if ($contenido)
				{
					cInclude("includes", "functions.i18n.php");
					die(i18n("No start article for this category"));
				}
				else
				{
					if ($error == 1)
					{
						echo "Fatal error: Could not display error page. Error to display was: 'No start article in this category'";
					}
					else
					{
						header($errsite);
					}
				}
			}
		}
	}
}
else
{
	$sql = "SELECT idcat, idart FROM ".$cfg["tab"]["cat_art"]." WHERE idcatart='".Contenido_Security::toInteger($idcatart)."'";

	$db->query($sql);
	$db->next_record();

	$idcat = $db->f("idcat");
	$idart = $db->f("idart");
}

/* Get idcatart */
if (0 != $idart && 0 != $idcat)
{
	$sql = "SELECT idcatart FROM ".$cfg["tab"]["cat_art"]." WHERE idart = '".Contenido_Security::toInteger($idart)."' AND idcat = '".Contenido_Security::toInteger($idcat)."'";

	$db->query($sql);
	$db->next_record();

	$idcatart = $db->f("idcatart");
}

$idartlang = getArtLang($idart, $lang);

if ($idartlang === false)
{
	header($errsite);
}

/*
 * removed database roundtrip for checking
 * if cache is enabled
 * CON-115
 * 2008-06-25 Thorsten Granz
 */
// START: concache, murat purc
if ($cfg["cache"]["disable"] != '1') {
	cInclude('frontend', 'includes/concache.php');
	$oCacheHandler = new cConCacheHandler($GLOBALS['cfgConCache'], $db);
	$oCacheHandler->start($iStartTime); // $iStartTime ist optional und ist die startzeit des scriptes, z. b. am anfang von fron_content.php
}
// END: concache


##############################################
# BACKEND / FRONTEND EDITING
##############################################

/**
 * If user has contenido-backend rights.
 * $contenido <==> the cotenido backend session as http global
 * In Backend: e.g. contenido/index.php?contenido=dac651142d6a6076247d3afe58c8f8f2
 * Can also be set via front_content.php?contenido=dac651142d6a6076247d3afe58c8f8f2
 *
 * Note: In backend the file contenido/external/backendedit/front_content.php is included!
 * The reason is to avoid cross-site scripting errors in the backend, if the backend domain differs from
 * the frontend domain.
 */
if ($contenido)
{
	cInclude("classes", 'class.inuse.php');
	cInclude("classes", 'class.user.php');
	cInclude("classes", 'class.table.php');
	cInclude("classes", 'class.notification.php');

	$perm->load_permissions();

	/* Change mode edit / view */
	if (isset ($changeview))
	{
		$sess->register("view");
		$view = $changeview;
	}

	$col = new InUseCollection;

	if ($overrideid != "" && $overridetype != "")
	{
		$col->removeItemMarks($overridetype, $overrideid);
	}
	/* Remove all own marks */
	$col->removeSessionMarks($sess->id);
	/* If the override flag is set, override a specific InUseItem */

	list ($inUse, $message) = $col->checkAndMark("article", $idartlang, true, i18n("Article is in use by %s (%s)"), true, $cfg['path']['contenido_fullhtml']."external/backendedit/front_content.php?changeview=edit&action=con_editart&idartlang=$idartlang&type=$type&typenr=$typenr&idart=$idart&idcat=$idcat&idcatart=$idcatart&client=$client&lang=$lang");

    $sHtmlInUse = '';
    $sHtmlInUseMessage = '';
	if ($inUse == true)
	{
		$disabled = 'disabled="disabled"';
        $sHtmlInUseCss = '<link rel="stylesheet" type="text/css" href="'.$cfg['path']['contenido_fullhtml'].'styles/inuse.css" />';
        $sHtmlInUseMessage = $message;
    }

	$sql = "SELECT locked FROM ".$cfg["tab"]["art_lang"]." WHERE idart='".Contenido_Security::toInteger($idart)."' AND idlang = '".Contenido_Security::toInteger($lang)."'";
	$db->query($sql);
	$db->next_record();
	$locked = $db->f("locked");
	if ($locked == 1)
	{
		$inUse = true;
		$disabled = 'disabled="disabled"';
	}

	/* Check if the user has permission to edit articles in this category */
	$allow = true;
    CEC_Hook::setBreakCondition(CEC_Hook::BREAK_AT_FALSE);
    $value = CEC_Hook::execute("Contenido.Frontend.AllowEdit", $lang, $idcat, $idart, $auth->auth["uid"]);
    if ($value === false)
    {
        $allow = false;
    }

	if ($perm->have_perm_area_action_item("con_editcontent", "con_editart", $idcat) && $inUse == false && $allow == true)
	{
		/* Create buttons for editing */
		$edit_preview = '<table cellspacing="0" cellpadding="4" border="0">';

		if ($view == "edit")
		{
			$edit_preview = '<tr>
                                <td width="18">
                                    <a title="Preview" style="font-family: Verdana; font-size: 10px; color: #000000; text-decoration: none" href="'.$sess->url("front_content.php?changeview=prev&idcat=$idcat&idart=$idart").'"><img src="'.$cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"].'but_preview.gif" alt="Preview" title="Preview" border="0"></a>
                                </td>
                                <td width="18">
                                    <a title="Preview" style="font-family: Verdana; font-size: 10px; color: #000000; text-decoration: none" href="'.$sess->url("front_content.php?changeview=prev&idcat=$idcat&idart=$idart").'">Preview</a>
                                </td>
                            </tr>';
		}
		else
		{
			$edit_preview = '<tr>
                                <td width="18">
                                    <a title="Preview" style="font-family: Verdana; font-size: 10px; color: #000000; text-decoration: none" href="'.$sess->url("front_content.php?changeview=edit&idcat=$idcat&idart=$idart").'"><img src="'.$cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"].'but_edit.gif" alt="Preview" title="Preview" border="0"></a>
                                </td>
                                <td width="18">
                                    <a title="Preview" style="font-family: Verdana; font-size: 10px; color: #000000; text-decoration: none" href="'.$sess->url("front_content.php?changeview=edit&idcat=$idcat&idart=$idart").'">Edit</a>
                                </td>
                            </tr>';
		}

		/* Display articles */
		if ($cfg["is_start_compatible"] == true)
		{
			$sql = "SELECT idart, is_start FROM ".$cfg["tab"]["cat_art"]." WHERE idcat='".Contenido_Security::toInteger($idcat)."' ORDER BY idart";

			$db->query($sql);
		}
		else
		{
			$sql = "SELECT idart FROM ".$cfg["tab"]["cat_art"]." WHERE idcat='".Contenido_Security::toInteger($idcat)."' ORDER BY idart";

			$db->query($sql);
		}

		$a = 1;

		$edit_preview .= '<tr><td colspan="2"><table cellspacing="0" cellpadding="2" border="0"></tr><td style="font-family: verdana; font-size:10; color:#000000; text-decoration:none">Articles in category:<br>';

		while ($db->next_record() && ($db->affected_rows() != 1))
		{

			$class = "font-family:'Verdana'; font-size:10; color:#000000; text-decoration: underline; font-weight:normal";
			if (!isset ($idart))
			{
				if (isStartArticle(getArtLang($idart, $lang), $idcat, $lang))
				{
					$class = "font-family: verdana; font-size:10; color:#000000; text-decoration: underline ;font-weight:bold";
				}
			}
			else
			{
				if ($idart == $db->f("idart"))
				{
					$class = "font-family: verdana; font-size:10; color:#000000; text-decoration: underline; font-weight:bold";
				}
			}

			$edit_preview .= "<a style=\"$class\" href=\"".$sess->url("front_content.php?idart=".$db->f("idart")."&idcat=$idcat")."\">$a</a>&nbsp;";
			$a ++;
		}

		$edit_preview .= '</td></tr></table></td></tr></table>';

	}

} // end if $contenido


/* If mode is 'edit' and user has permission to edit articles in the current category  */
if ($inUse == false && $allow == true && $view == "edit" && ($perm->have_perm_area_action_item("con_editcontent", "con_editart", $idcat)))
{
	cInclude("includes", "functions.tpl.php");
	cInclude("includes", "functions.con.php");
	include ($cfg["path"]["contenido"].$cfg["path"]["includes"]."include.con_editcontent.php");
}
else
{

##############################################
# FRONTEND VIEW
##############################################

	/* Mark submenuitem 'Preview' in the Contenido Backend (Area: Contenido --> Articles --> Preview) */
	if ($contenido)
	{
		$markscript = markSubMenuItem(4, true);
	}

	unset($edit); // disable editmode

	/* 'mode' is preview (Area: Contenido --> Articles --> Preview) or article displayed in the front-end */
	$sql = "SELECT
                createcode
            FROM
                ".$cfg["tab"]["cat_art"]."
            WHERE
                idcat = '".Contenido_Security::toInteger($idcat)."' AND
                idart = '".Contenido_Security::toInteger($idart)."'";

	$db->query($sql);
	$db->next_record();

	##############################################
	# code generation
	##############################################

	/* Check if code is expired, create new code if needed */
	if ($db->f("createcode") == 0 && $force == 0)
	{
		$sql = "SELECT code FROM ".$cfg["tab"]["code"]." WHERE idcatart = '".Contenido_Security::toInteger($idcatart)."' AND idlang = '".Contenido_Security::toInteger($lang)."'";
		$db->query($sql);

		if ($db->num_rows() == 0)
		{
			/* Include here for performance reasons */
			cInclude("includes", "functions.tpl.php");

			conGenerateCode($idcat, $idart, $lang, $client);

			$sql = "SELECT code FROM ".$cfg["tab"]["code"]." WHERE idcatart = '".Contenido_Security::toInteger($idcatart)."' AND idlang = '".Contenido_Security::toInteger($lang)."'";
			$db->query($sql);
		}

		if ($db->next_record())
		{
			$code = stripslashes($db->f("code"));
		}
		else
		{
			if ($contenido)
				$code = "echo \"No code available.\";";
			else
			{
				if ($error == 1)
				{
					echo "Fatal error: Could not display error page. Error to display was: 'No code available'";
				}
				else
				{
					header($errsite);
				}
			}
		}
	}
	else
	{
		$sql = "DELETE FROM ".$cfg["tab"]["code"]." WHERE idcatart = '".Contenido_Security::toInteger($idcatart)."'";
		$db->query($sql);

		cInclude("includes", "functions.con.php");
		cInclude("includes", "functions.tpl.php");
		cInclude("includes", "functions.mod.php");

		conGenerateCode($idcat, $idart, $lang, $client);

		$sql = "SELECT code FROM ".$cfg["tab"]["code"]." WHERE idcatart = '".Contenido_Security::toInteger($idcatart)."' AND idlang = '".Contenido_Security::toInteger($lang)."'";

		$db->query($sql);
		$db->next_record();

		$code = stripslashes($db->f("code"));
	}

	/*  Add mark Script to code if user is in the backend */
	$code = preg_replace("/<\/head>/i", "$markscript\n</head>", $code, 1);

    /* If article is in use, display notification */
    if ($sHtmlInUseCss && $sHtmlInUseMessage) {
        $code = preg_replace("/<\/head>/i", "$sHtmlInUseCss\n</head>", $code, 1);
        $code = preg_replace("/(<body[^>]*)>/i", "\${1}> \n $sHtmlInUseMessage", $code, 1);
    }

	/* Check if category is public */
	$sql = "SELECT public FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat='".Contenido_Security::toInteger($idcat)."' AND idlang='".Contenido_Security::toInteger($lang)."'";

	$db->query($sql);
	$db->next_record();

	$public = $db->f("public");

	##############################################
	# protected categories
	##############################################
	if ($public == 0)
	{
		if ($auth->auth["uid"] == "nobody")
		{
			$sql = "SELECT user_id, value FROM ".$cfg["tab"]["user_prop"]." WHERE type='frontend' and name='allowed_ip'";
			$db->query($sql);

			while ($db->next_record())
			{
				$user_id = $db->f("user_id");

				$range = urldecode($db->f("value"));
				$slash = strpos($range, "/");

				if ($slash == false)
				{
					$netmask = "255.255.255.255";
					$network = $range;
				}
				else
				{
					$network = substr($range, 0, $slash);
					$netmask = substr($range, $slash +1, strlen($range) - $slash -1);
				}

				if (IP_match($network, $netmask, $_SERVER["REMOTE_ADDR"]))
				{
					$sql = "SELECT idright
							FROM ".$cfg["tab"]["rights"]." AS A,
								 ".$cfg["tab"]["actions"]." AS B,
								 ".$cfg["tab"]["area"]." AS C
							 WHERE B.name = 'front_allow' AND C.name = 'str' AND A.user_id = '".Contenido_Security::escapeDB($user_id, $db2)."' AND A.idcat = '".Contenido_Security::toInteger($idcat)."'
									AND A.idarea = C.idarea AND B.idaction = A.idaction";

					$db2 = new DB_Contenido;
					$db2->query($sql);

					if ($db2->num_rows() > 0)
					{
						$auth->auth["uid"] = $user_id;
						$validated = 1;
					}
				}
			}
			if ($validated != 1)
			{
				$allow = false;

                CEC_Hook::setBreakCondition(CEC_Hook::BREAK_AT_TRUE);
                $value = CEC_Hook::execute("Contenido.Frontend.CategoryAccess", $lang, $idcat, $auth->auth["uid"]);
                if ($value === true)
                {
                    $allow = true;
                }

				$auth->login_if(!$allow);
			}
		}
		else
		{
			$allow = false;

            CEC_Hook::setBreakCondition(CEC_Hook::BREAK_AT_TRUE);
            $value = CEC_Hook::execute("Contenido.Frontend.CategoryAccess", $lang, $idcat, $auth->auth["uid"]);
            if ($value === true)
            {
                $allow = true;
            }

			if (!$allow)
			{
				header($errsite);
			}
		}
	}

	##############################################
	# statistic
	##############################################
	/* Sanity: If the statistic table doesn't contain an entry, create one */
	$sql = "SELECT idcatart FROM ".$cfg["tab"]["stat"]." WHERE idcatart = '".Contenido_Security::toInteger($idcatart)."' AND idlang='".Contenido_Security::toInteger($lang)."'";
	$db->query($sql);

	if ($db->next_record())
	{
		/* Update the statistics. */
		$sql = "UPDATE ".$cfg["tab"]["stat"]." SET visited = visited + 1 WHERE idcatart = '".Contenido_Security::toInteger($idcatart)."' AND idclient = '".Contenido_Security::toInteger($client)."'
                AND idlang = '".Contenido_Security::toInteger($lang)."'";
		$db->query($sql);
	}
	else
	{
		/* Insert new record */
		$next = $db->nextid($cfg["tab"]["stat"]);
		$sql = "INSERT INTO ".$cfg["tab"]["stat"]." (visited, idcatart, idlang, idstat, idclient) VALUES ('1', '".Contenido_Security::toInteger($idcatart)."', '".Contenido_Security::toInteger($lang)."',
                '".Contenido_Security::toInteger($next)."', '".Contenido_Security::toInteger($client)."')";
		$db->query($sql);
	}

	/*
	 * Check if an article is start article of the category
	 */
	if ($cfg["is_start_compatible"] == true)
	{
		$sql = "SELECT is_start FROM ".$cfg["tab"]["cat_art"]." WHERE idcatart='".Contenido_Security::toInteger($idcatart)."'";
		$db->query($sql);
		$db->next_record();
		$isstart = $db->f("is_start");
	}
	else
	{
		$sql = "SELECT startidartlang FROM ".$cfg["tab"]["cat_lang"]." WHERE idcat='".Contenido_Security::toInteger($idcat)."' AND idlang = '".Contenido_Security::toInteger($lang)."'";
		$db->query($sql);
		$db->next_record();
		if ($db->f("idartlang") == $idartlang)
		{
			$isstart = 1;
		}
		else
		{
			$isstart = 0;
		}
	}

	##############################################
	# time management
	##############################################
	$sql = "SELECT timemgmt FROM ".$cfg["tab"]["art_lang"]." WHERE idart='".Contenido_Security::toInteger($idart)."' AND idlang = '".Contenido_Security::toInteger($lang)."'";
	$db->query($sql);
	$db->next_record();

	if (($db->f("timemgmt") == "1") && ($isstart != 1))
	{
		$sql = "SELECT online, redirect, redirect_url FROM ".$cfg["tab"]["art_lang"]." WHERE idart='".Contenido_Security::toInteger($idart)."' AND idlang = '".Contenido_Security::toInteger($lang)."'
                AND NOW() > datestart AND NOW() < dateend";
	}
	else
	{
		$sql = "SELECT online, redirect, redirect_url FROM ".$cfg["tab"]["art_lang"]." WHERE idart='".Contenido_Security::toInteger($idart)."' AND idlang = '".Contenido_Security::toInteger($lang)."'";
	}

	$db->query($sql);
	$db->next_record();

	$online = $db->f("online");
	$redirect = $db->f("redirect");
	$redirect_url = $db->f("redirect_url");

	@ eval ("\$"."redirect_url = \"$redirect_url\";"); // transform variables

	$insert_base = getEffectiveSetting('generator', 'basehref', "true");

	/*
	 * generate base url
	 */
	if ($insert_base == "true")
	{
		$is_XHTML = getEffectiveSetting('generator', 'xhtml', "false");

		$str_base_uri = $cfgClient[$client]["path"]["htmlpath"];

        $str_base_uri = CEC_Hook::execute("Contenido.Frontend.BaseHrefGeneration", $str_base_uri);

		if ($is_XHTML == "true") {
			$baseCode = '<base href="'.$str_base_uri.'" />';
		} else {
			$baseCode = '<base href="'.$str_base_uri.'">';
		}

		$code = str_ireplace_once("<head>", "<head>\n".$baseCode, $code);
	}

	/*
	 * Handle online (offline) articles
	 */
	if ($online)
	{
		if ($redirect == '1' && $redirect_url != '')
		{
			page_close();
			/*
			 * Redirect to the URL defined in article properties
			 */
			header("Location: $redirect_url");
			exit;
		}
		else
		{
			if ($cfg["debug"]["codeoutput"])
			{
				echo "<textarea>".htmlspecialchars($code)."</textarea>";
			}

			/*
			 * That's it! The code of an article will be evaluated.
			 * The code of an article is basically a PHP script which is cached in the database.
			 * Layout and Modules are merged depending on the Container definitions of the Template.
			 */

            $aExclude = explode(',', getEffectiveSetting('frontend.no_outputbuffer', 'idart', ''));
            if (in_array(Contenido_Security::toInteger($idart), $aExclude)) {
    			eval ("?>\n".$code."\n<?php\n");
            } else {
    			// write html output into output buffer and assign it to an variable
    			ob_start();
            	eval ("?>\n".$code."\n<?php\n");
    			$htmlCode = ob_get_contents();
    			ob_end_clean();
    			
    			// process CEC Hook to do some preparations before output
                $htmlCode = CEC_Hook::execute('Contenido.Frontend.HTMLCodeOutput', $htmlCode);
                
    			// print output
    			echo $htmlCode;
            }
             
		}
	}
	else
	{
		# if user is in the backend display offline articles
		if ($contenido)
		{
			eval ("?>\n".$code."\n<?php\n");
		}
		else
		{
			if ($error == 1)
			{
				echo "Fatal error: Could not display error page. Error to display was: 'No contenido session variable set. Probable error cause: Start article in this category is not set on-line.'";
			}
			else
			{
				header($errsite);
			}
		}
	}
}

/*
 * removed database roundtrip for checking
 * if cache is enabled
 * CON-115
 * 2008-06-25 Thorsten Granz
 */
// START: concache, murat purc
if ($cfg["cache"]["disable"] != '1') {
	$oCacheHandler->end();
	#echo $oCacheHandler->getInfo();
}
// END: concache

/*
 * configuration settings after the site is displayed.
 */
if (file_exists("config.after.php"))
{
	@ include ("config.after.php");
}

if (isset ($savedlang))
{
	$lang = $savedlang;
}

page_close();

/**
 * IP_match
 *
 * @param string $network
 * @param string $mask
 * @param string $ip
 * @return boolean
 */
function IP_match($network, $mask, $ip)
{

	bcscale(3);
	$ip_long = ip2long($ip);
	$mask_long = ip2long($network);

	#
	# Convert mask to divider
	#
	if (ereg("^[0-9]+$", $mask))
	{
		/// 212.50.13.0/27 style mask (Cisco style)
		$divider = bcpow(2, (32 - $mask));
	}
	else
	{
		/// 212.50.13.0/255.255.255.0 style mask
		$xmask = ip2long($mask);
		if ($xmask < 0)
			$xmask = bcadd(bcpow(2, 32), $xmask);
		$divider = bcsub(bcpow(2, 32), $xmask);
	}
	#
	# Test is IP within specified mask
	#
	if (floor(bcdiv($ip_long, $divider)) == floor(bcdiv($mask_long, $divider)))
	{
		# match - this IP is within specified mask
		return true;
	}
	else
	{
		# fail - this IP is NOT within specified mask
		return false;
	}
}

?>