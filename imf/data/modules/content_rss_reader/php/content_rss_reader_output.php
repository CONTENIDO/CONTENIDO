<?php
/**
 *
 *
 * Description: Display an RSS Feed
 *
 * @version 1.0.0
 * @author Timo Hummel, Andreas Lindner, Alexander Scheider
 * @copyright four for business AG <www.4fb.de>
 *
 *
 */

// assert framework initialization
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');
// get smarty instance
$tpl = Contenido_SmartyWrapper::getInstance();

$urlLabel = mi18n("URL");
$templateLabel = mi18n("SELECT_TEMPLATE");
$countEntriesLabel = mi18n("COUNT_ENTRIES");
$save = mi18n("SAVE");
$label_overview = mi18n("LABEL_OVERVIEW");
$nothingSelectedLabel = mi18n("NOTHING_SELECTED");
$hostLabel = mi18n("HOST");
$errorMessage = mi18n("COULD_NOT_READ_RESSOURCE");

// get id's
$idartlang = cRegistry::getArticleLanguageId();
$idlang = cRegistry::getLanguageId();
$idclient = cRegistry::getClientId();
$options = array();
// create article object
$art = new cApiArticleLanguage($idartlang);

// if post save values in db
if ('POST' === strtoupper($_SERVER['REQUEST_METHOD']) && $_POST['plugin_type'] == 'rss_reader') {
    conSaveContentEntry($idartlang, "CMS_HTML", 5000, $_POST['url']);
    conSaveContentEntry($idartlang, "CMS_HTML", 5001, $_POST['template']);
    conSaveContentEntry($idartlang, "CMS_HTML", 5002, $_POST['count_entries']);
    conSaveContentEntry($idartlang, "CMS_HTML", 5003, $_POST['host']);
}

// get saved content
$url = strip_tags($art->getContent("CMS_HTML", 5000));
$template = strip_tags($art->getContent("CMS_HTML", 5001));
$countEntries = strip_tags($art->getContent("CMS_HTML", 5002));
$host = strip_tags($art->getContent("CMS_HTML", 5003));
$cfgClient = cRegistry::getClientConfig($idclient);

$select = new cHTMLSelectElement("template");
$defaultOption = new cHTMLOptionElement($nothingSelectedLabel, "");
$select->addOptionElement(0, $defaultOption);

$strPath_fs = $cfgClient[$idclient]["path"]["frontend"] . 'templates/';
$handle = opendir($strPath_fs);
while ($entryName = readdir($handle)) {
    if (is_file($strPath_fs . $entryName)) {
        $options[] = array(
            $entryName,
            $entryName
        );
    }
}

$select->autoFill($options);
$select->setSelected(array(
    $template,
    $template
));

// if backend mode set some values and display config tpl
if (cRegistry::isBackendEditMode()) {

    $tpl->assign('urlLabel', $urlLabel);
    $tpl->assign('url', $url);
    $tpl->assign('templateLabel', $templateLabel);
    $tpl->assign('templates', $select->render());
    $tpl->assign('countEntriesLabel', $countEntriesLabel);
    $tpl->assign('countEntries', $countEntries);
    $tpl->assign('label_overview', $label_overview);
    $tpl->assign('save', $save);
    $tpl->assign('hostLabel', $hostLabel);
    $tpl->assign('host', $host);

    $tpl->display('content_rss_reader/template/rss_config_template.tpl');
} else {

    if ($url == "") {
        $sFeed = "http://www.contenido.org/rss/de/news";
    } else {
        $sFeed = $url;
    }

    if ($countEntries == "") {
        $FeedMaxItems = 999;
    } else {
        $FeedMaxItems = cSecurity::toInteger($countEntries);
    }

    $sCachePath = $cfgClient['cache']['path'];

    $oSocket = @fsockopen($host, 80, $errno, $errstr, 3);

    if (!is_resource($oSocket)) {
        if (cFileHandler::exists($sCachePath . "content_rss_reader.xml")) {

            $filePath = $sCachePath . "content_rss_reader.xml";
        } else {
            $oNotification = new cGuiNotification();
            $oNotification->displayNotification(cGuiNotification::LEVEL_ERROR, $errorMessage);
            return;
        }
    } else {

        // get file
        if (!fputs($oSocket, "GET " . $sFeed . " HTTP/1.0\r\nHost:" . $host . " \r\n\r\n")) {
            return;
        }

        $sVendorFile = '';

        while (!feof($oSocket)) {
            $sVendorFile .= fgets($oSocket, 128);
        }

        $sSeparator = strpos($sVendorFile, "\r\n\r\n");
        $sVendorFile = substr($sVendorFile, $sSeparator + 4);
        fclose($oSocket);

        $ret = cFileHandler::create($sCachePath . "content_rss_reader.xml", $sVendorFile);

        if ($ret) {
            $filePath = $sCachePath . "content_rss_reader.xml";
        }
    }

    if (cFileHandler::exists($sCachePath . "content_rss_reader.xml")) {

        $reader = new cXmlReader();
        $reader->load($filePath);

        for ($iCnt = 0; $iCnt < $FeedMaxItems; $iCnt++) {
            $title = $reader->getXpathValue('*/channel/item/title', $iCnt);
            $link = $reader->getXpathValue('*/channel/item/link', $iCnt);
            $description = $reader->getXpathValue('*/channel/item/description', $iCnt);
            $date = $reader->getXpathValue('*/channel/item/pubDate', $iCnt);


            $begin = strpos($date,',');
            $end = strlen($date)-strlen(strchr($date,'+'));
            $date = substr($date,$begin+2,(int)$end-9);
            $date = substr_replace($date, '.', 2, 0);

            $title = conHtmlentities(utf8_encode($title), ENT_QUOTES);
            $description = conHtmlentities(utf8_encode($description), ENT_QUOTES);

            $tpl->assign('title', $title);
            $tpl->assign('description', $description);
            $tpl->assign('date', $date);
            $tpl->assign('link', $link);

            $tpl->display($strPath_fs . $template);
        }
    } else {
    }
}



?>