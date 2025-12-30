<?php

/**
 * This file handles the login into the backend.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$db = cRegistry::getDb();
$cfg = cRegistry::getConfig();
$lang = cRegistry::getLanguageId();

$aAvailableLanguages = i18nGetAvailableLanguages();

// Get all available languages and filter by `$cfg['login_languages']`
$aAvailableLanguages = array_filter($aAvailableLanguages, function($code) use($cfg) {
    return in_array($code, $cfg['login_languages']);
}, ARRAY_FILTER_USE_KEY);

// Detect selected language, either by configuration or by POST
if (empty($_POST['belang'])) {
    $defaultBackendLang = $cfg['backend']['default_belang'] ?? '';
    $sSelectedLang = isset($aAvailableLanguages[$defaultBackendLang]) ? $defaultBackendLang : '';
} else {
    $sSelectedLang = $_POST['belang'];
    $GLOBALS['belang'] = $sSelectedLang;
}

// Detect preferred language by client settings, if not set before
if (empty($sSelectedLang)) {
    $aAcceptLanguages = i18nStripAcceptLanguages($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
    foreach ($aAcceptLanguages as $sValue) {
        $mEncoding = i18nMatchBrowserAccept($sValue);
        if ($mEncoding) {
            $sSelectedLang = $mEncoding;
            break;
        }
    }
}
$GLOBALS['belang'] = $sSelectedLang;

$sNotification = '';
if (getSystemProperty('maintenance', 'mode') == 'enabled') {
    $notification = new cGuiNotification();
    $sNotification = $notification->returnMessageBox('warning', i18n("CONTENIDO is in maintenance mode. Only sysadmins are allowed to login. Please try again later.") . '<br>');
}

// Check at CONTENIDO backend login whether the database tables are filled or not
$cApiUserColl = new cApiUserCollection();
$cApiUserColl->setLimit(0, 1);
$cApiUserColl->query();
if (empty($cApiUserColl->fetchTable(['user_id']))) {
    $notification = new cGuiNotification();
    $notification->displayNotification('error', i18n('Your database is obviously empty. Please ensure that you have installed CONTENIDO completely and/or that your database configuration is correct.'));
}

// Get backend label
$backend_label = cSecurity::toString(getSystemProperty('backend', 'backend_label'));
if (!empty(trim($backend_label))) {
    $sTitle = ':: :: ' . $backend_label . ' :: :: CONTENIDO Login';
} else {
    $sTitle = ':: :: CONTENIDO Login';
}

$sLanguageOptions = '';
foreach ($aAvailableLanguages as $sCode => $aEntry) {
    list($sLanguage, $sCountry, $sCodeSet, $sAcceptTag) = $aEntry;
    $bSelected = $sSelectedLang == $sCode;
    $sLanguageOptions .= (new cHTMLOptionElement($sLanguage . ' (' . $sCountry . ')', $sCode, $bSelected))->toHtml();
}

// Class implements password recovery, all functionality is implemented there
$oRequestPassword = new cPasswordRequest($db, $cfg);
$sRequestPasswordForm = $oRequestPassword->renderForm(1);

// Send right encoding http header
sendEncodingHeader($db, $cfg, $lang);

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
$tpl->set('s', 'ERROR', !empty($_POST['username']) ? i18n('Invalid login or password!') : '');
$tpl->set('s', 'PASSWORD', i18n('Password'));
$tpl->set('s', 'TIME', time());
$tpl->set('s', 'FORM', $sRequestPasswordForm);
$tpl->set('s', 'NOTI', $sNotification);

$tpl->generate($cfg['path']['templates'] . $cfg['templates']['main_loginform']);
