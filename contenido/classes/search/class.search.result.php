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
 * If $searchResult = $search->searchIndex($searchWords, $searchWordsToExclude);
 *
 * use object with
 *
 * $oSearchResults = new cSearchResult($searchResult, 10);
 *
 * // html-tags to emphasize the located search-words
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
     * Array of html-tags to emphasize the search-words
     *
     * @var array
     */
    protected $_replacement = [];

    /**
     * Array of article id's with ranking information
     *
     * @var array
     */
    protected $_rankStructure = [];

    /**
     * Array of result-pages with array's of article id's
     *
     * @var array
     */
    protected $_orderedSearchResult = [];

    /**
     * Array of article id's with information about cms-types, occurrence of
     * keyword/search-word, similarity .
     *
     * @var array
     */
    protected $_searchResult = [];

    /**
     * Constructor to create an instance of this class.
     *
     * Compute ranking factor for each search result and order the
     * search results by ranking factor.
     *
     * NOTE: The ranking factor is the sum of occurrences of matching
     * search-terms weighted by similarity (in %) between search-word
     * and matching word in the article.
     *
     * TODO: One can think of more sophisticated ranking strategies.
     * The content type information could be used for example because a
     * matching word in the headline (CMS_HEADLINE[1]) could be weighted
     * more than a matching word in the text (CMS_HTML[1]).
     *
     * @param array $searchResult
     *                      list of article ids
     * @param int   $resultPerPage
     *                      number of items per page
     * @param cDb   $oDB    [optional]
     *                      CONTENIDO database object
     * @param bool  $bDebug [optional]
     *                      flag to enable debugging
     *
     * @throws cInvalidArgumentException|cDbException
     */
    public function __construct(array $searchResult, $resultPerPage, $oDB = NULL, $bDebug = false) {
        parent::__construct($oDB, $bDebug);

        $this->_index = new cSearchIndex($oDB);

        $this->_searchResult = $searchResult;
        $this->_debug('$this->search_result', $this->_searchResult);

        $this->_resultPerPage = $resultPerPage;
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
     * @param array $rankedSearch
     * @param int $resultPerPage
     */
    public function setOrderedSearchResult(array $rankedSearch, $resultPerPage) {
        asort($rankedSearch);

        $sorted_rank = array_reverse($rankedSearch, true);

        if (isset($resultPerPage) && $resultPerPage > 0) {
            $split_result = array_chunk($sorted_rank, $resultPerPage, true);
            $this->_orderedSearchResult = $split_result;
        } else {
            $this->_orderedSearchResult[] = $sorted_rank;
        }
    }

    /**
     *
     * @param int    $artId
     *                   Id of an article
     * @param string $cmsType
     * @param int    $id [optional]
     *
     * @return string
     *                   Content of an article, specified by its content type
     *
     * @throws cDbException|cException
     */
    public function getContent($artId, $cmsType, $id = 0) {
        $article = new cApiArticleLanguage();
        $article->loadByArticleAndLanguageId($artId, $this->lang);
        return $article->getContent($cmsType, $id);
    }

    /**
     *
     * @param int    $artId
     *                       Id of an article
     * @param string $cmsType
     *                       Content type
     * @param int    $cmsNr [optional]
     *
     * @return array Content of an article in search result, specified by its type
     *         Content of an article in search result, specified by its type
     *
     * @throws cDbException|cException
     */
    public function getSearchContent($artId, $cmsType, $cmsNr = NULL) {
        $cmsType = cString::toUpperCase($cmsType);
        if (cString::getStringLength($cmsType) > 0) {
            if (!cString::findFirstOccurrenceCI($cmsType, 'cms_')) {
                if (in_array($cmsType, $this->_index->getCmsTypeSuffix())) {
                    $cmsType = 'CMS_' . $cmsType;
                }
            } else {
                if (!array_key_exists($cmsType, $this->_index->getCmsType())) {
                    return [];
                }
            }
        }

        $article = new cApiArticleLanguage();
        $article->loadByArticleAndLanguageId($artId, $this->lang);
        $content = [];
        if (isset($this->_searchResult[$artId][$cmsType])) {
            // If search-word occurs in cms_type
            $searchWords = $this->_searchResult[$artId]['search'];
            $searchWords = array_unique($searchWords);

            $idType = $this->_searchResult[$artId][$cmsType];
            $idType = array_unique($idType);

            if (isset($cmsNr) && is_numeric($cmsNr)) {
                // Get content of cms_type[cms_nr]
                // Sild consistent escaped string(Timo Trautmann) 2008-04-17
                $cmsContent = conHtmlentities(conHtmlEntityDecode(strip_tags($article->getContent($cmsType, $cmsNr))));
                $cmsContent = $this->highlightSearchWords($searchWords, $cmsContent);
                $content[] = htmlspecialchars_decode($cmsContent);
            } else {
                // get content of cms_type[$id], where $id are the cms_type
                // numbers found in search
                foreach ($idType as $id) {
                    $cmsContent = strip_tags($article->getContent($cmsType, $id));
                    $cmsContent = $this->highlightSearchWords($searchWords, $cmsContent);
                    $content[] = $cmsContent;
                }
            }
        } else {
            // search-word was not found in cms_type
            if (isset($cmsNr) && is_numeric($cmsNr)) {
                $content[] = strip_tags($article->getContent($cmsType, $cmsNr));
            } else {
                $artContent = $article->getContent($cmsType);
                if (count($artContent) > 0) {
                    foreach ($artContent as $val) {
                        $content[] = strip_tags($val);
                    }
                }
            }
        }
        return $content;
    }

    /**
     * @param array $searchWords
     * @param string $cmsContent
     * @return string
     */
    protected function highlightSearchWords(array $searchWords, $cmsContent) {
        if (count($this->_replacement) == 2) {
            foreach ($searchWords as $word) {
                // Build consistent escaped string, replace ae ue ..
                // with original html entities (Timo Trautmann) 2008-04-17
                $word_escaped = conHtmlentities(conHtmlEntityDecode($this->_index->addSpecialUmlauts($word)));
                $match = [];
                preg_match("/($word|$word_escaped)/i", $cmsContent, $match);

                if (isset($match[0])) {
                    $pattern = $match[0];
                    $replacement = $this->_replacement[0] . $pattern . $this->_replacement[1];
                    // Emphasize located search-words
                    $cmsContent = preg_replace("/$pattern/i", $replacement, $cmsContent);
                }
            }
        }

        return $cmsContent;
    }

    /**
     * Returns articles in page.
     *
     * @param int $pageId
     * @return array
     *         Articles in page $pageId
     */
    public function getSearchResultPage($pageId) {
        if (isset($this->_orderedSearchResult[$pageId - 1])) {
            $this->_resultPage = $pageId;
            return $this->_orderedSearchResult[$pageId - 1];
        } else {
            return [];
        }
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
     * @param int $artId
     *         Id of an article
     * @return int
     *         Similarity between search-word and matching word in article
     */
    public function getSimilarity($artId) {
        return $this->_searchResult[$artId]['similarity'] ?? 0.0001;
    }

    /**
     *
     * @param int $artId
     *         Id of an article
     * @return int
     *         number of matching search-words found in article
     */
    public function getOccurrence($artId) {
        $aOccurence = $this->_searchResult[$artId]['occurence'];
        $iSumOfOccurence = 0;
        for ($i = 0; $i < count($aOccurence); $i++) {
            $iSumOfOccurence += $aOccurence[$i];
        }

        return $iSumOfOccurence;
    }

    /**
     *
     * @param string $rep1
     *         The opening html-tag to emphasize the search-word e.g. '<b>'
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
     * Returns the first found category id by article id.
     *
     * @param int $artId
     *
     * @return int|void
     *         Category id or nothing
     * @throws cDbException|cInvalidArgumentException
     */
    public function getArtCat($artId) {
        $catArtColl = new cApiCategoryArticleCollection();
        $result = $catArtColl->getCategoryIdsByArticleId($artId);
        if (count($result) > 0) {
            return $result[0];
        }
    }

}
