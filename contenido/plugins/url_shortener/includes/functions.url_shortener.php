<?php

/**
 * This file contains the Plugin Manager configurations.
 *
 * @package    Plugin
 * @subpackage UrlShortener
 * @author     Simon Sprankel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Constructs the HTML code containing table rows which are added to the end of
 * the article edit form
 *
 * @param int $articleId
 * @param int $languageId
 * @param int $clientId
 * @param int|bool $disabled (0 or 1)
 * @return string rendered HTML code
 * @throws cDbException|cException
 */
function pius_editFormAdditionalRows($articleId, $languageId, $clientId, $disabled)
{
    $disabled = cSecurity::toBoolean($disabled);

    $shortUrl = new cApiShortUrl();
    $shortUrl->loadByMany([
        'idart' => $articleId,
        'idlang' => $languageId,
        'idclient' => $clientId
    ]);

    $tr = new cHTMLTableRow();

    $td = new cHTMLTableData();
    $td->setClass('text_medium no_wrap');
    $td->setContent(i18n('Short URL', 'url_shortener'));
    $tr->appendContent($td);

    $infoButton = new cGuiBackendHelpbox(i18n('INFO', 'url_shortener'));

    $td = new cHTMLTableData();
    $td->setClass('text_medium');
    $textbox = new cHTMLTextbox(
        'url_shortener_shorturl',
        $shortUrl->get('shorturl'),
        24,
        0,
        '',
        $disabled,
        NULL,
        '',
        'textField'
    );
    $td->setContent($textbox . ' ' . $infoButton->render());
    $tr->appendContent($td);

    return $tr->render();
}

/**
 * Function is called after an article has been saved.
 * Checks whether a short URL has been given via $_POST and saves/deletes it.
 *
 * @param int|null $articleId The id of the edited article.
 * @param array $values the values which are saved
 * @throws cDbException|cException|cInvalidArgumentException
 */
function pius_conSaveArtAfter($articleId, $values)
{
    // if not all parameters have been given, do nothing
    if (!isset($_POST['url_shortener_shorturl']) || !isset($articleId)) {
        return;
    }
    $shorturl = $_POST['url_shortener_shorturl'];
    $languageId = cRegistry::getLanguageId();
    $clientId = cRegistry::getClientId();
    $shortUrlItem = new cApiShortUrl();
    $shortUrlItem->loadByMany([
        'idart' => $articleId,
        'idlang' => $languageId,
        'idclient' => $clientId
    ]);
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
        $message = pius_getErrorMessage(cApiShortUrlCollection::ERR_ALREADY_EXISTS, $shortUrlItem);
        $notification = new cGuiNotification();
        $notification->displayNotification(cGuiNotification::LEVEL_ERROR, $message);
        return;
    }
    // check if given shorturl is valid
    $shortUrlColl = new cApiShortUrlCollection();
    $errorCode = $shortUrlColl->isValidShortUrl($shorturl);
    if ($errorCode !== true) {
        $message = pius_getErrorMessage($errorCode);
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
            $item = cApiCecHook::executeAndReturn('ContenidoPlugin.UrlShortener.BeforeRemove', $shortUrlItem);
            if ($item instanceof cApiShortUrl) {
                $shortUrlItem = $item;
            }

            $shortUrlColl->delete($shortUrlItem->get('idshorturl'));
        }
    } else {
        // a short URL has been given, so save it
        if ($shortUrlItem->isLoaded()) {
            // short URL already exists, update it
            $oldShortUrlItem = clone $shortUrlItem;
            $shortUrlItem->set('shorturl', $shorturl);

            $item = cApiCecHook::executeAndReturn('ContenidoPlugin.UrlShortener.BeforeEdit', $shortUrlItem, $oldShortUrlItem);
            if ($item instanceof cApiShortUrl) {
                $shortUrlItem = $item;
            }

            $shortUrlItem->store();
        } else {
            // short URL does not exist yet, create a new one
            $shortUrlItem = $shortUrlColl->create($shorturl, $articleId, $languageId, $clientId);
            cApiCecHook::executeAndReturn('ContenidoPlugin.UrlShortener.AfterCreate', $shortUrlItem);
        }
    }
}

/**
 * Computes an error message which describes the given error code.
 *
 * @param int $errorCode the error code
 * @return string The error message describing the given error code
 * @throws cDbException|cException
 */
function pius_getErrorMessage($errorCode, ?cApiShortUrl $shortUrlItem = NULL)
{
    switch ($errorCode) {
        case cApiShortUrlCollection::ERR_INVALID_CHARS:
            return i18n('The entered short URL contains invalid characters!', 'url_shortener');
        case cApiShortUrlCollection::ERR_IS_ARTICLE_ALIAS:
            return i18n('The entered short URL is already an article alias!', 'url_shortener');
        case cApiShortUrlCollection::ERR_IS_CATEGORY_ALIAS:
            return i18n('The entered short URL is already a category alias!', 'url_shortener');
        case cApiShortUrlCollection::ERR_IS_CLIENT_FOLDER:
            return i18n('The entered short URL is a subdirectory of the client directory!', 'url_shortener');
        case cApiShortUrlCollection::ERR_TOO_SHORT:
            return i18n('The entered short URL is too short!', 'url_shortener');
        case cApiShortUrlCollection::ERR_ALREADY_EXISTS:
            $message = i18n('The entered short URL already exists!', 'url_shortener');
            $message .= '<br />';
            if ($shortUrlItem !== NULL) {
                // add the client name to the error message
                $clientColl = new cApiClientCollection();
                $message .= i18n('Client', 'url_shortener') . ': ' . $clientColl->getClientname($shortUrlItem->get('idclient'));
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
    }
    return i18n('The entered short URL is not valid!', 'url_shortener');
}

/**
 * Function is called after the plugins have been loaded.
 * If the string placeholder in the example URL http://www.domain.de/placeholder
 * is a defined short URL, the user is redirected to the correct URL.
 *
 * @throws cDbException|cException|cInvalidArgumentException
 */
function pius_afterLoadPlugins()
{
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    $shorturl = cString::getPartOfString($requestUri, cString::findLastPos($requestUri, '/') + 1);
    $shortUrlItem = new cApiShortUrl();
    $shortUrlItem->loadBy('shorturl', $shorturl);
    if ($shortUrlItem->isLoaded()) {
        $uriParams = [
            'idart' => $shortUrlItem->get('idart'),
            'lang' => $shortUrlItem->get('idlang')
        ];
        $url = cUri::getInstance()->build($uriParams, true);
        header('Location:' . $url);
        exit();
    }
}

/**
 * Chain for delete short urls at con_deleteart action
 *
 * @param int $articleId The id of deleted article
 * @return int Number of deleted entries
 * @throws cDbException|cException|cInvalidArgumentException
 */
function pius_conDeleteArtAfter($articleId)
{
    $count = 0;
    if (cRegistry::getPerm()->have_perm_area_action('url_shortener', 'url_shortener_delete')) {
        $articleId = cSecurity::toInteger($articleId);
        $shortUrlColl = new cApiShortUrlCollection();
        $count = $shortUrlColl->deleteBy('idart', $articleId);
    }

    return $count;
}

/**
 * @deprecated Since URL Shortener 2.0.2, use {@see pius_editFormAdditionalRows()} instead
 */
function piUsEditFormAdditionalRows($articleId, $languageId, $clientId, $disabled)
{
    cDeprecated(__FUNCTION__ . ' is Since URL Shortener 2.0.2, use pius_editFormAdditionalRows() instead');
    return pius_editFormAdditionalRows($articleId, $languageId, $clientId, $disabled);
}
/**
 * @deprecated Since URL Shortener 2.0.2, use {@see pius_conSaveArtAfter()} instead
 */
function piUsConSaveArtAfter($articleId, $values)
{
    cDeprecated(__FUNCTION__ . ' is Since URL Shortener 2.0.2, use pius_conSaveArtAfter() instead');
    pius_conSaveArtAfter($articleId, $values);
}
/**
 * @deprecated Since URL Shortener 2.0.2, use {@see pius_getErrorMessage()} instead
 */
function piUsGetErrorMessage($errorCode, $shortUrlItem = NULL)
{
    cDeprecated(__FUNCTION__ . ' is Since URL Shortener 2.0.2, use pius_getErrorMessage() instead');
    return pius_getErrorMessage($errorCode, $shortUrlItem);
}
/**
 * @deprecated Since URL Shortener 2.0.2, use {@see pius_afterLoadPlugins()} instead
 */
function piUsAfterLoadPlugins()
{
    cDeprecated(__FUNCTION__ . ' is Since URL Shortener 2.0.2, use pius_afterLoadPlugins() instead');
    pius_afterLoadPlugins();
}
/**
 * @deprecated Since URL Shortener 2.0.2, use {@see pius_conDeleteArtAfter()} instead
 */
function piUseConDeleteArtAfter($articleId)
{
    cDeprecated(__FUNCTION__ . ' is Since URL Shortener 2.0.2, use pius_conDeleteArtAfter() instead');
    return pius_conDeleteArtAfter($articleId);
}
