<?php

/**
 * This file contains the actionlog collection and item class.
 *
 * @package          Core
 * @subpackage       GenericDB_Model
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          https://www.contenido.org/license/LIZENZ.txt
 * @link             https://www.4fb.de
 * @link             https://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Actionlog collection.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 * @method cApiActionlog createNewItem
 * @method cApiActionlog|bool next
 */
class cApiActionlogCollection extends ItemCollection {

    /**
     * Constructor to create an instance of this class.
     *
     * Tables user, client, language, action & category_article
     * are allowed as join partners.
     *
     * @throws cInvalidArgumentException
     */
    public function __construct() {
        parent::__construct(cRegistry::getDbTableName('actionlog'), 'idlog');
        $this->_setItemClass('cApiActionlog');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiUserCollection');
        $this->_setJoinPartner('cApiClientCollection');
        $this->_setJoinPartner('cApiLanguageCollection');
        $this->_setJoinPartner('cApiActionCollection');
        $this->_setJoinPartner('cApiCategoryArticleCollection');
    }

    /**
     * Creates an actionlog item.
     *
     * @param string $userId
     *                             User id
     * @param int    $idclient
     * @param int    $idlang
     * @param int    $idaction
     * @param int    $idcatart
     * @param string $logtimestamp [optional]
     *
     * @return cApiActionlog
     *
     * @throws cDbException
     * @throws cException
     * @throws cInvalidArgumentException
     */
    public function create($userId, $idclient, $idlang, $idaction, $idcatart, $logtimestamp = '') {
        $item = $this->createNewItem();

        if (empty($logtimestamp)) {
            $logtimestamp = date('Y-m-d H:i:s');
        }

        $item->set('user_id', $userId);
        $item->set('idclient', $idclient);
        $item->set('idlang', $idlang);
        $item->set('idaction', $idaction);
        $item->set('idcatart', $idcatart);
        $item->set('logtimestamp', $logtimestamp);

        $item->store();

        return $item;
    }

}

/**
 * Actionlog item.
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiActionlog extends Item
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
        parent::__construct(cRegistry::getDbTableName('actionlog'), 'idlog');
        $this->setFilters([], []);
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * User-defined setter for action log fields.
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
            case 'idaction':
            case 'idcatart':
            case 'idclient':
                $value = cSecurity::toInteger($value);
                break;
        }

        return parent::setField($name, $value, $bSafe);
    }

}
