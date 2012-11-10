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

    $tpl->set("d", "TITLE", conHtmlentities($title, ENT_QUOTES));
    $tpl->set("d", "LINK", conHtmlentities($link, ENT_QUOTES));
    $tpl->set("d", "DESCRIPTION", conHtmlentities($description, ENT_QUOTES));
    $tpl->set("d", "READ_ON", mi18n("READ_MORE"));
    $tpl->next();
}

$tpl->generate(cRegistry::getFrontendPath() . "templates/" . "CMS_VALUE[1]");
?>