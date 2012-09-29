<?php
$iIdcatStart = getEffectiveSetting('navigation', 'idcat-meta', 2);

$tpl = new cTemplate();

$categoryHelper = cCategoryHelper::getInstance();
$categoryHelper->setAuth(cRegistry::getAuth());

$categoryTree = $categoryHelper->getSubCategories($iIdcatStart, 1);

foreach ($categoryTree as $treeData) {
	$url = $treeData['item']->getLink();

	$tpl->set('d', 'title', $treeData['item']->getField('name'));
	$tpl->set('d', 'label', $treeData['item']->getField('name'));
	$tpl->set('d', 'url', $url);
	$tpl->next();
}

$items = $tpl->generate('templates/navigation_meta_item.html', true, false);

$tpl->reset();
$tpl->set('s', 'items', $items);
$tpl->generate('templates/navigation_meta_container.html');
?>