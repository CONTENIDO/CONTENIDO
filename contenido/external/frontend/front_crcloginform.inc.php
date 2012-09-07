<?php
/**
 * Project: 
 * Contenido Content Management System
 * 
 * Description: 
 * <Description>
 * 
 * Requirements: 
 * @con_php_req 5
 * @con_template <Templatefiles>
 * @con_notice <Notice>
 * 
 *
 * @package    Contenido Backend <Area>
 * @version    <version>
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <Contenido Version>
 * @deprecated file deprecated in contenido release <Contenido Version>
 * 
 * {@internal 
 *   created  2003-01-21
 *   modified 2005-09-29, Andreas Lindner
 *   modified 2008-07-04, bilal arslan, added security fix
 *   modified 2008-11-18, Murat Purc, add usage of Contenido_Url to create urls to frontend pages and redesign of HTML markup
 *   modified 2009-01-03, Murat Purc, synchronized with cms/front_crcloginform.inc.php
 *
 *   $Id: front_crcloginform.inc.php 931 2009-01-03 18:17:46Z xmurrix $:
 * }}
 * 
 */
if(!defined('CON_FRAMEWORK')) {
  die('Illegal call');
}

global $cfg, $idcat, $idart, $idcatart, $lang, $client, $username, $encoding;

$err_catart = trim(getEffectiveSetting("login_error_page", "idcatart", ""));
$err_cat    = trim(getEffectiveSetting("login_error_page", "idcat", ""));
$err_art    = trim(getEffectiveSetting("login_error_page", "idart", ""));

$oUrl = Contenido_Url::getInstance();

$sClientHtmlPath = $cfgClient[$client]['path']['htmlpath'];

$sUrl = $sClientHtmlPath . 'front_content.php';

$sErrorUrl = $sUrl;
$bRedirect = false;

if ($err_catart != '') {
    $sErrorUrl .= '?idcatart=' . $err_catart . '&lang=' . $lang;
    $bRedirect  = true;
} elseif ($err_art != '' && $err_cat != '') {
    $sErrorUrl .= '?idcat=' . $err_cat . '&idart=' . $err_art . '&lang=' . $lang;
    $bRedirect  = true;
} elseif ($err_cat != '') {
    $sErrorUrl .= '?idcat=' . $err_cat . '&lang=' . $lang;
    $bRedirect  = true;
} elseif ($err_art != '') {
    $sErrorUrl .= '?idart=' . $err_art . '&lang=' . $lang;
    $bRedirect  = true;
}

if ($bRedirect) {
    $aUrl = $oUrl->parse($sess->url($sErrorUrl));
    $sErrorUrl = $oUrl->buildRedirect($aUrl['params']);
    header('Location: ' . $sErrorUrl);
    exit();
}

if (isset($_GET['return']) || isset($_POST['return'])){
    $aLocator = array('lang=' . (int) $lang);

    if ($idcat > 0) {
        $aLocator[] = 'idcat=' . intval($idcat);
    }
    if ($idart > 0) {
        $aLocator[] = 'idart=' . intval($idart);
    }
    if (isset($_POST['username']) || isset($_GET['username'])){
        $aLocator[] = 'wrongpass=1';
    }

    $sErrorUrl = $sUrl . '?' . implode('&', $aLocator);
    $aUrl = $oUrl->parse($sess->url($sErrorUrl));
    $sErrorUrl = $oUrl->buildRedirect($aUrl['params']);
    header ('Location: ' . $sErrorUrl);
    exit();
}

// set form action
$sFormAction = $sess->url($sUrl . '?idcat=' . intval($idcat) . '&lang=' . $lang);
$aUrl = $oUrl->parse($sFormAction);
$sFormAction = $oUrl->build($aUrl['params']);

// set login input image, use button as fallback
if (!is_file($cfgClient[$client]['path']['frontend'] . 'images/but_ok.gif')) {
    $sLoginButton = '<input type="image" title="Login" alt="Login" src="' . $sClientHtmlPath . 'images/but_ok.gif" />' . "\n";
} else {
    $sLoginButton = '<input type="button" title="Login" value="Login" style="font-size:90%;font-weight:bold;background-color:' . $cfg['color']['table_header'] . ';border:1px solid ' . $cfg['color']['table_border'] . ';" />' . "\n";
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $encoding[$lang] ?>" /> 
    <title>:: :: :: :: Contenido Login</title>
    <script type="text/javascript"><!--
    if (top != self) {
        top.location.href = self.location.href;
    }
    // --></script>
    <style type="text/css"><!--
    * {margin:0; padding:0;}
    html, body {height: 100%;}
    body {background-color:#fff; font-family: Verdana, Arial, Helvetica, Sans-Serif; font-size: 11px; color:#000;}
    a img {border:none;}
    #loginPageWrap {
        width:230px; height:120px; text-align:center; border:1px solid <?php echo $cfg['color']['table_border'] ?>; background-color:<?php echo $cfg['color']['table_light'] ?>;
        position:absolute; left:50%; top:50%; margin-left:-115px; margin-top:-60px; 
    }
    #login {text-align:left;}
    #login label {display:block; float:left; width:70px; }
    #login input.text {float:right; width:130px; margin:0; }
    #login .formHeader {font-weight:bold; background-color:<?php echo $cfg['color']['table_header'] ?>; border-bottom:1px solid <?php echo $cfg['color']['table_border'] ?>; padding:3px; margin-bottom:10px;}
    #login .formRow {padding:0 10px; height:31px;}
    #login .clear {clear:both;}
    // --></style>
</head>
<body>

<div id="loginPageWrap">
    <form id="login" name="login" method="post" action="<?php echo $sFormAction; ?>">
        <input type="hidden" name="vaction" value="login" />
        <input type="hidden" name="formtimestamp" value="<?php echo time(); ?>" />
        <input type="hidden" name="idcat" value="<?php echo intval($idcat); ?>" />
        <div class="formHeader">Login</div>
        <div class="formRow">
            <label for="username">Username:</label><input type="text" class="text" name="username" id="username" size="20" maxlength="32" value="<?php echo ( isset($this->auth['uname']) ) ? $this->auth['uname'] : ''  ?>" /><br class="clear" />
        </div>
        <div class="formRow">
            <label for="password">Password:</label><input type="password" class="text" name="password" id="password" size="20" maxlength="32" /><br class="clear" />
        </div>
        <div class="formRow" style="text-align:right">
            <?php echo $sLoginButton ?>
        </div>
    </form>
</div>

<script type="text/javascript"><!--
if (document.login.username.value == '') {
    document.login.username.focus();
} else {
    document.login.password.focus();
}
// --></script>

</body>
</html>
