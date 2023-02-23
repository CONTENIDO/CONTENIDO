<?php

/**
 * This file contains the array utility class.
 *
 * @package    Core
 * @subpackage Util
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * The article collector returns you a list of articles, which destination you
 * can choose.
 * You have the ability to limit, sort and filter the article list.
 *
 * You can configure the article collector with an options array, which can
 * include the configuration as described in {@see cArticleCollector::__construct()}.
 *
 * TODO: Use generic DB instead of SQL queries
 *
 * @package    Core
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
     * @var cApiArticleLanguage[]
     */
    protected $_articles = [];

    /**
     * Total paging data.
     *
     * @var cApiArticleLanguage[]|array
     */
    protected $_pages = [];

    /**
     * Array of start articles of the requested categories, where the key is the
     * category id and the value the id of the start article.
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
     *      Array with options for the collector as follows:
     *     - idcat - (int) Category ID
     *     - categories - (array) Array with multiple category IDs
     *     - lang - (int) Language ID, active language if omitted
     *     - client - (int) Client ID, active client if omitted
     *     - artspecs - (array) Array of article specifications, which should be considered
     *     - offline - (bool) Include offline article in the collection, defaults to false
     *     - offlineonly - (bool) Only list offline articles, defaults to false
     *     - start - (bool) Include start article in the collection, defaults to false
     *     - startonly - (bool) Only list start articles, defaults to false
     *     - order - (string) Articles will be ordered by this property, defaults to created
     *         Allowed values are:
     *         'sortsequence', 'title', 'modificationdate', 'publisheddate',
     *         and 'creationdate'.
     *     - direction - (string) Order direction, ASC or DESC for ascending/descending, defaults to DESC
     *     - limit - (int) Limit numbers of articles in collection, default to 0 (unlimited)
     *     - offset - (int) The offset of the first row to return
     *
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function __construct(array $options = []) {
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
    public function setOptions(array $options) {
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

        $options['order'] = $options['order'] ?? '';
        $orderMap = [
            'sortsequence' => 'artsort',
            'title' => 'title',
            'modificationdate' => 'lastmodified',
            'publisheddate' => 'published',
            'creationdate' => 'created',
        ];
        $options['order'] = $orderMap[$options['order']] ??  $orderMap['creationdate'];

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
     * @throws cDbException|cException|cInvalidArgumentException
     */
    public function loadArticles() {
        $this->_articles = [];

        // Collect start articles
        $this->_startArticles = $this->_fetchStartArticles();
        if ($this->_options['startonly'] && count($this->_startArticles) == 0) {
            return;
        }

        $sql = $this->_buildArticlesQuery();

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
     * @throws cBadMethodCallException|cDbException|cException
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
     * @throws cOutOfBoundsException
     */
    #[\ReturnTypeWillChange]
    public function seek($position) {
        $this->_currentPosition = $position;

        if ($this->valid() === false) {
            throw new cOutOfBoundsException("Invalid seek position: " . $position);
        }
    }

    /**
     * Method "rewind" of the implemented iterator.
     */
    #[\ReturnTypeWillChange]
    public function rewind() {
        $this->_currentPosition = 0;
    }

    /**
     * Method "current" of the implemented iterator.
     *
     * @return cApiArticleLanguage|null
     */
    #[\ReturnTypeWillChange]
    public function current() {
        return $this->_articles[$this->_currentPosition] ?? null;
    }

    /**
     * Method "key" of the implemented iterator.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function key() {
        return $this->_currentPosition;
    }

    /**
     * Method "next" of the implemented iterator.
     */
    #[\ReturnTypeWillChange]
    public function next() {
        ++$this->_currentPosition;
    }

    /**
     * Method "valid" of the implemented iterator.
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function valid() {
        return isset($this->_articles[$this->_currentPosition]);
    }

    /**
     * Method "count" of the implemented Countable interface. Returns the amount
     * of all loaded articles.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count() {
        return count($this->_articles);
    }

    /**
     * Returns the array of start articles.
     *
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

    /**
     * Builds the articles query to retrieve distinct articles from the
     * article-language table by using the defined options.
     *
     * @return string
     */
    protected function _buildArticlesQuery() {
        $options = $this->_options;

        // This sql-line uses cat_art table with alias c. If no categories found, it writes only "WHERE" into sql-query
        if ((count($options['categories']) > 0)) {
            $tabCatArt = cRegistry::getDbTableName('cat_art');
            $in = implode(",", $options['categories']);
            $sqlCat = ", " . $tabCatArt . " AS c WHERE c.idcat IN (" . $in . ") AND b.idart = c.idart AND ";
        } else {
            $sqlCat = ' WHERE ';
        }

        $in = implode("','", $options['artspecs']);
        $sqlArtSpecs = (count($options['artspecs']) > 0) ? " a.artspec IN ('" . $in . "') AND " : '';

        $sqlStartArticles = '';
        if (count($this->_startArticles) > 0) {
            if (!$options['start']) {
                $in = implode(",", $this->_startArticles);
                $sqlStartArticles = "a.idartlang NOT IN (" . $in . ") AND ";
            }

            if ($options['startonly']) {
                $in = implode(",", $this->_startArticles);
                $sqlStartArticles = "a.idartlang IN (" . $in . ") AND ";
            }
        }

        $tabArt = cRegistry::getDbTableName('art');
        $tabArtLang = cRegistry::getDbTableName('art_lang');

        $sql = "SELECT DISTINCT a.idartlang FROM " . $tabArtLang . " AS a, ";
        $sql .= $tabArt . " AS b";
        $sql .= $sqlCat . $sqlStartArticles . $sqlArtSpecs . "b.idclient = " . $options['client'] . " AND ";
        $sql .= "a.idlang = " . $options['lang'] . " AND " . "a.idart = b.idart";

        if ($options['offlineonly']) {
            $sql .= " AND a.online = 0";
        } elseif (!$options['offline']) {
            $sql .= " AND a.online = 1";
        }

        $sql .= " ORDER BY a." . $options['order'] . " " . $options['direction'];

        if ($options['limit'] > 0) {
            $sql .= " LIMIT " . $options['limit'];
        }

        if ($options['offset'] > 0) {
            $sql .= " OFFSET " . $options['offset'];
        }

        return $sql;
    }

}
