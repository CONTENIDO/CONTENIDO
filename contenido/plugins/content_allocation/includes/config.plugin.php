<?php

/**
 * This file contains the configuration for the plugin content allocation.
 *
 * @package    Plugin
 * @subpackage ContentAllocation
 * @author     Unknown
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $cfg, $lngAct;

// plugin includes
plugin_include('content_allocation', 'classes/class.content_allocation_tree.php');
plugin_include('content_allocation', 'classes/class.content_allocation_treeview.php');
plugin_include('content_allocation', 'classes/class.content_allocation_article.php');
plugin_include('content_allocation', 'classes/class.content_allocation.php');
plugin_include('content_allocation', 'classes/class.content_allocation_complexlist.php');
plugin_include('content_allocation', 'includes/functions.chains.php');

// plugin_variables
$cfg['tab']['pica_alloc']     = $cfg['sql']['sqlprefix'] . '_pica_alloc';
$cfg['tab']['pica_alloc_con'] = $cfg['sql']['sqlprefix'] . '_pica_alloc_con';
$cfg['tab']['pica_lang']      = $cfg['sql']['sqlprefix'] . '_pica_lang';

$backendPath = cRegistry::getBackendPath();
$cfg['pica']['logpath']                  = $backendPath . $cfg['path']['plugins'] . 'repository/log/data/';
$cfg['pica']['treetemplate']             = $backendPath . $cfg['path']['plugins'] . 'content_allocation/templates/template.tree_structure.html';
$cfg['pica']['treetemplate_article']     = $backendPath . $cfg['path']['plugins'] . 'content_allocation/templates/template.tree_article.html';
$cfg['pica']['treetemplate_complexlist'] = $backendPath . $cfg['path']['plugins'] . 'content_allocation/templates/template.tree_complexlist.html';
$cfg['pica']['loglevel']                 = 'warn';
$cfg['pica']['style_complexlist']        = 'complexlist.css';
$cfg['pica']['script_complexlist']       = 'complexlist.js';

// administration > users > area translations
$lngAct['con_contentallocation']['storeallocation'] = i18n('Store tagging', 'content_allocation');

// Add chain functions
$cec = cRegistry::getCecRegistry();
$cec->addChainFunction('Contenido.Article.RegisterCustomTab', 'pica_RegisterCustomTab');
$cec->addChainFunction('Contenido.Article.GetCustomTabProperties', 'pica_GetCustomTabProperties');
$cec->addChainFunction('Contenido.Article.conCopyArtLang_AfterInsert', 'pica_CopyArticleAllocations');
$cec->addChainFunction('Contenido.Action.con_deleteart.AfterCall', 'pica_DeleteArticleAllocations');

?>