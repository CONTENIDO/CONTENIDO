<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Communication/Messaging system
 *
 * Code is taken over from file contenido/classes/class.communications.php in
 * favor of
 * normalizing API.
 *
 * @package CONTENIDO API
 * @version 0.1.1
 * @author Murat Purc <murat@purc.de>
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 * @since file available since CONTENIDO release 4.9.0
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * Communication collection
 *
 * @package CONTENIDO API
 * @subpackage Model
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
        $item = parent::createNewItem();

        $item->set('idclient', (int) $client);
        $item->set('author', $auth->auth['uid']);
        $item->set('created', date('Y-m-d H:i:s'), false);

        return $item;
    }

}

/**
 * Communication item
 *
 * @package CONTENIDO API
 * @subpackage Model
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

// ##############################################################################
// Old versions of communication item collection and communication item classes
//
// NOTE: Class implemetations below are deprecated and the will be removed in
// future versions of contenido.
// Don't use them, they are still available due to downwards compatibility.

/**
 * Communication item collection
 *
 * @deprecated [2011-09-19] Use instead of this class.
 */
class CommunicationCollection extends cApiCommunicationCollection {

    public function __construct() {
        cDeprecated("Use class cApiCommunicationCollection instead");
        parent::__construct();
    }

    public function CommunicationCollection() {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }

}

/**
 * Single communication item
 *
 * @deprecated [2011-09-19] Use instead of this class.
 */
class CommunicationItem extends cApiCommunication {

    public function __construct($mId = false) {
        cDeprecated("Use class cApiCommunication instead");
        parent::__construct($mId);
    }

    public function CommunicationItem($mId = false) {
        cDeprecated("Use __construct() instead");
        $this->__construct($mId);
    }

}
