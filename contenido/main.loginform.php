<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Login form
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Backend
 * @version    1.0.4
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created  2003-01-21
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


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
if (getenv('CONTENIDO_IGNORE_SETUP') != 'true') {
    $aMessages = array();

    // Check, if setup folder is still available
    if (cFileHandler::exists(dirname(dirname(__FILE__)) . '/setup')) {
        $aMessages[] = i18n("The setup directory still exists. Please remove the setup directory before you continue.");
    }

    // Check, if sysadmin and/or admin accounts are still using well-known default passwords
    $db = cRegistry::getDb();

    $sDate = date('Y-m-d');
    $sSQL = "SELECT * FROM " . $cfg['tab']['phplib_auth_user_md5'] . "
             WHERE (username = 'sysadmin' AND password = '48a365b4ce1e322a55ae9017f3daf0c0'
                    AND (valid_from <= '" . $db->escape($sDate) . "' OR valid_from = '0000-00-00' OR valid_from is NULL) AND
                   (valid_to >= '" . $db->escape($sDate) . "' OR valid_to = '0000-00-00' OR valid_to is NULL))
                 OR (username = 'admin' AND password = '21232f297a57a5a743894a0e4a801fc3'
                     AND (valid_from <= '" . $db->escape($sDate) . "' OR valid_from = '0000-00-00' OR valid_from is NULL) AND
                    (valid_to >= '" . $db->escape($sDate) . "' OR valid_to = '0000-00-00' OR valid_to is NULL))
                   ";
    $db->query($sSQL);

    if ($db->num_rows() > 0) {
        $aMessages[] = i18n("The sysadmin and/or the admin account still contains a well-known default password. Please change immediately after login.");
    }
    unset($db);

    if (getSystemProperty('maintenance', 'mode') == 'enabled') {
        $aMessages[] = i18n("CONTENIDO is in maintenance mode. Only sysadmins are allowed to login. Please try again later.");
    }

    if (count($aMessages) > 0) {
        $notification = new cGuiNotification();
        $noti = $notification->returnMessageBox('warning', implode('<br />', $aMessages), 1) . '<br />';
    }
}

// Fill template
$tpl = new cTemplate();
$tpl->reset();

$tpl->set('s', 'BASEPATH', $cfg['path']['contenido_fullhtml']);
$tpl->set('s', 'TITEL', ':: :: :: :: CONTENIDO Login');
$tpl->set('s', 'ACTION', $this->url());

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
$tpl->set('s', 'USERNAME', (isset($this->auth["uname"])) ? htmlentities(strip_tags($this->auth["uname"])) : "");

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