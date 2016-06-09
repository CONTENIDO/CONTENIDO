<?php
/**
 * This file contains the array utility class.
 *
 * @package    Core
 * @subpackage Util
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * The
 * article collector returns you a list of articles, which destination you can
 * choose.
 * You have the ability to limit, sort and filter the article list.
 *
 * You can configure the article collector with an options array, which can include the
 * following configuration.
 *
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
 * - direction - order direction, ASC or DESC for ascending/descending, defaults to DESC
 * - limit - limit numbers of articles in collection, default to 0 (unlimited)
 *
 * TODO: Use generic DB instead of SQL queries
 *
 * @package Core
 * @subpackage Util
 */
class cArticleCollector implements SeekableIterator, Countable {

    /**
     * Options for the collector.
     *
     * @var array
     */
    protected $_options = array();

    /**
     * Loaded articles.
     *
     * @var array
     */
    protected $_articles = array();

    /**
     * Total paging data.
     *
     * @var array
     */
    protected $_pages = array();

    /**
     * Start articles of the requested categories.
     *
     * @var array
     */
    protected $_startArticles = array();

    /**
     * Current position for the iterator.
     *
     * @var int
     */
    protected $_currentPosition = 0;

    /**
     * Constructor to create an instance of this class.
     *
     * If options are defined, the loading process is automatically
     * initiated.
     *
     * @param array $options [optional, default: empty array]
     *         array with options for the collector
     */
    public function __construct($options = array()) {
        $this->setOptions($options);
        $this->loadArticles();
    }

    /**
     * Setter for the collector options. Validates incoming options and sets the
     * default of the missing options.
     *
     * @param array $options
     *         array with option
     */
    public function setOptions($options) {
        if (isset($options['idcat']) && !isset($options['categories'])) {
            $options['categories'] = array(
                    $options['idcat']
            );
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

            case 'title':
                $options['order'] = 'title';
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
     *
     * @throws cUnexpectedValueException
     */
    public function loadArticles() {
        $this->_articles = array();

        $cfg = cRegistry::getConfig();

        $sqlCatLang = (count($this->_options['categories']) > 0) ? " idcat IN ('" . implode("','", $this->_options['categories']) . "') AND " : '';

        $db = cRegistry::getDb();
        $sql = "SELECT startidartlang, idcat FROM " . $cfg['tab']['cat_lang'] . " WHERE " . $sqlCatLang . " idlang=" . $this->_options['lang'];
        $db->query($sql);

        while ($db->nextRecord()) {
            $startId = $db->f('startidartlang');
            if ($startId > 0) {
                $this->_startArticles[$db->f('idcat')] = $startId;
            }
        }

        // This sql-line uses cat_art table with alias c. If no categories found, it writes only "WHERE" into sql-query
        $sqlCat = (count($this->_options['categories']) > 0) ? ", " . $cfg['tab']['cat_art'] . " AS c WHERE c.idcat IN ('" . implode("','", $this->_options['categories']) . "') AND b.idart = c.idart AND " : ' WHERE ';

        $sqlArtSpecs = (count($this->_options['artspecs']) > 0) ? " a.artspec IN ('" . implode("','", $this->_options['artspecs']) . "') AND " : '';

        if (count($this->_startArticles) > 0) {
            if ($this->_options['start'] == false) {
                $sqlStartArticles = "a.idartlang NOT IN ('" . implode("','", $this->_startArticles) . "') AND ";
            }

            if ($this->_options['startonly'] == true) {
                $sqlStartArticles = "a.idartlang IN ('" . implode("','", $this->_startArticles) . "') AND ";
            }
        }

        if ($this->_options['startonly'] == true && count($this->_startArticles) == 0) {
            return;
        }

        $sql = "SELECT DISTINCT a.idartlang FROM " . $cfg['tab']['art_lang'] . " AS a, ";
        $sql .= $cfg['tab']['art'] . " AS b";
        $sql .= $sqlCat . $sqlStartArticles . $sqlArtSpecs . "b.idclient = '" . $this->_options['client'] . "' AND ";
        $sql .= "a.idlang = '" . $this->_options['lang'] . "' AND " . "a.idart = b.idart";

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

        while ($db->nextRecord()) {
            $artLangId = $db->f('idartlang');
            $this->_articles[] = new cApiArticleLanguage($artLangId);
        }

        // Execute cec hook
        cApiCecHook::execute('Contenido.ArticleCollector.Articles', array(
            'idart' => cRegistry::getArticleId(),
            'articles' => $this->_articles
        ));
    }

    /**
     * Compatibility method for old ArticleCollection class. Returns the start
     * article of a category. Does work only if one category was requested.
     *
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
     * Compatibility method for old ArticleCollection class. Returns the next
     * article.
     *
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
     * Compatibility method for old ArticleCollection. Split the article results
     * into pages of a given size.
     * Example: Article Collection with 5 articles
     * [0] => 250 [1] => 251 [2] => 253 [3] => 254 [4] => 255
     * $collection->setResultPerPage(2)
     * Would split the results into 3 pages
     * [0] => [0] => 250 [1] => 251 [1] => [0] => 253 [1] => 254 [2] => [0] =>
     * 255
     * A page can be selected with $collection->setPage(int page)
     *
     * @param int $resPerPage
     */
    public function setResultPerPage($resPerPage) {
        if ($resPerPage > 0) {
            if (is_array($this->_articles)) {
                $this->_pages = array_chunk($this->_articles, $resPerPage);
            } else {
                $this->_pages = array();
            }
        }
    }

    /**
     * Compatibility method for old ArticleCollection. Select a page if the
     * results was divided before.
     * $collection->setResultPerPage(2); $collection->setPage(1);
     * // Iterate through all articles of page two while ($art =
     * $collection->nextArticle()) { ... }
     *
     * @param int $page
     *         The page of the article collection
     */
    public function setPage($page) {
        if (is_array($this->_pages[$page])) {
            $this->_articles = $this->_pages[$page];
        }
    }

    /**
     * Seeks a specific position in the loaded articles.
     *
     * @param int $position
     *         position to load
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
     */
    public function rewind() {
        $this->_currentPosition = 0;
    }

    /**
     * Method "current" of the implemented iterator.
     *
     * @return mixed
     */
    public function current() {
        return $this->_articles[$this->_currentPosition];
    }

    /**
     * Method "key" of the implemented iterator.
     *
     * @return int
     */
    public function key() {
        return $this->_currentPosition;
    }

    /**
     * Method "next" of the implemented iterator.
     */
    public function next() {
        ++$this->_currentPosition;
    }

    /**
     * Method "valid" of the implemented iterator.
     *
     * @return bool
     */
    public function valid() {
        return isset($this->_articles[$this->_currentPosition]);
    }

    /**
     * Method "count" of the implemented Countable interface. Returns the amount
     * of all loaded articles.
     *
     * @return int
     */
    public function count() {
        return count($this->_articles);
    }

}