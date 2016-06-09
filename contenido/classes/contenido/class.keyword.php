<?php

/**
 * This file contains the keyword collection and item class.
 *
 * @package          Core
 * @subpackage       GenericDB_Model
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
     * Constructor to create an instance of this class.
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['keywords'], 'idkeyword');
        $this->_setItemClass('cApiKeyword');
    }

    /**
     * @todo params w/ defaults should be relocated
     * @param string $keyword
     * @param string $exp [optional]
     * @param string $auto
     * @param string $self [optional]
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
     * Constructor to create an instance of this class.
     *
     * @param mixed $mId [optional]
     *         Specifies the ID of item to load
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
     * @param bool $bSafe [optional]
     *         Flag to run defined inFilter on passed value
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
