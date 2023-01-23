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
 * @var array $cfg
 */

plugin_include('search_solr', 'classes/class.solr.php');

// define template names
$cfg['templates']['solr_right_bottom'] = $cfg['plugins'][Solr::getName()] . 'templates/template.right_bottom.tpl';

// include necessary sources, setup autoloader for plugin
$pluginClassPath = 'contenido/plugins/' . Solr::getName() . '/';
cAutoload::addClassmapConfig([
    'SolrIndexer' => $pluginClassPath . 'classes/class.solr_indexer.php',
    'SolrSearcherAbstract' => $pluginClassPath . 'classes/class.solr_searcher_abstract.php',
    'SolrSearcherSimple' => $pluginClassPath . 'classes/class.solr_searcher_simple.php',
    'SolrSearchModule' => $pluginClassPath . 'classes/class.solr_search_module.php',
    'SolrRightBottomPage' => $pluginClassPath . 'classes/class.solr.gui.php',
    'SolrException' => $pluginClassPath . 'classes/class.solr_exception.php',
    'SolrWarning' => $pluginClassPath . 'classes/class.solr_warning.php'
]);
unset($pluginClassPath);

// == add chain functions
$cec = cRegistry::getCecRegistry();
// reindex article after article properties are updated
$cec->addChainFunction('Contenido.Action.con_saveart.AfterCall', 'SolrIndexer::handleStoringOfArticle');
// reindex article after any content entry is updated
$cec->addChainFunction('Contenido.Content.AfterStore', 'SolrIndexer::handleStoringOfContentEntry');
