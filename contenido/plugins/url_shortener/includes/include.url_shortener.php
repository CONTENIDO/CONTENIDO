<?php
/**
 * Description:
 * Backend page for showing and editing short URLs.
 *
 * @package plugin
 * @subpackage URL Shortener
 * @version SVN Revision $Rev:$
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


global $action, $idshorturl;

$page = new cGuiPage('url_shortener');

// check permissions
$auth = cRegistry::getAuth();
$userPerm = explode(',', $auth->auth['perm']);
if (!in_array('sysadmin', $userPerm)) {
    $page->displayError(i18n('Short URLs can only be managed by system administrators!', 'url_shortener'));
}

// process the actions
if ($action === 'url_shortener_delete' && !empty($idshorturl)) {
    $shortUrlColl = new cApiShortUrlCollection();
    if ($shortUrlColl->delete($idshorturl)) {
        $page->displayInfo(i18n('The short URL has successfully been deleted!', 'url_shortener'));
    }
} else if ($action === 'url_shortener_edit' && !empty($idshorturl)) {
    // TODO edit the shorturl
}

// show notification if there are no short URLs yet
$shortUrlColl = new cApiShortUrlCollection();
$shortUrlColl->query();
if ($shortUrlColl->count() === 0) {
    $page->displayInfo(i18n('No short URLs have been defined yet!', 'url_shortener'));
    $page->render();
    exit;
}

$table = new cHTMLTable();
$table->setClass('generic');
$table->setWidth('100%');

// construct the table header
$theader = new cHTMLTableHeader();
$tableHeads = array(
    i18n('Client', 'url_shortener'),
    i18n('Language', 'url_shortener'),
    i18n('Category', 'url_shortener'),
    i18n('Article', 'url_shortener'),
    i18n('SEO URL', 'url_shortener'),
    i18n('Short URL', 'url_shortener'),
    i18n('Creation Date', 'url_shortener'),
    i18n('URL With idart And idlang', 'url_shortener'),
    i18n('Actions', 'url_shortener')
);
foreach ($tableHeads as $tableHead) {
    $th = new cHTMLTableHead();
    $th->setContent($tableHead);
    $theader->appendContent($th);
}
$table->appendContent($theader);

// construct the table body
$tbody = new cHTMLTableBody();

// TODO add paging functionality via $shortUrlColl->setLimit();
while (($shortUrl = $shortUrlColl->next()) !== false) {
    $tr = new cHTMLTableRow();
    $contents = array();

    // get the client name
    $apiClient = new cApiClient($shortUrl->get('idclient'));
    $contents[] = $apiClient->get('name');

    // get the language name
    $langItem = new cApiLanguage($shortUrl->get('idlang'));
    $contents[] = $langItem->get('name');

    // get the category
    $catArt = new cApiCategoryArticle();
    $catArt->loadBy('idart', $shortUrl->get('idart'));
    $catLang = new cApiCategoryLanguage();
    $catLang->loadByCategoryIdAndLanguageId($catArt->get('idcat'), $shortUrl->get('idlang'));
    $contents[] = $catLang->get('name');

    // get the article
    $artlang = new cApiArticleLanguage();
    $artlang->loadByArticleAndLanguageId($shortUrl->get('idart'), $shortUrl->get('idlang'));
    $contents[] = $artlang->get('title');

    // construct SEO URL
    $uriParams = array(
        'idart' => $shortUrl->get('idart'),
        'lang' => $shortUrl->get('idlang')
    );
    $url = cUri::getInstance()->build($uriParams, true);
    $contents[] = $url;

    // get short URL
    $contents[] = $shortUrl->get('shorturl');

    // get creation date TODO format the date according to settings
    $contents[] = $shortUrl->get('created');

    // construct URL with idart and idlang
    $uriBuilder = cUriBuilderFactory::getUriBuilder('front_content');
    $uriBuilder->buildUrl($uriParams, true);
    $contents[] = $uriBuilder->getUrl();

    // create the action buttons
    $link = new cHTMLLink(cRegistry::getBackendUrl() . 'main.php?area=url_shortener&frame=4&action=url_shortener_delete&idshorturl=' . $shortUrl->get('idshorturl') . '&contenido=' . cRegistry::getSession()->id);
    $image = new cHTMLImage('images/but_delete.gif');
    $link->setContent($image);
    $contents[] = $link;

    // append all contents to the table row
    foreach ($contents as $content) {
        $td = new cHTMLTableData();
        $td->setContent($content);
        $tr->appendContent($td);
    }
    $tbody->appendContent($tr);
}

$table->appendContent($tbody);

$page->setContent($table);
$page->render();
