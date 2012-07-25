<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Action log management class
 *
 * Requirements:
 * @con_php_req 5.0
 *
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
 *   created  2012-01-24
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


/**
 * Actionlog collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiActionlogCollection extends ItemCollection
{
    /**
     * Constructor
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg['tab']['actionlog'], 'idlog');
        $this->_setItemClass('cApiActionlog');
    }

    /**
     * Creates a actionlog item entry
     *
     * @param  string  $userId  User id
     * @param  int  $idclient
     * @param  int  $idlang
     * @param  int  $idaction
     * @param  int  $idcatart
     * @param  string  $logtimestamp
     * @return cApiActionlog
     */
    public function create($userId, $idclient, $idlang, $idaction, $idcatart, $logtimestamp = '')
    {
        $item = parent::createNewItem();

        if (empty($logtimestamp)) {
            $logtimestamp = date('Y-m-d H:i:s');
        }

        $item->set('user_id', $this->escape($userId));
        $item->set('idclient', (int) $idclient);
        $item->set('idlang', (int) $idlang);
        $item->set('idaction', (int) $idaction);
        $item->set('idcatart', (int) $idcatart);
        $item->set('logtimestamp', $this->escape($logtimestamp));

        $item->store();

        return $item;
    }
}


/**
 * Actionlog item
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiActionlog extends Item
{

    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['actionlog'], 'idlog');
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }
}

?>