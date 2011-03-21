<?php
/**
 * Project:
 * Contenido Content Management System
 *
 * Description:
 * PHPLIB Data storage container using PHPs session feature.
 * Session data will persist in superglobal $_SESSION. 
 *
 * Requirements:
 * @con_php_req 5
 *
 * @package    Contenido Backend Session
 * @version    0.1
 * @author     Murat Purc <murat@purc.de>
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since contenido release 4.8.15
 *
 * {@internal
 *   created  2011-03-18
 *
 *   $Id: $:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}


class CT_Session
{
    /**
     * Namespace in superglobal $_SESSION
     * @var  string
     */
    protected $_sNameSpace = 'ct_session_ns';

    /**
     * Session container's intialize method, configures PHP session and starts
     * it, if not done before.
     *
     * @param  array  $aOptions  Session container configuration as follows:
     *                           - $aOptions['namespace']  (string)  Namespace to use
     *                           - $aOptions['session.*']  (string)  Each possible session configuration
     *                                                               see php.ini
     */
    public function ac_start(array $aOptions = array())
    {
        // set namespace
        if (isset($aOptions['namespace'])) {
            $this->_sNameSpace = $aOptions['namespace'];
        }

        // configure session
        foreach ($aOptions as $k => $v) {
            if (strpos($k, 'session.') === 0) {
                @ini_set($k, $v);
            }
        }

        if (!isset($_SESSION)) {
            session_start();
        }
        
        if (!isset($_SESSION[$this->_sNameSpace])) {
            $_SESSION[$this->_sNameSpace] = array();
        }
    }

    public function ac_get_lock()
    {
        // no need to lock session
    }

    public function ac_release_lock()
    {
        // no need to release lock session
    }

    public function ac_gc($gc_time, $name)
    {
        // no need for garbace collection, will be done by PHP's gc
    }

    public function ac_store($id, $name, $str)
    {
        $_SESSION[$this->_sNameSpace][$name] = $str;
        return true;
    }

    public function ac_get_value($id, $name)
    {
        return isset($_SESSION[$this->_sNameSpace][$name]) ? $_SESSION[$this->_sNameSpace][$name] : '';
    }

    public function ac_delete($id, $name)
    {
        // don't destroy session, untill backend and frontend uses it
        #session_destroy();
        if (isset($_SESSION[$this->_sNameSpace][$name])) {
            $_SESSION[$this->_sNameSpace][$name] = '';
        }
    }

    public function ac_newid($str, $name)
    {
        session_regenerate_id(false);
        return session_id();
    }

    public function ac_halt($s)
    {
        die($s);
    }
}

?>