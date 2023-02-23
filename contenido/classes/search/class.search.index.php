<?php

/**
 * This file contains the base class for building search indices.
 *
 * @package Core
 * @subpackage Frontend_Search
 * @author Willi Man
 * @copyright four for business AG <www.4fb.de>
 * @license https://www.contenido.org/license/LIZENZ.txt
 * @link https://www.4fb.de
 * @link https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

cInclude('includes', 'functions.encoding.php');

/**
 * CONTENIDO API - Search Index Object.
 *
 * This object creates an index of an article.
 *
 * Create object where $db is the global CONTENIDO database object.
 *
 * $oIndex = new SearchIndex($db);
 *
 * Start indexing where $aContent is the complete content of an article
 * specified by its content types.
 *
 * $oIndex->start($idart, $aContent);
 *
 * It looks like:
 * <pre>
 * [
 *     [CMS_HTMLHEAD] => [
 *         [1] => 'Herzlich Willkommen...',
 *         [2] => '...auf Ihrer Website!',
 *     ],
 *     [CMS_HTML] => [
 *         [1] => 'Die Inhalte auf dieser Website ...',
 *         ...
 *     ],
 *     ...
 * ]
 * </pre>
 *
 * The index for keyword 'willkommen' would look like
 * '&12=1(CMS_HTMLHEAD-1)' which means the keyword 'willkommen' occurs
 * 1 times in article with articleId 12 and content type CMS_HTMLHEAD[1].
 *
 * TODO: The basic idea of the indexing process is to take the complete
 * content of an article and to generate normalized index terms from the
 * content and to store a specific index structure in the relation
 * 'con_keywords'.
 *
 * To take the complete content is not very flexible. It would be better
 * to differentiate by specific content types or by any content.
 *
 * The &, =, () and - seperated string is not easy to parse to compute
 * the search result set.
 *
 * It would be a better idea (and a lot of work) to extend the relation
 * 'con_keywords' to store keywords by articleId (or content source
 * identifier) and content type.
 *
 * The functions removeSpecialChars, setStopwords, setContentTypes and
 * setCmsOptions should be sourced out into a new helper-class.
 *
 * Keep in mind that class Search and SearchResult uses an instance of
 * object Index.
 *
 * @package Core
 * @subpackage Frontend_Search
 */
class cSearchIndex extends cSearchBaseAbstract {

    /**
     * content of the cms-types of an article
     *
     * @var array
     */
    protected $_keycode = [];

    /**
     * list of keywords of an article
     *
     * @var array
     */
    protected $_keywords = [];

    /**
     * words, which should not be indexed
     *
     * @var array
     */
    protected $_stopwords = [];

    /**
     * keywords of an article stored in the DB
     *
     * @var array
     */
    protected $_keywordsOld = [];

    /**
     * keywords to be deleted
     *
     * @var array
     */
    protected $_keywordsDel = [];

    /**
     * 'auto' or 'self'
     *
     * The field 'auto' in table con_keywords is used for automatic indexing.
     * The value is a string like "&12=2(CMS_HTMLHEAD-1,CMS_HTML-1)",
     * which means a keyword occurs 2 times in article with $idart 12
     * and can be found in CMS_HTMLHEAD[1] and CMS_HTML[1].
     *
     * The field 'self' can be used in the article properties to index
     * the article manually.
     *
     * @var string
     */
    protected $_place;

    /**
     * array of cms types
     *
     * @var array
     */
    protected $_cmsOptions = [];

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
     * linkdescr - Link description
     * swf - Upload id of the element
     * etc.
     *
     * @var array
     */
    protected $_cmsType = [];

    /**
     * suffix of all available cms types
     *
     * @var array
     */
    protected $_cmsTypeSuffix = [];

    /**
     *
     * @var int
     */
    protected $idart;

    /**
     * Constructor to create an instance of this class.
     *
     * Set object properties.
     *
     * @param cDb $db [optional]
     *                CONTENIDO database object
     *
     * @throws cDbException
     * @throws cInvalidArgumentException
     */
    public function __construct($db = NULL) {
        parent::__construct($db);

        $this->setContentTypes();
    }

    /**
     * Start indexing the article.
     *
     * @param int    $idart             Article Id
     * @param array  $aContent          The complete content of an article specified by its content types.
     *                                  It looks like:
     *                                  [
     *                                  [CMS_HTMLHEAD] => [
     *                                  [1] => Herzlich Willkommen...
     *                                  [2] => ...auf Ihrer Website!
     *                                  ]
     *                                  [CMS_HTML] => [
     *                                  [1] => Die Inhalte auf dieser Website ...
     *                                  ]
     *                                  ]
     * @param string $place             [optional] The field where to store the index information in db.
     * @param array  $cms_options       [optional] One can specify explicitly cms types which should not be indexed.
     * @param array  $aStopwords        [optional] Array with words which should not be indexed.
     *
     * @throws cInvalidArgumentException|cDbException
     */
    public function start($idart, $aContent, $place = 'auto', $cms_options = [], $aStopwords = []) {
        if (!is_int((int) $idart) || $idart < 0) {
            return;
        } else {
            $this->idart = $idart;
        }

        $this->_place = $place;
        $this->_keycode = $aContent;
        $this->addTitle();
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
     * Adds the title and pagetitle of the article to the index.
     *
     * @return void
     * @throws cDbException
     */
    public function addTitle() {
        $sql = "SELECT `title`, `pagetitle` FROM `%s` WHERE `idart` = %d AND `idlang` = %d";
        $this->db->query($sql, cRegistry::getDbTableName('art_lang'), $this->idart, $this->lang);
        if ($this->db->nextRecord()) {
            $title = $this->db->f('title') . ' ' . $this->db->f('pagetitle');
            $firstItemKey = cArray::getFirstKey($this->_keycode['CMS_HTML'] ?? []);
            if ($firstItemKey) {
                $this->_keycode['CMS_HTML'][$firstItemKey] .= ' ' . $title;
            }
        }
    }

    /**
     * For each cms-type create index structure.
     *
     * It looks like:
     * Array (
     *     [die] => CMS_HTML-1
     *     [inhalte] => CMS_HTML-1
     *     [auf] => CMS_HTML-1 CMS_HTMLHEAD-2
     *     [dieser] => CMS_HTML-1
     *     [website] => CMS_HTML-1 CMS_HTML-1 CMS_HTMLHEAD-2
     * )
     *
     * @throws cInvalidArgumentException
     */
    public function createKeywords() {
        // Create only keycodes, if some are available
        if (is_array($this->_keycode)) {
            foreach ($this->_keycode as $idtype => $data) {
                if ($this->checkCmsType($idtype)) {
                    foreach ($data as $typeid => $code) {
                        $tmp_keys = $this->_splitCodeToKeywords($code);

                        foreach ($tmp_keys as $value) {
                            // index terms are stored with lower case
                            $value = conHtmlentities($value);
                            $value = trim(cString::toLowerCase($value));
                            $value = conHtmlEntityDecode($value);

                            if (!in_array($value, $this->_stopwords)) {
                                // eliminate stopwords
                                $value = $this->removeSpecialChars($value);

                                if (cString::getStringLength($value) > 1) {
                                    // do not index single characters
                                    $this->_keywords[$value] = ($this->_keywords[$value] ?? '') . $idtype . '-' . $typeid . ' ';
                                }
                            }
                        }
                    }
                }
            }
        }

        $this->_debug('createKeywords() keywords', $this->_keywords);
    }

    /**
     * Generate index_string from index structure and save keywords.
     * The index_string looks like "&12=2(CMS_HTMLHEAD-1,CMS_HTML-1)".
     *
     * @throws cInvalidArgumentException
     * @throws cDbException
     */
    public function saveKeywords() {
        $tabKeywords = cRegistry::getDbTableName('keywords');

        foreach ($this->_keywords as $keyword => $count) {
            $tmp_count = preg_split('/[\s]/', trim($count));
            $this->_debug('tmp_count', $tmp_count);

            $occurrence = count($tmp_count);
            $tmp_count = array_unique($tmp_count);
            $cms_types = implode(',', $tmp_count);
            $index_string = '&' . $this->idart . '=' . $occurrence . '(' . $cms_types . ')';

            if (!array_key_exists($keyword, $this->_keywordsOld)) {
                // if keyword is new, save index information
                $sql   = "INSERT INTO `%s` (`keyword`, `%s`, `idlang`) VALUES ('%s', '%s', %d)";
                $sql = $this->db->prepare($sql, $tabKeywords, $this->_place, $keyword, $index_string, $this->lang);
            } else {
                // if keyword already exists, create new index_string
                if (preg_match("/&$this->idart=/", $this->_keywordsOld[$keyword])) {
                    $index_string = preg_replace("/&$this->idart=[0-9]+\([\w\-,]+\)/", $index_string, $this->_keywordsOld[$keyword]);
                } else {
                    $index_string = $this->_keywordsOld[$keyword] . $index_string;
                }

                $sql = "UPDATE `%s` SET `%s` = '%s' WHERE `idlang` = %d AND `keyword` = '%s'";
                $sql = $this->db->prepare($sql, $tabKeywords, $this->_place, $index_string, $this->lang, $keyword);
            }
            $this->_debug('sql', $sql);
            $this->db->query($sql);
        }
    }

    /**
     * If keywords don't occur in the article anymore,
     * update index_string and delete keyword if necessary.
     *
     * @throws cInvalidArgumentException
     * @throws cDbException
     */
    public function deleteKeywords() {
        $tabKeywords = cRegistry::getDbTableName('keywords');
        foreach ($this->_keywordsDel as $key_del) {
            $index_string = preg_replace("/&$this->idart=[0-9]+\([\w\-,]+\)/", "", $this->_keywordsOld[$key_del]);

            if (cString::getStringLength($index_string) == 0) {
                // Keyword is not referenced by any article
                $sql = "DELETE FROM `%s` WHERE `idlang` = %d AND `keyword` = '%s'";
                $sql = $this->db->prepare($sql, $tabKeywords, $this->lang, $key_del);
            } else {
                $sql = "UPDATE `%s` SET `%s` = '%s' WHERE `idlang` = %d AND `keyword` = '%s'";
                $sql = $this->db->prepare($sql, $tabKeywords, $this->_place, $index_string, $this->lang, $key_del);
            }
            $this->_debug('sql', $sql);
            $this->db->query($sql);
        }
    }

    /**
     * Get the keywords of an article.
     *
     * @throws cInvalidArgumentException
     * @throws cDbException
     */
    public function getKeywords() {
        $keywords = array_map([
            $this->db, 'escape'
        ], array_keys($this->_keywords));
        $keywords = implode("','", $keywords);

        $sql = "-- cSearchIndex->getKeywords()
                SELECT
                    `keyword`, `auto`, `self`
                FROM
                    `%s`
                WHERE
                    `idlang` = %d AND
                    (`keyword` IN ('{KEYWORDS}') OR `%s` REGEXP '&%d=')";

        // Prepare sql without keywords, we don't want any strings in keywords
        // being interpreted as specifiers
        $sql = $this->db->prepare(
            $sql, cRegistry::getDbTableName('keywords'), $this->lang, $this->_place, $this->idart
        );
        $sql = str_replace('{KEYWORDS}', $keywords, $sql);
        $this->_debug('sql', $sql);

        $this->db->query($sql);

        $place = $this->_place;

        while ($this->db->nextRecord()) {
            $this->_keywordsOld[$this->db->f('keyword')] = $this->db->f($place);
        }
    }

    /**
     * Remove special characters from index term.
     *
     * @param string $key
     *         Keyword
     * @return array|string|string[]
     */
    public function removeSpecialChars($key) {
        $aSpecialChars = [
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
        ];

        // for ($i = 127; $i < 192; $i++) {
        // some other special characters
        // $aSpecialChars[] = chr($i);
        // }

        // TODO: The transformation of accented characters must depend
        // on the selected encoding of the language of a client and
        // should not be treated in this method.
        // modified 2007-10-01, H. Librenz - added as hotfix for encoding
        // problems (doesn't find any words with umlaut vowels in it
        // since you turn on UTF-8 as language encoding)
        $sEncoding = cRegistry::getEncoding();

        if (cString::toLowerCase($sEncoding) != 'iso-8859-2') {
            $key = conHtmlentities($key, NULL, $sEncoding);
        } else {
            $key = htmlentities_iso88592($key);
        }

        // $aUmlautMap = [
        // '&Uuml;' => 'ue',
        // '&uuml;' => 'ue',
        // '&Auml;' => 'ae',
        // '&auml;' => 'ae',
        // '&Ouml;' => 'oe',
        // '&ouml;' => 'oe',
        // '&szlig;' => 'ss'
        // ];

        // foreach ($aUmlautMap as $sUmlaut => $sMapped) {
        // $key = str_replace($sUmlaut, $sMapped, $key);
        // }

        $key = conHtmlEntityDecode($key);
        return str_replace($aSpecialChars, '', $key);
    }

    /**
     *
     * @param string $key
     *         Keyword
     * @return string
     */
    public function addSpecialUmlauts($key) {
        $key = conHtmlentities($key, NULL, cRegistry::getEncoding());
        $aUmlautMap = [
            'Ue' => '&Uuml;',
            'ue' => '&uuml;',
            'Ae' => '&Auml;',
            'ae' => '&auml;',
            'Oe' => '&Ouml;',
            'oe' => '&ouml;',
            'ss' => '&szlig;'
        ];

        foreach ($aUmlautMap as $sUmlaut => $sMapped) {
            $key = str_replace($sUmlaut, $sMapped, $key);
        }

        return conHtmlEntityDecode($key);
    }

    /**
     * Set the array of stopwords which should not be indexed.
     *
     * @param array $aStopwords
     */
    public function setStopwords($aStopwords) {
        if (is_array($aStopwords) && count($aStopwords) > 0) {
            $this->_stopwords = $aStopwords;
        }
    }

    /**
     * Set the cms types.
     *
     * @throws cInvalidArgumentException
     * @throws cDbException
     */
    public function setContentTypes() {
        $typeColl = new cApiTypeCollection();
        $typeColl->addResultField('type');
        $typeColl->query();
        foreach ($typeColl->fetchTable(['idtype' => 'idtype', 'type' => 'type']) as $entry) {
            $idType = cSecurity::toInteger($entry['idtype']);
            $this->_cmsType[$idType] = $entry['idtype'];
            $this->_cmsTypeSuffix[$idType] = cString::getPartOfString(
                $entry['type'], 4, cString::getStringLength($entry['type'])
            );
        }
    }

    /**
     * Set the cms_options array of cms types which should be treated
     * special.
     *
     * @param mixed $cms_options
     */
    public function setCmsOptions($cms_options) {
        if (is_array($cms_options) && count($cms_options) > 0) {
            foreach ($cms_options as $opt) {
                $opt = cString::toUpperCase($opt);

                if (cString::getStringLength($opt) > 0) {
                    if (!cString::findFirstOccurrenceCI($opt, 'cms_')) {
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
            $this->_cmsOptions = [];
        }
    }

    /**
     * Check if the requested content type should be indexed (false) or
     * not (true).
     *
     * @param string $idtype
     * @return bool
     */
    public function checkCmsType($idtype) {
        $idtype = cString::toUpperCase($idtype);

        // Do not index CMS_RAW
        if ($idtype == "CMS_RAW") {
            return true;
        }

        return !((count($this->_cmsOptions) === 0 || in_array($idtype, $this->_cmsOptions)));
    }

    /**
     * Returns the property _cmsType.
     *
     * @return array
     */
    public function getCmsType() {
        return $this->_cmsType;
    }

    /**
     * Returns the property _cmsTypeSuffix.
     *
     * @return array
     */
    public function getCmsTypeSuffix() {
        return $this->_cmsTypeSuffix;
    }

    /**
     * Cleans the code from HTML markup and creates a list of
     * keywords that can be indexed.
     *
     * @param string $code
     *
     * @return string[] List of keyword to index
     * @throws cInvalidArgumentException
     */
    protected function _splitCodeToKeywords($code) {
        $this->_debug('code', $code);

        // Remove backslash
        $code = stripslashes($code);

        // Replace HTML line breaks (<br>, <br/>, <br />, etc.) with newlines
        $code = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $code);

        // Remove HTML tags
        $code = strip_tags($code);
        if (cString::getStringLength($code) > 0) {
            $code = conHtmlEntityDecode($code);
        }
        $this->_debug('code', $code);

        // Split content by any number of commas, space characters
        $keywords = mb_split('[\s,]+', trim($code));
        if (!is_array($keywords)) {
            return [];
        }

        // Split the keys also by hyphens, we want to index words with
        // and without hypens
        $keywords2 = array_map(function($item) {
            return mb_split('[-]+', $item);
        }, $keywords);
        $keywords2 = array_filter($keywords2, function($item) {
            return count($item) > 1;
        });

        // Merge both key lists and make the result unique
        foreach ($keywords2 as $entries) {
            $keywords = array_merge($keywords, $entries);
        }
        $keywords = array_unique($keywords);

        $this->_debug('_splitCodeToKeywords() keywords', $keywords);

        return $keywords;
    }

}
