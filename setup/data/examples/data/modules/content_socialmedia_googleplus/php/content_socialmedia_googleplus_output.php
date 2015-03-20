<?php
/**
 * Description: Google Plus
 *
 * @version   1.0.0
 * @author    unknown
 * @copyright four for business AG <www.4fb.de>
 *
 * {@internal
 *   created unknown
 *   $Id: googleplus_output.php 2755 2012-07-25 20:10:28Z xmurrix $
 * }}
 */

$tpl = cSmartyFrontend::getInstance();

$urlLabel = mi18n("URL");
$automaticURLLabel = mi18n("AUTOMATIC_URL_LABEL");
$lookLabel = mi18n("LOOK");
$normalLabel = mi18n("NORMAL") . ' (24px)';
$smallLabel = mi18n("SMALL") . ' (15px)';
$mediumLabel = mi18n("MEDIUM") . ' (20px)';
$tallLabel = mi18n("TALL") . ' (60px)';
$displayCounterLabel = mi18n("DISPLAY_COUNTER");
$label_overview = mi18n("OVERVIEW");
$saveLabel = mi18n("SAVE");

$idartlang = cRegistry::getArticleLanguageId();
$idlang = cRegistry::getLanguageId();
$idclient = cRegistry::getClientId();

//create article object
$art = new cApiArticleLanguage($idartlang);

//if post save values in db
if (cRegistry::isBackendEditMode() && 'POST' === strtoupper($_SERVER['REQUEST_METHOD']) && $_POST['plugin_type'] == 'gplus') {

    // CON-2174
    $url = $_POST['url'];
    if (null === parse_url($url, PHP_URL_SCHEME)) {
    	$url = 'http://' . $url;
    }

    conSaveContentEntry($idartlang, "CMS_HTML", 3000, $url);
    conSaveContentEntry($idartlang, "CMS_HTML", 3001, $_POST['size']);
    conSaveContentEntry($idartlang, "CMS_HTML", 3002, $_POST['counter']);
    conSaveContentEntry($idartlang, "CMS_HTML", 3003, $_POST['currentArticleUrl']);
}

//get saved content
$url = $art->getContent("CMS_HTML", 3000);
$size = $art->getContent("CMS_HTML", 3001);
$counter = $art->getContent("CMS_HTML", 3002);
$currentArticleUrl = $art->getContent("CMS_HTML", 3003);

if ($currentArticleUrl == "1") {
    $url = cRegistry::getFrontendUrl() . $art->getLink();
}

//if backend mode set some values and display config tpl
if (cRegistry::isBackendEditMode()) {
    $tpl->assign('url', $url);
    $tpl->assign('size', $size);
    $tpl->assign('counter', $counter);
    $tpl->assign('urlLabel', $urlLabel);
    $tpl->assign('lookLabel', $lookLabel);
    $tpl->assign('normalLabel', $normalLabel);
    $tpl->assign('smallLabel', $smallLabel);
    $tpl->assign('mediumLabel', $mediumLabel);
    $tpl->assign('tallLabel', $tallLabel);
    $tpl->assign('displayCounterLabel', $displayCounterLabel);
    $tpl->assign('save', $saveLabel);
    $tpl->assign('label_overview', $label_overview);
    $tpl->assign("automaticURLLabel", $automaticURLLabel);
    $tpl->assign("currentArticleUrl", $currentArticleUrl);

    $tpl->assign("urlHelp", new cGuiBackendHelpbox(conHtmlSpecialChars(mi18n("URL_HELP"))));
    $tpl->assign("normalHelp", new cGuiBackendHelpbox(conHtmlSpecialChars(mi18n("NORMAL_HELP"))));
    $tpl->assign("smallHelp", new cGuiBackendHelpbox(conHtmlSpecialChars(mi18n("SMALL_HELP"))));
    $tpl->assign("mediumHelp", new cGuiBackendHelpbox(conHtmlSpecialChars(mi18n("MEDIUM_HELP"))));
    $tpl->assign("tallHelp", new cGuiBackendHelpbox(conHtmlSpecialChars(mi18n("TALL_HELP"))));
    $tpl->assign("counterHelp", new cGuiBackendHelpbox(conHtmlSpecialChars(mi18n("COUNTER_HELP"))));

    $tpl->display('google_plus_config_view.tpl');
} else {

    if ($size == 'standard') {
        $tpl->assign('LAYOUT', '');
    } else {
        $tpl->assign('LAYOUT', ' size="' . conHtmlSpecialChars($size) . '"');
    }

    if ($counter) {
        $tpl->assign('SHOW_COUNT', '');
    } else {
        $tpl->assign('SHOW_COUNT', ' annotation="none"');
    }

    if ($url != '') {
        $tpl->assign('URL', ' href="' . urlencode($url) . '"');
    } else {
        $tpl->assign('URL', '');
    }

    $langObj = new cApiLanguage($idlang);
    $locale = $langObj->getProperty('language', 'code');

    $tpl->assign('LOCALE', $locale);
    $tpl->display('google_plus.tpl');
}

?>