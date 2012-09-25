<?php
$tpl = new cTemplate();

if ($auth->auth["uid"] == "nobody") {
    $sTargetIdcat = getEffectiveSetting('login', 'idcat', '1');
    $sTargetIdart = getEffectiveSetting('login', 'idart', '1');
    $sFormAction = 'front_content.php?idcat='.$sTargetIdcat.'&amp;idart='.$sTargetIdart;

    $tpl->set('s', 'headline', mi18n("Geschlossener Bereich Login"));
    $tpl->set('s', 'form_action', $sFormAction);
    $tpl->set('s', 'label_name', mi18n("name"));
    $tpl->set('s', 'label_pass', mi18n("pass"));
    $tpl->set('s', 'label_login', mi18n("einloggen"));
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
    $sText = str_replace('[uname]', $oFeUser->get('username'), mi18n("Willkommen <strong>[uname]</strong>, schön, dass Sie wieder bei uns vorbeischauen."));
    if ($bCatIsPublic === true) {
        $sUrl = 'front_content.php?idcat='.$idcat.'&amp;idart='.$idart.'&logout=true';
    } else {
        $iIdcatHome = (int) getEffectiveSetting('navigation', 'idcat-home', '1');
        $sUrl = 'front_content.php?idcat='.$iIdcatHome.'&amp;logout=true';
    }
    $tpl->set('s', 'headline', mi18n("Geschlossener Bereich Logout"));
    $tpl->set('s', 'text', $sText);
    $tpl->set('s', 'url', $sUrl);
    $tpl->set('s', 'label_logout', mi18n("ausloggen"));
    $tpl->generate('templates/login_form_loggedin.html');
}

?>