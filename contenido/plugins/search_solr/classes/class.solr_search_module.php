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
 * This module handles a search request using a Solr searcher implementation and
 * displays the returned search results using a Smarty template.
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 */
class SolrSearchModule
{

    /**
     * @var string
     */
    private $searchTerm;

    /**
     * @var int
     */
    private $page;

    /**
     * @var int
     */
    private $itemsPerPage;

    /**
     * @var string
     */
    private $templateName;

    /**
     * @var array
     */
    private $label;

    /**
     * @var SolrObject
     */
    private $response = NULL;

    /**
     * @throws cException
     */
    public function __construct(?array $options = NULL)
    {
        if (NULL !== $options) {
            foreach ($options as $name => $value) {
                $this->$name = $value;
            }

            $this->searchTerm = cSecurity::toString($this->searchTerm);
            $this->page = cSecurity::toInteger($this->page);
            $this->itemsPerPage = cSecurity::toInteger($this->itemsPerPage);
        }
        $this->response = $this->getSearchResults();
    }

    /**
     * @throws cException
     */
    private function getSearchResults(): SolrObject
    {
        $searcher = new SolrSearcherSimple();
        $searcher->setSearchTerm($this->searchTerm);
        $searcher->setPage($this->page);
        $searcher->setItemsPerPage($this->itemsPerPage);
        return $searcher->getSearchResults();
    }

    /**
     * @throws cException|cInvalidArgumentException|SmartyException
     */
    public function render()
    {
        $tpl = cSmartyFrontend::getInstance();
        $tpl->assign('label', $this->label);
        $tpl->assign('href', cUri::getInstance()->build([
            'idart' => cRegistry::getArticleId(),
            'lang' => cRegistry::getLanguageId()
        ]));
        $tpl->assign('searchTerm', $this->searchTerm);
        $tpl->assign('page', $this->page);
        $tpl->assign('itemsPerPage', $this->itemsPerPage);

        // calculate number of pages
        $numPages = $this->response->numFound / $this->itemsPerPage;
        if (is_float($numPages)) {
            $numPages = ceil($numPages);
        }

        $tpl->assign('numPages', $numPages);
        $tpl->assign('numFound', $this->response->numFound);
        $tpl->assign('start', $this->response->start);
        if (false === $this->response->docs) {
            $tpl->assign('results', []);
        } else {
            $tpl->assign('results', $this->response->docs);
        }
        $tpl->display($this->templateName);
    }

}

