<?php
/**
 * Backend action file con_meta_saveart
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Dominik Ziegler
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.con2.php');

$oldData = array();

$availableTags = conGetAvailableMetaTagTypes();
foreach ($availableTags as $key => $value) {
    $oldData[$value["metatype"]] = conGetMetaValue($idartlang, $key);
}

$artLang = new cApiArticleLanguage(cSecurity::toInteger($idartlang));
$artLang->set('pagetitle', $_POST["page_title"]);
$artLang->set("urlname", $_POST["alias"]);
$artLang->set("sitemapprio", $_POST["sitemap_prio"]);
$artLang->set("changefreq", $_POST["sitemap_change_freq"]);
$artLang->store();

$robots = "";
$robotArray = ($_POST["robots"] == null) ? array() : $_POST["robots"];
if(in_array("noindex", $robotArray)) {
    $robots .= "noindex, ";
} else {
    $robots .= "index, ";
}
if(in_array("nosnippet", $robotArray)) {
	$robots .= "nosnippet, ";
}
if(in_array("noimageindex", $robotArray)) {
    $robots .= "noimageindex, ";
}
if(in_array("noarchive", $robotArray)) {
    $robots .= "noarchive, ";
}

if(in_array("nofollow", $robotArray)) {
    $robots .= "nofollow";
} else {
    $robots .= "follow";
}

$newData = array();
foreach ($availableTags as $key => $value) {
    if($value["metatype"] == "robots") {
    	conSetMetaValue($idartlang, $key, $robots);
    	$newData[$value["metatype"]] = $robots;
    } else {
    	conSetMetaValue($idartlang, $key, $_POST["META" . $value["metatype"]]);
    	$newData[$value["metatype"]] = $_POST["META" . $value["metatype"]];
    }
}

// meta tags have been saved, so clear the article cache
$purge = new cSystemPurge();
$purge->clearArticleCache($idartlang);

cApiCecHook::execute("Contenido.Action.con_meta_saveart.AfterCall", $idart, $newData, $oldData);

$notification->displayNotification("info", i18n("Changes saved"));
