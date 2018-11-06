<?php

/**
 * This file contains the base class for building search indices.
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
 * Array (
 *      [CMS_HTMLHEAD] => Array (
 *          [1] => Herzlich Willkommen...
 *          [2] => ...auf Ihrer Website!
 *      )
 *      [CMS_HTML] => Array (
 *          [1] => Die Inhalte auf dieser Website ...
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
    protected $_keycode = array();

    /**
     * list of keywords of an article
     *
     * @var array
     */
    protected $_keywords = array();

    /**
     * words, which should not be indexed
     *
     * @var array
     */
    protected $_stopwords = array();

    /**
     * keywords of an article stored in the DB
     *
     * @var array
     */
    protected $_keywordsOld = array();

    /**
     * keywords to be deleted
     *
     * @var array
     */
    protected $_keywordsDel = array();

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
     * suffix of all available cms types
     *
     * @var array
     */
    protected $_cmsTypeSuffix = array();

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
     * @param int    $idart
     *                            Article Id
     * @param array  $aContent
     *                            The complete content of an article specified by its content types.
     *                            It looks like:
     *                            Array (
     *                            [CMS_HTMLHEAD] => Array (
     *                            [1] => Herzlich Willkommen...
     *                            [2] => ...auf Ihrer Website!
     *                            )
     *                            [CMS_HTML] => Array (
     *                            [1] => Die Inhalte auf dieser Website ...
     *                            )
     *                            )
     * @param string $place       [optional]
     *                            The field where to store the index information in db.
     * @param array  $cms_options [optional]
     *                            One can specify explicitly cms types which should not be indexed.
     * @param array  $aStopwords  [optional]
     *                            Array with words which should not be indexed.
     *
     * @throws cInvalidArgumentException
     * @throws cDbException
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
                        if (cString::getStringLength($code) > 0) {
                            $code = conHtmlEntityDecode($code);
                        }
                        $this->_debug('code', $code);

                        // split content by any number of commas, space
                        // characters or hyphens
                        $tmp_keys = mb_split('[\s,-]+', trim($code));
                        $this->_debug('tmp_keys', $tmp_keys);

                        foreach ($tmp_keys as $value) {
                            // index terms are stored with lower case
                            // $value = strtolower($value);

                            $value = conHtmlentities($value);
                            $value = trim(cString::toLowerCase($value));
                            $value = conHtmlEntityDecode($value);

                            if (!in_array($value, $this->_stopwords)) {
                                // eliminate stopwords
                                $value = $this->removeSpecialChars($value);

                                if (cString::getStringLength($value) > 1) {
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
     * Generate index_string from index structure and save keywords.
     * The index_string looks like "&12=2(CMS_HTMLHEAD-1,CMS_HTML-1)".
     *
     * @throws cInvalidArgumentException
     * @throws cDbException
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
     * If keywords don't occur in the article anymore,
     * update index_string and delete keyword if necessary.
     *
     * @throws cInvalidArgumentException
     * @throws cDbException
     */
    public function deleteKeywords() {
        foreach ($this->_keywordsDel as $key_del) {
            $index_string = preg_replace("/&$this->idart=[0-9]+\([\w-,]+\)/", "", $this->_keywordsOld[$key_del]);

            if (cString::getStringLength($index_string) == 0) {
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
     * Get the keywords of an article.
     *
     * @throws cInvalidArgumentException
     * @throws cDbException
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
     * Remove special characters from index term.
     *
     * @param string $key
     *         Keyword
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
     * @param string $key
     *         Keyword
     * @return string
     */
    public function addSpecialUmlauts($key) {
        $key = conHtmlentities($key, NULL, cRegistry::getEncoding());
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
        $sql = "SELECT type, idtype FROM " . $this->cfg['tab']['type'] . ' ';
        $this->_debug('sql', $sql);
        $this->db->query($sql);
        while ($this->db->nextRecord()) {
            $this->_cmsType[$this->db->f('type')] = $this->db->f('idtype');
            $this->_cmsTypeSuffix[$this->db->f('idtype')] = cString::getPartOfString($this->db->f('type'), 4, cString::getStringLength($this->db->f('type')));
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
            $this->_cmsOptions = array();
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

        return (count($this->_cmsOptions) === 0 || in_array($idtype, $this->_cmsOptions)) ? false : true;
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
}
