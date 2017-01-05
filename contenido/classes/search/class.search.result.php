<?php

/**
 * This file contains the class for content search results.
 *
 * @package Core
 * @subpackage Frontend_Search
 * @author Willi Man
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.encoding.php');


/**
 * CONTENIDO API - SearchResult Object
 *
 * This object ranks and displays the result of the indexed fulltext
 * search.
 * If you are not comfortable with this API feel free to use your own
 * methods to display the search results.
 * The search result is basically an array with article ID's.
 *
 * If $search_result = $search->searchIndex($searchword, $searchwordex);
 *
 * use object with
 *
 * $oSearchResults = new cSearchResult($search_result, 10);
 *
 * // html-tags to emphasize the located searchwords
 * $oSearchResults->setReplacement('<span style="color:red">', '</span>');
 *
 * $num_res = $oSearchResults->getNumberOfResults();
 * $num_pages = $oSearchResults->getNumberOfPages();
 * // first result page
 * $res_page = $oSearchResults->getSearchResultPage(1);
 *
 * foreach ($res_page as $key => $val) {
 *      $headline = $oSearchResults->getSearchContent($key, 'HTMLHEAD');
 *      $first_headline = $headline[0];
 *      $text = $oSearchResults->getSearchContent($key, 'HTML');
 *      $first_text = $text[0];
 *      $similarity = $oSearchResults->getSimilarity($key);
 *      $iOccurrence = $oSearchResults->getOccurrence($key);
 * }
 *
 * @package Core
 * @subpackage Frontend_Search
 */
class cSearchResult extends cSearchBaseAbstract {

    /**
     * Instance of class Index
     *
     * @var object
     */
    protected $_index;

    /**
     * Number of results
     *
     * @var int
     */
    protected $_results;

    /**
     * Number of result pages
     *
     * @var int
     */
    protected $_pages;

    /**
     * Current result page
     *
     * @var int
     */
    protected $_resultPage;

    /**
     * Results per page to display
     *
     * @var int
     */
    protected $_resultPerPage;

    /**
     * Array of html-tags to emphasize the searchwords
     *
     * @var array
     */
    protected $_replacement = array();

    /**
     * Array of article id's with ranking information
     *
     * @var array
     */
    protected $_rankStructure = array();

    /**
     * Array of result-pages with array's of article id's
     *
     * @var array
     */
    protected $_orderedSearchResult = array();

    /**
     * Array of article id's with information about cms-types, occurence of
     * keyword/searchword, similarity .
     *
     * @var array
     */
    protected $_searchResult = array();

    /**
     * Constructor to create an instance of this class.
     *
     * Compute ranking factor for each search result and order the
     * search results by ranking factor.
     *
     * NOTE: The ranking factor is the sum of occurences of matching
     * searchterms weighted by similarity (in %) between searchword
     * and matching word in the article.
     *
     * TODO: One can think of more sophisticated ranking strategies.
     * The content type information could be used for example because a
     * matching word in the headline (CMS_HEADLINE[1]) could be weighted
     * more than a matching word in the text (CMS_HTML[1]).
     *
     * @param array $search_result
     *         list of article ids
     * @param int $result_per_page
     *         number of items per page
     * @param cDb $oDB [optional]
     *         CONTENIDO database object
     * @param bool $bDebug [optional]
     *         flag to enable debugging
     */
    public function __construct($search_result, $result_per_page, $oDB = NULL, $bDebug = false) {
        parent::__construct($oDB, $bDebug);

        $this->_index = new cSearchIndex($oDB);

        $this->_searchResult = $search_result;
        $this->_debug('$this->search_result', $this->_searchResult);

        $this->_resultPerPage = $result_per_page;
        $this->_results = count($this->_searchResult);

        // compute ranking factor for each search result
        foreach ($this->_searchResult as $article => $val) {
            $this->_rankStructure[$article] = $this->getOccurrence($article) * ($this->getSimilarity($article) / 100);
        }
        $this->_debug('$this->rank_structure', $this->_rankStructure);

        $this->setOrderedSearchResult($this->_rankStructure, $this->_resultPerPage);
        $this->_pages = count($this->_orderedSearchResult);
        $this->_debug('$this->ordered_search_result', $this->_orderedSearchResult);
    }

    /**
     *
     * @param array $ranked_search
     * @param int $result_per_page
     */
    public function setOrderedSearchResult($ranked_search, $result_per_page) {
        asort($ranked_search);

        $sorted_rank = array_reverse($ranked_search, true);

        if (isset($result_per_page) && $result_per_page > 0) {
            $split_result = array_chunk($sorted_rank, $result_per_page, true);
            $this->_orderedSearchResult = $split_result;
        } else {
            $this->_orderedSearchResult[] = $sorted_rank;
        }
    }

    /**
     *
     * @param int $art_id
     *         Id of an article
     * @param string $cms_type
     * @param int $id [optional]
     * @return string
     *         Content of an article, specified by it's content type
     */
    public function getContent($art_id, $cms_type, $id = 0) {
        $article = new cApiArticleLanguage();
        $article->loadByArticleAndLanguageId($art_id, $this->lang);
        return $article->getContent($cms_type, $id);
    }

    /**
     *
     * @param int $art_id
     *         Id of an article
     * @param string $cms_type
     *         Content type
     * @param int $cms_nr [optional]
     * @return string
     *         Content of an article in search result, specified by its type
     */
    public function getSearchContent($art_id, $cms_type, $cms_nr = NULL) {
        $cms_type = cString::toUpperCase($cms_type);
        if (cString::getStringLength($cms_type) > 0) {
            if (!cString::findFirstOccurrenceCI($cms_type, 'cms_')) {
                if (in_array($cms_type, $this->_index->getCmsTypeSuffix())) {
                    $cms_type = 'CMS_' . $cms_type;
                }
            } else {
                if (!array_key_exists($cms_type, $this->_index->getCmsType())) {
                    return array();
                }
            }
        }

        $article = new cApiArticleLanguage();
        $article->loadByArticleAndLanguageId($art_id, $this->lang);
        $content = array();
        if (isset($this->_searchResult[$art_id][$cms_type])) {
            // if searchword occurs in cms_type
            $search_words = $this->_searchResult[$art_id]['search'];
            $search_words = array_unique($search_words);

            $id_type = $this->_searchResult[$art_id][$cms_type];
            $id_type = array_unique($id_type);

            if (isset($cms_nr) && is_numeric($cms_nr)) {
                // get content of cms_type[cms_nr]
                // build consistent escaped string(Timo Trautmann) 2008-04-17
                $cms_content = conHtmlentities(conHtmlEntityDecode(strip_tags($article->getContent($cms_type, $cms_nr))));
                if (count($this->_replacement) == 2) {
                    foreach ($search_words as $word) {
                        // build consistent escaped string, replace ae ue ..
                        // with original html entities (Timo Trautmann)
                        // 2008-04-17
                        $word = conHtmlentities(conHtmlEntityDecode($this->_index->addSpecialUmlauts($word)));
                        $match = array();
                        preg_match("/$word/i", $cms_content, $match);
                        if (isset($match[0])) {
                            $pattern = $match[0];
                            $replacement = $this->_replacement[0] . $pattern . $this->_replacement[1];
                            $cms_content = preg_replace("/$pattern/i", $replacement, $cms_content); // emphasize
                            // located
                            // searchwords
                        }
                    }
                }
                $content[] = htmlspecialchars_decode($cms_content);
            } else {
                // get content of cms_type[$id], where $id are the cms_type
                // numbers found in search
                foreach ($id_type as $id) {
                    $cms_content = strip_tags($article->getContent($cms_type, $id));

                    if (count($this->_replacement) == 2) {
                        foreach ($search_words as $word) {
                            preg_match("/$word/i", $cms_content, $match);
                            if (isset($match[0])) {
                                $pattern = $match[0];
                                $replacement = $this->_replacement[0] . $pattern . $this->_replacement[1];
                                $cms_content = preg_replace("/$pattern/i", $replacement, $cms_content); // emphasize
                                // located
                                // searchwords
                            }
                        }
                    }
                    $content[] = $cms_content;
                }
            }
        } else {
            // searchword was not found in cms_type
            if (isset($cms_nr) && is_numeric($cms_nr)) {
                $content[] = strip_tags($article->getContent($cms_type, $cms_nr));
            } else {
                $art_content = $article->getContent($cms_type);
                if (count($art_content) > 0) {
                    foreach ($art_content as $val) {
                        $content[] = strip_tags($val);
                    }
                }
            }
        }
        return $content;
    }

    /**
     * Returns articles in page.
     *
     * @param int $page_id
     * @return array
     *         Articles in page $page_id
     */
    public function getSearchResultPage($page_id) {
        $this->_resultPage = $page_id;
        $result_page = $this->_orderedSearchResult[$page_id - 1];
        return $result_page;
    }

    /**
     * Returns number of result pages.
     *
     * @return int
     */
    public function getNumberOfPages() {
        return $this->_pages;
    }

    /**
     * Returns number of results.
     *
     * @return int
     */
    public function getNumberOfResults() {
        return $this->_results;
    }

    /**
     *
     * @param int $art_id
     *         Id of an article
     * @return int
     *         Similarity between searchword and matching word in article
     */
    public function getSimilarity($art_id) {
        return $this->_searchResult[$art_id]['similarity'];
    }

    /**
     *
     * @param int $art_id
     *         Id of an article
     * @return int
     *         number of matching searchwords found in article
     */
    public function getOccurrence($art_id) {
        $aOccurence = $this->_searchResult[$art_id]['occurence'];
        $iSumOfOccurence = 0;
        for ($i = 0; $i < count($aOccurence); $i++) {
            $iSumOfOccurence += $aOccurence[$i];
        }

        return $iSumOfOccurence;
    }

    /**
     *
     * @param string $rep1
     *         The opening html-tag to emphasize the searchword e.g. '<b>'
     * @param string $rep2
     *         The closing html-tag e.g. '</b>'
     */
    public function setReplacement($rep1, $rep2) {
        if (cString::getStringLength(trim($rep1)) > 0 && cString::getStringLength(trim($rep2)) > 0) {
            $this->_replacement[] = $rep1;
            $this->_replacement[] = $rep2;
        }
    }

    /**
     *
     * @todo refactor this because it shouldn't be the Search's job
     * @param int $artid
     * @return int
     *         Category Id
     */
    public function getArtCat($artid) {
        $sql = "SELECT idcat FROM " . $this->cfg['tab']['cat_art'] . "
                WHERE idart = " . cSecurity::toInteger($artid) . " ";
        $this->db->query($sql);
        if ($this->db->nextRecord()) {
            return $this->db->f('idcat');
        }
    }
}
