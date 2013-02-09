<?php
/**
 * Description: Search output box
 *
 * @version 1.0.2
 * @author Willi Man
 * @copyright four for business AG <www.4fb.de>
 *
 *            {@internal
 *            created 2004-05-04
 *            $Id: search_output_output.php 3584 2012-10-26 10:50:54Z
 *            konstantinos.katikak $
 *            }}
 */

// get ugly global variables
global $tpl, $sArtSpecs;

// get nice global variables from registry
$cfg = cRegistry::getConfig();
$client = cRegistry::getClientId();
$lang = cRegistry::getLanguageId();
$db = cRegistry::getDb();
$idcat = cRegistry::getCategoryId();
$idart = cRegistry::getArticleId();
$sess = cRegistry::getSession();

// system properties in use:
// type: searchrange, name: include
// Contains comma-separated list of cats to be included into search (sub-cats
// are included automatically)

// Logical combination of search terms with AND or OR

define('CON_SEARCH_ITEMSPERPAGE', 10);
define('CON_SEARCH_MAXLEN_TEASERTEXT', 200);

// Includes
cInclude('includes', 'functions.api.string.php');

// initialize template object
$tpl = Contenido_SmartyWrapper::getInstance();
global $force;
if (1 == $force) {
    $tpl->clearAllCache();
}

// translatable labels
$tpl->assign('label', array(
    'gaveResults' => mi18n("GAVE_RESULTS"),
    'gaveNoResults' => mi18n("GAVE_NO_RESULTS"),
    'linkSearchResults' => mi18n("LINK_SEARCH_RESULTS"),
    'more' => mi18n("MORE"),
    'next' => mi18n("NEXT"),
    'previous' => mi18n("PREVIOUS"),
    'resultPage' => mi18n("RESULT_PAGE"),
    'txtNoSearchResults' => mi18n("TXT_NO_SEARCH_RESULTS"),
    'viewResultPage' => mi18n("VIEW_RESULT_PAGE"),
    'yourSearchFor' => mi18n("YOUR_SEARCH_FOR")
));

// Multilingual settings
$sYourSearchFor = mi18n("YOUR_SEARCH_FOR");
$sMore = mi18n("MORE");

// Get search term and pre-process it
if (isset($_GET['searchterm'])) {
    $searchTerm = $_GET['searchterm'];
} elseif (isset($_POST['searchterm'])) {
    $searchTerm = $_POST['searchterm'];
}
$searchTerm = urldecode(conHtmlentities(strip_tags(stripslashes($searchTerm))));
$searchTerm = str_replace(' + ', ' AND ', $searchTerm);
$searchTerm = str_replace(' - ', ' NOT ', $searchTerm);
$searchterm_display = $searchTerm;

if (strlen(trim($searchTerm)) > 0) {

    // Parse search term and set search options
    $searchTerm = conHtmlEntityDecode($searchTerm);

    if (stristr($searchTerm, ' or ') === false) {
        $combine = 'and';
    } else {
        $combine = 'or';
    }
    $searchTerm = str_replace(' and ', ' ', strtolower($searchTerm));
    $searchTerm = str_replace(' or ', ' ', strtolower($searchTerm));

    $search = new cSearch(array(
        // use db function regexp
        'db' => 'regexp',
        // combine searchterms with and
        'combine' => $combine,
        // => searchrange specified in 'cat_tree', 'categories' and 'articles'
        // is excluded, otherwise included (exclusive)
        'exclude' => false,
        // searchrange
        'cat_tree' => getSearchRange($client),
        // array of article specifications => search only articles with these
        // artspecs
        'artspecs' => getArticleSpecs($client, $lang),
        // => do not search articles or articles in categories which are offline
        // or protected
        'protected' => true
    ));
    // search only in these cms-types
    $search->setCmsOptions(array(
        'head',
        'html',
        'htmlhead',
        'htmltext',
        'text'
    ));

    // Execute search
    $aSearchResults = $search->searchIndex($searchTerm, '');

    // Build results page
    if (0 < count($aSearchResults)) {

        // Build meessage
        $message = $sYourSearchFor . " '" . conHtmlSpecialChars(strip_tags($searchterm_display)) . "' " . mi18n("GAVE_RESULTS") . ":";
        $message = str_replace('$$$', count($aSearchResults), $message);
        $tpl->assign('MESSAGE', $message);

        // Number of results per page
        $number_of_results = CON_SEARCH_ITEMSPERPAGE;
        $oSearchResults = new cSearchResult($aSearchResults, $number_of_results);

        $num_res = $oSearchResults->getNumberOfResults() + $pdf_count;
        $num_pages = $oSearchResults->getNumberOfPages();
        // html-tags to emphasize the located searchterms
        $oSearchResults->setReplacement('<strong>', '</strong>');

        // Get current result page
        if (isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0) {
            $page = $_GET['page'];
            $res_page = $oSearchResults->getSearchResultPage($page);
        } else {
            $page = 1;
            $res_page = $oSearchResults->getSearchResultPage($page);
        }

        // Build links to other result pages
        for ($i = 1; $i <= $num_pages; $i++) {
            // this is just for sample client - modify to your needs!
            if ($cfg['url_builder']['name'] == 'front_content' || $cfg['url_builder']['name'] == 'MR') {
                $aParams = array(
                    'lang' => $lang,
                    'idcat' => $idcat,
                    'idart' => $idart,
                    'searchterm' => $searchterm_display,
                    'page' => ($i . $sArtSpecs)
                );
            } else {
                $aParams = array(
                    'search' => array(
                        'lang' => $lang,
                        'idcat' => $idcat,
                        'idart' => $idart,
                        'searchterm' => $searchterm_display,
                        'page' => ($i . $sArtSpecs)
                    ),
                    // needed to build category path
                    'idcat' => $idcat,
                    // needed to build category path
                    'lang' => $lang,
                    // needed to build category path
                    'level' => 1
                );
            }
            try {
                $nextlink = cUri::getInstance()->build($aParams);
            } catch (cInvalidArgumentException $e) {
                $nextlink = $sess->url('front_content.php?idcat=' . $idcat . '&idart=' . $idart . '&searchterm=' . $searchterm_display . '&page=' . $i . $sArtSpecs);
            }
            if ($i == $page) {
                $nextlinks .= '<span style="white-space:nowrap;"> <strong>' . $i . '</strong> </span>';
            } else {
                $nextlinks .= '<span style="white-space:nowrap;"> <a href="' . $nextlink . '" title="' . $i . '. ' . mi18n("VIEW_RESULT_PAGE") . '">' . $i . '</a> </span>';
            }
        }
        $tpl->assign('PAGES', $nextlinks);

        // Build link to next result page
        if ($page < $num_pages) {
            $n = $page + 1;
            // this is just for sample client - modify to your needs!
            if ($cfg['url_builder']['name'] == 'front_content' || $cfg['url_builder']['name'] == 'MR') {
                $aParams = array(
                    'lang' => $lang,
                    'idcat' => $idcat,
                    'idart' => $idart,
                    'searchterm' => $searchterm_display,
                    'page' => ($n . $sArtSpecs)
                );
            } else {
                $aParams = array(
                    'search' => array(
                        'lang' => $lang,
                        'idcat' => $idcat,
                        'idart' => $idart,
                        'searchterm' => $searchterm_display,
                        'page' => ($n . $sArtSpecs)
                    ),
                    // needed to build category path
                    'idcat' => $idcat,
                    // needed to build category path
                    'lang' => $lang,
                    // needed to build category path
                    'level' => 1
                );
            }
            try {
                $next = cUri::getInstance()->build($aParams);
            } catch (cInvalidArgumentException $e) {
                $next = $sess->url(implode('?', array(
                    'front_content.php',
                    implode('&', array(
                        'idcat=' . $idcat,
                        'idart=' . $idart,
                        'searchterm=' . $searchTerm,
                        'page=' . $n . $sArtSpecs
                    ))
                )));
            }
            $nextpage .= ' <a href="' . $next . '" title="' . mi18n("VIEW_NEXT_RESULT_PAGE") . '">' . mi18n("NEXT") . '  <img src="images/link_pfeil_klein.gif" alt="" /></a>';
            $tpl->assign('NEXT', $nextpage);
        } else {
            $tpl->assign('NEXT', '');
        }

        // Build link to previous result page
        if ($page > 1) {
            $p = $page - 1;
            // this is just for sample client - modify to your needs!
            if ($cfg['url_builder']['name'] == 'front_content' || $cfg['url_builder']['name'] == 'MR') {
                $aParams = array(
                    'lang' => $lang,
                    'idcat' => $idcat,
                    'idart' => $idart,
                    'searchterm' => $searchterm_display,
                    'page' => ($p . $sArtSpecs)
                );
            } else {
                $aParams = array(
                    'search' => array(
                        'lang' => $lang,
                        'idcat' => $idcat,
                        'idart' => $idart,
                        'searchterm' => $searchterm_display,
                        'page' => ($p . $sArtSpecs)
                    ),
                    // needed to build category path
                    'idcat' => $idcat,
                    // needed to build category path
                    'lang' => $lang,
                    // needed to build category path
                    'level' => 1
                );
            }
            try {
                $pre = cUri::getInstance()->build($aParams);
            } catch (cInvalidArgumentException $e) {
                $pre = $sess->url('front_content.php?idcat=' . $idcat . '&idart=' . $idart . '&searchterm=' . $searchTerm . '&page=' . $p . $sArtSpecs);
            }
            $prevpage .= '<a href="' . $pre . '" title="' . mi18n("VIEW_PREVIOUS_PAGE") . '"><img src="images/link_pfeil_klein_links.gif" alt="" />  ' . mi18n("PREVIOUS") . '</a> ';
            $tpl->assign('PREV', $prevpage);
        } else {
            $tpl->assign('PREV', '');
        }

        if (count($res_page) > 0) {
            $i = 1;
            // Build single search result on result page
            foreach ($res_page as $key => $val) {
                $num = $i + (($page - 1) * $number_of_results);
                $oArt = new cApiArticleLanguage();
                $oArt->loadByArticleAndLanguageId($key, $lang);
                // Get publishing date of article
                $pub_system = $oArt->getField('published');
                $pub_user = trim(strip_tags($oArt->getContent('HEAD', 90)));
                if ($pub_user != '') {
                    $show_pub_date = "[" . $pub_user . "]";
                } else {
                    $show_pub_date = '';
                    if ($pub_system[8] != '0') {
                        $show_pub_date .= $pub_system[8];
                    }
                    $show_pub_date .= $pub_system[9] . '.';
                    if ($pub_system[5] != '0') {
                        $show_pub_date .= $pub_system[5];
                    }
                    $show_pub_date .= $pub_system[6] . "." . $pub_system[0] . $pub_system[1] . $pub_system[2] . $pub_system[3] . "]";
                    $show_pub_date = "[" . $show_pub_date;
                }

                // Get text and headline of current article
                $aHeadline = $oSearchResults->getSearchContent($key, 'HTMLHEAD', 1);
                $aSubheadline = $oSearchResults->getSearchContent($key, 'HTMLHEAD', 2);
                $text = $oSearchResults->getSearchContent($key, 'HTML', 1);
                $text = cApiStrTrimAfterWord($text[0], CON_SEARCH_MAXLEN_TEASERTEXT);
                // conflict with cApiStrTrimAfterWord and
                // setReplacement('<strong>', '</strong>')
                $headline = cApiStrTrimAfterWord($aHeadline[0], CON_SEARCH_MAXLEN_TEASERTEXT);
                $subheadline = cApiStrTrimAfterWord($aSubheadline[0], CON_SEARCH_MAXLEN_TEASERTEXT);

                $cat_id = $oSearchResults->getArtCat($key);
                $similarity = $oSearchResults->getSimilarity($key);

                $similarity = sprintf("%.0f", $similarity);

                // Send output to template
                // this is just for sample client - modify to your needs!
                if ($cfg['url_builder']['name'] == 'front_content' || $cfg['url_builder']['name'] == 'MR') {
                    $aParams = array(
                        'lang' => $lang,
                        'idcat' => $cat_id,
                        'idart' => $key
                    );
                } else {
                    $aParams = array(
                        'search' => array(
                            'lang' => $lang,
                            'idcat' => $cat_id,
                            'idart' => $key
                        ),
                        // needed to build category path
                        'idcat' => $idcat,
                        // needed to build category path
                        'lang' => $lang,
                        // needed to build category path
                        'level' => 1
                    );
                }
                try {
                    $href = cUri::getInstance()->build($aParams);
                } catch (cInvalidArgumentException $e) {
                    $href = $sess->url("front_content.php?idcat=$cat_id&idart=$key");
                }
                $tpl->set('d', 'more', $sMore);
                $tpl->set('d', 'HREF', $href);
                $tpl->set('d', 'TITLE', mi18n("LINK_SEARCH_RESULTS") . ' ' . $i);
                $tpl->set('d', 'NUM', $num);
                $tpl->set('d', 'CATNAME', $headline);
                $tpl->set('d', 'HEADLINE', $text);
                $tpl->set('d', 'SUBHEADLINE', $subheadline);
                $tpl->set('d', 'SIMILARITY', $similarity);
                $tpl->set('d', 'TARGET', '_self');
                $tpl->set('d', 'PUB_DATE', $show_pub_date);
                $tpl->next();
                $i++;

            }
            $tpl->display('content_search_results/template/get.tpl');
        }
    } else {
        // No results
        $tpl->assign('MESSAGE', $sYourSearchFor . " '" . conHtmlSpecialChars(strip_tags($searchTerm)) . "' " . mi18n("GAVE_NO_RESULTS") . ".");
        $tpl->assign('NEXT', '');
        $tpl->assign('PREV', '');
        $tpl->assign('PAGES', '');
        $tpl->assign('result_page', '');
        $tpl->display('content_search_results/template/get.tpl');
    }
} else {
    echo '<div id="searchResults">';
    echo '<h1>' . mi18n("TXT_NO_SEARCH_RESULTS") . '</h1>';
    echo '</div>';
}

/**
 *
 * @param int $client
 */
function getSearchRange($client) {

    $clientObject = new cApiClient($client);

    $searchRange = $clientObject->getProperty('searchrange', 'include');
    $searchRange = explode(',', $searchRange);

    return $searchRange;

}

/**
 * Get all article specs
 *
 * @param int $client
 * @param int $lang
 */
function getArticleSpecs($client, $lang) {

    $cfg = cRegistry::getConfig();
    $db = cRegistry::getDb();

    $sql = "-- getArticleSpecs()
        SELECT
            idartspec
            , artspec
        FROM
            " . $cfg['tab']['art_spec'] . "
        WHERE
            client = $client
            AND lang = $lang
            AND online = 1
        ;";

    $db->query($sql);

    $aArtSpecs = array();
    while ($db->next_record()) {
        $aArtSpecs[] = $db->f('idartspec');
    }
    $aArtSpecs[] = 0;

}

?>