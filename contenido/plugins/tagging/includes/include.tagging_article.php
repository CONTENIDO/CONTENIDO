<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Tagging Articles
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO Plugins
 * @subpackage Tagging
 * @version    1.0.2
 * @author     unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created unknown
 *   modified 2008-07-02, Frederic Schneider, add security fix
 *
 *   $Id: include.tagging_article.php 2101 2012-04-03 12:46:11Z mischa.holz $:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

// check requests
Contenido_Security::checkRequests();

cInclude('includes', 'functions.pathresolver.php');

function str_replace_recursive($array) {
    if (!is_array($array)) return false;

    $result = array();

    foreach ($array as $value) {
        $result[] = str_replace('e', '', $value);
    }

    return $result;
}

// fetch idartlang for idart
$sql = "SELECT idartlang FROM ".$cfg['tab']['art_lang']." WHERE idart=".(int) $idart." AND idlang=".(int) $lang;
$db->query($sql);
$db->next_record();
$this_idartlang = $db->f('idartlang');

$oPage = new cPage;
$oPage->setMargin(10);

$oTree = new pApiTaggingComplexList('06bd456d-fe76-40cb-b041-b9ba90dc400a');
$oAlloc = new pApiTagging;

if ($_POST['action'] == 'storeallocation') {
    $oAlloc->storeAllocations($this_idartlang, $_POST['allocation']);
}
if ($_GET['step'] == 'collapse') {
    $oTree->setTreeStatus($_GET['idpica_alloc']);
}

#build category path
$catString = '';
prCreateURLNameLocationString($idcat, '/', $catString);
$oArticle = new cApiArticleLanguage();
$oArticle->loadByArticleAndLanguageId($idart, $lang);
$sArticleTitle = $oArticle->getField('title');

$sLocationString = '<div class="categorypath">' . $catString . '/' . htmlspecialchars($sArticleTitle) . '</div>';

// load allocations
$loadedAllocations = $oAlloc->loadAllocations($this_idartlang);

$oTree->setChecked($loadedAllocations);
$result = $oTree->renderTree(true);

if ($result == false) {
    $result = $notification->returnNotification('warning', i18n('There is no tagging tree.', 'tagging'));
} else {
    if (!is_object($tpl)) { $tpl = new Template(); }
    $hiddenfields = '<input type="hidden" name="action" value="storeallocation">
        <input type="hidden" name="idart" value="'.$idart.'">
        <input type="hidden" name="contenido" value="'.$sess->id.'">
        <input type="hidden" name="area" value="'.$area.'">
        <input type="hidden" name="frame" value="'.$frame.'">
        <input type="hidden" name="idcat" value="'.$idcat.'">';
    $tpl->set('s', 'HIDDENFIELDS', $hiddenfields);

    if (sizeof($loadedAllocations) > 0) {
        $tpl->set('s', 'ARRAY_CHECKED_BOXES', 'var checkedBoxes = [' . implode(',', $loadedAllocations) . '];');
    } else {
        $tpl->set('s', 'ARRAY_CHECKED_BOXES', 'var checkedBoxes = [];');
    }

    $oDiv = new cHTMLDiv();
    $oDiv->updateAttributes(array('style' => 'text-align:right;padding:5px;width:730px;border:1px #B3B3B3 solid;background-color:#FFF;'));
    $oDiv->setContent('<input type="image" src="images/but_ok.gif" />');
    $tpl->set('s', 'DIV', '<br>' . $oDiv->render());

    $tpl->set('s', 'TREE', $result);

    $tpl->set('s', 'REMOVE_ALL', i18n("Remove all", 'tagging'));
    $tpl->set('s', 'REMOVE', i18n("Remove", 'tagging'));

    $result = $tpl->generate($cfg['pica']['treetemplate_complexlist'], true);

    $script = '<link rel="stylesheet" type="text/css" href="'.$cfg['pica']['style_complexlist'].'"/>
    <script language="javascript" src="'.$cfg['pica']['script_complexlist'].'"></script>';
    $oPage->addScript('style', $script);
}


$oPage->setContent($sLocationString.$result . markSubMenuItem(6, true));
$oPage->render();

?>