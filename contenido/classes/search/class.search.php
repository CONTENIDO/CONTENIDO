<?php

/**
 * This file contains the class for content search.
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
 * CONTENIDO API - Search Object
 *
 * This object starts a indexed fulltext search.
 *
 * TODO:
 * - The way to set the search options could be done much more better!
 * - The computation of the set of searchable articles should not be
 * treated in this class.
 * - It is better to compute the array of searchable articles from the
 * outside and to pass the array of searchable articles as parameter.
 * - Avoid foreach loops.
 *
 * Use object with
 *
 * $options = array(
 *      // use db function regexp
 *      'db' => 'regexp',
 *      // combine searchwords with or
 *      'combine' => 'or'
 * );
 *
 * The range of searchable articles is by default the complete content
 * which is online and not protected.
 *
 * With option 'searchable_articles' you can define your own set of
 * searchable articles.
 *
 * If parameter 'searchable_articles' is set, the options 'cat_tree',
 * 'categories', 'articles', 'exclude', 'artspecs', 'protected' and
 * 'dontshowofflinearticles' won't have any effect.
 *
 * $options = array(
 *      // use db function regexp
 *      'db' => 'regexp',
 *      // combine searchwords with or
 *      'combine' => 'or',
 *      'searchable_articles' => array(5, 6, 9, 13)
 * );
 *
 * One can define the range of searchable articles by setting the
 * parameter 'exclude' to false which means the range of categories
 * defined by parameter 'cat_tree' or 'categories' and the range of
 * articles defined by parameter 'articles' is included.
 *
 * $options = array(
 *      // use db function regexp
 *      'db' => 'regexp',
 *      // combine searchwords with or
 *      'combine' => 'or',
 *      // searchrange specified in 'cat_tree', 'categories' and
 *      // 'articles' is included
 *      'exclude' => false,
 *      // tree with root 12 included
 *      'cat_tree' => array(12),
 *      // categories 100, 111 included
 *      'categories' => array(100,111),
 *      // article 33 included
 *      'articles' => array(33),
 *      // array of article specifications => search only articles with
 *      // these artspecs
 *      'artspecs' => array(2, 3),
 *      // results per page
 *      'res_per_page' => 2,
 *      // do not search articles or articles in categories which are
 *      // offline or protected
 *      'protected' => true,
 *      // search offline articles or articles in categories which are
 *      // offline
 *      'dontshowofflinearticles' => false
 * );
 *
 * You can build the complement of the range of searchable articles by
 * setting the parameter 'exclude' to true which means the range of
 * categories defined by parameter 'cat_tree' or 'categories' and the
 * range of articles defined by parameter 'articles' is excluded from
 * search.
 *
 * $options = array(
 *      // use db function regexp
 *      'db' => 'regexp',
 *      // combine searchwords with or
 *      'combine' => 'or',
 *      // searchrange specified in 'cat_tree', 'categories' and
 *      // 'articles' is excluded
 *      'exclude' => true,
 *      // tree with root 12 excluded
 *      'cat_tree' => array(12),
 *      // categories 100, 111 excluded
 *      'categories' => array(100,111),
 *      // article 33 excluded
 *      'articles' => array(33),
 *      // array of article specifications => search only articles with
 *      // these artspecs
 *      'artspecs' => array(2, 3),
 *      // results per page
 *      'res_per_page' => 2,
 *      // do not search articles or articles in categories which are
 *      // offline or protected
 *      'protected' => true,
 *      // search offline articles or articles in categories which are
 *      // offline
 *      'dontshowofflinearticles' => false
 * );
 *
 * $search = new Search($options);
 *
 * // search only in these cms-types
 * $search->setCmsOptions(array(
 *      "htmlhead",
 *      "html",
 *      "head",
 *      "text",
 *      "imgdescr",
 *      "link",
 *      "linkdescr"
 * ));
 *
 * // start search
 * $search_result = $search->searchIndex($searchword, $searchwordex);
 *
 * The search result structure has following form
 * Array (
 * [20] => Array (
 * [CMS_HTML] => Array (
 * [0] => 1
 * [1] => 1
 * [2] => 1
 * )
 * [keyword] => Array (
 * [0] => content
 * [1] => contenido
 * [2] => wwwcontenidoorg
 * )
 * [search] => Array (
 * [0] => con
 * [1] => con
 * [2] => con
 * )
 * [occurence] => Array (
 * [0] => 1
 * [1] => 5
 * [2] => 1
 * )
 * [similarity] => 60
 * )
 * )
 *
 * The keys of the array are the article ID's found by search.
 *
 * Searching 'con' matches keywords 'content', 'contenido' and
 * 'wwwcontenidoorg' in article with ID 20 in content type CMS_HTML[1].
 * The search term occurs 7 times.
 * The maximum similarity between searchterm and matching keyword is 60%.
 *
 * // rank and display the results
 * $oSearchResults = new cSearchResult($search_result, 10);
 *
 * @package Core
 * @subpackage Frontend_Search
 */
class cSearch extends cSearchBaseAbstract {

    /**
     * Instance of class Index
     *
     * @var object
     */
    protected $_index;

    /**
     * search words
     *
     * @var array
     */
    protected $_searchWords = array();

    /**
     * words which should be excluded from search
     *
     * @var array
     */
    protected $_searchWordsExclude = array();

    /**
     * type of db search
     *
     * like => 'sql like'
     * regexp => 'sql regexp'
     *
     * @var string
     */
    protected $_searchOption;

    /**
     * logical combination of searchwords (and, or)
     *
     * @var string
     */
    protected $_searchCombination;

    /**
     * array of searchable articles
     *
     * @var array
     */
    protected $_searchableArts = array();

    /**
     * article specifications
     *
     * @var array
     */
    protected $_articleSpecs = array();

    /**
     * If $protected = true => do not search articles which are offline
     * or articles in catgeories which are offline (protected) unless
     * the user has access to them.
     *
     * @var bool
     */
    protected $_protected;

    /**
     * If $dontshowofflinearticles = false => search offline articles or
     * articles in categories which are offline.
     *
     * @var bool
     */
    protected $_dontshowofflinearticles;

    /**
     * If $exclude = true => the specified search range is excluded from
     * search, otherwise included.
     *
     * @var bool
     */
    protected $_exclude;

    /**
     * Array of article id's with information about cms-types, occurence
     * of keyword/searchword, similarity.
     *
     * @var array
     */
    protected $_searchResult = array();

    /**
     * Constructor to create an instance of this class.
     *
     * @param array $options
     *                  $options['db']
     *                  'regexp' => DB search with REGEXP
     *                  'like' => DB search with LIKE
     *                  'exact' => exact match;
     *                  $options['combine']
     *                  'and', 'or' Combination of search words with AND, OR
     *                  $options['exclude']
     *                  'true' => searchrange specified in 'cat_tree', 'categories'
     *                  and 'articles' is excluded;
     *                  'false' => searchrange specified in 'cat_tree', 'categories'
     *                  and 'articles' is included
     *                  $options['cat_tree']
     *                  e.g. array(8) => The complete tree with root 8 is in/excluded
     *                  from search
     *                  $options['categories']
     *                  e.g. array(10, 12) => Categories 10, 12 in/excluded
     *                  $options['articles']
     *                  e.g. array(23) => Article 33 in/excluded
     *                  $options['artspecs']
     *                  e.g. array(2, 3) => search only articles with certain article
     *                  specifications
     *                  $options['protected']
     *                  'true' => do not search articles which are offline (locked)
     *                  or articles in catgeories which are offline (protected)
     *                  $options['dontshowofflinearticles']
     *                  'false' => search offline articles or articles in categories
     *                  which are offline
     *                  $options['searchable_articles']
     *                  array of article ID's which should be searchable
     * @param cDb   $db [optional]
     *                  CONTENIDO database object
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function __construct($options, $db = NULL) {
        parent::__construct($db);

        $this->_index = new cSearchIndex($db);

        $this->_searchOption = (array_key_exists('db', $options)) ? cString::toLowerCase($options['db']) : 'regexp';
        $this->_searchCombination = (array_key_exists('combine', $options)) ? cString::toLowerCase($options['combine']) : 'or';
        $this->_protected = (array_key_exists('protected', $options)) ? $options['protected'] : true;
        $this->_dontshowofflinearticles = (array_key_exists('dontshowofflinearticles', $options)) ? $options['dontshowofflinearticles'] : true;
        $this->_exclude = (array_key_exists('exclude', $options)) ? $options['exclude'] : true;
        $this->_articleSpecs = (array_key_exists('artspecs', $options) && is_array($options['artspecs'])) ? $options['artspecs'] : array();

        if (array_key_exists('searchable_articles', $options) && is_array($options['searchable_articles'])) {
            $this->_searchableArts = $options['searchable_articles'];
        } else {
            $this->_searchableArts = $this->getSearchableArticles($options);
        }

        // minimum similarity between searchword and keyword in percent
        $this->intMinimumSimilarity = 50;
    }

    /**
     * indexed fulltext search
     *
     * @param string $searchwords
     *                                    The search words
     * @param string $searchwords_exclude [optional]
     *                                    The words, which should be excluded from search
     *
     * @return bool|array
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function searchIndex($searchwords, $searchwords_exclude = '') {
        if (cString::getStringLength(trim($searchwords)) > 0) {
            $this->_searchWords = $this->stripWords($searchwords);
        } else {
            return false;
        }

        if (cString::getStringLength(trim($searchwords_exclude)) > 0) {
            $this->_searchWordsExclude = $this->stripWords($searchwords_exclude);
        }

        $tmp_searchwords = array();
        foreach ($this->_searchWords as $word) {
            $wordEscaped = cSecurity::escapeDB($word, $this->db);
            if ($this->_searchOption == 'like') {
                $wordEscaped = "'%" . $wordEscaped . "%'";
            } elseif ($this->_searchOption == 'exact') {
                $wordEscaped = "'" . $wordEscaped . "'";
            }
            $tmp_searchwords[] = $wordEscaped;
        }

        if (count($this->_searchWordsExclude) > 0) {
            foreach ($this->_searchWordsExclude as $word) {
                $wordEscaped = cSecurity::escapeDB($word, $this->db);
                if ($this->_searchOption == 'like') {
                    $wordEscaped = "'%" . $wordEscaped . "%'";
                } elseif ($this->_searchOption == 'exact') {
                    $wordEscaped = "'" . $wordEscaped . "'";
                }
                $tmp_searchwords[] = $wordEscaped;
                $this->_searchWords[] = $word;
            }
        }

        if ($this->_searchOption == 'regexp') {
            // regexp search
            $kwSql = "keyword REGEXP '" . implode('|', $tmp_searchwords) . "'";
        } elseif ($this->_searchOption == 'like') {
            // like search
            $search_like = implode(" OR keyword LIKE ", $tmp_searchwords);
            $kwSql = "keyword LIKE " . $search_like;
        } elseif ($this->_searchOption == 'exact') {
            // exact match
            $search_exact = implode(" OR keyword = ", $tmp_searchwords);
            $kwSql = "keyword LIKE " . $search_exact;
        }

        $sql = "SELECT keyword, auto FROM " . $this->cfg['tab']['keywords'] . " WHERE idlang=" . cSecurity::toInteger($this->lang) . " AND " . $kwSql . " ";
        $this->_debug('sql', $sql);
        $this->db->query($sql);

        while ($this->db->nextRecord()) {

            $tmp_index_string = preg_split('/&/', $this->db->f('auto'), -1, PREG_SPLIT_NO_EMPTY);

            $this->_debug('index', $this->db->f('auto'));

            $tmp_index = array();
            foreach ($tmp_index_string as $string) {
                $tmp_string = preg_replace('/[=\(\)]/', ' ', $string);
                $tmp_index[] = preg_split('/\s/', $tmp_string, -1, PREG_SPLIT_NO_EMPTY);
            }
            $this->_debug('tmp_index', $tmp_index);

            foreach ($tmp_index as $string) {
                $artid = $string[0];

                // filter nonsearchable articles
                if (in_array($artid, $this->_searchableArts)) {

                    $cms_place = $string[2];
                    $keyword = $this->db->f('keyword');
                    $percent = 0;
                    $similarity = 0;
                    foreach ($this->_searchWords as $word) {
                        // computes similarity between searchword and keyword in
                        // percent
                        similar_text($word, $keyword, $percent);
                        if ($percent > $similarity) {
                            $similarity = $percent;
                            $searchword = $word;
                        }
                    }

                    $tmp_cmstype = preg_split('/[,]/', $cms_place, -1, PREG_SPLIT_NO_EMPTY);
                    $this->_debug('tmp_cmstype', $tmp_cmstype);

                    $tmp_cmstype2 = array();
                    foreach ($tmp_cmstype as $type) {
                        $tmp_cmstype2[] = preg_split('/-/', $type, -1, PREG_SPLIT_NO_EMPTY);
                    }
                    $this->_debug('tmp_cmstype2', $tmp_cmstype2);

                    foreach ($tmp_cmstype2 as $type) {
                        if (!$this->_index->checkCmsType($type[0])) {
                            // search for specified cms-types
                            if ($similarity >= $this->intMinimumSimilarity) {
                                // include article into searchresult set only if
                                // similarity between searchword and keyword is
                                // big enough
                                $this->_searchResult[$artid][$type[0]][] = $type[1];
                                $this->_searchResult[$artid]['keyword'][] = $this->db->f('keyword');
                                $this->_searchResult[$artid]['search'][] = $searchword;
                                $this->_searchResult[$artid]['occurence'][] = $string[1];
                                $this->_searchResult[$artid]['debug_similarity'][] = $percent;
                                if ($similarity > ($this->_searchResult[$artid]['similarity'] ?? 0)) {
                                    $this->_searchResult[$artid]['similarity'] = $similarity;
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($this->_searchCombination == 'and') {
            // all search words must appear in the article
            foreach ($this->_searchResult as $article => $val) {
                if (!count(array_diff($this->_searchWords, $val['search'])) == 0) {
                    // $this->rank_structure[$article] = $rank[$article];
                    unset($this->_searchResult[$article]);
                }
            }
        }

        if (count($this->_searchWordsExclude) > 0) {
            // search words to be excluded must not appear in article
            foreach ($this->_searchResult as $article => $val) {
                if (!count(array_intersect($this->_searchWordsExclude, $val['search'])) == 0) {
                    // $this->rank_structure[$article] = $rank[$article];
                    unset($this->_searchResult[$article]);
                }
            }
        }

        $this->_debug('$this->search_result', $this->_searchResult);
        $this->_debug('$this->searchable_arts', $this->_searchableArts);

        $searchTracking = new cApiSearchTrackingCollection();
        $searchTracking->trackSearch($searchwords, count($this->_searchResult));

        return $this->_searchResult;
    }

    /**
     *
     * @param mixed $cms_options
     *         The cms-types (htmlhead, html, ...) which should explicitly be
     *         searched.
     */
    public function setCmsOptions($cms_options) {
        if (is_array($cms_options) && count($cms_options) > 0) {
            $this->_index->setCmsOptions($cms_options);
        }
    }

    /**
     *
     * @param string $searchwords
     *         The search-words
     * @return array
     *         of stripped search-words
     */
    public function stripWords($searchwords) {
        // remove backslash and html tags
        $searchwords = trim(strip_tags(stripslashes($searchwords)));

        // split the phrase by any number of commas or space characters
        $tmp_words = mb_split('[\s,]+', $searchwords);

        $tmp_searchwords = array();

        foreach ($tmp_words as $word) {

            $word = htmlentities($word, ENT_COMPAT, 'UTF-8');
            $word = (trim(cString::toLowerCase($word)));
            $word = html_entity_decode($word, ENT_COMPAT, 'UTF-8');

            // $word =(trim(cString::toLowerCase($word)));
            if (cString::getStringLength($word) > 1) {
                $tmp_searchwords[] = $word;
            }
        }

        return array_unique($tmp_searchwords);
    }

    /**
     * Returns the category tree array.
     *
     * @todo This is not the job for search, should be outsourced ...
     * @param int $cat_start
     *         Root of a category tree
     * @return array
     *         Category Tree
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function getSubTree($cat_start) {
        $sql = "SELECT
                B.idcat, B.parentid
            FROM
                " . $this->cfg['tab']['cat_tree'] . " AS A,
                " . $this->cfg['tab']['cat'] . " AS B,
                " . $this->cfg['tab']['cat_lang'] . " AS C
            WHERE
                A.idcat  = B.idcat AND
                B.idcat  = C.idcat AND
                C.idlang = '" . cSecurity::toInteger($this->lang) . "' AND
                B.idclient = '" . cSecurity::toInteger($this->client) . "'
            ORDER BY
                idtree";
        $this->_debug('sql', $sql);
        $this->db->query($sql);

        // $aSubCats = array();
        // $i = false;
        // while ($this->db->nextRecord()) {
        // if ($this->db->f('parentid') < $cat_start) {
        // // ending part of tree
        // $i = false;
        // }
        // if ($this->db->f('idcat') == $cat_start) {
        // // starting part of tree
        // $i = true;
        // }
        // if ($i == true) {
        // $aSubCats[] = $this->db->f('idcat');
        // }
        // }

        $aSubCats = array(
            $cat_start
        );
        while ($this->db->nextRecord()) {
            // ommit if cat is no child of any recognized descendant
            if (!in_array($this->db->f('parentid'), $aSubCats)) {
                continue;
            }
            // ommit if cat is already recognized (happens with $cat_start)
            if (in_array($this->db->f('idcat'), $aSubCats)) {
                continue;
            }
            // add cat as recognized descendant
            $aSubCats[] = $this->db->f('idcat');
        }

        return $aSubCats;
    }

    /**
     * Returns list of searchable article ids in given search range.
     *
     * @param array $search_range
     * @return array
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function getSearchableArticles($search_range) {
        global $auth;

        $aCatRange = array();
        if (array_key_exists('cat_tree', $search_range) && is_array($search_range['cat_tree'])) {
            if (count($search_range['cat_tree']) > 0) {
                foreach ($search_range['cat_tree'] as $cat) {
                    $aCatRange = array_merge($aCatRange, $this->getSubTree($cat));
                }
            }
        }

        if (array_key_exists('categories', $search_range) && is_array($search_range['categories'])) {
            if (count($search_range['categories']) > 0) {
                $aCatRange = array_merge($aCatRange, $search_range['categories']);
            }
        }

        $aCatRange = array_unique($aCatRange);
        $sCatRange = implode("','", $aCatRange);
        $sArtRange = '';

        if (array_key_exists('articles', $search_range) && is_array($search_range['articles'])) {
            if (count($search_range['articles']) > 0) {
                $sArtRange = implode("','", $search_range['articles']);
            }
        }

        if ($this->_protected == true) {
            // access will be checked later
            $sProtected = " C.visible = 1 AND B.online = 1 ";
        } else {
            if ($this->_dontshowofflinearticles == true) {
                $sProtected = " C.visible = 1 AND B.online = 1 ";
            } else {
                $sProtected = " 1 ";
            }
        }

        if ($this->_exclude == true) {
            // exclude searchrange
            $sSearchRange = " A.idcat NOT IN ('" . $sCatRange . "') AND B.idart NOT IN ('" . $sArtRange . "') AND ";
        } else {
            // include searchrange
            if (cString::getStringLength($sArtRange) > 0) {
                $sSearchRange = " A.idcat IN ('" . $sCatRange . "') AND B.idart IN ('" . $sArtRange . "') AND ";
            } else {
                $sSearchRange = " A.idcat IN ('" . $sCatRange . "') AND ";
            }
        }

        if (count($this->_articleSpecs) > 0) {
            $sArtSpecs = " B.artspec IN ('" . implode("','", $this->_articleSpecs) . "') AND ";
        } else {
            $sArtSpecs = '';
        }

        $sql = "SELECT
                    A.idart,
                    A.idcat,
                    C.public
                FROM
                    " . $this->cfg["tab"]["cat_art"] . " as A,
                    " . $this->cfg["tab"]["art_lang"] . " as B,
                    " . $this->cfg["tab"]["cat_lang"] . " as C
                WHERE
                    " . $sSearchRange . "
                    B.idlang = '" . cSecurity::toInteger($this->lang) . "' AND
                    C.idlang = '" . cSecurity::toInteger($this->lang) . "' AND
                    A.idart = B.idart AND
                    B.searchable = 1  AND
                    A.idcat = C.idcat AND
                    " . $sArtSpecs . "
                    " . $sProtected . " ";
        $this->_debug('sql', $sql);
        $this->db->query($sql);

        $aIdArts = array();
        while ($this->db->nextRecord()) {
            if($this->db->f("idcat") != "" && $this->_protected) {
                if($this->db->f("public") == "0") {
                    // CEC to check category access
                    // break at 'true', default value 'false'
                    cApiCecHook::setBreakCondition(true, false);
                    $allow = cApiCecHook::executeWhileBreakCondition('Contenido.Frontend.CategoryAccess', $this->lang, $this->db->f("idcat"), $auth->auth['uid']);
                    if (!$allow) {
                        continue;
                    }
                }
            }

            $aIdArts[] = $this->db->f('idart');
        }
        return $aIdArts;
    }

    /**
     * Fetch all article specifications which are online.
     *
     * @return array
     *         Array of article specification Ids
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function getArticleSpecifications() {
        $sql = "SELECT
                    idartspec
                FROM
                    " . $this->cfg['tab']['art_spec'] . "
                WHERE
                    client = " . cSecurity::toInteger($this->client) . " AND
                    lang = " . cSecurity::toInteger($this->lang) . " AND
                    online = 1 ";
        $this->_debug('sql', $sql);
        $this->db->query($sql);
        $aArtspec = array();
        while ($this->db->nextRecord()) {
            $aArtspec[] = $this->db->f('idartspec');
        }
        return $aArtspec;
    }

    /**
     * Set article specification.
     *
     * @param int $iArtspecID
     */
    public function setArticleSpecification($iArtspecID) {
        $this->_articleSpecs[] = $iArtspecID;
    }

    /**
     * Add all article specifications matching name of article
     * specification (client dependent but language independent).
     *
     * @param string $sArtSpecName
     * @return bool
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function addArticleSpecificationsByName($sArtSpecName) {
        if (!isset($sArtSpecName) || cString::getStringLength($sArtSpecName) == 0) {
            return false;
        }

        $sql = "SELECT
                    idartspec
                FROM
                    " . $this->cfg['tab']['art_spec'] . "
                WHERE
                    client = " . cSecurity::toInteger($this->client) . " AND
                    artspec = '" . $this->db->escape($sArtSpecName) . "' ";
        $this->_debug('sql', $sql);
        $this->db->query($sql);
        while ($this->db->nextRecord()) {
            $this->_articleSpecs[] = $this->db->f('idartspec');
        }
    }
}
