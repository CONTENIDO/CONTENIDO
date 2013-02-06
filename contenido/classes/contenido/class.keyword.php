<?php
/**
 * Keyword Management System
 *
 * @package CONTENIDO API
 * @subpackage Model
 * @version SVN Revision $Rev:$
 * @author Frederic Schneider
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * File collection
 *
 * @package CONTENIDO API
 * @subpackage Model
 */
class cApiKeywordCollection extends ItemCollection {

    /**
     * Constructor
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['keyword'], 'idkeyword');
        $this->_setItemClass('cApiKeyword');
    }

    public function create($keyword, $exp = '', $auto, $self = '', $idlang) {
        $item = parent::createNewItem();

        $keyword = cSecurity::escapeString($keyword);
        $exp = cSecurity::escapeString($exp);
        $auto = cSecurity::escapeString($auto);
        $self = cSecurity::escapeString($self);
        $idlang = cSecurity::toInteger($idlang);

        $item->set('keyword', $keyword);
        $item->set('exp', $exp);
        $item->set('auto', $auto);
        $item->set('self', $self);
        $item->set('idlang', $idlang);

        $item->store();

        return ($item);
    }

}

/**
 * Keyword item
 *
 * @package CONTENIDO API
 * @subpackage Model
 */
class cApiKeyword extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['keyword'], 'idkeyword');
        $this->setFilters(array(
            'addslashes'
        ), array(
            'stripslashes'
        ));
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

}
