<?php
/**
 * Description: Twitter module
 *
 * @version    1.0.1
 * @author     konstantinos.katikak
 * @author     alexander.scheider@4fb.de
 * @copyright  four for business AG <www.4fb.de>
 *
 */

//get smarty instance
$tpl = cSmartyFrontend::getInstance();

//get translations
$label_overview = mi18n("OVERVIEW");
$nameLabel = mi18n("TWITTERNAME");
$labelWidth = mi18n("WIDTH");
$labelHeight = mi18n("HEIGHT");
$themeLabel = mi18n("THEME");
$lightThemeLabel = mi18n("LIGHT_THEME");
$darkThemeLabel = mi18n("DARK_THEME");
$showRepliesLabel = mi18n("SHOW_REPLIES");
$labelLinkColor = mi18n("LINK_COLOR");
$labelBorderColor = mi18n("BORDER_COLOR");
$labelRelated = mi18n("LABEL_RELATED");
$labelRelatedExplanation = mi18n("RELATED_EXPLANATION");
$save = mi18n("SAVE");

//get id's
$idartlang = cRegistry::getArticleLanguageId();
$idlang = cRegistry::getLanguageId();
$idclient = cRegistry::getClientId();

//create article object
$art = new cApiArticleLanguage($idartlang);
if (cRegistry::isBackendEditMode()) conSaveContentEntry($idartlang, "CMS_HTML", 4004, $_POST['show_replies']);
//if post save values in db
if (cRegistry::isBackendEditMode() && 'POST' === strtoupper($_SERVER['REQUEST_METHOD']) && $_POST['plugin_type'] == 'twitter') {
    conSaveContentEntry($idartlang, "CMS_HTML", 4000, $_POST['twitter_name']);
    conSaveContentEntry($idartlang, "CMS_HTML", 4001, $_POST['width']);
    conSaveContentEntry($idartlang, "CMS_HTML", 4002, $_POST['height']);
    conSaveContentEntry($idartlang, "CMS_HTML", 4003, $_POST['theme']);
    conSaveContentEntry($idartlang, "CMS_HTML", 4004, $_POST['show_replies']);
    conSaveContentEntry($idartlang, "CMS_HTML", 4005, $_POST['link_color']);
    conSaveContentEntry($idartlang, "CMS_HTML", 4006, $_POST['border_color']);
    conSaveContentEntry($idartlang, "CMS_HTML", 4007, $_POST['related']);
}

//get saved content
$twitterName = strip_tags($art->getContent("CMS_HTML", 4000));
$twitterWidth = strip_tags($art->getContent("CMS_HTML", 4001));
$twitterHeight = strip_tags($art->getContent("CMS_HTML", 4002));
$twitterTheme = strip_tags($art->getContent("CMS_HTML", 4003));
$twitterShowReplies = strip_tags($art->getContent("CMS_HTML", 4004));
$twitterLinkColor = strip_tags($art->getContent("CMS_HTML", 4005));
$twitterBorderColor = strip_tags($art->getContent("CMS_HTML", 4006));
$twitterRelated = strip_tags($art->getContent("CMS_HTML", 4007));


$tpl->assign('twitterName', $twitterName);
$tpl->assign('twitterWidth', $twitterWidth);
$tpl->assign('twitterHeight', $twitterHeight);
$tpl->assign('twitterTheme', $twitterTheme);
$tpl->assign('twitterReplies', $twitterShowReplies);
$tpl->assign('twitterLinkColor', $twitterLinkColor);
$tpl->assign('twitterBorderColor', $twitterBorderColor);
$tpl->assign('twitterRelated', $twitterRelated);
//if backend mode set some values and display config tpl
if (cRegistry::isBackendEditMode()) {
    $tpl->assign('label_overview', $label_overview);
    $tpl->assign('nameLabel', $nameLabel);
    $tpl->assign('labelWidth', $labelWidth);
    $tpl->assign('labelHeight', $labelHeight);
    $tpl->assign('themeLabel', $themeLabel);
    $tpl->assign('lightThemeLabel', $lightThemeLabel);
    $tpl->assign('darkThemeLabel', $darkThemeLabel);
    $tpl->assign('showRepliesLabel', $showRepliesLabel);
    $tpl->assign('labelLinkColor', $labelLinkColor);
    $tpl->assign('labelBorderColor', $labelBorderColor);
    $tpl->assign('labelRelated', $labelRelated);
    $tpl->assign('labelRelatedExplanation', $labelRelatedExplanation);
    $tpl->assign('urlToShareLabel', $urlToShareLabel);
    $tpl->assign('showCountLabel', $showCountLabel);
    $tpl->assign('save', $save);
	
	$tpl->assign("showRepliesHelp", new cGuiBackendHelpbox(htmlspecialchars(mi18n("SHOWREPLIES_HELP"))));
	$tpl->assign("relatedExplanationHelp", new cGuiBackendHelpbox(htmlspecialchars(mi18n("RELATEDEXPLANATION_HELP"))));

    $tpl->display('twitter_config_view.tpl');
} else {
    $tpl->display('twitter_embed_timeline.tpl');
}

?>