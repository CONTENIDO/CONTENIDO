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
die("test");
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

$jslink = 'parent.parent.frames[\'left\'].frames[\'left_top\'].document.getElementById(\'selectedfile\').value= \'' . $mcatlink . '\'; window.returnValue=\'' . $mcatlink . '\'; window.close();';
$content[] = '<div style="padding: 2px; border: 1px; border-color: #B3B3B3; border-style: solid; background-color: #F4F4F7;">';
$content[] = '<table border="0" cellspacing="0" cellpadding="0"><tr><td>';
$content[] = '<a href="javascript://" onclick="' . $jslink . '"><img alt="' . i18n("Use this category") . '" title="' . i18n("Use this category") . '" style="padding-right: 4px;" src="images/folder_movedown.gif"></a></td><td>';
$content[] = $cat_str;
$content[] = '</td></tr></table></div>';

$content[] = '<div>&#160;</div>';

$cApiCategoryArticleCollection = new cApiCategoryArticleCollection();
$cApiCategoryArticleCollection->link("cApiArticleCollection");
$cApiCategoryArticleCollection->link("cApiCategoryCollection");
$cApiCategoryArticleCollection->setWhere("cApiCategoryCollection.idcat", $idcat);
$cApiCategoryArticleCollection->query();

$headlines = array(
        i18n("Article"),
        i18n("Title"),
        i18n("Modified"),
        i18n("Created"),
        i18n("Sort order"),
        i18n("Online")
);
$fields = array(
        "is_start",
        "title",
        "lastmodified",
        "created",
        "artsort",
        "online"
);

$content[] = '<table class="generic" width="100%" border="0" cellspacing="0" cellpadding="2"><tr>';

foreach ($headlines as $headline) {
    $content[] = '<th width="1%" nowrap="nowrap">' . $headline . '</th>';
}

$content[] = '</tr>';

$dateformat = getEffectiveSetting("dateformat", "full", "Y-m-d H:i:s");

$odd = false;

while ($cApiCategoryArticle = $cApiCategoryArticleCollection->next()) {

    $obj = $cApiCategoryArticleCollection->fetchObject("cApiArticleCollection");
    $idart = $obj->get("idart");

    $obj = new cApiArticleLanguage();
    $obj->loadByArticleAndLanguageId($idart, $lang);

    $content[] = '<tr>';

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

    $jslink = 'parent.parent.frames[\'left\'].frames[\'left_top\'].document.getElementById(\'selectedfile\').value= \'' . $martlink . '\'; window.returnValue=\'' . $martlink . '\'; window.close();';

    foreach ($fields as $field) {
        switch ($field) {
            case "is_start":
                $value = isStartArticle($obj->get("idartlang"), $idcat, $lang);

                if ($value == true) {
                    $value = '<img src="images/isstart1.gif">';
                } else {
                    $value = '<img src="images/isstart0.gif">';
                }
                break;
            case "created":
            case "modified":
                $value = date($dateformat, strtotime($obj->get($field)));
                break;
            case "online":
                if ($obj->get("online") == true) {
                    $value = '<img src="images/online.gif">';
                } else {
                    $value = '<img src="images/offline.gif">';
                }
                break;

            default:
                $value = $obj->get($field);
                break;
        }

        if ($field == "title") {
            $xwidth = 'width="100%"';
        } else {
            $xwidth = 'width="1%"';
        }

        $content[] = '<td onclick="' . $jslink . '" nowrap="nowrap" ' . $xwidth . ' class="bordercell">' . $value . '</td>';
    }

    $content[] = '</tr>';
}

$content[] = '</table>';
$div = new cHTMLDiv();
$div->setContent(implode("", $content));
$page->setContent($div);
$page->render();
?>