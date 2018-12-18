<?php

/**
 *
 * @package Plugin
 * @subpackage SearchSolr
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * This module handles a search request using a Solr searcher implementation and
 * displays the returned search results using a Smarty template.
 *
 * @author Marcus Gnaß <marcus.gnass@4fb.de>
 */
class SolrSearchModule {

    /**
     *
     * @var string
     */
    private $_searchTerm;

    /**
     *
     * @var int
     */
    private $_page;

    /**
     *
     * @var int
     */
    private $_itemsPerPage;

    /**
     *
     * @var string
     */
    private $_templateName;

    /**
     *
     * @var SolrObject
     */
    private $_response = NULL;

    /**
     *
     * @param array $options
     */
    public function __construct(array $options = NULL) {
        if (NULL !== $options) {
            foreach ($options as $name => $value) {
                $name = '_' . $name;
                $this->$name = $value;
            }
        }
        $this->_response = $this->_getSearchResults();
    }

    /**
     *
     * @return SolrObject
     */
    private function _getSearchResults() {
        $searcher = new SolrSearcherSimple();
        $searcher->setSearchTerm($this->_searchTerm);
        $searcher->setPage($this->_page);
        $searcher->setItemsPerPage($this->_itemsPerPage);
        return $searcher->getSearchResults();
    }

    /**
     *
     */
    public function render() {
        $tpl = cSmartyFrontend::getInstance();
        $tpl->assign('label', $this->_label);
        $tpl->assign('href', cUri::getInstance()->build(array(
            'idart' => cRegistry::getArticleId(),
            'lang' => cRegistry::getLanguageId()
        )));
        $tpl->assign('searchTerm', $this->_searchTerm);
        $tpl->assign('page', $this->_page);
        $tpl->assign('itemsPerPage', $this->_itemsPerPage);

        // calculate number of pages
        $numPages = $this->_response->numFound / $this->_itemsPerPage;
		if (is_float($numPages)) {
			$numPages = ceil($numPages);
		}

        $tpl->assign('numPages', $numPages);
        $tpl->assign('numFound', $this->_response->numFound);
        $tpl->assign('start', $this->_response->start);
        if (false === $this->_response->docs) {
            $tpl->assign('results', array());
        } else {
            $tpl->assign('results', $this->_response->docs);
        }
        $tpl->display($this->_templateName);
    }

}

