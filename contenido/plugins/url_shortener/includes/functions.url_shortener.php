<?php
/**
 * This file contains the Plugin Manager configurations.
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

/**
 * Constructs the HTML code containing table rows which are added to the end of
 * the article edit form
 *
 * @return string rendered HTML code
 */
function piUsEditFormAdditionalRows($idart, $idlang, $idclient, $disabled) {
    $shortUrl = new cApiShortUrl();
    $shortUrl->loadByMany(array(
        'idart' => $idart,
        'idlang' => $idlang,
        'idclient' => $idclient
    ));

    $tr = new cHTMLTableRow();

    $td = new cHTMLTableData();
    $td->setClass('text_medium');
    $td->setContent(i18n('Short URL', 'url_shortener'));
    $tr->appendContent($td);

    $td = new cHTMLTableData();
    $td->setClass('text_medium');
    $textbox = new cHTMLTextbox('url_shortener_shorturl', $shortUrl->get('shorturl'), 24, '', '', $disabled);
    $td->setContent($textbox.' <img class="vAlignMiddle" title="'. i18n('INFO', 'url_shortener') .'" src="images/info.gif">');
    $tr->appendContent($td);

    return $tr->render();
}

/**
 * Function is called after an article has been saved.
 * Checks whether a short URL has been given via $_POST and saves/deletes it.
 *
 * @param array $values the values which are saved
 */
function piUsConSaveArtAfter($editedIdArt, $values) {
    // if not all parameters have been given, do nothing
    if (!isset($_POST['url_shortener_shorturl']) || $_POST['url_shortener_shorturl'] == '' || !isset($editedIdArt)) {
        return;
    }
    $shorturl = $_POST['url_shortener_shorturl'];
    $idart = $editedIdArt;
    $idlang = cRegistry::getLanguageId();
    $idclient = cRegistry::getClientId();
    $shortUrlItem = new cApiShortUrl();
    $shortUrlItem->loadByMany(array(
        'idart' => $idart,
        'idlang' => $idlang,
        'idclient' => $idclient
    ));
    // if given shorturl is already in use, show error message
    $checkShortUrlItem = new cApiShortUrl();
    $checkShortUrlItem->loadBy('shorturl', $shorturl);
    if ($checkShortUrlItem->isLoaded()) {
        // if shorturl has not been changed, do nothing
        if ($shortUrlItem->get('shorturl') === $checkShortUrlItem->get('shorturl')) {
            return;
        }
        // TODO add warning to session as soon as this is possible (depends
        // CON-772)
        // $session = cRegistry::getSession();
        // $session->addWarning($message);
        $message = piUsGetErrorMessage(cApiShortUrlCollection::ERR_ALREADY_EXISTS, $shortUrlItem);
        $notification = new cGuiNotification();
        $notification->displayNotification(cGuiNotification::LEVEL_ERROR, $message);
        return;
    }
    // check if given shorturl is valid
    $shortUrlColl = new cApiShortUrlCollection();
    $errorCode = $shortUrlColl->isValidShortUrl($shorturl);
    if ($errorCode !== true) {
        $message = piUsGetErrorMessage($errorCode);
        // TODO add warning to session as soon as this is possible (depends
        // CON-772)
        // $session = cRegistry::getSession();
        // $session->addWarning($message);
        $notification = new cGuiNotification();
        $notification->displayNotification(cGuiNotification::LEVEL_ERROR, $message);
        return;
    }
    if ($_POST['url_shortener_shorturl'] === '') {
        // delete short URL if it exists
        if ($shortUrlItem->isLoaded()) {
            $shortUrlColl->delete($shortUrlItem->get('idshorturl'));
        }
    } else {
        // a short URL has been given, so save it
        if ($shortUrlItem->isLoaded()) {
            // short URL already exists, update it
            $shortUrlItem->set('shorturl', $shorturl);
            $shortUrlItem->store();
        } else {
            // short URL does not exist yet, create a new one
            $shortUrlColl->create($shorturl, $idart, $idlang, $idclient);
        }
    }
}

/**
 * Computes an error message which describes the given error code.
 *
 * @param int $errorCode the error code
 * @return string the error message describing the given error code
 */
function piUsGetErrorMessage($errorCode, $shortUrlItem = NULL) {
    switch ($errorCode) {
        case cApiShortUrlCollection::ERR_INVALID_CHARS:
            return i18n('The entered short URL contains invalid characters!', 'url_shortener');
            break;
        case cApiShortUrlCollection::ERR_IS_ARTICLE_ALIAS:
            return i18n('The entered short URL is already an article alias!', 'url_shortener');
            break;
        case cApiShortUrlCollection::ERR_IS_CATEGORY_ALIAS:
            return i18n('The entered short URL is already a category alias!', 'url_shortener');
            break;
        case cApiShortUrlCollection::ERR_IS_CLIENT_FOLDER:
            return i18n('The entered short URL is a subdirectory of the client directory!', 'url_shortener');
            break;
        case cApiShortUrlCollection::ERR_TOO_SHORT:
            return i18n('The entered short URL is too short!', 'url_shortener');
            break;
        case cApiShortUrlCollection::ERR_ALREADY_EXISTS:
            $message = i18n('The entered short URL already exists!', 'url_shortener');
            $message .= '<br />';
            if ($shortUrlItem !== NULL) {
                // add the client name to the error message
                $clientColl = new cApiClientCollection();
                $message .= i18n('Client', 'url_shortener') . ': ' . $clientColl->getClientname($shortUrlItem->get('idclient'));;
                $message .= '<br />';
                // add the language name to the error message
                $langColl = new cApiLanguageCollection();
                $message .= i18n('Language', 'url_shortener') . ': ' . $langColl->getLanguageName($shortUrlItem->get('idlang'));
                $message .= '<br />';
                // add the category name to the error message
                $catArt = new cApiCategoryArticle();
                $catArt->loadBy('idart', $shortUrlItem->get('idart'));
                $catLang = new cApiCategoryLanguage();
                $catLang->loadByCategoryIdAndLanguageId($catArt->get('idcat'), $shortUrlItem->get('idlang'));
                $message .= i18n('Category', 'url_shortener') . ': ' . $catLang->get('name');
                $message .= '<br />';
                // add the article name to the error message
                $artlang = new cApiArticleLanguage();
                $artlang->loadByArticleAndLanguageId($shortUrlItem->get('idart'), $shortUrlItem->get('idlang'));
                $message .= i18n('Article', 'url_shortener') . ': ' . $artlang->get('title');
            }
            return $message;
            break;
    }
    return i18n('The entered short URL is not valid!', 'url_shortener');
}

/**
 * Function is called after the plugins have been loaded.
 * If the string placeholder in the example URL http://www.domain.de/placeholder
 * is a defined short URL, the user is redirected to the correct URL.
 */
function piUsAfterLoadPlugins() {
    $requestUri = $_SERVER['REQUEST_URI'];
    $shorturl = substr($requestUri, strrpos($requestUri, '/') + 1);
    $shortUrlItem = new cApiShortUrl();
    $shortUrlItem->loadBy('shorturl', $shorturl);
    if ($shortUrlItem->isLoaded()) {
        $uriParams = array(
            'idart' => $shortUrlItem->get('idart'),
            'lang' => $shortUrlItem->get('idlang')
        );
        $url = cUri::getInstance()->build($uriParams, true);
        header('Location:' . $url);
        exit();
    }
}
