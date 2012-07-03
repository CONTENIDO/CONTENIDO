<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO Article Object and Collection
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido_API
 * @version    1.2
 * @author     Jan Lengowski
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created  unknown
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *   modified 2011-03-15, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *   modified 2011-10-19, Murat Purc, moved Article implementation to cApiArticleLanguage in favor of normalizing the API
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * @deprecated  [2011-10-19] Use cApiArticleLanguage class in classes/contenido/class.articlelanguage.php as follows:
 * <pre>
 * // create with article language id
 * $obj = new cApiArticleLanguage($idartlang);
 *
 * // create with article id and language id
 * $obj = new cApiArticleLanguage();
 * $obj->loadByArticleAndLanguageId($idart, $lang);
 * </pre>
 */
class Article extends cApiArticleLanguage
{
    /**
     * Constructor. Wrapper for parent class, provides thee downwards compatible
     * interface for creation of the article object.
     *
     * @param int $idart Article id
     * @param int $client Client id (not used)
     * @param int $lang Language id
     * @param int $idartlang Article language id
     *
     * @return void
     */
    public function __construct($idart, $client, $lang, $idartlang = 0)
    {
        cDeprecated("Use cApiArticleLanguage class instead");
        $idart = (int) $idart;
        $client = (int) $client;
        $idartlang = (int) $idartlang;

        if ($idartlang > 1) {
            parent::__construct($idartlang);
        } else {
            parent::__construct();
            $this->loadByArticleAndLanguageId($idart, $lang);
        }
        $this->_getArticleContent();
    }

    function Article($idart, $client, $lang, $idartlang = 0)
    {
        cDeprecated("Use __construct()");
        $this->__construct($idart, $client, $lang, $idartlang);
    }
}


/**
 * CONTENIDO API - Article Object Collection.
 *
 * This class is used to manage multiple CONTENIDO article objects in a collection.
 *
 * The constructor awaits an associative array as parameter with the following schema:
 *
 * array( string paramname => mixed value );
 *
 * The parameter idcat is required: array('idcat'=>n)
 *
 * Legal parameter names are:
 *
 *  idcat     - CONTENIDO Category Id
 *  lang      - Language Id, active language if ommited
 *  client    - Client Id, active client if ommited
 *  start     - include start article in the collection, defaults to false
 *  artspecs  - Array of article specifications, which should be considered
 *  order     - articles will be orderered by this article property, defaults to 'created'
 *  direction - Order direcion, 'asc' or 'desc' for ascending/descending, defaults to 'asc'
 *  limit     - Limit numbers of articles in collection
 *
 * You can easy create article lists/teasers with this class.
 *
 * To create an article list of category 4 (maybe a news category) use:
 *
 * $myList = new ArticleCollection(array("idcat"=>4);
 *
 * while ($article = $myList->nextArticle())
 * {
 *     // Fetch the first headline
 *     $headline = $article->getContent('htmlhead', 1);
 *     $idart    = $article->getField('idart');
 *
 *     // Create a link
 *     echo '<a href="front_content.php?idcat='.$myList->idcat.'&idart='.$idart.'">'.$headline.'</a><br/>';
 * }
 *
 * @package CONTENIDO API
 * @version 1.0
 *
 * @author Jan Lengowski <Jan.Lengowski@4fb.de>
 * @copyright four for business AG <www.4fb.de>
 */
class ArticleCollection
{
    /**
     * Database Object
     * @var object
     */
    public $db;

    /**
     * Result Counter
     * @var int
     */
    public $cnt = 0;

    /**
     * Language id
     * @var int
     */
    public $lang;

    /**
     * Client ID
     * @var int
     */
    public $client;

    /**
     * Config array
     * @var array
     */
    public $tab;

    /**
     * Articles
     * @var array
     */
    public $articles;

    /**
     * Article Specifications
     * @var array
     */
    public $artspecs;

    /**
     * Include the Start-Article
     * @var int
     */
    public $start;

    /**
     * Id of the start article
     * @var int
     */
    public $startId;

    /**
     * Sort order
     * @var string
     */
    public $order;

    /**
     * Sort direction
     * @var string
     */
    public $direction;

    /**
     * Limit of numbers of articles in collection
     * @var int
     */
    public $limit;

    /**
     * Articles in collection
     * @var int
     */
    public $count;

    /**
     * Pages in Article Collection
     * @var int
     */
    public $iCountPages;

    /**
     * Results per page
     * @var int
     */
    public $iResultPerPage;

    /**
     * List of articles, splitted into pages
     * @var array
     */
    public $aPages;

    /**
     * Article Collection Constructor
     *
     * @param array Options array with schema array('option'=>'value');
     *  idcat (required) - CONTENIDO Category Id
     *  lang - Language Id, active language if ommited
     *  client - Client Id, active client if ommited
     *  artspecs  - Array of article specifications, which should be considered
     *  start - include start article in the collection, defaults to false
     *  order - articles will be orderered by this property, defaults to 'created'
     *  direction - Order direcion, 'asc' or 'desc' for ascending/descending
     *  limit - Limit numbers of articles in collection
     *
     * @return void
     */
    public function __construct($options)
    {
        global $cfg;

        $this->tab = $cfg['tab'];
        $this->db = cRegistry::getDb();

        if (!is_numeric($options['idcat'])) {
            return 'idcat has to be defined';
        }

        $this->_setObjectProperties($options);
        $this->_getArticlesByCatId($this->idcat);
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    function ArticleCollection($options)
    {
        cDeprecated("Use __construct()");
        $this->__construct($options);
    }

    /**
     * Set the Object properties
     *
     * @param array Options array with schema array('option'=>'value');
     *  idcat (required) - CONTENIDO Category Id
     *  lang - Language Id, active language if ommited
     *  client - Client Id, active client if ommited
     *  artspecs  - Array of article specifications, which should be considered
     *  start - include start article in the collection, defaults to false
     *  order - articles will be ordered by this property, defaults to 'created'
     *  direction - Order direcion, 'ASC' or 'DESC' for ascending/descending
     *  limit - Limit numbers of articles in collection
     *
     * @return void
     */
    private function _setObjectProperties($options)
    {
        global $client, $lang;

        $lang   = cSecurity::toInteger($lang);
        $client = cSecurity::toInteger($client);

        $this->idcat     = $options['idcat'];
        $this->lang      = (array_key_exists('lang',   $options))    ? $options['lang']      : $lang;
        $this->client    = (array_key_exists('client', $options))    ? $options['client']    : $client;
        $this->start     = (array_key_exists('start',  $options))    ? $options['start']     : false;
        $this->offline   = (array_key_exists('offline',$options))    ? $options['offline']   : false;
        $this->order     = (array_key_exists('order',  $options))    ? $options['order']     : 'created';
        $this->artspecs  = (array_key_exists('artspecs', $options) AND is_array($options['artspecs']))  ? $options['artspecs']  : array();
        $this->direction = (array_key_exists('direction', $options)) ? $options['direction'] : 'DESC';
        $this->limit = (array_key_exists('limit', $options)  AND is_numeric($options['limit'])) ? $options['limit'] : 0;
    }

    /**
     * Extracts all articles from a specified category id and stores them in the
     * internal article array
     *
     * @param int Category Id
     */
    private function _getArticlesByCatId($idcat)
    {
        global $cfg;

        $idcat = cSecurity::toInteger($idcat);

        $sArtSpecs = (count($this->artspecs) > 0) ? " a.artspec IN ('".implode("','", $this->artspecs)."') AND " : '';

        $sql = 'SELECT
                    a.idart,
                    a.idartlang,
                    c.is_start
                  FROM
                    '.$this->tab['art_lang'].' AS a,
                    '.$this->tab['art'].' AS b,
                    '.$this->tab['cat_art'].' AS c
                WHERE
                    c.idcat = '.$idcat.' AND
                    b.idclient = '.$this->client.' AND
                    b.idart = c.idart AND
                    a.idart = b.idart AND
                    '.$sArtSpecs.'
                    a.idlang = '.$this->lang.'';

        if (!$this->offline) {
            $sql .= ' AND a.online = 1 ';
        }

        $sql .= ' ORDER BY a.'.$this->order.' '.$this->direction.'';

        $this->db->query($sql);

        $db2 = cRegistry::getDb();
        $sql = "SELECT startidartlang FROM ".$cfg['tab']['cat_lang']." WHERE idcat=".$idcat." AND idlang=".$this->lang;
        $db2->query($sql);
        $db2->next_record();

        $startidartlang = $db2->f('startidartlang');

        if ($startidartlang != 0) {
            $sql = "SELECT idart FROM ".$cfg['tab']['art_lang']." WHERE idartlang=".$startidartlang;
            $db2->query($sql);
            $db2->next_record();
            $this->startId = $db2->f('idart');
         }

        while ($this->db->next_record()) {
            if ($this->db->f('idart') == $this->startId) {
                if ($this->start) {
                    $this->articles[] = $this->db->f('idart');
                }
            } else {
                $this->articles[] = $this->db->f('idart');
            }
        }

        $this->count = count($this->articles);
    }

    /**
     * Iterate to the next article, return object of type CONTENIDO Article Object
     * if an article is found. False otherwise.
     *
     * @return Article|false  CONTENIDO Article object or false
     */
    public function nextArticle()
    {
        $limit = true;
        if ($this->limit > 0) {
            if ($this->cnt >= $this->limit)
            $limit = false;
        }
        if ($this->cnt < count($this->articles) && $limit) {
            $idart = $this->articles[$this->cnt];

            if (is_numeric($idart)) {
                $this->cnt++;
                $oArticle = new cApiArticleLanguage();
                $oArticle->loadByArticleAndLanguageId($idart, $this->lang);
                return $oArticle;
            }
        }
        return false;
    }

    /**
     * Return ONLY the Start-Article
     *
     * @return  Article  CONTENIDO Article Object
     */
    public function startArticle()
    {
        $oArticle = new cApiArticleLanguage();
        $oArticle->loadByArticleAndLanguageId($this->startId, $this->lang);
        return $oArticle;
    }

    /**
     * Split the article results into pages of a given size.
     *
     * Example:
     * Article Collection with 5 articles
     *
     *   [0] => 250
     *   [1] => 251
     *   [2] => 253
     *   [3] => 254
     *   [4] => 255
     *
     * $collection->setResultPerPage(2)
     *
     * Would split the results into 3 pages
     *
     * [0] => [0] => 250
     *        [1] => 251
     * [1] => [0] => 253
     *        [1] => 254
     * [2] => [0] => 255
     *
     * A page can be selected with
     *
     * $collection->setPage(int page)
     *
     * @param int $resPerPage
     */
    public function setResultPerPage($resPerPage)
    {
        $this->iResultPerPage = $resPerPage;

        if ($this->iResultPerPage > 0) {
            if (is_array($this->articles)) {
                 $this->aPages = array_chunk($this->articles, $this->iResultPerPage);
                 $this->iCountPages = count($this->aPages);
            } else {
                $this->aPages = array();
                $this->iCountPages = 0;
            }
        }
    }

    /**
     * Select a page if the results was divided before.
     *
     * $collection->setResultsPerPage(2);
     * $collection->setPage(1);
     *
     * // Iterate through all articles of page two
     * while ($art = $collection->nextArticle())
     * { ... }
     *
     * @param int $iPage The page of the article collection
     */
    public function setPage($iPage)
    {
        if (is_array($this->aPages[$iPage])) {
            $this->articles = $this->aPages[$iPage];
        }
    }
}

?>