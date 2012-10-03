<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * CONTENIDO daabase, session and authentication classes
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    CONTENIDO Core
 * @version    1.7.1
 * @author     Boris Erdmann, Kristian Koehntopp
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 *
 *
 * {@internal
 *   created  2000-01-01
 *   $Id$:
 * }}
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * @deprecated 2012-10-02 This class is not supported any longer.
 */
class DB_Contenido extends cDb {
    public function __construct($options = array()) {
        parent::__construct($options);
        cDeprecated("This class is not supported any longer. Use cDb instead.");
    }
}

/**
 * @deprecated 2012-09-06 This class is not supported any longer.
 */
class Contenido_CT_Sql {
    public function __construct() {
        cDeprecated("This class is not supported any longer.");
    }
}

/**
 * @deprecated 2012-09-06 This class is not supported any longer.
 */
class Contenido_CT_File {
    public function __construct() {
        cDeprecated("This class is not supported any longer.");
    }
}

/**
 * @deprecated 2012-09-06 This class is not supported any longer.
 */
class Contenido_CT_Session {
    public function __construct() {
        cDeprecated("This class is not supported any longer.");
    }
}

/**
 * @package    CONTENIDO Core
 * @subpackage Session
 * @deprecated This class was replaced by cSession. Please use that instead
 */
class Contenido_Session extends Session {

    public $classname = 'Contenido_Session';
    public $cookiename = 'contenido';        ## defaults to classname
    public $magic = '123Hocuspocus';    ## ID seed
    public $mode = 'get';              ## We propagate session IDs with cookies
    public $fallback_mode = 'cookie';
    public $lifetime = 0;                  ## 0 = do session cookies, else minutes
    public $that_class = 'Contenido_CT_Sql'; ## name of data storage container
    public $gc_probability = 5;

    public function __construct() {
        global $cfg;

        cDeprecated('This class was replaced by cSession. Please use it instead.');

        $sFallback = 'sql';
        $sClassPrefix = 'Contenido_CT_';

        $sStorageContainer = strtolower($cfg['session_container']);

        if (class_exists($sClassPrefix . ucfirst($sStorageContainer))) {
            $sClass = $sClassPrefix . ucfirst($sStorageContainer);
        } else {
            $sClass = $sClassPrefix . ucfirst($sFallback);
        }

        $this->that_class = $sClass;
    }

    public function delete() {
        $oCol = new cApiInUseCollection();
        $oCol->removeSessionMarks($this->id);
        parent::delete();
    }

}

/**
 * @package    CONTENIDO Core
 * @subpackage Session
 * @deprecated This class was replaced by cFrontendSession. Please use that instead
 */
class Contenido_Frontend_Session extends Session {

    public $classname = 'Contenido_Frontend_Session';
    public $cookiename = 'sid';              ## defaults to classname
    public $magic = 'Phillipip';        ## ID seed
    public $mode = 'cookie';           ## We propagate session IDs with cookies
    public $fallback_mode = 'cookie';
    public $lifetime = 0;                  ## 0 = do session cookies, else minutes
    public $that_class = 'Contenido_CT_Sql'; ## name of data storage container
    public $gc_probability = 5;

    public function __construct() {
        global $load_lang, $load_client, $cfg;

        cDeprecated('This class was replaced by cFrontendSession. Please use it instead.');

        $this->cookiename = 'sid_' . $load_client . '_' . $load_lang;

        $this->setExpires(time() + 3600);

        // added 2007-10-11, H. Librenz
        // bugfix (found by dodger77): we need alternative session containers
        //                             also in frontend
        $sFallback = 'sql';
        $sClassPrefix = 'Contenido_CT_';

        $sStorageContainer = strtolower($cfg['session_container']);

        if (class_exists($sClassPrefix . ucfirst($sStorageContainer))) {
            $sClass = $sClassPrefix . ucfirst($sStorageContainer);
        } else {
            $sClass = $sClassPrefix . ucfirst($sFallback);
        }

        $this->that_class = $sClass;
    }

}

/**
 * Registers an external auth handler
 */
function register_auth_handler($aHandlers) {
    cDeprecated("Registering auth handlers is not supported any longer.");
}

?>