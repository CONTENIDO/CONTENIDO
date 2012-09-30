<?php
/**
 * This file contains the article collector class.
 *
 * @package Core
 * @subpackage Helper
 *
 * @author Dominik Ziegler
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

/**
 * This class contains functions for the article helper in CONTENIDO.
 * The article collector returns you a list of articles, which destination you can choose.
 *
 * You have the ability to limit, sort and filter the article list.
 * You can configure the article collector with an options array, which can include the following configuration.
 * - idcat - category ID
 * - categories - array with multiple category IDs
 * - lang - language ID, active language if ommited
 * - client - client ID, active client if ommited
 * - artspecs - array of article specifications, which should be considered
 * - offline - include offline article in the collection, defaults to false
 * - offlineonly - only list offline articles, defaults to false
 * - start - include start article in the collection, defaults to false
 * - startonly - only list start articles, defaults to false
 * - order - articles will be ordered by this property, defaults to created
 * - direction - order direcion, ASC'or DESC for ascending/descending, defaults to DESC
 * - limit - limit numbers of articles in collection, default to 0 (unlimited)
 *
 * @package Core
 * @subpackage Helper
 */
class cArticleCollector implements SeekableIterator, Countable {
    /**
     * Options for the collector.
     * @var array
     */
    protected $_options = array();

    /**
     * Loaded articles.
     * @var array
     */
    protected $_articles = array();

    /**
     * Start articles of the requested categories.
     * @var array
     */
    protected $_startArticles = array();

    /**
     * Current position for the iterator.
     * @var int
     */
    protected $_currentPosition = 0;

    /**
     * Constructor. If options are defined, the loading process is automatically initiated.
     * @param array $options array with options for the collector (optional, default: empty array)
     * @return void
     */
    public function __construct($options = array()) {
        if (count($options) > 0) {
            $this->setOptions($options);
            $this->loadArticles();
        }
    }

    /**
     * Setter for the collector options. Validates incoming options and sets the default of the missing options.
     * @param array $options array with option
     * @return void
     */
    public function setOptions($options) {
        if (isset($options['idcat']) && !isset($options['categories'])) {
            $options['categories'] = array($options['idcat']);
        }

        if (isset($options['categories']) === false) {
            $options['categories'] = array();
        }

        if (isset($options['lang']) === false) {
            $options['lang'] = cRegistry::getLanguageId();
        }

        if (isset($options['client']) === false) {
            $options['client'] = cRegistry::getClientId();
        }

        if (isset($options['start']) === false) {
            $options['start'] = false;
        }

        if (isset($options['startonly']) === false) {
            $options['startonly'] = false;
        }

        if (isset($options['offline']) === false) {
            $options['offline'] = false;
        }

        if (isset($options['offlineonly']) === false) {
            $options['offlineonly'] = false;
        }

        switch ($options['order']) {
            case 'sortsequence':
                $options['order'] = 'artsort';
                break;

            case 'modificationdate':
                $options['order'] = 'lastmodified';
                break;

            case 'publisheddate':
                $options['order'] = 'published';
                break;

            case 'creationdate':
            default:
                $options['order'] = 'created';
                break;
        }

        if (isset($options['artspecs']) === false) {
            $options['artspecs'] = array();
        }

        if (isset($options['direction']) === false) {
            $options['direction'] = 'DESC';
        }

        if (isset($options['limit']) === false) {
            $options['limit'] = 0;
        }

        $this->_options = $options;
    }

    /**
     * Executes the article search with the given options.
     * @throws cUnexpectedValueException
     * @return void
     */
    public function loadArticles() {
        $this->_articles = array();

        $cfg = cRegistry::getConfig();

        $sqlCat = (count($this->_options['categories']) > 0) ? " idcat IN ('" . implode("','", $this->_options['categories']) . "') AND " : '';

        $db = cRegistry::getDb();
        $sql = "SELECT startidartlang, idcat FROM " . $cfg['tab']['cat_lang'] . " WHERE " . $sqlCat . " idlang=" . $this->_options['lang'];
        $db->query($sql);

        while ($db->next_record()) {
            $startId = $db->f('startidartlang');
            if ($startId > 0) {
                $this->_startArticles[$db->f('idcat')] = $startId;
                if ($this->_options['startonly'] == true) {
                    $this->_articles[] = new cApiArticleLanguage($startId);
                }
            }
        }

        if (count($this->_articles) > 0) {
            return;
        }

        $sqlCat = (count($this->_options['categories']) > 0) ? " c.idcat IN ('" . implode("','", $this->_options['categories']) . "') AND b.idart = c.idart AND " : '';
        $sqlArtSpecs = (count($this->artspecs) > 0) ? " a.artspec IN ('" . implode("','", $this->artspecs) . "') AND " : '';

        $sql = "SELECT DISTINCT a.idartlang FROM "
            . $cfg['tab']['art_lang'] . " AS a, "
            . $cfg['tab']['art'] . " AS b, "
            . $cfg['tab']['cat_art'] . " AS c "
            . " WHERE "
            . $sqlCat
            . $sqlArtSpecs
            . "b.idclient = '" . $this->_options['client'] . "' AND "
            . "a.idlang = '" . $this->_options['lang'] . "' AND "
            . "a.idart = b.idart";

        if ($this->_options['offlineonly'] == true) {
            $sql .= " AND a.online = 0";
        } elseif ($this->_options['offline'] == false) {
            $sql .= " AND a.online = 1";
        }

        $sql .= " ORDER BY a." . $this->_options['order'] . " " . $this->_options['direction'];

        if ((int) $this->_options['limit'] > 0) {
            $sql .= " LIMIT " . $this->_options['limit'];
        }

        $db->query($sql);

        while ($db->next_record()) {
            $artLangId = $db->f('idartlang');

            if ($this->_options['start'] == false) {
                if (in_array($artLangId, $this->_startArticles) === false) {
                    $this->_articles[] = new cApiArticleLanguage($artLangId);
                }
            } else {
                $this->_articles[] = new cApiArticleLanguage($artLangId);
            }
        }
    }

    /**
     * Compatibility method for old ArticleCollection class.
     * Returns the start article of a category. Does work only if one category was requested.
     * @return cApiArticleLanguage
     * @throws cBadMethodCallException
     */
    public function startArticle() {
        if (count($this->_startArticles) != 1) {
            throw new cBadMethodCallException("Can not load start article due to multiple loaded start articles.");
        }

        return new cApiArticleLanguage(current($this->_startArticles));
    }

    /**
     * Compatibility method for old ArticleCollection class.
     * Returns the next article.
     * @return bool|cApiArticleLanguage
     */
    public function nextArticle() {
        $next = $this->current();
        $this->next();

        if ($next instanceof cApiArticleLanguage) {
            return $next;
        }

        return false;
    }

    /**
     * Seeks a specific position in the loaded articles.
     * @param int $position position to load
     * @throws cOutOfBoundsException
     */
    public function seek($position) {
        $this->_currentPosition = $position;

        if ($this->valid() === false) {
            throw new cOutOfBoundsException("Invalid seek position: " . $position);
        }
    }

    /**
     * Method "rewind" of the implemented iterator.
     * @return void
     */
    public function rewind() {
        $this->_currentPosition = 0;
    }

    /**
     * Method "current" of the implemented iterator.
     * @return mixed
     */
    public function current() {
        return $this->_articles[$this->_currentPosition];
    }

    /**
     * Method "key" of the implemented iterator.
     * @return int|mixed
     */
    public function key() {
        return $this->_currentPosition;
    }

    /**
     * Method "next" of the implemented iterator.
     * @return void
     */
    public function next() {
        ++$this->_currentPosition;
    }

    /**
     * Method "valid" of the implemented iterator.
     * @return bool
     */
    public function valid() {
        return isset($this->_articles[$this->_currentPosition]);
    }

    /**
     * Method "count" of the implemented Countable interface.
     * Returns the amount of all loaded articles.
     * @return int
     */
    public function count() {
        return count($this->_articles);
    }
}