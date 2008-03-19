<?php

// includes
cInclude('classes', 'widgets/class.widgets.page.php');
cInclude('classes', 'class.htmlelements.php');
cInclude('classes', 'class.ui.php');
cInclude('classes', 'class.notification.php');

// plugin includes
plugin_include('content_allocation', 'classes/class.content_allocation_tree.php');
plugin_include('content_allocation', 'classes/class.content_allocation_treeview.php');
plugin_include('content_allocation', 'classes/class.content_allocation_article.php');
plugin_include('content_allocation', 'classes/class.content_allocation.php');
plugin_include('content_allocation', 'classes/class.content_allocation_complexlist.php');
#plugin_include('repository', 'log/class.logger.php');

// plugin_variables
$cfg['tab']['pica_alloc'] = $cfg['sql']['sqlprefix'].'_pica_alloc';
$cfg['tab']['pica_alloc_con'] = $cfg['sql']['sqlprefix'].'_pica_alloc_con';
$cfg['tab']['pica_lang'] = $cfg['sql']['sqlprefix'].'_pica_lang';

$cfg['pica']['logpath'] = $cfg['path']['contenido'] . $cfg['path']['plugins'] . 'repository/log/data/';
$cfg['pica']['loglevel'] = 'warn';
$cfg['pica']['treetemplate'] = $cfg['path']['contenido'] . $cfg['path']['plugins'] . 'content_allocation/templates/template.tree_structure.html';
$cfg['pica']['treetemplate_article'] = $cfg['path']['contenido'] . $cfg['path']['plugins'] . 'content_allocation/templates/template.tree_article.html';
$cfg['pica']['treetemplate_complexlist'] = $cfg['path']['contenido'] . $cfg['path']['plugins'] . 'content_allocation/templates/template.tree_complexlist.html';

$cfg['pica']['style_complexlist'] = $cfg['path']['contenido_fullhtml'] . $cfg['path']['plugins'] . 'content_allocation/style/complexlist.css';
$cfg['pica']['script_complexlist'] = $cfg['path']['contenido_fullhtml'] . $cfg['path']['plugins'] . 'content_allocation/scripts/complexlist.js';

// administration > users > area translations
global $lngAct, $_cecRegistry;
$lngAct['con_contentallocation']['storeallocation'] = i18n("Store content allocations");

plugin_include('content_allocation', 'includes/functions.chains.php');

$_cecRegistry->addChainFunction("Contenido.Article.RegisterCustomTab", "pica_RegisterCustomTab");
$_cecRegistry->addChainFunction("Contenido.Article.GetCustomTabProperties", "pica_GetCustomTabProperties");
#$_cecRegistry->addChainFunction("Contenido.ArticleList.Actions", "pica_ArticleListActions");
#$_cecRegistry->addChainFunction("Contenido.ArticleList.RenderAction", "pica_RenderArticleAction");
?>
