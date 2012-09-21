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


$page = new cGuiPage('url_shortener');
$page->displayInfo('First version of URL Shortener works ^^');

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

$shortUrlColl = new cApiShortUrlCollection();
// TODO add paging functionality via $shortUrlColl->setLimit();
$shortUrlColl->query();
while (($shortUrl = $shortUrlColl->next()) !== false) {
    $tr = new cHTMLTableRow();
    $contents = array();
    // get the client name
    $client = new cApiClient($shortUrl->get('idclient'));
    $contents[] = $client->get('name');
    // get the language name
    $lang = new cApiLanguage($shortUrl->get('idlang'));
    $contents[] = $lang->get('name');
    // TODO get the category
    $contents[] = 'cat';
    // get the article
    $artlang = new cApiArticleLanguage();
    $artlang->loadByArticleAndLanguageId($shortUrl->get('idart'), $shortUrl->get('idlang'));
    $contents[] = $artlang->get('title');
    // TODO get SEO URL
    $params = array(
        'idart' => $shortUrl->get('idart'),
        'lang' => $shortUrl->get('idlang')
    );
    $url = cUri::getInstance()->build($params, true);
    $contents[] = $url;
    // get short URL
    $contents[] = $shortUrl->get('shorturl');
    // get creation date TODO format the date according to settings
    $contents[] = $shortUrl->get('created');
    // TODO construct URL with idart and idlang

    $contents[] = 'URL with idart and idlang';
    // create the action buttons
    $link = new cHTMLLink('');

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
