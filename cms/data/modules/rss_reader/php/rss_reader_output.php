<?php
$tpl = new cTemplate();

if ("CMS_VALUE[0]" == "") {
    $feedUrl = "http://www.contenido.org/rss/de/news";
} else {
    $feedUrl = "CMS_VALUE[0]";
}

if ("CMS_VALUE[2]" == "") {
    $maxFeedItems = 5;
} else {
    $maxFeedItems = intval("CMS_VALUE[2]");
}

$doc = new cXmlReader();

$domDocument = new DOMDocument();
$domDocument->load($feedUrl);

$doc->setDomDocument($domDocument);

for ($i = 0; $i < $maxFeedItems; $i++) {
	$title = $doc->getXpathValue('*/channel/item/title', $i);
	$link = $doc->getXpathValue('*/channel/item/link', $i);
	$description = $doc->getXpathValue('*/channel/item/description', $i);
	
	$tpl->set("d", "TITLE", htmlentities($title, ENT_QUOTES));
	$tpl->set("d", "LINK", htmlentities($link, ENT_QUOTES));
	$tpl->set("d", "DESCRIPTION", htmlentities($description, ENT_QUOTES));
	$tpl->set("d", "READ_ON", mi18n("weiterlesen"));
	$tpl->next();
}

$tpl->generate(cRegistry::getFrontendPath() . "templates/" . "CMS_VALUE[1]");
?>