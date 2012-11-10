<?php
$tpl = new cTemplate();

if ($auth->auth["uid"] == "nobody") {
    $sTargetIdcat = getEffectiveSetting('login', 'idcat', '1');
    $sTargetIdart = getEffectiveSetting('login', 'idart', '1');
    $sFormAction = 'front_content.php?idcat='.$sTargetIdcat.'&idart='.$sTargetIdart;

    $tpl->set('s', 'headline', mi18n("CLOSED_AREA_LOGIN"));
    $tpl->set('s', 'form_action', $sFormAction);
    $tpl->set('s', 'label_name', mi18n("NAME"));
    $tpl->set('s', 'label_pass', mi18n("PASS"));
    $tpl->set('s', 'label_login', mi18n("LOGIN"));
    $tpl->generate('templates/login_form.html');
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
    $tpl->set('s', 'headline', mi18n("CLOSED_AREA_LOGOUT"));
    $tpl->set('s', 'text', $sText);
    $tpl->set('s', 'url', $sUrl);
    $tpl->set('s', 'label_logout', mi18n("LOGOUT"));
    $tpl->generate('templates/login_form_loggedin.html');
}

?>