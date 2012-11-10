<?php
	echo "CMS_TEASER[5]";
    
    $art = new Article($idart, $client, $lang);
    $contentValue = $art->getContent("TEASER", 5);

    $teaser = new cContentTypeTeaser($contentValue, 5, array());
    $articles = $teaser->getConfiguredArticles();
    
    print_r($articles);
?>