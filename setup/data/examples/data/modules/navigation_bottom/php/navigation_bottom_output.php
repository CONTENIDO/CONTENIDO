<?php

/**
 * description: bottom navigation
 *
 * @package Module
 * @subpackage NavigationBottom
 * @author marcus.gnass@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

// assert framework initialization
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

// read articles from defined cat, including its start article and ordering it
// by its custom order
$collector = new cArticleCollector(array(
    'idcat' => getEffectiveSetting('navigation_bottom', 'idcat', 1),
    'start' => true,
    'order' => 'sortsequence'
));

$articles = array();
foreach ($collector as $article) {
    $articles[] = array(
        'title' => $article->get('title'),
        'url' => cUri::getInstance()->build(array(
            'idart' => $article->get('idart'),
            'lang' => cRegistry::getLanguageId()
        ), true)
    );
}

// use smarty template to output header text
$tpl = cSmartyFrontend::getInstance();
$tpl->assign('articles', $articles);
$tpl->display('get.tpl');

?>