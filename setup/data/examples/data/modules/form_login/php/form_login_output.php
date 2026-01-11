<?php

/**
 * Description: login/logout form.
 *
 * The class {@see cAuthHandlerFrontend()} deals with the login/authentication process when the
 * login credentials are sent by a form.
 *
 * This module displays the login form in case the user is not logged in or the users' logged in status.
 *
 * @package    Module
 * @subpackage FormLogin
 * @author     Timo.trautmann@4fb.de
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

$tpl = cSmartyFrontend::getInstance();
$auth = cRegistry::getAuth();
$idcat = cRegistry::getCategoryId();
$lang = cRegistry::getLanguageId();
$idart = cRegistry::getArticleId();

if ($auth->getUserId() === cAuth::AUTH_UID_NOBODY) {
    $loginArticleId = cSecurity::toInteger(getEffectiveSetting('login', 'idart', '1'));
    $tpl->assign('form_action', sprintf('front_content.php?idart=%d', $loginArticleId));
    $tpl->assign('label_name', mi18n("NAME"));
    $tpl->assign('label_pass', mi18n("PASS"));
    $tpl->assign('label_login', mi18n("LOGIN"));
    $tpl->display('login.tpl');
} else {
    try {
        $categoryLanguage = new cApiCategoryLanguage();
        $isCategoryPublicAndVisible = $categoryLanguage->loadByCategoryIdAndLanguageId($idcat, $lang)
            && $categoryLanguage->get('visible') == 1
            && $categoryLanguage->get('public') == 1;
    } catch (Exception $e) {
        $isCategoryPublicAndVisible = false;
        echo $e->getMessage();
    }

    $frontendUser = new cApiFrontendUser($auth->getUserId());
    $sText = str_replace('[uname]', $frontendUser->get('username'), mi18n("TXT_WELCOME_USER"));
    if ($isCategoryPublicAndVisible) {
        $sUrl = sprintf('front_content.php?idcat=%d&idart=%d&logout=true', $idcat, $idart);
    } else {
        $sUrl = sprintf(
            'front_content.php?idcat=%d&logout=true',
            cSecurity::toInteger(getEffectiveSetting('navigation', 'idcat-home', '1'))
        );
    }

    $tpl->assign('text', $sText);
    $tpl->assign('url', $sUrl);
    $tpl->assign('label_logout', mi18n("LOGOUT"));
    $tpl->display('logout.tpl');
}

?>
