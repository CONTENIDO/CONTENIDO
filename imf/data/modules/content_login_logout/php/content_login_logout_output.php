<?php
/**
 * description: logout current user
 *
 * @package Module
 * @subpackage logout
 * @author alexander.scheider@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */


$tpl = Contenido_SmartyWrapper::getInstance();
$curi = cUri::getInstance();
global $force;
if (1 == $force) {
    $tpl->clearAllCache();
}
if ($auth->auth["uid"] != "nobody") {

    try {
        $category = new cApiCategoryLanguage();
        $category->loadByCategoryIdAndLanguageId($idcat, $lang);
        $bCatIsPublic = ($category->get('visible') == 1 && $category->get('public') == 1)? true : false;
    } catch (Exception $e) {
        echo $e->getMessage();
    }

    $oFeUserCollection = new cApiFrontendUserCollection();
    $oFeUser = $oFeUserCollection->loadItem($auth->auth["uid"]);

    $sWelcome = mi18n("LOGGED_IN_AS");
    $sText = $sWelcome . ': ' . $oFeUser->get('username');

    if ($bCatIsPublic === true) {

        $sUrl = $curi->build(array(
            'idcat' => $idcat,
            'idart' => $idart,
            'lang' => cRegistry::getLanguageId(),
            'logout' => 'true'
        ));
    } else {
        $iIdcatHome = (int) getEffectiveSetting('navigation', 'idcat-home', '1');
        $sUrl = $curi->build(array(
            'idcat' => $iIdcatHome,
            'lang' => cRegistry::getLanguageId(),
            'logout' => 'true'
        ));
    }

    $tpl->assign('text', $sText);
    $tpl->assign('url', $sUrl);
    $tpl->assign('label_logout', mi18n("LOGOUT"));

    $tpl->display('logout.tpl');
} else {

    $sUrl = $sUrl = 'front_content.php?idcat=41&idart=51';
    $tpl->assign('label_login', mi18n("LOGIN"));
    $tpl->assign('urlLogin', $sUrl);
    $tpl->display('login.tpl');
}

?>