<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Login form
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend
 * @version    1.0.4
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 *
 * {@internal
 *   created  2003-01-21
 *   modified 2008-06-17, Rudi Bieller, some ugly fix for possible abuse of belang...
 *   modified 2008-07-02, Frederic Schneider, add security fix
 *   modified 2010-05-20, Murat Purc, removed request check during processing ticket [#CON-307]
 *   modified 2010-05-25, Dominik Ziegler, Remove password and username maxlength definitions at backend login [#CON-314]
 *   modified 2010-05-27, Dominik Ziegler, restored maxlength definition for username at backend login [#CON-314]
 *
 *   $Id: main.loginform.php 1168 2010-05-27 09:03:52Z dominik.ziegler $:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}


global $cfg, $username;

$aLangs = i18nStripAcceptLanguages($_SERVER['HTTP_ACCEPT_LANGUAGE']);

foreach ($aLangs as $sValue)
{
    $sEncoding = i18nMatchBrowserAccept($sValue);
    $GLOBALS['belang'] = $sEncoding;

    if ($sEncoding !== false)
    {
        break;
    }
}

if (isset($_POST['belang']) && $_POST['belang'] != '') {
    $sSelectedLang = $_POST['belang'];
    $GLOBALS['belang'] = $sSelectedLang;
}

$noti = "";
if (getenv('CONTENIDO_IGNORE_SETUP') != "true")
{
	$aMessages = array();

	// Check, if setup folder is still available
	if (file_exists(dirname(dirname(__FILE__))."/setup"))
	{
		$aMessages[] = i18n("The setup directory still exists. Please remove the setup directory before you continue.");
	}

	// Check, if sysadmin and/or admin accounts are still using well-known default passwords
	$db   = new DB_Contenido;

    $sDate = date('Y-m-d');
	$sSQL = "SELECT * FROM ".$cfg["tab"]["phplib_auth_user_md5"]."
			 WHERE (username = 'sysadmin' AND password = '48a365b4ce1e322a55ae9017f3daf0c0'
                    AND (valid_from <= '".Contenido_Security::escapeDB($sDate, $db)."' OR valid_from = '0000-00-00' OR valid_from is NULL) AND
                   (valid_to >= '".Contenido_Security::escapeDB($sDate, $db)."' OR valid_to = '0000-00-00' OR valid_to is NULL))
				 OR (username = 'admin' AND password = '21232f297a57a5a743894a0e4a801fc3'
                     AND (valid_from <= '".Contenido_Security::escapeDB($sDate, $db)."' OR valid_from = '0000-00-00' OR valid_from is NULL) AND
                    (valid_to >= '".Contenido_Security::escapeDB($sDate, $db)."' OR valid_to = '0000-00-00' OR valid_to is NULL))
                   ";
	$db->query($sSQL);

	if ($db->num_rows() > 0)
	{
		$aMessages[] = i18n("The sysadmin and/or the admin account still contains a well-known default password. Please change immediately after login.");
	}
	unset ($db);

	if (getSystemProperty('maintenance', 'mode') == 'enabled') {
        $aMessages[] = i18n("Contenido is in maintenance mode. Only sysadmins are allowed to login. Please try again later.");
    }

	if (count($aMessages) > 0)
	{
		$notification = new Contenido_Notification;
		$noti = $notification->messageBox("warning", implode("<br />", $aMessages), 1). "<br />";
	}
}

header("Content-Type: text/html; charset=ISO-8859-1");

?>
<html>
<head>
		<base href="<?php echo $cfg['path']['contenido_fullhtml'] ?>" />
    <title>:: :: :: :: Contenido Login</title>
    <link rel="stylesheet" type="text/css" href="styles/contenido.css" />
		<link REL="SHORTCUT ICON" HREF="<?php echo $cfg["path"]["contenido_fullhtml"]."favicon.ico"; ?>" />
    <script type="text/javascript" src="scripts/md5.js"></script>
    <script type="text/javascript" src="scripts/str_overview.js"></script>

    <script type="text/javascript">
        if(top!=self)
        {
            top.location="index.php";
        }

        function doChallengeResponse()
        {
            str = document.login.username.value + ":" +
    MD5(document.login.password.value) + ":" +
    document.login.challenge.value;

            document.login.response.value = MD5(str);
            document.login.password.value = "";
            document.login.submit();

        }
    </script>

</head>
<body>
<div style="border-top: 1px solid #0060b1;"></div>
<div style="height:110px;overflow:hidden;">
    <div id="head">
    	<a id="head_logo" href="http://www.contenido.org"><img title="Contenido Website." alt="Contenido Website." src="images/conlogo.gif" /></a>

    	<div id="head_content" class="left_menu_dist">
    		<div id="head_info" class="left_dist">
    			&nbsp;
    		</div>
    			<form name="login" method="post" action="<?php echo $this->url() ?>">
    			<div id="head_nav1" class="left_dist head_nav_login">

    				<select id="lang" name="belang" tabindex="3" class="text_medium" onchange="document.login.submit();">
    					<?php

    					$aAvailableLangs = i18nGetAvailableLanguages();

    					foreach ($aAvailableLangs as $sCode => $aEntry)
    					{
    						if (isset($cfg["login_languages"]))
    						{
    							if (in_array($sCode, $cfg["login_languages"]))
    							{
    								list($sLanguage, $sCountry, $sCodeSet, $sAcceptTag) = $aEntry;

                                    if ($sSelectedLang) {
                                        if ($sSelectedLang == $sCode) {
                                            $sSelected = ' selected="selected"';
                                        } else {
                                            $sSelected = '';
                                        }
                                    } else if ($sCode == $sEncoding) {
    									$sSelected = ' selected="selected"';
    								} else {
    									$sSelected = '';
    								}

    								echo '<option value="'.$sCode.'"'.$sSelected.'>'.$sLanguage.' ('.$sCountry.')</option>';
    							}
    						} else {
    							list($sLanguage, $sCountry, $sCodeSet, $sAcceptTag) = $aEntry;

                                if ($sSelectedLang) {
                                    if ($sSelectedLang == $sCode) {
                                        $sSelected = ' selected="selected"';
                                    } else {
                                        $sSelected = '';
                                    }
                                } else if ($sCode == $sEncoding) {
    								$sSelected = ' selected="selected"';
    							} else {
    								$sSelected = '';
    							}

    							echo '<option value="'.$sCode.'"'.$sSelected.'>'.$sLanguage.' ('.$sCountry.')</option>';
    						}
    					}
    					?>
    					</select>
                      <label id="lbllang" for="lang"><?php echo i18n("Language"); ?></label>

                      <div class="text_medium_bold login_title"><?php echo i18n("Contenido Backend"); ?></div>

    				    <label id="lblusername" for="username" style="width:75px; display:block; float:left;"><?php echo i18n("Login"); ?>:</label>
    				    <input id="username" tabindex="1" type="text" class="text_medium" name="username" size="25" maxlength="32" value="<?php echo ( isset($this->auth["uname"]) ) ? conHtmlentities(strip_tags($this->auth["uname"])) : ""  ?>" />
    			</div>
    			<div id="head_nav2" class="head_nav_login left_dist">
                    <input id="okbutton" tabindex="4" type="image" title="Login" alt="Login" src="images/but_ok.gif" />
                    <div style="float:right; margin-right:25px;" class="text_error">
                        <?php if ( isset($username) && $username != '') {
                                    echo i18n("Invalid Login or Password!");
                                }
                         ?>
                    </div>
                    <div style="clear:both;display:none;"></div>
                    <div class="text_medium_bold login_title">&nbsp;</div>

                    <label id="lblpasswd" for="passwd" style="width:75px; display:block; float:left;"><?php echo i18n("Password"); ?>:</label>
                    <input id="passwd" tabindex="2" type="password" class="text_medium" name="password" size="25" />

                    <input type="hidden" name="vaction" value="login" />
                    <input type="hidden" name="formtimestamp" value="<?php echo time(); ?>" />
    			</div>
    		</form>
    	</div>

    	<div id="navcontainer" style="border-top: 1px solid #666666;clear:left;">
        <?php
            //class implements passwort recovery, all functionality is implemented there
            $oRequestPassword = new RequestPassword($db, $cfg);
            $oRequestPassword->renderForm();
        ?>
    	</div>
    </div>

    <div style="background-color: #F1F1F1;height:80px;"></div>
    <div id="navcontainer" style="border-top: 1px solid #666666;"></div>
</div>
<div id="alertbox">
	<?php echo $noti; ?>
</div>

<script type="text/javascript">
    if (document.login.username.value == '')
    {
      document.login.username.focus();
    }
    else
    {
    	document.login.password.focus();
    }
</script>
<!-- <?php echo $cfg['datetag']; ?> -->
</body>
</html>