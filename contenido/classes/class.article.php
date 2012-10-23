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
 *   $Id$:
 * }}
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
class Article extends cApiArticleLanguage {

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
    public function __construct($idart, $client, $lang, $idartlang = 0) {
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

    function Article($idart, $client, $lang, $idartlang = 0) {
        cDeprecated("Use __construct()");
        $this->__construct($idart, $client, $lang, $idartlang);
    }

}

/**
 * @deprecated  [2012-10-22] This class is deprecated. Use cArticleCollector instead.
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
 */
class ArticleCollection extends cArticleCollector {
    /** @deprecated  [2012-10-22] This class is deprecated. Use cArticleCollector instead. */
    public function __construct($options) {
        cDeprecated("This class is deprecated. Use cArticleCollector instead.");
        parent::__construct($options);
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    function ArticleCollection($options) {
        cDeprecated("Use __construct()");
        $this->__construct($options);
    }
}

?>