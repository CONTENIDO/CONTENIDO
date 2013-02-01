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
$type = "CMS_LINKEDITOR";
$typeid = 500;
$idartlang = cRegistry::getArticleLanguageId(true);
$artId = cRegistry::getArticleId(true);
$client = cRegistry::getClientId(true);
$lang = cRegistry::getLanguageId(true);

// create typegenerator object
$ocType = new cTypeGenerator();

global $force;
if (1 == $force) {
    $tpl->clearAllCache();
}

if ($_POST['linkCount']) {

    $value = $_POST['linkCount'];
    for ($i = 1; $i <= $value; $i++) {

        $val = $val . $ocType->getGeneratedCmsTag("CMS_LINKEDITOR", $i);
    }

    conSaveContentEntry($idartlang, $type, $typeid, $val);
}

// if backendmode then add additional fields
if (cRegistry::isBackendEditMode()) {
    $label = mi18n("LABEL_HEADER_LINKLIST");
    $input = '<input type="text" name="text_field" id="text_field"/>';
    $button = '<input type="button" id="create_linkfields" value="create"/>';
} else {
    $label = NULL;
    $input = NULL;
    $button = NULL;
}

// load article if exists otherwise create new one
$art = new Article($artId, $client, $lang);

global $force;
if (1 == $force) {
    $tpl->clearAllCache();
}

// assign data to the smarty template
$tpl->assign('label', $label);
$tpl->assign('usable_links', mi18n("usable_links"));

// if article was successfully loaded assign the content
if ($art->isLoaded()) {
    $tpl->assign('contents', $art->getContent($type, $typeid));
}
$tpl->assign('inputfield', $input);
$tpl->assign('button', $button);

echo $tpl->fetch('content_link_list/template/get.tpl');

?>