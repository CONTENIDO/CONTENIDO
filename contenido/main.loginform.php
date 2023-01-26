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

$aAcceptLanguages = i18nStripAcceptLanguages($_SERVER['HTTP_ACCEPT_LANGUAGE']);
$mEncoding = false;
foreach ($aAcceptLanguages as $sValue) {
    $mEncoding = i18nMatchBrowserAccept($sValue);
    $GLOBALS['belang'] = $mEncoding;
    if ($mEncoding !== false) {
        break;
    }
}

if (empty($_POST['belang'])) {
    $sSelectedLang = '';
} else {
    $sSelectedLang = $_POST['belang'];
    $GLOBALS['belang'] = $sSelectedLang;
}

$sNotification = '';
if (getSystemProperty('maintenance', 'mode') == 'enabled') {
    $notification = new cGuiNotification();
    $sNotification = $notification->returnMessageBox('warning', i18n("CONTENIDO is in maintenance mode. Only sysadmins are allowed to login. Please try again later.") . '<br>');
}

// CON-2714
// Please check at CONTENIDO backend login whether the database tables are filled or not
$db = new cDb();
$sql = $db->prepare('SELECT `user_id` FROM `%s`', $cfg['tab']['user']);
$db->query($sql);
if ($db->numRows() == 0) {
    $notification = new cGuiNotification();
    $notification->displayNotification('error', i18n('Your database is obviously empty. Please ensure that you have installed CONTENIDO completely and/or that your database configuration is correct.'));
}

// Get backend label
$backend_label = getSystemProperty('backend', 'backend_label');
if (!is_string($backend_label) && !empty(trim($backend_label))) {
    $sTitle = ':: :: ' . $backend_label . ' :: :: CONTENIDO Login';
} else {
    $sTitle = ':: :: CONTENIDO Login';
}

$aAvailableLanguages = i18nGetAvailableLanguages();
$sLanguageOptions = '';
foreach ($aAvailableLanguages as $sCode => $aEntry) {
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
        } elseif ($sCode == $mEncoding) {
            $sSelected = ' selected="selected"';
        } else {
            $sSelected = '';
        }
        $sLanguageOptions.= '<option value="' . $sCode . '"' . $sSelected . '>' . $sLanguage . ' (' . $sCountry . ')</option>';
    }
}

//class implements password recovery, all functionality is implemented there
$oRequestPassword = new cPasswordRequest($db, $cfg);
$sRequestPasswordForm = $oRequestPassword->renderForm(1);

// send right encoding http header
sendEncodingHeader($db, $cfg, !empty($lang) ? $lang : 0);

// Fill and render the template
$tpl = new cTemplate();
$tpl->reset();

$tpl->set('s', 'BASEPATH', cRegistry::getBackendUrl());
$tpl->set('s', 'TITEL', $sTitle);
$tpl->set('s', 'ACTION', cRegistry::getSession()->selfURL());
$tpl->set('s', 'OPTIONS', $sLanguageOptions);
$tpl->set('s', 'LANGUAGE', i18n('Language'));
$tpl->set('s', 'BACKEND', i18n('CONTENIDO Backend'));
$tpl->set('s', 'LOGIN', i18n('Login'));
$tpl->set('s', 'USERNAME', isset($this->auth['uname']) ? conHtmlentities(strip_tags($this->auth["uname"])) : '');
$tpl->set('s', 'ERROR', !empty($username) ? i18n('Invalid login or password!') : '');
$tpl->set('s', 'PASSWORD', i18n('Password'));
$tpl->set('s', 'TIME', time());
$tpl->set('s', 'FORM', $sRequestPasswordForm);
$tpl->set('s', 'NOTI', $sNotification);

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['main_loginform']);
