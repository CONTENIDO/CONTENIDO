<?php
/**
 * This file contains the actionlog collection and item class.
 *
 * @package          Core
 * @subpackage       GenericDB_Model
 * @version          SVN Revision $Rev:$
 *
 * @author           Murat Purc <murat@purc.de>
 * @copyright        four for business AG <www.4fb.de>
 * @license          http://www.contenido.org/license/LIZENZ.txt
 * @link             http://www.4fb.de
 * @link             http://www.contenido.org
 */

defined('CON_FRAMEWORK') || die('Illegal call: Missing framework initialization - request aborted.');

/**
 * Actionlog collection
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiActionlogCollection extends ItemCollection {

    /**
     * Constructor
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['actionlog'], 'idlog');
        $this->_setItemClass('cApiActionlog');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiUserCollection');
        $this->_setJoinPartner('cApiClientCollection');
        $this->_setJoinPartner('cApiLanguageCollection');
        $this->_setJoinPartner('cApiActionCollection');
        $this->_setJoinPartner('cApiCategoryArticleCollection');
    }

    /**
     * Creates a actionlog item entry
     *
     * @param string $userId User id
     * @param int    $idclient
     * @param int    $idlang
     * @param int    $idaction
     * @param int    $idcatart
     * @param string $logtimestamp
     *
     * @return cApiActionlog
     */
    public function create($userId, $idclient, $idlang, $idaction, $idcatart, $logtimestamp = '') {
        $item = $this->createNewItem();

        if (empty($logtimestamp)) {
            $logtimestamp = date('Y-m-d H:i:s');
        }

        $item->set('user_id', $this->escape($userId));
        $item->set('idclient', (int)$idclient);
        $item->set('idlang', (int)$idlang);
        $item->set('idaction', (int)$idaction);
        $item->set('idcatart', (int)$idcatart);
        $item->set('logtimestamp', $this->escape($logtimestamp));

        $item->store();

        return $item;
    }

}

/**
 * Actionlog item
 *
 * @package    Core
 * @subpackage GenericDB_Model
 */
class cApiActionlog extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['actionlog'], 'idlog');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }
}
