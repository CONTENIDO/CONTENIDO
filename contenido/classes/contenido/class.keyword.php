<?php
/**
 * This file contains the keyword collection and item class.
 *
 * @package          Core
 * @subpackage       GenericDB_Model
 * @version          SVN Revision $Rev:$
 *
 * @author           Frederic Schneider
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Keyword collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiKeywordCollection extends ItemCollection {

    /**
     * Constructor
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['keywords'], 'idkeyword');
        $this->_setItemClass('cApiKeyword');
    }

    /**
     *
     * @param string $keyword
     * @param string $exp
     * @param string $auto
     * @param string $self
     * @param int $idlang
     * @return cApiKeyword
     */
    public function create($keyword, $exp = '', $auto, $self = '', $idlang) {
        $item = $this->createNewItem();

        $item->set('keyword', $keyword);
        $item->set('exp', $exp);
        $item->set('auto', $auto);
        $item->set('self', $self);
        $item->set('idlang', $idlang);

        $item->store();

        return $item;
    }

}

/**
 * Keyword item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiKeyword extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['keywords'], 'idkeyword');
        $this->setFilters(array(
            'addslashes'
        ), array(
            'stripslashes'
        ));
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Userdefined setter for keyword fields.
     *
     * @param string $name
     * @param mixed $value
     * @param bool $bSafe Flag to run defined inFilter on passed value
     * @return bool
     */
    public function setField($name, $value, $bSafe = true) {
        switch ($name) {
            case 'idlang':
                $value = (int) $value;
                break;
        }

        return parent::setField($name, $value, $bSafe);
    }

}
