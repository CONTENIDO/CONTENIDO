<?php

/**
 * This file contains the class for content search.
 *
 * @package    Core
 * @subpackage Frontend_Search
 * @author     Willi Man
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
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
 * <pre>
 * $options = [
 *      // use db function regexp
 *      'db' => 'regexp',
 *      // combine search-words with or
 *      'combine' => 'or'
 * ];
 * </pre>
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
 * <pre>
 * $options = [
 *      // use db function regexp
 *      'db' => 'regexp',
 *      // combine search-words with or
 *      'combine' => 'or',
 *      'searchable_articles' => array(5, 6, 9, 13)
 * ];
 * </pre>
 *
 * One can define the range of searchable articles by setting the
 * parameter 'exclude' to false which means the range of categories
 * defined by parameter 'cat_tree' or 'categories' and the range of
 * articles defined by parameter 'articles' is included.
 *
 * <pre>
 * $options = [
 *      // use db function regexp
 *      'db' => 'regexp',
 *      // combine search-words with or
 *      'combine' => 'or',
 *      // search-range specified in 'cat_tree', 'categories' and
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
 * ];
 * </pre>
 *
 * You can build the complement of the range of searchable articles by
 * setting the parameter 'exclude' to true which means the range of
 * categories defined by parameter 'cat_tree' or 'categories' and the
 * range of articles defined by parameter 'articles' is excluded from
 * search.
 *
 * <pre>
 * $options = [
 *      // use db function regexp
 *      'db' => 'regexp',
 *      // combine search-words with or
 *      'combine' => 'or',
 *      // search-range specified in 'cat_tree', 'categories' and
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
 * ];
 *
 * $search = new cSearch($options);
 *
 * // search only in these cms-types
 * $search->setCmsOptions([
 *      "htmlhead",
 *      "html",
 *      "head",
 *      "text",
 *      "imgdescr",
 *      "link",
 *      "linkdescr"
 * ]);
 *
 * // start search
 * $searchResult = $search->searchIndex($searchWord, $searchWordsExclude);
 * </pre>
 *
 * The search result structure has following form:
 *
 * <pre>
 * [
 *     [20] => [
 *         [CMS_HTML] => [
 *             [0] => 1,
 *             [1] => 1,
 *             [2] => 1,
 *         ],
 *         [keyword] => [
 *             [0] => 'content',
 *             [1] => 'contenido',
 *             [2] => 'wwwcontenidoorg',
 *         ],
 *         [search] => [
 *             [0] => 'con',
 *             [1] => 'con',
 *             [2] => 'con',
 *         ],
 *         [occurence] => [
 *             [0] => 1,
 *             [1] => 5,
 *             [2] => 1,
 *         ],
 *         [similarity] => 60,
 *     ],
 * ]
 * </pre>
 *
 * The keys of the array are the article ID's found by search.
 *
 * Searching 'con' matches keywords 'content', 'contenido' and
 * 'wwwcontenidoorg' in article with ID 20 in content type CMS_HTML[1].
 * The search term occurs 7 times.
 * The maximum similarity between searchterm and matching keyword is 60%.
 *
 * <pre>
 * // rank and display the results
 * $oSearchResults = new cSearchResult($searchResult, 10);
 * </pre>
 *
 * @package    Core
 * @subpackage Frontend_Search
 */
class cSearch extends cSearchBaseAbstract
{

    /**
     * Instance of class Index
     *
     * @var object
     */
    protected $_index;

    /**
     * Authentication instance
     *
     * @var cAuth
     */
    protected $_auth;

    /**
     * search words
     *
     * @var array
     */
    protected $_searchWords = [];

    /**
     * words which should be excluded from search
     *
     * @var array
     */
    protected $_searchWordsExclude = [];

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
     * logical combination of search-words (and, or)
     *
     * @var string
     */
    protected $_searchCombination;

    /**
     * array of searchable articles
     *
     * @var array
     */
    protected $_searchableArts = [];

    /**
     * article specifications
     *
     * @var array
     */
    protected $_articleSpecs = [];

    /**
     * If $protected = true => do not search articles which are offline
     * or articles in categories which are offline (protected) unless
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
     * Array of article id's with information about cms-types, occurrence
     * of keyword/search-word, similarity.
     *
     * @var array
     */
    protected $_searchResult = [];

    /**
     * Minimum similarity between search-word and keyword in percent.
     *
     * @var int
     */
    protected $intMinimumSimilarity;

    /**
     * Constructor to create an instance of this class.
     *
     * @param array $options
     *             <pre>
     *              $options['db']
     *                  'regexp' => DB search with REGEXP
     *                  'like' => DB search with LIKE
     *                  'exact' => exact match;
     *              $options['combine']
     *                  'and', 'or' Combination of search words with AND, OR
     *              $options['exclude']
     *                  'true' => search-range specified in 'cat_tree', 'categories'
     *                  and 'articles' is excluded;
     *                  'false' => search-range specified in 'cat_tree', 'categories'
     *                  and 'articles' is included
     *              $options['cat_tree']
     *                  e.g. array(8) => The complete tree with root 8 is in/excluded
     *                  from search
     *              $options['categories']
     *                  e.g. array(10, 12) => Categories 10, 12 in/excluded
     *              $options['articles']
     *                  e.g. array(23) => Article 33 in/excluded
     *              $options['artspecs']
     *                  e.g. array(2, 3) => search only articles with certain article
     *                  specifications
     *              $options['protected']
     *                  'true' => do not search articles which are offline (locked)
     *                  or articles in categories which are offline (protected)
     *              $options['dontshowofflinearticles']
     *                  'false' => search offline articles or articles in categories
     *                  which are offline
     *              $options['searchable_articles']
     *                  array of article ID's which should be searchable
     *              $options['minimum_similarity']
     *                  'int' => Minimum similarity between search-word and keyword in percent,
     *                           range can be between > 0 and <= 100, default is 50.
     *                           1 = Slightest similarity
     *                           100 = Exact match
     *             </pre>
     * @param cDb   $db [optional]
     *                  CONTENIDO database object
     * @param cAuth  $auth [optional]
     *                  Authentication object
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function __construct(array $options, $db = NULL, $auth = NULL)
    {
        parent::__construct($db);

        $this->_index = new cSearchIndex($db);
        $this->_auth = $auth instanceof cAuth ? $auth : cRegistry::getAuth();

        $this->_setOptions($options);
    }

    /**
     * indexed fulltext search
     *
     * @param string $searchWords
     *                                    The search words
     * @param string $searchWordsExclude [optional]
     *                                    The words, which should be excluded from search
     *
     * @return bool|array
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function searchIndex($searchWords, $searchWordsExclude = '')
    {
        if (cString::getStringLength(trim($searchWords)) > 0) {
            $this->_searchWords = $this->stripWords($searchWords);
        } else {
            return false;
        }

        if (cString::getStringLength(trim($searchWordsExclude)) > 0) {
            $this->_searchWordsExclude = $this->stripWords($searchWordsExclude);
        }

        $tmpSearchWords = $this->_prepareSearchWordsForQuery($this->_searchWords);
        if (count($this->_searchWordsExclude) > 0) {
            $tmpSearchWords = array_merge(
                $tmpSearchWords,
                $this->_prepareSearchWordsForQuery($this->_searchWordsExclude)
            );
        }

        // Build the '<field> <operator> <value>' condition for the query
        switch ($this->_searchOption) {
            case 'regexp':
                // regexp search
                $kwSql = "`keyword` REGEXP '" . implode('|', $tmpSearchWords) . "'";
                break;
            case 'like':
                // like search
                $search_like = implode(" OR keyword LIKE ", $tmpSearchWords);
                $kwSql = "`keyword` LIKE " . $search_like;
                break;
            case 'exact':
            default:
                // exact match
                $search_exact = implode(" OR keyword = ", $tmpSearchWords);
                $kwSql = "`keyword` LIKE " . $search_exact;
            break;
        }

        // Prepare sql without keywords, we don't want any strings in keywords sql
        // being interpreted as specifiers
        $sql = "SELECT `keyword`, `auto` FROM `%s` WHERE `idlang` = %d AND {KEYWORDS}";
        $sql =  $this->db->prepare($sql, cRegistry::getDbTableName('keywords'), $this->lang);
        $sql = str_replace('{KEYWORDS}', $kwSql, $sql);
        $this->_debug('sql', $sql);
        $this->db->query($sql);

        while ($this->db->nextRecord()) {
            $keyword = $this->db->f('keyword');
            $auto = $this->db->f('auto');

            $this->_debug('index', $auto);

            $tmpIndexString = preg_split('/&/', $auto, -1, PREG_SPLIT_NO_EMPTY);
            $tmpIndex = [];
            foreach ($tmpIndexString as $string) {
                $tmp_string = preg_replace('/[=\(\)]/', ' ', $string);
                $tmpIndex[] = preg_split('/\s/', $tmp_string, -1, PREG_SPLIT_NO_EMPTY);
            }
            $this->_debug('tmp_index', $tmpIndex);

            foreach ($tmpIndex as $string) {
                $artid = $string[0];

                // filter non-searchable articles
                if (in_array($artid, $this->_searchableArts)) {

                    $cms_place = $string[2];
                    $percent = 0;
                    $similarity = 0;
                    $searchWord = '';
                    foreach ($this->_searchWords as $word) {
                        // computes similarity between search-word and keyword in
                        // percent
                        similar_text($word, $keyword, $percent);
                        if ($percent > $similarity) {
                            $similarity = $percent;
                            $searchWord = $word;
                        }
                    }

                    $tmpCmsType = preg_split('/[,]/', $cms_place, -1, PREG_SPLIT_NO_EMPTY);
                    $this->_debug('tmpCmsType', $tmpCmsType);

                    $tmpCmsType2 = [];
                    foreach ($tmpCmsType as $type) {
                        $tmpCmsType2[] = preg_split('/-/', $type, -1, PREG_SPLIT_NO_EMPTY);
                    }
                    $this->_debug('tmpCmsType2', $tmpCmsType2);

                    foreach ($tmpCmsType2 as $type) {
                        if (!$this->_index->checkCmsType($type[0])) {
                            // search for specified cms-types
                            if ($similarity >= $this->intMinimumSimilarity) {
                                // include article into search-result set only if
                                // similarity between search-word and keyword is
                                // big enough
                                $this->_searchResult[$artid][$type[0]][] = $type[1];
                                $this->_searchResult[$artid]['keyword'][] = $keyword;
                                $this->_searchResult[$artid]['search'][] = $searchWord;
                                $this->_searchResult[$artid]['occurence'][] = $string[1];
                                $this->_searchResult[$artid]['debug_similarity'][] = $percent;
                                if ($similarity > ($this->_searchResult[$artid]['similarity'] ?? 0)) {
                                    $this->_searchResult[$artid]['similarity'] = $similarity;
                                }
                            }
                        }
                    }
                } else {
                    $this->_debug('Article for search-word is not in array of searchable articles', [
                        'search-word' => $keyword,
                        'idart' => $artid,
                    ]);
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
        $searchTracking->trackSearch($searchWords, count($this->_searchResult));

        return $this->_searchResult;
    }

    /**
     *
     * @param mixed $cms_options
     *         The cms-types (htmlhead, html, ...) which should explicitly be
     *         searched.
     */
    public function setCmsOptions($cms_options)
    {
        if (is_array($cms_options) && count($cms_options) > 0) {
            $this->_index->setCmsOptions($cms_options);
        }
    }

    /**
     *
     * @param string $searchWords
     *         The search-words
     * @return array
     *         of stripped search-words
     */
    public function stripWords($searchWords)
    {
        // remove backslash and html tags
        $searchWords = trim(strip_tags(stripslashes($searchWords)));

        // split the phrase by any number of commas or space characters
        $tmp_words = mb_split('[\s,]+', $searchWords);

        $tmpSearchWords = [];
        foreach ($tmp_words as $word) {
            $word = htmlentities($word, ENT_COMPAT, 'UTF-8');
            $word = (trim(cString::toLowerCase($word)));
            $word = html_entity_decode($word, ENT_COMPAT, 'UTF-8');

            // $word =(trim(cString::toLowerCase($word)));
            if (cString::getStringLength($word) > 1) {
                $tmpSearchWords[] = $word;
            }
        }

        return array_unique($tmpSearchWords);
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
    public function getSubTree($cat_start)
    {
        $sql = "SELECT
                B.idcat, B.parentid
            FROM
                " . cRegistry::getDbTableName('cat_tree') . " AS A,
                " . cRegistry::getDbTableName('cat') . " AS B,
                " . cRegistry::getDbTableName('cat_lang') . " AS C
            WHERE
                A.idcat  = B.idcat AND
                B.idcat  = C.idcat AND
                C.idlang = " . $this->lang . " AND
                B.idclient = " . $this->client . "
            ORDER BY
                idtree";
        $this->_debug('sql', $sql);
        $this->db->query($sql);

        // $aSubCats = [];
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

        $aSubCats = [$cat_start];
        while ($this->db->nextRecord()) {
            // omit if cat is no child of any recognized descendant
            if (!in_array($this->db->f('parentid'), $aSubCats)) {
                continue;
            }
            // omit if cat is already recognized (happens with $cat_start)
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
    public function getSearchableArticles($search_range)
    {
        global $auth;

        $aCatRange = [];
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

        if ($this->_protected) {
            // access will be checked later
            $sProtected = " C.visible = 1 AND B.online = 1 ";
        } else {
            if ($this->_dontshowofflinearticles) {
                $sProtected = " C.visible = 1 AND B.online = 1 ";
            } else {
                $sProtected = " 1 ";
            }
        }

        if ($this->_exclude) {
            // exclude search-range
            $sSearchRange = " A.idcat NOT IN ('" . $sCatRange . "') AND B.idart NOT IN ('" . $sArtRange . "') AND ";
        } else {
            // include search-range
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
                    " . cRegistry::getDbTableName('cat_art') . " as A,
                    " . cRegistry::getDbTableName('art_lang') . " as B,
                    " . cRegistry::getDbTableName('cat_lang') . " as C
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

        $aIdArts = [];
        while ($this->db->nextRecord()) {
            $idcat = cSecurity::toInteger($this->db->f('idcat'));
            if ($idcat > 0 && $this->_protected) {
                if ($this->db->f('public') == '0') {
                    // CEC to check category access
                    // break at 'true', default value 'false'
                    cApiCecHook::setBreakCondition(true, false);
                    $allow = cApiCecHook::executeWhileBreakCondition(
                        'Contenido.Frontend.CategoryAccess', $this->lang, $idcat, $this->_auth->auth['uid']
                    );
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
    public function getArticleSpecifications()
    {
        $sql = "SELECT `idartspec` FROM `%d` WHERE `client` = %d AND `lang` = %d AND `online` = 1";
        $sql = $this->db->prepare($sql, cRegistry::getDbTableName('art_spec'), $this->client, $this->lang);
        $this->_debug('sql', $sql);
        $this->db->query($sql);

        $aArtspec = [];
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
    public function setArticleSpecification($iArtspecID)
    {
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
    public function addArticleSpecificationsByName($sArtSpecName)
    {
        if (!isset($sArtSpecName) || cString::getStringLength($sArtSpecName) == 0) {
            return false;
        }

        $sql = "SELECT `idartspec` FROM `%d` WHERE `client` = %d AND `artspec` = '%s'";
        $sql = $this->db->prepare($sql, cRegistry::getDbTableName('art_spec'), $this->client, $sArtSpecName);
        $this->_debug('sql', $sql);
        $this->db->query($sql);
        while ($this->db->nextRecord()) {
            $this->_articleSpecs[] = $this->db->f('idartspec');
        }

        return true;
    }

    /**
     * Sets and validates the options, passed to the constructor.
     *
     * @param array $options
     * @return void
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    protected function _setOptions(array $options)
    {
        $this->_searchOption = cString::toLowerCase($options['db'] ?? '');
        if (!in_array($this->_searchOption, ['regexp', 'like', 'exact'])) {
            $this->_searchOption = 'regexp';
        }

        $this->_searchCombination = cString::toLowerCase($options['combine'] ?? '');
        if (!in_array($this->_searchCombination, ['and', 'or'])) {
            $this->_searchCombination = 'or';
        }

        $this->_protected = cSecurity::toBoolean($options['combine'] ?? '1');
        $this->_dontshowofflinearticles = cSecurity::toBoolean($options['dontshowofflinearticles'] ?? '1');
        $this->_exclude = cSecurity::toBoolean($options['exclude'] ?? '1');

        $this->_articleSpecs = $options['artspecs'] ?? '';
        if (!is_array($this->_articleSpecs)) {
            $this->_articleSpecs = [];
        }

        $this->_searchableArts = $options['searchable_articles'] ?? '';
        if (!is_array($this->_searchableArts)) {
            $this->_searchableArts = $this->getSearchableArticles($options);
        }

        $this->intMinimumSimilarity = cSecurity::toInteger($options['minimum_similarity'] ?? '50');
        if ($this->intMinimumSimilarity < 1 || $this->intMinimumSimilarity > 100) {
            $this->intMinimumSimilarity = 50;
        }
    }

    /**
     * Prepares the passed list of search-terms for the usage in SQL operators.
     *
     * @param array $searchWords
     * @return array
     */
    protected function _prepareSearchWordsForQuery(array $searchWords): array
    {
        $tmpSearchWords = [];
        foreach ($searchWords as $word) {
            $wordEscaped = cSecurity::escapeDB($word, $this->db);
            if ($this->_searchOption === 'like') {
                // Escape the percent sign for the LIKE operator value
                $wordEscaped = str_replace('%', '\\%', $wordEscaped);
                $wordEscaped = "'%" . $wordEscaped . "%'";
            } elseif ($this->_searchOption === 'regexp') {
                // Escape special regex characters for the REGEXP operator value, the '&' sign too.
                $wordEscaped = preg_quote($wordEscaped, '&');
                // Escape all single backslashes against double ones
                $wordEscaped = preg_replace('/\\\\/', '\\\\\\\\', $wordEscaped);
            } elseif ($this->_searchOption === 'exact') {
                // Exact search also works with LIKE, escape the percent sign for the LIKE operator value
                $wordEscaped = str_replace('%', '\\%', $wordEscaped);
                $wordEscaped = "'" . $wordEscaped . "'";
            }
            $tmpSearchWords[] = $wordEscaped;
        }
        return $tmpSearchWords;
    }

}
