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

global $cfg;

$pluginName = basename(dirname(__DIR__, 1));

$cfg['plugins'][$pluginName] = cRegistry::getBackendPath() . $cfg['path']['plugins'] . "$pluginName/";

plugin_include($pluginName, 'classes/class.solr.php');

// define template names
$cfg['templates']['solr_right_bottom'] = $cfg['plugins'][Solr::getName()] . 'templates/template.right_bottom.tpl';

// include necessary sources, setup autoloader for plugin
$pluginClassesPath = 'contenido/plugins/' . Solr::getName() . '/classes';
cAutoload::addClassmapConfig([
    'SolrIndexer' => $pluginClassesPath . '/class.solr_indexer.php',
    'SolrSearcherAbstract' => $pluginClassesPath . '/class.solr_searcher_abstract.php',
    'SolrSearcherSimple' => $pluginClassesPath . '/class.solr_searcher_simple.php',
    'SolrSearchModule' => $pluginClassesPath . '/class.solr_search_module.php',
    'SolrRightBottomPage' => $pluginClassesPath . '/class.solr.gui.php',
    'SolrException' => $pluginClassesPath . '/class.solr_exception.php',
    'SolrWarning' => $pluginClassesPath . '/class.solr_warning.php'
]);

// == add chain functions
$cec = cRegistry::getCecRegistry();
// reindex article after article properties are updated
$cec->addChainFunction('Contenido.Action.con_saveart.AfterCall', 'SolrIndexer::handleStoringOfArticle');
// reindex article after any content entry is updated
$cec->addChainFunction('Contenido.Content.AfterStore', 'SolrIndexer::handleStoringOfContentEntry');

unset($pluginName, $pluginClassesPath);
