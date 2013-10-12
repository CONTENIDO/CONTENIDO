<?php
/**
 * This file contains various classes for content search.
 * API to index a CONTENIDO article
 * API to search in the index structure
 * API to display the searchresults
 *
 * @package Core
 * @subpackage Frontend_Search
 * @version SVN Revision $Rev:$
 *
 * @author Willi Man
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.encoding.php');

/**
 * Abstract base search class.
 * Provides general properties and functions
 * for child implementations.
 *
 * @author Murat Purc <murat@purc.de>
 *
 * @package Core
 * @subpackage Frontend_Search
 */
abstract class cSearchBaseAbstract {

    /**
     * CONTENIDO database object
     *
     * @var cDb
     */
    protected $oDB;

    /**
     * CONTENIDO configuration data
     *
     * @var array
     */
    protected $cfg;

    /**
     * Language id of a client
     *
     * @var int
     */
    protected $lang;

    /**
     * Client id
     *
     * @var int
     */
    protected $client;

    /**
     * Initialises some properties
     *
     * @param cDb $oDB Optional database instance
     * @param bool $bDebug Optional, flag to enable debugging (no longer needed)
     */
    protected function __construct($oDB = NULL, $bDebug = false) {
        global $cfg, $lang, $client;

        $this->cfg = $cfg;
        $this->lang = $lang;
        $this->client = $client;

        $this->bDebug = $bDebug;

        if ($oDB == NULL || !is_object($oDB)) {
            $this->db = cRegistry::getDb();
        } else {
            $this->db = $oDB;
        }
    }

    /**
     * Main debug function, prints dumps parameter if debugging is enabled
     *
     * @param string $msg Some text
     * @param mixed $var The variable to dump
     */
    protected function _debug($msg, $var) {
        $dump = $msg . ': ';
        if (is_array($var) || is_object($var)) {
            $dump .= print_r($var, true);
        } else {
            $dump .= $var;
        }
        cDebug::out($dump);
    }
}

/**
 * CONTENIDO API - Search Index Object
 *
 * This object creates an index of an article
 *
 * Create object with
 * $oIndex = new SearchIndex($db); # where $db is the global CONTENIDO database
 * object.
 * Start indexing with
 * $oIndex->start($idart, $aContent);
 * where $aContent is the complete content of an article specified by its
 * content types.
 * It looks like
 * Array (
 * [CMS_HTMLHEAD] => Array (
 * [1] => Herzlich Willkommen...
 * [2] => ...auf Ihrer Website!
 * )
 * [CMS_HTML] => Array (
 * [1] => Die Inhalte auf dieser Website ...
 *
 * The index for keyword 'willkommen' would look like '&12=1(CMS_HTMLHEAD-1)'
 * which means the keyword 'willkommen' occurs 1 times in article with articleId
 * 12 and content type CMS_HTMLHEAD[1].
 *
 * TODO: The basic idea of the indexing process is to take the complete content
 * of an article and to generate normalized index terms
 * from the content and to store a specific index structure in the relation
 * 'con_keywords'.
 * To take the complete content is not very flexible. It would be better to
 * differentiate by specific content types or by any content.
 * The &, =, () and - seperated string is not easy to parse to compute the
 * search result set.
 * It would be a better idea (and a lot of work) to extend the relation
 * 'con_keywords' to store keywords by articleId (or content source identifier)
 * and content type.
 * The functions removeSpecialChars, setStopwords, setContentTypes and
 * setCmsOptions should be sourced out into a new helper-class.
 * Keep in mind that class Search and SearchResult uses an instance of object
 * Index.
 *
 * @package Core
 * @subpackage Frontend_Search
 */
class cSearchIndex extends cSearchBaseAbstract {

    /**
     * the content of the cms-types of an article
     *
     * @var array
     */
    protected $_keycode = array();

    /**
     * the list of keywords of an article
     *
     * @var array
     */
    protected $_keywords = array();

    /**
     * the words, which should not be indexed
     *
     * @var array
     */
    protected $_stopwords = array();

    /**
     * the keywords of an article stored in the DB
     *
     * @var array
     */
    protected $_keywordsOld = array();

    /**
     * the keywords to be deleted
     *
     * @var array
     */
    protected $_keywordsDel = array();

    /**
     * 'auto' or 'self'
     * The field 'auto' in table con_keywords is used for automatic indexing.
     * The value is a string like "&12=2(CMS_HTMLHEAD-1,CMS_HTML-1)", which
     * means a keyword occurs 2 times in article with $idart 12
     * and can be found in CMS_HTMLHEAD[1] and CMS_HTML[1].
     * The field 'self' can be used in the article properties to index the
     * article manually.
     *
     * @var string
     */
    protected $_place;

    /**
     * array of cms types
     *
     * @var array
     */
    protected $_cmsOptions = array();

    /**
     * array of all available cms types
     *
     * htmlhead - HTML Headline
     * html - HTML Text
     * head - Headline (no HTML)
     * text - Text (no HTML)
     * img - Upload id of the element
     * imgdescr - Image description
     * link - Link (URL)
     * linktarget - Linktarget (_self, _blank, _top ...)
     * linkdescr - Linkdescription
     * swf - Upload id of the element
     * etc.
     *
     * @var array
     */
    protected $_cmsType = array();

    /**
     * the suffix of all available cms types
     *
     * @var array
     */
    protected $_cmsTypeSuffix = array();

    /**
     * Constructor, set object properties
     *
     * @param cDb $db CONTENIDO Database object
     * @return void
     */
    public function __construct($db = NULL) {
        parent::__construct($db);

        $this->setContentTypes();
    }

    /**
     * Start indexing the article.
     *
     * @param int $idart Article Id
     * @param array $aContent The complete content of an article specified by
     *        its content types.
     *        It looks like
     *        Array (
     *        [CMS_HTMLHEAD] => Array (
     *        [1] => Herzlich Willkommen...
     *        [2] => ...auf Ihrer Website!
     *        )
     *        [CMS_HTML] => Array (
     *        [1] => Die Inhalte auf dieser Website ...
     *
     * @param string $place The field where to store the index information in
     *        db.
     * @param array $cms_options One can specify explicitly cms types which
     *        should not be indexed.
     * @param array $aStopwords Array with words which should not be indexed.
     */
    public function start($idart, $aContent, $place = 'auto', $cms_options = array(), $aStopwords = array()) {
        if (!is_int((int) $idart) || $idart < 0) {
            return;
        } else {
            $this->idart = $idart;
        }

        $this->_place = $place;
        $this->_keycode = $aContent;
        $this->setStopwords($aStopwords);
        $this->setCmsOptions($cms_options);

        $this->createKeywords();

        $this->getKeywords();

        $this->saveKeywords();

        $new_keys = array_keys($this->_keywords);
        $old_keys = array_keys($this->_keywordsOld);

        $this->_keywordsDel = array_diff($old_keys, $new_keys);

        if (count($this->_keywordsDel) > 0) {
            $this->deleteKeywords();
        }
    }

    /**
     * for each cms-type create index structure.
     * it looks like
     * Array (
     * [die] => CMS_HTML-1
     * [inhalte] => CMS_HTML-1
     * [auf] => CMS_HTML-1 CMS_HTMLHEAD-2
     * [dieser] => CMS_HTML-1
     * [website] => CMS_HTML-1 CMS_HTML-1 CMS_HTMLHEAD-2
     * )
     */
    public function createKeywords() {
        $tmp_keys = array();

        // Only create keycodes, if some are available
        if (is_array($this->_keycode)) {
            foreach ($this->_keycode as $idtype => $data) {
                if ($this->checkCmsType($idtype)) {
                    foreach ($data as $typeid => $code) {
                        $this->_debug('code', $code);

                        // remove backslash
                        $code = stripslashes($code);
                        // replace HTML line breaks with newlines
                        $code = str_ireplace(array(
                            '<br>',
                            '<br />'
                        ), "\n", $code);
                        // remove html tags
                        $code = strip_tags($code);
                        if (strlen($code) > 0) {
                            $code = conHtmlEntityDecode($code);
                        }
                        $this->_debug('code', $code);

                        // split content by any number of commas or space
                        // characters
                        $tmp_keys = preg_split('/[\s,]+/', trim($code));
                        $this->_debug('tmp_keys', $tmp_keys);

                        foreach ($tmp_keys as $value) {
                            // index terms are stored with lower case
                            // $value = strtolower($value);

                            $value = htmlentities($value, ENT_COMPAT, 'UTF-8');
                            $value = trim(strtolower($value));
                            $value = html_entity_decode($value, ENT_COMPAT, 'UTF-8');

                            if (!in_array($value, $this->_stopwords)) {
                                // eliminate stopwords
                                $value = $this->removeSpecialChars($value);

                                if (strlen($value) > 1) {
                                    // do not index single characters
                                    $this->_keywords[$value] = $this->_keywords[$value] . $idtype . '-' . $typeid . ' ';
                                }
                            }
                        }
                    }
                }

                unset($tmp_keys);
            }
        }

        $this->_debug('keywords', $this->_keywords);
    }

    /**
     * generate index_string from index structure and save keywords
     * The index_string looks like "&12=2(CMS_HTMLHEAD-1,CMS_HTML-1)"
     */
    public function saveKeywords() {
        $tmp_count = array();

        foreach ($this->_keywords as $keyword => $count) {
            $tmp_count = preg_split('/[\s]/', trim($count));
            $this->_debug('tmp_count', $tmp_count);

            $occurrence = count($tmp_count);
            $tmp_count = array_unique($tmp_count);
            $cms_types = implode(',', $tmp_count);
            $index_string = '&' . $this->idart . '=' . $occurrence . '(' . $cms_types . ')';

            if (!array_key_exists($keyword, $this->_keywordsOld)) {
                // if keyword is new, save index information
                // $nextid = $this->db->nextid($this->cfg['tab']['keywords']);
                $sql = "INSERT INTO " . $this->cfg['tab']['keywords'] . "
                            (keyword, " . $this->_place . ", idlang)
                        VALUES
                            ('" . $this->db->escape($keyword) . "', '" . $this->db->escape($index_string) . "', " . cSecurity::toInteger($this->lang) . ")";
            } else {
                // if keyword allready exists, create new index_string
                if (preg_match("/&$this->idart=/", $this->_keywordsOld[$keyword])) {
                    $index_string = preg_replace("/&$this->idart=[0-9]+\([\w-,]+\)/", $index_string, $this->_keywordsOld[$keyword]);
                } else {
                    $index_string = $this->_keywordsOld[$keyword] . $index_string;
                }

                $sql = "UPDATE " . $this->cfg['tab']['keywords'] . "
                        SET " . $this->_place . " = '" . $index_string . "'
                        WHERE idlang='" . cSecurity::toInteger($this->lang) . "' AND keyword='" . $this->db->escape($keyword) . "'";
            }
            $this->_debug('sql', $sql);
            $this->db->query($sql);
        }
    }

    /**
     * if keywords don't occur in the article anymore, update index_string and
     * delete keyword if necessary
     */
    public function deleteKeywords() {
        foreach ($this->_keywordsDel as $key_del) {
            $index_string = preg_replace("/&$this->idart=[0-9]+\([\w-,]+\)/", "", $this->_keywordsOld[$key_del]);

            if (strlen($index_string) == 0) {
                // keyword is not referenced by any article
                $sql = "DELETE FROM " . $this->cfg['tab']['keywords'] . "
                    WHERE idlang = " . cSecurity::toInteger($this->lang) . " AND keyword = '" . $this->db->escape($key_del) . "'";
            } else {
                $sql = "UPDATE " . $this->cfg['tab']['keywords'] . "
                    SET " . $this->_place . " = '" . $index_string . "'
                    WHERE idlang = " . cSecurity::toInteger($this->lang) . " AND keyword = '" . $this->db->escape($key_del) . "'";
            }
            $this->_debug('sql', $sql);
            $this->db->query($sql);
        }
    }

    /**
     * get the keywords of an article
     */
    public function getKeywords() {
        $keys = implode("','", array_keys($this->_keywords));

        $sql = "SELECT
                    keyword, auto, self
                FROM
                    " . $this->cfg['tab']['keywords'] . "
                WHERE
                    idlang=" . cSecurity::toInteger($this->lang) . "  AND
                    (keyword IN ('" . $keys . "')  OR " . $this->_place . " REGEXP '&" . cSecurity::toInteger($this->idart) . "=')";

        $this->_debug('sql', $sql);

        $this->db->query($sql);

        $place = $this->_place;

        while ($this->db->nextRecord()) {
            $this->_keywordsOld[$this->db->f('keyword')] = $this->db->f($place);
        }
    }

    /**
     * remove special characters from index term
     *
     * @param string $key Keyword
     * @return mixed
     */
    public function removeSpecialChars($key) {
        $aSpecialChars = array(
            /*"-",*/
            "_",
            "'",
            ".",
            "!",
            "\"",
            "#",
            "$",
            "%",
            "&",
            "(",
            ")",
            "*",
            "+",
            ",",
            "/",
            ":",
            ";",
            "<",
            "=",
            ">",
            "?",
            "@",
            "[",
            "\\",
            "]",
            "^",
            "`",
            "{",
            "|",
            "}",
            "~",
            "„"
        );

        // for ($i = 127; $i < 192; $i++) {
        // some other special characters
        // $aSpecialChars[] = chr($i);
        // }

        // TODO: The transformation of accented characters must depend on the
        // selected encoding of the language of
        // a client and should not be treated in this method.
        // modified 2007-10-01, H. Librenz - added as hotfix for encoding
        // problems (doesn't find any words with
        // umlaut vowels in it since you turn on UTF-8 as language encoding)
        $sEncoding = getEncodingByLanguage($this->db, $this->lang);

        if (strtolower($sEncoding) != 'iso-8859-2') {
            $key = conHtmlentities($key, NULL, $sEncoding);
        } else {
            $key = htmlentities_iso88592($key);
        }

        // $aUmlautMap = array(
        // '&Uuml;' => 'ue',
        // '&uuml;' => 'ue',
        // '&Auml;' => 'ae',
        // '&auml;' => 'ae',
        // '&Ouml;' => 'oe',
        // '&ouml;' => 'oe',
        // '&szlig;' => 'ss'
        // );

        // foreach ($aUmlautMap as $sUmlaut => $sMapped) {
        // $key = str_replace($sUmlaut, $sMapped, $key);
        // }

        $key = conHtmlEntityDecode($key);
        $key = str_replace($aSpecialChars, '', $key);

        return $key;
    }

    /**
     *
     * @param string $key Keyword
     * @return string
     */
    public function addSpecialUmlauts($key) {
        $key = conHtmlentities($key, NULL, getEncodingByLanguage($this->db, $this->lang));
        $aUmlautMap = array(
            'ue' => '&Uuml;',
            'ue' => '&uuml;',
            'ae' => '&Auml;',
            'ae' => '&auml;',
            'oe' => '&Ouml;',
            'oe' => '&ouml;',
            'ss' => '&szlig;'
        );

        foreach ($aUmlautMap as $sUmlaut => $sMapped) {
            $key = str_replace($sUmlaut, $sMapped, $key);
        }

        $key = conHtmlEntityDecode($key);
        return $key;
    }

    /**
     * set the array of stopwords which should not be indexed
     *
     * @param array $aStopwords
     */
    public function setStopwords($aStopwords) {
        if (is_array($aStopwords) && count($aStopwords) > 0) {
            $this->_stopwords = $aStopwords;
        }
    }

    /**
     * set the cms types
     */
    public function setContentTypes() {
        $sql = "SELECT type, idtype FROM " . $this->cfg['tab']['type'] . ' ';
        $this->_debug('sql', $sql);
        $this->db->query($sql);
        while ($this->db->nextRecord()) {
            $this->_cmsType[$this->db->f('type')] = $this->db->f('idtype');
            $this->_cmsTypeSuffix[$this->db->f('idtype')] = substr($this->db->f('type'), 4, strlen($this->db->f('type')));
        }
    }

    /**
     * set the cms_options array of cms types which should be treated special
     *
     * @param mixed $cms_options
     */
    public function setCmsOptions($cms_options) {
        if (is_array($cms_options) && count($cms_options) > 0) {
            foreach ($cms_options as $opt) {
                $opt = strtoupper($opt);

                if (strlen($opt) > 0) {
                    if (!stristr($opt, 'cms_')) {
                        if (in_array($opt, $this->_cmsTypeSuffix)) {
                            $this->_cmsOptions[$opt] = 'CMS_' . $opt;
                        }
                    } else {
                        if (array_key_exists($opt, $this->_cmsType)) {
                            $this->_cmsOptions[$opt] = $opt;
                        }
                    }
                }
            }
        } else {
            $this->_cmsOptions = array();
        }
    }

    /**
     * check if the current cms type is in the cms_options array
     *
     * @param string $idtype
     * @return boolean
     */
    public function checkCmsType($idtype) {
        $idtype = strtoupper($idtype);
        return (in_array($idtype, $this->_cmsOptions)) ? false : true;
    }

    /**
     *
     * @return array the _cmsType property
     */
    public function getCmsType() {
        return $this->_cmsType;
    }

    /**
     *
     * @return array the _cmsTypeSuffix property
     */
    public function getCmsTypeSuffix() {
        return $this->_cmsTypeSuffix;
    }
}

/**
 * CONTENIDO API - Search Object
 *
 * This object starts a indexed fulltext search
 *
 * TODO:
 * The way to set the search options could be done much more better!
 * The computation of the set of searchable articles should not be treated in
 * this class.
 * It is better to compute the array of searchable articles from the outside and
 * to pass the array of searchable articles as parameter.
 * Avoid foreach loops.
 *
 * Use object with
 *
 * $options = array('db' => 'regexp', // use db function regexp
 * 'combine' => 'or'); // combine searchwords with or
 *
 * The range of searchable articles is by default the complete content which is
 * online and not protected.
 *
 * With option 'searchable_articles' you can define your own set of searchable
 * articles.
 * If parameter 'searchable_articles' is set the options 'cat_tree',
 * 'categories', 'articles', 'exclude', 'artspecs',
 * 'protected', 'dontshowofflinearticles' don't have any effect.
 *
 * $options = array('db' => 'regexp', // use db function regexp
 * 'combine' => 'or', // combine searchwords with or
 * 'searchable_articles' => array(5, 6, 9, 13));
 *
 * One can define the range of searchable articles by setting the parameter
 * 'exclude' to false which means the range of categories
 * defined by parameter 'cat_tree' or 'categories' and the range of articles
 * defined by parameter 'articles' is included.
 *
 * $options = array('db' => 'regexp', // use db function regexp
 * 'combine' => 'or', // combine searchwords with or
 * 'exclude' => false, // => searchrange specified in 'cat_tree', 'categories'
 * and 'articles' is included
 * 'cat_tree' => array(12), // tree with root 12 included
 * 'categories' => array(100,111), // categories 100, 111 included
 * 'articles' => array(33), // article 33 included
 * 'artspecs' => array(2, 3), // array of article specifications => search only
 * articles with these artspecs
 * 'res_per_page' => 2, // results per page
 * 'protected' => true); // => do not search articles or articles in categories
 * which are offline or protected
 * 'dontshowofflinearticles' => false); // => search offline articles or
 * articles in categories which are offline
 *
 * You can build the complement of the range of searchable articles by setting
 * the parameter 'exclude' to true which means the range of categories
 * defined by parameter 'cat_tree' or 'categories' and the range of articles
 * defined by parameter 'articles' is excluded from search.
 *
 * $options = array('db' => 'regexp', // use db function regexp
 * 'combine' => 'or', // combine searchwords with or
 * 'exclude' => true, // => searchrange specified in 'cat_tree', 'categories'
 * and 'articles' is excluded
 * 'cat_tree' => array(12), // tree with root 12 excluded
 * 'categories' => array(100,111), // categories 100, 111 excluded
 * 'articles' => array(33), // article 33 excluded
 * 'artspecs' => array(2, 3), // array of article specifications => search only
 * articles with these artspecs
 * 'res_per_page' => 2, // results per page
 * 'protected' => true); // => do not search articles or articles in categories
 * which are offline or protected
 * 'dontshowofflinearticles' => false); // => search offline articles or
 * articles in categories which are offline
 *
 * $search = new Search($options);
 *
 * $cms_options = array("htmlhead", "html", "head", "text", "imgdescr", "link",
 * "linkdescr");
 * search only in these cms-types
 * $search->setCmsOptions($cms_options);
 *
 * $search_result = $search->searchIndex($searchword, $searchwordex); // start
 * search
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
 * Searching 'con' matches keywords 'content', 'contenido' and 'wwwcontenidoorg'
 * in article with ID 20 in content type CMS_HTML[1].
 * The search term occurs 7 times.
 * The maximum similarity between searchterm and matching keyword is 60%.
 *
 * with $oSearchResults = new cSearchResult($search_result, 10);
 * one can rank and display the results
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
     * array of available cms types
     *
     * @var array
     */
    protected $_cmsType = array();

    /**
     * suffix of available cms types
     *
     * @var array
     */
    protected $_cmsTypeSuffix = array();

    /**
     * the search words
     *
     * @var array
     */
    protected $_searchWords = array();

    /**
     * the words which should be excluded from search
     *
     * @var array
     */
    protected $_searchWordsExclude = array();

    /**
     * type of db search
     * like => 'sql like', regexp => 'sql regexp'
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
     * If $protected = true => do not search articles which are offline or
     * articles in catgeories which are offline (protected)
     *
     * @var boolean
     */
    protected $_protected;

    /**
     * If $dontshowofflinearticles = false => search offline articles or
     * articles in categories which are offline
     *
     * @var boolean
     */
    protected $_dontshowofflinearticles;

    /**
     * If $exclude = true => the specified search range is excluded from search,
     * otherwise included
     *
     * @var boolean
     */
    protected $_exclude;

    /**
     * Array of article id's with information about cms-types, occurence of
     * keyword/searchword, similarity .
     *
     *
     *
     * @var array
     */
    protected $_searchResult = array();

    /**
     * Constructor
     *
     * @param array $options $options['db'] 'regexp' => DB search with REGEXP;
     *        'like' => DB search with LIKE; 'exact' => exact match;
     *        $options['combine'] 'and', 'or' Combination of search words with
     *        AND, OR
     *        $options['exclude'] 'true' => searchrange specified in 'cat_tree',
     *        'categories' and 'articles' is excluded; 'false' =>
     *        searchrange specified in 'cat_tree', 'categories' and
     *        'articles' is included
     *        $options['cat_tree'] e.g. array(8) => The complete tree with root
     *        8 is in/excluded from search
     *        $options['categories'] e.g. array(10, 12) => Categories 10, 12
     *        in/excluded
     *        $options['articles'] e.g. array(23) => Article 33 in/excluded
     *        $options['artspecs'] => e.g. array(2, 3) => search only articles
     *        with certain article specifications
     *        $options['protected'] 'true' => do not search articles which are
     *        offline (locked) or articles in catgeories which are offline
     *        (protected)
     *        $options['dontshowofflinearticles'] 'false' => search offline
     *        articles or articles in categories which are offline
     *        $options['searchable_articles'] array of article ID's which should
     *        be searchable
     * @param cDb $db Optional database instance
     */
    public function __construct($options, $db = NULL) {
        parent::__construct($db);

        $this->_index = new cSearchIndex($db);

        $this->_cmsType = $this->_index->cms_type;
        $this->_cmsTypeSuffix = $this->_index->cms_type_suffix;

        $this->_searchOption = (array_key_exists('db', $options)) ? strtolower($options['db']) : 'regexp';
        $this->_searchCombination = (array_key_exists('combine', $options)) ? strtolower($options['combine']) : 'or';
        $this->_protected = (array_key_exists('protected', $options)) ? $options['protected'] : true;
        $this->_dontshowofflinearticles = (array_key_exists('dontshowofflinearticles', $options)) ? $options['dontshowofflinearticles'] : false;
        $this->_exclude = (array_key_exists('exclude', $options)) ? $options['exclude'] : true;
        $this->_articleSpecs = (array_key_exists('artspecs', $options) && is_array($options['artspecs'])) ? $options['artspecs'] : array();
        $this->_index->setCmsOptions($this->_cmsTypeSuffix);

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
     * @param string $searchwords The search words
     * @param string $searchwords_exclude The words, which should be excluded
     *        from search
     * @return boolean multitype:
     */
    public function searchIndex($searchwords, $searchwords_exclude = '') {
        if (strlen(trim($searchwords)) > 0) {
            $this->_searchWords = $this->stripWords($searchwords);
        } else {
            return false;
        }

        if (strlen(trim($searchwords_exclude)) > 0) {
            $this->_searchWordsExclude = $this->stripWords($searchwords_exclude);
        }

        $tmp_searchwords = array();
        foreach ($this->_searchWords as $word) {
            $wordEscaped = $this->db->escape($word);
            if ($this->_searchOption == 'like') {
                $wordEscaped = "'%" . $wordEscaped . "%'";
            } elseif ($this->_searchOption == 'exact') {
                $wordEscaped = "'" . $wordEscaped . "'";
            }
            $tmp_searchwords[] = $word;
        }

        if (count($this->_searchWordsExclude) > 0) {
            foreach ($this->_searchWordsExclude as $word) {
                $wordEscaped = $this->db->escape($word);
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
            $kwSql = "keyword LIKE '" . $search_like;
        } elseif ($this->_searchOption == 'exact') {
            // exact match
            $search_exact = implode(" OR keyword = ", $tmp_searchwords);
            $kwSql = "keyword LIKE '" . $search_exact;
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
                                if ($similarity > $this->_searchResult[$artid]['similarity']) {
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

        return $this->_searchResult;
    }

    /**
     *
     * @param mixed $cms_options The cms-types (htmlhead, html, ...) which
     *            should
     *        explicitly be searched
     */
    public function setCmsOptions($cms_options) {
        if (is_array($cms_options) && count($cms_options) > 0) {
            $this->_index->setCmsOptions($cms_options);
        }
    }

    /**
     *
     * @param string $searchwords The search-words
     * @return array of stripped search-words
     */
    public function stripWords($searchwords) {
        // remove backslash and html tags
        $searchwords = trim(strip_tags(stripslashes($searchwords)));

        // split the phrase by any number of commas or space characters
        $tmp_words = preg_split('/[\s,]+/', $searchwords);

        $tmp_searchwords = array();

        foreach ($tmp_words as $word) {

            $word = htmlentities($word, ENT_COMPAT, 'UTF-8');
            $word = (trim(strtolower($word)));
            $word = html_entity_decode($word, ENT_COMPAT, 'UTF-8');

            // $word =(trim(strtolower($word)));
            if (strlen($word) > 1) {
                $tmp_searchwords[] = $word;
            }
        }

        return array_unique($tmp_searchwords);
    }

    /**
     * Returns the category tree array.
     *
     * @param int $cat_start Root of a category tree
     * @return array Category Tree
     * @todo This is not the job for search, should be outsourced ...
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
     * Returns list of searchable article ids.
     *
     * @param array $search_range
     * @return array Articles in specified search range
     */
    public function getSearchableArticles($search_range) {
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

        if (array_key_exists('articles', $search_range) && is_array($search_range['articles'])) {
            if (count($search_range['articles']) > 0) {
                $sArtRange = implode("','", $search_range['articles']);
            } else {
                $sArtRange = '';
            }
        }

        if ($this->_protected == true) {
            $sProtected = " C.public = 1 AND C.visible = 1 AND B.online = 1 ";
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
            if (strlen($sArtRange) > 0) {
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
                    A.idart
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
            $aIdArts[] = $this->db->f('idart');
        }
        return $aIdArts;
    }

    /**
     * Fetch all article specifications which are online,
     *
     * @return array Array of article specification Ids
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
     * Set article specification
     *
     * @param int $iArtspecID
     */
    public function setArticleSpecification($iArtspecID) {
        $this->_articleSpecs[] = $iArtspecID;
    }

    /**
     * Add all article specifications matching name of article specification
     * (client dependent but language independent)
     *
     * @param string $sArtSpecName
     * @return boolean
     */
    public function addArticleSpecificationsByName($sArtSpecName) {
        if (!isset($sArtSpecName) || strlen($sArtSpecName) == 0) {
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

/**
 * CONTENIDO API - SearchResult Object
 *
 * This object ranks and displays the result of the indexed fulltext search.
 * If you are not comfortable with this API feel free to use your own methods to
 * display the search results.
 * The search result is basically an array with article ID's.
 *
 * If $search_result = $search->searchIndex($searchword, $searchwordex);
 *
 * use object with
 *
 * $oSearchResults = new cSearchResult($search_result, 10);
 *
 * $oSearchResults->setReplacement('<span style="color:red">', '</span>'); //
 * html-tags to emphasize the located searchwords
 *
 * $num_res = $oSearchResults->getNumberOfResults();
 * $num_pages = $oSearchResults->getNumberOfPages();
 * $res_page = $oSearchResults->getSearchResultPage(1); // first result page
 * foreach ($res_page as $key => $val) {
 * $headline = $oSearchResults->getSearchContent($key, 'HTMLHEAD');
 * $first_headline = $headline[0];
 * $text = $oSearchResults->getSearchContent($key, 'HTML');
 * $first_text = $text[0];
 * $similarity = $oSearchResults->getSimilarity($key);
 * $iOccurrence = $oSearchResults->getOccurrence($key);
 * }
 *
 * @package Core
 * @subpackage Frontend_Search
 *
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
     *
     *
     *
     * @var array
     */
    protected $_searchResult = array();

    /**
     * Compute ranking factor for each search result and order the search
     * results by ranking factor
     * NOTE: The ranking factor is the sum of occurences of matching searchterms
     * weighted by similarity (in %) between searchword
     * and matching word in the article.
     * TODO: One can think of more sophisticated ranking strategies. One could
     * use the content type information for example
     * because a matching word in the headline (CMS_HEADLINE[1]) could be
     * weighted more than a matching word in the text (CMS_HTML[1]).
     *
     * @param array $search_result List of article ids
     * @param int $result_per_page Number of items per page
     * @param cDb $oDB Optional db instance
     * @param bool $bDebug Optional flag to enable debugging
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
     * @param int $art_id Id of an article
     * @param string $cms_type
     * @param int $id
     * @return string Content of an article, specified by it's content type
     */
    public function getContent($art_id, $cms_type, $id = 0) {
        $article = new cApiArticleLanguage();
        $article->loadByArticleAndLanguageId($art_id, $this->lang, true);
        return $article->getContent($cms_type, $id);
    }

    /**
     *
     * @param int $art_id Id of an article
     * @param string $cms_type Content type
     * @param int $cms_nr
     * @return string Content of an article in search result, specified by its
     *         type
     */
    public function getSearchContent($art_id, $cms_type, $cms_nr = NULL) {
        $cms_type = strtoupper($cms_type);
        if (strlen($cms_type) > 0) {
            if (!stristr($cms_type, 'cms_')) {
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
        $article->loadByArticleAndLanguageId($art_id, $this->lang, true);
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
     * @return array Articles in page $page_id
     */
    public function getSearchResultPage($page_id) {
        $this->_resultPage = $page_id;
        $result_page = $this->_orderedSearchResult[$page_id - 1];
        return $result_page;
    }

    /**
     * Returns number of result pages
     *
     * @return int
     */
    public function getNumberOfPages() {
        return $this->_pages;
    }

    /**
     * Returns number of results
     *
     * @return int
     */
    public function getNumberOfResults() {
        return $this->_results;
    }

    /**
     *
     * @param int $art_id Id of an article
     * @return int Similarity between searchword and matching word in article
     */
    public function getSimilarity($art_id) {
        return $this->_searchResult[$art_id]['similarity'];
    }

    /**
     *
     * @param int $art_id Id of an article
     * @return number of matching searchwords found in article
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
     * @param string $rep1 The opening html-tag to emphasize the searchword e.g.
     *        '<b>'
     * @param string $rep2 The closing html-tag e.g. '</b>'
     * @return void
     */
    public function setReplacement($rep1, $rep2) {
        if (strlen(trim($rep1)) > 0 && strlen(trim($rep2)) > 0) {
            $this->_replacement[] = $rep1;
            $this->_replacement[] = $rep2;
        }
    }

    /**
     *
     * @param int $artid
     * @return int Category Id
     * @todo Is not job of search, should be outsourced!
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
