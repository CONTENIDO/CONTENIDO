<?php
/**
 * Project: CONTENIDO Content Management System Description: Article list for
 * upload Requirements: @con_php_req 5.0
 *
 *
 * @package CONTENIDO Backend Includes
 * @version 1.0.1
 * @author unknown
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since file available since CONTENIDO release <= 4.6 {@internal created
 *        unknown modified 2008-06-27, Frederic Schneider, add security fix $Id:
 *        include.upl_artlist.php 3927 2013-01-30 11:00:00Z frederic.schneider
 *        $: }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude("includes", "functions.con.php");

$page = new cGuiPage("upl_artlist");

conCreateLocationString($idcat, "/", $cat_str);

$mcatlink = "";

$_cecIterator = $_cecRegistry->getIterator("Contenido.Content.CreateCategoryLink");
if ($_cecIterator->count() > 0) {
    while ($chainEntry = $_cecIterator->next()) {
        $catlink = $chainEntry->execute($idcat);

        if ($catlink != "") {
            $mcatlink = $catlink;
        }
    }
}

if ($mcatlink == "") {
    $mcatlink = "front_content.php?idcat=$idcat";
}

$page->set("s", "CATLINK", $mcatlink);
$page->set("s", "CATSTR", $cat_str);

$cApiCategoryArticleCollection = new cApiCategoryArticleCollection();
$cApiCategoryArticleCollection->link("cApiArticleCollection");
$cApiCategoryArticleCollection->link("cApiCategoryCollection");
$cApiCategoryArticleCollection->setWhere("cApiCategoryCollection.idcat", $idcat);
$cApiCategoryArticleCollection->query();

$dateformat = getEffectiveSetting("dateformat", "full", "Y-m-d H:i:s");

$odd = false;

while ($cApiCategoryArticle = $cApiCategoryArticleCollection->next()) {

    $obj = $cApiCategoryArticleCollection->fetchObject("cApiArticleCollection");
    $idart = $obj->get("idart");

    $obj = new cApiArticleLanguage();
    $obj->loadByArticleAndLanguageId($idart, $lang);

    $martlink = "";
    $idart = $obj->get("idart");

    $_cecIterator = $_cecRegistry->getIterator("Contenido.Content.CreateArticleLink");
    if ($_cecIterator->count() > 0) {
        while ($chainEntry = $_cecIterator->next()) {
            $artlink = $chainEntry->execute($idart, $idcat);

            if ($artlink != "") {
                $martlink = $artlink;
            }
        }
    }

    if ($martlink == "") {
        $martlink = "front_content.php?idart=$idart";
    }

    $page->set("d", "ARTLINK", $martlink);
    $page->set("d", "ISSTART", (isStartArticle($obj->get("idartlang"), $idcat, $lang)) ? "1" : "0");
    $page->set("d", "TITLE", $obj->get("title"));
    $page->set("d", "MODIFIED", date($dateformat, strtotime($obj->get("lastmodified"))));
    $page->set("d", "CREATED", date($dateformat, strtotime($obj->get("created"))));
    $page->set("d", "ONLINE", ($obj->get("online")) ? "online" : "offline");
    $page->next();
}

$page->render();
?>