<?php
/**
 * Plugin Manager configurations
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

/**
 * Constructs the HTML code containing table rows which are added to the end of
 * the article edit form
 *
 * @return string rendered HTML code
 */
function piusEditFormAdditionalRows($idart, $idlang, $idclient) {
    $shortUrl = new cApiShortUrl();
    $shortUrl->loadByMany(array(
        'idart' => $idart,
        'idlang' => $idlang,
        'idclient' => $idclient
    ));

    $tr = new cHTMLTableRow();
    $tr->setAttribute('valign', 'top');

    $td = new cHTMLTableData();
    $td->setClass('text_medium');
    $td->setContent(i18n('Short URL', 'url_shortener'));
    $tr->appendContent($td);

    $td = new cHTMLTableData();
    $td->setClass('text_medium');
    $textbox = new cHTMLTextbox('url_shortener_shorturl', $shortUrl->get('shorturl'));
    $td->setContent($textbox);
    $tr->appendContent($td);

    return $tr->render();
}

/**
 * Function is called after an article has been saved.
 * Checks whether a short URL has been given via $_POST and saves/deletes it.
 *
 * @param array $values the values which are saved
 */
function piusConSaveArtAfter($values) {
    // if not all parameters have been given, do nothing
    if (!isset($_POST['url_shortener_shorturl']) || !isset($values['idart']) || !isset($values['idlang']) || !isset($values['idclient'])) {
        return;
    }
    $shorturl = $_POST['url_shortener_shorturl'];
    $idart = $values['idart'];
    $idlang = $values['idlang'];
    $idclient = $values['idclient'];
    $shortUrlItem = new cApiShortUrl();
    $shortUrlItem->loadByMany(array(
        'idart' => $idart,
        'idlang' => $idlang,
        'idclient' => $idclient
    ));
    $shortUrlColl = new cApiShortUrlCollection();
    // if given shorturl is already in use, show error message
    // TODO check in a different function
    $shortUrlColl->select("shorturl='" . $shorturl . "'");
    if ($shortUrlColl->count() !== 0) {
        // TODO add warning to session as soon as this is possible (depends CON-772)
        // TODO add info in which client, language, category and article the shorturl is already in use
        // $session = cRegistry::getSession();
        // $session->addWarning(i18n('The entered short URL already exists', 'url_shortener'));
        echo i18n('The entered short URL already exists', 'url_shortener');
        return;
    }
    // TODO check if given shorturl is valid (document 2.5)
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
