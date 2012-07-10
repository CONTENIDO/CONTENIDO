<?php
/**
 * Project:
 * CONTENIDO Content Management System
 *
 * Description:
 * Authentication management
 *
 * Requirements:
 * @con_php_req 5
 *
 *
 * @package    CONTENIDO Core
 * @version    1.0
 * @author     Boris Erdmann, Kristian Koehntopp, Massimiliano Masserelli
 * @copyright  four for business AG <www.4fb.de>
 * @license    http://www.contenido.org/license/LIZENZ.txt
 * @link       http://www.4fb.de
 * @link       http://www.contenido.org
 * @since      file available since CONTENIDO release <CONTENIDO Version>
 * @deprecated file deprecated in CONTENIDO release <CONTENIDO Version>
 *
 * {@internal
 *   created  2002-07-21
 *   modified 2008-07-03, bilal arslan, added security fix
 *   modified 2011-10-09, Murat Purc, Partly ported to PHP 5 and formatted/documented code
 *
 *   $Id$:
 * }}
 *
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call');
}

/**
 * @package    CONTENIDO Core
 * @subpackage Authentication
 */
class Auth {
    const AUTH_UID_NOBODY = 'nobody';
    const AUTH_UID_FORM = 'form';

    /**
     * Authentication handler class name
     * @var string
     */
    public $classname = 'Auth';

    /**
     * List of persistent slots
     * @var array
     */
    public $persistent_slots = array('auth');

    /**
     * Max allowed idle time before reauthentication is necessary. If set to 0,
     * auth never expires.
     * @var int
     */
    public $lifetime = 15;

    /**
     * Refresh interval in minutes. When expires auth data is refreshed from db
     * using auth_refreshlogin() method. Set to 0 to disable refresh
     * @var int
     */
    public $refresh = 0;

    /**
     * Used in uniqid() generation
     * @var string
     */
    public $magic = '';

    /**
     * If true, a default auth is created...
     * @var bool
     */
    public $nobody = false;

    /**
     * The name of a button that can be used to cancel a login form
     * @var string
     */
    public $cancel_login = 'cancel_login';

    // End of user qualifiable settings.

    /**
     * Data array
     * @var array
     */
    public $auth = array();

    /**
     * In flag
     * @var bool
     */
    public $in   = false;

    /**
     * Database instance
     * @var DB_Contenido
     */
    public $db;

    /**
     * Initialization
     */
    public function start() {
        $cl = $this->cancel_login;
        global $sess, $$cl;

        // This is for performance, I guess but I'm not sure if it could
        // be safely removed -- negro
        if (!$this->in) {
            $sess->register('auth');
            $this->in = true;
        }

        // back compatibility: if d_c is set, create db object
        if (isset($this->database_class)) {
            $class = $this->database_class;
            $this->db = new $class;
        }

        /*
         * Check current auth state. Should be one of
         * 1) Not logged in (no valid auth info or auth expired)
         * 2) Logged in (valid auth info)
         * 3) Login in progress (if $$cl, revert to state 1)
         */
        if ($this->is_authenticated()) {
            $uid = $this->auth['uid'];
            switch ($uid) {
                case self::AUTH_UID_FORM:
                    // Login in progress
                    if ($$cl) {
                        // If $$cl is set, delete all auth info and set state to 'Not logged in',
                        // so eventually default or automatic authentication may take place
                        $this->unauth();
                        $state = 1;
                    } else {
                        // Set state to 'Login in progress'
                        $state = 3;
                    }
                    break;
                default:
                    // User is authenticated and auth not expired
                    $state = 2;
                    break;
            }
        } else {
            // User is not (yet) authenticated
            $this->unauth();
            $state = 1;
        }

        switch ($state) {
            case 1:
                // No valid auth info or auth is expired

                // Check for user supplied automatic login procedure
                if ($uid = $this->auth_preauth()) {
                    $this->auth['uid'] = $uid;
                    $this->auth['exp'] = time() + (60 * $this->lifetime);
                    $this->auth['refresh'] = time() + (60 * $this->refresh);
                    return true;
                }

                if ($this->nobody) {
                    // Authenticate as nobody
                    $this->auth['uid'] = self::AUTH_UID_NOBODY;
                    // $this->auth['uname'] = self::AUTH_UID_NOBODY;
                    $this->auth['exp'] = 0x7fffffff;
                    $this->auth['refresh'] = 0x7fffffff;
                    return true;
                } else {
                    // Show the login form
                    $this->auth_loginform();
                    $this->auth['uid'] = self::AUTH_UID_FORM;
                    $this->auth['exp'] = 0x7fffffff;
                    $this->auth['refresh'] = 0x7fffffff;
                    $sess->freeze();
                    exit;
                }
                break;
            case 2:
                // Valid auth info
                // Refresh expire info
                // DEFAULT handling: do not update exp for nobody.
                if ($uid != self::AUTH_UID_NOBODY) {
                    $this->auth['exp'] = time() + (60 * $this->lifetime);
                }
                break;
            case 3:
                // Login in progress, check results and act accordingly
                /*
                 *    if ($uid = $this->auth_preauth()) {
                 *      $this->auth['uid'] = $uid;
                 *        $this->auth['exp'] = time() + (60 * $this->lifetime);
                 *        $this->auth['refresh'] = time() + (60 * $this->refresh);
                 *        return true;
                 *    }
                 */

                if ($uid = $this->auth_validatelogin()) {
                    $this->auth['uid'] = $uid;
                    $this->auth['exp'] = time() + (60 * $this->lifetime);
                    $this->auth['refresh'] = time() + (60 * $this->refresh);
                    return true;
                } else {
                    $this->auth_loginform();
                    $this->auth['uid'] = self::AUTH_UID_FORM;
                    $this->auth['exp'] = 0x7fffffff;
                    $this->auth['refresh'] = 0x7fffffff;
                    $sess->freeze();
                    exit;
                }
                break;
            default:
                // This should never happen. Complain.
                echo "Error in auth handling: invalid state reached.\n";
                $sess->freeze();
                exit;
                break;
        }
    }

    public function login_if($t) {
        if ($t) {
            $this->unauth();  // We have to relogin, so clear current auth info
            $this->nobody = false; // We are forcing login, so default auth is disabled
            $this->start(); // Call authentication code
        }
    }

    public function unauth($nobody = false) {
        $this->auth['uid']  = '';
        $this->auth['perm'] = '';
        $this->auth['exp']  = 0;

        // Back compatibility: passing $nobody to this method is deprecated
        if ($nobody) {
            $this->auth['uid']  = self::AUTH_UID_NOBODY;
            $this->auth['perm'] = '';
            $this->auth['exp']  = 0x7fffffff;
        }
    }

    public function logout($nobody = '') {
        global $sess;
        $sess->unregister('auth');
        unset($this->auth['uname']);
        $this->unauth($nobody == '' ? $this->nobody : $nobody);
        $sess->freeze();
        return true;
    }

    public function is_authenticated(){
        if (isset($this->auth['uid']) && $this->auth['uid']
            && (($this->lifetime <= 0) || (time() < $this->auth['exp']))) {
            // If more than $this->refresh minutes are passed since last check,
            // perform auth data refreshing. Refresh is only done when current
            // session is valid (registered, not expired).
            if (($this->refresh > 0) && ($this->auth['refresh'])
                && ($this->auth['refresh'] < time())) {
                if ($this->auth_refreshlogin()) {
                    $this->auth['refresh'] = time() + (60 * $this->refresh);
                } else {
                    return false;
                }
            }
            return $this->auth['uid'];
        } else {
            return false;
        }
    }

    public function is_auth_nobody_uid() {
        return (isset($this->auth['uid']) && $this->auth['uid'] == self::AUTH_UID_NOBODY);
    }

    public function is_auth_form_uid() {
        return (isset($this->auth['uid']) && $this->auth['uid'] == self::AUTH_UID_FORM);
    }

    // Helper functions
    public function url() {
        return $GLOBALS['sess']->selfURL();
    }

    public function purl(){
        print $GLOBALS['sess']->selfURL();
    }

    /* This method can authenticate a user before the loginform
     * is being displayed. If it does, it must set a valid uid
     * (i.e. nobody IS NOT a valid uid) just like auth_validatelogin,
     * else it shall return false.
     */
    public function auth_preauth() {
        return false;
    }

    // Authentication dummies. Must be overridden by user.
    public function auth_loginform() {
        // donut
    }

    public function auth_validatelogin() {
        // donut
    }

    public function auth_refreshlogin() {
        // donut
    }

    public function auth_registerform() {
        // donut
    }

    public function auth_doregister() {
        // donut
    }
}

?>