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
 * If the request failed an exception is thrown.
 * This class allows handling of more than one article at once.
 *
 * @author marcus.gnass
 */
class SolrIndexer {

    /**
     * @var bool
     */
    const DBG = false;

    /**
     * Prefix to be used for Solr <uniqueKey> in order to distinguish docuemnts
     * from different sources.
     *
     * @var string
     */
    const ID_PREFIX = 'contenido_article_';

    /**
     *
     * @var array of SolrClient
     */
    private $_solrClients = NULL;

    /**
     * IDs of articles to be updated / added / deleted.
     *
     * @var array     */
    private $_articleIds = array();

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

        // get IDs of given article language
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
            $article = new cApiArticle($newData['idart']);
            if ($article->isLoaded()) {
                $idclient = $article->get('idclient');
            }
            // get idlang by idartlang
            $articleLanguage = new cApiArticleLanguage($newData['idartlang']);
            if ($articleLanguage->isLoaded()) {
                $idlang = $articleLanguage->get('idlang');
            }
            // get first idcat by idart
            $coll = new cApiCategoryArticleCollection();
            $idcat = array_shift($coll->getCategoryIdsByArticleId($newData['idart']));
            // get idcatlang by idcat & idlang
            $categoryLanguage = new cApiCategoryLanguage();
            $categoryLanguage->loadByCategoryIdAndLanguageId($idcat, $idlang);
            if ($categoryLanguage->isLoaded()) {
                $idcatlang = $articleLanguage->get('idlang');
            }
        }

        self::handleStoringOfContentEntry(array(
            'idclient' => $idclient,
            'idlang' => $idlang,
            'idcat' => $idcat,
            'idcatlang' => $idcatlang,
            'idart' => $idart,
            'idartlang' => $idartlang
        ));
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
    }

    /**
     * Destroy aggregated client instance.
     *
     * Destroys Solr client to free memory. Is this really neccessary?
     * As SolClient has a method __destruct() this seems to be correct.
     */
    public function __destruct() {
        foreach ($this->_solrClients as $key => $client) {
            unset($this->_solrClients[$key]);
        }
    }

    /**
     *
     * @param int $idclient
     * @param int $idlang
     * @return SolrClient
     */
    private function _getSolrClient($idclient, $idlang) {

        if (!isset($this->_solrClients[$idclient][$idlang])) {
            $opt = Solr::getClientOptions($idclient, $idlang);
            Solr::validateClientOptions($opt);
            $this->_solrClients[$idclient][$idlang] = new SolrClient($opt);
        }

        return $this->_solrClients[$idclient][$idlang];
    }

    /**
     * If the current articles are indexable for each article a new index
     * document will be created and filled with its content and eventually
     * be added to the index.
     *
     * @throws cException if Solr add request failed
     */
    public function addArticles() {

        $toAdd = array();
        foreach ($this->_articleIds as $articleIds) {

            // skip if article should not be indexed
            if (!$this->_isIndexable($articleIds['idartlang'])) {
                continue;
            }

            if (!isset($toAdd[$articleIds['idlang']])) {
                $toAdd[$articleIds['idlang']] = array(
                    'idclient' => $articleIds['idclient'],
                    'documents' => array()
                );
            }

            // get article content to be indexed
            $articleContent = $this->_getContent($articleIds['idartlang']);

            // create input document
            $solrInputDocument = new SolrInputDocument();
            $solrInputDocument->addField('source', 'contenido_article');
            $solrInputDocument->addField('url', cUri::getInstance()->build(array(
                'idart' => $articleIds['idart'],
                'lang' => $articleIds['idlang']
            )));
            $solrInputDocument->addField('id', self::ID_PREFIX . $articleIds['idartlang']);
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

            if (isset($articleContent['CMS_IMGEDITOR'])) {
                foreach ($articleContent['CMS_IMGEDITOR'] as $typeid => $idupl) {
                    if (0 == strlen($idupl)) {
                        continue;
                    }
                    $image = $this->_getImageUrlByIdupl($idupl);
                    if (false === $image) {
                        //Util::log("skipped \$idupl: $idupl");
                        continue;
                    }
                    $solrInputDocument->addField('images', $image);
                }
            }

            array_push($toAdd[$articleIds['idlang']]['documents'], $solrInputDocument);

        }

        // add and commit documents and then optimze index
        foreach ($toAdd as $idlang => $data) {
            try {
                $solrClient = $this->_getSolrClient($data['idclient'], $idlang);
                if (self::DBG) {
                    error_log('# addArticles #');
                    error_log('idclient: ' . $data['idclient']);
                    error_log('idlang: ' . $idlang);
                    error_log('config: ' . print_r($solrClient->getOptions(), 1));
                    error_log('#documents: ' . count($data['documents']));
                } else {
                    @$solrClient->addDocuments($data['documents']);
                    // @$solrClient->commit();
                    // @$solrClient->optimize();
                }
            } catch (Exception $e) {
                // log exception
                Solr::log($e);
                // rethrow as cException
                throw new cException('article could not be deleted from index', 0, $e);
            }
        }

    }

    /**
     */
    private function _getImageUrlByIdupl($idupl) {
        $upload = new cApiUpload($idupl);

        if (false === $upload->isLoaded()) {
            return false;
        }

        $idclient = $upload->get('idclient');
        $dirname = $upload->get('dirname');
        $filename = $upload->get('filename');

        $clientConfig = cRegistry::getClientConfig($idclient);
        $image = $clientConfig['upl']['htmlpath'] . $dirname . $filename;

        return $image;
    }

    /**
     * Delete all CONTENIDO article documents that are aggregated as
     * $this->_articleIds.
     *
     * @throws SolrClientException if Solr delete request failed
     */
    public function deleteArticles() {
        $toDelete = array();
        foreach ($this->_articleIds as $articleIds) {
            if (!isset($toDelete[$articleIds['idlang']])) {
                $toDelete[$articleIds['idlang']] = array(
                    'idclient' => $articleIds['idclient'],
                    'idartlangs' => array()
                );
            }
            $key = self::ID_PREFIX . strval($articleIds['idartlang']);
            array_push($toDelete[$articleIds['idlang']]['idartlangs'], $key);
        }
        foreach ($toDelete as $idlang => $data) {
            try {
                $solrClient = $this->_getSolrClient($data['idclient'], $idlang);
                if (self::DBG) {
                    error_log('# deleteArticles #');
                    error_log('idclient: ' . $data['idclient']);
                    error_log('idlang: ' . $idlang);
                    error_log('config: ' . print_r($solrClient->getOptions(), 1));
                    error_log('#idartlangs: ' . count($data['idartlangs']));
                    error_log('idartlangs: ' . print_r($data['idartlangs'], 1));
                } else {
                    $solrClient->deleteByIds($data['idartlangs']);
                    // @$solrClient->commit();
                }
            } catch (Exception $e) {
                // log exception
                Solr::log($e);
                // rethrow as cException
                throw new cException('article could not be deleted from index', 0, $e);
            }
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

        // 'CMS_IMG', 'CMS_LINK', 'CMS_LINKTARGET', 'CMS_SWF'
        $cms = "'CMS_HTMLHEAD','CMS_HTML','CMS_TEXT','CMS_IMGDESCR',"
            . "'CMS_LINKDESCR','CMS_HEAD','CMS_LINKTITLE','CMS_LINKEDIT',"
            . "'CMS_RAWLINK','CMS_IMGEDIT','CMS_IMGTITLE','CMS_SIMPLELINKEDIT',"
            . "'CMS_HTMLTEXT','CMS_EASYIMGEDIT','CMS_DATE','CMS_TEASER',"
            . "'CMS_FILELIST','CMS_IMGEDITOR','CMS_LINKEDITOR','CMS_PIFAFORM'";

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
                AND con_type.type IN ($cms)
            ORDER BY
                con_content.idtype
                , con_content.typeid
            ;");

        $content = array();
        while (false !== $db->nextRecord()) {
            $value = $db->f('value');
            //$value = utf8_encode($value);
            $value = strip_tags($value);
            //$value = html_entity_decode($value);
            $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
            $value = trim($value);

            $content[$db->f('type')][$db->f('typeid')] = $value;
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
