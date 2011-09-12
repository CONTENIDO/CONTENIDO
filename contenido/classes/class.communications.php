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
 *
 * @package    CONTENIDO Backend classes
 * @version    1.1
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <= 4.6
 *
 * {@internal
 *   created  unknown
 *   modified 2008-06-30, Dominik Ziegler, add security fix
 *   modified 2011-03-14, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class CommunicationCollection extends ItemCollection
{
    /**
     * Constructor Function
     * @param none
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg["tab"]["communications"], "idcommunication");
        $this->_setItemClass("CommunicationItem");
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function CommunicationCollection()
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct();
    }

    /**
     * Creates a new communication item
     */
    public function create()
    {
        global $auth, $client;
        $item = parent::create();

        $client = Contenido_Security::toInteger($client);

        $item->set("idclient", $client);
        $item->set("author", $auth->auth["uid"]);
        $item->set("created", date("Y-m-d H:i:s"), false);

        return $item;
    }
}


/**
 * Single CommunicationItem Item
 */
class CommunicationItem extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg["tab"]["communications"], "idcommunication");
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function CommunicationItem($mId = false)
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct($mId);
    }

    function store()
    {
        global $auth;
        $this->set("modifiedby", $auth->auth["uid"]);
        $this->set("modified", date("Y-m-d H:i:s"), false);

        parent::store();
    }
}

?>