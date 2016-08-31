<?php

/**
 * Login form for client
 *
 * NOTE:
 * This file has to run in clients frontend directory!
 *
 * @package          Core
 * @subpackage       Frontend
 * @author           Jan Lengowski
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $cfg, $cfgClient, $idcat, $idart, $idcatart, $lang, $client, $username, $encoding;

$sess = cRegistry::getSession();

$err_catart = trim(getEffectiveSetting('login_error_page', 'idcatart', ''));
$err_cat    = trim(getEffectiveSetting('login_error_page', 'idcat', ''));
$err_art    = trim(getEffectiveSetting('login_error_page', 'idart', ''));

$oUrl = cUri::getInstance();

$sClientHtmlPath = cRegistry::getFrontendUrl();

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
    $aUrl['params']['wrongpass'] = 1;
    $sErrorUrl = $oUrl->buildRedirect($aUrl['params']);
    header('Location: ' . $sErrorUrl);
    exit();
}

if (isset($_GET['return']) || isset($_POST['return'])) {
    $aLocator = array('lang=' . (int) $lang);

    if ($idcat > 0) {
        $aLocator[] = 'idcat=' . (int) $idcat;
    }
    if ($idart > 0) {
        $aLocator[] = 'idart=' . (int) $idart;
    }
    if (isset($_POST['username']) || isset($_GET['username'])) {
        $aLocator[] = 'wrongpass=1';
    }

    $sErrorUrl = $sUrl . '?' . implode('&', $aLocator);
    $aUrl = $oUrl->parse($sess->url($sErrorUrl));
    $sErrorUrl = $oUrl->buildRedirect($aUrl['params']);
    header('Location: ' . $sErrorUrl);
    exit();
}

// set form action
$sFormAction = $sess->url($sUrl . '?idcat=' . (int) $idcat . '&lang=' . $lang);
$aUrl = $oUrl->parse($sFormAction);
$sFormAction = $oUrl->build($aUrl['params']);

// set login input image, use button as fallback
if (cFileHandler::exists(cRegistry::getFrontendPath() . 'images/but_ok.gif')) {
    $sLoginButton = '<input type="image" title="Login" alt="Login" src="' . $sClientHtmlPath . 'images/but_ok.gif">' . "\n";
} else {
    $sLoginButton = '<input id="login_button" type="submit" title="Login" value="Login">' . "\n";
}

$tpl = new cTemplate();

$tpl->set('s', 'CHARSET', $encoding[$lang]);
$tpl->set('s', 'FORM_ACTION', $sFormAction);
$tpl->set('s', 'FORM_TIMESTAMP', time());
$tpl->set('s', 'IDCAT', $idcat);
$tpl->set("s", "USERNAME", (isset($this->auth['uname'])) ? $this->auth['uname'] : '');
$tpl->set("s", "LOGINBUTTON", $sLoginButton);

$tpl->generate($cfg['path']['contenido'] . $cfg["path"]["templates"] . $cfg["templates"]["front_loginform"]);

?>