<?php
/**
 * This file contains the backend page for displaying statistics overview.
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

$page = new cGuiPage("stat_search_overview");

// check for the client setting. If search tracking is not allowed, display an error
if(getEffectiveSetting("search", "term_tracking", "on") != "on") {
    $page->displayCriticalError(i18n('You disabled search tracking in the client settings.'));
    $page->render();
    die();
}

// the collection we'll be showing
$termCollection = new cApiSearchTrackingCollection();

// switch between showing information about a single search term or all of them
if($action == "show_single_term") {
    // select all entries about one term
    $termCollection->selectSearchTerm($term);
    
    $page->set("s", "ADDITIONAL_INFO", i18n('Date')); // in this view the last row shows the date of the search
    $page->set("s", "RESULTS_HEADER", i18n("Number of Results")); // and we'll display the number of results individually
    
    // fill template
    $i = 0;
    while($termItem = $termCollection->next()) {
        $i++;
        $page->set("d", "NUMBER", $i);
        $page->set("d", "SEARCH_TERM", $term);
        $page->set("d", "NUMBER_OF_RESULTS", $termItem->get("results"));
        $page->set("d", "ADDITIONAL_INFO", $termItem->get("datesearched"));
        $page->next();
    }
} else {
    // select all search terms and sort them by popularity
    $termCollection->selectPopularSearchTerms();
    
    $db = cRegistry::getDb();
    // select all search terms, count their occurence and calculate the average number of results
    $db->query('SELECT searchterm, COUNT(searchterm), AVG(results)
                FROM ' . $cfg['tab']['search_tracking'] . '
                GROUP BY searchterm
                ORDER BY COUNT(searchterm) DESC');
    $counts = array();
    // save this information in an array
    while($db->next_record()) {
        $counts[$db->f('searchterm')] = array('count' => $db->f('COUNT(searchterm)'), 'avg' => $db->f('AVG(results)'));
    }
    
    $page->set("s", "ADDITIONAL_INFO", i18n('Count')); // in this view the last row shows the number of searches with this term
    $page->set("s", "RESULTS_HEADER", i18n("Average Number of Results")); // and we show the average number of results for this term
    
    // fill template
    $i = 0;
    while($termItem = $termCollection->next()) {
        $i++;
        $page->set("d", "NUMBER", $i);
        $page->set("d", "SEARCH_TERM", $termItem->get('searchterm'));
        $page->set("d", "NUMBER_OF_RESULTS", round($counts[$termItem->get('searchterm')]['avg'], 2));
        $page->set("d", "ADDITIONAL_INFO", $counts[$termItem->get('searchterm')]['count']);
        $page->next();
    }
}

$page->render();
?>