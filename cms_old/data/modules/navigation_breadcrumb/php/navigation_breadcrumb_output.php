<?php
$helper = cCategoryHelper::getInstance();
foreach ($helper->getCategoryPath($idcat, 2) as $categoryLang) {
    $link = new cHTMLLink();
    $link->setLink(cUri::getInstance()->build(array('idcat' => $categoryLang->get('idcat'), 'lang' => $categoryLang->get('idlang'))));
    $link->setContent($categoryLang->get('name'));

    echo '&gt; ' . $link . ' ';
}
?>