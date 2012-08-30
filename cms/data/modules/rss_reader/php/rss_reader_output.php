<?php
/**
 * Description: Display an RSS Feed. Module "Output".
 *
 * @version    1.0.0
 * @author     Timo Hummel, Andreas Lindner
 * @copyright  four for business AG <www.4fb.de>
 *
 * {@internal
 *   created 2005-09-30
 *   $Id$
 * }}
 */

cInclude("pear", "XML/Parser.php");
cInclude("pear", "XML/RSS.php");

if ("CMS_VALUE[0]" == "") {
    $sFeed = "http://www.contenido.org/rss/de/news";
} else {
    $sFeed = "CMS_VALUE[0]";
}

if ("CMS_VALUE[2]" == "") {
    $FeedMaxItems = 999;
} else {
    $FeedMaxItems = intval("CMS_VALUE[2]");
}

// Preparse feed for an encoding due to the poorly designed PHP XML parser
$sFeedContent = substr(@file_get_contents($sFeed),0,1024);

$regExp = "/<\?xml.*encoding=[\"\'](.*)[\"\']\?>/i";

preg_match($regExp,trim($sFeedContent), $matches);

if ($matches[1]) {
    $rss = new XML_RSS($sFeed, $matches[1]);
} else {
    $rss = new XML_RSS($sFeed);
}

$rss->parse();

if (!isset($tpl) || !is_object($tpl)) {
    $tpl = new cTemplate();
}
$tpl->reset();

$i = 0;
foreach ($rss->getItems() as $item) {
    if ($i < $FeedMaxItems) {
        $tpl->set("d", "TITLE", htmlentities($item['title'],ENT_QUOTES));
        $tpl->set("d", "LINK", htmlentities($item['link'],ENT_QUOTES));
        $tpl->set("d", "DESCRIPTION", htmlentities($item['description'],ENT_QUOTES));
        $tpl->set("d", "READ_ON", mi18n("weiterlesen"));
        $tpl->next();
    }
    $i++;
}

$tpl->generate(cRegistry::getFrontendPath() . "templates/" . "CMS_VALUE[1]");

?>