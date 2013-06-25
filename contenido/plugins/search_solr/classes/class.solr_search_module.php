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
 * This module handles a search request using a Solr searcher implementation and
 * displays the returned search results using a Smarty template.
 *
 * @author marcus.gnass
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
    private $_results = NULL;

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
        $this->_results = $this->_getSearchResults();
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
        $tpl->assign('results', $this->_results);
        $tpl->display($this->_templateName);
    }

}

