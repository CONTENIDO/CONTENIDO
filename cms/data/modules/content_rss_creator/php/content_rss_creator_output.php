<?php
	$teaserIndex = 5;	
	
	$labelRssTitle = mi18n("LABEL_RSS_TITLE");
	$labelRssLink = mi18n("LABEL_RSS_LINK");
	$labelRssDescription = mi18n("LABEL_RSS_DESCRIPTION");
	$labelRssConfiguration = mi18n("LABEL_RSS_CONFIGURATION");
	$rss_h1 = mi18n("LABEL_RSS_H1");
	
	$rssTitle = "CMS_TEXT[1]";
	$rssLink = "CMS_TEXT[2]";
	$rssDescription = "CMS_HTML[1]";
	$rssConfiguration = '';
	
	$tpl = Contenido_SmartyWrapper::getInstance();
	
	$tpl->assign('label_rss_title', $labelRssTitle);
	$tpl->assign('label_rss_link', $labelRssLink);
	$tpl->assign('label_rss_configuration', $labelRssConfiguration);
	$tpl->assign('label_rss_description', $labelRssDescription);
	$tpl->assign('label_rss_h1', $rss_h1);

	$tpl->assign('rss_title', $rssTitle);
	$tpl->assign('rss_link', $rssLink);
	$tpl->assign('rss_description', $rssDescription);
	$tpl->assign('rss_configuration', $rssConfiguration);
	
	$tpl->display('content_rss_creator/template/rss_edit.tpl');
	
	echo "CMS_TEASER[5]";

    $art = new Article(cRegistry::getArticleLanguageId(), cRegistry::getClientId(), cRegistry::getLanguageId());
    $contentValue = $art->getContent("TEASER", $teaserIndex);

    $teaser = new cContentTypeTeaser($contentValue, $teaserIndex, array());
    $articles = $teaser->getConfiguredArticles();
    $configuration = $teaser->getConfiguration();
    
    $xmlString = '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"></rss>';
    
    $rssFeed = new SimpleXMLElement($xmlString);
    $rssChannel = $rssFeed->addChild('channel');
    $rssChannel->addChild('title', $art->getContent("CMS_TEXT", 1));
    $rssChannel->addChild('link', $art->getContent("CMS_TEXT", 2));
    $rssChannel->addChild('description', strip_tags($art->getContent("CMS_HTML", 1)));
    
    foreach ($articles as $article) {
    	$child = $rssChannel->addChild('item');
    	$title = strip_tags($article->getContent('HTMLHEAD', 1));
    	$text = strip_tags($article->getContent('HTML', 1));
    	$text = capiStrTrimAfterWord($text, $configuration['teaser_character_limit']);
    	
    	$child->addChild('title', conHtmlSpecialChars($title));
    	$child->addChild('link', conHtmlSpecialChars($text));
    	$child->addChild('description', conHtmlSpecialChars($text));
    }
    
    $cfgClient = cRegistry::getClientConfig();
    $client = cRegistry::getClientId();
    $filename = $cfgClient[$client]['xml']['frontendpath'] . 'rss.xml';

    $success = $rssFeed->asXML($filename);
    #print_r($configuration);
?>