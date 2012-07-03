<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Content entry class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO API
 * @version    1.0
 * @author     Dominik Ziegler
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * Content collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiContentCollection extends ItemCollection {
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['content'], 'idcontent');
        $this->_setItemClass('cApiContent');
    }

    /**
     * Creates a content entry.
     * @param int $idArtLang
     * @param int $idType
     * @param int $typeId
     * @param string $value
     * @param int $version
     * @param  string  $author
     * @param  string  $created
     * @param  string  $lastmodified
     * @return cApiContent
     */
    public function create($idArtLang, $idType, $typeId, $value, $version, $author = '', $created = '', $lastmodified = '')
    {
        global $auth;

        if (empty($author)) {
            $author = $auth->auth['uname'];
        }
        if (empty($created)) {
            $created = date('Y-m-d H:i:s');
        }
        if (empty($lastmodified)) {
            $lastmodified = date('Y-m-d H:i:s');
        }

        $oItem = parent::createNewItem();

        $oItem->set('idartlang', (int) $idArtLang);
        $oItem->set('idtype', (int) $idType);
        $oItem->set('typeid', (int) $typeId);
        $oItem->set('value', $this->escape($value));
        $oItem->set('version', (int) $version);
        $oItem->set('author', $this->escape($author));
        $oItem->set('created', $this->escape($created));
        $oItem->set('lastmodified', $this->escape($lastmodified));

        $oItem->store();

        return $oItem;
    }

}


/**
 * Content item
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiContent extends Item {
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['content'], 'idcontent');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }
}

?>