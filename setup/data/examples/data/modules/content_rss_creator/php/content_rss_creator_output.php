<?php

/**
 * description: rss creator
 *
 * @package Module
 * @subpackage ContentRssCreator
 * @version SVN Revision $Rev:$
 *
 * @author timo.trautmann@4fb.de
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

$teaserIndex = 5;

$cfgClient = cRegistry::getClientConfig();
$client = cRegistry::getClientId();
$filename = $cfgClient[$client]['xml']['frontendpath'] . 'rss.xml';

$labelRssTitle = mi18n("LABEL_RSS_TITLE");
$labelRssLink = mi18n("LABEL_RSS_LINK");
$labelRssDescription = mi18n("LABEL_RSS_DESCRIPTION");
$labelRssConfiguration = mi18n("LABEL_RSS_CONFIGURATION");
$labelRssH1 = mi18n("LABEL_RSS_H1");
$labelRssLogo = mi18n("LABEL_RSS_LOGO");
$labelRssSource = mi18n("LABEL_RSS_SOURCE");

$rssTitle = "CMS_TEXT[1]";
$rssLink = "CMS_TEXT[2]";
$rssDescription = "CMS_HTML[1]";
$rssConfiguration = '';
$rssLogo = "CMS_IMGEDITOR[1]";
$rssLogoDisplay = "CMS_IMG[1]";
$rssSource = $filename;

$tpl = cSmartyFrontend::getInstance();
$tpl->assign('label_rss_title', $labelRssTitle);
$tpl->assign('label_rss_link', $labelRssLink);
$tpl->assign('label_rss_description', $labelRssDescription);
$tpl->assign('label_rss_configuration', $labelRssConfiguration);
$tpl->assign('label_rss_h1', $labelRssH1);
$tpl->assign('label_rss_logo', $labelRssLogo);
$tpl->assign('label_rss_source', $labelRssSource);
$tpl->assign('rss_title', $rssTitle);
$tpl->assign('rss_source', $rssSource);
$tpl->assign('rss_link', $rssLink);
$tpl->assign('rss_logo', $rssLogo);
$tpl->assign('rss_logo_display', $rssLogoDisplay);
$tpl->assign('rss_description', $rssDescription);
$tpl->assign('rss_configuration', $rssConfiguration);
$tpl->display('rss_edit.tpl');

echo "CMS_TEASER[5]";

$art = new cApiArticleLanguage(cRegistry::getArticleLanguageId());
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

$imgId = $art->getContent("CMS_IMG", 1);

if ((int) $imgId > 0) {
    $upload = new cApiUpload($imgId);
    $rssLogo = $cfgClient[$client]['path']['htmlpath'] . 'upload/' . $upload->get('dirname') . $upload->get('filename');

    $rssImage = $rssChannel->addChild('image');
    $rssImage->addChild('url', $rssLogo);
    $rssImage->addChild('title', $art->getContent("CMS_TEXT", 1));
    $rssImage->addChild('link', $art->getContent("CMS_TEXT", 2));
}

foreach ($articles as $article) {
    $child = $rssChannel->addChild('item');
    $title = strip_tags($article->getContent('HTMLHEAD', 1));
    $text = strip_tags($article->getContent('HTML', 1));
    $text = capiStrTrimAfterWord($text, $configuration['teaser_character_limit']);
    $link = $cfgClient[$client]['path']['htmlpath'] . $article->getLink();

    $child->addChild('title', conHtmlSpecialChars($title));
    $child->addChild('link', conHtmlSpecialChars($link));
    $child->addChild('description', conHtmlSpecialChars($text));
    $child->addChild('pubDate', date('D, d M Y H:i:s T', strtotime($article->getField('published'))));
}

$result = mi18n("LABEL_RSS_CREATION_FAILED");
if (isset($cfgClient[$client]['xml']['frontendpath'])) {
    if (false === cFileHandler::exists($cfgClient[$client]['xml']['frontendpath'])) {
        cDirHandler::create($cfgClient[$client]['xml']['frontendpath'], true);
    }
    // try to write xml to disk
    $success = $rssFeed->asXML($filename);
    if (false !== $success) {
        $result = mi18n("LABEL_RSS_CREATED");
    }
    
}

$tpl = cSmartyFrontend::getInstance();
$tpl->assign('RESULT_MSG', $result);
$tpl->display('result.tpl');

?>