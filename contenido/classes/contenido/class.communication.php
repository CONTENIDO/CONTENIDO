<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Communication/Messaging system
 *
 * Requirements:
 * @con_php_req 5.0
 *
 * Code is taken over from file contenido/classes/class.communications.php in favor of
 * normalizing API.
 *
 * @package    CONTENIDO API
 * @version    0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release 4.9.0
 *
 * {@internal
 *   created  2011-09-14
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * Communication collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiCommunicationCollection extends ItemCollection
{
    /**
     * Constructor Function
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg['tab']['communications'], 'idcommunication');
        $this->_setItemClass('cApiCommunication');
    }

    /**
     * Creates a new communication item
     * @return cApiCommunication
     */
    public function create()
    {
        global $auth, $client;
        $item = parent::create();

        $client = Contenido_Security::toInteger($client);

        $item->set('idclient', $client);
        $item->set('author', $auth->auth['uid']);
        $item->set('created', date('Y-m-d H:i:s'), false);

        return $item;
    }
}


/**
 * Communication item
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiCommunication extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['communications'], 'idcommunication');
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /**
     * Saves a communication item
     */
    public function store()
    {
        global $auth;
        $this->set('modifiedby', $auth->auth['uid']);
        $this->set('modified', date('Y-m-d H:i:s'), false);

        return parent::store();
    }
}


################################################################################
# Old versions of communication item collection and communication item classes
#
# NOTE: Class implemetations below are deprecated and the will be removed in 
#       future versions of contenido.
#       Don't use them, they are still available due to downwards compatibility.


/**
 * Communication item collection
 * @deprecated  [2011-09-19] Use cApiCommunicationCollection instead of this class.
 */
class CommunicationCollection extends cApiCommunicationCollection
{
    public function __construct()
    {
        cWarning(__FILE__, __LINE__, 'Deprecated class ' . __CLASS__ . ' use ' . get_parent_class($this));
        parent::__construct();
    }
    public function CommunicationCollection()
    {
        cWarning(__FILE__, __LINE__, 'Deprecated method call, use __construct()');
        $this->__construct();
    }
}


/**
 * Single communication item
 * @deprecated  [2011-09-19] Use cApiCommunication instead of this class.
 */
class CommunicationItem extends cApiCommunication
{
    public function __construct($mId = false)
    {
        cWarning(__FILE__, __LINE__, 'Deprecated class ' . __CLASS__ . ' use ' . get_parent_class($this));
        parent::__construct($mId);
    }
    public function CommunicationItem($mId = false)
    {
        cWarning(__FILE__, __LINE__, 'Deprecated method call, use __construct()');
        $this->__construct($mId);
    }
}

?>