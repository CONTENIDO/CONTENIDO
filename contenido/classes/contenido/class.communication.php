<?php
/**
 * This file contains the communication collection and item class.
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
 * Communication collection
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiCommunicationCollection extends ItemCollection {

    /**
     * Constructor Function
     */
    public function __construct() {
        global $cfg;
        parent::__construct($cfg['tab']['communications'], 'idcommunication');
        $this->_setItemClass('cApiCommunication');

        // set the join partners so that joins can be used via link() method
        $this->_setJoinPartner('cApiClientCollection');
    }

    /**
     * Creates a new communication item.
     *
     * @return cApiCommunication
     */
    public function create() {
        global $auth, $client;
        $item = $this->createNewItem();

        $item->set('idclient', (int) $client);
        $item->set('author', $auth->auth['uid']);
        $item->set('created', date('Y-m-d H:i:s'), false);

        return $item;
    }

}

/**
 * Communication item
 *
 * @package Core
 * @subpackage GenericDB_Model
 */
class cApiCommunication extends Item {

    /**
     * Constructor Function
     *
     * @param mixed $mId Specifies the ID of item to load
     */
    public function __construct($mId = false) {
        global $cfg;
        parent::__construct($cfg['tab']['communications'], 'idcommunication');
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Saves a communication item
     */
    public function store() {
        global $auth;
        $this->set('modifiedby', $auth->auth['uid']);
        $this->set('modified', date('Y-m-d H:i:s'), false);

        return parent::store();
    }

}
