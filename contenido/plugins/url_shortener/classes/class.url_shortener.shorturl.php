<?php
/**
 * Plugin Manager API classes
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
class cApiShortUrlCollection extends ItemCollection {

    public function __construct() {
        $cfg = cRegistry::getConfig();
        parent::__construct($cfg['tab']['url_shortener']['shorturl'], 'idshorturl');
        $this->_setItemClass('cApiShortUrl');
    }

    public function create($shorturl, $idart = null, $idlang = null, $idclient = null) {
        if (is_null($idart)) {
            $idart = cRegistry::getArticleId();
        }
        if (is_null($idlang)) {
            $idlang = cRegistry::getLanguageId();
        }
        if (is_null($idclient)) {
            $idclient = cRegistry::getClientId();
        }

        $item = parent::createNewItem();
        $item->set('shorturl', $shorturl);
        $item->set('idart', $idart);
        $item->set('idlang', $idlang);
        $item->set('idclient', $idclient);
        $item->set('created', date('Y-m-d H:i:s'));
        $item->store();

        return $item;
    }

}
class cApiShortUrl extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $id Specifies the ID of item to load
     */
    public function __construct($id = false) {
        $cfg = cRegistry::getConfig();
        parent::__construct($cfg['tab']['url_shortener']['shorturl'], 'idshorturl');
        if ($id !== false) {
            $this->loadByPrimaryKey($id);
        }
    }

}
