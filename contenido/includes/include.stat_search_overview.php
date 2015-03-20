<?php
/**
 * This file contains the backend page for displaying search statistic
 *
 * @package Core
 * @subpackage Backend
 * @version SVN Revision $Rev:$
 *         
 * @author Mischa Holz
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

$page = new cGuiPage("stat_search_overview");

// check for the client setting. If search tracking is not allowed, display an
// error
if (getEffectiveSetting("search", "term_tracking", "on") != "on") {
    $page->displayCriticalError(i18n('You disabled search tracking in the client settings.'));
    $page->render();
    die();
}

// the collection we'll be showing
$termCollection = new cApiSearchTrackingCollection();
$term = conHtmlEntityDecode($_GET['term']);

// switch between showing information about a single search term or all of them
if ($action == "show_single_term") {
    // select all entries about one term
    $termCollection->selectSearchTerm(addslashes($term));

    $page->set("s", "ADDITIONAL_INFO", i18n('Date'));
    $page->set("s", "RESULTS_HEADER", i18n("Number of Results"));

    // fill template
    $i = 0;
    while ($termItem = $termCollection->next()) {
        $i++;
        $page->set("d", "NUMBER", $i);
        $page->set("d", "SEARCH_TERM", conHtmlSpecialChars($term));
        $page->set("d", "NUMBER_OF_RESULTS", $termItem->get("results"));
        $page->set("d", "ADDITIONAL_INFO", $termItem->get("datesearched"));
        $page->next();
    }
} else {
    // select all search terms and sort them by popularity
    $termCollection->selectPopularSearchTerms();
    
    $db = cRegistry::getDb();
    // select all search terms, count their occurence and calculate the average
    // number of results
    $db->query('SELECT searchterm, COUNT(searchterm), AVG(results)
                FROM ' . $cfg['tab']['search_tracking'] . '
                GROUP BY searchterm
                ORDER BY COUNT(searchterm) DESC');
    $counts = array();
    // save this information in an array
    while ($db->next_record()) {
        $counts[$db->f('searchterm')] = array(
            'count' => $db->f('COUNT(searchterm)'),
            'avg' => $db->f('AVG(results)')
        );
    }
    
    $page->set("s", "ADDITIONAL_INFO", i18n('Count'));
    $page->set("s", "RESULTS_HEADER", i18n("Average Number of Results"));
    
    // fill template
    $i = 0;
    while ($termItem = $termCollection->next()) {
        $i++;
        $page->set("d", "NUMBER", $i);
        $page->set("d", "SEARCH_TERM", conHtmlSpecialChars($termItem->get('searchterm')));
        $page->set("d", "NUMBER_OF_RESULTS", round($counts[$termItem->get('searchterm')]['avg'], 2));
        $page->set("d", "ADDITIONAL_INFO", $counts[$termItem->get('searchterm')]['count']);
        $page->next();
    }
}

$page->render();
?>