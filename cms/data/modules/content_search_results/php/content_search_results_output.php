<?php

/**
 * A simple header.
 * Any formatting performed in the backend is removed.
 * The header, if given, is then wrapped in a H1 element.
 *
 * @package Module
 * @subpackage search_result
 * @version SVN Revision $Rev:$
 * @author marcus.gnass@4fb.de
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// frontend
cInclude('frontend', 'classes/class.module.search_result.php');

// TODO use cSecurity
$searchterm = '';
if (isset($_GET['search_term'])) {
    $searchterm = $_GET['search_term'];
} elseif (isset($_POST['search_term'])) {
    $searchterm = $_POST['search_term'];
}

// TODO use cSecurity
$page = 1;
if (isset($_GET['page']) && is_numeric($_GET['page']) && 0 < $_GET['page']) {
    $page = $_GET['page'];
}

$mod = new SearchResultModule(array(
    'templateName' => 'content_search_results/template/get.tpl',
    'label' => array(
        'linkSearchResults' => mi18n("LINK_SEARCH_RESULTS"),
        'more' => mi18n("MORE"),
        'msgNoResultsFound' => mi18n("MSG_NO_RESULTS_FOUND"),
        'msgResultsFound' => mi18n("MSG_RESULTS_FOUND"),
        'next' => mi18n("NEXT"),
        'previous' => mi18n("PREVIOUS"),
        'resultPage' => mi18n("RESULT_PAGE"),
        'viewResultPage' => mi18n("VIEW_RESULT_PAGE"),
        'viewNextResultPage' => mi18n("VIEW_NEXT_RESULT_PAGE"),
    ),
    // Number of results per page
    'itemsPerPage' => 10,
    'maxTeaserTextLen' => 200,
    'page' => $page,
    'searchTerm' => $searchterm
));
$mod->render();

?>