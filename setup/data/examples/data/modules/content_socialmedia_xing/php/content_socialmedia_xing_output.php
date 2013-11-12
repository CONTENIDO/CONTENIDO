<?php
/**
 * Description: XING social plugin
 *
 * @version    1.0.0
 * @author     alexander.scheider@4fb.de
 * @copyright  four for business AG <www.4fb.de>
 *
 */

$tpl = cSmartyFrontend::getInstance();

$urlProfileLabel = mi18n("URL_PROFILE");
$lookLabel = mi18n("LOOK");
$nameLabel = mi18n("NAME");
$save = mi18n("SAVE");
$label_overview = mi18n("OVERVIEW");
$idartlang = cRegistry::getArticleLanguageId();
$idlang = cRegistry::getLanguageId();
$idclient = cRegistry::getClientId();

//create article object
$art = new cApiArticleLanguage($idartlang);

//if post save values in db
if (cRegistry::isBackendEditMode() && 'POST' === strtoupper($_SERVER['REQUEST_METHOD']) && $_POST['plugin_type'] == 'xing') {
    conSaveContentEntry($idartlang, "CMS_HTML", 2000, $_POST['profile']);
    conSaveContentEntry($idartlang, "CMS_HTML", 2001, $_POST['look']);
    conSaveContentEntry($idartlang, "CMS_HTML", 2002, $_POST['name']);
}

//get saved content
$profile = $art->getContent("CMS_HTML", 2000);
$look = $art->getContent("CMS_HTML", 2001);
$name = $art->getContent("CMS_HTML", 2002);

//if backend mode set some values and display config tpl
if (cRegistry::isBackendEditMode()) {
    $tpl->assign('urlProfileLabel', $urlProfileLabel);
    $tpl->assign('lookLabel', $lookLabel);
    $tpl->assign('nameLabel', $nameLabel);
    $tpl->assign('save', $save);
    $tpl->assign('label_overview', $label_overview);

    $tpl->assign('profile', $profile);
    $tpl->assign('look', $look);
    $tpl->assign('name', $name);

    $tpl->display('xing_config_view.tpl');
} else {

    if ($profile != '' && $look != '') {

        $tpl->assign('NAME', $name);
        $tpl->assign('URL', $profile);

        if ($look == 'small') {
            $tpl->display('xing_small.tpl');
        } elseif ($look == 'big') {
            $tpl->display('xing_big.tpl');
        }
    }
}

?>