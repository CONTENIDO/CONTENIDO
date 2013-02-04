<?php

/**
 * description: standard link list
 *
 * @package Module
 * @subpackage content_list
 * @author timo.trautmann@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

// include class
cInclude('classes', 'class.typegenerator.php');

// use smarty template to output header text
$tpl = Contenido_SmartyWrapper::getInstance();

// initialize var's
$type = "CMS_TEXT";
$typeid = 500;
$idartlang = cRegistry::getArticleLanguageId(true);
$artId = cRegistry::getArticleId(true);
$client = cRegistry::getClientId(true);
$lang = cRegistry::getLanguageId(true);
$val = array();
// create typegenerator object
$ocType = new cTypeGenerator();

global $force;
if (1 == $force) {
    $tpl->clearAllCache();
}

// create art object
$art = new Article($artId, $client, $lang, $idartlang);
$linkCount = (int) $art->getContent($type, $typeid);

if ($_POST['linkCount']) {
    $linkCount = (int) $_POST['linkCount'];
    conSaveContentEntry($idartlang, $type, $typeid, $linkCount);
}

// if backendmode then add additional fields
if (cRegistry::isBackendEditMode()) {
    $label = mi18n("LABEL_HEADER_LINKLIST");
    $input = '<input type="text" name="text_field" id="text_field" value="' . $linkCount . '"/>';
    $button = '<input type="button" id="create_linkfields" value="create"/>';
} else {
    $label = NULL;
    $input = NULL;
    $button = NULL;
}

global $force;
if (1 == $force) {
    $tpl->clearAllCache();
}

for ($i = 0; $i < $linkCount; $i++) {
    $val[$i] =  stripslashes($ocType->getGeneratedCmsTag("CMS_LINKEDITOR", $typeid + $i));
}

// assign data to the smarty template
$tpl->assign('label', $label);
$tpl->assign('usable_links', mi18n("usable_links"));

// if article was successfully loaded assign the content
if ($art->isLoaded()) {
    $tpl->assign('contents', $val);
}
$tpl->assign('inputfield', $input);
$tpl->assign('button', $button);

echo $tpl->fetch('content_link_list/template/get.tpl');

?>