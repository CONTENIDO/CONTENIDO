<?php
/**
 * Facebook socialmedia module
 * @package Module
 * @subpackage facebook
 *
 * @author alexander.scheider@4fb.de
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

//get smarty instance
$tpl = cSmartyFrontend::getInstance();

//init vars and objects
$urlLabel = mi18n("URL");
$pluginLabel = mi18n("PLUGIN");
$likeButtonLabel = mi18n("LIKE_BUTTON");
$likeBoxLabel = mi18n("LIKE_BOX");
$layoutLabel = mi18n("LAYOUT");
$standardLabel = mi18n("STANDARD");
$buttonCountLabel = mi18n("BUTTON_COUNT");
$boxCountLabel = mi18n("BOX_COUNT");
$showFacesLabel = mi18n("SHOW_FACES");
$widthLabel = mi18n("WIDTH");
$heightLabel = mi18n("HEIGHT");
$saveLabel = mi18n("SAVE");
$label_overview = mi18n("OVERVIEW_LABEL");
$automaticURLLabel = mi18n("AUTOMATIC_URL_LABEL");
$idartlang = cRegistry::getArticleLanguageId();
$idlang = cRegistry::getLanguageId();
$idclient = cRegistry::getClientId();

//create article object
$art = new cApiArticleLanguage($idartlang);

//if post save values in db
if (cRegistry::isBackendEditMode() && 'POST' === strtoupper($_SERVER['REQUEST_METHOD']) && $_POST['plugin_type'] == 'facebook') {
    conSaveContentEntry($idartlang, "CMS_HTML", 1000, $_POST['url']);
    conSaveContentEntry($idartlang, "CMS_HTML", 1001, $_POST['plugin']);
    conSaveContentEntry($idartlang, "CMS_HTML", 1002, $_POST['layout']);
    conSaveContentEntry($idartlang, "CMS_HTML", 1003, $_POST['faces']);
    conSaveContentEntry($idartlang, "CMS_HTML", 1004, $_POST['width']);
    conSaveContentEntry($idartlang, "CMS_HTML", 1005, $_POST['height']);
    conSaveContentEntry($idartlang, "CMS_HTML", 1006, $_POST['automaticURL']);
}

//get saved content
$url = $art->getContent("CMS_HTML", 1000);
$pluginvalue = $art->getContent("CMS_HTML", 1001);
$layoutvalue = $art->getContent("CMS_HTML", 1002);
$facesvalue = $art->getContent("CMS_HTML", 1003);
$width = $art->getContent("CMS_HTML", 1004);
$height = $art->getContent("CMS_HTML", 1005);
$useAutomaticURL = $art->getContent("CMS_HTML", 1006);
if ($useAutomaticURL == "1") {
    $url = cRegistry::getFrontendUrl() . $art->getLink();
}

//if backend mode set some values and display config tpl
if (cRegistry::isBackendEditMode()) {
    $tpl->assign('url', $url);
    $tpl->assign('pluginvalue', $pluginvalue);
    $tpl->assign('layoutvalue', $layoutvalue);
    $tpl->assign('facesvalue', $facesvalue);
    $tpl->assign('width', $width);
    $tpl->assign('height', $height);
    $tpl->assign('urlLabel', $urlLabel);
    $tpl->assign('pluginLabel', $pluginLabel);
    $tpl->assign('likeButtonLabel', $likeButtonLabel);
    $tpl->assign('likeBoxLabel', $likeBoxLabel);
    $tpl->assign('layoutLabel', $layoutLabel);
    $tpl->assign('standardLabel', $standardLabel);
    $tpl->assign('buttonCountLabel', $buttonCountLabel);
    $tpl->assign('boxCountLabel', $boxCountLabel);
    $tpl->assign('showFacesLabel', $showFacesLabel);
    $tpl->assign('widthLabel', $widthLabel);
    $tpl->assign('heightLabel', $heightLabel);
    $tpl->assign('save', $saveLabel);
    $tpl->assign('label_overview', $label_overview);
    $tpl->assign("automaticURLLabel", $automaticURLLabel);
    $tpl->assign("useAutomaticURL", $useAutomaticURL);
    
    $tpl->assign("autoUrlHelp", new cGuiBackendHelpbox(conHtmlSpecialChars(mi18n("AUTO_URL_HELP"))));
    $tpl->assign("likeButtonHelp", new cGuiBackendHelpbox(conHtmlSpecialChars(mi18n("LIKE_BUTTON_HELP"))));
    $tpl->assign("likeBoxHelp", new cGuiBackendHelpbox(conHtmlSpecialChars(mi18n("LIKE_BOX_HELP"))));
    $tpl->assign("standardHelp", new cGuiBackendHelpbox(conHtmlSpecialChars(mi18n("STANDARD_HELP"))));
    $tpl->assign("buttonCountHelp", new cGuiBackendHelpbox(conHtmlSpecialChars(mi18n("BUTTON_COUNT_HELP"))));
    $tpl->assign("boxCountHelp", new cGuiBackendHelpbox(conHtmlSpecialChars(mi18n("BOX_COUNT_HELP"))));
    $tpl->assign("showFacesHelp", new cGuiBackendHelpbox(conHtmlSpecialChars(mi18n("SHOW_FACES_HELP"))));

    $tpl->display('facebook_config_view.tpl');
} else {
    //if no url set, set default contenido url
    if ($url == '') {
        $url = 'http://facebook.com/cms.contenido';
    }
    //if no type is set set default type
    if ($pluginvalue == '') {
        $pluginvalue = 'like_box';
    }

    cApiPropertyCollection::reset();
    $propColl = new cApiPropertyCollection();
    $propColl->changeClient($idclient);

    $language = $propColl->getValue('idlang', $idlang, 'language', 'code', '');
    $country = $propColl->getValue('idlang', $idlang, 'country', 'code', '');

    $locale = $language . '_' . strtoupper($country);

    if ($facesvalue != 'true') {
        $facesvalue = 'false';
    }

    $tpl->assign('SHOW_FACES', $facesvalue);
    $tpl->assign('LOCALE', $locale);
    $tpl->assign('WIDTH', $width);
    $tpl->assign('HEIGHT', $height);
    $tpl->assign('LAYOUT', $layoutvalue);

    switch ($pluginvalue) {
        case 'like_button':
            $tpl->assign('URL', urlencode($url));
            $tpl->display('facebook_like_button.tpl');
            break;
        case 'like_box':
            $tpl->assign('URL', $url);
            $tpl->display('facebook_like_box.tpl');
            break;
        default:
            $display = new cGuiNotification();
            $display->displayMessageBox(cGuiNotification::LEVEL_ERROR, 'Please configure facebook plugin!');
    }
}

?>