<?php
/**
 * description: login/logout form
 *
 * @package Module
 * @subpackage FormLogin
 * @version SVN Revision $Rev:$
 *
 * @author timo.trautmann@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

$tpl = cSmartyFrontend::getInstance();

if ($auth->auth["uid"] == "nobody") {
    $sTargetIdart = getEffectiveSetting('login', 'idart', '1');
    $sFormAction = 'front_content.php?idart='.$sTargetIdart;

    $tpl->assign('form_action', $sFormAction);
    $tpl->assign('label_name', mi18n("NAME"));
    $tpl->assign('label_pass', mi18n("PASS"));
    $tpl->assign('label_login', mi18n("LOGIN"));
    $tpl->display('login.tpl');
} else {
    try {
        $category = new cApiCategoryLanguage();
        $category->loadByCategoryIdAndLanguageId($idcat, $lang);
        $bCatIsPublic = ($category->get('visible') == 1 && $category->get('public') == 1) ? true : false;
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    $oFeUserCollection = new cApiFrontendUserCollection();
    $oFeUser = $oFeUserCollection->loadItem($auth->auth["uid"]);
    $sText = str_replace('[uname]', $oFeUser->get('username'), mi18n("TXT_WELCOME_USER"));
    if ($bCatIsPublic === true) {
        $sUrl = 'front_content.php?idcat='.$idcat.'&idart='.$idart.'&logout=true';
    } else {
        $iIdcatHome = (int) getEffectiveSetting('navigation', 'idcat-home', '1');
        $sUrl = 'front_content.php?idcat='.$iIdcatHome.'&logout=true';
    }

    $tpl->assign('text', $sText);
    $tpl->assign('url', $sUrl);
    $tpl->assign('label_logout', mi18n("LOGOUT"));
    $tpl->display('logout.tpl');
}

?>