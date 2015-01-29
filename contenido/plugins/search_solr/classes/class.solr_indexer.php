<?php

/**
 *
 * @package Plugin
 * @subpackage SearchSolr
 * @version SVN Revision $Rev:$
 * @author marcus.gnass
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This class sends update requests to a Solr core.
 * If the request failed an
 * exception is thrown. This class allows handling of more than one article at
 * once.
 *
 * <server>:8080/solr/admin/cores?action=STATUS
 * <server>:8080/solr/admin/cores?action=RENAME&core=collection1&other=contenido
 * <server>:8080/solr/admin/cores?action=RELOAD&core=contenido
 *
 * @author marcus.gnass
 */
class SolrIndexer {

    /**
     * IDs of articles to be updated / added / deleted.
     *
     * @var array
     */
    private $_articleIds = array();

    /**
     *
     * @var SolrClient
     */
    private $_solrClient = NULL;

    /**
     * CEC chain function for updating an article in the Solr core (index).
     *
     * This function is intended to be called after storing an article.
     * This function will delete and eventually add the given article from/to
     * the SOLR index. Adding will only be performed when the article should be
     * indexed. Removal will always be performed, even when the article is not
     * indexable, but it might have been indexed before!
     *
     * include.con_editcontent.php
     *
     * @param int $idartlang of article to be updated
     */
    public static function handleStoringOfArticle(array $newData, array $oldData) {

        // get IDs of given article langauge
        if (cRegistry::getArticleLanguageId() == $newData['idartlang']) {
            // quite easy if given article is current article
            $idclient = cRegistry::getClientId();
            $idlang = cRegistry::getLanguageId();
            $idcat = cRegistry::getCategoryId();
            $idart = cRegistry::getArticleId();
            $idcatlang = cRegistry::getCategoryLanguageId();
            $idartlang = cRegistry::getArticleLanguageId();
        } else {
            // == for other articles these infos have to be read from DB
            // get idclient by idart
            $article = new cApiArticle($idart);
            if ($article->isLoaded()) {
            	$idart = $article->get('idart');
                $idclient = $article->get('idclient');
            }
            // get idlang by idartlang
            $articleLanguage = new cApiArticleLanguage($idartlang);
            if ($articleLanguage->isLoaded()) {
                $idlang = $articleLanguage->get('idlang');
            }
            // get first idcat by idart
            $coll = new cApiCategoryArticleCollection();
            $idcat = array_shift($coll->getCategoryIdsByArticleId($idart));
            // get idcatlang by idcat & idlang
            $categoryLanguage = new cApiCategoryLanguage();
            $categoryLanguage->loadByCategoryIdAndLanguageId($idcat, $idlang);
            if ($categoryLanguage->isLoaded()) {
                $idcatlang = $articleLanguage->get('idlang');
            }
            // get idartlang
            $idartlang = $newData['idartlang'];
        }

        $articleIds = array(
            'idclient' => $idclient,
            'idlang' => $idlang,
            'idcat' => $idcat,
            'idcatlang' => $idcatlang,
            'idart' => $idart,
            'idartlang' => $idartlang
        );

        self::handleStoringOfContentEntry($articleIds);
    }

    /**
     * CEC chain function for updating an article in the Solr core (index).
     *
     * This function is intended to be called after storing an article.
     * This function will delete and eventually add the given article from/to
     * the SOLR index. Adding will only be performed when the article should be
     * indexed. Removal will always be performed, even when the article is not
     * indexable, but it might have been indexed before!
     *
     * include.con_editcontent.php
     *
     * @param int $idartlang of article to be updated
     */
    public static function handleStoringOfContentEntry(array $articleIds) {
        try {
            // build indexer instance
            $indexer = new self(array(
                $articleIds
            ));
            // update given articles
            $indexer->updateArticles();
        } catch (cException $e) {
            $lvl = $e instanceof SolrWarning? cGuiNotification::LEVEL_WARNING : cGuiNotification::LEVEL_ERROR;
            $note = new cGuiNotification();
            $note->displayNotification($lvl, $e->getMessage());
        }

        // destroy indexer to free mem
        unset($indexer);
    }

    /**
     * Create client instance (connect to Apache Solr) and aggregate it.
     *
     * @param array $articleIds IDs of articles to be handled
     */
    public function __construct(array $articleIds) {
        $this->_articleIds = $articleIds;
        $opt = Solr::getClientOptions();
        Solr::validateClientOptions($opt);
        $this->_solrClient = new SolrClient($opt);
    }

    /**
     * Destroy aggregated client instance.
     */
    public function __destruct() {

        // destroy Solr client to free mem
        // really neccessary?
        // as SolClient has a method __destruct() this seems to be correct
        unset($this->_solrClient);
    }

    /**
     * If the current articles are indexable for each article a new index
     * document will be created and filled with its content and eventually
     * be added to the index.
     *
     * @throws cException if Solr add request failed
     */
    public function addArticles() {
        $documents = array();
        foreach ($this->_articleIds as $articleIds) {

            // skip if article should not be indexed
            if (!$this->_isIndexable($articleIds['idartlang'])) {
                continue;
            }

            // get article content to be indexed
            $articleContent = $this->_getContent($articleIds['idartlang']);

            // create input document
            $solrInputDocument = new SolrInputDocument();
            // $solrInputDocument->addField('raise_exception', 'uncomment this
            // to raise an exception');
            // add IDs
            $solrInputDocument->addField('id_client', $articleIds['idclient']);
            $solrInputDocument->addField('id_lang', $articleIds['idlang']);
            $solrInputDocument->addField('id_cat', $articleIds['idcat']);
            $solrInputDocument->addField('id_art', $articleIds['idart']);
            $solrInputDocument->addField('id_cat_lang', $articleIds['idcatlang']);
            $solrInputDocument->addField('id_art_lang', $articleIds['idartlang']);

            // add content one by one
            foreach ($articleContent as $type => $typeContent) {

                // field names in Solr should always be lowercase!
                $type = strtolower($type);

                // == sort content of a certain content type by their typeids
                // This is important so that the most prominent headline can be
                // displayed first.
                ksort($typeContent);

                // add each content entry seperatly (content type fields are
                // defined as multiValued)
                foreach ($typeContent as $typeid => $contentEntry) {
                    $contentEntry = trim($contentEntry);
                    if (0 < strlen($contentEntry)) {
                        $solrInputDocument->addField($type, $contentEntry);
                    }
                }
            }

            array_push($documents, $solrInputDocument);
        }

        // add and commit documents and then optimze index
        try {
            @$this->_solrClient->addDocuments($documents);
            @$this->_solrClient->commit();
            @$this->_solrClient->optimize();
        } catch (Exception $e) {
            // log exception
            Solr::log($e);
            // rethrow as cException
            throw new cException('article could not be added to index', 0, $e);
        }
    }

    /**
     *
     * @throws SolrClientException if Solr delete request failed
     */
    public function deleteArticles() {

        function getIdartlang(array $array) {
            return $array['idartlang'];
        }

        $idartlangs = array_map('getIdartlang', $this->_articleIds);

        // delete document
        try {
            @$this->_solrClient->deleteByIds($idartlangs);
        } catch (Exception $e) {
            // log exception
            Solr::log($e);
            // rethrow as cException
            throw new cException('article could not be deleted from index', 0, $e);
        }
    }

    /**
     *
     * @throws cException if Solr delete request failed
     */
    public function updateArticles() {

        // Always delete articles from index, even if article should not be
        // indexed it might have been indexed before
        // What happens if an article could not be deleted cause it was not
        // indexed before? does this throw an exception? if yes an article
        // could never been indexed!
        try {
            $this->deleteArticles();
        } catch (cException $e) {
            // ignore exception so that articles can be indexed nonetheless
        }

        // add articles to index
        // will be skipped if article is not indexable
        $this->addArticles();
    }

    /**
     * An article is indexable if it is online and searchable.
     *
     * Articles that are hidden due to a protected category are indexable. The
     * searcher is responsible for making sure these aticles are only displayed
     * to privileged users.
     *
     * @param int $idartlang of article to be checked
     * @return bool
     */
    private function _isIndexable($idartlang) {

        // What about time managment?
        $articleLanguage = new cApiArticleLanguage($idartlang);
        if (!$articleLanguage->isLoaded()) {
            return false;
        } else if (1 != $articleLanguage->get('online')) {
            return false;
        } else if (1 != $articleLanguage->get('searchable')) {
            return false;
        } else {
            return true;
        }
    }

    /**
     *
     * @param int $idartlang of article to be read
     * @return array
     */
    private function _getContent($idartlang) {

        // exclude certain content types from indexing
        // like in conMakeArticleIndex & conGenerateKeywords
        $db = cRegistry::getDb();
        $db->query("-- SolrIndexer->_getContent()
            SELECT
                con_type.type
                , con_content.typeid
                , con_content.value
            FROM
                con_content
            INNER JOIN
                con_type
            ON
                con_content.idtype = con_type.idtype
            WHERE
                con_content.idartlang = $idartlang
                AND con_type.type NOT IN ('CMS_IMG', 'CMS_LINK', 'CMS_LINKTARGET', 'CMS_SWF')
            ORDER BY
                con_content.idtype
                , con_content.typeid
            ;");

        $content = array();
        while (false !== $db->nextRecord()) {
            $content[$db->f('type')][$db->f('typeid')] = $db->f('value');
        }

        // TODO check first alternative:
        // cInclude('includes', 'functions.con.php');
        // $content = conGetContentFromArticle($this->_idartlang);
        // TODO check second alternative:
        // $articleLanguage = new cApiArticleLanguage($this->_idartlang);
        // if (!$articleLanguage->isLoaded()) {
        // throw new cException('article could not be loaded');
        // }
        // $content = $articleLanguage->getContent();

        return $content;
    }

    /**
     *
     * @param SolrResponse $solrResponse
     * @throws cException if Solr update request failed
     */
    private function _checkResponse(SolrResponse $solrResponse, $msg = 'Solr update request failed') {
        $response = $solrResponse->getResponse();

        // SolrResponse::getDigestedResponse — Returns the XML response as
        // serialized PHP data
        // SolrResponse::getHttpStatus — Returns the HTTP status of the response
        // SolrResponse::getHttpStatusMessage — Returns more details on the HTTP
        // status
        // SolrResponse::getRawRequest — Returns the raw request sent to the
        // Solr server
        // SolrResponse::getRawRequestHeaders — Returns the raw request headers
        // sent to the Solr server
        // SolrResponse::getRawResponse — Returns the raw response from the
        // server
        // SolrResponse::getRawResponseHeaders — Returns the raw response
        // headers from the server
        // SolrResponse::getRequestUrl — Returns the full URL the request was
        // sent to
        // SolrResponse::getResponse — Returns a SolrObject representing the XML
        // response from the server
        // SolrResponse::setParseMode — Sets the parse mode
        // SolrResponse::success — Was the request a success

        if (0 != $response->status) {
            throw new cException($msg);
        }
    }
}
