<?php
/**
 * This file contains the Backend page for showing and editing short URLs.
 *
 * @package Plugin
 * @subpackage UrlShortener
 * @version SVN Revision $Rev:$
 *
 * @author Simon Sprankel
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

global $action, $cfg;

$page = new cGuiPage('url_shortener', 'url_shortener');

// check permissions
$auth = cRegistry::getAuth();
$userPerm = explode(',', $auth->auth['perm']);

if (!$perm->have_perm_area_action('url_shortener')) {
    $page->displayError(i18n('Short URLs can only be managed by authorized user!', 'url_shortener'));
}

// process the actions
if ($action === 'url_shortener_delete' && !empty($_POST['idshorturl']) && $perm->have_perm_area_action('url_shortener', 'url_shortener_delete')) {
    $shortUrlColl = new cApiShortUrlCollection();
    if ($shortUrlColl->delete($_POST['idshorturl'])) {
        $page->displayInfo(i18n('The short URL has successfully been deleted!', 'url_shortener'));
    }
} else if ($action === 'url_shortener_edit' && !empty($_POST['idshorturl']) && $perm->have_perm_area_action('url_shortener', 'url_shortener_edit')) {
    // only do something if shorturl has been changed
    $shortUrlItem = new cApiShortUrl($_POST['idshorturl']);
    if ($shortUrlItem->isLoaded() && $shortUrlItem->get('shorturl') !== $_POST['newshorturl']) {
        $valid = true;
        // if given shorturl is already in use, show error message
        $newShortUrlItem = new cApiShortUrl();
        $newShortUrlItem->loadBy('shorturl', $_POST['newshorturl']);
        if ($newShortUrlItem->isLoaded()) {
            $message = piUsGetErrorMessage(cApiShortUrlCollection::ERR_ALREADY_EXISTS, $newShortUrlItem);
            $notification = new cGuiNotification();
            $page->displayError($message);
            $valid = false;
        }
        // check if given shorturl is valid
        $shortUrlColl = new cApiShortUrlCollection();
        $errorCode = $shortUrlColl->isValidShortUrl($_POST['newshorturl']);
        if ($errorCode !== true) {
            $message = piUsGetErrorMessage($errorCode);
            $page->displayError($message);
            $valid = false;
        }
        // edit the shorturl
        $shortUrlItem = new cApiShortUrl($_POST['idshorturl']);
        if ($shortUrlItem->isLoaded() && $valid) {
            $shortUrlItem->set('shorturl', $_POST['newshorturl']);
            if ($shortUrlItem->store()) {
                $page->displayInfo(i18n('Short URL successfully edited!', 'url_shortener'));
            } else {
                $page->displayError(i18n('Short URL could not be saved!', 'url_shortener'));
            }
        }
    }
} else if ($action === 'url_shortener_copy_htaccess' && !empty($_GET['htaccess_type'])) {
    // copy the .htaccess file to the client path
    $validTypes = array(
        'simple',
        'restrictive'
    );
    if (in_array($_GET['htaccess_type'], $validTypes)) {
        $source = $cfg['path']['contenido'] . $cfg['path']['plugins'] . 'url_shortener/files/htaccess_' . $_GET['htaccess_type'] . '.txt';
        $dest = cRegistry::getFrontendPath() . '.htaccess';
        if (cFileHandler::exists($dest)) {
            $page->displayError(i18n('The .htaccess file already exists, so that it has not been copied!', 'url_shortener'));
        } else if (cFileHandler::copy($source, $dest)) {
            $page->displayInfo(i18n('The .htaccess file has been successfully copied to the client path!', 'url_shortener'));
        } else {
            $page->displayError(i18n('The .htaccess file could not be copied to the client path!', 'url_shortener'));
        }
    }
}

// show warning if there is no .htaccess file
if (!cFileHandler::exists(cRegistry::getFrontendPath() . '.htaccess')) {
    $message = i18n('A .htaccess file could not be found. The short URLs will not work!', 'url_shortener');
    $message .= '<br /><br />';
    $link = new cHTMLLink('main.php?area=url_shortener&action=url_shortener_copy_htaccess&htaccess_type=simple&frame=4&contenido=' . cRegistry::getSession()->id);
    $link->setContent(i18n('Copy the simple .htaccess file to the client path.', 'url_shortener'));
    $message .= $link->render();
    $message .= '<br /><br />';
    $link = new cHTMLLink('main.php?area=url_shortener&action=url_shortener_copy_htaccess&htaccess_type=restrictive&frame=4&contenido=' . cRegistry::getSession()->id);
    $link->setContent(i18n('Copy the restrictive .htaccess file to the client path.', 'url_shortener'));
    $message .= $link->render();
    $message .= '<br /><br />';
    $message .= i18n('If CONTENIDO is installed in a subdirectory, you need to change the RewriteBase path in the copied .htaccess file!', 'url_shortener');
    $page->displayWarning($message);
}

// show notification if there are no short URLs yet
$shortUrlColl = new cApiShortUrlCollection();
$shortUrlColl->query();
if ($shortUrlColl->count() === 0) {
    $page->displayInfo(i18n('No short URLs have been defined yet!', 'url_shortener'));
    $page->render();
    exit();
}

// add the edit form to the page
$form = new cHTMLForm('edit_form', '', 'post');
$form->setVar('area', 'url_shortener');
$form->setVar('frame', '4');
$form->setVar('action', '');
$form->setVar('contenido', cRegistry::getSession()->id);
$form->setVar('idshorturl', '');
$form->setVar('newshorturl', '');
$page->appendContent($form);

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
    $tr->setID('shorturl-' . $shortUrl->get('idshorturl'));
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

    // get short URL
    $shortUrlDomain = getEffectiveSetting('url_shortener', 'domain');
    if (empty($shortUrlDomain)) {
        $shortUrlDomain = cRegistry::getFrontendUrl();
    }
    $link = new cHTMLLink($shortUrlDomain . $shortUrl->get('shorturl'));
    $link->setClass('shorturl');
    $link->setTargetFrame('_blank');
    $link->setContent($shortUrlDomain . $shortUrl->get('shorturl'));
    $editLink = new cHTMLLink('javascript: void(0)');
    $editLink->setClass('edit-link');
    $editLink->setEvent('click', 'editShortUrl(' . $shortUrl->get('idshorturl') . ', "' . $shortUrl->get('shorturl') . '"); return false;');
    $editImage = new cHTMLImage('images/but_edithtml.gif');
    $editLink->setContent($editImage);
    $saveLink = new cHTMLLink('javascript: void(0)');
    $saveLink->setClass('save-link');
    $saveLink->setEvent('click', 'saveShortUrl(' . $shortUrl->get('idshorturl') . '); return false;');
    $saveLink->appendStyleDefinition('display', 'none');
    $saveImage = new cHTMLImage('images/but_ok.gif');
    $saveLink->setContent($saveImage);
    $contents[] = $link->render() . $editLink->render() . $saveLink->render();

    // get creation date
    // TODO format the date according to settings
    $contents[] = $shortUrl->get('created');

    // construct URL with idart and idlang
    $uriBuilder = cUriBuilderFactory::getUriBuilder('front_content');
    $uriParams = array(
        'idart' => $shortUrl->get('idart'),
        'lang' => $shortUrl->get('idlang')
    );
    $uriBuilder->buildUrl($uriParams, true);
    $url = $uriBuilder->getUrl();
    $link = new cHTMLLink(cRegistry::getFrontendUrl() . $url);
    $link->setTargetFrame('_blank');
    $link->setContent($url);
    $contents[] = $link;

    // create the action buttons
    $link = new cHTMLLink('javascript: void(0)');
    $link->setEvent('click', 'Con.showConfirmation("' . sprintf(i18n('Do you really want to delete the short URL %s?', 'url_shortener'), $shortUrl->get('shorturl')) . '", function() { deleteShortUrl(' . $shortUrl->get('idshorturl') . '); }); return false;');
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

$page->appendContent($table);
$page->addScript('url_shortener.js');
$page->addStyle('url_shortener.css');
$page->render();
