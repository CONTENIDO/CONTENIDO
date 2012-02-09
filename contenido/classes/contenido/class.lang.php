<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Language management class
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    CONTENIDO API
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


/**
 * Language collection
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiLanguageCollection extends ItemCollection
{
    /**
     * Constructor
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg['tab']['lang'], 'idlang');
        $this->_setItemClass('cApiLanguage');
        $this->_setJoinPartner('cApiClientLanguageCollection');
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiLanguageCollection()
    {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }

    /**
     * Creates a language entry.
     *
     * @global object $auth
     * @param string $name
     * @param int $active
     * @param string $encoding
     * @param string $direction
     * @return cApiLanguage
     */
    public function create($name, $active, $encoding, $direction)
    {
        global $auth;

        $item = parent::create();

        $item->set('name', $this->escape($name), false);
        $item->set('active', (int) $active, false);
        $item->set('encoding', $this->escape($encoding), false);
        $item->set('direction', $this->escape($direction), false);
        $item->set('author', $this->escape($auth->auth['uid']), false);
        $item->set('created', date('Y-m-d H:i:s'), false);
        $item->set('lastmodified', '0000-00-00 00:00:00', false);
        $item->store();

        return $item;
    }

    /**
     * Returns next accessible language for current client and current logged in user.
     *
     * @global object $perm
     * @global array $cfg
     * @global int $client
     * @global int $lang
     *
     * @return  cApiLanguage|null
     */
    public function nextAccessible()
    {
        global $perm, $cfg, $client, $lang;

        $item = parent::next();

        $lang = (int) $lang;
        $client = (int) $client;

        $clientsLanguageColl = new cApiClientLanguageCollection();
        $clientsLanguageColl->select('idlang = ' . $lang);
        if ($clientsLang = $clientsLanguageColl->next()) {
            if ($client != $clientsLang->get('idclient')) {
                $item = $this->nextAccessible();
            }
        }

        if ($item) {
            if ($perm->have_perm_client('lang[' . $item->get('idlang') . ']') ||
                $perm->have_perm_client('admin[' . $client . ']') ||
                $perm->have_perm_client()) {
                // Do nothing for now
            } else {
                $item = $this->nextAccessible();
            }

            return $item;
        } else {
            return false;
        }
    }

}


/**
 * Language item
 * @package    CONTENIDO API
 * @subpackage Model
 */
class cApiLanguage extends Item
{
    /**
     * Constructor Function
     * @param  mixed  $mId  Specifies the ID of item to load
     */
    public function __construct($mId = false)
    {
        global $cfg;
        parent::__construct($cfg['tab']['lang'], 'idlang');
        if ($mId !== false) {
            $this->loadByPrimaryKey($mId);
        }
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function cApiLanguage($mId = false)
    {
        cDeprecated("Use __construct() instead");
        $this->__construct($mId);
    }

    /**
     * Stores made changes.
     *
     * @return bool
     */
    public function store()
    {
        $this->set('lastmodified', date('Y-m-d H:i:s'), false);
        return parent::store();
    }

}


################################################################################
# Old versions of language item collection and language item classes
#
# NOTE: Class implemetations below are deprecated and the will be removed in
#       future versions of contenido.
#       Don't use them, they are still available due to downwards compatibility.


/**
 * Language collection
 * @deprecated  [2011-11-15] Use cApiLanguageCollection instead of this class.
 */
class Languages extends cApiLanguageCollection
{
    public function __construct()
    {
        cDeprecated("Use class cApiLanguageCollection instead");
        parent::__construct();
    }
    public function Languages()
    {
        cDeprecated("Use __construct() instead");
        $this->__construct();
    }
}


/**
 * Single language item
 * @deprecated  [2011-11-15] Use cApiLanguage instead of this class.
 */
class Language extends cApiLanguage
{
    public function __construct($mId = false)
    {
        cDeprecated("Use class cApiLanguage instead");
        parent::__construct($mId);
    }
    public function Language($mId = false)
    {
        cDeprecated("Use __construct() instead");
        $this->__construct($mId);
    }
}

?>