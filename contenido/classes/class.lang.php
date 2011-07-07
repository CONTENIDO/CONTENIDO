<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * Class for language management and information
 *
 * Requirements:
 * @con_php_req 5.0
 *
 *
 * @package    Contenido Backend classes
 * @version    1.1
 * @author     Timo A. Hummel
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release <= 4.6
 * @todo       merge logic with contenido/classes/contenido/class.lang.php
 *
 * {@internal
 *   created  2003-05-20
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

/**
 * Class Language
 * Class for language collections
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright four for business 2003
 */
class Languages extends ItemCollection
{
    /**
     * Constructor
     * @param none
     */
    public function __construct()
    {
        global $cfg;
        parent::__construct($cfg["tab"]["lang"],"idlang");
        $this->_setItemClass("Language");
    }

    /** @deprecated  [2011-03-15] Old constructor function for downwards compatibility */
    public function Languages()
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct();
    }

    public function nextAccessible()
    {
        global $perm, $client, $cfg, $lang;

        $item = parent::next();

        $db = new DB_Contenido();
        $lang   = Contenido_Security::toInteger($lang);
        $client = Contenido_Security::toInteger($client);

        $sql = "SELECT idclient FROM ".$cfg["tab"]["clients_lang"]." WHERE idlang = '".$lang."'";
        $db->query($sql);

        if ($db->next_record()) {
            if ($client != $db->f("idclient")) {
                $item = $this->nextAccessible();
            }
        }

        if ($item) {
            if ($perm->have_perm_client("lang[".$item->get("idlang")."]") ||
                $perm->have_perm_client("admin[".$client."]") ||
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
 * Class Language
 * Class for a single language item
 * @author Timo A. Hummel <Timo.Hummel@4fb.de>
 * @version 0.1
 * @copyright four for business 2003
 */
class Language extends Item
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
    public function Language($mId = false)
    {
        cWarning(__FILE__, __LINE__, "Deprecated method call, use __construct()");
        $this->__construct($mId);
    }
}

?>