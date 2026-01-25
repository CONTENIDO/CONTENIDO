<?php

/**
 * This file contains the backend page for article list in upload section.
 *
 * @package    Core
 * @subpackage Backend
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.con.php');

$page = new cGuiPage('upl_artlist');

$idcat = cRegistry::getCategoryId();
$lang = cRegistry::getLanguageId();

conCreateLocationString($idcat, '/', $cat_str);

$mcatlink = '';

$cecIterator = cApiCecRegistry::getInstance()->getIterator('Contenido.Content.CreateCategoryLink');
while ($chainEntry = $cecIterator->next()) {
    $catlink = $chainEntry->execute($idcat);

    if ($catlink != '') {
        $mcatlink = $catlink;
    }
}

if ($mcatlink == '') {
    $mcatlink = "front_content.php?idcat=$idcat";
}

$page->set('s', 'CATLINK', $mcatlink);
$page->set('s', 'CATSTR', $cat_str);

$cApiCategoryArticleCollection = new cApiCategoryArticleCollection();
$cApiCategoryArticleCollection->link('cApiArticleCollection');
$cApiCategoryArticleCollection->link('cApiCategoryCollection');
$cApiCategoryArticleCollection->setWhere('cApiCategoryCollection.idcat', $idcat);
$cApiCategoryArticleCollection->query();

$dateformat = getEffectiveSetting('dateformat', 'full', 'Y-m-d H:i:s');

$odd = false;

while ($cApiCategoryArticle = $cApiCategoryArticleCollection->next()) {
    $obj = $cApiCategoryArticleCollection->fetchObject('cApiArticleCollection');
    $idart = $obj->get('idart');

    $obj = new cApiArticleLanguage();
    $obj->loadByArticleAndLanguageId($idart, $lang);

    $martlink = '';
    $idart = $obj->get('idart');
    if ($idart == null) {
        continue;
    }

    $cecIterator = cApiCecRegistry::getInstance()->getIterator('Contenido.Content.CreateArticleLink');
    while ($chainEntry = $cecIterator->next()) {
        $artlink = $chainEntry->execute($idart, $idcat);
        if ($artlink != '') {
            $martlink = $artlink;
        }
    }

    if ($martlink == '') {
        $martlink = "front_content.php?idart=$idart";
    }

    $page->set('d', 'ARTLINK', $martlink);
    $page->set('d', 'ISSTART', (isStartArticle($obj->get('idartlang'), $idcat, $lang)) ? '1' : '0');
    $page->set('d', 'TITLE', $obj->get('title'));
    $page->set('d', 'MODIFIED', date($dateformat, strtotime($obj->get('lastmodified'))));
    $page->set('d', 'CREATED', date($dateformat, strtotime($obj->get('created'))));
    $page->set('d', 'ONLINE', ($obj->get('online')) ? 'online' : 'offline');
    $page->next();
}

$page->render();
