<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Language management class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend classes
 * @version    1.5
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 * {@internal
 *   created  2007-05-25
 *   modified 2011-03-15, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class cApiLanguageCollection extends ItemCollection
{
    /**
     * Constructor
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg["tab"]["lang"], "idlang");
        $this->_setItemClass("cApiLanguage");
        $this->_setJoinPartner("cApiClientLanguageCollection");
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiLanguageCollection()
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct();
    }
}


class cApiLanguage extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg["tab"]["lang"], "idlang");
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiLanguage($mId = false)
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct($mId);
    }
}

?>