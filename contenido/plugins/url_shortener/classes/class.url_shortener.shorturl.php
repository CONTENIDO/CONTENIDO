<?php

/**
 * This file contains the Plugin Manager API classes.
 *
 * @package    Plugin
 * @subpackage UrlShortener
 * @author     Simon Sprankel
 * @copyright  four for business AG <www.4fb.de>
 * @license    https://www.contenido.org/license/LIZENZ.txt
 * @link       https://www.4fb.de
 * @link       https://www.contenido.org
 */

// assert CONTENIDO framework
defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Plugin Manager API classes.
 *
 * @author Ingo van Peeren
 * @package    Plugin
 * @subpackage UrlShortener
 * @method cApiShortUrl createNewItem
 * @method cApiShortUrl|bool next
 */
class cApiShortUrlCollection extends ItemCollection {

    /**
     *
     * @var int
     */
    const ERR_IS_CLIENT_FOLDER = 1;

    /**
     *
     * @var int
     */
    const ERR_TOO_SHORT = 2;

    /**
     *
     * @var int
     */
    const ERR_INVALID_CHARS = 3;

    /**
     *
     * @var int
     */
    const ERR_IS_ARTICLE_ALIAS = 4;

    /**
     *
     * @var int
     */
    const ERR_IS_CATEGORY_ALIAS = 5;

    /**
     *
     * @var int
     */
    const ERR_ALREADY_EXISTS = 6;

    /**
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        parent::__construct(cRegistry::getDbTableName('url_shortener_shorturl'), 'idshorturl');
        $this->_setItemClass('cApiShortUrl');
    }

    /**
     *
     * @param string $shorturl
     * @param int    $idart
     * @param int    $idlang
     * @param int    $idclient
     *
     * @return cApiShortUrl
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function create($shorturl, $idart = NULL, $idlang = NULL, $idclient = NULL) {
        if (is_null($idart)) {
            $idart = cRegistry::getArticleId();
        }
        if (is_null($idlang)) {
            $idlang = cRegistry::getLanguageId();
        }
        if (is_null($idclient)) {
            $idclient = cRegistry::getClientId();
        }

        /** @var cApiShortUrl $item */
        $item = $this->createNewItem();
        $item->set('shorturl', $shorturl);
        $item->set('idart', $idart);
        $item->set('idlang', $idlang);
        $item->set('idclient', $idclient);
        $item->set('created', date('Y-m-d H:i:s'));
        $item->store();

        return $item;
    }

    /**
     * Checks whether the given short URL is valid with the following criteria:
     * - given url is not a directory in the client folder
     * - given url respects minimum length
     * - given url contains only valid characters
     * - given url is not an article or category alias
     *
     * @param string $shorturl the short URL to check
     *
     * @return int boolean error code if the given shorturl is invalid or true
     *         if it is valid
     * @throws cDbException
     */
    public function isValidShortUrl($shorturl) {
        $cfg = cRegistry::getConfig();

        if (cString::getStringLength(trim($shorturl)) === 0) {
            return true;
        }

        // check if given shorturl is a directory in the client folder
        $exclude = scandir(cRegistry::getFrontendPath());
        if (is_array($cfg['url_shortener']['exlude_dirs'])) {
            $exclude = array_merge($exclude, $cfg['url_shortener']['exlude_dirs']);
        }
        if (in_array($shorturl, $exclude)) {
            return self::ERR_IS_CLIENT_FOLDER;
        }

        // check if given shorturl respects minimum length
        $minLength = 3;
        if (is_numeric($cfg['url_shortener']['minimum_length'])) {
            $minLength = $cfg['url_shortener']['minimum_length'];
        }
        if (cString::getStringLength($shorturl) < $minLength) {
            return self::ERR_TOO_SHORT;
        }

        // check if given shorturl contains only valid characters
        if (isset($cfg['url_shortener']['allowed_chars'])) {
            if (!preg_match($cfg['url_shortener']['allowed_chars'], $shorturl)) {
                return self::ERR_INVALID_CHARS;
            }
        }

        // check if there is an article or category alias with this name
        $artLangColl = new cApiArticleLanguageCollection();
        $artLangColl->select("urlname='" . $shorturl . "'");
        if ($artLangColl->count() > 0) {
            return self::ERR_IS_ARTICLE_ALIAS;
        }
        $catLangColl = new cApiCategoryLanguageCollection();
        $catLangColl->select("urlname='" . $shorturl . "'");
        if ($catLangColl->count() > 0) {
            return self::ERR_IS_CATEGORY_ALIAS;
        }

        return true;
    }
}

/**
 * @author Ingo van Peeren
 * @package    Plugin
 * @subpackage UrlShortener
 */
class cApiShortUrl extends Item {
    /**
     * Constructor Function
     *
     * @param mixed $id Specifies the ID of item to load
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($id = false) {
        parent::__construct(cRegistry::getDbTableName('url_shortener_shorturl'), 'idshorturl');
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }
}
