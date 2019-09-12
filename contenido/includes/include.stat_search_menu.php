<?php

/**
 * This file contains the menu frame backend page for the search tracking
 *
 * @package Core
 * @subpackage Backend
 * @author Mischa Holz
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$page = new cGuiPage("stat_search_menu");

// don't display anything if the feature is turned off
if (getEffectiveSetting("search", "term_tracking", "on") != "on") {
    die();
}

// select the most popular terms and display them
$searchTerms = new cApiSearchTrackingCollection();
$db = $searchTerms->queryPopularSearchTerms();
while ($db->nextRecord()) {
    $page->set("d", "SEARCHTERM_URL", urlencode($db->f("searchterm")));
    $page->set("d", "SEARCHTERM", conHtmlSpecialChars($db->f("searchterm")));
    $page->next();
}

$page->render();

?>