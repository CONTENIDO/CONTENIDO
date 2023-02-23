<?php

/**
 * This file contains the keyword collection and item class.
 *
 * @package          Core
 * @subpackage       GenericDB_Model
 * @author           Frederic Schneider
 * @copyright        four for business AG <www.4fb.de>
 * @license          https://www.contenido.org/license/LIZENZ.txt
 * @link             https://www.4fb.de
 * @link             https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Keyword collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 * @method cApiKeyword createNewItem
 * @method cApiKeyword|bool next
 */
class cApiKeywordCollection extends ItemCollection {
    /**
     * Constructor to create an instance of this class.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        parent::__construct(cRegistry::getDbTableName('keywords'), 'idkeyword');
        $this->_setItemClass('cApiKeyword');
    }

    /**
     * @todo params w/ defaults should be relocated
     *
     * @param string $keyword
     * @param string $exp  [optional]
     * @param string $auto
     * @param string $self [optional]
     * @param int    $idlang
     *
     * @return cApiKeyword
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
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
class cApiKeyword extends Item
{
    /**
     * Constructor to create an instance of this class.
     *
     * @param mixed $mId [optional]
     *                   Specifies the ID of item to load
     *
     * @throws cDbException
     * @throws cException
     */
    public function __construct($mId = false) {
        parent::__construct(cRegistry::getDbTableName('keywords'), 'idkeyword');
        $this->setFilters(['addslashes'], ['stripslashes']);
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * User-defined setter for keyword fields.
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
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $bSafe);
    }

}
