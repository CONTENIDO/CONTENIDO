<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * API to index a contenido article
 * API to search in the index structure
 * API to display the searchresults
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend classes
 * @version    1.0.2
 * @author     Willi Man
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 *
 * {@internal
 *   created 2004-01-15
 *   modified 2008-06-30, Frederic Schneider, add security fix
 *   modified 2008-07-11, Dominik Ziegler, marked class search_helper as deprecated
 *   modified 2008-11-12, Andreas Lindner, add special treatment for iso-8859-2
 *   modified 2013-01-02, Murat Purc, Fixed escape issue in db search options [#CON-939]
 *
 *   $Id: class.search.php 873 2008-11-12 09:18:50Z andreas.lindner $:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

cInclude('includes', 'functions.encoding.php');

/**
 * Contenido API - Index Object
 *
 * This object creates an index of an article
 *
 * Create object with
 * $oIndex = new Index($db); # where $db is the global Contenido database object.
 * Start indexing with
 * $oIndex->start($idart, $aContent);
 * where $aContent is the complete content of an article specified by its content types.
 * It looks like
 * Array (
 *     [CMS_HTMLHEAD] => Array (
 *         [1] => Herzlich Willkommen...
 *         [2] => ...auf Ihrer Website!
 *     )
 *     [CMS_HTML] => Array (
 *         [1] => Die Inhalte auf dieser Website ...
 *
 * The index for keyword 'willkommen' would look like '&12=1(CMS_HTMLHEAD-1)' which means the keyword 'willkommen' occurs 1 times in article with articleId 12 and content type CMS_HTMLHEAD[1].
 *
 * TODO: The basic idea of the indexing process is to take the complete content of an article and to generate normalized index terms
 * from the content and to store a specific index structure in the relation 'con_keywords'.
 * To take the complete content is not very flexible. It would be better to differentiate by specific content types or by any content.
 * The &, =, () and - seperated string is not easy to parse to compute the search result set.
 * It would be a better idea (and a lot of work) to extend the relation 'con_keywords' to store keywords by articleId (or content source identifier) and content type.
 * The functions removeSpecialChars, setStopwords, setContentTypes and setCmsOptions should be sourced out into a new helper-class.
 * Keep in mind that class Search and SearchResult uses an instance of object Index.
 * Consider character tables in relation 'con_chartable'.
 */

class Index {

    /**
     * the content of the cms-types of an article
     * @var array
     */
    var $keycode = array();

    /**
     * the list of keywords of an article
     * @var array
     */
    var $keywords = array();

    /**
     * the words, which should not be indexed
     * @var array
     */
    var $stopwords = array();

    /**
     * the keywords of an article stored in the DB
     * @var array
     */
    var $keywords_old = array();

    /**
     * the keywords to be deleted
     * @var array
     */
    var $keywords_del = array();

    /**
     * article id
     * @var int
     */
    var $idart;

    /**
     * 'auto' or 'self'
     * The field 'auto' in table con_keywords is used for automatic indexing.
     * The value is a string like "&12=2(CMS_HTMLHEAD-1,CMS_HTML-1)", which means a keyword occurs 2 times in article with $idart 12
     * and can be found in CMS_HTMLHEAD[1] and CMS_HTML[1].
     * The field 'self' can be used in the article properties to index the article manually.
     * @var string
     */
    var $place;

    /**
     * language of a client
     * @var int
     */
    var $lang;

    /**
     * Contenido database object
     * @var object
     */
    var $db;

    /**
     * configuration data
     * @var array
     */
    var $cfg;

    /**
     * array of cms types
     * @var array
     */
    var $cms_options = array();

    /**
     * array of all available cms types
     *
     * htmlhead      - HTML Headline
     * html          - HTML Text
     * head          - Headline (no HTML)
     * text          - Text (no HTML)
     * img           - Upload id of the element
     * imgdescr      - Image description
     * link          - Link (URL)
     * linktarget    - Linktarget (_self, _blank, _top ...)
     * linkdescr     - Linkdescription
     * swf           - Upload id of the element
     * etc.
     *
     * @var array
     */
    var $cms_type = array();

    /**
     * the suffix of all available cms types
     * @var array
     */
    var $cms_type_suffix = array();

    /**
     * @var bool
     */
    var $bDebug;

    /**
     * Constructor
     * set object properties
     * @param $oDB Contenido Database object
     * @return void
     */
    function Index($oDB = false) {
        # TODO: avoid globals
        global $cfg, $lang;

        $this->cfg = $cfg;
        $this->lang = $lang;
        $this->bDebug = false;

        if ($oDB == false) {
            $this->db = new DB_Contenido;
        } elseif (is_object($oDB)) {
            $this->db = &$oDB;
        }

        $this->setContentTypes();
    }

    /**
     * start indexing the article
     * @param int $idart Article Id
     * @param array $aContent The complete content of an article specified by its content types.
     * It looks like
     * Array (
     *     [CMS_HTMLHEAD] => Array (
     *         [1] => Herzlich Willkommen...
     *         [2] => ...auf Ihrer Website!
     *     )
     *     [CMS_HTML] => Array (
     *         [1] => Die Inhalte auf dieser Website ...
     *
     * @param string $place The field where to store the index information in db.
     * @param array $aCmsOptions One can specify explicitly cms types which should not be indexed.
     * @param arary $aStopwords Array with words which should not be indexed.
     * @return void
     */
    function start($idart, $aContent, $place = 'auto', $aCmsOptions = array(), $aStopwords = array()) {
        if (!is_int((int) $idart) || $idart < 0) {
            return null;
        } else {
            $this->idart = $idart;
        }

        $this->place = $place;
        $this->keycode = $aContent;
        $this->setStopwords($aStopwords);
        $this->setCmsOptions($aCmsOptions);

        $this->createKeywords();
        $this->getKeywords();
        $this->saveKeywords();

        $aNewKeys = array_keys($this->keywords);
        $aOldKeys = array_keys($this->keywords_old);

        $this->keywords_del = array_diff($aOldKeys, $aNewKeys);

        if (count($this->keywords_del) > 0) {
            $this->deleteKeywords();
        }
    }

    /**
     * for each cms-type create index structure.
     * it looks like
     * Array (
     *     [die] => CMS_HTML-1
     *     [inhalte] => CMS_HTML-1
     *     [auf] => CMS_HTML-1 CMS_HTMLHEAD-2
     *     [dieser] => CMS_HTML-1
     *     [website] => CMS_HTML-1 CMS_HTML-1 CMS_HTMLHEAD-2
     * )
     *
     * @param none
     * @return void
     */
    function createKeywords() {
        $aKeys = array();

        if (is_array($this->keycode)) { // Only create keycodes, if some are available
            foreach ($this->keycode as $idtype => $data) {
                if ($this->checkCmsType($idtype)) {
                    foreach ($data as $typeid => $code) {
                        if ($this->bDebug) {
                            echo "<pre>code<br>" . $code . "</pre>";
                        }

                        $code = stripslashes($code); // remove backslash
                        $code = str_ireplace(array('<br>', '<br />'), "\n", $code); // replace HTML line breaks with newlines
                        $code = strip_tags($code); // remove html tags
                        if (strlen($code) > 0) {
                            $code = conHtmlEntityDecode($code);
                        }

                        if ($this->bDebug) {
                            echo "<pre>code<br>" . $code . "</pre>";
                        }

                        $aKeys = preg_split("/[\s,]+/", trim($code)); // split content by any number of commas or space characters

                        if ($this->bDebug) {
                            echo "<pre>keys<br>" . print_r($aKeys, true) . "</pre>";
                        }

                        foreach ($aKeys as $value) {
                            $value = strtolower($value); // index terms are stored with lower case

                            if (!in_array($value, $this->stopwords)) { // eliminate stopwords
                                $value = $this->removeSpecialChars($value);

                                if (strlen($value) > 1) { // do not index single characters
                                    $this->keywords[$value] = $this->keywords[$value] . $idtype . "-" . $typeid . " ";
                                }
                            }
                        }
                    }
                }

                unset($aKeys);
            }
        }

        if ($this->bDebug) {
            echo "<pre>keys<br>" . print_r($this->keywords, true) . "</pre>";
        }
    }

    /**
     * generate index_string from index structure and save keywords
     * The index_string looks like "&12=2(CMS_HTMLHEAD-1,CMS_HTML-1)"
     * @return void
     */
    function saveKeywords() {
        $aCmsTypes = array();

        foreach ($this->keywords as $keyword => $count) {
            $aCmsTypes = preg_split("/[\s]/", trim($count));
            if ($this->bDebug) {
                echo "<pre>" . print_r($aCmsTypes, true) . "</pre>";
            }

            // @TODO  Shouldn't we calculate the occurrance after removing the duplicates?
            $iOccurrence = count($aCmsTypes);
            $aCmsTypes = array_unique($aCmsTypes);
            $sCmsTypes = implode(',', $aCmsTypes);

            $index_string = '&' . $this->idart . '=' . $iOccurrence . '(' . $sCmsTypes . ')';

            if (!array_key_exists($keyword, $this->keywords_old)) {// if keyword is new, save index information
                $nextid = $this->db->nextid($this->cfg['tab']['keywords']);

                $sql = "INSERT INTO " . $this->cfg['tab']['keywords'] . " (keyword, " . $this->place . ", idlang, idkeyword)
                        VALUES ('" . Contenido_Security::escapeDB($keyword, $this->db) . "', '" . Contenido_Security::escapeDB($index_string, $this->db) . "', " . Contenido_Security::toInteger($this->lang) . ", " . Contenido_Security::toInteger($nextid) . ")";

                if ($this->bDebug) {
                    echo "<pre>" . $sql . "</pre>";
                }

                $this->db->query($sql);
            } else { // if keyword allready exists, create new index_string
                if (preg_match("/&$this->idart=/", $this->keywords_old[$keyword])) {
                    $index_string = preg_replace("/&$this->idart=[0-9]+\([\w-,]+\)/", $index_string, $this->keywords_old[$keyword]);
                } else {
                    $index_string = $this->keywords_old[$keyword] . $index_string;
                }

                $sql = "UPDATE " . $this->cfg['tab']['keywords'] . " SET " . $this->place . " = '" . $index_string . "'
                        WHERE idlang = " . Contenido_Security::toInteger($this->lang) . " AND keyword = '" . Contenido_Security::escapeDB($keyword, $this->db) . "'";

                if ($this->bDebug) {
                    echo "<pre>" . $sql . "</pre>";
                }

                $this->db->query($sql);
            }
        }
    }

    /**
     * if keywords don't occur in the article anymore, update index_string and delete keyword if necessary
     * @param none
     * @return void
     */
    function deleteKeywords() {
        foreach ($this->keywords_del as $key_del) {
            $index_string = preg_replace("/&$this->idart=[0-9]+\([\w-,]+\)/", '', $this->keywords_old[$key_del]);

            if (strlen($index_string) == 0) { // keyword is not referenced by any article
                $sql = "DELETE FROM " . $this->cfg['tab']['keywords'] . "
                        WHERE idlang = " . Contenido_Security::toInteger($this->lang) . " AND keyword = '" . Contenido_Security::escapeDB($key_del, $this->db) . "'";
            } else {
                $sql = "UPDATE " . $this->cfg['tab']['keywords'] . " SET " . $this->place . " = '" . $index_string . "'
                        WHERE idlang = " . Contenido_Security::toInteger($this->lang) . " AND keyword = '" . Contenido_Security::escapeDB($key_del, $this->db) . "'";
            }

            if ($this->bDebug) {
                echo "<pre>" . $sql . "</pre>";
            }

            $this->db->query($sql);
        }
    }

    /**
     * get the keywords of an article
     * @param none
     * @return void
     */
    function getKeywords() {
        $keys = implode("','", array_keys($this->keywords));

        $sql = "SELECT keyword, auto, self FROM " . $this->cfg['tab']['keywords']
             . " WHERE idlang=" . Contenido_Security::toInteger($this->lang) . " AND (keyword IN ('" . $keys . "')"
             . " OR " . $this->place . " REGEXP '&" . Contenido_Security::toInteger($this->idart) . "=')";

        if ($this->bDebug) {
            echo "<pre>" . $sql . "</pre>";
        }

        $this->db->query($sql);

        $place = $this->place;

        while ($this->db->next_record()) {
            $this->keywords_old[$this->db->f('keyword')] = $this->db->f($place);
        }
    }

    /**
     * remove special characters from index term
     * @param $key Keyword
     * @return $key
     */
    function removeSpecialChars($key) {
        $aSpecialChars = array(
            "-", "_", "'", ".", "!", "\"", "#", "$", "%", "&", "(", ")", "*", "+",
            ",", "/", ":", ";", "<", "=", ">", "?", "@", "[", "\\", "]", "^", "`",
            "{", "|", "}", "~"
        );

        for ($i = 127; $i < 192; $i++) {
            $aSpecialChars[] = chr($i);  // some other special characters
        }

        /*
         * TODO: The transformation of accented characters must depend on the selected encoding of the language of
         * a client and should not be treated in this method.
         * modified 2007-10-01, H. Librenz - added as hotfix for encoding problems (doesn't find any words with
         *                                      umlaut vowels in it since you turn on UTF-8 as language encoding)
         */
        $sEncoding = getEncodingByLanguage($this->db, $this->lang, $this->cfg);

        if (strtolower($sEncoding) != 'iso-8859-2') {
            $key = conHtmlentities($key, NULL, $sEncoding);
        } else {
            $key = conHtmlentities_iso88592($key);
        }

        $aUmlautMap = array(
            '&Uuml;' => 'Ue',
            '&uuml;' => 'ue',
            '&Auml;' => 'Ae',
            '&auml;' => 'ae',
            '&Ouml;' => 'Oe',
            '&ouml;' => 'oe',
            '&szlig;' => 'ss'
        );

        foreach ($aUmlautMap as $sUmlaut => $sMapped) {
            $key = str_replace($sUmlaut, $sMapped, $key);
        }
        $key = conHtmlEntityDecode($key);
        $key = str_replace($aSpecialChars, '', $key);

        return $key;
    }

    /**
     * @modified 2008-04-17, Timo Trautmann - reverse function to removeSpecialChars
     *                                        (important for syntaxhighlighting searchterm in searchresults)
     * adds umlauts to search term
     * @param $key Keyword
     * @return $key
     */
    function addSpecialUmlauts($key) {
        $key = conHtmlentities($key, NULL, getEncodingByLanguage($this->db, $this->lang, $this->cfg));
        $aUmlautMap = array(
            'Ue' => '&Uuml;',
            'ue' => '&uuml;',
            'Ae' => '&Auml;',
            'ae' => '&auml;',
            'Oe' => '&Ouml;',
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
     * @param array $aStopwords
     * @return void
     */
    function setStopwords($aStopwords) {
        if (is_array($aStopwords) && count($aStopwords) > 0) {
            $this->stopwords = $aStopwords;
        }
    }

    /**
     * set the cms types
     * @param none
     * @return void
     */
    function setContentTypes() {
        $sql = "SELECT type, idtype FROM " . $this->cfg['tab']['type'];

        if ($this->bDebug) {
            echo "<pre>" . $sql . "</pre>";
        }

        $this->db->query($sql);

        while ($this->db->next_record()) {
            $this->cms_type[$this->db->f('type')] = $this->db->f('idtype');
            $this->cms_type_suffix[$this->db->f('idtype')] = substr($this->db->f('type'), 4, strlen($this->db->f('type')));
        }
    }

    /**
     * set the cms_options array of cms types which should be treated special
     * @param none
     * @return void
     */
    function setCmsOptions($cms_options) {
        if (is_array($cms_options) && count($cms_options) > 0) {
            foreach ($cms_options as $opt) {
                $opt = strtoupper($opt);

                if (strlen($opt) > 0) {
                    if (!stristr($opt, 'cms_')) {
                        if (in_array($opt, $this->cms_type_suffix)) {
                            $this->cms_options[$opt] = 'CMS_' . $opt;
                        }
                    } else {
                        if (array_key_exists($opt, $this->cms_type)) {
                            $this->cms_options[$opt] = $opt;
                        }
                    }
                }
            }
        } else {
            $this->cms_options = array();
        }
    }

    /**
     * check if the current cms type is in the cms_options array
     * @param $idtype
     *
     * @return bolean
     */
    function checkCmsType($idtype) {
        $idtype = strtoupper($idtype);

        if (in_array($idtype, $this->cms_options)) {
            return false;
        } else {
            return true;
        }
    }

}



/**
 * Contenido API - Search Object
 *
 * This object starts a indexed fulltext search
 *
 * TODO:
 * The way to set the search options could be done much more better!
 * The computation of the set of searchable articles should not be treated in this class.
 * It is better to compute the array of searchable articles from the outside and to pass the array of searchable articles as parameter.
 * Avoid foreach loops.
 *
 * Use object with
 *
 * $options = array(
 *     'db' => 'regexp', // use db function regexp
 *     'combine' => 'or' // combine searchwords with or
 * );
 *
 * The range of searchable articles is by default the complete content which is online and not protected.
 *
 * With option 'searchable_articles' you can define your own set of searchable articles.
 * If parameter 'searchable_articles' is set the options 'cat_tree', 'categories', 'articles', 'exclude', 'artspecs',
 * 'protected', 'dontshowofflinearticles' don't have any effect.
 *
 * $options = array(
 *     'db' => 'regexp', // use db function regexp
 *     'combine' => 'or', // combine searchwords with or
 *     'searchable_articles' => array(5, 6, 9, 13)
 * );
 *
 * One can define the range of searchable articles by setting the parameter 'exclude' to false which means the range of categories
 * defined by parameter 'cat_tree' or 'categories' and the range of articles defined by parameter 'articles' is included.
 *
 * $options = array(
 *     'db' => 'regexp',  // use db function regexp
 *     'combine' => 'or',  // combine searchwords with or
 *     'exclude' => false,  // searchrange specified in 'cat_tree', 'categories' and 'articles' is included
 *     'cat_tree' => array(12),  // tree with root 12 included
 *     'categories' => array(100,111),  // categories 100, 111 included
 *     'articles' => array(33),  // article 33 included
 *     'artspecs' => array(2, 3),  // array of article specifications => search only articles with these artspecs
 *     'res_per_page' => 2,  // results per page
 *     'protected' => true);  // do not search articles or articles in categories which are offline or protected
 *     'dontshowofflinearticles' => false  // search offline articles or articles in categories which are offline
 * );
 *
 * You can build the complement of the range of searchable articles by setting the parameter 'exclude' to true which means the range of categories
 * defined by parameter 'cat_tree' or 'categories' and the range of articles defined by parameter 'articles' is excluded from search.
 *
 * $options = array(
 *     'db' => 'regexp',  // use db function regexp
 *     'combine' => 'or',  // combine searchwords with or
 *     'exclude' => true,  // searchrange specified in 'cat_tree', 'categories' and 'articles' is excluded
 *     'cat_tree' => array(12),  // tree with root 12 excluded
 *     'categories' => array(100, 111),  // categories 100, 111 excluded
 *     'articles' => array(33),  // article 33 excluded
 *     'artspecs' => array(2, 3),  // array of article specifications => search only articles with these artspecs
 *     'res_per_page' => 2,  // results per page
 *     'protected' => true);  // do not search articles or articles in categories which are offline or protected
 *     'dontshowofflinearticles' => false  // search offline articles or articles in categories which are offline
 * );
 *
 * $search = new Search($options);
 *
 * $cms_options = array("htmlhead", "html", "head", "text", "imgdescr", "link", "linkdescr");
 * search only in these cms-types
 * $search->setCmsOptions($cms_options);
 *
 * $aSearchResult = $search->searchIndex($searchword, $searchwordex); // start search
 *
 * The search result structure has following form
 * Array (
 *     [20] => Array (
 *         [CMS_HTML] => Array (
 *             [0] => 1
 *             [1] => 1
 *             [2] => 1
 *         )
 *         [keyword] => Array (
 *             [0] => content
 *             [1] => contenido
 *             [2] => wwwcontenidoorg
 *         )
 *         [search] => Array (
 *             [0] => con
 *             [1] => con
 *             [2] => con
 *         )
 *         [occurence] => Array (
 *             [0] => 1
 *             [1] => 5
 *             [2] => 1
 *         )
 *         [similarity] => 60
 *     )
 * )
 *
 * The keys of the array are the article ID's found by search.
 *
 * Searching 'con' matches keywords 'content', 'contenido' and 'wwwcontenidoorg' in article with ID 20 in content type CMS_HTML[1].
 * The search term occurs 7 times.
 * The maximum similarity between searchterm and matching keyword is 60%.
 *
 * with $oSearchResults = new SearchResult($aSearchResult, 10);
 * one can rank and display the results
 *
 * @version 1.0.1
 *
 * @author Willi Man
 * @copyright four for business AG <www.4fb.de>
 */
class Search {

    /**
     * Instance of class Index
     * @var object
     */
    var $index;

    /**
     * configuration data
     * @var array
     */
    var $cfg;

    /**
     * a contenido client
     * @var int
     */
    var $client;

    /**
     * language of a client
     * @var int
     */
    var $lang;

    /**
     * Contenido database object
     * @var object
     */
    var $db;

    /**
     * array of available cms types
     * @var array
     */
    var $cms_type = array();

    /**
     * suffix of available cms types
     * @var array
     */
    var $cms_type_suffix = array();

    /**
     * the search words
     * @var array
     */
    var $search_words = array();

    /**
     * the words which should be excluded from search
     * @var array
     */
    var $search_words_exclude = array();

    /**
     * type of db search
     * like => 'sql like', regexp => 'sql regexp'
     * @var string
     */
    var $search_option;

    /**
     * logical combination of searchwords (and, or)
     * @var string
     */
    var $search_combination;

    /**
     * array of searchable articles
     * @var array
     */
    var $searchable_arts = array();

    /**
     * article specifications
     * @var array
     */
    var $article_specs = array();

    /**
     * If $protected = true => do not search articles which are offline or articles in catgeories which are offline (protected)
     * @var bool
     */
    var $protected;

    /**
     * If $dontshowofflinearticles = false => search offline articles or articles in categories which are offline
     * @var bool
     */
    var $dontshowofflinearticles;

    /**
     * If $exclude = true => the specified search range is excluded from search, otherwise included
     * @var bool
     */
    var $exclude;

    /**
     * Array of article id's with information about cms-types, occurence of keyword/searchword, similarity ...
     * @var array
     */
    var $search_result = array();

    /**
     * Debug option
     * @var bool
     */
    var $bDebug;

    /**
     * Constructor
     *
     * @param array $options
     * $options['db'] 'regexp' => DB search with REGEXP; 'like' => DB search with LIKE; 'exact' => exact match;
     * $options['combine'] 'and', 'or' Combination of search words with AND, OR
     * $options['exclude'] 'true'  => searchrange specified in 'cat_tree', 'categories' and 'articles' is excluded; 'false' => searchrange specified in 'cat_tree', 'categories' and 'articles' is included
     * $options['cat_tree']  e.g. array(8) => The complete tree with root 8 is in/excluded from search
     * $options['categories'] e.g. array(10, 12) => Categories 10, 12 in/excluded
     * $options['articles'] e.g. array(23) => Article 33 in/excluded
     * $options['artspecs'] => e.g. array(2, 3) => search only articles with certain article specifications
     * $options['protected'] 'true' => do not search articles which are offline (locked) or articles in catgeories which are offline (protected)
     * $options['dontshowofflinearticles'] 'false' => search offline articles or articles in categories which are offline
     * $options['searchable_articles'] array of article ID's which should be searchable
     *
     * @return void
     */
    function Search($options, $oDB = false) {
        # TODO: avoid globals
        global $cfg, $lang, $client;

        $this->cfg = $cfg;
        $this->lang = $lang;
        $this->client = $client;
        $this->bDebug = false;
        if ($oDB == false) {
            $this->db = new DB_Contenido;
        } elseif (is_object($oDB)) {
            $this->db = &$oDB;
        }
        $this->index = new Index($oDB);

        $this->cms_type = $this->index->cms_type;
        $this->cms_type_suffix = $this->index->cms_type_suffix;

        $this->search_option = (array_key_exists('db', $options)) ? strtolower($options['db']) : 'regexp';
        $this->search_combination = (array_key_exists('combine', $options)) ? strtolower($options['combine']) : 'or';
        $this->protected = (array_key_exists('protected', $options)) ? $options['protected'] : true;
        $this->dontshowofflinearticles = (array_key_exists('dontshowofflinearticles', $options)) ? $options['dontshowofflinearticles'] : false;
        $this->exclude = (array_key_exists('exclude', $options)) ? $options['exclude'] : true;
        $this->article_specs = (array_key_exists('artspecs', $options) && is_array($options['artspecs'])) ? $options['artspecs'] : array();
        $this->index->setCmsOptions($this->cms_type_suffix);

        if (array_key_exists('searchable_articles', $options) && is_array($options['searchable_articles'])) {
            $this->searchable_arts = $options['searchable_articles'];
        } else {
            $this->searchable_arts = $this->getSearchableArticles($options);
        }

        $this->intMinimumSimilarity = 50; # minimum similarity between searchword and keyword in percent
    }

    /**
     * indexed fulltext search
     * @param string $searchwords The search words
     * @param string $searchwords_exclude The words, which should be excluded from search
     * @return void
     */
    function searchIndex($searchwords, $searchwords_exclude = '') {
        if (strlen(trim($searchwords)) > 0) {
            $this->search_words = $this->stripWords($searchwords);
        } else {
            return false;
        }

        if (strlen(trim($searchwords_exclude)) > 0) {
            $this->search_words_exclude = $this->stripWords($searchwords_exclude);
        }

        $tmp_searchwords = array();
        foreach ($this->search_words as $word) {
            $wordEscaped = Contenido_Security::escapeDB($word, $this->db);
            if ($this->search_option == 'like') {
                $wordEscaped = "'%" . $wordEscaped . "%'";
            } elseif ($this->search_option == 'exact') {
                $wordEscaped = "'" . $wordEscaped . "'";
            }
            $tmp_searchwords[] = $wordEscaped;
        }

        if (count($this->search_words_exclude) > 0) {
            foreach ($this->search_words_exclude as $word) {
                $wordEscaped = Contenido_Security::escapeDB($word, $this->db);
                if ($this->search_option == 'like') {
                    $wordEscaped = "'%" . $wordEscaped . "%'";
                } elseif ($this->search_option == 'exact') {
                    $wordEscaped = "'" . $wordEscaped . "'";
                }
                $tmp_searchwords[] = $wordEscaped;
                $this->search_words[] = $word;
            }
        }

        if ($this->search_option == 'regexp' && !empty($tmp_searchwords)) { // regexp search if searchwords are not empty
            $search_regexp = implode('|', $tmp_searchwords);

            $sql = "SELECT keyword, auto FROM " . $this->cfg['tab']['keywords']
                 . " WHERE idlang = " . Contenido_Security::toInteger($this->lang)
                 . " AND keyword REGEXP '" . $search_regexp . "' ";
        } elseif ($this->search_option == 'like') { // like search
            $search_like = implode(" OR keyword LIKE ", $tmp_searchwords);

            $sql = "SELECT keyword, auto FROM " . $this->cfg['tab']['keywords']
                 . " WHERE idlang = " . Contenido_Security::toInteger($this->lang)
                 . " AND keyword LIKE " . $search_like . " ";
        } elseif ($this->search_option == 'exact') { // exact match
            $search_exact = implode(" OR keyword = ", $tmp_searchwords);

            $sql = "SELECT keyword, auto FROM " . $this->cfg['tab']['keywords']
                 . " WHERE idlang = " . Contenido_Security::toInteger($this->lang)
                 . " AND keyword = " . $search_exact . " ";
        }

        if ($this->bDebug) {
            echo "<pre>$sql</pre>";
        }

        if ($sql) {
	        $this->db->query($sql);
        }

        while ($this->db->next_record()) {
            $aIndexItems = preg_split("/&/", $this->db->f('auto'), -1, PREG_SPLIT_NO_EMPTY);

            if ($this->bDebug) {
                echo "<pre>index " . $this->db->f('auto') . "</pre>";
            }

            $tmp_index = array();
            foreach ($aIndexItems as $string) {
                $tmp_string = preg_replace("/[=\(\)]/", " ", $string);
                $tmp_index[] = preg_split("/\s/", $tmp_string, -1, PREG_SPLIT_NO_EMPTY);
            }

            if ($this->bDebug) {
                echo "<pre>tmp_index " . print_r($tmp_index, true) . "</pre>";
            }

            foreach ($tmp_index as $string) {
                $artid = $string[0];

                if (in_array($artid, $this->searchable_arts)) { // filter nonsearchable articles
                    $cms_place = $string[2];
                    $keyword = $this->db->f('keyword');

                    $percent = 0;
                    $similarity = 0;
                    foreach ($this->search_words as $word) {
                        similar_text($word, $keyword, $percent); // computes similarity between searchword and keyword in percent
                        if ($percent > $similarity) {
                            $similarity = $percent;
                            $searchword = $word;
                        }
                    }

                    $tmp_cmstype = preg_split("/[,]/", $cms_place, -1, PREG_SPLIT_NO_EMPTY);

                    if ($this->bDebug) {
                        echo "<pre>tmp_cmstype " . print_r($tmp_cmstype, true) . "</pre>";
                    }

                    $tmp_cmstype2 = array();
                    foreach ($tmp_cmstype as $type) {
                        $tmp_cmstype2[] = preg_split("/-/", $type, -1, PREG_SPLIT_NO_EMPTY);
                    }

                    if ($this->bDebug) {
                        echo "<pre>tmp_cmstype2 " . print_r($tmp_cmstype2, true) . "</pre>";
                    }

                    foreach ($tmp_cmstype2 as $type) {
                        if (!$this->index->checkCmsType($type[0])) { // search for specified cms-types
                            if ($similarity >= $this->intMinimumSimilarity) { // include article into searchresult set only if similarity between searchword and keyword is big enough
                                $this->search_result[$artid][$type[0]][] = $type[1];
                                $this->search_result[$artid]['keyword'][] = $this->db->f('keyword');
                                $this->search_result[$artid]['search'][] = $searchword;
                                $this->search_result[$artid]['occurence'][] = $string[1];
                                $this->search_result[$artid]['debug_similarity'][] = $percent;
                                if ($similarity > $this->search_result[$artid]['similarity']) {
                                    $this->search_result[$artid]['similarity'] = $similarity;
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($this->search_combination == 'and') { // all search words must appear in the article
            foreach ($this->search_result as $article => $val) {
                if (!count(array_diff($this->search_words, $val['search'])) == 0) {
                    //$this->rank_structure[$article] = $rank[$article];
                    unset($this->search_result[$article]);
                }
            }
        }

        if (count($this->search_words_exclude) > 0) { // search words to be excluded must not appear in article
            foreach ($this->search_result as $article => $val) {
                if (!count(array_intersect($this->search_words_exclude, $val['search'])) == 0) {
                    //$this->rank_structure[$article] = $rank[$article];
                    unset($this->search_result[$article]);
                }
            }
        }

        if ($this->bDebug) {
            echo "<pre>search_result: " . print_r($this->search_result, true) . "</pre>";
            echo "<pre>searchable_arts: ". print_r($this->searchable_arts, true) . "</pre>";
        }

        return $this->search_result;
    }

    /**
     * @param  array  $aCmsOptions  The cms-types (htmlhead, html, ...) which should explicitly be searched
     * @return void
     */
    function setCmsOptions($aCmsOptions) {
        if (is_array($aCmsOptions) && count($aCmsOptions) > 0) {
            $this->index->setCmsOptions($aCmsOptions);
        }
    }

    /**
     * @param  string  $sSearchWords  The search-words
     * @return  array  Array of stripped search-words
     */
    function stripWords($sSearchWords) {
        $sSearchWords = trim(strip_tags(stripslashes($sSearchWords))); // remove backslash and html tags
        $aSearchWords = preg_split("/[\s,]+/", $sSearchWords); // split the phrase by any number of commas or space characters

        $aNewSearchWords = array();

        foreach ($aSearchWords as $word) {
            $word = $this->index->removeSpecialChars(trim(strtolower($word)));
            if (strlen($word) > 1) {
                $aNewSearchWords[] = $word;
            }
        }

        return array_unique($aNewSearchWords);
    }

    /**
     * @param $cat_start Root of a category tree
     * @return Category Tree
     */
    function getSubTree($cat_start) {
        $sql = "SELECT
                B.idcat, B.parentid
            FROM
                " . $this->cfg['tab']['cat_tree'] . " AS A,
                " . $this->cfg['tab']['cat'] . " AS B,
                " . $this->cfg['tab']['cat_lang'] . " AS C
            WHERE
                A.idcat  = B.idcat AND
                B.idcat  = C.idcat AND
                C.idlang = '" . Contenido_Security::toInteger($this->lang) . "' AND
                B.idclient = '" . Contenido_Security::toInteger($this->client) . "'
            ORDER BY
                idtree";

        if ($this->bDebug) {
            echo "<pre>$sql</pre>";
        }

        $this->db->query($sql);

        $aSubCats = array();
        $flag = false;

        while ($this->db->next_record()) {
            if ($this->db->f('parentid') < $cat_start) { // ending part of tree
                $flag = false;
            }

            if ($this->db->f('idcat') == $cat_start) { // starting part of tree
                $flag = true;
            }

            if ($flag == true) {
                $aSubCats[] = $this->db->f('idcat');
            }
        }
        return $aSubCats;
    }

    /**
     * @param  array  $aSearchRange
     * @return Articles in specified search range
     */
    function getSearchableArticles($aSearchRange) {
        $aCatRange = array();
        if (array_key_exists('cat_tree', $aSearchRange) && is_array($aSearchRange['cat_tree'])) {
            if (count($aSearchRange['cat_tree']) > 0) {
                foreach ($aSearchRange['cat_tree'] as $cat) {
                    $aCatRange = array_merge($aCatRange, $this->getSubTree($cat));
                }
            }
        }

        if (array_key_exists('categories', $aSearchRange) && is_array($aSearchRange['categories'])) {
            if (count($aSearchRange['categories']) > 0) {
                $aCatRange = array_merge($aCatRange, $aSearchRange['categories']);
            }
        }

        $aCatRange = array_unique($aCatRange);
        $sCatRange = implode("','", $aCatRange);

        if (array_key_exists('articles', $aSearchRange) && is_array($aSearchRange['articles'])) {
            if (count($aSearchRange['articles']) > 0) {
                $sArtRange = implode("','", $aSearchRange['articles']);
            } else {
                $sArtRange = '';
            }
        }

        $aIdArts = array();

        if ($this->protected == true) {
            $sProtected = " C.public = 1 AND C.visible = 1 AND B.online = 1 ";
        } else {
            if ($this->dontshowofflinearticles == true) {
                $sProtected = " C.visible = 1 AND B.online = 1 ";
            } else {
                $sProtected = " 1 ";
            }
        }

        if ($this->exclude == true) {
            // exclude searchrange
            $sSearchRange = " A.idcat NOT IN ('" . $sCatRange . "') AND B.idart NOT IN ('" . $sArtRange . "') AND ";
        } elseif (strlen($sArtRange) > 0) {
            // include searchrange
            $sSearchRange = " A.idcat IN ('" . $sCatRange . "') AND B.idart IN ('" . $sArtRange . "') AND ";
        } else {
            $sSearchRange = " A.idcat IN ('" . $sCatRange . "') AND ";
        }

        if (count($this->article_specs) > 0) {
            $sArtSpecs = " B.artspec IN ('" . implode("','", $this->article_specs) . "') AND ";
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
                    B.idlang = '" . Contenido_Security::toInteger($this->lang) . "' AND
                    C.idlang = '" . Contenido_Security::toInteger($this->lang) . "' AND
                    A.idart = B.idart AND
                    A.idcat = C.idcat AND
                    " . $sArtSpecs . "
                    " . $sProtected . " ";

        if ($this->bDebug) {
            echo "<pre>$sql</pre>";
        }

        $this->db->query($sql);

        while ($this->db->next_record()) {
            $aIdArts[] = $this->db->f('idart');
        }

        return $aIdArts;
    }

    /**
     * Fetch all article specifications which are online
     * @return array  Array of article specification Ids
     */
    function getArticleSpecifications() {
        $sql = "SELECT idartspec FROM " . $this->cfg['tab']['art_spec']
             . " WHERE client = " . Contenido_Security::toInteger($this->client) . " AND"
             . " lang = " . Contenido_Security::toInteger($this->lang) . " AND online = 1";

        if ($this->bDebug) {
            echo "<pre>$sql</pre>";
        }

        $this->db->query($sql);
        $aArtspec = array();
        while ($this->db->next_record()) {
            $aArtspec[] = $this->db->f('idartspec');
        }
        return $aArtspec;
    }

    /**
     * Set article specification
     * @param  int  $iArtspecId
     * @return void
     */
    function setArticleSpecification($iArtspecId) {
        $this->article_specs[] = $iArtspecId;
    }

    /**
     * Add all article specifications matching name of article specification (client dependent but language independent)
     * @param  string  $sArtSpecName
     * @return void
     */
    function addArticleSpecificationsByName($sArtSpecName) {
        if (empty($sArtSpecName)) {
            return false;
        }

        $sql = "SELECT idartspec FROM" . $this->cfg['tab']['art_spec']
             . " WHERE client = " . Contenido_Security::toInteger($this->client) . " AND"
             . " artspec = '" . Contenido_Security::escapeDB($sArtSpecName, $this->db) . "' ";

        if ($this->bDebug) {
            echo "<pre>$sql</pre>";
        }

        $this->db->query($sql);
        while ($this->db->next_record()) {
            $this->article_specs[] = $this->db->f('idartspec');
        }
    }

}



/**
 * Contenido API - SearchResult Object
 *
 * This object ranks and displays the result of the indexed fulltext search.
 * If you are not comfortable with this API feel free to use your own methods to display the search results.
 * The search result is basically an array with article ID's.
 *
 * If $aSearchResult = $search->searchIndex($searchword, $searchwordex);
 *
 * use object with
 *
 * $oSearchResults = new SearchResult($aSearchResult, 10);
 *
 * $oSearchResults->setReplacement('<span style="color:red">', '</span>'); // html-tags to emphasize the located searchwords
 *
 * $num_res = $oSearchResults->getNumberOfResults();
 * $num_pages = $oSearchResults->getNumberOfPages();
 * $res_page = $oSearchResults->getSearchResultPage(1); // first result page
 * foreach ($res_page as $key => $val) {
 *     $headline = $oSearchResults->getSearchContent($key, 'HTMLHEAD');
 *     $first_headline = $headline[0];
 *     $text = $oSearchResults->getSearchContent($key, 'HTML');
 *     $first_text = $text[0];
 *     $similarity = $oSearchResults->getSimilarity($key);
 *       $iOccurrence = $oSearchResults->getOccurrence($key);
 * }
 *
 * @version 1.0.0
 *
 * @author Willi Man
 * @copyright four for business AG <www.4fb.de>
 *
 */
class SearchResult {

    /**
     * Instance of class Index
     * @var object
     */
    var $index;

    /**
     * configuration settings
     * @var array
     */
    var $cfg;

    /**
     * a contenido client
     * @var int
     */
    var $client;

    /**
     * language of a client
     * @var int
     */
    var $lang;

    /**
     * Contenido database object
     * @var object
     */
    var $db;

    /**
     * Number of results
     * @var int
     */
    var $results;

    /**
     * Number of result pages
     * @var int
     */
    var $pages;

    /**
     * Current result page
     * @var int
     */
    var $result_page;

    /**
     * Results per page to display
     * @var int
     */
    var $result_per_page;

    /**
     * Array of html-tags to emphasize the searchwords
     * @var array
     */
    var $replacement = array();

    /**
     * Array of article id's with ranking information
     * @var array
     */
    var $rank_structure = array();

    /**
     * Array of result-pages with array's of article id's
     * @var array
     */
    var $ordered_search_result = array();

    /**
     * Array of article id's with information about cms-types, occurence of keyword/searchword, similarity ...
     * @var array
     */
    var $search_result = array();

    /**
     * Debug option
     * @var bool
     */
    var $bDebug;

    /**
     * Compute ranking factor for each search result and order the search results by ranking factor
     * NOTE: The ranking factor is the sum of occurences of matching searchterms weighted by similarity (in %) between searchword
     * and matching word in the article.
     * TODO: One can think of more sophisticated ranking strategies. One could use the content type information for example
     * because a matching word in the headline (CMS_HEADLINE[1]) could be weighted more than a matching word in the text (CMS_HTML[1]).
     *
     * @param  array  $aSearchResult  The search result, usually the return value of $search->searchIndex();
     * @param  int  $iResultPerPage
     * @param  DB_Contenido  $oDB
     * @param  bool  $bDebug
     */
    function SearchResult($aSearchResult, $iResultPerPage, $oDB = false, $bDebug = false) {
        # TODO: avoid globals
        global $cfg, $lang, $client;

        $this->cfg = $cfg;
        $this->lang = $lang;
        $this->client = $client;
        $this->bDebug = $bDebug;
        if ($oDB == false) {
            $this->db = new DB_Contenido;
        } elseif (is_object($oDB)) {
            $this->db = &$oDB;
        }
        $this->index = new Index($oDB);

        $this->search_result = $aSearchResult;

        if ($this->bDebug) {
            echo "<pre>search_result: " . print_r($this->search_result, true) . "</pre>";
        }

        $this->result_per_page = $iResultPerPage;
        $this->results = count($this->search_result);

        // Compute ranking factor for each search result
        foreach ($this->search_result as $article => $val) {
            $this->rank_structure[$article] = $this->getOccurrence($article) * ( $this->getSimilarity($article) / 100);
        }

        if ($this->bDebug) {
            echo "<pre>rank_structure: " . print_r($this->rank_structure, true) . "</pre>";
        }

        $this->setOrderedSearchResult($this->rank_structure, $this->result_per_page);
        $this->pages = count($this->ordered_search_result);

        if ($this->bDebug) {
            echo "<pre>ordered_search_result: " . print_r($this->ordered_search_result, true) . "</pre>";
        }
    }

    /**
     * @param  array  $aRankedSearch
     * @param  int  $iResultPerPage
     * @return void
     */
    function setOrderedSearchResult($aRankedSearch, $iResultPerPage) {
        asort($aRankedSearch);

        $aSortedRankedSearch = array_reverse($aRankedSearch, true);

        if (isset($iResultPerPage) && $iResultPerPage > 0) {
            $aSplitResult = array_chunk($aSortedRankedSearch, $iResultPerPage, true);
            $this->ordered_search_result = $aSplitResult;
        } else {
            $this->ordered_search_result[] = $aSortedRankedSearch;
        }
    }

    /**
     * @param  int  $iArtId  Id of an article
     * @param  string  $sCmsType Content type
     * @param  int  $iCmsNr Content type number
     * @return Content of an article, specified by it's content type
     */
    function getContent($iArtId, $sCmsType, $iCmsNr = 0) {
        $article = new Article($iArtId, $this->client, $this->lang);
        return $article->getContent($sCmsType, $iCmsNr);
    }

    /**
     * @param  int  $iArtId  Id of an article
     * @param  string  $sCmsType  Content type
     * @param  int|null  $mCmsNr
     * @return  array  Content of an article in search result, specified by its type
     */
    function getSearchContent($iArtId, $sCmsType, $mCmsNr = NULL) {
        $sCmsType = strtoupper($sCmsType);
        if (strlen($sCmsType) > 0) {
            if (!stristr($sCmsType, 'cms_')) {
                if (in_array($sCmsType, $this->index->cms_type_suffix)) {
                    $sCmsType = 'CMS_' . $sCmsType;
                }
            } else {
                if (!array_key_exists($sCmsType, $this->index->cms_type)) {
                    return array();
                }
            }
        }

        $article = new Article($iArtId, $this->client, $this->lang);
        $content = array();
        if (isset($this->search_result[$iArtId][$sCmsType])) { // if searchword occurs in cms_type
            $search_words = $this->search_result[$iArtId]['search'];
            $search_words = array_unique($search_words);

            $id_type = $this->search_result[$iArtId][$sCmsType];
            $id_type = array_unique($id_type);

            if (isset($mCmsNr) && is_numeric($mCmsNr)) { // get content of cms_type[cms_nr]
                //build consistent escaped string(Timo Trautmann) 2008-04-17
                $cms_content = conHtmlentities(conHtmlEntityDecode(strip_tags($article->getContent($sCmsType, $mCmsNr))));
                if (count($this->replacement) == 2) {
                    foreach ($search_words as $word) {
                        //build consistent escaped string, replace ae ue .. with original html entities (Timo Trautmann) 2008-04-17
                        $word = conHtmlentities(conHtmlEntityDecode($this->index->addSpecialUmlauts($word)));
                        $match = array();
                        preg_match("/$word/i", $cms_content, $match);
                        if (isset($match[0])) {
                            $pattern = $match[0];
                            $replacement = $this->replacement[0] . $pattern . $this->replacement[1];
                            $cms_content = preg_replace("/$pattern/i", $replacement, $cms_content); // emphasize located searchwords
                        }
                    }
                }
                $content[] = htmlspecialchars_decode($cms_content);
            } else { // get content of cms_type[$id], where $id are the cms_type numbers found in search
                foreach ($id_type as $id) {
                    $cms_content = strip_tags($article->getContent($sCmsType, $id));

                    if (count($this->replacement) == 2) {
                        foreach ($search_words as $word) {
                            preg_match("/$word/i", $cms_content, $match);
                            if (isset($match[0])) {
                                $pattern = $match[0];
                                $replacement = $this->replacement[0] . $pattern . $this->replacement[1];
                                $cms_content = preg_replace("/$pattern/i", $replacement, $cms_content); // emphasize located searchwords
                            }
                        }
                    }
                    $content[] = $cms_content;
                }
            }
        } else { // searchword was not found in cms_type
            if (isset($mCmsNr) && is_numeric($mCmsNr)) {
                $content[] = strip_tags($article->getContent($sCmsType, $mCmsNr));
            } else {
                $art_content = $article->getContent($sCmsType);
                if (is_array($art_content) && count($art_content) > 0) {
                    foreach ($art_content as $val) {
                        $content[] = strip_tags($val);
                    }
                }
            }
        }
        return $content;
    }

    /**
     * @param  int  $iPageId
     * @return  array  Artices in page $iPageId
     */
    function getSearchResultPage($iPageId) {
        if (!isset($this->ordered_search_result[$iPageId - 1])) {
            return array();
        }

        $this->result_page = $iPageId;
        return $this->ordered_search_result[$iPageId - 1];
    }

    /**
     * @return  int  Number of result pages
     */
    function getNumberOfPages() {
        return $this->pages;
    }

    /**
     * @return  int  Number of articles in search result
     */
    function getNumberOfResults() {
        return $this->results;
    }

    /**
     * @param  int  $iArtId Id of an article
     * @return float  Similarity between searchword and matching word in article
     */
    function getSimilarity($iArtId) {
        if (!isset($this->search_result[$iArtId])) {
            return 0;
        }

        return $this->search_result[$iArtId]['similarity'];
    }

    /**
     * @param  int  $iArtId Id of an article
     * @return  int  Number of matching searchwords found in article
     */
    function getOccurrence($iArtId) {
        if (!isset($this->search_result[$iArtId])) {
            return 0;
        }

        $aOccurence = $this->search_result[$iArtId]['occurence'];
        $iSumOfOccurence = 0;
        for ($i = 0; $i < count($aOccurence); $i++) {
            $iSumOfOccurence += $aOccurence[$i];
        }

        return $iSumOfOccurence;
    }

    /**
     * @param string $rep1 The opening html-tag to emphasize the searchword e.g. '<b>'
     * @param string $rep2 The closing html-tag e.g. '</b>'
     * @return void
     */
    function setReplacement($rep1, $rep2) {
        if (strlen(trim($rep1)) > 0 && strlen(trim($rep2)) > 0) {
            $this->replacement[] = $rep1;
            $this->replacement[] = $rep2;
        }
    }

    /**
     * @param  int  $iArtId
     * @return  int|null  Category Id
     */
    function getArtCat($iArtId) {
        $sql = "SELECT idcat FROM " . $this->cfg['tab']['cat_art'] . "
                WHERE idart = " . Contenido_Security::toInteger($iArtId) . " ";

        $this->db->query($sql);

        return ($this->db->next_record()) ? $this->db->f('idcat') : null;
    }
}

/**
 * @deprecated
 * @since 2008-07-11
 */
class Search_helper {
    var $oDb = NULL;
    function search_helper($oDb, $lang, $client) {
    }
}

?>