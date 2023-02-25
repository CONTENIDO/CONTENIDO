<?php

/**
 * description: standard link list
 *
 * @package    Module
 * @subpackage ContentList
 * @author     Timo.trautmann@4fb.de
 * @author     alexander.scheider@4fb.de
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

// include class
cInclude('classes', 'class.typegenerator.php');

$type = "CMS_TEXT";
$typeid = 500;

$idartlang = cRegistry::getArticleLanguageId(true);
$artId = cRegistry::getArticleId(true);
$client = cRegistry::getClientId();
$lang = cRegistry::getLanguageId();

// create art object
$art = new cApiArticleLanguage();
$art->loadByArticleAndLanguageId($artId, $lang);
$linkCount = cSecurity::toInteger($art->getContent($type, $typeid));

// if backendmode then add additional fields
if (cRegistry::isBackendEditMode()) {
    if (isset($_POST['linkCount'])) {
        $linkCount = cSecurity::toInteger($_POST['linkCount']);
        conSaveContentEntry($idartlang, $type, $typeid, $linkCount);
    }

    $backend = true;
    $label = mi18n("LABEL_HEADER_LINKLIST");
    $createLabel = mi18n("createLabel");
    $createButton = mi18n("createButton");
    $input = '<input type="text" name="link_count" value="' . $linkCount . '"/>';
    $button = '<input type="button" data-content-link-list-action="create_link_fields" value="' . conHtmlSpecialChars($createButton) . '"/>';
} else {
    $backend = false;
    $label = '';
    $input = NULL;
    $button = NULL;
    $createLabel = '';
}

$val            = [];
$valDescription = [];
// create typegenerator object
$ocType = new cTypeGenerator();
for ($i = 0; $i < $linkCount; $i++) {
    $val[$typeid + $i] = stripslashes($ocType->getGeneratedCmsTag('CMS_LINKEDITOR', $typeid + $i));
    $valDescription[$typeid + $i] = stripslashes($ocType->getGeneratedCmsTag('CMS_HTML', $typeid + $i));
}

// use smarty template to output header text
$tpl = cSmartyFrontend::getInstance();
$tpl->assign('label', $label);
$tpl->assign('createLabel', $createLabel);
$tpl->assign('usableLinks', mi18n("usable_links"));
$tpl->assign('breakForBackend', $backend);
// if article was successfully loaded assign the content
if ($art->isLoaded()) {
    $tpl->assign('contents', $val);
    $tpl->assign('descriptions', $valDescription);
}
$tpl->assign('inputField', $input);
$tpl->assign('button', $button);
$tpl->display('get.tpl');

?>