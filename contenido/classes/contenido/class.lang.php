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
 * @version    1.5.1
 * @author     Bjoern Behrens
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @todo       merge logic with contenido/classes/class.lang.php
 *
 * {@internal
 *   created  2007-05-25
 *   modified 2011-03-15, Murat Purc, adapted to new GenericDB, partly ported to PHP 5, formatting
 *   modified 2011-07-07, Murat Purc, added functions cApiLanguageCollection::create() and cApiLanguage::store()
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

    /**
     * Creates a language entry.
     * @global object $auth
     * @param string $sName
     * @param int $iActive
     * @param string $sEncoding
     * @param string $sDirection
     * @return cApiLanguage
     */
    public function create($sName, $iActive, $sEncoding, $sDirection)
    {
        global $auth;

        $oItem = parent::create();

        $oItem->set('name', $this->escape($sName), false);
        $oItem->set('active', (int) $iActive, false);
        $oItem->set('encoding', $this->escape($sEncoding), false);
        $oItem->set('direction', $this->escape($sDirection), false);
        $oItem->set('author', $this->escape($auth->auth['uid']), false);
        $oItem->set('created', date('Y-m-d H:i:s'), false);
        $oItem->set('lastmodified', '0000-00-00 00:00:00', false);
        $oItem->store();

        return $oItem;
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

    /**
     * Stores made changes
     * @return bool
     */
    public function store()
    {
        $this->set('lastmodified', date('Y-m-d H:i:s'), false);
        return parent::store();
    }

}

?>