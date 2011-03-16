<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Area management class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend classes
 * @version    1.5
 * @author     Timo Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2004-08-04
 *   modified 2008-06-27, Frederic Schneider, add security fix
 *   modified 2011-03-15, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class cApiTemplateCollection extends ItemCollection
{
    public function __construct($select = false)
    {
        global $cfg;
        parent::__construct($cfg["tab"]["tpl"], "idtpl");
        $this->_setItemClass("cApiTemplate");
        if ($select !== false) {
            $this->select($select);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiTemplateCollection($select = false)
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct($select);
    }

    public function setDefaultTemplate($idtpl)
    {
        global $cfg, $client;

        $db = new DB_Contenido();
        $sql = "UPDATE ".$cfg["tab"]["tpl"]." SET defaulttemplate = 0 WHERE idclient = '" . Contenido_Security::toInteger($client) . "'";
        $db->query($sql);

        $sql = "UPDATE ".$cfg["tab"]["tpl"]." SET defaulttemplate = 1 WHERE idtpl = '" . Contenido_Security::toInteger($idtpl) . "'";
        $db->query($sql);
    }
}


class cApiTemplate extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg["tab"]["tpl"], "idtpl");
        $this->setFilters(array(), array());
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiTemplate($mId = false)
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct($mId);
    }
}

?>