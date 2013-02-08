<?php

/**
 * description: google map
 *
 * @package Module
 * @author alexander.scheider@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

// use smarty template to output header text
$tpl = Contenido_SmartyWrapper::getInstance();

$typeHead = "CMS_HTMLHEAD";
$typeText = "CMS_TEXT";
$idartlang = cRegistry::getArticleLanguageId(true);
$artId = cRegistry::getArticleId(true);
$client = cRegistry::getClientId(true);
$lang = cRegistry::getLanguageId(true);
// add cms tags for backend edit mod

$artHeader = new Article($artId, $client, $lang, $idartlang);
$header = $artHeader->getContent($typeHead, 600);

$artAddress = new Article($artId, $client, $lang, $idartlang);
$address = $artAddress->getContent($typeHead, 601);

$artLat = new Article($artId, $client, $lang, $idartlang);
$lat = $artLat->getContent($typeText, 602);

$artLon = new Article($artId, $client, $lang, $idartlang);
$lon = $artLon->getContent($typeText, 603);

$artMarkerTitle = new Article($artId, $client, $lang, $idartlang);
$markerTitle = $artMarkerTitle->getContent($typeText, 604);

$artWay = new Article($artId, $client, $lang, $idartlang);
$way = $artWay->getContent($typeHead, 605);

// get gmap api
$gmapApiKey = '<script src="http://maps.google.com/maps/api/js?sensor=false" type="text/javascript"></script>';
// assign data to the smarty template
$tpl->assign('gmapApiKey', $gmapApiKey);

if (cRegistry::isBackendEditMode()) {
    echo "Header:";
    echo "CMS_HTMLHEAD[600]";
    echo "<br />";
    echo "Adresse:";
    echo "CMS_HTMLHEAD[601]";
    echo "<br />";
    echo mi18n("latitude") . ':';
    echo "CMS_TEXT[602]";
    echo "<br />";
    echo mi18n("longitude") . ':';
    echo "CMS_TEXT[603]";
    echo "<br />";
    echo mi18n("markerTitle") . ':';
    echo "CMS_TEXT[604]";
    echo "<br />";
}

if (FALSE === cRegistry::isBackendEditMode()) {
    $tpl->assign('header', $header);
    $tpl->assign('address', $address);
    $tpl->assign('lat', $lat);
    $tpl->assign('lon', $lon);
    $tpl->assign('markerTitle', $markerTitle);
    $tpl->assign('way', $way);
    $tpl->assign('wayDescription', mi18n("way"));
}

// fetch template content
echo $tpl->fetch('content_map_google/template/get.tpl');

if (cRegistry::isBackendEditMode()) {
    echo mi18n("way") . ':';
    echo "CMS_HTMLHEAD[605]";
}

?>