<?php

/**
 * Settings:
 * Which articles are searchable can be defined by a client setting
 * searchable/idcats
 *
 * @package Module
 * @subpackage ContentSearchResult
 * @author marcus.gnass@4fb.de
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// frontend
cInclude('module', 'class.module.search_result.php');

// get search term
$searchTerm = '';
if (isset($_GET['search_term'])) {
    $searchTerm = cSecurity::toString($_GET['search_term']);
} elseif (isset($_POST['search_term'])) {
    $searchTerm = cSecurity::toString($_POST['search_term']);
}

// get page number
$page = isset($_GET['page']) ? abs(cSecurity::toInteger($_GET['page'])) : 1;

// create & render module
$mod = new SearchResultModule(array(
    'templateName' => 'content_search_results/template/get.tpl',
    'label' => array(
        'linkSearchResults' => mi18n("LINK_SEARCH_RESULTS"),
        'more' => mi18n("MORE"),
        'msgNoResultsFound' => mi18n("MSG_NO_RESULTS_FOUND"),
        'msgResultsFound' => mi18n("MSG_RESULTS_FOUND"),
        'msgRange' => mi18n("MSG_RANGE"),
        'next' => mi18n("NEXT"),
        'headline' => mi18n("RESULT_PAGE_HEADLINE"),
        'submit' => mi18n("SEARCH_SUBMIT"),
        'previous' => mi18n("PREVIOUS"),
        'resultPage' => mi18n("RESULT_PAGE"),
        'viewResultPage' => mi18n("VIEW_RESULT_PAGE"),
        'viewNextResultPage' => mi18n("VIEW_NEXT_RESULT_PAGE")
    ),
    'itemsPerPage' => 10,
    'maxTeaserTextLen' => 200,
    'page' => $page,
    'searchTerm' => $searchTerm
));
$mod->render();

?>