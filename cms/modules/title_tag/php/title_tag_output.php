<?php
/**
* $RCSfile$
*
* Description: Print HTML title tag content
*
* @version 1.0.0
* @author Rudi Bieller
* @copyright four for business AG <www.4fb.de>
*
* {@internal
* created 2008-04-07
* }}
*
* $Id$
*/

try {
    $oBread = new Contenido_FrontendNavigation_Breadcrumb($db, $cfg, $client, $lang, $cfgClient);
    $oBreadCats = $oBread->get($idcat, 1);
    $sBread = 'CONTENIDO - ';
    $aBread = array();
    foreach ($oBreadCats as $oConCat) {
        $aBread[] = $oConCat->getCategoryLanguage()->getName();
    }
    $sBread .= implode(' - ', $aBread);
    $oArticle = new cApiArticleLanguage();
    $oArticle->loadByArticleAndLanguageId($idart, $lang);
    $sHeadline = strip_tags($oArticle->getContent('CMS_HTMLHEAD', 1));
    if ($sHeadline != '') {
        $sBread .= ' - '.$sHeadline;
    }
    echo $sBread;
} catch (InvalidArgumentException $eI) {
    echo 'CONTENIDO ';
} catch (Exception $e) {
    echo 'CONTENIDO ';
}
?>