<?php

/**
 *
 * @package Plugin
 * @subpackage SearchSolr
 * @author marcus.gnass
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Simple Solr search implementation.
 *
 * This searcher is restricted on single core searches (due to the fact that
 * SolrQuery does not support multi core requests).
 *
 * @author marcus.gnass
 */
class SolrSearcherSimple extends SolrSearcherAbstract {

    /**
     *
     * @throws cException if search cannot be performed for empty search term
     * @return SolrObject
     */
    public function getSearchResults() {

        $searchTerm = $this->_searchTerm;
        $searchTerm = trim($searchTerm);
        $searchTerm = explode(' ', $searchTerm);
        $searchTerm = array_map('trim', $searchTerm);
        $searchTerm = array_filter($searchTerm);

        // there are no results if there is no search term
        if (empty($searchTerm)) {
            throw new cException('search cannot be performed for empty search term');
        }

        /* SolrQuery */
        $query = new SolrQuery();
        // set the search query
        $query->setQuery('content:*' . implode('* *', $searchTerm) . '*');
        // specify the number of rows to skip
        $query->setStart(($this->_page - 1) * $this->_itemsPerPage);
        // specify the maximum number of rows to return in the result
        $query->setRows($this->_itemsPerPage);
        // specify fields to return
        // $query->addField('content');
        // $query->addField('id_art_lang');

        /* SolrClient */
        $idclient = cRegistry::getClientId();
        $idlang = cRegistry::getLanguageId();
        $options = Solr::getClientOptions($idclient, $idlang);
        Solr::log(print_r($options, true));
        $solrClient = new SolrClient($options);
        // $solrClient->setServlet(SolrClient::SEARCH_SERVLET_TYPE,
        // $this->_servlet);

        $results = NULL;
        try {
            $solrQueryResponse = @$solrClient->query($query);
            $response = $solrQueryResponse->getResponse();
            $response = $response->response;
        } catch (SolrClientException $e) {
            Solr::log($e, $e->getFile(), $e->getLine());
            Solr::log($solrClient->getDebug());
            Solr::log($query->toString());
        }

        return $response;
    }

}

