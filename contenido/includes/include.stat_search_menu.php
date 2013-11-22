<?php
/**
 * This file contains the menu frame backend page for statistics area.
 *
 * @package          Core
 * @subpackage       Backend
 * @version          SVN Revision $Rev:$
 *
 * @author           Olaf Niemann
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$page = new cGuiPage("stat_search_menu");

if(getEffectiveSetting("search", "term_tracking", "on") != "on") {
    die();
}

$searchTerms = new cApiSearchTrackingCollection();
$searchTerms->selectPopularSearchTerms();
while($term = $searchTerms->next()) {
    $page->set("d", "SEARCHTERM_URL", urlencode($term->get("searchterm")));
    $page->set("d", "SEARCHTERM", $term->get("searchterm"));
    $page->next();
}

$page->render();
?>