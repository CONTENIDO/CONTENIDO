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
abstract class SolrSearcherAbstract
{

    /**
     * @var string Term to be searched for.
     */
    protected $searchTerm = '';

    /**
     * @var int Number of search result page to be displayed. This value is one-based!
     */
    protected $page = 1;

    /**
     * @var int
     */
    protected $itemsPerPage = 10;

    public function setSearchTerm(string $searchTerm)
    {
        $this->searchTerm = $searchTerm;
    }

    public function setPage(int $page)
    {
        $this->page = $page;
    }

    public function setItemsPerPage(int $itemsPerPage)
    {
        $this->itemsPerPage = $itemsPerPage;
    }

    abstract public function getSearchResults(): ?SolrObject;
}
