<?php
/**
 * This file contains the global authentication class.
 *
 * @package Core
 * @subpackage Authentication
 *
 * @author Dominik Ziegler
 * @copyright four for business AG <www.4fb.de>
 * @license http://www.contenido.org/license/LIZENZ.txt
 * @link http://www.4fb.de
 * @link http://www.contenido.org
 */

if (!defined('CON_FRAMEWORK')) {
    die('Illegal call: Missing framework initialization - request aborted.');
}

/**
 * This class contains functions for global authentication in CONTENIDO.
 *
 * @package Core
 * @subpackage Authentication
 */
class cAuth {

    const AUTH_UID_NOBODY = 'nobody';

    const AUTH_UID_FORM = 'form';

    /**
     * Lifetime for authenticated users in minutes.
     * After that time the authentication expires.
     *
     * @var integer
     */
    protected $_lifetime = 15;

    /**
     * The global auth information array.
     *
     * @var array
     */
    public $auth = array();

    /**
     * Automatic authentication as nobody.
     *
     * @var bool
     */
    protected $_defaultNobody = false;

    /**
     * The "in flag".
     * Nobody knows, for which reasons it exists.
     *
     * @var bool
     */
    private $_in = false;

    /**
     * Magic getter function for outdated variable names.
     *
     * @param string $name name of the variable
     * @return mixed
     */
    public function __get($name) {
        if ($name == 'lifetime') {
            return $this->_lifetime;
        }

        if ($name == 'persistent_slots') {
            return array(
                "auth"
            );
        }

        if ($name == 'classname') {
            return get_class($this);
        }
    }

    /**
     * Starts the authentication process.
     *
     * @return void
     */
    public function start() {
        $sess = cRegistry::getSession();
        if (!$this->_in) {
            $sess->register('auth');
            $this->_in = true;
        }

        if ($this->isAuthenticated()) {
            $authInfo = $this->getAuthInfo();
            $userId = $authInfo['uid'];
            if ($userId == self::AUTH_UID_FORM) {
                $userId = $this->validateCredentials();
                if ($userId !== false) {
                    $this->_setAuthInfo($userId);
                    $this->logSuccessfulAuth();
                } else {
                    $this->_fetchLoginForm();
                }
            } elseif ($userId != self::AUTH_UID_NOBODY) {
                $this->_setExpiration();
            }
        } else {
            $this->resetAuthInfo();
            if ($this->_defaultNobody == true) {
                $this->_setAuthInfo(self::AUTH_UID_NOBODY);
            } else {
                $this->_startLoginProcess();
            }
        }
    }

    /**
     * Restarts the authentication process.
     *
     * @return void
     */
    public function restart() {
        $this->resetAuthInfo();
        $this->_defaultNobody = false;
        $this->start();
    }

    /**
     * Resets the global authentication information.
     *
     * @param bool $nobody If flag set to true, the default authentication is
     *        switched to nobody. (optional, default: false)
     * @return void
     */
    public function resetAuthInfo($nobody = false) {
        $this->auth['uid'] = ($nobody == false? '' : self::AUTH_UID_NOBODY);
        $this->auth['perm'] = '';

        $this->_setExpiration($nobody == false? 0 : NULL);
    }

    /**
     * Logs out the current user, resets the auth information and freezes the
     * session.
     *
     * @param bool $nobody If flag set to true, nobody is recreated as user.
     * @return bool true
     */
    public function logout($nobody = false) {
        $sess = cRegistry::getSession();

        $sess->unregister('auth');
        unset($this->auth['uname']);

        $this->resetAuthInfo($nobody == false? $this->_defaultNobody : $nobody);
        $sess->freeze();

        return true;
    }

    /**
     * Getter for the auth information.
     *
     * @return array auth information
     */
    public function getAuthInfo() {
        return $this->auth;
    }

    /**
     * Checks, if user is authenticated (NOT logged in!).
     *
     * @return bool
     */
    public function isAuthenticated() {
        $authInfo = $this->getAuthInfo();

        if (isset($authInfo['uid']) && $authInfo['uid'] && (($this->_lifetime <= 0) || (time() < $authInfo['exp']))) {
            return $authInfo['uid'];
        } else {
            return false;
        }
    }

    /**
     * Checks, if user is currently in login form mode.
     *
     * @return bool
     */
    public function isLoginForm() {
        $authInfo = $this->getAuthInfo();
        return (isset($authInfo['uid']) && $authInfo['uid'] == self::AUTH_UID_FORM);
    }

    /**
     * Sets or refreshs the expiration of the authentication.
     *
     * @param int $expiration new expiration (optional, default: null = current
     *        time plus lifetime minutes)
     * @return void
     */
    protected function _setExpiration($expiration = NULL) {
        if ($expiration === NULL) {
            $expiration = time() + (60 * $this->_lifetime);
        }

        $this->auth['exp'] = $expiration;
    }

    /**
     * Sets the authentication info for a user.
     *
     * @param string $userId user ID to set
     * @return void
     */
    protected function _setAuthInfo($userId) {
        $this->auth['uid'] = $userId;
        $this->_setExpiration();
    }

    /**
     * Fetches the login form.
     *
     * @return void
     */
    protected function _fetchLoginForm() {
        $sess = cRegistry::getSession();

        $this->_setAuthInfo(self::AUTH_UID_FORM);
        $this->displayLoginForm();

        $sess->freeze();
        exit();
    }

    /**
     * Starts the login process by pre authorizing.
     *
     * @return void
     */
    protected function _startLoginProcess() {
        $userId = $this->preAuthorize();
        if ($userId !== false) {
            $this->_setAuthInfo($userId);
            return true;
        }

        $sess = cRegistry::getSession();

        $this->_fetchLoginForm();
    }

    /**
     *
     * @deprecated 2012-09-22
     */
    public function is_authenticated() {
        return $this->isAuthenticated();
    }

    /**
     *
     * @deprecated 2012-09-22
     */
    public function is_auth_form_uid() {
        return $this->isLoginForm();
    }

    /**
     *
     * @deprecated 2012-09-22
     */
    public function unauth($nobody = false) {
        return $this->resetAuthInfo($nobody);
    }

    /**
     *
     * @deprecated 2012-09-22
     */
    public function login_if($t) {
        if ($t) {
            $this->restart();
        }
    }

    /**
     *
     * @deprecated 2012-09-22
     */
    public function auth_preauth() {
        return $this->preAuthorize();
    }

    /**
     *
     * @deprecated 2012-09-22
     */
    public function auth_loginform() {
        return $this->displayLoginForm();
    }

    /**
     *
     * @deprecated 2012-09-22
     */
    public function auth_validatelogin() {
        return $this->validateCredentials();
    }

    /**
     *
     * @deprecated 2012-09-22
     */
    public function auth_loglogin() {
        return $this->logSuccessfulAuth();
    }

    /**
     *
     * @deprecated 2012-09-22
     */
    public function url() {
        $sess = cRegistry::getSession();
        return $sess->selfURL();
    }

    /**
     *
     * @deprecated 2012-09-22
     */
    public function purl() {
        $sess = cRegistry::getSession();
        print $sess->selfURL();
    }

}

/**
 *
 * @deprecated 2012-09-22
 */
class Contenido_Auth extends cAuth {

}

/**
 *
 * @deprecated 2012-09-22
 */
class Auth extends cAuth {

}