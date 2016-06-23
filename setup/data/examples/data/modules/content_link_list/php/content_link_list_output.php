<?php

/**
 * description: standard link list
 *
 * @package Module
 * @subpackage ContentList
 * @author timo.trautmann@4fb.de
 * @author alexander.scheider@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
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
$linkCount = (int) $art->getContent($type, $typeid);

// if backendmode then add additional fields
if (cRegistry::isBackendEditMode()) {

    if ($_POST['linkCount']) {
        $linkCount = (int) $_POST['linkCount'];
        conSaveContentEntry($idartlang, $type, $typeid, $linkCount);
    }

    $backend = TRUE;
    $label = mi18n("LABEL_HEADER_LINKLIST");
    $createLabel = mi18n("createLabel");
    $createButton = mi18n("createButton");
    $input = '<input type="text" name="text_field" id="text_field" value="' . $linkCount . '"/>';
    $button = '<input type="button" id="create_linkfields" value="' . conHtmlSpecialChars($createButton) . '"/>';
} else {
    $label = NULL;
    $input = NULL;
    $button = NULL;
}

$val = array();
$valDescription = array();
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
$tpl->assign('usable_links', mi18n("usable_links"));
$tpl->assign('breakForBackend', $backend);
// if article was successfully loaded assign the content
if ($art->isLoaded()) {
    $tpl->assign('contents', $val);
    $tpl->assign('descriptions', $valDescription);
}
$tpl->assign('inputfield', $input);
$tpl->assign('button', $button);
$tpl->display('get.tpl');

?>