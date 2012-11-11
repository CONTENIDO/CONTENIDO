<?php

/**
 * description: bottom navigation
 *
 * @package Module
 * @subpackage navigation_bottom
 * @version SVN Revision $Rev:$
 * @author marcus.gnass@4fb.de
 * @copyright four for business AG
 * @link http://www.4fb.de
 */

global $force;

// read articles from defined cat, including its start article and ordering it
// by its custom order
$collector = new cArticleCollector(array(
    'idcat' => getEffectiveSetting('navigation_bottom', 'idcat', 1),
    'start' => true,
    'order' => 'sortsequence'
));

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
$tpl = Contenido_SmartyWrapper::getInstance();
if (1 == $force) {
    $tpl->clearAllCache();
}
$tpl->assign('articles', $articles);
$tpl->display('navigation_bottom/template/get.tpl');

?>