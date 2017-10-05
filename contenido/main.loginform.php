<?php
/**
 * This file handles the login into the backend.
 *
 * @package          Core
 * @subpackage       Backend
 * @author           Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $cfg, $username;

$aLangs = i18nStripAcceptLanguages($_SERVER['HTTP_ACCEPT_LANGUAGE']);

foreach ($aLangs as $sValue) {
    $sEncoding = i18nMatchBrowserAccept($sValue);
    $GLOBALS['belang'] = $sEncoding;
    if ($sEncoding !== false) {
        break;
    }
}

if (isset($_POST['belang']) && $_POST['belang'] != '') {
    $sSelectedLang = $_POST['belang'];
    $GLOBALS['belang'] = $sSelectedLang;
}

$noti = '';
if (getSystemProperty('maintenance', 'mode') == 'enabled') {
    $notification = new cGuiNotification();
    $noti = $notification->returnMessageBox('warning', i18n("CONTENIDO is in maintenance mode. Only sysadmins are allowed to login. Please try again later.") . '<br>');
}

// Fill template
$tpl = new cTemplate();
$tpl->reset();

$sess = cRegistry::getSession();

// CON-2714
// Please check at CONTENIDO backend login whether the database tables are filled
$db = new cDb();
$sql = $db->prepare('SELECT user_id FROM %s', $cfg['tab']['user']);
$db->query($sql);
if ($db->num_rows() == 0) {
    $notification = new cGuiNotification();
    $notification->displayNotification('error', i18n('Your database is obviously empty. Please ensure that you have installed CONTENIDO completely and/or that your database configuration is correct.'));
}

// Get backend label
$backend_label = getSystemProperty('backend', 'backend_label');
$backend_label = " " . $backend_label . " ";

$tpl->set('s', 'BASEPATH', cRegistry::getBackendUrl());
$tpl->set('s', 'TITEL', ':: ::' . $backend_label . ':: :: CONTENIDO Login');
$tpl->set('s', 'ACTION', $sess->selfURL());

$aAvailableLangs = i18nGetAvailableLanguages();
$str = '';
foreach ($aAvailableLangs as $sCode => $aEntry) {
    $addLanguageOption = false;
    if (isset($cfg['login_languages'])) {
        if (in_array($sCode, $cfg['login_languages'])) {
            $addLanguageOption = true;
        }
    } else {
        $addLanguageOption = true;
    }

    if ($addLanguageOption) {
        list($sLanguage, $sCountry, $sCodeSet, $sAcceptTag) = $aEntry;
        if ($sSelectedLang) {
            if ($sSelectedLang == $sCode) {
                $sSelected = ' selected="selected"';
            } else {
                $sSelected = '';
            }
        } elseif ($sCode == $sEncoding) {
            $sSelected = ' selected="selected"';
        } else {
            $sSelected = '';
        }
        $str.= '<option value="' . $sCode . '"' . $sSelected . '>' . $sLanguage . ' (' . $sCountry . ')</option>';
    }
}
$tpl->set('s', 'OPTIONS', $str);
$tpl->set('s', 'LANGUAGE', i18n('Language'));
$tpl->set('s', 'BACKEND', i18n('CONTENIDO Backend'));
$tpl->set('s', 'LOGIN', i18n('Login'));
$tpl->set('s', 'USERNAME', (isset($this->auth["uname"])) ? conHtmlentities(strip_tags($this->auth["uname"])) : "");

if (isset($username) && $username != '') {
    $err = i18n('Invalid login or password!');
}
$tpl->set('s', 'ERROR', $err);
$tpl->set('s', 'PASSWORD', i18n('Password'));
$tpl->set('s', 'TIME', time());

//class implements passwort recovery, all functionality is implemented there
$oRequestPassword = new cPasswordRequest($db, $cfg);
$str = $oRequestPassword->renderForm(1);
$tpl->set('s', 'FORM', $str);

$tpl->set('s', 'NOTI', $noti);

// send right encoding http header
sendEncodingHeader($db, $cfg, $lang);

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['main_loginform']);

?>