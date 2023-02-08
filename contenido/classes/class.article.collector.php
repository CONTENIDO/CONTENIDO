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
 * - idcat - (int) Category ID
 * - categories - (array) Array with multiple category IDs
 * - lang - (int) Language ID, active language if omitted
 * - client - (int) Client ID, active client if omitted
 * - artspecs - (array) Array of article specifications, which should be considered
 * - offline - (bool) Include offline article in the collection, defaults to false
 * - offlineonly - (bool) Only list offline articles, defaults to false
 * - start - (bool) Include start article in the collection, defaults to false
 * - startonly - (bool) Only list start articles, defaults to false
 * - order - (string) Articles will be ordered by this property, defaults to created
 * - direction - (string) Order direction, ASC or DESC for ascending/descending, defaults to DESC
 * - limit - (int) Limit numbers of articles in collection, default to 0 (unlimited)
 * - offset - (int) The offset of the first row to return
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
    protected $_options = [];

    /**
     * Loaded articles.
     *
     * @var array
     */
    protected $_articles = [];

    /**
     * Total paging data.
     *
     * @var array
     */
    protected $_pages = [];

    /**
     * Start articles of the requested categories.
     *
     * @var array
     */
    protected $_startArticles = [];

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
     *                       array with options for the collector
     *
     * @throws cDbException
     */
    public function __construct($options = []) {
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
            $options['categories'] = [
                $options['idcat']
            ];
        }

        $options['categories'] = $options['categories'] ?? [];

        $options['lang'] = cSecurity::toInteger($options['lang'] ?? cRegistry::getLanguageId());

        $options['client'] = cSecurity::toInteger($options['client'] ?? cRegistry::getClientId());

        $options['start'] = cSecurity::toBoolean($options['start'] ?? '0');

        $options['startonly'] = cSecurity::toBoolean($options['startonly'] ?? '0');

        $options['offline'] = cSecurity::toBoolean($options['offline'] ?? '0');

        $options['offlineonly'] = cSecurity::toBoolean($options['offlineonly'] ?? '0');

        $order = $options['order'] ?? '';
        switch ($order) {
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

        $options['artspecs'] = $options['artspecs'] ?? [];

        $options['direction'] = cString::toUpperCase($options['direction'] ?? 'DESC');
        if (!in_array($options['direction'], ['ASC', 'DESC'])) {
            $options['direction'] = 'DESC';
        }

        $options['limit'] = cSecurity::toInteger($options['limit'] ?? '0');
        $options['offset'] = cSecurity::toInteger($options['offset'] ?? '0');

        $this->_options = $options;
    }

    /**
     * Executes the article search with the given options.
     *
     * @throws cDbException
     */
    public function loadArticles() {
        $this->_articles = [];

        // Collect start articles
        $this->_startArticles = $this->_fetchStartArticles();
        if ($this->_options['startonly'] == true && count($this->_startArticles) == 0) {
            return;
        }

        // This sql-line uses cat_art table with alias c. If no categories found, it writes only "WHERE" into sql-query
        if ((count($this->_options['categories']) > 0)) {
            $tabCatArt = cRegistry::getDbTableName('cat_art');
            $sqlCat = ", " . $tabCatArt . " AS c WHERE c.idcat IN ('" . implode("','", $this->_options['categories']) . "') AND b.idart = c.idart AND ";
        } else {
            $sqlCat = ' WHERE ';
        }

        $sqlArtSpecs = (count($this->_options['artspecs']) > 0) ? " a.artspec IN ('" . implode("','", $this->_options['artspecs']) . "') AND " : '';

        $sqlStartArticles = '';
        if (count($this->_startArticles) > 0) {
            if ($this->_options['start'] == false) {
                $sqlStartArticles = "a.idartlang NOT IN ('" . implode("','", $this->_startArticles) . "') AND ";
            }

            if ($this->_options['startonly'] == true) {
                $sqlStartArticles = "a.idartlang IN ('" . implode("','", $this->_startArticles) . "') AND ";
            }
        }

        $tabArt = cRegistry::getDbTableName('art');
        $tabArtLang = cRegistry::getDbTableName('art_lang');

        $sql = "SELECT DISTINCT a.idartlang FROM " . $tabArtLang . " AS a, ";
        $sql .= $tabArt . " AS b";
        $sql .= $sqlCat . $sqlStartArticles . $sqlArtSpecs . "b.idclient = '" . $this->_options['client'] . "' AND ";
        $sql .= "a.idlang = '" . $this->_options['lang'] . "' AND " . "a.idart = b.idart";

        if ($this->_options['offlineonly'] == true) {
            $sql .= " AND a.online = 0";
        } elseif ($this->_options['offline'] == false) {
            $sql .= " AND a.online = 1";
        }

        $sql .= " ORDER BY a." . $this->_options['order'] . " " . $this->_options['direction'];

        if ($this->_options['limit'] > 0) {
            $sql .= " LIMIT " . $this->_options['limit'];
        }

        if ($this->_options['limit'] > 0) {
            $sql .= " OFFSET " . $this->_options['limit'];
        }

        // Run the query and collect all found article language ids
        $db = cRegistry::getDb();
        $db->query($sql);
        $artLangIds = [];
        while ($db->nextRecord()) {
            $artLangIds[] = $db->f('idartlang');
        }

        // Retrieve all found articles in chunks (default size is 100)
        $articleLanguageCollection = new cApiArticleLanguageCollection();
        $articleLanguageCollection->fetchChunkObjectsByIds($artLangIds, function ($results, $page) {
            $this->_articles = array_merge($this->_articles, $results);
        });

        // Execute cec hook
        cApiCecHook::execute('Contenido.ArticleCollector.Articles', [
            'idart' => cRegistry::getArticleId(),
            'articles' => $this->_articles
        ]);
    }

    /**
     * Compatibility method for old ArticleCollection class. Returns the start
     * article of a category. Does work only if one category was requested.
     *
     * @return cApiArticleLanguage
     *
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
                $this->_pages = [];
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
        if (array_key_exists($page, $this->_pages)) {
            $this->_articles = $this->_pages[$page];
        }
    }

    /**
     * Seeks a specific position in the loaded articles.
     *
     * @param int $position
     *         position to load
     *
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
        return $this->_articles[$this->_currentPosition] ?? null;
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

    /**
     * @return array
     */
    public function getStartArticles() {
        return $this->_startArticles;
    }

    /**
     * Fetches all start articles for defined categories from the database.
     *
     * @return array  Array where the key is the category id and the value
     *                the id of the start article.
     * @throws cDbException|cException
     */
    protected function _fetchStartArticles() {
        $catLangColl = new cApiCategoryLanguageCollection();
        $catLangColl->addResultFields(['startidartlang', 'idcat']);
        $catLangColl->setWhere('idlang', $this->_options['lang']);
        if (count($this->_options['categories']) > 0) {
            $catLangColl->setWhere('idcat', $this->_options['categories'], 'IN');
        }
        $catLangColl->query();
        $fields = ['startidartlang' => 'startidartlang', 'idcat' => 'idcat'];
        $startArticles = [];
        foreach ($catLangColl->fetchTable($fields) as $entry) {
            $startId = cSecurity::toInteger($entry['startidartlang']);
            if ($startId > 0) {
                $startArticles[cSecurity::toInteger($entry['idcat'])] = $startId;
            }
        }

        return $startArticles;
    }

}
