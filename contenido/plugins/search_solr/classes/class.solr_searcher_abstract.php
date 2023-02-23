<?php

/**
 *
 * @package    Plugin
 * @subpackage SearchSolr
 * @author     Marcus Gnaß <marcus.gnass@4fb.de>
 * @copyright  four for business AG
 * @link       https://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Abstract class for Solr search implementations.
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 */
abstract class SolrSearcherAbstract {

    /**
     * Term to be searched for.
     *
     * @var string
     */
    protected $_searchTerm = '';

    /**
     * Number of search result page to be displayed.
     * This value is one-based!
     *
     * @var int
     */
    protected $_page = 1;

    /**
     *
     * @var int
     */
    protected $_itemsPerPage = 10;

    /**
     *
     * @param string $searchTerm
     */
    public function setSearchTerm($searchTerm) {
        $this->_searchTerm = $searchTerm;
    }

    /**
     *
     * @param int $page
     */
    public function setPage($page) {
        $this->_page = $page;
    }

    /**
     *
     * @param int $itemsPerPage
     */
    public function setItemsPerPage($itemsPerPage) {
        $this->_itemsPerPage = $itemsPerPage;
    }

    /**
     *
     * @return SolrObject
     */
    abstract public function getSearchResults();
}
